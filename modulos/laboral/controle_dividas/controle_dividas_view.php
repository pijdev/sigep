<?php
require_once __DIR__ . '/controle_dividas_logica.php';
?>

<script>
    // Definir informações da página para o SPA do SIGEP
    window.currentPage = 'controle_dividas_view.php';
    window.pageTitle = 'Controle de Dívidas';
    window.breadcrumbParent = 'Financeiro';

    // Atualizar breadcrumb para multinível
    document.addEventListener('DOMContentLoaded', function() {
        const breadcrumbParent = document.getElementById('breadcrumb-parent');
        const breadcrumbTitle = document.getElementById('breadcrumb-title');
        const contentMainTitle = document.getElementById('content-main-title');

        if (breadcrumbParent) breadcrumbParent.innerText = 'Financeiro';
        if (breadcrumbTitle) breadcrumbTitle.innerText = 'Controle de Dívidas';
        if (contentMainTitle) contentMainTitle.innerText = 'Controle de Dívidas';

        console.log('[BREADCRUMB] Multinível atualizado: Laboral / Financeiro / Controle de Dívidas');
    });
</script>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo KPI -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info pointer" onclick="abrirModalKPI('total_ativas')">
                    <div class="inner">
                        <h3 id="stats-total">0</h3>
                        <p>Cadastros de Dívidas Ativas</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success pointer" onclick="abrirModalKPI('arrecadado_mes')">
                    <div class="inner">
                        <h3 id="stats-arrecadado">R$ 0,00</h3>
                        <p>Total Descontado neste Mês</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning pointer" onclick="abrirModalKPI('pendentes')">
                    <div class="inner">
                        <h3 id="stats-pendentes">0</h3>
                        <p>Dívidas Pendentes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger pointer" onclick="abrirModalKPI('inadimplentes')">
                    <div class="inner">
                        <h3 id="stats-inadimplentes">0</h3>
                        <p>Internos Inadimplentes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-filter mr-2"></i>
                            Filtros e Busca
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="form-filtros">
                            <!-- Campos ocultos para manter ordenação no relatório -->
                            <input type="hidden" id="sort-by" value="data_cadastro">
                            <input type="hidden" id="sort-order" value="DESC">

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="busca">Buscar Interno</label>
                                        <input type="text" class="form-control" id="busca" placeholder="Nome, IPEN ou CPF...">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filtro-status-detalhado">Status</label>
                                        <select class="form-control" id="filtro-status-detalhado">
                                            <option value="">Todos</option>
                                            <option value="Pendente">Pendente</option>
                                            <option value="Ativa">Ativa</option>
                                            <option value="Suspensa">Suspensa</option>
                                            <option value="Quitada">Quitada</option>
                                            <option value="Inativa">Inativa</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filtro-status">Status Interno</label>
                                        <select class="form-control" id="filtro-status">
                                            <option value="">Todos</option>
                                            <option value="A">Ativo</option>
                                            <option value="I">Inativo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="filtro-tipo">Tipo Dívida</label>
                                        <select class="form-control" id="filtro-tipo">
                                            <option value="">Todos</option>
                                            <option value="Pensão">Pensão</option>
                                            <option value="Multa">Multa</option>
                                            <option value="Indenização">Indenização</option>
                                            <option value="Indenização">Outros</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>&nbsp;</label><br>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="mostrar-inativos">
                                            <label class="form-check-label" for="mostrar-inativos">
                                                Mostrar Internos Inativos
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="button" class="btn btn-primary" onclick="aplicarFiltros()">
                                        <i class="fas fa-search mr-2"></i>Aplicar Filtros
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                                        <i class="fas fa-eraser mr-2"></i>Limpar
                                    </button>
                                    <button type="button" class="btn btn-success float-right" onclick="abrirModalCadastro()">
                                        <i class="fas fa-plus mr-2"></i>Cadastrar Nova Dívida
                                    </button>
                                    <button type="button" class="btn btn-info float-right mr-2" onclick="imprimirRelatorio()">
                                        <i class="fas fa-print mr-2"></i>Imprimir Relatório (Tabela Atual)
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Resultados -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            Dívidas Cadastradas
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="tabela-dividas">
                                <thead>
                                    <tr>
                                        <th style="width: 180px;">Interno</th>
                                        <th style="width: 80px;">IPEN</th>
                                        <th style="width: 120px;">CPF</th>
                                        <th style="width: 100px;">Tipo Dívida</th>
                                        <th style="width: 100px;">Valor Total / Total Descontado</th>
                                        <th style="width: 80px;">% Desc.</th>
                                        <th style="width: 100px;">Saldo / Total Pago</th>
                                        <th style="width: 90px;">Nº Autos</th>
                                        <th style="width: 80px;">Status Interno</th>
                                        <th style="width: 110px;">Status Dívida</th>
                                        <th style="width: 120px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-corpo">
                                    <tr>
                                        <td colspan="11" class="text-center">
                                            <i class="fas fa-spinner fa-spin"></i> Carregando dados...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-7">
                                <div class="dataTables_info" id="tabela-info">
                                    Mostrando 0 de 0 registros
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_paginate" id="tabela-paginacao">
                                    <!-- Paginação será inserida aqui -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal Cadastro/Edição -->
<div class="modal fade" id="modal-cadastro" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modal-titulo">Cadastrar Nova Dívida</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-cadastro">
                    <input type="hidden" id="multa-id">
                    <input type="hidden" id="interno-id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="busca-interno">Buscar Interno <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="busca-interno" placeholder="Digite nome, IPEN ou CPF...">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-primary" onclick="buscarInterno()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Busque por Nome, IPEN ou Nome Social</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Interno Selecionado</label>
                                <div class="alert alert-info" id="interno-selecionado">
                                    <i class="fas fa-user"></i> Nenhum interno selecionado
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cpf">CPF do Interno (opcional)</label>
                                <input type="text" class="form-control" id="cpf" placeholder="Digite o CPF (XXX.XXX.XXX-XX)" maxlength="14">
                                <small class="form-text text-muted">Digite o CPF do interno se ainda não estiver cadastrado</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="autos">Número dos Autos (opcional)</label>
                                <input type="text" class="form-control" id="autos">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tipo-divida">Tipo de Dívida <span class="text-danger">*</span></label>
                                <select class="form-control" id="tipo-divida" required>
                                    <option value="">Selecione...</option>
                                    <option value="Pensão">Pensão</option>
                                    <option value="Multa">Multa</option>
                                    <option value="Indenização">Indenização</option>
                                    <option value="Outros">Outros</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4" id="campo-valor-divida">
                            <div class="form-group">
                                <label for="valor-divida">Valor da Dívida <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">R$</span>
                                    </div>
                                    <input type="text" class="form-control money" id="valor-divida" placeholder="0,00" required>
                                </div>
                                <small class="form-text text-muted" id="ajuda-valor-divida">
                                    Para Pensão, este campo não é utilizado
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="percentual-desconto">% Desconto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="percentual-desconto" min="0" max="100" step="0.01" value="25.00" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Percentual de desconto sobre o salário (padrão: 25%)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Campos extras para Pensão Alimentícia -->
                    <div class="row" id="campos-pensao" style="display: none; background: #f8f9fa; padding: 15px; margin: 0 -15px 15px -15px; border-left: 4px solid #007bff;">
                        <div class="col-12 mb-2">
                            <h6 class="text-primary font-weight-bold">
                                <i class="fas fa-money-check-alt mr-2"></i>Dados Bancários do Favorecido
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pensao-favorecido">Favorecido (Nome) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pensao-favorecido" placeholder="Nome completo do favorecido">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="pensao-banco">Banco <span class="text-danger">*</span></label>
                                <select class="form-control" id="pensao-banco">
                                    <option value="">Carregando bancos...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="pensao-agencia">Agência <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pensao-agencia" placeholder="0000">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="pensao-conta">Conta <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pensao-conta" placeholder="000000-0">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="pensao-op">Op</label>
                                <input type="text" class="form-control" id="pensao-op" placeholder="Opcional">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="pensao-tipo-conta">Tipo de Conta</label>
                                <select class="form-control" id="pensao-tipo-conta">
                                    <option value="Corrente">Corrente</option>
                                    <option value="Poupança">Poupança</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label for="pensao-determinacao">Determinação <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="pensao-determinacao" rows="4" placeholder="Descreva a determinação judicial ou acordo..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="status-detalhado">Status Detalhado</label>
                                <select class="form-control" id="status-detalhado">
                                    <option value="Pendente">Pendente</option>
                                    <option value="Ativa">Ativa</option>
                                    <option value="Suspensa">Suspensa</option>
                                    <option value="Quitada">Quitada</option>
                                    <option value="Inativa">Inativa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarDívida()">
                    <i class="fas fa-save mr-2"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lançamento Mensal -->
<div class="modal fade" id="modal-lancamento" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Lançar Salário do Mês</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-lancamento">
                    <input type="hidden" id="lancamento-divida-id">
                    <input type="hidden" id="lancamento-ipen">

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Interno</label>
                                <div class="alert alert-info" id="lancamento-interno">
                                    <i class="fas fa-user"></i> <span id="lancamento-nome"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="mes-referência">Mês de Referência <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="mes-referencia" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="salario-real">Salário Real (SAGEP) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control money" id="salario-real" placeholder="R$ 0,00" required>
                                <small class="form-text text-muted">Consulte o valor no SAGEP e insira aqui</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-warning">
                                <i class="fas fa-calculator"></i>
                                <strong>Cálculo Automático:</strong>
                                <span id="calculo-preview">Salário: R$ 0,00 × Desconto: 25% = R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="lancarSalario()">
                    <i class="fas fa-money-bill-wave mr-2"></i>Lançar Desconto
                </button>
            </div>
        </div>
    </div>
</div>


<!-- Scripts -->
<script src="modulos/laboral/controle_dividas/assets/js/controle_dividas.js"></script>
