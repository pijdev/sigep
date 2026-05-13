<!-- modulos/servicos/notificacoes/notificacoes_view.php -->
<!-- Sistema de Notificações SIGEP -->

<!-- Content Header (para SPA) -->
<div class="content-header px-4">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-bold" id="content-main-title">Notificações</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/" id="breadcrumb-parent">SIGEP</a></li>
                    <li class="breadcrumb-item active" id="breadcrumb-title">Notificações</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="container-fluid">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="totalNotificacoes">0</h3>
                        <p>Total de Notificações</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-bell"></i>
                    </div>
                    <a href="#" class="small-box-footer" onclick="carregarNotificacoes()">
                        Atualizar <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="naoLidas">0</h3>
                        <p>Não Lidas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <a href="#" class="small-box-footer" onclick="marcarTodasComoLidas()">
                        Marcar todas <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="hoje">0</h3>
                        <p>Hoje</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="small-box-footer">&nbsp;</div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="erros">0</h3>
                        <p>Erros/Sistema</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="small-box-footer">&nbsp;</div>
                </div>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="row mb-3">
            <div class="col-12">
                <button type="button" class="btn btn-success mr-2" onclick="carregarNotificacoes()">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
                <button type="button" class="btn btn-primary mr-2" onclick="marcarTodasComoLidas()">
                    <i class="fas fa-check-double"></i> Marcar Todas como Lidas
                </button>
                <button type="button" class="btn btn-info" onclick="$('#modalPreferencias').modal('show'); carregarPreferencias();">
                    <i class="fas fa-cog"></i> Preferências
                </button>
            </div>
        </div>

        <!-- Notifications Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Minhas Notificações</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" name="table_search" class="form-control float-right" placeholder="Buscar..." id="searchInput">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default" onclick="filtrarNotificacoes()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Tipo</th>
                            <th>Título</th>
                            <th>Mensagem</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="notificacoesTableBody">
                        <!-- Notificações carregadas via AJAX -->
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <div class="row">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info" id="paginationInfo">Mostrando 0 de 0 notificações</div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div class="dataTables_paginate paging_simple_numbers float-right" id="paginationControls">
                            <!-- Controles de paginação -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Preferências -->
<div class="modal fade" id="modalPreferencias" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-cog mr-2"></i>
                    Preferências de Notificação
                </h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Configure quais tipos de notificações você deseja receber:</p>
                <div id="preferenciasContainer">
                    <!-- Preferências carregadas via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" onclick="salvarPreferencias()">Salvar</button>
            </div>
        </div>
    </div>
</div>

<?php if ($_SESSION['user_admin'] ?? false): ?>
<!-- Seção de Administração de Canais -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-broadcast-tower mr-2"></i>
                    Administração de Canais de Notificação
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-success btn-sm" onclick="mostrarModalCriarCanal()">
                        <i class="fas fa-plus mr-1"></i> Novo Canal
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="canaisContainer">
                    <!-- Canais serão carregados aqui -->
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Criar Canal -->
<div class="modal fade" id="modalCriarCanal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Criar Novo Canal</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formCriarCanal">
                    <div class="form-group">
                        <label for="canalNome">Nome do Canal</label>
                        <input type="text" class="form-control" id="canalNome" required>
                        <small class="form-text text-muted">Nome único para identificar o canal</small>
                    </div>
                    <div class="form-group">
                        <label for="canalDescricao">Descrição</label>
                        <textarea class="form-control" id="canalDescricao" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="criarCanal()">Criar Canal</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gerenciar Canal -->
<div class="modal fade" id="modalGerenciarCanal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gerenciar Canal: <span id="modalCanalNome"></span></h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="canalTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="inscricoes-tab" data-toggle="tab" href="#inscricoes" role="tab">Inscrições</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="tipos-tab" data-toggle="tab" href="#tipos" role="tab">Tipos de Notificação</a>
                    </li>
                </ul>
                <div class="tab-content mt-3" id="canalTabContent">
                    <div class="tab-pane fade show active" id="inscricoes" role="tabpanel">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Inscritos no Canal</h6>
                            <button class="btn btn-sm btn-primary" onclick="mostrarModalAdicionarInscricao()">Adicionar</button>
                        </div>
                        <div id="inscricoesContainer">
                            <!-- Inscrições serão carregadas aqui -->
                        </div>
                    </div>
                    <div class="tab-pane fade" id="tipos" role="tabpanel">
                        <div class="d-flex justify-content-between mb-3">
                            <h6>Tipos de Notificação Ativos</h6>
                            <button class="btn btn-sm btn-success" onclick="mostrarModalAdicionarTipo()">Adicionar Tipo</button>
                        </div>
                        <div id="tiposContainer">
                            <!-- Tipos serão carregados aqui -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Inscrição -->
<div class="modal fade" id="modalAdicionarInscricao" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Inscrição</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAdicionarInscricao">
                    <div class="form-group">
                        <label>Tipo de Inscrição</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoInscricao" id="tipoUsuario" value="user" checked>
                            <label class="form-check-label" for="tipoUsuario">
                                Usuário Específico
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="tipoInscricao" id="tipoSetor" value="setor">
                            <label class="form-check-label" for="tipoSetor">
                                Todo o Setor
                            </label>
                        </div>
                    </div>
                    <div class="form-group" id="usuarioGroup">
                        <label for="usuarioSelect">Usuário</label>
                        <select class="form-control" id="usuarioSelect">
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                    <div class="form-group d-none" id="setorGroup">
                        <label for="setorSelect">Setor</label>
                        <select class="form-control" id="setorSelect">
                            <option value="">Carregando...</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="adicionarInscricao()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Tipo -->
<div class="modal fade" id="modalAdicionarTipo" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Tipo de Notificação</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formAdicionarTipo">
                    <div class="form-group">
                        <label for="tipoNome">Nome do Tipo</label>
                        <input type="text" class="form-control" id="tipoNome" placeholder="ex: executado, erro, atrasado" required>
                        <small class="form-text text-muted">Nome do tipo de notificação que será enviado pelo canal</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="adicionarTipoNotificacao()">Adicionar</button>
            </div>
        </div>
    </div>
</div>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/servicos/notificacoes/assets/css/notificacoes.css?v=<?= time() ?>">

<!-- JavaScript específico do módulo -->
<script>
// modulos/servicos/notificacoes/assets/js/notificacoes.js
// JavaScript do módulo de notificações

let notificationCurrentPage = 1;
let totalPages = 1;
const itemsPerPage = 20;

// Inicializar tooltips AdminLTE
$('[data-toggle="tooltip"]').tooltip();

// Configurar polling suave (opcional)
setInterval(atualizarContadorHeader, 60000); // 1 minuto

// Carregar notificações com paginação
function carregarNotificacoes(pagina = 1) {
    notificationCurrentPage = pagina;

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
            carregarNotificacoes(notificationCurrentPage);
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
            carregarNotificacoes(notificationCurrentPage);
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

// Inicializar página
carregarNotificacoes();
atualizarEstatisticas();

// ===== FUNÇÕES PARA CANAIS (apenas admin) =====

// Carregar canais
function carregarCanais() {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {action: 'listar_canais'})
    .done(function(data) {
        if (data.success) {
            renderizarCanais(data.canais);
        } else {
            mostrarErro('Erro ao carregar canais');
        }
    });
}

// Renderizar canais
function renderizarCanais(canais) {
    let html = '';
    if (canais.length === 0) {
        html = '<p class="text-muted">Nenhum canal encontrado.</p>';
    } else {
        canais.forEach(function(canal) {
            html += `
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">${escapeHtml(canal.nome)}</h6>
                        <button class="btn btn-sm btn-primary" onclick="gerenciarCanal(${canal.id}, '${escapeHtml(canal.nome)}')">Gerenciar</button>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">${escapeHtml(canal.descricao || 'Sem descrição')}</p>
                    </div>
                </div>
            `;
        });
    }
    $('#canaisContainer').html(html);
}

// Criar canal
function criarCanal() {
    const nome = $('#canalNome').val().trim();
    const descricao = $('#canalDescricao').val().trim();
    if (!nome) {
        mostrarErro('Nome do canal é obrigatório');
        return;
    }
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'criar_canal',
        nome: nome,
        descricao: descricao
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Canal criado com sucesso');
            $('#modalCriarCanal').modal('hide');
            $('#formCriarCanal')[0].reset();
            carregarCanais();
        } else {
            mostrarErro(data.error || 'Erro ao criar canal');
        }
    });
}

// Gerenciar canal
let currentCanalId = null;
function gerenciarCanal(id, nome) {
    currentCanalId = id;
    $('#modalCanalNome').text(nome);
    $('#modalGerenciarCanal').modal('show');
    carregarInscricoes();
    carregarTiposNotificacao();
}

// Carregar inscrições
function carregarInscricoes() {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'listar_inscricoes',
        canal_id: currentCanalId
    })
    .done(function(data) {
        if (data.success) {
            renderizarInscricoes(data.inscricoes);
        }
    });
}

// Renderizar inscrições
function renderizarInscricoes(inscricoes) {
    let html = '';
    if (inscricoes.length === 0) {
        html = '<p class="text-muted">Nenhuma inscrição encontrada.</p>';
    } else {
        inscricoes.forEach(function(inscricao) {
            const tipoLabel = inscricao.tipo === 'user' ? 'Usuário' : 'Setor';
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>${tipoLabel}: ${inscricao.identificador}</span>
                    <button class="btn btn-sm btn-danger" onclick="removerInscricao('${inscricao.tipo}', ${inscricao.identificador})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });
    }
    $('#inscricoesContainer').html(html);
}

// Carregar tipos de notificação
function carregarTiposNotificacao() {
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'listar_tipos_notificacao',
        canal_id: currentCanalId
    })
    .done(function(data) {
        if (data.success) {
            renderizarTipos(data.tipos);
        }
    });
}

// Renderizar tipos
function renderizarTipos(tipos) {
    let html = '';
    if (tipos.length === 0) {
        html = '<p class="text-muted">Nenhum tipo encontrado.</p>';
    } else {
        tipos.forEach(function(tipo) {
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>${escapeHtml(tipo)}</span>
                    <button class="btn btn-sm btn-danger" onclick="removerTipo('${escapeHtml(tipo)}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
        });
    }
    $('#tiposContainer').html(html);
}

// Mostrar modal criar canal
function mostrarModalCriarCanal() {
    $('#modalCriarCanal').modal('show');
}

// Mostrar modal adicionar inscrição
function mostrarModalAdicionarInscricao() {
    $('#modalAdicionarInscricao').modal('show');
}

// Adicionar inscrição
function adicionarInscricao() {
    const tipo = $('input[name=tipoInscricao]:checked').val();
    const identificador = tipo === 'user' ? $('#usuarioSelect').val() : $('#setorSelect').val();
    if (!identificador) {
        mostrarErro('Selecione um usuário ou setor');
        return;
    }
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'inscrever_' + tipo,
        canal_id: currentCanalId,
        [tipo + '_id']: identificador
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Inscrição adicionada');
            $('#modalAdicionarInscricao').modal('hide');
            carregarInscricoes();
        } else {
            mostrarErro('Erro ao adicionar inscrição');
        }
    });
}

// Remover inscrição
function removerInscricao(tipo, identificador) {
    if (!confirm('Remover inscrição?')) return;
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'desinscrever',
        canal_id: currentCanalId,
        tipo: tipo,
        identificador: identificador
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Inscrição removida');
            carregarInscricoes();
        }
    });
}

// Mostrar modal adicionar tipo
function mostrarModalAdicionarTipo() {
    $('#modalAdicionarTipo').modal('show');
}

// Adicionar tipo de notificação
function adicionarTipoNotificacao() {
    const tipo = $('#tipoNome').val().trim();
    if (!tipo) {
        mostrarErro('Nome do tipo é obrigatório');
        return;
    }
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'adicionar_tipo_notificacao',
        canal_id: currentCanalId,
        tipo: tipo
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Tipo adicionado');
            $('#modalAdicionarTipo').modal('hide');
            $('#formAdicionarTipo')[0].reset();
            carregarTiposNotificacao();
        }
    });
}

// Remover tipo de notificação
function removerTipo(tipo) {
    if (!confirm('Remover tipo?')) return;
    $.post('/modulos/servicos/notificacoes/notificacoes_logica.php', {
        action: 'remover_tipo_notificacao',
        canal_id: currentCanalId,
        tipo: tipo
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Tipo removido');
            carregarTiposNotificacao();
        }
    });
}

// Toggle tipo inscrição
$('input[name=tipoInscricao]').change(function() {
    if (this.value === 'user') {
        $('#usuarioGroup').removeClass('d-none');
        $('#setorGroup').addClass('d-none');
    } else {
        $('#setorGroup').removeClass('d-none');
        $('#usuarioGroup').addClass('d-none');
    }
});

// Inicializar canais se admin
<?php if ($_SESSION['user_admin'] ?? false): ?>
carregarCanais();
<?php endif; ?>
</script>
