<?php
// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

if (session_status() === PHP_SESSION_NONE) session_start();
?>

<section class="content">
    <div class="container-fluid">

        <!-- Cadastro de Solicitação -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus-circle"></i>
                            Nova Solicitação de Colchão
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="form-solicitacao">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="ipen">IPEN <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="ipen" name="ipen"
                                                placeholder="Digite o IPEN" required maxlength="10">
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" onclick="buscarInterno()">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="nome_interno">Nome do Interno</label>
                                        <input type="text" class="form-control" id="nome_interno" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="galeria">Galeria</label>
                                        <input type="text" class="form-control" id="galeria" readonly>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="bloco">Bloco</label>
                                        <input type="text" class="form-control" id="bloco" readonly>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="form-group">
                                        <label for="res">Cela</label>
                                        <input type="text" class="form-control" id="res" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="data_solicitacao">Data da Solicitação</label>
                                        <input type="date" class="form-control" id="data_solicitacao" name="data_solicitacao">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="data_ultimo_recebimento">Último Recebimento</label>
                                        <input type="date" class="form-control" id="data_ultimo_recebimento" name="data_ultimo_recebimento"
                                            placeholder="Deixe em branco se não souber">
                                        <small class="form-text text-muted">Deixe em branco se não souber a data. A solicitação ficará pendente.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="status_entrega">Status para Entrega</label>
                                        <input type="text" class="form-control" id="status_entrega" readonly>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="dias_desde_ultimo">Dias Desde Último</label>
                                        <input type="text" class="form-control" id="dias_desde_ultimo" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="observacoes">Observações</label>
                                        <textarea class="form-control" id="observacoes" name="observacoes" rows="2"
                                            placeholder="Observações sobre a solicitação..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Cadastrar Solicitação
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="limparFormulario()">
                                        <i class="fas fa-times"></i> Limpar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTROLES DE VISUALIZAÇÃO -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i>
                            Solicitações de Colchão
                        </h5>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="custom-control custom-checkbox mr-3">
                            <input type="checkbox" class="custom-control-input" id="mostrarAtendidas" onchange="toggleMostrarAtendidas()">
                            <label class="custom-control-label" for="mostrarAtendidas">
                                Mostrar Atendidas
                            </label>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm" onclick="abrirFiltros()">
                            <i class="fas fa-filter"></i> Filtros
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros e Lista de Solicitações -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list"></i>
                            Solicitações de Colchão
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" onclick="carregarSolicitacoes()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" onclick="gerarTermosSelecionados()" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                <i class="fas fa-file-contract"></i> Termos Selecionados
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" onclick="imprimirRelatorio()" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                <i class="fas fa-print"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros (inicialmente ocultos) -->
                        <div id="painel-filtros" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-md-2">
                                    <label>IPEN</label>
                                    <input type="text" class="form-control" id="filtro_ipen" placeholder="IPEN">
                                </div>
                                <div class="col-md-3">
                                    <label>Nome</label>
                                    <input type="text" class="form-control" id="filtro_nome" placeholder="Nome do interno">
                                </div>
                                <div class="col-md-2">
                                    <label>Galeria</label>
                                    <select class="form-control" id="filtro_galeria">
                                        <option value="">Todas</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Status</label>
                                    <select class="form-control" id="filtro_status">
                                        <option value="">Todos</option>
                                        <option value="PENDENTE">Pendente</option>
                                        <option value="PRIORIDADE">Prioridade</option>
                                        <option value="COM DIREITO">Com Direito</option>
                                        <option value="SEM DIREITO">Sem Direito</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>Período Solicitação</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control" id="filtro_data_inicio">
                                        <div class="input-group-prepend input-group-append">
                                            <span class="input-group-text">até</span>
                                        </div>
                                        <input type="date" class="form-control" id="filtro_data_fim">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" onclick="aplicarFiltros()">
                                        <i class="fas fa-search"></i> Aplicar Filtros
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                                        <i class="fas fa-times"></i> Limpar Filtros
                                    </button>
                                </div>
                            </div>
                            <hr>
                        </div>

                        <!-- Tabela de Solicitações -->
                        <div id="solicitacoes-tabela">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Carregando...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- OFFCANVAS para Atender Solicitação -->
<div id="offcanvasBackdropAtender" class="offcanvas-backdrop-custom" style="display:none;"></div>
<div class="offcanvas-custom" id="offcanvasAtenderSolicitacao" aria-hidden="true">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="m-0"><i class="fas fa-check-circle"></i> Atender Solicitação de Colchão</h5>
        <button type="button" class="btn btn-light btn-sm" onclick="fecharOffcanvasAtender()">&times;</button>
    </div>
    <div class="offcanvas-body" style="padding: 0;">
        <div style="max-height: 70vh; overflow-y: auto; padding: 20px;">
            <form id="form-atender-solicitacao">
                <input type="hidden" id="atender_id" name="id">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>IPEN</label>
                            <input type="text" class="form-control" id="atender_ipen" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Nome</label>
                            <input type="text" class="form-control" id="atender_nome" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Local</label>
                            <input type="text" class="form-control" id="atender_local" readonly>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Status</label>
                            <input type="text" class="form-control" id="atender_status" readonly>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="atender_local_entrega">Local de Retirada <span class="text-danger">*</span></label>
                            <select class="form-control" id="atender_local_entrega" required>
                                <option value="">Selecione...</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="form-group">
                            <label for="atender_observacoes">Observações</label>
                            <textarea class="form-control" id="atender_observacoes" rows="3" placeholder="Observações sobre a entrega..."></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- RODAPÉ DO OFFCANVAS -->
        <div class="border-top p-3 bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-secondary btn-sm" onclick="fecharOffcanvasAtender()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success btn-sm" onclick="confirmarAtendimento()">
                    <i class="fas fa-check"></i> Confirmar Entrega
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Offcanvas para Atualizar Data de Solicitação Pendente -->
<div id="offcanvasBackdrop" class="offcanvas-backdrop-custom" style="display:none;"></div>
<div class="offcanvas-custom" id="offcanvasAtualizarData" aria-hidden="true">
    <div class="offcanvas-custom-header">
        <h5 class="mb-0">
            <i class="fas fa-calendar-alt mr-2"></i>Atualizar Data de Recebimento
        </h5>
        <button type="button" class="close" aria-label="Close" onclick="fecharOffcanvasAtualizarData()">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="offcanvas-custom-body">
        <form id="form-atualizar-data-offcanvas" onsubmit="return false;">
            <input type="hidden" id="atualizar_id" name="id">

            <!-- Informações da Solicitação -->
            <div class="alert alert-info mb-3">
                <h6 class="mb-2">
                    <i class="fas fa-info-circle mr-2"></i>Solicitação #<span id="info_id"></span>
                </h6>
                <div class="row">
                    <div class="col-12">
                        <strong>IPEN:</strong> <span id="info_ipen"></span><br>
                        <strong>Nome:</strong> <span id="info_nome"></span>
                    </div>
                </div>
            </div>

            <!-- Data do Último Recebimento -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title text-warning">
                        <i class="fas fa-calendar mr-2"></i>Data do Último Recebimento
                    </h6>
                    <div class="form-group">
                        <label for="atualizar_data_ultimo" class="form-label">
                            Selecione a data <span class="text-danger">*</span>
                        </label>
                        <input type="date" class="form-control form-control-lg"
                            id="atualizar_data_ultimo" name="data_ultimo_recebimento" required>
                        <small class="form-text text-muted">
                            Informe a data do último recebimento para o sistema calcular o status da solicitação.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex" style="gap: 8px;">
                <button type="button" class="btn btn-secondary flex-fill" onclick="fecharOffcanvasAtualizarData()">
                    <i class="fas fa-times mr-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success flex-fill" onclick="salvarAtualizacaoData()">
                    <i class="fas fa-save mr-2"></i>Salvar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Scripts específicos da página -->
<style>
    /* Variáveis CSS para dark/light mode */
    :root {
        --main-bg: #ffffff;
        --card-bg: #ffffff;
        --card-header-bg: #f8f9fa;
        --input-bg: #ffffff;
        --border-color: #dee2e6;
        --main-text-color: #212529;
        --input-text-color: #495057;
        --muted-text: #6c757d;
        --placeholder-color: #adb5bd;
        --hover-bg: #f8f9fa;
        --table-hover-bg: #f5f5f5;
        --modal-bg: #ffffff;
        --modal-header-bg: #f8f9fa;
    }

    body.dark-mode {
        --main-bg: #1a1a1a;
        --card-bg: #2d3748;
        --card-header-bg: #374151;
        --input-bg: #374151;
        --border-color: #4a5568;
        --main-text-color: #f7fafc;
        --input-text-color: #e2e8f0;
        --muted-text: #a0aec0;
        --placeholder-color: #718096;
        --hover-bg: #374151;
        --table-hover-bg: #2d3748;
        --modal-bg: #2d3748;
        --modal-header-bg: #374151;
    }

    /* Aplicar variáveis aos elementos */
    .content {
        background: var(--main-bg);
        color: var(--main-text-color);
    }

    .card {
        background: var(--card-bg);
        border-color: var(--border-color);
    }

    .card-header {
        background: var(--card-header-bg);
        border-color: var(--border-color);
        color: var(--main-text-color);
    }

    .card-body {
        background: var(--card-bg);
        color: var(--main-text-color);
    }

    .form-control {
        background: var(--input-bg);
        border-color: var(--border-color);
        color: var(--input-text-color);
    }

    .form-control:focus {
        background: var(--input-bg);
        border-color: #007bff;
        color: var(--input-text-color);
    }

    .form-control::placeholder {
        color: var(--placeholder-color);
    }

    .form-text {
        color: var(--muted-text);
    }

    .table {
        background: var(--card-bg);
        color: var(--main-text-color);
    }

    .table th {
        background: var(--card-header-bg);
        border-color: var(--border-color);
        color: var(--main-text-color);
    }

    .table td {
        border-color: var(--border-color);
    }

    .table-hover tbody tr:hover {
        background: var(--table-hover-bg);
    }

    .table-warning {
        background-color: rgba(255, 193, 7, 0.25) !important;
    }

    .table-warning td {
        color: var(--main-text-color) !important;
        font-weight: 500 !important;
    }

    body.dark-mode .table-warning {
        background-color: rgba(255, 193, 7, 0.2) !important;
    }

    body.dark-mode .table-warning td {
        color: #1a1a1a !important;
        font-weight: 600 !important;
        text-shadow: 0 0 1px rgba(255, 255, 255, 0.3) !important;
    }

    .modal-content {
        background: var(--modal-bg);
        color: var(--main-text-color);
    }

    .modal-header {
        background: var(--modal-header-bg);
        border-color: var(--border-color);
        color: var(--main-text-color);
    }

    .modal-body {
        background: var(--modal-bg);
        color: var(--main-text-color);
    }

    .alert {
        border-radius: 0.5rem;
        border: none;
    }

    .alert-info {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }

    .alert-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #212529;
    }

    .alert-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .alert-success {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
    }

    /* Badges */
    .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }

    .badge-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #212529;
    }

    .badge-danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .badge-success {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
    }

    .badge-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }

    /* Botões */
    .btn {
        border-radius: 0.375rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-outline-info {
        border-color: #17a2b8;
        color: #17a2b8;
    }

    .btn-outline-info:hover {
        background: #17a2b8;
        color: white;
        transform: translateY(-1px);
    }

    .btn-outline-primary {
        border-color: #007bff;
        color: #007bff;
    }

    .btn-outline-primary:hover {
        background: #007bff;
        color: white;
        transform: translateY(-1px);
    }

    .btn-outline-danger {
        border-color: #dc3545;
        color: #dc3545;
    }

    .btn-outline-danger:hover {
        background: #dc3545;
        color: white;
        transform: translateY(-1px);
    }

    .btn-success {
        background: linear-gradient(135deg, #28a745, #218838);
        border: none;
    }

    .btn-success:hover {
        background: linear-gradient(135deg, #218838, #1e7e34);
        transform: translateY(-1px);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        border: none;
    }

    .btn-secondary:hover {
        background: linear-gradient(135deg, #5a6268, #545b62);
        transform: translateY(-1px);
    }

    /* Labels */
    label {
        color: var(--main-text-color);
        font-weight: 500;
    }

    /* Links */
    a {
        color: #007bff;
    }

    a:hover {
        color: #0056b3;
    }

    body.dark-mode a {
        color: #3182ce;
    }

    body.dark-mode a:hover {
        color: #2c5aa0;
    }

    /* Offcanvas Styles */
    .offcanvas {
        background: var(--modal-bg);
        color: var(--main-text-color);
    }

    .offcanvas-header {
        background: var(--modal-header-bg);
        border-color: var(--border-color);
        color: var(--main-text-color);
    }

    .offcanvas-title {
        color: var(--main-text-color);
    }

    .offcanvas-body {
        background: var(--modal-bg);
        color: var(--main-text-color);
    }

    .btn-close {
        filter: invert(1);
    }

    body.dark-mode .btn-close {
        filter: invert(0);
    }

    /* Offcanvas custom (compatível com Bootstrap 4) */
    .offcanvas-backdrop-custom {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1040;
    }

    .offcanvas-custom {
        position: fixed;
        top: 0;
        right: 0;
        height: 100vh;
        width: 420px;
        max-width: 90vw;
        transform: translateX(100%);
        transition: transform 0.25s ease;
        background: var(--modal-bg);
        color: var(--main-text-color);
        z-index: 1050;
        box-shadow: -8px 0 20px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
    }

    .offcanvas-custom.show {
        transform: translateX(0);
    }

    .offcanvas-custom-header {
        padding: 16px 20px;
        background: var(--modal-header-bg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .offcanvas-custom-header h5 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
    }

    .offcanvas-custom-header .close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: var(--muted-text);
        opacity: 0.8;
        transition: opacity 0.2s;
    }

    .offcanvas-custom-header .close:hover {
        opacity: 1;
    }

    .offcanvas-custom-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
        background: var(--modal-bg);
    }

    .offcanvas-custom-body .alert {
        margin-bottom: 20px;
        border-radius: 8px;
    }

    .offcanvas-custom-body .card {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        margin-bottom: 20px;
    }

    .offcanvas-custom-body .card:last-child {
        margin-bottom: 0;
    }

    .offcanvas-custom-body .card-header {
        background: var(--card-header-bg);
        border-bottom: 1px solid var(--border-color);
        padding: 12px 16px;
        border-radius: 8px 8px 0 0;
    }

    .offcanvas-custom-body .card-body {
        padding: 16px;
    }

    .offcanvas-custom-body .form-group {
        margin-bottom: 16px;
    }

    .offcanvas-custom-body .form-group:last-child {
        margin-bottom: 0;
    }

    .offcanvas-custom-body .form-label {
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--main-text-color);
    }

    .offcanvas-custom-body .form-control {
        border-radius: 6px;
        border: 1px solid var(--border-color);
        padding: 10px 12px;
        font-size: 14px;
    }

    .offcanvas-custom-body .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .offcanvas-custom-body .form-control[readonly] {
        background-color: var(--hover-bg);
        font-weight: 600;
    }

    .offcanvas-custom-body .input-group {
        display: flex;
        align-items: stretch;
    }

    .offcanvas-custom-body .input-group .form-control {
        flex: 1;
    }

    .offcanvas-custom-body .input-group-append {
        display: flex;
        margin-left: -1px;
    }

    .offcanvas-custom-body .input-group-text {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        margin-bottom: 0;
        font-size: 14px;
        font-weight: 400;
        line-height: 1.5;
        color: var(--input-text-color);
        text-align: center;
        white-space: nowrap;
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 0 6px 6px 0;
    }

    .offcanvas-custom-body .btn {
        border-radius: 6px;
        padding: 10px 16px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .offcanvas-custom-body .btn:hover {
        transform: translateY(-1px);
    }

    .offcanvas-custom-body .text-danger {
        color: #dc3545 !important;
    }

    .offcanvas-custom-body .text-warning {
        color: #ffc107 !important;
    }

    .offcanvas-custom-body .text-info {
        color: #17a2b8 !important;
    }

    .offcanvas-custom-body .font-weight-bold {
        font-weight: 700 !important;
    }
</style>
<script>
    // Data atual para os formulários
    (function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_solicitacao').value = today;

        // Verificar se há mensagem de sucesso ou erro
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('success') === '1') {
            const message = urlParams.get('message') || 'Operação realizada com sucesso!';
            showAlert(message, 'success');

            // Limpar URL sem recarregar
            window.history.replaceState({}, document.title, window.location.pathname);
        } else if (urlParams.get('error') === '1') {
            const message = urlParams.get('message') || 'Ocorreu um erro.';
            showAlert(message, 'error');

            // Limpar URL sem recarregar
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Carregar dados iniciais
        carregarSolicitacoes();
        carregarGalerias();
    })();

    // Buscar interno pelo IPEN
    function buscarInterno() {
        const ipen = document.getElementById('ipen').value.trim();

        if (!ipen) {
            showAlert('Digite o IPEN do interno.', 'error');
            return;
        }

        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_interno_dados&ipen=${ipen}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const interno = data.interno;
                    document.getElementById('nome_interno').value = interno.nome;
                    document.getElementById('galeria').value = interno.galeria || '';
                    document.getElementById('bloco').value = interno.bloco || '';
                    document.getElementById('res').value = interno.res || '';

                    // Buscar último recebimento
                    buscarUltimoRecebimento(ipen);

                    // Verificar solicitações recentes
                    verificarSolicitacoesRecentes(ipen);
                } else {
                    showAlert(data.message || 'Interno não encontrado.', 'error');
                    limparCamposInterno();
                }
            })
            .catch(error => {
                console.error('Erro ao buscar interno:', error);
                showAlert('Erro ao buscar dados do interno.', 'error');
            });
    }

    // Buscar último recebimento do interno
    function buscarUltimoRecebimento(ipen) {
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_ultimo_recebimento&ipen=${ipen}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ultimo = data.ultimo;
                    // Não preencher automaticamente - deixar para o usuário decidir
                    document.getElementById('dias_desde_ultimo').value = ultimo.dias_desde_ultimo || '';
                    document.getElementById('status_entrega').value = ultimo.status_entrega || '';
                } else {
                    // Nunca recebeu
                    document.getElementById('dias_desde_ultimo').value = 'Nunca recebeu';
                    document.getElementById('status_entrega').value = 'PRIORIDADE';
                }
            })
            .catch(error => {
                console.error('Erro ao buscar último recebimento:', error);
            });
    }

    // Verificar solicitações recentes do interno
    function verificarSolicitacoesRecentes(ipen) {
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacoes&ipen=${ipen}&mostrar_atendidas=true`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.solicitacoes.length > 0) {
                    // Verificar se há solicitações nos últimos 30 dias
                    const hoje = new Date();
                    const solicitacoesRecentes = data.solicitacoes.filter(sol => {
                        const dataCriacao = new Date(sol.criado_em);
                        const diasDiferenca = Math.floor((hoje - dataCriacao) / (1000 * 60 * 60 * 24));
                        return diasDiferenca <= 30;
                    });

                    if (solicitacoesRecentes.length > 0) {
                        const maisRecente = solicitacoesRecentes[0];
                        const dias = Math.floor((hoje - new Date(maisRecente.criado_em)) / (1000 * 60 * 60 * 24));

                        let mensagem = `⚠️ <strong>Atenção!</strong> Este interno tem solicitação(s) recente(s):<br><br>`;
                        mensagem += `• <strong>${dias} dias atrás</strong> - Status: <span class="badge badge-${getStatusBadgeClass(maisRecente.status_display)}">${maisRecente.status_display}</span><br>`;

                        if (maisRecente.status_solicitacao === 'Atendida') {
                            mensagem += `• Data do atendimento: ${formatDate(maisRecente.data_atendimento)}<br>`;
                        }

                        mensagem += `<br><strong>Para evitar duplicidades:</strong><br>`;
                        mensagem += `• Aguarde pelo menos 30 dias entre solicitações<br>`;
                        mensagem += `• Verifique o histórico completo antes de criar nova solicitação`;

                        // Mostrar alerta de aviso (amarelo)
                        showAlertWarning(mensagem);
                    }
                }
            })
            .catch(error => {
                console.error('Erro ao verificar solicitações recentes:', error);
            });
    }

    // Função auxiliar para obter classe do badge de status
    function getStatusBadgeClass(status) {
        switch (status) {
            case 'PRIORIDADE':
                return 'danger';
            case 'COM DIREITO':
                return 'success';
            case 'SEM DIREITO':
                return 'warning';
            case 'PENDENTE':
                return 'secondary';
            case 'Atendida':
                return 'info';
            default:
                return 'secondary';
        }
    }

    // Função auxiliar para formatar data
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    // Função para mostrar alerta de aviso (amarelo)
    function showAlertWarning(message) {
        const alertHtml = `
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

        const firstCard = document.querySelector('.card');
        if (firstCard) {
            firstCard.insertAdjacentHTML('beforebegin', alertHtml);

            // Auto-remove após 15 segundos
            setTimeout(() => {
                const alert = document.querySelector('.alert-warning');
                if (alert) {
                    alert.remove();
                }
            }, 15000);
        }
    }

    // Limpar campos do interno
    function limparCamposInterno() {
        document.getElementById('nome_interno').value = '';
        document.getElementById('galeria').value = '';
        document.getElementById('bloco').value = '';
        document.getElementById('res').value = '';
        document.getElementById('data_ultimo_recebimento').value = '';
        document.getElementById('dias_desde_ultimo').value = '';
        document.getElementById('status_entrega').value = '';
    }

    // Atualizar status quando data for alterada
    document.getElementById('data_ultimo_recebimento').addEventListener('change', function() {
        const data = this.value;
        if (data) {
            const dias = Math.floor((new Date() - new Date(data)) / (1000 * 60 * 60 * 24));
            document.getElementById('dias_desde_ultimo').value = dias;

            if (dias < 365) {
                document.getElementById('status_entrega').value = 'SEM DIREITO';
            } else if (dias > 730) {
                document.getElementById('status_entrega').value = 'PRIORIDADE';
            } else {
                document.getElementById('status_entrega').value = 'COM DIREITO';
            }
        } else {
            document.getElementById('dias_desde_ultimo').value = '';
            document.getElementById('status_entrega').value = 'PENDENTE';
        }
    });

    // Limpar formulário completo
    function limparFormulario() {
        document.getElementById('form-solicitacao').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('data_solicitacao').value = today;
        limparCamposInterno();
    }

    // Carregar galerias para filtros
    function carregarGalerias() {
        fetch('/includes/internos_colchoes_solicitacoes_logica.php?action=get_galerias')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('filtro_galeria');
                    data.galerias.forEach(galeria => {
                        select.innerHTML += `<option value="${galeria}">${galeria}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao carregar galerias:', error);
            });
    }

    // Carregar solicitações
    function carregarSolicitacoes() {
        const div = document.getElementById('solicitacoes-tabela');
        div.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

        const mostrarAtendidas = document.getElementById('mostrarAtendidas').checked;

        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacoes&mostrar_atendidas=${mostrarAtendidas}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exibirSolicitacoes(data.solicitacoes);
                } else {
                    div.innerHTML = '<div class="alert alert-warning">Nenhuma solicitação encontrada.</div>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar solicitações:', error);
                div.innerHTML = '<div class="alert alert-danger">Erro ao carregar solicitações.</div>';
            });
    }

    // Exibir solicitações na tabela
    function exibirSolicitacoes(solicitacoes) {
        const div = document.getElementById('solicitacoes-tabela');

        if (solicitacoes.length === 0) {
            div.innerHTML = '<div class="alert alert-info">Nenhuma solicitação encontrada.</div>';
            return;
        }

        let html = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th width="40px"><input type="checkbox" id="selecionar-todos" onclick="selecionarTodos()"></th>
                        <th>IPEN</th>
                        <th>Nome</th>
                        <th>Local</th>
                        <th>Data Solicitação</th>
                        <th>Último Recebimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
    `;

        solicitacoes.forEach(solicitacao => {
            const statusBadge = getStatusBadge(solicitacao.status_display);
            const tempoUltimo = solicitacao.data_ultimo_recebimento ?
                `Há ${solicitacao.dias_desde_ultimo} dias` : '⏳ Pendente';

            // Destaque para solicitações pendentes
            const rowClass = solicitacao.status_entrega === 'PENDENTE' ? 'table-warning' : '';

            html += `
            <tr class="${rowClass}">
                <td>${solicitacao.status_solicitacao === 'Atendida' ? `<input type="checkbox" class="selecao-termo" value="${solicitacao.id}">` : ''}</td>
                <td>${solicitacao.ipen}</td>
                <td>${solicitacao.nome_interno}</td>
                <td>${solicitacao.galeria}/${solicitacao.bloco || ''} ${solicitacao.res || ''}</td>
                <td>${formatDate(solicitacao.data_solicitacao)}</td>
                <td>${tempoUltimo}</td>
                <td>${statusBadge}</td>
                <td>
                    ${solicitacao.status_solicitacao === 'Atendida' ?
                        `<button class="btn btn-sm btn-outline-success" onclick="gerarTermoColchao(${solicitacao.id})" title="Gerar Termo de Entrega">
                            <i class="fas fa-file-contract"></i> Termo
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="cancelarSolicitacao(${solicitacao.id})">
                            <i class="fas fa-times"></i> Cancelar
                        </button>` :
                        (solicitacao.status_entrega === 'PENDENTE' ?
                            `<button class="btn btn-sm btn-outline-info" onclick="atualizarDataSolicitacao(${solicitacao.id})" title="Atualizar Data">
                                <i class="fas fa-calendar"></i> Atualizar Data
                            </button>` :
                            `<button class="btn btn-sm btn-outline-primary" onclick="atenderSolicitacao(${solicitacao.id})">
                                <i class="fas fa-check"></i> Atender
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="cancelarSolicitacao(${solicitacao.id})">
                                <i class="fas fa-times"></i> Cancelar
                            </button>`
                        )
                    }
                </td>
            </tr>
        `;
        });

        html += '</tbody></table></div>';
        div.innerHTML = html;
    }

    // Obter badge de status
    function getStatusBadge(status) {
        const badges = {
            'PENDENTE': '<span class="badge badge-warning">⏳ PENDENTE</span>',
            'PRIORIDADE': '<span class="badge badge-danger">🔴 PRIORIDADE</span>',
            'COM DIREITO': '<span class="badge badge-success">🟢 COM DIREITO</span>',
            'SEM DIREITO': '<span class="badge badge-secondary">🔵 SEM DIREITO</span>'
        };
        return badges[status] || status;
    }

    // Formatar data
    function formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString + 'T00:00:00');
        return date.toLocaleDateString('pt-BR', {
            timeZone: 'America/Sao_Paulo'
        });
    }

    // Abrir/fechar painel de filtros
    function abrirFiltros() {
        const painel = document.getElementById('painel-filtros');
        painel.style.display = painel.style.display === 'none' ? 'block' : 'none';
    }

    // Aplicar filtros
    function aplicarFiltros() {
        const filtros = {
            ipen: document.getElementById('filtro_ipen').value,
            nome: document.getElementById('filtro_nome').value,
            galeria: document.getElementById('filtro_galeria').value,
            status: document.getElementById('filtro_status').value,
            data_inicio: document.getElementById('filtro_data_inicio').value,
            data_fim: document.getElementById('filtro_data_fim').value
        };

        const mostrarAtendidas = document.getElementById('mostrarAtendidas').checked;

        const params = new URLSearchParams(filtros);
        params.append('mostrar_atendidas', mostrarAtendidas);

        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacoes&${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    exibirSolicitacoes(data.solicitacoes);
                } else {
                    document.getElementById('solicitacoes-tabela').innerHTML =
                        '<div class="alert alert-info">Nenhuma solicitação encontrada com os filtros aplicados.</div>';
                }
            })
            .catch(error => {
                console.error('Erro ao aplicar filtros:', error);
            });
    }

    // Toggle mostrar atendidas
    function toggleMostrarAtendidas() {
        carregarSolicitacoes();
    }

    // Limpar filtros
    function limparFiltros() {
        document.getElementById('filtro_ipen').value = '';
        document.getElementById('filtro_nome').value = '';
        document.getElementById('filtro_galeria').value = '';
        document.getElementById('filtro_status').value = '';
        document.getElementById('filtro_data_inicio').value = '';
        document.getElementById('filtro_data_fim').value = '';
        carregarSolicitacoes();
    }

    function abrirOffcanvasAtualizarData() {
        const backdrop = document.getElementById('offcanvasBackdrop');
        const panel = document.getElementById('offcanvasAtualizarData');
        if (backdrop) backdrop.style.display = 'block';
        if (panel) {
            panel.classList.add('show');
            panel.setAttribute('aria-hidden', 'false');
        }
        document.body.style.overflow = 'hidden';
    }

    function fecharOffcanvasAtualizarData() {
        const backdrop = document.getElementById('offcanvasBackdrop');
        const panel = document.getElementById('offcanvasAtualizarData');
        if (panel) {
            panel.classList.remove('show');
            panel.setAttribute('aria-hidden', 'true');
        }
        if (backdrop) backdrop.style.display = 'none';
        document.body.style.overflow = '';
    }

    // Atualizar data de solicitação pendente - USANDO OFFCANVAS
    function atualizarDataSolicitacao(id) {
        // Buscar dados da solicitação
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacao_para_atualizar&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const solicitacao = data.solicitacao;

                    // Preencher apenas os campos necessários
                    document.getElementById('atualizar_id').value = solicitacao.id;
                    document.getElementById('info_id').textContent = solicitacao.id;
                    document.getElementById('info_ipen').textContent = solicitacao.ipen;
                    document.getElementById('info_nome').textContent = solicitacao.nome_interno;
                    document.getElementById('atualizar_data_ultimo').value = solicitacao.data_ultimo_recebimento || '';

                    abrirOffcanvasAtualizarData();
                } else {
                    showAlert(data.message || 'Erro ao carregar dados da solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro ao carregar solicitação:', error);
                showAlert('Erro ao carregar dados da solicitação.', 'error');
            });
    }

    // Salvar atualização de data - MÉTODO POST
    function salvarAtualizacaoData() {
        const id = document.getElementById('atualizar_id').value;
        const data = document.getElementById('atualizar_data_ultimo').value;

        if (!data) {
            showAlert('Selecione a data do último recebimento.', 'error');
            return;
        }

        if (!confirm('Confirma a atualização da data para ' + data + '?')) {
            return;
        }

        // Usar POST em vez de GET
        const formData = new FormData();
        formData.append('action', 'atualizar_data_solicitacao');
        formData.append('id', id);
        formData.append('data_ultimo_recebimento', data);

        fetch('/includes/internos_colchoes_solicitacoes_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    fecharOffcanvasAtualizarData();
                    carregarSolicitacoes(); // Recarregar lista
                } else {
                    showAlert(data.message || 'Erro ao atualizar data.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao atualizar data.', 'error');
            });
    }

    // Event listeners para offcanvas de atendimento
    document.addEventListener('DOMContentLoaded', function() {
        const backdrop = document.getElementById('offcanvasBackdropAtender');
        if (backdrop) {
            backdrop.addEventListener('click', fecharOffcanvasAtender);
        }

        // Listener para tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const panel = document.getElementById('offcanvasAtenderSolicitacao');
                if (panel && panel.style.transform === 'translateX(0px)') {
                    fecharOffcanvasAtender();
                }
            }
        });
    });

    // Gerar Termo de Entrega de Colchão
    function gerarTermoColchao(id) {
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=gerar_termo&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Abrir termo em nova janela
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(data.html);
                    newWindow.document.close();
                } else {
                    showAlert(data.message || 'Erro ao gerar termo.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao gerar termo.', 'error');
            });
    }

    // Imprimir relatório
    function imprimirRelatorio() {
        // Capturar filtros atuais da página
        const filtros = {
            ipen: document.getElementById('filtro_ipen').value,
            nome: document.getElementById('filtro_nome').value,
            galeria: document.getElementById('filtro_galeria').value,
            status: document.getElementById('filtro_status').value,
            data_inicio: document.getElementById('filtro_data_inicio').value,
            data_fim: document.getElementById('filtro_data_fim').value
        };

        // Construir URL com filtros
        const params = new URLSearchParams(filtros);
        params.append('action', 'imprimir_relatorio');

        // Abrir relatório em nova janela
        window.open(`/includes/internos_colchoes_solicitacoes_logica.php?${params.toString()}`, '_blank');
    }

    // Selecionar todos os checkboxes
    function selecionarTodos() {
        const masterCheckbox = document.getElementById('selecionar-todos');
        const checkboxes = document.querySelectorAll('.selecao-termo');

        checkboxes.forEach(checkbox => {
            checkbox.checked = masterCheckbox.checked;
        });
    }

    // Gerar termos para solicitações selecionadas
    function gerarTermosSelecionados() {
        const checkboxesSelecionadas = document.querySelectorAll('.selecao-termo:checked');

        if (checkboxesSelecionadas.length === 0) {
            showAlert('Selecione pelo menos uma solicitação para gerar termos.', 'error');
            return;
        }

        const ids = Array.from(checkboxesSelecionadas).map(cb => cb.value);

        // Confirmar geração de múltiplos termos
        if (!confirm(`Gerar termos para ${ids.length} solicitação(ões) selecionada(s)?\n\nTodos os termos serão gerados em uma única página para impressão.`)) {
            return;
        }

        // Mostrar loading
        showAlert(`Gerando termos para ${ids.length} solicitação(ões)... Aguarde.`, 'success');

        // Fazer chamada única para gerar todos os termos
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=gerar_termos_lote&ids=${ids.join(',')}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Abrir todos os termos em uma única janela/página
                    const newWindow = window.open('', '_blank');
                    newWindow.document.write(data.html);
                    newWindow.document.close();

                    // Limpar seleção após geração
                    document.getElementById('selecionar-todos').checked = false;
                    selecionarTodos();
                } else {
                    showAlert(data.message || 'Erro ao gerar termos.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao gerar termos.', 'error');
            });
    }

    // Atender solicitação
    function atenderSolicitacao(id) {
        // Buscar dados da solicitação
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacao&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const solicitacao = data.solicitacao;

                    // Verificar se interno não tem direito e mostrar modal de confirmação
                    if (solicitacao.status_entrega === 'SEM DIREITO') {
                        showModalConfirmacaoAtendimentoSemDireito(solicitacao);
                        return;
                    }

                    // Se tem direito, prosseguir normalmente
                    prosseguirAtendimentoNormal(solicitacao);
                } else {
                    showAlert('Erro ao carregar dados da solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao carregar dados da solicitação.', 'error');
            });
    }

    // Carregar locais para entrega
    function carregarLocaisParaEntrega() {
        fetch('/includes/internos_colchoes_logica.php?action=get_locais')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('atender_local_entrega');
                select.innerHTML = '<option value="">Selecione...</option>';

                if (data.success && data.locais.length > 0) {
                    data.locais.forEach(local => {
                        if (local.status === 'Ativo' && local.quantidade > 0) {
                            const option = `<option value="${local.id}">${local.nome} (${local.quantidade} disponíveis)</option>`;
                            select.innerHTML += option;
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao carregar locais:', error);
            });
    }

    // Mostrar modal de validação e confirmação
    function showModalValidacaoAtendimento(solicitacao, validacao, mensagemValidacao) {
        // Preencher modal de atendimento
        document.getElementById('atender_id').value = solicitacao.id;
        document.getElementById('atender_ipen').value = solicitacao.ipen;
        document.getElementById('atender_nome').value = solicitacao.nome_interno;
        document.getElementById('atender_local').value = `${solicitacao.galeria}/${solicitacao.bloco || ''} ${solicitacao.res || ''}`;
        document.getElementById('atender_status').value = solicitacao.status_entrega;

        // Carregar locais disponíveis
        carregarLocaisParaEntrega();

        // Criar modal de validação
        const modalHtml = `
        <div class="modal fade" id="modalValidacaoAtendimento" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-shield-alt"></i>
                            Validação de Aptidão
                        </h5>
                        <button type="button" class="btn-close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert ${validacao.apto ? 'alert-success' : 'alert-danger'}">
                            <h6>
                                <i class="fas ${validacao.apto ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                                Status: ${validacao.apto ? 'APTO' : 'NÃO APTO'}
                            </h6>
                            <p class="mb-0">${mensagemValidacao}</p>
                        </div>

                        ${validacao.apto ? `
                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Confirmação de Atendimento</h6>
                                <p>Deseja realmente atender esta solicitação de colchão?</p>
                                <div class="d-flex gap-2 mt-3">
                                    <button type="button" class="btn btn-success" onclick="prosseguirAtendimento()">
                                        <i class="fas fa-check"></i> Sim, Atender
                                    </button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancelar
                                    </button>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fas fa-times"></i> Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

        // Adicionar modal ao body
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer);

        // Mostrar modal
        const modal = new bootstrap.Modal(document.getElementById('modalValidacaoAtendimento'));
        modal.show();

        // Remover modal do DOM quando for fechado
        document.getElementById('modalValidacaoAtendimento').addEventListener('hidden.bs.modal', function() {
            modalContainer.remove();
        });
    }

    // Mostrar modal de confirmação de atendimento sem direito
    function showModalConfirmacaoAtendimentoSemDireito(solicitacao) {
        // Criar ID único para este modal baseado no IPEN
        const modalId = `modalConfirmacaoAtendimentoSemDireito_${solicitacao.ipen}`;

        // Remover qualquer modal anterior que possa existir
        const modaisAnteriores = document.querySelectorAll('[id^="modalConfirmacaoAtendimentoSemDireito_"]');
        modaisAnteriores.forEach(modal => {
            const container = modal.closest('div');
            while (container && !container.classList.contains('modal')) {
                container = container.parentElement;
            }
            if (container) container.remove();
        });

        // Remover backdrops antigos
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());

        // Criar modal de confirmação para atendimento sem direito
        const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Confirmação: Atendimento Fora das Regras
                        </h5>
                        <button type="button" class="btn-close" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Dados da Solicitação</h6>
                            <p class="mb-1"><strong>IPEN:</strong> ${solicitacao.ipen}</p>
                            <p class="mb-1"><strong>Nome:</strong> ${solicitacao.nome_interno}</p>
                            <p class="mb-1"><strong>Local:</strong> Galeria ${solicitacao.galeria} - Bloco ${solicitacao.bloco || ''} - Cela ${solicitacao.res || ''}</p>
                            <p class="mb-1"><strong>Data Solicitação:</strong> ${new Date(solicitacao.data_solicitacao + 'T00:00:00').toLocaleDateString('pt-BR', {timeZone: 'America/Sao_Paulo'})}</p>
                        </div>

                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Motivo: Sem Direito</h6>
                            <p class="mb-0">Este interno não tem direito a colchão atualmente porque:</p>
                            <ul class="mb-0 mt-2">
                                <li><strong>Último recebimento:</strong> Há ${solicitacao.dias_desde_ultimo || 'N/A'} dias</li>
                                <li><strong>Período mínimo:</strong> 365 dias entre recebimentos</li>
                                <li><strong>Status atual:</strong> ${solicitacao.status_entrega}</li>
                            </ul>
                        </div>

                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Atenção</h6>
                            <p>Esta ação irá atender a solicitação <strong>FORA DAS REGRAS NORMAlS</strong> do sistema.</p>
                            <p class="mb-0">Certifique-se de que há justificativa adequada para esta exceção.</p>
                        </div>

                        <div class="form-group">
                            <label for="confirmacao_ipen_atendimento_${solicitacao.ipen}" class="form-label"><strong>Digite o IPEN para confirmar o atendimento excepcional:</strong></label>
                            <input type="text" class="form-control" id="confirmacao_ipen_atendimento_${solicitacao.ipen}" placeholder="Digite o IPEN: ${solicitacao.ipen}" maxlength="10">
                            <small class="form-text text-muted">Digite exatamente o IPEN para confirmar a operação excepcional</small>
                        </div>

                        <div class="form-group">
                            <label for="autorizado_por_${solicitacao.ipen}" class="form-label"><strong>Autorizado por:</strong> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="autorizado_por_${solicitacao.ipen}" placeholder="Nome da pessoa que autorizou" maxlength="100" required>
                            <small class="form-text text-muted">Ex: Assistente Silva, Coordenação, etc.</small>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-warning" id="btnConfirmarAtendimentoSemDireito_${solicitacao.ipen}" onclick="confirmarAtendimentoSemDireito(${solicitacao.id}, '${solicitacao.ipen}')">
                                <i class="fas fa-exclamation-triangle"></i> Atender Mesmo Assim
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

        // Adicionar modal ao body
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer);

        // Mostrar modal
        const modalElement = document.getElementById(modalId);

        // Tentar criar com Bootstrap 5
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
        // Fallback para Bootstrap 4/3 ou jQuery
        else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(`#${modalId}`).modal('show');
        }
        // Fallback final - mostrar manualmente
        else {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');

            // Criar backdrop se não existir
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }

        // Focar no campo IPEN
        setTimeout(() => {
            document.getElementById(`confirmacao_ipen_atendimento_${solicitacao.ipen}`).focus();
        }, 500);

        // Remover modal do DOM quando for fechado
        document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
            modalContainer.remove();
        });
    }

    // Confirmar atendimento sem direito com validação do IPEN e autorização
    function confirmarAtendimentoSemDireito(id, ipenCorreto) {
        const ipenDigitado = document.getElementById(`confirmacao_ipen_atendimento_${ipenCorreto}`).value.trim();
        const autorizadoPor = document.getElementById(`autorizado_por_${ipenCorreto}`).value.trim();

        if (ipenDigitado !== ipenCorreto) {
            showAlert('IPEN incorreto! Digite o IPEN correto para confirmar o atendimento excepcional.', 'error');
            document.getElementById(`confirmacao_ipen_atendimento_${ipenCorreto}`).focus();
            document.getElementById(`confirmacao_ipen_atendimento_${ipenCorreto}`).select();
            return;
        }

        if (!autorizadoPor || autorizadoPor.length < 3) {
            showAlert('Campo "Autorizado por" é obrigatório! Informe quem autorizou este atendimento excepcional.', 'error');
            document.getElementById(`autorizado_por_${ipenCorreto}`).focus();
            return;
        }

        // Fechar modal
        const modalId = `modalConfirmacaoAtendimentoSemDireito_${ipenCorreto}`;
        const modalElement = document.getElementById(modalId);

        // Tentar fechar com Bootstrap 5
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.getInstance) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
                // Forçar remoção após fechar
                setTimeout(() => {
                    const modalContainer = modalElement.closest('div');
                    while (modalContainer && !modalContainer.classList.contains('modal')) {
                        modalContainer = modalContainer.parentElement;
                    }
                    if (modalContainer) {
                        modalContainer.remove();
                    }
                    // Remover backdrop que possa ter ficado
                    document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                }, 300);
            }
        }
        // Fallback para Bootstrap 4/3 ou jQuery
        else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(`#${modalId}`).modal('hide');
            // Forçar remoção após fechar
            setTimeout(() => {
                const modalContainer = modalElement.closest('div');
                while (modalContainer && !modalContainer.classList.contains('modal')) {
                    modalContainer = modalContainer.parentElement;
                }
                if (modalContainer) {
                    modalContainer.remove();
                }
                // Remover backdrop que possa ter ficado
                document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
            }, 300);
        }
        // Fallback final - remover manualmente
        else {
            const modalContainer = modalElement.closest('div');
            while (modalContainer && !modalContainer.classList.contains('modal')) {
                modalContainer = modalContainer.parentElement;
            }
            if (modalContainer) {
                modalContainer.remove();
            }
            // Remover backdrop
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
        }

        // Executar atendimento normal com informação de autorização
        executarAtendimentoNormal(id, autorizadoPor);
    }

    // Executar atendimento normal após confirmação
    function executarAtendimentoNormal(id, autorizadoPor) {
        // Buscar novamente os dados da solicitação
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacao&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Adicionar informação de autorização nas observações
                    const solicitacao = data.solicitacao;
                    const observacoesOriginais = document.getElementById('atender_observacoes').value.trim();
                    const observacoesComAutorizacao = observacoesOriginais ?
                        `${observacoesOriginais}\n\nATENDIMENTO EXCEPCIONAL AUTORIZADO POR: ${autorizadoPor}` :
                        `ATENDIMENTO EXCEPCIONAL AUTORIZADO POR: ${autorizadoPor}`;

                    document.getElementById('atender_observacoes').value = observacoesComAutorizacao;

                    prosseguirAtendimentoNormal(solicitacao);
                } else {
                    showAlert('Erro ao recarregar dados da solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao recarregar dados da solicitação.', 'error');
            });
    }

    // Prosseguir com atendimento normal
    function prosseguirAtendimentoNormal(solicitacao) {
        // Preencher campos do formulário
        document.getElementById('atender_id').value = solicitacao.id;
        document.getElementById('atender_ipen').value = solicitacao.ipen;
        document.getElementById('atender_nome').value = solicitacao.nome_interno;
        document.getElementById('atender_local').value = `${solicitacao.galeria}/${solicitacao.bloco || ''} ${solicitacao.res || ''}`;
        document.getElementById('atender_status').value = solicitacao.status_entrega;

        // Carregar locais disponíveis
        carregarLocaisParaEntrega();

        // Abrir offcanvas após carregar locais
        setTimeout(() => {
            abrirOffcanvasAtender();
        }, 100);
    }

    // Mostrar modal de confirmação de cancelamento
    function showModalConfirmacaoCancelamento(solicitacao) {
        // Criar ID único para este modal baseado no IPEN
        const modalId = `modalConfirmacaoCancelamento_${solicitacao.ipen}`;

        // Remover qualquer modal anterior que possa existir
        const modaisAnteriores = document.querySelectorAll('[id^="modalConfirmacaoCancelamento_"]');
        modaisAnteriores.forEach(modal => {
            const container = modal.closest('div');
            while (container && !container.classList.contains('modal')) {
                container = container.parentElement;
            }
            if (container) container.remove();
        });

        // Remover backdrops antigos
        document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());

        // Criar modal de confirmação de cancelamento
        const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle"></i>
                            Confirmar Cancelamento da Solicitação
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-info-circle"></i> Dados da Solicitação</h6>
                            <p class="mb-1"><strong>IPEN:</strong> ${solicitacao.ipen}</p>
                            <p class="mb-1"><strong>Nome:</strong> ${solicitacao.nome_interno}</p>
                            <p class="mb-1"><strong>Local:</strong> Galeria ${solicitacao.galeria} - Bloco ${solicitacao.bloco || ''} - Cela ${solicitacao.res || ''}</p>
                            <p class="mb-1"><strong>Status:</strong> ${solicitacao.status_solicitacao}</p>
                        </div>

                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Atenção</h6>
                            <p>Esta ação irá cancelar a solicitação permanentemente.</p>
                            ${solicitacao.status_solicitacao === 'Atendida' ? '<p><strong>Como a solicitação já foi atendida, o colchão será devolvido ao estoque.</strong></p>' : ''}
                        </div>

                        <div class="form-group">
                            <label for="confirmacao_ipen_${solicitacao.ipen}" class="form-label"><strong>Digite o IPEN para confirmar o cancelamento:</strong></label>
                            <input type="text" class="form-control" id="confirmacao_ipen_${solicitacao.ipen}" placeholder="Digite o IPEN: ${solicitacao.ipen}" maxlength="10">
                            <small class="form-text text-muted">Digite exatamente o IPEN para confirmar a operação</small>
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-danger" id="btnConfirmarCancelamento_${solicitacao.ipen}" onclick="confirmarCancelamentoComIPEN(${solicitacao.id}, '${solicitacao.ipen}')">
                                <i class="fas fa-times"></i> Cancelar Solicitação
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

        // Adicionar modal ao body
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer);

        // Mostrar modal
        const modalElement = document.getElementById(modalId);

        // Tentar criar com Bootstrap 5
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
        // Fallback para Bootstrap 4/3 ou jQuery
        else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(`#${modalId}`).modal('show');
        }
        // Fallback final - mostrar manualmente
        else {
            modalElement.style.display = 'block';
            modalElement.classList.add('show');
            document.body.classList.add('modal-open');

            // Criar backdrop se não existir
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
        }

        // Focar no campo IPEN
        setTimeout(() => {
            document.getElementById(`confirmacao_ipen_${solicitacao.ipen}`).focus();
        }, 500);

        // Remover modal do DOM quando for fechado
        document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
            modalContainer.remove();
        });
    }

    // Confirmar cancelamento com validação do IPEN
    function confirmarCancelamentoComIPEN(id, ipenCorreto) {
        const ipenDigitado = document.getElementById(`confirmacao_ipen_${ipenCorreto}`).value.trim();

        if (ipenDigitado !== ipenCorreto) {
            showAlert('IPEN incorreto! Digite o IPEN correto para confirmar o cancelamento.', 'error');
            document.getElementById(`confirmacao_ipen_${ipenCorreto}`).focus();
            document.getElementById(`confirmacao_ipen_${ipenCorreto}`).select();
            return;
        }

        // Fechar modal
        const modalId = `modalConfirmacaoCancelamento_${ipenCorreto}`;
        const modalElement = document.getElementById(modalId);

        // Tentar fechar com Bootstrap 5
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.getInstance) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
        }
        // Fallback para Bootstrap 4/3 ou jQuery
        else if (typeof $ !== 'undefined' && $.fn.modal) {
            $(`#${modalId}`).modal('hide');
        }
        // Fallback final - remover manualmente
        else {
            const modalContainer = modalElement.closest('div');
            while (modalContainer && !modalContainer.classList.contains('modal')) {
                modalContainer = modalContainer.parentElement;
            }
            if (modalContainer) {
                modalContainer.remove();
            }
            // Remover backdrop
            document.querySelectorAll('.modal-backdrop').forEach(backdrop => backdrop.remove());
            document.body.classList.remove('modal-open');
        }

        // Executar cancelamento
        executarCancelamento(id);
    }

    // Executar o cancelamento
    function executarCancelamento(id) {
        fetch('/includes/internos_colchoes_solicitacoes_logica.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'cancelar_solicitacao',
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Solicitação cancelada com sucesso!', 'success');
                    carregarSolicitacoes();
                } else {
                    showAlert(data.message || 'Erro ao cancelar solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao cancelar solicitação.', 'error');
            });
    }

    // Funções do Offcanvas de Atendimento
    function abrirOffcanvasAtender() {
        document.getElementById('offcanvasBackdropAtender').style.display = 'block';
        document.getElementById('offcanvasAtenderSolicitacao').style.transform = 'translateX(0)';
    }

    function fecharOffcanvasAtender() {
        document.getElementById('offcanvasBackdropAtender').style.display = 'none';
        document.getElementById('offcanvasAtenderSolicitacao').style.transform = 'translateX(100%)';
    }

    // Confirmar atendimento
    function confirmarAtendimento() {
        const id = document.getElementById('atender_id').value;
        const idLocal = document.getElementById('atender_local_entrega').value;
        const observacoes = document.getElementById('atender_observacoes').value;

        if (!idLocal) {
            showAlert('Selecione o local de retirada do colchão.', 'error');
            return;
        }

        // Verificar se interno já recebeu colchão este ano
        const ipen = document.getElementById('atender_ipen').value;
        const nome = document.getElementById('atender_nome').value;

        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=verificar_entrega_anual&ipen=${ipen}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.ja_recebeu) {
                    // Interno já recebeu colchão este ano - mostrar SweetAlert de confirmação
                    Swal.fire({
                        title: '⚠️ Atenção - Entrega Duplicada',
                        html: `
                        <div class="text-left">
                            <p><strong>O interno <span class="text-primary">${nome}</span> com IPEN <span class="text-primary">${ipen}</span> já recebeu colchão em <span class="text-warning">${data.data_ultima_entrega}</span>.</strong></p>
                            <p class="mb-3">Deseja realmente entregar um novo colchão para ele?</p>
                            <div class="form-group">
                                <label for="nome_autorizacao" class="form-label"><strong>Quem está autorizando esta entrega?</strong></label>
                                <input type="text" id="nome_autorizacao" class="form-control" placeholder="Digite o nome do responsável pela autorização" required>
                            </div>
                        </div>
                    `,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        confirmButtonText: '<i class="fas fa-check"></i> Sim, entregar mesmo assim',
                        cancelButtonText: '<i class="fas fa-times"></i> Não, cancelar',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            const nomeAutorizacao = document.getElementById('nome_autorizacao').value.trim();
                            if (!nomeAutorizacao) {
                                Swal.showValidationMessage('Por favor, informe o nome do responsável pela autorização');
                                return false;
                            }
                            return {
                                nome_autorizacao: nomeAutorizacao
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Prosseguir com atendimento incluindo autorização
                            executarAtendimento(id, idLocal, observacoes, result.value.nome_autorizacao);
                        }
                    });
                } else {
                    // Interno não recebeu colchão este ano - prosseguir normalmente
                    executarAtendimento(id, idLocal, observacoes);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                // Em caso de erro na verificação, prosseguir normalmente
                executarAtendimento(id, idLocal, observacoes);
            });
    }

    // Executar atendimento efetivamente
    function executarAtendimento(id, idLocal, observacoes, nomeAutorizacao = null) {
        const formData = new FormData();
        formData.append('action', 'atender_solicitacao');
        formData.append('id', id);
        formData.append('id_local_origem', idLocal);
        formData.append('observacoes', observacoes);

        if (nomeAutorizacao) {
            formData.append('nome_autorizacao', nomeAutorizacao);
        }

        fetch('/includes/internos_colchoes_solicitacoes_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Solicitação atendida com sucesso!', 'success');
                    fecharOffcanvasAtender();
                    carregarSolicitacoes();
                } else {
                    showAlert(data.message || 'Erro ao atender solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao atender solicitação.', 'error');
            });
    }

    // Cancelar solicitação
    function cancelarSolicitacao(id) {
        // Buscar dados da solicitação
        fetch(`/includes/internos_colchoes_solicitacoes_logica.php?action=get_solicitacao&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const solicitacao = data.solicitacao;
                    showModalConfirmacaoCancelamento(solicitacao);
                } else {
                    showAlert('Erro ao carregar dados da solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao carregar dados da solicitação.', 'error');
            });
    }

    // Formulário de solicitação
    document.getElementById('form-solicitacao').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'criar_solicitacao');

        fetch('/includes/internos_colchoes_solicitacoes_logica.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Solicitação cadastrada com sucesso!', 'success');
                    limparFormulario();
                    carregarSolicitacoes();
                } else {
                    showAlert(data.message || 'Erro ao cadastrar solicitação.', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao cadastrar solicitação.', 'error');
            });
    });

    // Função para mostrar alertas
    function showAlert(message, type) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;

        const firstCard = document.querySelector('.card');
        if (firstCard) {
            firstCard.insertAdjacentHTML('beforebegin', alertHtml);
        }

        setTimeout(() => {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
</script>
