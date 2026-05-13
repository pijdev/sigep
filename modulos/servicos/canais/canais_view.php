<!-- modulos/servicos/canais/canais_view.php -->
<!-- Administração de Canais de Notificação -->

<!-- Content Header -->
<div class="content-header px-4">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-bold" id="content-main-title">Canais de Notificação</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="/" id="breadcrumb-parent">SIGEP</a></li>
                    <li class="breadcrumb-item active" id="breadcrumb-title">Canais de Notificação</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<div class="container-fluid">
    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-search mr-2"></i>
                        Buscar Canais
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="mostrarModalCriarCanal()">
                            <i class="fas fa-plus mr-1"></i> Novo Canal
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <input type="text" class="form-control" id="searchCanais" placeholder="Buscar canais...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <select class="form-control" id="filterStatus">
                                    <option value="">Todos os Status</option>
                                    <option value="ativo">Ativos</option>
                                    <option value="inativo">Inativos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary btn-block" onclick="limparFiltros()">
                                <i class="fas fa-times mr-1"></i> Limpar Filtros
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Canais Grid -->
    <div class="row" id="canaisGrid">
        <!-- Canais serão carregados aqui -->
    </div>

    <!-- Empty State -->
    <div class="row" id="emptyState" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-broadcast-tower fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">Nenhum canal encontrado</h4>
                    <p class="text-muted">Crie seu primeiro canal para começar a enviar notificações.</p>
                    <button class="btn btn-success" onclick="mostrarModalCriarCanal()">
                        <i class="fas fa-plus mr-1"></i> Criar Primeiro Canal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Canal -->
<div class="modal fade" id="modalEditarCanal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Canal</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formEditarCanal">
                    <input type="hidden" id="editarCanalId">
                    <div class="form-group">
                        <label for="editarCanalNome">Nome do Canal</label>
                        <input type="text" class="form-control" id="editarCanalNome" required>
                        <small class="form-text text-muted">Nome único para identificar o canal</small>
                    </div>
                    <div class="form-group">
                        <label for="editarCanalDescricao">Descrição</label>
                        <textarea class="form-control" id="editarCanalDescricao" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="salvarEdicaoCanal()">Salvar</button>
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

<!-- JavaScript específico do módulo -->
<script>
// modulos/servicos/canais/canais.js
// JavaScript do módulo de canais de notificação

let currentCanalId = null;

// Inicializar
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    carregarCanais();

    // Event listeners para busca e filtros
    $('#searchCanais').on('input', function() {
        filtrarCanais();
    });

    $('#filterStatus').on('change', function() {
        filtrarCanais();
    });
});

// ===== FUNÇÕES PARA CANAIS =====

// Carregar canais
function carregarCanais() {
    $.post('/modulos/servicos/canais/canais_logica.php', {action: 'listar_canais'})
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
    if (canais.length === 0) {
        $('#emptyState').show();
        $('#canaisGrid').html('');
        return;
    }

    $('#emptyState').hide();
    let html = '';

    canais.forEach(function(canal) {
        const canalId = canal.id;
        const canalNome = escapeHtml(canal.nome);
        const canalDescricao = escapeHtml(canal.descricao || 'Sem descrição');
        const statusColor = canal.ativo ? 'success' : 'secondary';
        const statusText = canal.ativo ? 'Ativo' : 'Inativo';

        html += `
            <div class="col-lg-4 col-md-6 col-sm-12">
                <div class="card card-${statusColor} card-outline">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-broadcast-tower mr-2"></i>
                            ${canalNome}
                        </h5>
                        <div class="card-tools">
                            <span class="badge badge-${statusColor}">${statusText}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text text-muted">${canalDescricao}</p>

                        <!-- Estatísticas -->
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="description-block">
                                    <h5 class="description-header text-primary" id="stats-inscricoes-${canalId}">0</h5>
                                    <span class="description-text">INSCRITOS</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="description-block">
                                    <h5 class="description-header text-info" id="stats-tipos-${canalId}">0</h5>
                                    <span class="description-text">TIPOS</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="description-block">
                                    <h5 class="description-header text-success" id="stats-mensagens-${canalId}">0</h5>
                                    <span class="description-text">MSGS</span>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="progress mb-3">
                            <div class="progress-bar bg-${statusColor}" role="progressbar" style="width: 75%" id="progress-${canalId}"></div>
                        </div>
                        <p class="text-sm text-muted mb-0">Configuração: <span class="text-${statusColor}">75%</span> Completa</p>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button class="btn btn-outline-primary" onclick="abrirChat(${canalId}, '${canalNome}')" title="Enviar Mensagem">
                                <i class="fas fa-comment"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="gerenciarCanal(${canalId}, '${canalNome}')" title="Gerenciar Canal">
                                <i class="fas fa-cogs"></i>
                            </button>
                            <button class="btn btn-outline-warning" onclick="editarCanal(${canalId}, '${canalNome}', '${escapeHtml(canal.descricao || '')}')" title="Editar Canal">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deletarCanal(${canalId}, '${canalNome}')" title="Excluir Canal">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    $('#canaisGrid').html(html);

    // Carregar estatísticas para cada canal
    canais.forEach(function(canal) {
        carregarEstatisticasCanal(canal.id);
    });
}

// Criar canal
function criarCanal() {
    const nome = $('#canalNome').val().trim();
    const descricao = $('#canalDescricao').val().trim();
    if (!nome) {
        mostrarErro('Nome do canal é obrigatório');
        return;
    }
    $.post('/modulos/servicos/canais/canais_logica.php', {
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
function gerenciarCanal(id, nome) {
    currentCanalId = id;
    $('#modalCanalNome').text(nome);
    $('#modalGerenciarCanal').modal('show');
    carregarInscricoes();
    carregarTiposNotificacao();
}

// Carregar inscrições
function carregarInscricoes() {
    $.post('/modulos/servicos/canais/canais_logica.php', {
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
                    <span>${tipoLabel}: ${inscricao.identificador_nome}</span>
                    <button class="btn btn-sm btn-danger" onclick="removerInscricao('${inscricao.tipo}', '${inscricao.identificador}')">
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
    $.post('/modulos/servicos/canais/canais_logica.php', {
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

// Editar canal
function editarCanal(id, nome, descricao) {
    $('#editarCanalId').val(id);
    $('#editarCanalNome').val(nome);
    $('#editarCanalDescricao').val(descricao);
    $('#modalEditarCanal').modal('show');
}

// Salvar edição do canal
function salvarEdicaoCanal() {
    const id = $('#editarCanalId').val();
    const nome = $('#editarCanalNome').val().trim();
    const descricao = $('#editarCanalDescricao').val().trim();
    if (!nome) {
        mostrarErro('Nome do canal é obrigatório');
        return;
    }
    $.post('/modulos/servicos/canais/canais_logica.php', {
        action: 'editar_canal',
        canal_id: id,
        nome: nome,
        descricao: descricao
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Canal editado com sucesso');
            $('#modalEditarCanal').modal('hide');
            carregarCanais();
        } else {
            mostrarErro(data.error || 'Erro ao editar canal');
        }
    });
}

// Deletar canal
function deletarCanal(id, nome) {
    if (!confirm(`Deseja realmente deletar o canal "${nome}"?`)) return;
    $.post('/modulos/servicos/canais/canais_logica.php', {
        action: 'deletar_canal',
        canal_id: id
    })
    .done(function(data) {
        if (data.success) {
            mostrarSucesso('Canal deletado com sucesso');
            carregarCanais();
        } else {
            mostrarErro('Erro ao deletar canal');
        }
    });
}

// Carregar setores
function carregarSetores() {
    $.post('/modulos/servicos/canais/canais_logica.php', {action: 'listar_setores'})
    .done(function(data) {
        if (data.success) {
            let options = '<option value="">Selecione um setor</option>';
            data.setores.forEach(function(setor) {
                options += `<option value="${setor.slug}">${setor.nome}</option>`;
            });
            $('#setorSelect').html(options);
        }
    });
}

// Carregar estatísticas do canal
function carregarEstatisticasCanal(canalId) {
    $.post('/modulos/servicos/canais/canais_logica.php', {
        action: 'get_estatisticas_canal',
        canal_id: canalId
    })
    .done(function(data) {
        if (data.success) {
            $('#stats-inscricoes-' + canalId).text(data.stats.inscricoes || 0);
            $('#stats-tipos-' + canalId).text(data.stats.tipos || 0);
            $('#stats-mensagens-' + canalId).text(data.stats.mensagens || 0);

            // Calcular progresso baseado nas estatísticas
            const progresso = calcularProgresso(data.stats);
            $('#progress-' + canalId).css('width', progresso + '%');
            $('#progress-' + canalId).closest('.card-body').find('.text-success').text(progresso + '%');
        }
    });
}

// Calcular progresso de configuração do canal
function calcularProgresso(stats) {
    let pontos = 0;
    if (stats.inscricoes > 0) pontos += 25;
    if (stats.tipos > 0) pontos += 25;
    if (stats.mensagens > 0) pontos += 25;
    pontos += 25; // Canal criado
    return Math.min(pontos, 100);
}

// Limpar filtros
function limparFiltros() {
    $('#searchCanais').val('');
    $('#filterStatus').val('');
    carregarCanais();
}

// Filtrar canais
function filtrarCanais() {
    const searchTerm = $('#searchCanais').val().toLowerCase();
    const statusFilter = $('#filterStatus').val();

    $('.card').each(function() {
        const card = $(this);
        const canalNome = card.find('.card-title').text().toLowerCase();
        const canalStatus = card.find('.badge').text().toLowerCase();

        let mostrar = true;

        // Filtro por busca
        if (searchTerm && !canalNome.includes(searchTerm)) {
            mostrar = false;
        }

        // Filtro por status
        if (statusFilter) {
            const filtroStatus = statusFilter === 'ativo' ? 'ativo' : 'inativo';
            if (!canalStatus.includes(filtroStatus)) {
                mostrar = false;
            }
        }

        if (mostrar) {
            card.closest('.col-lg-4').show();
        } else {
            card.closest('.col-lg-4').hide();
        }
    });
}

// Abrir chat direto do canal
function abrirChat(canalId, canalNome) {
    Swal.fire({
        title: `Enviar Mensagem - ${canalNome}`,
        html: `
            <div class="form-group">
                <label>Tipo de Notificação:</label>
                <select class="form-control" id="chatTipoNotificacao">
                    <option value="">Carregando tipos...</option>
                </select>
            </div>
            <div class="form-group">
                <label>Título da Mensagem:</label>
                <input type="text" class="form-control" id="chatTitulo" placeholder="Ex: Alerta do Sistema">
            </div>
            <div class="form-group">
                <label>Mensagem:</label>
                <textarea class="form-control" id="chatMensagem" rows="3" placeholder="Digite sua mensagem..."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Enviar',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const tipo = $('#chatTipoNotificacao').val();
            const titulo = $('#chatTitulo').val().trim();
            const mensagem = $('#chatMensagem').val().trim();

            if (!tipo) {
                Swal.showValidationMessage('Selecione um tipo de notificação');
                return false;
            }
            if (!titulo) {
                Swal.showValidationMessage('Título é obrigatório');
                return false;
            }
            if (!mensagem) {
                Swal.showValidationMessage('Mensagem é obrigatória');
                return false;
            }

            return { tipo, titulo, mensagem };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            enviarMensagemChat(canalId, result.value.tipo, result.value.titulo, result.value.mensagem);
        }
    });

    // Carregar tipos de notificação disponíveis
    carregarTiposParaChat(canalId);
}

// Carregar tipos para chat
function carregarTiposParaChat(canalId) {
    $.post('/modulos/servicos/canais/canais_logica.php', {
        action: 'listar_tipos_notificacao',
        canal_id: canalId
    })
    .done(function(data) {
        if (data.success && data.tipos.length > 0) {
            let options = '<option value="">Selecione um tipo</option>';
            data.tipos.forEach(function(tipo) {
                options += `<option value="${escapeHtml(tipo)}">${escapeHtml(tipo)}</option>`;
            });
            $('#chatTipoNotificacao').html(options);
        } else {
            $('#chatTipoNotificacao').html('<option value="">Nenhum tipo disponível</option>');
        }
    });
}

// Enviar mensagem via chat
function enviarMensagemChat(canalId, tipo, titulo, mensagem) {
    $.post('/modulos/servicos/canais/canais_logica.php', {
        action: 'enviar_mensagem_manual',
        canal_id: canalId,
        tipo: tipo,
        titulo: titulo,
        mensagem: mensagem
    })
    .done(function(data) {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Mensagem Enviada!',
                text: 'A notificação foi enviada para todos os inscritos no canal.',
                timer: 3000,
                showConfirmButton: false
            });
            // Recarregar estatísticas
            carregarEstatisticasCanal(canalId);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: data.error || 'Erro ao enviar mensagem'
            });
        }
    });
}

// Adicionar inscrição
function adicionarInscricao() {
    const tipo = $('input[name=tipoInscricao]:checked').val();
    const identificador = tipo === 'user' ? $('#usuarioSelect').val() : $('#setorSelect').val();
    if (!identificador) {
        mostrarErro('Selecione um usuário ou setor');
        return;
    }
    $.post('/modulos/servicos/canais/canais_logica.php', {
        action: tipo === 'user' ? 'inscrever_usuario' : 'inscrever_setor',
        canal_id: currentCanalId,
        [tipo === 'user' ? 'user_id' : 'setor_id']: identificador
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
    $.post('/modulos/servicos/canais/canais_logica.php', {
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

// Mostrar modal adicionar inscrição
function mostrarModalAdicionarInscricao() {
    $('#modalAdicionarInscricao').modal('show');
    carregarUsuarios();
    carregarSetores();
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
    $.post('/modulos/servicos/canais/canais_logica.php', {
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
    $.post('/modulos/servicos/canais/canais_logica.php', {
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
</script>

<!-- SweetAlert2 and Toastr Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
