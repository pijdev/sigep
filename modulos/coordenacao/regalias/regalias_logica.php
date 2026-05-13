<?php
// Painel Regalias - Controller SIGEP
// Descrição: Módulo para visualização e gerenciamento de regalias

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
function returnError($message, $code = 500)
{
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code], JSON_UNESCAPED_UNICODE);
    exit;
}

// Verificar se usuário está autenticado
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_nome'])) {
    returnError('Usuário não autenticado', 401);
}

// Verificar permissão específica
if (!($_SESSION['user_admin'] || ($_SESSION['perm_coordenacao'] ?? 0))) {
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

// Funções auxiliares
function getCorGaleria($galeria)
{
    $cores = [
        'A' => 'primary',
        'B' => 'secondary',
        'C' => 'success',
        'D' => 'danger',
        'E' => 'warning',
        'G' => 'info',
        'H' => 'dark',
        'S' => 'purple'
    ];
    return $cores[$galeria] ?? 'secondary';
}

function getCorSetor($setor)
{
    if (strpos($setor, 'Alimentação') !== false) return 'success';
    if (strpos($setor, 'Fundo Rotativo') !== false) return 'warning';
    if (strpos($setor, 'Corte de Cabelo') !== false) return 'danger';
    if (strpos($setor, 'Conveniado') !== false) return 'info';
    if (strpos($setor, 'Manutenção') !== false) return 'secondary';
    return 'primary';
}

function getFuncaoDescricao($setor)
{
    $descricoes = [
        'Alimentação' => 'Entrega de Marmitas (Por Remição)',
        'Fundo Rotativo' => 'Serviços Gerais',
        'Corte de Cabelo' => 'Barbeiro do Bloco (Por Remição)',
        'Conveniado - Hospital' => 'Hospital',
        'Manutenção' => 'Manutenção Predial',
        'Limpeza' => 'Limpeza e Organização',
        'OBRAS' => 'Construção Civil',
        'Serviços Gerais' => 'Serviços Gerais',
        'Censura' => 'Rouparia, Eletrônicos e Cartas',
        'HORTA' => 'Horta e Jardinagem',
        'Biblioteca' => 'Organização e Limpeza',
        'COZINHA' => 'Preparo de Alimentação',
        'Pintura' => 'Pintura e Acabamentos',
        'Roçada' => 'Jardinagem e Limpeza',
        'Reciclagem' => 'Separação de Materiais',
        'Serralheria' => 'Serralheria',
        'Almoxarifado' => 'Controle de Estoque',
        'Eletrônica / Informática' => 'Manutenção de Equipamentos e Desenvolvimento de Sistemas',
        'Manutenção Geral' => 'Manutenções Gerais'
    ];

    foreach ($descricoes as $chave => $descricao) {
        if (strpos($setor, $chave) !== false) {
            return $descricao;
        }
    }

    return 'Função não especificada';
}

function getDiasTrabalho($ipen, $pdo)
{
    try {
        // Primeiro tentar buscar remicao_inicio na tabela laboral
        $stmt = $pdo->prepare("SELECT il.remicacao_inicio FROM internos_laboral il WHERE il.ipen = ? AND il.status = 'A'");
        $stmt->execute([$ipen]);
        $resultado = $stmt->fetch();

        if ($resultado && !empty($resultado['remicao_inicio'])) {
            // Usar data de início da remição (mais preciso)
            $dataInicio = new DateTime($resultado['remicao_inicio']);
            $dataHoje = new DateTime();
            $diferenca = $dataInicio->diff($dataHoje);

            return $diferenca->days . ' dias';
        }

        // Se não tiver remição, usar data_ativo da tabela internos
        $stmt = $pdo->prepare("SELECT i.data_ativo FROM internos WHERE ipen = ? AND regalia = 'S'");
        $stmt->execute([$ipen]);
        $resultado = $stmt->fetch();

        if ($resultado && !empty($resultado['data_ativo'])) {
            $dataInicio = new DateTime($resultado['data_ativo']);
            $dataHoje = new DateTime();
            $diferenca = $dataInicio->diff($dataHoje);

            return $diferenca->days . ' dias';
        }

        return 'Não definido';
    } catch (PDOException $e) {
        return 'Não definido';
    } catch (Exception $e) {
        return 'Não definido';
    }
}

// Funções CRUD
function getRegalias($pdo, $filtros = [])
{
    try {
        $sql = "SELECT i.ipen, i.nome, i.galeria, i.bloco, i.regalia_setor, il.dias_semana, i.data_ativo
                FROM internos i
                LEFT JOIN internos_laboral il ON i.ipen = il.ipen AND il.status = 'A'
                WHERE i.regalia = 'S'";

        $params = [];

        if (!empty($filtros['galeria'])) {
            $sql .= " AND i.galeria = ?";
            $params[] = $filtros['galeria'];
        }

        if (!empty($filtros['setor'])) {
            $sql .= " AND i.regalia_setor LIKE ?";
            $params[] = '%' . $filtros['setor'] . '%';
        }

        if (!empty($filtros['busca'])) {
            $sql .= " AND (i.nome LIKE ? OR i.ipen LIKE ?)";
            $params[] = '%' . $filtros['busca'] . '%';
            $params[] = '%' . $filtros['busca'] . '%';
        }

        $sql .= " ORDER BY i.galeria, i.bloco, i.nome";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar regalias: ' . $e->getMessage());
    }
}

function getDetalhesRegalia($pdo, $ipen)
{
    try {
        $stmt = $pdo->prepare("
            SELECT i.*, il.estabelecimento, il.dias_semana, il.status as status_laboral
            FROM internos i
            LEFT JOIN internos_laboral il ON i.ipen = il.ipen
            WHERE i.ipen = ?
        ");
        $stmt->execute([$ipen]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar detalhes: ' . $e->getMessage());
    }
}

function getEstatisticas($pdo)
{
    try {
        $estatisticas = [];

        // Total por setor
        $stmt = $pdo->query("
            SELECT regalia_setor, COUNT(*) as total
            FROM internos
            WHERE regalia = 'S' AND regalia_setor IS NOT NULL
            GROUP BY regalia_setor
            ORDER BY total DESC
        ");
        $estatisticas['por_setor'] = $stmt->fetchAll();

        // Total por galeria
        $stmt = $pdo->query("
            SELECT galeria, COUNT(*) as total
            FROM internos
            WHERE regalia = 'S'
            GROUP BY galeria
            ORDER BY galeria
        ");
        $estatisticas['por_galeria'] = $stmt->fetchAll();

        // Total geral
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM internos WHERE regalia = 'S'");
        $estatisticas['total'] = $stmt->fetch()['total'];

        return $estatisticas;
    } catch (PDOException $e) {
        throw new Exception('Erro ao buscar estatísticas: ' . $e->getMessage());
    }
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    ob_clean();

    try {
        switch ($_POST['action']) {
            case 'listar':
                $filtros = [
                    'galeria' => $_POST['galeria'] ?? '',
                    'setor' => $_POST['setor'] ?? '',
                    'busca' => $_POST['busca'] ?? ''
                ];
                $regalias = getRegalias($pdo, $filtros);
                echo json_encode(['success' => true, 'data' => $regalias], JSON_UNESCAPED_UNICODE);
                break;

            case 'detalhes':
                $ipen = $_POST['ipen'] ?? 0;
                $detalhes = getDetalhesRegalia($pdo, $ipen);
                if ($detalhes) {
                    echo json_encode(['success' => true, 'data' => $detalhes], JSON_UNESCAPED_UNICODE);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Regalia não encontrada'], JSON_UNESCAPED_UNICODE);
                }
                break;

            case 'estatisticas':
                $estatisticas = getEstatisticas($pdo);
                echo json_encode(['success' => true, 'data' => $estatisticas], JSON_UNESCAPED_UNICODE);
                break;

            default:
                throw new Exception('Ação não reconhecida');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    exit;
}

// Carregar dados para a view
try {
    $regalias = getRegalias($pdo);

    // Usar dias_semana diretamente
    foreach ($regalias as &$regalia) {
        $regalia['dias_trabalho'] = $regalia['dias_semana'] ?? 'Não definido';
    }
} catch (Exception $e) {
    $regalias = [];
}
