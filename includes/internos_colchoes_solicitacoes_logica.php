<?php
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

// Incluir configuração do banco
require_once '../conf/db.php';

// Debug - Registrar todos os requests (apenas em desenvolvimento)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("DEBUG: Request received - Action: " . ($_POST['action'] ?? 'not set') . " Method: " . $_SERVER['REQUEST_METHOD']);
}

// Conectar ao banco de dados
try {
    $config = include '../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Configurar timezone para São Paulo
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco de dados.']);
    exit;
}

// Obter ação da requisição
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Debug - Log da ação recebida (apenas em desenvolvimento)
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("DEBUG: Action received: " . $action);
    error_log("DEBUG: GET data: " . json_encode($_GET));
    error_log("DEBUG: POST data: " . json_encode($_POST));
}

switch ($action) {
    case 'get_interno_dados':
        getInternoDados($pdo);
        break;
    case 'get_ultimo_recebimento':
        getUltimoRecebimento($pdo);
        break;
    case 'get_galerias':
        getGalerias($pdo);
        break;
    case 'criar_solicitacao':
        criarSolicitacao($pdo);
        break;
    case 'get_solicitacoes':
        getSolicitacoes($pdo);
        break;
    case 'get_solicitacao':
        getSolicitacao($pdo);
        break;
    case 'cancelar_solicitacao':
        cancelarSolicitacao($pdo);
        break;
    case 'atender_solicitacao':
        atenderSolicitacao($pdo);
        break;
    case 'validar_aptidao':
        validarAptidao($pdo);
        break;
    case 'get_solicitacao_para_atualizar':
        getSolicitacaoParaAtualizar($pdo);
        break;
    case 'atualizar_data_solicitacao':
        error_log("DEBUG: Calling atualizarDataSolicitacao");
        atualizarDataSolicitacao($pdo);
        break;
    case 'gerar_termos_lote':
        gerarTermosLote($pdo);
        break;
    case 'imprimir_relatorio':
        imprimirRelatorio($pdo);
        break;
    case 'verificar_entrega_anual':
        verificarEntregaAnual($pdo);
        break;
    case 'gerar_termo':
        gerarTermoEntrega($pdo);
        break;
    default:
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ação não reconhecida.']);
        break;
}

/**
 * Obter dados do interno pelo IPEN
 */
function getInternoDados($pdo)
{
    try {
        $ipen = trim($_GET['ipen'] ?? '');

        if (empty($ipen)) {
            throw new Exception('IPEN não informado.');
        }

        $stmt = $pdo->prepare("
            SELECT ipen, nome, galeria, bloco, res, status, data_ativo
            FROM internos
            WHERE ipen = ? AND status = 'A'
        ");
        $stmt->execute([$ipen]);
        $interno = $stmt->fetch();

        if ($interno) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'interno' => $interno]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Interno não encontrado ou inativo.']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obter último recebimento do interno
 */
function getUltimoRecebimento($pdo)
{
    try {
        $ipen = trim($_GET['ipen'] ?? '');

        if (empty($ipen)) {
            throw new Exception('IPEN não informado.');
        }

        // Buscar última entrega para este interno
        $stmt = $pdo->prepare("
            SELECT data_saida
            FROM internos_colchoes_saidas
            WHERE id_interno = ? AND tipo_destino = 'Interno' AND status = 'Ativo'
            ORDER BY data_saida DESC
            LIMIT 1
        ");
        $stmt->execute([$ipen]);
        $ultimo = $stmt->fetch();

        if ($ultimo) {
            // Calcular dias usando PHP em vez de DATEDIFF do SQL
            $dataSaida = new DateTime($ultimo['data_saida']);
            $hoje = new DateTime();
            $diferenca = $hoje->diff($dataSaida);
            $dias = $diferenca->days;

            // Calcular status baseado na fórmula da planilha
            if ($dias < 365) {
                $status = 'SEM DIREITO';
            } elseif ($dias > 730) {
                $status = 'PRIORIDADE';
            } else {
                $status = 'COM DIREITO';
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ultimo' => [
                    'data_ultimo_recebimento' => $ultimo['data_saida'],
                    'dias_desde_ultimo' => $dias,
                    'status_entrega' => $status
                ]
            ]);
        } else {
            // Nunca recebeu - é prioridade
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Nunca recebeu'
            ]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Validar se interno está apto a receber colchão
 */
function validarAptidaoInterno($pdo, $ipen)
{
    try {
        // Buscar último recebimento
        $stmt = $pdo->prepare("
            SELECT data_saida
            FROM internos_colchoes_saidas
            WHERE id_interno = ? AND tipo_destino = 'Interno' AND status = 'Ativo'
            ORDER BY data_saida DESC
            LIMIT 1
        ");
        $stmt->execute([$ipen]);
        $ultimo = $stmt->fetch();

        if (!$ultimo) {
            // Nunca recebeu - apto
            return [
                'apto' => true,
                'motivo' => 'Interno nunca recebeu colchão anteriormente.',
                'status' => 'PRIORIDADE'
            ];
        }

        // Calcular dias desde último recebimento
        $dataSaida = new DateTime($ultimo['data_saida']);
        $hoje = new DateTime();
        $diferenca = $hoje->diff($dataSaida);
        $dias = $diferenca->days;

        // Calcular status baseado na fórmula da planilha
        if ($dias < 365) {
            return [
                'apto' => false,
                'motivo' => "Interno recebeu colchão há {$dias} dias. Período mínimo de 365 dias não cumprido.",
                'status' => 'SEM DIREITO',
                'dias' => $dias
            ];
        } else {
            return [
                'apto' => true,
                'motivo' => "Interno está apto a receber colchão. Último recebimento há {$dias} dias.",
                'status' => $dias > 730 ? 'PRIORIDADE' : 'COM DIREITO',
                'dias' => $dias
            ];
        }
    } catch (Exception $e) {
        return [
            'apto' => false,
            'motivo' => 'Erro ao validar aptidão: ' . $e->getMessage(),
            'status' => 'ERRO'
        ];
    }
}

/**
 * Validar aptidão do interno (endpoint)
 */
function validarAptidao($pdo)
{
    try {
        $ipen = trim($_GET['ipen'] ?? '');

        if (empty($ipen)) {
            throw new Exception('IPEN não informado.');
        }

        $validacao = validarAptidaoInterno($pdo, $ipen);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'validacao' => $validacao]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obter dados de uma solicitação específica
 */
function getSolicitacao($pdo)
{
    try {
        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        $stmt = $pdo->prepare("
            SELECT * FROM internos_colchoes_solicitacoes
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $solicitacao = $stmt->fetch();

        if ($solicitacao) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'solicitacao' => $solicitacao]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Solicitação não encontrada.']);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Atender solicitação (registrar saída para interno)
 */
function atenderSolicitacao($pdo)
{
    // Limpar qualquer output anterior
    if (ob_get_length()) ob_clean();

    try {
        $id = intval($_POST['id'] ?? 0);
        $idLocalOrigem = intval($_POST['id_local_origem'] ?? 0);
        $observacoes = trim($_POST['observacoes'] ?? '');

        if ($id <= 0 || $idLocalOrigem <= 0) {
            throw new Exception('Dados obrigatórios não preenchidos.');
        }

        // Buscar dados da solicitação
        $stmt = $pdo->prepare("
            SELECT * FROM internos_colchoes_solicitacoes
            WHERE id = ? AND status_solicitacao = 'Aberta'
        ");
        $stmt->execute([$id]);
        $solicitacao = $stmt->fetch();

        if (!$solicitacao) {
            throw new Exception('Solicitação não encontrada ou já atendida.');
        }

        // Validar aptidão do interno (apenas para logging, não bloqueia mais)
        $validacao = validarAptidaoInterno($pdo, $solicitacao['ipen']);

        // Log da validação para auditoria (apenas em desenvolvimento)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("DEBUG: Atendimento solicitado para interno " . $solicitacao['ipen'] . " - Status: " . $validacao['status'] . " - Apto: " . ($validacao['apto'] ? 'SIM' : 'NAO'));
        }

        // Verificar se há estoque suficiente
        $stmt = $pdo->prepare("SELECT quantidade FROM internos_colchoes_estoque WHERE id_local = ?");
        $stmt->execute([$idLocalOrigem]);
        $estoque = $stmt->fetch();

        // Log de estoque (apenas em desenvolvimento)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("DEBUG: Estoque antes de atendimento - Local: $idLocalOrigem, Quantidade: " . ($estoque['quantidade'] ?? 'NULL'));
        }

        if (!$estoque || $estoque['quantidade'] < 1) {
            throw new Exception('Estoque insuficiente no local selecionado.');
        }

        // Iniciar transação
        $pdo->beginTransaction();

        // Registrar saída para interno
        $stmt = $pdo->prepare("
            INSERT INTO internos_colchoes_saidas
            (data_saida, quantidade, tipo_destino, id_local_origem, id_interno, motivo_saida, observacoes, cadastrado_por, data_cadastro, status)
            VALUES (CURDATE(), 1, 'Interno', ?, ?, 'Atendimento de solicitação', ?, ?, NOW(), 'Ativo')
        ");
        $stmt->execute([
            $idLocalOrigem,
            $solicitacao['ipen'],
            $observacoes,
            $_SESSION['user_id']
        ]);

        $idSaida = $pdo->lastInsertId();

        // Registrar entrega (permitir múltiplas entregas para manter histórico completo)
        $stmt = $pdo->prepare("
            INSERT INTO internos_colchoes_entregas
            (id_interno, id_saida, data_entrega, status, cadastrado_por, data_cadastro)
            VALUES (?, ?, CURDATE(), 'Entregue', ?, NOW())
        ");
        $stmt->execute([$solicitacao['ipen'], $idSaida, $_SESSION['user_id']]);

        // Atualizar status da solicitação
        $stmt = $pdo->prepare("
            UPDATE internos_colchoes_solicitacoes
            SET status_solicitacao = 'Atendida',
                data_atendimento = CURDATE(),
                id_local_entrega = ?,
                atualizado_em = NOW(),
                atualizado_por = ?
            WHERE id = ?
        ");
        $stmt->execute([$idLocalOrigem, $_SESSION['user_id'], $id]);

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Solicitação atendida com sucesso!',
            'validacao' => $validacao
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Exibir página simples para atualizar data de solicitação
 */
function getSolicitacaoParaAtualizar($pdo)
{
    try {
        // Verificar sessão
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Sessão não iniciada.');
        }

        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        // Buscar solicitação
        $stmt = $pdo->prepare("
            SELECT * FROM internos_colchoes_solicitacoes
            WHERE id = ? AND status_solicitacao = 'Aberta'
        ");
        $stmt->execute([$id]);
        $solicitacao = $stmt->fetch();

        if (!$solicitacao) {
            throw new Exception('Solicitação não encontrada ou já atendida.');
        }

        // Retornar JSON para o offcanvas
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'solicitacao' => $solicitacao
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Atualizar data de último recebimento de solicitação pendente
 */
function atualizarDataSolicitacao($pdo)
{
    // Debug log (apenas em desenvolvimento)
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("DEBUG: atualizarDataSolicitacao called");
    }
    try {
        // Verificar sessão
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Sessão não iniciada.');
        }

        // Aceitar tanto GET quanto POST
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        $dataUltimoRecebimento = trim($_GET['data_ultimo_recebimento'] ?? $_POST['data_ultimo_recebimento'] ?? '');

        // Debug logs (apenas em desenvolvimento)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("DEBUG: ID: " . $id . ", Data: " . $dataUltimoRecebimento);
            error_log("DEBUG: Method: " . $_SERVER['REQUEST_METHOD']);
        }

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        // Buscar solicitação
        $stmt = $pdo->prepare("
            SELECT * FROM internos_colchoes_solicitacoes
            WHERE id = ? AND status_solicitacao = 'Aberta'
        ");
        $stmt->execute([$id]);
        $solicitacao = $stmt->fetch();

        if (!$solicitacao) {
            throw new Exception('Solicitação não encontrada ou já atendida.');
        }

        // Calcular novo status
        if (empty($dataUltimoRecebimento)) {
            $diasDesdeUltimo = null;
            $statusEntrega = 'PENDENTE';
        } else {
            // Calcular dias usando PHP em vez de DATEDIFF do SQL
            $dataUltimo = new DateTime($dataUltimoRecebimento);
            $hoje = new DateTime();
            $diferenca = $hoje->diff($dataUltimo);
            $diasDesdeUltimo = $diferenca->days;

            if ($diasDesdeUltimo < 365) {
                $statusEntrega = 'SEM DIREITO';
            } elseif ($diasDesdeUltimo > 730) {
                $statusEntrega = 'PRIORIDADE';
            } else {
                $statusEntrega = 'COM DIREITO';
            }
        }

        // Atualizar solicitação
        $stmt = $pdo->prepare("
            UPDATE internos_colchoes_solicitacoes
            SET data_ultimo_recebimento = ?,
                dias_desde_ultimo = ?,
                status_entrega = ?,
                atualizado_em = NOW(),
                atualizado_por = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $dataUltimoRecebimento ?: null,
            $diasDesdeUltimo,
            $statusEntrega,
            $_SESSION['user_id'],
            $id
        ]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Data atualizada com sucesso!',
            'status_entrega' => $statusEntrega,
            'dias_desde_ultimo' => $diasDesdeUltimo
        ]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obter galerias para filtros
 */
function getGalerias($pdo)
{
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT galeria
            FROM internos
            WHERE status = 'A' AND galeria IS NOT NULL AND galeria != ''
            ORDER BY galeria
        ");
        $galerias = $stmt->fetchAll(PDO::FETCH_COLUMN);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'galerias' => $galerias]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar galerias: ' . $e->getMessage()]);
    }
}

/**
 * Criar nova solicitação
 */
function criarSolicitacao($pdo)
{
    try {
        $ipen = trim($_POST['ipen'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');
        $dataSolicitacao = trim($_POST['data_solicitacao'] ?? '');
        $dataUltimoRecebimento = trim($_POST['data_ultimo_recebimento'] ?? '');

        if (empty($ipen)) {
            throw new Exception('IPEN é obrigatório.');
        }

        if (empty($dataSolicitacao)) {
            throw new Exception('Data da solicitação é obrigatória.');
        }

        // Verificar se já existe solicitação aberta para este interno
        $stmt = $pdo->prepare("
            SELECT id FROM internos_colchoes_solicitacoes
            WHERE ipen = ? AND status_solicitacao = 'Aberta'
        ");
        $stmt->execute([$ipen]);
        if ($stmt->fetch()) {
            throw new Exception('Já existe uma solicitação aberta para este interno.');
        }

        // Verificar se existe solicitação recente nos últimos 30 dias (exceto canceladas)
        $stmt = $pdo->prepare("
            SELECT id, data_solicitacao, status_solicitacao, criado_em
            FROM internos_colchoes_solicitacoes
            WHERE ipen = ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND status_solicitacao != 'Cancelada'
            ORDER BY criado_em DESC
            LIMIT 1
        ");
        $stmt->execute([$ipen]);
        $solicitacaoRecente = $stmt->fetch();

        if ($solicitacaoRecente) {
            $dataRecente = new DateTime($solicitacaoRecente['criado_em']);
            $hoje = new DateTime();
            $dias = $hoje->diff($dataRecente)->days;

            $mensagem = "Atenção! Este interno já possui uma solicitação registrada há {$dias} dias.";

            if ($solicitacaoRecente['status_solicitacao'] === 'Atendida') {
                $mensagem .= " Status: Atendida em " . date('d/m/Y', strtotime($solicitacaoRecente['data_solicitacao'])) . ".";
            } elseif ($solicitacaoRecente['status_solicitacao'] === 'Cancelada') {
                $mensagem .= " Status: Cancelada. Se necessário, contate a administração.";
            } else {
                $mensagem .= " Status: Aguardando atendimento.";
            }

            $mensagem .= " Para evitar duplicidades, aguarde pelo menos 30 dias entre solicitações ou verifique o histórico anterior.";

            throw new Exception($mensagem);
        }

        // Buscar dados do interno
        $stmt = $pdo->prepare("
            SELECT nome, galeria, bloco, res
            FROM internos
            WHERE ipen = ? AND status = 'A'
        ");
        $stmt->execute([$ipen]);
        $interno = $stmt->fetch();

        if (!$interno) {
            throw new Exception('Interno não encontrado ou inativo.');
        }

        // Se não foi informada data, marcar como pendente
        if (empty($dataUltimoRecebimento)) {
            $dataUltimo = null;
            $diasDesdeUltimo = null;
            $statusEntrega = 'PENDENTE';
        } else {
            // Validar e calcular status se data foi informada
            $dataUltimo = $dataUltimoRecebimento;
            // Calcular dias usando PHP em vez de DATEDIFF do SQL
            $dataUltimoObj = new DateTime($dataUltimo);
            $hoje = new DateTime();
            $diferenca = $hoje->diff($dataUltimoObj);
            $diasDesdeUltimo = $diferenca->days;

            if ($diasDesdeUltimo < 365) {
                $statusEntrega = 'SEM DIREITO';
            } elseif ($diasDesdeUltimo > 730) {
                $statusEntrega = 'PRIORIDADE';
            } else {
                $statusEntrega = 'COM DIREITO';
            }
        }

        // Inserir solicitação
        $stmt = $pdo->prepare("
            INSERT INTO internos_colchoes_solicitacoes
            (id_interno, ipen, nome_interno, galeria, bloco, res, data_solicitacao,
             data_ultimo_recebimento, dias_desde_ultimo, status_entrega, observacoes, criado_em, criado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        $stmt->execute([
            $ipen,
            $ipen,
            $interno['nome'],
            $interno['galeria'],
            $interno['bloco'],
            $interno['res'],
            $dataSolicitacao,
            $dataUltimo,
            $diasDesdeUltimo,
            $statusEntrega,
            $observacoes,
            $_SESSION['user_id']
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Solicitação cadastrada com sucesso!']);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Obter solicitações com filtros
 */
function getSolicitacoes($pdo)
{
    try {
        $ipen = trim($_GET['ipen'] ?? '');
        $nome = trim($_GET['nome'] ?? '');
        $galeria = trim($_GET['galeria'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $mostrarAtendidas = trim($_GET['mostrar_atendidas'] ?? 'false') === 'true';
        $dataInicio = trim($_GET['data_inicio'] ?? '');
        $dataFim = trim($_GET['data_fim'] ?? '');

        // Condição base - incluir ou não atendidas
        $where = $mostrarAtendidas ?
            ["s.status_solicitacao IN ('Aberta', 'Atendida')"] :
            ["s.status_solicitacao = 'Aberta'"];
        $params = [];

        if (!empty($ipen)) {
            $where[] = "s.ipen LIKE ?";
            $params[] = "%{$ipen}%";
        }

        if (!empty($nome)) {
            $where[] = "s.nome_interno LIKE ?";
            $params[] = "%{$nome}%";
        }

        if (!empty($galeria)) {
            $where[] = "s.galeria = ?";
            $params[] = $galeria;
        }

        if (!empty($status)) {
            $where[] = "s.status_entrega = ?";
            $params[] = $status;
        }

        if (!empty($dataInicio)) {
            $where[] = "s.data_solicitacao >= ?";
            $params[] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $where[] = "s.data_solicitacao <= ?";
            $params[] = $dataFim;
        }

        $sql = "
            SELECT s.*,
                   CASE
                       WHEN s.status_solicitacao = 'Atendida' THEN 'Atendida'
                       ELSE s.status_entrega
                   END as status_display
            FROM internos_colchoes_solicitacoes s
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                CASE s.status_entrega
                    WHEN 'PRIORIDADE' THEN 1
                    WHEN 'COM DIREITO' THEN 2
                    WHEN 'SEM DIREITO' THEN 3
                END,
                s.data_solicitacao DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $solicitacoes = $stmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'solicitacoes' => $solicitacoes]);
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao consultar solicitações: ' . $e->getMessage()]);
    }
}

/**
 * Cancelar solicitação
 */
function cancelarSolicitacao($pdo)
{
    try {
        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        // Buscar dados da solicitação
        $stmt = $pdo->prepare("SELECT * FROM internos_colchoes_solicitacoes WHERE id = ?");
        $stmt->execute([$id]);
        $solicitacao = $stmt->fetch();

        if (!$solicitacao) {
            throw new Exception('Solicitação não encontrada.');
        }

        if ($solicitacao['status_solicitacao'] === 'Cancelada') {
            throw new Exception('Solicitação já está cancelada.');
        }

        // Iniciar transação
        $pdo->beginTransaction();

        // Se a solicitação foi atendida, fazer estorno completo
        if ($solicitacao['status_solicitacao'] === 'Atendida') {
            // Buscar dados da entrega e saída
            $stmt = $pdo->prepare("
                SELECT e.id as id_entrega, s.id as id_saida, s.id_local_origem, s.quantidade
                FROM internos_colchoes_entregas e
                JOIN internos_colchoes_saidas s ON e.id_saida = s.id
                WHERE e.id_interno = ? AND s.tipo_destino = 'Interno' AND s.status = 'Ativo'
                ORDER BY e.data_entrega DESC
                LIMIT 1
            ");
            $stmt->execute([$solicitacao['ipen']]);
            $entrega = $stmt->fetch();

            if ($entrega) {
                // Atualizar estoque - DEVOLVER O COLCHÃO
                $stmt = $pdo->prepare("
                    UPDATE internos_colchoes_estoque
                    SET quantidade = quantidade + ?,
                        ultima_atualizacao = NOW(),
                        atualizado_por = ?
                    WHERE id_local = ?
                ");
                $stmt->execute([$entrega['quantidade'], $_SESSION['user_id'], $entrega['id_local_origem']]);

                // Cancelar a entrega
                $stmt = $pdo->prepare("
                    UPDATE internos_colchoes_entregas
                    SET status = 'Devolvido',
                        observacoes_devolucao = CONCAT('Cancelamento automático em ', NOW(), ' - Solicitação ID: ', ?)
                    WHERE id = ?
                ");
                $stmt->execute([$id, $entrega['id_entrega']]);

                // Cancelar a saída
                $stmt = $pdo->prepare("
                    UPDATE internos_colchoes_saidas
                    SET status = 'Cancelado'
                    WHERE id = ?
                ");
                $stmt->execute([$entrega['id_saida']]);

                // Debug log (apenas em desenvolvimento)
                if (defined('DEBUG_MODE') && DEBUG_MODE) {
                    error_log("DEBUG: Estorno realizado - Local: {$entrega['id_local_origem']}, Quantidade: {$entrega['quantidade']}, Entrega ID: {$entrega['id_entrega']}");
                }
            }
        }

        // Cancelar a solicitação
        $stmt = $pdo->prepare("
            UPDATE internos_colchoes_solicitacoes
            SET status_solicitacao = 'Cancelada',
                atualizado_em = NOW(),
                atualizado_por = ?
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $id]);

        $pdo->commit();

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Solicitação cancelada com sucesso!']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Verificar se interno já recebeu colchão este ano
 */
function verificarEntregaAnual($pdo)
{
    try {
        $ipen = trim($_GET['ipen'] ?? '');

        if (empty($ipen)) {
            throw new Exception('IPEN não informado.');
        }

        // Buscar última entrega deste interno no ano atual
        $stmt = $pdo->prepare("
            SELECT data_entrega
            FROM internos_colchoes_entregas
            WHERE id_interno = ?
              AND status = 'Entregue'
              AND YEAR(data_entrega) = YEAR(CURDATE())
            ORDER BY data_entrega DESC
            LIMIT 1
        ");
        $stmt->execute([$ipen]);
        $ultimaEntrega = $stmt->fetch();

        if ($ultimaEntrega) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ja_recebeu' => true,
                'data_ultima_entrega' => date('d/m/Y', strtotime($ultimaEntrega['data_entrega']))
            ]);
        } else {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'ja_recebeu' => false
            ]);
        }
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Gerar Termo de Entrega de Colchão
 */
function gerarTermoEntrega($pdo)
{
    try {
        $id = intval($_GET['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('ID inválido.');
        }

        // Buscar dados da solicitação e entrega com nome social
        $stmt = $pdo->prepare("
            SELECT s.*, e.data_entrega, e.id_saida,
                   i.nome as nome_registro,
                   i.nome_social
            FROM internos_colchoes_solicitacoes s
            LEFT JOIN internos_colchoes_entregas e ON s.id = e.id_interno
            LEFT JOIN internos i ON s.ipen = i.ipen
            WHERE s.id = ? AND s.status_solicitacao = 'Atendida'
        ");
        $stmt->execute([$id]);
        $dados = $stmt->fetch();

        if (!$dados) {
            throw new Exception('Solicitação não encontrada ou não atendida.');
        }

        // Função para formatar nome com social entre parênteses
        function formatarNomeCompleto($nomeRegistro, $nomeSocial)
        {
            if (!empty($nomeSocial)) {
                return $nomeRegistro . ' (' . $nomeSocial . ')';
            }
            return $nomeRegistro;
        }

        $nomeFormatado = formatarNomeCompleto($dados['nome_registro'], $dados['nome_social']);

        // Gerar HTML do termo
        $html_termo = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Termo de Responsabilidade - Colchão</title>
            <link rel='icon' type='image/svg+xml' href='../favicon.svg'>
            <style>
                @page { size: A4; margin: 1.5cm; }
                body { font-family: 'Times New Roman', serif; font-size: 12px; }
                .page-break { page-break-after: always; }
                .header-table { width: 100%; text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
                .box { border: 1px solid #000; padding: 10px; margin-bottom: 10px; }
                .bold { font-weight: bold; }
                .regras { font-size: 11px; text-align: justify; margin: 15px 0; }
                .assinatura { margin-top: 50px; text-align: center; }
            </style>
        </head>
        <body onload='window.print();'>
            <div class='termo-container'>
                <table class='header-table'>
                    <tr>
                        <td width='20%'><img src='../assets/img/logo_estado.png' style='max-height:60px;' onerror='this.style.display=\"none\"'></td>
                        <td width='60%'>
                            ESTADO DE SANTA CATARINA<br>
                            SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                            <strong>TERMO DE ENTREGA E RESPONSABILIDADE</strong><br>
                            COLCHÃO
                        </td>
                        <td width='20%'><img src='../assets/img/logo_sap.png' style='max-height:60px;' onerror='this.style.display=\"none\"'></td>
                    </tr>
                </table>

                <div class='box' style='background: #eee;'>
                    <span class='bold'>DADOS DO DETENTO</span><br>
                    IPEN: {$dados['ipen']}<br>
                    NOME: {$nomeFormatado}<br>
                    LOCAL: Galeria {$dados['galeria']} - Bloco {$dados['bloco']} - Cela {$dados['res']}
                </div>

                <div class='box'>
                    <span class='bold'>ITEM RECEBIDO:</span>
                    <table width='100%' border='1' style='border-collapse: collapse; margin-top: 5px;'>
                        <tr>
                            <th>Item</th>
                            <th>Última Solicitação</th>
                            <th>Data da Entrega</th>
                        </tr>
                        <tr>
                            <td style='padding:4px'>01 (um) Colchão de Solteiro D28</td>
                            <td style='padding:4px'>" . ($dados['data_ultimo_recebimento'] ? date('d/m/Y', strtotime($dados['data_ultimo_recebimento'])) : 'Primeiro Recebimento') . "</td>
                            <td style='padding:4px'>" . date('d/m/Y') . "</td>
                        </tr>
                    </table>
                </div>

                <div class='regras'>
                    <strong>TERMO DE RESPONSABILIDADE:</strong><br>
                    Eu, <strong>{$nomeFormatado}</strong>, declaro ter recebido o colchão acima descrito em perfeito estado de conservação.<br><br>

                    <strong>ESTOU CIENTE QUE:</strong><br>
                    1. O colchão é um bem da unidade prisional e deve ser utilizado exclusivamente por mim.<br>
                    2. É proibido ceder, vender ou alugar o colchão a terceiros.<br>
                    3. Em caso de dano ou desgaste excessivo, devo comunicar imediatamente ao chefe de segurança/plantão.<br>
                    4. O mau uso do colchão implicará na abertura de Medida Disciplinar.<br>
                    5. Sou responsável pela conservação e higiene do colchão.<br><br>

                    Declaro que o item foi recebido em perfeitas condições e assumo total responsabilidade por sua conservação.<br><br>
                </div>

                <div class='assinatura'>
                    ___________________________________________________________<br>
                    <strong>{$dados['ipen']} - {$nomeFormatado}</strong><br>
                    Reeducando(a)<br><br>

                    Data da Entrega: " . date('d/m/Y') . "
                </div>
            </div>
        </body>
        </html>
        ";

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html_termo]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Gerar múltiplos Termos de Entrega de Colchão em lote
 */
function gerarTermosLote($pdo)
{
    try {
        $idsString = trim($_GET['ids'] ?? '');

        if (empty($idsString)) {
            throw new Exception('IDs não informados.');
        }

        // Converter string de IDs para array
        $ids = array_map('intval', explode(',', $idsString));
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        if (empty($ids)) {
            throw new Exception('Nenhum ID válido informado.');
        }

        // Buscar dados de todas as solicitações com nome social
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT s.*, e.data_entrega, e.id_saida,
                   i.nome as nome_registro,
                   i.nome_social
            FROM internos_colchoes_solicitacoes s
            LEFT JOIN internos_colchoes_entregas e ON s.id = e.id_interno
            LEFT JOIN internos i ON s.ipen = i.ipen
            WHERE s.id IN ($placeholders) AND s.status_solicitacao = 'Atendida'
            ORDER BY s.id
        ");
        $stmt->execute($ids);
        $solicitacoes = $stmt->fetchAll();

        if (empty($solicitacoes)) {
            throw new Exception('Nenhuma solicitação atendida encontrada.');
        }

        // Função para formatar nome com social entre parênteses
        function formatarNomeCompletoLote($nomeRegistro, $nomeSocial)
        {
            if (!empty($nomeSocial)) {
                return $nomeRegistro . ' (' . $nomeSocial . ')';
            }
            return $nomeRegistro;
        }

        // Gerar HTML combinado com todos os termos
        $html_termos = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Termos de Responsabilidade - Colchões</title>
            <link rel='icon' type='image/svg+xml' href='../favicon.svg'>
            <style>
                @page { size: A4; margin: 1.5cm; }
                body { font-family: 'Times New Roman', serif; font-size: 12px; }
                .page-break { page-break-after: always; }
                .header-table { width: 100%; text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
                .box { border: 1px solid #000; padding: 10px; margin-bottom: 10px; }
                .bold { font-weight: bold; }
                .regras { font-size: 11px; text-align: justify; margin: 15px 0; }
                .assinatura { margin-top: 50px; text-align: center; }
                .termo-container { margin-bottom: 30px; }
            </style>
        </head>
        <body onload='window.print();'>
        ";

        // Gerar termo para cada solicitação
        foreach ($solicitacoes as $dados) {
            $nomeFormatado = formatarNomeCompletoLote($dados['nome_registro'], $dados['nome_social']);

            $html_termos .= "
            <div class='termo-container'>
                <table class='header-table'>
                    <tr>
                        <td width='20%'><img src='../assets/img/logo_estado.png' style='max-height:60px;' onerror='this.style.display=\"none\"'></td>
                        <td width='60%'>
                            ESTADO DE SANTA CATARINA<br>
                            SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                            <strong>TERMO DE ENTREGA E RESPONSABILIDADE</strong><br>
                            COLCHÃO
                        </td>
                        <td width='20%'><img src='../assets/img/logo_sap.png' style='max-height:60px;' onerror='this.style.display=\"none\"'></td>
                    </tr>
                </table>

                <div class='box' style='background: #eee;'>
                    <span class='bold'>DADOS DO DETENTO</span><br>
                    IPEN: {$dados['ipen']}<br>
                    NOME: {$nomeFormatado}<br>
                    LOCAL: Galeria {$dados['galeria']} - Bloco {$dados['bloco']} - Cela {$dados['res']}
                </div>

                <div class='box'>
                    <span class='bold'>ITEM RECEBIDO:</span>
                    <table width='100%' border='1' style='border-collapse: collapse; margin-top: 5px;'>
                        <tr>
                            <th>Item</th>
                            <th>Última Solicitação</th>
                            <th>Data da Entrega</th>
                        </tr>
                        <tr>
                            <td style='padding:4px'>01 (um) Colchão de Solteiro D28</td>
                            <td style='padding:4px'>" . ($dados['data_ultimo_recebimento'] ? date('d/m/Y', strtotime($dados['data_ultimo_recebimento'])) : 'Primeiro Recebimento') . "</td>
                            <td style='padding:4px'>" . date('d/m/Y') . "</td>
                        </tr>
                    </table>
                </div>

                <div class='regras'>
                    <strong>TERMO DE RESPONSABILIDADE:</strong><br>
                    Eu, <strong>{$nomeFormatado}</strong>, declaro ter recebido o colchão acima descrito em perfeito estado de conservação.<br><br>

                    <strong>ESTOU CIENTE QUE:</strong><br>
                    1. O colchão é um bem da unidade prisional e deve ser utilizado exclusivamente por mim.<br>
                    2. É proibido ceder, vender ou alugar o colchão a terceiros.<br>
                    3. Em caso de dano ou desgaste excessivo, devo comunicar imediatamente ao chefe de segurança/plantão.<br>
                    4. O mau uso do colchão implicará na abertura de Medida Disciplinar.<br>
                    5. Sou responsável pela conservação e higiene do colchão.<br><br>

                    Declaro que o item foi recebido em perfeitas condições e assumo total responsabilidade por sua conservação.<br><br>
                </div>

                <div class='assinatura'>
                    ___________________________________________________________<br>
                    <strong>{$dados['ipen']} - {$nomeFormatado}</strong><br>
                    Reeducando(a)<br><br>

                    Data da Entrega: " . date('d/m/Y') . "
                </div>
            </div>
            ";

            // Adicionar quebra de página, exceto no último termo
            if ($dados !== end($solicitacoes)) {
                $html_termos .= "<div class='page-break'></div>";
            }
        }

        $html_termos .= "
        </body>
        </html>
        ";

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'html' => $html_termos]);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

/**
 * Imprimir relatório de solicitações - FORMATO PROFISSIONAL
 */
function imprimirRelatorio($pdo)
{
    try {
        // Capturar filtros da URL
        $ipen = trim($_GET['ipen'] ?? '');
        $nome = trim($_GET['nome'] ?? '');
        $galeria = trim($_GET['galeria'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $dataInicio = trim($_GET['data_inicio'] ?? '');
        $dataFim = trim($_GET['data_fim'] ?? '');

        // Construir WHERE com filtros
        $where = ["s.status_solicitacao = 'Aberta'"];
        $params = [];

        if (!empty($ipen)) {
            $where[] = "s.ipen LIKE ?";
            $params[] = "%{$ipen}%";
        }

        if (!empty($nome)) {
            $where[] = "s.nome_interno LIKE ?";
            $params[] = "%{$nome}%";
        }

        if (!empty($galeria)) {
            $where[] = "s.galeria = ?";
            $params[] = $galeria;
        }

        if (!empty($status)) {
            $where[] = "s.status_entrega = ?";
            $params[] = $status;
        }

        if (!empty($dataInicio)) {
            $where[] = "s.data_solicitacao >= ?";
            $params[] = $dataInicio;
        }

        if (!empty($dataFim)) {
            $where[] = "s.data_solicitacao <= ?";
            $params[] = $dataFim;
        }

        // SQL com ordenação padrão (mesma da tabela)
        $sql = "
            SELECT s.*
            FROM internos_colchoes_solicitacoes s
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                CASE s.status_entrega
                    WHEN 'PRIORIDADE' THEN 1
                    WHEN 'COM DIREITO' THEN 2
                    WHEN 'SEM DIREITO' THEN 3
                    WHEN 'PENDENTE' THEN 4
                END,
                s.data_solicitacao DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $solicitacoes = $stmt->fetchAll();

        // Título do relatório baseado nos filtros
        $titulo_filtro = "COMPLETO";
        if (!empty($ipen) || !empty($nome) || !empty($galeria) || !empty($status) || !empty($dataInicio) || !empty($dataFim)) {
            $titulo_filtro = "FILTROS APLICADOS";
        }

        $titulo_relatorio = "RELATÓRIO DE SOLICITAÇÕES DE COLCHÃO - {$titulo_filtro} - " . date("d/m/Y");

        // Gerar HTML profissional baseado no formato da rouparia
?>
        <!DOCTYPE html>
        <html lang="pt-br">

        <head>
            <meta charset="UTF-8">
            <title><?= $titulo_relatorio ?></title>
            <link rel="icon" type="image/svg+xml" href="favicon.svg">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                @page {
                    size: A4 landscape;
                    margin: 0.3cm;
                }

                body {
                    font-size: 8pt;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: white;
                    padding: 10px;
                    color: black;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 15px;
                    table-layout: fixed;
                }

                th,
                td {
                    border: 1px solid black !important;
                    padding: 3px 4px !important;
                    vertical-align: middle;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }

                th {
                    background: #e9ecef !important;
                    font-weight: bold;
                    text-align: center;
                    text-transform: uppercase;
                    font-size: 7pt;
                }

                .row-priority {
                    background-color: #ffebee !important;
                    font-weight: bold;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .row-direito {
                    background-color: #e8f5e9 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .row-sem-direito {
                    background-color: #f5f5f5 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .row-pendente {
                    background-color: #fff3cd !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                h4 {
                    font-weight: 800;
                    text-transform: uppercase;
                    margin-bottom: 0;
                    font-size: 12pt;
                }

                h5 {
                    border-bottom: 2px solid black;
                    margin-top: 15px;
                    text-transform: uppercase;
                    font-size: 9pt;
                    font-weight: bold;
                }

                .header-info {
                    margin-bottom: 10px;
                    font-size: 8pt;
                }

                .total {
                    margin-top: 15px;
                    font-weight: bold;
                    border: 2px solid black;
                    padding: 8px;
                    background: #f8f9fa;
                    font-size: 8pt;
                }

                .badge-priority {
                    background: #dc3545;
                    color: white;
                    padding: 1px 4px;
                    border-radius: 2px;
                    font-size: 6pt;
                    font-weight: bold;
                }

                .badge-direito {
                    background: #28a745;
                    color: white;
                    padding: 1px 4px;
                    border-radius: 2px;
                    font-size: 6pt;
                    font-weight: bold;
                }

                .badge-sem-direito {
                    background: #6c757d;
                    color: white;
                    padding: 1px 4px;
                    border-radius: 2px;
                    font-size: 6pt;
                    font-weight: bold;
                }

                .badge-pendente {
                    background: #ffc107;
                    color: #212529;
                    padding: 1px 4px;
                    border-radius: 2px;
                    font-size: 6pt;
                    font-weight: bold;
                }

                /* Larguras otimizadas para A4 paisagem */
                .col-ipen {
                    width: 60px;
                }

                .col-nome {
                    width: 200px;
                }

                .col-local {
                    width: 80px;
                }

                .col-data-solicitacao {
                    width: 80px;
                }

                .col-ultimo-recebimento {
                    width: 100px;
                }

                .col-status {
                    width: 80px;
                }

                .col-dias {
                    width: 50px;
                }
            </style>
        </head>

        <body onload="window.print();">
            <div class="d-flex justify-content-between align-items-end mb-3 border-bottom pb-2">
                <h4><?= $titulo_relatorio ?></h4>
                <div class="text-end small">
                    <div>Sistema Prisional Integrado - SIGEP</div>
                    <div>Módulo: Censura - Colchões</div>
                    <div>Emitido em: <?= date("d/m/Y H:i:s") ?></div>
                    <div>Usuário: <?= $_SESSION['user_nome'] ?? 'Sistema' ?></div>
                </div>
            </div>

            <div class='header-info'>
                <h5>FILTROS APLICADOS</h5>
                <div class="row">
                    <div class="col-md-6">
                        <strong>IPEN:</strong> <?= !empty($ipen) ? $ipen : 'Todos' ?><br>
                        <strong>Nome:</strong> <?= !empty($nome) ? $nome : 'Todos' ?><br>
                        <strong>Galeria:</strong> <?= !empty($galeria) ? $galeria : 'Todas' ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong> <?= !empty($status) ? $status : 'Todos' ?><br>
                        <strong>Período:</strong> <?=
                                                    (!empty($dataInicio) && !empty($dataFim) ?
                                                        date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) :
                                                        'Todos') ?>
                    </div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th class="col-ipen">IPEN</th>
                        <th>Nome do Interno</th>
                        <th class="col-local">Local</th>
                        <th class="col-data-solicitacao">Data Solicitação</th>
                        <th class="col-ultimo-recebimento">Último Recebimento</th>
                        <th class="col-status">Status</th>
                        <th class="col-dias">Dias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $total = 0;
                    $prioridade = 0;
                    $comDireito = 0;
                    $semDireito = 0;
                    $pendente = 0;

                    foreach ($solicitacoes as $s):
                        $total++;

                        // Classes e contadores por status
                        $rowClass = '';
                        $badgeClass = '';
                        if ($s['status_entrega'] === 'PRIORIDADE') {
                            $rowClass = 'row-priority';
                            $badgeClass = 'badge-priority';
                            $prioridade++;
                        } elseif ($s['status_entrega'] === 'COM DIREITO') {
                            $rowClass = 'row-direito';
                            $badgeClass = 'badge-direito';
                            $comDireito++;
                        } elseif ($s['status_entrega'] === 'SEM DIREITO') {
                            $rowClass = 'row-sem-direito';
                            $badgeClass = 'badge-sem-direito';
                            $semDireito++;
                        } else {
                            $rowClass = 'row-pendente';
                            $badgeClass = 'badge-pendente';
                            $pendente++;
                        }

                        $tempoUltimo = $s['data_ultimo_recebimento'] ?
                            (new DateTime($s['data_ultimo_recebimento']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') . ' (' . $s['dias_desde_ultimo'] . ' dias)' : 'Nunca recebeu';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td class="col-ipen" align="center"><?= $s['ipen'] ?></td>
                            <td class="col-nome" title="<?= htmlspecialchars($s['nome_interno']) ?>"><?= $s['nome_interno'] ?></td>
                            <td class="col-local" align="center"><?= $s['galeria'] ?>/<?= $s['bloco'] ?> <?= $s['res'] ?></td>
                            <td class="col-data-solicitacao" align="center"><?= (new DateTime($s['data_solicitacao']))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y') ?></td>
                            <td class="col-ultimo-recebimento" align="center" title="<?= $tempoUltimo ?>"><?= $tempoUltimo ?></td>
                            <td class="col-status" align="center">
                                <span class="<?= $badgeClass ?>"><?= $s['status_entrega'] ?></span>
                            </td>
                            <td class="col-dias" align="center"><?= $s['dias_desde_ultimo'] ?: '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class='total'>
                <div class="row">
                    <div class="col-md-6">
                        <strong>TOTAL DE SOLICITAÇÕES: <?= $total ?></strong>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge-priority">PRIORIDADE: <?= $prioridade ?></span> |
                        <span class="badge-direito">COM DIREITO: <?= $comDireito ?></span> |
                        <span class="badge-sem-direito">SEM DIREITO: <?= $semDireito ?></span> |
                        <span class="badge-pendente">PENDENTE: <?= $pendente ?></span>
                    </div>
                </div>
            </div>
        </body>

        </html>
<?php

        exit;
    } catch (Exception $e) {
        echo "<h1>Erro ao Gerar Relatório</h1><p>" . $e->getMessage() . "</p>";
    }
}
?>
