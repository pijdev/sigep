<?php
// modulos/servicos/notificacoes/notificacoes_logica.php
// Sistema de Notificações SIGEP

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Configuração do banco
$config = require __DIR__ . '/../../../conf/db.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
               $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$userId = $_SESSION['user_id'];

require_once __DIR__ . '/notificacoes_lib.php';

// Instancia manager
$notificationManager = new NotificationManager($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    ob_clean();

    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'buscar_notificacoes':
                $pagina = (int)($_POST['pagina'] ?? 1);
                $limite = (int)($_POST['limite'] ?? 20);
                $notificacoes = $notificationManager->buscarTodas($userId, $pagina, $limite);
                $total = $notificationManager->contarTodas($userId);
                echo json_encode(['success' => true, 'notificacoes' => $notificacoes, 'total' => $total]);
                break;

            case 'marcar_lida':
                $id = (int)$_POST['id'];
                $success = $notificationManager->marcarComoLida($id, $userId);
                echo json_encode(['success' => $success]);
                break;

            case 'marcar_todas_lidas':
                $success = $notificationManager->marcarTodasComoLidas($userId);
                echo json_encode(['success' => $success]);
                break;

            case 'buscar_preferencias':
                $preferencias = $notificationManager->buscarPreferencias($userId);
                echo json_encode(['success' => true, 'preferencias' => $preferencias]);
                break;

            case 'atualizar_preferencia':
                $tipo = $_POST['tipo'];
                $ativa = (int)$_POST['ativa'];
                $success = $notificationManager->atualizarPreferencia($userId, $tipo, $ativa);
                echo json_encode(['success' => $success]);
                break;

            case 'get_contagem':
                $count = $notificationManager->getContagemNaoLidas($userId);
                echo json_encode(['success' => true, 'count' => $count]);
                break;

            case 'criar_notificacao':
                // Para uso interno por outros módulos
                $tipo = $_POST['tipo'];
                $titulo = $_POST['titulo'];
                $mensagem = $_POST['mensagem'];
                $dados = json_decode($_POST['dados'] ?? '[]', true);
                $success = $notificationManager->criar($userId, $tipo, $titulo, $mensagem, $dados);
                echo json_encode(['success' => $success]);
                break;

            // ===== API PARA CANAIS (apenas admin) =====

            case 'listar_canais':
                $canais = $notificationManager->listarCanais();
                echo json_encode(['success' => true, 'canais' => $canais]);
                break;

            case 'criar_canal':
                if (!($_SESSION['user_admin'] ?? false)) {
                    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                    break;
                }
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                if ($nome) {
                    $id = $notificationManager->criarCanal($nome, $descricao);
                    echo json_encode(['success' => true, 'canal_id' => $id]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Nome obrigatório']);
                }
                break;

            case 'inscrever_usuario':
                if (!($_SESSION['user_admin'] ?? false)) {
                    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                    break;
                }
                $canalId = (int)$_POST['canal_id'];
                $userIdInsc = (int)$_POST['user_id'];
                $success = $notificationManager->inscreverUsuario($canalId, $userIdInsc);
                echo json_encode(['success' => $success]);
                break;

            case 'inscrever_setor':
                if (!($_SESSION['user_admin'] ?? false)) {
                    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                    break;
                }
                $canalId = (int)$_POST['canal_id'];
                $setorId = (int)$_POST['setor_id'];
                $success = $notificationManager->inscreverSetor($canalId, $setorId);
                echo json_encode(['success' => $success]);
                break;

            case 'desinscrever':
                if (!($_SESSION['user_admin'] ?? false)) {
                    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                    break;
                }
                $canalId = (int)$_POST['canal_id'];
                $tipo = $_POST['tipo'];
                $identificador = (int)$_POST['identificador'];
                $success = $notificationManager->desinscrever($canalId, $tipo, $identificador);
                echo json_encode(['success' => $success]);
                break;

            case 'listar_inscricoes':
                $canalId = (int)$_POST['canal_id'];
                $inscricoes = $notificationManager->listarInscricoes($canalId);
                echo json_encode(['success' => true, 'inscricoes' => $inscricoes]);
                break;

            case 'adicionar_tipo_notificacao':
                if (!($_SESSION['user_admin'] ?? false)) {
                    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                    break;
                }
                $canalId = (int)$_POST['canal_id'];
                $tipo = $_POST['tipo'];
                $success = $notificationManager->adicionarTipoNotificacao($canalId, $tipo);
                echo json_encode(['success' => $success]);
                break;

            case 'remover_tipo_notificacao':
                if (!($_SESSION['user_admin'] ?? false)) {
                    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
                    break;
                }
                $canalId = (int)$_POST['canal_id'];
                $tipo = $_POST['tipo'];
                $success = $notificationManager->removerTipoNotificacao($canalId, $tipo);
                echo json_encode(['success' => $success]);
                break;

            case 'listar_tipos_notificacao':
                $canalId = (int)$_POST['canal_id'];
                $tipos = $notificationManager->listarTiposNotificacao($canalId);
                echo json_encode(['success' => true, 'tipos' => $tipos]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Ação desconhecida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Renderizar view apenas quando este arquivo for acessado diretamente (evita quebrar o SPA quando incluído).
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $notificacoes = $notificationManager->buscarTodas((int)$userId, 1, 20);
    $totalNotificacoes = $notificationManager->contarTodas((int)$userId);
    $preferencias = $notificationManager->buscarPreferencias((int)$userId);
    $tiposDisponiveis = ['backup', 'tarefa', 'erro', 'alerta', 'sistema']; // Pode ser dinâmico
    require __DIR__ . '/notificacoes_view.php';
}
?>
