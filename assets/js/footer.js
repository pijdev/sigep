// assets/js/footer.js
// JavaScript específico para o footer.php - Versão AdminLTE 3 Puro

class FooterManager {
    constructor() {
        this.init();
    }

    init() {
        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        console.log('FooterManager: Inicializando componentes AdminLTE 3...');

        // Inicializar componentes AdminLTE
        this.initializeAdminLTEComponents();

        // Configurar eventos específicos do footer
        this.setupFooterEvents();

        // Atualizar informações dinâmicas
        this.updateDynamicInfo();

        console.log('FooterManager: Componentes inicializados');
    }

    initializeAdminLTEComponents() {
        // AdminLTE 3 não precisa de inicialização especial para footer
        // Componentes são automáticos com as classes CSS

        // Inicializar tooltips se houver
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('[data-toggle="tooltip"]').tooltip();
        }

        // Adicionar role de acessibilidade
        this.addAccessibilityAttributes();
    }

    addAccessibilityAttributes() {
        const footer = document.querySelector('.main-footer');
        if (footer && !footer.hasAttribute('role')) {
            footer.setAttribute('role', 'contentinfo');
        }
    }

    setupFooterEvents() {
        // Configurar links externos
        this.setupExternalLinks();

        // Configurar efeitos hover sutis
        this.setupHoverEffects();
    }

    setupExternalLinks() {
        const externalLinks = document.querySelectorAll('.main-footer a[href^="http"]');
        externalLinks.forEach(link => {
            if (!link.hasAttribute('target')) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
    }

    setupHoverEffects() {
        const footerLinks = document.querySelectorAll('.main-footer a');
        footerLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.2s ease';
            });
        });
    }

    updateDynamicInfo() {
        // Atualizar ano atual se necessário
        this.updateCurrentYear();

        // Atualizar informações de performance (se disponível)
        this.updatePerformanceInfo();
    }

    updateCurrentYear() {
        const yearElements = document.querySelectorAll('.footer-year');
        const currentYear = new Date().getFullYear();

        yearElements.forEach(element => {
            element.textContent = currentYear;
        });
    }

    updatePerformanceInfo() {
        // Apenas para admins/desenvolvedores
        if (!this.isUserAdmin()) return;

        const performanceInfo = this.getPerformanceInfo();
        if (performanceInfo) {
            this.displayPerformanceInfo(performanceInfo);
        }
    }

    isUserAdmin() {
        return window.spaManager?.isAdmin || false;
    }

    getPerformanceInfo() {
        if (!window.performance) return null;

        const navigation = window.performance.getNavigationByType();
        const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;

        return {
            loadTime: Math.round(loadTime),
            domReady: Math.round(window.performance.timing.domContentLoadedEventEnd - window.performance.timing.navigationStart),
            memory: performance.memory ? Math.round(performance.memory.usedJSHeapSize / 1024 / 1024) : null
        };
    }

    displayPerformanceInfo(info) {
        // Criar elemento de performance se não existir
        let perfElement = document.querySelector('.footer-performance');
        if (!perfElement) {
            perfElement = document.createElement('div');
            perfElement.className = 'footer-performance text-muted small';
            perfElement.style.cssText = 'font-size: 0.75rem; opacity: 0.7;';
        }

        perfElement.innerHTML = `Load: ${info.loadTime}ms | DOM: ${info.domReady}ms${info.memory ? ` | Mem: ${info.memory}MB` : ''}`;

        // Adicionar ao footer
        const footer = document.querySelector('.main-footer');
        if (footer) {
            footer.appendChild(perfElement);
        }
    }

    // Métodos públicos para compatibilidade
    updateFooterInfo(info) {
        if (info.version) {
            const versionElement = document.querySelector('.main-footer .float-right');
            if (versionElement) {
                versionElement.innerHTML = `<b>Version</b> ${info.version}`;
            }
        }
    }

    updateTimestamp() {
        this.updateCurrentYear();
    }
}

// Inicializar automaticamente
window.footerManager = new FooterManager();

// Compatibilidade global
window.footerUtils = {
    updateFooterInfo: (info) => window.footerManager.updateFooterInfo(info),
    updateTimestamp: () => window.footerManager.updateTimestamp()
};
