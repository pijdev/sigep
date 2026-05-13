<?php
// auth/security_functions.php
// Funções de segurança para auditoria e rate limiting

if (!function_exists('registrarAuditoria')) {
    function registrarAuditoria($pdo, $acao, $usuario_id = null, $usuario_nome = null, $detalhes = [])
    {
        // Se não houver conexão com banco, registrar em log e retornar false
        if (!$pdo) {
            error_log("AUDITORIA SEM BD: $acao - Usuario: $usuario_nome - IP: " . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0') . " - Detalhes: " . json_encode($detalhes));
            return false;
        }

        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessao_id = session_id();

        $detalhes_json = !empty($detalhes) ? json_encode($detalhes) : null;

        $sql = "INSERT INTO user_sessions
                (usuario_id, usuario_nome, ip_address, user_agent, acao, detalhes, sessao_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$usuario_id, $usuario_nome, $ip_address, $user_agent, $acao, $detalhes_json, $sessao_id]);
    }
}

if (!function_exists('verificarRateLimit')) {
    function verificarRateLimit($pdo, $usuario = null)
    {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Verificar se está bloqueado
        $sql_check = "SELECT tentativas, bloqueado_ate, ultimo_reset
                     FROM user_login_attempts
                     WHERE ip_address = ?" . ($usuario ? " AND usuario = ?" : "");

        $stmt = $pdo->prepare($sql_check);
        $params = $usuario ? [$ip_address, $usuario] : [$ip_address];
        $stmt->execute($params);
        $rate_data = $stmt->fetch();

        if ($rate_data) {
            // Verificar se bloqueio expirou
            if ($rate_data['bloqueado_ate'] && strtotime($rate_data['bloqueado_ate']) > time()) {
                return [
                    'bloqueado' => true,
                    'bloqueado_ate' => $rate_data['bloqueado_ate'],
                    'tentativas' => $rate_data['tentativas']
                ];
            }

            // Resetar contagem se passou o tempo limite
            $reset_time = $usuario ? 600 : 900; // 10 min para usuário, 15 min para IP
            if (time() - strtotime($rate_data['ultimo_reset']) > $reset_time) {
                $sql_reset = "UPDATE user_login_attempts
                             SET tentativas = 1, bloqueado_ate = NULL, ultimo_reset = NOW()
                             WHERE ip_address = ?" . ($usuario ? " AND usuario = ?" : "");
                $stmt_reset = $pdo->prepare($sql_reset);
                $stmt_reset->execute($params);
                return ['bloqueado' => false, 'tentativas' => 1];
            }

            return ['bloqueado' => false, 'tentativas' => $rate_data['tentativas']];
        }

        return ['bloqueado' => false, 'tentativas' => 0];
    }
}

if (!function_exists('incrementarRateLimit')) {
    function incrementarRateLimit($pdo, $usuario = null)
    {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $max_tentativas = $usuario ? 3 : 5; // 3 para usuário, 5 para IP

        // Verificar estado atual
        $rate_info = verificarRateLimit($pdo, $usuario);

        if ($rate_info['tentativas'] >= $max_tentativas) {
            // Aplicar bloqueio progressivo
            $bloqueio_minutos = min(pow(2, $rate_info['tentativas'] - $max_tentativas + 1), 60);
            $bloqueio_ate = date('Y-m-d H:i:s', time() + ($bloqueio_minutos * 60));

            $sql_block = "UPDATE user_login_attempts
                         SET tentativas = tentativas + 1, bloqueado_ate = ?
                         WHERE ip_address = ?" . ($usuario ? " AND usuario = ?" : "");

            $stmt_block = $pdo->prepare($sql_block);
            $params = $usuario ? [$bloqueio_ate, $ip_address, $usuario] : [$bloqueio_ate, $ip_address];
            $stmt_block->execute($params);

            return [
                'bloqueado' => true,
                'bloqueado_ate' => $bloqueio_ate,
                'tentativas' => $rate_info['tentativas'] + 1
            ];
        } else {
            // Incrementar tentativas
            if ($rate_info['tentativas'] == 0) {
                // Primeira tentativa - inserir registro
                $sql_insert = "INSERT INTO user_login_attempts
                              (ip_address, usuario, tentativas)
                              VALUES (?, ?, 1)";
                $stmt_insert = $pdo->prepare($sql_insert);
                $params = $usuario ? [$ip_address, $usuario] : [$ip_address, null];
                $stmt_insert->execute($params);
            } else {
                // Incrementar existente
                $sql_update = "UPDATE user_login_attempts
                             SET tentativas = tentativas + 1
                             WHERE ip_address = ?" . ($usuario ? " AND usuario = ?" : "");
                $stmt_update = $pdo->prepare($sql_update);
                $params = $usuario ? [$ip_address, $usuario] : [$ip_address];
                $stmt_update->execute($params);
            }

            return [
                'bloqueado' => false,
                'tentativas' => $rate_info['tentativas'] + 1
            ];
        }
    }
}

if (!function_exists('resetarRateLimit')) {
    function resetarRateLimit($pdo, $usuario = null)
    {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $sql = "DELETE FROM user_login_attempts
                WHERE ip_address = ?" . ($usuario ? " AND usuario = ?" : "");

        $stmt = $pdo->prepare($sql);
        $params = $usuario ? [$ip_address, $usuario] : [$ip_address];
        return $stmt->execute($params);
    }
}

if (!function_exists('gerarCSRFToken')) {
    function gerarCSRFToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validarCSRFToken')) {
    function validarCSRFToken($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
