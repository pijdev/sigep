<?php
// paginas/internos_recebimento_roupas.php

// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

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

// Verificar se usuário pode configurar datas (admin ou censura)
$pode_configurar_datas = (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true) ||
    (isset($_SESSION['perm_censura']) && $_SESSION['perm_censura'] > 0);

// --- AÇÕES DO BACKEND ---
if (isset($_REQUEST['acao'])) {

    // 1. BUSCAR INTERNO
    if ($_REQUEST['acao'] === 'buscar_interno') {
        ob_clean();
        header('Content-Type: application/json');
        $termo = trim($_REQUEST['termo']);
        try {
            // Busca aprimorada
            $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res, cor_roupa, kit FROM internos
                    WHERE (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?) AND status = 'A' LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $like = "%$termo%";
            $stmt->execute([$like, $like, $like]);
            echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 2. BUSCAR ÚLTIMO RECEBIMENTO DO INTERNO NO PERÍODO
    if ($_REQUEST['acao'] === 'get_ultimo_recebimento') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $ipen = $_POST['ipen'];
            $periodo_id = $_POST['periodo_id'];

            // Busca último recebimento do interno no período
            $stmt = $pdo->prepare("SELECT r.*, i.nome as nome_interno, i.nome_social, i.galeria, i.bloco, i.res
                                   FROM internos_recebimento_roupas r
                                   JOIN internos i ON r.id_interno = i.ipen
                                   WHERE r.id_interno = ? AND r.id_periodo = ?
                                   ORDER BY r.data_recebimento DESC LIMIT 1");
            $stmt->execute([$ipen, $periodo_id]);
            $ultimo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ultimo) {
                echo json_encode(['status' => 'success', 'existe' => false]);
                exit;
            }

            // Busca itens do último recebimento
            $stmtItens = $pdo->prepare("SELECT item, quantidade, detalhes FROM internos_recebimento_roupas_itens WHERE id_recebimento = ?");
            $stmtItens->execute([$ultimo['id']]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'existe' => true, 'dados' => $ultimo, 'itens' => $itens]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 3. BUSCAR DADOS PARA EDIÇÃO
    if ($_REQUEST['acao'] === 'get_recebimento') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            // Restrição: Portaria não pode editar
            if ($eh_portaria) {
                throw new Exception("Usuário da portaria não tem permissão para editar registros.");
            }

            $id = $_POST['id'];

            // Dados principais
            $stmt = $pdo->prepare("SELECT r.*, i.nome as nome_interno, i.nome_social, i.cor_roupa, i.kit
                                   FROM internos_recebimento_roupas r
                                   JOIN internos i ON r.id_interno = i.ipen
                                   WHERE r.id = ?");
            $stmt->execute([$id]);
            $dados = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dados) throw new Exception("Registro não encontrado.");

            // Itens
            $stmtItens = $pdo->prepare("SELECT item, quantidade, detalhes FROM internos_recebimento_roupas_itens WHERE id_recebimento = ?");
            $stmtItens->execute([$id]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'dados' => $dados, 'itens' => $itens]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 3. SALVAR (INSERIR OU EDITAR)
    if ($_REQUEST['acao'] === 'salvar_recebimento' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $pdo->beginTransaction();

            $id_recebimento = $_POST['id_recebimento'] ?? ''; // Se tiver ID, é edição

            // Restrição: Portaria só pode inserir, não editar
            if ($eh_portaria && !empty($id_recebimento)) {
                throw new Exception("Usuário da portaria não tem permissão para editar registros.");
            }

            $ipen = $_POST['ipen'];
            $periodo_id = 0;
            $ja_recebeu = false; // Inicializa variável

            // Busca período ativo para validar (apenas se for novo insert)
            if (empty($id_recebimento)) {
                $stmtP = $pdo->prepare("SELECT id, nome FROM internos_recebimento_roupas_periodos WHERE ? BETWEEN data_inicio AND data_fim AND ativo = 1 LIMIT 1");
                $stmtP->execute([date('Y-m-d')]);
                $periodo = $stmtP->fetch(PDO::FETCH_ASSOC);

                if (!$periodo) throw new Exception("Não há período ativo hoje.");
                $periodo_id = $periodo['id'];

                // Verifica duplicidade (apenas insert) - NÃO BLOQUEIA, APENAS MARCA COMO EXTRA
                $stmtCheck = $pdo->prepare("SELECT id FROM internos_recebimento_roupas WHERE id_interno = ? AND id_periodo = ?");
                $stmtCheck->execute([$ipen, $periodo_id]);
                $ja_recebeu = ($stmtCheck->rowCount() > 0);
            }

            if (empty($id_recebimento)) {
                // INSERT - Marca como extra se já recebeu no período
                $is_extra = $ja_recebeu ? 1 : 0;
                $stmtInsert = $pdo->prepare("INSERT INTO internos_recebimento_roupas (id_interno, id_periodo, data_recebimento, entregue_por_tipo, entregue_por_nome, cadastrado_por, is_extra) VALUES (?, ?, NOW(), ?, ?, ?, ?)");
                $stmtInsert->execute([$ipen, $periodo_id, $_POST['entregue_por_tipo'], $_POST['entregue_por_nome'], $usuario_logado, $is_extra]);
                $id_recebimento = $pdo->lastInsertId();
            } else {
                // UPDATE (Edição)
                // Não altera período nem interno, apenas quem entregou e itens
                $stmtUp = $pdo->prepare("UPDATE internos_recebimento_roupas SET entregue_por_tipo = ?, entregue_por_nome = ? WHERE id = ?");
                $stmtUp->execute([$_POST['entregue_por_tipo'], $_POST['entregue_por_nome'], $id_recebimento]);

                // Limpa itens antigos para reinserir
                $pdo->prepare("DELETE FROM internos_recebimento_roupas_itens WHERE id_recebimento = ?")->execute([$id_recebimento]);
            }

            // Inserir Itens (Comum para Insert e Update)
            $stmtItem = $pdo->prepare("INSERT INTO internos_recebimento_roupas_itens (id_recebimento, item, quantidade, detalhes) VALUES (?, ?, ?, ?)");
            if (isset($_POST['itens'])) {
                foreach ($_POST['itens'] as $nomeItem => $dados) {
                    if ((int)$dados['qtd'] > 0) {
                        $stmtItem->execute([$id_recebimento, $nomeItem, (int)$dados['qtd'], $dados['detalhes'] ?? '']);
                    }
                }
            }

            $pdo->commit();
            echo json_encode(['status' => 'success', 'msg' => 'Salvo com sucesso!', 'id_novo' => $id_recebimento]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 4. EXCLUIR
    if ($_REQUEST['acao'] === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            // Restrição: Portaria não pode excluir
            if ($eh_portaria) {
                throw new Exception("Usuário da portaria não tem permissão para excluir registros.");
            }

            $stmt = $pdo->prepare("DELETE FROM internos_recebimento_roupas WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            echo json_encode(['status' => 'success', 'msg' => 'Registro excluído.']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 5. SALVAR CONFIG DATAS
    if ($_REQUEST['acao'] === 'salvar_config' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            if (isset($_POST['periodos']) && is_array($_POST['periodos'])) {
                $sql = "UPDATE internos_recebimento_roupas_periodos SET data_inicio = ?, data_fim = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                foreach ($_POST['periodos'] as $id => $datas) $stmt->execute([$datas['inicio'], $datas['fim'], $id]);
                echo json_encode(['status' => 'success', 'msg' => 'Datas atualizadas!']);
            } else throw new Exception("Dados inválidos.");
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 6. MARCAR ENTREGUE
    if ($_REQUEST['acao'] === 'marcar_entregue' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $ids = explode(',', $_POST['ids_recebimento']);
            $stmt = $pdo->prepare("UPDATE internos_recebimento_roupas SET data_entrega_interno = NOW() WHERE id = ? AND data_entrega_interno IS NULL");
            foreach ($ids as $id) $stmt->execute([$id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // 7. CONSULTAR ROUPAS CIVIS
    if ($_REQUEST['acao'] === 'consultar_roupas') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $where = ["1=1"];
            $params = [];

            // Filtros
            if (!empty($_REQUEST['busca_interno'])) {
                $where[] = "(i.nome LIKE ? OR i.ipen LIKE ? OR i.nome_social LIKE ?)";
                $termo = "%" . $_REQUEST['busca_interno'] . "%";
                $params[] = $termo;
                $params[] = $termo;
                $params[] = $termo;
            }
            if (!empty($_REQUEST['busca_item'])) {
                $where[] = "JSON_SEARCH(rc.pecas, 'one', ?) IS NOT NULL";
                $params[] = "%" . $_REQUEST['busca_item'] . "%";
            }
            if (!empty($_REQUEST['data_inicio'])) {
                $where[] = "rc.criado_em >= ?";
                $params[] = $_REQUEST['data_inicio'];
            }
            if (!empty($_REQUEST['data_fim'])) {
                $where[] = "rc.criado_em <= ?";
                $params[] = $_REQUEST['data_fim'] . ' 23:59:59';
            }

            $sql = "SELECT rc.id, rc.ipen, rc.nome, rc.pecas, rc.criado_por, rc.criado_em,
                           i.nome_social, i.galeria, i.bloco, i.res
                    FROM internos_rouparia_civil rc
                    LEFT JOIN internos i ON rc.ipen = i.ipen
                    WHERE " . implode(" AND ", $where) . "
                    ORDER BY rc.criado_em DESC, i.nome ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Processar dados JSON das peças
            foreach ($dados as &$dado) {
                $pecas = json_decode($dado['pecas'], true);
                $dado['pecas_processadas'] = [];

                if (is_array($pecas)) {
                    // Processar predefinidos
                    if (isset($pecas['predefinidos']) && is_array($pecas['predefinidos'])) {
                        foreach ($pecas['predefinidos'] as $item) {
                            if (isset($item['tipo']) && isset($item['quantidade'])) {
                                $dado['pecas_processadas'][] = [
                                    'item' => $item['tipo'],
                                    'quantidade' => $item['quantidade']
                                ];
                            }
                        }
                    }

                    // Processar outros (se existir)
                    if (isset($pecas['outros']) && is_array($pecas['outros'])) {
                        foreach ($pecas['outros'] as $item) {
                            if (isset($item['tipo']) && isset($item['quantidade'])) {
                                $dado['pecas_processadas'][] = [
                                    'item' => $item['tipo'],
                                    'quantidade' => $item['quantidade']
                                ];
                            }
                        }
                    }
                }

                unset($dado['pecas']); // Remover JSON original
            }

            echo json_encode(['status' => 'success', 'dados' => $dados]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }
}

// --- DADOS PARA A TELA ---
$stmtTodosPeriodos = $pdo->query("SELECT * FROM internos_recebimento_roupas_periodos ORDER BY id ASC");
$todosPeriodos = $stmtTodosPeriodos->fetchAll(PDO::FETCH_ASSOC);

$hoje = date('Y-m-d');
$periodoAtivo = null;
foreach ($todosPeriodos as $p) {
    if ($p['ativo'] && $hoje >= $p['data_inicio'] && $hoje <= $p['data_fim']) {
        $periodoAtivo = $p;
        break;
    }
}

$idPeriodoRef = $periodoAtivo ? $periodoAtivo['id'] : ($todosPeriodos[0]['id'] ?? 0);
$limites = [];
if ($idPeriodoRef) {
    $stmtLim = $pdo->prepare("SELECT * FROM internos_recebimento_roupas_limites WHERE id_periodo = ?");
    $stmtLim->execute([$idPeriodoRef]);
    $limites = $stmtLim->fetchAll(PDO::FETCH_ASSOC);
}

// Filtros
$where = ["1=1"];
$params = [];
if (!empty($_GET['busca'])) {
    $where[] = "(i.nome LIKE ? OR i.ipen LIKE ?)";
    $params[] = "%{$_GET['busca']}%";
    $params[] = "%{$_GET['busca']}%";
}
if (!empty($_GET['galeria'])) {
    $where[] = "i.galeria = ?";
    $params[] = $_GET['galeria'];
}
if (!empty($_GET['bloco'])) {
    $where[] = "i.bloco = ?";
    $params[] = $_GET['bloco'];
}
if (!empty($_GET['cela'])) {
    $where[] = "i.res = ?";
    $params[] = $_GET['cela'];
}

$sqlList = "SELECT r.*, i.nome as nome_interno, i.nome_social, i.galeria, i.bloco, i.res, i.kit, i.cor_roupa,
            (SELECT GROUP_CONCAT(CONCAT(qtd.quantidade, 'x ', qtd.item) SEPARATOR ', ') FROM internos_recebimento_roupas_itens qtd WHERE qtd.id_recebimento = r.id) as resumo_itens
            FROM internos_recebimento_roupas r
            JOIN internos i ON r.id_interno = i.ipen
            WHERE " . implode(" AND ", $where) . "
            ORDER BY r.data_recebimento DESC LIMIT 50";
try {
    $stmtList = $pdo->prepare($sqlList);
    $stmtList->execute($params);
    $recebimentos = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recebimentos = [];
}

// Dados para Selects de Filtro
$galerias = $pdo->query("SELECT DISTINCT galeria FROM internos ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos = $pdo->query("SELECT DISTINCT bloco FROM internos ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);
?>

<script>
    window.pageTitle = 'Recebimento de Roupas';
    window.currentPage = 'internos_recebimento_roupas.php';

    window.safeReload = function() {
        if (typeof loadPage === 'function') loadPage('paginas/internos_recebimento_roupas.php');
        else if (typeof window.reloadContent === 'function') window.reloadContent('paginas/internos_recebimento_roupas.php');
        else window.location.reload();
    }
</script>

<style>
    .status-badge {
        font-size: 0.8rem;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
    }

    .bg-pendente {
        background: #ffeeba;
        color: #856404;
    }

    .bg-entregue {
        background: #d4edda;
        color: #155724;
    }

    .bg-extra {
        background: #ffc107;
        color: #212529;
    }

    /* Offcanvas */
    .offcanvas-right {
        position: fixed;
        top: 0;
        right: 0;
        width: 600px;
        height: 100%;
        background: #fff;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: transform 0.3s ease-in-out;
        z-index: 1060;
        display: flex;
        flex-direction: column;
    }

    .offcanvas-header {
        background: #343a40;
        color: white;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid #007bff;
    }

    .offcanvas-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    /* Search Results - FIX Z-INDEX */
    .search-results {
        position: absolute;
        width: 100%;
        z-index: 10000;
        background: white;
        border: 1px solid #ddd;
        max-height: 200px;
        overflow-y: auto;
        display: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .search-item {
        padding: 10px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
    }

    .search-item:hover {
        background-color: #f0f4ff;
    }

    /* Dark Mode */
    body.dark-mode .offcanvas-right {
        background-color: #343a40;
        color: #fff;
        box-shadow: -10px 0 30px rgba(0, 0, 0, 0.7);
    }

    body.dark-mode .offcanvas-header {
        background-color: #212529;
        border-bottom-color: #3f6791;
    }

    body.dark-mode .form-control {
        background-color: #3f474e;
        border-color: #6c757d;
        color: #fff;
    }

    body.dark-mode .form-control:focus {
        background-color: #454d55;
        border-color: #007bff;
    }

    body.dark-mode .form-control[readonly] {
        background-color: #2f353a;
        opacity: 1;
    }

    body.dark-mode .search-results {
        background-color: #3f474e;
        border-color: #6c757d;
    }

    body.dark-mode .search-item {
        border-bottom-color: #4b545c;
        color: #fff;
    }

    body.dark-mode .search-item:hover {
        background-color: #4b545c;
    }

    body.dark-mode .search-item small {
        color: #ced4da !important;
    }

    body.dark-mode .items-container {
        background-color: #3f474e;
        border-color: #6c757d;
    }

    body.dark-mode .card {
        background-color: #343a40;
        color: #fff;
    }

    body.dark-mode .table-hover tbody tr:hover {
        color: #fff;
        background-color: rgba(255, 255, 255, 0.075);
    }

    body.dark-mode .input-group-text {
        background-color: #3f474e;
        border-color: #6c757d;
        color: #fff;
    }

    .items-container {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        background-color: #f8f9fa;
    }
</style>

<section class="content pt-3">
    <div class="container-fluid">

        <?php if ($periodoAtivo): ?>
            <div class="alert alert-info shadow-sm py-2">
                <i class="fas fa-check-circle"></i> Período Ativo: <strong><?= $periodoAtivo['nome'] ?></strong>
                (Até <?= date('d/m/Y', strtotime($periodoAtivo['data_fim'])) ?>)
            </div>
        <?php else: ?>
            <div class="alert alert-danger shadow-sm py-2">
                <i class="fas fa-exclamation-triangle"></i> <strong>Atenção!</strong> Não há período ativo hoje.
                <a href="#" onclick="window.abrirConfig()" class="text-white font-weight-bold ml-2" style="text-decoration: underline;">Configurar Datas</a>
            </div>
        <?php endif; ?>

        <!-- BARRA DE FERRAMENTAS -->
        <div class="row mb-3">
            <div class="col-lg-4 d-flex gap-2 mb-2">
                <button class="btn btn-primary shadow-sm mr-2" <?= !$periodoAtivo ? 'disabled' : '' ?> onclick="window.abrirNovoRecebimento()">
                    <i class="fas fa-plus-circle"></i> Novo
                </button>
                <button class="btn btn-warning shadow-sm" onclick="window.abrirConsultaRoupas()" style="color: black;">
                    <i class="fas fa-search"></i> Roupas Civis
                </button>
                <?php if ($pode_configurar_datas): ?>
                    <div class="ml-2">
                        <button class="btn btn-secondary shadow-sm" onclick="window.abrirConfig()">
                            <i class="fas fa-calendar-alt"></i> Datas
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-8">
                <form class="form-inline float-right" id="formFiltro" onsubmit="return false;">
                    <div class="input-group input-group-sm mr-2 mb-1">
                        <input type="text" class="form-control" name="busca" placeholder="Nome ou IPEN" value="<?= $_GET['busca'] ?? '' ?>">
                    </div>

                    <div class="input-group input-group-sm mr-2 mb-1">
                        <select class="form-control" name="galeria">
                            <option value="">Galeria</option>
                            <?php foreach ($galerias as $g) echo "<option value='$g' " . ($_GET['galeria'] == $g ? 'selected' : '') . ">$g</option>"; ?>
                        </select>
                    </div>

                    <div class="input-group input-group-sm mr-2 mb-1">
                        <select class="form-control" name="bloco">
                            <option value="">Bloco</option>
                            <?php foreach ($blocos as $b) echo "<option value='$b' " . ($_GET['bloco'] == $b ? 'selected' : '') . ">$b</option>"; ?>
                        </select>
                    </div>

                    <div class="input-group input-group-sm mr-2 mb-1">
                        <input type="text" class="form-control" name="cela" placeholder="Cela" size="5" value="<?= $_GET['cela'] ?? '' ?>">
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" onclick="window.safeReloadWithFilters()">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- TABELA -->
        <div class="card shadow">
            <div class="card-header border-0">
                <h3 class="card-title">Registros Recentes</h3>
                <div class="card-tools">
                    <?php if (!$eh_portaria): ?>
                        <button class="btn btn-success btn-sm" onclick="imprimirSelecionados('termo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-file-signature"></i> Gerar Termo</button>
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
                            <th>Local</th>
                            <th>Kit</th>
                            <th>Itens</th>
                            <th>Entregador</th>
                            <th>Status</th>
                            <th width="80" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recebimentos as $r):
                            $nome = $r['nome_social'] ? "<strong>{$r['nome_social']}</strong> <small>({$r['nome_interno']})</small>" : $r['nome_interno'];
                            $dtEntrega = $r['data_entrega_interno'] ? date('d/m/Y H:i', strtotime($r['data_entrega_interno'])) : '';
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="chk-print" value="<?= $r['id'] ?>"
                                        data-entregue="<?= $r['data_entrega_interno'] ? '1' : '0' ?>"
                                        data-data-entregue="<?= $dtEntrega ?>">
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($r['data_recebimento'])) ?></td>
                                <td><?= $r['id_interno'] ?></td>
                                <td><?= $nome ?><br><small class="text-muted"></small></td>
                                <td><small class="text-muted"><?= "{$r['galeria']}{$r['bloco']}-{$r['res']}" ?></small></td>
                                <td>
                                    <?php
                                    $kit = $r['kit'];
                                    $corRoupa = $r['cor_roupa'] ?: 'Laranja';
                                    $corIcon = $corRoupa === 'Laranja' ? 'text-warning' : 'text-success';
                                    $iconCamiseta = '<i class="fas fa-tshirt ' . $corIcon . '" title="Cor da roupa: ' . $corRoupa . '"></i>';

                                    if ($kit && $kit > 0) {
                                        echo '<span class="badge badge-primary">' . $kit . '</span> ' . $iconCamiseta;
                                    } else {
                                        echo '<span class="text-muted">Sem kit</span> ' . $iconCamiseta;
                                    }
                                    ?>
                                </td>
                                <td><small><?= $r['resumo_itens'] ?></small></td>
                                <td><?= $r['entregue_por_tipo'] ?>: <?= $r['entregue_por_nome'] ?></td>
                                <td>
                                    <?php if ($r['is_extra']): ?>
                                        <span class='status-badge bg-extra'><i class="fas fa-exclamation-circle"></i> EXTRA</span>
                                    <?php endif; ?>
                                    <?php if ($r['data_entrega_interno']): ?>
                                        <span class='status-badge bg-entregue'>Entregue <?= date('d/m', strtotime($r['data_entrega_interno'])) ?></span>
                                    <?php else: ?>
                                        <span class='status-badge bg-pendente'>Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <?php if (!$eh_portaria): ?>
                                            <button class="btn btn-warning text-white" title="Editar" onclick="window.editarRecebimento(<?= $r['id'] ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger" title="Excluir" onclick="window.excluirRecebimento(<?= $r['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<!-- OFFCANVAS: NOVO/EDITAR RECEBIMENTO -->
<div id="offcanvasNovo" class="offcanvas-right">
    <div class="offcanvas-header">
        <h5 class="m-0" id="offcanvasTitle"><i class="fas fa-box-open"></i> Novo Recebimento</h5>
        <button class="btn btn-sm btn-outline-light" onclick="window.fecharNovoRecebimento()"><i class="fas fa-times"></i></button>
    </div>
    <div class="offcanvas-body">
        <form id="formRegistrar">
            <input type="hidden" name="acao" value="salvar_recebimento">
            <input type="hidden" name="id_recebimento" id="hiddenIdRecebimento">
            <input type="hidden" name="ipen" id="hiddenIpen" required>
            <input type="hidden" name="cor_roupa_interno" id="hiddenCorRoupa">

            <div class="form-group position-relative" id="groupBusca">
                <label>Buscar Interno</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="buscaInternoInput" placeholder="IPEN ou Nome..." autocomplete="off">
                    <div class="input-group-append">
                        <span class="input-group-text" id="search-spinner" style="display:none"><i class="fas fa-spinner fa-spin"></i></span>
                    </div>
                </div>
                <div id="sugestoesInterno" class="search-results"></div>
            </div>

            <div class="form-group">
                <label>Interno Selecionado</label>
                <input type="text" class="form-control font-weight-bold" id="displayNomeInterno" readonly>
                <small id="msgCorInterno" class="form-text text-muted"></small>
            </div>

            <div class="row">
                <div class="col-4">
                    <div class="form-group">
                        <label>Tipo</label>
                        <select class="form-control" name="entregue_por_tipo" id="inputTipo">
                            <option value="Visitante">Visitante</option>
                            <option value="Advogado">Advogado</option>
                        </select>
                    </div>
                </div>
                <div class="col-8">
                    <div class="form-group">
                        <label>Nome do Entregador</label>
                        <input type="text" class="form-control" name="entregue_por_nome" id="inputEntregador" required>
                    </div>
                </div>
            </div>

            <hr>
            <h6>Itens Permitidos (<?= $periodoAtivo['nome'] ?? 'Inativo' ?>)</h6>
            <div class="items-container mb-3">
                <?php if (empty($limites)): ?>
                    <p class="text-center text-muted p-2">Nenhum período ativo. Configure as datas.</p>
                <?php else: ?>
                    <?php foreach ($limites as $lim):
                        // ID único para os inputs para facilitar preenchimento via JS na edição
                        $safeId = str_replace(' ', '_', $lim['item_nome']);
                    ?>
                        <div class="row item-row align-items-center mb-2 pb-2 border-bottom">
                            <div class="col-12 font-weight-bold d-flex justify-content-between">
                                <span><?= $lim['item_nome'] ?></span>
                                <span class="badge badge-secondary">Max: <?= $lim['quantidade_maxima'] ?></span>
                            </div>
                            <div class="col-3 mt-1">
                                <input type="number" class="form-control form-control-sm text-center item-qtd"
                                    id="qtd_<?= $safeId ?>"
                                    name="itens[<?= $lim['item_nome'] ?>][qtd]" min="0" max="<?= $lim['quantidade_maxima'] ?>" value="0">
                            </div>
                            <div class="col-9 mt-1">
                                <?php if (strpos(strtolower($lim['item_nome']), 'calça') !== false || strpos(strtolower($lim['item_nome']), 'blusa') !== false || strpos(strtolower($lim['item_nome']), 'camiseta') !== false || strpos(strtolower($lim['item_nome']), 'bermuda') !== false || strpos(strtolower($lim['item_nome']), 'casaco') !== false || strpos(strtolower($lim['item_nome']), 'moletom') !== false): ?>
                                    <select class="form-control form-control-sm select-cor-roupa item-detalhe" id="det_<?= $safeId ?>" name="itens[<?= $lim['item_nome'] ?>][detalhes]">
                                        <option value="Laranja">Laranja</option>
                                        <option value="Verde">Verde</option>
                                    </select>
                                <?php elseif (strpos(strtolower($lim['item_nome']), 'meia') !== false): ?>
                                    <select class="form-control form-control-sm item-detalhe" id="det_<?= $safeId ?>" name="itens[<?= $lim['item_nome'] ?>][detalhes]">
                                        <option value="Branca">Branca</option>
                                        <option value="Cinza">Cinza</option>
                                    </select>
                                <?php elseif (strpos(strtolower($lim['item_nome']), 'toalha') !== false || strpos(strtolower($lim['item_nome']), 'lençol') !== false || strpos(strtolower($lim['item_nome']), 'lencol') !== false): ?>
                                    <select class="form-control form-control-sm select-cor-roupa item-detalhe" id="det_<?= $safeId ?>" name="itens[<?= $lim['item_nome'] ?>][detalhes]">
                                        <option value="Branco">Branco</option>
                                        <option value="Laranja">Laranja</option>
                                    </select>
                                <?php elseif (in_array($lim['item_nome'], ['Cueca', 'Chinelo'])): ?>
                                    <input type="text" class="form-control form-control-sm" value="Branco" readonly>
                                    <input type="hidden" name="itens[<?= $lim['item_nome'] ?>][detalhes]" value="Branco">
                                <?php else: ?>
                                    <input type="text" class="form-control form-control-sm item-detalhe" id="det_<?= $safeId ?>" name="itens[<?= $lim['item_nome'] ?>][detalhes]" placeholder="Detalhes">
                                <?php endif; ?>
                            </div>
                            <div class="col-12"><small class="text-muted" style="font-size:0.75rem"><?= $lim['regras_especificas'] ?></small></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- OFFCANVAS: DATAS -->
<div id="offcanvasConfig" class="offcanvas-right">
    <div class="offcanvas-header">
        <h5 class="m-0"><i class="fas fa-calendar-alt"></i> Períodos</h5>
        <button class="btn btn-sm btn-outline-light" onclick="window.fecharConfig()"><i class="fas fa-times"></i></button>
    </div>
    <div class="offcanvas-body">
        <form id="formConfig" onsubmit="window.salvarConfig(event)">
            <input type="hidden" name="acao" value="salvar_config">
            <?php foreach ($todosPeriodos as $per):
                $isVerao = stripos($per['nome'], 'verão') !== false;
                $icon = $isVerao ? 'fa-sun' : 'fa-snowflake';
                $color = $isVerao ? 'text-warning' : 'text-info';
            ?>
                <div class="card mb-3 border-secondary">
                    <div class="card-header bg-dark py-2">
                        <i class="fas <?= $icon ?> <?= $color ?> mr-2"></i> <?= $per['nome'] ?>
                    </div>
                    <div class="card-body p-2">
                        <div class="form-group mb-1">
                            <label class="small m-0">Início</label>
                            <input type="date" class="form-control form-control-sm" name="periodos[<?= $per['id'] ?>][inicio]" value="<?= $per['data_inicio'] ?>" required>
                        </div>
                        <div class="form-group mb-0">
                            <label class="small m-0">Fim</label>
                            <input type="date" class="form-control form-control-sm" name="periodos[<?= $per['id'] ?>][fim]" value="<?= $per['data_fim'] ?>" required>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-success btn-block mt-3"><i class="fas fa-save"></i> Salvar Datas</button>
        </form>
    </div>
</div>

<!-- MODAL: CONSULTA ROUPAS CIVIS -->
<div class="modal fade" id="modalConsultaRoupas" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search"></i> Consulta de Roupas Civis Cadastradas</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- FORMULÁRIO DE PESQUISA -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-filter"></i> Filtros de Pesquisa</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label>Interno (Nome/IPEN)</label>
                                <input type="text" class="form-control" id="consultaBuscaInterno" placeholder="Nome ou IPEN...">
                            </div>
                            <div class="col-md-3">
                                <label>Item</label>
                                <input type="text" class="form-control" id="consultaBuscaItem" placeholder="Camiseta, Calça, etc...">
                            </div>
                            <div class="col-md-2">
                                <label>Data Início</label>
                                <input type="date" class="form-control" id="consultaDataInicio">
                            </div>
                            <div class="col-md-2">
                                <label>Data Fim</label>
                                <input type="date" class="form-control" id="consultaDataFim">
                            </div>
                            <div class="col-md-2">
                                <label>&nbsp;</label><br>
                                <button type="button" class="btn btn-primary btn-block" onclick="window.buscarConsultaRoupas()">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TABELA DE RESULTADOS -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-list"></i> Resultados</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped" id="tabelaConsultaRoupas">
                                <thead>
                                    <tr>
                                        <th>Data Cadastro</th>
                                        <th>IPEN</th>
                                        <th>Interno</th>
                                        <th>Local</th>
                                        <th>Roupas Cadastradas</th>
                                        <th>Cadastrado Por</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            Use os filtros acima para pesquisar registros
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- CONTROLES UI ---
    window.abrirNovoRecebimento = function() {
        // Reset form
        document.getElementById('formRegistrar').reset();
        $('#hiddenIdRecebimento').val('');
        $('#hiddenIpen').val('');
        $('#displayNomeInterno').val('');
        $('#groupBusca').show(); // Mostra busca
        $('#offcanvasTitle').html('<i class="fas fa-box-open"></i> Novo Recebimento');

        document.getElementById('offcanvasNovo').style.transform = 'translateX(0)';
    }

    window.fecharNovoRecebimento = function() {
        document.getElementById('offcanvasNovo').style.transform = 'translateX(100%)';
    }
    window.abrirConfig = function() {
        document.getElementById('offcanvasConfig').style.transform = 'translateX(0)';
    }
    window.fecharConfig = function() {
        document.getElementById('offcanvasConfig').style.transform = 'translateX(100%)';
    }

    // --- CONTROLES MODAL CONSULTA ---
    window.abrirConsultaRoupas = function() {
        $('#modalConsultaRoupas').modal('show');
        // Limpar filtros ao abrir
        $('#consultaBuscaInterno, #consultaBuscaItem, #consultaDataInicio, #consultaDataFim').val('');
        $('#tabelaConsultaRoupas tbody').html('<tr><td colspan="10" class="text-center text-muted">Use os filtros acima para pesquisar registros</td></tr>');
    }

    // Adicionar atalho Enter para busca
    $('#consultaBuscaInterno, #consultaBuscaItem, #consultaDataInicio, #consultaDataFim').on('keypress', function(e) {
        if (e.which === 13) { // Enter
            e.preventDefault();
            window.buscarConsultaRoupas();
        }
    });

    window.buscarConsultaRoupas = async function() {
        const params = new URLSearchParams();
        params.append('acao', 'consultar_roupas');

        const buscaInterno = $('#consultaBuscaInterno').val().trim();
        const buscaItem = $('#consultaBuscaItem').val().trim();
        const dataInicio = $('#consultaDataInicio').val();
        const dataFim = $('#consultaDataFim').val();

        if (buscaInterno) params.append('busca_interno', buscaInterno);
        if (buscaItem) params.append('busca_item', buscaItem);
        if (dataInicio) params.append('data_inicio', dataInicio);
        if (dataFim) params.append('data_fim', dataFim);

        try {
            const res = await fetch('paginas/internos_recebimento_roupas.php?' + params.toString());
            const json = await res.json();

            if (json.status === 'success') {
                let html = '';
                if (json.dados.length === 0) {
                    html = '<tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado</td></tr>';
                } else {
                    json.dados.forEach(d => {
                        const nome = d.nome_social ? `${d.nome_social} (${d.nome})` : d.nome;
                        const dataCadastro = new Date(d.criado_em).toLocaleString('pt-BR');
                        const local = d.galeria && d.bloco && d.res ? `${d.galeria}${d.bloco}-${d.res}` : '-';

                        // Processar peças cadastradas
                        let roupasHtml = '';
                        if (d.pecas_processadas && Array.isArray(d.pecas_processadas)) {
                            roupasHtml = d.pecas_processadas.map(peca =>
                                `<span class="badge badge-info">${peca.quantidade}x ${peca.item}</span>`
                            ).join(' ');
                        }

                        html += `
                            <tr>
                                <td>${dataCadastro}</td>
                                <td>${d.ipen}</td>
                                <td>${nome}</td>
                                <td><small>${local}</small></td>
                                <td>${roupasHtml}</td>
                                <td><small>${d.criado_por}</small></td>
                            </tr>
                        `;
                    });
                }
                $('#tabelaConsultaRoupas tbody').html(html);
            } else {
                alert(json.msg || 'Erro ao buscar dados.');
            }
        } catch (e) {
            alert('Erro na comunicação com o servidor.');
            console.error(e);
        }
    }

    window.safeReloadWithFilters = function() {
        const query = $('#formFiltro').serialize();
        if (typeof loadPage === 'function') loadPage('paginas/internos_recebimento_roupas.php?' + query);
        else if (typeof window.reloadContent === 'function') window.reloadContent('paginas/internos_recebimento_roupas.php?' + query);
        else window.location.href = 'paginas/internos_recebimento_roupas.php?' + query;
    }

    // --- PESQUISA OTIMIZADA (oninput) ---
    $('#buscaInternoInput').on('input', function() {
        const termo = $(this).val();
        if (termo.length < 3) {
            $('#sugestoesInterno').hide();
            return;
        }

        // Debounce simples
        if (this.timer) clearTimeout(this.timer);
        $('#search-spinner').show();

        this.timer = setTimeout(async () => {
            try {
                const res = await fetch('paginas/internos_recebimento_roupas.php?acao=buscar_interno&termo=' + encodeURIComponent(termo));
                const json = await res.json();

                let html = '';
                if (json.status === 'success' && json.dados.length > 0) {
                    json.dados.forEach(d => {
                        const nomeExib = d.nome_social ? `${d.nome_social} (${d.nome})` : d.nome;
                        const safeNome = nomeExib.replace(/'/g, "\\'");
                        const cor = d.cor_roupa || 'Laranja';
                        const kit = d.kit || 0;
                        html += `<div class="search-item" onclick="selecionarInterno('${d.ipen}', '${safeNome}', '${cor}', ${kit})">
                                    <div class="font-weight-bold text-primary">${d.ipen}</div>
                                    <div>${nomeExib}</div>
                                    <small class="text-muted">Loc: ${d.galeria}-${d.bloco}-${d.res} | Cor: ${cor} | Kit: ${kit > 0 ? kit : 'Sem kit'}</small>
                                 </div>`;
                    });
                    $('#sugestoesInterno').html(html).show();
                } else {
                    $('#sugestoesInterno').html('<div class="p-3 text-muted text-center">Nenhum encontrado.</div>').show();
                }
            } catch (e) {
                console.error(e);
            } finally {
                $('#search-spinner').hide();
            }
        }, 300);
    });

    window.selecionarInterno = function(ipen, nome, cor, kit) {
        $('#hiddenIpen').val(ipen);
        $('#hiddenCorRoupa').val(cor);
        $('#displayNomeInterno').val(`${ipen} - ${nome}`);
        $('#msgCorInterno').html(`Roupa Cadastrada: <strong>${cor}</strong> | Kit: <strong>${kit > 0 ? kit : 'Sem kit'}</strong>`);
        $('#sugestoesInterno').hide();
        $('#buscaInternoInput').val('');
    }

    // --- EDIÇÃO ---
    window.editarRecebimento = async function(id) {
        window.abrirNovoRecebimento(); // Abre limpo primeiro
        $('#offcanvasTitle').html('<i class="fas fa-edit"></i> Editar Recebimento');
        $('#groupBusca').hide(); // Esconde busca, pois não muda o interno na edição
        $('#hiddenIdRecebimento').val(id);

        try {
            const fd = new FormData();
            fd.append('acao', 'get_recebimento');
            fd.append('id', id);
            const res = await fetch('paginas/internos_recebimento_roupas.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();

            if (json.status === 'success') {
                const d = json.dados;
                // Preenche dados principais
                $('#hiddenIpen').val(d.id_interno);
                $('#hiddenCorRoupa').val(d.cor_roupa || 'Laranja');
                $('#displayNomeInterno').val(`${d.id_interno} - ${d.nome_social || d.nome_interno}`);
                $('#inputTipo').val(d.entregue_por_tipo);
                $('#inputEntregador').val(d.entregue_por_nome);

                // Preenche informações de kit
                const kitInfo = d.kit && d.kit > 0 ? `Kit: ${d.kit}` : 'Kit: Sem kit';
                $('#msgCorInterno').html(`Roupa Cadastrada: <strong>${d.cor_roupa || 'Laranja'}</strong> | <strong>${kitInfo}</strong>`);

                // Preenche Itens
                json.itens.forEach(item => {
                    const safeId = item.item.replace(/ /g, '_');
                    $(`#qtd_${safeId}`).val(item.quantidade);
                    if (item.detalhes) $(`#det_${safeId}`).val(item.detalhes);
                });
            } else {
                alert(json.msg);
                window.fecharNovoRecebimento();
            }
        } catch (e) {
            alert("Erro ao carregar dados.");
            window.fecharNovoRecebimento();
        }
    }

    // --- SALVAR ---
    $('#formRegistrar').on('submit', async function(e) {
        e.preventDefault();
        if (!$('#hiddenIpen').val()) return alert('Selecione um interno!');

        // Validação Cor
        const corSistema = $('#hiddenCorRoupa').val().trim().toLowerCase();
        let conflitoCor = false,
            corSel = '';
        $('.select-cor-roupa').each(function() {
            if ($(this).closest('.row').find('input[type="number"]').val() > 0) {
                let val = $(this).val().toLowerCase();
                if (corSistema && val !== corSistema) {
                    conflitoCor = true;
                    corSel = $(this).val();
                    return false;
                }
            }
        });

        if (conflitoCor) {
            if (!confirm(`ATENÇÃO!\nO interno usa ${corSistema.toUpperCase()}.\nVocê selecionou ${corSel.toUpperCase()}.\nDeseja prosseguir?`)) return;
        }

        // Verificar se é edição ou novo
        const isEdicao = $('#hiddenIdRecebimento').val() !== '';

        if (!isEdicao) {
            // NOVO RECEBIMENTO - Verificar se já recebeu no período
            try {
                const fd = new FormData();
                fd.append('acao', 'get_ultimo_recebimento');
                fd.append('ipen', $('#hiddenIpen').val());
                fd.append('periodo_id', '<?= $periodoAtivo['id'] ?? 0 ?>');

                const res = await fetch('paginas/internos_recebimento_roupas.php', {
                    method: 'POST',
                    body: fd
                });
                const json = await res.json();

                if (json.status === 'success' && json.existe) {
                    // Já recebeu - Mostrar SweetAlert com tabela
                    const ultimo = json.dados;
                    const itens = json.itens;

                    // Construir tabela HTML
                    let tabelaHtml = '<table class="table table-sm table-bordered mb-0">';
                    tabelaHtml += '<thead><tr><th>Item</th><th>Qtd</th><th>Detalhes</th></tr></thead>';
                    tabelaHtml += '<tbody>';
                    itens.forEach(item => {
                        tabelaHtml += `<tr><td>${item.item}</td><td>${item.quantidade}</td><td>${item.detalhes || '-'}</td></tr>`;
                    });
                    tabelaHtml += '</tbody></table>';

                    const dataUltimo = new Date(ultimo.data_recebimento).toLocaleString('pt-BR');
                    const nomeInterno = ultimo.nome_social ? `${ultimo.nome_social} (${ultimo.nome_interno})` : ultimo.nome_interno;

                    const result = await Swal.fire({
                        title: '<i class="fas fa-exclamation-triangle text-warning"></i> Interno já recebeu neste período',
                        html: `
                            <div class="text-left">
                                <p><strong>Interno:</strong> ${nomeInterno} (IPEN: ${ultimo.id_interno})</p>
                                <p><strong>Local:</strong> ${ultimo.galeria}${ultimo.bloco}-${ultimo.res}</p>
                                <p><strong>Último recebimento:</strong> ${dataUltimo}</p>
                                <p><strong>Entregue por:</strong> ${ultimo.entregue_por_tipo} - ${ultimo.entregue_por_nome}</p>
                                <hr>
                                <p class="mb-2"><strong>Itens recebidos:</strong></p>
                                ${tabelaHtml}
                                <hr>
                                <p class="text-warning"><small><i class="fas fa-info-circle"></i> Este novo recebimento será marcado como <strong>EXTRA</strong> (fora da regra padrão).</small></p>
                            </div>
                        `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-check"></i> Sim, prosseguir',
                        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        width: '600px'
                    });

                    if (!result.isConfirmed) {
                        return; // Usuário cancelou
                    }
                }
            } catch (err) {
                console.error('Erro ao verificar último recebimento:', err);
                // Continua mesmo se houver erro na verificação
            }
        }

        // Prosseguir com o salvamento
        try {
            const res = await fetch('paginas/internos_recebimento_roupas.php', {
                method: 'POST',
                body: new FormData(this)
            });
            const json = await res.json();
            if (json.status === 'success') {
                Swal.fire({
                    title: 'Sucesso!',
                    text: json.msg,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });

                // Pergunta recibo apenas se for NOVO (não edição)
                if (!$('#hiddenIdRecebimento').val()) {
                    const resultRecibo = await Swal.fire({
                        title: 'Imprimir Recibo?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: '<i class="fas fa-print"></i> Sim',
                        cancelButtonText: '<i class="fas fa-times"></i> Não',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d'
                    });

                    if (resultRecibo.isConfirmed) {
                        window.open('paginas/imprimir_recibo_visitante.php?ids=' + json.id_novo, '_blank');
                    }
                }

                window.fecharNovoRecebimento();
                window.safeReloadWithFilters();
            } else {
                Swal.fire('Erro', json.msg, 'error');
            }
        } catch (err) {
            Swal.fire('Erro', 'Erro na comunicação com o servidor', 'error');
        }
    });

    window.salvarConfig = async function(e) {
        e.preventDefault();
        try {
            const res = await fetch('paginas/internos_recebimento_roupas.php', {
                method: 'POST',
                body: new FormData(e.target)
            });
            const json = await res.json();
            alert(json.msg);
            if (json.status === 'success') window.safeReloadWithFilters();
        } catch (err) {
            alert('Erro: ' + err);
        }
    }

    window.imprimirSelecionados = async function(tipo) {
        let ids = [],
            jaEntregues = [];
        $('.chk-print:checked').each(function() {
            ids.push($(this).val());
            if ($(this).data('entregue') == 1) jaEntregues.push($(this).data('data-entregue'));
        });

        if (!ids.length) return alert('Selecione registros.');
        if (tipo === 'recibo') return window.open('paginas/imprimir_recibo_visitante.php?ids=' + ids.join(','), '_blank');

        let acaoBanco = true;
        if (jaEntregues.length > 0) {
            if (!confirm(`Alguns itens JÁ FORAM ENTREGUES.\nDeseja reimprimir sem alterar a data?`)) return;
            acaoBanco = false;
        } else {
            if (!confirm('Gerar termo e marcar como ENTREGUE?')) return;
        }

        if (acaoBanco) {
            const fd = new FormData();
            fd.append('acao', 'marcar_entregue');
            fd.append('ids_recebimento', ids.join(','));
            const res = await fetch('paginas/internos_recebimento_roupas.php', {
                method: 'POST',
                body: fd
            });
            const json = await res.json();
            if (json.status !== 'success') return alert(json.msg);
        }
        window.open('paginas/imprimir_termo_rouparia.php?ids=' + ids.join(','), '_blank');
        if (acaoBanco) window.safeReloadWithFilters();
    }

    window.excluirRecebimento = async function(id) {
        if (!confirm('Excluir este registro permanentemente?')) return;
        const fd = new FormData();
        fd.append('acao', 'excluir');
        fd.append('id', id);
        const res = await fetch('paginas/internos_recebimento_roupas.php', {
            method: 'POST',
            body: fd
        });
        const json = await res.json();
        alert(json.msg);
        if (json.status === 'success') window.safeReloadWithFilters();
    }
</script>
