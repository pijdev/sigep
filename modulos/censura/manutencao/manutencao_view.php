<?php
require_once __DIR__ . '/manutencao_logica.php';
?>

<script>
    window.currentPage = 'manutencao_view.php';
    window.pageTitle = 'Controle de Manutenções';
</script>

<!-- CSS específico -->
<link rel="stylesheet" href="/modulos/censura/manutencao/assets/css/manutencao.css?v=<?= time() ?>">

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Cards Resumo -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= number_format($viewData['estatisticas']['pendentes'] ?? 0) ?></h3>
                        <p>Serviços Pendentes</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= number_format($viewData['estatisticas']['executados'] ?? 0) ?></h3>
                        <p>Serviços Executados</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= number_format($viewData['estatisticas']['hoje'] ?? 0) ?></h3>
                        <p>Serviços de Hoje</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= number_format($viewData['estatisticas']['instalacoes'] ?? 0) ?></h3>
                        <p>Instalações Totais</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-tools"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($viewData['acesso_total']): ?>
        <!-- Botões de Ação -->
        <div class="row mb-3">
            <div class="col-12">
                <button class="btn btn-primary" onclick="abrirModalNovoServico()">
                    <i class="fas fa-plus"></i> Novo Serviço
                </button>
                <button class="btn btn-info" onclick="abrirModalConsultaEletronicos()">
                    <i class="fas fa-search"></i> Consultar Eletrônicos
                </button>
                <button class="btn btn-warning" onclick="window.open('/manutencao/controle-manual/', '_blank')">
                    <i class="fas fa-print"></i> Imprimir Controle Manual
                </button>
                <button class="btn btn-secondary" onclick="recarregarDados()">
                    <i class="fas fa-sync"></i> Atualizar
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filtros</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filtro_situacao">Situação</label>
                                <select class="form-control" id="filtro_situacao">
                                    <option value="">Todas</option>
                                    <option value="PENDENTE">Pendentes</option>
                                    <option value="EXECUTADO">Executados</option>
                                    <option value="CANCELADO">Cancelados</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_tipo">Tipo de Serviço</label>
                                <select class="form-control" id="filtro_tipo">
                                    <option value="">Todos</option>
                                    <option value="INSTALACAO">Instalação</option>
                                    <option value="TROCA">Troca</option>
                                    <option value="MANUTENCAO">Manutenção</option>
                                    <option value="REPARO">Reparo</option>
                                    <option value="REMOCAO">Remoção</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_data_inicio">Data Início</label>
                                <input type="date" class="form-control" id="filtro_data_inicio">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_data_fim">Data Fim</label>
                                <input type="date" class="form-control" id="filtro_data_fim">
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <button class="btn btn-outline-primary" onclick="aplicarFiltros()">
                                    <i class="fas fa-filter"></i> Aplicar Filtros
                                </button>
                                <button class="btn btn-outline-secondary" onclick="limparFiltros()">
                                    <i class="fas fa-times"></i> Limpar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Serviços -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Serviços Recentes</h3>
                        <div class="card-tools">
                            <div class="btn-group">
                                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button type="button" class="btn btn-tool" data-card-widget="maximize">
                                    <i class="fas fa-expand"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="tabela_servicos">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Eletrônico</th>
                                        <th>Interno</th>
                                        <th>Cela</th>
                                        <th>Data Solicitação</th>
                                        <th>Data Execução</th>
                                        <th>Situação</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($viewData['servicos'] as $servico): ?>
                                    <tr>
                                        <td><?= $servico['id'] ?></td>
                                        <td>
                                            <span class="badge badge-<?= getTipoServicoBadge($servico['tipo_servico']) ?>">
                                                <?= $servico['tipo_servico'] ?>
                                            </span>
                                        </td>
                                        <td><?= $servico['tipo_item'] ?> - <?= $servico['marca_modelo'] ?></td>
                                        <td><?= $servico['nome_interno'] ?></td>
                                        <td><?= $servico['cela_destino'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($servico['data_solicitacao'])) ?></td>
                                        <td><?= $servico['data_execucao'] ? date('d/m/Y H:i', strtotime($servico['data_execucao'])) : '-' ?></td>
                                        <td>
                                            <span class="badge badge-<?= getSituacaoBadge($servico['status']) ?>">
                                                <?= $servico['status'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-info" onclick="verDetalhes(<?= $servico['id'] ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($viewData['acesso_total'] && $servico['status'] === 'PENDENTE'): ?>
                                                <button class="btn btn-sm btn-success" onclick="executarServico(<?= $servico['id'] ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="cancelarServico(<?= $servico['id'] ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
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

        <!-- Eletrônicos em Estoque -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Eletrônicos em Estoque</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Modelo</th>
                                        <th>Cor</th>
                                        <th>Interno</th>
                                        <th>Dono Original</th>
                                        <th>Data Entrada</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($viewData['eletronicos_estoque'] as $eletronico): ?>
                                    <tr>
                                        <td><?= $eletronico['id'] ?></td>
                                        <td><?= $eletronico['tipo_item'] ?></td>
                                        <td><?= $eletronico['marca_modelo'] ?></td>
                                        <td><?= $eletronico['cor'] ?></td>
                                        <td><?= $eletronico['nome_interno'] ?></td>
                                        <td><?= $eletronico['nome_dono'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($eletronico['data_entrada'])) ?></td>
                                        <td>
                                            <?php if ($viewData['acesso_total']): ?>
                                            <button class="btn btn-sm btn-primary" onclick="selecionarEletronico(<?= $eletronico['id'] ?>, '<?= $eletronico['tipo_item'] ?>', '<?= $eletronico['marca_modelo'] ?>')">
                                                <i class="fas fa-plus"></i> Usar
                                            </button>
                                            <?php endif; ?>
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

    </div>
</section>

<!-- Modal Novo Serviço -->
<div class="modal fade" id="modalNovoServico" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Novo Serviço de Manutenção</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formNovoServico">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tipo_servico">Tipo de Serviço <span class="text-danger">*</span></label>
                                <select class="form-control" id="tipo_servico" required>
                                    <option value="">Selecione...</option>
                                    <option value="INSTALACAO">Instalação</option>
                                    <option value="TROCA">Troca</option>
                                    <option value="MANUTENCAO">Manutenção</option>
                                    <option value="REPARO">Reparo</option>
                                    <option value="REMOCAO">Remoção</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cela_destino">Cela de Destino <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cela_destino" placeholder="Ex: SE-3, A-1, BB-10" required>
                                <small class="form-text text-muted">Formato: Letra-Número (ex: SE-3)</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="busca_eletronico">Eletrônico <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="busca_eletronico" placeholder="Digite para buscar eletrônicos em estoque..." required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="abrirModalBuscaEletronicos()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="id_eletronico" required>
                                <small class="form-text text-muted">Busque por tipo, modelo ou nome do interno</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="busca_interno">Interno <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="busca_interno" placeholder="Digite para buscar interno..." required>
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="button" onclick="abrirModalBuscaInternos()">
                                            <i class="fas fa-search"></i> Buscar
                                        </button>
                                    </div>
                                </div>
                                <input type="hidden" id="ipen_interno" required>
                                <small class="form-text text-muted">Busque por IPEN, nome ou apelido</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="observacoes">Observações</label>
                                <textarea class="form-control" id="observacoes" rows="3" placeholder="Adicione observações sobre o serviço..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="salvarServico()">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Busca Eletrônicos -->
<div class="modal fade" id="modalBuscaEletronicos" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Eletrônicos em Estoque</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="busca_eletronico_modal" placeholder="Digite para buscar...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filtro_tipo_eletronico">
                            <option value="">Todos os tipos</option>
                            <option value="Chuveiro">Chuveiro</option>
                            <option value="TV">TV</option>
                            <option value="Radio">Radio</option>
                            <option value="Ventilador">Ventilador</option>
                            <option value="Chaleira">Chaleira</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filtro_situacao_eletronico">
                            <option value="Estoque">Em Estoque</option>
                            <option value="Na Cela">Na Cela</option>
                            <option value="Retirado">Retirado</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-block" onclick="buscarEletronicosModal()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tipo</th>
                                <th>Modelo</th>
                                <th>Cor</th>
                                <th>Interno</th>
                                <th>Dono Original</th>
                                <th>Data Entrada</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody id="resultados_eletronicos">
                            <!-- Resultados serão carregados via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Busca Internos -->
<div class="modal fade" id="modalBuscaInternos" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Buscar Internos</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="busca_interno_modal" placeholder="Digite para buscar internos...">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-block" onclick="buscarInternosModal()">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>
                </div>

                <div id="resultados_internos">
                    <!-- Resultados serão carregados via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes Serviço -->
<div class="modal fade" id="modalDetalhesServico" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detalhes do Serviço</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="detalhes_servico_conteudo">
                    <!-- Detalhes serão carregados via AJAX -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<?php
// Funções auxiliares para badges
function getTipoServicoBadge($tipo) {
    $badges = [
        'INSTALACAO' => 'primary',
        'TROCA' => 'warning',
        'MANUTENCAO' => 'info',
        'REPARO' => 'secondary',
        'REMOCAO' => 'danger'
    ];
    return $badges[$tipo] ?? 'secondary';
}

function getSituacaoBadge($situacao) {
    $badges = [
        'PENDENTE' => 'warning',
        'EXECUTADO' => 'success',
        'CANCELADO' => 'danger'
    ];
    return $badges[$situacao] ?? 'secondary';
}
?>

<!-- JavaScript específico do módulo -->
<script src="/modulos/censura/manutencao/assets/js/manutencao.js?v=<?= time() ?>"></script>
