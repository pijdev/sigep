<?php

/**
 * API Endpoint - Histórico de Internos
 * Gerencia dados da tabela internos_historico_detalhado
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
        if (!empty($params['ipen'])) {
            $where[] = "h.ipen = ?";
            $values[] = $params['ipen'];
        }

        if (!empty($params['campo'])) {
            $where[] = "h.campo = ?";
            $values[] = $params['campo'];
        }

        if (!empty($params['kit'])) {
            $where[] = "(h.valor_antigo = ? OR h.valor_novo = ?)";
            $values[] = $params['kit'];
            $values[] = $params['kit'];
        }

        // Paginação
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

        $sql = "SELECT
                    h.id, h.ipen, h.campo, h.valor_antigo, h.valor_novo,
                    h.data_alteracao, h.operacao,
                    i.nome, i.nome_social,
                    CASE
                        WHEN h.valor_antigo IS NULL THEN 'INSERIDO'
                        WHEN h.valor_novo IS NULL THEN 'REMOVIDO'
                        WHEN h.valor_antigo != h.valor_novo THEN 'ALTERADO'
                        ELSE 'ALTERADO'
                    END as tipo_alteracao
                FROM internos_historico_detalhado h
                LEFT JOIN internos i ON h.ipen = i.ipen";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY h.data_alteracao DESC";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $data = $stmt->fetchAll();

        // Contar total
        $sql_count = "SELECT COUNT(*) as total FROM internos_historico_detalhado h";
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
        if (empty($data['ipen']) || empty($data['campo'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'ipen e campo são obrigatórios']);
            return;
        }

        $sql = "INSERT INTO internos_historico_detalhado
                (ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao)
                VALUES (?, ?, ?, ?, NOW(), ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['ipen'],
            $data['campo'],
            $data['valor_antigo'] ?? null,
            $data['valor_novo'] ?? null,
            $data['operacao'] ?? 'SISTEMA'
        ]);

        $id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Histórico registrado com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
