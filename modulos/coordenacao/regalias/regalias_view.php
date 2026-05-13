<?php
require_once __DIR__ . '/regalias_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Painel de Regalias</h1>
    </div>
    <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="#">Coordenação</a></li>
            <li class="breadcrumb-item active">Painel Regalias</li>
        </ol>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info pointer" onclick="filtrarRegalias('todos')">
                    <div class="inner">
                        <h3 id="stats-total"><?php echo count($regalias); ?></h3>
                        <p>Total Regalias</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success pointer" onclick="filtrarRegalias('alimentacao')">
                    <div class="inner">
                        <h3 id="stats-alimentacao"><?php echo count(array_filter($regalias, fn($r) => $r['regalia_setor'] === 'Alimentação')); ?></h3>
                        <p>Alimentação</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning pointer" onclick="filtrarRegalias('fundo')">
                    <div class="inner">
                        <h3 id="stats-fundo"><?php echo count(array_filter($regalias, fn($r) => $r['regalia_setor'] === 'Fundo Rotativo')); ?></h3>
                        <p>Fundo Rotativo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-coins"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger pointer" onclick="filtrarRegalias('corte')">
                    <div class="inner">
                        <h3 id="stats-corte"><?php echo count(array_filter($regalias, fn($r) => $r['regalia_setor'] === 'Corte de Cabelo')); ?></h3>
                        <p>Corte de Cabelo</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-cut"></i>
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
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-galeria">Galeria</label>
                                    <select class="form-control" id="filtro-galeria">
                                        <option value="">Todas</option>
                                        <option value="A">Galeria A</option>
                                        <option value="B">Galeria B</option>
                                        <option value="C">Galeria C</option>
                                        <option value="D">Galeria D</option>
                                        <option value="E">Galeria E</option>
                                        <option value="G">Galeria G</option>
                                        <option value="H">Galeria H</option>
                                        <option value="S">Galeria S</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="filtro-setor">Setor</label>
                                    <select class="form-control" id="filtro-setor">
                                        <option value="">Todos</option>
                                        <option value="Alimentação">Alimentação</option>
                                        <option value="Fundo Rotativo">Fundo Rotativo</option>
                                        <option value="Corte de Cabelo">Corte de Cabelo</option>
                                        <option value="Conveniado - Hospital">Conveniado - Hospital</option>
                                        <option value="Manutenção">Manutenção</option>
                                        <option value="Limpeza">Limpeza</option>
                                        <option value="OBRAS">OBRAS</option>
                                        <option value="Serviços Gerais">Serviços Gerais</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="busca-nome">Buscar por Nome ou IPEN</label>
                                    <input type="text" class="form-control" id="busca-nome" placeholder="Digite o nome ou IPEN...">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="button" class="btn btn-primary btn-block" onclick="aplicarFiltros()">
                                        <i class="fas fa-search mr-2"></i>Buscar
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="button" class="btn btn-default btn-sm" onclick="limparFiltros()">
                                    <i class="fas fa-times mr-2"></i>Limpar Filtros
                                </button>
                                <button type="button" class="btn btn-success btn-sm ml-2" onclick="exportarDados()">
                                    <i class="fas fa-download mr-2"></i>Exportar Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Regalias -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            Lista de Regalias
                            <small class="text-muted ml-2">
                                (<span id="contador-regalias"><?php echo count($regalias); ?></span> encontrados)
                            </small>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" onclick="atualizarDados()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover" id="tabela-regalias">
                                <thead>
                                    <tr>
                                        <th>IPEN</th>
                                        <th>Nome</th>
                                        <th>Galeria</th>
                                        <th>Bloco</th>
                                        <th>Setor</th>
                                        <th>Função</th>
                                        <th>Dias Trabalho</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="tabela-corpo">
                                    <?php foreach ($regalias as $regalia): ?>
                                        <tr data-galeria="<?php echo $regalia['galeria']; ?>" data-setor="<?php echo $regalia['regalia_setor']; ?>">
                                            <td><?php echo $regalia['ipen']; ?></td>
                                            <td><?php echo htmlspecialchars($regalia['nome']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getCorGaleria($regalia['galeria']); ?>">
                                                    <?php echo $regalia['galeria']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo $regalia['bloco']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo getCorSetor($regalia['regalia_setor']); ?>">
                                                    <?php echo htmlspecialchars($regalia['regalia_setor']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo getFuncaoDescricao($regalia['regalia_setor']); ?></td>
                                            <td><?php echo $regalia['dias_trabalho']; ?></td>
                                            <td>
                                                <span class="badge badge-success">Ativo</span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" onclick="mostrarDetalhes(<?php echo $regalia['ipen']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-warning" onclick="editarRegalia(<?php echo $regalia['ipen']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos e Estatísticas -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-pie mr-2"></i>
                            Distribuição por Setor
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="grafico-setor" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-chart-bar mr-2"></i>
                            Distribuição por Galeria
                        </h3>
                    </div>
                    <div class="card-body">
                        <canvas id="grafico-galeria" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal Detalhes -->
<div class="modal fade" id="modal-detalhes" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Regalia</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal-detalhes-conteudo">
                <!-- Conteúdo carregado via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="modulos/coordenacao/regalias/assets/js/regalias.js"></script>
