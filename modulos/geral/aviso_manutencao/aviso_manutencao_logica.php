<?php
/**
 * SIGEP - Módulo de Avisos de Manutenção
 * Controller - Lógica de negócios
 * 
 * Descrição: Sistema completo para gerenciamento de avisos de manutenção
 * Permite criar, editar, exibir e gerenciar avisos de manutenção do sistema
 */

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar Timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Função para retornar erro JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão específica para operações de gestão
if (!($_SESSION['user_admin'] || ($_SESSION['perm_ti'] ?? 0))) {
    returnError('Sem permissão para acessar este módulo', 403);
}

// Configurar conexão PDO
try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '-03:00'");
} catch (PDOException $e) {
    returnError('Erro na conexão com banco de dados: ' . $e->getMessage(), 500);
}

/**
 * Buscar avisos de manutenção ativos
 */
function buscarAvisosAtivos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM avisos_manutencao 
            WHERE ativo = 1 
            AND data_inicio <= NOW() 
            AND data_fim >= NOW()
            ORDER BY severidade DESC, data_inicio ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar avisos ativos: ' . $e->getMessage());
    }
}

/**
 * Buscar todos os avisos (para gestão)
 */
function buscarTodosAvisos($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM avisos_manutencao 
            ORDER BY data_inicio DESC, criado_em DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar todos os avisos: ' . $e->getMessage());
    }
}

/**
 * Criar novo aviso de manutenção
 */
function criarAviso($pdo, $dados) {
    try {
        // Validar campos obrigatórios
        $camposObrigatorios = ['titulo', 'mensagem', 'severidade', 'data_inicio', 'data_fim'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                throw new Exception("Campo obrigatório: {$campo}");
            }
        }

        // Validar severidade
        $severidadesValidas = ['info', 'success', 'warning', 'danger'];
        if (!in_array($dados['severidade'], $severidadesValidas)) {
            throw new Exception('Severidade inválida');
        }

        // Validar datas
        $dataInicio = new DateTime($dados['data_inicio']);
        $dataFim = new DateTime($dados['data_fim']);
        
        if ($dataFim <= $dataInicio) {
            throw new Exception('Data fim deve ser posterior à data início');
        }

        // Processar arrays JSON
        $setoresImpactados = !empty($dados['setores_impactados']) ? json_encode($dados['setores_impactados']) : null;
        $sistemasImpactados = !empty($dados['sistemas_impactados']) ? json_encode($dados['sistemas_impactados']) : null;

        $stmt = $pdo->prepare("
            INSERT INTO avisos_manutencao (
                titulo, mensagem, severidade, data_inicio, data_fim,
                setores_impactados, sistemas_impactados, criado_por
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $dados['titulo'],
            $dados['mensagem'],
            $dados['severidade'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $setoresImpactados,
            $sistemasImpactados,
            $_SESSION['user_nome'] ?? 'admin'
        ]);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        throw new Exception('Erro ao criar aviso: ' . $e->getMessage());
    }
}

/**
 * Atualizar aviso de manutenção
 */
function atualizarAviso($pdo, $id, $dados) {
    try {
        // Verificar se aviso existe
        $stmt = $pdo->prepare("SELECT id FROM avisos_manutencao WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            throw new Exception('Aviso não encontrado');
        }

        // Validar campos obrigatórios
        $camposObrigatorios = ['titulo', 'mensagem', 'severidade', 'data_inicio', 'data_fim'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                throw new Exception("Campo obrigatório: {$campo}");
            }
        }

        // Validar datas
        $dataInicio = new DateTime($dados['data_inicio']);
        $dataFim = new DateTime($dados['data_fim']);
        
        if ($dataFim <= $dataInicio) {
            throw new Exception('Data fim deve ser posterior à data início');
        }

        // Processar arrays JSON
        $setoresImpactados = !empty($dados['setores_impactados']) ? json_encode($dados['setores_impactados']) : null;
        $sistemasImpactados = !empty($dados['sistemas_impactados']) ? json_encode($dados['sistemas_impactados']) : null;

        $stmt = $pdo->prepare("
            UPDATE avisos_manutencao SET 
                titulo = ?, mensagem = ?, severidade = ?, 
                data_inicio = ?, data_fim = ?, 
                setores_impactados = ?, sistemas_impactados = ?,
                ativo = ?, atualizado_por = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $dados['titulo'],
            $dados['mensagem'],
            $dados['severidade'],
            $dados['data_inicio'],
            $dados['data_fim'],
            $setoresImpactados,
            $sistemasImpactados,
            $dados['ativo'] ?? 1,
            $_SESSION['user_nome'] ?? 'admin',
            $id
        ]);

        return true;
    } catch (PDOException $e) {
        throw new Exception('Erro ao atualizar aviso: ' . $e->getMessage());
    }
}

/**
 * Excluir aviso de manutenção
 */
function excluirAviso($pdo, $id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM avisos_manutencao WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        throw new Exception('Erro ao excluir aviso: ' . $e->getMessage());
    }
}

/**
 * Buscar aviso por ID
 */
function buscarAvisoPorId($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM avisos_manutencao WHERE id = ?");
        $stmt->execute([$id]);
        $aviso = $stmt->fetch();
        
        if ($aviso) {
            // Decodificar arrays JSON
            $aviso['setores_impactados'] = $aviso['setores_impactados'] ? json_decode($aviso['setores_impactados'], true) : [];
            $aviso['sistemas_impactados'] = $aviso['sistemas_impactados'] ? json_decode($aviso['sistemas_impactados'], true) : [];
        }
        
        return $aviso;
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar aviso: ' . $e->getMessage());
    }
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ob_clean();

    try {
        switch ($_POST['action']) {
            case 'listar_ativos':
                $avisos = buscarAvisosAtivos($pdo);
                echo json_encode(['success' => true, 'data' => $avisos], JSON_UNESCAPED_UNICODE);
                break;

            case 'listar_todos':
                $avisos = buscarTodosAvisos($pdo);
                echo json_encode(['success' => true, 'data' => $avisos], JSON_UNESCAPED_UNICODE);
                break;

            case 'buscar':
                $id = $_POST['id'] ?? 0;
                if (!$id) {
                    throw new Exception('ID não informado');
                }
                $aviso = buscarAvisoPorId($pdo, $id);
                echo json_encode(['success' => true, 'data' => $aviso], JSON_UNESCAPED_UNICODE);
                break;

            case 'criar':
                $aviso_id = criarAviso($pdo, $_POST);
                echo json_encode(['success' => true, 'data' => ['id' => $aviso_id]], JSON_UNESCAPED_UNICODE);
                break;

            case 'atualizar':
                $id = $_POST['id'] ?? 0;
                if (!$id) {
                    throw new Exception('ID não informado');
                }
                atualizarAviso($pdo, $id, $_POST);
                echo json_encode(['success' => true, 'message' => 'Aviso atualizado com sucesso'], JSON_UNESCAPED_UNICODE);
                break;

            case 'excluir':
                $id = $_POST['id'] ?? 0;
                if (!$id) {
                    throw new Exception('ID não informado');
                }
                excluirAviso($pdo, $id);
                echo json_encode(['success' => true, 'message' => 'Aviso excluído com sucesso'], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Carregar avisos ativos para a view
try {
    $avisosAtivos = buscarAvisosAtivos($pdo);
} catch (Exception $e) {
    $avisosAtivos = [];
}
?>
