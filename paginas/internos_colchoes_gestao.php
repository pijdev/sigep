<?php
// Verificar e iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) session_start();

// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

require_once __DIR__ . '/../includes/internos_colchoes_logica.php';

?>

<link rel="stylesheet" href="../assets/css/internos_colchoes_gestao.css">
<script src="../assets/js/internos_colchoes_gestao.js"></script>

<section class="content">
    <div class="container-fluid">

        <!-- Resumo do Estoque -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-warehouse"></i>
                            Estoque de Colchões
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" onclick="carregarEstoque()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" onclick="abrirGerenciarLocais()">
                                <i class="fas fa-warehouse"></i> Estoque de Colchões
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="estoque-resumo">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Carregando estoque...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Abas: Entrada, Saída, Histórico -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#entrada" role="tab">
                                    <i class="fas fa-plus-circle text-success"></i> Entrada de Colchões
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#saida" role="tab">
                                    <i class="fas fa-minus-circle text-danger"></i> Saída de Colchões
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#historico" role="tab">
                                    <i class="fas fa-history"></i> Histórico
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">

                            <!-- Aba: Entrada -->
                            <div class="tab-pane fade show active" id="entrada" role="tabpanel">
                                <form id="form-entrada-colchoes" method="post">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="data_entrada">Data <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="data_entrada" name="data_entrada" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="quantidade">Quantidade <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="quantidade" name="quantidade" min="1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="origem">Origem <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="origem" name="origem"
                                                    placeholder="Ex: Compra, Doação" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="documento_referencia">Documento Referência</label>
                                                <input type="text" class="form-control" id="documento_referencia" name="documento_referencia"
                                                    placeholder="Ex: NF 1234">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="id_local_destino">Local Destino <span class="text-danger">*</span></label>
                                                <select class="form-control" id="id_local_destino" name="id_local_destino" required>
                                                    <option value="">Selecione...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="observacoes">Observações</label>
                                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-plus-circle"></i> Registrar Entrada
                                            </button>
                                            <button type="reset" class="btn btn-secondary ml-2">
                                                <i class="fas fa-undo"></i> Limpar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Aba: Saída -->
                            <div class="tab-pane fade" id="saida" role="tabpanel">
                                <form id="form-saida-colchoes" method="post">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="data_saida">Data <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" id="data_saida" name="data_saida" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="quantidade_saida">Quantidade <span class="text-danger">*</span></label>
                                                <input type="number" class="form-control" id="quantidade_saida" name="quantidade" min="1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="tipo_destino">Tipo Destino <span class="text-danger">*</span></label>
                                                <select class="form-control" id="tipo_destino" name="tipo_destino" required onchange="toggleDestinoFields()">
                                                    <option value="">Selecione...</option>
                                                    <option value="Outro">Outro</option>
                                                    <option value="Descarte">Descarte</option>
                                                    <option value="Manutenção">Manutenção</option>
                                                    <option value="Devolução">Devolução</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label for="id_local_origem">Local Origem <span class="text-danger">*</span></label>
                                                <select class="form-control" id="id_local_origem" name="id_local_origem" required>
                                                    <option value="">Selecione...</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row" id="row_outro" style="display:none;">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="destino_outro">Destino <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="destino_outro" name="destino_outro" placeholder="Descreva o destino">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="motivo_saida">Motivo da Saída <span class="text-danger">*</span></label>
                                                <textarea class="form-control" id="motivo_saida" name="motivo_saida" rows="2" required placeholder="Descreva o motivo da saída"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="observacoes_saida">Observações</label>
                                                <textarea class="form-control" id="observacoes_saida" name="observacoes" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-minus-circle"></i> Registrar Saída
                                            </button>
                                            <button type="reset" class="btn btn-secondary ml-2">
                                                <i class="fas fa-undo"></i> Limpar
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Aba: Histórico -->
                            <div class="tab-pane fade" id="historico" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-md-2">
                                        <label>Tipo</label>
                                        <select class="form-control" id="filtro_tipo">
                                            <option value="">Todos</option>
                                            <option value="Entrada">Entrada</option>
                                            <option value="Saída">Saída</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Período</label>
                                        <div class="input-group">
                                            <input type="date" class="form-control" id="filtro_data_inicio">
                                            <div class="input-group-prepend input-group-append">
                                                <span class="input-group-text">até</span>
                                            </div>
                                            <input type="date" class="form-control" id="filtro_data_fim">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-primary form-control" onclick="carregarHistorico()">
                                            <i class="fas fa-search"></i> Filtrar
                                        </button>
                                    </div>
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <button type="button" class="btn btn-success form-control" onclick="imprimirRelatorioColchoes()">
                                            <i class="fas fa-print"></i> Imprimir Relatório
                                        </button>
                                    </div>
                                </div>
                                <div id="historico-lista">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> Selecione os filtros e clique em "Filtrar" para visualizar o histórico.
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Backdrop para Offcanvas -->
<div class="offcanvas-backdrop"></div>

<!-- Modal para Gerenciar Locais -->
<div class="modal fade" id="modalGerenciarLocais" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-warehouse"></i>
                    Gerenciar Locais de Estoque
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulário de Cadastro/Edição -->
                <div id="form-local-container" style="display: none;">
                    <h6 id="form-local-title">
                        <i class="fas fa-plus-circle"></i>
                        Novo Local
                    </h6>
                    <form id="form-local">
                        <input type="hidden" id="local_id" name="id">
                        <div class="form-group mb-2">
                            <label for="local_nome">
                                <i class="fas fa-tag"></i>
                                Nome <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="local_nome" name="nome" required placeholder="Ex: Almoxarifado Central">
                        </div>
                        <div class="form-group mb-2">
                            <label for="local_descricao">
                                <i class="fas fa-align-left"></i>
                                Descrição
                            </label>
                            <textarea class="form-control" id="local_descricao" name="descricao" rows="2" placeholder="Descrição detalhada do local..."></textarea>
                        </div>
                        <div class="form-group mb-2">
                            <label for="local_tipo">
                                <i class="fas fa-list"></i>
                                Tipo <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="local_tipo" name="tipo" required>
                                <option value="">Selecione...</option>
                                <option value="Almoxarifado">📦 Almoxarifado</option>
                                <option value="Depósito">🏪 Depósito</option>
                                <option value="Cela">🔒 Cela</option>
                                <option value="Outro">📌 Outro</option>
                            </select>
                        </div>
                        <div class="form-group mb-2">
                            <label for="local_capacidade">
                                <i class="fas fa-chart-bar"></i>
                                Capacidade Máxima
                            </label>
                            <input type="number" class="form-control" id="local_capacidade" name="capacidade_maxima" min="0" placeholder="Ex: 100">
                        </div>
                        <div class="form-group mb-3">
                            <label for="local_status">
                                <i class="fas fa-toggle-on"></i>
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="local_status" name="status" required>
                                <option value="Ativo">✅ Ativo</option>
                                <option value="Inativo">❌ Inativo</option>
                            </select>
                        </div>
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-secondary" onclick="cancelarFormLocal()">
                                <i class="fas fa-times"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-primary" onclick="salvarLocal()">
                                <i class="fas fa-save"></i> Salvar
                            </button>
                        </div>
                    </form>
                    <hr class="my-3">
                </div>

                <!-- Botão Novo Local -->
                <button type="button" class="btn btn-primary mb-3 w-100" onclick="mostrarFormLocal()">
                    <i class="fas fa-plus-circle"></i> Novo Local
                </button>

                <!-- Lista de Locais -->
                <div id="lista-locais">
                    <div class="text-center">
                        <i class="fas fa-spinner fa-spin"></i> Carregando...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
