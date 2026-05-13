// assets/js/index.js
// JavaScript específico para index.php

document.addEventListener('DOMContentLoaded', function() {
    // Adicionar classe de animação aos elementos
    const welcomeCards = document.querySelectorAll('.small-box');
    const callout = document.querySelector('.callout');

    // Animar cards de boas-vindas
    welcomeCards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('fade-in');
        }, index * 200);
    });

    // Animar callout de navegação
    if (callout) {
        setTimeout(() => {
            callout.classList.add('fade-in');
        }, welcomeCards.length * 200);
    }

    // Adicionar efeito de clique nos cards
    welcomeCards.forEach(card => {
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    // Atualizar timestamp de última atividade
    updateLastActivity();

    // Verificar sessão periodicamente
    setInterval(checkSession, 30000); // 30 segundos
});

// Atualizar timestamp de última atividade
function updateLastActivity() {
    const fd = new FormData();
    fd.append('action', 'update_activity');

    fetch('auth/update_session_theme.php', {
        method: 'POST',
        body: fd
    }).catch(error => {
        console.log('Erro ao atualizar atividade:', error);
    });
}

// Verificar se a sessão está ativa
function checkSession() {
    fetch('/', {
        method: 'HEAD'
    }).then(response => {
        if (response.redirected && response.url.includes('login.php')) {
            // Sessão expirou, redirecionar para login
            window.location.href = '/autenticacao?expirou';
        }
    }).catch(error => {
        console.log('Erro ao verificar sessão:', error);
    });
}
