/**
 * Kanban de Solicitações
 * Regras:
 *  - Confirmações destrutivas: SweetAlert2
 *  - Notificações / feedback: toast Bootstrap (função toast())
 *  - Nenhum confirm() nativo, nenhum alert() nativo
 *  - Sem modal "loading" que trava a tela
 */
(function ($) {
    'use strict';

    const API = '/modulos/coordenacao/solicitacoes/index.php';

    /* ------------------------------------------------------------------
       STATUS CONFIG
       Pendentes   → bg-info    (azul)
       Em Atendimento → bg-warning (amarelo)
       Aguardando  → bg-danger  (vermelho)
       Atendidas   → bg-success (verde)
       Canceladas  → bg-secondary (cinza) – oculta por padrão
    ------------------------------------------------------------------ */
    const STATUS = [
        { key: 'Pendentes',      label: 'Pendente',        cssColor: '#17a2b8' },
        { key: 'Em Atendimento', label: 'Em Atendimento',   cssColor: '#ffc107' },
        { key: 'Aguardando',     label: 'Aguardando',       cssColor: '#dc3545' },
        { key: 'Atendidas',      label: 'Concluído',        cssColor: '#28a745' },
        { key: 'Canceladas',     label: 'Cancelado',        cssColor: '#6c757d' },
    ];

    /* Estado local */
    let setores        = [];
    let categorias     = [];
    let cardSelecionado= null;
    let internoSel     = null;
    let filtros        = { termo: '', setor: '', categoria: '', ver_canceladas: '0' };

    /* ================================================================
       INIT
    ================================================================ */
    $(function () {
        carregarSetores();
        carregarCategorias();
        carregarKanban();
        bindEventos();
    });

    /* ================================================================
       EVENTOS
    ================================================================ */
    function bindEventos() {
        $('#btnCriarCard').on('click', abrirNovoCard);
        $('#btnFiltrar').on('click', () => $('#painelFiltros').toggleClass('d-none'));
        $('#btnRecarregar').on('click', carregarKanban);
        $('#btnAplicarFiltros').on('click', aplicarFiltros);

        $('#formCard').on('submit', function (e) { e.preventDefault(); salvarCard(); });
        $('#formRapido').on('submit', function (e) { e.preventDefault(); criarRapido(); });
        $('#formCategoria').on('submit', function (e) { e.preventDefault(); salvarCategoria(); });

        $('#btnDeletarCard').on('click', confirmarDeletar);
        $('#btnGerenciarCategorias').on('click', abrirModalCategorias);
        $('#btnCancelarCat').on('click', cancelarEdicaoCategoria);

        $('#btnAdicionarTarefa').on('click', adicionarTarefa);
        $('#novaTarefa').on('keypress', function (e) {
            if (e.which === 13) { e.preventDefault(); adicionarTarefa(); }
        });

        $('#btnAdicionarComentario').on('click', adicionarComentario);

        $('#buscaInterno').on('input', function () { buscaInterno($(this).val(), 'sugestoesInterno', false); });
        $('#buscaInterno').on('blur', function () { setTimeout(() => $('#sugestoesInterno').hide(), 200); });

        $('#rapidoInterno').on('input', function () { buscaInterno($(this).val(), 'sugestoesRapido', true); });
        $('#rapidoInterno').on('blur', function () { setTimeout(() => $('#sugestoesRapido').hide(), 200); });

        $('#modalCard').on('hidden.bs.modal', limparModal);
    }


    /* ================================================================
       CARREGAR DADOS
    ================================================================ */
    function carregarSetores() {
        $.get(API + '?acao=buscar_setores').done(function (r) {
            console.log('buscar_setores resposta:', r);
            if (!r.success) {
                console.error('Erro ao carregar setores:', r);
                return;
            }
            setores = r.dados || [];
            console.log('Setores carregados:', setores);
            const opts = setores.map(s => `<option value="${esc(s)}">${esc(s)}</option>`).join('');
            $('#filtroSetor, #cardSetor, #rapidoSetor').each(function () {
                const primeiro = $(this).is('#filtroSetor') ? '<option value="">Todos</option>' : '<option value="">Selecione...</option>';
                $(this).html(primeiro + opts);
            });
        }).fail(function (err) {
            console.error('Falha em buscar_setores:', err);
        });
    }

    function carregarCategorias() {
        $.get(API + '?acao=buscar_categorias').done(function (r) {
            if (!r.success) return;
            categorias = r.dados;
            preencherSelectsCategorias();
        });
    }

    function preencherSelectsCategorias() {
        const opc = categorias.map(c =>
            `<option value="${esc(c.name)}" data-cor="${esc(c.cor)}">${esc(c.name)}</option>`
        ).join('');
        const base = '<option value="">Sem categoria</option>';
        const baseF= '<option value="">Todas</option>';
        $('#cardCategoria, #rapidoCategoria').html(base + opc);
        $('#filtroCategoria').html(baseF + opc);
    }

    function carregarKanban() {
        $('#kanbanBoard').html('<div class="text-center py-5 w-100"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>');
        $.get(API, $.extend({ acao: 'listar_cards' }, filtros)).done(function (r) {
            if (r.success) renderizarKanban(r.dados);
            else toast('Erro ao carregar solicitações', 'danger');
        }).fail(function () { toast('Falha na conexão', 'danger'); });
    }

    /* ================================================================
       RENDERIZAR KANBAN
    ================================================================ */
    function renderizarKanban(dados) {
        const $board = $('#kanbanBoard').empty();
        const mostrarCanceladas = filtros.ver_canceladas === '1';

        STATUS.forEach(function (st) {
            if (st.key === 'Canceladas' && !mostrarCanceladas) return;

            const cards = dados[st.key] || [];
            const $col  = $(`
                <div class="kanban-col" data-status="${st.key}">
                    <div class="kanban-col-header" style="--col-color:${st.cssColor}">
                        <h6 class="kanban-col-title">${esc(st.label)}</h6>
                        <div class="kanban-col-actions">
                            <span class="badge badge-secondary">${cards.length}</span>
                            <button class="btn btn-sm btn-outline-secondary btn-add-col" title="Nova solicitação nesta coluna">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="kanban-col-cards" data-status="${st.key}"></div>
                </div>
            `);

            $col.find('.btn-add-col').on('click', function () { abrirRapido(st.key); });

            const $container = $col.find('.kanban-col-cards');
            cards.forEach(function (card) { $container.append(criarCard(card)); });

            $board.append($col);
        });

        inicializarSortable();
    }


    function criarCard(card) {
        const catCor = (categorias.find(c => c.name === card.categoria) || {}).cor || '#6c757d';
        const catTag = card.categoria
            ? `<span class="card-cat-tag" style="background:${esc(catCor)}">${esc(card.categoria)}</span>`
            : '';
        const nomeMostrar = card.nome_social || card.nome_interno || '—';
        const $card = $(`
            <div class="kanban-card" data-id="${card.id}" data-status="${esc(card.status)}">
                <div class="card-id">#${card.id}</div>
                ${catTag}
                <div class="card-interno"><i class="fas fa-user mr-1 text-muted"></i><strong>${esc(String(card.ipen))}</strong> — ${esc(nomeMostrar)}</div>
                <div class="card-descricao">${esc(truncar(card.descricao, 90))}</div>
                <div class="card-meta">
                    <span><i class="fas fa-building mr-1"></i>${esc(card.setor_destino || '')}</span>
                    <span><i class="fas fa-tasks mr-1"></i>${card.tarefas_concluidas || 0}/${card.total_tarefas || 0}</span>
                </div>
            </div>
        `);

        $card.on('click', function () { abrirDetalhes(card.id); });
        return $card;
    }

    /* ================================================================
       DRAG & DROP (SortableJS)
    ================================================================ */
    function inicializarSortable() {
        document.querySelectorAll('.kanban-col-cards').forEach(function (col) {
            new Sortable(col, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'sortable-ghost',
                dragClass: 'sortable-drag',
                onEnd: function (evt) {
                    const $card  = $(evt.item);
                    const cardId = $card.data('id');
                    const novo   = $(evt.to).data('status');
                    const orig   = $card.data('status');
                    if (novo !== orig) atualizarStatus(cardId, novo, orig, $card);
                }
            });
        });
    }

    function atualizarStatus(cardId, novoStatus, statusOrig, $card) {
        $.ajax({
            url: API + '?acao=atualizar_status',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: cardId, status: novoStatus }),
            dataType: 'json'
        }).done(function (r) {
            if (r.success) {
                $card.data('status', novoStatus).attr('data-status', novoStatus);
                toast('Status atualizado', 'success');
            } else {
                reverterCard($card, statusOrig);
                toast(r.message || 'Erro ao atualizar status', 'danger');
            }
        }).fail(function () {
            reverterCard($card, statusOrig);
            toast('Falha na conexão', 'danger');
        });
    }

    function reverterCard($card, statusOrig) {
        const $orig = $(`.kanban-col-cards[data-status="${statusOrig}"]`);
        $card.appendTo($orig).data('status', statusOrig).attr('data-status', statusOrig);
    }


    /* ================================================================
       MODAL CARD (criar / editar)
    ================================================================ */
    function abrirNovoCard() {
        limparModal();
        $('#modalCardTitulo').text('Nova Solicitação');
        $('#btnDeletarCard').addClass('d-none');
        $('#modalCard').modal('show');
    }

    function abrirDetalhes(id) {
        $.get(API + '?acao=buscar_detalhes&id=' + id).done(function (r) {
            if (!r.success) { toast('Erro ao carregar detalhes', 'danger'); return; }
            preencherModal(r.dados);
            $('#modalCard').modal('show');
        }).fail(function () { toast('Falha na conexão', 'danger'); });
    }

    function preencherModal(dados) {
        cardSelecionado = dados;
        $('#modalCardTitulo').text('Editar Solicitação #' + dados.id);
        $('#cardId').val(dados.id);
        $('#internoId').val(dados.id_interno);
        $('#buscaInterno').val(dados.ipen + ' — ' + (dados.nome_social || dados.nome_interno));
        $('#infoInterno').text(dados.nome_interno + ' | ' + (dados.galeria || '') + ' ' + (dados.bloco || '')).removeClass('d-none');
        $('#cardSetor').val(dados.setor_destino);
        $('#cardStatus').val(dados.status);
        $('#cardCategoria').val(dados.categoria || '');
        $('#cardResponsavel').val(dados.responsavel_nome || '');
        $('#cardDescricao').val(dados.descricao);
        $('#btnDeletarCard').removeClass('d-none');
        renderizarTarefas(dados.tarefas || []);
        renderizarHistorico(dados.log || []);
    }

    function limparModal() {
        cardSelecionado = null;
        internoSel      = null;
        $('#formCard')[0].reset();
        $('#cardId').val('');
        $('#internoId').val('');
        $('#infoInterno').addClass('d-none').text('');
        $('#listaTarefas').empty();
        $('#listaHistorico').html('<p class="text-muted text-sm">Nenhum histórico.</p>');
        $('#buscaInterno').val('');
    }

    function salvarCard() {
        const dados = {
            id:              $('#cardId').val() || 0,
            id_interno:      $('#internoId').val(),
            ipen:            internoSel ? internoSel.ipen : 0,
            nome_interno:    internoSel ? internoSel.nome : $('#buscaInterno').val(),
            nome_social:     internoSel ? internoSel.nome_social : '',
            galeria:         internoSel ? internoSel.galeria : '',
            bloco:           internoSel ? internoSel.bloco : '',
            res:             internoSel ? internoSel.res : '',
            setor_destino:   $('#cardSetor').val(),
            descricao:       $('#cardDescricao').val().trim(),
            status:          $('#cardStatus').val(),
            categoria:       $('#cardCategoria').val(),
            responsavel_nome:$('#cardResponsavel').val().trim(),
            tarefas:         coletarTarefas(),
        };

        if (!dados.id_interno) { toast('Selecione um interno', 'warning'); return; }
        if (!dados.descricao)  { toast('Descrição é obrigatória', 'warning'); return; }
        if (!dados.setor_destino) { toast('Setor destino é obrigatório', 'warning'); return; }

        $.ajax({
            url: API + '?acao=salvar_card',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dados),
            dataType: 'json'
        }).done(function (r) {
            if (r.success) {
                $('#modalCard').modal('hide');
                toast(r.message || 'Solicitação salva', 'success');
                carregarKanban();
            } else {
                toast(r.message || 'Erro ao salvar', 'danger');
            }
        }).fail(function () { toast('Falha na conexão', 'danger'); });
    }

    function confirmarDeletar() {
        if (!cardSelecionado) return;
        Swal.fire({
            title: 'Excluir solicitação?',
            html: `<strong>#${cardSelecionado.id}</strong> — ${esc(cardSelecionado.nome_interno)}<br><small class="text-muted">${esc(truncar(cardSelecionado.descricao, 80))}</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
        }).then(function (res) {
            if (!res.isConfirmed) return;
            $.ajax({
                url: API + '?acao=deletar_card',
                method: 'DELETE',
                contentType: 'application/json',
                data: JSON.stringify({ id: cardSelecionado.id }),
                dataType: 'json'
            }).done(function (r) {
                if (r.success) {
                    $('#modalCard').modal('hide');
                    toast('Solicitação excluída', 'success');
                    carregarKanban();
                } else {
                    toast(r.message || 'Erro ao excluir', 'danger');
                }
            }).fail(function () { toast('Falha na conexão', 'danger'); });
        });
    }


    /* ================================================================
       MODAL CRIAÇÃO RÁPIDA
    ================================================================ */
    function abrirRapido(status) {
        $('#formRapido')[0].reset();
        $('#rapidoStatus').val(status);
        $('#rapidoInternoId, #rapidoInternoNome, #rapidoInternoIPEN').val('');
        $('#modalRapido').modal('show');
    }

    function criarRapido() {
        const dados = {
            id_interno:    $('#rapidoInternoId').val(),
            ipen:          $('#rapidoInternoIPEN').val(),
            nome_interno:  $('#rapidoInternoNome').val(),
            descricao:     $('#rapidoDescricao').val().trim(),
            setor_destino: $('#rapidoSetor').val(),
            categoria:     $('#rapidoCategoria').val(),
            status:        $('#rapidoStatus').val(),
        };

        if (!dados.descricao)     { toast('É obrigatório descrever a solicitação!', 'warning'); return; }
        if (!dados.setor_destino) { toast('Setor é obrigatório!', 'warning'); return; }

        $.ajax({
            url: API + '?acao=criar_rapido',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(dados),
            dataType: 'json'
        }).done(function (r) {
            if (r.success) {
                $('#modalRapido').modal('hide');
                toast('Solicitação criada', 'success');
                carregarKanban();
            } else {
                toast(r.message || 'Erro ao criar', 'danger');
            }
        }).fail(function () { toast('Falha na conexão', 'danger'); });
    }

    /* ================================================================
       AUTOCOMPLETE INTERNO
    ================================================================ */
    function buscaInterno(termo, containerId, isRapido) {
        const $c = $('#' + containerId);
        if (termo.length < 2) { $c.hide(); return; }

        $.get(API + '?acao=buscar_interno&termo=' + encodeURIComponent(termo)).done(function (r) {
            if (!r.success || !r.dados.length) { $c.hide(); return; }
            const itens = r.dados.map(function (p) {
                return `<button type="button"
                    data-ipen="${p.ipen}"
                    data-nome="${esc(p.nome)}"
                    data-social="${esc(p.nome_social || '')}"
                    data-galeria="${esc(p.galeria || '')}"
                    data-bloco="${esc(p.bloco || '')}"
                    data-res="${esc(p.res || '')}">
                    <strong>${p.ipen}</strong> — ${esc(p.nome_social || p.nome)}
                    <small>${p.galeria || ''}${p.bloco || ''}-${p.res || ''}</small>
                </button>`;
            }).join('');
            $c.html(itens).show();

            $c.find('button').on('click', function () {
                const $b = $(this);
                const d  = {
                    ipen: $b.data('ipen'), nome: $b.data('nome'),
                    nome_social: $b.data('social'), galeria: $b.data('galeria'),
                    bloco: $b.data('bloco'), res: $b.data('res')
                };
                if (isRapido) {
                    $('#rapidoInterno').val(d.ipen + ' — ' + (d.nome_social || d.nome));
                    $('#rapidoInternoId').val(d.ipen);
                    $('#rapidoInternoNome').val(d.nome);
                    $('#rapidoInternoIPEN').val(d.ipen);
                } else {
                    internoSel = d;
                    $('#buscaInterno').val(d.ipen + ' — ' + (d.nome_social || d.nome));
                    $('#internoId').val(d.ipen);
                    $('#infoInterno').text(d.nome + (d.galeria ? ' | ' + d.galeria : '')+(d.bloco ? '' + d.bloco : '') + '-' + (d.res ? + d.res : '' ))  .removeClass('d-none');
                }
                $c.hide();
            });
        }).fail(function () { $c.hide(); });
    }


    /* ================================================================
       TAREFAS
    ================================================================ */
    function renderizarTarefas(tarefas) {
        const $c = $('#listaTarefas').empty();
        if (!tarefas.length) { $c.html('<p class="text-muted text-sm">Nenhuma tarefa.</p>'); return; }
        tarefas.forEach(function (t) { $c.append(criarItemTarefa(t.texto, t.concluida)); });
    }

    function criarItemTarefa(texto, concluida) {
        const $t = $(`
            <div class="tarefa-item${concluida ? ' concluida' : ''}">
                <input type="checkbox" ${concluida ? 'checked' : ''}>
                <input type="text" value="${esc(texto)}">
                <button type="button" class="btn btn-sm btn-link text-danger p-0">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `);
        $t.find('input[type=checkbox]').on('change', function () {
            $(this).closest('.tarefa-item').toggleClass('concluida');
        });
        $t.find('button').on('click', function () { $(this).closest('.tarefa-item').remove(); });
        return $t;
    }

    function adicionarTarefa() {
        const v = $('#novaTarefa').val().trim();
        if (!v) return;
        $('#listaTarefas').append(criarItemTarefa(v, false));
        $('#novaTarefa').val('');
    }

    function coletarTarefas() {
        const t = [];
        $('#listaTarefas .tarefa-item').each(function () {
            t.push({
                texto:    $(this).find('input[type=text]').val(),
                concluida: $(this).find('input[type=checkbox]').prop('checked')
            });
        });
        return t;
    }

    /* ================================================================
       HISTÓRICO / COMENTÁRIOS
    ================================================================ */
    function renderizarHistorico(log) {
        const $c = $('#listaHistorico').empty();
        if (!log.length) { $c.html('<p class="text-muted text-sm">Nenhum registro.</p>'); return; }
        log.forEach(function (item) {
            $c.append(`
                <div class="historico-item">
                    <span class="badge badge-secondary mr-1">${esc(item.acao)}</span>
                    <small class="text-muted">${esc(item.usuario || '')} — ${esc(item.criado_em || '')}</small>
                    <p class="mb-0 mt-1">${esc(item.descricao)}</p>
                </div>
            `);
        });
    }

    function adicionarComentario() {
        const v = $('#novoComentario').val().trim();
        if (!v) { toast('Digite um comentário', 'warning'); return; }
        const id = $('#cardId').val();
        if (!id) { toast('Salve a solicitação antes de comentar', 'warning'); return; }

        $.ajax({
            url: API + '?acao=salvar_resposta',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id, resposta: v }),
            dataType: 'json'
        }).done(function (r) {
            if (r.success) {
                $('#novoComentario').val('');
                toast('Comentário adicionado', 'success');
                abrirDetalhes(id); // recarrega histórico
            } else {
                toast(r.message || 'Erro', 'danger');
            }
        }).fail(function () { toast('Falha na conexão', 'danger'); });
    }


    /* ================================================================
       FILTROS
    ================================================================ */
    function aplicarFiltros() {
        filtros = {
            termo:          $('#filtroTermo').val().trim(),
            setor:          $('#filtroSetor').val(),
            categoria:      $('#filtroCategoria').val(),
            ver_canceladas: $('#filtroVerCanceladas').is(':checked') ? '1' : '0',
        };
        carregarKanban();
    }

    /* ================================================================
       CATEGORIAS (CRUD no modal)
    ================================================================ */
    function abrirModalCategorias() {
        renderizarTabelaCategorias();
        $('#modalCategorias').modal('show');
    }

    function renderizarTabelaCategorias() {
        const $tb = $('#listaCategorias');
        if (!categorias.length) {
            $tb.html('<tr><td colspan="3" class="text-center text-muted">Nenhuma categoria</td></tr>');
            return;
        }
        $tb.html(categorias.map(function (c) {
            return `<tr>
                <td>
                    <span class="cat-cor" style="background:${esc(c.cor)}"></span>
                    ${esc(c.name)}
                </td>
                <td><code>${esc(c.cor)}</code></td>
                <td class="text-right">
                    <button class="btn btn-xs btn-outline-secondary btn-edit-cat" data-id="${c.id}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-xs btn-outline-danger btn-del-cat" data-id="${c.id}" data-nome="${esc(c.name)}" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>`;
        }).join(''));

        $tb.find('.btn-edit-cat').on('click', function () {
            const id  = $(this).data('id');
            const cat = categorias.find(c => c.id === id);
            if (!cat) return;
            $('#catId').val(cat.id);
            $('#catNome').val(cat.name);
            $('#catCor').val(cat.cor);
            $('#btnCancelarCat').removeClass('d-none');
        });

        $tb.find('.btn-del-cat').on('click', function () {
            const id   = $(this).data('id');
            const nome = $(this).data('nome');
            Swal.fire({
                title: 'Excluir categoria?',
                html: `<strong>${esc(nome)}</strong>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
            }).then(function (res) {
                if (!res.isConfirmed) return;
                $.ajax({
                    url: API + '?acao=deletar_categoria',
                    method: 'DELETE',
                    contentType: 'application/json',
                    data: JSON.stringify({ id: id }),
                    dataType: 'json'
                }).done(function (r) {
                    if (r.success) {
                        toast('Categoria excluída', 'success');
                        recarregarCategorias();
                    } else {
                        toast(r.message || 'Erro ao excluir', 'danger');
                    }
                }).fail(function () { toast('Falha na conexão', 'danger'); });
            });
        });
    }

    function salvarCategoria() {
        const id   = $('#catId').val();
        const nome = $('#catNome').val().trim();
        const cor  = $('#catCor').val();

        if (!nome) { toast('Nome é obrigatório', 'warning'); return; }

        const acao = id ? 'atualizar_categoria' : 'criar_categoria';
        const body = id ? { id: parseInt(id), nome, cor } : { nome, cor };

        $.ajax({
            url: API + '?acao=' + acao,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(body),
            dataType: 'json'
        }).done(function (r) {
            if (r.success) {
                toast(id ? 'Categoria atualizada' : 'Categoria criada', 'success');
                cancelarEdicaoCategoria();
                recarregarCategorias();
            } else {
                toast(r.message || 'Erro ao salvar', 'danger');
            }
        }).fail(function () { toast('Falha na conexão', 'danger'); });
    }

    function cancelarEdicaoCategoria() {
        $('#catId').val('');
        $('#catNome').val('');
        $('#catCor').val('#6c757d');
        $('#btnCancelarCat').addClass('d-none');
    }

    function recarregarCategorias() {
        $.get(API + '?acao=buscar_categorias').done(function (r) {
            if (!r.success) return;
            categorias = r.dados;
            preencherSelectsCategorias();
            renderizarTabelaCategorias();
        });
    }


    /* ================================================================
       TOAST (Bootstrap alert flutuante simples)
    ================================================================ */
    function toast(msg, tipo) {
        // tipo: success | danger | warning | info
        const iconMap = {
            success: 'fa-check-circle',
            danger:  'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info:    'fa-info-circle',
        };
        const icon = iconMap[tipo] || 'fa-info-circle';

        if (!document.getElementById('toastContainer')) {
            $('body').append('<div id="toastContainer"></div>');
        }

        const $t = $(`
            <div class="alert alert-${tipo} alert-dismissible shadow-sm mb-0 py-2 px-3"
                 role="alert" style="min-width:260px;max-width:380px;font-size:.875rem">
                <i class="fas ${icon} mr-2"></i>${esc(msg)}
                <button type="button" class="close py-1 px-2" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);

        $('#toastContainer').append($t);
        setTimeout(function () { $t.alert('close'); }, 4000);
    }

    /* ================================================================
       UTILITÁRIOS
    ================================================================ */
    function esc(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function truncar(str, max) {
        if (!str) return '';
        return str.length > max ? str.substring(0, max) + '…' : str;
    }

}(jQuery));
