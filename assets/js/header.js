// assets/js/header.js
// JavaScript específico para o header.php - Versão AdminLTE 3 Puro

// Inicializar componentes AdminLTE
function initializeAdminLTEComponents() {
    // Inicializar tooltips AdminLTE
    if (typeof $ !== 'undefined' && $.fn.tooltip) {
        $('[data-toggle="tooltip"]').tooltip();
    }

    // Inicializar dropdowns AdminLTE
    if (typeof $ !== 'undefined' && $.fn.dropdown) {
        $('.dropdown-toggle').dropdown();
    }

    // AdminLTE 3 inicializa automaticamente com data-widget
    // Não precisa inicializar manualmente o dark-mode-toggle
}

// Configurar eventos específicos do header
function setupHeaderEvents() {
    // Prevenir fechamento acidental de dropdowns ao clicar em itens com onclick
    const dropdownItems = document.querySelectorAll('.dropdown-item');
    dropdownItems.forEach(item => {
        const onclick = item.getAttribute('onclick');
        const href = item.getAttribute('href');
        
        if (onclick || href === '#') {
            item.addEventListener('click', function(e) {
                if (href === '#') {
                    e.preventDefault();
                }
            });
        }
    });
    
    // Fechar dropdowns ao clicar fora
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                if (typeof $ !== 'undefined' && $.fn.dropdown) {
                    $(dropdown).dropdown('hide');
                }
            });
        }
    });
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    initializeAdminLTEComponents();
    setupHeaderEvents();
});