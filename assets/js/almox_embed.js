// assets/js/almox_embed.js
// JavaScript específico para almox_embed.php

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar iframe
    initializeIframe();
    
    // Configurar listeners de eventos
    setupIframeEventListeners();
    
    // Adicionar efeitos visuais
    addIframeEffects();
});

// Inicializar iframe
function initializeIframe() {
    const iframe = document.querySelector('.iframe-full');
    if (iframe) {
        // Adicionar classe de carregamento
        iframe.classList.add('iframe-loading');
        
        // Configurar eventos de carregamento
        iframe.addEventListener('load', handleIframeLoad);
        iframe.addEventListener('error', handleIframeError);
        
        // Timeout para carregamento
        setTimeout(() => {
            if (iframe.classList.contains('iframe-loading')) {
                handleIframeTimeout();
            }
        }, 10000); // 10 segundos
        
        // Verificar periodicamente se ainda estamos na página de login
        startLoginPageMonitor();
    }
}

// Monitorar se ainda estamos na página de login
function startLoginPageMonitor() {
    const iframe = document.querySelector('.iframe-full');
    if (!iframe) return;
    
    // Verificar a cada 2 segundos se ainda estamos na página de login
    const monitorInterval = setInterval(() => {
        try {
            // Tentar acessar a URL do iframe
            const currentUrl = iframe.contentWindow.location.href;
            
            // Se não estamos mais na página de login ou em uma página permitida
            if (currentUrl && !isAllowedIframePage(currentUrl)) {
                console.log('Detectado redirecionamento para página não permitida:', currentUrl);
                clearInterval(monitorInterval);
                handlePostLoginRedirect(currentUrl);
            }
        } catch (e) {
            // Cross-origin error - iframe foi redirecionado para domínio diferente ou página protegida
            console.log('Cross-origin detectado, provavelmente redirecionamento pós-login');
            clearInterval(monitorInterval);
            handlePostLoginRedirect();
        }
    }, 2000); // Verificar a cada 2 segundos
    
    // Parar monitoramento após 5 minutos (tempo suficiente para qualquer sessão)
    setTimeout(() => {
        clearInterval(monitorInterval);
    }, 300000); // 5 minutos
}

// Verificar se a página é permitida para iframe
function isAllowedIframePage(url) {
    try {
        const urlObj = new URL(url);
        const hostname = urlObj.hostname;
        const pathname = urlObj.pathname;
        
        // Permitir páginas de login e algumas páginas básicas
        return (hostname === 'www.almoxpij.com' || hostname === 'almoxpij.com') &&
               (pathname === '/' || 
                pathname === '/login.php' || 
                pathname.startsWith('/login') ||
                pathname.includes('login'));
    } catch (e) {
        return false;
    }
}

// Lidar com redirecionamento pós-login
function handlePostLoginRedirect(currentUrl = null) {
    console.warn('Redirecionamento pós-login detectado. Abrindo em nova aba...');
    
    // Abrir em nova aba
    const targetUrl = currentUrl || 'https://www.almoxpij.com/';
    const newWindow = window.open(targetUrl, '_blank');
    
    // Substituir iframe por mensagem explicativa
    const iframe = document.querySelector('.iframe-full');
    if (iframe) {
        const container = iframe.parentElement;
        const messageDiv = document.createElement('div');
        messageDiv.className = 'alert alert-success m-3';
        messageDiv.innerHTML = `
            <h5><i class="fas fa-check-circle mr-2"></i>Login Realizado com Sucesso!</h5>
            <p class="mb-2">
                Você foi redirecionado para o sistema SST e Almoxarifado em uma nova aba/janela 
                devido a restrições de segurança do navegador.
            </p>
            <p class="mb-0">
                <strong>Se não abriu automaticamente:</strong><br>
                <a href="${targetUrl}" target="_blank" class="btn btn-success btn-sm">
                    <i class="fas fa-external-link-alt mr-1"></i> Abrir SST e Almoxarifado
                </a>
            </p>
        `;
        
        container.replaceChild(messageDiv, iframe);
    }
}

// Configurar listeners de eventos do iframe
function setupIframeEventListeners() {
    // Listener para redimensionamento da janela
    window.addEventListener('resize', handleResize);
    
    // Listener para mudança de tema
    const themeToggle = document.getElementById('sigepDarkModeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            setTimeout(updateIframeTheme, 100);
        });
    }
    
    // Listener para foco/perda do iframe
    const iframe = document.querySelector('.iframe-full');
    if (iframe) {
        iframe.addEventListener('focus', handleIframeFocus);
        iframe.addEventListener('blur', handleIframeBlur);
    }
}

// Adicionar efeitos visuais
function addIframeEffects() {
    const iframe = document.querySelector('.iframe-full');
    if (iframe) {
        // Adicionar efeito de entrada
        iframe.style.opacity = '0';
        iframe.style.transform = 'scale(0.95)';
        
        setTimeout(() => {
            iframe.style.transition = 'all 0.3s ease';
            iframe.style.opacity = '1';
            iframe.style.transform = 'scale(1)';
        }, 100);
    }
}

// Manipular carregamento do iframe
function handleIframeLoad() {
    const iframe = this;
    iframe.classList.remove('iframe-loading');
    iframe.classList.add('iframe-loaded');
    
    console.log('Iframe carregado com sucesso');
    
    // Remover classe de sucesso após 2 segundos
    setTimeout(() => {
        iframe.classList.remove('iframe-loaded');
    }, 2000);
}

// Manipular erro do iframe
function handleIframeError() {
    const iframe = this;
    iframe.classList.remove('iframe-loading');
    iframe.classList.add('iframe-error');
    
    console.error('Erro ao carregar iframe');
    
    // Tentar detectar se é erro de X-Frame-Options
    setTimeout(() => {
        try {
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            if (iframeDoc && iframeDoc.body && 
                (iframeDoc.body.innerHTML.includes('Refused to display') || 
                 iframeDoc.body.innerHTML.includes('X-Frame-Options'))) {
                handleXFrameOptionsError();
            }
        } catch (e) {
            // Cross-origin error - provável bloqueio de iframe
            handleXFrameOptionsError();
        }
    }, 1000);
}

// Manipular timeout do iframe
function handleIframeTimeout() {
    const iframe = document.querySelector('.iframe-full');
    if (iframe && iframe.classList.contains('iframe-loading')) {
        iframe.classList.remove('iframe-loading');
        iframe.classList.add('iframe-error');
        
        showIframeError('O conteúdo está demorando para carregar. Verifique sua conexão.');
    }
}

// Mostrar mensagem de erro
function showIframeError(message) {
    const container = document.querySelector('.iframe-full').parentElement;
    
    // Criar elemento de erro
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger m-3';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle mr-2"></i>
        ${message}
        <button class="btn btn-sm btn-outline-danger ml-2" onclick="location.reload()">
            <i class="fas fa-redo"></i> Recarregar
        </button>
    `;
    
    // Inserir antes do iframe
    container.insertBefore(errorDiv, container.firstChild);
    
    // Remover após 5 segundos
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

// Manipular redimensionamento
function handleResize() {
    const iframe = document.querySelector('.iframe-full');
    if (iframe) {
        // Ajustar altura baseada no viewport
        const viewportHeight = window.innerHeight;
        const headerHeight = document.querySelector('.content-header')?.offsetHeight || 110;
        const newHeight = viewportHeight - headerHeight;
        
        iframe.style.height = `${newHeight}px`;
    }
}

// Atualizar tema do iframe
function updateIframeTheme() {
    const iframe = document.querySelector('.iframe-full');
    if (iframe && iframe.contentWindow) {
        try {
            // Tentar comunicar com o iframe (se for mesmo domínio)
            iframe.contentWindow.postMessage({
                type: 'theme_change',
                theme: document.body.classList.contains('dark-mode') ? 'dark' : 'light'
            }, '*');
        } catch (e) {
            console.log('Não foi possível comunicar com o iframe (domínio diferente)');
        }
    }
}

// Manipular foco do iframe
function handleIframeFocus() {
    this.style.boxShadow = '0 0 0 2px rgba(0, 123, 255, 0.25)';
}

// Manipular perda de foco do iframe
function handleIframeBlur() {
    this.style.boxShadow = '';
}

// Função pública para recarregar iframe
window.reloadAlmoxIframe = function() {
    const iframe = document.querySelector('.iframe-full');
    if (iframe) {
        iframe.classList.remove('iframe-error', 'iframe-loaded');
        iframe.classList.add('iframe-loading');
        iframe.src = iframe.src; // Recarrega
    }
};

// Função pública para verificar status do iframe
window.getAlmoxIframeStatus = function() {
    const iframe = document.querySelector('.iframe-full');
    if (!iframe) return 'not_found';
    
    if (iframe.classList.contains('iframe-loading')) return 'loading';
    if (iframe.classList.contains('iframe-error')) return 'error';
    if (iframe.classList.contains('iframe-loaded')) return 'loaded';
    
    return 'unknown';
};
