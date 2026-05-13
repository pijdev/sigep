<?php
// internos_doacao_eletronicos_logica.php - Lógica AJAX para doações

// Debug: Log de TODAS as requisições
file_put_contents(__DIR__ . '/debug_todas_requests.txt', date('Y-m-d H:i:s') . " - REQUEST: " . json_encode($_REQUEST) . "\n", FILE_APPEND);

// Debug: Log inicial para identificar problemas
file_put_contents(__DIR__ . '/debug_request.txt', date('Y-m-d H:i:s') . " - REQUEST: " . print_r($_REQUEST, true) . "\n", FILE_APPEND);

// Desabilitar exibição de erros para não contaminar JSON
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Debug mode for testing
define('DEBUG_MODE', true);

// Verificar se usuário tem acesso
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$eh_admin = (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true);
$eh_censura = (isset($_SESSION['perm_censura']) && $_SESSION['perm_censura'] > 0);
$eh_coord = (isset($_SESSION['perm_coord']) && $_SESSION['perm_coord'] > 0);
$eh_direcao = (isset($_SESSION['perm_direcao']) && $_SESSION['perm_direcao'] > 0);

// Temporarily allow access for testing - REMOVE IN PRODUCTION!
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $eh_admin = true;
}

if (!$eh_admin && !$eh_censura && !$eh_coord && !$eh_direcao) {
    echo json_encode(['status'=>'error', 'msg'=>'Acesso negado']);
    exit;
}

$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error', 'msg'=>'Erro DB: ' . $e->getMessage()]);
    exit;
}

// --- FUNÇÕES AUXILIARES ---
function registrarHistorico($pdo, $acao, $detalhes, $usuario, $id_doacao = null, $id_item = null) {
    $stmt = $pdo->prepare("INSERT INTO internos_doacao_eletronicos_historico (id_doacao, id_item, acao, detalhes, usuario) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id_doacao, $id_item, $acao, $detalhes, $usuario]);
}

// 1. BUSCAR INTERNO PARA DOADOR
if ($_REQUEST['acao'] === 'buscar_interno_doador') {
    header('Content-Type: application/json');
    $termo = trim($_REQUEST['termo']);
    try {
        $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos
                WHERE (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?) AND status = 'A' LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $l = "%$termo%"; $stmt->execute([$l,$l,$l]);
        echo json_encode(['status'=>'success', 'dados'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) { echo json_encode(['error'=>$e->getMessage()]); }
    exit;
}

// 2. BUSCAR INTERNO PARA RECEPTOR
if ($_REQUEST['acao'] === 'buscar_interno_receptor') {
    header('Content-Type: application/json');
    $termo = trim($_REQUEST['termo']);
    $id_doador = (int)$_REQUEST['id_doador'];
    try {
        $sql = "SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos
                WHERE (ipen LIKE ? OR nome LIKE ? OR nome_social LIKE ?) AND status = 'A' AND ipen != ? LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $l = "%$termo%"; $stmt->execute([$l,$l,$l,$id_doador]);
        echo json_encode(['status'=>'success', 'dados'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) { echo json_encode(['error'=>$e->getMessage()]); }
    exit;
}

// 3. CARREGAR BLOCOS DO RECEPTOR
if ($_REQUEST['acao'] === 'carregar_blocos_receptor') {
    header('Content-Type: application/json');
    $galeria = $_REQUEST['galeria'];
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT bloco FROM internos WHERE galeria = ? AND status = 'A' ORDER BY bloco");
        $stmt->execute([$galeria]);
        echo json_encode(['status'=>'success', 'blocos'=>$stmt->fetchAll(PDO::FETCH_COLUMN)]);
    } catch (Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }
    exit;
}

// 4. CARREGAR CELAS PARA RECEPTOR
if ($_REQUEST['acao'] === 'carregar_celas_receptor') {
    header('Content-Type: application/json');
    $galeria = $_REQUEST['galeria'];
    $bloco = $_REQUEST['bloco'];
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT res FROM internos WHERE galeria = ? AND bloco = ? AND status = 'A' ORDER BY res");
        $stmt->execute([$galeria, $bloco]);
        $celas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['status'=>'success', 'celas'=>$celas]);
    } catch (Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }
    exit;
}

// 5. CARREGAR ITENS DO DOADOR
if ($_REQUEST['acao'] === 'carregar_itens_doador') {
    header('Content-Type: application/json');
    $id_doador = (int)$_REQUEST['id_doador'];
    try {
        // Buscar itens ativos na cela do doador (não doados anteriormente)
        // Adicionada validação para itens já doados
        $stmt = $pdo->prepare("
            SELECT e.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res,
                   CASE WHEN EXISTS(
                       SELECT 1 FROM internos_doacao_eletronicos_itens d
                       WHERE d.id_eletronico_original = e.id
                   ) THEN 1 ELSE 0 END as ja_doado
            FROM internos_eletronicos e
            JOIN internos i ON e.id_interno = i.ipen
            WHERE e.id_interno = ? AND e.situacao != 'Retirado'
            ORDER BY e.tipo_item
        ");
        $stmt->execute([$id_doador]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['status'=>'success', 'itens'=>$itens]);
    } catch (Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }
    exit;
}

// 6. PROCESSAR DOAÇÃO
// Verificar se acao está vindo via POST (form) ou via JSON body
$acao = $_REQUEST['acao'] ?? null;
if (!$acao && $_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
    $json_input = json_decode(file_get_contents('php://input'), true);
    $acao = $json_input['acao'] ?? null;
}

if ($acao === 'processar_doacao') {
    ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    try {
        $input_raw = file_get_contents('php://input');

        // Se não houver input, retornar erro
        if (empty($input_raw)) {
            echo json_encode(['status'=>'error', 'msg'=>'Nenhum dado recebido']);
            exit;
        }

        $dados = json_decode($input_raw, true);

        // Validar se dados foram recebidos
        if (!$dados || !isset($dados['id_doador']) || !isset($dados['itens_ids'])) {
            echo json_encode(['status'=>'error', 'msg'=>'Dados incompletos recebidos']);
            exit;
        }

        // Validar chaves esperadas
        $chaves_esperadas = ['id_doador', 'tipo_receptor', 'itens_ids', 'confirmacao_ja_doados'];
        $chaves_faltando = [];

        foreach ($chaves_esperadas as $chave) {
            if (!isset($dados[$chave])) {
                $chaves_faltando[] = $chave;
            }
        }

        if (!empty($chaves_faltando)) {
            echo json_encode(['status'=>'error', 'msg'=>'Campos obrigatórios faltando: ' . implode(', ', $chaves_faltando)]);
            exit;
        }

        $id_doador = (int)$dados['id_doador'];
        $tipo_receptor = $dados['tipo_receptor'];
        $itens_ids = $dados['itens_ids'];
        $confirmacao_ja_doados = $dados['confirmacao_ja_doados'] ?? false;

        // Validar itens_ids
        if (!is_array($itens_ids) || empty($itens_ids)) {
            echo json_encode(['status'=>'error', 'msg'=>'Nenhum item selecionado para doação']);
            exit;
        }

        $pdo->beginTransaction();

        // Inserir registro da doação
        $stmtInsert = $pdo->prepare("
            INSERT INTO internos_doacao_eletronicos
            (id_doador, id_receptor, galeria_receptor, bloco_receptor, cela_receptor, tipo_receptor, usuario_cadastro)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $params = [$id_doador, null, null, null, null, $tipo_receptor, $usuario_logado];

        if ($tipo_receptor === 'CELA') {
            $params[2] = $dados['galeria_receptor']; // galeria_receptor
            $params[3] = $dados['bloco_receptor'];   // bloco_receptor
            $params[4] = $dados['cela_receptor'];    // cela_receptor
        } else {
            $params[1] = (int)$dados['id_receptor']; // id_receptor
        }

        $stmtInsert->execute($params);
        $id_doacao = $pdo->lastInsertId();

        // Registrar itens da doação
        $stmtInsertItem = $pdo->prepare("
            INSERT INTO internos_doacao_eletronicos_itens
            (id_doacao, id_eletronico_original, tipo_item, marca_modelo, cor, estado_conservacao, nota_fiscal)
            SELECT ?, id, tipo_item, marca_modelo, cor, estado_conservacao, nota_fiscal
            FROM internos_eletronicos WHERE id = ?
        ");

        foreach ($itens_ids as $item_id) {
            $stmtInsertItem->execute([$id_doacao, $item_id]);
        }

        // Verificar se há itens já doados (validação adicional)
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) as ja_doados
            FROM internos_doacao_eletronicos_itens
            WHERE id_doacao != ? AND id_eletronico_original IN (" . str_repeat('?,', count($itens_ids)-1) . "?)
        ");
        $check_params = array_merge([$id_doacao], $itens_ids);
        $stmtCheck->execute($check_params);
        $ja_doados = $stmtCheck->fetch(PDO::FETCH_ASSOC)['ja_doados'];

        if ($ja_doados > 0) {
            // Se há itens já doados, mas usuário confirmou, prosseguir com aviso
            if (!isset($dados['confirmacao_ja_doados']) || $dados['confirmacao_ja_doados'] !== true) {
                throw new Exception("Alguns itens já foram doados anteriormente. Confirme se deseja prosseguir.");
            }
        }

        // Atualizar situação dos itens originais para "Estoque" (aguardando aprovação)
        $placeholders = str_repeat('?,', count($itens_ids)-1) . '?';
        $stmtUpdate = $pdo->prepare("UPDATE internos_eletronicos SET situacao = 'Estoque' WHERE id IN ($placeholders)");
        $stmtUpdate->execute($itens_ids);

        // ITENS SÃO TRANSFERIDOS APENAS APÓS APROVAÇÃO
        // Verifique a função aprovar_doacao abaixo

        // Registrar histórico
        registrarHistorico($pdo, 'DOACAO_CRIADA', "Doação criada com " . count($itens_ids) . " itens", $usuario_logado, $id_doacao);

        foreach ($itens_ids as $item_id) {
            registrarHistorico($pdo, 'ITEM_DOADO', "Item transferido para doação", $usuario_logado, $id_doacao, $item_id);
        }

        $pdo->commit();

        $response = [
            'status'=>'success',
            'msg'=>'Doação processada com sucesso!',
            'id_doacao'=>$id_doacao,
            'itens_doacao'=>$itens_ids
        ];

        // Limpar output buffer e enviar JSON
        ob_clean();
        echo json_encode($response);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollback();
        }

        $error_response = ['status'=>'error', 'msg'=>$e->getMessage()];

        // Limpar output buffer e enviar JSON
        ob_clean();
        echo json_encode($error_response);
    }
    exit;
}
    // Debug: Log de TODAS as requisições
    file_put_contents(__DIR__ . '/debug_todas_requests.txt', date('Y-m-d H:i:s') . " - REQUEST: " . json_encode($_REQUEST) . "\n", FILE_APPEND);

    // Verificação adicional: evitar requisições vazias ou duplicadas
    if (empty($_REQUEST) || !isset($_REQUEST['acao'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Requisição inválida ou vazia']);
        exit;
    }

    // Debug simples
    error_log("DEBUG: aprovar_doacao chamado - " . date('Y-m-d H:i:s'));
    error_log("DEBUG: REQUEST_METHOD = " . $_SERVER['REQUEST_METHOD']);
    error_log("DEBUG: CONTENT_TYPE = " . ($_SERVER['CONTENT_TYPE'] ?? 'NÃO DEFINIDO'));
    error_log("DEBUG: php://input = " . file_get_contents('php://input'));

    ob_clean(); // Limpar qualquer output anterior
    header('Content-Type: application/json');


    // Ler dados JSON do corpo da requisição
    $json_input = file_get_contents('php://input');

    // Fallback para testes ou ambientes sem php://input
    if (empty($json_input) && isset($_POST['id_doacao'])) {
        $dados = $_POST;
    } else {
        $dados = json_decode($json_input, true);
    }

    if (!$dados || !isset($dados['id_doacao'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Dados inválidos']);
        exit;
    }

    $id_doacao = (int)$dados['id_doacao'];

    // Debug: Log antes do try
    error_log("DEBUG: Antes do try - dados recebidos: " . json_encode($dados));

    try {
        $pdo->beginTransaction();

        // Buscar dados da doação
        $stmt = $pdo->prepare("
            SELECT d.*, i_doador.nome as nome_doador, i_receptor.nome as nome_receptor
            FROM internos_doacao_eletronicos d
            LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
            LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
            WHERE d.id = ?
        ");
        $stmt->execute([$id_doacao]);
        $doacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doacao) {
            throw new Exception("Doação não encontrada");
        }

        if ($doacao['status'] !== 'Pendente') {
            throw new Exception("Esta doação já foi processada. Status atual: {$doacao['status']}");
        }

        // Buscar itens da doação
        $stmtItens = $pdo->prepare("
            SELECT di.*, e.tipo_item, e.marca_modelo, e.cor, e.estado_conservacao, e.nota_fiscal,
                   e.polegadas, e.tem_controle, e.tem_fonte, e.tamanho, e.capacidade,
                   e.comprimento, e.nome_item_personalizado, e.descricao_personalizada
            FROM internos_doacao_eletronicos_itens di
            JOIN internos_eletronicos e ON di.id_eletronico_original = e.id
            WHERE di.id_doacao = ?
        ");
        $stmtItens->execute([$id_doacao]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        if (empty($itens)) {
            throw new Exception("Nenhum item encontrado nesta doação");
        }

        // Atualizar status da doação
        $stmtUpdateDoacao = $pdo->prepare("
            UPDATE internos_doacao_eletronicos
            SET status = 'Aprovado', data_aprovacao = NOW()
            WHERE id = ?
        ");
        $stmtUpdateDoacao->execute([$id_doacao]);

        // Transferir itens para o receptor (se for interno)
        if ($doacao['tipo_receptor'] === 'INTERNO' && $doacao['id_receptor']) {
            foreach ($itens as $item) {
                // Inserir item para o receptor
                $stmtInsertReceptor = $pdo->prepare("
                    INSERT INTO internos_eletronicos
                    (id_interno, tipo_item, marca_modelo, cor, estado_conservacao, nota_fiscal,
                     data_entrada, entregue_por, cadastrado_por, situacao,
                     polegadas, tem_controle, tem_fonte, tamanho, capacidade,
                     comprimento, nome_item_personalizado, descricao_personalizada)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, 'Na Cela',
                            ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmtInsertReceptor->execute([
                    $doacao['id_receptor'],
                    $item['tipo_item'],
                    $item['marca_modelo'],
                    $item['cor'],
                    $item['estado_conservacao'],
                    $item['nota_fiscal'],
                    $doacao['id_doador'], // entregue_por
                    $usuario_logado, // cadastrado_por
                    $item['polegadas'],
                    $item['tem_controle'],
                    $item['tem_fonte'],
                    $item['tamanho'],
                    $item['capacidade'],
                    $item['comprimento'],
                    $item['nome_item_personalizado'],
                    $item['descricao_personalizada']
                ]);

                $id_novo_item = $pdo->lastInsertId();

                // Atualizar tabela de itens da doação com o ID do novo item transferido
                $stmtUpdateItemDoacao = $pdo->prepare("
                    UPDATE internos_doacao_eletronicos_itens
                    SET id_eletronico_transferido = ?
                    WHERE id_doacao = ? AND id_eletronico_original = ?
                ");
                $stmtUpdateItemDoacao->execute([$id_novo_item, $id_doacao, $item['id_eletronico_original']]);

                // Marcar item original como Retirado
                $stmtUpdateOriginal = $pdo->prepare("
                    UPDATE internos_eletronicos
                    SET situacao = 'Retirado'
                    WHERE id = ?
                ");
                $stmtUpdateOriginal->execute([$item['id_eletronico_original']]);

                // Registrar histórico
                registrarHistorico($pdo, 'ITEM_TRANSFERIDO',
                    "Item {$item['tipo_item']} transferido para receptor {$doacao['id_receptor']}",
                    $usuario_logado, $id_doacao, $item['id_eletronico_original']);
            }
        }

        // Registrar histórico da aprovação
        registrarHistorico($pdo, 'DOACAO_APROVADA',
            "Doação aprovada e itens transferidos",
            $usuario_logado, $id_doacao);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'msg' => 'Doação aprovada com sucesso! Itens transferidos.'
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }

        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;


// 10. CANCELAR DOAÇÃO
if ($_REQUEST['acao'] === 'cancelar_doacao') {
    header('Content-Type: application/json');

    // Ler dados JSON do corpo da requisição
    $json_input = file_get_contents('php://input');
    $dados = json_decode($json_input, true);

    if (!$dados || !isset($dados['id_doacao']) || !isset($dados['motivo'])) {
        echo json_encode(['status' => 'error', 'msg' => 'Dados inválidos']);
        exit;
    }

    $id_doacao = (int)$dados['id_doacao'];
    $motivo = $dados['motivo'];

    try {
        $pdo->beginTransaction();

        // Buscar dados da doação
        $stmt = $pdo->prepare("
            SELECT d.*, i_doador.nome as nome_doador
            FROM internos_doacao_eletronicos d
            LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
            WHERE d.id = ?
        ");
        $stmt->execute([$id_doacao]);
        $doacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doacao) {
            throw new Exception("Doação não encontrada");
        }

        if ($doacao['status'] !== 'Pendente') {
            throw new Exception("Esta doação já foi processada. Status atual: {$doacao['status']}");
        }

        // Buscar itens da doação
        $stmtItens = $pdo->prepare("
            SELECT di.id_eletronico_original
            FROM internos_doacao_eletronicos_itens di
            WHERE di.id_doacao = ?
        ");
        $stmtItens->execute([$id_doacao]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Atualizar status da doação
        $stmtUpdateDoacao = $pdo->prepare("
            UPDATE internos_doacao_eletronicos
            SET status = 'Cancelado', motivo_cancelamento = ?, data_cancelamento = NOW()
            WHERE id = ?
        ");
        $stmtUpdateDoacao->execute([$motivo, $id_doacao]);

        // Devolver itens para o doador
        foreach ($itens as $item) {
            $stmtUpdateOriginal = $pdo->prepare("
                UPDATE internos_eletronicos
                SET situacao = 'Na Cela'
                WHERE id = ?
            ");
            $stmtUpdateOriginal->execute([$item['id_eletronico_original']]);

            // Registrar histórico
            registrarHistorico($pdo, 'ITEM_DEVOLVIDO',
                "Item devolvido para doador devido a cancelamento",
                $usuario_logado, $id_doacao, $item['id_eletronico_original']);
        }

        // Registrar histórico do cancelamento
        registrarHistorico($pdo, 'DOACAO_CANCELADA',
            "Doação cancelada: $motivo",
            $usuario_logado, $id_doacao);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'msg' => 'Doação cancelada com sucesso! Itens devolvidos.'
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollback();
        }

        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// 11. BUSCAR HISTÓRICO COMPLETO COM RASTREABILIDADE
if ($_REQUEST['acao'] === 'buscar_historico_completo') {
    header('Content-Type: application/json');

    try {
        // Parâmetros de filtro
        $data_inicio = $_POST['data_inicio'] ?? null;
        $data_fim = $_POST['data_fim'] ?? null;
        $acao = $_POST['acao_filtro'] ?? null;
        $id_doacao = $_POST['id_doacao'] ?? null;
        $id_item = $_POST['id_item'] ?? null;
        $usuario = $_POST['usuario'] ?? null;

        // Construir WHERE
        $where = [];
        $params = [];

        if ($data_inicio) {
            $where[] = "h.data_hora >= ?";
            $params[] = $data_inicio . ' 00:00:00';
        }

        if ($data_fim) {
            $where[] = "h.data_hora <= ?";
            $params[] = $data_fim . ' 23:59:59';
        }

        if ($acao) {
            $where[] = "h.acao = ?";
            $params[] = $acao;
        }

        if ($id_doacao) {
            $where[] = "h.id_doacao = ?";
            $params[] = $id_doacao;
        }

        if ($id_item) {
            $where[] = "h.id_item = ?";
            $params[] = $id_item;
        }

        if ($usuario) {
            $where[] = "h.usuario LIKE ?";
            $params[] = "%$usuario%";
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Query principal com rastreabilidade completa
        $sql = "
            SELECT
                h.id,
                h.id_doacao,
                h.id_item,
                h.acao,
                h.detalhes,
                h.usuario,
                h.data_hora,
                COALESCE(e.tipo_item, 'N/A') as tipo_item,
                COALESCE(e.marca_modelo, '') as marca_modelo,
                d.id_doador,
                COALESCE(i_doador.nome, 'N/A') as nome_doador,
                d.id_receptor,
                COALESCE(i_receptor.nome, 'N/A') as nome_receptor,
                d.tipo_receptor,
                d.galeria_receptor,
                d.bloco_receptor,
                d.cela_receptor,
                CASE
                    WHEN h.acao = 'ITEM_DOADO' THEN CONCAT('IPEN ', d.id_doador, ' (', COALESCE(i_doador.nome, 'N/A'), ')')
                    WHEN h.acao IN ('ITEM_TRANSFERIDO', 'DOACAO_APROVADA') THEN
                        CASE
                            WHEN d.tipo_receptor = 'INTERNO' THEN CONCAT('IPEN ', d.id_receptor, ' (', COALESCE(i_receptor.nome, 'N/A'), ')')
                            ELSE CONCAT('Cela ', d.galeria_receptor, '-', d.bloco_receptor, '-', d.cela_receptor)
                        END
                    WHEN h.acao = 'ITEM_DEVOLVIDO' THEN CONCAT('IPEN ', d.id_doador, ' (', COALESCE(i_doador.nome, 'N/A'), ')')
                    ELSE 'N/A'
                END as origem_destino,
                CASE
                    WHEN h.acao = 'ITEM_DOADO' THEN 'Doação'
                    WHEN h.acao = 'ITEM_TRANSFERIDO' THEN 'Recebimento'
                    WHEN h.acao = 'ITEM_DEVOLVIDO' THEN 'Devolução'
                    WHEN h.acao = 'DOACAO_CRIADA' THEN 'Criação'
                    WHEN h.acao = 'DOACAO_APROVADA' THEN 'Aprovação'
                    WHEN h.acao = 'DOACAO_CANCELADA' THEN 'Cancelamento'
                    WHEN h.acao = 'TERMO_ASSINADO' THEN 'Assinatura'
                    ELSE h.acao
                END as tipo_movimento
            FROM internos_doacao_eletronicos_historico h
            LEFT JOIN internos_doacao_eletronicos d ON h.id_doacao = d.id
            LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
            LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
            LEFT JOIN internos_eletronicos e ON h.id_item = e.id
            $where_clause
            ORDER BY h.data_hora DESC, h.id DESC
            LIMIT 1000
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $historico,
            'total' => count($historico)
        ]);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// 7. ASSINAR TERMO
if ($_REQUEST['acao'] === 'assinar_termo') {
    header('Content-Type: application/json');
    $id_doacao = (int)$_POST['id_doacao'];

    try {
        $stmt = $pdo->prepare("UPDATE internos_doacao_eletronicos SET termo_assinado = TRUE WHERE id = ?");
        $stmt->execute([$id_doacao]);

        registrarHistorico($pdo, 'TERMO_ASSINADO', "Termo de doação assinado", $usuario_logado, $id_doacao);

        echo json_encode(['status'=>'success', 'msg'=>'Termo assinado com sucesso!']);
    } catch (Exception $e) { echo json_encode(['status'=>'error', 'msg'=>$e->getMessage()]); }
    exit;
}

// 8. VER DETALHES DA DOAÇÃO
if ($_REQUEST['acao'] === 'ver_detalhes_doacao') {
    header('Content-Type: application/json');
    $id_doacao = (int)$_REQUEST['id_doacao'];

    try {
        // Buscar dados da doação
        $stmt = $pdo->prepare("
            SELECT d.*,
                   i_doador.nome as nome_doador, i_doador.nome_social as nome_social_doador,
                   i_doador.galeria as galeria_doador, i_doador.bloco as bloco_doador, i_doador.res as cela_doador,
                   i_receptor.nome as nome_receptor, i_receptor.nome_social as nome_social_receptor,
                   i_receptor.galeria as galeria_receptor_interno, i_receptor.bloco as bloco_receptor_interno, i_receptor.res as cela_receptor_interno
            FROM internos_doacao_eletronicos d
            LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
            LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
            WHERE d.id = ?
        ");
        $stmt->execute([$id_doacao]);
        $doacao = $stmt->fetch(PDO::FETCH_ASSOC);

        // Buscar itens da doação
        $stmtItens = $pdo->prepare("
            SELECT di.*, e.tipo_item, e.marca_modelo, e.cor, e.estado_conservacao, e.nota_fiscal
            FROM internos_doacao_eletronicos_itens di
            JOIN internos_eletronicos e ON di.id_eletronico_original = e.id
            WHERE di.id_doacao = ?
            ORDER BY e.tipo_item
        ");
        $stmtItens->execute([$id_doacao]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Montar descrição dos itens
        $descricao_itens = '';
        foreach ($itens as $item) {
            $descricao_itens .= $item['tipo_item'] . ' - ' . ($item['marca_modelo'] ?: 'Sem marca') . ' (' . ($item['cor'] ?: 'Sem cor') . ")\n";
        }

        // Retornar dados para processamento no frontend
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'doacao' => $doacao,
            'itens' => $itens,
            'descricao_itens' => $descricao_itens,
            'url_termo' => 'paginas/internos_doacao_eletronicos_logica.php?acao=gerar_termo_html&id_doacao=' . $id_doacao
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
    exit;
}

// 12. GERAR TERMO DE DOAÇÃO (HTML)
if ($_REQUEST['acao'] === 'gerar_termo_html') {
    $id_doacao = (int)$_REQUEST['id_doacao'];

    try {
        // Buscar dados completos da doação
        $stmt = $pdo->prepare("
            SELECT d.*,
                   i_doador.nome as nome_doador, i_doador.nome_social as nome_social_doador,
                   i_doador.galeria as galeria_doador, i_doador.bloco as bloco_doador, i_doador.res as cela_doador,
                   i_receptor.nome as nome_receptor, i_receptor.nome_social as nome_social_receptor,
                   i_receptor.galeria as galeria_receptor, i_receptor.bloco as bloco_receptor, i_receptor.res as cela_receptor
            FROM internos_doacao_eletronicos d
            LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
            LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
            WHERE d.id = ?
        ");
        $stmt->execute([$id_doacao]);
        $doacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doacao) {
            throw new Exception("Doação não encontrada");
        }

        // Buscar itens da doação
        $stmtItens = $pdo->prepare("
            SELECT di.*, e.tipo_item, e.marca_modelo, e.cor, e.estado_conservacao, e.nota_fiscal
            FROM internos_doacao_eletronicos_itens di
            JOIN internos_eletronicos e ON di.id_eletronico_original = e.id
            WHERE di.id_doacao = ?
            ORDER BY e.tipo_item
        ");
        $stmtItens->execute([$id_doacao]);
        $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

        // Montar descrição dos itens
        $descricao_itens = '';
        foreach ($itens as $item) {
            $descricao_itens .= $item['tipo_item'] . ' - ' . ($item['marca_modelo'] ?: 'Sem marca') . ' (' . ($item['cor'] ?: 'Sem cor') . ")\n";
        }

        // Carregar o template do termo
        ob_start();
        include __DIR__ . '/../modulos/censura/doacao/termo_doacao_censura.php';
        $template = ob_get_clean();

        // Substituir placeholders no template
        $template = str_replace('<span class="input-fill" style="width: 75%;"></span>', htmlspecialchars($descricao_itens), $template);
        $template = str_replace('<span class="input-fill" style="width: 75%;"></span>', htmlspecialchars($doacao['nome_social_doador'] ?: $doacao['nome_doador']), $template);
        $template = str_replace('<span class="input-fill" style="width: 150px;"></span>', $doacao['id_doador'], $template);
        $template = str_replace('<span class="input-fill" style="width: 100px;"></span>', $doacao['galeria_doador'], $template);
        $template = str_replace('<span class="input-fill" style="width: 100px;"></span>', $doacao['cela_doador'], $template);

        echo $template;
    } catch (Exception $e) {
        echo "Erro ao gerar termo: " . $e->getMessage();
    }
    exit;
}
?>
