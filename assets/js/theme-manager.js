// assets/js/theme-manager.js
// Gerenciador de temas do sistema SIGEP

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.init();
    }

    init() {
        // Aplicar tema salvo
        this.applyTheme(this.currentTheme);
        
        // Adicionar listener para mudanças de preferência do sistema
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.getStoredTheme() === 'system') {
                    this.applyTheme(e.matches ? 'dark' : 'light');
                }
            });
        }
    }

    getStoredTheme() {
        return localStorage.getItem('sigep-theme') || 'light';
    }

    setStoredTheme(theme) {
        localStorage.setItem('sigep-theme', theme);
        this.currentTheme = theme;
    }

    applyTheme(theme) {
        const body = document.body;
        const html = document.documentElement;
        
        // Remover classes de tema
        body.classList.remove('dark-mode', 'light-mode');
        html.classList.remove('dark-mode', 'light-mode');
        
        // Aplicar novo tema
        if (theme === 'dark') {
            body.classList.add('dark-mode');
            html.classList.add('dark-mode');
        } else {
            body.classList.add('light-mode');
            html.classList.add('light-mode');
        }
        
        // Atualizar estado do toggle se existir
        const toggle = document.getElementById('sigepDarkModeToggle');
        if (toggle) {
            toggle.checked = theme === 'dark';
        }
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        this.setStoredTheme(newTheme);
        this.applyTheme(newTheme);
        
        // Salvar preferência no servidor
        this.saveThemePreference(newTheme);
    }

    async saveThemePreference(theme) {
        try {
            await fetch('auth/update_session_theme.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update_theme',
                    theme: theme
                })
            });
        } catch (error) {
            console.warn('Não foi possível salvar preferência de tema:', error);
        }
    }
}

// Inicializar Theme Manager
window.themeManager = new ThemeManager();

// Função global para toggle de tema
window.toggleDarkMode = () => {
    window.themeManager.toggleTheme();
};
