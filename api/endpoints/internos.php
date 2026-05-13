<?php
// Endpoint: Internos
require_once '../config/api.php';

checkToken(); // segurança básica

$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET /api/endpoints/internos.php - Listar internos
if ($method === 'GET') {
    $sql = "SELECT * FROM internos WHERE 1=1";
    
    // Filtros
    if (!empty($_GET['status']) && in_array($_GET['status'], ['A', 'I'])) {
        $sql .= " AND status = '" . addslashes($_GET['status']) . "'";
    }
    if (!empty($_GET['ala'])) {
        $sql .= " AND ala LIKE '%" . addslashes($_GET['ala']) . "%'";
    }
    if (!empty($_GET['cor_roupa']) && in_array($_GET['cor_roupa'], ['Laranja', 'Verde'])) {
        $sql .= " AND cor_roupa = '" . addslashes($_GET['cor_roupa']) . "'";
    }
    if (!empty($_GET['search'])) {
        $search = addslashes($_GET['search']);
        $sql .= " AND (nome LIKE '%$search%' OR nome_social LIKE '%$search%' OR apelido LIKE '%$search%')";
    }
    
    // Ordenação
    $orderBy = $_GET['order_by'] ?? 'nome';
    $orderDir = $_GET['order_dir'] ?? 'ASC';
    $sql .= " ORDER BY $orderBy $orderDir";
    
    // Paginação
    $limit = min(intval($_GET['limit'] ?? 50), 100);
    $offset = intval($_GET['offset'] ?? 0);
    $sql .= " LIMIT $limit OFFSET $offset";
    
    $stmt = $pdo->query($sql);
    $internos = $stmt->fetchAll();
    
    // Total para paginação
    $countSql = "SELECT COUNT(*) FROM internos WHERE 1=1";
    if (!empty($_GET['status']) && in_array($_GET['status'], ['A', 'I'])) {
        $countSql .= " AND status = '" . addslashes($_GET['status']) . "'";
    }
    if (!empty($_GET['ala'])) {
        $countSql .= " AND ala LIKE '%" . addslashes($_GET['ala']) . "%'";
    }
    if (!empty($_GET['search'])) {
        $search = addslashes($_GET['search']);
        $countSql .= " AND (nome LIKE '%$search%' OR nome_social LIKE '%$search%' OR apelido LIKE '%$search%')";
    }
    
    $total = $pdo->query($countSql)->fetchColumn();
    
    json([
        'success' => true,
        'data' => $internos,
        'total' => intval($total),
        'limit' => $limit,
        'offset' => $offset
    ]);
}

// POST /api/endpoints/internos.php - Criar interno
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        json(['error' => 'JSON inválido'], 400);
    }
    
    // Validação básica
    if (empty($input['nome'])) {
        json(['error' => 'Nome é obrigatório'], 400);
    }
    
    try {
        $sql = "INSERT INTO internos (nome, nome_social, cpf, apelido, ala, galeria, bloco, piso, 
                tipo_residencia, res, status, regalia, regalia_galeria, cor_roupa, regalia_setor, 
                regalia_kit, kit, tamanho_kit, data_ativo) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $input['nome'],
            $input['nome_social'] ?? null,
            $input['cpf'] ?? null,
            $input['apelido'] ?? null,
            $input['ala'] ?? null,
            $input['galeria'] ?? null,
            $input['bloco'] ?? null,
            $input['piso'] ?? null,
            $input['tipo_residencia'] ?? null,
            $input['res'] ?? null,
            $input['status'] ?? 'A',
            $input['regalia'] ?? 'N',
            $input['regalia_galeria'] ?? 'N',
            $input['cor_roupa'] ?? null,
            $input['regalia_setor'] ?? null,
            $input['regalia_kit'] ?? null,
            $input['kit'] ?? 0,
            $input['tamanho_kit'] ?? null
        ];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json([
            'success' => true,
            'message' => 'Interno criado com sucesso',
            'ipen' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        json(['error' => 'Erro ao criar interno: ' . $e->getMessage()], 500);
    }
}

// PUT /api/endpoints/internos.php - Atualizar interno
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['ipen'])) {
        json(['error' => 'IPEN é obrigatório'], 400);
    }
    
    // Verificar se interno existe
    $check = $pdo->prepare("SELECT ipen FROM internos WHERE ipen = ?");
    $check->execute([$input['ipen']]);
    if (!$check->fetch()) {
        json(['error' => 'Interno não encontrado'], 404);
    }
    
    try {
        $setParts = [];
        $params = [];
        
        $updatableFields = [
            'nome', 'nome_social', 'cpf', 'apelido', 'ala', 'galeria', 'bloco', 'piso',
            'tipo_residencia', 'res', 'status', 'regalia', 'regalia_galeria', 'cor_roupa',
            'regalia_setor', 'regalia_kit', 'kit', 'tamanho_kit', 'data_alterado'
        ];
        
        foreach ($updatableFields as $field) {
            if (array_key_exists($field, $input)) {
                $setParts[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        
        if (empty($setParts)) {
            json(['error' => 'Nenhum campo para atualizar'], 400);
        }
        
        $params[] = $input['ipen'];
        
        $sql = "UPDATE internos SET " . implode(', ', $setParts) . " WHERE ipen = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        json([
            'success' => true,
            'message' => 'Interno atualizado com sucesso',
            'ipen' => $input['ipen']
        ]);
        
    } catch (Exception $e) {
        json(['error' => 'Erro ao atualizar interno: ' . $e->getMessage()], 500);
    }
}

// DELETE /api/endpoints/internos.php - Excluir interno
if ($method === 'DELETE') {
    $ipen = $_GET['ipen'] ?? json_decode(file_get_contents('php://input'), true)['ipen'] ?? null;
    
    if (!$ipen) {
        json(['error' => 'IPEN é obrigatório'], 400);
    }
    
    // Verificar se interno existe
    $check = $pdo->prepare("SELECT ipen FROM internos WHERE ipen = ?");
    $check->execute([$ipen]);
    if (!$check->fetch()) {
        json(['error' => 'Interno não encontrado'], 404);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM internos WHERE ipen = ?");
        $stmt->execute([$ipen]);
        
        json([
            'success' => true,
            'message' => 'Interno excluído com sucesso',
            'ipen' => $ipen
        ]);
        
    } catch (Exception $e) {
        json(['error' => 'Erro ao excluir interno: ' . $e->getMessage()], 500);
    }
}

// GET específico /api/endpoints/internos.php?ipen=123
if ($method === 'GET' && !empty($_GET['ipen'])) {
    $ipen = $_GET['ipen'];
    
    $stmt = $pdo->prepare("SELECT * FROM internos WHERE ipen = ?");
    $stmt->execute([$ipen]);
    $interno = $stmt->fetch();
    
    if (!$interno) {
        json(['error' => 'Interno não encontrado'], 404);
    }
    
    json([
        'success' => true,
        'data' => $interno
    ]);
}

json(['error' => 'Método não permitido'], 405);
?>