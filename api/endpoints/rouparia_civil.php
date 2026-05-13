<?php

/**
 * API Endpoint - Roupa Civil
 * Gerencia dados da tabela internos_rouparia_civil
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
        if (!empty($params['ipen'])) {
            $where[] = "rc.ipen = ?";
            $values[] = $params['ipen'];
        }

        if (!empty($params['id'])) {
            $where[] = "rc.id = ?";
            $values[] = $params['id'];
        }

        if (!empty($params['criado_por'])) {
            $where[] = "rc.criado_por LIKE ?";
            $values[] = "%" . $params['criado_por'] . "%";
        }

        if (!empty($params['galeria'])) {
            $where[] = "i.galeria = ?";
            $values[] = $params['galeria'];
        }

        if (!empty($params['bloco'])) {
            $where[] = "i.bloco = ?";
            $values[] = $params['bloco'];
        }

        if (!empty($params['situacao'])) {
            $where[] = "i.situacao = ?";
            $values[] = $params['situacao'];
        }

        if (!empty($params['regalia'])) {
            if ($params['regalia'] === 'S') {
                $where[] = "i.regalia = 'S'";
            } elseif ($params['regalia'] === 'N') {
                $where[] = "i.regalia = 'N'";
            }
        }

        if (!empty($params['cor'])) {
            $where[] = "i.cor_roupa = ?";
            $values[] = $params['cor'];
        }

        // Filtro de período
        if (!empty($params['data_inicio'])) {
            $where[] = "rc.criado_em >= ?";
            $values[] = $params['data_inicio'] . ' 00:00:00';
        }

        if (!empty($params['data_fim'])) {
            $where[] = "rc.criado_em <= ?";
            $values[] = $params['data_fim'] . ' 23:59:59';
        }

        // Filtro para mostrar apenas internos com roupa civil
        if (isset($params['apenas_com_kit']) && $params['apenas_com_kit'] === '1') {
            $where[] = "rc.pecas IS NOT NULL";
        }

        // Filtro de pesquisa por interno
        if (!empty($params['pesquisa_interno'])) {
            $pesquisa = trim($params['pesquisa_interno']);
            $where[] = "(i.ipen LIKE ? OR i.nome LIKE ? OR i.nome_social LIKE ?)";
            $values[] = "%{$pesquisa}%";
            $values[] = "%{$pesquisa}%";
            $values[] = "%{$pesquisa}%";
        }

        // Paginação
        $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
        $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

        $sql = "SELECT
                    rc.id, rc.ipen, rc.nome, rc.pecas, rc.criado_por, rc.criado_em,
                    i.nome as interno_nome, i.nome_social as interno_nome_social,
                    i.galeria, i.bloco, i.res, i.situacao, i.regalia, i.regalia_setor,
                    i.cor_roupa, i.status as interno_status
                FROM internos_rouparia_civil rc
                LEFT JOIN internos i ON rc.ipen = i.ipen";

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY rc.criado_em DESC, rc.ipen ASC";

        if ($limit > 0) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $data = $stmt->fetchAll();

        // Contar total
        $sql_count = "SELECT COUNT(*) as total FROM internos_rouparia_civil rc LEFT JOIN internos i ON rc.ipen = i.ipen";
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
        if (empty($data['ipen'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'IPEN é obrigatório']);
            return;
        }

        $sql = "INSERT INTO internos_rouparia_civil
                (ipen, nome, pecas, criado_por, criado_em)
                VALUES (?, ?, ?, ?, NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['ipen'],
            $data['nome'] ?? null,
            $data['pecas'] ?? null,
            $data['criado_por'] ?? 'Sistema'
        ]);

        $id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'data' => ['id' => $id],
            'message' => 'Dados de roupa civil cadastrados com sucesso'
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

        // Verificar se o registro existe
        $stmt_check = $pdo->prepare("SELECT id FROM internos_rouparia_civil WHERE id = ?");
        $stmt_check->execute([$data['id']]);
        if (!$stmt_check->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Registro não encontrado']);
            return;
        }

        $sql = "UPDATE internos_rouparia_civil SET ";
        $fields = [];
        $values = [];

        if (isset($data['pecas'])) {
            $fields[] = "pecas = ?";
            $values[] = $data['pecas'];
        }

        if (isset($data['nome'])) {
            $fields[] = "nome = ?";
            $values[] = $data['nome'];
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
            'message' => 'Dados de roupa civil atualizados com sucesso'
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

        $sql = "DELETE FROM internos_rouparia_civil WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$params['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Dados de roupa civil removidos com sucesso'
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
