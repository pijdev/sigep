<?php
// SolicitacoesView - Renderiza a interface do Kanban de Solicitações
?>
<link rel="stylesheet" href="/modulos/coordenacao/solicitacoes/assets/css/app.css?v=<?= time() ?>">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="/node_modules/sortablejs/Sortable.min.js"></script>

<section class="content pt-3">
    <div class="container-fluid">

        <!-- Cabeçalho -->
        <div class="row mb-3 align-items-center">
            <div class="col-md-7">
                <h4>Painel</h4>
            </div>
            <div class="col-md-5 text-md-right mt-2 mt-md-0">
                <button id="btnCriarCard" class="btn btn-primary btn-sm mr-1">
                    <i class="fas fa-plus mr-1"></i>Nova Solicitação
                </button>
                <button id="btnFiltrar" class="btn btn-default btn-sm mr-1">
                    <i class="fas fa-filter mr-1"></i>Filtros
                </button>
                <button id="btnRecarregar" class="btn btn-default btn-sm">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>

        <!-- Painel de Filtros -->
        <div id="painelFiltros" class="card card-outline card-secondary mb-3 d-none">
            <div class="card-body py-2">
                <div class="row align-items-end g-2">
                    <div class="col-md-3">
                        <label class="text-sm mb-1">Buscar</label>
                        <input id="filtroTermo" type="text" class="form-control form-control-sm"
                               placeholder="IPEN, nome, descrição...">
                    </div>
                    <div class="col-md-3">
                        <label class="text-sm mb-1">Setor</label>
                        <select id="filtroSetor" class="form-control form-control-sm">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="text-sm mb-1">Categoria</label>
                        <select id="filtroCategoria" class="form-control form-control-sm">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <div class="custom-control custom-checkbox mt-4">
                            <input type="checkbox" class="custom-control-input" id="filtroVerCanceladas">
                            <label class="custom-control-label text-sm" for="filtroVerCanceladas">Ver Canceladas</label>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button id="btnAplicarFiltros" class="btn btn-primary btn-sm btn-block mt-1">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div id="kanbanBoard">
            <div class="text-center py-5 w-100">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="text-muted mt-2">Carregando...</p>
            </div>
        </div>

    </div>
</section>

<!-- Modal Solicitação -->
<div class="modal fade" id="modalCard" tabindex="-1" role="dialog" aria-labelledby="modalCardTitulo">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCardTitulo">Nova Solicitação</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formCard">
                <input type="hidden" id="cardId" name="id">
                <div class="modal-body">

                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tabPrincipal">
                                <i class="fas fa-info-circle mr-1"></i>Principal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabTarefas">
                                <i class="fas fa-tasks mr-1"></i>Tarefas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tabHistorico">
                                <i class="fas fa-history mr-1"></i>Histórico
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <!-- Aba Principal -->
                        <div class="tab-pane fade show active" id="tabPrincipal">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="text-sm font-weight-bold">
                                        <i class="fas fa-user mr-1"></i>Interno
                                    </label>
                                    <div class="position-relative">
                                        <input id="buscaInterno" type="text" class="form-control"
                                               placeholder="Digite iPEN, Nome ou Nome Social..." autocomplete="off">
                                        <div id="sugestoesInterno" class="sugestoes-lista"></div>
                                    </div>
                                    <input type="hidden" id="internoId" name="id_interno">
                                    <small id="infoInterno" class="text-info d-none"></small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-sm font-weight-bold">
                                        <i class="fas fa-building mr-1"></i>Setor Destino
                                    </label>
                                    <select id="cardSetor" name="setor_destino" class="form-control" required>
                                        <option value="">Selecione...</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-sm font-weight-bold">
                                        <i class="fas fa-flag mr-1"></i>Status
                                    </label>
                                    <select id="cardStatus" name="status" class="form-control">
                                        <option value="Pendentes">Pendente</option>
                                        <option value="Em Atendimento">Em Atendimento</option>
                                        <option value="Aguardando">Aguardando</option>
                                        <option value="Atendidas">Concluído</option>
                                        <option value="Canceladas">Cancelado</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-sm font-weight-bold">
                                        <i class="fas fa-tag mr-1"></i>Categoria
                                    </label>
                                    <div class="input-group">
                                        <select id="cardCategoria" name="categoria" class="form-control">
                                            <option value="">Sem categoria</option>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" id="btnGerenciarCategorias"
                                                    class="btn btn-outline-secondary btn-sm" title="Gerenciar categorias">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="text-sm font-weight-bold">
                                        <i class="fas fa-user-tie mr-1"></i>Responsável
                                    </label>
                                    <input id="cardResponsavel" type="text" name="responsavel_nome"
                                           class="form-control" placeholder="Nome do responsável">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="text-sm font-weight-bold">
                                        <i class="fas fa-align-left mr-1"></i>Descrição
                                    </label>
                                    <textarea id="cardDescricao" name="descricao" class="form-control"
                                              rows="4" placeholder="Descreva a solicitação..." required></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Aba Tarefas -->
                        <div class="tab-pane fade" id="tabTarefas">
                            <div id="listaTarefas" class="mb-3"></div>
                            <div class="input-group">
                                <input id="novaTarefa" type="text" class="form-control form-control-sm"
                                       placeholder="Nova tarefa...">
                                <div class="input-group-append">
                                    <button type="button" id="btnAdicionarTarefa"
                                            class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Aba Histórico -->
                        <div class="tab-pane fade" id="tabHistorico">
                            <div id="listaHistorico" class="mb-3">
                                <p class="text-muted text-sm">Nenhum histórico.</p>
                            </div>
                            <div class="border-top pt-3">
                                <label class="text-sm font-weight-bold">Comentário</label>
                                <textarea id="novoComentario" class="form-control form-control-sm mb-2"
                                          rows="2" placeholder="Adicionar comentário..."></textarea>
                                <button type="button" id="btnAdicionarComentario"
                                        class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-comment mr-1"></i>Enviar
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnDeletarCard" class="btn btn-danger d-none">
                        <i class="fas fa-trash mr-1"></i>Excluir
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Criação Rápida -->
<div class="modal fade" id="modalRapido" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-bolt mr-2"></i>Criação Rápida</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="formRapido">
                <input type="hidden" id="rapidoStatus" name="status">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold">Interno</label>
                        <div class="position-relative">
                            <input id="rapidoInterno" type="text" class="form-control"
                                   placeholder="Digite IPEN ou nome..." autocomplete="off">
                            <div id="sugestoesRapido" class="sugestoes-lista"></div>
                        </div>
                        <input type="hidden" id="rapidoInternoId">
                        <input type="hidden" id="rapidoInternoNome">
                        <input type="hidden" id="rapidoInternoIPEN">
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Descrição</label>
                        <textarea id="rapidoDescricao" class="form-control" rows="3"
                                  placeholder="Descrição da solicitação..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Setor</label>
                                <select id="rapidoSetor" class="form-control" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Categoria</label>
                                <select id="rapidoCategoria" class="form-control">
                                    <option value="">Sem categoria</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus mr-1"></i>Criar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Gerenciar Categorias -->
<div class="modal fade" id="modalCategorias" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tags mr-2"></i>Categorias</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <table class="table table-sm mb-3">
                    <tbody id="listaCategorias">
                        <tr><td colspan="3" class="text-center text-muted">Carregando...</td></tr>
                    </tbody>
                </table>
                <hr>
                <form id="formCategoria">
                    <input type="hidden" id="catId">
                    <div class="input-group">
                        <input type="text" id="catNome" class="form-control form-control-sm"
                               placeholder="Nome da categoria..." maxlength="50" required>
                        <input type="color" id="catCor" class="form-control form-control-color form-control-sm"
                               value="#6c757d" style="max-width:50px">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-save"></i>
                            </button>
                            <button type="button" id="btnCancelarCat" class="btn btn-secondary btn-sm d-none">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="/modulos/coordenacao/solicitacoes/assets/js/app.js?v=<?= time() ?>"></script>
