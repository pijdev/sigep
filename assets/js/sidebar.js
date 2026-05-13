// assets/js/sidebar.js
// JavaScript específico para o sidebar.php

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar sidebar
    initializeSidebar();

    // Configurar listeners de eventos
    setupSidebarEventListeners();

    // Carregar estado inicial do menu
    loadMenuState();

    // Configurar comportamento do sidebar recolhido
    setupCollapsedSidebarBehavior();
});

// Inicializar sidebar
function initializeSidebar() {
    // Configurar estado inicial dos submenus
    const treeviewItems = document.querySelectorAll('.nav-item.has-treeview');
    treeviewItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        const treeview = item.querySelector('.nav-treeview');

        if (link && treeview) {
            // Adicionar listener para toggle do submenu
            link.addEventListener('click', function(e) {
                e.preventDefault();
                toggleSubmenu(item);
            });
        }
    });

    // Configurar navegação AJAX
    setupAjaxNavigation();

    // Configurar animações
    setupAnimations();
}

// Configurar listeners de eventos do sidebar
function setupSidebarEventListeners() {
    // Listener para redimensionamento da janela
    window.addEventListener('resize', handleResize);

    // Listener para clique fora do sidebar (mobile)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            const sidebar = document.querySelector('.main-sidebar');
            const isClickInsideSidebar = sidebar.contains(e.target);
            const isMenuToggle = e.target.closest('[data-widget="pushmenu"]');

            if (!isClickInsideSidebar && !isMenuToggle && sidebar.classList.contains('sidebar-open')) {
                closeSidebar();
            }
        }
    });
}

// Configurar comportamento do sidebar recolhido
function setupCollapsedSidebarBehavior() {
    const sidebar = document.querySelector('.main-sidebar');
    const body = document.body;

    // Verificar se sidebar está recolhido
    function isSidebarCollapsed() {
        return body.classList.contains('sidebar-collapse');
    }

    // Criar tooltips para itens do menu
    createTooltips();

    // Adicionar comportamento hover para itens com submenu
    const treeviewItems = document.querySelectorAll('.nav-item.has-treeview');
    treeviewItems.forEach(item => {
        const link = item.querySelector('.nav-link');
        const treeview = item.querySelector('.nav-treeview');
        const angleIcon = link.querySelector('.fa-angle-left');

        if (link && treeview) {
            // Mouse enter - mostrar submenu
            link.addEventListener('mouseenter', function() {
                if (isSidebarCollapsed()) {
                    // Abrir submenu temporariamente
                    item.classList.add('hover-open');

                    // Ajustar posição da seta
                    if (angleIcon) {
                        angleIcon.style.display = 'block';
                        angleIcon.style.position = 'absolute';
                        angleIcon.style.right = '10px';
                        angleIcon.style.top = '50%';
                        angleIcon.style.transform = 'translateY(-50%) rotate(-90deg)';
                    }
                }
            });

            // Mouse leave - esconder submenu
            link.addEventListener('mouseleave', function() {
                if (isSidebarCollapsed()) {
                    // Fechar submenu temporário
                    item.classList.remove('hover-open');

                    // Restaurar seta
                    if (angleIcon) {
                        angleIcon.style.display = '';
                        angleIcon.style.position = '';
                        angleIcon.style.right = '';
                        angleIcon.style.top = '';
                        angleIcon.style.transform = '';
                    }
                }
            });

            // Prevenir clique no sidebar recolhido
            link.addEventListener('click', function(e) {
                if (isSidebarCollapsed()) {
                    e.preventDefault();
                    e.stopPropagation();

                    // Expandir sidebar temporariamente ou redirecionar
                    const hasSubItems = treeview && treeview.children.length > 0;
                    if (!hasSubItems) {
                        // Se não tiver submenu, executar ação normal
                        const onclick = link.getAttribute('onclick');
                        if (onclick) {
                            eval(onclick);
                        }
                    }
                }
            });
        }
    });

    // Configurar Painel de Internos no sidebar recolhido
    const painelInternosLink = document.querySelector('.user-panel .nav-item .nav-link');
    if (painelInternosLink) {
        painelInternosLink.addEventListener('click', function(e) {
            if (isSidebarCollapsed()) {
                e.preventDefault();
                // Executar ação normalmente
                const onclick = this.getAttribute('onclick');
                if (onclick) {
                    eval(onclick);
                }
            }
        });
    }

    // Monitorar mudanças no estado do sidebar
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                const isCollapsed = body.classList.contains('sidebar-collapse');
                updateTooltips(isCollapsed);
            }
        });
    });

    observer.observe(body, { attributes: true });
}

// Criar tooltips para itens do menu
function createTooltips() {
    const navLinks = document.querySelectorAll('.nav-link');
    const navHeaders = document.querySelectorAll('.nav-header');

    // Adicionar tooltips aos links
    navLinks.forEach(link => {
        const pElement = link.querySelector('p');
        if (pElement) {
            const tooltipText = pElement.textContent.trim();
            if (tooltipText) {
                // Criar elemento tooltip
                const tooltip = document.createElement('span');
                tooltip.className = 'sidebar-tooltip';
                tooltip.textContent = tooltipText;

                // Adicionar ao link
                link.appendChild(tooltip);
            }
        }
    });

    // Adicionar tooltips aos headers
    navHeaders.forEach(header => {
        const headerText = header.textContent.trim();
        if (headerText) {
            header.setAttribute('data-tooltip', headerText);
        }
    });
}

// Atualizar visibilidade dos tooltips
function updateTooltips(isCollapsed) {
    const tooltips = document.querySelectorAll('.sidebar-tooltip');

    if (isCollapsed) {
        // Mostrar tooltips quando sidebar está recolhido
        tooltips.forEach(tooltip => {
            tooltip.style.display = 'block';
        });
    } else {
        // Esconder tooltips quando sidebar está expandido
        tooltips.forEach(tooltip => {
            tooltip.style.display = 'none';
        });
    }
}

// Toggle de submenu
function toggleSubmenu(item) {
    const isOpen = item.classList.contains('open');
    const icon = item.querySelector('.fa-angle-left');

    // Fechar outros submenus no mesmo nível
    const parent = item.parentElement;
    const siblings = parent.querySelectorAll('.nav-item.has-treeview');
    siblings.forEach(sibling => {
        if (sibling !== item) {
            sibling.classList.remove('open');
            const siblingIcon = sibling.querySelector('.fa-angle-left');
            if (siblingIcon) {
                siblingIcon.style.transform = 'rotate(0deg)';
            }
        }
    });

    // Toggle do item atual
    if (isOpen) {
        item.classList.remove('open');
        if (icon) {
            icon.style.transform = 'rotate(0deg)';
        }
    } else {
        item.classList.add('open');
        if (icon) {
            icon.style.transform = 'rotate(-90deg)';
        }
    }

    // Salvar estado
    saveMenuState();
}

// Configurar navegação AJAX
function setupAjaxNavigation() {
    const navLinks = document.querySelectorAll('.nav-link[onclick]');
    navLinks.forEach(link => {
        if (link.getAttribute('onclick') && link.getAttribute('onclick').includes('loadPage')) {
            link.addEventListener('click', function(e) {
                // Remover classe active de outros links APENAS se não for treeview
                const isTreeview = link.closest('.has-treeview');
                if (!isTreeview) {
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    // Adicionar classe active ao link clicado
                    this.classList.add('active');
                }

                // Salvar estado de navegação
                saveNavigationState(this);
            });
        }
    });
}

// Configurar animações
function setupAnimations() {
    // Animação de entrada para itens do menu
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-20px)';

        setTimeout(() => {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 50);
    });
}

// Manipular redimensionamento
function handleResize() {
    const sidebar = document.querySelector('.main-sidebar');
    if (window.innerWidth > 768) {
        sidebar.classList.remove('sidebar-open');
    }
}

// Fechar sidebar (mobile)
function closeSidebar() {
    const sidebar = document.querySelector('.main-sidebar');
    if (sidebar) {
        sidebar.classList.remove('sidebar-open');
    }
}

// Salvar estado do menu
function saveMenuState() {
    const openItems = document.querySelectorAll('.nav-item.has-treeview.open');
    const state = Array.from(openItems).map(item => {
        const link = item.querySelector('.nav-link');
        return link.textContent.trim();
    });

    localStorage.setItem('sigep_menu_state', JSON.stringify(state));
}

// Carregar estado do menu
function loadMenuState() {
    const savedState = localStorage.getItem('sigep_menu_state');
    if (savedState) {
        try {
            const state = JSON.parse(savedState);
            const treeviewItems = document.querySelectorAll('.nav-item.has-treeview');

            treeviewItems.forEach(item => {
                const link = item.querySelector('.nav-link');
                const linkText = link.textContent.trim();

                if (state.includes(linkText)) {
                    item.classList.add('open');
                    const icon = item.querySelector('.fa-angle-left');
                    if (icon) {
                        icon.style.transform = 'rotate(-90deg)';
                    }
                }
            });
        } catch (e) {
            console.error('Erro ao carregar estado do menu:', e);
        }
    }
}

// Salvar estado de navegação
function saveNavigationState(link) {
    const navData = {
        href: link.getAttribute('href'),
        onclick: link.getAttribute('onclick'),
        text: link.textContent.trim()
    };

    localStorage.setItem('sigep_last_nav', JSON.stringify(navData));
}

// Carregar estado do menu
function loadMenuState() {
    try {
        const savedState = localStorage.getItem('sigep_menu_state');
        if (savedState) {
            const openItems = JSON.parse(savedState);
            openItems.forEach(itemText => {
                const navItems = document.querySelectorAll('.nav-item.has-treeview');
                navItems.forEach(item => {
                    const link = item.querySelector('.nav-link');
                    if (link && link.textContent.trim() === itemText) {
                        item.classList.add('open');
                        const icon = item.querySelector('.fa-angle-left');
                        if (icon) {
                            icon.style.transform = 'rotate(-90deg)';
                        }
                    }
                });
            });
        }
    } catch (error) {
        console.log('Erro ao carregar estado do menu:', error);
    }
}

// Salvar estado de navegação
function saveNavigationState(link) {
    try {
        const navigationState = {
            href: link.getAttribute('href') || link.getAttribute('onclick'),
            text: link.textContent.trim(),
            timestamp: new Date().getTime()
        };

        localStorage.setItem('sigep_navigation_state', JSON.stringify(navigationState));
    } catch (error) {
        console.log('Erro ao salvar estado de navegação:', error);
    }
}

// Função utilitária para destacar item ativo baseado na URL atual
function highlightActiveMenuItem() {
    // DESATIVADO PARA SPA: Não destacar itens automaticamente
    // No SPA, os links são gerenciados pela navegação loadPage()
    return;

    /* Código original comentado para SPA
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');

    // Remover todas as classes active primeiro
    navLinks.forEach(link => {
        link.classList.remove('active');
    });

    // No SPA, não destacar nenhum item por padrão
    // Apenas destacar se houver navegação explícita
    if (currentPath !== '/' && currentPath !== '/inicio/') {
        navLinks.forEach(link => {
            const href = link.getAttribute('href');
            const onclick = link.getAttribute('onclick');

            if (href && currentPath.includes(href)) {
                link.classList.add('active');
            } else if (onclick && onclick.includes(currentPath)) {
                link.classList.add('active');
            }
        });
    }
    */
}

// NÃO executar highlight automaticamente no SPA
// highlightActiveMenuItem();
