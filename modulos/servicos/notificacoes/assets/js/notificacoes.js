// modulos/servicos/notificacoes/assets/js/notificacoes.js
// JavaScript do módulo de notificações

let currentPage = 1;
let totalPages = 1;
const itemsPerPage = 20;

$(document).ready(function() {
    // Inicializar tooltips AdminLTE
    $('[data-toggle="tooltip"]').tooltip();

    // Configurar polling suave (opcional)
    setInterval(atualizarContadorHeader, 60000); // 1 minuto
});

// Carregar notificações com paginação
function carregarNotificacoes(pagina = 1) {
    currentPage = pagina;

    // Mostrar loading
    $('#notificacoesTableBody').html('<tr><td colspan="6" class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Carregando...</span></div></td></tr>');

    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'buscar_notificacoes',
        pagina: pagina,
        limite: itemsPerPage
    })
    .done(function(data) {
        if (data.success) {
            renderizarNotificacoes(data.notificacoes);
            atualizarPaginacao(data.total, pagina);
            atualizarEstatisticas();
        } else {
            mostrarErro('Erro ao carregar notificações: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .fail(function() {
        mostrarErro('Erro de conexão ao carregar notificações');
    });
}

// Renderizar notificações na tabela
function renderizarNotificacoes(notificacoes) {
    let html = '';

    if (notificacoes.length === 0) {
        html = '<tr><td colspan="6" class="text-center text-muted">Nenhuma notificação encontrada</td></tr>';
    } else {
        notificacoes.forEach(function(notif) {
            const statusClass = notif.lida ? 'notification-read' : 'notification-unread';
            const statusIndicator = notif.lida ? 'status-read' : 'status-unread';
            const badgeClass = 'badge-tipo-' + notif.tipo.toLowerCase();
            const dataFormatada = new Date(notif.created_at).toLocaleString('pt-BR');

            html += `
                <tr class="notification-item ${statusClass}">
                    <td><span class="status-indicator ${statusIndicator}"></span>${notif.lida ? 'Lida' : 'Nova'}</td>
                    <td><span class="badge notification-badge ${badgeClass}">${notif.tipo}</span></td>
                    <td><strong>${escapeHtml(notif.titulo)}</strong></td>
                    <td>${escapeHtml(notif.mensagem.substring(0, 100))}${notif.mensagem.length > 100 ? '...' : ''}</td>
                    <td>${dataFormatada}</td>
                    <td class="notification-actions">
                        ${!notif.lida ? `<button class="btn btn-sm btn-success btn-action" onclick="marcarComoLida(${notif.id})" title="Marcar como lida"><i class="fas fa-check"></i></button>` : ''}
                        <button class="btn btn-sm btn-info btn-action" onclick="verDetalhes(${notif.id})" title="Ver detalhes"><i class="fas fa-eye"></i></button>
                    </td>
                </tr>
            `;
        });
    }

    $('#notificacoesTableBody').html(html);
}

// Atualizar paginação
function atualizarPaginacao(total, paginaAtual) {
    totalPages = Math.ceil(total / itemsPerPage);

    let info = `Mostrando ${Math.min((paginaAtual - 1) * itemsPerPage + 1, total)} a ${Math.min(paginaAtual * itemsPerPage, total)} de ${total} notificações`;
    $('#paginationInfo').text(info);

    let controls = '';
    if (totalPages > 1) {
        controls += '<ul class="pagination">';

        // Anterior
        if (paginaAtual > 1) {
            controls += `<li class="page-item"><a class="page-link" href="#" onclick="carregarNotificacoes(${paginaAtual - 1})">Anterior</a></li>`;
        }

        // Páginas
        for (let i = Math.max(1, paginaAtual - 2); i <= Math.min(totalPages, paginaAtual + 2); i++) {
            const active = i === paginaAtual ? 'active' : '';
            controls += `<li class="page-item ${active}"><a class="page-link" href="#" onclick="carregarNotificacoes(${i})">${i}</a></li>`;
        }

        // Próximo
        if (paginaAtual < totalPages) {
            controls += `<li class="page-item"><a class="page-link" href="#" onclick="carregarNotificacoes(${paginaAtual + 1})">Próximo</a></li>`;
        }

        controls += '</ul>';
    }

    $('#paginationControls').html(controls);
}

// Atualizar estatísticas
function atualizarEstatisticas() {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {action: 'buscar_notificacoes', pagina: 1, limite: 1000})
    .done(function(data) {
        if (data.success) {
            const notificacoes = data.notificacoes;
            const total = notificacoes.length;
            const naoLidas = notificacoes.filter(n => !n.lida).length;
            const hoje = notificacoes.filter(n => {
                const hoje = new Date().toDateString();
                return new Date(n.created_at).toDateString() === hoje;
            }).length;
            const erros = notificacoes.filter(n => n.tipo.toLowerCase() === 'erro' || n.tipo.toLowerCase() === 'sistema').length;

            $('#totalNotificacoes').text(total);
            $('#naoLidas').text(naoLidas);
            $('#hoje').text(hoje);
            $('#erros').text(erros);
        }
    });
}

// Marcar notificação como lida
function marcarComoLida(id) {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'marcar_lida',
        id: id
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Notificação marcada como lida');
            carregarNotificacoes(currentPage);
            atualizarContadorHeader();
        } else {
            mostrarErro('Erro ao marcar notificação como lida');
        }
    })
    .fail(function() {
        mostrarErro('Erro de conexão');
    });
}

// Marcar todas como lidas
function marcarTodasComoLidas() {
    if (!confirm('Tem certeza que deseja marcar todas as notificações como lidas?')) {
        return;
    }

    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {action: 'marcar_todas_lidas'})
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Todas as notificações foram marcadas como lidas');
            carregarNotificacoes(currentPage);
            atualizarContadorHeader();
        } else {
            mostrarErro('Erro ao marcar notificações como lidas');
        }
    })
    .fail(function() {
        mostrarErro('Erro de conexão');
    });
}

// Ver detalhes da notificação
function verDetalhes(id) {
    // Por enquanto, apenas marcar como lida e recarregar
    // Futuro: modal com detalhes completos
    marcarComoLida(id);
}

// Carregar preferências
function carregarPreferencias() {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {action: 'buscar_preferencias'})
    .done(function(data) {
        if (data.success) {
            renderizarPreferencias(data.preferencias);
        } else {
            mostrarErro('Erro ao carregar preferências');
        }
    })
    .fail(function() {
        mostrarErro('Erro de conexão');
    });
}

// Renderizar preferências
function renderizarPreferencias(preferencias) {
    const tipos = ['backup', 'tarefa', 'erro', 'alerta', 'sistema'];
    let html = '';

    tipos.forEach(function(tipo) {
        const ativa = preferencias[tipo] !== undefined ? preferencias[tipo] : true;
        const checked = ativa ? 'checked' : '';
        const activeClass = ativa ? 'active' : '';

        html += `
            <div class="preferences-item ${activeClass}">
                <div>
                    <strong>${tipo.charAt(0).toUpperCase() + tipo.slice(1)}</strong>
                    <br><small class="text-muted">Receber notificações de ${tipo}</small>
                </div>
                <label class="preferences-switch">
                    <input type="checkbox" data-tipo="${tipo}" ${checked}>
                    <span class="slider"></span>
                </label>
            </div>
        `;
    });

    $('#preferenciasContainer').html(html);

    // Atualizar classes ativas
    $('.preferences-switch input').change(function() {
        const item = $(this).closest('.preferences-item');
        if (this.checked) {
            item.addClass('active');
        } else {
            item.removeClass('active');
        }
    });
}

// Salvar preferências
function salvarPreferencias() {
    const preferencias = {};
    $('.preferences-switch input').each(function() {
        preferencias[$(this).data('tipo')] = this.checked ? 1 : 0;
    });

    let promises = [];
    for (const [tipo, ativa] of Object.entries(preferencias)) {
        promises.push(
            $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
                action: 'atualizar_preferencia',
                tipo: tipo,
                ativa: ativa
            })
        );
    }

    Promise.all(promises)
    .then(function(results) {
        const allSuccess = results.every(r => r.success);
        if (allSuccess) {
            mostrarSucesso('Preferências salvas com sucesso');
            $('#modalPreferencias').modal('hide');
        } else {
            mostrarErro('Erro ao salvar algumas preferências');
        }
    })
    .catch(function() {
        mostrarErro('Erro de conexão');
    });
}

// Filtrar notificações (básico)
function filtrarNotificacoes() {
    const termo = $('#searchInput').val().toLowerCase();
    $('#notificacoesTableBody tr').each(function() {
        const texto = $(this).text().toLowerCase();
        if (texto.includes(termo)) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}

// Atualizar contador no header
function atualizarContadorHeader() {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {action: 'get_contagem'})
    .done(function(data) {
        if (data.success && window.updateNotificationBadge) {
            window.updateNotificationBadge(data.count);
        }
    });
}

// Funções de feedback usando AdminLTE Toasts
function mostrarSucesso(mensagem) {
    $(document).Toasts('create', {
        class: 'bg-success',
        title: 'Sucesso',
        body: mensagem,
        autohide: true,
        delay: 3000
    });
}

function mostrarErro(mensagem) {
    $(document).Toasts('create', {
        class: 'bg-danger',
        title: 'Erro',
        body: mensagem,
        autohide: true,
        delay: 5000
    });
}

function mostrarAlerta(mensagem) {
    $(document).Toasts('create', {
        class: 'bg-warning',
        title: 'Aviso',
        body: mensagem,
        autohide: true,
        delay: 4000
    });
}

// Função utilitária para escapar HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
