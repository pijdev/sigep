<?php
/**
 * CFTV Proxy - Solução para contornar X-Frame-Options
 * Módulo: modulos/seguranca/cftv/cftv_proxy.php
 * 
 * ATENÇÃO: Esta é uma solução alternativa. O ideal é configurar
 * o servidor CFTV para permitir iframe do SIGEP.
 */

session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso negado');
}

// Verificar permissão de admin
if (empty($_SESSION['user_admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso restrito a administradores');
}

// URL do sistema CFTV
$CFTV_URL = 'https://172.16.0.251/';

// Função para fazer proxy do conteúdo
function proxyCFTV($url) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // Para certificado local
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    
    return [
        'content' => $response,
        'code' => $httpCode,
        'type' => $contentType
    ];
}

// Se for requisição AJAX para recursos do CFTV
if (isset($_GET['resource'])) {
    $resource = $_GET['resource'];
    $fullUrl = $CFTV_URL . ltrim($resource, '/');
    
    $result = proxyCFTV($fullUrl);
    
    header('Content-Type: ' . ($result['type'] ?: 'text/html'));
    header('X-Frame-Options: SAMEORIGIN'); // Permitir no nosso iframe
    
    // Remover headers de segurança que bloqueiam iframe
    header_remove('X-Frame-Options');
    header('Content-Security-Policy: frame-ancestors *');
    
    echo $result['content'];
    exit;
}

// Página principal do proxy
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CFTV - Central de Monitoramento</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Source Sans Pro', sans-serif;
            background: #f4f6f9;
            height: 100vh;
            overflow: hidden;
        }
        .cftv-container {
            width: 100%;
            height: 100vh;
            position: relative;
        }
        .cftv-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            z-index: 1000;
        }
        .loading-overlay.hidden { display: none; }
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid #fff;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error-message {
            background: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            max-width: 500px;
        }
        .error-message h3 { margin-bottom: 10px; }
        .btn-open-external {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .btn-open-external:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="cftv-container">
        <div id="loading" class="loading-overlay">
            <div class="spinner"></div>
            <p>Carregando sistema CFTV...</p>
        </div>
        
        <iframe 
            id="cftv-frame"
            class="cftv-iframe"
            src="https://172.16.0.251/#/"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-downloads"
            allow="fullscreen; autoplay; camera; microphone"
        ></iframe>
    </div>

    <script>
    (function() {
        const iframe = document.getElementById('cftv-frame');
        const loading = document.getElementById('loading');
        let loadTimeout;
        let errorDetected = false;
        
        // Timeout para detectar erro de carregamento
        loadTimeout = setTimeout(function() {
            if (!errorDetected) {
                // Tentar verificar se iframe carregou
                try {
                    const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (!iframeDoc || iframeDoc.body.innerHTML === '') {
                        showError();
                    }
                } catch(e) {
                    // Erro de cross-origin esperado - iframe está funcionando
                    loading.classList.add('hidden');
                }
            }
        }, 5000);
        
        iframe.onload = function() {
            clearTimeout(loadTimeout);
            loading.classList.add('hidden');
        };
        
        iframe.onerror = function() {
            errorDetected = true;
            showError();
        };
        
        function showError() {
            loading.innerHTML = `
                <div class="error-message">
                    <h3><i class="fas fa-exclamation-triangle"></i> Erro de Conexão</h3>
                    <p>Não foi possível carregar o CFTV no iframe devido a restrições de segurança (X-Frame-Options).</p>
                    <p><strong>Soluções:</strong></p>
                    <ul style="text-align: left; margin: 10px 0;">
                        <li>Configure o servidor CFTV para permitir iframe</li>
                        <li>Abra em nova aba usando o botão abaixo</li>
                    </ul>
                    <a href="https://172.16.0.251/#/" target="_blank" class="btn-open-external">
                        <i class="fas fa-external-link-alt"></i> Abrir CFTV em Nova Aba
                    </a>
                </div>
            `;
        }
        
        // Monitorar mensagens de erro do iframe
        window.addEventListener('message', function(e) {
            if (e.data && e.data.type === 'cftv-error') {
                showError();
            }
        });
    })();
    </script>
</body>
</html>
