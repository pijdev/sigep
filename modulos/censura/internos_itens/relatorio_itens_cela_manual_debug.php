<?php
// VERSÃO DEBUG - SEM AUTENTICAÇÃO
session_start();

// Conexão direta com o banco
require_once __DIR__ . '/../../../conf/db.php';

try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Filtros fixos para teste
$filtros = [
    'interno' => $_GET['interno'] ?? '',
    'cela' => $_GET['cela'] ?? '',
    'galeria' => $_GET['galeria'] ?? 'd',
    'bloco' => $_GET['bloco'] ?? 'a'
];

// Montar WHERE clause
$where_conditions = [];
$params = [];

if (!empty($filtros['interno'])) {
    $where_conditions[] = "(i.ipen LIKE ? OR i.nome LIKE ?)";
    $params[] = "%" . $filtros['interno'] . "%";
    $params[] = "%" . $filtros['interno'] . "%";
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

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Consulta principal
$sql = "
    SELECT
        i.ipen,
        i.nome,
        i.galeria,
        i.bloco,
        i.res,
        CONCAT(i.galeria, '-', i.bloco, '-', i.res) as local_completo
    FROM internos i
    {$where_clause}
    ORDER BY i.galeria, i.bloco, i.res, i.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$internos = $stmt->fetchAll();

// Agrupar internos por cela
$internos_por_cela = [];
foreach ($internos as $index => $interno) {
    $chave_cela = $interno['galeria'] . '-' . $interno['bloco'] . '-' . $interno['res'];

    echo "<!-- AGRUPAMENTO_INDEX_$index: Chave='$chave_cela' | IPEN={$interno['ipen']} | Nome={$interno['nome']} -->\n";

    if (!isset($internos_por_cela[$chave_cela])) {
        $internos_por_cela[$chave_cela] = [
            'galeria' => $interno['galeria'],
            'bloco' => $interno['bloco'],
            'res' => $interno['res'],
            'local_completo' => $interno['local_completo'],
            'internos' => []
        ];
        echo "<!-- CRIADA_CELA: $chave_cela -->\n";
    } else {
        echo "<!-- CELA_JA_EXISTIA: $chave_cela -->\n";
    }
    $internos_por_cela[$chave_cela]['internos'][] = $interno;
}

// DEBUG: Consulta direta ao banco para D-A-8 e D-A-9
$sql_debug = "
    SELECT galeria, bloco, res, CONCAT(galeria, '-', bloco, '-', res) as local_completo, COUNT(*) as total
    FROM internos
    WHERE galeria = 'd' AND bloco = 'a' AND res IN ('8', '9')
    GROUP BY galeria, bloco, res
    ORDER BY res
";
$stmt_debug = $pdo->prepare($sql_debug);
$stmt_debug->execute();
$dados_debug = $stmt_debug->fetchAll();

echo "<!-- DEBUG_SQL_DADOS -->\n";
foreach ($dados_debug as $dado) {
    echo "<!-- SQL_{$dado['local_completo']}: galeria='{$dado['galeria']}' | bloco='{$dado['bloco']}' | res='{$dado['res']}' | total={$dado['total']} -->\n";
}
echo "<!-- DEBUG_SQL_FIM -->\n";

// DEBUG: Mostrar estrutura completa do array
echo "<!-- DEBUG_ARRAY_START -->\n";
echo "<!-- Total de celas: " . count($internos_por_cela) . " -->\n";
$contador_debug = 0;
foreach ($internos_por_cela as $chave => $dados) {
    $contador_debug++;
    echo "<!-- DEBUG_ARRAY[$contador_debug]: Chave='$chave' | Local='{$dados['local_completo']}' | Internos=" . count($dados['internos']) . " -->\n";
}
echo "<!-- DEBUG_ARRAY_END -->\n";

// Limitar a 15 internos por cela
foreach ($internos_por_cela as $chave_cela => &$dados_cela) {
    if (count($dados_cela['internos']) > 15) {
        $dados_cela['internos'] = array_slice($dados_cela['internos'], 0, 15);
        $dados_cela['limitado'] = true;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DEBUG - Relatório de Itens na Cela</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-info { background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc; }
        .pagina-cela { border: 2px solid #333; margin: 20px 0; padding: 10px; }
        .header-cela { background: #f8f9fa; padding: 5px; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #333; padding: 5px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>DEBUG - Relatório de Itens na Cela</h1>
    <div class="debug-info">
        <p><strong>Filtros:</strong> Galeria=<?php echo $filtros['galeria']; ?> | Bloco=<?php echo $filtros['bloco']; ?></p>
        <p><strong>Total de celas:</strong> <?php echo count($internos_por_cela); ?></p>
        <p><strong>Total de internos:</strong> <?php echo count($internos); ?></p>
    </div>

    <?php if (count($internos_por_cela) > 0): ?>
        <?php
        $total_celas = count($internos_por_cela);
        $contador_celas = 0;

        $chaves_cela = array_keys($internos_por_cela);
        foreach ($chaves_cela as $index => $chave_cela):
            $dados_cela = $internos_por_cela[$chave_cela];
            $contador_celas++;
            $is_ultima_pagina = ($contador_celas == $total_celas);

            // DEBUG: Mostrar exatamente o que está sendo renderizado
            echo "<!-- RENDER_ITERACAO_$contador_celas: Chave='$chave_cela' | Local='{$dados_cela['local_completo']}' | Internos=" . count($dados_cela['internos']) . " -->\n";
        ?>
            <div class="pagina-cela">
                <div class="header-cela">
                    <h2>CELA: <?php echo htmlspecialchars($dados_cela['local_completo']); ?></h2>
                    <p>Total de Internos: <?php echo count($dados_cela['internos']); ?></p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>IPEN</th>
                            <th>NOME</th>
                            <th>LOCAL</th>
                            <th>ITENS ELETRÔNICOS</th>
                            <th>OUTROS ITENS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dados_cela['internos'] as $interno): ?>
                            <tr>
                                <td><?php echo $interno['ipen']; ?></td>
                                <td><?php echo htmlspecialchars($interno['nome']); ?></td>
                                <td><?php echo $interno['local_completo']; ?></td>
                                <td style="border: 1px dashed #666; height: 20px;"></td>
                                <td style="border: 1px dashed #666; height: 20px;"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="debug-info">
            <h3>NENHUM INTERNO ENCONTRADO</h3>
            <p>Não foram encontrados internos com os filtros selecionados.</p>
        </div>
    <?php endif; ?>

    <div class="debug-info">
        <p><strong>DEBUG FINAL:</strong> Loop executado <?php echo $contador_celas; ?> vezes</p>
        <p><strong>ARRAY FINAL:</strong> <?php echo count($internos_por_cela); ?> celas</p>
    </div>
</body>
</html>
