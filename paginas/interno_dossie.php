<?php
require_once __DIR__ . '/conf/db.php';
require_once __DIR__ . '/includes/header.php';

// Verificar se foi informado o IPEN
if (empty($_GET['ipen'])) {
    echo "<div class='alert alert-danger'>IPEN não informado.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$ipen = (int)$_GET['ipen'];

// Buscar dados do interno
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $stmt = $pdo->prepare("SELECT * FROM internos WHERE ipen = ?");
    $stmt->execute([$ipen]);
    $interno = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$interno) {
        echo "<div class='alert alert-danger'>Interno não encontrado.</div>";
        require_once __DIR__ . '/includes/footer.php';
        exit;
    }

} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erro: " . $e->getMessage() . "</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Buscar histórico de doações (como doador e como receptor)
try {
    $sql = "
        SELECT
            h.*,
            d.tipo_receptor,
            d.id_doador as doacao_id_doador,
            d.id_receptor as doacao_id_receptor,
            COALESCE(i_doador.nome, 'N/A') as nome_doador,
            COALESCE(i_receptor.nome, 'N/A') as nome_receptor,
            e.tipo_item,
            e.marca_modelo,
            CASE
                WHEN h.id_doacao IS NOT NULL THEN 'Doação'
                ELSE 'Outro'
            END as tipo_historico
        FROM internos_doacao_eletronicos_historico h
        LEFT JOIN internos_doacao_eletronicos d ON h.id_doacao = d.id
        LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
        LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
        LEFT JOIN internos_eletronicos e ON h.id_item = e.id
        WHERE (d.id_doador = ? OR d.id_receptor = ?)
        ORDER BY h.data_hora DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$ipen, $ipen]);
    $historico_doacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $historico_doacoes = [];
}

?>

<!-- Content Header -->
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dossiê do Interno</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="internos.php">Internos</a></li>
                    <li class="breadcrumb-item active">Dossiê</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Dados do Interno -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user mr-2"></i>
                    Dados Pessoais
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>IPEN:</strong><br>
                        <span class="badge badge-secondary"><?= $interno['ipen'] ?></span>
                    </div>
                    <div class="col-md-3">
                        <strong>Nome:</strong><br>
                        <?= $interno['nome_social'] ?: $interno['nome'] ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Galeria:</strong><br>
                        <?= $interno['galeria'] ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Bloco:</strong><br>
                        <?= $interno['bloco'] ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Cela:</strong><br>
                        <?= $interno['res'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Histórico de Doações -->
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-hand-holding-heart mr-2"></i>
                    Histórico de Doações
                </h3>
                <span class="badge badge-pill badge-info">
                    <?= count($historico_doacoes) ?> registros
                </span>
            </div>
            <div class="card-body">
                <?php if (empty($historico_doacoes)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Este interno não possui histórico de doações.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Ação</th>
                                    <th>Item</th>
                                    <th>Detalhes</th>
                                    <th>Envolvido como</th>
                                    <th>Usuário</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico_doacoes as $item): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($item['data_hora'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= getTipoBadgeColor($item['tipo_historico']) ?>">
                                                <?= $item['tipo_historico'] ?>
                                            </span>
                                        </td>
                                        <td><?= formatarAcao($item['acao']) ?></td>
                                        <td>
                                            <?= $item['tipo_item'] ?>
                                            <?= $item['marca_modelo'] ? ' - ' . $item['marca_modelo'] : '' ?>
                                        </td>
                                        <td><?= $item['detalhes'] ?></td>
                                        <td>
                                            <?php if ($item['doacao_id_doador'] == $ipen): ?>
                                                <span class="badge badge-primary">Doador</span>
                                            <?php elseif ($item['doacao_id_receptor'] == $ipen): ?>
                                                <span class="badge badge-success">Receptor</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?= $item['usuario'] ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumo Estatístico -->
        <div class="row">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-hand-holding-heart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Itens Doados</span>
                        <span class="info-box-number"><?= contarAcoes($historico_doacoes, $ipen, 'doador') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-gift"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Itens Recebidos</span>
                        <span class="info-box-number"><?= contarAcoes($historico_doacoes, $ipen, 'receptor') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-exchange-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Transferências</span>
                        <span class="info-box-number"><?= contarAcoes($historico_doacoes, $ipen, 'transferencia') ?></span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-undo"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Devoluções</span>
                        <span class="info-box-number"><?= contarAcoes($historico_doacoes, $ipen, 'devolucao') ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php
// Funções auxiliares
function getTipoBadgeColor($tipo) {
    $cores = [
        'Doação' => 'info',
        'Outro' => 'secondary'
    ];
    return $cores[$tipo] ?? 'secondary';
}

function formatarAcao($acao) {
    $acoes = [
        'DOACAO_CRIADA' => 'Doação Criada',
        'DOACAO_APROVADA' => 'Doação Aprovada',
        'DOACAO_CANCELADA' => 'Doação Cancelada',
        'ITEM_DOADO' => 'Item Doado',
        'ITEM_TRANSFERIDO' => 'Item Transferido',
        'ITEM_DEVOLVIDO' => 'Item Devolvido',
        'TERMO_ASSINADO' => 'Termo Assinado'
    ];
    return $acoes[$acao] ?? $acao;
}

function contarAcoes($historico, $ipen, $tipo) {
    $count = 0;
    foreach ($historico as $item) {
        switch ($tipo) {
            case 'doador':
                if ($item['doacao_id_doador'] == $ipen && $item['acao'] === 'ITEM_DOADO') $count++;
                break;
            case 'receptor':
                if ($item['doacao_id_receptor'] == $ipen && $item['acao'] === 'ITEM_TRANSFERIDO') $count++;
                break;
            case 'transferencia':
                if ($item['acao'] === 'ITEM_TRANSFERIDO') $count++;
                break;
            case 'devolucao':
                if ($item['acao'] === 'ITEM_DEVOLVIDO') $count++;
                break;
        }
    }
    return $count;
}

require_once __DIR__ . '/includes/footer.php';
?>
