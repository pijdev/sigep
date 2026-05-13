<?php
require_once __DIR__ . '/job_manager_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info pointer" onclick="verDetalhesCard('total')">
                    <div class="inner">
                        <h3 id="jobs-total">0</h3>
                        <p>Total de Jobs</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tasks"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success pointer" onclick="verDetalhesCard('ativos')">
                    <div class="inner">
                        <h3 id="jobs-ativos">0</h3>
                        <p>Jobs Ativos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning pointer" onclick="verDetalhesCard('executando')">
                    <div class="inner">
                        <h3 id="jobs-executando">0</h3>
                        <p>Executando</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger pointer" onclick="verDetalhesCard('erro')">
                    <div class="inner">
                        <h3 id="jobs-erro">0</h3>
                        <p>Com Erro</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="row mb-3">
            <div class="col-12">
                <button class="btn btn-primary" onclick="abrirModalJob()">
                    <i class="fas fa-plus"></i> Novo Job
                </button>
                <button class="btn btn-info" onclick="atualizarLista()">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
                <button class="btn btn-warning" onclick="executarTodosJobs()">
                    <i class="fas fa-play"></i> Executar Todos
                </button>
                <button class="btn btn-secondary" onclick="pausarTodosJobs()">
                    <i class="fas fa-pause"></i> Pausar Todos
                </button>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select id="filtro-status" class="form-control">
                                    <option value="">Todos os Status</option>
                                    <?php foreach ($viewData['status_job'] as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="filtro-tipo" class="form-control">
                                    <option value="">Todos os Tipos</option>
                                    <?php foreach ($viewData['tipos_job'] as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="filtro-nome" class="form-control" placeholder="Buscar por nome...">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-block btn-outline-primary" onclick="aplicarFiltros()">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Jobs -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Jobs Cadastrados
                </h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tabela-jobs" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Próxima Execução</th>
                                <th>Última Execução</th>
                                <th>Prioridade</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="corpo-tabela-jobs">
                            <!-- Preenchido via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal Job -->
<div class="modal fade" id="modalJob" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-cog"></i>
                    <span id="titulo-modal-job">Novo Job</span>
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-job">
                    <input type="hidden" id="job-id">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="job-nome">Nome do Job <span class="text-danger">*</span></label>
                                <input type="text" id="job-nome" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="job-tipo">Tipo <span class="text-danger">*</span></label>
                                <select id="job-tipo" class="form-control" required>
                                    <?php foreach ($viewData['tipos_job'] as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="job-descricao">Descrição</label>
                        <textarea id="job-descricao" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="job-comando">Comando/Script <span class="text-danger">*</span></label>
                        <textarea id="job-comando" class="form-control" rows="2" required></textarea>
                        <small class="form-text text-muted">Ex: powershell -File "C:\scripts\backup.ps1" ou mysqldump -u root -p database</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="job-diretorio">Diretório de Trabalho</label>
                                <input type="text" id="job-diretorio" class="form-control" placeholder="C:\scripts">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="job-executar-como">Executar Como</label>
                                <select id="job-executar-como" class="form-control">
                                    <option value="SYSTEM">SYSTEM</option>
                                    <option value="Administrator">Administrator</option>
                                    <option value="www-data">www-data</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="job-agendamento-tipo">Agendamento</label>
                                <select id="job-agendamento-tipo" class="form-control" onchange="toggleCamposAgendamento()">
                                    <?php foreach ($viewData['tipos_agendamento'] as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="div-intervalo-valor" style="display: none;">
                            <div class="form-group">
                                <label for="job-intervalo-valor">Intervalo (X)</label>
                                <input type="number" id="job-intervalo-valor" class="form-control" min="1" value="1">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="job-proxima-execucao">Próxima Execução</label>
                                <input type="datetime-local" id="job-proxima-execucao" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="job-priority">Prioridade (1-10)</label>
                                <input type="number" id="job-priority" class="form-control" min="1" max="10" value="5">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="job-timeout">Timeout (segundos)</label>
                                <input type="number" id="job-timeout" class="form-control" value="3600">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input class="custom-control-input" type="checkbox" id="job-compactar" value="1">
                                    <label for="job-compactar" class="custom-control-label">Compactar Saída (ZIP)</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input class="custom-control-input" type="checkbox" id="job-versionamento" value="1" onchange="toggleCamposVersionamento()">
                                    <label for="job-versionamento" class="custom-control-label">Habilitar Retenção</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="div-versionamento" style="display: none;">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="job-retencao-valor">Manter por</label>
                                <div class="input-group">
                                    <input type="number" id="job-retencao-valor" class="form-control" min="1" value="7">
                                    <select id="job-retencao-unidade" class="form-control">
                                        <option value="versoes">Versões (unid)</option>
                                        <option value="dias">Dias</option>
                                        <option value="semanas">Semanas</option>
                                        <option value="meses">Meses</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="job-email">E-mail para Notificação</label>
                        <input type="email" id="job-email" class="form-control" placeholder="admin@sigep.local">
                    </div>

                    <div class="form-group">
                        <label for="job-log">Arquivo de Log</label>
                        <input type="text" id="job-log" class="form-control" placeholder="C:\logs\job_nome.log">
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarJob()">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Execuções -->
<div class="modal fade" id="modalExecucoes" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-history"></i> Histórico de Execuções
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Status</th>
                                <th>Duração</th>
                                <th>Saída</th>
                                <th>Código</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="corpo-tabela-execucoes">
                            <!-- Preenchido via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes Card -->
<div class="modal fade" id="modalDetalhesCard" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titulo-modal-detalhes">Detalhes</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="tabela-detalhes-card">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Última Execução</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div id="mensagem-vazia-card" class="text-center py-3" style="display: none;">
                    <h5 class="text-muted">Nenhum job encontrado :).</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Log Detalhes -->
<div class="modal fade" id="modalLog" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fas fa-file-alt"></i> Log da Execução
                </h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Saída Padrão</h5>
                        <pre id="log-saida" class="bg-light p-3" style="max-height: 400px; overflow-y: auto;"></pre>
                    </div>
                    <div class="col-md-6">
                        <h5>Saída de Erro</h5>
                        <pre id="log-erro" class="bg-danger text-white p-3" style="max-height: 400px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/servicos/job_manager/assets/css/job_manager.css?v=<?= time() ?>">

<!-- JavaScript específico do módulo -->
<script src="/modulos/servicos/job_manager/assets/js/job_manager.js?v=<?= time() ?>"></script>
