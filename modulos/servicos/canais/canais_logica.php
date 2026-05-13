<?php
// modulos/servicos/canais/canais_logica.php
// API para administração de canais de notificação

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Verificar se é admin
if (!($_SESSION['user_admin'] ?? false)) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

// Configuração do banco
$config = require __DIR__ . '/../../../conf/db.php';
$pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
               $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

require_once __DIR__ . '/../notificacoes/notificacoes_lib.php';
$notificationManager = new NotificationManager($pdo);

// API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    ob_clean();

    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'listar_canais':
                $canais = $notificationManager->listarCanais();
                echo json_encode(['success' => true, 'canais' => $canais]);
                break;

            case 'criar_canal':
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
                $canalId = (int)$_POST['canal_id'];
                $userId = (string)$_POST['user_id'];
                $success = $notificationManager->inscreverUsuario($canalId, (int)$userId);
                echo json_encode(['success' => $success]);
                break;

            case 'inscrever_setor':
                $canalId = (int)$_POST['canal_id'];
                $setorSlug = $_POST['setor_id'];
                $success = $notificationManager->inscreverSetor($canalId, $setorSlug);
                echo json_encode(['success' => $success]);
                break;

            case 'desinscrever':
                $canalId = (int)$_POST['canal_id'];
                $tipo = $_POST['tipo'];
                $identificador = (string)$_POST['identificador'];
                $success = $notificationManager->desinscrever($canalId, $tipo, $identificador);
                echo json_encode(['success' => $success]);
                break;

            case 'listar_inscricoes':
                $canalId = (int)$_POST['canal_id'];
                $inscricoes = $notificationManager->listarInscricoes($canalId);
                echo json_encode(['success' => true, 'inscricoes' => $inscricoes]);
                break;

            case 'adicionar_tipo_notificacao':
                $canalId = (int)$_POST['canal_id'];
                $tipo = $_POST['tipo'];
                $success = $notificationManager->adicionarTipoNotificacao($canalId, $tipo);
                echo json_encode(['success' => $success]);
                break;

            case 'remover_tipo_notificacao':
                $canalId = (int)$_POST['canal_id'];
                $tipo = $_POST['tipo'];
                $success = $notificationManager->removerTipoNotificacao($canalId, $tipo);
                echo json_encode(['success' => $success]);
                break;

            case 'editar_canal':
                $canalId = (int)$_POST['canal_id'];
                $nome = trim($_POST['nome'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                if ($nome && $canalId) {
                    $success = $notificationManager->editarCanal($canalId, $nome, $descricao);
                    echo json_encode(['success' => $success]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
                }
                break;

            case 'deletar_canal':
                $canalId = (int)$_POST['canal_id'];
                if ($canalId) {
                    $success = $notificationManager->deletarCanal($canalId);
                    echo json_encode(['success' => $success]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'ID inválido']);
                }
                break;

            case 'listar_usuarios':
                $usuarios = $notificationManager->listarUsuarios();
                echo json_encode(['success' => true, 'usuarios' => $usuarios]);
                break;

            case 'listar_setores':
                $setores = $notificationManager->listarSetores();
                echo json_encode(['success' => true, 'setores' => $setores]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Ação desconhecida']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Renderizar view
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require __DIR__ . '/canais_view.php';
}
?>
