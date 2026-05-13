<?php
// Lógica de backend para Gestão de Colchões
// Configuração da conexão
$config = require __DIR__ . '/../conf/db.php';
$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $config['host'],
    $config['dbname'],
    $config['charset']
);

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
    exit;
}

// Verificar e iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) session_start();

// Verificar sessão e permissão
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada.']);
    exit;
}

// Verificar permissão do setor Censura
if (!isset($_SESSION['user_admin']) || $_SESSION['user_admin'] != 1) {
    if (!isset($_SESSION['perm_censura']) || ($_SESSION['perm_censura'] != 1 && $_SESSION['perm_censura'] != 2)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
        exit;
    }
}

// Obter ação da requisição
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_locais':
        getLocais($pdo);
        break;
    case 'get_estoque':
        getEstoque($pdo);
        break;
    case 'get_todos_internos':
        getTodosInternos($pdo);
        break;
    case 'get_internos_aptos':
        getInternosAptos($pdo);
        break;
    case 'registrar_entrada':
        registrarEntrada($pdo);
        break;
    case 'registrar_saida':
        registrarSaida($pdo);
        break;
    case 'get_local':
        getLocal($pdo);
        break;
    case 'criar_local':
        criarLocal($pdo);
        break;
    case 'atualizar_local':
        atualizarLocal($pdo);
        break;
    case 'excluir_local':
        excluirLocal($pdo);
        break;
    case 'get_historico':
        getHistorico($pdo);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
        break;
}

if (!empty($action)) exit;

/**
 * Obter locais de estoque ativos
 */
function getLocais($pdo)
{
    try {
        $sql = "
            SELECT
                l.id,
                l.nome,
                l.descricao,
                l.tipo,
                l.capacidade_maxima,
                l.status,
                COALESCE(e.quantidade, 0) as quantidade
            FROM internos_colchoes_locais l
            LEFT JOIN internos_colchoes_estoque e ON l.id = e.id_local
            WHERE l.status = 'Ativo'
            ORDER BY l.nome
        ";

        $stmt = $pdo->query($sql);
        $locais = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'locais' => $locais]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar locais: ' . $e->getMessage()]);
    }
}

/**
 * Obter estoque atual por local
 */
function getEstoque($pdo)
{
    try {
        $sql = "
            SELECT
                l.id,
                l.nome,
                l.descricao,
                l.tipo,
                l.capacidade_maxima,
                l.status,
                COALESCE(e.quantidade, 0) as quantidade,
                e.ultima_atualizacao
            FROM internos_colchoes_locais l
            LEFT JOIN internos_colchoes_estoque e ON l.id = e.id_local
            WHERE l.status = 'Ativo'
            ORDER BY l.nome
        ";

        $stmt = $pdo->query($sql);
        $estoque = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'estoque' => $estoque]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar estoque: ' . $e->getMessage()]);
    }
}

/**
 * Obter todos os internos ativos com indicação de aptidão
 */
function getTodosInternos($pdo)
{
    try {
        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.galeria,
                i.bloco,
                i.ala,
                i.res,
                i.data_ativo,
                MAX(s.data_saida) as ultimo_colchao,
                CASE
                    WHEN i.data_ativo IS NULL OR i.data_ativo > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 0
                    WHEN MAX(s.data_saida) IS NULL THEN 1
                    WHEN MAX(s.data_saida) <= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 1
                    ELSE 0
                END as apto,
                CASE
                    WHEN i.data_ativo IS NULL OR i.data_ativo > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Menos de 1 ano de cadeia'
                    WHEN MAX(s.data_saida) IS NULL THEN 'Nunca recebeu colchão'
                    WHEN MAX(s.data_saida) > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Recebeu colchão há menos de 1 ano'
                    ELSE 'Apto'
                END as motivo_inaptidao
            FROM internos i
            LEFT JOIN internos_colchoes_saidas s ON i.ipen = s.id_interno
                AND s.status = 'Ativo'
                AND s.tipo_destino = 'Interno'
            WHERE i.status = 'A'
                AND i.data_ativo IS NOT NULL
            GROUP BY i.ipen, i.nome, i.galeria, i.bloco, i.ala, i.res, i.data_ativo
            ORDER BY i.nome
        ";

        $stmt = $pdo->query($sql);
        $internos = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'internos' => $internos]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar internos: ' . $e->getMessage()]);
    }
}

/**
 * Obter internos aptos a receber colchões
 * Regra: 1 ano de cadeia E 1 ano desde última entrega
 */
function getInternosAptos($pdo)
{
    try {
        $sql = "
            SELECT
                i.ipen,
                i.nome,
                i.galeria,
                i.bloco,
                i.ala,
                i.res,
                i.data_ativo,
                MAX(s.data_saida) as ultimo_colchao,
                CASE
                    WHEN i.data_ativo IS NULL OR i.data_ativo > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Menos de 1 ano de cadeia'
                    WHEN MAX(s.data_saida) IS NULL THEN 'Nunca recebeu colchão'
                    WHEN MAX(s.data_saida) > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Recebeu colchão há menos de 1 ano'
                    ELSE 'Apto'
                END as motivo_inaptidao
            FROM internos i
            LEFT JOIN internos_colchoes_saidas s ON i.ipen = s.id_interno
                AND s.status = 'Ativo'
                AND s.tipo_destino = 'Interno'
            WHERE i.status = 'A'
                AND i.data_ativo IS NOT NULL
                AND i.data_ativo <= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
            GROUP BY i.ipen, i.nome, i.galeria, i.bloco, i.ala, i.res, i.data_ativo
            HAVING motivo_inaptidao = 'Apto'
            ORDER BY i.nome
        ";

        $stmt = $pdo->query($sql);
        $internos = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'internos' => $internos]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar internos aptos: ' . $e->getMessage()]);
    }
}

/**
 * Registrar entrada de colchões
 */
function registrarEntrada($pdo)
{
    try {
        // Validar dados
        $dataEntrada = $_POST['data_entrada'] ?? '';
        $quantidade = intval($_POST['quantidade'] ?? 0);
        $origem = $_POST['origem'] ?? '';
        $documentoRef = $_POST['documento_referencia'] ?? '';
        $idLocalDestino = intval($_POST['id_local_destino'] ?? 0);
        $observacoes = $_POST['observacoes'] ?? '';

        if (empty($dataEntrada) || $quantidade <= 0 || empty($origem) || $idLocalDestino <= 0) {
            throw new Exception('Dados obrigatórios não preenchidos.');
        }

        // Verificar se o local existe e está ativo
        $stmt = $pdo->prepare("SELECT id FROM internos_colchoes_locais WHERE id = ? AND status = 'Ativo'");
        $stmt->execute([$idLocalDestino]);
        if (!$stmt->fetch()) {
            throw new Exception('Local de destino inválido ou inativo.');
        }

        // Iniciar transação
        $pdo->beginTransaction();

        // Inserir registro de entrada (o trigger cuida do estoque)
        $sql = "
            INSERT INTO internos_colchoes_entradas
            (data_entrada, quantidade, origem, documento_referencia, id_local_destino, observacoes, cadastrado_por, data_cadastro)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dataEntrada,
            $quantidade,
            $origem,
            $documentoRef,
            $idLocalDestino,
            $observacoes,
            $_SESSION['user_id']
        ]);

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Entrada registrada com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Registrar saída de colchões
 */
function registrarSaida($pdo)
{
    try {
        // Validar dados
        $dataSaida = $_POST['data_saida'] ?? '';
        $quantidade = intval($_POST['quantidade'] ?? 0);
        $tipoDestino = $_POST['tipo_destino'] ?? '';
        $idLocalOrigem = intval($_POST['id_local_origem'] ?? 0);
        $idInterno = !empty($_POST['id_interno']) ? intval($_POST['id_interno']) : null;
        $destinoOutro = $_POST['destino_outro'] ?? '';
        $motivoSaida = $_POST['motivo_saida'] ?? '';
        $observacoes = $_POST['observacoes'] ?? '';

        if (empty($dataSaida) || $quantidade <= 0 || empty($tipoDestino) || $idLocalOrigem <= 0 || empty($motivoSaida)) {
            throw new Exception('Dados obrigatórios não preenchidos.');
        }

        // Validações específicas por tipo de destino
        if ($tipoDestino === 'Interno' && (!$idInterno || $idInterno <= 0)) {
            throw new Exception('Interno não selecionado.');
        }

        if ($tipoDestino === 'Outro' && empty($destinoOutro)) {
            throw new Exception('Destino (Outro) não informado.');
        }

        // Verificar se o local existe e está ativo
        $stmt = $pdo->prepare("SELECT id FROM internos_colchoes_locais WHERE id = ? AND status = 'Ativo'");
        $stmt->execute([$idLocalOrigem]);
        if (!$stmt->fetch()) {
            throw new Exception('Local de origem inválido ou inativo.');
        }

        // Verificar se há estoque suficiente
        $stmt = $pdo->prepare("SELECT quantidade FROM internos_colchoes_estoque WHERE id_local = ?");
        $stmt->execute([$idLocalOrigem]);
        $estoque = $stmt->fetch();

        if (!$estoque || $estoque['quantidade'] < $quantidade) {
            throw new Exception('Estoque insuficiente no local selecionado.');
        }

        // Se for para interno, verificar se está apto
        if ($tipoDestino === 'Interno') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as apto
                FROM internos i
                LEFT JOIN internos_colchoes_saidas s ON i.ipen = s.id_interno
                    AND s.status = 'Ativo'
                    AND s.tipo_destino = 'Interno'
                WHERE i.ipen = ?
                    AND i.status = 'A'
                    AND i.data_ativo IS NOT NULL
                    AND i.data_ativo <= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                    AND (s.data_saida IS NULL OR s.data_saida <= DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
            ");
            $stmt->execute([$idInterno]);
            $apto = $stmt->fetch();

            if (!$apto || $apto['apto'] == 0) {
                throw new Exception('Interno não está apto a receber colchão (verifique tempo de cadeia e última entrega).');
            }
        }

        // Iniciar transação
        $pdo->beginTransaction();

        // Inserir registro de saída
        $sql = "
            INSERT INTO internos_colchoes_saidas
            (data_saida, quantidade, tipo_destino, id_local_origem, id_interno, destino_outro, motivo_saida, observacoes, cadastrado_por, data_cadastro, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Ativo')
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $dataSaida,
            $quantidade,
            $tipoDestino,
            $idLocalOrigem,
            $idInterno,
            $destinoOutro,
            $motivoSaida,
            $observacoes,
            $_SESSION['user_id']
        ]);

        $idSaida = $pdo->lastInsertId();

        // Atualizar estoque
        $sql = "
            UPDATE internos_colchoes_estoque
            SET quantidade = GREATEST(0, quantidade - ?),
                ultima_atualizacao = NOW(),
                atualizado_por = ?
            WHERE id_local = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$quantidade, $_SESSION['user_id'], $idLocalOrigem]);

        // Se for para interno, registrar entrega
        if ($tipoDestino === 'Interno') {
            $sql = "
                INSERT INTO internos_colchoes_entregas
                (id_interno, id_saida, data_entrega, status, cadastrado_por, data_cadastro)
                VALUES (?, ?, ?, 'Entregue', ?, NOW())
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$idInterno, $idSaida, $dataSaida, $_SESSION['user_id']]);
        }

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Saída registrada com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obter histórico de movimentações
 */
function getHistorico($pdo)
{
    try {
        $tipo = $_GET['tipo'] ?? '';
        $dataInicio = $_GET['data_inicio'] ?? '';
        $dataFim = $_GET['data_fim'] ?? '';

        // Montar SQL para entradas
        $sqlEntradas = "
            SELECT
                e.data_entrada as data,
                'Entrada' as tipo,
                e.quantidade,
                l.nome as local_destino,
                e.origem as destino,
                u.nome as responsavel,
                e.observacoes
            FROM internos_colchoes_entradas e
            INNER JOIN internos_colchoes_locais l ON e.id_local_destino = l.id
            LEFT JOIN users u ON e.cadastrado_por = u.id
            WHERE 1=1
        ";

        $params = [];

        if (!empty($dataInicio)) {
            $sqlEntradas .= " AND e.data_entrada >= ?";
            $params[] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $sqlEntradas .= " AND e.data_entrada <= ?";
            $params[] = $dataFim;
        }

        // Montar SQL para saídas
        $sqlSaidas = "
            SELECT
                s.data_saida as data,
                'Saida' as tipo,
                s.quantidade,
                lo.nome as local_origem,
                CASE
                    WHEN s.tipo_destino = 'Interno' THEN CONCAT('Interno: ', i.nome, ' (', i.ipen, ')')
                    WHEN s.tipo_destino = 'Alojamento_Policia' THEN 'Alojamento Polícia'
                    ELSE s.destino_outro
                END as destino,
                u.nome as responsavel,
                s.observacoes
            FROM internos_colchoes_saidas s
            INNER JOIN internos_colchoes_locais lo ON s.id_local_origem = lo.id
            LEFT JOIN internos i ON s.id_interno = i.ipen
            LEFT JOIN users u ON s.cadastrado_por = u.id
            WHERE s.status = 'Ativo'
        ";

        $paramsSaidas = [];

        if (!empty($dataInicio)) {
            $sqlSaidas .= " AND s.data_saida >= ?";
            $paramsSaidas[] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $sqlSaidas .= " AND s.data_saida <= ?";
            $paramsSaidas[] = $dataFim;
        }

        // Executar queries
        $historico = [];

        if (empty($tipo) || $tipo === 'Entrada') {
            $stmt = $pdo->prepare($sqlEntradas);
            $stmt->execute($params);
            $historico = array_merge($historico, $stmt->fetchAll());
        }

        if (empty($tipo) || $tipo === 'Saida') {
            $stmt = $pdo->prepare($sqlSaidas);
            $stmt->execute($paramsSaidas);
            $historico = array_merge($historico, $stmt->fetchAll());
        }

        // Ordenar por data (decrescente)
        usort($historico, function ($a, $b) {
            return strtotime($b['data']) - strtotime($a['data']);
        });

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'historico' => $historico]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar histórico: ' . $e->getMessage()]);
    }
}

/**
 * Obter um local específico
 */
function getLocal($pdo)
{
    try {
        $id = intval($_GET['id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT id, nome, descricao, tipo, capacidade_maxima, status, criado_em
            FROM internos_colchoes_locais
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $local = $stmt->fetch();

        if ($local) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'local' => $local]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Local não encontrado.']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar local: ' . $e->getMessage()]);
    }
}

/**
 * Criar novo local
 */
function criarLocal($pdo)
{
    try {
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $capacidade = !empty($_POST['capacidade_maxima']) ? intval($_POST['capacidade_maxima']) : null;
        $status = $_POST['status'] ?? 'Ativo';

        if (empty($nome) || empty($tipo)) {
            throw new Exception('Nome e tipo são obrigatórios.');
        }

        // Verificar se já existe local com mesmo nome
        $stmt = $pdo->prepare("SELECT id FROM internos_colchoes_locais WHERE nome = ?");
        $stmt->execute([$nome]);
        if ($stmt->fetch()) {
            throw new Exception('Já existe um local com este nome.');
        }

        $pdo->beginTransaction();

        // Inserir local
        $stmt = $pdo->prepare("
            INSERT INTO internos_colchoes_locais
            (nome, descricao, tipo, capacidade_maxima, status, criado_em, criado_por)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([$nome, $descricao, $tipo, $capacidade, $status, $_SESSION['user_id']]);

        $idLocal = $pdo->lastInsertId();

        // Criar registro de estoque
        $stmt = $pdo->prepare("
            INSERT INTO internos_colchoes_estoque
            (id_local, quantidade, ultima_atualizacao, atualizado_por)
            VALUES (?, 0, NOW(), ?)
        ");
        $stmt->execute([$idLocal, $_SESSION['user_id']]);

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Local criado com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Atualizar local existente
 */
function atualizarLocal($pdo)
{
    try {
        $id = intval($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $capacidade = !empty($_POST['capacidade_maxima']) ? intval($_POST['capacidade_maxima']) : null;
        $status = $_POST['status'] ?? 'Ativo';

        if ($id <= 0 || empty($nome) || empty($tipo)) {
            throw new Exception('Dados inválidos.');
        }

        // Verificar se já existe outro local com mesmo nome
        $stmt = $pdo->prepare("SELECT id FROM internos_colchoes_locais WHERE nome = ? AND id != ?");
        $stmt->execute([$nome, $id]);
        if ($stmt->fetch()) {
            throw new Exception('Já existe outro local com este nome.');
        }

        $stmt = $pdo->prepare("
            UPDATE internos_colchoes_locais
            SET nome = ?, descricao = ?, tipo = ?, capacidade_maxima = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$nome, $descricao, $tipo, $capacidade, $status, $id]);

        if ($stmt->rowCount() > 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Local atualizado com sucesso!']);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nenhuma alteração realizada.']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Excluir local
 */
function excluirLocal($pdo)
{
    try {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        $pdo->beginTransaction();

        // Verificar se há movimentações neste local
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM (
                SELECT id FROM internos_colchoes_entradas WHERE id_local_destino = ?
                UNION ALL
                SELECT id FROM internos_colchoes_saidas WHERE id_local_origem = ?
            ) as movimentacoes
        ");
        $stmt->execute([$id, $id]);
        $movimentacoes = $stmt->fetch();

        if ($movimentacoes['total'] > 0) {
            throw new Exception('Não é possível excluir este local pois existem movimentações registradas.');
        }

        // Excluir estoque
        $stmt = $pdo->prepare("DELETE FROM internos_colchoes_estoque WHERE id_local = ?");
        $stmt->execute([$id]);

        // Excluir local
        $stmt = $pdo->prepare("DELETE FROM internos_colchoes_locais WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Local excluído com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
