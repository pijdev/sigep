<?php
session_start();

require_once __DIR__ . '/session_auth.php';

function kiosk_deny(): void
{
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Acesso invalido.';
    exit;
}

if (isset($_SESSION['user_id']) && (int)($_SESSION['kiosk_mode'] ?? 0) === 1) {
    header('Location: /');
    exit;
}

$token = trim((string)($_GET['t'] ?? ''));
if ($token === '') {
    kiosk_deny();
}

try {
    $config = require_once __DIR__ . '/../conf/db.php';
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['user'],
        $config['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmtUser = $pdo->prepare("
        SELECT *
        FROM users
        WHERE is_kiosk = 1
          AND status = 'Ativo'
          AND kiosk_token IS NOT NULL
    ");
    $stmtUser->execute();
    $users = $stmtUser->fetchAll();

    $user = null;
    foreach ($users as $candidate) {
        if (!empty($candidate['kiosk_token']) && password_verify($token, $candidate['kiosk_token'])) {
            $user = $candidate;
            break;
        }
    }

    if (!$user) {
        kiosk_deny();
    }

    session_regenerate_id(true);
    sigep_apply_user_session($user, true);
    header('Location: /');
    exit;
} catch (Throwable $e) {
    kiosk_deny();
}
