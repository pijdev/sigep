<?php
session_start();
require_once __DIR__ . '/security_functions.php';

// Registrar auditoria do logout se houver usuário logado
$usuario_id = $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['user_nome'] ?? null;

if ($usuario_id) {
    try {
        $config = require_once __DIR__ . '/../conf/db.php';
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        // Registrar logout na auditoria
        registrarAuditoria($pdo, 'logout', $usuario_id, $usuario_nome, ['logout_manual' => true]);

        // Salvar cookie para lockscreen (dados do último usuário)
        $lockscreen_data = [
            'usuario_id' => $usuario_id,
            'usuario_nome' => $usuario_nome,
            'timestamp' => time(),
            'token' => hash('sha256', $usuario_id . $usuario_nome . time() . 'sigep_lockscreen')
        ];

        $lockscreen_cookie = base64_encode(json_encode($lockscreen_data));
        setcookie('sigep_last_user', $lockscreen_cookie, time() + 300, '/', '', true, true); // 5 minutos

    } catch (PDOException $e) {
        // Continuar mesmo se falhar auditoria
        error_log("Erro auditoria logout: " . $e->getMessage());
    }
}

session_unset();
session_destroy();
header("Location: /autenticacao?msg=sucesso");
exit;
?>
