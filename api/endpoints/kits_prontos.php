<?php

/**
 * API Endpoint - Kits Prontos
 * Gerencia dados da tabela censura_rouparia_kits_prontos
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
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

// Processar requisições
$method = $_SERVER['REQUEST_METHOD'];
$params = $_GET;

switch ($method) {
    case 'GET':
        handleGet($pdo, $params);
        break;
    case 'POST':
        handlePost($pdo, json_decode(file_get_contents('php://input'), true));
        break;
    case 'PUT':
        handlePut($pdo, json_decode(file_get_contents('php://input'), true));
        break;
    case 'DELETE':
        handleDelete($pdo, $params);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
}

function handleGet($pdo, $params)
{
    try {
        $where = [];
        $values = [];

        // Filtros
        if (!empty($params['kit_numero'])) {
            $where[] = "kit_numero = ?";
            $values[] = $params['kit_numero'];
        }

        if (!empty($params['status'])) {
            $where[] = "status = ?";
            $values[] = $params['status'];
        }

        if (!empty($params['cor'])) {
            $where[] = "cor = ?";
            $values[] = $params['cor'];
        }

        // Paginação
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

        $sql = "SELECT * FROM censura_rouparia_kits_prontos";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY data_cadastro DESC";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $data = $stmt->fetchAll();

        // Contar total
        $sql_count = "SELECT COUNT(*) as total FROM censura_rouparia_kits_prontos";
        if (!empty($where)) {
            $sql_count .= " WHERE " . implode(" AND ", $where);
        }
        $stmt_count = $pdo->prepare($sql_count);
        $stmt_count->execute($values);
        $total = $stmt_count->fetch()['total'];

        echo json_encode([
            'success' => true,
            'data' => $data,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handlePost($pdo, $data)
{
    try {
        // Validar campos obrigatórios
        if (empty($data['kit_numero']) || empty($data['cor'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'kit_numero e cor são obrigatórios']);
            return;
        }

        $sql = "INSERT INTO censura_rouparia_kits_prontos
                (kit_numero, cor, status, info_adicional, criado_por, data_cadastro)
                VALUES (?, ?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['kit_numero'],
            $data['cor'],
            $data['status'] ?? 'pronto',
            $data['info_adicional'] ?? null,
            $data['criado_por'] ?? 'Sistema'
        ]);

        $id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Kit pronto cadastrado com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handlePut($pdo, $data)
{
    try {
        if (empty($data['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
            return;
        }

        $sql = "UPDATE censura_rouparia_kits_prontos SET ";
        $fields = [];
        $values = [];

        if (isset($data['status'])) {
            $fields[] = "status = ?";
            $values[] = $data['status'];
        }

        if (isset($data['cor'])) {
            $fields[] = "cor = ?";
            $values[] = $data['cor'];
        }

        if (isset($data['info_adicional'])) {
            $fields[] = "info_adicional = ?";
            $values[] = $data['info_adicional'];
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            return;
        }

        $sql .= implode(", ", $fields) . " WHERE id = ?";
        $values[] = $data['id'];

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        echo json_encode([
            'success' => true,
            'message' => 'Kit pronto atualizado com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDelete($pdo, $params)
{
    try {
        if (empty($params['id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
            return;
        }

        $sql = "DELETE FROM censura_rouparia_kits_prontos WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$params['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Kit pronto removido com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
