<?php
// SolicitacoesView - Renderiza a interface HTML do Kanban
// O Controller só é carregado pelo index.php para requisições de API
?>

<script>
    window.pageTitle = 'Solicitações - Kanban';
    window.currentPage = 'solicitacoes';
</script>

<link rel="stylesheet" href="modulos/coordenacao/solicitacoes/assets/css/solicitacoes.css">

<section class="content pt-3">
    <div class="container-fluid">
        <!-- Cabeçalho do Kanban -->
        <div class="row mb-3">
            <div class="col-md-8">
                <h1 class="m-0 text-primary"><i class="fas fa-columns mr-2"></i>Painel de Solicitações</h1>
                <p class="text-muted text-sm mb-0">Arraste e solte os cards para gerenciar o status das solicitações</p>
            </div>
            <div class="col-md-4 text-md-right mt-2 mt-md-0">
                <div class="btn-group w-100">
                    <button id="btnCriarCard" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Nova Solicitação
                    </button>
                    <button id="btnFiltrarKanban" class="btn btn-default">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    <button id="btnRecarregarKanban" class="btn btn-default">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Área de mensagens -->
        <div id="kanbanMensagens"></div>

        <!-- Painel de Filtros (colapsável) -->
        <div id="painelFiltros" class="card card-outline card-secondary mb-3 d-none">
            <div class="card-body">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label for="filtroTermo" class="text-sm">Buscar por interno ou descrição</label>
                        <input id="filtroTermo" type="text" class="form-control form-control-sm" placeholder="IPEN, nome, descrição...">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="filtroSetor" class="text-sm">Setor</label>
                        <select id="filtroSetor" class="form-control form-control-sm">
                            <option value="">Todos os setores</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="filtroCategoria" class="text-sm">Categoria</label>
                        <select id="filtroCategoria" class="form-control form-control-sm">
                            <option value="">Todas categorias</option>
                            <option value="Saúde">Saúde</option>
                            <option value="Jurídico">Jurídico</option>
                            <option value="Social">Social</option>
                            <option value="Educação">Educação</option>
                            <option value="Trabalho">Trabalho</option>
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label for="filtroPrioridade" class="text-sm">Prioridade</label>
                        <select id="filtroPrioridade" class="form-control form-control-sm">
                            <option value="">Todas prioridades</option>
                            <option value="Urgente">Urgente</option>
                            <option value="Alta">Alta</option>
                            <option value="Média">Média</option>
                            <option value="Baixa">Baixa</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button id="btnAplicarFiltros" class="btn btn-primary btn-block">
                            <i class="fas fa-search mr-1"></i> Aplicar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kanban Board -->
        <div id="kanbanBoard" class="kanban-board">
            <div class="kanban-loading text-center py-5">
                <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                <p class="text-muted mt-2">Carregando solicitações...</p>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Criação/Edição de Card -->
<div class="modal fade" id="modalCard" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalCardTitle">
                    <i class="fas fa-plus-circle mr-2"></i>Nova Solicitação
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formCard">
                <input type="hidden" id="cardId" name="id">
                <div class="modal-body">
                    <!-- Abas do Modal -->
                    <ul class="nav nav-tabs mb-3" id="cardTabs" role="tablist">
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
                                <!-- Seleção de Interno -->
                                <div class="col-lg-12 mb-3">
                                    <label for="buscaInterno" class="text-sm font-weight-bold">
                                        <i class="fas fa-user mr-1"></i>Buscar Interno
                                    </label>
                                    <div class="input-group">
                                        <input id="buscaInterno" type="text" class="form-control" 
                                               placeholder="Digite IPEN, nome ou nome social..." autocomplete="off">
                                        <div class="input-group-append">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        </div>
                                    </div>
                                    <div id="sugestoesInterno" class="bg-white shadow-sm mt-1" style="display:none;"></div>
                                    <input type="hidden" id="internoId" name="id_interno">
                                    <div id="infoInternoSelecionado" class="alert alert-info mt-2 d-none">
                                        <small><i class="fas fa-user-check mr-1"></i>
                                        <span id="textoInfoInterno">Nenhum interno selecionado</span></small>
                                    </div>
                                </div>

                                <!-- Setor e Status -->
                                <div class="col-md-6 mb-3">
                                    <label for="cardSetor" class="text-sm font-weight-bold">
                                        <i class="fas fa-building mr-1"></i>Setor Destino
                                    </label>
                                    <select id="cardSetor" name="setor_destino" class="form-control" required>
                                        <option value="">Selecione...</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cardStatus" class="text-sm font-weight-bold">
                                        <i class="fas fa-flag mr-1"></i>Status
                                    </label>
                                    <select id="cardStatus" name="status" class="form-control">
                                        <option value="Pendentes">Pendentes</option>
                                        <option value="Em Atendimento">Em Atendimento</option>
                                        <option value="Aguardando">Aguardando</option>
                                        <option value="Atendidas">Atendidas</option>
                                        <option value="Canceladas">Canceladas</option>
                                    </select>
                                </div>

                                <!-- Categoria e Prioridade -->
                                <div class="col-md-6 mb-3">
                                    <label for="cardCategoria" class="text-sm font-weight-bold">
                                        <i class="fas fa-tag mr-1"></i>Categoria
                                    </label>
                                    <select id="cardCategoria" name="categoria" class="form-control">
                                        <option value="">Selecione...</option>
                                        <option value="Saúde">Saúde</option>
                                        <option value="Jurídico">Jurídico</option>
                                        <option value="Social">Social</option>
                                        <option value="Educação">Educação</option>
                                        <option value="Trabalho">Trabalho</option>
                                        <option value="Outros">Outros</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cardPrioridade" class="text-sm font-weight-bold">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>Prioridade
                                    </label>
                                    <select id="cardPrioridade" name="prioridade" class="form-control">
                                        <option value="Baixa">Baixa</option>
                                        <option value="Média" selected>Média</option>
                                        <option value="Alta">Alta</option>
                                        <option value="Urgente">Urgente</option>
                                    </select>
                                </div>

                                <!-- Data Limite -->
                                <div class="col-md-6 mb-3">
                                    <label for="cardDataLimite" class="text-sm font-weight-bold">
                                        <i class="fas fa-calendar-alt mr-1"></i>Data Limite
                                    </label>
                                    <input id="cardDataLimite" type="date" name="data_limite" class="form-control">
                                </div>

                                <!-- Responsável -->
                                <div class="col-md-6 mb-3">
                                    <label for="cardResponsavel" class="text-sm font-weight-bold">
                                        <i class="fas fa-user-tie mr-1"></i>Responsável
                                    </label>
                                    <input id="cardResponsavel" type="text" name="responsavel_nome" class="form-control" 
                                           placeholder="Nome do responsável">
                                </div>

                                <!-- Descrição -->
                                <div class="col-lg-12 mb-3">
                                    <label for="cardDescricao" class="text-sm font-weight-bold">
                                        <i class="fas fa-align-left mr-1"></i>Descrição da Solicitação
                                    </label>
                                    <textarea id="cardDescricao" name="descricao" class="form-control" rows="4" 
                                              placeholder="Descreva detalhadamente a solicitação..." required></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Aba Tarefas -->
                        <div class="tab-pane fade" id="tabTarefas">
                            <div class="mb-3">
                                <h6 class="font-weight-bold mb-2">Tarefas do Card</h6>
                                <p class="text-muted text-sm mb-3">Adicione tarefas para acompanhar atividades internas da solicitação.</p>
                                <div id="listaTarefas" class="mb-3"></div>
                                <div class="input-group">
                                    <input id="novaTarefa" type="text" class="form-control" placeholder="Nova tarefa...">
                                    <div class="input-group-append">
                                        <button type="button" id="btnAdicionarTarefa" class="btn btn-outline-primary">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Aba Histórico -->
                        <div class="tab-pane fade" id="tabHistorico">
                            <div id="listaHistorico" class="mb-3">
                                <p class="text-muted text-sm">Selecione uma solicitação para ver o histórico.</p>
                            </div>
                            <div class="border-top pt-3">
                                <label class="text-sm font-weight-bold">Adicionar Comentário</label>
                                <textarea id="novoComentario" class="form-control mb-2" rows="3" 
                                          placeholder="Digite um comentário ou observação..."></textarea>
                                <button type="button" id="btnAdicionarComentario" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-comment mr-1"></i>Adicionar Comentário
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Cancelar
                    </button>
                    <button type="button" id="btnDeletarCard" class="btn btn-danger d-none">
                        <i class="fas fa-trash mr-1"></i>Excluir
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Criação Rápida (por coluna) -->
<div class="modal fade" id="modalCriacaoRapida" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-bolt mr-2"></i>Criação Rápida
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formCriacaoRapida">
                <input type="hidden" id="rapidoStatus" name="status">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rapidoInterno" class="font-weight-bold">Interno</label>
                        <input id="rapidoInterno" type="text" class="form-control" 
                               placeholder="Digite IPEN ou nome..." autocomplete="off">
                        <div id="sugestoesRapido" class="bg-white shadow-sm mt-1" style="display:none;"></div>
                        <input type="hidden" id="rapidoInternoId">
                        <input type="hidden" id="rapidoInternoNome">
                        <input type="hidden" id="rapidoInternoIPEN">
                    </div>
                    <div class="form-group">
                        <label for="rapidoDescricao" class="font-weight-bold">Descrição</label>
                        <textarea id="rapidoDescricao" name="descricao" class="form-control" rows="3" 
                                  placeholder="Descrição rápida da solicitação..." required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rapidoSetor">Setor</label>
                                <select id="rapidoSetor" name="setor_destino" class="form-control" required>
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="rapidoPrioridade">Prioridade</label>
                                <select id="rapidoPrioridade" name="prioridade" class="form-control">
                                    <option value="Média" selected>Média</option>
                                    <option value="Alta">Alta</option>
                                    <option value="Urgente">Urgente</option>
                                    <option value="Baixa">Baixa</option>
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

<script src="modulos/coordenacao/solicitacoes/assets/js/solicitacoes.js"></script>
