<?php
// paginas/internos_doacao_eletronicos.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro DB: " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) session_start();
$nome_user = $_SESSION['user_nome'] ?? 'Usuario Sistema';
$setor_user = $_SESSION['user_setor'] ?? '';
$usuario_logado = $nome_user . ($setor_user ? " (" . mb_strtoupper($setor_user, 'UTF-8') . ")" : "");

// Verificar se usuário tem acesso (admin ou censura)
// Tempariamente comentado para testes - REATIVAR EM PRODUÇÃO!
// $eh_admin = (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true);
// $eh_censura = (isset($_SESSION['perm_censura']) && $_SESSION['perm_censura'] > 0);
// if (!$eh_admin && !$eh_censura) {
//     die("Acesso negado. Apenas equipe da Censura tem permissão.");
// }

// Permitir acesso apenas para visualização do usuário Rouparia

// --- AJAX ---
if (isset($_REQUEST['acao'])) {
    require_once __DIR__ . '/../includes/internos_doacao_eletronicos_logica.php';
    exit;
}

// --- DADOS PARA DASHBOARD ---
$stats = $pdo->query("
    SELECT
        COUNT(*) as total_doacoes,
        COUNT(CASE WHEN tipo_receptor = 'CELA' THEN 1 END) as doacoes_cela,
        COUNT(CASE WHEN tipo_receptor = 'INTERNO' THEN 1 END) as doacoes_interno,
        COALESCE(SUM((SELECT COUNT(*) FROM internos_doacao_eletronicos_itens WHERE id_doacao = d.id)), 0) as total_itens_doados
    FROM internos_doacao_eletronicos d
    WHERE DATE(data_doacao) = CURDATE()
")->fetch(PDO::FETCH_ASSOC);

// Filtros para relatórios
$where = [];
$params = [];

if (!empty($_GET['ipen'])) {
    $where[] = "(d.id_doador = ? OR d.id_receptor = ?)";
    $params[] = $_GET['ipen'];
    $params[] = $_GET['ipen'];
}

if (!empty($_GET['nome'])) {
    $where[] = "(i_doador.nome LIKE ? OR i_doador.nome_social LIKE ? OR i_receptor.nome LIKE ? OR i_receptor.nome_social LIKE ?)";
    $p = "%{$_GET['nome']}%";
    array_push($params, $p, $p, $p, $p);
}

if (!empty($_GET['galeria'])) {
    $where[] = "(d.galeria_receptor = ? OR i_doador.galeria = ? OR i_receptor.galeria = ?)";
    $params[] = $_GET['galeria'];
    $params[] = $_GET['galeria'];
    $params[] = $_GET['galeria'];
}

if (!empty($_GET['bloco'])) {
    $where[] = "(d.bloco_receptor = ? OR i_doador.bloco = ? OR i_receptor.bloco = ?)";
    $params[] = $_GET['bloco'];
    $params[] = $_GET['bloco'];
    $params[] = $_GET['bloco'];
}

if (!empty($_GET['cela'])) {
    $where[] = "(d.cela_receptor = ? OR i_doador.res = ? OR i_receptor.res = ?)";
    $params[] = $_GET['cela'];
    $params[] = $_GET['cela'];
    $params[] = $_GET['cela'];
}

if (!empty($_GET['tipo_receptor'])) {
    $where[] = "d.tipo_receptor = ?";
    $params[] = $_GET['tipo_receptor'];
}

if (!empty($_GET['data_inicio'])) {
    $where[] = "DATE(d.data_doacao) >= ?";
    $params[] = $_GET['data_inicio'];
}

if (!empty($_GET['data_fim'])) {
    $where[] = "DATE(d.data_doacao) <= ?";
    $params[] = $_GET['data_fim'];
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$sqlRelatorio = "
    SELECT
        d.*,
        i_doador.nome as nome_doador,
        i_doador.nome_social as nome_social_doador,
        i_doador.galeria as galeria_doador,
        i_doador.bloco as bloco_doador,
        i_doador.res as cela_doador,
        i_receptor.nome as nome_receptor,
        i_receptor.nome_social as nome_social_receptor,
        i_receptor.galeria as galeria_receptor_interno,
        i_receptor.bloco as bloco_receptor_interno,
        i_receptor.res as cela_receptor_interno,
        COUNT(di.id) as qtd_itens,
        GROUP_CONCAT(DISTINCT di.tipo_item SEPARATOR ', ') as tipos_itens
    FROM internos_doacao_eletronicos d
    LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
    LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
    LEFT JOIN internos_doacao_eletronicos_itens di ON d.id = di.id_doacao
    $whereSql
    GROUP BY d.id
    ORDER BY d.data_doacao DESC
    LIMIT 100
";

$stmtRelatorio = $pdo->prepare($sqlRelatorio);
$stmtRelatorio->execute($params);
$doacoes = $stmtRelatorio->fetchAll(PDO::FETCH_ASSOC);

// Helpers
$galerias = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE status='A' ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos = $pdo->query("SELECT DISTINCT bloco FROM internos WHERE status='A' ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);

?>
<script src="https://code.jquery.com"></script>
<script>
    window.pageTitle = 'Doação de Eletrônicos';
    window.currentPage = 'internos_doacao_eletronicos.php';
</script>

<style>
    .card-doacao {
        cursor: pointer;
        transition: transform 0.2s;
    }

    .card-doacao:hover {
        transform: translateY(-2px);
    }

    .offcanvas-doacao {
        position: fixed;
        top: 0;
        right: 0;
        width: 800px;
        height: 100%;
        background: #fff;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: 0.3s;
        z-index: 1060;
    }

    .offcanvas-header-doacao {
        background: #28a745;
        color: white;
        padding: 15px;
    }

    .offcanvas-body-doacao {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }

    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 30px;
    }

    .step {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .step-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
    }

    .step.active .step-circle {
        background: #007bff;
        color: white;
    }

    .step.completed .step-circle {
        background: #28a745;
        color: white;
    }

    .step-line {
        position: absolute;
        top: 20px;
        left: 50%;
        width: calc(100% - 40px);
        height: 2px;
        background: #e9ecef;
        z-index: -1;
    }

    .step.completed+.step .step-line {
        background: #28a745;
    }

    .item-card {
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .item-card:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .item-card.selected {
        border-color: #28a745;
        background-color: #d4edda;
    }

    .doacao-warning {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 5px;
        padding: 15px;
        margin: 15px 0;
    }

    .doacao-warning .alert-icon {
        color: #856404;
        font-size: 24px;
        margin-right: 10px;
    }
</style>

<section class="content pt-3">
    <div class="container-fluid">

        <!-- CABEÇALHO -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card card-outline card-success">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-hand-holding-heart"></i> Sistema de Doação de Eletrônicos</h3>
                        <div class="card-tools">
                            <button class="btn btn-success btn-sm" onclick="abrirOffcanvasDoacao()">
                                <i class="fas fa-plus"></i> Nova Doação
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">Sistema para registrar doações de equipamentos eletrônicos entre internos ou para celas da unidade prisional.</p>
                        <div class="mt-3">
                            <div class="btn-group" role="group">
                                <a href="/censura/doacao/termo-censura/" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-file-alt"></i> Termo de Entrega - Censura
                                </a>
                                <a href="/censura/doacao/termo-cela/" target="_blank" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-file-contract"></i> Termo de Doação para Cela
                                </a>
                                <a href="/censura/doacao/termo-interno/" target="_blank" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-hand-holding-heart"></i> Termo de Doação Interno
                                </a>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Termos abrem em nova janela para impressão
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARDS DE ESTATÍSTICAS HOJE -->
        <div class="row mb-3">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $stats['total_doacoes'] ?></h3>
                        <p>Doações Hoje</p>
                    </div>
                    <div class="icon"><i class="fas fa-hand-holding-heart"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $stats['doacoes_cela'] ?></h3>
                        <p>Para Celas</p>
                    </div>
                    <div class="icon"><i class="fas fa-home"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $stats['doacoes_interno'] ?></h3>
                        <p>Para Internos</p>
                    </div>
                    <div class="icon"><i class="fas fa-user"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= $stats['total_itens_doados'] ?></h3>
                        <p>Itens Doados Hoje</p>
                    </div>
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
        </div>

        <!-- FILTROS PARA RELATÓRIOS -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-header">
                <h5 class="card-title"><i class="fas fa-filter"></i> Relatórios de Doações</h5>
            </div>
            <div class="card-body">
                <form class="form-inline justify-content-start" method="GET">
                    <input type="text" class="form-control form-control-sm mr-2 mb-2" name="ipen" placeholder="IPEN" value="<?= $_GET['ipen'] ?? '' ?>" size="8">
                    <input type="text" class="form-control form-control-sm mr-2 mb-2" name="nome" placeholder="Nome/Nome Social" value="<?= $_GET['nome'] ?? '' ?>" size="15">
                    <select class="form-control form-control-sm mr-2 mb-2" name="galeria">
                        <option value="">Galeria</option>
                        <?php foreach ($galerias as $g) echo "<option value='$g' " . (($_GET['galeria'] ?? '') == $g ? 'selected' : '') . ">$g</option>"; ?>
                    </select>
                    <select class="form-control form-control-sm mr-2 mb-2" name="bloco">
                        <option value="">Bloco</option>
                        <?php foreach ($blocos as $b) echo "<option value='$b' " . (($_GET['bloco'] ?? '') == $b ? 'selected' : '') . ">$b</option>"; ?>
                    </select>
                    <input type="text" class="form-control form-control-sm mr-2 mb-2" name="cela" placeholder="Cela" value="<?= $_GET['cela'] ?? '' ?>" size="5">
                    <select class="form-control form-control-sm mr-2 mb-2" name="tipo_receptor">
                        <option value="">Tipo</option>
                        <option value="CELA" <?= (($_GET['tipo_receptor'] ?? '') == 'CELA' ? 'selected' : '') ?>>Para Cela</option>
                        <option value="INTERNO" <?= (($_GET['tipo_receptor'] ?? '') == 'INTERNO' ? 'selected' : '') ?>>Para Interno</option>
                    </select>
                    <input type="date" class="form-control form-control-sm mr-2 mb-2" name="data_inicio" value="<?= $_GET['data_inicio'] ?? '' ?>">
                    <input type="date" class="form-control form-control-sm mr-2 mb-2" name="data_fim" value="<?= $_GET['data_fim'] ?? '' ?>">
                    <button type="submit" class="btn btn-sm btn-primary mr-2 mb-2"><i class="fas fa-search"></i> Filtrar</button>
                    <button type="button" class="btn btn-sm btn-info mr-2 mb-2" onclick="abrirModalHistorico()">
                        <i class="fas fa-history"></i> Histórico Completo
                    </button>
                    <a href="internos_doacao_eletronicos.php" class="btn btn-sm btn-secondary mb-2"><i class="fas fa-times"></i> Limpar</a>
                </form>
            </div>
        </div>

        <!-- TABELA DE DOAÇÕES -->
        <div class="card shadow">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-sm text-center align-middle">
                    <thead class="thead-dark">
                        <tr>
                            <th>Data</th>
                            <th>Doador</th>
                            <th>Receptor</th>
                            <th>Itens</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($doacoes)): ?>
                            <tr>
                                <td colspan="7" class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                                    Nenhuma doação encontrada com os filtros aplicados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($doacoes as $doacao): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($doacao['data_doacao'])) ?></td>
                                    <td class="text-left">
                                        <strong><?= $doacao['id_doador'] ?></strong><br>
                                        <small><?= $doacao['nome_social_doador'] ?: $doacao['nome_doador'] ?></small><br>
                                        <small class="text-muted"><?= $doacao['galeria_doador'] ?>-<?= $doacao['bloco_doador'] ?>-<?= $doacao['cela_doador'] ?></small>
                                    </td>
                                    <td class="text-left">
                                        <?php if ($doacao['tipo_receptor'] === 'CELA'): ?>
                                            <strong>CELA</strong><br>
                                            <small class="text-muted">Galeria <?= $doacao['galeria_receptor'] ?> - Bloco <?= $doacao['bloco_receptor'] ?> - Cela <?= $doacao['cela_receptor'] ?></small>
                                        <?php else: ?>
                                            <strong><?= $doacao['id_receptor'] ?></strong><br>
                                            <small><?= $doacao['nome_social_receptor'] ?: $doacao['nome_receptor'] ?></small><br>
                                            <small class="text-muted"><?= $doacao['galeria_receptor_interno'] ?>-<?= $doacao['bloco_receptor_interno'] ?>-<?= $doacao['cela_receptor_interno'] ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?= $doacao['qtd_itens'] ?> item(s)</span><br>
                                        <small class="text-muted"><?= $doacao['tipos_itens'] ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $doacao['tipo_receptor'] == 'CELA' ? 'success' : 'warning' ?>">
                                            <?= $doacao['tipo_receptor'] == 'CELA' ? 'Para Cela' : 'Para Interno' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $doacao['status'] ?? 'Pendente';
                                        $statusClass = [
                                            'Pendente' => 'warning',
                                            'Aprovado' => 'success',
                                            'Cancelado' => 'danger'
                                        ][$status] ?? 'secondary';
                                        ?>
                                        <span class="badge badge-<?= $statusClass ?>">
                                            <?= $status ?>
                                        </span>
                                        <?php if ($doacao['termo_assinado']): ?>
                                            <br><small class="text-success">Termo Assinado</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="verDetalhesDoacao(<?= $doacao['id'] ?>)">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>

                                        <?php if (($doacao['status'] ?? 'Pendente') === 'Pendente'): ?>
                                            <button class="btn btn-sm btn-success" onclick="aprovarDoacao(<?= $doacao['id'] ?>)" title="Aprovar doação" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                                <i class="fas fa-check"></i> Aprovar
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="cancelarDoacao(<?= $doacao['id'] ?>)" title="Cancelar doação" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                                <i class="fas fa-times"></i> Cancelar
                                            </button>
                                        <?php endif; ?>

                                        <?php if (($doacao['status'] ?? 'Pendente') === 'Aprovado'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="imprimirTermo(<?= $doacao['id'] ?>)" title="Imprimir Termo" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                                <i class="fas fa-print"></i> Termo
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- OFFCANVAS DE NOVA DOAÇÃO -->
<div id="offcanvasDoacao" class="offcanvas-doacao">
    <div class="offcanvas-header-doacao">
        <h5><i class="fas fa-hand-holding-heart"></i> Nova Doação</h5>
        <button type="button" class="close text-white" onclick="fecharOffcanvasDoacao()">&times;</button>
    </div>
    <div class="offcanvas-body-doacao">
        <div class="step-indicator">
            <div class="step active" id="step1">
                <div class="step-circle">1</div>
                <div>Doador</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step2">
                <div class="step-circle">2</div>
                <div>Receptor</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step3">
                <div class="step-circle">3</div>
                <div>Itens</div>
            </div>
            <div class="step-line"></div>
            <div class="step" id="step4">
                <div class="step-circle">4</div>
                <div>Confirmação</div>
            </div>
        </div>

        <div id="stepContent">
            <!-- PASSO 1: BUSCA DO DOADOR -->
            <div id="passo1" class="passo-content">
                <h5>Buscar Doador</h5>
                <div class="form-group">
                    <label>Buscar Interno:</label>
                    <input type="text" class="form-control" id="buscaDoador" placeholder="Digite IPEN, nome ou nome social..." onkeyup="buscarInternoDoador()">
                    <small class="form-text text-muted">Digite pelo menos 3 caracteres</small>
                </div>
                <div id="sugestoesDoador" class="mt-2" style="max-height: 200px; overflow-y: auto;"></div>
                <div id="doadorSelecionado" class="mt-3" style="display: none;">
                    <div class="alert alert-success">
                        <h6>Doador Selecionado:</h6>
                        <div id="infoDoador"></div>
                    </div>
                    <button class="btn btn-primary" onclick="proximoPasso(2)">Próximo <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>

            <!-- PASSO 2: TIPO DE RECEPTOR -->
            <div id="passo2" class="passo-content" style="display: none;">
                <h5>Tipo de Receptor</h5>
                <div class="row">
                    <div class="col-6">
                        <div class="card card-doacao" onclick="selecionarTipoReceptor('CELA')">
                            <div class="card-body text-center">
                                <i class="fas fa-home fa-3x text-success mb-3"></i>
                                <h6>Doar para Cela</h6>
                                <small class="text-muted">Doação coletiva para cela específica</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-doacao" onclick="selecionarTipoReceptor('INTERNO')">
                            <div class="card-body text-center">
                                <i class="fas fa-user fa-3x text-warning mb-3"></i>
                                <h6>Doar para Interno</h6>
                                <small class="text-muted">Doação individual para outro interno</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="formReceptorCela" class="mt-3" style="display: none;">
                    <h6>Informações da Cela:</h6>
                    <div class="form-row">
                        <div class="col-4">
                            <select class="form-control" id="galeriaReceptor" onchange="carregarBlocosReceptor()">
                                <option value="">Galeria</option>
                                <?php foreach ($galerias as $g) echo "<option value='$g'>$g</option>"; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <select class="form-control" id="blocoReceptor" onchange="carregarCelasReceptor()">
                                <option value="">Bloco</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <select class="form-control" id="celaReceptor">
                                <option value="">Cela</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" onclick="validarReceptorCela()">Confirmar <i class="fas fa-check"></i></button>
                </div>
                <div id="formReceptorInterno" class="mt-3" style="display: none;">
                    <h6>Buscar Interno Receptor:</h6>
                    <input type="text" class="form-control" id="buscaReceptorInterno" placeholder="Digite IPEN, nome ou nome social..." onkeyup="buscarInternoReceptor()">
                    <div id="sugestoesReceptor" class="mt-2" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
                <button class="btn btn-secondary mt-3" onclick="voltarPasso(1)"><i class="fas fa-arrow-left"></i> Voltar</button>
            </div>

            <!-- PASSO 3: SELEÇÃO DE ITENS -->
            <div id="passo3" class="passo-content" style="display: none;">
                <h5>Selecionar Itens para Doação</h5>
                <div id="listaItensDoador">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Carregando itens do doador...</p>
                    </div>
                </div>
                <div id="itensSelecionados" class="mt-3" style="display: none;">
                    <div class="alert alert-info">
                        <h6>Itens Selecionados:</h6>
                        <div id="resumoItensSelecionados"></div>
                    </div>
                    <button class="btn btn-primary" onclick="proximoPasso(4)">Confirmar Itens <i class="fas fa-arrow-right"></i></button>
                </div>
                <button class="btn btn-secondary mt-3" onclick="voltarPasso(2)"><i class="fas fa-arrow-left"></i> Voltar</button>
            </div>

            <!-- PASSO 4: CONFIRMAÇÃO E TERMO -->
            <div id="passo4" class="passo-content" style="display: none;">
                <h5>Confirmação da Doação</h5>
                <div id="resumoDoacao">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p>Preparando termo...</p>
                    </div>
                </div>
                <div id="termoContainer" style="display: none;">
                    <div class="doacao-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle alert-icon"></i>
                            <div>
                                <strong>IMPORTANTE:</strong> Esta doação é <strong>IRREVOGÁVEL E INTRANSFERÍVEL</strong>.
                                Uma vez confirmada, os itens não poderão mais ser requeridos pelo doador.
                            </div>
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input type="checkbox" class="form-check-input" id="confirmacaoIrrevogavel">
                        <label class="form-check-label" for="confirmacaoIrrevogavel">
                            <strong>Confirmo que entendo que esta doação é irrevogável e intransferível.</strong>
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="confirmacaoMonitor">
                        <label class="form-check-label" for="confirmacaoMonitor">
                            <strong>Sou funcionário da Censura e autorizo esta doação.</strong>
                        </label>
                    </div>
                    <div class="mt-4">
                        <button class="btn btn-success btn-lg btn-block" id="btnFinalizarDoacao" disabled onclick="finalizarDoacao()">
                            <i class="fas fa-hand-holding-heart"></i> Finalizar Doação
                        </button>
                    </div>
                </div>
                <button class="btn btn-secondary mt-3" onclick="voltarPasso(3)"><i class="fas fa-arrow-left"></i> Voltar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE HISTÓRICO COMPLETO -->
<div class="modal fade" id="modalHistorico" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-history mr-2"></i>
                    Histórico Completo de Doações - Rastreabilidade Total
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- FILTROS AVANÇADOS -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6><i class="fas fa-filter mr-2"></i>Filtros Avançados</h6>
                        <form id="formFiltroHistorico" class="row g-2">
                            <div class="col-md-3">
                                <label>Período:</label>
                                <div class="input-group input-group-sm">
                                    <input type="date" id="hist_data_inicio" class="form-control">
                                    <div class="input-group-prepend input-group-append">
                                        <span class="input-group-text">até</span>
                                    </div>
                                    <input type="date" id="hist_data_fim" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label>Ação:</label>
                                <select id="hist_acao" class="form-control form-control-sm">
                                    <option value="">Todas</option>
                                    <option value="DOACAO_CRIADA">Doação Criada</option>
                                    <option value="DOACAO_APROVADA">Doação Aprovada</option>
                                    <option value="DOACAO_CANCELADA">Doação Cancelada</option>
                                    <option value="ITEM_DOADO">Item Doado</option>
                                    <option value="ITEM_TRANSFERIDO">Item Transferido</option>
                                    <option value="ITEM_DEVOLVIDO">Item Devolvido</option>
                                    <option value="TERMO_ASSINADO">Termo Assinado</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>ID Doação:</label>
                                <input type="number" id="hist_id_doacao" class="form-control form-control-sm" placeholder="ID">
                            </div>
                            <div class="col-md-2">
                                <label>ID Item:</label>
                                <input type="number" id="hist_id_item" class="form-control form-control-sm" placeholder="ID">
                            </div>
                            <div class="col-md-2">
                                <label>Usuário:</label>
                                <input type="text" id="hist_usuario" class="form-control form-control-sm" placeholder="Usuário">
                            </div>
                            <div class="col-md-1">
                                <label>&nbsp;</label><br>
                                <button type="button" onclick="filtrarHistorico()" class="btn btn-sm btn-primary btn-block">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABELA DE HISTÓRICO -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabelaHistorico" class="table table-sm table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>ID Doação</th>
                                        <th>ID Item</th>
                                        <th>Ação</th>
                                        <th>Detalhes</th>
                                        <th>Usuário</th>
                                        <th>Tipo Item</th>
                                        <th>De</th>
                                        <th>Para</th>
                                    </tr>
                                </thead>
                                <tbody id="corpoTabelaHistorico">
                                    <tr>
                                        <td colspan="9" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Carregando...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Mostrando a rastreabilidade completa de todos os itens eletrônicos do sistema.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" onclick="exportarHistorico()" class="btn btn-success">
                    <i class="fas fa-download"></i> Exportar CSV
                </button>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="assets/css/internos_doacao_eletronicos.css?v=<?= date('YmdHis') . '_' . rand(1000, 9999) ?>">

<script src="assets/js/internos_doacao_eletronicos.js?v=<?= date('YmdHis') . '_' . rand(1000, 9999) ?>"></script>
