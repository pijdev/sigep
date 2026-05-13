<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sessão não iniciada']);
    exit;
}

require_once '../conf/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
    $config['user'],
    $config['pass'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

$action = $_POST['action'] ?? '';
$uid = $_SESSION['user_id'];

// Limpa qualquer output anterior
if (ob_get_level() > 0) {
    ob_clean();
}
header('Content-Type: application/json');

function kioskGeneratePlainToken(): string
{
    return bin2hex(random_bytes(32));
}

if ($action === 'update_theme') {
    try {
        $dark = (int)$_POST['dark_mode'];
        $stmt = $pdo->prepare("UPDATE acesso_seguro SET dark_mode = ? WHERE id = ?");
        $stmt->execute([$dark, $uid]);
        $_SESSION['user_theme'] = $dark;
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'update_profile') {
    try {
        $nome = trim($_POST['nome']);
        $pass = !empty($_POST['nova_senha']) ? password_hash($_POST['nova_senha'], PASSWORD_DEFAULT) : null;

        if ($pass) {
            $stmt = $pdo->prepare("UPDATE acesso_seguro SET nome = ?, senha = ? WHERE id = ?");
            $stmt->execute([$nome, $pass, $uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE acesso_seguro SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $uid]);
        }
        $_SESSION['user_nome'] = $nome;
        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'toggle_kiosk_mode') {
    try {
        $enabled = isset($_POST['is_kiosk']) && (int)$_POST['is_kiosk'] === 1 ? 1 : 0;
        if ($enabled === 1) {
            $stmt = $pdo->prepare("UPDATE acesso_seguro SET is_kiosk = 1 WHERE id = ?");
            $stmt->execute([$uid]);
        } else {
            $stmt = $pdo->prepare("UPDATE acesso_seguro SET is_kiosk = 0, kiosk_token = NULL, kiosk_token_updated_at = NULL WHERE id = ?");
            $stmt->execute([$uid]);
        }

        echo json_encode(['success' => true, 'is_kiosk' => $enabled]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

if ($action === 'generate_kiosk_token' || $action === 'regenerate_kiosk_token') {
    try {
        $stmt = $pdo->prepare("SELECT id, is_kiosk, status, kiosk_token FROM acesso_seguro WHERE id = ? LIMIT 1");
        $stmt->execute([$uid]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
            exit;
        }
        if ((int)$user['is_kiosk'] !== 1) {
            echo json_encode(['success' => false, 'message' => 'Ative o Modo Kiosk antes de gerar token.']);
            exit;
        }
        if (($user['status'] ?? 'Inativo') !== 'Ativo') {
            echo json_encode(['success' => false, 'message' => 'Usuário inativo não pode gerar token.']);
            exit;
        }
        if ($action === 'generate_kiosk_token' && !empty($user['kiosk_token'])) {
            echo json_encode(['success' => false, 'code' => 'token_exists', 'message' => 'Já existe token ativo. Use Regenerar para substituir.']);
            exit;
        }

        $plainToken = kioskGeneratePlainToken();
        $hashToken = password_hash($plainToken, PASSWORD_DEFAULT);

        $stmtSave = $pdo->prepare("UPDATE acesso_seguro SET kiosk_token = ?, kiosk_token_updated_at = NOW() WHERE id = ?");
        $stmtSave->execute([$hashToken, $uid]);

        echo json_encode([
            'success' => true,
            'token' => $plainToken,
            'message' => $action === 'regenerate_kiosk_token'
                ? 'Token regenerado com sucesso.'
                : 'Token gerado com sucesso.'
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
