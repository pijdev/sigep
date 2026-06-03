/**
 * SolicitacoesKanban - Implementação Kanban estilo Microsoft Planner
 * 
 * Funcionalidades:
 * - Drag and Drop com jQuery UI Sortable
 * - Comunicação assíncrona via Fetch API
 * - Criação/Edição de cards
 * - Filtros dinâmicos
 * - Modais de detalhes
 */

(function ($) {
    'use strict';

    class SolicitacoesKanban {
        constructor() {
            this.endpoint = '/modulos/coordenacao/solicitacoes/SolicitacoesController.php';
            this.listaSetores = [];
            this.dadosKanban = {};
            this.cardSelecionado = null;
            this.internoSelecionado = null;
            this.filtrosAtivos = {
                termo: '',
                setor: '',
                categoria: '',
                prioridade: ''
            };
            
            // Configuração dos status para o Kanban
            this.statusConfig = [
                { key: 'Pendentes', label: 'A Fazer', color: '#ffc107', icon: 'fa-clock' },
                { key: 'Em Atendimento', label: 'Em Andamento', color: '#17a2b8', icon: 'fa-spinner' },
                { key: 'Aguardando', label: 'Impedido', color: '#6c757d', icon: 'fa-pause-circle' },
                { key: 'Atendidas', label: 'Concluído', color: '#28a745', icon: 'fa-check-circle' },
                { key: 'Canceladas', label: 'Cancelado', color: '#dc3545', icon: 'fa-times-circle' }
            ];

            // Configuração de prioridades
            this.prioridadeConfig = {
                'Urgente': { class: 'badge-danger', icon: 'fa-exclamation-triangle' },
                'Alta': { class: 'badge-warning', icon: 'fa-arrow-up' },
                'Média': { class: 'badge-info', icon: 'fa-minus' },
                'Baixa': { class: 'badge-secondary', icon: 'fa-arrow-down' }
            };

            // Cores para categorias
            this.categoriaCores = {
                'Saúde': '#e74c3c',
                'Jurídico': '#3498db',
                'Social': '#9b59b6',
                'Educação': '#2ecc71',
                'Trabalho': '#f39c12',
                'Outros': '#95a5a6'
            };

            this.init();
        }

        init() {
            this.bindEvents();
            this.carregarSetores();
            this.carregarKanban();
        }

        bindEvents() {
            // Botões principais
            $('#btnCriarCard').on('click', () => this.abrirModalNovoCard());
            $('#btnFiltrarKanban').on('click', () => this.togglePainelFiltros());
            $('#btnRecarregarKanban').on('click', () => this.carregarKanban());
            $('#btnAplicarFiltros').on('click', () => this.aplicarFiltros());

            // Formulário principal
            $('#formCard').on('submit', (e) => {
                e.preventDefault();
                this.salvarCard();
            });

            // Formulário criação rápida
            $('#formCriacaoRapida').on('submit', (e) => {
                e.preventDefault();
                this.criarRapido();
            });

            // Botão deletar
            $('#btnDeletarCard').on('click', () => this.confirmarDeletarCard());

            // Busca de interno no modal principal
            $('#buscaInterno').on('input', (e) => this.handleBuscaInterno(e, 'sugestoesInterno'));
            $('#buscaInterno').on('blur', () => setTimeout(() => $('#sugestoesInterno').hide(), 200));

            // Busca de interno na criação rápida
            $('#rapidoInterno').on('input', (e) => this.handleBuscaInterno(e, 'sugestoesRapido', true));
            $('#rapidoInterno').on('blur', () => setTimeout(() => $('#sugestoesRapido').hide(), 200));

            // Tarefas
            $('#btnAdicionarTarefa').on('click', () => this.adicionarTarefa());
            $('#novaTarefa').on('keypress', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.adicionarTarefa();
                }
            });

            // Comentários
            $('#btnAdicionarComentario').on('click', () => this.adicionarComentario());
            $('#novoComentario').on('keypress', (e) => {
                if (e.which === 13) {
                    e.preventDefault();
                    this.adicionarComentario();
                }
            });

            // Fechar modais
            $('#modalCard').on('hidden.bs.modal', () => this.limparModal());
        }

        // ============================================
        // MÉTODOS DE CARGA DE DADOS
        // ============================================

        carregarSetores() {
            $.get(`${this.endpoint}?acao=buscar_setores`)
                .done((response) => {
                    if (response.success) {
                        this.listaSetores = response.dados;
                        this.preencherSelectsSetores();
                    }
                })
                .fail(() => this.exibirMensagem('Erro ao carregar setores', 'danger'));
        }

        preencherSelectsSetores() {
            const selects = ['#filtroSetor', '#cardSetor', '#rapidoSetor'];
            const options = '<option value="">Todos os setores</option>' + 
                           this.listaSetores.map(s => `<option value="${s}">${s}</option>`).join('');
            
            selects.forEach(selector => {
                const $select = $(selector);
                const primeiro = $select.find('option').first();
                $select.html(primo).append(this.listaSetores.map(s => 
                    `<option value="${s}">${s}</option>`
                ).join(''));
            });
        }

        carregarKanban() {
            this.exibirLoading();
            
            const params = new URLSearchParams({
                acao: 'listar_cards',
                ...this.filtrosAtivos
            });

            console.log('Carregando Kanban...', this.endpoint, params.toString());

            $.get(`${this.endpoint}?${params.toString()}`)
                .done((response) => {
                    console.log('Resposta recebida:', response);
                    if (response.success) {
                        this.dadosKanban = response.dados;
                        this.renderizarKanban(response.dados);
                    } else {
                        this.exibirMensagem('Erro ao carregar solicitações: ' + (response.message || 'Erro desconhecido'), 'danger');
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Erro na requisição:', status, error);
                    console.error('Resposta completa:', xhr);
                    this.exibirMensagem('Falha na conexão com servidor: ' + error, 'danger');
                });
        }

        // ============================================
        // RENDERIZAÇÃO DO KANBAN
        // ============================================

        renderizarKanban(dados) {
            const $board = $('#kanbanBoard');
            $board.empty();

            if (Object.keys(dados).length === 0) {
                $board.html(`
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhuma solicitação encontrada</p>
                    </div>
                `);
                return;
            }

            // Renderiza cada coluna do Kanban
            this.statusConfig.forEach(status => {
                const cards = dados[status.key] || [];
                const $coluna = this.criarColuna(status, cards);
                $board.append($coluna);
            });

            // Inicializa jQuery UI Sortable nas colunas
            this.inicializarSortable();
        }

        criarColuna(status, cards) {
            const $coluna = $(`
                <div class="kanban-column" data-status="${status.key}">
                    <div class="kanban-column-header">
                        <div class="d-flex align-items-center">
                            <div class="kanban-column-indicator" style="background-color: ${status.color}"></div>
                            <h6 class="kanban-column-title mb-0">${status.label}</h6>
                        </div>
                        <div class="kanban-column-actions">
                            <span class="badge badge-light">${cards.length}</span>
                            <button class="btn btn-sm btn-outline-success btn-add-card ml-1" title="Adicionar card">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="kanban-column-cards" data-status="${status.key}"></div>
                </div>
            `);

            // Evento do botão de adicionar card rápido
            $coluna.find('.btn-add-card').on('click', () => {
                this.abrirCriacaoRapida(status.key);
            });

            // Adiciona os cards
            const $container = $coluna.find('.kanban-column-cards');
            cards.forEach(card => {
                $container.append(this.criarCard(card));
            });

            return $coluna;
        }

        criarCard(card) {
            const $card = $(`
                <div class="kanban-card" data-id="${card.id}" data-status="${card.status}">
                    <div class="kanban-card-header">
                        <div class="kanban-card-priority">
                            <span class="badge ${this.prioridadeConfig[card.prioridade]?.class || 'badge-secondary'}">
                                <i class="fas ${this.prioridadeConfig[card.prioridade]?.icon || 'fa-minus'}"></i>
                                ${card.prioridade}
                            </span>
                        </div>
                        <div class="kanban-card-id">#${card.id}</div>
                    </div>
                    
                    <div class="kanban-card-category">
                        ${card.categoria ? `<span class="kanban-category-tag" style="background-color: ${this.categoriaCores[card.categoria] || '#95a5a6'}">${card.categoria}</span>` : ''}
                    </div>

                    <div class="kanban-card-body">
                        <div class="kanban-card-interno">
                            <i class="fas fa-user mr-1"></i>
                            <strong>${card.ipen}</strong> — ${card.nome_social || card.nome_interno}
                        </div>
                        <div class="kanban-card-descricao">${this.truncarTexto(card.descricao, 80)}</div>
                    </div>

                    ${card.data_limite ? `
                        <div class="kanban-card-deadline ${card.data_limite_vencida ? 'text-danger' : 'text-muted'}">
                            <i class="fas fa-calendar-alt mr-1"></i>
                            ${card.data_limite_formatada}
                        </div>
                    ` : ''}

                    <div class="kanban-card-footer">
                        <div class="kanban-card-responsavel">
                            ${card.responsavel_nome ? `
                                <div class="kanban-avatar" title="${card.responsavel_nome}">
                                    ${this.obterIniciais(card.responsavel_nome)}
                                </div>
                            ` : `
                                <div class="kanban-avatar kanban-avatar-placeholder" title="Sem responsável">
                                    <i class="fas fa-user"></i>
                                </div>
                            `}
                        </div>
                        <div class="kanban-card-tarefas">
                            <span class="text-muted small">
                                <i class="fas fa-tasks mr-1"></i>
                                ${card.tarefas_concluidas}/${card.total_tarefas}
                            </span>
                        </div>
                    </div>
                </div>
            `);

            // Evento de clique para abrir detalhes
            $card.on('click', () => this.abrirModalDetalhes(card.id));

            return $card;
        }

        // ============================================
        // JQUERY UI SORTABLE (Drag and Drop)
        // ============================================

        inicializarSortable() {
            $('.kanban-column-cards').sortable({
                connectWith: '.kanban-column-cards',
                placeholder: 'kanban-card-placeholder',
                tolerance: 'pointer',
                helper: 'clone',
                opacity: 0.8,
                cursor: 'move',
                scroll: true,
                scrollSensitivity: 50,
                
                start: (event, ui) => {
                    ui.placeholder.height(ui.item.outerHeight());
                    ui.item.addClass('kanban-card-dragging');
                },

                stop: (event, ui) => {
                    ui.item.removeClass('kanban-card-dragging');
                    
                    const $card = ui.item;
                    const cardId = $card.data('id');
                    const novoStatus = $card.parent().data('status');
                    const statusOriginal = $card.data('status');

                    if (novoStatus !== statusOriginal) {
                        this.atualizarStatusCard(cardId, novoStatus, statusOriginal, $card);
                    }
                },

                receive: (event, ui) => {
                    const $column = $(event.target);
                    $column.addClass('kanban-column-highlight');
                    setTimeout(() => $column.removeClass('kanban-column-highlight'), 300);
                }
            }).disableSelection();
        }

        atualizarStatusCard(cardId, novoStatus, statusOriginal, $card) {
            const dados = {
                id: cardId,
                status: novoStatus
            };

            $.ajax({
                url: `${this.endpoint}?acao=atualizar_status`,
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                data: JSON.stringify(dados),
                dataType: 'json'
            })
            .done((response) => {
                if (response.success) {
                    $card.data('status', novoStatus);
                    this.exibirMensagem(`Status atualizado para ${novoStatus}`, 'success');
                } else {
                    // Reverte visualmente se falhar
                    this.reverterCard($card, statusOriginal);
                    this.exibirMensagem('Erro ao atualizar status', 'danger');
                }
            })
            .fail(() => {
                this.reverterCard($card, statusOriginal);
                this.exibirMensagem('Falha na conexão', 'danger');
            });
        }

        reverterCard($card, statusOriginal) {
            const $colunaOriginal = $(`.kanban-column-cards[data-status="${statusOriginal}"]`);
            $card.appendTo($colunaOriginal);
            $card.data('status', statusOriginal);
        }

        // ============================================
        // MODAIS E FORMULÁRIOS
        // ============================================

        abrirModalNovoCard() {
            $('#modalCardTitle').html('<i class="fas fa-plus-circle mr-2"></i>Nova Solicitação');
            $('#formCard')[0].reset();
            $('#cardId').val('');
            $('#btnDeletarCard').addClass('d-none');
            $('#infoInternoSelecionado').addClass('d-none');
            $('#listaTarefas').empty();
            $('#listaHistorico').html('<p class="text-muted text-sm">Selecione uma solicitação para ver o histórico.</p>');
            
            $('#modalCard').modal('show');
        }

        abrirModalDetalhes(cardId) {
            $.get(`${this.endpoint}?acao=buscar_detalhes&id=${cardId}`)
                .done((response) => {
                    if (response.success) {
                        this.preencherModalComDados(response.dados);
                        $('#modalCard').modal('show');
                    } else {
                        this.exibirMensagem('Erro ao carregar detalhes', 'danger');
                    }
                })
                .fail(() => this.exibirMensagem('Falha na conexão', 'danger'));
        }

        preencherModalComDados(dados) {
            this.cardSelecionado = dados;
            
            $('#modalCardTitle').html(`<i class="fas fa-edit mr-2"></i>Editar Solicitação #${dados.id}`);
            $('#cardId').val(dados.id);
            $('#internoId').val(dados.id_interno);
            $('#buscaInterno').val(`${dados.ipen} - ${dados.nome_social || dados.nome_interno}`);
            
            $('#infoInternoSelecionado')
                .removeClass('d-none')
                .html(`<i class="fas fa-user-check mr-1"></i>${dados.nome_interno} - ${dados.galeria || ''} ${dados.bloco || ''}`);

            $('#cardSetor').val(dados.setor_destino);
            $('#cardStatus').val(dados.status);
            $('#cardCategoria').val(dados.categoria || '');
            $('#cardPrioridade').val(dados.prioridade);
            $('#cardDataLimite').val(dados.data_limite || '');
            $('#cardResponsavel').val(dados.responsavel_nome || '');
            $('#cardDescricao').val(dados.descricao);

            $('#btnDeletarCard').removeClass('d-none');

            // Renderiza tarefas
            this.renderizarTarefas(dados.tarefas || []);

            // Renderiza histórico
            this.renderizarHistorico(dados.log || []);
        }

        limparModal() {
            this.cardSelecionado = null;
            this.internoSelecionado = null;
        }

        abrirCriacaoRapida(status) {
            $('#formCriacaoRapida')[0].reset();
            $('#rapidoStatus').val(status);
            $('#modalCriacaoRapida').modal('show');
        }

        // ============================================
        // CRUD DE CARDS
        // ============================================

        salvarCard() {
            const dados = this.obterDadosFormulario();
            
            if (!this.validarFormulario(dados)) {
                return;
            }

            $.ajax({
                url: `${this.endpoint}?acao=salvar_card`,
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                data: JSON.stringify(dados),
                dataType: 'json'
            })
            .done((response) => {
                if (response.success) {
                    $('#modalCard').modal('hide');
                    this.exibirMensagem('Solicitação salva com sucesso', 'success');
                    this.carregarKanban();
                } else {
                    this.exibirMensagem(response.message || 'Erro ao salvar', 'danger');
                }
            })
            .fail(() => this.exibirMensagem('Falha na conexão', 'danger'));
        }

        criarRapido() {
            const dados = {
                id_interno: $('#rapidoInternoId').val(),
                ipen: $('#rapidoInternoIPEN').val(),
                nome_interno: $('#rapidoInternoNome').val(),
                descricao: $('#rapidoDescricao').val(),
                setor_destino: $('#rapidoSetor').val(),
                status: $('#rapidoStatus').val(),
                prioridade: $('#rapidoPrioridade').val()
            };

            $.ajax({
                url: `${this.endpoint}?acao=criar_rapido`,
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                data: JSON.stringify(dados),
                dataType: 'json'
            })
            .done((response) => {
                if (response.success) {
                    $('#modalCriacaoRapida').modal('hide');
                    this.exibirMensagem('Solicitação criada com sucesso', 'success');
                    this.carregarKanban();
                } else {
                    this.exibirMensagem(response.message || 'Erro ao criar', 'danger');
                }
            })
            .fail(() => this.exibirMensagem('Falha na conexão', 'danger'));
        }

        confirmarDeletarCard() {
            if (!this.cardSelecionado || !confirm('Tem certeza que deseja excluir esta solicitação?')) {
                return;
            }

            $.ajax({
                url: `${this.endpoint}?acao=deletar_card`,
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                data: JSON.stringify({ id: this.cardSelecionado.id }),
                dataType: 'json'
            })
            .done((response) => {
                if (response.success) {
                    $('#modalCard').modal('hide');
                    this.exibirMensagem('Solicitação excluída com sucesso', 'success');
                    this.carregarKanban();
                } else {
                    this.exibirMensagem(response.message || 'Erro ao excluir', 'danger');
                }
            })
            .fail(() => this.exibirMensagem('Falha na conexão', 'danger'));
        }

        obterDadosFormulario() {
            return {
                id: $('#cardId').val(),
                id_interno: $('#internoId').val(),
                ipen: this.internoSelecionado?.ipen || 0,
                nome_interno: this.internoSelecionado?.nome || '',
                nome_social: this.internoSelecionado?.nome_social || '',
                galeria: this.internoSelecionado?.galeria || '',
                bloco: this.internoSelecionado?.bloco || '',
                res: this.internoSelecionado?.res || '',
                setor_destino: $('#cardSetor').val(),
                descricao: $('#cardDescricao').val(),
                status: $('#cardStatus').val(),
                categoria: $('#cardCategoria').val(),
                prioridade: $('#cardPrioridade').val(),
                data_limite: $('#cardDataLimite').val(),
                responsavel_nome: $('#cardResponsavel').val(),
                tarefas: this.obterTarefasDoDOM()
            };
        }

        validarFormulario(dados) {
            if (!dados.id_interno) {
                this.exibirMensagem('Selecione um interno', 'warning');
                return false;
            }
            if (!dados.descricao) {
                this.exibirMensagem('Descrição é obrigatória', 'warning');
                return false;
            }
            if (!dados.setor_destino) {
                this.exibirMensagem('Setor destino é obrigatório', 'warning');
                return false;
            }
            return true;
        }

        // ============================================
        // BUSCA DE INTERNOS (AUTOCOMPLETE)
        // ============================================

        handleBuscaInterno(evento, containerId, isRapido = false) {
            const termo = $(evento.target).val().trim();
            const $container = $(`#${containerId}`);

            if (termo.length < 2) {
                $container.hide();
                return;
            }

            $.get(`${this.endpoint}?acao=buscar_interno&termo=${encodeURIComponent(termo)}`)
                .done((response) => {
                    if (response.success && response.dados.length > 0) {
                        $container.html(response.dados.map(interno => `
                            <button type="button" class="list-group-item list-group-item-action" 
                                    data-ipen="${interno.ipen}" 
                                    data-nome="${interno.nome}" 
                                    data-nome-social="${interno.nome_social || ''}"
                                    data-galeria="${interno.galeria || ''}"
                                    data-bloco="${interno.bloco || ''}"
                                    data-res="${interno.res || ''}">
                                <strong>${interno.ipen}</strong> - ${interno.nome_social || interno.nome}
                                <br><small class="text-muted">${interno.galeria || ''} ${interno.bloco || ''}</small>
                            </button>
                        `).join('')).show();

                        $container.find('button').on('click', function() {
                            const dados = {
                                ipen: $(this).data('ipen'),
                                nome: $(this).data('nome'),
                                nome_social: $(this).data('nome-social'),
                                galeria: $(this).data('galeria'),
                                bloco: $(this).data('bloco'),
                                res: $(this).data('res')
                            };

                            if (isRapido) {
                                $('#rapidoInterno').val(`${dados.ipen} - ${dados.nome_social || dados.nome}`);
                                $('#rapidoInternoId').val(dados.ipen);
                                $('#rapidoInternoNome').val(dados.nome);
                                $('#rapidoInternoIPEN').val(dados.ipen);
                            } else {
                                $('#buscaInterno').val(`${dados.ipen} - ${dados.nome_social || dados.nome}`);
                                $('#internoId').val(dados.ipen);
                                this.internoSelecionado = dados;
                                
                                $('#infoInternoSelecionado')
                                    .removeClass('d-none')
                                    .html(`<i class="fas fa-user-check mr-1"></i>${dados.nome} - ${dados.galeria || ''} ${dados.bloco || ''}`);
                            }

                            $container.hide();
                        });
                    } else {
                        $container.hide();
                    }
                })
                .fail(() => $container.hide());
        }

        // ============================================
        // TAREFAS
        // ============================================

        renderizarTarefas(tarefas) {
            const $container = $('#listaTarefas');
            $container.empty();

            if (tarefas.length === 0) {
                $container.html('<p class="text-muted text-sm">Nenhuma tarefa adicionada.</p>');
                return;
            }

            tarefas.forEach((tarefa, index) => {
                const $tarefa = $(`
                    <div class="tarefa-item ${tarefa.concluida ? 'completed' : ''}" data-index="${index}">
                        <input type="checkbox" ${tarefa.concluida ? 'checked' : ''} class="mr-2">
                        <input type="text" class="form-control form-control-sm" value="${tarefa.texto}">
                        <button type="button" class="btn btn-sm btn-link text-danger"><i class="fas fa-trash"></i></button>
                    </div>
                `);

                // Checkbox de conclusão
                $tarefa.find('input[type="checkbox"]').on('change', function() {
                    $(this).closest('.tarefa-item').toggleClass('completed');
                });

                // Botão de exclusão
                $tarefa.find('button').on('click', function() {
                    $(this).closest('.tarefa-item').remove();
                });

                $container.append($tarefa);
            });
        }

        adicionarTarefa() {
            const texto = $('#novaTarefa').val().trim();
            if (!texto) return;

            const $tarefa = $(`
                <div class="tarefa-item">
                    <input type="checkbox" class="mr-2">
                    <input type="text" class="form-control form-control-sm" value="${texto}">
                    <button type="button" class="btn btn-sm btn-link text-danger"><i class="fas fa-trash"></i></button>
                </div>
            `);

            $tarefa.find('input[type="checkbox"]').on('change', function() {
                $(this).closest('.tarefa-item').toggleClass('completed');
            });

            $tarefa.find('button').on('click', function() {
                $(this).closest('.tarefa-item').remove();
            });

            $('#listaTarefas').append($tarefa);
            $('#novaTarefa').val('');
        }

        obterTarefasDoDOM() {
            const tarefas = [];
            $('#listaTarefas .tarefa-item').each(function() {
                const $tarefa = $(this);
                tarefas.push({
                    texto: $tarefa.find('input[type="text"]').val(),
                    concluida: $tarefa.find('input[type="checkbox"]').prop('checked')
                });
            });
            return tarefas;
        }

        // ============================================
        // HISTÓRICO E COMENTÁRIOS
        // ============================================

        renderizarHistorico(log) {
            const $container = $('#listaHistorico');
            $container.empty();

            if (log.length === 0) {
                $container.html('<p class="text-muted text-sm">Nenhum registro encontrado.</p>');
                return;
            }

            log.forEach(item => {
                const data = new Date(item.criado_em);
                const dataFormatada = data.toLocaleString('pt-BR');
                
                $container.append(`
                    <div class="historico-item">
                        <div class="d-flex justify-content-between">
                            <strong>
                                <span class="badge badge-info">${item.acao}</span>
                                ${item.usuario ? `<small class="ml-2">${item.usuario}</small>` : ''}
                            </strong>
                            <small class="text-muted">${dataFormatada}</small>
                        </div>
                        <p class="mb-1 mt-1">${item.descricao || ''}</p>
                    </div>
                `);
            });
        }

        adicionarComentario() {
            const comentario = $('#novoComentario').val().trim();
            if (!comentario || !this.cardSelecionado) return;

            $.post(`${this.endpoint}?acao=salvar_resposta`, {
                id: this.cardSelecionado.id,
                resposta: comentario
            }, 'json')
            .done((response) => {
                if (response.success) {
                    $('#novoComentario').val('');
                    this.abrirModalDetalhes(this.cardSelecionado.id); // Recarrega histórico
                    this.exibirMensagem('Comentário adicionado', 'success');
                } else {
                    this.exibirMensagem('Erro ao adicionar comentário', 'danger');
                }
            })
            .fail(() => this.exibirMensagem('Falha na conexão', 'danger'));
        }

        // ============================================
        // FILTROS
        // ============================================

        togglePainelFiltros() {
            $('#painelFiltros').toggleClass('d-none');
        }

        aplicarFiltros() {
            this.filtrosAtivos = {
                termo: $('#filtroTermo').val().trim(),
                setor: $('#filtroSetor').val(),
                categoria: $('#filtroCategoria').val(),
                prioridade: $('#filtroPrioridade').val()
            };
            this.carregarKanban();
            $('#painelFiltros').addClass('d-none');
        }

        // ============================================
        // UTILITÁRIOS
        // ============================================

        exibirLoading() {
            $('#kanbanBoard').html(`
                <div class="kanban-loading text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                    <p class="text-muted mt-2">Carregando solicitações...</p>
                </div>
            `);
        }

        exibirMensagem(mensagem, tipo) {
            const $container = $('#kanbanMensagens');
            const alerta = `
                <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
                    ${mensagem}
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            `;
            $container.html(alerta);
            
            setTimeout(() => {
                $container.find('.alert').alert('close');
            }, 5000);
        }

        truncarTexto(texto, maximo) {
            if (!texto) return '';
            return texto.length > maximo ? texto.substring(0, maximo) + '...' : texto;
        }

        obterIniciais(nome) {
            if (!nome) return '';
            return nome.split(' ').map(p => p[0]).join('').substring(0, 2).toUpperCase();
        }
    }

    // Inicializa quando o documento estiver pronto
    $(document).ready(function() {
        window.solicitacoesKanban = new SolicitacoesKanban();
    });

})(jQuery);
