<?php
// includes/almox_embed_logica.php
// Lógica PHP para almox_embed.php

// Configurações da página
function getAlmoxEmbedConfig() {
    return [
        'title' => 'SST e Almoxarifado',
        'parent' => 'SST e Almoxarifado',
        'external_url' => 'https://www.almoxpij.com/',
        'proxy_url' => 'proxy.php?url=' . urlencode('https://www.almoxpij.com/'),
        'allowed_domains' => ['www.almoxpij.com', 'almoxpij.com'],
        'iframe_timeout' => 10000, // 10 segundos
        'auto_refresh' => false,
        'refresh_interval' => 300000 // 5 minutos
    ];
}

// Verificar se o proxy está disponível
function isProxyAvailable() {
    // Verificar se o arquivo proxy.php existe
    $proxyFile = __DIR__ . '/../proxy.php';
    return file_exists($proxyFile) && is_readable($proxyFile);
}

// Obter informações de status do proxy
function getProxyStatus() {
    $config = getAlmoxEmbedConfig();
    
    $status = [
        'available' => true, // Sempre tentar
        'external_accessible' => false,
        'error' => null
    ];
    
    // Verificar se site externo está acessível (opcional)
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'HEAD'
        ]
    ]);
    
    $headers = @get_headers($config['external_url'], 0, $context);
    $status['external_accessible'] = $headers !== false;
    
    if (!$status['external_accessible']) {
        $status['error'] = 'Site externo pode estar lento, tentando mesmo assim...';
    }
    
    return $status;
}

// Gerar URL do proxy com parâmetros adicionais
function generateProxyUrl($additionalParams = []) {
    $config = getAlmoxEmbedConfig();
    $proxyUrl = $config['proxy_url'];
    
    if (!empty($additionalParams)) {
        $proxyUrl .= '&' . http_build_query($additionalParams);
    }
    
    return $proxyUrl;
}

// Validar segurança da URL
function validateProxyUrl($url) {
    $config = getAlmoxEmbedConfig();
    $parsedUrl = parse_url($url);
    
    // Verificar se é um domínio permitido
    if (!in_array($parsedUrl['host'], $config['allowed_domains'])) {
        return false;
    }
    
    // Verificar se é HTTPS
    if ($parsedUrl['scheme'] !== 'https') {
        return false;
    }
    
    return true;
}

// Obter métricas de uso (para futura implementação)
function getAlmoxEmbedMetrics() {
    // Placeholder para futuras métricas
    return [
        'loads_today' => 0,
        'average_load_time' => 0,
        'error_count' => 0,
        'last_access' => null
    ];
}

// Configurar headers de segurança para a página
function setAlmoxEmbedSecurityHeaders() {
    // Headers básicos apenas - evitar bloqueios em contexto AJAX
    header('Content-Type: text/html; charset=utf-8');
    
    // Removidos headers que podem causar 403 em requisições AJAX
    // header('X-Frame-Options: SAMEORIGIN');
    // header('Content-Security-Policy: frame-ancestors \'self\'');
    // header('X-Content-Type-Options: nosniff');
    // header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Verificar permissões do usuário para acessar
function canUserAccessAlmoxEmbed() {
    // Iniciar sessão se não estiver ativa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar se usuário está logado
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    // Verificar se usuário tem permissão (sempre true para almoxarifado)
    // Futuramente pode adicionar restrições específicas
    return true;
}

// Registrar acesso (para logs/auditoria)
function logAlmoxEmbedAccess($success = true) {
    $logData = [
        'user_id' => $_SESSION['user_id'] ?? 'anonymous',
        'user_name' => $_SESSION['user_nome'] ?? 'Anonymous',
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'success' => $success,
        'error' => $success ? null : (error_get_last()['message'] ?? 'Unknown error')
    ];
    
    // Futuramente implementar log em banco de dados
    // Por enquanto, apenas registrar em arquivo se a constante estiver definida
    if (defined('ALMOX_EMBED_LOG_FILE')) {
        $logLine = json_encode($logData) . PHP_EOL;
        file_put_contents(ALMOX_EMBED_LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
    }
}
?>
