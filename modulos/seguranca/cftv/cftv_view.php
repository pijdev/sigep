<?php
/**
 * CFTV - Central de Monitoramento (Embed via Proxy)
 * Módulo: modulos/seguranca/cftv/cftv_view.php
 * Descrição: View com iframe via proxy local (contorna X-Frame-Options)
 */

// Proteção contra acesso direto
if (!defined('SIGEP_SISTEMA')) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 403 Forbidden');
        exit('Acesso negado');
    }
}

// Verificar permissão de admin
if (empty($_SESSION['user_admin'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso restrito a administradores');
}

// Detectar se estamos usando proxy
$usandoProxy = false;
$proxyUrl = '/cftv/';  // URL do proxy (mesmo domínio)
$directUrl = 'https://172.16.0.251/#/';  // URL direta

// Tentar verificar se proxy está configurado (via request de teste)
$testUrl = 'http://' . $_SERVER['HTTP_HOST'] . '/cftv/';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Se retornar 200, 301, 302 ou 401, o proxy está configurado
if (in_array($httpCode, [200, 301, 302, 307, 401, 403])) {
    $usandoProxy = true;
}
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-video mr-2"></i>
                            Central de Monitoramento CFTV
                        </h3>
                        <div class="card-tools">
                            <?php if ($usandoProxy): ?>
                            <span class="badge badge-success mr-2">
                                <i class="fas fa-check-circle"></i> Proxy Ativo
                            </span>
                            <?php else: ?>
                            <span class="badge badge-warning mr-2" title="Configure o proxy no Apache para embed funcionar">
                                <i class="fas fa-exclamation-circle"></i> Proxy Inativo
                            </span>
                            <?php endif; ?>
                            <button type="button" class="btn btn-tool" onclick="recarregarCFTV()" title="Recarregar">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <?php if ($usandoProxy): ?>
                            <button type="button" class="btn btn-tool" onclick="toggleFullscreen()" title="Tela Cheia">
                                <i class="fas fa-expand"></i>
                            </button>
                            <?php endif; ?>
                            <a href="<?= $directUrl ?>" target="_blank" class="btn btn-sm btn-success" title="Abrir em Nova Aba">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </div>
                    
                    <?php if ($usandoProxy): ?>
                    <!-- EMBED ATIVO -->
                    <div class="card-body p-0">
                        <div id="cftv-container" class="cftv-container">
                            <iframe 
                                id="cftv-iframe"
                                src="<?= $proxyUrl ?>#/" 
                                width="100%" 
                                height="700" 
                                frameborder="0" 
                                allowfullscreen
                                allow="fullscreen; autoplay; camera; microphone"
                            ></iframe>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <!-- PROXY NÃO CONFIGURADO -->
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Configuração Necessária</h5>
                            <p>Para usar o CFTV em embed, é necessário configurar o <strong>Reverse Proxy</strong> no Apache.</p>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-cog mr-2"></i>
                                            Configuração do Apache
                                        </h3>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>1.</strong> Copie o arquivo <code>apache_cftv_proxy.conf</code> para a pasta de configuração do Apache:</p>
                                        <pre class="bg-dark text-light p-3 rounded"><code># Windows (XAMPP/WAMP)
C:\Aplicativos\Apache\conf\extra\cftv_proxy.conf

# Ou adicione ao httpd.conf diretamente</code></pre>

                                        <p><strong>2.</strong> Inclua no <code>httpd.conf</code>:</p>
                                        <pre class="bg-dark text-light p-3 rounded"><code>Include conf/extra/cftv_proxy.conf</code></pre>

                                        <p><strong>3.</strong> Reinicie o Apache</p>
                                        
                                        <hr>
                                        
                                        <p class="text-muted">
                                            <i class="fas fa-info-circle mr-1"></i>
                                            Após configurar, recarregue esta página.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card card-outline card-success">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-external-link-alt mr-2"></i>
                                            Acesso Direto
                                        </h3>
                                    </div>
                                    <div class="card-body text-center">
                                        <p>Enquanto não configura o proxy:</p>
                                        <a href="<?= $directUrl ?>" target="_blank" class="btn btn-success btn-lg">
                                            <i class="fas fa-video mr-2"></i>
                                            Abrir CFTV
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-server mr-1"></i>
                            Servidor CFTV: 172.16.0.251 | 
                            <?php if ($usandoProxy): ?>
                            <i class="fas fa-link mr-1"></i> Proxy: <?= $proxyUrl ?>
                            <?php else: ?>
                            <i class="fas fa-times-circle mr-1"></i> Proxy não configurado
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Estilos -->
<style>
.cftv-container {
    position: relative;
    width: 100%;
    height: 700px;
    background: #000;
}

.cftv-container iframe {
    display: block;
    width: 100%;
    height: 100%;
    border: none;
}

.cftft-container.fullscreen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    background: #000;
}

.cftv-container.fullscreen iframe {
    height: 100vh;
}

@media (max-width: 768px) {
    .cftv-container {
        height: 500px;
    }
}
</style>

<!-- Scripts -->
<script>
(function() {
    'use strict';
    
    window.recarregarCFTV = function() {
        const iframe = document.getElementById('cftv-iframe');
        if (iframe) {
            iframe.src = iframe.src;
            console.log('CFTV recarregado');
        }
    };
    
    window.toggleFullscreen = function() {
        const container = document.getElementById('cftv-container');
        if (!container) return;
        
        if (container.classList.contains('fullscreen')) {
            container.classList.remove('fullscreen');
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        } else {
            container.classList.add('fullscreen');
            if (container.requestFullscreen) {
                container.requestFullscreen();
            }
        }
    };
    
    console.log('CFTV View carregado - Proxy: <?= $usandoProxy ? "Ativo" : "Inativo" ?>');
})();
</script>
