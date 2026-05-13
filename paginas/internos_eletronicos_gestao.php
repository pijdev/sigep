<?php
// paginas/internos_eletronicos_gestao.php

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar permissões para movimentação de chuveiros
$eh_admin = isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true;
$eh_censura = isset($_SESSION['perm_censura']) && (int)$_SESSION['perm_censura'] > 0;
$eh_manutencao = isset($_SESSION['perm_manutencao']) && (int)$_SESSION['perm_manutencao'] > 0;
$pode_mover_chuveiros = $eh_admin || $eh_censura || $eh_manutencao;

$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

$ipen_filter = isset($_GET['ipen']) ? trim($_GET['ipen']) : null;
$galeria_filter = isset($_GET['galeria']) ? trim($_GET['galeria']) : null;
$bloco_filter = isset($_GET['bloco']) ? trim($_GET['bloco']) : null;
$ala_filter = isset($_GET['ala']) ? trim($_GET['ala']) : null;
$cela_filter = isset($_GET['cela']) ? trim($_GET['cela']) : null;

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Erro DB");
}

// --- AJAX ---
if (isset($_REQUEST['acao'])) {

    // Listar Itens de um Interno Específico
    if ($_REQUEST['acao'] === 'listar_itens_interno') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $stmt = $pdo->prepare("
                SELECT e.*,
                       COALESCE(di.id_doacao, 0) as id_doacao_origem,
                       COALESCE(d.id_doador, 0) as id_doador_origem,
                       COALESCE(i_doador.nome, '') as nome_doador_origem,
                       d.data_doacao as data_doacao_origem
                FROM internos_eletronicos e
                LEFT JOIN internos_doacao_eletronicos_itens di ON e.id = di.id_eletronico_transferido
                LEFT JOIN internos_doacao_eletronicos d ON di.id_doacao = d.id
                LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
                WHERE e.id_interno = ? AND e.situacao != 'Retirado'
                ORDER BY e.tipo_item
            ");
            $stmt->execute([$_POST['ipen']]);
            echo json_encode(['status' => 'success', 'dados' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    // Listar para Offcanvas Cards
    if ($_REQUEST['acao'] === 'listar_por_status') {
        ob_clean();
        header('Content-Type: application/json');
        $status = $_POST['status'];

        $whereSql = "WHERE e.situacao != 'Retirado'";
        if ($status !== 'Total' && $status !== 'Retirado') $whereSql = "WHERE e.situacao = '$status'";
        if ($status === 'Retirado') $whereSql = "WHERE e.situacao = 'Retirado'";

        if ($ipen_filter) $whereSql .= " AND e.id_interno = '$ipen_filter'";

        $galeria = $_POST['galeria'] ?? null;
        $bloco = $_POST['bloco'] ?? null;
        $ala = $_POST['ala'] ?? null;
        $cela = $_POST['cela'] ?? null;

        if ($galeria && $cela) {
            $cell_where = "galeria = '$galeria' AND res = '$cela' AND status = 'A'";
            if ($bloco !== '' && $bloco !== null) $cell_where .= " AND bloco = '$bloco'";
            if ($ala !== '' && $ala !== null) $cell_where .= " AND ala = '$ala'";
            $whereSql .= " AND e.id_interno IN (SELECT ipen FROM internos WHERE $cell_where)";
        }

        $sql = "SELECT e.*, i.nome, i.galeria, i.bloco, i.res
                FROM internos_eletronicos e
                JOIN internos i ON e.id_interno = i.ipen
                $whereSql
                ORDER BY e.data_entrada DESC LIMIT 100";

        $res = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'dados' => $res]);
        exit;
    }

    if ($_REQUEST['acao'] === 'alterar_situacao') {
        ob_clean();
        header('Content-Type: application/json');
        try {
            $id = $_POST['id_item'];
            $nova = $_POST['nova_situacao'];

            // Regra Duplicidade na Cela (Exceto se for Máquina Cabelo que pode ser coletivo, ou Extensão)
            // Se a regra for rígida 1 por preso, mantenha. Se Máquina for exceção, adicione ao IF.
            if ($nova == 'Na Cela') {
                $stmtCheck = $pdo->prepare("SELECT id FROM internos_eletronicos WHERE id_interno = (SELECT id_interno FROM internos_eletronicos WHERE id = ?) AND tipo_item = (SELECT tipo_item FROM internos_eletronicos WHERE id = ?) AND situacao = 'Na Cela' AND id != ?");
                $stmtCheck->execute([$id, $id, $id]);
                if ($stmtCheck->rowCount() > 0) {
                    echo json_encode(['status' => 'error', 'msg' => 'Já existe um item deste tipo na cela.']);
                    exit;
                }
            }

            $sql = "UPDATE internos_eletronicos SET situacao = ? WHERE id = ?";
            if ($nova == 'Retirado') {
                $sql = "UPDATE internos_eletronicos SET situacao = ?, data_retirada = NOW() WHERE id = ?";
            }

            $pdo->prepare($sql)->execute([$nova, $id]);
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
        }
        exit;
    }

    // Gerar Relatório de Eletrônicos por Galeria
    if ($_REQUEST['acao'] === 'gerar_relatorio') {
        ob_clean();
        header('Content-Type: text/html; charset=UTF-8');

        // Consulta para totais por galeria com colunas separadas
        $sqlTotais = "
            SELECT
                i.galeria,
                COUNT(*) as total_itens,
                SUM(CASE WHEN e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as na_cela,
                SUM(CASE WHEN e.situacao = 'Estoque' THEN 1 ELSE 0 END) as estoque,
                SUM(CASE WHEN e.situacao = 'Retirado' THEN 1 ELSE 0 END) as baixados,
                SUM(CASE WHEN e.tipo_item = 'TV' THEN 1 ELSE 0 END) as tv_total,
                SUM(CASE WHEN e.tipo_item = 'Radio' THEN 1 ELSE 0 END) as radio_total,
                SUM(CASE WHEN e.tipo_item = 'Ventilador' THEN 1 ELSE 0 END) as vent_total,
                SUM(CASE WHEN e.tipo_item = 'Chaleira' THEN 1 ELSE 0 END) as chaleira_total,
                SUM(CASE WHEN e.tipo_item = 'Chuveiro' THEN 1 ELSE 0 END) as chuveiro_total,
                SUM(CASE WHEN e.tipo_item = 'Maquina Cabelo' THEN 1 ELSE 0 END) as maq_cabelo_total,
                SUM(CASE WHEN e.tipo_item = 'Bola' THEN 1 ELSE 0 END) as bola_total,
                SUM(CASE WHEN e.tipo_item = 'Banqueta' THEN 1 ELSE 0 END) as banqueta_total,
                SUM(CASE WHEN e.tipo_item = 'Extensao' THEN 1 ELSE 0 END) as extensao_total,
                SUM(CASE WHEN e.tipo_item = 'Cabo Antena' THEN 1 ELSE 0 END) as cabo_antena_total,
                SUM(CASE WHEN e.tipo_item = 'Antena Digital' THEN 1 ELSE 0 END) as antena_digital_total,
                SUM(CASE WHEN e.tipo_item = 'Violao' THEN 1 ELSE 0 END) as violao_total
            FROM internos_eletronicos e
            JOIN internos i ON e.id_interno = i.ipen
            WHERE i.status = 'A' AND e.situacao != 'Retirado'" . ($ipen_filter ? " AND e.id_interno = '$ipen_filter'" : "") . "
            GROUP BY i.galeria
            ORDER BY i.galeria";

        $totaisGaleria = $pdo->query($sqlTotais)->fetchAll(PDO::FETCH_ASSOC);

        // Consulta para dados completos da tabela agrupados por galeria e ordenados
        $sqlCompleta = "
            SELECT
                i.galeria,
                CONCAT(i.galeria, i.bloco, '-', i.res) as local,
                CAST(i.res AS UNSIGNED) as cela_num,
                i.ipen,
                i.nome,
                GROUP_CONCAT(
                    CONCAT(
                        e.tipo_item,
                        ' (',
                        e.situacao,
                        CASE WHEN e.marca_modelo != '' THEN CONCAT(' - ', e.marca_modelo) ELSE '' END,
                        ')'
                    ) SEPARATOR '; '
                ) as itens
            FROM internos i
            " . ($ipen_filter ? "JOIN" : "LEFT JOIN") . " internos_eletronicos e ON i.ipen = e.id_interno AND e.situacao != 'Retirado'
            WHERE i.status = 'A'" . ($ipen_filter ? " AND i.ipen = '$ipen_filter'" : "") . "
            GROUP BY i.ipen, i.galeria, i.bloco, i.res, i.nome
            HAVING itens IS NOT NULL AND itens != ''
            ORDER BY i.galeria, i.bloco, CAST(i.res AS UNSIGNED), i.nome";

        $dadosCompletos = $pdo->query($sqlCompleta)->fetchAll(PDO::FETCH_ASSOC);

        // Calcular totais gerais
        $totaisGerais = [
            'total_itens' => 0,
            'na_cela' => 0,
            'estoque' => 0,
            'baixados' => 0,
            'tv_total' => 0,
            'radio_total' => 0,
            'vent_total' => 0,
            'chaleira_total' => 0,
            'chuveiro_total' => 0,
            'maq_cabelo_total' => 0,
            'bola_total' => 0,
            'banqueta_total' => 0,
            'extensao_total' => 0,
            'cabo_antena_total' => 0,
            'antena_digital_total' => 0,
            'violao_total' => 0
        ];

        foreach ($totaisGaleria as $galeria) {
            foreach ($totaisGerais as $key => $value) {
                $totaisGerais[$key] += $galeria[$key];
            }
        }

        // Gerar HTML do relatório - Versão Mergida Gemini + Nossas Otimizações
        $html = "
    <!DOCTYPE html>
    <html lang='pt-br'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>SIGEP - Relatório de Eletrônicos</title>

        <!-- FontAwesome REAL (melhoria do Gemini) -->
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>

        <!-- Source Sans Pro Font -->
        <link rel='preconnect' href='https://fonts.googleapis.com'>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Source+Sans+Pro:ital,wght@0,300;0,400;0,600;0,700&display=swap' rel='stylesheet'>
                /* Reset e Base - AdminLTE3 Style */
                * { box-sizing: border-box; }
                body {
                    font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    background: #f4f6f9;
                    color: #1a1a1a;
                    line-height: 1.4;
                    font-size: 14px;
                }
                .container {
                    max-width: 1200px;
                    margin: 0 auto;
                    padding: 15px;
                    text-align: center;
                }

                /* Header - Full Width Centered */
                .header {
                    width: 100%;
                    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
                    color: white;
                    padding: 40px 20px;
                    text-align: center;
                    margin-bottom: 30px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                }
                .header h1 {
                    margin: 0 0 10px 0;
                    font-size: 2.5em;
                    font-weight: 300;
                    letter-spacing: 0.5px;
                    text-align: center;
                }
                .header h3 {
                    margin: 0 0 15px 0;
                    font-weight: 300;
                    opacity: 0.95;
                    font-size: 1.6em;
                    text-align: center;
                }
                .header p {
                    margin: 0;
                    opacity: 0.85;
                    font-size: 1.2em;
                    text-align: center;
                }

                /* Cards - AdminLTE3 Style */
                .card {
                    background: white;
                    border-radius: 0.375rem;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.125);
                    margin-bottom: 15px;
                    overflow: hidden;
                    border: 1px solid #dee2e6;
                    text-align: left;
                }
                .card-header {
                    background: #343a40;
                    color: white;
                    padding: 12px 15px;
                    font-weight: 600;
                    font-size: 1.1em;
                    border-bottom: 1px solid #dee2e6;
                }
                .card-body {
                    padding: 15px;
                }

                /* Small Boxes - AdminLTE3 Style */
                .small-box {
                    position: relative;
                    display: block;
                    margin-bottom: 15px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.125);
                    border-radius: 0.375rem;
                    transition: transform 0.2s;
                    border: 1px solid #dee2e6;
                }
                .small-box:hover {
                    transform: translateY(-1px);
                }
                .small-box .inner {
                    padding: 8px 12px;
                }
                .small-box .inner h3 {
                    font-size: 1.8rem;
                    font-weight: 700;
                    margin: 0 0 5px 0;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    line-height: 1.2;
                }
                .small-box .inner p {
                    font-size: 0.95rem;
                    margin: 0;
                    font-weight: 500;
                }
                .small-box .icon {
                    position: absolute;
                    top: 8px;
                    right: 12px;
                    font-size: 50px;
                    opacity: 0.3;
                    z-index: 1;
                }
                .bg-info {
                    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%) !important;
                    color: white !important;
                }
                .bg-success {
                    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
                    color: white !important;
                }
                .bg-warning {
                    background: linear-gradient(135deg, #ffc107 0%, #d39e00 100%) !important;
                    color: black !important;
                }
                .bg-secondary {
                    background: linear-gradient(135deg, #6c757d 0%, #545b62 100%) !important;
                    color: white !important;
                }

                /* Info Boxes - AdminLTE3 Style */
                .info-box {
                    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
                    margin-bottom: 1rem;
                    border-radius: 0.375rem;
                    background-color: #fff;
                    display: flex;
                    align-items: center;
                    padding: 0;
                    min-height: 70px;
                    border: 1px solid #dee2e6;
                }
                .info-box-icon {
                    width: 60px;
                    height: 60px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1.75rem;
                    color: #fff;
                    border-radius: 0.375rem 0 0 0.375rem;
                }
                .info-box-content {
                    padding: 8px 12px;
                    margin-left: 0;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    flex: 1;
                }
                .info-box-text {
                    text-transform: uppercase;
                    font-weight: 600;
                    font-size: 0.75rem;
                    margin-bottom: 0.25rem;
                    color: #6c757d;
                }
                .info-box-number {
                    font-size: 1.1rem;
                    font-weight: 700;
                    margin-bottom: 0;
                    color: #1a1a1a;
                }
                .bg-light {
                    background-color: #f8f9fa !important;
                }

                /* Badges - AdminLTE3 Style */
                .badge {
                    display: inline-block;
                    padding: 0.25em 0.5em;
                    font-size: 0.75em;
                    font-weight: 600;
                    line-height: 1;
                    text-align: center;
                    white-space: nowrap;
                    vertical-align: baseline;
                    border-radius: 0.375rem;
                    border: 1px solid transparent;
                }
                .badge-info {
                    background-color: #17a2b8;
                    color: white;
                }
                .badge-success {
                    background-color: #28a745;
                    color: white;
                }
                .badge-warning {
                    background-color: #ffc107;
                    color: black;
                }
                .badge-secondary {
                    background-color: #6c757d;
                    color: white;
                }

                /* Tables - Compact for Printing */
                .table-responsive {
                    margin-top: 10px;
                }
                .table {
                    width: 100%;
                    margin-bottom: 1rem;
                    color: #1a1a1a;
                    border-collapse: collapse;
                    font-size: 11px;
                }
                .table th, .table td {
                    padding: 4px 6px;
                    vertical-align: top;
                    border-top: 1px solid #dee2e6;
                    line-height: 1.2;
                }
                .table thead th {
                    vertical-align: bottom;
                    border-bottom: 2px solid #dee2e6;
                    background-color: #343a40;
                    color: white;
                    font-weight: 600;
                    font-size: 10px;
                    padding: 6px 4px;
                    position: sticky;
                    top: 0;
                    z-index: 10;
                }
                .table-bordered {
                    border: 1px solid #dee2e6;
                }
                .table-bordered th, .table-bordered td {
                    border: 1px solid #dee2e6;
                }
                .table-striped tbody tr:nth-of-type(odd) {
                    background-color: rgba(0,0,0,0.025);
                }
                .table-primary {
                    background-color: #cce5ff;
                }
                .font-weight-bold {
                    font-weight: 700 !important;
                }

                /* Grid Layout */
                .row {
                    display: flex;
                    flex-wrap: wrap;
                    margin-right: -10px;
                    margin-left: -10px;
                }
                .col-12, .col-lg-2, .col-lg-3, .col-md-6 {
                    position: relative;
                    width: 100%;
                    padding-right: 10px;
                    padding-left: 10px;
                }
                .col-lg-2 {
                    flex: 0 0 16.666667%;
                    max-width: 16.666667%;
                }
                .col-lg-3 {
                    flex: 0 0 25%;
                    max-width: 25%;
                }
                .col-md-6 {
                    flex: 0 0 50%;
                    max-width: 50%;
                }

                /* Utilities */
                .text-center {
                    text-align: center !important;
                }
                .text-primary {
                    color: #007bff !important;
                }
                .text-info {
                    color: #17a2b8 !important;
                }
                .text-success {
                    color: #28a745 !important;
                }
                .text-warning {
                    color: #ffc107 !important;
                }
                .text-danger {
                    color: #dc3545 !important;
                }
                .text-secondary {
                    color: #6c757d !important;
                }
                .text-muted {
                    color: #6c757d !important;
                }
                .font-weight-300 {
                    font-weight: 300 !important;
                }
                .mb-3 {
                    margin-bottom: 0.5rem !important;
                }
                .mb-4 {
                    margin-bottom: 0.75rem !important;
                }
                .mt-3 {
                    margin-top: 0.5rem !important;
                }
                .mt-4 {
                    margin-top: 0.75rem !important;
                }

                /* Simple Icons (Unicode symbols that render as icons) */
                .fas {
                    font-weight: bold;
                    display: inline-block;
                    font-style: normal;
                    font-variant: normal;
                    text-rendering: auto;
                    font-size: 1.1em;
                    line-height: 1;
                    font-family: sans-serif;
                }
                .fa-futbol:before { content: '⚽'; }
                .fa-tv:before { content: '📺'; }
                .fa-broadcast-tower:before { content: '📻'; }
                .fa-fan:before { content: '💨'; }
                .fa-mug-hot:before { content: '☕'; }
                .fa-shower:before { content: '🚿'; }
                .fa-chair:before { content: '🪑'; }
                .fa-plug:before { content: '🔌'; }
                .fa-antenna:before { content: '📡'; }
                .fa-satellite:before { content: '🛰️'; }
                .fa-guitar:before { content: '🎸'; }

                /* PRINT OPTIMIZATION - A4 Black Toner with Grayscale Differentiation */
                @media print {
                    body {
                        background: white !important;
                        color: black !important;
                        font-size: 12px !important;
                        line-height: 1.3 !important;
                        margin: 0 !important;
                        padding: 0 !important;
                    }

                    .container {
                        max-width: none !important;
                        margin: 0 !important;
                        padding: 8px !important;
                        width: 100% !important;
                    }

                    .card {
                        box-shadow: none !important;
                        border: 2px solid #000 !important;
                        margin-bottom: 6px !important;
                        page-break-inside: avoid;
                        background: white !important;
                    }

                    .card-header {
                        background: #f0f0f0 !important;
                        color: black !important;
                        padding: 6px 8px !important;
                        font-size: 12px !important;
                        font-weight: bold !important;
                        border-bottom: 2px solid #000 !important;
                    }

                    .card-body {
                        padding: 8px !important;
                    }

                    .header {
                        background: white !important;
                        color: black !important;
                        padding: 15px 0 !important;
                        margin-bottom: 10px !important;
                        border-bottom: 3px solid #000 !important;
                        page-break-after: avoid;
                    }

                    .header h1 {
                        font-size: 18px !important;
                        margin: 0 !important;
                        font-weight: bold !important;
                        text-align: center !important;
                    }

                    .header h3 {
                        font-size: 14px !important;
                        margin: 5px 0 0 0 !important;
                        font-weight: bold !important;
                        text-align: center !important;
                    }

                    .header p {
                        font-size: 11px !important;
                        margin: 5px 0 0 0 !important;
                        text-align: center !important;
                    }

                    .small-box .icon {
                        display: none !important;
                    }

                    .small-box .inner {
                        padding: 6px !important;
                    }

                    .small-box .inner h3 {
                        font-size: 16px !important;
                        margin: 0 0 3px 0 !important;
                        font-weight: bold !important;
                    }

                    .small-box .inner p {
                        font-size: 10px !important;
                        font-weight: bold !important;
                    }

                    /* Grayscale differentiation for backgrounds */
                    .bg-info {
                        background: white !important;
                        color: black !important;
                        border: 3px solid #666 !important;
                        border-left: 8px solid #666 !important;
                    }

                    .bg-success {
                        background: white !important;
                        color: black !important;
                        border: 3px solid #333 !important;
                        border-left: 8px solid #333 !important;
                        font-weight: bold !important;
                    }

                    .bg-warning {
                        background: #f9f9f9 !important;
                        color: black !important;
                        border: 3px solid #999 !important;
                        border-left: 8px solid #999 !important;
                    }

                    .bg-secondary {
                        background: #e0e0e0 !important;
                        color: black !important;
                        border: 3px solid #777 !important;
                        border-left: 8px solid #777 !important;
                    }

                    .info-box {
                        border: 2px solid #000 !important;
                        margin-bottom: 6px !important;
                        page-break-inside: avoid;
                        background: white !important;
                    }

                    .info-box-icon {
                        background: #e0e0e0 !important;
                        border-right: 2px solid #000 !important;
                        color: black !important;
                        font-weight: bold !important;
                    }

                    .info-box-content {
                        padding: 6px !important;
                    }

                    .info-box-text {
                        font-size: 9px !important;
                        margin-bottom: 2px !important;
                        font-weight: bold !important;
                        text-transform: uppercase !important;
                    }

                    .info-box-number {
                        font-size: 12px !important;
                        font-weight: bold !important;
                    }

                    .bg-light {
                        background: #f5f5f5 !important;
                        border: 1px solid #ccc !important;
                    }

                    .table {
                        font-size: 9px !important;
                        margin-bottom: 8px !important;
                        border: 2px solid #000 !important;
                    }

                    .table th, .table td {
                        padding: 3px 4px !important;
                        border: 1px solid #000 !important;
                    }

                    .table thead th {
                        font-size: 8px !important;
                        padding: 4px 3px !important;
                        font-weight: bold !important;
                        background: #e0e0e0 !important;
                        color: black !important;
                        border: 2px solid #000 !important;
                    }

                    .table-primary {
                        background: #f0f0f0 !important;
                        font-weight: bold !important;
                    }

                    .badge {
                        border: 1px solid #000 !important;
                        font-size: 8px !important;
                        padding: 2px 4px !important;
                        font-weight: bold !important;
                    }

                    /* Enhanced grayscale differentiation */
                    .badge-info {
                        background: #e0e0e0 !important;
                        color: black !important;
                        border: 2px solid #666 !important;
                    }

                    .badge-success {
                        background: #d0d0d0 !important;
                        color: black !important;
                        border: 2px solid #333 !important;
                        font-weight: bold !important;
                    }

                    .badge-warning {
                        background: #f0f0f0 !important;
                        color: black !important;
                        border: 2px solid #999 !important;
                    }

                    .badge-secondary {
                        background: #c0c0c0 !important;
                        color: black !important;
                        border: 2px solid #777 !important;
                    }

                    .row {
                        margin-right: -5px !important;
                        margin-left: -5px !important;
                    }

                    .col-12, .col-lg-2, .col-lg-3, .col-md-6 {
                        padding-right: 5px !important;
                        padding-left: 5px !important;
                    }

                    /* Maximize content per page */
                    .card-body {
                        padding: 6px !important;
                    }

                    .table-responsive {
                        margin-top: 5px !important;
                    }

                    /* Avoid page breaks in important content */
                    .header, .small-box, .info-box {
                        page-break-inside: avoid;
                    }

                    /* Force more content per page */
                    .card {
                        margin-bottom: 4px !important;
                    }

                    .no-print {
                        display: none !important;
                    }

                    /* Enhanced table printing */
                    .table {
                        page-break-inside: auto;
                    }

                    .table tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }

                    .table thead {
                        display: table-header-group;
                    }

                    .table tbody {
                        display: table-row-group;
                    }

                    /* Typography enhancements for grayscale */
                    .font-weight-bold {
                        font-weight: bold !important;
                    }

                    .text-primary {
                        font-weight: bold !important;
                        text-decoration: underline !important;
                    }

                    .text-info, .text-success, .text-warning, .text-secondary {
                        font-weight: bold !important;
                    }
                }

                /* Responsive */
                @media (max-width: 768px) {
                    .col-lg-2, .col-lg-3 {
                        flex: 0 0 50%;
                        max-width: 50%;
                    }
                    .header h1 {
                        font-size: 1.8em;
                    }
                    .small-box .inner h3 {
                        font-size: 1.5rem;
                    }
                }
            </style>
        </head>
        <body class='hold-transition sidebar-mini'>
            <div class='wrapper'>
                <!-- HEADER FULL WIDTH -->
                <div class='header'>
                    <h1><i class='fas fa-plug'></i>
                        SISTEMA PRISIONAL INTEGRADO - SIGEP
                    </h1>
                    <h3>Relatório Completo de Eletrônicos</h3>
                    <p><i class='fas fa-calendar-alt'></i>
                        Gerado em: " . date('d/m/Y H:i:s') . "
                    </p>
                </div>

                <div class='content-wrapper'>
                    <section class='content'>
                        <div class='container-fluid'>

                            <!-- RESUMO GERAL COM CARDS BONITOS -->
                            <div class='summary-cards'>
                                <h4 class='mb-4'><i class='fas fa-chart-bar text-info'></i> Resumo Geral</h4>
                                <div class='row'>

                                    <!-- Estatísticas Principais -->
                                    <div class='col-lg-3 col-6'>
                                        <div class='small-box bg-info summary-card'>
                                            <div class='inner'>
                                                <h3>{$totaisGerais['total_itens']}</h3>
                                                <p>Total de Itens Ativos</p>
                                            </div>
                                            <div class='icon'>
                                                <i class='fas fa-plug'></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class='col-lg-3 col-6'>
                                        <div class='small-box bg-success summary-card'>
                                            <div class='inner'>
                                                <h3>{$totaisGerais['na_cela']}</h3>
                                                <p>Em Celas</p>
                                            </div>
                                            <div class='icon'>
                                                <i class='fas fa-check-circle'></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class='col-lg-3 col-6'>
                                        <div class='small-box bg-warning summary-card'>
                                            <div class='inner'>
                                                <h3>{$totaisGerais['estoque']}</h3>
                                                <p>No Estoque</p>
                                            </div>
                                            <div class='icon'>
                                                <i class='fas fa-boxes'></i>
                                            </div>
                                        </div>
                                    </div>

                                    <div class='col-lg-3 col-6'>
                                        <div class='small-box bg-secondary summary-card'>
                                            <div class='inner'>
                                                <h3>{$totaisGerais['baixados']}</h3>
                                                <p>Baixados</p>
                                            </div>
                                            <div class='icon'>
                                                <i class='fas fa-archive'></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detalhamento por Tipo de Item -->
                                <div class='row mt-3'>
                                    <div class='col-12'>
                                        <div class='card'>
                                            <div class='card-header'>
                                                <h5 class='card-title mb-0'><i class='fas fa-list-ul'></i> Detalhamento por Tipo de Item</h5>
                                            </div>
                                            <div class='card-body'>
                                                <div class='row'>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-tv text-primary'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Televisores</span>
                                                                <span class='info-box-number'>{$totaisGerais['tv_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-broadcast-tower text-success'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Rádios</span>
                                                                <span class='info-box-number'>{$totaisGerais['radio_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-fan text-info'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Ventiladores</span>
                                                                <span class='info-box-number'>{$totaisGerais['vent_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-mug-hot text-warning'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Chaleiras</span>
                                                                <span class='info-box-number'>{$totaisGerais['chaleira_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-shower text-info'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Chuveiros</span>
                                                                <span class='info-box-number'>{$totaisGerais['chuveiro_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-futbol text-warning'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Bola</span>
                                                                <span class='info-box-number'>{$totaisGerais['bola_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-chair text-secondary'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Banqueta</span>
                                                                <span class='info-box-number'>{$totaisGerais['banqueta_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-plug text-info'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Extensão</span>
                                                                <span class='info-box-number'>{$totaisGerais['extensao_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-antenna text-success'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Cabo Antena</span>
                                                                <span class='info-box-number'>{$totaisGerais['cabo_antena_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-satellite text-primary'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Antena Digital</span>
                                                                <span class='info-box-number'>{$totaisGerais['antena_digital_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class='col-lg-2 col-6 mb-3'>
                                                        <div class='info-box bg-light'>
                                                            <span class='info-box-icon'><i class='fas fa-guitar text-danger'></i></span>
                                                            <div class='info-box-content'>
                                                                <span class='info-box-text'>Violão</span>
                                                                <span class='info-box-number'>{$totaisGerais['violao_total']}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TOTAIS POR GALERIA -->
                            <div class='card'>
                                <div class='card-header'>
                                    <h4 class='card-title mb-0'><i class='fas fa-building text-primary'></i> Totais por Galeria</h4>
                                </div>
                                <div class='card-body table-responsive'>
                                    <table class='table table-bordered table-striped'>
                                        <thead class='thead-dark'>
                                            <tr>
                                                <th>Galeria</th>
                                                <th>Total Itens</th>
                                                <th>Em Celas</th>
                                                <th>Estoque</th>
                                                <th>Baixados</th>
                                                <th>TV</th>
                                                <th>Rádio</th>
                                                <th>Ventilador</th>
                                                <th>Chaleira</th>
                                                <th>Chuveiro</th>
                                                <th>Máq. Cabelo</th>
                                                <th>Bola</th>
                                                <th>Banqueta</th>
                                                <th>Extensão</th>
                                                <th>Cabo Antena</th>
                                                <th>Antena Digital</th>
                                                <th>Violão</th>
                                                <th>Chuveiro</th>
                                            </tr>
                                        </thead>
                                        <tbody>";

        foreach ($totaisGaleria as $galeria) {
            $html .= "
                                            <tr>
                                                <td><strong class='text-primary'>{$galeria['galeria']}</strong></td>
                                                <td><span class='badge badge-info'>{$galeria['total_itens']}</span></td>
                                                <td><span class='badge badge-success'>{$galeria['na_cela']}</span></td>
                                                <td><span class='badge badge-warning'>{$galeria['estoque']}</span></td>
                                                <td><span class='badge badge-secondary'>{$galeria['baixados']}</span></td>
                                                <td>{$galeria['tv_total']}</td>
                                                <td>{$galeria['radio_total']}</td>
                                                <td>{$galeria['vent_total']}</td>
                                                <td>{$galeria['chaleira_total']}</td>
                                                <td>{$galeria['chuveiro_total']}</td>
                                                <td>{$galeria['maq_cabelo_total']}</td>
                                                <td>{$galeria['bola_total']}</td>
                                                <td>{$galeria['banqueta_total']}</td>
                                                <td>{$galeria['extensao_total']}</td>
                                                <td>{$galeria['cabo_antena_total']}</td>
                                                <td>{$galeria['antena_digital_total']}</td>
                                                <td>{$galeria['violao_total']}</td>
                                            </tr>";
        }

        $outrosGerais = $totaisGerais['bola_total'] + $totaisGerais['banqueta_total'] + $totaisGerais['extensao_total'] +
            $totaisGerais['cabo_antena_total'] + $totaisGerais['antena_digital_total'] + $totaisGerais['violao_total'];

        $html .= "
                                            <tr class='table-primary font-weight-bold'>
                                                <td><strong>TOTAL GERAL</strong></td>
                                                <td><span class='badge badge-info'>{$totaisGerais['total_itens']}</span></td>
                                                <td><span class='badge badge-success'>{$totaisGerais['na_cela']}</span></td>
                                                <td><span class='badge badge-warning'>{$totaisGerais['estoque']}</span></td>
                                                <td><span class='badge badge-secondary'>{$totaisGerais['baixados']}</span></td>
                                                <td><strong>{$totaisGerais['tv_total']}</strong></td>
                                                <td><strong>{$totaisGerais['radio_total']}</strong></td>
                                                <td><strong>{$totaisGerais['vent_total']}</strong></td>
                                                <td><strong>{$totaisGerais['chaleira_total']}</strong></td>
                                                <td><strong>{$totaisGerais['chuveiro_total']}</strong></td>
                                                <td><strong>{$totaisGerais['maq_cabelo_total']}</strong></td>
                                                <td><strong>{$totaisGerais['bola_total']}</strong></td>
                                                <td><strong>{$totaisGerais['banqueta_total']}</strong></td>
                                                <td><strong>{$totaisGerais['extensao_total']}</strong></td>
                                                <td><strong>{$totaisGerais['cabo_antena_total']}</strong></td>
                                                <td><strong>{$totaisGerais['antena_digital_total']}</strong></td>
                                                <td><strong>{$totaisGerais['violao_total']}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- RELAÇÃO COMPLETA AGRUPADA POR GALERIA -->
                            <div class='card'>
                                <div class='card-header'>
                                    <h4 class='card-title mb-0'><i class='fas fa-users text-success'></i> Relação Completa de Itens por Interno</h4>
                                    <div class='card-tools'>
                                        <small class='text-muted'>Ordenado por galeria, cela (numérica) e nome (alfabética)</small>
                                    </div>
                                </div>
                                <div class='card-body table-responsive'>
                                    <table class='table table-bordered table-striped table-sm'>
                                        <thead class='thead-dark'>
                                            <tr>
                                                <th>Local</th>
                                                <th>IPEN</th>
                                                <th>Nome do Interno</th>
                                                <th>Itens Eletrônicos</th>
                                            </tr>
                                        </thead>
                                        <tbody>";

        $galeriaAtual = '';
        foreach ($dadosCompletos as $dado) {
            if ($galeriaAtual != $dado['galeria']) {
                $galeriaAtual = $dado['galeria'];
                $html .= "
                                            <tr class='table-secondary'>
                                                <td colspan='4' class='font-weight-bold text-center bg-primary text-white'>
                                                    <i class='fas fa-building'></i> GALERIA {$galeriaAtual}
                                                </td>
                                            </tr>";
            }

            $html .= "
                                            <tr>
                                                <td><strong class='text-info'>{$dado['local']}</strong></td>
                                                <td>{$dado['ipen']}</td>
                                                <td>{$dado['nome']}</td>
                                                <td style='font-size: 11px;'>{$dado['itens']}</td>
                                            </tr>";
        }

        $html .= "
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- RODAPÉ -->
                            <div class='card'>
                                <div class='card-body'>
                                    <div class='row'>
                                        <div class='col-md-6'>
                                            <h5><i class='fas fa-info-circle text-info'></i> Observações</h5>
                                            <ul class='list-unstyled'>
                                                <li><i class='fas fa-check text-success'></i> Este relatório contém apenas itens ativos (não baixados)</li>
                                                <li><i class='fas fa-check text-success'></i> Os totais por galeria incluem itens em cela e estoque</li>
                                                <li><i class='fas fa-check text-success'></i> Relatório gerado automaticamente pelo sistema SIGEP</li>
                                                <li><i class='fas fa-check text-success'></i> Agrupamento por galeria com ordenação numérica das celas</li>
                                                <li><i class='fas fa-check text-success'></i> Ordenação alfabética dos internos dentro de cada cela</li>
                                            </ul>
                                        </div>
                                        <div class='col-md-6 text-center'>
                                            <div class='mt-4'>
                                                <p class='mb-1'><strong>Data de geração:</strong></p>
                                                <h5 class='text-primary'>" . date('d/m/Y H:i:s') . "</h5>
                                                <p class='text-muted mt-3'>
                                                    <i class='fas fa-cogs'></i>
                                                    Sistema Prisional Integrado - SIGEP
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section>
                </div>
            </div>

        </body>
        </html>";

        echo $html;
        exit;
    }
}

// --- DADOS PARA O DASHBOARD ---

// Contadores dos Cards (Garante que retornam 0 se null)
$stats = $pdo->query("
    SELECT
        COUNT(*) as total,
        IFNULL(SUM(CASE WHEN situacao = 'Na Cela' THEN 1 ELSE 0 END), 0) as na_cela,
        IFNULL(SUM(CASE WHEN situacao = 'Estoque' THEN 1 ELSE 0 END), 0) as estoque,
        IFNULL(SUM(CASE WHEN situacao = 'Retirado' THEN 1 ELSE 0 END), 0) as baixados
    FROM internos_eletronicos
")->fetch(PDO::FETCH_ASSOC);

// Filtros da Tabela
$where = ["i.status = 'A'"];
$params = [];

if (!empty($_GET['busca'])) {
    $where[] = "(i.nome LIKE ? OR i.ipen LIKE ?)";
    $p = "%{$_GET['busca']}%";
    array_push($params, $p, $p);
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
if (!empty($_GET['item'])) {
    $where[] = "EXISTS (SELECT 1 FROM internos_eletronicos e2 WHERE e2.id_interno = i.ipen AND e2.tipo_item = ? AND e2.situacao != 'Retirado')";
    $params[] = $_GET['item'];
}

$sqlTable = "SELECT i.ipen, i.nome, i.galeria, i.bloco, i.res,
        SUM(CASE WHEN e.tipo_item = 'TV' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as tv_cela,
        SUM(CASE WHEN e.tipo_item = 'TV' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as tv_estoque,
        SUM(CASE WHEN e.tipo_item = 'Radio' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as radio_cela,
        SUM(CASE WHEN e.tipo_item = 'Radio' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as radio_estoque,
        SUM(CASE WHEN e.tipo_item = 'Ventilador' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as vent_cela,
        SUM(CASE WHEN e.tipo_item = 'Ventilador' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as vent_estoque,
        SUM(CASE WHEN e.tipo_item = 'Maquina Cabelo' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as maq_cela,
        SUM(CASE WHEN e.tipo_item = 'Maquina Cabelo' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as maq_estoque,
        SUM(CASE WHEN e.tipo_item = 'Chaleira' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as chaleira_cela,
        SUM(CASE WHEN e.tipo_item = 'Chaleira' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as chaleira_estoque,
        SUM(CASE WHEN e.tipo_item = 'Bola' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as bola_cela,
        SUM(CASE WHEN e.tipo_item = 'Bola' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as bola_estoque,
        SUM(CASE WHEN e.tipo_item = 'Banqueta' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as banqueta_cela,
        SUM(CASE WHEN e.tipo_item = 'Banqueta' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as banqueta_estoque,
        SUM(CASE WHEN e.tipo_item = 'Extensao' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as extensao_cela,
        SUM(CASE WHEN e.tipo_item = 'Extensao' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as extensao_estoque,
        SUM(CASE WHEN e.tipo_item = 'Cabo Antena' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as cabo_antena_cela,
        SUM(CASE WHEN e.tipo_item = 'Cabo Antena' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as cabo_antena_estoque,
        SUM(CASE WHEN e.tipo_item = 'Antena Digital' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as antena_digital_cela,
        SUM(CASE WHEN e.tipo_item = 'Antena Digital' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as antena_digital_estoque,
        SUM(CASE WHEN e.tipo_item = 'Violao' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as violao_cela,
        SUM(CASE WHEN e.tipo_item = 'Violao' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as violao_estoque,
        SUM(CASE WHEN e.tipo_item = 'Chuveiro' AND e.situacao = 'Na Cela' THEN 1 ELSE 0 END) as chuveiro_cela,
        SUM(CASE WHEN e.tipo_item = 'Chuveiro' AND e.situacao = 'Estoque' THEN 1 ELSE 0 END) as chuveiro_estoque
        FROM internos i
        LEFT JOIN internos_eletronicos e ON i.ipen = e.id_interno AND e.situacao != 'Retirado'
        WHERE " . implode(" AND ", $where) . "
        GROUP BY i.ipen
        ORDER BY i.galeria, i.bloco, i.res
        LIMIT 50";

$stmtTable = $pdo->prepare($sqlTable);
$stmtTable->execute($params);
$lista = $stmtTable->fetchAll(PDO::FETCH_ASSOC);

// Helpers
$galerias = $pdo->query("SELECT DISTINCT galeria FROM internos WHERE status='A' ORDER BY galeria")->fetchAll(PDO::FETCH_COLUMN);
$blocos = $pdo->query("SELECT DISTINCT bloco FROM internos WHERE status='A' ORDER BY bloco")->fetchAll(PDO::FETCH_COLUMN);
?>

<script>
    window.pageTitle = 'Gestão de Eletrônicos';
    window.currentPage = 'internos_eletronicos_gestao.php';
    window.safeReload = function() {
        loadPage('paginas/internos_eletronicos_gestao.php?' + $('#formFiltro').serialize());
    }

    // Permissão para movimentar chuveiros (Censura, Manutenção ou Admin)
    window.podeMoverChuveiros = <?= $pode_mover_chuveiros ? 'true' : 'false' ?>;
</script>

<link rel="stylesheet" href="assets/css/internos_doacao_eletronicos.css">

<style>
    .icon-status {
        font-size: 1.1rem;
        margin-right: 5px;
        opacity: 0.2;
    }

    .icon-status.active {
        opacity: 1;
    }

    .text-cela {
        color: #28a745;
        text-shadow: 0 0 2px rgba(40, 167, 69, 0.3);
    }

    .text-estoque {
        color: #ffc107;
        text-shadow: 0 0 2px rgba(255, 193, 7, 0.3);
    }

    .card-stat {
        cursor: pointer;
        transition: transform 0.2s;
        min-height: 100px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .card-stat:hover {
        transform: translateY(-3px);
    }

    .small-box .icon {
        top: 10px;
    }

    /* Offcanvas */
    .offcanvas-right {
        position: fixed;
        top: 0;
        right: 0;
        width: 600px;
        height: 100%;
        background: #fff;
        box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3);
        transform: translateX(100%);
        transition: 0.3s;
        z-index: 1060;
        display: flex;
        flex-direction: column;
    }

    .offcanvas-header {
        background: #fd7e14;
        color: white;
        padding: 15px;
        display: flex;
        justify-content: space-between;
    }

    .offcanvas-body {
        padding: 15px;
        overflow-y: auto;
        flex: 1;
    }

    body.dark-mode .offcanvas-right {
        background: #343a40;
        color: #fff;
    }
</style>

<section class="content pt-3">
    <div class="container-fluid">

        <!-- CARDS DE ESTATÍSTICA (CORRIGIDOS) -->
        <div class="row mb-3">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info card-stat" onclick="abrirOffcanvasStats('Total')">
                    <div class="inner">
                        <h3><?= ($stats['total'] - $stats['baixados']) ?></h3>
                        <p>Total Ativos</p>
                    </div>
                    <div class="icon"><i class="fas fa-plug"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success card-stat" onclick="abrirOffcanvasStats('Na Cela')">
                    <div class="inner">
                        <h3><?= $stats['na_cela'] ?></h3>
                        <p>Em Celas</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning card-stat" onclick="abrirOffcanvasStats('Estoque')">
                    <div class="inner">
                        <h3><?= $stats['estoque'] ?></h3>
                        <p>No Estoque</p>
                    </div>
                    <div class="icon"><i class="fas fa-boxes"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-secondary card-stat" onclick="abrirOffcanvasStats('Retirado')">
                    <div class="inner">
                        <h3><?= $stats['baixados'] ?></h3>
                        <p>Baixados/Entregues</p>
                    </div>
                    <div class="icon"><i class="fas fa-sign-out-alt"></i></div>
                </div>
            </div>
        </div>

        <!-- FILTROS (CORRIGIDO WARNING) -->
        <div class="card card-outline card-purple shadow-sm mb-3">
            <div class="card-body p-2">
                <form class="form-inline justify-content-end" id="formFiltro" onsubmit="return false;">
                    <input type="text" class="form-control form-control-sm mr-2" name="busca" placeholder="Nome, IPEN..." value="<?= $_GET['busca'] ?? '' ?>">
                    <select class="form-control form-control-sm mr-2" name="galeria">
                        <option value="">Galeria</option>
                        <?php foreach ($galerias as $g) echo "<option value='$g' " . (($_GET['galeria'] ?? '') == $g ? 'selected' : '') . ">$g</option>"; ?>
                    </select>
                    <select class="form-control form-control-sm mr-2" name="bloco">
                        <option value="">Bloco</option>
                        <?php foreach ($blocos as $b) echo "<option value='$b' " . (($_GET['bloco'] ?? '') == $b ? 'selected' : '') . ">$b</option>"; ?>
                    </select>
                    <input type="text" class="form-control form-control-sm mr-2" name="cela" placeholder="Cela" size="5" value="<?= $_GET['cela'] ?? '' ?>">

                    <!-- CORREÇÃO DO SELECT DE ITEM -->
                    <select class="form-control form-control-sm mr-2" name="item">
                        <option value="">Todos Itens</option>
                        <option value="TV" <?= (($_GET['item'] ?? '') == 'TV' ? 'selected' : '') ?>>TV</option>
                        <option value="Radio" <?= (($_GET['item'] ?? '') == 'Radio' ? 'selected' : '') ?>>Rádio</option>
                        <option value="Ventilador" <?= (($_GET['item'] ?? '') == 'Ventilador' ? 'selected' : '') ?>>Ventilador</option>
                        <option value="Maquina Cabelo" <?= (($_GET['item'] ?? '') == 'Maquina Cabelo' ? 'selected' : '') ?>>Máquina Cabelo</option>
                        <option value="Chaleira" <?= (($_GET['item'] ?? '') == 'Chaleira' ? 'selected' : '') ?>>Chaleira</option>
                        <option value="Bola" <?= (($_GET['item'] ?? '') == 'Bola' ? 'selected' : '') ?>>Bola</option>
                        <option value="Banqueta" <?= (($_GET['item'] ?? '') == 'Banqueta' ? 'selected' : '') ?>>Banqueta</option>
                        <option value="Extensao" <?= (($_GET['item'] ?? '') == 'Extensao' ? 'selected' : '') ?>>Extensão</option>
                        <option value="Cabo Antena" <?= (($_GET['item'] ?? '') == 'Cabo Antena' ? 'selected' : '') ?>>Cabo Antena</option>
                        <option value="Antena Digital" <?= (($_GET['item'] ?? '') == 'Antena Digital' ? 'selected' : '') ?>>Antena Digital</option>
                        <option value="Violao" <?= (($_GET['item'] ?? '') == 'Violao' ? 'selected' : '') ?>>Violão</option>
                        <option value="Chuveiro" <?= (($_GET['item'] ?? '') == 'Chuveiro' ? 'selected' : '') ?>>Chuveiro</option>
                    </select>

                    <button class="btn btn-sm btn-purple" onclick="window.safeReload()"><i class="fas fa-filter"></i> Filtrar</button>
                    <button class="btn btn-sm btn-success ml-2" onclick="gerarRelatorio()" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-file-pdf"></i> Gerar Relatório</button>
                    <button class="btn btn-sm btn-info ml-2" onclick="abrirRelatorioItensCela()" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-clipboard-list"></i> Relatório Itens Cela</button>
                    <button class="btn btn-sm btn-warning ml-2" onclick="abrirRelatorioItensCadastrados()" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-list"></i> Itens Cadastrados</button>
                </form>
            </div>
        </div>

        <!-- TABELA (CORRIGIDA MÁQUINA CABELO) -->
        <div class="card shadow">
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-sm text-center align-middle">
                    <thead class="thead-dark">
                        <tr>
                            <th>IPEN</th>
                            <th class="text-left">Nome</th>
                            <th>Local</th>
                            <th>TV</th>
                            <th>Rádio</th>
                            <th>Ventilador</th>
                            <th>Máq. Cabelo</th>
                            <th>Chaleira</th>
                            <th>Bola</th>
                            <th>Banqueta</th>
                            <th>Extensão</th>
                            <th>Cabo Antena</th>
                            <th>Antena Digital</th>
                            <th>Violão</th>
                            <th>Chuveiro</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista as $i): ?>
                            <tr>
                                <td><?= $i['ipen'] ?></td>
                                <td class="text-left font-weight-bold"><?= $i['nome'] ?></td>
                                <td><?= "{$i['galeria']}-{$i['bloco']}-{$i['res']}" ?></td>
                                <td>
                                    <i class="fas fa-tv icon-status text-cela <?= $i['tv_cela'] ? 'active' : '' ?>" title="Na Cela"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['tv_estoque'] ? 'active' : '' ?>" title="Estoque"></i>
                                </td>
                                <td>
                                    <i class="fas fa-broadcast-tower icon-status text-cela <?= $i['radio_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['radio_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-fan icon-status text-cela <?= $i['vent_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['vent_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-cut icon-status text-cela <?= $i['maq_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['maq_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-mug-hot icon-status text-cela <?= $i['chaleira_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['chaleira_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-circle icon-status text-cela <?= $i['bola_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['bola_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-chair icon-status text-cela <?= $i['banqueta_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['banqueta_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-plug icon-status text-cela <?= $i['extensao_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['extensao_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-broadcast-tower icon-status text-cela <?= $i['cabo_antena_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['cabo_antena_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-satellite-dish icon-status text-cela <?= $i['antena_digital_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['antena_digital_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-guitar icon-status text-cela <?= $i['violao_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['violao_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <i class="fas fa-shower icon-status text-cela <?= $i['chuveiro_cela'] ? 'active' : '' ?>"></i>
                                    <i class="fas fa-box icon-status text-estoque <?= $i['chuveiro_estoque'] ? 'active' : '' ?>"></i>
                                </td>
                                <td>
                                    <button class="btn btn-xs btn-outline-info mr-1" onclick="abrirGestao(<?= $i['ipen'] ?>, '<?= addslashes($i['nome']) ?>')" title="Gerenciar Itens"><i class="fas fa-cog"></i></button>
                                    <button class="btn btn-xs btn-outline-warning mr-1" onclick="abrirDossie(<?= $i['ipen'] ?>, '<?= addslashes($i['nome']) ?>')" title="Ver Dossiê"><i class="fas fa-file-alt"></i></button>
                                    <button class="btn btn-xs btn-outline-success" onclick="abrirDoacaoFromGestao(<?= $i['ipen'] ?>, '<?= addslashes($i['nome']) ?>')" title="Iniciar Doação"><i class="fas fa-hand-holding-heart"></i></button>
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
<div id="offStats" class="offcanvas-right">
    <div class="offcanvas-header bg-dark">
        <h5 class="m-0" id="titleStats">Detalhes</h5>
        <button class="btn btn-sm btn-light" onclick="fecharOff('offStats')">&times;</button>
    </div>
    <div class="offcanvas-body" id="bodyStats"></div>
</div>

<div id="offGestao" class="offcanvas-right">
    <div class="offcanvas-header bg-purple">
        <h5 class="m-0"><i class="fas fa-user-cog"></i> Gestão de Pertences</h5>
        <button class="btn btn-sm btn-light" onclick="fecharOff('offGestao')">&times;</button>
    </div>
    <div class="offcanvas-body">
        <h5 id="nomeGestao" class="font-weight-bold mb-3 border-bottom pb-2"></h5>
        <div id="listaItens"></div>
    </div>
</div>

<!-- OFFCANVAS DE DOAÇÃO -->
<div id="offcanvasDoacao" class="offcanvas-doacao">
    <div class="offcanvas-header-doacao">
        <h5><i class="fas fa-hand-holding-heart"></i> Doação de Eletrônicos</h5>
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
                    <button type="button" class="btn btn-primary" onclick="proximoPasso(2)">Próximo <i class="fas fa-arrow-right"></i></button>
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
                    <button type="button" class="btn btn-primary mt-3" onclick="validarReceptorCela()">Confirmar <i class="fas fa-check"></i></button>
                </div>
                <div id="formReceptorInterno" class="mt-3" style="display: none;">
                    <h6>Buscar Interno Receptor:</h6>
                    <input type="text" class="form-control" id="buscaReceptorInterno" placeholder="Digite IPEN, nome ou nome social..." onkeyup="buscarInternoReceptor()">
                    <div id="sugestoesReceptor" class="mt-2" style="max-height: 150px; overflow-y: auto;"></div>
                </div>
                <button type="button" class="btn btn-secondary mt-3" onclick="voltarPasso(1)"><i class="fas fa-arrow-left"></i> Voltar</button>
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
                    <button type="button" class="btn btn-primary" onclick="proximoPasso(4)">Confirmar Itens <i class="fas fa-arrow-right"></i></button>
                </div>
                <button type="button" class="btn btn-secondary mt-3" onclick="voltarPasso(2)"><i class="fas fa-arrow-left"></i> Voltar</button>
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
                        <input type="checkbox" class="form-check-input" id="confirmacaoIrrevogavel" onchange="verificarConfirmacoes()">
                        <label class="form-check-label" for="confirmacaoIrrevogavel">
                            <strong>Confirmo que entendo que esta doação é irrevogável e intransferível.</strong>
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="confirmacaoMonitor" onchange="verificarConfirmacoes()">
                        <label class="form-check-label" for="confirmacaoMonitor">
                            <strong>Sou funcionário da Censura e autorizo esta doação.</strong>
                        </label>
                    </div>
                    <div class="mt-4">
                        <button type="button" class="btn btn-success btn-lg btn-block" id="btnFinalizarDoacao" disabled onclick="finalizarDoacao()">
                            <i class="fas fa-hand-holding-heart"></i> Finalizar Doação
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-secondary mt-3" onclick="voltarPasso(3)"><i class="fas fa-arrow-left"></i> Voltar</button>
            </div>
        </div>
    </div>
</div>

<script>
    function fecharOff(id) {
        document.getElementById(id).style.transform = 'translateX(100%)';
    }

    // 1. GESTÃO INDIVIDUAL
    function abrirGestao(ipen, nome) {
        $('#nomeGestao').text(nome);
        $('#listaItens').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i></div>');
        document.getElementById('offGestao').style.transform = 'translateX(0)';

        $.post('paginas/internos_eletronicos_gestao.php', {
            acao: 'listar_itens_interno',
            ipen: ipen
        }, function(res) {
            if (res.status === 'success') {
                let html = '';
                if (res.dados.length === 0) html = '<div class="alert alert-secondary">Nenhum item.</div>';

                res.dados.forEach(item => {
                    let color = item.situacao === 'Na Cela' ? 'success' : (item.situacao === 'Estoque' ? 'warning' : 'secondary');
                    let buttons = '';
                    let isChuveiro = item.tipo_item === 'Chuveiro';

                    if (item.situacao === 'Estoque') {
                        if (isChuveiro) {
                            if (window.podeMoverChuveiros) {
                                buttons = `<button type="button" class="btn btn-sm btn-outline-success btn-block mb-1" onclick="alterarSituacao(${item.id}, 'Na Cela')"><i class="fas fa-arrow-down"></i> Mover para Cela</button>`;
                            } else {
                                buttons = `<button type="button" class="btn btn-sm btn-outline-success btn-block mb-1" disabled title="Somente os setores de Censura, Manutenção ou Admin fazem movimentações de chuveiros"><i class="fas fa-arrow-down"></i> Mover para Cela</button>`;
                            }
                        } else {
                            buttons = `<button type="button" class="btn btn-sm btn-outline-success btn-block mb-1" onclick="alterarSituacao(${item.id}, 'Na Cela')"><i class="fas fa-arrow-down"></i> Mover para Cela</button>`;
                        }
                    } else if (item.situacao === 'Na Cela') {
                        if (isChuveiro) {
                            if (window.podeMoverChuveiros) {
                                buttons = `<button type="button" class="btn btn-sm btn-outline-warning btn-block mb-1" onclick="alterarSituacao(${item.id}, 'Estoque')"><i class="fas fa-arrow-up"></i> Recolher ao Estoque</button>`;
                            } else {
                                buttons = `<button type="button" class="btn btn-sm btn-outline-warning btn-block mb-1" disabled title="Somente os setores de Censura, Manutenção ou Admin fazem movimentações de chuveiros"><i class="fas fa-arrow-up"></i> Recolher ao Estoque</button>`;
                            }
                        } else {
                            buttons = `<button type="button" class="btn btn-sm btn-outline-warning btn-block mb-1" onclick="alterarSituacao(${item.id}, 'Estoque')"><i class="fas fa-arrow-up"></i> Recolher ao Estoque</button>`;
                        }
                    }

                    if (item.situacao !== 'Retirado') {
                        if (isChuveiro) {
                            if (window.podeMoverChuveiros) {
                                buttons += `<button type="button" class="btn btn-sm btn-outline-danger btn-block" onclick="if(confirm('Confirmar Baixa/Entrega para Família?')) alterarSituacao(${item.id}, 'Retirado')"><i class="fas fa-sign-out-alt"></i> Baixar (Entregar)</button>`;
                            } else {
                                buttons += `<button type="button" class="btn btn-sm btn-outline-danger btn-block" disabled title="Somente os setores de Censura, Manutenção ou Admin fazem movimentações de chuveiros"><i class="fas fa-sign-out-alt"></i> Baixar (Entregar)</button>`;
                            }
                        } else {
                            buttons += `<button type="button" class="btn btn-sm btn-outline-danger btn-block" onclick="if(confirm('Confirmar Baixa/Entrega para Família?')) alterarSituacao(${item.id}, 'Retirado')"><i class="fas fa-sign-out-alt"></i> Baixar (Entregar)</button>`;
                        }
                    }

                    let donationTag = '';
                    if (item.id_doacao_origem && item.id_doacao_origem > 0) {
                        donationTag = `<span class="badge badge-info ml-2" title="Doado por ${item.nome_doador_origem} em ${new Date(item.data_doacao_origem).toLocaleDateString('pt-BR')}"><i class="fas fa-hand-holding-heart"></i> Doação</span>`;
                    }

                    html += `
                    <div class="card mb-2 border-${color}" data-tipo-item="${item.tipo_item}">
                        <div class="card-body p-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="font-weight-bold">${item.tipo_item}</h6>
                                <div>
                                    <span class="badge badge-${color}">${item.situacao}</span>
                                    ${donationTag}
                                </div>
                            </div>
                            <small>${item.marca_modelo} (${item.cor})</small><br>
                            <small class="text-muted">NF: ${item.nota_fiscal}</small>
                            <div class="mt-2">${buttons}</div>
                        </div>
                    </div>`;
                });
                $('#listaItens').html(html);
            }
        }, 'json');
    }

    function alterarSituacao(id, nova) {
        $.post('paginas/internos_eletronicos_gestao.php', {
            acao: 'alterar_situacao',
            id_item: id,
            nova_situacao: nova
        }, function(res) {
            if (res.status === 'success') {
                alert('Atualizado!');
                window.safeReload();
            } else {
                alert('Erro: ' + res.msg);
            }
        }, 'json');
    }

    // 2. DETALHES DOS CARDS
    function abrirOffcanvasStats(status) {
        $('#titleStats').text('Itens: ' + status);
        $('#bodyStats').html('<div class="text-center p-3"><i class="fas fa-spinner fa-spin"></i></div>');
        document.getElementById('offStats').style.transform = 'translateX(0)';

        $.post('paginas/internos_eletronicos_gestao.php', {
            acao: 'listar_por_status',
            status: status
        }, function(res) {
            if (res.status === 'success') {
                let html = '<ul class="list-group list-group-flush">';
                res.dados.forEach(d => {
                    html += `
                    <li class="list-group-item p-2 bg-transparent">
                        <div class="d-flex justify-content-between">
                            <strong>${d.tipo_item}</strong>
                            <small>${d.galeria}-${d.bloco}-${d.res}</small>
                        </div>
                        <small>${d.nome}</small><br>
                        <small class="text-muted">${d.marca_modelo} (${d.situacao})</small>
                    </li>`;
                });
                html += '</ul>';
                if (res.dados.length >= 100) html += '<div class="alert alert-warning mt-2 small">Exibindo os últimos 100 registros.</div>';
                $('#bodyStats').html(html);
            }
        }, 'json');
    }

    // 3.2. RELATÓRIO DE ITENS CADASTRADOS
    function abrirRelatorioItensCadastrados() {
        // Obter filtros atuais
        const form = document.getElementById('formFiltro');
        const formData = new FormData(form);

        // Construir URL com filtros
        let url = '/censura/relatorio-itens-cadastrados/?';

        // Adicionar filtros relevantes
        if (formData.get('busca')) {
            url += '&interno=' + encodeURIComponent(formData.get('busca'));
        }
        if (formData.get('galeria')) {
            url += '&galeria=' + encodeURIComponent(formData.get('galeria'));
        }
        if (formData.get('bloco')) {
            url += '&bloco=' + encodeURIComponent(formData.get('bloco'));
        }
        if (formData.get('cela')) {
            url += '&cela=' + encodeURIComponent(formData.get('cela'));
        }

        // Abrir em nova aba
        window.open(url, '_blank');
    }

    function gerarRelatorio() {
        if (confirm('Deseja gerar o relatório completo de eletrônicos?\n\nEste relatório incluirá:\n- Totais por galeria\n- Relação completa de itens por interno\n- Estatísticas gerais\n\nO relatório será aberto em uma nova aba.')) {
            // Abrir em nova aba
            window.open('paginas/internos_eletronicos_gestao.php?acao=gerar_relatorio', '_blank');
        }
    }

    // 3.1. RELATÓRIO DE ITENS NA CELA MANUAL
    function abrirRelatorioItensCela() {
        // Obter filtros atuais
        const form = document.getElementById('formFiltro');
        const formData = new FormData(form);

        // Construir URL com filtros
        let url = '/censura/relatorio-itens-cela/?';

        // Adicionar filtros relevantes
        if (formData.get('busca')) {
            url += '&interno=' + encodeURIComponent(formData.get('busca'));
        }
        if (formData.get('galeria')) {
            url += '&galeria=' + encodeURIComponent(formData.get('galeria'));
        }
        if (formData.get('bloco')) {
            url += '&bloco=' + encodeURIComponent(formData.get('bloco'));
        }
        if (formData.get('cela')) {
            url += '&cela=' + encodeURIComponent(formData.get('cela'));
        }

        // Abrir em nova aba
        window.open(url, '_blank');
    }

    // 4. ABRIR DOSSIÊ
    function abrirDossie(ipen, nome) {
        window.open('interno_dossie.php?ipen=' + ipen, '_blank');
    }
</script>

<script src="assets/js/internos_doacao_eletronicos.js?v=<?= date('YmdHis') ?>"></script>
