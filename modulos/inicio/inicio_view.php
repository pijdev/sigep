<?php
// modulos/inicio/inicio_view.php
// View do módulo início - HTML puro (MVC)

require_once __DIR__ . '/inicio_logica.php';
//include_once __DIR__ . '/../../includes/header.php';
include_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- CSS específico do módulo início -->
<link rel="stylesheet" href="/modulos/inicio/assets/css/inicio.css?v=<?= time() ?>">

<div class="content-wrapper">
    <div class="content-header px-4">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-bold" id="content-main-title">Início</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/" id="breadcrumb-parent">SIGEP</a></li>
                        <li class="breadcrumb-item active" id="breadcrumb-title">Bem-vindo</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- Elementos usados pelo SPA (loadPage) - mantidos invisíveis no Início -->
    <section class="content px-4">
        <div class="container-fluid" id="main-content">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="row">
                    </div>
                </div>
            </div>

            <!-- Internos: Contadores -->
            <div class="row mb-4">
                <div class="col-12 d-flex align-items-center justify-content-between mb-2">
                </div>
                <div class="col-lg-2 col-6">
                    <div class="small-box bg-primary">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['total_ativos'], 0, ',', '.') ?></h3>
                            <p>Total ativos</p>
                        </div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <a href="#" class="small-box-footer" onclick="loadPage('/paginas/internos_painel.php', 'Painel de Internos', 'TI'); return false;">
                            Abrir painel <i class="fas fa-arrow-circle-right"></i>
                        </a>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['saida_temporaria'], 0, ',', '.') ?></h3>
                            <p>Saída temporária</p>
                        </div>
                        <div class="icon"><i class="fas fa-suitcase-rolling"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['trabalho_interno'], 0, ',', '.') ?></h3>
                            <p>Trabalho interno</p>
                        </div>
                        <div class="icon"><i class="fas fa-hammer"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['remicao_ativa'], 0, ',', '.') ?></h3>
                            <p>Remição (laboral)</p>
                        </div>
                        <div class="icon"><i class="fas fa-industry"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['regalias'], 0, ',', '.') ?></h3>
                            <p>Regalia</p>
                        </div>
                        <div class="icon"><i class="fas fa-crown"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>

                <div class="col-lg-2 col-6">
                    <div class="small-box bg-secondary">
                        <div class="inner">
                            <h3><?= number_format((int)$internosCounters['ctc_ativo'], 0, ',', '.') ?></h3>
                            <p>CTC ativo</p>
                        </div>
                        <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                        <div class="small-box-footer">&nbsp;</div>
                    </div>
                </div>
            </div>

            <!-- Internos: Busca e Dossiê -->
            <div class="row">
                <div class="col-lg-7">
                    <div class="card card-outline card-primary shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-search mr-2"></i>
                                Busca rápida de interno
                            </h3>
                        </div>
                        <div class="card-body">
                            <label class="small text-muted mb-2" for="inicioBuscaInterno">
                                Pesquise por IPEN, nome, nome social ou apelido
                            </label>
                            <div class="position-relative">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="inicioBuscaInterno" autocomplete="off" placeholder="Digite para buscar..." />
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" id="inicioBtnDossie" disabled>
                                            <i class="fas fa-user-check mr-1"></i> Procurar
                                        </button>
                                    </div>
                                </div>
                                <div id="inicioBuscaResultados" class="list-group inicio-busca-resultados"></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted" id="inicioBuscaHint">Digite ao menos 2 caracteres.</small>
                                <small class="text-muted" id="inicioBuscaSelecionado"></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card card-outline card-secondary shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-bolt mr-2"></i>
                                Atalhos de internos
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column">
                                <a class="btn btn-outline-secondary btn-sm text-left mb-2" href="#" onclick="loadPage('/paginas/internos_eletronicos_gestao.php', 'Eletrônicos', 'Censura'); return false;">
                                    <i class="fas fa-tv mr-2 text-info"></i> Gestão de eletrônicos
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-left mb-2" href="#" onclick="loadPage('/paginas/internos_recebimento_livros.php', 'Recebimento de Livros', 'Censura'); return false;">
                                    <i class="fas fa-book mr-2 text-info"></i> Recebimento de livros
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-left mb-2" href="#" onclick="loadPage('/paginas/internos_entrega_kits.php', 'Entrega de Kits', 'Censura'); return false;">
                                    <i class="fas fa-box-open mr-2 text-warning"></i> Entrega de kits
                                </a>
                                <a class="btn btn-outline-secondary btn-sm text-left" href="#" onclick="loadPage('/paginas/internos_md_cadastro.php', 'Medidas Disciplinares', 'Coordenação'); return false;">
                                    <i class="fas fa-gavel mr-2 text-danger"></i> Medidas disciplinares
                                </a>
                            </div>
                            <hr>
                            <p class="text-muted small mb-0">
                                Dica: o dossiê mostra um resumo do interno (trabalho, kit, regalia, eletrônicos, livros, cartas, CTC, MD e escoltas) com base no que existe hoje no SIGEP.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Análises -->
            <div class="row">
                <div class="col-12">
                    <h3 class="h5 mb-3 text-muted font-weight-normal">Análises e Movimentações</h3>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline card-info shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-pie mr-2"></i>Internos por situação</h3>
                        </div>
                        <div class="card-body">
                            <div class="inicio-chart-box">
                                <canvas id="inicioChartSituacao"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline card-info shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tv mr-2"></i>Eletrônicos na cela</h3>
                        </div>
                        <div class="card-body">
                            <div class="inicio-chart-box">
                                <canvas id="inicioChartEletronicos"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-outline card-secondary shadow-sm">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-sun mr-2"></i>Hoje</h3>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-muted small">Escoltas</div>
                                    <div class="h4 mb-0"><?= (int)$inicioHoje['escoltas_total'] ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Eclusa</div>
                                    <div class="h4 mb-0"><?= (int)$inicioHoje['eclusa_total'] ?></div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Cartas</div>
                                    <div class="h4 mb-0"><?= (int)$inicioHoje['cartas_total'] ?></div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Eclusa entradas</span>
                                <span class="font-weight-bold"><?= (int)$inicioHoje['eclusa_entradas'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span class="text-muted">Eclusa saídas</span>
                                <span class="font-weight-bold"><?= (int)$inicioHoje['eclusa_saidas'] ?></span>
                            </div>
                            <div class="mt-3">
                                <a class="btn btn-outline-secondary btn-sm btn-block" href="#" onclick="loadPage('/modulos/eclusa/escolta/escolta_view.php', 'Escoltas', 'Eclusa'); return false;">
                                    <i class="fas fa-shield-alt mr-1"></i> Abrir módulo de escoltas
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- <  <div class="col-lg-8">
<!-- <      <div class="card card-outline card-primary shadow-sm">
<!-- <          <div class="card-header">
<!-- <              <h3 class="card-title"><i class="fas fa-chart-line mr-2"></i>Escoltas por dia (14 dias)</h3>
<!-- <          </div>
<!-- <          <div class="card-body">
<!-- <              <div class="inicio-chart-wide">
<!-- <                  <canvas id="inicioChartEscoltas"></canvas>
<!-- <              </div>
<!-- <          </div>
<!-- <      </div>
<!-- <  </div>
<!-- <  <div class="col-lg-4">
<!-- <      <div class="card card-outline card-success shadow-sm">
<!-- <          <div class="card-header">
<!-- <              <h3 class="card-title"><i class="fas fa-door-open mr-2"></i>Eclusa: entradas e saídas (14 dias)</h3>
<!-- <          </div>
<!-- <          <div class="card-body">
<!-- <              <div class="inicio-chart-box">
<!-- <                  <canvas id="inicioChartEclusa"></canvas>
<!-- <              </div>
<!-- <          </div>
<!-- <      </div>
<!-- <  </div>
<!-- <div>

            <!-- Modal Dossiê -->
                <div class="modal fade" id="modalDossieInterno" tabindex="-1" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-id-badge mr-2"></i>
                                    Dossiê do interno
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="inicioDossieBody">
                                <div class="text-center p-5 text-muted">
                                    <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                                    <div>Carregando...</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-primary" id="inicioBtnImprimirDossie" disabled>
                                    <i class="fas fa-print mr-1"></i> Imprimir
                                </button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Detalhes Doação (será adicionado dinamicamente) -->
                <div id="modalContainerDoacoes"></div>
            </div>
    </section>
</div>

<?php
if ($autoloadConfig !== null):
?>
    <script>
        window.sigepAutoloadPage = <?= json_encode($autoloadConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
<?php
endif;
?>

<script>
    window.sigepInicioChartsData = <?= json_encode([
                                        'charts' => $inicioChartsData,
                                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<!-- JavaScript específico do módulo início -->
<script src="/modulos/inicio/assets/js/inicio.js?v=<?= time() ?>"></script>

<?php include_once __DIR__ . '/../../includes/footer.php'; ?>
