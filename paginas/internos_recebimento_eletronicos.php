<?php
// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar este módulo.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

require_once '../includes/internos_recebimento_eletronicos_logica.php';

?>






<link rel="stylesheet" href="../assets/css/internos_recebimento_eletronicos.css">



<section class="content pt-3">

    <div class="container-fluid">



        <?php if(!$periodoValido): ?>

            <?php if($eh_portaria): ?>

                <div class="alert alert-danger">

                    <i class="fas fa-exclamation-triangle"></i> <strong>Bloqueado para Portaria:</strong> Fora do período oficial de entrada. (Permitido apenas: 01 a 10, meses pares).

                </div>

            <?php else: ?>

                <div class="alert alert-warning">

                    <i class="fas fa-calendar-times"></i> <strong>Atenção:</strong> Hoje não é dia oficial de entrada. (Permitido: 01 a 10, meses pares).

                </div>

            <?php endif; ?>

        <?php endif; ?>





        <!-- FORMULÁRIO -->

        <div class="card card-purple shadow mb-4">

            <div class="card-header">

                <h3 class="card-title"><i class="fas fa-plug"></i> Registrar Entrada:</h3>

                <div class="card-tools">

                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>

                </div>

            </div>

            <div class="card-body">

                <form id="formEletro" onsubmit="salvarLote(event)">

                    <input type="hidden" name="acao" value="registrar_lote">

                    <input type="hidden" name="ipen" id="hiddenIpen">

                    <input type="hidden" name="tipo_dono" id="hiddenTipoDono" value="interno">

                    <input type="hidden" name="itensSelecionados" id="itensSelecionados" value="">





                    <!-- LINHA 1: Busca e Interno Selecionado -->

                    <div class="row mb-2">

                        <div class="col-md-5 position-relative">

                            <label class="small text-muted">Buscar Interno ou Setor</label>

                            <div class="input-group input-group-sm">

                                <input type="text" class="form-control form-control-sm" id="buscaInput" placeholder="IPEN, Nome ou Setor..." autocomplete="off">

                                <div class="input-group-append">

                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="buscarInterno()">

                                        <i class="fas fa-search"></i>

                                    </button>

                                </div>

                            </div>

                            <div id="sugestoes" class="search-results"></div>

                        </div>

                        <div class="col-md-4">

                            <label class="small text-muted">Quem está trazendo?</label>

                            <input type="text" class="form-control form-control-sm" name="entregue_por" placeholder="Nome do familiar">

                        </div>

                        <div class="col-md-3">

                            <button type="button" class="btn btn-primary btn-block btn-sm" onclick="abrirOffcanvasItens()">

                                <i class="fas fa-plus"></i> Selecionar Itens

                            </button>

                        </div>

                    </div>





                    <!-- LINHA 2: Interno Selecionado e Resumo -->

                    <div class="row mb-2">

                        <div class="col-md-8">

                            <label class="small text-muted">Selecionado</label>

                            <input type="text" class="form-control form-control-sm" id="nomeSelecionado" readonly placeholder="Nenhum interno selecionado">

                            <div id="tipoDonoSelecionado" style="display:none;" class="mt-1"></div>

                        </div>

                        <div class="col-md-4">

                            <label class="small text-muted">Itens Selecionados</label>

                            <div id="resumoItens" class="alert alert-info py-1 px-2 small">Nenhum item selecionado</div>

                        </div>

                    </div>





                    <!-- BOTÃO FINALIZAR -->

                    <div class="row">

                        <div class="col-12 text-right">

                            <button type="submit" class="btn btn-success">

                                <i class="fas fa-save"></i> Finalizar Entrada

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>



        <!-- TABELA DE REGISTROS RECENTES -->

        <div class="card shadow">

            <div class="card-header border-0">

                <h3 class="card-title">Fluxo de Entrega</h3>

                <div class="card-tools">

                    <button class="btn btn-secondary btn-sm" onclick="imprimirSelecionados('recibo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-receipt"></i> Recibo Visita</button>

                    <?php if (!$eh_portaria): ?>

                        <button class="btn btn-success btn-sm" onclick="imprimirSelecionados('termo')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class="fas fa-file-contract"></i> Entregar (Termo)</button>

                    <?php endif; ?>

                </div>

            </div>

            <div class="card-body table-responsive p-0">

                <!-- CONTROLES DE PAGINAÇÃO E ORDENAÇÃO -->

                <div class="p-3 border-bottom">

                    <div class="row align-items-center">

                        <div class="col-md-6">

                            <small class="text-muted">

                                Mostrando <?= (($page - 1) * $per_page) + 1 ?>-<?= min($page * $per_page, $total_registros) ?> de <?= $total_registros ?> registros

                            </small>

                        </div>

                        <div class="col-md-6 text-right">

                            <div class="d-inline-flex align-items-center">

                                <label class="mb-0 mr-2"><small>Por página:</small></label>

                                <select class="form-control form-control-sm" style="width: auto;" onchange="changePerPage(this.value)">

                                    <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>

                                    <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20</option>

                                    <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>

                                    <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>

                                </select>

                            </div>

                        </div>

                    </div>

                </div>

                <table class="table table-hover table-striped">

                    <thead>

                        <tr>

                            <th width="40"><input type="checkbox" onclick="$('.chk-print').prop('checked', this.checked)"></th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('data_entrada')">

                                    Data Entrada

                                    <?php if($sort_by === 'data_entrada'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('id_interno')">

                                    Interno

                                    <?php if($sort_by === 'id_interno'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('tipo_item')">

                                    Item

                                    <?php if($sort_by === 'tipo_item'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('situacao')">

                                    Situação

                                    <?php if($sort_by === 'situacao'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('entregue_por')">

                                    Origem

                                    <?php if($sort_by === 'entregue_por'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>Ação</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($lista as $r):

                            $nome = $r['nome_social'] ?: $r['nome'];

                            $badge = ($r['situacao'] == 'Na Cela')

                                ? "<span class='badge badge-success'>NA CELA <br><small>".($r['data_entrega_interno'] ? date('d/m', strtotime($r['data_entrega_interno'])) : '')."</small></span>"

                                : ($r['tipo_item'] == 'Chuveiro'
                                    ? "<span class='badge badge-info'>ESTOQUE (MANUTENÇÃO)</span>"
                                    : "<span class='badge badge-warning'>ESTOQUE (CENSURA)</span>");

                        ?>

                        <tr>

                            <td>

                                <input type="checkbox" class="chk-print <?= $r['tipo_item'] == 'Chuveiro' ? 'chuveiro-item' : '' ?>" value="<?= $r['id'] ?>"

                                       data-situacao="<?= $r['situacao'] ?>"
                                       data-tipo-item="<?= $r['tipo_item'] ?>"
                                       <?= $r['tipo_item'] == 'Chuveiro' ? 'disabled title="Chuveiros são gerenciados pelo setor de Manutenção"' : '' ?>>

                            </td>

                            <td><?= date('d/m H:i', strtotime($r['data_entrada'])) ?></td>

                            <td><?= $r['id_interno'] ?> - <?= $nome ?><br><small><?= "{$r['galeria']}-{$r['bloco']}-{$r['res']}" ?></small></td>

                            <td><strong><?= $r['tipo_item'] ?></strong><br><small><?= $r['marca_modelo'] ?> (NF: <?= $r['nota_fiscal'] ?>)</small></td>

                            <td><?= $badge ?></td>

                            <td><?= $r['entregue_por'] ?></td>

                            <td>

                                <?php if (!$eh_portaria): ?>

                                    <button class="btn btn-xs btn-danger" onclick="excluir(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>

                                <?php else: ?>

                                    <button class="btn btn-xs btn-secondary" title="Apenas visualização" disabled>

                                        <i class="fas fa-eye"></i>

                                    </button>

                                <?php endif; ?>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>



                <!-- CONTROLES DE PAGINAÇÃO -->

                <?php if($total_pages > 1): ?>

                <div class="p-3 border-top">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">

                                Página <?= $page ?> de <?= $total_pages ?>

                            </small>

                        </div>

                        <div>

                            <nav aria-label="Paginação">

                                <ul class="pagination pagination-sm mb-0">

                                    <!-- Anterior -->

                                    <?php if($page > 1): ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $page - 1 ?>)">

                                                <i class="fas fa-chevron-left"></i>

                                            </a>

                                        </li>

                                    <?php else: ?>

                                        <li class="page-item disabled">

                                            <a class="page-link" href="#" tabindex="-1">

                                                <i class="fas fa-chevron-left"></i>

                                            </a>

                                        </li>

                                    <?php endif; ?>



                                    <!-- Números das páginas -->

                                    <?php

                                    $start_page = max(1, $page - 2);

                                    $end_page = min($total_pages, $page + 2);



                                    if($start_page > 1): ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(1)">1</a>

                                        </li>

                                        <?php if($start_page > 2): ?>

                                            <li class="page-item disabled">

                                                <span class="page-link">...</span>

                                            </li>

                                        <?php endif; ?>

                                    <?php endif; ?>



                                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>

                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $i ?>)"><?= $i ?></a>

                                        </li>

                                    <?php endfor; ?>



                                    <?php if($end_page < $total_pages): ?>

                                        <?php if($end_page < $total_pages - 1): ?>

                                            <li class="page-item disabled">

                                                <span class="page-link">...</span>

                                            </li>

                                        <?php endif; ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $total_pages ?>)"><?= $total_pages ?></a>

                                        </li>

                                    <?php endif; ?>

                                </ul>

                            </nav>

                        </div>

                    </div>

                </div>

                <?php endif; ?>

            </div>

        </div>



        <!-- OFFCANVAS PARA SELEÇÃO DE ITENS -->

        <div id="offcanvasBackdropItens" class="offcanvas-backdrop-custom" style="display:none;"></div>

        <div class="offcanvas-custom" id="offcanvasItens" aria-hidden="true">

            <div class="offcanvas-header bg-primary text-white">

                <h5 class="offcanvas-title fw-bold">DETALHAMENTO DOS ITENS</h5>

                <button type="button" class="btn-close btn-close-white" onclick="fecharOffcanvasItens()"></button>

            </div>

            <div class="offcanvas-body" style="padding: 0;">

                <div style="max-height: 70vh; overflow-y: auto; padding: 15px;">

                    <?php

                    $itensPermitidos = [

                        'TV' => ['nome' => 'TV', 'campos' => ['marca_modelo', 'polegadas', 'cor', 'estado_conservacao', 'tem_controle', 'tem_fonte', 'nota_fiscal']],

                        'Radio' => ['nome' => 'Rádio', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'tem_fonte', 'nota_fiscal']],

                        'Ventilador' => ['nome' => 'Ventilador', 'campos' => ['marca_modelo', 'tamanho', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Chuveiro' => ['nome' => 'Chuveiro', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Chaleira' => ['nome' => 'Chaleira', 'campos' => ['marca_modelo', 'capacidade', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Maquina Cabelo' => ['nome' => 'Máquina de Corte de Cabelo', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Extensao' => ['nome' => 'Extensão', 'campos' => ['marca_modelo', 'tamanho', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Cabo Antena' => ['nome' => 'Cabo de Antena', 'campos' => ['comprimento', 'estado_conservacao', 'nota_fiscal']],

                        'Antena Digital' => ['nome' => 'Antena', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Bola' => ['nome' => 'Bola', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Banqueta' => ['nome' => 'Banqueta', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Violao' => ['nome' => 'Violão', 'campos' => ['marca_modelo', 'cor', 'estado_conservacao', 'nota_fiscal']],

                        'Outros' => ['nome' => 'Outros', 'campos' => ['nome_item_personalizado', 'descricao_personalizada', 'cor', 'estado_conservacao', 'nota_fiscal']]

                    ];



                    $categorias = [

                        'Eletrônicos' => ['TV', 'Radio', 'Ventilador', 'Chuveiro', 'Chaleira'],

                        'Acessórios' => ['Maquina Cabelo', 'Extensao', 'Cabo Antena', 'Antena Digital'],

                        'Outros' => ['Bola', 'Banqueta', 'Violao', 'Outros']

                    ];



                    foreach($categorias as $categoria => $itensCategoria):

                    ?>

                    <div class="mb-4">

                        <h6 class="text-primary border-bottom pb-1 mb-3">

                            <i class="fas fa-tag"></i> <?= $categoria ?>

                        </h6>

                        <?php foreach($itensCategoria as $key):

                            if(!isset($itensPermitidos[$key])) continue;

                            $itemConfig = $itensPermitidos[$key];

                        ?>

                        <div class="card mb-2 border-light item-card-offcanvas" data-item="<?= $key ?>">

                            <div class="card-body p-3">

                                <div class="d-flex align-items-center justify-content-between mb-2">

                                    <div class="d-flex align-items-center">

                                        <div class="custom-control custom-checkbox mr-3">

                                            <input type="checkbox" class="custom-control-input chk-item-offcanvas"

                                                   id="chk_off_<?= $key ?>" data-item="<?= $key ?>">

                                            <label class="custom-control-label font-weight-bold" for="chk_off_<?= $key ?>">

                                                <?= $itemConfig['nome'] ?>

                                            </label>

                                        </div>

                                    </div>

                                    <button type="button" class="btn btn-outline-secondary btn-sm btn-detalhes"

                                            onclick="toggleDetalhes('<?= $key ?>')" style="display: none;">

                                        <i class="fas fa-edit"></i>

                                    </button>

                                </div>



                                <div class="detalhes-item" id="detalhes_<?= str_replace(' ', '', $key) ?>" style="display: none;">

                                    <div class="row mt-3">

                                        <?php if($key === 'Outros'): ?>

                                        <!-- Outros: Nome do Item, Descrição, Cor, Estado, Nota Fiscal -->

                                        <div class="col-md-4">

                                            <input type="text" class="form-control form-control-sm" id="nome_item_<?= $key ?>" placeholder="Nome do Item">

                                        </div>

                                        <div class="col-md-4">

                                            <input type="text" class="form-control form-control-sm" id="descricao_<?= $key ?>" placeholder="Descrição detalhada">

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="cor_<?= $key ?>">

                                                <option value="Preto">Preto</option><option value="Branco">Branco</option>

                                                <option value="Cinza">Cinza</option><option value="Azul">Azul</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php elseif($key === 'TV'): ?>

                                        <!-- TV: Marca/Modelo, Polegadas, Cor, Estado, Controle, Nota Fiscal -->

                                        <div class="col-md-3">

                                            <select class="form-control form-control-sm" id="marca_<?= $key ?>">

                                                <option value="">Selecione...</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="polegadas_<?= $key ?>">

                                                <option value="">Polegadas</option>

                                                <option value="14">14"</option>

                                                <option value="21">21"</option>

                                                <option value="24">24"</option>

                                                <option value="29">29"</option>

                                                <option value="32">32"</option>

                                                <option value="40">40"</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="cor_<?= $key ?>">

                                                <option value="Preto">Preto</option><option value="Branco">Branco</option>

                                                <option value="Cinza">Cinza</option><option value="Prata">Prata</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="tem_controle_<?= $key ?>">

                                                <option value="Não">Sem Controle</option>

                                                <option value="Sim">Com Controle</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php elseif($key === 'Violao'): ?>

                                        <!-- Violão: Marca/Modelo, Cor, Estado, Nota Fiscal -->

                                        <div class="col-md-5">

                                            <select class="form-control form-control-sm" id="marca_<?= $key ?>">

                                                <option value="">Selecione...</option>

                                            </select>

                                        </div>

                                        <div class="col-md-3">

                                            <select class="form-control form-control-sm" id="cor_<?= $key ?>">

                                                <option value="Marrom">Marrom</option><option value="Preto">Preto</option><option value="Natural">Natural</option><option value="Vermelho">Vermelho</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php elseif($key === 'Maquina Cabelo'): ?>

                                        <!-- Máquina de Cabelo: Marca/Modelo, Cor, Estado, Nota Fiscal -->

                                        <div class="col-md-5">

                                            <select class="form-control form-control-sm" id="marca_<?= $key ?>">

                                                <option value="">Selecione...</option>

                                            </select>

                                        </div>

                                        <div class="col-md-3">

                                            <select class="form-control form-control-sm" id="cor_<?= $key ?>">

                                                <option value="Preto">Preto</option><option value="Branco">Branco</option><option value="Cinza">Cinza</option><option value="Rosa">Rosa</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php elseif($key === 'Cabo Antena'): ?>

                                        <!-- Cabo de Antena: Comprimento, Estado, Nota Fiscal -->

                                        <div class="col-md-3">

                                            <input type="text" class="form-control form-control-sm" id="comprimento_<?= $key ?>" placeholder="Comprimento (metros)">

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php elseif($key === 'Antena Digital'): ?>

                                        <!-- Antena Digital: Marca/Modelo, Estado, Nota Fiscal -->

                                        <div class="col-md-5">

                                            <select class="form-control form-control-sm" id="marca_<?= $key ?>">

                                                <option value="">Selecione...</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php elseif($key === 'Banqueta'): ?>

                                        <!-- Banqueta: Marca/Modelo, Cor (só branco), Estado, Nota Fiscal -->

                                        <div class="col-md-5">

                                            <select class="form-control form-control-sm" id="marca_<?= $key ?>">

                                                <option value="">Selecione...</option>

                                            </select>

                                        </div>

                                        <div class="col-md-3">

                                            <select class="form-control form-control-sm" id="cor_<?= $key ?>">

                                                <option value="Branco">Branco</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm" id="nf_<?= $key ?>" placeholder="Nº Nota/Cupom Fiscal">

                                        </div>

                                        <?php else: ?>

                                        <div class="col-md-5">

                                            <select class="form-control form-control-sm"

                                                   id="marca_<?= $key ?>">

                                                <option value="">Selecione...</option>

                                            </select>

                                        </div>

                                        <div class="col-md-2">

                                            <select class="form-control form-control-sm" id="estado_<?= $key ?>">

                                                <option value="Novo">Novo</option><option value="Usado">Usado</option>

                                            </select>

                                        </div>

                                        <div class="col-md-12 mt-2">

                                            <input type="text" class="form-control form-control-sm"

                                                   id="nf_<?= $key ?>" placeholder="Nº Nota Fiscal">

                                        </div>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </div>

                        </div>

                        <?php endforeach; ?>

                    </div>

                    <?php endforeach; ?>

                </div>



                <!-- RODAPÉ DO OFFCANVAS -->

                <div class="border-top p-3 bg-light">

                    <div class="d-flex justify-content-between align-items-center">

                        <div id="contadorItens" class="text-muted small">0 itens selecionados</div>

                        <div>

                            <button type="button" class="btn btn-secondary btn-sm mr-2" onclick="limparSelecao()">

                                <i class="fas fa-times"></i> Limpar

                            </button>

                            <button type="button" class="btn btn-primary btn-sm" onclick="aplicarSelecao()">

                                <i class="fas fa-check"></i> Aplicar

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>



        <!-- TABELA DE REGISTROS RECENTES -->

        <div class="card shadow">

            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

                <span class="fw-bold">Histórico Recente de Entradas</span>

                <input type="text" class="form-control form-control-sm w-auto" placeholder="Filtrar nesta página...">

            </div>

            <div class="card-body table-responsive p-0">

                <!-- CONTROLES DE PAGINAÇÃO E ORDENAÇÃO -->

                <div class="p-3 border-bottom">

                    <div class="row align-items-center">

                        <div class="col-md-6">

                            <small class="text-muted">

                                Mostrando <?= (($page - 1) * $per_page) + 1 ?>-<?= min($page * $per_page, $total_registros) ?> de <?= $total_registros ?> registros

                            </small>

                        </div>

                        <div class="col-md-6 text-right">

                            <div class="d-inline-flex align-items-center">

                                <label class="mb-0 mr-2"><small>Por página:</small></label>

                                <select class="form-control form-control-sm" style="width: auto;" onchange="changePerPage(this.value)">

                                    <option value="10" <?= $per_page == 10 ? 'selected' : '' ?>>10</option>

                                    <option value="20" <?= $per_page == 20 ? 'selected' : '' ?>>20</option>

                                    <option value="50" <?= $per_page == 50 ? 'selected' : '' ?>>50</option>

                                    <option value="100" <?= $per_page == 100 ? 'selected' : '' ?>>100</option>

                                </select>

                            </div>

                        </div>

                    </div>

                </div>

                <table class="table table-hover table-striped">

                    <thead>

                        <tr>

                            <th width="40"><input type="checkbox" onclick="$('.chk-print').prop('checked', this.checked)"></th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('data_entrada')">

                                    Data Entrada

                                    <?php if($sort_by === 'data_entrada'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('id_interno')">

                                    Interno

                                    <?php if($sort_by === 'id_interno'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('tipo_item')">

                                    Item

                                    <?php if($sort_by === 'tipo_item'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('situacao')">

                                    Situação

                                    <?php if($sort_by === 'situacao'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>

                                <a href="#" class="text-decoration-none" onclick="sortTable('entregue_por')">

                                    Origem

                                    <?php if($sort_by === 'entregue_por'): ?>

                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>

                                    <?php else: ?>

                                        <i class="fas fa-sort text-muted"></i>

                                    <?php endif; ?>

                                </a>

                            </th>

                            <th>Ação</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($lista as $r):

                            $nome = $r['nome_social'] ?: $r['nome'];

                            $badge = ($r['situacao'] == 'Na Cela')

                                ? "<span class='badge badge-success'>NA CELA <br><small>".($r['data_entrega_interno'] ? date('d/m', strtotime($r['data_entrega_interno'])) : '')."</small></span>"

                                : ($r['tipo_item'] == 'Chuveiro'
                                    ? "<span class='badge badge-info'>ESTOQUE (MANUTENÇÃO)</span>"
                                    : "<span class='badge badge-warning'>ESTOQUE (CENSURA)</span>");

                        ?>

                        <tr>

                            <td>

                                <input type="checkbox" class="chk-print <?= $r['tipo_item'] == 'Chuveiro' ? 'chuveiro-item' : '' ?>" value="<?= $r['id'] ?>"

                                       data-situacao="<?= $r['situacao'] ?>"
                                       data-tipo-item="<?= $r['tipo_item'] ?>"
                                       <?= $r['tipo_item'] == 'Chuveiro' ? 'disabled title="Chuveiros são gerenciados pelo setor de Manutenção"' : '' ?>>

                            </td>

                            <td><?= date('d/m H:i', strtotime($r['data_entrada'])) ?></td>

                            <td><?= $r['id_interno'] ?> - <?= $nome ?><br><small><?= "{$r['galeria']}-{$r['bloco']}-{$r['res']}" ?></small></td>

                            <td><strong><?= $r['tipo_item'] ?></strong><br><small><?= $r['marca_modelo'] ?> (NF: <?= $r['nota_fiscal'] ?>)</small></td>

                            <td><?= $badge ?></td>

                            <td><?= $r['entregue_por'] ?></td>

                            <td>

                                <?php if (!$eh_portaria): ?>

                                    <button class="btn btn-xs btn-danger" onclick="excluir(<?= $r['id'] ?>)"><i class="fas fa-trash"></i></button>

                                <?php else: ?>

                                    <button class="btn btn-xs btn-secondary" title="Apenas visualização" disabled>

                                        <i class="fas fa-eye"></i>

                                    </button>

                                <?php endif; ?>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>



                <!-- CONTROLES DE PAGINAÇÃO -->

                <?php if($total_pages > 1): ?>

                <div class="p-3 border-top">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <small class="text-muted">

                                Página <?= $page ?> de <?= $total_pages ?>

                            </small>

                        </div>

                        <div>

                            <nav aria-label="Paginação">

                                <ul class="pagination pagination-sm mb-0">

                                    <!-- Anterior -->

                                    <?php if($page > 1): ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $page - 1 ?>)">

                                                <i class="fas fa-chevron-left"></i>

                                            </a>

                                        </li>

                                    <?php else: ?>

                                        <li class="page-item disabled">

                                            <a class="page-link" href="#" tabindex="-1">

                                                <i class="fas fa-chevron-left"></i>

                                            </a>

                                        </li>

                                    <?php endif; ?>



                                    <!-- Números das páginas -->

                                    <?php

                                    $start_page = max(1, $page - 2);

                                    $end_page = min($total_pages, $page + 2);



                                    if($start_page > 1): ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(1)">1</a>

                                        </li>

                                        <?php if($start_page > 2): ?>

                                            <li class="page-item disabled">

                                                <span class="page-link">...</span>

                                            </li>

                                        <?php endif; ?>

                                    <?php endif; ?>



                                    <?php for($i = $start_page; $i <= $end_page; $i++): ?>

                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $i ?>)"><?= $i ?></a>

                                        </li>

                                    <?php endfor; ?>



                                    <?php if($end_page < $total_pages): ?>

                                        <?php if($end_page < $total_pages - 1): ?>

                                            <li class="page-item disabled">

                                                <span class="page-link">...</span>

                                            </li>

                                        <?php endif; ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $total_pages ?>)"><?= $total_pages ?></a>

                                        </li>

                                    <?php endif; ?>



                                    <!-- Próximo -->

                                    <?php if($page < $total_pages): ?>

                                        <li class="page-item">

                                            <a class="page-link" href="#" onclick="goToPage(<?= $page + 1 ?>)">

                                                <i class="fas fa-chevron-right"></i>

                                            </a>

                                        </li>

                                    <?php else: ?>

                                        <li class="page-item disabled">

                                            <a class="page-link" href="#" tabindex="-1">

                                                <i class="fas fa-chevron-right"></i>

                                            </a>

                                        </li>

                                    <?php endif; ?>

                                </ul>

                            </nav>

                        </div>

                    </div>

                </div>

                <?php endif; ?>

            </div>

        </div>



    </div>

</section>



<script src="../assets/js/internos_recebimento_eletronicos.js"></script>
