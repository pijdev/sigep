<?php
// Relatório de Colchões - Filtro Customizado
session_start();
require_once __DIR__ . '/../../conf/db.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    die('Acesso negado. Sessão expirada.');
}

// Verificar permissão do setor Censura
if (!isset($_SESSION['user_admin']) || $_SESSION['user_admin'] != 1) {
    if (!isset($_SESSION['perm_censura']) || ($_SESSION['perm_censura'] != 1 && $_SESSION['perm_censura'] != 2)) {
        die('Acesso negado. Permissão insuficiente.');
    }
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com banco
try {
    $config = require __DIR__ . '/../../conf/db.php';
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
    'tipo' => $_GET['tipo'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? ''
];

// Query base para entradas
$sqlEntradas = "
    SELECT
        e.data_entrada as data,
        'Entrada' as tipo,
        e.quantidade,
        l.nome as local_destino,
        e.origem as destino,
        u.nome as responsavel,
        e.observacoes,
        e.documento_referencia
    FROM internos_colchoes_entradas e
    INNER JOIN internos_colchoes_locais l ON e.id_local_destino = l.id
    LEFT JOIN users u ON e.cadastrado_por = u.id
    WHERE 1=1
";

$paramsEntradas = [];

if (!empty($filtros['data_inicio'])) {
    $sqlEntradas .= " AND e.data_entrada >= ?";
    $paramsEntradas[] = $filtros['data_inicio'];
}

if (!empty($filtros['data_fim'])) {
    $sqlEntradas .= " AND e.data_entrada <= ?";
    $paramsEntradas[] = $filtros['data_fim'];
}

$sqlEntradas .= " ORDER BY e.data_entrada DESC";

// Query base para saídas
$sqlSaidas = "
    SELECT
        s.data_saida as data,
        'Saida' as tipo,
        s.quantidade,
        lo.nome as local_origem,
        CASE
            WHEN s.tipo_destino = 'Interno' THEN CONCAT('Interno: ', i.nome, ' (', i.ipen, ')')
            WHEN s.tipo_destino = 'Alojamento_Policia' THEN 'Alojamento Polícia'
            ELSE s.destino_outro
        END as destino,
        u.nome as responsavel,
        s.observacoes,
        s.motivo_saida
    FROM internos_colchoes_saidas s
    INNER JOIN internos_colchoes_locais lo ON s.id_local_origem = lo.id
    LEFT JOIN internos i ON s.id_interno = i.ipen
    LEFT JOIN users u ON s.cadastrado_por = u.id
    WHERE s.status = 'Ativo'
";

$paramsSaidas = [];

if (!empty($filtros['data_inicio'])) {
    $sqlSaidas .= " AND s.data_saida >= ?";
    $paramsSaidas[] = $filtros['data_inicio'];
}

if (!empty($filtros['data_fim'])) {
    $sqlSaidas .= " AND s.data_saida <= ?";
    $paramsSaidas[] = $filtros['data_fim'];
}

$sqlSaidas .= " ORDER BY s.data_saida DESC";

// Executar queries
$historico = [];

if (empty($filtros['tipo']) || $filtros['tipo'] === 'Entrada') {
    $stmt = $pdo->prepare($sqlEntradas);
    $stmt->execute($paramsEntradas);
    $historico = array_merge($historico, $stmt->fetchAll());
}

if (empty($filtros['tipo']) || $filtros['tipo'] === 'Saida') {
    $stmt = $pdo->prepare($sqlSaidas);
    $stmt->execute($paramsSaidas);
    $historico = array_merge($historico, $stmt->fetchAll());
}

// Ordenar por data (decrescente)
usort($historico, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});

// Calcular totais
$total_registros = count($historico);
$total_entradas = array_sum(array_filter(array_column($historico, 'quantidade'), function($q, $k) use ($historico) { return $historico[$k]['tipo'] === 'Entrada'; }, ARRAY_FILTER_USE_BOTH));
$total_saidas = array_sum(array_filter(array_column($historico, 'quantidade'), function($q, $k) use ($historico) { return $historico[$k]['tipo'] === 'Saida'; }, ARRAY_FILTER_USE_BOTH));
$saldo_geral = $total_entradas - $total_saidas;

// Obter resumo do estoque atual
$sqlEstoque = "
    SELECT
        l.nome,
        l.tipo,
        l.capacidade_maxima,
        COALESCE(e.quantidade, 0) as quantidade,
        e.ultima_atualizacao
    FROM internos_colchoes_locais l
    LEFT JOIN internos_colchoes_estoque e ON l.id = e.id_local
    WHERE l.status = 'Ativo'
    ORDER BY l.nome
";

$stmt = $pdo->query($sqlEstoque);
$estoque_atual = $stmt->fetchAll();
$total_estoque = array_sum(array_column($estoque_atual, 'quantidade'));

// Título do relatório
$txt_filtro = "TODAS AS MOVIMENTAÇÕES";
if (!empty($filtros['tipo'])) {
    $txt_filtro = strtoupper($filtros['tipo']) . "S DE COLCHÕES";
}
if (!empty($filtros['data_inicio']) && !empty($filtros['data_fim'])) {
    $txt_filtro .= " - PERÍODO: " . date('d/m/Y', strtotime($filtros['data_inicio'])) . " a " . date('d/m/Y', strtotime($filtros['data_fim']));
} elseif (!empty($filtros['data_inicio'])) {
    $txt_filtro .= " - A PARTIR DE: " . date('d/m/Y', strtotime($filtros['data_inicio']));
} elseif (!empty($filtros['data_fim'])) {
    $txt_filtro .= " - ATÉ: " . date('d/m/Y', strtotime($filtros['data_fim']));
}

$titulo_relatorio = "RELATÓRIO DE CONTROLE DE COLCHÕES - $txt_filtro - " . date("d/m/Y H:i");

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_relatorio ?></title>
    <link rel="icon" type="image/svg+xml" href="../../favicon.svg">
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

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .resumo-box {
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }

        .resumo-estoque {
            margin-bottom: 15px;
            padding: 10px;
            background: #e8f5e8;
            border-radius: 5px;
            border-left: 4px solid #28a745;
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
        <small class="text-muted">Sistema SIGEP - Módulo de Gestão de Colchões</small>
    </div>

    <!-- Resumo do Estoque Atual -->
    <div class="resumo-estoque">
        <strong>RESUMO DO ESTOQUE ATUAL</strong><br>
        <strong>Total em Estoque:</strong> <?= $total_estoque ?> colchões |
        <strong>Locais Ativos:</strong> <?= count($estoque_atual) ?>
        
        <table style="margin-top: 10px; margin-bottom: 0;">
            <thead>
                <tr>
                    <th style="width: 30%;">Local</th>
                    <th style="width: 15%;">Tipo</th>
                    <th style="width: 15%;">Capacidade</th>
                    <th style="width: 15%;">Quantidade</th>
                    <th style="width: 25%;">Última Atualização</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($estoque_atual as $local): ?>
                    <tr>
                        <td><?= htmlspecialchars($local['nome']) ?></td>
                        <td><?= htmlspecialchars($local['tipo']) ?></td>
                        <td class="text-center"><?= $local['capacidade_maxima'] ?: 'N/A' ?></td>
                        <td class="text-center font-bold"><?= $local['quantidade'] ?></td>
                        <td class="text-center"><?= $local['ultima_atualizacao'] ? date('d/m/Y H:i', strtotime($local['ultima_atualizacao'])) : 'N/A' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Resumo das Movimentações -->
    <div class="resumo-box">
        <strong>RESUMO DAS MOVIMENTAÇÕES</strong><br>
        <strong>Total de Registros:</strong> <?= $total_registros ?> |
        <strong>Total de Entradas:</strong> <?= $total_entradas ?> colchões |
        <strong>Total de Saídas:</strong> <?= $total_saidas ?> colchões |
        <strong>Saldo Geral:</strong> <?= $saldo_geral ?> colchões
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

    <!-- Tabela de Movimentações -->
    <table>
        <thead>
            <tr>
                <th style="width: 80px;">Data</th>
                <th style="width: 70px;">Tipo</th>
                <th style="width: 80px;">Quantidade</th>
                <th style="width: 120px;">Local</th>
                <th style="width: 200px;">Destino</th>
                <th style="width: 120px;">Responsável</th>
                <th style="width: 150px;">Motivo/Referência</th>
                <th style="width: 200px;">Observações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historico as $item):
                $badge_class = $item['tipo'] === 'Entrada' ? 'badge-success' : 'badge-danger';
                $motivo_ref = $item['tipo'] === 'Entrada' ? ($item['documento_referencia'] ?: '-') : ($item['motivo_saida'] ?: '-');
            ?>
                <tr>
                    <td class="text-center"><?= date('d/m/Y', strtotime($item['data'])) ?></td>
                    <td class="text-center"><span class="badge <?= $badge_class ?>"><?= $item['tipo'] ?></span></td>
                    <td class="text-center font-bold"><?= $item['quantidade'] ?></td>
                    <td><?= htmlspecialchars($item['local_origem'] ?: $item['local_destino'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($item['destino'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($item['responsavel'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($motivo_ref) ?></td>
                    <td><?= htmlspecialchars($item['observacoes'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($historico)): ?>
                <tr>
                    <td colspan="8" class="text-center" style="padding: 20px;">
                        <em>Nenhuma movimentação encontrada com os filtros aplicados.</em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAIS:</td>
                <td class="text-center"><?= $total_registros ?></td>
                <td colspan="2"></td>
                <td colspan="3"></td>
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
        if (!empty($filtros['tipo'])) $filtros_aplicados[] = "Tipo: {$filtros['tipo']}";
        if (!empty($filtros['data_inicio'])) $filtros_aplicados[] = "Data Início: " . date('d/m/Y', strtotime($filtros['data_inicio']));
        if (!empty($filtros['data_fim'])) $filtros_aplicados[] = "Data Fim: " . date('d/m/Y', strtotime($filtros['data_fim']));
        echo !empty($filtros_aplicados) ? implode(' | ', $filtros_aplicados) : 'Nenhum filtro específico';
        ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>
