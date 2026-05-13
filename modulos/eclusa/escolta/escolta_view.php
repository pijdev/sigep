<?php
require_once __DIR__ . '/escolta_logica.php';
?>

<script>
    window.currentPage = 'escolta_view.php';
    window.pageTitle = 'Gestão de Escoltas - Eclusa';
</script>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/eclusa/escolta/assets/css/escolta.css?v=<?= time() ?>">

<!-- Main content -->
<section class="content pt-3">
    <div class="container-fluid">
        <!-- Header com ações -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h4 class="m-0 text-dark">
            </h4>
            <div class="btn-group mt-2 mt-md-0">
                <button class="btn btn-success" id="btnNovaMovimentacao">
                    <i class="fas fa-plus mr-1"></i>Nova Escolta
                </button>
                <button class="btn btn-primary" id="btnAbrirRelatorio">
                    <i class="fas fa-file-alt mr-1"></i>Relatório
                </button>
                <button class="btn btn-info" id="btnAbrirDashboard" onclick="window.open('/dashboard/eclusa_escolta', '_blank')">
                    <i class="fas fa-chart-line mr-1"></i>Dashboard
                </button>
                <button class="btn btn-outline-secondary" id="btnAtualizarTudo">
                    <i class="fas fa-sync-alt mr-1"></i>Atualizar
                </button>
            </div>
        </div>

        <!-- Cards Estatísticos -->
        <div class="row">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="info-box kpi-card" data-kpi="total">
                    <span class="info-box-icon bg-info"><i class="fas fa-exchange-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Escoltas</span>
                        <span class="info-box-number" id="kpiTotal"><?= (int) $viewData['contadores']['total'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="info-box kpi-card" data-kpi="hoje">
                    <span class="info-box-icon bg-primary"><i class="fas fa-calendar-day"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Escoltas Hoje</span>
                        <span class="info-box-number" id="kpiHoje"><?= (int) $viewData['contadores']['escoltasHoje'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="info-box kpi-card" data-kpi="entradas">
                    <span class="info-box-icon bg-success"><i class="fas fa-arrow-right"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Finalizadas Hoje</span>
                        <span class="info-box-number" id="kpiEntradas"><?= (int) $viewData['contadores']['finalizadasHoje'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="info-box kpi-card" data-kpi="saidas">
                    <span class="info-box-icon bg-danger"><i class="fas fa-arrow-left"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pendentes Hoje</span>
                        <span class="info-box-number" id="kpiSaidas"><?= (int) $viewData['contadores']['pendentesHoje'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="info-box kpi-card" data-kpi="veiculos">
                    <span class="info-box-icon bg-warning"><i class="fas fa-truck"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Top Destinos</span>
                        <span class="info-box-number" id="kpiVeiculos"><?= count($viewData['top_destinos']) ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="info-box kpi-card" data-kpi="empresas">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-building"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Top Motoristas</span>
                        <span class="info-box-number" id="kpiEmpresas"><?= count($viewData['top_motoristas']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Filtros -->
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filtros</h3>
            </div>
            <div class="card-body pb-2">
                <form id="filtrosMovForm" class="row">
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="placa" id="filtro_placa">
                            <option value="">Todas as placas</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="destino" id="filtro_destino">
                            <option value="">Todos os destinos</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="interno" id="filtro_interno">
                            <option value="">Todos os internos</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="motorista" id="filtro_motorista">
                            <option value="">Todos os motoristas</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-control" name="status" id="filtro_status">
                            <option value="">Todos os status</option>
                            <option value="Pendente">Pendente</option>
                            <option value="Finalizado">Finalizado</option>
                            <option value="Cancelado">Cancelado</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="text" class="form-control" name="search" placeholder="Busca geral">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="date" class="form-control" name="data_inicio">
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="date" class="form-control" name="data_fim">
                    </div>
                    <div class="col-md-8 mb-2 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search mr-1"></i>Filtrar</button>
                        <button type="button" class="btn btn-outline-secondary" id="btnLimparFiltros"><i class="fas fa-eraser mr-1"></i>Limpar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabela Principal -->
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i>Escoltas</h3>
                <div class="d-flex align-items-center">
                    <span id="movMeta" class="small text-muted"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-eclusa mb-0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Interno</th>
                                <th>Destino</th>
                                <th>Motorista</th>
                                <th>Placa</th>
                                <th>Status</th>
                                <th>H. Prevista</th>
                                <th>H. Chegada</th>
                                <th>H. Retorno</th>
                                <th>NOT</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody id="movTableBody">
                            <tr>
                                <td colspan="11" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>Carregando...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <button class="btn btn-sm btn-outline-secondary" id="movPrev"><i class="fas fa-chevron-left mr-1"></i>Anterior</button>
                <span class="small text-muted" id="movPageInfo"></span>
                <button class="btn btn-sm btn-outline-secondary" id="movNext">Próxima<i class="fas fa-chevron-right ml-1"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- Offcanvas Nova Movimentação -->
<div class="offcanvas-eclusa" id="offcanvasMovimentacao">
    <div class="offcanvas-eclusa-header">
        <h5 class="mb-0" id="tituloMovForm"><i class="fas fa-shield-alt mr-2 text-primary"></i>Nova Escolta</h5>
        <button type="button" class="btn btn-light btn-sm" data-offcanvas-close>&times;</button>
    </div>
    <div class="offcanvas-eclusa-body">
        <form id="movimentacaoForm">
            <input type="hidden" name="db_action" value="salvar">
            <input type="hidden" name="id" id="mov_id">
            <input type="hidden" name="veiculo_id" id="veiculo_id">
            <input type="hidden" name="empresa_id" id="empresa_id">
            <input type="hidden" name="motorista_id" id="motorista_id">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Data da Escolta *</label>
                    <input type="date" class="form-control" name="data_cadastro" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Cadastrado por</label>
                    <input type="text" class="form-control" name="cadastrado_por" placeholder="Usuário responsável">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Interno *</label>
                    <div class="busca-inteligente">
                        <input type="text" class="form-control" name="interno" id="interno" data-campo="interno" placeholder="Ex: 687556 - PAULO CÉZAR PEREIRA LIMA" required>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Destino *</label>
                    <div class="input-group">
                        <div class="busca-inteligente flex-grow-1">
                            <input type="text" class="form-control" name="destino" id="destino" data-campo="destino" placeholder="Ex: Hospital Regional, Fórum, IGP, Delegacia..." required>
                        </div>
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-map-marker-alt"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" style="max-height: 300px; overflow-y: auto;">
                                <h6 class="dropdown-header"><i class="fas fa-hospital mr-2"></i>Hospitais</h6>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Hospital Regional de São José">Hospital Regional de São José</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Hospital Bethesda">Hospital Bethesda</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Hospital Regional Hans Dieter Schimidt">Hospital Regional Hans Dieter Schimidt</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="UPA 24h">UPA 24h</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Pronto Socorro">Pronto Socorro</a>

                                <h6 class="dropdown-header"><i class="fas fa-gavel mr-2"></i>Fórum/Justiça</h6>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Fórum Criminal">Fórum Criminal</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Vara de Execução Criminal">Vara de Execução Criminal</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Cartório Criminal">Cartório Criminal</a>

                                <h6 class="dropdown-header"><i class="fas fa-user-md mr-2"></i>Médicos</h6>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Consulta Médica - Dr. XXX">Consulta Médica - Dr. XXX</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Dentista - Dra. XXX">Dentista - Dra. XXX</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Psicólogo - Dr. XXX">Psicólogo - Dr. XXX</a>

                                <h6 class="dropdown-header"><i class="fas fa-briefcase mr-2"></i>Advogados</h6>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Escritório Dr. XXX">Escritório Dr. XXX</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Defensoria Pública">Defensoria Pública</a>

                                <h6 class="dropdown-header"><i class="fas fa-building mr-2"></i>Outros</h6>
                                <a class="dropdown-item destino-comum" href="#" data-destino="Delegacia">Delegacia</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="IML">IML</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="CREAS">CREAS</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="CRAS">CRAS</a>
                                <a class="dropdown-item destino-comum" href="#" data-destino="IGP">IGP</a>

                                <div class="dropdown-divider"></div>
                                <h6 class="dropdown-header text-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Evite usar:</h6>
                                <a class="dropdown-item text-muted small" href="#" style="pointer-events: none;">❌ CONSULTA EXTERNA</a>
                                <a class="dropdown-item text-muted small" href="#" style="pointer-events: none;">❌ CONSULTA</a>
                                <a class="dropdown-item text-muted small" href="#" style="pointer-events: none;">❌ EXTERNO</a>
                                <a class="dropdown-item text-muted small" href="#" style="pointer-events: none;">❌ SAÍDA</a>
                            </div>
                        </div>
                    </div>
                    <small class="text-muted">Seja específico: "Hospital Regional Hans Dieter Schimidt" em vez de "CONSULTA EXTERNA"</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-control" name="status">
                        <option value="Pendente">Pendente</option>
                        <option value="Finalizado">Finalizado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Hora Prevista</label>
                    <input type="time" class="form-control" name="hora_prevista">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Hora de Chegada</label>
                    <input type="time" class="form-control" name="hora_chegada">
                    <small class="text-muted">Horário em que a polícia chegou para buscar o interno</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Hora de Retorno</label>
                    <input type="time" class="form-control" name="hora_retorno">
                    <small class="text-muted">Horário em que a escolta foi concluída</small>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Motivo</label>
                    <input type="text" class="form-control" name="motivo" placeholder="Motivo da escolta">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Placa</label>
                    <div class="plate-container">
                        <div class="busca-inteligente">
                            <input type="text" class="form-control plate-input" name="placa" id="placa" data-campo="placa" placeholder="ABC1C34">
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Motorista</label>
                    <div class="busca-inteligente">
                        <input type="text" class="form-control" name="motorista" id="motorista" data-campo="motorista" placeholder="Nome do motorista">
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">É NOT?</label>
                    <select class="form-control" name="eh_not">
                        <option value="Não">Não</option>
                        <option value="Sim">Sim</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2" data-offcanvas-close>Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Salvar</button>
            </div>
        </form>
    </div>
</div>

<!-- Offcanvas Relatório -->
<div class="offcanvas-eclusa" id="offcanvasRelatorio">
    <div class="offcanvas-eclusa-header">
        <h5 class="mb-0">Gerar Relatório</h5>
        <button type="button" class="btn btn-light btn-sm" data-offcanvas-close>&times;</button>
    </div>
    <div class="offcanvas-eclusa-body">
        <form id="relatorioForm" class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Data Início</label>
                <input type="date" class="form-control" name="data_inicio">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Data Fim</label>
                <input type="date" class="form-control" name="data_fim">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Placa</label>
                <input type="text" class="form-control" name="placa" placeholder="ABC-1234">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Destino</label>
                <input type="text" class="form-control" name="destino" placeholder="Local de destino">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Interno</label>
                <input type="text" class="form-control" name="interno" placeholder="Nome ou IPEN do interno">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Motorista</label>
                <input type="text" class="form-control" name="motorista" placeholder="Motorista">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Status</label>
                <select class="form-control" name="status">
                    <option value="">Todos</option>
                    <option value="Pendente">Pendente</option>
                    <option value="Finalizado">Finalizado</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2" data-offcanvas-close>Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGerarRelatorio"><i class="fas fa-file-pdf mr-1"></i>Gerar</button>
            </div>
        </form>
    </div>
</div>

<!-- Offcanvas Registrar Chegada -->
<div class="offcanvas-eclusa" id="offcanvasChegada">
    <div class="offcanvas-eclusa-header">
        <h5 class="mb-0">Registrar Chegada da Polícia</h5>
        <button type="button" class="btn btn-light btn-sm" data-offcanvas-close>&times;</button>
    </div>
    <div class="offcanvas-eclusa-body">
        <form id="chegadaForm">
            <input type="hidden" name="db_action" value="registrar_chegada">
            <input type="hidden" name="id" id="chegada_id">

            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">Hora de Chegada da Polícia *</label>
                    <input type="time" class="form-control" name="hora_chegada" id="chegada_hora" required>
                    <small class="text-muted">Horário em que a polícia chegou para buscar o interno</small>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Informação:</strong> Após registrar a chegada, a escolta continuará em andamento.
                A finalização será feita posteriormente com a hora de retorno.
            </div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2" data-offcanvas-close>Cancelar</button>
                <button type="submit" class="btn btn-info">
                    <i class="fas fa-clock mr-1"></i>Registrar Chegada
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Offcanvas Finalizar Escolta -->
<div class="offcanvas-eclusa" id="offcanvasFinalizar">
    <div class="offcanvas-eclusa-header">
        <h5 class="mb-0">Finalizar Escolta</h5>
        <button type="button" class="btn btn-light btn-sm" data-offcanvas-close>&times;</button>
    </div>
    <div class="offcanvas-eclusa-body">
        <form id="finalizarForm">
            <input type="hidden" name="db_action" value="finalizar">
            <input type="hidden" name="id" id="finalizar_id">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status da Finalização *</label>
                    <select class="form-control" name="status_finalizacao" id="status_finalizacao" required>
                        <option value="">Selecione...</option>
                        <option value="Finalizado">Finalizado com Sucesso</option>
                        <option value="Pendente">Pendente</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Hora de Retorno *</label>
                    <input type="time" class="form-control" name="hora_retorno" id="hora_retorno" required>
                    <small class="text-muted">Horário em que a escolta foi concluída</small>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label">Motivo <small class="text-muted">(obrigatório se status ≠ Finalizado)</small></label>
                    <textarea class="form-control" name="motivo_finalizacao" id="motivo_finalizacao" rows="3" placeholder="Descreva o motivo..."></textarea>
                </div>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Atenção:</strong> Se a escolta for finalizada com sucesso, o motivo não é obrigatório.
                Para status "Pendente" ou "Cancelado", o motivo é obrigatório.
            </div>

            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary mr-2" data-offcanvas-close>Cancelar</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check mr-1"></i>Finalizar Escolta
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Offcanvas KPI Detalhes -->
<div class="offcanvas-eclusa" id="offcanvasKpiDetalhes">
    <div class="offcanvas-eclusa-header">
        <h5 class="mb-0" id="kpiDetalhesTitulo">Detalhes</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnImprimirKpi">
                <i class="fas fa-print mr-1"></i>Imprimir
            </button>
            <button type="button" class="btn btn-light btn-sm" data-offcanvas-close>&times;</button>
        </div>
    </div>
    <div class="offcanvas-eclusa-body" id="kpiDetalhesConteudo"></div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluirEscolta" tabindex="-1" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-info-circle mr-2"></i>
                    <strong>Atenção:</strong> Esta ação não poderá ser desfeita!
                </div>

                <p class="mb-3">Tem certeza que deseja excluir a escolta <strong id="escoltaNumero">#0000</strong>?</p>

                <div class="form-group">
                    <label for="nomeConfirmacao" class="form-label">
                        <i class="fas fa-user-shield mr-1"></i>
                        Confirme seu nome para prosseguir:
                        <small class="text-muted ml-2">(<span id='nomeEsperado'>NOME ESPERADO</span>)</small>
                    </label>
                    <input type="text" class="form-control" id="nomeConfirmacao"
                        placeholder="Digite o nome de quem cadastrou" required>
                    <small class="text-muted">
                        O nome deve ser igual ao de quem cadastrou a escolta para permitir a exclusão.
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">
                    <i class="fas fa-trash mr-1"></i>Excluir Escolta
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
        <div>Processando...</div>
    </div>
</div>

<!-- JavaScript específico do módulo -->
<script src="/modulos/eclusa/escolta/assets/js/escolta.js?v=<?= time() ?>"></script>
