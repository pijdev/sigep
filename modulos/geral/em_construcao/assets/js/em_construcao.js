// Animação automática dos ícones
const iconContainer = document.getElementById('iconContainer');
let animationState = false;

function toggleAnimation() {
    animationState = !animationState;
    if (animationState) {
        iconContainer.classList.add('animate');
    } else {
        iconContainer.classList.remove('animate');
    }
}

// Iniciar animação automática a cada 3 segundos
setInterval(toggleAnimation, 3000);

// Detectar tema do sistema
function detectTheme() {
    const isDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute('data-theme', isDarkMode ? 'dark' : 'light');
}

// Detectar mudanças de tema
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', detectTheme);

// Inicializar tema
detectTheme();

// Console log
console.log("SIGEP - Página de Módulos Implementados carregada com sucesso!");
