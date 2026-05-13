<?php
require_once __DIR__ . '/canteiros_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Canteiros de Trabalho</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Laboral</a></li>
            <li class="breadcrumb-item active">Canteiros</li>
        </ol>
        <div class="float-sm-right mt-1">
            <small class="text-muted">
                <i class="fas fa-sync-alt fa-spin mr-1" id="loading-indicator" style="display: none;"></i>
                <span id="last-update">Atualizado agora</span>
            </small>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo -->
        <div class="row mb-3 no-print">
            <div class="col-lg-3 col-6">
                <div class="info-box bg-info">
                    <div class="info-box-icon">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Canteiros</span>
                        <span class="info-box-number" id="stats-total-canteiros">0</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-success">
                    <div class="info-box-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Canteiros Ativos</span>
                        <span class="info-box-number" id="stats-canteiros-ativos">0</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-warning">
                    <div class="info-box-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Canteiros Vazios</span>
                        <span class="info-box-number" id="stats-canteiros-vazios">0</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-primary">
                    <div class="info-box-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Internos Trabalhando</span>
                        <span class="info-box-number" id="stats-internos-trabalhando">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Segunda linha de cards -->
        <div class="row mb-3 no-print">
            <div class="col-lg-3 col-6">
                <div class="info-box bg-purple">
                    <div class="info-box-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Regalias</span>
                        <span class="info-box-number" id="stats-total-regalias">0</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-teal">
                    <div class="info-box-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Conveniados</span>
                        <span class="info-box-number" id="stats-conveniados">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros Rápidos -->
        <div class="row mb-3 no-print">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-filter mr-2"></i>
                            Filtros Rápidos
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-galeria">Galeria</label>
                                    <select class="form-control" id="filtro-galeria">
                                        <option value="">Todas as Galerias</option>
                                        <option value="A">Galeria A</option>
                                        <option value="B">Galeria B</option>
                                        <option value="C">Galeria C</option>
                                        <option value="D">Galeria D</option>
                                        <option value="E">Galeria E</option>
                                        <option value="F">Galeria F</option>
                                        <option value="G">Galeria G</option>
                                        <option value="H">Galeria H</option>
                                        <option value="S">Semiaberto</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-canteiro">Canteiro</label>
                                    <select class="form-control" id="filtro-canteiro">
                                        <option value="">Todos os Canteiros</option>
                                        <option value="CISER">CISER</option>
                                        <option value="TUTTI">Tutti Baby</option>
                                        <option value="TIGRE">Tigre</option>
                                        <option value="PLASBOHN">Plasbohn</option>
                                        <option value="COZINHA">Cozinha H</option>
                                        <option value="CARGA">Carga e Descarga</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-status">Status</label>
                                    <select class="form-control" id="filtro-status">
                                        <option value="">Todos os Status</option>
                                        <option value="ativos">Apenas Ativos</option>
                                        <option value="vazios">Apenas Vazios</option>
                                        <option value="regalia">Apenas Regalias</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-primary" onclick="aplicarFiltros()">
                                        <i class="fas fa-search mr-2"></i>Aplicar Filtros
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                                        <i class="fas fa-times mr-2"></i>Limpar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mapa de Galerias -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-map mr-2"></i>
                            Mapa de Galerias e Canteiros
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="mapa-galerias">
                            <!-- Galeria A -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>Galeria A
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="small-box bg-gray" onclick="verDetalhesCanteiro('AA')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">AA</h3>
                                                        <p>Vazio</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="small-box bg-gray" onclick="verDetalhesCanteiro('AB')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">AB</h3>
                                                        <p>Vazio</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galeria B -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>Galeria B
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="small-box bg-success" onclick="verDetalhesCanteiro('BA')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">BA</h3>
                                                        <p>Tutti Baby</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="small-box bg-gray" onclick="verDetalhesCanteiro('BB')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">BB</h3>
                                                        <p>Vazio</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galeria C -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>Galeria C
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="small-box bg-gray" onclick="verDetalhesCanteiro('CA')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">CA</h3>
                                                        <p>Vazio</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="small-box bg-gray" onclick="verDetalhesCanteiro('CB')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">CB</h3>
                                                        <p>Vazio</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-times-circle text-danger"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galeria D -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>Galeria D
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="small-box bg-success" onclick="verDetalhesCanteiro('DA')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">DA</h3>
                                                        <p>Plasbohn D8</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="small-box bg-success" onclick="verDetalhesCanteiro('DB')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">DB</h3>
                                                        <p>Tutti Baby 02</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galeria E -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>Galeria E
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="small-box bg-success" onclick="verDetalhesCanteiro('EA')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">EA</h3>
                                                        <p>Plasbohn</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-check-circle text-success"></i>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="small-box bg-gray" onclick="verDetalhesCanteiro('EB')">
                                                    <div class="inner">
                                                        <h3 class="text-bold">EB</h3>
                                                        <p>Inexistente</p>
                                                    </div>
                                                    <div class="icon">
                                                        <i class="fas fa-ban text-muted"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Área Industrial -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-industry mr-2"></i>Área Industrial
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="small-box bg-success" onclick="verDetalhesCanteiro('CISER')">
                                                <div class="inner">
                                                    <h3 class="text-bold">CISER</h3>
                                                    <p>Indústria</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="small-box bg-success" onclick="verDetalhesCanteiro('TUTTI 05')">
                                                <div class="inner">
                                                    <h3 class="text-bold">Tutti 05</h3>
                                                    <p>Indústria</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="small-box bg-success" onclick="verDetalhesCanteiro('TIGRE 01')">
                                                <div class="inner">
                                                    <h3 class="text-bold">Tigre 01</h3>
                                                    <p>Indústria</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <div class="small-box bg-success" onclick="verDetalhesCanteiro('TIGRE 02')">
                                                <div class="inner">
                                                    <h3 class="text-bold">Tigre 02</h3>
                                                    <p>Indústria</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fas fa-check-circle text-success"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Galeria H -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-building mr-2"></i>Galeria H
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="small-box bg-success" onclick="verDetalhesCanteiro('COZINHA H')">
                                            <div class="inner">
                                                <h3 class="text-bold">Cozinha H</h3>
                                                <p>Soluções Alimentação</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-check-circle text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Semiaberto -->
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card card-outline card-secondary">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-door-open mr-2"></i>Semiaberto
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="small-box bg-success" onclick="verDetalhesCanteiro('TIGRE S')">
                                            <div class="inner">
                                                <h3 class="text-bold">Tigre S</h3>
                                                <p>Tigre Semiaberto</p>
                                            </div>
                                            <div class="icon">
                                                <i class="fas fa-check-circle text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gestão de Canteiros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            Gestão de Canteiros
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" onclick="recarregarListaCanteiros()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div class="btn-group">
                                <button type="button" class="btn btn-tool btn-sm dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-download"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" onclick="exportarListaCanteirosCSV()">
                                        <i class="fas fa-file-csv mr-2"></i>Exportar CSV
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="exportarExcel('canteiros')">
                                        <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros da lista -->
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="busca-canteiro">Buscar Canteiro</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="busca-canteiro" placeholder="Digite o nome do canteiro...">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" onclick="buscarCanteiros()">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-status-lista">Status</label>
                                    <select class="form-control" id="filtro-status-lista" onchange="filtrarListaCanteiros()">
                                        <option value="">Todos</option>
                                        <option value="ativos">Ativos</option>
                                        <option value="vazios">Vazios</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="ordenacao-lista">Ordenar por</label>
                                    <select class="form-control" id="ordenacao-lista" onchange="ordenarListaCanteiros()">
                                        <option value="nome">Nome</option>
                                        <option value="internos">Qtd. Internos</option>
                                        <option value="status">Status</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-success btn-sm" onclick="exportarListaCanteiros()">
                                        <i class="fas fa-download mr-1"></i>Exportar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela de Canteiros -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tabela-canteiros">
                                <thead>
                                    <tr>
                                        <th>Canteiro</th>
                                        <th>Empresa</th>
                                        <th>Status</th>
                                        <th>Internos</th>
                                        <th>Galerias</th>
                                        <th>Turnos</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="corpo-tabela-canteiros">
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Carregando...</span>
                                            </div>
                                            <p class="mt-2">Carregando lista de canteiros...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="dataTables_info">
                                    Mostrando <span id="registro-inicio">0</span> a <span id="registro-fim">0</span> de <span id="registro-total">0</span> canteiros
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <ul class="pagination" id="paginacao-canteiros">
                                        <!-- Paginação será gerada dinamicamente -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gestão de Internos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-users mr-2"></i>
                            Gestão de Internos
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" onclick="recarregarListaInternos()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                            <div class="btn-group">
                                <button type="button" class="btn btn-tool btn-sm dropdown-toggle" data-toggle="dropdown">
                                    <i class="fas fa-download"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a class="dropdown-item" href="#" onclick="exportarListaInternosCSV()">
                                        <i class="fas fa-file-csv mr-2"></i>Exportar CSV
                                    </a>
                                    <a class="dropdown-item" href="#" onclick="exportarExcel('internos')">
                                        <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros avançados de internos -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="busca-interno">Buscar Interno</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="busca-interno" placeholder="Nome, IPEN ou galeria...">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button" onclick="buscarInternos()">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filtro-galeria-internos">Galeria</label>
                                    <select class="form-control" id="filtro-galeria-internos" onchange="filtrarListaInternos()">
                                        <option value="">Todas</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="E">E</option>
                                        <option value="F">F</option>
                                        <option value="G">G</option>
                                        <option value="H">H</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filtro-bloco">Bloco</label>
                                    <select class="form-control" id="filtro-bloco" onchange="filtrarListaInternos()">
                                        <option value="">Todos</option>
                                        <option value="A">A</option>
                                        <option value="B">B</option>
                                        <option value="C">C</option>
                                        <option value="D">D</option>
                                        <option value="E">E</option>
                                        <option value="F">F</option>
                                        <option value="G">G</option>
                                        <option value="H">H</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="filtro-regalia">Regalia</label>
                                    <select class="form-control" id="filtro-regalia" onchange="filtrarListaInternos()">
                                        <option value="">Todos</option>
                                        <option value="S">Com Regalia</option>
                                        <option value="N">Sem Regalia</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-empresa">Empresa</label>
                                    <select class="form-control" id="filtro-empresa" onchange="filtrarListaInternos()">
                                        <option value="">Todas</option>
                                        <option value="CISER">CISER</option>
                                        <option value="TUTTI">Tutti Baby</option>
                                        <option value="TIGRE">Tigre</option>
                                        <option value="PLASBOHN">Plasbohn</option>
                                        <option value="COZINHA">Cozinha H</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tabela de Internos -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tabela-internos">
                                <thead>
                                    <tr>
                                        <th>IPEN</th>
                                        <th>Nome</th>
                                        <th>Galeria</th>
                                        <th>Empresa</th>
                                        <th>Turno</th>
                                        <th>Regalia</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="corpo-tabela-internos">
                                    <tr>
                                        <td colspan="8" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Carregando...</span>
                                            </div>
                                            <p class="mt-2">Carregando lista de internos...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginação Internos -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="dataTables_info">
                                    Mostrando <span id="interno-inicio">0</span> a <span id="interno-fim">0</span> de <span id="interno-total">0</span> internos
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="dataTables_paginate paging_simple_numbers">
                                    <ul class="pagination" id="paginacao-internos">
                                        <!-- Paginação será gerada dinamicamente -->
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Dashboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-line mr-2"></i>
                            Analytics Dashboard
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" onclick="atualizarGraficos()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filtros do Analytics -->
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="periodo-analytics">Período</label>
                                    <select class="form-control" id="periodo-analytics" onchange="atualizarGraficos()">
                                        <option value="7">Últimos 7 dias</option>
                                        <option value="30" selected>Últimos 30 dias</option>
                                        <option value="90">Últimos 90 dias</option>
                                        <option value="365">Último ano</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo-grafico">Tipo de Análise</label>
                                    <select class="form-control" id="tipo-grafico" onchange="atualizarGraficos()">
                                        <option value="ocupacao">Ocupação</option>
                                        <option value="empresas">Empresas</option>
                                        <option value="regalias">Regalias</option>
                                        <option value="turnos">Turnos</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="agrupamento">Agrupamento</label>
                                    <select class="form-control" id="agrupamento" onchange="atualizarGraficos()">
                                        <option value="diario">Diário</option>
                                        <option value="semanal">Semanal</option>
                                        <option value="mensal">Mensal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-success btn-sm" onclick="exportarTodosGraficos()">
                                            <i class="fas fa-image mr-1"></i>Gráficos
                                        </button>
                                        <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="#" onclick="exportarGraficoComoImagem('graficoEmpresas')">
                                                <i class="fas fa-chart-pie mr-2"></i>Empresas
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="exportarGraficoComoImagem('graficoGalerias')">
                                                <i class="fas fa-chart-bar mr-2"></i>Galerias
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="exportarGraficoComoImagem('graficoEvolucao')">
                                                <i class="fas fa-chart-line mr-2"></i>Evolução
                                            </a>
                                            <a class="dropdown-item" href="#" onclick="exportarGraficoComoImagem('graficoRegalias')">
                                                <i class="fas fa-chart-pie mr-2"></i>Regalias
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grid de Gráficos -->
                        <div class="row">
                            <!-- Gráfico de Pizza - Distribuição por Empresa -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-pie mr-2"></i>
                                            Distribuição por Empresa
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="graficoEmpresas" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Barras - Ocupação por Galeria -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-bar mr-2"></i>
                                            Ocupação por Galeria
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="graficoGalerias" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Linhas - Evolução Temporal -->
                            <div class="col-md-8 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-line mr-2"></i>
                                            Evolução Temporal
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="graficoEvolucao" width="800" height="300"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Gráfico de Donut - Regalias -->
                            <div class="col-md-4 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-chart-pie mr-2"></i>
                                            Regalias vs Sem Regalia
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="graficoRegalias" width="300" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KPIs Analytics -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-info"><i class="fas fa-chart-line"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Taxa de Ocupação</span>
                                        <span class="info-box-number" id="kpitaxaOcupacao">0%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-success"><i class="fas fa-trending-up"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Crescimento Mensal</span>
                                        <span class="info-box-number" id="kpicrescimento">0%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-warning"><i class="fas fa-users"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Média por Canteiro</span>
                                        <span class="info-box-number" id="kpimediaCanteiro">0</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="info-box">
                                    <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text">Canteiros Críticos</span>
                                        <span class="info-box-number" id="kpicanteirosCriticos">0</span>
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

<!-- Modal Detalhes do Canteiro -->
<div class="modal fade" id="modalDetalhesCanteiro" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalhes do Canteiro</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="conteudo-detalhes-canteiro">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Turnos do Canteiro -->
<div class="modal fade" id="modalTurnosCanteiro" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Turnos do Canteiro</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="conteudo-turnos-canteiro">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ficha do Interno -->
<div class="modal fade" id="modalFichaInterno" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Ficha Completa do Interno</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="conteudo-ficha-interno">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="/modulos/laboral/canteiros/assets/js/canteiros.js?v=<?= time() ?>"></script>
