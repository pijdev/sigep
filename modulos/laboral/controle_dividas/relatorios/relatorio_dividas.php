<?php
// Relatório de Dívidas - Filtro Customizado
session_start();
require_once __DIR__ . '/../../../../conf/db.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    die('Acesso negado. Sessão expirada.');
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com banco
try {
    $config = require __DIR__ . '/../../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Falha na conexão com o Banco de Dados.");
}

// Obter parâmetros de filtro
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'status_detalhado' => $_GET['status_detalhado'] ?? '',
    'status' => $_GET['status'] ?? '',
    'tipo' => $_GET['tipo'] ?? '',
    'mostrar_inativos' => isset($_GET['mostrar_inativos']) ? true : false,
    'sort_by' => $_GET['sort_by'] ?? 'data_cadastro',
    'sort_order' => $_GET['sort_order'] ?? 'DESC'
];

// Query base
$sql_base = "
    FROM laboral_controle_dividas lm
    LEFT JOIN (
        SELECT
            ipen,
            nome,
            nome_social,
            status as interno_status
        FROM internos
    ) i ON lm.ipen = i.ipen
    WHERE 1=1
";

$params = [];

// Aplicar filtros
if (!empty($filtros['busca'])) {
    $sql_base .= " AND (i.nome LIKE :busca OR i.nome_social LIKE :busca OR lm.ipen LIKE :busca)";
    $params[':busca'] = '%' . $filtros['busca'] . '%';
}

if (!empty($filtros['status_detalhado'])) {
    $sql_base .= " AND lm.status_detalhado = :status_detalhado";
    $params[':status_detalhado'] = $filtros['status_detalhado'];
}

if (!empty($filtros['status'])) {
    $sql_base .= " AND lm.status = :status";
    $params[':status'] = $filtros['status'];
}

if (!empty($filtros['tipo'])) {
    $sql_base .= " AND lm.tipo_divida = :tipo";
    $params[':tipo'] = $filtros['tipo'];
}

if (!$filtros['mostrar_inativos']) {
    $sql_base .= " AND (i.interno_status IS NULL OR i.interno_status != 'I')";
}

// Ordenação válida
$sort_by = $filtros['sort_by'];
$sort_order = strtoupper($filtros['sort_order']) === 'ASC' ? 'ASC' : 'DESC';

$order_columns = [
    'data_cadastro' => 'lm.data_cadastro',
    'ipen' => 'lm.ipen',
    'nome' => 'i.nome',
    'tipo_divida' => 'lm.tipo_divida',
    'valor_atual' => 'lm.valor_atual',
    'status_detalhado' => 'lm.status_detalhado'
];

$order_sql = isset($order_columns[$sort_by]) ? $order_columns[$sort_by] : 'lm.data_cadastro';
$order_sql .= " $sort_order";

// Consulta principal
$sql = "
    SELECT
        lm.id,
        lm.ipen,
        lm.cpf,
        lm.autos,
        lm.tipo_divida,
        lm.valor_divida,
        lm.valor_atual,
        lm.percentual_desconto,
        lm.status,
        lm.status_detalhado,
        lm.data_cadastro,
        i.nome as interno_nome,
        i.nome_social as interno_nome_social
    $sql_base
    ORDER BY $order_sql
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dividas = $stmt->fetchAll();

// Calcular totais
$total_dividas = count($dividas);
$total_valor_original = array_sum(array_column($dividas, 'valor_divida'));
$total_valor_atual = array_sum(array_column($dividas, 'valor_atual'));
$total_descontado = $total_valor_original - $total_valor_atual;

// Título do relatório
$txt_filtro = "TODAS AS DÍVIDAS";
if (!empty($filtros['status_detalhado'])) {
    $txt_filtro = "DÍVIDAS " . strtoupper($filtros['status_detalhado']);
}
if (!empty($filtros['tipo'])) {
    $txt_filtro .= " - TIPO: " . strtoupper($filtros['tipo']);
}
if (!empty($filtros['busca'])) {
    $txt_filtro .= " - BUSCA: " . htmlspecialchars($filtros['busca']);
}

$titulo_relatorio = "RELATÓRIO DE DÍVIDAS - $txt_filtro - " . date("d/m/Y H:i");

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_relatorio ?></title>
    <link rel="icon" type="image/svg+xml" href="../../../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5cm;
        }

        body {
            font-size: 9pt;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            padding: 15px;
            color: black;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid black !important;
            padding: 4px 6px !important;
        }

        th {
            background: #e9ecef !important;
            font-weight: bold;
            text-align: center;
        }

        .header-img {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .font-bold {
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-warning {
            background: #ffc107;
            color: black;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .badge-secondary {
            background: #6c757d;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-dark {
            background: #343a40;
            color: white;
        }

        .valor-destaque {
            font-weight: bold;
            color: #000;
        }

        .valor-original {
            text-decoration: line-through;
            color: #6c757d;
            font-size: 8pt;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
                font-size: 8pt;
            }

            th,
            td {
                padding: 3px 4px !important;
            }
        }
    </style>
</head>

<body>
    <!-- Cabeçalho -->
    <div class="text-center mb-3">
        <h4 class="font-bold mb-1"><?= $titulo_relatorio ?></h4>
        <small class="text-muted">Sistema SIGEP - Módulo Laboral</small>
    </div>

    <!-- Resumo -->
    <div style="margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #007bff;">
        <strong>Total de Dívidas:</strong> <?= $total_dividas ?> |
        <strong>Valor Original Total:</strong> R$ <?= number_format($total_valor_original ?? 0, 2, ',', '.') ?> |
        <strong>Valor Atual Total:</strong> R$ <?= number_format($total_valor_atual ?? 0, 2, ',', '.') ?> |
        <strong>Total Descontado:</strong> R$ <?= number_format($total_descontado ?? 0, 2, ',', '.') ?>
    </div>

    <!-- Botão Imprimir -->
    <div class="no-print mb-3 text-center">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir / Salvar PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Fechar
        </button>
    </div>

    <!-- Tabela -->
    <table>
        <thead>
            <tr>
                <th style="width: 60px;">ID</th>
                <th style="width: 80px;">IPEN</th>
                <th>Nome do Interno</th>
                <th style="width: 100px;">CPF</th>
                <th style="width: 120px;">Autos</th>
                <th style="width: 90px;">Tipo</th>
                <th style="width: 100px;">Valor Original</th>
                <th style="width: 100px;">Valor Atual</th>
                <th style="width: 60px;">% Desc.</th>
                <th style="width: 90px;">Status</th>
                <th style="width: 100px;">Cadastro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dividas as $d):
                $nome = $d['interno_nome_social'] ?: $d['interno_nome'];
                $badge_class = match ($d['status_detalhado']) {
                    'Pendente' => 'badge-warning',
                    'Ativa' => 'badge-info',
                    'Quitada' => 'badge-success',
                    'Suspensa' => 'badge-secondary',
                    default => 'badge-secondary'
                };
            ?>
                <tr>
                    <td class="text-center"><?= $d['id'] ?></td>
                    <td class="text-center font-bold"><?= $d['ipen'] ?></td>
                    <td><?= htmlspecialchars($nome ?: 'N/A') ?></td>
                    <td class="text-center"><?= $d['cpf'] ?></td>
                    <td class="text-center"><?= htmlspecialchars($d['autos']) ?></td>
                    <td class="text-center"><?= $d['tipo_divida'] ?></td>
                    <td class="text-right">R$ <?= number_format($d['valor_divida'] ?? 0, 2, ',', '.') ?></td>
                    <td class="text-right valor-destaque">R$ <?= number_format($d['valor_atual'] ?? 0, 2, ',', '.') ?></td>
                    <td class="text-center"><?= $d['percentual_desconto'] ?>%</td>
                    <td class="text-center"><span class="badge <?= $badge_class ?>"><?= $d['status_detalhado'] ?></span></td>
                    <td class="text-center"><?= date('d/m/Y', strtotime($d['data_cadastro'])) ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($dividas)): ?>
                <tr>
                    <td colspan="11" class="text-center" style="padding: 20px;">
                        <em>Nenhuma dívida encontrada com os filtros aplicados.</em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td colspan="6" class="text-right">TOTAIS:</td>
                <td class="text-right">R$ <?= number_format($total_valor_original ?? 0, 2, ',', '.') ?></td>
                <td class="text-right">R$ <?= number_format($total_valor_atual ?? 0, 2, ',', '.') ?></td>
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Rodapé -->
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 0.8em;">
        <strong>Relatório gerado em:</strong> <?= date('d/m/Y H:i:s') ?> |
        <strong>Usuário:</strong> <?= htmlspecialchars($_SESSION['user_nome'] ?? 'Sistema') ?> |
        <strong>Filtros aplicados:</strong>
        <?php
        $filtros_aplicados = [];
        if (!empty($filtros['busca'])) $filtros_aplicados[] = "Busca: {$filtros['busca']}";
        if (!empty($filtros['status_detalhado'])) $filtros_aplicados[] = "Status: {$filtros['status_detalhado']}";
        if (!empty($filtros['tipo'])) $filtros_aplicados[] = "Tipo: {$filtros['tipo']}";
        if (!empty($filtros['sort_by'])) $filtros_aplicados[] = "Ordenado por: {$filtros['sort_by']} {$filtros['sort_order']}";
        echo !empty($filtros_aplicados) ? implode(' | ', $filtros_aplicados) : 'Nenhum filtro específico';
        ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>
