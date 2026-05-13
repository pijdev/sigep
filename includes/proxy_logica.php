<?php
function getProxyContent($url) {
    // Verifica se a URL é válida e permitida
    $allowedDomains = ['www.almoxpij.com', 'almoxpij.com'];
    $parsedUrl = parse_url($url);
    
    if (!in_array($parsedUrl['host'], $allowedDomains)) {
        return '<div class="alert alert-danger">URL não permitida</div>';
    }
    
    // Configuração do contexto para bypass do X-Frame-Options
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ]);
    
    // Tenta obter o conteúdo
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        return '<div class="alert alert-danger">Erro ao carregar o conteúdo</div>';
    }
    
    // Remove apenas headers que bloqueiam iframe, mas mantém o resto
    $content = preg_replace('/<meta[^>]*X-Frame-Options[^>]*>/i', '', $content);
    $content = preg_replace('/<meta[^>]*Content-Security-Policy[^>]*>/i', '', $content);
    
    // Adiciona base tag para que links relativos funcionem
    $baseTag = '<base href="' . htmlspecialchars($parsedUrl['scheme'] . '://' . $parsedUrl['host']) . '/">';
    
    // Insere base tag após <head> ou no início se não encontrar
    if (preg_match('/<head[^>]*>/i', $content)) {
        $content = preg_replace('/<head[^>]*>/i', '$0' . "\n" . $baseTag, $content);
    } else {
        $content = $baseTag . "\n" . $content;
    }
    
    return $content;
}

function getIframeSrc($url) {
    // Retorna uma URL de proxy que pode ser usada em iframe
    $allowedDomains = ['www.almoxpij.com', 'almoxpij.com'];
    $parsedUrl = parse_url($url);
    
    if (!in_array($parsedUrl['host'], $allowedDomains)) {
        return 'about:blank';
    }
    
    // Cria uma URL de proxy local
    return 'proxy.php?url=' . urlencode($url);
}
?>
