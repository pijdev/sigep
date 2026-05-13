<?php
/**
 * SIGEP - Relatório Manual de Itens na Cela
 * Relatório para preenchimento manual de itens dos internos
 * Formato: A4 Paisagem para impressão com Toner
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    header('Location: /index.php');
    exit;
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
} catch (PDOException $e) {
    die('Erro na conexão com banco de dados');
}

// Processar filtros
$filtros = [
    'interno' => $_GET['interno'] ?? '',
    'cela' => $_GET['cela'] ?? '',
    'galeria' => $_GET['galeria'] ?? '',
    'bloco' => $_GET['bloco'] ?? ''
];

// Construir WHERE clause
$where_conditions = ["i.status = 'A'"];
$params = [];

if (!empty($filtros['interno'])) {
    $where_conditions[] = "(i.nome LIKE ? OR i.ipen = ?)";
    $params[] = '%' . $filtros['interno'] . '%';
    $params[] = $filtros['interno'];
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

$where_clause = implode(' AND ', $where_conditions);

// Consultar internos
$sql = "
    SELECT
        i.ipen,
        i.nome,
        i.kit,
        i.galeria,
        i.bloco,
        i.res,
        CONCAT(i.galeria, i.bloco, '-', i.res) as local_completo
    FROM internos i
    WHERE {$where_clause}
    ORDER BY i.galeria, i.bloco, i.res, i.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$internos = $stmt->fetchAll();

// Agrupar internos por cela
$internos_por_cela = [];
foreach ($internos as $interno) {
    $chave_cela = $interno['galeria'] . '-' . $interno['bloco'] . '-' . $interno['res'];

    if (!isset($internos_por_cela[$chave_cela])) {
        $internos_por_cela[$chave_cela] = [
            'galeria' => $interno['galeria'],
            'bloco' => $interno['bloco'],
            'res' => $interno['res'],
            'local_completo' => $interno['local_completo'],
            'internos' => []
        ];
    }
    $internos_por_cela[$chave_cela]['internos'][] = $interno;
}

// Limitar a 15 internos por cela para evitar problemas de impressão
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
    <title>Relatório de Itens na Cela - Preenchimento Manual</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
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

        .container {
            width: 100%;
            max-width: 100%;
        }

        /* Cabeçalho */
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }

        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 11px;
            color: #666;
        }

        /* Filtros */
        .filtros {
            background: #f5f5f5;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .filtros-row {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .filtro-item {
            display: flex;
            flex-direction: column;
            min-width: 120px;
        }

        .filtro-item label {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 10px;
        }

        .filtro-item input {
            padding: 4px 8px;
            border: 1px solid #ccc;
            border-radius: 3px;
            font-size: 11px;
        }

        .btn-filtrar {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-top: 5px;
        }

        .btn-filtrar:hover {
            background: #0056b3;
        }

        .btn-imprimir {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            float: right;
        }

        .btn-imprimir:hover {
            background: #1e7e34;
        }

        /* Tabela */
        .table-container {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 20px;
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
            font-size: 14px;
            text-align: center;
        }

        td {
            padding: 8px;
            vertical-align: top;
            font-size: 12px;
        }

        .ipen {
            text-align: center;
            font-weight: bold;
            min-width: 60px;
        }

        .nome {
            min-width: 200px;
            font-weight: bold;
        }

        .local {
            min-width: 120px;
            text-align: center;
        }

        .itens-field {
            min-width: 400px;
            height: 80px;
            background: white;
            border: 1px dashed #ccc;
            position: relative;
        }

        .itens-field::before {
            content: 'PREENCHER MANUALMENTE';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ccc;
            font-size: 9px;
            font-style: italic;
            pointer-events: none;
        }

        /* Container do relatório */
        .relatorio-container {
            width: 100%;
        }

        /* Quebra de página por cela */
        .pagina-cela {
            page-break-after: always;
            page-break-inside: avoid;
        }

        .pagina-cela:last-child {
            page-break-after: auto;
        }

        .conteudo-cela {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-height: calc(100vh - 20px);
            overflow: hidden;
        }

        .tabela-cela {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            flex: 1;
            font-size: 9px;
            page-break-inside: avoid;
            overflow: hidden;
            max-height: 60vh;
        }

        .header-cela {
            background: #f8f9fa;
            padding: 5px;
            margin-bottom: 5px;
            border: 2px solid #333;
            text-align: center;
            page-break-inside: avoid;
        }

        .titulo-cela {
            font-size: 11px;
            font-weight: bold;
            margin: 0;
        }

        .subtitulo-cela {
            font-size: 8px;
            color: #666;
            margin: 2px 0 0 0;
        }

        .ipen {
            text-align: center;
            font-weight: bold;
            min-width: 40px;
            font-size: 8px;
        }

        .nome {
            min-width: 120px;
            font-weight: bold;
            font-size: 8px;
        }

        .local {
            min-width: 70px;
            text-align: center;
            font-size: 8px;
        }

        .itens-field {
            min-width: 180px;
            height: 35px;
            background: white;
            border: 1px dashed #ccc;
            position: relative;
            page-break-inside: avoid;
        }

        .itens-field::before {
            content: 'PREENCHER MANUALMENTE';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #ccc;
            font-size: 7px;
            font-style: italic;
            pointer-events: none;
        }

        /* Espaço para escrita */
        .espaco-escrita {
            min-height: 30px;
            background: white;
            border: 1px solid #eee;
            padding: 2px;
        }

        /* Assinaturas */
        .assinaturas {
            display: flex;
            justify-content: space-around;
            margin-top: 40px;
        }

        .assinatura-item {
            text-align: center;
            min-width: 200px;
        }

        .linha-assinatura {
            border-bottom: 1px solid #333;
            margin: 30px 0 5px 0;
            height: 1px;
        }

        .texto-assinatura {
            font-size: 10px;
            color: #666;
        }

        /* Rodapé */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        /* Estilos para impressão */
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                font-size: 10px !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 297mm !important;
                height: 210mm !important;
            }

            .pagina-cela {
                width: 100% !important;
                height: 100vh !important;
                overflow: hidden !important;
                page-break-after: always !important;
                page-break-inside: avoid !important;
            }

            .pagina-cela:last-child {
                page-break-after: auto !important;
            }

            .conteudo-cela {
                width: 100% !important;
                height: calc(100vh - 5px) !important;
                overflow: hidden !important;
            }

            .header-cela {
                page-break-inside: avoid !important;
                margin-bottom: 3px !important;
                padding: 3px !important;
                border: 1px solid #000 !important;
                background: #f0f0f0 !important;
            }

            .tabela-cela {
                width: 100% !important;
                border-collapse: collapse !important;
                margin: 0 !important;
                font-size: 10px !important;
                page-break-inside: avoid !important;
            }

            th, td {
                border: 1px solid #000 !important;
                padding: 2px 3px !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
                vertical-align: top !important;
            }

            .ipen {
                width: 40px !important;
                font-size: 9px !important;
                text-align: center !important;
            }

            .nome {
                width: 120px !important;
                font-size: 9px !important;
            }

            .local {
                width: 70px !important;
                font-size: 9px !important;
                text-align: center !important;
            }

            .titulo-cela {
                font-size: 11px !important;
                margin: 0 !important;
            }

            .subtitulo-cela {
                font-size: 9px !important;
                margin: 1px 0 0 0 !important;
            }

            .assinaturas {
                margin-top: 8px !important;
                page-break-inside: avoid !important;
            }

            .assinatura-item {
                width: 100px !important;
                text-align: center !important;
            }

            .linha-assinatura {
                border-bottom: 1px solid #000 !important;
                margin: 10px 0 2px 0 !important;
            }

            .texto-assinatura {
                font-size: 8px !important;
            }

            tr {
                page-break-inside: avoid !important;
                height: 30px !important;
            }

            @page {
                size: A4 landscape !important;
                margin: 3mm !important;
            }
        }

        /* Responsividade */
        @media (max-width: 1200px) {
            .itens-field {
                min-width: 300px;
            }
        }

        @media (max-width: 768px) {
            .filtros-row {
                flex-direction: column;
            }

            .filtro-item {
                width: 100%;
            }

            table {
                font-size: 9px;
            }

            th, td {
                padding: 3px 4px;
            }

            .itens-field {
                min-width: 200px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho -->
        <div class="header">
            <h1>RELATÓRIO DE IDENTIFICAÇÃO DE ITENS NAS CELAS</h1>
            <p>Preenchimento Manual - Sistema Prisional Integrado SIGEP</p>
            <p>Data: <?php echo date('d/m/Y H:i'); ?></p>
        </div>

        <!-- Filtros (não imprime) -->
        <div class="filtros no-print">
            <form method="GET" action="">
                <div class="filtros-row">
                    <div class="filtro-item">
                        <label for="interno">Interno (IPEN/Nome):</label>
                        <input type="text" id="interno" name="interno" value="<?php echo htmlspecialchars($filtros['interno']); ?>" placeholder="IPEN ou nome">
                    </div>
                    <div class="filtro-item">
                        <label for="cela">Cela:</label>
                        <input type="text" id="cela" name="cela" value="<?php echo htmlspecialchars($filtros['cela']); ?>" placeholder="Número da cela">
                    </div>
                    <div class="filtro-item">
                        <label for="galeria">Galeria:</label>
                        <input type="text" id="galeria" name="galeria" value="<?php echo htmlspecialchars($filtros['galeria']); ?>" placeholder="Ex: A, B, C">
                    </div>
                    <div class="filtro-item">
                        <label for="bloco">Bloco:</label>
                        <input type="text" id="bloco" name="bloco" value="<?php echo htmlspecialchars($filtros['bloco']); ?>" placeholder="Ex: 1, 2, 3">
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn-filtrar">Filtrar</button>
                    <button type="button" class="btn-imprimir" onclick="window.print()">Imprimir</button>
                    <button type="button" class="btn-imprimir" onclick="window.location.href='?'">Limpar Filtros</button>
                </div>
            </form>
        </div>

        <!-- Tabela de Relatório -->
        <div class="table-container">
            <?php if (count($internos_por_cela) > 0): ?>
                <div class="relatorio-container">
                <?php
                $total_celas = count($internos_por_cela);
                $contador_celas = 0;

                // Converter array associativo para array indexado para evitar bug no foreach
                $celas_lista = [];
                foreach ($internos_por_cela as $chave => $dados) {
                    $celas_lista[] = $dados;
                }

                foreach ($celas_lista as $index => $dados_cela):
                    $contador_celas++;
                    $is_ultima_pagina = ($contador_celas == $total_celas);

                                    ?>
                    <div class="pagina-cela">
                        <div class="conteudo-cela">
                            <!-- Cabeçalho da Cela -->
                            <div class="header-cela">
                                <h2 class="titulo-cela">CELA: <?php echo htmlspecialchars($dados_cela['local_completo']); ?></h2>
                                <p class="subtitulo-cela">
                                    Relatório de Identificação de Itens -
                                    Total de Internos: <?php echo count($dados_cela['internos']); ?>
                                    <?php if (isset($dados_cela['limitado']) && $dados_cela['limitado']): ?>
                                        <br><strong style="color: red;">⚠️ LIMITADO A 15 INTERNOS PARA IMPRESSÃO</strong>
                                    <?php endif; ?>
                                </p>
                            </div>

                            <!-- Tabela da Cela -->
                            <table class="tabela-cela">
                                <thead>
                                    <tr>
                                        <th class="ipen">IPEN</th>
                                        <th class="nome">NOME (KIT Nº)</th>
                                        <th class="local">LOCAL</th>
                                        <th style="min-width: 200px;">ITENS ELETRÔNICOS (PREENCHER MANUALMENTE)</th>
                                        <th style="min-width: 200px;">OUTROS ITENS (ROUPAS, LIVROS, ETC.)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dados_cela['internos'] as $interno): ?>
                                        <tr>
                                            <td class="ipen"><?php echo $interno['ipen']; ?></td>
                                            <td class="nome"><?php echo htmlspecialchars(strtoupper($interno['nome'])); ?> (KIT Nº: <?php echo $interno['kit']; ?>)</td>
                                            <td class="local"><?php echo htmlspecialchars($interno['local_completo']); ?></td>
                                            <td style="border: 1px dashed #666; height: 20px;"></td>
                                            <td style="border: 1px dashed #666; height: 20px;"></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <!-- Área de Assinaturas -->
                            <div class="assinaturas">
                                <div class="assinatura-item">
                                    <div class="linha-assinatura"></div>
                                    <div class="texto-assinatura">ASSINATURA DO RESPONSÁVEL</div>
                                </div>
                                <div class="assinatura-item">
                                    <div class="linha-assinatura"></div>
                                    <div class="texto-assinatura">DATA DA VERIFICAÇÃO</div>
                                </div>
                                <div class="assinatura-item">
                                    <div class="linha-assinatura"></div>
                                    <div class="texto-assinatura">ASSINATURA DA CHEFIA</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>

                <!-- Resumo (apenas na tela, não imprime) -->
                <div class="no-print" style="margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">
                    <p><strong>RESUMO DO RELATÓRIO:</strong></p>
                    <p>Total de Celas: <?php echo count($internos_por_cela); ?></p>
                    <p>Total de Internos: <?php echo count($internos); ?></p>
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
                    <h3>NENHUM INTERNO ENCONTRADO</h3>
                    <p>Não foram encontrados internos com os filtros selecionados.</p>
                    <p>Tente ajustar os filtros ou <a href="?" style="color: #007bff;">clique aqui para limpar todos os filtros</a>.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rodapé (apenas na tela, não imprime) -->
        <div class="footer no-print">
            <p>Sistema Prisional Integrado SIGEP - Relatório gerado em <?php echo date('d/m/Y H:i:s'); ?></p>
            <p>Este documento deve ser preenchido manualmente durante a verificação das celas.</p>
            <p>Após o preenchimento, arquivar no local apropriado conforme normas da unidade.</p>
        </div>
    </div>

    <script>
        // Limpar filtros
        function limparFiltros() {
            window.location.href = window.location.pathname;
        }

        // Auto-focus no primeiro campo
        document.addEventListener('DOMContentLoaded', function() {
            const primeiroCampo = document.querySelector('input[type="text"]');
            if (primeiroCampo) {
                primeiroCampo.focus();
            }
        });

        // Permitir Enter para filtrar
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                e.preventDefault();
                document.querySelector('.btn-filtrar').click();
            }
        });
    </script>
</body>
</html>
