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

$pdo = null;
try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // --- 1. PROCESSAMENTO AJAX (Ações POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['db_action'])) {
        ob_clean();
        header('Content-Type: application/json');
        try {
            if ($_POST['db_action'] === 'search_internos') {
                $term = "%" . $_POST['term'] . "%";
                $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res, forma_pagamento FROM internos
                        WHERE (nome LIKE ? OR ipen LIKE ?) AND status = 'A' LIMIT 8";
                $st = $pdo->prepare($sql);
                $st->execute([$term, $term]);
                echo json_encode($st->fetchAll());
                exit;
            }
            if ($_POST['db_action'] === 'save_balance') {
                $table = ($_POST['type'] === 'Pix') ? 'peculio_saldos_pix' : 'peculio_saldos_trabalho';
                $sql = "INSERT INTO $table (ipen, mes_referencia, valor) VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
                $pdo->prepare($sql)->execute([$_POST['ipen'], $_POST['mes'], $_POST['valor']]);
                echo json_encode(['success' => true]);
                exit;
            }
            if ($_POST['db_action'] === 'del_balance') {
                $table = ($_POST['type'] === 'Pix') ? 'peculio_saldos_pix' : 'peculio_saldos_trabalho';
                $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['id']]);
                echo json_encode(['success' => true]);
                exit;
            }
            if ($_POST['db_action'] === 'save_item') {
                $sql = "UPDATE peculio_itens SET nome = ?, maximo = ?, preco = ? WHERE id = ?";
                $pdo->prepare($sql)->execute([strtoupper($_POST['nome']), $_POST['max'], $_POST['preco'], $_POST['id']]);
                echo json_encode(['success' => true]);
                exit;
            }
            if ($_POST['db_action'] === 'reorder') {
                foreach ($_POST['ordem'] as $pos => $id) {
                    $pdo->prepare("UPDATE peculio_itens SET ordem = ? WHERE id = ?")->execute([$pos, $id]);
                }
                echo json_encode(['success' => true]);
                exit;
            }
            if ($_POST['db_action'] === 'save_global_limit') {
                $pdo->prepare("UPDATE sistema_config SET valor = ? WHERE chave = 'limite_peculio_global'")->execute([$_POST['valor']]);
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (Exception $ex) {
            echo json_encode(['success' => false, 'message' => $ex->getMessage()]);
            exit;
        }
    }
} catch (Exception $e) {
    die("Erro 500");
}

// --- 2. LOGICA DE FILTRO E SQL ---
$mes_atual = date('m/Y');
$limite_global = $pdo->query("SELECT valor FROM sistema_config WHERE chave = 'limite_peculio_global'")->fetchColumn() ?: '400.00';
$f_s = $_GET['s'] ?? '';
$f_g = $_GET['g'] ?? '';
$f_b = $_GET['b'] ?? '';
$f_r = $_GET['r'] ?? '';
$f_p = $_GET['p'] ?? '';

$where = ["i.status='A'"];
$params = [];
if ($f_s) {
    $where[] = "(i.nome LIKE :s OR i.ipen LIKE :s OR i.nome_social LIKE :s)";
    $params[':s'] = "%$f_s%";
}
if ($f_g) {
    $where[] = "i.galeria = :g";
    $params[':g'] = $f_g;
}
if ($f_b) {
    $where[] = "i.bloco = :b";
    $params[':b'] = $f_b;
}
if ($f_r) {
    $where[] = "i.res = :r";
    $params[':r'] = $f_r;
}
if ($f_p) {
    $where[] = "i.forma_pagamento = :p";
    $params[':p'] = $f_p;
}

// SQL com Cálculo de Débito (Lançado - Pedidos)
$sql_internos = "SELECT i.*,
    (IFNULL((SELECT valor FROM peculio_saldos_pix WHERE ipen=i.ipen AND mes_referencia='$mes_atual'), 0) -
     IFNULL((SELECT SUM(valor_total) FROM peculio_controle WHERE ipen=i.ipen AND mes_referencia='$mes_atual'), 0)) as pix_dispo,
    (IFNULL((SELECT valor FROM peculio_saldos_trabalho WHERE ipen=i.ipen AND mes_referencia='$mes_atual'), 0) -
     IFNULL((SELECT SUM(valor_total) FROM peculio_controle WHERE ipen=i.ipen AND mes_referencia='$mes_atual'), 0)) as trab_dispo
    FROM internos i WHERE " . implode(" AND ", $where) . "
    HAVING (pix_dispo > 0 OR trab_dispo > 0)
    ORDER BY galeria, res, nome";

// --- 3. MODO IMPRESSÃO (COM CSS EMBUTIDO) ---
if (isset($_GET['execute_print'])) {
    ob_end_clean();
    $st = $pdo->prepare($sql_internos);
    $st->execute($params);
    $data_p = $st->fetchAll();

    // Relatório de Saldos
    if (isset($_GET['balance_report'])) {
        $type = $_GET['type'];
?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Saldos <?= $type ?></title>
            <style>
                body {
                    font-family: sans-serif;
                    text-transform: uppercase;
                    padding: 20px;
                    font-size: 10pt;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                }

                th,
                td {
                    border: 1px solid #000;
                    padding: 6px;
                    text-align: center;
                }

                th {
                    background: #eee;
                }
            </style>
        </head>

        <body onload="window.print()">
            <h2>RELATÓRIO DE SALDOS - <?= $type ?> (<?= $mes_atual ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>IPEN</th>
                        <th align="left">NOME</th>
                        <th>LOCAL</th>
                        <th>SALDO DISPONÍVEL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data_p as $p) {
                        $saldo = ($type === 'Pix') ? $p['pix_dispo'] : $p['trab_dispo'];
                        if ($saldo > 0) echo "<tr><td>{$p['ipen']}</td><td align='left'>" . ($p['nome_social'] ?: $p['nome']) . "</td><td>{$p['galeria']}{$p['bloco']}-{$p['res']}</td><td>R$ " . number_format($saldo, 2, ',', '.') . "</td></tr>";
                    } ?>
                </tbody>
            </table>
        </body>

        </html><?php exit;
            }

            // Folha de Pecúlio (A5 Lado a Lado)
            $itens_p = $pdo->query("SELECT * FROM peculio_itens ORDER BY ordem ASC")->fetchAll();
                ?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Folhas de Pecúlio</title>
        <style>
            @page {
                size: A4 landscape;
                margin: 4mm;
            }

            body {
                font-family: 'Arial Narrow', sans-serif;
                margin: 0;
                text-transform: uppercase;
                line-height: 1;
            }

            .row-wrap {
                display: flex;
                justify-content: space-between;
                page-break-after: always;
                height: 195mm;
            }

            .folha {
                width: 142mm;
                border: 1.5pt solid #000;
                padding: 2mm;
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                height: 100%;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            .tab-h td {
                border: 1pt solid #000;
                padding: 2px;
                text-align: center;
                font-weight: bold;
                font-size: 10pt;
                background: #BDD7EE !important;
                -webkit-print-color-adjust: exact;
            }

            .tab-m td {
                border: 0.5pt solid #000;
                padding: 2px 5px;
                font-size: 8pt;
                font-weight: bold;
            }

            .tab-i {
                font-size: 6.2pt;
                border: 0.8pt solid #000;
            }

            .tab-i th {
                background: #FFFF00 !important;
                -webkit-print-color-adjust: exact;
                border: 0.8pt solid #000;
                padding: 1px;
            }

            .tab-i td {
                border: 0.5pt solid #000;
                padding: 0.5px 2px;
                height: 8.2pt;
            }

            .foot {
                margin-top: auto;
                font-size: 6pt;
                text-align: center;
                border-top: 1pt solid #000;
                padding-top: 2px;
                font-weight: bold;
            }
        </style>
    </head>

    <body onload="window.print()">
        <?php for ($i = 0; $i < count($data_p); $i += 2): ?>
            <div class="row-wrap">
                <?php for ($j = 0; $j < 2; $j++): if (isset($data_p[$i + $j])): $in = $data_p[$i + $j];
                        $s = ($in['trab_dispo'] > 0 ? $in['trab_dispo'] : $in['pix_dispo']); ?>
                        <div class="folha">
                            <table class="tab-h">
                                <tr>
                                    <td>PENITENCIÁRIA INDUSTRIAL DE JOINVILLE</td>
                                    <td width="20%">CELA: <?= $in['galeria'] . $in['bloco'] . "-" . $in['res'] ?></td>
                                </tr>
                            </table>
                            <table class="tab-m">
                                <tr>
                                    <td width="20%">MÊS: <?= $mes_atual ?></td>
                                    <td width="55%">NOME: <b><?= ($in['nome_social'] ?: $in['nome']) ?></b></td>
                                    <td width="25%">IPEN: <b><?= $in['ipen'] ?></b></td>
                                </tr>
                                <tr>
                                    <td><?= $in['forma_pagamento'] ?></td>
                                    <td>DISPONÍVEL R$: <?= number_format($s, 2, ',', '.') ?></td>
                                    <td align="right">TOTAL R$: ........</td>
                                </tr>
                            </table>
                            <table class="tab-i">
                                <thead>
                                    <tr>
                                        <th>PRODUTOS</th>
                                        <th width="35">MÁX</th>
                                        <th width="50">PREÇO</th>
                                        <th width="35">QNT</th>
                                        <th width="60">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody><?php foreach ($itens_p as $it) echo "<tr><td>{$it['nome']}</td><td align='center'>{$it['maximo']}</td><td align='center'>" . number_format($it['preco'], 2, ',', '.') . "</td><td></td><td></td></tr>"; ?></tbody>
                            </table>
                            <div class="foot">CONFERIDO POR: ................................................................ ASSINATURA: ................................................................</div>
                        </div>
                <?php endif;
                endfor; ?>
            </div>
        <?php endfor; ?>
    </body>

    </html><?php exit;
        }

        // --- 4. PREPARAÇÃO DA INTERFACE ---
        $st_i = $pdo->prepare($sql_internos);
        $st_i->execute($params);
        $internos_aptos = $st_i->fetchAll();
        $optsG = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE galeria!='' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
        $optsB = $pdo->query("SELECT DISTINCT bloco FROM internos WHERE bloco!='' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
        $optsR = $pdo->query("SELECT DISTINCT res FROM internos WHERE res!='' ORDER BY CAST(res AS UNSIGNED)")->fetchAll(PDO::FETCH_COLUMN);
        $catalogo = $pdo->query("SELECT * FROM peculio_itens ORDER BY ordem ASC")->fetchAll();
            ?>

<style>
    .t-compact td {
        padding: 3px 6px !important;
        vertical-align: middle !important;
        font-size: 0.85rem;
    }

    .pg-box {
        padding: 2px 8px;
        margin: 0 1px;
        background: #343a40;
        color: #fff;
        border-radius: 4px;
        cursor: pointer;
    }

    .pg-box.active {
        background: #007bff;
        font-weight: bold;
    }

    .handle-drag {
        cursor: grab;
        color: #999;
        width: 30px;
        text-align: center;
    }

    .search-results {
        position: absolute;
        z-index: 1050;
        width: 100%;
        background: #fff;
        border: 1px solid #ddd;
        display: none;
        max-height: 180px;
        overflow-y: auto;
        color: #333;
    }

    .search-item {
        padding: 6px 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        font-weight: bold;
    }
</style>

<div class="content-header px-0">
    <div class="container-fluid px-0">
        <div class="row no-gutters align-items-center">
            <div class="col-md-4">
                <h5 class="m-0 font-weight-bold text-success text-uppercase"><i class="fas fa-shopping-cart mr-2"></i>Folha de Pecúlio</h5>
            </div>
            <div class="col-md-3">
                <div class="input-group input-group-sm shadow-sm">
                    <div class="input-group-prepend"><span class="input-group-text bg-warning font-weight-bold small">LIMITE R$</span></div>
                    <input type="number" id="lim_glob" class="form-control text-center font-weight-bold" value="<?= $limite_global ?>">
                    <div class="input-group-append"><button class="btn btn-dark" onclick="window.saveGlobalLimit()"><i class="fas fa-save"></i></button></div>
                </div>
            </div>
            <div class="col-md-5 text-right px-1">
                <button class="btn btn-sm btn-info font-weight-bold mr-1" onclick="$('#modal_balance').modal('show')"><i class="fas fa-coins"></i> GESTÃO DE SALDOS</button>
                <button class="btn btn-sm btn-success font-weight-bold shadow" onclick="window.printFolhas()"><i class="fas fa-print"></i> IMPRIMIR (<?= count($internos_aptos) ?>)</button>
            </div>
        </div>
    </div>
</div>

<!-- FILTROS -->
<div class="card shadow-sm mb-2 p-2 border-primary">
    <div class="row no-gutters align-items-end small font-weight-bold text-muted uppercase">
        <div class="col-md-3 px-1"><label>NOME / IPEN:</label><input type="text" id="fil_s" class="form-control form-control-sm" value="<?= $f_s ?>"></div>
        <div class="col-md-1 px-1"><label>GAL:</label><select id="fil_g" class="form-control form-control-sm">
                <option value="">TUDO</option><?php foreach ($optsG as $v) echo "<option value='$v' " . ($f_g == $v ? 'selected' : '') . ">$v</option>"; ?>
            </select></div>
        <div class="col-md-1 px-1"><label>BLO:</label><select id="fil_b" class="form-control form-control-sm">
                <option value="">TUDO</option><?php foreach ($optsB as $v) echo "<option value='$v' " . ($f_b == $v ? 'selected' : '') . ">$v</option>"; ?>
            </select></div>
        <div class="col-md-1 px-1"><label>CELA:</label><select id="fil_r" class="form-control form-control-sm">
                <option value="">TUDO</option><?php foreach ($optsR as $v) echo "<option value='$v' " . ($f_r == $v ? 'selected' : '') . ">$v</option>"; ?>
            </select></div>
        <div class="col-md-1 px-1"><label>PAG.:</label><select id="fil_p" class="form-control form-control-sm">
                <option value="">TUDO</option>
                <option value="Pix" <?= $f_p == 'Pix' ? 'selected' : '' ?>>PIX</option>
                <option value="Salário" <?= $f_p == 'Salário' ? 'selected' : '' ?>>SALÁRIO</option>
            </select></div>
        <div class="col-md-5 px-1 text-right"><button class="btn btn-primary btn-sm px-3" onclick="window.runPijFil()"><i class="fas fa-filter"></i> FILTRAR</button> <button class="btn btn-outline-secondary btn-sm" onclick="loadPage('paginas/peculio_itens.php')">LIMPAR</button></div>
    </div>
</div>

<div class="row no-gutters">
    <!-- Catálogo Compacto e Sortable -->
    <div class="col-lg-7 px-1">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header p-1 px-2 d-flex align-items-center">
                <h3 class="card-title font-weight-bold small text-uppercase">Catálogo Ordenável</h3><button class="btn btn-xs btn-success ml-auto" onclick="$('#m_new_item_box').modal('show')"><i class="fas fa-plus"></i></button>
            </div>
            <div class="card-body p-0 overflow-auto" style="max-height: 55vh;">
                <table class="table table-sm table-bordered m-0 t-compact">
                    <thead class="bg-dark text-center">
                        <tr>
                            <th width="35"></th>
                            <th>PRODUTO</th>
                            <th width="50">MÁX</th>
                            <th width="85">PREÇO</th>
                            <th width="70">AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody id="pij_drag_body"><?php foreach ($catalogo as $it): ?>
                            <tr data-id="<?= $it['id'] ?>">
                                <td class="handle-drag"><i class="fas fa-grip-lines"></i></td>
                                <td class="p-0"><input type="text" class="form-control border-0 font-weight-bold pl-2" value="<?= $it['nome'] ?>"></td>
                                <td class="p-0"><input type="number" class="form-control border-0 text-center" value="<?= $it['maximo'] ?>"></td>
                                <td class="p-0"><input type="text" class="form-control border-0 text-center font-weight-bold text-success" value="<?= number_format($it['preco'], 2, ',', '.') ?>"></td>
                                <td class="text-center"><button class="btn btn-xs btn-link text-info" onclick="window.svIt(<?= $it['id'] ?>)"><i class="fas fa-save"></i></button><button class="btn btn-xs btn-link text-danger" onclick="window.dlIt(<?= $it['id'] ?>)"><i class="fas fa-trash"></i></button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tabela de Internos Apenas com Saldo -->
    <div class="col-lg-5 px-1">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header p-2 font-weight-bold small text-center uppercase">Internos com Disponibilidade</div>
            <div class="card-body p-0 overflow-auto" style="max-height: 55vh;">
                <table class="table table-sm table-striped m-0 t-compact">
                    <thead class="bg-dark">
                        <tr>
                            <th>IPEN</th>
                            <th>NOME</th>
                            <th class="text-right">DISPONÍVEL</th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($internos_aptos as $i): $val = ($i['forma_pagamento'] == 'Salário' ? $i['trab_dispo'] : $i['pix_dispo']); ?>
                            <tr>
                                <td><small class='font-weight-bold'><?= $i['ipen'] ?></small></td>
                                <td class='small uppercase font-weight-bold text-truncate' style='max-width:140px'><?= ($i['nome_social'] ?: $i['nome']) ?></td>
                                <td class='text-right text-success font-weight-bold pr-2'>R$ <?= number_format($val, 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL GESTÃO SALDOS -->
<div id="modal_balance" class="modal fade" data-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-info">
            <div class="modal-header bg-info py-2 text-white">
                <h5>GERENCIADOR DE SALDOS</h5><button class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body row">
                <div class="col-md-6 border-right">
                    <form id="formBalance"><input type="hidden" name="db_action" value="save_balance">
                        <div class="form-group position-relative"><label class="small font-weight-bold">BUSCAR INTERNO:</label><input type="text" id="search_int" class="form-control" autocomplete="off"><input type="hidden" name="ipen" id="sel_ipen">
                            <div id="search_results" class="search-results shadow"></div>
                        </div>
                        <div class="row">
                            <div class="col-6"><label class="small font-weight-bold">MÊS REF:</label><input type="text" name="mes" class="form-control" value="<?= $mes_atual ?>"></div>
                            <div class="col-6"><label class="small font-weight-bold">TIPO:</label><select name="type" class="form-control">
                                    <option value="Pix">PIX</option>
                                    <option value="Trabalho">SALÁRIO</option>
                                </select></div>
                        </div>
                        <label class="small font-weight-bold mt-2">VALOR R$:</label><input type="text" name="valor" class="form-control form-control-lg font-weight-bold text-center text-success" placeholder="0,00" required>
                        <button type="button" onclick="window.submitBalance()" class="btn btn-info btn-block mt-3 py-2 font-weight-bold">LANÇAR SALDO</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="small font-weight-bold mb-1">REGISTROS RECENTES:</div>
                    <div style="height:210px; overflow-y:auto;">
                        <table class="table table-xs table-striped small">
                            <tbody>
                                <?php $rec = $pdo->query("(SELECT id, ipen, valor, 'Pix' as tp FROM peculio_saldos_pix WHERE mes_referencia='$mes_atual') UNION (SELECT id, ipen, valor, 'Trab' as tp FROM peculio_saldos_trabalho WHERE mes_referencia='$mes_atual') ORDER BY id DESC LIMIT 10")->fetchAll();
                                foreach ($rec as $r) echo "<tr><td class='py-1 pl-2'><b>{$r['ipen']}</b> ({$r['tp']})</td><td class='text-right pr-2 font-weight-bold'>R$ " . number_format($r['valor'], 2, ',', '.') . "<button class='btn btn-xs text-danger ml-2' onclick='window.delBal({$r['id']}, \"" . ($r['tp'] == 'Pix' ? 'Pix' : 'Trabalho') . "\")'>&times;</button></td></tr>"; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-1 border-top mt-2"><button class="btn btn-xs btn-dark btn-block" onclick="window.printBalRep('Pix')">LISTA PIX</button><button class="btn btn-xs btn-outline-dark btn-block" onclick="window.printBalRep('Trabalho')">LISTA TRABALHO</button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.fmtPURL = (pg = 1) => `paginas/peculio_itens.php?p_it=${pg}&s=${encodeURIComponent($('#fil_s').val())}&g=${$('#fil_g').val()}&b=${$('#fil_b').val()}&r=${$('#fil_r').val()}&p=${$('#fil_p').val()}`;
    window.runPijFil = () => loadPage(window.fmtPURL());
    window.printFolhas = () => window.open(window.fmtPURL().replace('itens.php?', 'itens.php?execute_print=1&'), '_blank');
    window.printBalRep = (type) => window.open(`paginas/peculio_itens.php?execute_print=1&balance_report=1&type=${type}&mes=${encodeURIComponent('<?= $mes_atual ?>')}`, '_blank');

    window.saveGlobalLimit = async () => {
        if (confirm("Mudar limite?")) {
            const fd = new FormData();
            fd.append('db_action', 'save_global_limit');
            fd.append('valor', $('#lim_glob').val());
            await fetch('paginas/peculio_itens.php', {
                method: 'POST',
                body: fd
            });
            loadPage('paginas/peculio_itens.php');
        }
    };
    window.svIt = async (id) => {
        const tr = document.querySelector(`tr[data-id="${id}"]`),
            ins = tr.querySelectorAll('input'),
            fd = new FormData();
        fd.append('db_action', 'save_item');
        fd.append('id', id);
        fd.append('nome', ins[0].value);
        fd.append('max', ins[1].value);
        fd.append('preco', ins[2].value.replace(/\./g, '').replace(',', '.'));
        const res = await fetch('paginas/peculio_itens.php', {
            method: 'POST',
            body: fd
        });
        if ((await res.json()).success) {
            tr.style.background = '#d4edda';
            setTimeout(() => tr.style.background = '', 500);
        }
    };
    window.dlIt = async (id) => {
        if (confirm("EXCLUIR?")) {
            const fd = new FormData();
            fd.append('db_action', 'del_item');
            fd.append('id', id);
            await fetch('paginas/peculio_itens.php', {
                method: 'POST',
                body: fd
            });
            loadPage('paginas/peculio_itens.php');
        }
    };

    $('#search_int').on('keyup', async function() {
        const val = $(this).val();
        if (val.length < 3) {
            $('#search_results').hide();
            return;
        }
        const fd = new FormData();
        fd.append('db_action', 'search_internos');
        fd.append('term', val);
        const res = await (await fetch('paginas/peculio_itens.php', {
            method: 'POST',
            body: fd
        })).json();
        let h = '';
        res.forEach(i => {
            h += `<div class="search-item" onclick="window.selInt(${i.ipen}, '${i.nome_social||i.nome}')"><b>${i.ipen}</b> - ${i.nome_social||i.nome} <br><small class='text-muted'>${i.galeria}${i.bloco}-${i.res} (${i.forma_pagamento})</small></div>`;
        });
        $('#search_results').html(h).show();
    });
    window.selInt = (ipen, nome) => {
        $('#sel_ipen').val(ipen);
        $('#search_int').val(nome);
        $('#search_results').hide();
    };

    window.submitBalance = async () => {
        const fd = new FormData(document.getElementById('formBalance'));
        fd.set('valor', fd.get('valor').replace(/\./g, '').replace(',', '.'));
        const res = await (await fetch('paginas/peculio_itens.php', {
            method: 'POST',
            body: fd
        })).json();
        if (res.success) {
            $('.modal').modal('hide');
            loadPage('paginas/peculio_itens.php');
        } else alert(res.message);
    };
    window.delBal = async (id, type) => {
        if (confirm("Remover?")) {
            const fd = new FormData();
            fd.append('db_action', 'del_balance');
            fd.append('id', id);
            fd.append('type', type);
            await fetch('paginas/peculio_itens.php', {
                method: 'POST',
                body: fd
            });
            loadPage('paginas/peculio_itens.php');
        }
    };

    (function() {
        const checkSortable = setInterval(() => {
            if (typeof Sortable !== 'undefined') {
                clearInterval(checkSortable);
                Sortable.create(document.getElementById('pij_drag_body'), {
                    handle: '.handle-drag',
                    animation: 150,
                    onEnd: async (evt) => {
                        const fd = new FormData();
                        fd.append('db_action', 'reorder');
                        Array.from(evt.to.children).forEach(tr => fd.append('ordem[]', tr.dataset.id));
                        await fetch('paginas/peculio_itens.php', {
                            method: 'POST',
                            body: fd
                        });
                    }
                });
            }
        }, 100);
    })();
</script>
<?php ob_end_flush(); ?>
