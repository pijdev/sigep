<?php
session_start();
require_once __DIR__ . '/session_auth.php';
require_once __DIR__ . '/security_functions.php';

// Verificar cookie "lembrar-me" antes de verificar sessão
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_sigep'])) {
    try {
        $config = require_once __DIR__ . '/../conf/db.php';
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $remember_token = $_COOKIE['remember_sigep'];

        // Buscar usuário pelo token
        $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ? AND remember_expiry > NOW() AND status = 'Ativo' LIMIT 1");
        $stmt->execute([$remember_token]);
        $row = $stmt->fetch();

        if ($row) {
            // Token válido - fazer login automático
            sigep_apply_user_session($row, false);

            // Gerar novo token para segurança
            $new_token = hash('sha256', $row['id'] . $row['usuario'] . time() . 'sigep_remember');
            $new_expiry = time() + (30 * 24 * 60 * 60);

            $stmt_update = $pdo->prepare("UPDATE users SET remember_token = ?, remember_expiry = ? WHERE id = ?");
            $stmt_update->execute([$new_token, date('Y-m-d H:i:s', $new_expiry), $row['id']]);

            // Atualizar cookie
            setcookie('remember_sigep', $new_token, $new_expiry, '/', '', true, true);

            registrarAuditoria($pdo, 'login_sucesso', $row['id'], $row['usuario'], ['lembrar_me_auto' => true]);

            header("Location: /");
            exit;
        } else {
            // Token inválido - limpar cookie
            setcookie('remember_sigep', '', time() - 3600, '/', '', true, true);
        }
    } catch (PDOException $e) {
        // Erro no banco - continuar normalmente
        error_log("Erro remember-me: " . $e->getMessage());
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// Processar mensagens amigáveis
$msg = $_GET['msg'] ?? '';
$erro = '';

switch ($msg) {
    case 'sucesso':
        $erro = "Logout realizado com sucesso!";
        break;
    case 'expirou':
        $erro = "Sua sessão expirou. Faça login novamente.";
        break;
    case 'bloqueado':
        $erro = "Sua conta está bloqueada. Procure contato com a administração.";
        break;
    case 'erro':
        $erro = "Ocorreu um erro. Tente novamente.";
        break;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize variables outside try block for catch block access
    $pdo = null;
    $usuario_form = null;
    $senha_form = null;

    try {
        $config = require_once __DIR__ . '/../conf/db.php';
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $usuario_form = trim($_POST['u']);
        $senha_form   = $_POST['s'];
        $is_lockscreen_login = isset($_POST['lockscreen_user']);

        // Se for lockscreen, buscar usuário pelo ID
        if ($is_lockscreen_login) {
            $lockscreen_user_id = (int)$_POST['lockscreen_user'];
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$lockscreen_user_id]);
            $row = $stmt->fetch();

            if ($row) {
                $usuario_form = $row['usuario'];
            }
        }

        // Validar CSRF token
        if (!validarCSRFToken($_POST['csrf_token'] ?? '')) {
            $erro = "Requisição inválida. Tente novamente.";
            registrarAuditoria($pdo, 'login_falha', null, $usuario_form, ['motivo' => 'csrf_invalid']);
        } elseif (!empty($usuario_form) && !empty($senha_form)) {

            // Verificar rate limiting
            $rate_limit = verificarRateLimit($pdo, $usuario_form);

            if ($rate_limit['bloqueado']) {
                $tempo_restante = ceil((strtotime($rate_limit['bloqueado_ate']) - time()) / 60);
                $erro = "Muitas tentativas. Tente novamente em {$tempo_restante} minutos.";
                registrarAuditoria($pdo, 'login_falha', null, $usuario_form, [
                    'motivo' => 'rate_limit_bloqueado',
                    'tentativas' => $rate_limit['tentativas'],
                    'bloqueado_ate' => $rate_limit['bloqueado_ate']
                ]);
            } else {

                $stmt = $pdo->prepare("SELECT * FROM users WHERE usuario = ? LIMIT 1");
                $stmt->execute([$usuario_form]);
                $row = $stmt->fetch();

                if ($row && password_verify($senha_form, $row['senha'])) {
                    if ($row['status'] === 'Inativo') {
                        $erro = "Sua conta está bloqueada. Procure contato com a administração.";
                        registrarAuditoria($pdo, 'conta_bloqueada', $row['id'], $usuario_form, ['motivo' => 'status_inativo']);
                        header("Location: /autenticacao?msg=bloqueado");
                        exit;
                    } else {
                        // Login bem sucedido
                        sigep_apply_user_session($row, false);
                        resetarRateLimit($pdo, $usuario_form);

                        // Processar "lembrar-me"
                        if (isset($_POST['lembrar_me'])) {
                            $remember_token = hash('sha256', $row['id'] . $row['usuario'] . time() . 'sigep_remember');
                            $remember_expiry = time() + (30 * 24 * 60 * 60); // 30 dias

                            // Salvar token no banco
                            $stmt_token = $pdo->prepare("UPDATE users SET remember_token = ?, remember_expiry = ? WHERE id = ?");
                            $stmt_token->execute([$remember_token, date('Y-m-d H:i:s', $remember_expiry), $row['id']]);

                            // Setar cookie
                            setcookie('remember_sigep', $remember_token, $remember_expiry, '/', '', true, true);
                        }

                        registrarAuditoria($pdo, 'login_sucesso', $row['id'], $usuario_form, [
                            'lembrar_me' => isset($_POST['lembrar_me'])
                        ]);

                        header("Location: /");
                        exit;
                    }
                } else {
                    // Login falhou
                    $rate_info = incrementarRateLimit($pdo, $usuario_form);

                    if ($rate_info['bloqueado']) {
                        $tempo_restante = ceil((strtotime($rate_info['bloqueado_ate']) - time()) / 60);
                        $erro = "Muitas tentativas. Tente novamente em {$tempo_restante} minutos.";
                        registrarAuditoria($pdo, 'login_falha', null, $usuario_form, [
                            'motivo' => 'rate_limit_bloqueado',
                            'tentativas' => $rate_info['tentativas'],
                            'bloqueado_ate' => $rate_info['bloqueado_ate']
                        ]);
                    } else {
                        $tentativas_restantes = ($usuario_form ? 3 : 5) - $rate_info['tentativas'];
                        $erro = "Credenciais incorretas. Tente novamente. ({$tentativas_restantes} tentativas restantes)";
                        registrarAuditoria($pdo, 'login_falha', null, $usuario_form, [
                            'motivo' => 'credenciais_invalidas',
                            'tentativas' => $rate_info['tentativas'],
                            'restantes' => $tentativas_restantes
                        ]);
                    }
                }
            }
        } else {
            $erro = "Preencha todos os campos.";
            registrarAuditoria($pdo, 'login_falha', null, $usuario_form, ['motivo' => 'campos_vazios']);
        }
    } catch (PDOException $e) {
        $erro = "Erro no sistema. Tente novamente mais tarde.";
        registrarAuditoria($pdo, 'login_falha', null, $usuario_form, [
            'motivo' => 'erro_banco',
            'erro' => $e->getMessage()
        ]);
    }
}

// Gerar CSRF token para o formulário
$csrf_token = gerarCSRFToken();

// Verificar modo lockscreen
$lockscreen_data = null;
$is_lockscreen = false;

if (isset($_COOKIE['sigep_last_user'])) {
    try {
        $lockscreen_data = json_decode(base64_decode($_COOKIE['sigep_last_user']), true);

        // Validar token e timestamp
        if (
            $lockscreen_data &&
            isset($lockscreen_data['timestamp'], $lockscreen_data['token']) &&
            (time() - $lockscreen_data['timestamp']) < 300
        ) { // 5 minutos

            $expected_token = hash(
                'sha256',
                $lockscreen_data['usuario_id'] .
                    $lockscreen_data['usuario_nome'] .
                    $lockscreen_data['timestamp'] .
                    'sigep_lockscreen'
            );

            if (hash_equals($expected_token, $lockscreen_data['token'])) {
                $is_lockscreen = true;
            }
        }
    } catch (Exception $e) {
        // Cookie inválido, ignorar
        $is_lockscreen = false;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="UTF-8">
    <title>SIGEP - Login</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Segoe UI, Roboto, Arial, sans-serif;
        }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #2563eb 100%);
            overflow: hidden;
            position: relative;
        }

        /* partículas animadas de fundo */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, rgba(255, 255, 255, 0.08) 0%, transparent 50%);
            animation: particles 20s ease-in-out infinite alternate;
            z-index: 1;
        }

        @keyframes particles {
            0% {
                transform: translate(0px, 0px) rotate(0deg);
            }

            50% {
                transform: translate(-20px, -10px) rotate(1deg);
            }

            100% {
                transform: translate(20px, 10px) rotate(-1deg);
            }
        }

        /* layout */

        .container {
            display: flex;
            height: 100vh;
        }

        /* AREA IMAGEM */

        .left {
            flex: 2;
            position: relative;
            overflow: hidden;
            background: #000;
        }

        .left img {

            position: absolute;
            width: 110%;
            height: 110%;
            object-fit: cover;
            animation: zoomMove 25s ease-in-out infinite alternate;

        }

        /* animação de movimento */

        @keyframes zoomMove {

            0% {
                transform: scale(1) translate(0px, 0px);
            }

            50% {
                transform: scale(1.1) translate(-20px, -10px);
            }

            100% {
                transform: scale(1.15) translate(20px, 10px);
            }

        }

        /* AREA LOGIN */

        .right {

            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(180deg,
                    #0f172a,
                    #020617);

        }

        /* box */

        .login-box {

            width: 100%;
            max-width: 400px;
            padding: 45px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);

            animation: fadeIn 0.8s ease-out;
            position: relative;
            z-index: 10;
        }

        /* brilho sutil no glass */
        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            border-radius: 20px 20px 0 0;
        }

        /* animação entrada */

        @keyframes fadeIn {

            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }

        .logo {

            font-size: 34px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;

        }

        .subtitle {

            color: #94a3b8;
            margin-bottom: 35px;

        }

        /* campos */

        .input-group {
            margin-bottom: 18px;
        }

        .input-group label {
            font-size: 13px;
            color: #cbd5f5;
            display: block;
            margin-bottom: 6px;
        }

        .input-group input {

            width: 100%;
            padding: 15px 20px 15px 50px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .input-group input:focus {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.3);
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.1), 0 0 0 1px rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .input-group .icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            font-size: 18px;
            transition: color 0.3s ease;
        }

        .input-group input:focus+.icon {
            color: rgba(255, 255, 255, 0.7);
        }

        /* botão */

        .btn {

            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 50%, #1d4ed8 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            transform: translateY(-2px);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(0);
        }

        /* loading spinner */
        .btn.loading {
            color: transparent;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* lembrar-me */

        .remember-group {
            margin-bottom: 20px;
        }

        .remember-label {
            display: flex;
            align-items: center;
            cursor: pointer;
            font-size: 13px;
            color: #cbd5f5;
        }

        .remember-label input[type=checkbox] {
            display: none;
        }

        .remember-switch {
            width: 40px;
            height: 20px;
            background: #334155;
            border-radius: 20px;
            position: relative;
            margin-right: 10px;
            transition: 0.3s;
        }

        .remember-switch::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: #94a3b8;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: 0.3s;
        }

        .remember-label input:checked+.remember-switch {
            background: #3b82f6;
        }

        .remember-label input:checked+.remember-switch::after {
            transform: translateX(20px);
            background: white;
        }

        .remember-text {
            user-select: none;
        }

        /* links */

        .links {

            margin-top: 18px;
            display: flex;
            justify-content: space-between;
            font-size: 13px;

        }

        .links button {

            font-size: 12px;
            padding: 2px 5px;
            margin: 0 5px;

        }

        .links a {

            color: #60a5fa;
            text-decoration: none;

        }

        .links a:hover {

            text-decoration: underline;

        }

        /* mostrar senha */

        .password-wrapper {
            position: relative;
        }

        .show-pass {

            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            font-size: 13px;

        }

        /* erro */

        .erro {

            background: #7f1d1d;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            color: #fecaca;
            font-size: 14px;

        }

        /* lockscreen */
        .lockscreen-box {
            width: 100%;
            max-width: 380px;
            padding: 40px;
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            text-align: center;
            animation: fadeIn 1s ease;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #3b82f6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: white;
            font-weight: bold;
        }

        .user-name {
            font-size: 24px;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
        }

        .lock-message {
            color: #94a3b8;
            margin-bottom: 30px;

            .lock-form {
                width: 100%;
            }

            /* input */

            .input-group {
                position: relative;
                margin-bottom: 25px;
            }

            .input-group input {
                width: 100%;
                padding: 15px 20px 15px 50px;
                background: rgba(255, 255, 255, 0.05);
                border: 2px solid rgba(255, 255, 255, 0.1);
                border-radius: 12px;
                color: white;
                font-size: 16px;
                transition: all 0.3s ease;
                outline: none;
            }

            .input-group input::placeholder {
                color: rgba(255, 255, 255, 0.5);
                border-radius: 8px;
                color: white;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: 0.2s;
                margin-bottom: 15px;
            }

            .unlock-btn:hover {
                background: #2563eb;
            }

            .unlock-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
            }

            .other-user-btn {
                background: none;
                border: none;
                color: #60a5fa;
                font-size: 14px;
                cursor: pointer;
                text-decoration: underline;
                transition: 0.2s;
            }

            .other-user-btn:hover {
                color: #3b82f6;
            }

            /* mobile */

            @media(max-width:900px) {

                .left {
                    display: none;
                }

                .right {
                    flex: 1;
                }

                body {
                    overflow: auto;
                }

            }

            /* Animação shake para erros de validação */
            @keyframes shake {

                0%,
                100% {
                    transform: translateX(0);
                }

                10%,
                30%,
                50%,
                70%,
                90% {
                    transform: translateX(-5px);
                }

                20%,
                40%,
                60%,
                80% {
                    transform: translateX(5px);
                }
            }

            /* Validação Real-time */
            .validation-feedback {
                display: block;
                font-size: 12px;
                margin-top: 5px;
                min-height: 16px;
                transition: all 0.3s ease;
            }

            .validation-feedback.valid {
                color: #10b981;
            }

            .validation-feedback.invalid {
                color: #ef4444;
            }

            .validation-feedback.weak {
                color: #f59e0b;
            }

            .validation-feedback.medium {
                color: #3b82f6;
            }

            .validation-feedback.strong {
                color: #10b981;
            }

            /* Indicador de força de senha */
            .password-strength {
                margin-top: 8px;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .password-strength.visible {
                opacity: 1;
            }

            .strength-bar {
                width: 100%;
                height: 4px;
                background: rgba(255, 255, 255, 0.1);
                border-radius: 2px;
                overflow: hidden;
                margin-bottom: 4px;
            }

            .strength-fill {
                height: 100%;
                width: 0%;
                transition: all 0.3s ease;
                border-radius: 2px;
            }

            .strength-fill.weak {
                width: 33%;
                background: #ef4444;
            }

            .strength-fill.medium {
                width: 66%;
                background: #f59e0b;
            }

            .strength-fill.strong {
                width: 100%;
                background: #10b981;
            }

            .strength-text {
                font-size: 11px;
                color: rgba(255, 255, 255, 0.6);
                display: block;
            }

            /* Estados de input validados */
            .input-group input.valid {
                border-color: rgba(16, 185, 129, 0.3);
                box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.2);
            }

            .input-group input.invalid {
                border-color: rgba(239, 68, 68, 0.3);
                box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.2);
                animation: shake 0.5s ease;
            }

            /* Language Selector Sutil */
            .language-selector-simple {
                display: flex;
                justify-content: center;
                gap: 12px;
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }

            .lang-flag-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.1);
                color: white;
                font-size: 18px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .lang-flag-btn:hover {
                background: rgba(255, 255, 255, 0.1);
                border-color: rgba(255, 255, 255, 0.2);
                transform: scale(1.05);
            }

            .lang-flag-btn:active {
                transform: scale(0.95);
            }

            /* Remover CSS antigo do seletor */
            .language-selector,
            .lang-btn,
            .lang-menu,
            .lang-option {
                display: none !important;
            }

            /* Estados de input validados */
    </style>

</head>

<body>

    <div class="container">

        <!-- imagem -->

        <div class="left">

            <img src="/assets/img/sigep_light.png">

        </div>


        <!-- login -->

        <div class="right">

            <?php if ($is_lockscreen && $lockscreen_data): ?>
                <!-- LOCKSCREEN MODE -->
                <div class="lockscreen-box">

                    <div class="user-avatar">
                        <?= strtoupper(substr($lockscreen_data['usuario_nome'], 0, 1)) ?>
                    </div>

                    <div class="user-name"><?= htmlspecialchars($lockscreen_data['usuario_nome']) ?></div>
                    <div class="lock-message">
                        <?= $msg === 'sucesso' ? 'Logout realizado. Desbloqueie para continuar.' : 'Sua sessão expirou. Desbloqueie para continuar.' ?>
                    </div>

                    <?php if (!empty($erro)): ?>
                        <div class="erro"><?= $erro ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/autenticacao" class="lock-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="lockscreen_user" value="<?= htmlspecialchars($lockscreen_data['usuario_id']) ?>">

                        <div class="lock-input-group">
                            <input type="password" name="s" class="lock-input" placeholder="Digite sua senha" required>
                        </div>

                        <button type="submit" class="unlock-btn" id="unlockBtn">
                            <span id="btnText">Desbloquear</span>
                        </button>

                        <button type="button" class="other-user-btn" onclick="switchToNormalLogin()">
                            Usar outra conta
                        </button>
                    </form>

                </div>

            <?php else: ?>
                <!-- NORMAL LOGIN MODE -->
                <div class="login-box">

                    <div class="logo">SIGEP</div>
                    <div class="subtitle" data-i18n="subtitle">Sistema Prisional Integrado</div>

                    <?php if (!empty($erro)): ?>
                        <div class="erro"><?= $erro ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/autenticacao">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="input-group">
                            <label data-i18n="usuario">Usuário</label>
                            <input type="text" name="u" id="usuario" required>
                            <span class="validation-feedback" id="usuario-feedback"></span>
                        </div>

                        <div class="input-group password-wrapper">
                            <label data-i18n="senha">Senha</label>

                            <input type="password" name="s" id="senha" required>
                            <span class="show-pass" onclick="toggleSenha()" data-i18n="ver">ver</span>
                            <div class="password-strength" id="password-strength">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strength-fill"></div>
                                </div>
                                <span class="strength-text" id="strength-text" data-i18n="forca_senha">Força da senha</span>
                            </div>
                            <span class="validation-feedback" id="senha-feedback"></span>
                        </div>

                        <div class="remember-group">
                            <label class="remember-label">
                                <input type="checkbox" name="lembrar_me" id="lembrar_me">
                                <span class="remember-switch"></span>
                                <span class="remember-text" data-i18n="lembrar">Manter conectado neste dispositivo</span>
                            </label>
                        </div>

                        <button class="btn" data-i18n="login">
                            Entrar
                        </button>

                    </form>

                    <div class="links">
                        <button type="button" class="btn btn-link text-info" onclick="esqueciSenha()" data-i18n="esqueci">Esqueci minha senha</button>
                        <button type="button" class="btn btn-link text-info" onclick="registrarSe()" data-i18n="registrar">Registrar-se</button>
                    </div>

                    <!-- Language Selector Sutil -->
                    <div class="language-selector-simple">
                        <button type="button" class="lang-flag-btn" onclick="setLanguage('pt')" title="Português">
                            🇧🇷
                        </button>
                        <button type="button" class="lang-flag-btn" onclick="setLanguage('en')" title="English">
                            🇺🇸
                        </button>
                    </div>

                </div>
            <?php endif; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Funções Globais (FORA de qualquer escopo - acessíveis pelo HTML)
        let currentLang = 'pt';
        const translations = {
            pt: {
                login: "Entrar",
                usuario: "Usuário",
                senha: "Senha",
                lembrar: "Manter conectado neste dispositivo",
                esqueci: "Esqueci minha senha",
                registrar: "Registrar-se",
                subtitle: "Sistema Prisional Integrado",
                ver: "ver",
                forca_senha: "Força da senha",
                senha_fraca: "Senha fraca",
                senha_media: "Senha média",
                senha_forte: "Senha forte",
                usuario_min: "Mínimo 3 caracteres",
                usuario_max: "Máximo 20 caracteres",
                usuario_chars: "Apenas letras, números e _",
                usuario_valido: "✓ Usuário válido",
                senha_min: "Mínimo 8 caracteres",
                senha_razoavel: "Senha razoável",
                senha_excelente: "Senha excelente",
                senha_adicione: "Adicione mais caracteres e variedade",
                entendi: "Entendi",
                ok: "OK"
            },
            en: {
                login: "Sign In",
                usuario: "Username",
                senha: "Password",
                lembrar: "Remember me on this device",
                esqueci: "Forgot password",
                registrar: "Sign Up",
                subtitle: "Integrated Prison System",
                ver: "show",
                forca_senha: "Password strength",
                senha_fraca: "Weak password",
                senha_media: "Medium password",
                senha_forte: "Strong password",
                usuario_min: "Minimum 3 characters",
                usuario_max: "Maximum 20 characters",
                usuario_chars: "Only letters, numbers and _",
                usuario_valido: "✓ Valid username",
                senha_min: "Minimum 8 characters",
                senha_razoavel: "Reasonable password",
                senha_excelente: "Excellent password",
                senha_adicione: "Add more characters and variety",
                entendi: "Understood",
                ok: "OK"
            }
        };

        // Função de tradução (global)
        function t(key) {
            return translations[currentLang][key] || key;
        }

        // Atribuir funções explicitamente ao window
        window.setLanguage = function(lang) {
            setCookie('sigep_lang', lang, 365);
            updateLanguage(lang);

            // Atualizar validações com novo idioma
            updateValidationMessages();
        };

        window.updateValidationMessages = function() {
            // Re-validar campos para atualizar mensagens
            const usuarioInput = document.getElementById('usuario');
            const senhaInput = document.getElementById('senha');

            if (usuarioInput) {
                usuarioInput.dispatchEvent(new Event('input'));
            }

            if (senhaInput) {
                senhaInput.dispatchEvent(new Event('input'));
            }
        };

        window.toggleLanguageMenu = function() {
            // Não usado no novo design, mas mantido para compatibilidade
            console.log('toggleLanguageMenu chamado');
        };

        window.toggleSenha = function() {
            let campo = document.getElementById("senha");
            if (campo.type === "password") {
                campo.type = "text";
            } else {
                campo.type = "password";
            }
        };

        window.esqueciSenha = function() {
            Swal.fire({
                title: t('esqueci'),
                text: 'Entre em contato com a administração para solicitar uma nova senha.',
                icon: 'info',
                confirmButtonText: t('entendi')
            });
        };

        window.registrarSe = function() {
            Swal.fire({
                title: t('registrar'),
                text: 'Função em desenvolvimento.',
                icon: 'info',
                confirmButtonText: t('ok')
            });
        };

        window.switchToNormalLogin = function() {
            // Limpar cookie de lockscreen
            document.cookie = 'sigep_last_user=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
            // Recarregar página
            window.location.reload();
        };

        // Função para calcular força da senha (global)
        function calculatePasswordStrength(password) {
            let score = 0;

            // Comprimento
            if (password.length >= 8) score += 1;
            if (password.length >= 12) score += 1;

            // Letras maiúsculas
            if (/[A-Z]/.test(password)) score += 1;

            // Letras minúsculas
            if (/[a-z]/.test(password)) score += 1;

            // Números
            if (/[0-9]/.test(password)) score += 1;

            // Caracteres especiais
            if (/[^A-Za-z0-9]/.test(password)) score += 1;

            // Determinar nível
            if (score <= 2) {
                return {
                    level: 'weak',
                    message: t('senha_fraca'),
                    score: score
                };
            } else if (score <= 4) {
                return {
                    level: 'medium',
                    message: t('senha_media'),
                    score: score
                };
            } else {
                return {
                    level: 'strong',
                    message: t('senha_forte'),
                    score: score
                };
            }
        }

        // Funções auxiliares globais
        window.getCookie = function(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) return parts.pop().split(';').shift();
            return null;
        };

        window.setCookie = function(name, value, days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = `expires=${date.toUTCString()}`;
            document.cookie = `${name}=${value};${expires};path=/`;
        };

        window.updateLanguage = function(lang) {
            currentLang = lang;

            // Atualizar todos os elementos com data-i18n
            document.querySelectorAll('[data-i18n]').forEach(element => {
                const key = element.getAttribute('data-i18n');
                element.textContent = t(key);
            });

            // Atualizar placeholder dos inputs
            const usuarioInput = document.getElementById('usuario');
            const senhaInput = document.getElementById('senha');
            if (usuarioInput) usuarioInput.placeholder = t('usuario');
            if (senhaInput) senhaInput.placeholder = t('senha');

            // Atualizar botões
            const loginBtn = document.querySelector('.btn[data-i18n="login"]');
            if (loginBtn) loginBtn.textContent = t('login');

            // Atualizar seletor de idioma
            updateLanguageSelector(lang);
        };

        window.updateLanguageSelector = function(lang) {
            const langFlag = document.getElementById('langFlag');
            const langText = document.getElementById('langText');

            const flags = {
                pt: '🇧🇷',
                en: '🇺🇸'
            };

            const texts = {
                pt: 'PT',
                en: 'EN'
            };

            if (langFlag) langFlag.textContent = flags[lang];
            if (langText) langText.textContent = texts[lang];
        };

        // Funções auxiliares (dentro do DOMContentLoaded)
        document.addEventListener('DOMContentLoaded', function() {
            const unlockBtn = document.getElementById('unlockBtn');
            const btnText = document.getElementById('btnText');

            if (unlockBtn && btnText) {
                unlockBtn.addEventListener('click', function() {
                    btnText.innerHTML = 'Desbloqueando...';
                    unlockBtn.disabled = true;

                    // Reabilitar após 3 segundos (fallback)
                    setTimeout(function() {
                        btnText.innerHTML = 'Desbloquear';
                        unlockBtn.disabled = false;
                    }, 3000);
                });
            }

            // Loading spinner no botão entrar
            const loginForm = document.querySelector('form[action="/autenticacao"]:not(.lock-form)');
            const loginBtn = document.querySelector('.btn:not(.unlock-btn)');

            if (loginForm && loginBtn) {
                loginForm.addEventListener('submit', function(e) {
                    loginBtn.classList.add('loading');
                    loginBtn.disabled = true;
                });
            }

            // Animação shake para erros
            const errorElements = document.querySelectorAll('.erro');
            errorElements.forEach(error => {
                error.style.animation = 'shake 0.5s ease';
            });

            // Validação Real-time
            const usuarioInput = document.getElementById('usuario');
            const senhaInput = document.getElementById('senha');
            const usuarioFeedback = document.getElementById('usuario-feedback');
            const senhaFeedback = document.getElementById('senha-feedback');
            const passwordStrength = document.getElementById('password-strength');
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');

            // Validação do usuário
            if (usuarioInput && usuarioFeedback) {
                usuarioInput.addEventListener('input', function() {
                    const value = this.value.trim();

                    if (value.length === 0) {
                        usuarioFeedback.textContent = '';
                        usuarioFeedback.className = 'validation-feedback';
                        this.classList.remove('valid', 'invalid');
                    } else if (value.length < 3) {
                        usuarioFeedback.textContent = t('usuario_min');
                        usuarioFeedback.className = 'validation-feedback invalid';
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                    } else if (value.length > 20) {
                        usuarioFeedback.textContent = t('usuario_max');
                        usuarioFeedback.className = 'validation-feedback invalid';
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                    } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                        usuarioFeedback.textContent = t('usuario_chars');
                        usuarioFeedback.className = 'validation-feedback invalid';
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                    } else {
                        usuarioFeedback.textContent = t('usuario_valido');
                        usuarioFeedback.className = 'validation-feedback valid';
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                    }
                });
            }

            // Validação e força da senha
            if (senhaInput && senhaFeedback && passwordStrength) {
                senhaInput.addEventListener('input', function() {
                    const value = this.value;

                    if (value.length === 0) {
                        senhaFeedback.textContent = '';
                        senhaFeedback.className = 'validation-feedback';
                        passwordStrength.classList.remove('visible');
                        this.classList.remove('valid', 'invalid');
                    } else if (value.length < 8) {
                        senhaFeedback.textContent = t('senha_min');
                        senhaFeedback.className = 'validation-feedback invalid';
                        passwordStrength.classList.remove('visible');
                        this.classList.remove('valid');
                        this.classList.add('invalid');
                    } else {
                        // Calcular força da senha
                        const strength = calculatePasswordStrength(value);

                        // Mostrar indicador de força
                        passwordStrength.classList.add('visible');

                        // Atualizar barra de força
                        strengthFill.className = 'strength-fill ' + strength.level;

                        // Atualizar texto
                        const strengthTexts = {
                            weak: t('senha_fraca'),
                            medium: t('senha_media'),
                            strong: t('senha_forte')
                        };
                        strengthText.textContent = strengthTexts[strength.level];
                        strengthText.className = 'strength-text ' + strength.level;

                        // Feedback principal
                        senhaFeedback.textContent = '✓ ' + strength.message;
                        senhaFeedback.className = 'validation-feedback ' + strength.level;

                        // Estado do input
                        this.classList.remove('invalid');
                        this.classList.add('valid');
                    }
                });
            }

            // Funções auxiliares dentro do DOMContentLoaded
            function detectLanguage() {
                // Verificar cookie primeiro
                const cookieLang = getCookie('sigep_lang');
                if (cookieLang && translations[cookieLang]) {
                    return cookieLang;
                }

                // Detectar do navegador
                const browserLang = navigator.language || navigator.userLanguage;
                if (browserLang.startsWith('en')) {
                    return 'en';
                }

                // Fallback para português
                return 'pt';
            }

            function getCookie(name) {
                const value = `; ${document.cookie}`;
                const parts = value.split(`; ${name}=`);
                if (parts.length === 2) return parts.pop().split(';').shift();
                return null;
            }

            function setCookie(name, value, days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                const expires = `expires=${date.toUTCString()}`;
                document.cookie = `${name}=${value};${expires};path=/`;
            }

            function updateLanguage(lang) {
                currentLang = lang;

                // Atualizar todos os elementos com data-i18n
                document.querySelectorAll('[data-i18n]').forEach(element => {
                    const key = element.getAttribute('data-i18n');
                    element.textContent = t(key);
                });

                // Atualizar placeholder dos inputs
                const usuarioInput = document.getElementById('usuario');
                const senhaInput = document.getElementById('senha');
                if (usuarioInput) usuarioInput.placeholder = t('usuario');
                if (senhaInput) senhaInput.placeholder = t('senha');

                // Atualizar botões
                const loginBtn = document.querySelector('.btn[data-i18n="login"]');
                if (loginBtn) loginBtn.textContent = t('login');

                // Atualizar seletor de idioma
                updateLanguageSelector(lang);
            }

            function updateLanguageSelector(lang) {
                const langFlag = document.getElementById('langFlag');
                const langText = document.getElementById('langText');

                const flags = {
                    pt: '🇧🇷',
                    en: '🇺🇸'
                };

                const texts = {
                    pt: 'PT',
                    en: 'EN'
                };

                if (langFlag) langFlag.textContent = flags[lang];
                if (langText) langText.textContent = texts[lang];
            }

            // Inicializar idioma
            const detectedLang = detectLanguage();
            updateLanguage(detectedLang);
        });
    </script>

</body>

</html>
