<?php
// Relatório de Itens Cadastrados por Interno - SIGEP
// Para uso como base na verificação de celas

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta HTML
header('Content-Type: text/html; charset=utf-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar Timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    die('<h1>Acesso Negado</h1><p>Usuário não autenticado.</p>');
}

// Verificar permissão
if (!($_SESSION['user_admin'] || ($_SESSION['perm_censura'] ?? 0) || ($_SESSION['perm_coord'] ?? 0) || ($_SESSION['perm_direcao'] ?? 0))) {
    die('<h1>Acesso Negado</h1><p>Sem permissão para acessar este módulo.</p>');
}

// Configurar conexão PDO
try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die('<h1>Erro na Conexão</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Capturar filtros
$filtros = [
    'interno' => $_GET['interno'] ?? '',
    'cela' => $_GET['cela'] ?? '',
    'galeria' => $_GET['galeria'] ?? '',
    'bloco' => $_GET['bloco'] ?? ''
];

// Construir cláusula WHERE
$where_conditions = [];
$params = [];

if (!empty($filtros['interno'])) {
    $where_conditions[] = "(i.ipen LIKE ? OR i.nome LIKE ?)";
    $params[] = '%' . $filtros['interno'] . '%';
    $params[] = '%' . $filtros['interno'] . '%';
}

if (!empty($filtros['cela'])) {
    $where_conditions[] = "i.res = ?";
    $params[] = $filtros['cela'];
}

if (!empty($filtros['galeria'])) {
    $where_conditions[] = "i.galeria = ?";
    $params[] = $filtros['galeria'];
}

if (!empty($filtros['bloco'])) {
    $where_conditions[] = "i.bloco = ?";
    $params[] = $filtros['bloco'];
}

$where_conditions[] = "i.status = 'A'";
$where_conditions[] = "ie.situacao = 'Na Cela'";

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Buscar itens eletrônicos dos internos
$sql = "
    SELECT
        i.ipen,
        i.nome,
        CONCAT(i.galeria, ' - ', i.bloco, ' - ', i.res) as local_completo,
        ie.tipo_item,
        ie.marca_modelo,
        ie.cor,
        ie.polegadas,
        ie.tem_controle,
        ie.tem_fonte,
        ie.estado_conservacao,
        CASE
            WHEN ie.tem_controle = 'Sim' AND ie.polegadas IS NOT NULL THEN
                CONCAT(ie.tipo_item, ' ', ie.marca_modelo, ' ', ie.polegadas, '\"', ' Com Controle')
            WHEN ie.polegadas IS NOT NULL THEN
                CONCAT(ie.tipo_item, ' ', ie.marca_modelo, ' ', ie.polegadas, '\"')
            WHEN ie.tem_controle = 'Sim' THEN
                CONCAT(ie.tipo_item, ' ', ie.marca_modelo, ' Com Controle')
            ELSE
                CONCAT(ie.tipo_item, ' ', ie.marca_modelo)
        END as item_descricao
    FROM internos i
    INNER JOIN internos_eletronicos ie ON i.ipen = ie.id_interno
    {$where_clause}
    ORDER BY i.galeria, i.bloco, i.res, i.nome, ie.tipo_item
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll();

// Agrupar itens por interno
$itens_por_interno = [];
foreach ($itens as $item) {
    $ipen = $item['ipen'];
    if (!isset($itens_por_interno[$ipen])) {
        $itens_por_interno[$ipen] = [
            'ipen' => $item['ipen'],
            'nome' => $item['nome'],
            'local_completo' => $item['local_completo'],
            'itens' => []
        ];
    }
    $itens_por_interno[$ipen]['itens'][] = $item;
}

// Contar itens por tipo para cada interno
foreach ($itens_por_interno as $ipen => &$interno) {
    $contagem = [];
    foreach ($interno['itens'] as $item) {
        $chave = $item['item_descricao'];
        if (!isset($contagem[$chave])) {
            $contagem[$chave] = 0;
        }
        $contagem[$chave]++;
    }

    $interno['itens_contados'] = [];
    foreach ($contagem as $descricao => $quantidade) {
        $interno['itens_contados'][] = [
            'descricao' => $descricao,
            'quantidade' => $quantidade
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Itens Cadastrados por Interno</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            background: white;
            color: black;
        }

        /* Configuração A4 Paisagem */
        @page {
            size: A4 landscape;
            margin: 15mm;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        .filtros-info {
            background: #f9f9f9;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            font-size: 10px;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 11px;
            text-align: center;
        }

        .ipen {
            text-align: center;
            font-weight: bold;
            min-width: 60px;
            font-size: 10px;
        }

        .nome {
            min-width: 200px;
            font-weight: bold;
            font-size: 10px;
        }

        .local {
            min-width: 100px;
            font-size: 10px;
        }

        .item-descricao {
            min-width: 300px;
            font-size: 9px;
        }

        .quantidade {
            text-align: center;
            min-width: 60px;
            font-weight: bold;
            font-size: 10px;
        }

        .interno-header {
            background: #e8f4fd !important;
            font-weight: bold;
        }

        .interno-header td {
            font-size: 11px;
            padding: 8px;
        }

        .item-row td {
            font-size: 9px;
            padding: 4px 6px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .no-print {
            margin-top: 20px;
            padding: 15px;
            background: #f0f8ff;
            border: 1px solid #007bff;
            border-radius: 5px;
        }

        .no-print h3 {
            color: #007bff;
            margin-bottom: 10px;
        }

        .no-print button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }

        .no-print button:hover {
            background: #0056b3;
        }

        /* Estilos para impressão */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 9px;
            }

            .header h1 {
                font-size: 14px;
            }

            .header p {
                font-size: 9px;
            }

            .filtros-info {
                font-size: 8px;
            }

            th, td {
                font-size: 8px;
                padding: 3px 4px;
            }

            .ipen {
                font-size: 8px;
            }

            .nome {
                font-size: 8px;
            }

            .local {
                font-size: 8px;
            }

            .item-descricao {
                font-size: 7px;
            }

            .quantidade {
                font-size: 8px;
            }

            .interno-header td {
                font-size: 9px;
            }

            .item-row td {
                font-size: 7px;
            }

            @page {
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <!-- Cabeçalho -->
    <div class="header">
        <h1>RELATÓRIO DE ITENS ELETRÔNICOS CADASTRADOS POR INTERNO</h1>
        <p>Data: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <!-- Informações dos Filtros -->
    <?php if (!empty(array_filter($filtros))): ?>
        <div class="filtros-info">
            <strong>Filtros Aplicados:</strong>
            <?php
            $filtros_aplicados = [];
            if (!empty($filtros['interno'])) $filtros_aplicados[] = "Interno: {$filtros['interno']}";
            if (!empty($filtros['cela'])) $filtros_aplicados[] = "Cela: {$filtros['cela']}";
            if (!empty($filtros['galeria'])) $filtros_aplicados[] = "Galeria: {$filtros['galeria']}";
            if (!empty($filtros['bloco'])) $filtros_aplicados[] = "Bloco: {$filtros['bloco']}";
            echo !empty($filtros_aplicados) ? implode(' | ', $filtros_aplicados) : 'Nenhum';
            ?>
        </div>
    <?php endif; ?>

    <!-- Tabela de Relatório -->
    <div class="table-container">
        <?php if (count($itens_por_interno) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th class="ipen">IPEN</th>
                        <th class="nome">INTERNO</th>
                        <th class="local">LOCALIZAÇÃO</th>
                        <th class="item-descricao">ITEM</th>
                        <th class="quantidade">QUANTIDADE</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens_por_interno as $interno): ?>
                        <!-- Cabeçalho do Interno -->
                        <tr class="interno-header">
                            <td class="ipen"><?php echo $interno['ipen']; ?></td>
                            <td colspan="4"><?php echo htmlspecialchars(strtoupper($interno['nome'])); ?> - <?php echo htmlspecialchars($interno['local_completo']); ?></td>
                        </tr>

                        <!-- Itens do Interno -->
                        <?php foreach ($interno['itens_contados'] as $item): ?>
                            <tr class="item-row">
                                <td></td>
                                <td></td>
                                <td></td>
                                <td class="item-descricao"><?php echo htmlspecialchars($item['descricao']); ?></td>
                                <td class="quantidade">x<?php echo $item['quantidade']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Resumo -->
            <div style="margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                <p><strong>RESUMO DO RELATÓRIO:</strong></p>
                <p>Total de Internos: <?php echo count($itens_por_interno); ?></p>
                <p>Total de Itens: <?php echo count($itens); ?></p>
                <p>Filtros aplicados:
                    <?php
                    $filtros_aplicados = [];
                    if (!empty($filtros['interno'])) $filtros_aplicados[] = "Interno: {$filtros['interno']}";
                    if (!empty($filtros['cela'])) $filtros_aplicados[] = "Cela: {$filtros['cela']}";
                    if (!empty($filtros['galeria'])) $filtros_aplicados[] = "Galeria: {$filtros['galeria']}";
                    if (!empty($filtros['bloco'])) $filtros_aplicados[] = "Bloco: {$filtros['bloco']}";
                    echo !empty($filtros_aplicados) ? implode(' | ', $filtros_aplicados) : 'Nenhum';
                    ?>
                </p>
            </div>

        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #f9f9f9; border: 1px solid #ddd;">
                <h3>NENHUM ITEM ENCONTRADO</h3>
                <p>Não foram encontrados itens eletrônicos com os filtros selecionados.</p>
                <p>Tente ajustar os filtros ou <a href="?" style="color: #007bff;">clique aqui para limpar todos os filtros</a>.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        <p>Sistema Prisional Integrado SIGEP - Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
        <p>Este relatório lista todos os itens eletrônicos cadastrados para uso como base na verificação das celas.</p>
    </div>

    <!-- Controles (apenas na tela) -->
    <div class="no-print">
        <h3>Controles do Relatório</h3>
        <button onclick="window.print()"><i class="fas fa-print"></i> Imprimir Relatório</button>
        <button onclick="window.history.back()"><i class="fas fa-arrow-left"></i> Voltar</button>
    </div>
</body>
</html>
