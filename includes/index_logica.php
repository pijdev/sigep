<?php
// includes/index_logica.php
// Lógica PHP para index.php

session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header("Location: /autenticacao");
    exit;
}

$isKioskMode = isset($_SESSION['kiosk_mode']) && (int)$_SESSION['kiosk_mode'] === 1;

if (!$isKioskMode && isset($_SESSION['ultimo_clique'])) {
    $inatividade = time() - $_SESSION['ultimo_clique'];
    if ($inatividade > 600) {
        $usuario_id = $_SESSION['user_id'] ?? null;
        $usuario_nome = $_SESSION['user_nome'] ?? null;

        // Registrar auditoria de sessão expirada
        if ($usuario_id) {
            try {
                require_once __DIR__ . '/../auth/security_functions.php';
                $config = require __DIR__ . '/../conf/db.php';
                $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);

                // Salvar cookie para lockscreen antes de destruir sessão
                $lockscreen_data = [
                    'usuario_id' => $usuario_id,
                    'usuario_nome' => $usuario_nome,
                    'timestamp' => time(),
                    'token' => hash('sha256', $usuario_id . $usuario_nome . time() . 'sigep_lockscreen')
                ];

                $lockscreen_cookie = base64_encode(json_encode($lockscreen_data));
                setcookie('sigep_last_user', $lockscreen_cookie, time() + 300, '/', '', true, true); // 5 minutos

                registrarAuditoria($pdo, 'sessao_expirou', $usuario_id, $usuario_nome, [
                    'inatividade_segundos' => $inatividade
                ]);
            } catch (PDOException $e) {
                error_log("Erro auditoria sessão expirada: " . $e->getMessage());
            }
        }

        session_unset();
        session_destroy();
        header("Location: /autenticacao?msg=expirou");
        exit;
    }
}

$_SESSION['ultimo_clique'] = time();

$pdo = null;
try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Erro Crítico no Banco.");
}

// Função para verificar sessão (usada em páginas específicas)
function verificarSessao()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /autenticacao");
        exit;
    }
}
