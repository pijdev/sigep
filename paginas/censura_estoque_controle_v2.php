<?php
// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

require_once __DIR__ . '/../includes/censura_estoque_controle_v2_logica.php';
header('Content-Type: text/html; charset=utf-8');
?>
<link rel="stylesheet" href="assets/css/censura_estoque_v2.css?v=<?php echo time(); ?>">
<script>
window.currentPage = 'censura_estoque_controle_v2.php';
window.pageTitle = 'Controle de Estoque V2 - Censura';
</script>

<div class="container-fluid estoque-v2-page">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="m-0"><i class="fas fa-warehouse mr-2 text-success"></i>Controle de Estoque V2</h4>
    <div class="btn-group">
      <button class="btn btn-outline-primary" id="btnGerenciarProdutos"><i class="fas fa-box-open"></i> Produtos</button>
      <button class="btn btn-outline-info" id="btnGerenciarFornecedores"><i class="fas fa-truck"></i> Fornecedores</button>
      <button class="btn btn-outline-secondary" id="btnGerenciarEstoques"><i class="fas fa-warehouse"></i> Estoques</button>
      <button class="btn btn-outline-warning" id="btnRelatorios"><i class="fas fa-chart-line"></i> Relatórios</button>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-primary cursor-pointer" id="cardProdutosAtivos" data-kpi="produtos">
        <div class="inner"><h3 id="kpiProdutos"><?= (int) $contadores['totalProdutos'] ?></h3><p>Produtos Ativos</p></div>
        <div class="icon"><i class="fas fa-box"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-info cursor-pointer" id="cardVariantesAtivas" data-kpi="variantes">
        <div class="inner"><h3 id="kpiVariantes"><?= (int) $contadores['totalVariantes'] ?></h3><p>Variantes Ativas</p></div>
        <div class="icon"><i class="fas fa-tags"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-warning cursor-pointer" id="cardItensAlerta" data-kpi="alerta">
        <div class="inner"><h3 id="kpiAlerta"><?= (int) $contadores['itensAlerta'] ?></h3><p>Itens em Alerta</p></div>
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-danger cursor-pointer" id="cardItensCritico" data-kpi="critico">
        <div class="inner"><h3 id="kpiCritico"><?= (int) $contadores['itensCritico'] ?></h3><p>Itens Críticos</p></div>
        <div class="icon"><i class="fas fa-skull-crossbones"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-success cursor-pointer" id="cardEntradasHoje" data-kpi="entradas">
        <div class="inner"><h3 id="kpiEntradasHoje"><?= (int) $contadores['entradasHoje'] ?></h3><p>Entradas Hoje</p></div>
        <div class="icon"><i class="fas fa-arrow-down"></i></div>
      </div>
    </div>
    <div class="col-lg-2 col-md-4 col-6">
      <div class="small-box bg-maroon cursor-pointer" id="cardSaidasHoje" data-kpi="saidas">
        <div class="inner"><h3 id="kpiSaidasHoje"><?= (int) $contadores['saidasHoje'] ?></h3><p>Saídas Hoje</p></div>
        <div class="icon"><i class="fas fa-arrow-up"></i></div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
      <strong>Movimentações</strong>
      <div class="btn-group">
        <button class="btn btn-sm btn-success" id="btnNovaEntrada">
          <i class="fas fa-plus"></i> Nova Entrada
        </button>
        <button class="btn btn-sm btn-danger" id="btnNovaSaida">
          <i class="fas fa-minus"></i> Nova Saída
        </button>
      </div>
    </div>
    <div class="card-body">
      <form id="filtrosMovForm" class="row">
        <div class="col-md-3 mb-2"><input class="form-control" name="search" placeholder="Buscar produto/motivo/documento"></div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="tipo_movimentacao"><option value="">Tipo</option><option>Entrada</option><option>Saida</option></select>
        </div>
        <div class="col-md-2 mb-2">
          <select class="form-control" name="status"><option value="">Status</option><option>Ativo</option><option>Cancelado</option></select>
        </div>
        <div class="col-md-2 mb-2"><input class="form-control" type="date" name="data_inicio"></div>
        <div class="col-md-2 mb-2"><input class="form-control" type="date" name="data_fim"></div>
        <div class="col-md-1 mb-2 d-flex">
          <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i></button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-hover" id="movTable">
          <thead>
            <tr>
              <th>Data</th><th>Produto</th><th>Variante</th><th>Tipo</th><th>Quantidade</th><th>Destino/Origem</th><th>Status</th><th>Ações</th>
            </tr>
          </thead>
          <tbody id="movTableBody"><tr><td colspan="8" class="text-center text-muted py-4">Carregando...</td></tr></tbody>
        </table>
      </div>

      <div class="d-flex justify-content-between align-items-center">
        <div id="movMeta" class="text-muted small"></div>
        <div>
          <button class="btn btn-sm btn-outline-secondary" id="movPrev">Anterior</button>
          <button class="btn btn-sm btn-outline-secondary" id="movNext">Próxima</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="offcanvas-v2" id="offcanvasProdutosV2">
  <div class="offcanvas-v2-header"><h5>Gerenciar Produtos e Variantes</h5><button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button></div>
  <div class="offcanvas-v2-body">
    <form id="produtoFormV2" class="mb-3">
      <input type="hidden" name="id" id="produto_id_v2">
      <div class="form-row">
        <div class="col-md-6 mb-2"><input class="form-control" name="nome" placeholder="Nome do produto" required></div>
        <div class="col-md-6 mb-2"><select class="form-control" name="id_tipo" required><option value="">Tipo</option><?php foreach ($tipos as $t): ?><option value="<?= (int) $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 mb-2"><select class="form-control" name="id_fornecedor"><option value="">Fornecedor</option><?php foreach ($fornecedores as $f): ?><option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6 mb-2"><select class="form-control" name="id_estoque"><option value="">Estoque padrão</option><?php foreach ($estoques as $e): ?><option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-12 mb-2"><textarea class="form-control" name="descricao" rows="2" placeholder="Descrição"></textarea></div>
      </div>
      <button class="btn btn-primary" type="submit">Salvar Produto</button>
      <button class="btn btn-success" type="button" id="btnNovoProdutoV2">Novo</button>
    </form>

    <hr>
    <h6>Variantes (Cor + Tamanho)</h6>
    <form id="varianteFormV2" class="form-row mb-2">
      <input type="hidden" name="id" id="variante_id_v2">
      <input type="hidden" name="id_produto" id="variante_produto_id_v2">
      <div class="col-md-3 mb-2"><input class="form-control" name="cor" placeholder="Cor" required></div>
      <div class="col-md-2 mb-2"><input class="form-control" name="tamanho" placeholder="Tam" required></div>
      <div class="col-md-3 mb-2"><input class="form-control" name="sku_interno" placeholder="SKU"></div>
      <div class="col-md-2 mb-2"><input class="form-control" name="quantidade_minima" type="number" min="0" value="0"></div>
      <div class="col-md-2 mb-2"><input class="form-control" name="quantidade_alerta" type="number" min="0" value="0"></div>
      <div class="col-md-12 mb-2">
      <button class="btn btn-outline-primary btn-sm" type="submit">Salvar Variante</button>
      <button class="btn btn-outline-secondary btn-sm" type="button" id="btnNovaVarianteV2">Nova Variante</button>
    </div>
    </form>
    <div id="variantesGridV2" class="small"></div>
    <hr>
    <div id="produtosGridV2"></div>

    <!-- Modal para selecionar produto fonte de variantes -->
    <div class="modal fade" id="modalHerdarVariantes" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Herdar Variantes</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Selecione o produto fonte para copiar as variantes:</p>
            <div class="mb-3">
              <input type="text" class="form-control" id="buscaProdutoFonte" placeholder="Buscar produto...">
            </div>
            <div id="listaProdutosFonte" style="max-height: 300px; overflow-y: auto;"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="button" class="btn btn-primary" id="btnConfirmarHerdar" disabled>Confirmar Herança</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="offcanvas-v2" id="offcanvasFornecedoresV2">
  <div class="offcanvas-v2-header"><h5>Fornecedores</h5><button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button></div>
  <div class="offcanvas-v2-body">
    <form id="fornecedorFormV2" class="mb-3">
      <input type="hidden" name="id" id="fornecedor_id_v2">
      <div class="form-row">
        <div class="col-md-6 mb-2"><input class="form-control" name="nome" placeholder="Nome" required></div>
        <div class="col-md-6 mb-2"><input class="form-control" name="cnpj_cpf" placeholder="CNPJ/CPF"></div>
        <div class="col-md-6 mb-2"><input class="form-control" name="telefone" placeholder="Telefone"></div>
        <div class="col-md-6 mb-2"><input class="form-control" name="email" placeholder="E-mail"></div>
        <div class="col-md-12 mb-2"><textarea class="form-control" name="endereco" rows="2" placeholder="Endereço"></textarea></div>
      </div>
      <button class="btn btn-primary" type="submit">Salvar Fornecedor</button>
    </form>
    <div id="fornecedoresGridV2"></div>
  </div>
</div>

<div class="offcanvas-v2" id="offcanvasEstoquesV2">
  <div class="offcanvas-v2-header"><h5>Estoques</h5><button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button></div>
  <div class="offcanvas-v2-body">
    <form id="estoqueFormV2" class="mb-3">
      <input type="hidden" name="id" id="estoque_id_v2">
      <div class="form-row">
        <div class="col-md-6 mb-2"><input class="form-control" name="nome" placeholder="Nome" required></div>
        <div class="col-md-6 mb-2"><input class="form-control" name="tipo" placeholder="Tipo" required></div>
        <div class="col-md-6 mb-2"><input class="form-control" name="capacidade_maxima" type="number" min="0" value="0" placeholder="Capacidade"></div>
        <div class="col-md-6 mb-2"><select class="form-control" name="status"><option>Ativo</option><option>Inativo</option></select></div>
        <div class="col-md-12 mb-2"><input class="form-control" name="descricao" placeholder="Descrição"></div>
      </div>
      <button class="btn btn-primary" type="submit">Salvar Estoque</button>
    </form>
    <div id="estoquesGridV2"></div>
  </div>
</div>

<div class="offcanvas-v2" id="offcanvasRelatoriosV2">
  <div class="offcanvas-v2-header"><h5>Relatórios</h5><button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button></div>
  <div class="offcanvas-v2-body">
    <form id="relatoriosFormV2" class="form-row mb-2">
      <div class="col-md-5 mb-2"><input class="form-control" type="date" name="data_inicio" value="<?= date('Y-m-01') ?>"></div>
      <div class="col-md-5 mb-2"><input class="form-control" type="date" name="data_fim" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-md-2 mb-2"><button class="btn btn-outline-primary btn-block" type="submit">Resumo</button></div>
    </form>
    <div class="btn-group mb-2 w-100">
      <button class="btn btn-outline-success btn-sm" data-relatorio="relatorio_entradas">Entradas</button>
      <button class="btn btn-outline-danger btn-sm" data-relatorio="relatorio_saidas">Saídas</button>
      <button class="btn btn-outline-warning btn-sm" data-relatorio="relatorio_estoque_baixo">Estoque Baixo</button>
      <button class="btn btn-outline-info btn-sm" data-relatorio="relatorio_sem_reposicao">Sem Reposição</button>
      <button class="btn btn-outline-secondary btn-sm" data-relatorio="relatorio_giro_produtos">Giro</button>
      <button class="btn btn-outline-dark btn-sm" id="btnDashboardAlmoxarifado"><i class="fas fa-chart-line mr-1"></i> Dashboard</button>
      <button class="btn btn-outline-primary btn-sm" id="btnGerarOficio"><i class="fas fa-file-alt mr-1"></i> Gerar Ofício</button>
    </div>
    <div id="relatoriosGridV2"></div>
  </div>
</div>

<!-- Offcanvas para Nova Entrada -->
<div class="offcanvas-v2" id="offcanvasNovaEntrada">
  <div class="offcanvas-v2-header">
    <h5><i class="fas fa-plus-circle text-success mr-2"></i>Nova Entrada</h5>
    <button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button>
  </div>
  <div class="offcanvas-v2-body">
    <form id="novaEntradaForm" class="row">
      <input type="hidden" name="id_produto" id="entrada_id_produto">
      <input type="hidden" name="id_variante" id="entrada_id_variante">
      <input type="hidden" name="tipo_movimentacao" value="Entrada">

      <div class="col-md-6 mb-3">
        <label class="form-label">Produto *</label>
        <input type="text" class="form-control" id="entrada_produto_busca" placeholder="Digite para buscar produto..." required>
        <div id="entrada_produto_resultados" class="position-absolute w-100 bg-white border rounded shadow-sm" style="max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Variante *</label>
        <select class="form-control" id="entrada_variante_select" required disabled>
          <option value="">Selecione um produto primeiro</option>
        </select>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Quantidade *</label>
        <input type="number" class="form-control" name="quantidade" min="1" required>
      </div>

      <div class="col-md-3 mb-3">
        <label class="form-label">Data da Entrada *</label>
        <input type="date" class="form-control" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Fornecedor *</label>
        <select class="form-control" name="id_fornecedor" required>
          <option value="">Selecione um fornecedor...</option>
          <?php foreach ($fornecedores as $f): ?>
            <option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Nota Fiscal</label>
        <input type="text" class="form-control" name="documento_referencia" placeholder="Número da NF">
      </div>

      <div class="col-md-12 mb-3">
        <label class="form-label">Motivo da Entrada</label>
        <input type="text" class="form-control" name="motivo_movimentacao" placeholder="Ex: Compra, Doação, etc.">
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Local de Armazenamento</label>
        <select class="form-control" name="id_estoque">
          <option value="">Selecione um estoque...</option>
          <?php foreach ($estoques as $e): ?>
            <option value="<?= (int) $e['id'] ?>"><?= htmlspecialchars($e['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 mb-3">
        <label class="form-label">Observações</label>
        <textarea class="form-control" name="observacoes" rows="2" placeholder="Observações adicionais sobre a entrada..."></textarea>
      </div>

      <div class="col-12">
        <button type="button" class="btn btn-secondary" data-offcanvas-close>Cancelar</button>
        <button type="button" class="btn btn-success" id="btnSalvarEntrada">
          <i class="fas fa-plus-circle mr-1"></i> Registrar Entrada
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Offcanvas para Nova Saída -->
<div class="offcanvas-v2" id="offcanvasNovaSaida">
  <div class="offcanvas-v2-header">
    <h5><i class="fas fa-minus-circle text-danger mr-2"></i>Nova Saída</h5>
    <button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button>
  </div>
  <div class="offcanvas-v2-body">
    <form id="novaSaidaForm" class="row">
      <input type="hidden" name="id_produto" id="saida_id_produto">
      <input type="hidden" name="id_variante" id="saida_id_variante">
      <input type="hidden" name="tipo_movimentacao" value="Saida">

      <div class="col-md-6 mb-3">
        <label class="form-label">Produto *</label>
        <input type="text" class="form-control" id="saida_produto_busca" placeholder="Digite para buscar produto..." required>
        <div id="saida_produto_resultados" class="position-absolute w-100 bg-white border rounded shadow-sm" style="max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Variante *</label>
        <select class="form-control" id="saida_variante_select" required disabled>
          <option value="">Selecione um produto primeiro</option>
        </select>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Quantidade *</label>
        <input type="number" class="form-control" name="quantidade" min="1" required>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Data da Saída *</label>
        <input type="date" class="form-control" name="data_movimentacao" value="<?= date('Y-m-d') ?>" required>
      </div>

      <div class="col-md-4 mb-3">
        <label class="form-label">Destino *</label>
        <select class="form-control" name="tipo_destino_origem" id="saida_tipo_destino" required>
          <option value="">Selecione...</option>
          <option value="Interno">Interno</option>
          <option value="Funcionario">Funcionário</option>
          <option value="Outro">Outro</option>
        </select>
      </div>

      <div class="col-md-12 mb-3" id="saida_destino_container">
        <label class="form-label">Destinatário</label>
        <div id="saida_destino_interno" style="display: none;">
          <input type="text" class="form-control" id="saida_interno_busca" placeholder="Buscar por IPEN, nome ou nome social...">
          <div id="saida_interno_resultados" class="position-absolute w-100 bg-white border rounded shadow-sm" style="max-height: 200px; overflow-y: auto; z-index: 1000; display: none;"></div>
          <input type="hidden" name="id_interno" id="saida_interno_id">
          <div id="saida_interno_dados" class="mt-2" style="display: none;">
            <small class="text-muted">
              <strong>IPEN:</strong> <span id="saida_interno_ipen"></span><br>
              <strong>Nome:</strong> <span id="saida_interno_nome"></span><br>
              <strong>Nome Social:</strong> <span id="saida_interno_nome_social"></span>
            </small>
          </div>
        </div>
        <div id="saida_destino_funcionario" style="display: none;">
          <input type="text" class="form-control" name="id_funcionario" placeholder="Nome do funcionário...">
        </div>
        <div id="saida_destino_outro" style="display: none;">
          <input type="text" class="form-control" name="destino_origem_outro" placeholder="Descrição do destino...">
        </div>
      </div>

      <div class="col-md-12 mb-3">
        <label class="form-label">Motivo da Saída *</label>
        <select class="form-control" name="motivo_movimentacao" required>
          <option value="">Selecione um motivo...</option>
          <option value="Distribuição">Distribuição</option>
          <option value="Baixa">Baixa</option>
          <option value="Perda">Perda</option>
          <option value="Transferência">Transferência</option>
          <option value="Outro">Outro</option>
        </select>
      </div>

      <div class="col-md-6 mb-3">
        <label class="form-label">Documento Referência</label>
        <input type="text" class="form-control" name="documento_referencia" placeholder="Número do documento, recibo, etc.">
      </div>

      <div class="col-12 mb-3">
        <label class="form-label">Observações</label>
        <textarea class="form-control" name="observacoes" rows="2" placeholder="Observações adicionais sobre a saída..."></textarea>
      </div>

      <div class="col-12">
        <button type="button" class="btn btn-secondary" data-offcanvas-close>Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnSalvarSaida">
          <i class="fas fa-minus-circle mr-1"></i> Registrar Saída
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Offcanvas para Detalhes dos Cards -->
<div class="offcanvas-v2" id="offcanvasDetalhesCard">
  <div class="offcanvas-v2-header">
    <h5 id="detalhesCardTitulo"><i class="fas fa-chart-bar mr-2"></i>Detalhes</h5>
    <button type="button" class="btn btn-sm btn-light" data-offcanvas-close>&times;</button>
  </div>
  <div class="offcanvas-v2-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 id="detalhesCardSubtitulo" class="m-0"></h6>
      <button class="btn btn-sm btn-outline-primary" id="btnImprimirDetalhes">
        <i class="fas fa-print mr-1"></i> Imprimir
      </button>
    </div>
    <div id="detalhesCardConteudo">
      <div class="text-center text-muted py-4">
        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
        <p>Carregando dados...</p>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Dashboard -->
<div class="modal fade" id="modalDashboard" tabindex="-1">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-chart-line mr-2"></i>Dashboard Almoxarifado
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <div id="dashboardContent">
          <div class="text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
            <p>Carregando dashboard...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        <button type="button" class="btn btn-primary" id="btnAtualizarDashboard">
          <i class="fas fa-sync-alt mr-1"></i> Atualizar
        </button>
        <button type="button" class="btn btn-success" id="btnTelaCheia">
          <i class="fas fa-expand mr-1"></i> Tela Cheia
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Ofício -->
<div class="modal fade" id="modalOficio" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Ofício - Relatório <div class="d-grid gap-2">
              <button class="btn btn-primary" id="btnRelatorioEntradas">
                <i class="fas fa-arrow-down mr-1"></i> Entradas
              </button>
              <button class="btn btn-danger" id="btnRelatorioSaidas">
                <i class="fas fa-arrow-up mr-1"></i> Saídas
              </button>
              <button class="btn btn-success" id="btnRelatorioSaldo">
                <i class="fas fa-balance-scale mr-1"></i> Saldo
              </button>
              <button class="btn btn-info" id="btnRelatorioMovimentacoes">
                <i class="fas fa-exchange-alt mr-1"></i> Movimentações
              </button>
              <button class="btn btn-secondary" id="btnRelatorioFornecedores">
                <i class="fas fa-truck mr-1"></i> Fornecedores
              </button>
              <button class="btn btn-dark" id="btnDashboardAlmoxarifado">
                <i class="fas fa-chart-line mr-1"></i> Dashboard
              </button>
              <button class="btn btn-warning" id="btnGerarOficio">
                <i class="fas fa-file-alt mr-1"></i> Gerar Ofício
              </button>
            </div>
    </div>
  </div>
</div>

<script src="assets/js/censura_estoque_v2.js?v=<?php echo time(); ?>"></script>
