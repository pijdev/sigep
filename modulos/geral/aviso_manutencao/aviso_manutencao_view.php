<?php
// modulos/geral/aviso_manutencao/aviso_manutencao_view.php
// View do módulo de gestão de avisos de manutenção - Padrão SPA SIGEP

require_once __DIR__ . '/aviso_manutencao_logica.php';
?>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/geral/aviso_manutencao/assets/css/aviso_manutencao.css?v=<?= time() ?>">

<!-- Content Header (SPA - sem header duplicado) -->
<div class="row mb-2">
    <div class="col-sm-6">
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid" id="main-content">
        <!-- Cards Resumo -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="stats-total"><?= count($avisosAtivos) ?></h3>
                        <p>Avisos Ativos</p>
                    </div>
                    <div class="icon"><i class="fas fa-bell"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="stats-warning">0</h3>
                        <p>Avisos Warning</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="stats-danger">0</h3>
                        <p>Avisos Danger</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="stats-success">0</h3>
                        <p>Avisos Success</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
        </div>

        <!-- Botão Novo Aviso -->
        <div class="row mb-3">
            <div class="col-12">
                <button class="btn btn-primary" onclick="abrirModalNovoAviso()">
                    <i class="fas fa-plus mr-2"></i>Novo Aviso de Manutenção
                </button>
                <button class="btn btn-secondary ml-2" onclick="recarregarAvisos()">
                    <i class="fas fa-sync mr-2"></i>Recarregar
                </button>
            </div>
        </div>

        <!-- Tabela de Avisos -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            Todos os Avisos de Manutenção
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="tabelaAvisos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Severidade</th>
                                        <th>Período</th>
                                        <th>Status</th>
                                        <th>Criado Em</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabelaAvisosBody">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <i class="fas fa-spinner fa-spin mr-2"></i>
                                            Carregando...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
</div>

<!-- Modal Novo/Editar Aviso -->
<div class="modal fade" id="modalAviso" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAvisoTitle">
                    <i class="fas fa-bell mr-2"></i>
                    <span id="modalAvisoTitleText">Novo Aviso de Manutenção</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formAviso">
                <div class="modal-body">
                    <input type="hidden" id="aviso_id" name="id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aviso_titulo">Título do Aviso <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="aviso_titulo" name="titulo" required maxlength="255">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aviso_severidade">Severidade <span class="text-danger">*</span></label>
                                <select class="form-control" id="aviso_severidade" name="severidade" required>
                                    <option value="info">Info (Azul)</option>
                                    <option value="success">Success (Verde)</option>
                                    <option value="warning" selected>Warning (Amarelo)</option>
                                    <option value="danger">Danger (Vermelho)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="aviso_mensagem">Mensagem Detalhada <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="aviso_mensagem" name="mensagem" rows="4" required placeholder="Descreva detalhadamente a manutenção, sistemas impactados, etc..."></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aviso_data_inicio">Data/Hora Início <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="aviso_data_inicio" name="data_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aviso_data_fim">Data/Hora Fim <span class="text-danger">*</span></label>
                                <input type="datetime-local" class="form-control" id="aviso_data_fim" name="data_fim" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aviso_setores">Setores Impactados</label>
                                <select class="form-control" id="aviso_setores" name="setores_impactados[]" multiple>
                                    <option value="TI">TI</option>
                                    <option value="Censura">Censura</option>
                                    <option value="Coordenação">Coordenação</option>
                                    <option value="Eclusa">Eclusa</option>
                                    <option value="Todos">Todos</option>
                                </select>
                                <small class="form-text text-muted">Ctrl+Click para selecionar múltiplos</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="aviso_sistemas">Sistemas Impactados</label>
                                <input type="text" class="form-control" id="aviso_sistemas" placeholder="Separe por vírgula: Painel de Internos, Relatórios, etc...">
                                <small class="form-text text-muted">Ex: Painel de Internos, Relatórios, Gestão de Usuários</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="aviso_ativo" name="ativo" checked>
                            <label class="custom-control-label" for="aviso_ativo">Aviso Ativo</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Salvar Aviso
                    </button>
                </div>
            </form>
        </div>
    </div>
    </section>

    <!-- JavaScript específico do módulo -->
    <script src="/modulos/geral/aviso_manutencao/assets/js/aviso_manutencao.js?v=<?= time() ?>"></script>
