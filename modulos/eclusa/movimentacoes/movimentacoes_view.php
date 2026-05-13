<?php
// Incluir lógica do módulo
require_once 'movimentacoes_logica.php';
?>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="modulos/eclusa/movimentacoes/assets/css/movimentacoes.css?v=<?php echo time(); ?>">

<div class="container-fluid eclusa-movimentacoes-page pt-2">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h4 class="m-0 text-dark">
      <i class="fas fa-archway mr-2 text-warning"></i>
      Controle de Movimentações da Eclusa
    </h4>
    <div class="btn-group mt-2 mt-md-0">
      <button class="btn btn-success" id="btnNovaMovimentacao">
        <i class="fas fa-plus mr-1"></i>Nova Movimentação
      </button>
      <button class="btn btn-primary" id="btnAbrirRelatorio">
        <i class="fas fa-file-alt mr-1"></i>Relatório
      </button>
      <button class="btn btn-info" id="btnAbrirDashboard" onclick="window.open('/dashboard/eclusa', '_blank')">
        <i class="fas fa-chart-line mr-1"></i>Dashboard
      </button>
      <button class="btn btn-outline-secondary" id="btnAtualizarTudo">
        <i class="fas fa-sync-alt mr-1"></i>Atualizar
      </button>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-primary kpi-card" data-kpi="total">
        <div class="inner">
          <h3 id="kpiTotal"><?php echo (int) $contadores['totalMovimentacoes']; ?></h3>
          <p>Total</p>
        </div>
        <div class="icon"><i class="fas fa-exchange-alt"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-info kpi-card" data-kpi="hoje">
        <div class="inner">
          <h3 id="kpiHoje"><?php echo (int) $contadores['movimentacoesHoje']; ?></h3>
          <p>Hoje</p>
        </div>
        <div class="icon"><i class="fas fa-calendar-day"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-success kpi-card" data-kpi="entradas">
        <div class="inner">
          <h3 id="kpiEntradas"><?php echo (int) $contadores['entradasHoje']; ?></h3>
          <p>Entradas Hoje</p>
        </div>
        <div class="icon"><i class="fas fa-arrow-right"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-danger kpi-card" data-kpi="saidas">
        <div class="inner">
          <h3 id="kpiSaidas"><?php echo (int) $contadores['saidasHoje']; ?></h3>
          <p>Saídas Hoje</p>
        </div>
        <div class="icon"><i class="fas fa-arrow-left"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-warning kpi-card" data-kpi="veiculos">
        <div class="inner">
          <h3 id="kpiVeiculos"><?php echo count($top_veiculos); ?></h3>
          <p>Top Veículos</p>
        </div>
        <div class="icon"><i class="fas fa-truck"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-secondary kpi-card" data-kpi="empresas">
        <div class="inner">
          <h3 id="kpiEmpresas"><?php echo count($top_empresas); ?></h3>
          <p>Top Empresas</p>
        </div>
        <div class="icon"><i class="fas fa-building"></i></div>
      </div>
    </div>
  </div>

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
          <select class="form-control" name="veiculo" id="filtro_veiculo">
            <option value="">Todos os veículos</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="empresa" id="filtro_empresa">
            <option value="">Todas as empresas</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="motorista" id="filtro_motorista">
            <option value="">Todos os motoristas</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="tipo_movimento">
            <option value="">Tipo</option>
            <option value="entrada">Entrada</option>
            <option value="saida">Saída</option>
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

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title"><i class="fas fa-list mr-1"></i>Movimentações</h3>
      <span id="movMeta" class="small text-muted"></span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped table-eclusa mb-0">
          <thead>
            <tr>
              <th>Data</th>
              <th>Chegada</th>
              <th>Entrada</th>
              <th>Saída</th>
              <th>Placa</th>
              <th>Veículo</th>
              <th>Empresa</th>
              <th>Motorista</th>
              <th>Tipo</th>
              <th>Cadastrado Por</th>
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

<div class="offcanvas-eclusa" id="offcanvasMovimentacao">
  <div class="offcanvas-eclusa-header">
    <h5 class="mb-0" id="tituloMovForm">Nova Movimentação</h5>
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
          <label class="form-label">Data Movimentação *</label>
          <input type="date" class="form-control" name="data_movimentacao" required>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Cadastrado por</label>
          <input type="text" class="form-control" name="cadastrado_por" placeholder="Usuário responsável">
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Hora Chegada</label>
          <input type="time" class="form-control" name="hora_chegada">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Hora Entrada</label>
          <input type="time" class="form-control" name="hora_entrada">
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Hora Saída</label>
          <input type="time" class="form-control" name="hora_saida">
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Placa</label>
          <div class="input-group">
            <div class="busca-inteligente flex-grow-1">
              <input type="text" class="form-control" name="placa_veiculo" id="placa_veiculo" data-campo="placa" placeholder="ABC-1234">
            </div>
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-secondary" id="btnNovoVeiculo" title="Cadastrar novo veículo">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Veículo</label>
          <div class="busca-inteligente">
            <input type="text" class="form-control" name="tipo_veiculo" id="tipo_veiculo" data-campo="veiculo" placeholder="Modelo / descrição">
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="form-label">Empresa</label>
          <div class="input-group">
            <div class="busca-inteligente flex-grow-1">
              <input type="text" class="form-control" name="empresa" id="empresa" data-campo="empresa" placeholder="Empresa">
            </div>
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-secondary" id="btnNovaEmpresa" title="Cadastrar nova empresa">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Motorista</label>
          <div class="input-group">
            <div class="busca-inteligente flex-grow-1">
              <input type="text" class="form-control" name="motorista" id="motorista" data-campo="motorista" placeholder="Nome do motorista">
            </div>
            <div class="input-group-append">
              <button type="button" class="btn btn-outline-secondary" id="btnNovoMotorista" title="Cadastrar novo motorista">
                <i class="fas fa-plus"></i>
              </button>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label">Observações</label>
          <textarea class="form-control" name="observacoes" rows="1" placeholder="Observações"></textarea>
        </div>
      </div>

      <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary mr-2" data-offcanvas-close>Cancelar</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>

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
        <label class="form-label">Veículo</label>
        <input type="text" class="form-control" name="veiculo" placeholder="Nome do veículo">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Empresa</label>
        <input type="text" class="form-control" name="empresa" placeholder="Empresa">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Motorista</label>
        <input type="text" class="form-control" name="motorista" placeholder="Motorista">
      </div>
      <div class="col-md-6 mb-3">
        <label class="form-label">Tipo</label>
        <select class="form-control" name="tipo_movimento">
          <option value="">Todos</option>
          <option value="entrada">Entrada</option>
          <option value="saida">Saída</option>
        </select>
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button type="button" class="btn btn-outline-secondary mr-2" data-offcanvas-close>Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnGerarRelatorio"><i class="fas fa-file-pdf mr-1"></i>Gerar</button>
      </div>
    </form>
  </div>
</div>

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

<div class="loading-overlay" id="loadingOverlay">
  <div class="loading-spinner">
    <i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i>
    <div>Processando...</div>
  </div>
</div>

<script>
window.SIGEP_BASE_PATH = "<?php echo htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8'); ?>";
window.SIGEP_USER_NAME = "<?php echo htmlspecialchars($_SESSION['user_nome'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";
</script>
<script src="modulos/eclusa/movimentacoes/assets/js/movimentacoes.js?v=<?php echo time(); ?>"></script>
