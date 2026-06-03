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

function abrirAlertaCopa(url) {
    Swal.fire({
        title: '<span style="color: #009c3b;">Vai</span> <span style="color: #e2cc03;">Brasil</span><span style="color: #009c3b;">! ⚽</span>',
        html: '<p><span style="color: #ffffff;">Tabela da Copa do Mundo FIFA 2026, fornecida pelo sistema </span><b><span style="color: #397CEB;">SIG</span><span style="color: #FFFFFF;">EP</span></b><span style="color: #ffffff;">.</span></p><p><span style="color: #ffffff;">Confira as partidas e os resultados em tempo real!</span></p>',
        icon: 'warning',
        iconColor: '#009c3b',
        showConfirmButton: true,
        timer: 5000,
        timerProgressBar: true,
        confirmButtonText: 'Bora! ⚽',
        confirmButtonColor: '#009c3b',
        showCancelButton: true,
        cancelButtonText: 'Depois...',
        cancelButtonColor: '#dc3545',
        background: '#2c2c2c',
        willOpen: () => {
            const loader = Swal.getPopup().querySelector('.swal2-loader');
            if (loader) {
                loader.style.borderLeftColor = '#009c3b';
                loader.style.borderRightColor = '#ffdf00';
            }
        },
        customClass: {
            popup: 'swal2-popup-copa'
        }
    }).then((result) => {
        // Redireciona se o usuário clicar no botão de confirmação OU se o tempo do timer acabar
        if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
            window.location.href = url;
        }
    });
}