/**
 * Gerenciador de carregamento de páginas dinâmicas (SPA)
 */
function loadPage(url, title = '', parent = '') {
    // Se for uma âncora de javascript vazia, ignore
    if (url === '#' || url.startsWith('javascript:')) return false;

    console.log(`Carregando: ${url} [Parent: ${parent} | Titulo: ${title}]`);
    
    const container = document.getElementById('main-content-wrapper'); // ajuste para o ID do seu container main
    if (!container) return;

    // Feedback visual opcional de carregamento
    container.style.opacity = '0.5';

    fetch(url)
        .then(response => {
            if (!response.ok) throw new Error('Falha ao carregar a página solicitada.');
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            container.style.opacity = '1';
            
            // Atualiza o título da página ou breadcrumbs se necessário
            if (title) document.title = `${title} - SIGEP`;
        })
        .catch(error => {
            console.error(error);
            container.innerHTML = `<div class="alert alert-danger m-3">Ocorreu um erro ao carregar o módulo: ${error.message}</div>`;
            container.style.opacity = '1';
        });
}

// Vincula automaticamente todos os cliques da Sidebar prevenindo comportamento padrão
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('.app-sidebar');
    if (sidebar) {
        sidebar.addEventListener('click', (event) => {
            const link = event.target.closest('a[onclick]');
            if (link) {
                event.preventDefault();
                event.stopPropagation();
            }
        });
    }
});