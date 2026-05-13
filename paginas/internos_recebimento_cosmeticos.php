<?php
// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

// paginas/internos_recebimento_cosmeticos.php

$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro DB: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) session_start();

$nome_user = $_SESSION['user_nome'] ?? $_SESSION['usuario_nome'] ?? 'Usuario Sistema';
$setor_user = $_SESSION['user_setor'] ?? '';
$usuario_logado = $nome_user . ($setor_user ? " (" . mb_strtoupper($setor_user, 'UTF-8') . ")" : "");

// Verificar se usuário tem acesso total (admin ou censura)
$eh_admin = (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true);
$eh_censura = (isset($_SESSION['perm_censura']) && $_SESSION['perm_censura'] > 0);
$tem_acesso_total = $eh_admin || $eh_censura;

// Verificar se usuário é da portaria (mas não se for admin ou censura)
$eh_portaria = (isset($_SESSION['perm_portaria']) && $_SESSION['perm_portaria'] > 0) && !$tem_acesso_total;

// --- AÇÕES DO BACKEND ---
if (isset($_REQUEST['acao'])) {

    // 1. BUSCAR INTERNO (Traz campo LGBT)
    if ($_REQUEST['acao'] === 'buscar_interno') {
        ob_clean(); header('Content-Type: application/json');
        $termo = trim($_REQUEST['termo']);
        try {
            $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res, lgbt FROM internos
                    WHERE (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?) AND status = 'A' LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $like = "%$termo%";
            $stmt->execute([$like, $like, $like]);
            echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 2. BUSCAR DADOS PARA EDIÇÃO
    if ($_REQUEST['acao'] === 'get_recebimento') {
        ob_clean(); header('Content-Type: application/json');
        try {
            // Restrição: Portaria não pode editar
            if ($eh_portaria) {
                throw new Exception("Usuário da portaria não tem permissão para editar registros.");
            }

            $id = $_POST['id'];
            $stmt = $pdo->prepare("SELECT r.*, i.nome as nome_interno, i.nome_social, i.lgbt FROM internos_recebimento_cosmeticos r JOIN internos i ON r.id_interno = i.ipen WHERE r.id = ?");
            $stmt->execute([$id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmtItens = $pdo->prepare("SELECT item, quantidade, detalhes FROM internos_recebimento_cosmeticos_itens WHERE id_recebimento = ?");
            $stmtItens->execute([$id]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'dados' => $dados, 'itens' => $itens]);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 3. REGISTRAR / EDITAR
    if ($_REQUEST['acao'] === 'salvar_recebimento' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean(); header('Content-Type: application/json');
        try {
            $pdo->beginTransaction();
            $id_recebimento = $_POST['id_recebimento'] ?? '';
            // Restrição: Portaria só pode inserir, não editar
            if ($eh_portaria && !empty($id_recebimento)) {
                throw new Exception("Usuário da portaria não tem permissão para editar registros.");
            }

            $ipen = $_POST['ipen'];

            // VALIDAR SE É LGBT
            $stmtLgbt = $pdo->prepare("SELECT lgbt FROM internos WHERE ipen = ?");
            $stmtLgbt->execute([$ipen]);
            $resLgbt = $stmtLgbt->fetchColumn();
            if($resLgbt !== 'S') throw new Exception("ERRO: O interno não está cadastrado como LGBT.");

            if(empty($id_recebimento)) {
                // INSERT: Valida se já recebeu no mês
                $stmtCheck = $pdo->prepare("SELECT id FROM internos_recebimento_cosmeticos WHERE id_interno = ? AND YEAR(data_recebimento) = YEAR(NOW()) AND MONTH(data_recebimento) = MONTH(NOW())");
                $stmtCheck->execute([$ipen]);
                if ($stmtCheck->rowCount() > 0) throw new Exception("Este interno já recebeu cosméticos neste mês.");

                $stmtInsert = $pdo->prepare("INSERT INTO internos_recebimento_cosmeticos (id_interno, data_recebimento, entregue_por_tipo, entregue_por_nome, cadastrado_por) VALUES (?, NOW(), ?, ?, ?)");
                $stmtInsert->execute([$ipen, $_POST['entregue_por_tipo'], $_POST['entregue_por_nome'], $usuario_logado]);
                $id_recebimento = $pdo->lastInsertId();
            } else {
                // UPDATE
                $stmtUp = $pdo->prepare("UPDATE internos_recebimento_cosmeticos SET entregue_por_tipo = ?, entregue_por_nome = ? WHERE id = ?");
                $stmtUp->execute([$_POST['entregue_por_tipo'], $_POST['entregue_por_nome'], $id_recebimento]);
                $pdo->prepare("DELETE FROM internos_recebimento_cosmeticos_itens WHERE id_recebimento = ?")->execute([$id_recebimento]);
            }

            // ITENS
            $stmtItem = $pdo->prepare("INSERT INTO internos_recebimento_cosmeticos_itens (id_recebimento, item, quantidade, detalhes) VALUES (?, ?, ?, ?)");
            if(isset($_POST['itens'])){
                foreach ($_POST['itens'] as $nomeItem => $dados) {
                    if ((int)$dados['qtd'] > 0) {
                        $stmtItem->execute([$id_recebimento, $nomeItem, (int)$dados['qtd'], $dados['detalhes'] ?? '']);
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'msg' => 'Salvo com sucesso!', 'id_novo' => $id_recebimento]);
        } catch (Exception $e) { $pdo->rollBack(); echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 4. EXCLUIR
    if ($_REQUEST['acao'] === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean(); header('Content-Type: application/json');
        try {
            // Restrição: Portaria não pode excluir
            if ($eh_portaria) {
                throw new Exception("Usuário da portaria não tem permissão para excluir registros.");
            }

            $pdo->prepare("DELETE FROM internos_recebimento_cosmeticos WHERE id = ?")->execute([$_POST['id']]);
            echo json_encode(['status' => 'success', 'msg' => 'Registro excluído.']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }

    // 5. MARCAR ENTREGUE
    if ($_REQUEST['acao'] === 'marcar_entregue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean(); header('Content-Type: application/json');
        try {
            $ids = explode(',', $_POST['ids_recebimento']);
            $stmt = $pdo->prepare("UPDATE internos_recebimento_cosmeticos SET data_entrega_interno = NOW() WHERE id = ? AND data_entrega_interno IS NULL");
            foreach ($ids as $id) $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) { echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]); }
        exit;
    }
}

// --- DADOS PARA A TELA ---
// Limites do Banco
$limites = $pdo->query("SELECT * FROM internos_recebimento_cosmeticos_limites ORDER BY item_nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filtros
$where = ["1=1"];
$params = [];
if (!empty($_GET['busca'])) {
    $where[] = "(i.nome LIKE ? OR i.ipen LIKE ?)";
    $params[] = "%{$_GET['busca']}%"; $params[] = "%{$_GET['busca']}%";
}
if (!empty($_GET['galeria'])) { $where[] = "i.galeria = ?"; $params[] = $_GET['galeria']; }
if (!empty($_GET['bloco']))   { $where[] = "i.bloco = ?"; $params[] = $_GET['bloco']; }
if (!empty($_GET['cela']))    { $where[] = "i.res = ?"; $params[] = $_GET['cela']; }

$sqlList = "SELECT r.*, i.nome as nome_interno, i.nome_social, i.galeria, i.bloco, i.res,
            (SELECT GROUP_CONCAT(CONCAT(qtd.quantidade, 'x ', qtd.item) SEPARATOR ', ') FROM internos_recebimento_cosmeticos_itens qtd WHERE qtd.id_recebimento = r.id) as resumo_itens
            FROM internos_recebimento_cosmeticos r
            JOIN internos i ON r.id_interno = i.ipen
            WHERE " . implode(" AND ", $where) . "
            ORDER BY r.data_recebimento DESC LIMIT 50";
try {
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $recebimentos = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $recebimentos = []; }

$galerias = $pdo->query("SELECT DISTINCT galeria FROM internos ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos = $pdo->query("SELECT DISTINCT bloco FROM internos ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);
?>

<script>
    window.pageTitle = 'Cosméticos (LGBT)';
    window.currentPage = 'internos_recebimento_cosmeticos.php';

    window.safeReload = function() {
        const query = $('#formFiltro').serialize();
        if(typeof loadPage === 'function') loadPage('paginas/internos_recebimento_cosmeticos.php?' + query);
        else window.location.href = 'paginas/internos_recebimento_cosmeticos.php?' + query;
    }
</script>

<style>
    .status-badge { font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    .bg-pendente { background: #ffeeba; color: #856404; }
    .bg-entregue { background: #d4edda; color: #155724; }
    .offcanvas-right { position: fixed; top: 0; right: 0; width: 600px; height: 100%; background: #fff; box-shadow: -10px 0 30px rgba(0,0,0,0.3); transform: translateX(100%); transition: transform 0.3s ease-in-out; z-index: 1060; display: flex; flex-direction: column; }
    .offcanvas-header { background: #e83e8c; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
    .offcanvas-body { padding: 20px; overflow-y: auto; flex: 1; }
    .search-results { position: absolute; width: 100%; z-index: 10000; background: white; border: 1px solid #ddd; max-height: 200px; overflow-y: auto; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
    .search-item { padding: 10px; cursor: pointer; border-bottom: 1px solid #eee; }
    .search-item:hover { background-color: #f0f4ff; }
    .items-container { border: 1px solid #dee2e6; border-radius: 5px; padding: 10px; background-color: #f8f9fa; }

    /* Dark Mode */
    body.dark-mode .offcanvas-right { background-color: #343a40; color: #fff; }
    body.dark-mode .offcanvas-header { background-color: #212529; border-bottom: 2px solid #e83e8c; }
    body.dark-mode .form-control { background-color: #3f474e; border-color: #6c757d; color: #fff; }
    body.dark-mode .search-results { background-color: #3f474e; border-color: #6c757d; }
    body.dark-mode .search-item { border-bottom-color: #4b545c; color: #fff; }
    body.dark-mode .search-item:hover { background-color: #4b545c; }
    body.dark-mode .items-container { background-color: #3f474e; border-color: #6c757d; }
</style>

<section class="content pt-3">
    <div class="container-fluid">

        <div class="alert alert-light shadow-sm border-left border-pink" style="border-left-width: 5px; border-left-color: #e83e8c;">
            <i class="fas fa-info-circle text-pink"></i> <strong>Regra:</strong> Entregas permitidas apenas para internos cadastrados como <strong>LGBT</strong>, uma vez por mês.
            <br><small>Observação para familiares: Trazer embalagens transparentes para transferência dos produtos.</small>
        </div>

        <div class="row mb-3">
            <div class="col-lg-3">
                <button class="btn btn-primary shadow-sm btn-block" style="background-color: #e83e8c; border-color: #e83e8c;" onclick="window.abrirNovoRecebimento()">
                    <i class="fas fa-plus-circle"></i> Novo Recebimento
                </button>
            </div>
            <div class="col-lg-9">
                <form class="form-inline float-right" id="formFiltro" onsubmit="return false;">
                    <div class="input-group input-group-sm mr-2 mb-1"><input type="text" class="form-control" name="busca" placeholder="Nome ou IPEN" value="<?= $_GET['busca']??'' ?>"></div>
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <select class="form-control" name="galeria"><option value="">Galeria</option><?php foreach($galerias as $g) echo "<option value='$g' ".($_GET['galeria']==$g?'selected':'').">$g</option>"; ?></select>
                    </div>
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <select class="form-control" name="bloco"><option value="">Bloco</option><?php foreach($blocos as $b) echo "<option value='$b' ".($_GET['bloco']==$b?'selected':'').">$b</option>"; ?></select>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-pink mb-1" onclick="window.safeReload()"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>

        <div class="card shadow">
            <div class="card-header border-0">
                <h3 class="card-title">Registros Recentes</h3>
                <div class="card-tools">
                    <?php if (!$eh_portaria): ?>
                        <button class="btn btn-success btn-sm" onclick="imprimirSelecionados('termo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-file-signature"></i> Termo</button>
                    <?php endif; ?>
                    <button class="btn btn-info btn-sm" onclick="imprimirSelecionados('recibo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-receipt"></i> Recibo</button>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" onclick="$('.chk-print').prop('checked', this.checked)"></th>
                            <th>Data</th>
                            <th>IPEN</th>
                            <th>Interno</th>
                            <th>Itens</th>
                            <th>Entregador</th>
                            <th>Status</th>
                            <th width="80" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recebimentos as $r):
                            $nome = $r['nome_social'] ? "<strong>{$r['nome_social']}</strong> <small>({$r['nome_interno']})</small>" : $r['nome_interno'];
                            $dtEntrega = $r['data_entrega_interno'] ? date('d/m/Y H:i', strtotime($r['data_entrega_interno'])) : '';
                        ?>
                        <tr>
                            <td><input type="checkbox" class="chk-print" value="<?= $r['id'] ?>" data-entregue="<?= $r['data_entrega_interno'] ? '1' : '0' ?>" data-data-entregue="<?= $dtEntrega ?>"></td>
                            <td><?= date('d/m/Y', strtotime($r['data_recebimento'])) ?></td>
                            <td><?= $r['id_interno'] ?></td>
                            <td><?= $nome ?><br><small class="text-muted"><?= "{$r['galeria']}-{$r['bloco']}-{$r['res']}" ?></small></td>
                            <td><small><?= $r['resumo_itens'] ?></small></td>
                            <td><?= $r['entregue_por_tipo'] ?>: <?= $r['entregue_por_nome'] ?></td>
                            <td><?= $r['data_entrega_interno'] ? "<span class='status-badge bg-entregue'>Entregue</span>" : "<span class='status-badge bg-pendente'>Pendente</span>" ?></td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <?php if (!$eh_portaria): ?>
                                        <button class="btn btn-warning text-white" onclick="window.editarRecebimento(<?= $r['id'] ?>)"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-danger" onclick="window.excluirRecebimento(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary" title="Apenas visualização" disabled>
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- OFFCANVAS -->
<div id="offcanvasNovo" class="offcanvas-right">
    <div class="offcanvas-header">
        <h5 class="m-0" id="offcanvasTitle"></h5>
        <button class="btn btn-sm btn-outline-light" onclick="window.fecharNovoRecebimento()"><i class="fas fa-times"></i></button>
    </div>
    <div class="offcanvas-body">
        <form id="formRegistrar">
            <input type="hidden" name="acao" value="salvar_recebimento">
            <input type="hidden" name="id_recebimento" id="hiddenIdRecebimento">
            <input type="hidden" name="ipen" id="hiddenIpen" required>

            <div class="form-group position-relative" id="groupBusca">
                <label>Buscar Interno (Apenas LGBT)</label>
                <input type="text" class="form-control" id="buscaInternoInput" placeholder="IPEN ou Nome..." autocomplete="off">
                <div id="sugestoesInterno" class="search-results"></div>
            </div>

            <div class="form-group">
                <label>Interno</label>
                <input type="text" class="form-control font-weight-bold" id="displayNomeInterno" readonly>
                <small id="alertLgbt" class="text-danger font-weight-bold" style="display:none">Este interno não é LGBT.</small>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="form-group"><label>Tipo</label><select class="form-control" name="entregue_por_tipo" id="inputTipo"><option>Visitante</option><option>Laboral</option><option>Advogado</option><option>Correios</option></select></div>
                </div>
                <div class="col-8">
                    <div class="form-group"><label>Nome Entregador</label><input type="text" class="form-control" name="entregue_por_nome" id="inputEntregador" required></div>
                </div>
            </div>

            <hr>
            <h6>Itens Permitidos</h6>
            <div class="items-container mb-3">
                <?php foreach($limites as $lim): $safeId = str_replace(' ', '_', $lim['item_nome']); ?>
                <div class="row item-row align-items-center mb-2 pb-2 border-bottom">
                    <div class="col-8 font-weight-bold">
                        <span><?= $lim['item_nome'] ?></span> <span class="badge badge-secondary"><?= $lim['quantidade_maxima'] ?></span>
                        <div class="small text-muted font-weight-normal"><?= $lim['regras'] ?></div>
                    </div>
                    <div class="col-4">
                        <input type="number" class="form-control form-control-sm text-center item-qtd"
                               id="qtd_<?= $safeId ?>" name="itens[<?= $lim['item_nome'] ?>][qtd]" min="0" max="<?= $lim['quantidade_maxima'] ?>" value="0">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" id="btnSalvar"><i class="fas fa-save"></i> Salvar</button>
        </form>
    </div>
</div>

<script>
    // UI Controls
    window.abrirNovoRecebimento = function() {
        document.getElementById('formRegistrar').reset();
        $('#hiddenIdRecebimento').val(''); $('#hiddenIpen').val(''); $('#displayNomeInterno').val('');
        $('#groupBusca').show(); $('#offcanvasTitle').html('<i class="fas fa-plus-circle"></i> Novo Recebimento');
        $('#btnSalvar').prop('disabled', true);
        document.getElementById('offcanvasNovo').style.transform = 'translateX(0)';
    }
    window.fecharNovoRecebimento = () => document.getElementById('offcanvasNovo').style.transform = 'translateX(100%)';

    // Pesquisa com Filtro LGBT Visual
    $('#buscaInternoInput').on('input', function() {
        const termo = $(this).val();
        if(termo.length < 3) { $('#sugestoesInterno').hide(); return; }

        if(this.timer) clearTimeout(this.timer);
        this.timer = setTimeout(async () => {
            const res = await fetch('paginas/internos_recebimento_cosmeticos.php?acao=buscar_interno&termo=' + encodeURIComponent(termo));
            const json = await res.json();
            let html = '';
            if(json.status === 'success') {
                json.dados.forEach(d => {
                    const nomeExib = d.nome_social ? `${d.nome_social} (${d.nome})` : d.nome;
                    const isLgbt = d.lgbt === 'S';
                    const colorClass = isLgbt ? 'text-success' : 'text-danger';
                    const icon = isLgbt ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-times-circle"></i>';

                    html += `<div class="search-item" onclick="selecionarInterno('${d.ipen}', '${nomeExib.replace(/'/g, "\\'")}', '${d.lgbt}')">
                                <div class="font-weight-bold">${d.ipen} - ${nomeExib}</div>
                                <small class="${colorClass}">${icon} LGBT: ${d.lgbt}</small>
                             </div>`;
                });
                $('#sugestoesInterno').html(html).show();
            }
        }, 300);
    });

    window.selecionarInterno = function(ipen, nome, lgbt) {
        $('#hiddenIpen').val(ipen);
        $('#displayNomeInterno').val(`${ipen} - ${nome}`);
        $('#sugestoesInterno').hide();
        $('#buscaInternoInput').val('');

        if(lgbt !== 'S') {
            $('#alertLgbt').show();
            $('#btnSalvar').prop('disabled', true);
            alert('Atenção: Interno NÃO é LGBT. Cadastro bloqueado.');
        } else {
            $('#alertLgbt').hide();
            $('#btnSalvar').prop('disabled', false);
        }
    }

    // Editar
    window.editarRecebimento = async function(id) {
        window.abrirNovoRecebimento();
        $('#offcanvasTitle').html('<i class="fas fa-edit"></i> Editar');
        $('#groupBusca').hide();
        $('#hiddenIdRecebimento').val(id);
        $('#btnSalvar').prop('disabled', false);

        const fd = new FormData(); fd.append('acao', 'get_recebimento'); fd.append('id', id);
        const res = await fetch('paginas/internos_recebimento_cosmeticos.php', {method:'POST', body: fd});
        const json = await res.json();

        if(json.status === 'success') {
            const d = json.dados;
            $('#hiddenIpen').val(d.id_interno);
            $('#displayNomeInterno').val(`${d.id_interno} - ${d.nome_social||d.nome_interno}`);
            $('#inputTipo').val(d.entregue_por_tipo);
            $('#inputEntregador').val(d.entregue_por_nome);

            json.itens.forEach(item => {
                const safeId = item.item.replace(/ /g, '_');
                $(`#qtd_${safeId}`).val(item.quantidade);
            });
        }
    }

    // Salvar
    $('#formRegistrar').on('submit', async function(e){
        e.preventDefault();
        try {
            const res = await fetch('paginas/internos_recebimento_cosmeticos.php', {method:'POST', body: new FormData(this)});
            const json = await res.json();
            if(json.status === 'success') {
                alert(json.msg);
                if(!$('#hiddenIdRecebimento').val() && confirm('Imprimir Recibo?')) {
                    window.open('paginas/imprimir_recibo_cosmeticos.php?ids=' + json.id_novo, '_blank');
                }
                window.fecharNovoRecebimento();
                window.safeReload();
            } else alert(json.msg);
        } catch(err) { alert('Erro: ' + err); }
    });

    window.excluirRecebimento = async function(id) {
        if(!confirm('Excluir?')) return;
        const fd = new FormData(); fd.append('acao','excluir'); fd.append('id',id);
        const res = await fetch('paginas/internos_recebimento_cosmeticos.php', {method:'POST', body: fd});
        const json = await res.json();
        if(json.status === 'success') window.safeReload();
    }

    window.imprimirSelecionados = async function(tipo) {
        let ids = [], jaEntregues = [];
        $('.chk-print:checked').each(function() { ids.push($(this).val()); if($(this).data('entregue') == 1) jaEntregues.push(1); });
        if(!ids.length) return alert('Selecione.');

        if(tipo === 'recibo') return window.open('paginas/imprimir_recibo_cosmeticos.php?ids=' + ids.join(','), '_blank');

        let acaoBanco = true;
        if(jaEntregues.length > 0) {
            if(!confirm('Reimprimir sem alterar data?')) return;
            acaoBanco = false;
        } else {
            if(!confirm('Gerar termo e marcar como ENTREGUE?')) return;
        }

        if(acaoBanco) {
            const fd = new FormData(); fd.append('acao','marcar_entregue'); fd.append('ids_recebimento',ids.join(','));
            await fetch('paginas/internos_recebimento_cosmeticos.php', {method:'POST', body: fd});
        }
        window.open('paginas/imprimir_termo_cosmeticos.php?ids=' + ids.join(','), '_blank');
        if(acaoBanco) window.safeReload();
    }
</script>
