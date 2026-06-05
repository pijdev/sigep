<?php
require_once __DIR__ . '/inicio_logica.php';
?>

<div class="content-wrapper">

    <!-- HEADER -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold" id="content-main-title">Início</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right mb-0">
                        <li class="breadcrumb-item"><a href="/" id="breadcrumb-parent">SIGEP</a></li>
                        <li class="breadcrumb-item active" id="breadcrumb-title">Bem-vindo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- CONTENT -->
    <section class="content">
        <div class="container-fluid" id="main-content">

            <!-- SMALL BOXES -->
            <div class="row mb-4">

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-primary elevation-1">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['total_ativos'], 0, ',', '.') ?></h3>
                            <p class="mb-0">Total ativos</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <a href="#" class="small-box-footer"
                           onclick="loadPage('/paginas/internos_painel.php','Painel de Internos','TI');return false;">
                            Abrir painel <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning elevation-1">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['saida_temporaria'], 0, ',', '.') ?></h3>
                            <p class="mb-0">Saída temporária</p>
                        </div>
                        <div class="icon"><i class="fas fa-suitcase-rolling"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info elevation-1">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['trabalho_interno'], 0, ',', '.') ?></h3>
                            <p class="mb-0">Trabalho interno</p>
                        </div>
                        <div class="icon"><i class="fas fa-hammer"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success elevation-1">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['remicao_ativa'], 0, ',', '.') ?></h3>
                            <p class="mb-0">Remição (laboral)</p>
                        </div>
                        <div class="icon"><i class="fas fa-industry"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-danger elevation-1">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['regalias'], 0, ',', '.') ?></h3>
                            <p class="mb-0">Regalia</p>
                        </div>
                        <div class="icon"><i class="fas fa-crown"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-secondary elevation-1">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['ctc_ativo'], 0, ',', '.') ?></h3>
                            <p class="mb-0">CTC ativo</p>
                        </div>
                        <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

            </div>

            <!-- BUSCA + ATALHOS -->
            <div class="row mb-4">

                <!-- BUSCA -->
                <div class="col-lg-7">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search mr-1"></i> Busca rápida de interno
                            </h3>
                        </div>

                        <div class="card-body">

                            <label class="small text-muted mb-1">
                                Pesquise por IPEN, nome, nome social ou apelido
                            </label>

                            <div class="input-group input-group-sm mb-2">
                                <input type="text"
                                       class="form-control"
                                       id="inicioBuscaInterno"
                                       autocomplete="off"
                                       placeholder="Digite para buscar...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary"
                                            id="inicioBtnDossie"
                                            disabled>
                                        <i class="fas fa-search mr-1"></i> Procurar
                                    </button>
                                </div>
                            </div>

                            <div id="inicioBuscaResultados"
                                 class="list-group shadow-sm"
                                 style="max-height:250px;overflow-y:auto;"></div>

                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted" id="inicioBuscaHint">Digite ao menos 2 caracteres.</small>
                                <small class="text-muted" id="inicioBuscaSelecionado"></small>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- ATALHOS -->
                <div class="col-lg-5">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt mr-1"></i> Atalhos de internos
                            </h3>
                        </div>

                        <div class="card-body">

                            <div class="btn-group-vertical w-100">

                                <a class="btn btn-outline-secondary text-left"
                                   href="#"
                                   onclick="loadPage('/paginas/internos_eletronicos_gestao.php','Eletrônicos','Censura');return false;">
                                    <i class="fas fa-tv mr-2 text-info"></i> Gestão de eletrônicos
                                </a>

                                <a class="btn btn-outline-secondary text-left"
                                   href="#"
                                   onclick="loadPage('/paginas/internos_recebimento_livros.php','Recebimento de Livros','Censura');return false;">
                                    <i class="fas fa-book mr-2 text-info"></i> Recebimento de livros
                                </a>

                                <a class="btn btn-outline-secondary text-left"
                                   href="#"
                                   onclick="loadPage('/paginas/internos_entrega_kits.php','Entrega de Kits','Censura');return false;">
                                    <i class="fas fa-box-open mr-2 text-warning"></i> Entrega de kits
                                </a>

                                <a class="btn btn-outline-secondary text-left"
                                   href="#"
                                   onclick="loadPage('/paginas/internos_md_cadastro.php','Medidas Disciplinares','Coordenação');return false;">
                                    <i class="fas fa-gavel mr-2 text-danger"></i> Medidas disciplinares
                                </a>

                            </div>

                            <hr>

                            <p class="small text-muted mb-0">
                                O dossiê reúne informações completas do interno em tempo real.
                            </p>

                        </div>
                    </div>
                </div>

            </div>

            <!-- ANALYTICS -->
            <div class="row">

                <div class="col-lg-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-pie mr-1"></i> Internos por situação</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="inicioChartSituacao"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline card-info">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tv mr-1"></i> Eletrônicos na cela</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="inicioChartEletronicos"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline card-secondary">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-sun mr-1"></i> Hoje</h3>
                        </div>

                        <div class="card-body">

                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <small class="text-muted">Escoltas</small>
                                    <div class="h5 mb-0"><?= (int)$inicioHoje['escoltas_total'] ?></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Eclusa</small>
                                    <div class="h5 mb-0"><?= (int)$inicioHoje['eclusa_total'] ?></div>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted">Cartas</small>
                                    <div class="h5 mb-0"><?= (int)$inicioHoje['cartas_total'] ?></div>
                                </div>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Entradas</span>
                                <span class="font-weight-bold"><?= (int)$inicioHoje['eclusa_entradas'] ?></span>
                            </div>

                            <div class="d-flex justify-content-between small mb-3">
                                <span class="text-muted">Saídas</span>
                                <span class="font-weight-bold"><?= (int)$inicioHoje['eclusa_saidas'] ?></span>
                            </div>

                            <a class="btn btn-outline-secondary btn-sm btn-block"
                               href="#"
                               onclick="loadPage('/modulos/eclusa/escolta/escolta_view.php','Escoltas','Eclusa');return false;">
                                <i class="fas fa-shield-alt mr-1"></i> Abrir módulo de escoltas
                            </a>

                        </div>
                    </div>
                </div>

            </div>

        </div>
    </section>
</div>

<!-- MODAL -->
<div class="modal fade" id="modalDossieInterno" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-id-badge mr-1"></i> Dossiê do interno
                </h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>

            <div class="modal-body text-center text-muted p-5" id="inicioDossieBody">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <div>Carregando...</div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-primary" id="inicioBtnImprimirDossie" disabled>
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
                <button class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>

        </div>
    </div>
</div>

<div id="modalContainerDoacoes"></div>

<script>
window.sigepInicioChartsData = <?= json_encode([
    'charts' => $inicioChartsData,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>