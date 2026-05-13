<?php

/**
 * Lista de Trabalho - Consulta de Internos
 * Busca dados dos internos nas tabelas internos e internos_laboral
 */

require_once __DIR__ . '/conf/db.php';

// Array de IPENs extraído do arquivo lista_trabalho.html
$ipens = [
    774086,
    729164,
    619449,
    644440,
    786557,
    702676,
    517246,
    687517,
    784872,
    724711,
    654861,
    780748,
    682472,
    717456,
    717655,
    806760,
    636935,
    701161,
    517382,
    606014,
    707467,
    738241,
    552111,
    539093,
    745922,
    785427,
    668996,
    564611,
    643457,
    548582,
    795427,
    781992,
    790302,
    798294,
    762004,
    529364,
    633465,
    525431,
    699809,
    592567,
    739172,
    563285,
    697620,
    512557,
    698988,
    491468,
    485271,
    677135,
    580225,
    672588,
    574990,
    640410,
    662986,
    598733,
    794236,
    690618,
    611163,
    663350,
    635400,
    560790,
    781025,
    549013,
    578735,
    555088,
    633461,
    532800,
    787596,
    742482,
    549891,
    754221,
    796805,
    663186,
    646884,
    547891,
    573859,
    787350,
    666974,
    786729,
    777223,
    754005,
    666152,
    555244,
    788207,
    596218,
    517846,
    755054,
    783232,
    546026,
    710650,
    569163,
    616388,
    629914,
    494877,
    734486,
    766022,
    474580,
    732408,
    626260,
    786664,
    789048,
    707099,
    733104,
    699116,
    776513,
    510277,
    561252
];

// Conexão PDO
try {
    $config = require __DIR__ . '/conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    die('Erro na conexão com banco de dados: ' . $e->getMessage());
}

// Função para determinar se deve fazer roupa
function deveFazerRoupa($estabelecimento)
{
    if (empty($estabelecimento)) {
        return true; // Não tem local de trabalho
    }

    $locais_fazer = [
        'REGALIA DE GALERIA ALIMENTAÇÃO (REMIÇÃO)',
        'FUNDO ROTATIVO',
        'SECRETARIA DE ESTADO',
        'CORTE DE CABELO (REMIÇÃO)'
    ];

    foreach ($locais_fazer as $local) {
        if (stripos($estabelecimento, $local) !== false) {
            return true;
        }
    }

    return false;
}

// Função para formatar local no padrão galeriabloco-cela
function formatarLocal($galeria, $bloco, $res)
{
    $partes = [];

    if (!empty($galeria)) {
        $partes[] = strtoupper($galeria);
    }

    if (!empty($bloco)) {
        $partes[] = strtoupper($bloco);
    }

    if (!empty($res)) {
        $partes[] = $res;
    }

    if (empty($partes)) {
        return '-';
    }

    return implode('-', $partes);
}

// Consultar dados dos internos
$placeholders = str_repeat('?,', count($ipens) - 1) . '?';
$sql = "
    SELECT
        i.ipen,
        CASE
            WHEN i.nome_social != '' THEN i.nome_social
            ELSE i.nome
        END as nome,
        i.kit as numero_kit,
        i.cor_roupa,
        i.galeria,
        i.bloco,
        i.res,
        i.situacao,
        il.estabelecimento as local_trabalho,
        il.status as status_laboral
    FROM internos i
    LEFT JOIN internos_laboral il ON i.ipen = il.ipen AND il.status = 'A'
    WHERE i.ipen IN ($placeholders)
    ORDER BY i.nome
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($ipens);
    $internos = $stmt->fetchAll();
} catch (PDOException $e) {
    die('Erro ao consultar internos: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Trabalho - Internos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            padding: 10px;
            background: white;
            font-size: 10px;
        }

        h1 {
            margin-bottom: 10px;
            color: #333;
            font-size: 14px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            font-size: 9px;
        }

        thead {
            background: #f0f0f0;
            color: black;
        }

        th,
        td {
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #000;
            vertical-align: middle;
        }

        th {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
            white-space: nowrap;
        }

        tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .tag-fazer {
            background: #28a745;
            color: white;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 8px;
        }

        .tag-nao-fazer {
            background: #dc3545;
            color: white;
            padding: 2px 4px;
            border-radius: 2px;
            font-weight: bold;
            font-size: 8px;
        }

        .cor-laranja {
            color: #000;
            font-weight: normal;
        }

        .cor-verde {
            color: #000;
            font-weight: normal;
        }

        /* Colunas com larguras específicas */
        th:nth-child(1),
        td:nth-child(1) {
            width: 60px;
            text-align: center;
        }

        th:nth-child(2),
        td:nth-child(2) {
            width: 180px;
        }

        th:nth-child(3),
        td:nth-child(3) {
            width: 40px;
            text-align: center;
        }

        th:nth-child(4),
        td:nth-child(4) {
            width: 60px;
            text-align: center;
        }

        th:nth-child(5),
        td:nth-child(5) {
            width: 70px;
            text-align: center;
        }

        th:nth-child(6),
        td:nth-child(6) {
            width: 120px;
        }

        th:nth-child(7),
        td:nth-child(7) {
            width: auto;
        }

        th:nth-child(8),
        td:nth-child(8) {
            width: 70px;
            text-align: center;
        }

        @media print {
            body {
                padding: 5mm;
                background: white;
            }

            h1 {
                font-size: 12pt;
                margin-bottom: 5mm;
            }

            table {
                font-size: 8pt;
            }

            th,
            td {
                padding: 2pt 3pt;
                border: 1px solid #000;
            }

            th {
                font-size: 7pt;
            }

            .tag-fazer,
            .tag-nao-fazer {
                font-size: 7pt;
                padding: 1pt 3pt;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>

<body>
    <h1>Lista de Trabalho - Internos (<?php echo count($internos); ?> registros)</h1>

    <table>
        <thead>
            <tr>
                <th>IPEN</th>
                <th>Nome</th>
                <th>Kit</th>
                <th>Cor Roupa</th>
                <th>Local</th>
                <th>Situação</th>
                <th>Local de Trabalho</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($internos as $interno): ?>
                <?php
                $local = formatarLocal($interno['galeria'], $interno['bloco'], $interno['res']);
                $fazer = deveFazerRoupa($interno['local_trabalho']);
                $tag = $fazer ? 'FAZER' : 'NÃO FAZER';
                $tagClass = $fazer ? 'tag-fazer' : 'tag-nao-fazer';
                $corClass = $interno['cor_roupa'] === 'Laranja' ? 'cor-laranja' : 'cor-verde';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($interno['ipen']); ?></td>
                    <td><?php echo htmlspecialchars($interno['nome']); ?></td>
                    <td><?php echo htmlspecialchars($interno['numero_kit'] ?? '-'); ?></td>
                    <td class="<?php echo $corClass; ?>"><?php echo htmlspecialchars($interno['cor_roupa'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($local); ?></td>
                    <td><?php echo htmlspecialchars($interno['situacao'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($interno['local_trabalho'] ?? '-'); ?></td>
                    <td><span class="<?php echo $tagClass; ?>"><?php echo $tag; ?></span></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>
