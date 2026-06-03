<?php
/**
 * Proxy iPEN → SIGEP
 * Resolve problema CORS/HTTPS-HTTP entre iPEN e SIGEP local
 * 
 * COLOCAR ESTE ARQUIVO NO SERVIDOR iPEN EM:
 * https://www.sc.gov.br/ipen/proxy-sigep.php
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$sigepBaseUrl = 'http://10.40.88.200';

try {
    switch ($action) {
        case 'last_import':
            $unidade = $_GET['unidade'] ?? '8019';
            $url = $sigepBaseUrl . '/api/importa18_last_import.php?unidade=' . $unidade;
            
            $response = file_get_contents($url);
            if ($response === false) {
                throw new Exception('Falha ao consultar SIGEP');
            }
            
            echo $response;
            break;
            
        case 'import':
            $reportData = $_POST['report_data'] ?? '';
            $unidade = $_POST['unidade'] ?? '8019';
            $source = $_POST['source'] ?? 'autoimport';
            
            if (empty($reportData)) {
                throw new Exception('Dados não informados');
            }
            
            $postData = http_build_query([
                'report_data' => $reportData,
                'unidade' => $unidade,
                'source' => $source
            ]);
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $postData
                ]
            ]);
            
            $url = $sigepBaseUrl . '/api/importa18_auto.php';
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new Exception('Falha ao enviar para SIGEP');
            }
            
            echo $response;
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
