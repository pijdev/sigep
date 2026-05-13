<?php

/**
 * API Endpoint - Procedures de Kits
 * Gerencia procedures: refazer_kit, finalizar_confeccao_kit
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, X-API-Token');

require_once __DIR__ . '/../config/api.php';

// Verificar token
$token = $_SERVER['HTTP_X_API_TOKEN'] ?? '';
if ($token !== $config['token']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Token inválido']);
    exit;
}

// Conexão com banco
try {
    $db_config = require __DIR__ . '/../../conf/db.php';
    $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['user'], $db_config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro na conexão']);
    exit;
}

// Apenas POST é permitido para procedures
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método não permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

try {
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'refazer_kit':
            handleRefazerKit($pdo, $data);
            break;
        case 'finalizar_confeccao_kit':
            handleFinalizarConfeccao($pdo, $data);
            break;
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ação inválida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function handleRefazerKit($pdo, $data)
{
    try {
        // Validar campos obrigatórios
        if (empty($data['kit_numero']) || empty($data['cor'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'kit_numero e cor são obrigatórios']);
            return;
        }

        $usuario = $data['usuario'] ?? 'Sistema';
        $info_adicional = $data['info_adicional'] ?? '';

        // Usar procedure refazer_kit
        $stmt = $pdo->prepare("CALL refazer_kit(?, ?, ?, ?)");
        $stmt->execute([
            $data['kit_numero'],
            $data['cor'],
            $info_adicional,
            $usuario
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Procedure refazer_kit executada com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        throw new Exception('Erro na procedure refazer_kit: ' . $e->getMessage());
    }
}

function handleFinalizarConfeccao($pdo, $data)
{
    try {
        // Validar campos obrigatórios
        if (empty($data['kit_numero']) || empty($data['cor'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'kit_numero e cor são obrigatórios']);
            return;
        }

        $usuario = $data['usuario'] ?? 'Sistema';
        $info_adicional = $data['info_adicional'] ?? '';

        // Usar procedure finalizar_confeccao_kit
        $stmt = $pdo->prepare("CALL finalizar_confeccao_kit(?, ?, ?, ?)");
        $stmt->execute([
            $data['kit_numero'],
            $data['cor'],
            $info_adicional,
            $usuario
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Procedure finalizar_confeccao_kit executada com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        throw new Exception('Erro na procedure finalizar_confeccao_kit: ' . $e->getMessage());
    }
}
