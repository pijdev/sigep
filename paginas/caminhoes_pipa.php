<?php
require_once __DIR__ . '/../includes/caminhoes_pipa_logica.php';
header('Content-Type: text/html; charset=utf-8');

// Calcular basePath para assets no contexto SPA
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
if ($scriptDir === '.' || $scriptDir === '/' || $scriptDir === '\\') {
    $scriptDir = '';
}

if (substr($scriptDir, -8) === '/paginas') {
    $basePath = substr($scriptDir, 0, -8);
} else {
    $basePath = $scriptDir;
}

if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}

// Garantir que basePath nunca seja vazio na SPA
if (empty($basePath)) {
    $basePath = ''; // Para SPA, o basePath relativo funciona melhor
}

// Inicializar contadores para evitar erros
$contadores = [
    'totalRegistros' => 0,
    'registrosHoje' => 0,
    'totalLitros' => 0,
    'mediaLitros' => 0,
    'totalMotoristas' => 0,
    'totalVeiculos' => 0
];
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($basePath); ?>/assets/css/caminhoes_pipa.css?v=<?php echo time(); ?>">

<div class="container-fluid caminhoes-pipa-page pt-2">
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h4 class="m-0 text-dark">
      <i class="fas fa-truck-pickup mr-2 text-primary"></i>
      Controle de Caminhões Pipa
    </h4>
    <div class="btn-group mt-2 mt-md-0">
      <button class="btn btn-success" id="btnNovoRegistro">
        <i class="fas fa-plus mr-1"></i>Novo Registro
      </button>
      <button class="btn btn-primary" id="btnAbrirRelatorio">
        <i class="fas fa-file-alt mr-1"></i>Relatório
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
          <h3 id="kpiTotal"><?php echo (int) $contadores['totalRegistros']; ?></h3>
          <p>Total de Registros</p>
        </div>
        <div class="icon"><i class="fas fa-clipboard-list"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-info kpi-card" data-kpi="hoje">
        <div class="inner">
          <h3 id="kpiHoje"><?php echo (int) $contadores['registrosHoje']; ?></h3>
          <p>Registros Hoje</p>
        </div>
        <div class="icon"><i class="fas fa-calendar-day"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-success kpi-card" data-kpi="litros">
        <div class="inner">
          <h3 id="kpiLitros"><?php echo number_format($contadores['totalLitros'], 0, ',', '.'); ?></h3>
          <p>Total Litros</p>
        </div>
        <div class="icon"><i class="fas fa-tint"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-warning kpi-card" data-kpi="media">
        <div class="inner">
          <h3 id="kpiMedia"><?php echo number_format($contadores['mediaLitros'], 1, ',', '.'); ?></h3>
          <p>Média Litros</p>
        </div>
        <div class="icon"><i class="fas fa-chart-line"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-danger kpi-card" data-kpi="motoristas">
        <div class="inner">
          <h3 id="kpiMotoristas"><?php echo (int) $contadores['totalMotoristas']; ?></h3>
          <p>Motoristas</p>
        </div>
        <div class="icon"><i class="fas fa-users"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-secondary kpi-card" data-kpi="veiculos">
        <div class="inner">
          <h3 id="kpiVeiculos"><?php echo (int) $contadores['totalVeiculos']; ?></h3>
          <p>Veículos</p>
        </div>
        <div class="icon"><i class="fas fa-truck"></i></div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header">
      <h3 class="card-title"><i class="fas fa-filter mr-1"></i>Filtros</h3>
    </div>
    <div class="card-body pb-2">
      <form id="filtrosForm" class="row">
        <div class="col-md-2 mb-2">
          <select class="form-control" name="placa" id="filtro_placa">
            <option value="">Todas as placas</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="motorista" id="filtro_motorista">
            <option value="">Todos os motoristas</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="empresa" id="filtro_empresa">
            <option value="">Todas as empresas</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="tipo" id="filtro_tipo">
            <option value="">Todos os tipos</option>
            <option value="Pipa">Pipa</option>
            <option value="Tanque">Tanque</option>
          </select>
        </div>
        <div class="col-md-2 mb-2">
          <input type="date" class="form-control" name="data_inicio" id="filtro_data_inicio" placeholder="Data Início">
        </div>
        <div class="col-md-2 mb-2">
          <input type="date" class="form-control" name="data_fim" id="filtro_data_fim" placeholder="Data Fim">
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
      <h3 class="card-title"><i class="fas fa-list mr-1"></i>Registros de Caminhões Pipa</h3>
      <span id="regMeta" class="small text-muted"></span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover table-striped table-caminhoes-pipa mb-0">
          <thead>
            <tr>
              <th>Data</th>
              <th>Placa</th>
              <th>Motorista</th>
              <th>Empresa</th>
              <th>Tipo</th>
              <th>Litros</th>
              <th>Km Inicial</th>
              <th>Km Final</th>
              <th>Km Percorridos</th>
              <th>Status</th>
              <th class="text-center">Ações</th>
            </tr>
          </thead>
          <tbody id="registrosTableBody">
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
      <button class="btn btn-sm btn-outline-secondary" id="regPrev"><i class="fas fa-chevron-left mr-1"></i>Anterior</button>
      <span class="small text-muted" id="regPageInfo"></span>
      <button class="btn btn-sm btn-outline-secondary" id="regNext">Próxima<i class="fas fa-chevron-right ml-1"></i></button>
    </div>
  </div>
</div>

<div class="offcanvas-caminhoes-pipa" id="offcanvasRegistro">
  <div class="offcanvas-caminhoes-pipa-header">
    <h5 class="offcanvas-title"><i class="fas fa-truck-pickup mr-2"></i>Novo Registro</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas">
      <i class="fas fa-times"></i>
    </button>
  </div>
  <div class="offcanvas-caminhoes-pipa-body">
    <form id="formRegistro">
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="placa">Placa *</label>
          <input type="text" class="form-control" id="placa" name="placa" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="motorista">Motorista *</label>
          <input type="text" class="form-control" id="motorista" name="motorista" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="empresa">Empresa *</label>
          <input type="text" class="form-control" id="empresa" name="empresa" required>
        </div>
        <div class="col-md-6 mb-3">
          <label for="tipo">Tipo *</label>
          <select class="form-control" id="tipo" name="tipo" required>
            <option value="">Selecione...</option>
            <option value="Pipa">Pipa</option>
            <option value="Tanque">Tanque</option>
          </select>
        </div>
        <div class="col-md-3 mb-3">
          <label for="litros">Litros *</label>
          <input type="number" class="form-control" id="litros" name="litros" step="0.1" required>
        </div>
        <div class="col-md-3 mb-3">
          <label for="km_inicial">Km Inicial *</label>
          <input type="number" class="form-control" id="km_inicial" name="km_inicial" required>
        </div>
        <div class="col-md-3 mb-3">
          <label for="km_final">Km Final *</label>
          <input type="number" class="form-control" id="km_final" name="km_final" required>
        </div>
        <div class="col-md-3 mb-3">
          <label for="status">Status *</label>
          <select class="form-control" id="status" name="status" required>
            <option value="">Selecione...</option>
            <option value="Ativo">Ativo</option>
            <option value="Inativo">Inativo</option>
            <option value="Manutenção">Manutenção</option>
          </select>
        </div>
        <div class="col-md-12 mb-3">
          <label for="observacoes">Observações</label>
          <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-12 text-center">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i>Salvar Registro
          </button>
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="offcanvas">
            <i class="fas fa-times mr-1"></i>Cancelar
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="<?php echo htmlspecialchars($basePath); ?>/assets/js/caminhoes_pipa.js?v=<?php echo time(); ?>"></script>
