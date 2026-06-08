<?php
require_once __DIR__ . '/lista_trabalho_logica.php';
?>

<script>
    window.currentPage = 'lista_trabalho.php';
    window.pageTitle = 'Lista de Trabalho';
</script>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/laboral/lista_trabalho/assets/css/lista_trabalho.css?v=<?= time() ?>">

    <!-- Main content -->
    <section class="content pt-3">
        <div class="container-fluid">
        <!-- CARDS ESTATÍSTICA -->
        <div class="row mb-3 no-print">
            <div class="col-lg-3 col-6">
                <div class="info-box js-stat-card" role="button" tabindex="0" data-stat="total_internos" data-stat-title="Total de Internos" title="Ver detalhes">
                    <div class="info-box-icon bg-info">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Total de Internos</span>
                        <span class="info-box-number"><?= $estatisticas['total_internos'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-success js-stat-card" role="button" tabindex="0" data-stat="ctc_favoraveis" data-stat-title="CTC Favoráveis" title="Ver detalhes">
                    <div class="info-box-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">CTC Favoráveis</span>
                        <span class="info-box-number"><?= $estatisticas['ctc_favoraveis'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-warning js-stat-card" role="button" tabindex="0" data-stat="ctc_desfavoraveis" data-stat-title="CTC Desfavoráveis" title="Ver detalhes">
                    <div class="info-box-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">CTC Desfavoráveis</span>
                        <span class="info-box-number"><?= $estatisticas['ctc_desfavoraveis'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box bg-danger js-stat-card" role="button" tabindex="0" data-stat="ctc_vencidos" data-stat-title="CTC Vencidos" title="Ver detalhes">
                    <div class="info-box-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">CTC Vencidos</span>
                        <span class="info-box-number"><?= $estatisticas['ctc_vencidos'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARDS ADICIONAIS -->
        <div class="row mb-3 no-print">
            <div class="col-lg-4 col-6">
                <div class="info-box bg-primary js-stat-card" role="button" tabindex="0" data-stat="trabalhando" data-stat-title="Trabalhando" title="Ver detalhes">
                    <div class="info-box-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Trabalhando</span>
                        <span class="info-box-number"><?= $estatisticas['trabalhando'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="info-box bg-secondary js-stat-card" role="button" tabindex="0" data-stat="nao_trabalhando" data-stat-title="Não Trabalham" title="Ver detalhes">
                    <div class="info-box-icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Não Trabalham</span>
                        <span class="info-box-number"><?= $estatisticas['nao_trabalhando'] ?></span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-6">
                <div class="info-box bg-dark js-stat-card" role="button" tabindex="0" data-stat="aguardando_ctc" data-stat-title="Aguardando CTC" title="Ver detalhes">
                    <div class="info-box-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="info-box-content">
                        <span class="info-box-text">Aguardando CTC</span>
                        <span class="info-box-number"><?= $estatisticas['aguardando_ctc'] ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD DE FILTROS -->
        <div class="row mb-3 no-print">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-filter mr-2"></i>
                            Filtros e Busca
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="collapse">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-tool btn-sm" data-card-widget="remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="formFiltros" class="mb-0">
                            <div class="row">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="busca">
                                            <i class="fas fa-search mr-1"></i>Busca
                                        </label>
                                        <input type="text" class="form-control" id="busca" name="busca"
                                            placeholder="IPEN ou Nome" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="resultado">
                                            <i class="fas fa-balance-scale mr-1"></i>Resultado
                                        </label>
                                        <select class="form-control" id="resultado" name="resultado">
                                            <option value="">Todos</option>
                                            <option value="Favorável" <?= (isset($_GET['resultado']) && $_GET['resultado'] === 'Favorável') ? 'selected' : '' ?>>Favorável</option>
                                            <option value="Desfavorável" <?= (isset($_GET['resultado']) && $_GET['resultado'] === 'Desfavorável') ? 'selected' : '' ?>>Desfavorável</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="galeria">
                                            <i class="fas fa-door-open mr-1"></i>Galeria
                                        </label>
                                        <input type="text" class="form-control" id="galeria" name="galeria"
                                            placeholder="Galeria" value="<?= htmlspecialchars($_GET['galeria'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="estabelecimento">
                                            <i class="fas fa-building mr-1"></i>Estabelecimento
                                        </label>
                                        <input type="text" class="form-control" id="estabelecimento" name="estabelecimento"
                                            placeholder="Estabelecimento" value="<?= htmlspecialchars($_GET['estabelecimento'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="situacao_ctc">
                                            <i class="fas fa-info-circle mr-1"></i>Situação CTC
                                        </label>
                                        <select class="form-control" id="situacao_ctc" name="situacao_ctc">
                                            <option value="">Todas</option>
                                            <option value="Regular" <?= (isset($_GET['situacao_ctc']) && $_GET['situacao_ctc'] === 'Regular') ? 'selected' : '' ?>>Regular</option>
                                            <option value="Vencido" <?= (isset($_GET['situacao_ctc']) && $_GET['situacao_ctc'] === 'Vencido') ? 'selected' : '' ?>>Vencido</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="mostrar_trabalhando">
                                            <i class="fas fa-eye mr-1"></i>Visualizar
                                        </label>
                                        <select class="form-control" id="mostrar_trabalhando" name="mostrar_trabalhando">
                                            <option value="true" <?= (!isset($_GET['mostrar_trabalhando']) || $_GET['mostrar_trabalhando'] === 'true') ? 'selected' : '' ?>>Todos</option>
                                            <option value="false" <?= (isset($_GET['mostrar_trabalhando']) && $_GET['mostrar_trabalhando'] === 'false') ? 'selected' : '' ?>>Apenas não trabalham</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" onclick="filtrarInternos()">
                                            <i class="fas fa-search mr-1"></i>Filtrar
                                        </button>
                                        <button type="button" class="btn btn-secondary" onclick="limparFiltros()">
                                            <i class="fas fa-eraser mr-1"></i>Limpar
                                        </button>
                                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalListaExclusao">
                                            <i class="fas fa-user-slash mr-1"></i>Lista de Exclusão
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela Principal -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            Lista de Trabalho do Laboral
                            <small class="text-muted">(<?= $total ?> internos - pagina <?= $pagina ?> de <?= $total_paginas ?>)</small>
                        </h3>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool btn-sm" onclick="abrirOffcanvasCadastrarCTC()" title="Novo CTC">
                                <i class="fas fa-plus mr-1"></i>Novo CTC
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm table-bordered w-100" id="tabelaCTCs">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center">IPEN</th>
                                        <th>Nome Completo</th>
                                        <th class="text-center">Local</th>
                                        <th class="text-center">Última Entrada</th>
                                        <th class="text-center">Onde Trabalha</th>
                                        <th class="text-center">Início Trabalho</th>
                                        <th class="text-center">Data CTC</th>
                                        <th class="text-center">Resultado CTC</th>
                                        <th class="text-center">Quando Refazer</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($internos)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">
                                                <i class="fas fa-search fa-2x mb-2"></i><br>
                                                Nenhum interno encontrado com os filtros selecionados.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($internos as $interno): ?>
                                            <tr class="<?= $interno['situacao_ctc'] === 'Vencido' ? 'table-danger' : ($interno['trabalha'] == 'S' ? 'table-success' : '') ?>">
                                                <td class="text-center">
                                                    <strong><?= htmlspecialchars($interno['ipen']) ?></strong>
                                                    <?php if ($interno['prioridade'] > 1000): ?>
                                                        <i class="fas fa-star text-warning" title="Nunca trabalhou"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($interno['nome_completo']) ?></strong>
                                                    </div>
                                                    <small class="text-muted">
                                                        Prioridade: <?= number_format($interno['prioridade'], 0) ?> pontos
                                                    </small>
                                                    <div class="small mt-1">
                                                        <span class="badge badge-<?= ($interno['elegibilidade']['pode'] ?? false) ? 'primary' : 'danger' ?>">
                                                            <?= htmlspecialchars($interno['situacao']) ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-info">
                                                        <?= htmlspecialchars($interno['local_formatado']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($interno['ultima_entrada_cadeia']): ?>
                                                        <?= date('d/m/Y', strtotime($interno['ultima_entrada_cadeia'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($interno['onde_trabalha'] && $interno['onde_trabalha'] != 'Não Trabalha'): ?>
                                                        <span class="badge badge-success">
                                                            <i class="fas fa-briefcase mr-1"></i>
                                                            <?= htmlspecialchars($interno['onde_trabalha']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">
                                                            <i class="fas fa-user-times mr-1"></i>
                                                            Não Trabalha
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($interno['data_inicio_trabalho']): ?>
                                                        <?= date('d/m/Y', strtotime($interno['data_inicio_trabalho'])) ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($interno['data_ctc']): ?>
                                                        <span class="badge badge-<?= ($interno['resultado_ctc'] ?? '') === 'Favorável' ? 'success' : 'warning' ?>">
                                                            <?= date('d/m/Y', strtotime($interno['data_ctc'])) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-light">Sem CTC</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($interno['resultado_ctc']): ?>
                                                        <span class="badge badge-<?= ($interno['resultado_ctc'] ?? '') === 'Favorável' ? 'success' : 'warning' ?>">
                                                            <?= htmlspecialchars($interno['resultado_ctc']) ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($interno['quando_refazer']): ?>
                                                        <?php if ($interno['quando_refazer']['dias_restantes'] <= 0): ?>
                                                            <span class="badge badge-danger">
                                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                                Vencido
                                                            </span>
                                                        <?php elseif ($interno['quando_refazer']['dias_restantes'] <= 30): ?>
                                                            <span class="badge badge-warning">
                                                                <i class="fas fa-clock mr-1"></i>
                                                                <?= $interno['quando_refazer']['dias_restantes'] ?> dias
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge badge-info">
                                                                <?= $interno['quando_refazer']['formatada'] ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge badge-light">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-info" onclick="verDetalhesInterno(<?= $interno['ipen'] ?>)" title="Ver Detalhes">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if (!$interno['data_ctc'] || $interno['situacao_ctc'] === 'Vencido' || $interno['situacao_ctc'] === 'Sem nova data'): ?>
                                                            <button type="button" class="btn btn-sm btn-success" onclick="abrirOffcanvasCadastrarCTC(<?= $interno['ipen'] ?>)" title="Cadastrar CTC">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($interno['data_ctc']): ?>
                                                            <button type="button" class="btn btn-sm btn-warning" onclick="abrirOffcanvasEditarCTC(<?= $interno['ipen'] ?>)" title="Editar CTC">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="excluirCTC(<?= $interno['ipen'] ?>)" title="Excluir CTC">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php if ($total_paginas > 1): ?>
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap">
                            <div class="text-muted small mb-2 mb-md-0">
                                Exibindo <?= count($internos) ?> registros nesta página.
                            </div>
                            <nav aria-label="Paginação da lista de trabalho">
                                <ul class="pagination pagination-sm mb-0">
                                    <?php
                                    $paramsBase = $_GET;
                                    unset($paramsBase['pagina']);
                                    $paginaAnterior = max(1, $pagina - 1);
                                    $paginaProxima = min($total_paginas, $pagina + 1);
                                    ?>
                                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                                        <?php $queryAnterior = http_build_query(array_merge($paramsBase, ['pagina' => $paginaAnterior])); ?>
                                        <a class="page-link" href="#" onclick="navegarComFiltros(false, '<?= htmlspecialchars($queryAnterior) ?>'); return false;">Anterior</a>
                                    </li>
                                    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
                                        <?php $queryPagina = http_build_query(array_merge($paramsBase, ['pagina' => $i])); ?>
                                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                                            <a class="page-link" href="#" onclick="navegarComFiltros(false, '<?= htmlspecialchars($queryPagina) ?>'); return false;"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                                        <?php $queryProxima = http_build_query(array_merge($paramsBase, ['pagina' => $paginaProxima])); ?>
                                        <a class="page-link" href="#" onclick="navegarComFiltros(false, '<?= htmlspecialchars($queryProxima) ?>'); return false;">Próxima</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal Lista de Exclusão -->
<div class="modal fade" id="modalListaExclusao" tabindex="-1" role="dialog" aria-labelledby="modalListaExclusaoTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalListaExclusaoTitle">
                    <i class="fas fa-user-slash mr-2"></i>
                    Lista de Exclusão de Trabalho
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Botão Adicionar -->
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-success" onclick="mostrarFormAdicionarExclusao()">
                            <i class="fas fa-plus mr-1"></i>Adicionar Interno à Lista
                        </button>
                    </div>
                </div>

                <!-- Formulário Adicionar (inicialmente oculto) -->
                <div id="formAdicionarExclusao" class="d-none">
                    <div class="card card-outline card-success mb-3">
                        <div class="card-header">
                            <h6 class="card-title">Novo Item na Lista de Exclusão</h6>
                        </div>
                        <div class="card-body">
                            <form id="formNovaExclusao">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exc_ipen">IPEN:</label>
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" id="exc_ipen" placeholder="Digite IPEN, nome, nome social ou apelido para buscar...">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary" onclick="buscarInternoExclusao()">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div id="exc_nome" class="list-group inicio-busca-resultados" style="display: none;"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exc_motivo">Motivo da Exclusão:</label>
                                            <select class="form-control" id="exc_motivo" onchange="toggleMotivoOutro()">
                                                <option value="">Selecione...</option>
                                                <option value="Extinção de pena">Extinção de pena</option>
                                                <option value="Tornozeleira">Tornozeleira</option>
                                                <option value="Livramento condicional">Livramento condicional</option>
                                                <option value="Prisão albergue">Prisão albergue</option>
                                                <option value="Outro">Outro</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="exc_motivo_outro">Motivo (Outro):</label>
                                            <input type="text" class="form-control" id="exc_motivo_outro" placeholder="Especifique o motivo">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="exc_data_inicio">Data Início:</label>
                                            <input type="date" class="form-control" id="exc_data_inicio" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="exc_data_fim">Data Fim (opcional):</label>
                                            <input type="date" class="form-control" id="exc_data_fim">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <div class="d-flex gap-2">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fas fa-save mr-1"></i>Salvar
                                                </button>
                                                <button type="button" class="btn btn-secondary" onclick="cancelarAdicionarExclusao()">
                                                    Cancelar
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="exc_observacoes">Observações:</label>
                                            <textarea class="form-control" id="exc_observacoes" rows="2" placeholder="Observações sobre a exclusão..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Lista de Exclusão -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title">Internos na Lista de Exclusão</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover" id="tabelaExclusao">
                                <thead class="thead-light">
                                    <tr>
                                        <th>IPEN</th>
                                        <th>Nome</th>
                                        <th>Motivo</th>
                                        <th>Data Início</th>
                                        <th>Data Fim</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-3">
                                            <i class="fas fa-spinner fa-spin"></i> Carregando...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Offcanvas Cadastrar/Editar CTC -->
<div class="offcanvas-custom" tabindex="-1" id="offcanvasCTC">
    <div class="offcanvas-custom-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-clipboard-check mr-2"></i>
            <span id="offcanvasCTCTitle">Cadastrar CTC</span>
        </h5>
        <button type="button" class="btn-close text-reset" data-offcanvas-close>&times;</button>
    </div>
    <form id="formCTC">
        <div class="offcanvas-custom-body">
            <input type="hidden" id="ctc_id" name="id">
            <input type="hidden" id="ctc_ipen" name="ipen">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ctc_interno_nome">Interno:</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="ctc_interno_nome" placeholder="Digite o IPEN ou nome e clique na lupa ou pressione Enter">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="buscarInternoCTC()" title="Buscar Interno">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Digite IPEN ou nome completo para buscar</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ctc_data_ctc">Data do CTC:</label>
                        <input type="date" class="form-control" id="ctc_data_ctc" name="data_ctc" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ctc_resultado">Resultado:</label>
                        <select class="form-control" id="ctc_resultado" name="resultado" required onchange="toggleMotivoDesfavoravel()">
                            <option value="">Selecione...</option>
                            <option value="Favorável">Favorável</option>
                            <option value="Desfavorável">Desfavorável</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="ctc_decisao_juiz">Decisão do Juiz:</label>
                        <input type="text" class="form-control" id="ctc_decisao_juiz" name="decisao_juiz" placeholder="Número da decisão">
                    </div>
                </div>
            </div>

            <div class="row" id="row_motivo_desfavoravel" style="display: none;">
                <div class="col-12">
                    <div class="form-group">
                        <label for="ctc_motivo_desfavoravel">Motivo do Desfavorável:</label>
                        <select class="form-control" id="ctc_motivo_desfavoravel" name="motivo_desfavoravel">
                            <option value="">Selecione...</option>
                            <option value="Falta de vaga">Falta de vaga</option>
                            <option value="Incompatibilidade com atividade">Incompatibilidade com atividade</option>
                            <option value="Risco de fuga">Risco de fuga</option>
                            <option value="Medida disciplinar ativa">Medida disciplinar ativa</option>
                            <option value="Outro">Outro</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="form-group">
                        <label for="ctc_observacoes">Observações:</label>
                        <textarea class="form-control" id="ctc_observacoes" name="observacoes" rows="3" placeholder="Observações adicionais sobre o CTC..."></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Validade do CTC:</strong>
                        <ul class="mb-0">
                            <li><strong>Favorável:</strong> 1 ano para regime fechado, 6 meses para semiaberto</li>
                            <li><strong>Desfavorável:</strong> 6 meses para ambos os regimes</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="offcanvas-custom-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i>Salvar CTC
            </button>
            <button type="button" class="btn btn-secondary" data-offcanvas-close>Cancelar</button>
        </div>
    </form>
</div>

<!-- Offcanvas Detalhes do Interno -->
<div class="offcanvas-custom" tabindex="-1" id="offcanvasDetalhesInterno">
    <div class="offcanvas-custom-header">
        <h5 class="offcanvas-title">
            <i class="fas fa-user mr-2"></i>
            Detalhes do Interno
        </h5>
        <button type="button" class="btn-close text-reset" data-offcanvas-close>&times;</button>
    </div>
    <div class="offcanvas-custom-body">
        <div id="detalhesInternoContent">
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Carregando detalhes...</p>
            </div>
        </div>
    </div>
    <div class="offcanvas-custom-footer">
        <button type="button" class="btn btn-secondary" data-offcanvas-close>Fechar</button>
    </div>


<!-- Scripts -->
<script src="/modulos/laboral/lista_trabalho/assets/js/lista_trabalho.js?v=<?= time() ?>"></script>
