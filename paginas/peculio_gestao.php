<?php
session_start();
ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    die('Acesso negado. Usuário não autenticado.');
}

// Verificar permissão do setor Laboral (usando validação padronizada)
$hasLaboralPermission = (isset($_SESSION['perm_laboral']) && (int) $_SESSION['perm_laboral'] > 0)
    || (!empty($_SESSION['user_admin']));

if (! $hasLaboralPermission) {
    die('Acesso negado. Sem permissão para acessar este módulo.');
}

try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // -------------------------------------------------------------------------
    // 1. PROCESSAMENTO DE API (POST E GET AJAX)
    // -------------------------------------------------------------------------

    // Busca dados para preencher o Modal de CHECK
    if (isset($_GET['fetch_pedido'])) {
        ob_clean();
        header('Content-Type: application/json');
        $id = (int)$_GET['fetch_pedido'];
        $base = $pdo->query("SELECT * FROM peculio_controle WHERE id = $id")->fetch();
        $itens = $pdo->prepare("SELECT pci.*, pi.nome FROM peculio_controle_itens pci JOIN peculio_itens pi ON pci.id_item = pi.id WHERE id_controle = ?");
        $itens->execute([$id]);
        echo json_encode(['base' => $base, 'itens' => $itens->fetchAll()]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
        ob_clean();
        header('Content-Type: application/json');
        try {
            // ROTA EXTRATO: Histórico de Entradas (Saldos) e Saídas (Pedidos)
            if ($_POST['db_action'] === 'get_extrato') {
                $ipen = $_POST['ipen'];
                $mes = $_POST['mes'];
                $sql = "SELECT 'DEPÓSITO PIX' as tipo, valor, '$mes' as data_ref, 'Crédito' as movimento FROM peculio_saldos_pix WHERE ipen=? AND mes_referencia=?
                        UNION ALL
                        SELECT 'DEPÓSITO SALÁRIO' as tipo, valor, '$mes' as data_ref, 'Crédito' as movimento FROM peculio_saldos_trabalho WHERE ipen=? AND mes_referencia=?
                        UNION ALL
                        SELECT CONCAT('PEDIDO #', id) as tipo, valor_total as valor, data_geracao as data_ref, 'Débito' as movimento FROM peculio_controle WHERE ipen=? AND mes_referencia=?
                        ORDER BY movimento DESC";
                $st = $pdo->prepare($sql);
                $st->execute([$ipen, $mes, $ipen, $mes, $ipen, $mes]);
                echo json_encode(['success' => true, 'historico' => $st->fetchAll()]);
                exit;
            }

            // Calcula saldo disponível descontando pedidos já feitos
            if ($_POST['db_action'] === 'get_saldo_interno') {
                $mes = $_POST['mes'];
                $ipen = $_POST['ipen'];
                $si = $pdo->prepare("SELECT forma_pagamento FROM internos WHERE ipen = ?");
                $si->execute([$ipen]);
                $interno = $si->fetch();
                $tabela = ($interno['forma_pagamento'] == 'Pix') ? 'peculio_saldos_pix' : 'peculio_saldos_trabalho';

                $sql = "SELECT (IFNULL((SELECT valor FROM $tabela WHERE ipen = ? AND mes_referencia = ?), 0) -
                                IFNULL((SELECT SUM(valor_total) FROM peculio_controle WHERE ipen = ? AND mes_referencia = ?), 0))";
                $st_s = $pdo->prepare($sql);
                $st_s->execute([$ipen, $mes, $ipen, $mes]);
                echo json_encode(['saldo' => (float)$st_s->fetchColumn(), 'tipo' => $interno['forma_pagamento']]);
                exit;
            }

            if ($_POST['db_action'] === 'search_internos') {
                $term = "%" . $_POST['term'] . "%";
                $st = $pdo->prepare("SELECT ipen, nome, nome_social, galeria, bloco, res, forma_pagamento FROM internos WHERE (nome LIKE ? OR ipen LIKE ?) LIMIT 8");
                $st->execute([$term, $term]);
                echo json_encode($st->fetchAll());
                exit;
            }

            if ($_POST['db_action'] === 'lancar_pedido') {
                $pdo->beginTransaction();
                $si = $pdo->prepare("SELECT nome, galeria, bloco, res FROM internos WHERE ipen = ?");
                $si->execute([$_POST['p_ipen']]);
                $d = $si->fetch();
                $loc = "{$d['galeria']}{$d['bloco']}-{$d['res']}";
                $pdo->prepare("INSERT INTO peculio_controle (ipen, nome_na_epoca, local_na_epoca, mes_referencia, valor_total) VALUES (?,?,?,?,?)")->execute([$_POST['p_ipen'], $d['nome'], $loc, $_POST['p_mes'], $_POST['p_valor_total']]);
                $pid = $pdo->lastInsertId();
                if (isset($_POST['item_id'])) {
                    $ins = $pdo->prepare("INSERT INTO peculio_controle_itens (id_controle, id_item, quantidade) VALUES (?,?,?)");
                    foreach ($_POST['item_id'] as $idx => $itid) {
                        if ($_POST['item_qnt'][$idx] > 0) $ins->execute([$pid, $itid, $_POST['item_qnt'][$idx]]);
                    }
                }
                $pdo->commit();
                echo json_encode(['success' => true]);
                exit;
            }

            if ($_POST['db_action'] === 'finalizar_entrega') {
                $e_id = $_POST['e_id'];
                $st_t = $_POST['st_entrega'];
                $err = false;
                $log = "";
                $pdo->beginTransaction();
                if (isset($_POST['c_it_id'])) {
                    $upd = $pdo->prepare("UPDATE peculio_controle_itens SET qtd_faltante=?, status_item=? WHERE id_controle=? AND id_item=?");
                    foreach ($_POST['c_it_id'] as $idx => $itid) {
                        $qf = (int)$_POST['qf'][$idx];
                        if ($qf > 0) {
                            $err = true;
                            $log .= "{$qf}x {$_POST['c_it_nome'][$idx]}; ";
                        }
                        $upd->execute([$qf, ($qf > 0 ? 'Faltante' : 'OK'), $e_id, $itid]);
                    }
                }
                $pdo->prepare("UPDATE peculio_controle SET status_entrega=?, incidencia=?, observacao=?, data_entrega=NOW() WHERE id=?")->execute([($st_t === 'Entregue' && !$err ? 'Entregue' : 'Pendente'), ($st_t === 'Não Entregue' ? $_POST['incidencia'] : 'Nenhuma'), $_POST['obs'], $e_id]);
                $pdo->commit();
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
            exit;
        }
    }

    // Geração de Impressão (PHP)
    if (isset($_GET['execute_print'])) {
        ob_end_clean();
        $tipo = $_GET['execute_print'];
        $mesRef = $_GET['p_m'];
        $sql = "SELECT pc.*, i.galeria, i.bloco, i.res, i.forma_pagamento, IFNULL(NULLIF(i.nome_social, ''), i.nome) as nome_f FROM peculio_controle pc JOIN internos i ON pc.ipen = i.ipen WHERE pc.mes_referencia = '$mesRef'";
        if (!empty($_GET['p_g'])) $sql .= " AND i.galeria = '{$_GET['p_g']}'";
        $data_p = $pdo->query($sql . " ORDER BY i.galeria, CAST(i.res AS UNSIGNED), nome_f")->fetchAll();
?>
        <!DOCTYPE html>
        <html>

        <head>
            <style>
                @page {
                    size: A4 landscape;
                    margin: 0.8cm;
                }

                body {
                    font-family: sans-serif;
                    font-size: 9pt;
                    text-transform: uppercase;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                th,
                td {
                    border: 1.5px solid #000;
                    padding: 5px;
                }

                th {
                    background: #eee;
                }

                .h {
                    text-align: center;
                    font-size: 16pt;
                    font-weight: bold;
                    border: 3px solid #000;
                    padding: 10px;
                    margin-bottom: 10px;
                }
            </style>
        </head>

        <body onload="window.print()">
            <div class="h"><?= ($tipo == 'termo' ? 'TERMO DE RECEBIMENTO' : 'RELATÓRIO DE FALTAS') ?> - <?= $mesRef ?></div>
            <table>
                <thead>
                    <tr align="center">
                        <th>IPEN</th>
                        <th>NOME</th>
                        <th>CELA</th>
                        <th>PG</th>
                        <th>VALOR</th>
                        <th>ASSINATURA / FALTAS</th>
                    </tr>
                </thead>
                <tbody><?php foreach ($data_p as $r) echo "<tr><td align='center'>{$r['ipen']}</td><td>{$r['nome_f']}</td><td align='center'>{$r['galeria']}{$r['bloco']}-{$r['res']}</td><td align='center'>{$r['forma_pagamento']}</td><td align='center'>R$" . number_format($r['valor_total'], 2, ',', '.') . "</td><td></td></tr>"; ?></tbody>
            </table>
        </body>

        </html><?php exit;
            }
        } catch (Exception $e) {
            die("Erro de Conexão: " . $e->getMessage());
        }

        // Funções Helpers para a interface
        function formatMes($str)
        {
            $ms = ["", "JANEIRO", "FEVEREIRO", "MARÇO", "ABRIL", "MAIO", "JUNHO", "JULHO", "AGOSTO", "SETEMBRO", "OUTUBRO", "NOVEMBRO", "DEZEMBRO"];
            $p = explode('/', $str);
            return (isset($p[1])) ? $ms[(int)$p[0]] . " / " . $p[1] : $str;
        }
        function listMonths()
        {
            $out = "";
            $y = date('Y');
            for ($a = $y - 1; $a <= $y + 1; $a++) {
                for ($m = 1; $m <= 12; $m++) {
                    $v = str_pad($m, 2, '0', STR_PAD_LEFT) . "/$a";
                    $s = ($v == date('m/Y')) ? "selected" : "";
                    $out .= "<option value='$v' $s>" . formatMes($v) . "</option>";
                }
            }
            return $out;
        }

        $filt = ['s' => $_GET['s'] ?? '', 'g' => $_GET['g'] ?? '', 'm' => $_GET['m'] ?? date('m/Y'), 'e' => isset($_GET['e'])];
        $optsG = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE galeria IS NOT NULL ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
                ?>

<!-- -------------------------------------------------------------------------
     INTERFACE HTML (SIGEP STYLE)
     ------------------------------------------------------------------------- -->
<style>
    .search-results {
        position: absolute;
        z-index: 1060;
        width: 100%;
        background: #fff;
        border: 1px solid #ddd;
        display: none;
        max-height: 200px;
        overflow-y: auto;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        color: #333;
    }

    .search-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        font-weight: bold;
    }

    .search-item:hover {
        background: #f8f9fa;
        color: #007bff;
    }
</style>

<div class="content-header px-0">
    <div class="container-fluid px-0">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-primary font-weight-bold"><i class="fas fa-tasks mr-2"></i>Gestão de Entregas</h1>
            </div>
            <div class="col-sm-6 text-right">
                <button class="btn btn-primary shadow-sm" onclick="$('#modalNovoPedido').modal('show')"><i class="fas fa-plus"></i> NOVO PEDIDO</button>
                <button class="btn btn-dark shadow-sm" onclick="window.modalR('termo')"><i class="fas fa-print"></i> TERMO</button>
                <button class="btn btn-outline-danger shadow-sm" onclick="window.modalR('faltas')"><i class="fas fa-cart-arrow-down"></i> FALTAS</button>
            </div>
        </div>
    </div>
</div>

<!-- FILTROS -->
<div class="card card-outline card-primary shadow-sm mb-3">
    <div class="card-body p-3">
        <form id="f_filtros" onsubmit="window.fAtivo(event)" class="row align-items-end small fw-bold">
            <div class="col-md-4"><label>IPEN / NOME:</label><input type="text" name="s" class="form-control form-control-sm" value="<?= $filt['s'] ?>"></div>
            <div class="col-md-2"><label>MÊS REFERÊNCIA:</label><select name="m" class="form-control form-control-sm"><?= listMonths() ?></select></div>
            <div class="col-md-2"><label>GALERIA:</label><select name="g" class="form-control form-control-sm">
                    <option value="">TUDO</option><?php foreach ($optsG as $v) echo "<option value='$v' " . ($filt['g'] == $v ? 'selected' : '') . ">$v</option>"; ?>
                </select></div>
            <div class="col-md-2">
                <div class="custom-control custom-switch pt-1"><input type="checkbox" class="custom-control-input" name="e" id="swE" <?= $filt['e'] ? 'checked' : '' ?>><label class="custom-control-label" for="swE">VER FINALIZADOS</label></div>
            </div>
            <div class="col-md-2 text-right"><button type="submit" class="btn btn-info btn-sm btn-block font-weight-bold shadow-sm">FILTRAR</button></div>
        </form>
    </div>
</div>

<!-- TABELA PRINCIPAL -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-striped m-0 text-center small align-middle">
                <thead class="bg-dark text-white">
                    <tr>
                        <th>IPEN</th>
                        <th class="text-left pl-3">INTERNO / STATUS</th>
                        <th>TIPO</th>
                        <th>LOCAL</th>
                        <th>VALOR</th>
                        <th class="text-left">PENDÊNCIAS</th>
                        <th>AÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_list = "SELECT pc.*, i.galeria, i.bloco, i.res as cela, i.forma_pagamento, i.nome_social, i.nome as n_doc,
                        (SELECT GROUP_CONCAT(CONCAT(pci.qtd_faltante,'x ',pi.nome) SEPARATOR ', ') FROM peculio_controle_itens pci JOIN peculio_itens pi ON pci.id_item=pi.id WHERE pci.id_controle=pc.id AND pci.qtd_faltante>0) as fts
                        FROM peculio_controle pc JOIN internos i ON pc.ipen = i.ipen
                        WHERE pc.mes_referencia = '{$filt['m']}'";
                    if (!$filt['e']) $sql_list .= " AND pc.status_entrega != 'Entregue'";
                    if ($filt['s'])  $sql_list .= " AND (i.ipen='{$filt['s']}' OR i.nome LIKE '%{$filt['s']}%' OR i.nome_social LIKE '%{$filt['s']}%')";
                    if ($filt['g'])  $sql_list .= " AND i.galeria='{$filt['g']}'";

                    $listagem = $pdo->query($sql_list . " ORDER BY i.galeria, CAST(i.res AS UNSIGNED), i.nome")->fetchAll();

                    foreach ($listagem as $r):
                        $is_o = ($r['status_entrega'] == 'Entregue');
                        $t_r = ($r['incidencia'] != 'Nenhuma' && $r['incidencia'] != 'Item Faltante');
                        $t_f = !empty($r['fts']);
                    ?>
                        <tr style="border-left: 5px solid <?= $is_o ? '#28a745' : ($t_r || $t_f ? '#dc3545' : '#ffc107') ?>">
                            <td class="font-weight-bold text-primary"><?= $r['ipen'] ?></td>
                            <td class="text-left pl-3">
                                <div class="fw-bold text-uppercase"><?= ($r['nome_social'] ?: $r['n_doc']) ?></div>
                                <div><span class="badge badge-<?= $is_o ? 'success' : 'warning' ?>"><?= $is_o ? 'ENTREGUE' : 'PENDENTE' ?></span> <?php if ($t_r): ?><span class="badge badge-danger"><?= $r['incidencia'] ?></span><?php endif; ?></div>
                            </td>
                            <td><small class="badge badge-secondary"><?= $r['forma_pagamento'] ?></small></td>
                            <td><span class="text-muted font-weight-bold"><?= $r['galeria'] . $r['bloco'] . "-" . $r['cela'] ?></span></td>
                            <td class="text-success font-weight-bold">R$<?= number_format($r['valor_total'], 2, ',', '.') ?></td>
                            <td class="text-danger small text-left pl-2"><?= ($t_f ? $r['fts'] : ($t_r ? $r['incidencia'] : '-')) ?></td>
                            <td>
                                <button class="btn btn-xs btn-dark px-2 mr-1" onclick='window.runCheck(<?= json_encode($r) ?>)'>CHECK</button>
                                <button class="btn btn-xs btn-info px-2" onclick="window.verExtrato(<?= $r['ipen'] ?>, '<?= $r['mes_referencia'] ?>')"><i class="fas fa-list-alt"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAIS -->

<!-- MODAL NOVO PEDIDO -->
<div class="modal fade" id="modalNovoPedido" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="f_novo" onsubmit="window.saveP(event)" class="modal-content">
            <input type="hidden" name="db_action" value="lancar_pedido"><input type="hidden" name="p_valor_total" id="pvt_i">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title font-weight-bold">LANÇAMENTO DE PEDIDO</h5><button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-5 position-relative"><label>PESQUISAR INTERNO:</label><input type="text" id="n_search" class="form-control form-control-lg font-weight-bold border-primary" autocomplete="off"><input type="hidden" name="p_ipen" id="n_ipen_val">
                        <div id="res_search" class="search-results"></div>
                    </div>
                    <div class="col-md-3"><label>MES REF:</label><select name="p_mes" id="n_mes" class="form-control form-control-lg font-weight-bold" onchange="window.checkSaldo()"><?= listMonths() ?></select></div>
                    <div class="col-md-4"><label>SALDO ATUAL:</label>
                        <div id="n_saldo_txt" class="h4 font-weight-bold text-success mt-1">R$ 0,00</div>
                    </div>
                </div>
                <div class="alert alert-info text-center h3 font-weight-bold shadow-sm">TOTAL DO PEDIDO R$ <span id="pvt_l">0,00</span></div>
                <div class="table-responsive border rounded" style="max-height: 350px;">
                    <table class="table table-sm table-striped">
                        <tbody>
                            <?php $catalogo = $pdo->query("SELECT * FROM peculio_itens ORDER BY ordem ASC")->fetchAll();
                            foreach ($catalogo as $it): ?>
                                <tr>
                                    <td class="align-middle small font-weight-bold text-uppercase"><?= $it['nome'] ?><br><span class="badge badge-secondary">MAX: <?= $it['maximo'] ?> | R$<?= number_format($it['preco'], 2, ',', '.') ?></span></td>
                                    <td><input type="number" name="item_qnt[]" class="form-control form-control-sm text-center font-weight-bold i-calc" value="0" min="0" data-mx="<?= $it['maximo'] ?>" data-p="<?= $it['preco'] ?>" onchange="window.calc()"> <input type="hidden" name="item_id[]" value="<?= $it['id'] ?>"></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" id="btn_save" class="btn btn-primary btn-block btn-lg mt-3 font-weight-bold shadow" disabled>GRAVAR REGISTRO</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CHECK / BAIXA -->
<div class="modal fade" id="modalCheck" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="f_check" onsubmit="window.saveBaixa(event)" class="modal-content">
            <input type="hidden" name="db_action" value="finalizar_entrega"><input type="hidden" name="e_id" id="ck_id_h">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold">CONFERÊNCIA OPERACIONAL</h5><button class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="ck_ban" class="alert alert-dark text-center font-weight-bold text-uppercase h5 border-info"></div>
                <div class="form-group"><label class="font-weight-bold small">SITUAÇÃO DA ENTREGA:</label><select name="st_entrega" id="ck_st_sel" class="form-control font-weight-bold" onchange="window.tM(this.value)">
                        <option value="Entregue">ENTREGA FINALIZADA</option>
                        <option value="Não Entregue">BOLSA RETORNADA</option>
                    </select></div>
                <div id="ck_mot_box" class="form-group p-3 border border-danger rounded mb-3" style="display:none; background: #fff5f5;"><label class="small font-weight-bold text-danger">MOTIVO DO RETORNO:</label><select name="incidencia" id="ck_inc_sel" class="form-control">
                        <option value="Transferência">TRANSFERIDO</option>
                        <option value="Alvará">ALVARÁ</option>
                        <option value="Tornozeleira">TORNOZELEIRA</option>
                        <option value="Óbito">ÓBITO</option>
                        <option value="Saída Temporária">S. TEMPORÁRIA</option>
                        <option value="Saída Temporária">LIVRAMENTO CONDICIONAL</option>
                        <option value="Progresso">PROGRESSÃO</option>
                        <option value="Recusa">RECUSOU</option>
                        <option value="Castigo">SANÇÃO DISCIPLINAR</option>
                        <option value="Mercado não entregou">MERCADO NÃO ENTREGOU</option>
                    </select></div>
                <div class="table-responsive border rounded mb-3">
                    <table class="table table-sm table-dark m-0 small">
                        <thead>
                            <tr class="text-center">
                                <th>ITEM</th>
                                <th width="80">PED.</th>
                                <th width="100">FALTA</th>
                            </tr>
                        </thead>
                        <tbody id="ck_lista_it"></tbody>
                    </table>
                </div>
                <textarea name="obs" id="ck_obs_txt" class="form-control" rows="2" placeholder="Observações..."></textarea>
                <button type="submit" class="btn btn-success btn-block btn-lg font-weight-bold mt-3">SALVAR CONFERÊNCIA</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EXTRATO -->
<div class="modal fade" id="modalExtrato" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-info">
            <div class="modal-header bg-info py-2 text-white">
                <h5>EXTRATO FINANCEIRO NO MÊS</h5><button class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body p-0">
                <table class="table table-sm table-striped m-0 small">
                    <thead>
                        <tr>
                            <th>DESCRIÇÃO</th>
                            <th class="text-right">VALOR</th>
                        </tr>
                    </thead>
                    <tbody id="lista_ext"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RELATORIOS -->
<div class="modal fade" id="modalRel" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-weight-bold">GERAR DOCUMENTAÇÃO</h5><button class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rtp">
                <div class="form-group"><label>MÊS REFERÊNCIA:</label><select id="rm" class="form-control form-control-lg"><?= listMonths() ?></select></div>
                <div class="form-group"><label>FILTRAR GALERIA:</label><select id="rg" class="form-control">
                        <option value="">TUDO</option><?php foreach ($optsG as $v) echo "<option value='$v'>$v</option>"; ?>
                    </select></div>
                <button type="button" onclick="window.exeP()" class="btn btn-primary btn-block btn-lg mt-4 font-weight-bold shadow">IMPRIMIR AGORA</button>
            </div>
        </form>
    </div>
</div>

<script>
    // USAR O WINDOW PARA GARANTIR ESCOPO NO CARREGAMENTO AJAX
    window.fAtivo = (e) => {
        e.preventDefault();
        loadPage('paginas/peculio_gestao.php?' + new URLSearchParams(new FormData(e.target)).toString());
    };
    window.modalR = (t) => {
        $('#rtp').val(t);
        $('#modalRel').modal('show');
    };
    window.exeP = () => {
        window.open(`paginas/peculio_gestao.php?execute_print=${$('#rtp').val()}&p_m=${$('#rm').val()}&p_g=${$('#rg').val()}`, '_blank');
    };

    window.calc = () => {
        let tot = 0;
        document.querySelectorAll('.i-calc').forEach(i => {
            let v = parseInt(i.value) || 0;
            let mx = parseInt(i.dataset.mx);
            if (v > mx) {
                alert("Limite de " + mx + " excedido!");
                i.value = mx;
                v = mx;
            }
            tot += (parseFloat(i.dataset.p) * v);
        });
        document.getElementById('pvt_l').innerText = tot.toLocaleString('pt-br', {
            minimumFractionDigits: 2
        });
        document.getElementById('pvt_i').value = tot.toFixed(2);
        let saldo = parseFloat(document.getElementById('n_saldo_txt').innerText.replace('R$ ', '').replace('.', '').replace(',', '.'));
        document.getElementById('btn_save').disabled = (tot > saldo || tot <= 0);
        document.getElementById('pvt_l').style.color = (tot > saldo) ? 'red' : '';
    };

    $('#n_search').on('keyup', async function() {
        const term = $(this).val();
        if (term.length < 2) {
            $('#res_search').hide();
            return;
        }
        const fd = new FormData();
        fd.append('db_action', 'search_internos');
        fd.append('term', term);
        const r = await fetch('paginas/peculio_gestao.php', {
            method: 'POST',
            body: fd
        });
        const res = await r.json();
        let html = '';
        res.forEach(i => {
            html += `<div class="search-item" onclick="window.selecionarInterno(${i.ipen}, '${i.nome_social || i.nome}')">${i.ipen} - ${i.nome_social || i.nome} <br><small class='text-muted'>${i.galeria}${i.bloco}-${i.res} | PG: ${i.forma_pagamento}</small></div>`;
        });
        $('#res_search').html(html).show();
    });

    window.selecionarInterno = (ipen, nome) => {
        $('#n_ipen_val').val(ipen);
        $('#n_search').val(`${ipen} - ${nome}`);
        $('#res_search').hide();
        window.checkSaldo();
    };

    window.checkSaldo = async () => {
        const ipen = $('#n_ipen_val').val();
        const mes = $('#n_mes').val();
        if (!ipen) return;
        const fd = new FormData();
        fd.append('db_action', 'get_saldo_interno');
        fd.append('ipen', ipen);
        fd.append('mes', mes);
        const r = await (await fetch('paginas/peculio_gestao.php', {
            method: 'POST',
            body: fd
        })).json();
        document.getElementById('n_saldo_txt').innerText = r.saldo.toLocaleString('pt-br', {
            style: 'currency',
            currency: 'BRL'
        });
        window.calc();
    };

    window.verExtrato = async (ipen, mes) => {
        const fd = new FormData();
        fd.append('db_action', 'get_extrato');
        fd.append('ipen', ipen);
        fd.append('mes', mes);
        const r = await (await fetch('paginas/peculio_gestao.php', {
            method: 'POST',
            body: fd
        })).json();
        let html = '';
        r.historico.forEach(h => {
            const cor = h.movimento === 'Crédito' ? 'text-success' : 'text-danger';
            const sinal = h.movimento === 'Crédito' ? '+' : '-';
            html += `<tr><td><small>${h.data_ref}</small><br><b>${h.tipo}</b></td><td class="text-right font-weight-bold ${cor}">${sinal} R$ ${parseFloat(h.valor).toLocaleString('pt-br',{minimumFractionDigits:2})}</td></tr>`;
        });
        document.getElementById('lista_ext').innerHTML = html;
        $('#modalExtrato').modal('show');
    };

    window.runCheck = async (d) => {
        document.getElementById('ck_id_h').value = d.id;
        document.getElementById('ck_ban').innerText = `${d.ipen} - ${d.nome_social || d.n_doc}`;
        const r = await (await fetch('paginas/peculio_gestao.php?fetch_pedido=' + d.id)).json();
        document.getElementById('ck_obs_txt').value = r.base.observacao || '';
        const st = (r.base.incidencia != 'Nenhuma' && r.base.incidencia != 'Item Faltante') ? 'Não Entregue' : 'Entregue';
        $('#ck_st_sel').val(st);
        window.tM(st);
        if (r.base.incidencia != 'Nenhuma') $('#ck_inc_sel').val(r.base.incidencia);
        let h = '';
        r.itens.forEach(it => {
            h += `<tr align="center"><td>${it.nome}<input type="hidden" name="c_it_nome[]" value="${it.nome}"></td><td class="h6 font-weight-bold text-info">${it.quantidade}</td><td><input type="number" name="qf[]" class="form-control form-control-sm text-center mx-auto" value="${it.qtd_faltante || 0}" max="${it.quantidade}" style="width:70px;"><input type="hidden" name="c_it_id[]" value="${it.id_item}"></td></tr>`;
        });
        document.getElementById('ck_lista_it').innerHTML = h;
        $('#modalCheck').modal('show');
    };

    window.saveP = async (e) => {
        e.preventDefault();
        const res = await (await fetch('paginas/peculio_gestao.php', {
            method: 'POST',
            body: new FormData(e.target)
        })).json();
        if (res.success) {
            $('#modalNovoPedido').modal('hide');
            loadPage('paginas/peculio_gestao.php');
        } else alert(res.message);
    };

    window.saveBaixa = async (e) => {
        e.preventDefault();
        const res = await (await fetch('paginas/peculio_gestao.php', {
            method: 'POST',
            body: new FormData(e.target)
        })).json();
        if (res.success) {
            $('#modalCheck').modal('hide');
            loadPage('paginas/peculio_gestao.php?' + new URLSearchParams(new FormData(document.getElementById('f_filtros'))).toString());
        }
    };

    window.tM = (v) => document.getElementById('ck_mot_box').style.display = (v === 'Não Entregue' ? 'block' : 'none');
</script>
<?php ob_end_flush(); ?>
