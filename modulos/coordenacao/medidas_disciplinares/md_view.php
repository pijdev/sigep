<?php
// Incluir lógica do módulo
require_once 'md_logica.php';
?>

<script>
    window.pageTitle = 'Medidas Disciplinares';
    window.currentPage = 'md_view.php';
</script>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="modulos/coordenacao/medidas_disciplinares/assets/css/md.css">

<section class="content pt-3">
    <div class="container-fluid">

        <!-- CARDS ESTATÍSTICA -->
        <div class="row mb-3">
            <div class="col-lg-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($stats['ativas'], 0) ?></h3>
                        <p>MDs Ativas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3><?= number_format($stats['concluidas'], 0) ?></h3>
                        <p>MDs Concluídas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($stats['itens_retidos'], 0) ?></h3>
                        <p>Itens Retidos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="m-0 text-dark dark-mode-text font-weight-bold">
                <i class="fas fa-gavel text-primary mr-2"></i> Medidas Disciplinares
            </h4>
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalNovaMD">
                <i class="fas fa-plus mr-2"></i>Nova Medida Disciplinar
            </button>
        </div>

        <!-- FILTROS -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-body py-3">
                <form id="formFiltro" onsubmit="carregarMDs(); return false;">
                    <div class="row">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="busca" placeholder="Buscar por interno, IPEN ou motivo...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" name="status">
                                <option value="">Todos os status</option>
                                <option value="Ativa">Ativas</option>
                                <option value="Concluida">Concluídas</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search mr-2"></i>Filtrar
                            </button>
                            <button type="button" class="btn btn-outline-secondary ml-2" onclick="limparFiltros()">
                                <i class="fas fa-times mr-2"></i>Limpar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- LISTA DE MDs -->
        <div class="card shadow border-0">
            <div class="card-body">
                <div id="listaMDs">
                    <div class="text-center p-4">
                        <i class="fas fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="mt-2">Carregando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL NOVA MD -->
<div class="modal fade" id="modalNovaMD" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-gavel mr-2"></i>Nova Medida Disciplinar
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="formNovaMD">
                    <input type="hidden" name="id_medida" id="id_medida">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">IPEN do Interno <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="ipen_interno" name="id_interno" required>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="buscarInterno()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Digite o IPEN e clique na lupa para buscar</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">Local de Castigo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="local_castigo" required
                                       placeholder="Ex: Cela de Castigo 1, Isolamento...">
                            </div>
                        </div>
                    </div>

                    <div id="dadosInterno" class="alert alert-info d-none">
                        <h6><i class="fas fa-user mr-2"></i>Dados do Interno</h6>
                        <div id="infoInterno"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">Data Início <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="data_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="small font-weight-bold">Data Fim <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="data_fim" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">Motivo <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="motivo" rows="3" required
                                  placeholder="Descreva detalhadamente o motivo da medida disciplinar..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="small font-weight-bold">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="2"
                                  placeholder="Informações adicionais..."></textarea>
                    </div>

                    <!-- ITENS APREENDIDOS -->
                    <div class="border-top pt-3">
                        <h6><i class="fas fa-box mr-2"></i>Itens Apreendidos</h6>
                        <div id="itensApreendidos">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Busque o interno para carregar os itens apreendidos vinculados a ele ou à sua cela.</strong>
                            </div>
                        </div>
                    </div>

                    <!-- ANEXOS -->
                    <div class="border-top pt-3 mt-3">
                        <h6><i class="fas fa-paperclip mr-2"></i>Anexos (Fotos/Documentos)</h6>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="anexoMD" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                            <label class="custom-file-label" for="anexoMD">Escolher arquivo...</label>
                        </div>
                        <small class="text-muted">Formatos aceitos: JPG, PNG, GIF, PDF, DOC, DOCX (máx. 5MB)</small>
                        <div id="listaAnexos" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarMD()">
                    <i class="fas fa-save mr-2"></i>Salvar Medida Disciplinar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico do módulo -->
<script src="modulos/coordenacao/medidas_disciplinares/assets/js/md.js"></script>
