<?php

/**
 * SolicitacoesController - Controlador do módulo de Solicitações
 * Implementa API para Kanban estilo Microsoft Planner
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

// Configuração de banco de dados
$config = require __DIR__ . '/../../../conf/db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// Define cabeçalho JSON para requisições de API
header('Content-Type: application/json; charset=utf-8');

// Roteamento de requisições
$metodo = $_SERVER['REQUEST_METHOD'];
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';

try {
    // Roteamento baseado no método HTTP e ação
    switch ($metodo) {
        case 'GET':
            handleGetRequest($pdo, $acao);
            break;
        case 'POST':
            handlePostRequest($pdo, $acao);
            break;
        case 'PUT':
            handlePutRequest($pdo, $acao);
            break;
        case 'DELETE':
            handleDeleteRequest($pdo, $acao);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
}

exit;

/**
 * Manipula requisições GET
 */
function handleGetRequest(PDO $pdo, string $acao): void
{
    switch ($acao) {
        case 'listar_cards':
            listarCards($pdo);
            break;
        case 'buscar_interno':
            buscarInterno($pdo);
            break;
        case 'buscar_setores':
            buscarSetores($pdo);
            break;
        case 'buscar_detalhes':
            buscarDetalhes($pdo);
            break;
        case 'buscar_solicitacoes':
            buscarSolicitacoes($pdo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Ação GET inválida'], 400);
    }
}

/**
 * Manipula requisições POST
 */
function handlePostRequest(PDO $pdo, string $acao): void
{
    switch ($acao) {
        case 'salvar_card':
            salvarCard($pdo);
            break;
        case 'atualizar_status':
            atualizarStatus($pdo);
            break;
        case 'salvar_solicitacao':
            salvarSolicitacao($pdo);
            break;
        case 'salvar_resposta':
            salvarResposta($pdo);
            break;
        case 'criar_rapido':
            criarRapido($pdo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Ação POST inválida'], 400);
    }
}

/**
 * Manipula requisições PUT
 */
function handlePutRequest(PDO $pdo, string $acao): void
{
    switch ($acao) {
        case 'atualizar_status':
            atualizarStatus($pdo);
            break;
        case 'salvar_card':
            salvarCard($pdo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Ação PUT inválida'], 400);
    }
}

/**
 * Manipula requisições DELETE
 */
function handleDeleteRequest(PDO $pdo, string $acao): void
{
    switch ($acao) {
        case 'deletar_card':
            deletarCard($pdo);
            break;
        default:
            jsonResponse(['success' => false, 'message' => 'Ação DELETE inválida'], 400);
    }
}

/**
 * LISTAR CARDS - Retorna todas as solicitações agrupadas por status
 * Método principal do Kanban
 */
function listarCards(PDO $pdo): void
{
    $termo = trim($_GET['termo'] ?? '');
    $setor = trim($_GET['setor'] ?? '');
    $categoria = trim($_GET['categoria'] ?? '');
    $prioridade = trim($_GET['prioridade'] ?? '');

    $where = ["ativo = 'S'"];
    $params = [];

    if ($termo !== '') {
        $where[] = "(CAST(ipen AS CHAR) LIKE ? OR nome_interno LIKE ? OR nome_social LIKE ? OR descricao LIKE ? OR setor_destino LIKE ?)";
        $like = "%{$termo}%";
        $params = array_fill(0, 5, $like);
    }

    if ($setor !== '') {
        $where[] = 'setor_destino LIKE ?';
        $params[] = "%{$setor}%";
    }

    if ($categoria !== '') {
        $where[] = 'categoria = ?';
        $params[] = $categoria;
    }

    if ($prioridade !== '') {
        $where[] = 'prioridade = ?';
        $params[] = $prioridade;
    }

    $sql = "SELECT * FROM internos_solicitacoes 
            WHERE " . implode(' AND ', $where) . " 
            ORDER BY FIELD(status,'Pendentes','Em Atendimento','Aguardando','Atendidas','Canceladas'), 
                     FIELD(prioridade,'Urgente','Alta','Média','Baixa'),
                     atualizado_em DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitacoes = $stmt->fetchAll();

    // Processa tarefas JSON e adiciona contadores
    foreach ($solicitacoes as &$item) {
        $item['tarefas'] = json_decode($item['tarefas'] ?? '[]', true) ?: [];
        $item['tarefas_concluidas'] = count(array_filter($item['tarefas'], fn($t) => $t['concluida'] ?? false));
        $item['total_tarefas'] = count($item['tarefas']);
        $item['respostas_count'] = buscarContagemRespostas($pdo, (int)$item['id']);
        
        // Formata data limite se existir
        if ($item['data_limite']) {
            $item['data_limite_formatada'] = date('d/m/Y', strtotime($item['data_limite']));
            $item['data_limite_vencida'] = strtotime($item['data_limite']) < strtotime('today');
        }
    }

    // Agrupa por status para o Kanban
    $agrupado = [];
    $statusOrdenado = ['Pendentes', 'Em Atendimento', 'Aguardando', 'Atendidas', 'Canceladas'];
    
    foreach ($statusOrdenado as $status) {
        $agrupado[$status] = array_filter($solicitacoes, fn($s) => $s['status'] === $status);
    }

    jsonResponse([
        'success' => true,
        'dados' => $agrupado,
        'total' => count($solicitacoes),
        'contagem_por_status' => array_map(fn($s) => count($s), $agrupado)
    ]);
}

/**
 * ATUALIZAR STATUS - Atualiza o status de um card via drag and drop
 * Chamado quando um card é movido entre colunas
 */
function atualizarStatus(PDO $pdo): void
{
    // Obtém dados do POST ou JSON body
    $input = file_get_contents('php://input');
    $data = $input ? json_decode($input, true) : $_POST;
    
    $id = (int)($data['id'] ?? 0);
    $novoStatus = trim($data['status'] ?? '');
    
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }

    $statusValidos = ['Pendentes', 'Em Atendimento', 'Aguardando', 'Atendidas', 'Canceladas'];
    if (!in_array($novoStatus, $statusValidos)) {
        jsonResponse(['success' => false, 'message' => 'Status inválido'], 400);
    }

    // Busca status atual para log
    $stmt = $pdo->prepare('SELECT status, nome_interno FROM internos_solicitacoes WHERE id = ?');
    $stmt->execute([$id]);
    $atual = $stmt->fetch();

    if (!$atual) {
        jsonResponse(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
    }

    if ($atual['status'] === $novoStatus) {
        jsonResponse(['success' => true, 'message' => 'Status já atualizado']);
    }

    // Atualiza status
    $stmt = $pdo->prepare('UPDATE internos_solicitacoes SET status = ?, atualizado_em = NOW(), atualizado_por = ? WHERE id = ?');
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $stmt->execute([$novoStatus, $usuario, $id]);

    // Registra no log
    registrarLog($pdo, $id, 'Status', "Status alterado de {$atual['status']} para {$novoStatus}", [
        'anterior' => $atual['status'],
        'novo' => $novoStatus
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Status atualizado com sucesso',
        'status_anterior' => $atual['status'],
        'novo_status' => $novoStatus
    ]);
}

/**
 * SALVAR CARD - Cria ou atualiza uma solicitação completa
 * CRUD completo via requisição assíncrona
 */
function salvarCard(PDO $pdo): void
{
    $input = file_get_contents('php://input');
    $data = $input ? json_decode($input, true) : $_POST;
    
    $id = (int)($data['id'] ?? 0);
    
    // Validação básica
    if (empty($data['id_interno'])) {
        jsonResponse(['success' => false, 'message' => 'Selecione um interno'], 400);
    }
    if (empty($data['descricao'])) {
        jsonResponse(['success' => false, 'message' => 'Descrição é obrigatória'], 400);
    }
    if (empty($data['setor_destino'])) {
        jsonResponse(['success' => false, 'message' => 'Setor destino é obrigatório'], 400);
    }

    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $setorUsuario = $_SESSION['setor'] ?? 'não informado';

    // Prepara dados
    $campos = [
        'id_interno' => (int)$data['id_interno'],
        'ipen' => (int)($data['ipen'] ?? 0),
        'nome_interno' => trim($data['nome_interno'] ?? ''),
        'nome_social' => trim($data['nome_social'] ?? ''),
        'galeria' => trim($data['galeria'] ?? ''),
        'bloco' => trim($data['bloco'] ?? ''),
        'res' => trim($data['res'] ?? ''),
        'setor_destino' => trim($data['setor_destino']),
        'descricao' => trim($data['descricao']),
        'status' => trim($data['status'] ?? 'Pendentes'),
        'categoria' => trim($data['categoria'] ?? ''),
        'prioridade' => trim($data['prioridade'] ?? 'Média'),
        'responsavel_nome' => trim($data['responsavel_nome'] ?? ''),
        'data_limite' => !empty($data['data_limite']) ? $data['data_limite'] : null,
        'tarefas' => !empty($data['tarefas']) ? json_encode($data['tarefas']) : null,
        'atualizado_por' => $usuario,
        'atualizado_em' => date('Y-m-d H:i:s')
    ];

    if ($id > 0) {
        // Atualização
        $busca = $pdo->prepare('SELECT * FROM internos_solicitacoes WHERE id = ?');
        $busca->execute([$id]);
        $anterior = $busca->fetch();

        if (!$anterior) {
            jsonResponse(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
        }

        $setClause = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
        $sql = "UPDATE internos_solicitacoes SET $setClause WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge(array_values($campos), [$id]));

        // Log de alteração
        registrarLog($pdo, $id, 'Atualizado', 'Solicitação atualizada', [
            'dados_anteriores' => $anterior,
            'dados_novos' => $campos
        ], $usuario, $setorUsuario);

        jsonResponse(['success' => true, 'message' => 'Solicitação atualizada com sucesso', 'id' => $id]);
    } else {
        // Criação
        $campos['criado_por'] = $usuario;
        $campos['criado_em'] = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO internos_solicitacoes (" . implode(', ', array_keys($campos)) . 
               ") VALUES (" . implode(', ', array_fill(0, count($campos), '?')) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($campos));
        
        $novoId = (int)$pdo->lastInsertId();

        // Log de criação
        registrarLog($pdo, $novoId, 'Criado', 'Nova solicitação criada', [
            'dados' => $campos
        ], $usuario, $setorUsuario);

        jsonResponse(['success' => true, 'message' => 'Solicitação criada com sucesso', 'id' => $novoId]);
    }
}

/**
 * DELETAR CARD - Remove uma solicitação (soft delete)
 */
function deletarCard(PDO $pdo): void
{
    $input = file_get_contents('php://input');
    $data = $input ? json_decode($input, true) : $_POST;
    
    $id = (int)($data['id'] ?? 0);
    
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }

    // Busca dados para log
    $stmt = $pdo->prepare('SELECT * FROM internos_solicitacoes WHERE id = ? AND ativo = "S"');
    $stmt->execute([$id]);
    $solicitacao = $stmt->fetch();

    if (!$solicitacao) {
        jsonResponse(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
    }

    // Soft delete
    $stmt = $pdo->prepare('UPDATE internos_solicitacoes SET ativo = "N", atualizado_em = NOW(), atualizado_por = ? WHERE id = ?');
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $stmt->execute([$usuario, $id]);

    // Log
    registrarLog($pdo, $id, 'Atualizado', 'Solicitação removida (soft delete)', [
        'dados_removidos' => $solicitacao
    ], $usuario);

    jsonResponse(['success' => true, 'message' => 'Solicitação removida com sucesso']);
}

/**
 * CRIAR RÁPIDO - Cria uma solicitação de forma simplificada
 */
function criarRapido(PDO $pdo): void
{
    $input = file_get_contents('php://input');
    $data = $input ? json_decode($input, true) : $_POST;
    
    // Validação
    if (empty($data['id_interno']) || empty($data['descricao']) || empty($data['setor_destino'])) {
        jsonResponse(['success' => false, 'message' => 'Preencha todos os campos obrigatórios'], 400);
    }

    $usuario = $_SESSION['usuario'] ?? 'sistema';
    
    // Prepara dados básicos
    $campos = [
        'id_interno' => (int)$data['id_interno'],
        'ipen' => (int)($data['ipen'] ?? 0),
        'nome_interno' => trim($data['nome_interno'] ?? ''),
        'nome_social' => trim($data['nome_social'] ?? ''),
        'setor_destino' => trim($data['setor_destino']),
        'descricao' => trim($data['descricao']),
        'status' => trim($data['status'] ?? 'Pendentes'),
        'prioridade' => trim($data['prioridade'] ?? 'Média'),
        'criado_por' => $usuario,
        'atualizado_por' => $usuario
    ];

    $sql = "INSERT INTO internos_solicitacoes (" . implode(', ', array_keys($campos)) . 
           ") VALUES (" . implode(', ', array_fill(0, count($campos), '?')) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($campos));
    
    $novoId = (int)$pdo->lastInsertId();

    registrarLog($pdo, $novoId, 'Criado', 'Solicitação criada via criação rápida', [
        'dados' => $campos
    ], $usuario);

    jsonResponse(['success' => true, 'message' => 'Solicitação criada com sucesso', 'id' => $novoId]);
}

/**
 * BUSCAR INTERNO - Busca internos para autocomplete
 */
function buscarInterno(PDO $pdo): void
{
    $termo = trim($_GET['termo'] ?? '');
    if ($termo === '') {
        jsonResponse(['success' => true, 'dados' => []]);
    }

    $like = '%' . $termo . '%';
    $stmt = $pdo->prepare(
        "SELECT ipen, nome, nome_social, galeria, bloco, res
         FROM internos
         WHERE status = 'A' AND (CAST(ipen AS CHAR) LIKE ? OR nome LIKE ? OR nome_social LIKE ?)
         ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC
         LIMIT 12"
    );
    $stmt->execute([$like, $like, $like]);

    jsonResponse(['success' => true, 'dados' => $stmt->fetchAll()]);
}

/**
 * BUSCAR SETORES - Retorna lista de setores disponíveis
 */
function buscarSetores(PDO $pdo): void
{
    $setores = [];
    try {
        $stmt = $pdo->query("SELECT nome FROM sectors WHERE ativo = 'S' ORDER BY nome ASC");
        $setores = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nome');
    } catch (Exception $e) {
        $setores = [];
    }

    if (empty($setores)) {
        $stmt = $pdo->query(
            "SELECT DISTINCT regalia_setor AS nome FROM internos
             WHERE regalia_setor IS NOT NULL AND regalia_setor != ''
             ORDER BY regalia_setor ASC"
        );
        $setores = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nome');
    }

    jsonResponse(['success' => true, 'dados' => $setores]);
}

/**
 * BUSCAR DETALHES - Retorna detalhes completos de uma solicitação
 */
function buscarDetalhes(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
    }

    $stmt = $pdo->prepare('SELECT * FROM internos_solicitacoes WHERE id = ? AND ativo = "S"');
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Solicitação não encontrada'], 404);
    }

    $item['tarefas'] = json_decode($item['tarefas'] ?? '[]', true) ?: [];
    $item['log'] = buscarLogHistorico($pdo, $id);

    jsonResponse(['success' => true, 'dados' => $item]);
}

/**
 * BUSCAR SOLICITAÇÕES - Busca com filtros (legado, mantido para compatibilidade)
 */
function buscarSolicitacoes(PDO $pdo): void
{
    $termo = trim($_GET['termo'] ?? '');
    $setor = trim($_GET['setor'] ?? '');

    $where = ["ativo = 'S'"];
    $params = [];

    if ($termo !== '') {
        $where[] = "(CAST(ipen AS CHAR) LIKE ? OR nome_interno LIKE ? OR nome_social LIKE ? OR descricao LIKE ? OR setor_destino LIKE ?)";
        $like = "%{$termo}%";
        $params = array_fill(0, 5, $like);
    }

    if ($setor !== '') {
        $where[] = 'setor_destino LIKE ?';
        $params[] = "%{$setor}%";
    }

    $sql = "SELECT * FROM internos_solicitacoes WHERE " . implode(' AND ', $where) . " 
            ORDER BY FIELD(status,'Pendentes','Em Atendimento','Aguardando','Atendidas','Canceladas'), atualizado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $solicitacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($solicitacoes as &$item) {
        $item['tarefas'] = json_decode($item['tarefas'] ?? '[]', true) ?: [];
        $item['respostas'] = buscarContagemRespostas($pdo, (int)$item['id']);
    }

    jsonResponse(['success' => true, 'dados' => $solicitacoes]);
}

/**
 * SALVAR SOLICITAÇÃO - Legado, mantido para compatibilidade
 */
function salvarSolicitacao(PDO $pdo): void
{
    salvarCard($pdo); // Redireciona para o método novo
}

/**
 * SALVAR RESPOSTA - Adiciona resposta/comentário a uma solicitação
 */
function salvarResposta(PDO $pdo): void
{
    $id = (int)($_POST['id'] ?? 0);
    $resposta = trim($_POST['resposta'] ?? '');
    
    if ($id <= 0 || empty($resposta)) {
        jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
    }

    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $setorUsuario = $_SESSION['setor'] ?? 'não informado';

    // Registra no log como resposta
    registrarLog($pdo, $id, 'Resposta', $resposta, [
        'resposta' => $resposta
    ], $usuario, $setorUsuario);

    jsonResponse(['success' => true, 'message' => 'Resposta adicionada com sucesso']);
}

/**
 * Funções auxiliares
 */

function buscarContagemRespostas(PDO $pdo, int $solicitacaoId): int
{
    $stmt = $pdo->prepare('SELECT COUNT(*) AS total FROM internos_solicitacoes_log WHERE solicitacao_id = ? AND acao = "Resposta"');
    $stmt->execute([$solicitacaoId]);
    return (int)$stmt->fetchColumn();
}

function buscarLogHistorico(PDO $pdo, int $solicitacaoId): array
{
    $stmt = $pdo->prepare(
        'SELECT acao, descricao, dados_anteriores, dados_novos, usuario, setor_usuario, criado_em
         FROM internos_solicitacoes_log
         WHERE solicitacao_id = ?
         ORDER BY criado_em DESC'
    );
    $stmt->execute([$solicitacaoId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function registrarLog(PDO $pdo, int $solicitacaoId, string $acao, string $descricao, array $dados = [], string $usuario = '', string $setorUsuario = ''): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO internos_solicitacoes_log 
         (solicitacao_id, acao, descricao, dados_anteriores, dados_novos, usuario, setor_usuario)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $solicitacaoId,
        $acao,
        $descricao,
        isset($dados['dados_anteriores']) ? json_encode($dados['dados_anteriores']) : null,
        isset($dados['dados_novos']) ? json_encode($dados['dados_novos']) : (isset($dados['dados']) ? json_encode($dados['dados']) : null),
        $usuario,
        $setorUsuario
    ]);
}

function jsonResponse(array $data, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Garante que o schema do banco de dados esteja atualizado
ensureDatabaseSchema($pdo);

function ensureDatabaseSchema(PDO $pdo): void
{
    // Verifica se os novos campos existem e adiciona se necessário
    try {
        $columns = $pdo->query("SHOW COLUMNS FROM internos_solicitacoes")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('data_limite', $columns)) {
            $pdo->exec("ALTER TABLE internos_solicitacoes ADD COLUMN data_limite DATE NULL AFTER tarefas");
        }
        if (!in_array('categoria', $columns)) {
            $pdo->exec("ALTER TABLE internos_solicitacoes ADD COLUMN categoria VARCHAR(100) NULL AFTER data_limite");
        }
        if (!in_array('prioridade', $columns)) {
            $pdo->exec("ALTER TABLE internos_solicitacoes ADD COLUMN prioridade ENUM('Baixa','Média','Alta','Urgente') DEFAULT 'Média' AFTER categoria");
        }
        if (!in_array('responsavel_nome', $columns)) {
            $pdo->exec("ALTER TABLE internos_solicitacoes ADD COLUMN responsavel_nome VARCHAR(150) NULL AFTER prioridade");
        }
        if (!in_array('responsavel_foto', $columns)) {
            $pdo->exec("ALTER TABLE internos_solicitacoes ADD COLUMN responsavel_foto VARCHAR(255) NULL AFTER responsavel_nome");
        }
    } catch (Exception $e) {
        // Silencioso - schema pode já estar atualizado
    }
}
