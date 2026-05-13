<?php
require_once __DIR__ . '/../includes/censura_estoque_controle_logica.php';
header('Content-Type: text/html; charset=utf-8');
?>

<script>
    window.currentPage = 'censura_estoque_controle.php';
    window.pageTitle = 'Controle de Estoque - Censura';
</script>

<!-- CSS INLINE - Seguindo padrão da página funcional -->
<style>
    :root {
        --primary: #2c3e50;
        --accent: #3498db;
        --danger: #e74c3c;
        --success: #28a745;
        --warning: #ffc107;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #343a40;
    }

    /* ================= ANIMAÇÕES MODERNAS ================= */
    @import url('https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css');

    .animate-fade-in {
        animation: fadeIn 0.5s ease-in-out;
    }

    .animate-slide-up {
        animation: slideInUp 0.5s ease-out;
    }

    /* ================= TABELAS ================= */
    .thead-pretty {
        background: linear-gradient(180deg, #343a40 0%, #495057 100%);
        color: #fff;
        text-transform: uppercase;
        font-size: 0.75rem;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid #6c757d;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* ================= ALERTAS DE ESTOQUE ================= */
    .estoque-alerta {
        background-color: #fff3cd !important;
        color: #856404 !important;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .estoque-alerta:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(255,193,7,0.2);
    }

    .estoque-critico {
        background-color: #f8d7da !important;
        color: #721c24 !important;
        font-weight: bold;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        animation: pulse 2s infinite;
        transition: all 0.2s ease;
    }

    .estoque-critico:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(220,53,69,0.2);
    }

    .estoque-normal {
        background-color: #d4edda !important;
        color: #155724 !important;
        border: 1px solid #c3e6cb;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .estoque-normal:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(40,167,69,0.2);
    }

    /* ================= OFFCANVAS (DESIGN IGUAL AO FUNCIONAL) ================= */
    .offcanvas-right {
        position: fixed;
        top: 0;
        right: 0;
        width: 450px;
        height: 100%;
        background: #f8f9fa;
        z-index: 1060;
        box-shadow: -10px 0 30px rgba(0,0,0,0.5);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(100%);
        display: flex;
        flex-direction: column;
    }

    .offcanvas-header {
        background: linear-gradient(135deg, #343a40 0%, #495057 100%);
        color: white;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 3px solid var(--accent);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .offcanvas-header button:hover {
        color: var(--danger) !important;
        transform: scale(1.15);
        transition: all 0.2s ease;
    }

    .offcanvas-content {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
    }

    /* ================= CARDS ESTATÍSTICOS ================= */
    .card-estatistica {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        border: none;
        border-radius: 12px;
        overflow: hidden;
        position: relative;
    }

    .card-estatistica::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .card-estatistica:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .card-estatistica:hover::before {
        opacity: 1;
    }

    .card-estatistica .inner {
        position: relative;
        z-index: 2;
    }

    /* ================= BOTÕES MODERNOS ================= */
    .btn-modern {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 8px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        position: relative;
        overflow: hidden;
    }

    .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }

    .btn-modern:hover::before {
        left: 100%;
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    /* ================= FORMULÁRIOS ================= */
    .form-section-title {
        font-size: 0.85rem;
        text-transform: uppercase;
        font-weight: 700;
        margin-top: 15px;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 2px solid var(--accent);
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-control-modern {
        border-radius: 8px;
        border: 2px solid #e9ecef;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    }

    .form-control-modern:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        transform: translateY(-1px);
    }

    /* ================= INFO BOX ================= */
    .info-box-static {
        background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
    }

    .info-box-static:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.12);
    }

    .info-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        color: #6c757d;
        font-weight: 700;
        display: block;
        margin-bottom: 2px;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--primary);
        display: block;
    }

    .info-value-lg {
        font-size: 1.2rem;
        color: var(--accent);
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }

    /* ================= BOTÕES ================= */
    .btn-acao {
        padding: 4px 8px;
        font-size: 0.75rem;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .btn-acao:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    /* ================= LOADING OVERLAY ================= */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255,255,255,0.95);
        display: none;
        z-index: 9999;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(2px);
        border-radius: 12px;
    }

    .loading-overlay.show {
        display: flex;
    }

    .spinner-modern {
        width: 60px;
        height: 60px;
        border: 4px solid var(--light);
        border-top: 4px solid var(--accent);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ================= TABELA MODERNA ================= */
    .table-modern {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .table-modern tbody tr {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: default;
        border-bottom: 1px solid #e9ecef;
    }

    .table-modern tbody tr:hover {
        background: linear-gradient(135deg, rgba(52, 152, 219, 0.03) 0%, rgba(52, 152, 219, 0.08) 100%);
        transform: translateX(4px);
        box-shadow: 2px 0 8px rgba(52, 152, 219, 0.1);
    }

    .table-modern tbody tr:last-child {
        border-bottom: none;
    }

    /* ================= STATUS BADGES ================= */
    .badge-status-modern {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }

    .badge-status-modern:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* ================= DARK MODE ================= */
    .dark-mode .offcanvas-right {
        background: linear-gradient(135deg, #343a40 0%, #495057 100%);
        border-left: 1px solid #4b545c;
        color: #fff;
    }

    .dark-mode .offcanvas-header {
        background: linear-gradient(135deg, #2d3238 0%, #3f474e 100%);
        color: #fff;
        border-bottom-color: var(--accent);
    }

    .dark-mode .offcanvas-content {
        background: linear-gradient(to bottom, #495057 0%, #343a40 100%);
        color: #adb5bd;
    }

    .dark-mode .offcanvas-content .text-muted {
        color: #6c757d !important;
    }

    .dark-mode .offcanvas-content .badge {
        color: #fff;
    }

    .dark-mode .offcanvas-content .form-control {
        background: linear-gradient(135deg, #495057 0%, #3f474e 100%);
        border-color: #4b545c;
        color: #fff;
    }

    .dark-mode .offcanvas-content .form-control:focus {
        background: linear-gradient(135deg, #495057 0%, #3f474e 100%);
        border-color: var(--accent);
        color: #fff;
    }

    .dark-mode .offcanvas-content .btn {
        color: #fff;
    }

    .dark-mode .offcanvas-content .table {
        color: #adb5bd;
    }

    .dark-mode .offcanvas-content .table thead th {
        background: linear-gradient(135deg, #2d3238 0%, #3f474e 100%);
        border-color: #4b545c;
        color: #fff;
    }

    .dark-mode .offcanvas-content .table tbody tr {
        background: transparent;
        border-color: #4b545c;
    }

    .dark-mode .offcanvas-content .table tbody tr:hover {
        background: rgba(52, 152, 219, 0.1);
    }

    .dark-mode .info-box-static {
        background: linear-gradient(135deg, #3f474e 0%, #495057 100%);
        border-color: #4b545c;
    }

    .dark-mode .info-label {
        color: #adb5bd;
    }

    .dark-mode .info-value {
        color: #fff;
    }

    .dark-mode .table-modern tbody tr:hover {
        background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(52, 152, 219, 0.2) 100%);
    }

    /* ================= RESPONSIVE ================= */
    @media (max-width: 768px) {
        .offcanvas-right {
            width: 100%;
        }

        .card-estatistica:hover {
            transform: none;
        }

        .btn-modern:hover {
            transform: none;
        }

        .table-modern tbody tr:hover {
            transform: none;
        }
    }
</style>

<!-- Main content -->
<section class="content pt-3">
    <div class="container-fluid">
        <!-- CARDS ESTATÍSTICAS -->
        <div class="row mb-3 no-print">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info card-estatistica" onclick="mostrarProdutosAtivos()">
                    <div class="inner">
                        <h3><?= $total_produtos ?></h3>
                        <p>Produtos Ativos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Todos <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning card-estatistica" onclick="mostrarProdutosAlerta()">
                    <div class="inner">
                        <h3><?= $produtos_alerta ?></h3>
                        <p>Produtos em Alerta</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Lista <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger card-estatistica" onclick="mostrarProdutosCriticos()">
                    <div class="inner">
                        <h3><?= $produtos_criticos ?></h3>
                        <p>Estoque Crítico</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Lista <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success card-estatistica" onclick="mostrarMovimentacoesHoje()">
                    <div class="inner">
                        <h3><?= $mov_hoje ?></h3>
                        <p>Movimentações Hoje</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Relatório <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- BOTÕES DE AÇÃO -->
        <div class="row mb-3 no-print">
            <div class="col-12">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary btn-modern" onclick="gerenciarProdutos()">
                        <i class="fas fa-box"></i> Gerenciar Produtos
                    </button>
                    <button type="button" class="btn btn-info btn-modern" onclick="gerenciarFornecedores()">
                        <i class="fas fa-truck"></i> Gerenciar Fornecedores
                    </button>
                    <button type="button" class="btn btn-success btn-modern" onclick="novaMovimentacao('Entrada')">
                        <i class="fas fa-arrow-down"></i> Registrar Entrada
                    </button>
                    <button type="button" class="btn btn-danger btn-modern" onclick="novaMovimentacao('Saida')">
                        <i class="fas fa-arrow-up"></i> Registrar Saída
                    </button>
                    <button type="button" class="btn btn-secondary btn-modern" onclick="gerenciarEstoques()">
                        <i class="fas fa-warehouse"></i> Gerenciar Estoques
                    </button>
                    <button type="button" class="btn btn-warning btn-modern" onclick="gerenciarRelatorios()">
                        <i class="fas fa-print"></i> Relatórios
                    </button>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card card-dark no-print">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Filtros de Pesquisa</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form id="formFiltros" onsubmit="aplicarFiltros(); return false;">
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Pesquisa</label>
                            <input type="text" class="form-control form-control-sm" name="search" 
                                   placeholder="Produto, motivo..." value="<?= htmlspecialchars($f['search']) ?>">
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Tipo Produto</label>
                            <select class="form-control form-control-sm" name="id_tipo">
                                <option value="">Todos</option>
                                <?php foreach($tipos as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $f['id_tipo']==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Fornecedor</label>
                            <select class="form-control form-control-sm" name="id_fornecedor">
                                <option value="">Todos</option>
                                <?php foreach($fornecedores as $forn): ?>
                                    <option value="<?= $forn['id'] ?>" <?= $f['id_fornecedor']==$forn['id']?'selected':'' ?>><?= htmlspecialchars($forn['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Tipo Movimentação</label>
                            <select class="form-control form-control-sm" name="tipo_movimentacao">
                                <option value="">Todas</option>
                                <option value="Entrada" <?= $f['tipo_movimentacao']=='Entrada'?'selected':'' ?>>Entrada</option>
                                <option value="Saida" <?= $f['tipo_movimentacao']=='Saida'?'selected':'' ?>>Saída</option>
                            </select>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Período</label>
                            <div class="input-group input-group-sm">
                                <input type="date" class="form-control" name="data_inicio" value="<?= htmlspecialchars($f['data_inicio']) ?>">
                                <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($f['data_fim']) ?>">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i> FILTRAR
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limparFiltros()">
                                <i class="fas fa-times"></i> LIMPAR
                            </button>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="ver_canceladas" name="ver_canceladas" value="1" <?= $f['ver_canceladas'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="ver_canceladas">
                                    <i class="fas fa-eye"></i> Ver Canceladas
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        </div>

        <!-- TABELA DE MOVIMENTAÇÕES - NOVA VERSÃO ADMINLTE 4 -->
        <div class="card shadow position-relative">
            <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                <div class="spinner-modern"></div>
            </div>
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list"></i> Movimentações Recentes
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info" id="totalRegistros"><?= count($movimentacoes) ?> registros</span>
                    <button type="button" class="btn btn-tool" data-bs-toggle="collapse" data-bs-target="#tableContainer" aria-expanded="true">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body table-responsive p-0" id="tableContainer">
                <table class="table table-hover table-striped align-middle mb-0" id="movementsTable">
                    <thead class="table-dark">
                        <tr class="text-center">
                            <th class="text-nowrap" style="width: 100px">
                                <a href="#" class="text-white text-decoration-none sort-link" data-sort="data_movimentacao">
                                    DATA <i class="fas fa-sort ms-1"></i>
                                </a>
                            </th>
                            <th>
                                <a href="#" class="text-white text-decoration-none sort-link" data-sort="produto">
                                    PRODUTO <i class="fas fa-sort ms-1"></i>
                                </a>
                            </th>
                            <th class="text-nowrap" style="width: 80px">TIPO</th>
                            <th class="text-nowrap" style="width: 80px">
                                <a href="#" class="text-white text-decoration-none sort-link" data-sort="quantidade">
                                    QTD <i class="fas fa-sort ms-1"></i>
                                </a>
                            </th>
                            <th>DESTINO/ORIGEM</th>
                            <th>MOTIVO</th>
                            <th class="text-nowrap" style="width: 120px">DOCUMENTO</th>
                            <th class="text-nowrap" style="width: 150px">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody id="movementsTableBody">
                        <!-- Conteúdo será carregado dinamicamente via JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- OFFCANVAS - CADASTRO DE PRODUTO -->
<div class="offcanvas-right" id="offcanvasProduto">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-box"></i> Cadastro de Produto
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <form id="formProduto" onsubmit="salvarProduto(event)">
            <input type="hidden" id="produto_id" name="id">
            
            <!-- Informações Básicas -->
            <div class="form-section-title">
                <i class="fas fa-info-circle"></i> Informações Básicas
            </div>
            
            <div class="form-group">
                <label>Nome do Produto *</label>
                <input type="text" class="form-control" id="produto_nome" name="nome" required>
            </div>
            
            <div class="form-group">
                <label>Descrição</label>
                <textarea class="form-control" id="produto_descricao" name="descricao" rows="3"></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tipo de Produto *</label>
                        <div class="input-group">
                            <select class="form-control" id="produto_tipo" name="id_tipo" required>
                                <option value="">Selecione...</option>
                                <?php foreach($tipos as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="gerenciarTipos()" title="Gerenciar Tipos">
                                    <i class="fas fa-cog"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Fornecedor</label>
                        <select class="form-control" id="produto_fornecedor" name="id_fornecedor">
                            <option value="">Selecione...</option>
                            <?php foreach($fornecedores as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Controle de Estoque -->
            <div class="form-section-title">
                <i class="fas fa-chart-line"></i> Controle de Estoque
            </div>
            
            <div class="info-box-static">
                <span class="info-label">Saldo Atual</span>
                <span class="info-value-lg" id="produto_saldo_atual">0</span>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Quantidade Mínima *</label>
                        <input type="number" class="form-control" id="produto_qtd_minima" name="quantidade_minima" 
                               min="0" value="0" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Quantidade Alerta *</label>
                        <input type="number" class="form-control" id="produto_qtd_alerta" name="quantidade_alerta" 
                               min="0" value="0" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Unidade Medida *</label>
                        <select class="form-control" id="produto_unidade" name="unidade_medida" required>
                            <option value="un">Unidade</option>
                            <option value="kg">Quilograma</option>
                            <option value="l">Litro</option>
                            <option value="m">Metro</option>
                            <option value="cx">Caixa</option>
                            <option value="pct">Pacote</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Localização -->
            <div class="form-section-title">
                <i class="fas fa-map-marker-alt"></i> Localização
            </div>

            <div class="form-group">
                <label>Estoque/Localização Física *</label>
                <select class="form-control" id="produto_localizacao" name="id_estoque" required>
                    <option value="">Carregando estoques...</option>
                </select>
                <input type="hidden" id="produto_localizacao_legacy" name="localizacao" value="">
            </div>
            
            <!-- Status -->
            <div class="form-section-title">
                <i class="fas fa-toggle-on"></i> Status
            </div>
            
            <div class="form-group">
                <select class="form-control" id="produto_status" name="status">
                    <option value="Ativo">Ativo</option>
                    <option value="Inativo">Inativo</option>
                </select>
            </div>
            
            <!-- Botões -->
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Produto
                </button>
                <button type="button" class="btn btn-secondary ml-2" onclick="fecharOffcanvas()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>

        <!-- SEÇÃO DE LISTAGEM DE PRODUTOS -->
        <div class="mt-4 pt-4 border-top">
            <div class="form-section-title">
                <i class="fas fa-list"></i> Produtos Cadastrados
            </div>

            <div class="produtos-list-container" style="max-height: 300px; overflow-y: auto;">
                <div id="produtos-list">
                    <!-- Produtos serão carregados via JavaScript -->
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin"></i> Carregando produtos...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - CADASTRO DE FORNECEDOR -->
<div class="offcanvas-right" id="offcanvasFornecedor">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-truck"></i> Cadastro de Fornecedor
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <form id="formFornecedor" onsubmit="salvarFornecedor(event)">
            <input type="hidden" id="fornecedor_id" name="id">
            
            <!-- Informações Básicas -->
            <div class="form-section-title">
                <i class="fas fa-info-circle"></i> Informações do Fornecedor
            </div>
            
            <div class="form-group">
                <label>Nome/Razão Social *</label>
                <input type="text" class="form-control" id="fornecedor_nome" name="nome" required>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>CNPJ/CPF</label>
                        <input type="text" class="form-control" id="fornecedor_cnpj_cpf" name="cnpj_cpf">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Telefone</label>
                        <input type="text" class="form-control" id="fornecedor_telefone" name="telefone">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" class="form-control" id="fornecedor_email" name="email">
            </div>
            
            <div class="form-group">
                <label>Endereço</label>
                <textarea class="form-control" id="fornecedor_endereco" name="endereco" rows="3"></textarea>
            </div>
            
            <!-- Status -->
            <div class="form-section-title">
                <i class="fas fa-toggle-on"></i> Status
            </div>
            
            <div class="form-group">
                <select class="form-control" id="fornecedor_status" name="status" required>
                    <option value="Ativo">Ativo</option>
                    <option value="Inativo">Inativo</option>
                </select>
            </div>
            
            <!-- Botões -->
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Fornecedor
                </button>
                <button type="button" class="btn btn-secondary ml-2" onclick="fecharOffcanvas()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>

        <!-- SEÇÃO DE LISTAGEM DE FORNECEDORES -->
        <div class="mt-4 pt-4 border-top">
            <div class="form-section-title">
                <i class="fas fa-list"></i> Fornecedores Cadastrados
            </div>

            <div class="fornecedores-list-container" style="max-height: 300px; overflow-y: auto;">
                <div id="fornecedores-list">
                    <!-- Fornecedores serão carregados via JavaScript -->
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-spinner fa-spin"></i> Carregando fornecedores...
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - MOVIMENTAÇÃO -->
<div class="offcanvas-right" id="offcanvasMovimentacao">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-exchange-alt"></i> Registrar Movimentação
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <form id="formMovimentacao" onsubmit="salvarMovimentacao(event)">
            <!-- Tipo de Movimentação -->
            <div class="form-section-title">
                <i class="fas fa-info-circle"></i> Tipo da Movimentação
            </div>
            
            <div class="form-group">
                <select class="form-control" id="mov_tipo" name="tipo_movimentacao" required>
                    <option value="">Selecione...</option>
                    <option value="Entrada">Entrada</option>
                    <option value="Saida">Saída</option>
                </select>
            </div>
            
            <!-- Produto -->
            <div class="form-section-title">
                <i class="fas fa-box"></i> Produto
            </div>
            
            <div class="form-group">
                <label>Produto *</label>
                <select class="form-control" id="mov_produto" name="id_produto" required>
                    <option value="">Selecione...</option>
                    <?php foreach($produtos as $p): ?>
                        <option value="<?= $p['id'] ?>" 
                                data-saldo="<?= $p['quantidade_atual'] ?>"
                                data-minimo="<?= $p['quantidade_minima'] ?>"
                                data-alerta="<?= $p['quantidade_alerta'] ?>">
                            <?= htmlspecialchars($p['nome']) ?> 
                            (Saldo: <?= $p['quantidade_atual'] ?> <?= $p['unidade_medida'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="info-box-static">
                <span class="info-label">Saldo Atual</span>
                <span class="info-value-lg" id="saldo_atual">0</span>
            </div>
            
            <!-- Detalhes -->
            <div class="form-section-title">
                <i class="fas fa-list-alt"></i> Detalhes da Movimentação
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Quantidade *</label>
                        <input type="number" class="form-control" id="mov_quantidade" name="quantidade" 
                               min="1" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Data *</label>
                        <input type="date" class="form-control" id="mov_data" name="data_movimentacao" required>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Tipo de Destino/Origem *</label>
                <select class="form-control" id="mov_tipo_destino" name="tipo_destino_origem" required>
                    <option value="">Selecione...</option>
                    <option value="Interno">Interno</option>
                    <option value="Funcionario">Funcionário</option>
                    <option value="Alojamento_Policia">Alojamento Polícia</option>
                    <option value="Outro">Outro</option>
                    <option value="Fornecedor">Fornecedor</option>
                </select>
            </div>
            
            <!-- Campos dinâmicos baseados no tipo -->
            <div id="campos_interno" style="display: none;">
                <div class="form-group">
                    <label>Buscar Interno *</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="mov_interno_busca" 
                               placeholder="Digite IPEN, nome ou nome social..." autocomplete="off">
                        <button class="btn btn-outline-secondary" type="button" id="btn-limpar-interno">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" id="mov_id_interno" name="id_interno">
                    <div id="internos-sugestoes" class="internos-sugestoes" style="display: none;">
                        <!-- Sugestões aparecerão aqui -->
                    </div>
                    <small class="text-muted">Digite pelo menos 2 caracteres para buscar</small>
                </div>
                <div id="interno-selecionado" style="display: none;">
                    <div class="alert alert-success">
                        <strong>Interno Selecionado:</strong>
                        <span id="interno-info"></span>
                    </div>
                </div>
            </div>
            
            <div id="campos_funcionario" style="display: none;">
                <div class="form-group">
                    <label>Nome do Funcionário</label>
                    <input type="text" class="form-control" id="mov_funcionario" name="id_funcionario">
                </div>
            </div>
            
            <div id="campos_outro" style="display: none;">
                <div class="form-group">
                    <label>Descrição do Destino/Origem</label>
                    <input type="text" class="form-control" id="mov_outro" name="destino_origem_outro">
                </div>
            </div>
            
            <div id="campos_fornecedor" style="display: none;">
                <div class="form-group">
                    <label>Fornecedor</label>
                    <select class="form-control" id="mov_fornecedor" name="id_fornecedor">
                        <option value="">Selecione...</option>
                        <?php foreach($fornecedores as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Documento de Referência</label>
                <input type="text" class="form-control" id="mov_documento" name="documento_referencia" 
                       placeholder="Nota fiscal, guia, etc...">
            </div>
            
            <div class="form-group">
                <label>Motivo da Movimentação *</label>
                <textarea class="form-control" id="mov_motivo" name="motivo_movimentacao" rows="3" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Observações</label>
                <textarea class="form-control" id="mov_observacoes" name="observacoes" rows="2"></textarea>
            </div>
            
            <!-- Botões -->
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Registrar Movimentação
                </button>
                <button type="button" class="btn btn-secondary ml-2" onclick="fecharOffcanvas()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- OFFCANVAS - PRODUTOS ATIVOS -->
<div class="offcanvas-right" id="offcanvasProdutosAtivos">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-box text-info"></i> Produtos Ativos
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <div id="produtos-ativos-content">
            <!-- Conteúdo será carregado via JavaScript -->
            <div class="text-center text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <br><br>
                <span>Carregando produtos...</span>
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - PRODUTOS EM ALERTA -->
<div class="offcanvas-right" id="offcanvasProdutosAlerta">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-exclamation-triangle text-warning"></i> Produtos em Alerta
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <div id="produtos-alerta-content">
            <!-- Conteúdo será carregado via JavaScript -->
            <div class="text-center text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <br><br>
                <span>Carregando produtos em alerta...</span>
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - PRODUTOS CRÍTICOS -->
<div class="offcanvas-right" id="offcanvasProdutosCriticos">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-times-circle text-danger"></i> Produtos em Estoque Crítico
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <div id="produtos-criticos-content">
            <!-- Conteúdo será carregado via JavaScript -->
            <div class="text-center text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <br><br>
                <span>Carregando produtos críticos...</span>
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - MOVIMENTAÇÕES HOJE -->
<div class="offcanvas-right" id="offcanvasMovimentacoesHoje">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-exchange-alt text-success"></i> Movimentações de Hoje
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <!-- Filtros de Data -->
        <div class="form-section-title">
            <i class="fas fa-calendar"></i> Período
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Data Início</label>
                <input type="date" class="form-control" id="relatorio_data_inicio" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-6">
                <label>Data Fim</label>
                <input type="date" class="form-control" id="relatorio_data_fim" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="text-center mb-3">
            <button type="button" class="btn btn-primary" onclick="gerarRelatorioMovimentacoes()">
                <i class="fas fa-search"></i> Gerar Relatório
            </button>
        </div>

        <div id="movimentacoes-relatorio-content">
            <!-- Conteúdo será carregado via JavaScript -->
            <div class="text-center text-muted py-3">
                <i class="fas fa-info-circle fa-2x text-muted"></i>
                <br><br>
                <span>Selecione um período e clique em "Gerar Relatório"</span>
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - GERENCIAR TIPOS -->
<div class="offcanvas-right" id="offcanvasTipos">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-tags text-primary"></i> Gerenciar Tipos de Produto
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <!-- Formulário para novo tipo -->
        <div class="form-section-title">
            <i class="fas fa-plus"></i> Novo Tipo
        </div>

        <form id="formNovoTipo" onsubmit="salvarTipo(event)" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" id="novo_tipo_nome" placeholder="Nome do tipo..." required>
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Adicionar
                    </button>
                </div>
            </div>
        </form>

        <!-- Lista de tipos existentes -->
        <div class="form-section-title">
            <i class="fas fa-list"></i> Tipos Cadastrados
        </div>

        <div id="tipos-list" class="mt-3">
            <!-- Tipos serão carregados via JavaScript -->
            <div class="text-center text-muted py-3">
                <i class="fas fa-spinner fa-spin"></i> Carregando tipos...
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - GERENCIAR ESTOQUES -->
<div class="offcanvas-right" id="offcanvasEstoques">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-warehouse text-secondary"></i> Gerenciar Estoques
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <!-- Formulário para novo estoque -->
        <div class="form-section-title">
            <i class="fas fa-plus"></i> Novo Estoque
        </div>

        <form id="formNovoEstoque" onsubmit="salvarEstoque(event)" class="mb-4">
            <div class="form-group">
                <label>Nome do Estoque *</label>
                <input type="text" class="form-control" id="estoque_nome" placeholder="Ex: Almoxarifado Principal" required>
            </div>

            <div class="form-group">
                <label>Descrição</label>
                <input type="text" class="form-control" id="estoque_descricao" placeholder="Descrição opcional">
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Tipo *</label>
                        <select class="form-control" id="estoque_tipo" required>
                            <option value="Almoxarifado">Almoxarifado</option>
                            <option value="Depósito">Depósito</option>
                            <option value="Prateleira">Prateleira</option>
                            <option value="Gaveta">Gaveta</option>
                            <option value="Estante">Estante</option>
                            <option value="Geral">Geral</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Capacidade Máxima</label>
                        <input type="number" class="form-control" id="estoque_capacidade" placeholder="0 = ilimitada" min="0" value="0">
                    </div>
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Estoque
                </button>
            </div>
        </form>

        <!-- Lista de estoques existentes -->
        <div class="form-section-title">
            <i class="fas fa-list"></i> Estoques Cadastrados
        </div>

        <div id="estoques-list" class="mt-3">
            <!-- Estoques serão carregados via JavaScript -->
            <div class="text-center text-muted py-3">
                <i class="fas fa-spinner fa-spin"></i> Carregando estoques...
            </div>
        </div>
    </div>
</div>

<!-- OFFCANVAS - RELATÓRIOS CUSTOMIZADOS -->
<div class="offcanvas-right" id="offcanvasRelatorios">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">
            <i class="fas fa-print text-warning"></i> Relatórios Customizados
        </h5>
        <button type="button" class="btn-close text-reset" onclick="fecharOffcanvas()">
            <i class="fas fa-times-circle" style="font-size: 1.3rem;"></i>
        </button>
    </div>
    <div class="offcanvas-content">
        <!-- Filtros Gerais -->
        <div class="form-section-title">
            <i class="fas fa-filter"></i> Filtros Gerais
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Data Início</label>
                <input type="date" class="form-control" id="relatorio_data_inicio_geral" value="<?= date('Y-m-01') ?>">
            </div>
            <div class="col-md-6">
                <label>Data Fim</label>
                <input type="date" class="form-control" id="relatorio_data_fim_geral" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Relatórios de Produtos -->
        <div class="form-section-title">
            <i class="fas fa-box"></i> Relatórios de Produtos
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <button type="button" class="btn btn-primary btn-block" onclick="gerarRelatorioProdutosAtivos()">
                    <i class="fas fa-list"></i> Produtos Ativos
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-warning btn-block" onclick="gerarRelatorioProdutosAlerta()">
                    <i class="fas fa-exclamation-triangle"></i> Produtos em Alerta
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <button type="button" class="btn btn-danger btn-block" onclick="gerarRelatorioProdutosCriticos()">
                    <i class="fas fa-times-circle"></i> Produtos Críticos
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-info btn-block" onclick="gerarRelatorioEstoqueBaixo()">
                    <i class="fas fa-chart-bar"></i> Estoque Baixo
                </button>
            </div>
        </div>

        <!-- Relatórios de Movimentações -->
        <div class="form-section-title">
            <i class="fas fa-exchange-alt"></i> Relatórios de Movimentações
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <button type="button" class="btn btn-success btn-block" onclick="gerarRelatorioEntradas()">
                    <i class="fas fa-arrow-down"></i> Entradas
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-danger btn-block" onclick="gerarRelatorioSaidas()">
                    <i class="fas fa-arrow-up"></i> Saídas
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <button type="button" class="btn btn-primary btn-block" onclick="gerarRelatorioMaisEntraram()">
                    <i class="fas fa-trophy"></i> Mais Entraram
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-warning btn-block" onclick="gerarRelatorioMaisSairam()">
                    <i class="fas fa-star"></i> Mais Saíram
                </button>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <button type="button" class="btn btn-info btn-block" onclick="gerarRelatorioNaoRecebemTempo()">
                    <i class="fas fa-clock"></i> Não Recebem a Tempo
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-secondary btn-block" onclick="gerarRelatorioMovimentacoesPeriodo()">
                    <i class="fas fa-calendar"></i> Movimentações por Período
                </button>
            </div>
        </div>

        <!-- Área de Resultados -->
        <div class="form-section-title">
            <i class="fas fa-file-alt"></i> Resultados do Relatório
        </div>

        <div id="relatorios-resultados" class="mt-3" style="max-height: 400px; overflow-y: auto;">
            <div class="text-center text-muted py-4">
                <i class="fas fa-info-circle fa-2x text-muted"></i>
                <br><br>
                <span>Selecione um relatório para visualizar os resultados</span>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // ===== VARIÁVEIS GLOBAIS =====
    if (!window.currentOffcanvas) {
        window.currentOffcanvas = null;
    }
    if (!window.currentProdutoId) {
        window.currentProdutoId = null;
    }
    if (!window.estoqueEmEdicaoId) {
        window.estoqueEmEdicaoId = null;
    }

    // ===== FUNÇÕES DE OFFCANVAS =====
    function exibirOffcanvas(id) {
        // Fechar todos os offcanvs primeiro
        document.querySelectorAll('.offcanvas-right').forEach(oc => {
            oc.classList.remove('show');
            oc.style.transform = 'translateX(100%)';
        });

        // Exibir o offcanvas solicitado
        const offcanvas = document.getElementById(id);
        if (offcanvas) {
            offcanvas.classList.add('show');
            offcanvas.style.transform = 'translateX(0)';
            window.currentOffcanvas = id;

            // Carregar dados específicos se necessário
            if (id === 'offcanvasProduto') {
                atualizarSelectEstoques();
                if (window.currentProdutoId) {
                    carregarProduto(window.currentProdutoId);
                } else {
                    limparFormProduto();
                }
            }
        }
    }

    function fecharOffcanvas() {
        document.querySelectorAll('.offcanvas-right').forEach(oc => {
            oc.classList.remove('show');
            oc.style.transform = 'translateX(100%)';
        });
        window.currentOffcanvas = null;
        window.currentProdutoId = null;
    }

    // ===== FUNÇÕES DE GERENCIAMENTO =====
    function gerenciarProdutos() {
        limparFormProduto();
        exibirOffcanvas('offcanvasProduto');
        carregarListaProdutos();
    }

    function gerenciarFornecedores() {
        limparFormFornecedor();
        exibirOffcanvas('offcanvasFornecedor');
        carregarListaFornecedores();
    }

    // ===== FUNÇÕES DOS CARDS CLICÁVEIS =====
    function mostrarProdutosAtivos() {
        exibirOffcanvas('offcanvasProdutosAtivos');
        carregarProdutosAtivos();
    }

    function mostrarProdutosAlerta() {
        exibirOffcanvas('offcanvasProdutosAlerta');
        carregarProdutosAlerta();
    }

    function mostrarProdutosCriticos() {
        exibirOffcanvas('offcanvasProdutosCriticos');
        carregarProdutosCriticos();
    }

    function mostrarMovimentacoesHoje() {
        exibirOffcanvas('offcanvasMovimentacoesHoje');
    }

    function carregarProdutosAtivos() {
        const container = document.getElementById('produtos-ativos-content');
        container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Carregando produtos...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_produtos_ativos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>${data.produtos.length}</strong> produtos ativos encontrados
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Saldo</th>
                                    <th>Localização</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    let saldoClass = 'success';
                    if (p.quantidade_atual <= p.quantidade_minima) {
                        saldoClass = 'danger';
                    } else if (p.quantidade_atual <= p.quantidade_alerta) {
                        saldoClass = 'warning';
                    }

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td>${p.tipo_nome || 'N/A'}</td>
                            <td><span class="badge badge-${saldoClass}">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.localizacao || '-'}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar produtos</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro de conexão</div>';
        });
    }

    function carregarProdutosAlerta() {
        const container = document.getElementById('produtos-alerta-content');
        container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Carregando produtos em alerta...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_produtos_alerta'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>${data.produtos.length}</strong> produtos precisam de atenção
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Saldo Atual</th>
                                    <th>Mínimo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    const status = p.quantidade_atual <= p.quantidade_minima ? 'CRÍTICO' : 'ALERTA';
                    const statusClass = p.quantidade_atual <= p.quantidade_minima ? 'danger' : 'warning';

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td><span class="badge badge-${statusClass}">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.quantidade_minima} ${p.unidade_medida}</td>
                            <td><span class="badge badge-${statusClass}">${status}</span></td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar produtos</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro de conexão</div>';
        });
    }

    function carregarProdutosCriticos() {
        const container = document.getElementById('produtos-criticos-content');
        container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Carregando produtos críticos...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_produtos_criticos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> <strong>${data.produtos.length}</strong> produtos em situação crítica!
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Saldo Atual</th>
                                    <th>Mínimo</th>
                                    <th>Deficit</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    const deficit = p.quantidade_minima - p.quantidade_atual;

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td><span class="badge badge-danger">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.quantidade_minima} ${p.unidade_medida}</td>
                            <td><span class="badge badge-danger">-${deficit} ${p.unidade_medida}</span></td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar produtos</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro de conexão</div>';
        });
    }

    function gerarRelatorioMovimentacoes() {
        const dataInicio = document.getElementById('relatorio_data_inicio').value;
        const dataFim = document.getElementById('relatorio_data_fim').value;
        const container = document.getElementById('movimentacoes-relatorio-content');

        container.innerHTML = '<div class="text-center text-muted py-5"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        const formData = new FormData();
        formData.append('db_action', 'relatorio_movimentacoes');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Relatório gerado para o período de <strong>${dataInicio}</strong> a <strong>${dataFim}</strong>
                        <br><small><strong>${data.movimentacoes.length}</strong> movimentações encontradas</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Destino</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.movimentacoes.forEach(m => {
                    const tipoClass = m.tipo_movimentacao === 'Entrada' ? 'success' : 'danger';

                    html += `
                        <tr>
                            <td>
                                ${new Date(m.data_movimentacao + ' ' + m.data_cadastro).toLocaleString('pt-BR')}
                            </td>
                            <td><strong>${m.produto_nome}</strong></td>
                            <td><span class="badge badge-${tipoClass}">${m.tipo_movimentacao}</span></td>
                            <td>${m.quantidade} ${m.unidade_medida}</td>
                            <td>${m.destino_origem || '-'}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;

                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-warning"><i class="fas fa-info-circle"></i> Nenhuma movimentação encontrada no período selecionado</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erro ao gerar relatório</div>';
        });
    }

    function carregarListaProdutos() {
        const container = document.getElementById('produtos-list');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Carregando produtos...</div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_produtos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.produtos.length === 0) {
                    html = '<div class="text-center text-muted py-3">Nenhum produto cadastrado</div>';
                } else {
                    data.produtos.forEach(p => {
                        const statusClass = p.status === 'Ativo' ? 'success' : 'secondary';
                        let saldoClass = 'normal';
                        if (p.quantidade_atual <= p.quantidade_minima) {
                            saldoClass = 'estoque-critico';
                        } else if (p.quantidade_atual <= p.quantidade_alerta) {
                            saldoClass = 'estoque-alerta';
                        }

                        html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="flex-grow-1">
                                    <strong>${p.nome}</strong><br>
                                    <small class="text-muted">${p.tipo_nome || 'N/A'} | Saldo:
                                        <span class="badge badge-${saldoClass}">${p.quantidade_atual} ${p.unidade_medida}</span>
                                        <span class="badge badge-${statusClass}">${p.status}</span>
                                    </small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning btn-xs" onclick="editarProduto(${p.id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-xs" onclick="excluirProduto(${p.id}, '${p.nome.replace(/'/g, "\\'")}')" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-center text-danger py-3">Erro ao carregar produtos</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="text-center text-danger py-3">Erro de conexão</div>';
        });
    }

    function carregarListaFornecedores() {
        const container = document.getElementById('fornecedores-list');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin"></i> Carregando fornecedores...</div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_fornecedores'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.fornecedores.length === 0) {
                    html = '<div class="text-center text-muted py-3">Nenhum fornecedor cadastrado</div>';
                } else {
                    data.fornecedores.forEach(f => {
                        const statusClass = f.status === 'Ativo' ? 'success' : 'secondary';

                        html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="flex-grow-1">
                                    <strong>${f.nome}</strong><br>
                                    <small class="text-muted">${f.telefone || 'Sem telefone'} |
                                        <span class="badge badge-${statusClass}">${f.status}</span>
                                    </small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning btn-xs" onclick="editarFornecedor(${f.id})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-xs" onclick="excluirFornecedor(${f.id}, '${f.nome.replace(/'/g, "\\'")}')" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-center text-danger py-3">Erro ao carregar fornecedores</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="text-center text-danger py-3">Erro de conexão</div>';
        });
    }

    // ===== FUNÇÕES DE PRODUTOS =====
    function editarProduto(id) {
        window.currentProdutoId = id;
        carregarProduto(id);
        exibirOffcanvas('offcanvasProduto');
    }

    function excluirProduto(id) {
        if (!confirm('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.')) return;

        const formData = new FormData();
        formData.append('db_action', 'excluir_produto');
        formData.append('id', id);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Produto excluído com sucesso!');
                // Recarregar a lista de produtos
                carregarListaProdutos();
                // Atualizar contadores após exclusão
                atualizarContadores();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir produto');
        });
    }

    // ===== FUNÇÕES DE FORNECEDORES =====
    function editarFornecedor(id) {
        carregarFornecedor(id);
        exibirOffcanvas('offcanvasFornecedor');
    }

    function carregarFornecedor(id) {
        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `db_action=load_fornecedor&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('fornecedor_id').value = data.fornecedor.id;
                document.getElementById('fornecedor_nome').value = data.fornecedor.nome;
                document.getElementById('fornecedor_cnpj_cpf').value = data.fornecedor.cnpj_cpf || '';
                document.getElementById('fornecedor_telefone').value = data.fornecedor.telefone || '';
                document.getElementById('fornecedor_email').value = data.fornecedor.email || '';
                document.getElementById('fornecedor_endereco').value = data.fornecedor.endereco || '';
                document.getElementById('fornecedor_status').value = data.fornecedor.status;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar fornecedor:', error);
            alert('Erro ao carregar fornecedor');
        });
    }

    function excluirFornecedor(id) {
        if (!confirm('Tem certeza que deseja excluir este fornecedor? Esta ação não pode ser desfeita.')) return;

        const formData = new FormData();
        formData.append('db_action', 'excluir_fornecedor');
        formData.append('id', id);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Fornecedor excluído com sucesso!');
                // Recarregar a lista de fornecedores
                carregarListaFornecedores();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir fornecedor');
        });
    }

    function carregarProduto(id) {
        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `db_action=load_produto&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('produto_id').value = data.produto.id;
                document.getElementById('produto_nome').value = data.produto.nome;
                document.getElementById('produto_descricao').value = data.produto.descricao || '';
                document.getElementById('produto_tipo').value = data.produto.id_tipo;
                document.getElementById('produto_fornecedor').value = data.produto.id_fornecedor || '';
                document.getElementById('produto_qtd_minima').value = data.produto.quantidade_minima;
                document.getElementById('produto_qtd_alerta').value = data.produto.quantidade_alerta;
                const selectLocalizacao = document.getElementById('produto_localizacao');
                const localizacaoLegada = document.getElementById('produto_localizacao_legacy');
                if (selectLocalizacao) {
                    if (data.produto.id_estoque) {
                        selectLocalizacao.value = String(data.produto.id_estoque);
                    } else {
                        const localizacaoTexto = data.produto.localizacao || '';
                        const optionLegada = Array.from(selectLocalizacao.options).find(
                            opt => (opt.dataset.nome || '').trim().toLowerCase() === localizacaoTexto.trim().toLowerCase()
                        );
                        selectLocalizacao.value = optionLegada ? optionLegada.value : '';
                    }
                }
                if (localizacaoLegada) {
                    localizacaoLegada.value = data.produto.localizacao || '';
                }
                document.getElementById('produto_unidade').value = data.produto.unidade_medida;
                document.getElementById('produto_status').value = data.produto.status;

                // Atualizar saldo atual
                document.getElementById('produto_saldo_atual').textContent = data.saldo || 0;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar produto:', error);
            alert('Erro ao carregar produto');
        });
    }

    function limparFormProduto() {
        document.getElementById('formProduto').reset();
        document.getElementById('produto_id').value = '';
        document.getElementById('produto_saldo_atual').textContent = '0';
    }

    function salvarProduto(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const selectLocalizacao = document.getElementById('produto_localizacao');
        const hiddenLocalizacao = document.getElementById('produto_localizacao_legacy');
        if (selectLocalizacao && hiddenLocalizacao) {
            const selectedOption = selectLocalizacao.options[selectLocalizacao.selectedIndex];
            hiddenLocalizacao.value = selectedOption ? (selectedOption.dataset.nome || selectedOption.text || '') : '';
        }

        // Adicionar ação
        if (window.currentProdutoId) {
            formData.append('db_action', 'editar_produto');
            formData.append('id', window.currentProdutoId);
        } else {
            formData.append('db_action', 'salvar_produto');
        }

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Produto salvo com sucesso!');
                fecharOffcanvas();
                // Recarregar a lista de produtos
                if (window.currentOffcanvas === 'offcanvasProduto') {
                    carregarListaProdutos();
                }
            } else {
                alert('Erro: ' + (data.message || data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar produto');
        });
    }

    // ===== FUNÇÕES DE FORNECEDORES =====
    function novoFornecedor() {
        limparFormFornecedor();
        exibirOffcanvas('offcanvasFornecedor');
    }

    function limparFormFornecedor() {
        document.getElementById('formFornecedor').reset();
        document.getElementById('fornecedor_id').value = '';
    }

    function salvarFornecedor(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        formData.append('db_action', 'salvar_fornecedor');

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Fornecedor salvo com sucesso!');
                fecharOffcanvas();
                // Recarregar a lista de fornecedores
                if (window.currentOffcanvas === 'offcanvasFornecedor') {
                    carregarListaFornecedores();
                }
                // Atualizar automaticamente sem recarregar página
                aplicarFiltros();
                atualizarContadores();
            } else {
                alert('Erro: ' + (data.message || data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar fornecedor');
        });
    }

    // ===== FUNÇÕES DE MOVIMENTAÇÕES =====
    function novaMovimentacao(tipo) {
        limparFormMovimentacao();
        document.getElementById('mov_tipo').value = tipo;
        atualizarCamposMovimentacao();
        exibirOffcanvas('offcanvasMovimentacao');
    }

    function limparFormMovimentacao() {
        document.getElementById('formMovimentacao').reset();
        document.getElementById('mov_data').value = new Date().toISOString().split('T')[0];
        atualizarCamposDestino();
    }

    function salvarMovimentacao(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        formData.append('db_action', 'salvar_movimentacao');

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Movimentação registrada com sucesso!');
                fecharOffcanvas();
                // Atualizar automaticamente sem recarregar página
                aplicarFiltros();
                atualizarContadores();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao registrar movimentação');
        });
    }

    async function cancelarMovimentacao(id) {
        const result = await Swal.fire({
            title: 'Confirmar Cancelamento?',
            text: "O saldo do produto será revertido automaticamente!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sim, cancelar!',
            cancelButtonText: 'Não, manter',
            reverseButtons: true
        });

        if (result.isConfirmed) {
            // Mostrar loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) loadingOverlay.classList.add('show');

            try {
                const formData = new FormData();
                formData.append('db_action', 'cancelar_movimentacao');
                formData.append('id_mov', id);

                const response = await fetch('includes/censura_estoque_controle_logica.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    await Swal.fire({
                        title: 'Cancelado!',
                        text: 'Movimentação anulada com sucesso.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    });

                    // Atualizar dinamicamente sem recarregar a página
                    await aplicarFiltros();
                    await atualizarContadores();
                } else {
                    // Se for "já cancelada", ainda assim atualizar a tabela
                    if (data.error && data.error.includes('já foi cancelada')) {
                        await aplicarFiltros();
                        await atualizarContadores();
                        await Swal.fire({
                            title: 'Informação',
                            text: 'Esta movimentação já havia sido cancelada.',
                            icon: 'info'
                        });
                    } else {
                        await Swal.fire({
                            title: 'Erro!',
                            text: data.message || data.error || 'Erro desconhecido',
                            icon: 'error'
                        });
                    }
                }
            } catch (error) {
                console.error('Erro:', error);
                await Swal.fire({
                    title: 'Erro de Conexão!',
                    text: 'Não foi possível cancelar a movimentação.',
                    icon: 'error'
                });
            } finally {
                // Esconder loading overlay
                if (loadingOverlay) loadingOverlay.classList.remove('show');
            }
        }
    }

    // ===== FUNÇÕES DE FILTROS =====
    async function aplicarFiltros() {
        // Mostrar loading overlay
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) loadingOverlay.classList.add('show');

        try {
            const formData = new FormData(document.getElementById('formFiltros'));
            formData.append('db_action', 'filtrar_movimentacoes');

            const response = await fetch('includes/censura_estoque_controle_logica.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                atualizarTabelaMovimentacoes(data.movimentacoes);
                // Atualizar contador de registros
                const totalRegistros = document.getElementById('totalRegistros');
                if (totalRegistros) {
                    totalRegistros.textContent = data.movimentacoes.length + ' registros';
                }
            } else {
                await Swal.fire({
                    title: 'Erro nos Filtros',
                    text: data.message || 'Erro desconhecido ao aplicar filtros',
                    icon: 'error'
                });
            }
        } catch (error) {
            console.error('Erro:', error);
            await Swal.fire({
                title: 'Erro de Conexão',
                text: 'Não foi possível aplicar os filtros.',
                icon: 'error'
            });
        } finally {
            // Esconder loading overlay
            if (loadingOverlay) loadingOverlay.classList.remove('show');
        }
    }

    function limparFiltros() {
        document.getElementById('formFiltros').reset();
        // Limpar filtros dinamicamente sem recarregar página
        aplicarFiltros();
    }

    // ===== FUNÇÕES AUXILIARES =====
    function atualizarCamposDestino() {
        const tipoDestino = document.getElementById('mov_tipo_destino').value;
        const camposInterno = document.getElementById('campos_interno');
        const camposFuncionario = document.getElementById('campos_funcionario');
        const camposOutro = document.getElementById('campos_outro');
        const camposFornecedor = document.getElementById('campos_fornecedor');

        // Esconder todos os campos
        if (camposInterno) camposInterno.style.display = 'none';
        if (camposFuncionario) camposFuncionario.style.display = 'none';
        if (camposOutro) camposOutro.style.display = 'none';
        if (camposFornecedor) camposFornecedor.style.display = 'none';

        // Mostrar campos relevantes
        if (tipoDestino === 'Interno' && camposInterno) {
            camposInterno.style.display = 'block';
        } else if (tipoDestino === 'Funcionario' && camposFuncionario) {
            camposFuncionario.style.display = 'block';
        } else if (tipoDestino === 'Outro' && camposOutro) {
            camposOutro.style.display = 'block';
        } else if (tipoDestino === 'Fornecedor' && camposFornecedor) {
            camposFornecedor.style.display = 'block';
        }
    }

    function atualizarCamposMovimentacao() {
        atualizarCamposDestino();
    }

    function buscarInterno() {
        const ipen = document.getElementById('mov_ipen').value.trim();
        const nomeElement = document.getElementById('interno_nome');
        const inputElement = document.getElementById('mov_ipen');

        if (!ipen) {
            if (nomeElement) nomeElement.textContent = '';
            return;
        }

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `db_action=buscar_interno&ipen=${ipen}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (nomeElement) nomeElement.textContent = data.interno.nome;
                if (inputElement) inputElement.style.borderColor = '#28a745';
            } else {
                if (nomeElement) nomeElement.textContent = 'Interno não encontrado';
                if (inputElement) inputElement.style.borderColor = '#dc3545';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            if (nomeElement) nomeElement.textContent = 'Erro na busca';
        });
    }

    function atualizarSaldoProduto() {
        const produtoId = document.getElementById('mov_produto').value;
        const saldoElement = document.getElementById('saldo_atual');

        if (!produtoId || !saldoElement) {
            if (saldoElement) saldoElement.textContent = '0';
            return;
        }

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `db_action=buscar_saldo&id_produto=${produtoId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                saldoElement.textContent = data.saldo;
                saldoElement.className = 'info-value-lg';

                if (data.saldo <= data.minimo) {
                    saldoElement.style.color = '#dc3545';
                } else if (data.saldo <= data.alerta) {
                    saldoElement.style.color = '#ffc107';
                } else {
                    saldoElement.style.color = '#28a745';
                }
            }
        })
        .catch(error => {
            console.error('Erro:', error);
        });
    }

    function atualizarTabelaMovimentacoes(movimentacoes) {
        console.log('Atualizando tabela com', movimentacoes.length, 'movimentações');
        
        const tbody = document.querySelector('.table-movimentacoes tbody');
        if (!tbody) {
            console.error('Tabela .table-movimentacoes tbody não encontrada');
            return;
        }
        
        console.log('Tabela encontrada, limpando conteúdo...');
        
        // Limpar tabela atual
        tbody.innerHTML = '';
        
        if (movimentacoes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center">Nenhuma movimentação encontrada</td></tr>';
            return;
        }
        
        // Reconstruir linhas da tabela
        movimentacoes.forEach(mov => {
            const row = document.createElement('tr');
            
            // Aplicar estilo para movimentações canceladas
            if (mov.status === 'Cancelado') {
                row.className = 'mov-cancelada';
                row.style.opacity = '0.6';
                row.style.textDecoration = 'line-through';
            } else {
                const row_class = (mov.tipo_movimentacao === 'Entrada') ? 'mov-entrada' : 'mov-saida';
                row.className = row_class;
            }
            
            const tipoIcon = mov.tipo_movimentacao === 'Entrada' ? 
                '<i class="fas fa-arrow-down text-success"></i>' : 
                '<i class="fas fa-arrow-up text-danger"></i>';
            
            const statusBadge = mov.status === 'Ativo' ? 
                '<span class="badge badge-success">Ativo</span>' : 
                '<span class="badge badge-secondary">Cancelado</span>';
            
            // Botão de cancelar apenas para movimentações ativas
            const acoesCell = mov.status === 'Ativo' ? 
                `<td class="text-center">
                    <button class="btn btn-xs btn-warning btn-acao" 
                            onclick="if(confirm('Cancelar esta movimentação?')) cancelarMovimentacao(${mov.id})"
                            title="Cancelar">
                        <i class="fas fa-times"></i>
                    </button>
                </td>` : 
                '<td class="text-center"><span class="text-muted">-</span></td>';
            
            row.innerHTML = `
                <td>${mov.data_movimentacao || '-'}</td>
                <td>${tipoIcon} ${mov.tipo_movimentacao}</td>
                <td>${mov.produto_nome || '-'}</td>
                <td>${mov.quantidade} ${mov.unidade_medida || ''}</td>
                <td>${mov.tipo_destino_origem || '-'}</td>
                <td>${mov.interno_nome || mov.funcionario_nome || mov.destino_origem_outro || '-'}</td>
                <td>${mov.motivo_movimentacao || '-'}</td>
                <td>${statusBadge}</td>
                ${acoesCell}
            `;
            
            tbody.appendChild(row);
        });
        
        console.log('Tabela atualizada com sucesso -', movimentacoes.length, 'linhas adicionadas');
    }

    function atualizarContadores() {
        // Buscar estatísticas atualizadas
        const formData = new FormData();
        formData.append('db_action', 'atualizar_estatisticas');
        
        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Atualizar cards estatísticos
                const cards = {
                    'total_produtos': data.total_produtos,
                    'produtos_alerta': data.produtos_alerta, 
                    'produtos_criticos': data.produtos_criticos,
                    'mov_hoje': data.mov_hoje
                };
                
                // Atualizar cada card
                Object.keys(cards).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) {
                        element.textContent = cards[key];
                    }
                });
            }
        })
        .catch(error => console.error('Erro ao atualizar contadores:', error));
    }

    function gerarRelatorio(tipo) {
        alert('Funcionalidade de relatório em desenvolvimento');
    }

    // ===== FUNÇÕES DE TIPOS =====
    function gerenciarTipos() {
        exibirOffcanvas('offcanvasTipos');
        carregarListaTipos();
    }

    function salvarTipo(event) {
        event.preventDefault();
        const nome = document.getElementById('novo_tipo_nome').value.trim();

        if (!nome) return;

        const formData = new FormData();
        formData.append('db_action', 'salvar_tipo');
        formData.append('nome', nome);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('novo_tipo_nome').value = '';
                carregarListaTipos();
                // Atualizar o select de tipos no formulário de produto
                atualizarSelectTipos();
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar tipo');
        });
    }

    function carregarListaTipos() {
        const container = document.getElementById('tipos-list');

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_tipos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.tipos.length === 0) {
                    html = '<div class="text-center text-muted py-3">Nenhum tipo cadastrado</div>';
                } else {
                    data.tipos.forEach(tipo => {
                        html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="flex-grow-1">
                                    <strong>${tipo.nome}</strong>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning btn-xs" onclick="editarTipo(${tipo.id}, '${tipo.nome.replace(/'/g, "\\'")}')" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-xs" onclick="excluirTipo(${tipo.id}, '${tipo.nome.replace(/'/g, "\\'")}')" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Erro ao carregar tipos</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    }

    function editarTipo(id, nomeAtual) {
        const novoNome = prompt('Editar tipo:', nomeAtual);
        if (!novoNome || novoNome.trim() === nomeAtual) return;

        const formData = new FormData();
        formData.append('db_action', 'editar_tipo');
        formData.append('id', id);
        formData.append('nome', novoNome.trim());

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarListaTipos();
                atualizarSelectTipos();
                alert('Tipo atualizado com sucesso!');
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao editar tipo');
        });
    }

    function excluirTipo(id, nome) {
        if (!confirm(`Excluir tipo "${nome}"?`)) return;

        const formData = new FormData();
        formData.append('db_action', 'excluir_tipo');
        formData.append('id', id);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarListaTipos();
                atualizarSelectTipos();
                alert('Tipo excluído com sucesso!');
            } else {
                alert('Erro: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir tipo');
        });
    }

    function atualizarSelectTipos() {
        // Atualizar o select de tipos no formulário de produto
        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_tipos_select'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('produto_tipo');
                if (select) {
                    let options = '<option value="">Selecione...</option>';
                    data.tipos.forEach(tipo => {
                        options += `<option value="${tipo.id}">${tipo.nome}</option>`;
                    });
                    select.innerHTML = options;
                }
            }
        })
        .catch(error => console.error('Erro ao atualizar select de tipos:', error));
    }

    // ===== FUNÇÕES DE ESTOQUES =====
    function gerenciarEstoques() {
        exibirOffcanvas('offcanvasEstoques');
        carregarListaEstoques();
    }

    function salvarEstoque(event) {
        event.preventDefault();
        const nome = document.getElementById('estoque_nome').value.trim();
        const descricao = document.getElementById('estoque_descricao').value.trim();
        const tipo = document.getElementById('estoque_tipo').value;
        const capacidade = document.getElementById('estoque_capacidade').value;

        if (!nome) return;

        const formData = new FormData();
        if (window.estoqueEmEdicaoId) {
            formData.append('db_action', 'editar_estoque');
            formData.append('id', window.estoqueEmEdicaoId);
        } else {
            formData.append('db_action', 'salvar_estoque');
        }
        formData.append('nome', nome);
        formData.append('descricao', descricao);
        formData.append('tipo', tipo);
        formData.append('capacidade_maxima', capacidade);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('formNovoEstoque').reset();
                window.estoqueEmEdicaoId = null;
                carregarListaEstoques();
                atualizarSelectEstoques();
                const submitBtn = document.querySelector('#formNovoEstoque button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Salvar Estoque';
                }
                alert('Estoque salvo com sucesso!');
            } else {
                alert('Erro: ' + (data.message || data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao salvar estoque');
        });
    }

    function carregarListaEstoques() {
        const container = document.getElementById('estoques-list');

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_estoques'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '';
                if (data.estoques.length === 0) {
                    html = '<div class="text-center text-muted py-3">Nenhum estoque cadastrado</div>';
                } else {
                    data.estoques.forEach(estoque => {
                        html += `
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="flex-grow-1">
                                    <strong>${estoque.nome}</strong><br>
                                    <small class="text-muted">${estoque.tipo} |
                                        ${estoque.capacidade_maxima > 0 ? `Capacidade: ${estoque.capacidade_maxima}` : 'Capacidade ilimitada'}
                                        <span class="badge badge-${estoque.status === 'Ativo' ? 'success' : 'secondary'}">${estoque.status}</span>
                                    </small>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning btn-xs" onclick="editarEstoque(${estoque.id}, '${estoque.nome.replace(/'/g, "\\'")}', '${estoque.descricao.replace(/'/g, "\\'")}', '${estoque.tipo}', ${estoque.capacidade_maxima})" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-xs" onclick="excluirEstoque(${estoque.id}, '${estoque.nome.replace(/'/g, "\\'")}')" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                }
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Erro ao carregar estoques</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    }

    function editarEstoque(id, nome, descricao, tipo, capacidade) {
        // Preencher formulário para edição
        document.getElementById('estoque_nome').value = nome;
        document.getElementById('estoque_descricao').value = descricao;
        document.getElementById('estoque_tipo').value = tipo;
        document.getElementById('estoque_capacidade').value = capacidade;
        window.estoqueEmEdicaoId = id;

        // Alterar botão para indicar edição
        const submitBtn = document.querySelector('#formNovoEstoque button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Atualizar Estoque';
    }

    function atualizarEstoque(id) {
        window.estoqueEmEdicaoId = id;
        const form = document.getElementById('formNovoEstoque');
        if (form) form.requestSubmit();
    }

    function excluirEstoque(id, nome) {
        if (!confirm(`Excluir estoque "${nome}"?`)) return;

        const formData = new FormData();
        formData.append('db_action', 'excluir_estoque');
        formData.append('id', id);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                carregarListaEstoques();
                atualizarSelectEstoques();
                alert('Estoque excluído com sucesso!');
            } else {
                alert('Erro: ' + (data.message || data.error || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao excluir estoque');
        });
    }

    function atualizarSelectEstoques() {
        // Atualizar o select de estoques no formulário de produto
        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=listar_estoques_select'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('produto_localizacao');
                if (select) {
                    let options = '<option value="">Selecione um estoque...</option>';
                    data.estoques.forEach(estoque => {
                        options += `<option value="${estoque.id}" data-nome="${estoque.nome}">${estoque.nome} (${estoque.tipo})</option>`;
                    });
                    select.innerHTML = options;
                }
            }
        })
        .catch(error => console.error('Erro ao atualizar select de estoques:', error));
    }

    // ===== FUNÇÕES DE RELATÓRIOS =====
    function gerenciarRelatorios() {
        exibirOffcanvas('offcanvasRelatorios');
    }

    function gerarRelatorioProdutosAtivos() {
        const container = document.getElementById('relatorios-resultados');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=relatorio_produtos_ativos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-primary">
                        <i class="fas fa-box"></i> <strong>${data.produtos.length}</strong> produtos ativos no sistema
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Tipo</th>
                                    <th>Fornecedor</th>
                                    <th>Saldo</th>
                                    <th>Localização</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    let saldoClass = 'success';
                    if (p.quantidade_atual <= p.quantidade_minima) {
                        saldoClass = 'danger';
                    } else if (p.quantidade_atual <= p.quantidade_alerta) {
                        saldoClass = 'warning';
                    }

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td>${p.tipo_nome || 'N/A'}</td>
                            <td>${p.fornecedor_nome || 'N/A'}</td>
                            <td><span class="badge badge-${saldoClass}">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.localizacao || '-'}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    }

    function gerarRelatorioProdutosAlerta() {
        const container = document.getElementById('relatorios-resultados');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=relatorio_produtos_alerta'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>${data.produtos.length}</strong> produtos precisam de atenção
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Saldo Atual</th>
                                    <th>Mínimo</th>
                                    <th>Alerta</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    const status = p.quantidade_atual <= p.quantidade_minima ? 'CRÍTICO' : 'ALERTA';
                    const statusClass = p.quantidade_atual <= p.quantidade_minima ? 'danger' : 'warning';

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td><span class="badge badge-${statusClass}">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.quantidade_minima} ${p.unidade_medida}</td>
                            <td>${p.quantidade_alerta} ${p.unidade_medida}</td>
                            <td><span class="badge badge-${statusClass}">${status}</span></td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    }

    function gerarRelatorioProdutosCriticos() {
        const container = document.getElementById('relatorios-resultados');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=relatorio_produtos_criticos'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> <strong>${data.produtos.length}</strong> produtos em situação crítica!
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Saldo Atual</th>
                                    <th>Mínimo</th>
                                    <th>Deficit</th>
                                    <th>Última Movimentação</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    const deficit = p.quantidade_minima - p.quantidade_atual;

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td><span class="badge badge-danger">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.quantidade_minima} ${p.unidade_medida}</td>
                            <td><span class="badge badge-danger">-${deficit} ${p.unidade_medida}</span></td>
                            <td>${p.ultima_movimentacao || 'Nunca'}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    }

    function gerarRelatorioEstoqueBaixo() {
        const container = document.getElementById('relatorios-resultados');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=relatorio_estoque_baixo'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-info">
                        <i class="fas fa-chart-bar"></i> Produtos com estoque abaixo do nível de alerta
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Saldo</th>
                                    <th>Alerta</th>
                                    <th>Diferença</th>
                                    <th>% Disponível</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    const percentual = p.quantidade_alerta > 0 ? ((p.quantidade_atual / p.quantidade_alerta) * 100).toFixed(1) : 0;
                    const diferenca = p.quantidade_atual - p.quantidade_alerta;

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td><span class="badge badge-warning">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.quantidade_alerta} ${p.unidade_medida}</td>
                            <td><span class="badge badge-warning">${diferenca} ${p.unidade_medida}</span></td>
                            <td><span class="badge badge-info">${percentual}%</span></td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro de conexão</div>';
        });
    }

    function gerarRelatorioEntradas() {
        const dataInicio = document.getElementById('relatorio_data_inicio_geral').value;
        const dataFim = document.getElementById('relatorio_data_fim_geral').value;
        const container = document.getElementById('relatorios-resultados');

        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        const formData = new FormData();
        formData.append('db_action', 'relatorio_entradas');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-success">
                        <i class="fas fa-arrow-down"></i> Relatório de Entradas (${dataInicio} a ${dataFim})
                        <br><small><strong>${data.total_entradas}</strong> entradas totalizando <strong>${data.total_quantidade}</strong> unidades</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Data</th>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Fornecedor</th>
                                    <th>Documento</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.entradas.forEach(e => {
                    html += `
                        <tr>
                            <td>${new Date(e.data_movimentacao).toLocaleDateString('pt-BR')}</td>
                            <td><strong>${e.produto_nome}</strong></td>
                            <td><span class="badge badge-success">+${e.quantidade} ${e.unidade_medida}</span></td>
                            <td>${e.fornecedor_nome || '-'}</td>
                            <td>${e.documento_referencia || '-'}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-warning">Nenhuma entrada encontrada no período</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
        });
    }

    function gerarRelatorioSaidas() {
        const dataInicio = document.getElementById('relatorio_data_inicio_geral').value;
        const dataFim = document.getElementById('relatorio_data_fim_geral').value;
        const container = document.getElementById('relatorios-resultados');

        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        const formData = new FormData();
        formData.append('db_action', 'relatorio_saidas');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-danger">
                        <i class="fas fa-arrow-up"></i> Relatório de Saídas (${dataInicio} a ${dataFim})
                        <br><small><strong>${data.total_saidas}</strong> saídas totalizando <strong>${data.total_quantidade}</strong> unidades</small>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Data</th>
                                    <th>Produto</th>
                                    <th>Quantidade</th>
                                    <th>Destino</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.saidas.forEach(s => {
                    html += `
                        <tr>
                            <td>${new Date(s.data_movimentacao).toLocaleDateString('pt-BR')}</td>
                            <td><strong>${s.produto_nome}</strong></td>
                            <td><span class="badge badge-danger">-${s.quantidade} ${s.unidade_medida}</span></td>
                            <td>${s.destino_origem || '-'}</td>
                            <td>${s.motivo_movimentacao.substring(0, 30)}${s.motivo_movimentacao.length > 30 ? '...' : ''}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-warning">Nenhuma saída encontrada no período</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
        });
    }

    function gerarRelatorioMaisEntraram() {
        const dataInicio = document.getElementById('relatorio_data_inicio_geral').value;
        const dataFim = document.getElementById('relatorio_data_fim_geral').value;
        const container = document.getElementById('relatorios-resultados');

        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        const formData = new FormData();
        formData.append('db_action', 'relatorio_mais_entraram');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-primary">
                        <i class="fas fa-trophy"></i> Produtos que mais entraram (${dataInicio} a ${dataFim})
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Posição</th>
                                    <th>Produto</th>
                                    <th>Total Entradas</th>
                                    <th>Quantidade Total</th>
                                    <th>Fornecedor</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach((p, index) => {
                    html += `
                        <tr>
                            <td><span class="badge badge-primary">${index + 1}º</span></td>
                            <td><strong>${p.produto_nome}</strong></td>
                            <td><span class="badge badge-success">${p.total_entradas} entradas</span></td>
                            <td><span class="badge badge-info">${p.total_quantidade} ${p.unidade_medida}</span></td>
                            <td>${p.fornecedor_nome || '-'}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-warning">Nenhum dado encontrado no período</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
        });
    }

    function gerarRelatorioMaisSairam() {
        const dataInicio = document.getElementById('relatorio_data_inicio_geral').value;
        const dataFim = document.getElementById('relatorio_data_fim_geral').value;
        const container = document.getElementById('relatorios-resultados');

        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        const formData = new FormData();
        formData.append('db_action', 'relatorio_mais_sairam');
        formData.append('data_inicio', dataInicio);
        formData.append('data_fim', dataFim);

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-warning">
                        <i class="fas fa-star"></i> Produtos que mais saíram (${dataInicio} a ${dataFim})
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Posição</th>
                                    <th>Produto</th>
                                    <th>Total Saídas</th>
                                    <th>Quantidade Total</th>
                                    <th>Destino Mais Comum</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach((p, index) => {
                    html += `
                        <tr>
                            <td><span class="badge badge-warning">${index + 1}º</span></td>
                            <td><strong>${p.produto_nome}</strong></td>
                            <td><span class="badge badge-danger">${p.total_saidas} saídas</span></td>
                            <td><span class="badge badge-secondary">${p.total_quantidade} ${p.unidade_medida}</span></td>
                            <td>${p.destino_mais_comum || '-'}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-warning">Nenhum dado encontrado no período</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
        });
    }

    function gerarRelatorioNaoRecebemTempo() {
        const container = document.getElementById('relatorios-resultados');
        container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br><span>Gerando relatório...</span></div>';

        fetch('includes/censura_estoque_controle_logica.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'db_action=relatorio_nao_recebem_tempo'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = `
                    <div class="alert alert-info">
                        <i class="fas fa-clock"></i> Produtos que não recebem a muito tempo
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="thead-pretty">
                                <tr>
                                    <th>Produto</th>
                                    <th>Última Entrada</th>
                                    <th>Dias Sem Receber</th>
                                    <th>Saldo Atual</th>
                                    <th>Fornecedor</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.produtos.forEach(p => {
                    const dias = p.dias_sem_receber || 'Nunca recebeu';
                    let statusClass = 'warning';
                    if (p.dias_sem_receber > 90) statusClass = 'danger';
                    else if (p.dias_sem_receber > 30) statusClass = 'warning';
                    else statusClass = 'info';

                    html += `
                        <tr>
                            <td><strong>${p.nome}</strong></td>
                            <td>${p.ultima_entrada || 'Nunca'}</td>
                            <td><span class="badge badge-${statusClass}">${dias}</span></td>
                            <td><span class="badge badge-secondary">${p.quantidade_atual} ${p.unidade_medida}</span></td>
                            <td>${p.fornecedor_nome || '-'}</td>
                        </tr>
                    `;
                });

                html += `</tbody></table></div>`;
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="alert alert-warning">Nenhum produto encontrado</div>';
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            container.innerHTML = '<div class="alert alert-danger">Erro ao gerar relatório</div>';
        });
    }

    function gerarRelatorioMovimentacoesPeriodo() {
        const dataInicio = document.getElementById('relatorio_data_inicio_geral').value;
        const dataFim = document.getElementById('relatorio_data_fim_geral').value;

        gerarRelatorioMovimentacoes();
    }
    document.addEventListener('DOMContentLoaded', function() {
        atualizarSelectEstoques();

        // Fechar offcanvas com ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && window.currentOffcanvas) {
                fecharOffcanvas();
            }
        });

        // Fechar offcanvas clicando fora
        document.addEventListener('click', function(e) {
            if (window.currentOffcanvas && !e.target.closest('.offcanvas-right') && !e.target.closest('[onclick*="exibirOffcanvas"]')) {
                fecharOffcanvas();
            }
        });

        // Auto-complete para internos
        const ipenInput = document.getElementById('mov_ipen');
        if (ipenInput) {
            let timeout;
            ipenInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(buscarInterno, 500);
            });
        }

        // Atualizar saldo ao selecionar produto
        const produtoSelect = document.getElementById('mov_produto');
        if (produtoSelect) {
            produtoSelect.addEventListener('change', atualizarSaldoProduto);
        }

        // Atualizar campos de destino
        const tipoDestinoSelect = document.getElementById('mov_tipo_destino');
        if (tipoDestinoSelect) {
            tipoDestinoSelect.addEventListener('change', atualizarCamposDestino);
            atualizarCamposDestino(); // Executar uma vez no carregamento
        }
    });
</script>
