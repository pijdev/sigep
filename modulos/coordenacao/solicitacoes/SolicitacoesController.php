<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

$config = require __DIR__ . '/../../../conf/db.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

header('Content-Type: application/json; charset=utf-8');

$metodo = $_SERVER['REQUEST_METHOD'];
$acao   = $_GET['acao'] ?? $_POST['acao'] ?? '';

try {
    switch ($metodo) {
        case 'GET':
            switch ($acao) {
                case 'listar_cards':     listarCards($pdo);     break;
                case 'buscar_interno':   buscarInterno($pdo);   break;
                case 'buscar_setores':   buscarSetores($pdo);   break;
                case 'buscar_categorias': buscarCategorias($pdo); break;
                case 'buscar_detalhes':  buscarDetalhes($pdo);  break;
                case 'debug_setores':
                    try {
                        $stmt = $pdo->query("SELECT name FROM sectors ORDER BY name ASC");
                        $resultado = $stmt->fetchAll();
                        jsonResponse(['success' => true, 'query' => 'SELECT name FROM sectors', 'resultado' => $resultado]);
                    } catch (Exception $e) {
                        jsonResponse(['success' => false, 'erro' => $e->getMessage()]);
                    }
                    break;
                default: jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
            }
            break;

        case 'POST':
            switch ($acao) {
                case 'salvar_card':          salvarCard($pdo);         break;
                case 'atualizar_status':     atualizarStatus($pdo);    break;
                case 'salvar_resposta':      salvarResposta($pdo);     break;
                case 'criar_rapido':         criarRapido($pdo);        break;
                case 'criar_categoria':      criarCategoria($pdo);     break;
                case 'atualizar_categoria':  atualizarCategoria($pdo); break;
                default: jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
            }
            break;

        case 'DELETE':
            switch ($acao) {
                case 'deletar_card':      deletarCard($pdo);      break;
                case 'deletar_categoria': deletarCategoria($pdo); break;
                default: jsonResponse(['success' => false, 'message' => 'Ação inválida'], 400);
            }
            break;

        default:
            jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'message' => 'Erro: ' . $e->getMessage()], 500);
}
exit;

// ---------------------------------------------------------------------------
// LISTAR CARDS
// ---------------------------------------------------------------------------
function listarCards(PDO $pdo): void
{
    $termo      = trim($_GET['termo']      ?? '');
    $setor      = trim($_GET['setor']      ?? '');
    $categoria  = trim($_GET['categoria']  ?? '');
    $verCanceladas = ($_GET['ver_canceladas'] ?? '') === '1';

    $where  = ["ativo = 'S'"];
    $params = [];

    if ($termo !== '') {
        $where[] = "(CAST(ipen AS CHAR) LIKE ? OR nome_interno LIKE ? OR nome_social LIKE ? OR descricao LIKE ?)";
        $like = "%{$termo}%";
        $params = array_fill(0, 4, $like);
    }
    if ($setor !== '') {
        $where[] = 'setor_destino = ?';
        $params[] = $setor;
    }
    if ($categoria !== '') {
        $where[] = 'categoria = ?';
        $params[] = $categoria;
    }
    if (!$verCanceladas) {
        $where[] = "status != 'Canceladas'";
    }

    $sql  = "SELECT * FROM internos_solicitacoes WHERE " . implode(' AND ', $where) . " ORDER BY atualizado_em DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$item) {
        $item['tarefas']           = json_decode($item['tarefas'] ?? '[]', true) ?: [];
        $item['tarefas_concluidas'] = count(array_filter($item['tarefas'], fn($t) => $t['concluida'] ?? false));
        $item['total_tarefas']      = count($item['tarefas']);
    }

    $statusOrdem = ['Pendentes', 'Em Atendimento', 'Aguardando', 'Atendidas', 'Canceladas'];
    $agrupado    = array_fill_keys($statusOrdem, []);
    foreach ($rows as $r) {
        $agrupado[$r['status']][] = $r;
    }

    jsonResponse(['success' => true, 'dados' => $agrupado]);
}

// ---------------------------------------------------------------------------
// BUSCAR INTERNO (autocomplete)
// ---------------------------------------------------------------------------
function buscarInterno(PDO $pdo): void
{
    $termo = trim($_GET['termo'] ?? '');
    if ($termo === '') {
        jsonResponse(['success' => true, 'dados' => []]);
        return;
    }
    $like = '%' . $termo . '%';
    $stmt = $pdo->prepare(
        "SELECT ipen, nome, nome_social, galeria, bloco, res
         FROM internos
         WHERE status = 'A' AND (CAST(ipen AS CHAR) LIKE ? OR nome LIKE ? OR nome_social LIKE ?)
         ORDER BY COALESCE(NULLIF(nome_social,''), nome) ASC
         LIMIT 12"
    );
    $stmt->execute([$like, $like, $like]);
    jsonResponse(['success' => true, 'dados' => $stmt->fetchAll()]);
}

// ---------------------------------------------------------------------------
// BUSCAR SETORES
// ---------------------------------------------------------------------------
function buscarSetores(PDO $pdo): void
{
    try {
        // A tabela sectors não tem coluna ativo, então simplifica
        $stmt = $pdo->query("SELECT name FROM sectors ORDER BY name ASC");
        $setores = array_column($stmt->fetchAll(), 'name');
    } catch (Exception $e) {
        $setores = [];
    }
    jsonResponse(['success' => true, 'dados' => $setores]);
}

// ---------------------------------------------------------------------------
// BUSCAR CATEGORIAS (da tabela internos_solicitacoes_categorias)
// ---------------------------------------------------------------------------
function buscarCategorias(PDO $pdo): void
{
    try {
        $stmt = $pdo->query("SELECT id, name, '#6c757d' as cor FROM internos_solicitacoes_categorias ORDER BY name ASC");
        $cats = $stmt->fetchAll();
    } catch (Exception $e) {
        $cats = [];
    }
    jsonResponse(['success' => true, 'dados' => $cats]);
}

// ---------------------------------------------------------------------------
// CRIAR CATEGORIA
// ---------------------------------------------------------------------------
function criarCategoria(PDO $pdo): void
{
    $data = jsonBody();
    $nome = trim($data['nome'] ?? '');
    $cor  = trim($data['cor']  ?? '#6c757d');
    if ($nome === '') {
        jsonResponse(['success' => false, 'message' => 'Nome é obrigatório'], 400);
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO internos_solicitacoes_categorias (nome, cor) VALUES (?, ?)");
    $stmt->execute([$nome, $cor]);
    jsonResponse(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}

// ---------------------------------------------------------------------------
// ATUALIZAR CATEGORIA
// ---------------------------------------------------------------------------
function atualizarCategoria(PDO $pdo): void
{
    $data = jsonBody();
    $id   = (int)($data['id']   ?? 0);
    $nome = trim($data['nome']  ?? '');
    $cor  = trim($data['cor']   ?? '#6c757d');
    if ($id <= 0 || $nome === '') {
        jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400);
        return;
    }
    $stmt = $pdo->prepare("UPDATE internos_solicitacoes_categorias SET nome = ?, cor = ? WHERE id = ?");
    $stmt->execute([$nome, $cor, $id]);
    jsonResponse(['success' => true]);
}

// ---------------------------------------------------------------------------
// DELETAR CATEGORIA
// ---------------------------------------------------------------------------
function deletarCategoria(PDO $pdo): void
{
    $data = jsonBody();
    $id   = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM internos_solicitacoes_categorias WHERE id = ?");
    $stmt->execute([$id]);
    jsonResponse(['success' => true]);
}

// ---------------------------------------------------------------------------
// BUSCAR DETALHES
// ---------------------------------------------------------------------------
function buscarDetalhes(PDO $pdo): void
{
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400);
        return;
    }
    $stmt = $pdo->prepare('SELECT * FROM internos_solicitacoes WHERE id = ? AND ativo = "S"');
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        jsonResponse(['success' => false, 'message' => 'Não encontrado'], 404);
        return;
    }
    $item['tarefas'] = json_decode($item['tarefas'] ?? '[]', true) ?: [];
    $item['log']     = buscarLog($pdo, $id);
    jsonResponse(['success' => true, 'dados' => $item]);
}

// ---------------------------------------------------------------------------
// SALVAR CARD (criar ou editar)
// ---------------------------------------------------------------------------
function salvarCard(PDO $pdo): void
{
    $data = jsonBody();
    $id   = (int)($data['id'] ?? 0);

    if (empty($data['id_interno'])) {
        jsonResponse(['success' => false, 'message' => 'Selecione um interno'], 400); return;
    }
    if (empty($data['descricao'])) {
        jsonResponse(['success' => false, 'message' => 'Descrição é obrigatória'], 400); return;
    }
    if (empty($data['setor_destino'])) {
        jsonResponse(['success' => false, 'message' => 'Setor destino é obrigatório'], 400); return;
    }

    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $campos  = [
        'id_interno'      => (int)$data['id_interno'],
        'ipen'            => (int)($data['ipen'] ?? 0),
        'nome_interno'    => trim($data['nome_interno']    ?? ''),
        'nome_social'     => trim($data['nome_social']     ?? ''),
        'galeria'         => trim($data['galeria']         ?? ''),
        'bloco'           => trim($data['bloco']           ?? ''),
        'res'             => trim($data['res']             ?? ''),
        'setor_destino'   => trim($data['setor_destino']),
        'descricao'       => trim($data['descricao']),
        'status'          => trim($data['status']          ?? 'Pendentes'),
        'categoria'       => trim($data['categoria']       ?? ''),
        'responsavel_nome'=> trim($data['responsavel_nome']?? ''),
        'tarefas'         => !empty($data['tarefas']) ? json_encode($data['tarefas']) : null,
        'atualizado_por'  => $usuario,
        'atualizado_em'   => date('Y-m-d H:i:s'),
    ];

    if ($id > 0) {
        $set  = implode(', ', array_map(fn($k) => "$k = ?", array_keys($campos)));
        $stmt = $pdo->prepare("UPDATE internos_solicitacoes SET $set WHERE id = ?");
        $stmt->execute([...array_values($campos), $id]);
        registrarLog($pdo, $id, 'Atualizado', 'Solicitação atualizada', $usuario);
        jsonResponse(['success' => true, 'message' => 'Solicitação atualizada', 'id' => $id]);
    } else {
        $campos['criado_por'] = $usuario;
        $campos['criado_em']  = date('Y-m-d H:i:s');
        $cols = implode(', ', array_keys($campos));
        $phs  = implode(', ', array_fill(0, count($campos), '?'));
        $stmt = $pdo->prepare("INSERT INTO internos_solicitacoes ($cols) VALUES ($phs)");
        $stmt->execute(array_values($campos));
        $novoId = (int)$pdo->lastInsertId();
        registrarLog($pdo, $novoId, 'Criado', 'Nova solicitação criada', $usuario);
        jsonResponse(['success' => true, 'message' => 'Solicitação criada', 'id' => $novoId]);
    }
}

// ---------------------------------------------------------------------------
// ATUALIZAR STATUS (drag & drop)
// ---------------------------------------------------------------------------
function atualizarStatus(PDO $pdo): void
{
    $data      = jsonBody();
    $id        = (int)($data['id']     ?? 0);
    $novoStatus= trim($data['status']  ?? '');

    $validos = ['Pendentes', 'Em Atendimento', 'Aguardando', 'Atendidas', 'Canceladas'];
    if ($id <= 0 || !in_array($novoStatus, $validos)) {
        jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400); return;
    }

    $stmt = $pdo->prepare('SELECT status FROM internos_solicitacoes WHERE id = ?');
    $stmt->execute([$id]);
    $atual = $stmt->fetchColumn();
    if ($atual === false) {
        jsonResponse(['success' => false, 'message' => 'Não encontrado'], 404); return;
    }

    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $stmt = $pdo->prepare('UPDATE internos_solicitacoes SET status = ?, atualizado_em = NOW(), atualizado_por = ? WHERE id = ?');
    $stmt->execute([$novoStatus, $usuario, $id]);
    registrarLog($pdo, $id, 'Status', "De '{$atual}' para '{$novoStatus}'", $usuario);

    jsonResponse(['success' => true, 'status_anterior' => $atual, 'novo_status' => $novoStatus]);
}

// ---------------------------------------------------------------------------
// CRIAR RÁPIDO
// ---------------------------------------------------------------------------
function criarRapido(PDO $pdo): void
{
    $data = jsonBody();
    if (empty($data['descricao']) || empty($data['setor_destino'])) {
        jsonResponse(['success' => false, 'message' => 'Preencha todos os campos obrigatórios'], 400); return;
    }
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $campos  = [
        'id_interno'    => (int)($data['id_interno']  ?? 0),
        'ipen'          => (int)($data['ipen']         ?? 0),
        'nome_interno'  => trim($data['nome_interno']  ?? ''),
        'nome_social'   => trim($data['nome_social']   ?? ''),
        'setor_destino' => trim($data['setor_destino']),
        'descricao'     => trim($data['descricao']),
        'status'        => trim($data['status']        ?? 'Pendentes'),
        'categoria'     => trim($data['categoria']     ?? ''),
        'criado_por'    => $usuario,
        'atualizado_por'=> $usuario,
        'criado_em'     => date('Y-m-d H:i:s'),
        'atualizado_em' => date('Y-m-d H:i:s'),
    ];
    $cols = implode(', ', array_keys($campos));
    $phs  = implode(', ', array_fill(0, count($campos), '?'));
    $stmt = $pdo->prepare("INSERT INTO internos_solicitacoes ($cols) VALUES ($phs)");
    $stmt->execute(array_values($campos));
    $novoId = (int)$pdo->lastInsertId();
    registrarLog($pdo, $novoId, 'Criado', 'Solicitação criada rápida', $usuario);
    jsonResponse(['success' => true, 'message' => 'Solicitação criada', 'id' => $novoId]);
}

// ---------------------------------------------------------------------------
// DELETAR CARD (soft delete)
// ---------------------------------------------------------------------------
function deletarCard(PDO $pdo): void
{
    $data = jsonBody();
    $id   = (int)($data['id'] ?? 0);
    if ($id <= 0) {
        jsonResponse(['success' => false, 'message' => 'ID inválido'], 400); return;
    }
    $stmt = $pdo->prepare('SELECT id FROM internos_solicitacoes WHERE id = ? AND ativo = "S"');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Não encontrado'], 404); return;
    }
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    $stmt = $pdo->prepare('UPDATE internos_solicitacoes SET ativo = "N", atualizado_em = NOW(), atualizado_por = ? WHERE id = ?');
    $stmt->execute([$usuario, $id]);
    registrarLog($pdo, $id, 'Removido', 'Solicitação removida', $usuario);
    jsonResponse(['success' => true, 'message' => 'Solicitação removida']);
}

// ---------------------------------------------------------------------------
// SALVAR RESPOSTA / COMENTÁRIO
// ---------------------------------------------------------------------------
function salvarResposta(PDO $pdo): void
{
    $data    = jsonBody();
    $id      = (int)($data['id']       ?? 0);
    $resposta= trim($data['resposta']   ?? '');
    if ($id <= 0 || $resposta === '') {
        jsonResponse(['success' => false, 'message' => 'Dados inválidos'], 400); return;
    }
    $usuario = $_SESSION['usuario'] ?? 'sistema';
    registrarLog($pdo, $id, 'Resposta', $resposta, $usuario);
    jsonResponse(['success' => true, 'message' => 'Comentário adicionado']);
}

// ---------------------------------------------------------------------------
// HELPERS
// ---------------------------------------------------------------------------
function jsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return $decoded;
    }
    return $_POST;
}

function buscarLog(PDO $pdo, int $id): array
{
    $stmt = $pdo->prepare(
        'SELECT acao, descricao, usuario, criado_em
         FROM internos_solicitacoes_log
         WHERE solicitacao_id = ?
         ORDER BY criado_em DESC'
    );
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}

function registrarLog(PDO $pdo, int $id, string $acao, string $descricao, string $usuario): void
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO internos_solicitacoes_log (solicitacao_id, acao, descricao, usuario)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$id, $acao, $descricao, $usuario]);
    } catch (Exception $e) {
        // log silencioso para não quebrar o fluxo
    }
}

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
