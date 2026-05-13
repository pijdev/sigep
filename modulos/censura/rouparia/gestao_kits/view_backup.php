<?php
require_once __DIR__ . '/controller.php';
?>

<script>
    // Dados PHP passados para JavaScript (mantido aqui porque usa variáveis PHP)
    window.currentPage = 'gestao_kits';
    window.pageTitle = 'Rouparia';
    window.CENSURA_ROUPARIA_BOOTSTRAP = {
        sortBy: <?= json_encode($sort_by) ?>,
        sortOrder: <?= json_encode($sort_order) ?>,
        regaliasOcupadas: <?= json_encode(array_values($regalias_ocupadas)) ?>
    };

    // Variáveis de sessão para controle de acesso
    window.phpSessionVars = {
        user_nome: <?= json_encode($_SESSION['user_nome'] ?? '') ?>,
        usuario: <?= json_encode($_SESSION['usuario'] ?? '') ?>,
        userId: <?= json_encode($_SESSION['user_id'] ?? '') ?>,
        user_admin: <?= json_encode($_SESSION['user_admin'] ?? false) ?>,
        perm_censura: <?= json_encode($_SESSION['perm_censura'] ?? 0) ?>
    };
</script>
<link rel="stylesheet" href="/modulos/censura/rouparia/gestao_kits/assets/css/style.css">

<!-- Main content -->
<section class="content pt-3">
    <div class="container-fluid">
        <!-- CARDS ESTATÍSTICA -->
        <div class="row mb-3 no-print">
            <div class="col-lg-4 col-12" onclick="window.exibOff('off_liv')" style="cursor:pointer">

                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= count($k_livres_tela) ?></h3>
                        <p>Kits Disponíveis</p>
                        <div class="small text-muted">
                            <span class="badge badge-light"><?= count($kits_livres_para_fazer) ?> Livres para Fazer</span> |
                            <span class="badge badge-warning"><?= count($kits_prontos_livres) ?> Prontos para Entrega</span>
                        </div>
                    </div>
                    <div class="icon"><i class="fas fa-box-open"></i></div>
                    <a href="#" class="small-box-footer">Ver Detalhes <i class="fas fa-arrow-circle-right"></i></a>
                </div>

            </div>
            <div class="col-lg-4 col-12" onclick="window.exibOff('off_sem')" style="cursor:pointer">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= count($sem_k_tela) ?></h3>
                        <p>Internos Sem Kit</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-times"></i></div>
                    <a href="#" class="small-box-footer">Ver Lista <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-4 col-12" onclick="window.exibOff('off_rep')" style="cursor:pointer">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= count(array_unique($duplicados_ids)) ?></h3>
                        <p>Conflitos / Repetidos</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <a href="#" class="small-box-footer">Resolver <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- BARRA DE AÇÕES ORGANIZADA -->
        <div class="row mb-4 no-print">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-start" style="gap: 10px;">

                        <div class="d-flex align-items-center border-right pr-3 mr-1">
                            <span class="badge badge-light mr-2 text-muted"><i class="fas fa-file-alt"></i> RELATÓRIOS</span>
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="window.open(window.getRelatorioUrl('completo', 'nome'), '_blank')" title="Alfabética" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                    <i class="fas fa-sort-alpha-down"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info" onclick="window.open(window.getRelatorioUrl('completo', 'kit'), '_blank')" title="Numérica" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                    <i class="fas fa-sort-numeric-down"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open(window.getRelatorioUrl('lavanderia'), '_blank')" title="Lavanderia" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                    <i class="fas fa-tshirt"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="window.open(window.getRelatorioUrl('filtros'), '_blank')" title="Filtros Atuais" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex align-items-center border-right pr-3 mr-1">
                            <span class="badge badge-light mr-2 text-muted"><i class="fas fa-box"></i> KITS</span>
                            <button type="button" class="btn btn-sm btn-info" onclick="window.exibOff('off_cadastrar_kit')">
                                <i class="fas fa-plus mr-1"></i> Cadastrar
                            </button>
                        </div>

                        <div class="d-flex align-items-center">
                            <span class="badge badge-light mr-2 text-muted"><i class="fas fa-award"></i> REGALIAS</span>
                            <div class="btn-group shadow-sm">
                                <button type="button" class="btn btn-sm btn-warning" onclick="window.exibOff('off_regalias_fechado')">
                                    <i class="fas fa-lock mr-1"></i> Fechado
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="window.exibOff('off_regalias_semiaberto')">
                                    <i class="fas fa-lock-open mr-1"></i> Semiaberto
                                </button>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <span class="badge badge-light mr-2 text-muted"><i class="fas fa-tshirt"></i> ROUPA CIVIL</span>
                            <div class="btn-group shadow-sm">
                                <button type="button" class="btn btn-sm btn-info" onclick="window.exibOff('off_roupa_civil')">
                                    <i class="fas fa-plus mr-1"></i> Cadastrar
                                </button>
                            </div>
                        </div>

                        <div class="d-flex align-items-center">
                            <span class="badge badge-light mr-2 text-muted"><i class="fas fa-history"></i> HISTÓRICO</span>
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.exibOff('off_historico_kit')">
                                <i class="fas fa-search mr-1"></i> Kit
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS -->
        <div class="card card-dark no-print">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Filtros de Pesquisa</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button></div>
            </div>
            <div class="card-body">
                <form action="/modulos/censura/rouparia/gestao_kits/view.php" method="GET" id="formFiltro" onsubmit="window.reloadContent(this.action + '?' + $(this).serialize()); return false;">
                    <!-- Campos ocultos para manter ordenação -->
                    <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sort_by) ?>">
                    <input type="hidden" name="sort_order" value="<?= htmlspecialchars($sort_order) ?>">
                    <div class="row">
                        <div class="col-md-3 form-group"><label>Pesquisa (Nome/IPEN)</label><input type="text" class="form-control form-control-sm" name="search" placeholder="Digite..." value="<?= htmlspecialchars($f['search']) ?>"></div>
                        <div class="col-md-2 form-group"><label>Situação</label><select class="form-control form-control-sm" name="situacao">
                                <option value="">Tudo</option><?php foreach ($situacoes as $s) echo "<option value='$s' " . ($f['situacao'] == $s ? 'selected' : '') . ">$s</option>"; ?>
                            </select></div>
                        <div class="col-md-1 form-group"><label>Galeria</label>
                            <select class="form-control form-control-sm" name="galeria">
                                <option value="">Todas</option>
                                <?php foreach ($galerias_db as $g) echo "<option value='$g' " . ($f['galeria'] == $g ? 'selected' : '') . ">$g</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-1 form-group"><label>Bloco</label>
                            <select class="form-control form-control-sm" name="bloco">
                                <option value="">Todos</option>
                                <?php foreach ($blocos_db as $b) echo "<option value='$b' " . ($f['bloco'] == $b ? 'selected' : '') . ">$b</option>"; ?>
                            </select>
                        </div>
                        <div class="col-md-1 form-group"><label>Cela</label><input type="text" class="form-control form-control-sm" name="res" value="<?= htmlspecialchars($f['res']) ?>"></div>
                        <div class="col-md-2 form-group"><label>Regalia</label><select class="form-control form-control-sm" name="regalia">
                                <option value="">Tudo</option>
                                <option value="S" <?= $f['regalia'] == 'S' ? 'selected' : '' ?>>Sim (Regalia)</option>
                                <option value="N" <?= $f['regalia'] == 'N' ? 'selected' : '' ?>>Não (Comum)</option>
                            </select></div>
                        <div class="col-md-2 form-group"><label>Cor Roupa</label><select class="form-control form-control-sm" name="cor">
                                <option value="">Tudo</option>
                                <option value="Laranja" <?= $f['cor'] == 'Laranja' ? 'selected' : '' ?>>Laranja</option>
                                <option value="Verde" <?= $f['cor'] == 'Verde' ? 'selected' : '' ?>>Verde</option>
                            </select></div>
                    </div>
                    <div class="row">
                        <div class="col-md-1 form-group"><label style="color:#ffc107">Kit Nº</label><input type="number" class="form-control form-control-sm border-warning" name="kit_num" placeholder="Ex: 100" value="<?= htmlspecialchars($f['kit_num']) ?>"></div>
                        <div class="col-md-1 form-group"><label style="color:#ffc107">Reg. Kit Nº</label><input type="number" class="form-control form-control-sm border-warning" name="regkit_num" placeholder="Ex: 50" value="<?= htmlspecialchars($f['regkit_num']) ?>"></div>
                        <div class="col-md-2 form-group"><label>Setor Trabalho</label><select class="form-control form-control-sm" name="setor">
                                <option value="">Todos</option><?php foreach ($setores_db as $s) echo "<option value='$s' " . ($f['setor'] == $s ? 'selected' : '') . ">$s</option>"; ?>
                            </select></div>
                        <div class="col-md-2 form-group"><label>Tamanho</label><select class="form-control form-control-sm" name="tam">
                                <option value="">Todos</option><?php foreach (['P', 'M', 'G', 'G1', 'G2', 'G3'] as $t) echo "<option value='$t' " . ($f['tam'] == $t ? 'selected' : '') . ">$t</option>"; ?>
                            </select></div>
                        <div class="col-md-2 form-group"><label>Status Interno</label><select class="form-control form-control-sm" name="status">
                                <option value="A" <?= $f['status'] == 'A' ? 'selected' : '' ?>>Ativos</option>
                                <option value="I" <?= $f['status'] == 'I' ? 'selected' : '' ?>>Inativos</option>
                            </select></div>
                        <div class="col-md-4 d-flex align-items-end mb-3 gap-2">
                            <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-search"></i> FILTRAR AGORA</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.reloadContent('/modulos/censura/rouparia/gestao_kits/view.php')">Limpar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABELA PRINCIPAL -->
        <div class="card shadow">
            <div class="card-body table-responsive p-0">
                <table class="table table-head-fixed table-hover table-sm align-middle" id="tabIn">
                    <thead class="thead-pretty text-center">
                        <tr>
                            <th style="width: 80px"><?= generateSortLink('ipen', 'IPEN') ?></th>
                            <th><?= generateSortLink('nome', 'NOME COMPLETO') ?></th>
                            <th style="width: 90px"><?= generateSortLink('local', 'LOCAL') ?></th>
                            <th><?= generateSortLink('situacao', 'SITUAÇÃO') ?></th>
                            <th style="width: 60px"><?= generateSortLink('regalia', 'REG.') ?></th>
                            <th style="width: 90px"><?= generateSortLink('cor', 'COR') ?></th>
                            <th style="width: 80px"><?= generateSortLink('kit', 'KIT') ?></th>
                            <th style="width: 80px"><?= generateSortLink('kit_reg', 'KIT REG.') ?></th>
                            <th style="width: 80px"><?= generateSortLink('tam', 'TAM.') ?></th>
                            <th style="width: 100px">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($internos as $i): ?>
                            <tr class="row-<?= $i['ct'] ?> <?= $i['isR'] ? 'row-repetido' : '' ?> <?= $i['social_class'] ?>" data-ipen="<?= $i['ipen'] ?>">
                                <td class="text-center fw-bold"><?= $i['ipen'] ?></td>
                                <td class="small font-weight-bold"><?= $i['nome_exib'] ?></td>
                                <td class="text-center"><?= "{$i['galeria']}{$i['bloco']}-{$i['res']}" ?></td>
                                <td class="small"><?= $i['sit'] ?></td>
                                <td class="text-center"><span class="badge-btn <?= $i['regalia'] == 'S' ? 'b-sim' : 'b-nao' ?>" onclick="window.abrirEdicao(<?= htmlspecialchars(json_encode($i)) ?>)"><?= $i['regalia'] == 'S' ? 'SIM' : 'NÃO' ?></span></td>
                                <td><?= $i['cor_roupa'] ?: 'Laranja' ?></td>
                                <td class="text-center"><input type="number" class="input-edit-sm kit-v" data-old="<?= $i['kit'] ?>" value="<?= $i['kit'] ?>" oninput="window.checkChanges(<?= $i['ipen'] ?>)"></td>
                                <td class="text-center"><input type="number" class="input-edit-sm kr-v" data-old="<?= $i['kr'] ?>" value="<?= $i['kr'] ?>" <?= $i['regalia'] !== 'S' ? 'disabled' : '' ?> oninput="window.checkChanges(<?= $i['ipen'] ?>)"></td>
                                <td class="text-center"><select class="form-control form-control-sm p-0 text-center tam-v <?= ($i['tamanho_kit'] != 'G') ? 'tam-especial' : '' ?>" data-old="<?= $i['tamanho_kit'] ?>" onchange="window.checkChanges(<?= $i['ipen'] ?>)"><?php foreach (['P', 'M', 'G', 'G1', 'G2', 'G3'] as $t) echo "<option value='$t' " . ($i['tamanho_kit'] == $t ? 'selected' : '') . ">$t</option>"; ?></select></td>
                                <td class="text-center"><button class="btn btn-xs btn-success btn-block btn-save font-weight-bold" onclick="window.confirmarSalvar(<?= $i['ipen'] ?>)"><i class="fas fa-save"></i> SALVAR</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <span class="float-left text-muted small">Mostrando <?= count($internos) ?> de <?= $count_total ?> registros.</span>
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if ($page > 1): ?><li class="page-item"><a class="page-link" href="#" onclick="window.reloadContent('<?= getPagLink($page - 1) ?>'); return false;">«</a></li><?php endif; ?>
                    <li class="page-item disabled"><a class="page-link" href="#">Pág <?= $page ?> de <?= $total_paginas ?></a></li>
                    <?php if ($page < $total_paginas): ?><li class="page-item"><a class="page-link" href="#" onclick="window.reloadContent('<?= getPagLink($page + 1) ?>'); return false;">»</a></li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- OFFCANVAS DE EDIÇÃO DE CADASTRO -->
<div class="offcanvas-right" id="offcanvasCadastro">
    <div class="offcanvas-header">
        <h5 class="m-0 font-weight-bold">Editar Cadastro de Interno</h5>
        <button type="button" class="btn-close text-reset" onclick="window.fecharEdicaoCadastro()" style="background: none; border: none; color: white; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Fechar"><i class="fas fa-times-circle" style="font-size: 1.3rem;"></i></button>
    </div>
    <div class="offcanvas-content">
        <form id="formEditCadastro" onsubmit="window.salvarCadastro(event)">
            <?php include_once __DIR__ . '/../includes/internos_cadastro_formulario.php'; ?>
        </form>
    </div>
</div>

<!-- OFFCANVAS DE RESUMOS -->
<div class='offcanvas-custom' id='off_liv' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 400px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom bg-light'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <h5 class='m-0 text-dark'>KITS DISPONÍVEIS</h5>
            <div><button class='btn btn-sm btn-default mr-1' onclick="window.open('/modulos/censura/rouparia/gestao_kits/view.php?execute_print=1&mode=vagas', '_blank')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class='fas fa-print'></i> Imprimir</button><button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_liv', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button></div>
        </div>
        <div class="input-group input-group-sm"><input type="number" id="buscaKitInput" class="form-control" placeholder="Buscar kit..." onkeypress="if(event.key==='Enter') window.buscarKit()">
            <div class="input-group-append"><button class="btn btn-primary" onclick="window.buscarKit()"><i class="fas fa-search"></i></button></div>
        </div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 120px); overflow-y: auto; color: #333;'>

        <!-- Kits 100% Livres -->
        <div class='grid-kits mb-4'>
            <?php foreach ($kits_disponiveis_tela as $kit): ?>
                <div class='kit-badge <?= $kit['extra_class'] ?>' id='<?= $kit['badge_id'] ?>' title='<?= $kit['title'] ?>'><?= $kit['numero'] ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Seção de Quarentena -->
        <?php if (!empty($kits_quarentena_tela)): ?>
            <hr class="my-3">
            <h6 class="font-weight-bold text-orange mb-2">
                <i class="fas fa-hourglass-half mr-1"></i> EM QUARENTENA (7 Dias)
            </h6>
            <div class="small text-muted mb-2" style="font-size: 0.75rem; line-height: 1.2;">
                Estes números estão reservados aguardando o possível retorno do interno. Serão liberados automaticamente na data indicada.
            </div>

            <table class="table table-sm table-striped small border">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center">Kit</th>
                        <th>Interno (Saiu)</th>
                        <th class="text-center">Libera em</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kits_quarentena_tela as $kq): ?>
                        <tr>
                            <td class="text-center font-weight-bold text-orange" style="vertical-align: middle; font-size: 1rem;"><?= $kq['kit'] ?></td>
                            <td style="vertical-align: middle;">
                                <span class="font-weight-bold"><?= mb_strtoupper($kq['nome_exib']) ?></span>
                                <div style="font-size: 0.65rem; color: #666;">Saiu: <?= date('d/m', strtotime($kq['data_inativo'])) ?></div>
                            </td>
                            <td class="text-center font-weight-bold" style="vertical-align: middle; color: #28a745;">
                                <?= $kq['data_lib'] ?>
                                <div style="font-size: 0.65rem; color: #666; font-weight: normal;">(<?= $kq['dias_restantes'] ?> dias)</div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>

<div class='offcanvas-custom' id='off_sem' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 500px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom d-flex justify-content-between align-items-center bg-light'>
        <h5 class='m-0 text-dark'>Internos sem Kit</h5>
        <div><button class='btn btn-sm btn-default mr-1' onclick="window.open('modulos/censura/rouparia/gestao_kits/view.php?execute_print=1&mode=sem_kit', '_blank')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class='fas fa-print'></i> Imprimir</button><button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_sem', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button></div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 70px); overflow-y: auto; color: #333;'>
        <table class='table table-sm table-striped small'>
            <thead>
                <tr>
                    <th>IPEN</th>
                    <th>NOME</th>
                    <th>LOCAL</th>
                </tr>
            </thead>
            <tbody><?php foreach ($sem_k_tela as $sk): ?>
                    <tr>
                        <td><?= $sk['ipen'] ?></td>
                        <td><?= $sk['nome_formatado'] ?></td>
                        <td><?= "{$sk['galeria']}{$sk['bloco']}-{$sk['res']}" ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class='offcanvas-custom' id='off_rep' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 500px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom d-flex justify-content-between align-items-center bg-light'>
        <h5 class='m-0 text-dark'>Kits Repetidos</h5>
        <div><button class='btn btn-sm btn-default mr-1' onclick="window.open('modulos/censura/rouparia/gestao_kits/view.php?execute_print=1&mode=conflitos', '_blank')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class='fas fa-print'></i> Imprimir</button><button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_rep', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button></div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 70px); overflow-y: auto; color: #333;'>
        <table class='table table-sm table-striped small'>
            <thead>
                <tr>
                    <th>KIT</th>
                    <th>IPEN</th>
                    <th>NOME</th>
                    <th>LOCAL</th>
                </tr>
            </thead>
            <tbody><?php
                    // Garantir que temos dados dos repetidos
                    $dados_repetidos = !empty($repet_tela) ? $repet_tela : ($rep_lista ?? []);
                    foreach ($dados_repetidos as $r):
                        $nome_exib = !empty($r['nome_social']) ? "<b>{$r['nome_social']}</b>" : $r['nome'];
                    ?>
                    <tr>
                        <td class='text-danger font-weight-bold'><?= $r['kit'] ?></td>
                        <td><?= $r['ipen'] ?></td>
                        <td><?= $nome_exib ?></td>
                        <td><?= "{$r['galeria']}{$r['bloco']}-{$r['res']}" ?></td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- OFFCANVAS CADASTRAR KIT -->
<div class='offcanvas-custom' id='off_cadastrar_kit' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 500px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom bg-light'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <h5 class='m-0 text-dark'>Cadastrar Kit Pronto</h5>
            <div><button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_cadastrar_kit', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button></div>
        </div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 120px); overflow-y: auto; color: #333;'>
        <!-- Formulário -->
        <form id="formCadastrarKit" onsubmit="window.cadastrarKit(event)">
            <input type="hidden" name="acao" value="cadastrar_kit_pronto">
            <input type="hidden" name="kit_id" value="">
            <div class="form-section-title">
                <i class="fas fa-plus-circle"></i> Novo Kit Pronto
            </div>
            <div class="form-group">
                <label>Número do Kit</label>
                <input type="number" class="form-control" name="kit_numero" required placeholder="Ex: 100">
            </div>
            <div class="form-group">
                <label>Cor das Roupas</label>
                <select class="form-control" name="cor" required>
                    <option value="">Selecione</option>
                    <option value="Laranja">Laranja</option>
                    <option value="Verde">Verde</option>
                </select>
            </div>
            <div class="form-group">
                <label>Informações Adicionais</label>
                <textarea class="form-control" name="info_adicional" rows="3" placeholder="Ex: Faltou 1 toalha por falta de estoque"></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-save"></i> CADASTRAR KIT PRONTO
            </button>
        </form>

        <!-- Histórico -->
        <hr class="my-4">
        <div class="form-section-title">
            <i class="fas fa-history"></i> Últimos Kits Cadastrados
        </div>
        <div>
            <table class="table table-sm table-striped small">
                <thead class="bg-light">
                    <tr>
                        <th>Kit</th>
                        <th>Cor</th>
                        <th>Data</th>
                        <th>Info</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="kitsHistoryTable">
                    <?php foreach ($kits_prontos as $kp): ?>
                        <tr id="row-<?= $kp['id'] ?>">
                            <td class="font-weight-bold text-primary"><?= $kp['kit_numero'] ?></td>
                            <td><span class="badge badge-<?= $kp['cor'] == 'Laranja' ? 'warning' : 'success' ?>"><?= $kp['cor'] ?></span></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime($kp['data_cadastro'])) ?></td>
                            <td class="small" title="<?= htmlspecialchars($kp['info_adicional'] ?? '') ?>"><?= mb_strimwidth($kp['info_adicional'] ?? '', 0, 20, '...') ?></td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="excluirKit(<?= $kp['id'] ?>)">Excluir</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- OFFCANVAS REGALIAS FECHADO -->
<div class='offcanvas-custom' id='off_regalias_fechado' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom bg-light'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <h5 class='m-0 text-dark'><i class="fas fa-award mr-2"></i>REGALIAS FECHADO (1-35)</h5>
            <div>
                <button class='btn btn-sm btn-default mr-1' onclick="window.open('modulos/censura/rouparia/gestao_kits/view.php?execute_print=1&mode=regalias_fechado', '_blank')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class='fas fa-print'></i> Imprimir</button>
                <button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_regalias_fechado', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button>
            </div>
        </div>
        <div class="input-group input-group-sm mb-3">
            <input type="number" id="buscaRegaliaInput" class="form-control" placeholder="Pesquisar por número de kit..." onkeypress="if(event.key==='Enter') window.buscarRegalia()">
            <div class="input-group-append">
                <button class="btn btn-primary" onclick="window.buscarRegalia()"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 120px); overflow-y: auto; color: #333;'>

        <!-- Resumo de Regalias -->
        <div class="row mb-3">
            <div class="col-6">
                <div class="card border-success">
                    <div class="card-body text-center p-2">
                        <h4 class="text-success mb-0"><?= count($regalias_disponiveis) ?></h4>
                        <small class="text-muted">Disponíveis</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-warning">
                    <div class="card-body text-center p-2">
                        <h4 class="text-warning mb-0"><?= count($regalias_ocupadas) ?></h4>
                        <small class="text-muted">Ocupadas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Regalias Disponíveis -->
        <h6 class="font-weight-bold text-success mb-2">
            <i class="fas fa-check-circle mr-1"></i> DISPONÍVEIS (1-35)
        </h6>
        <div class='grid-kits mb-4'>
            <?php foreach ($regalias_disponiveis as $num): ?>
                <div class='kit-badge bg-light text-success border-success'><?= $num ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Lista de Regalias Ocupadas -->
        <?php if (!empty($regalias_fechado_tela)): ?>
            <hr class="my-3">
            <h6 class="font-weight-bold text-warning mb-2">
                <i class="fas fa-users mr-1"></i> REGALIAS ATRIBUÍDAS
            </h6>
            <div class="small text-muted mb-2" style="font-size: 0.75rem; line-height: 1.2;">
                Relação de internos do regime fechado com regalias atribuídas (excluídos semi-abertos G e S).
            </div>

            <table class="table table-sm table-striped small border">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center">IPEN</th>
                        <th>Nome Completo</th>
                        <th class="text-center">Local</th>
                        <th class="text-center">Kit Padrão</th>
                        <th class="text-center">Kit Regalia</th>
                        <th class="text-center">Setor</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($regalias_fechado_tela as $reg):
                        if ($reg['mudou_setor'] && $reg['setor_atual'] !== '') {
                    ?>
                            <tr>
                                <td colspan="7" class="text-center bg-light font-weight-bold" style="border-top: 2px solid #dee2e6;">
                                    <i class="fas fa-briefcase mr-1"></i> SETOR: <?= $reg['setor_atual'] ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        <tr data-reg-ipen="<?= $reg['ipen'] ?>">
                            <td class="text-center font-weight-bold"><?= $reg['ipen'] ?></td>
                            <td class="small"><?= $reg['nome_exib'] ?></td>
                            <td class="text-center small"><?= "{$reg['galeria']}{$reg['bloco']}-{$reg['res']}" ?></td>
                            <td class="text-center small">
                                <input type="number" class="form-control form-control-sm input-edit-sm kit-reg-v"
                                    data-old="<?= $reg['kit'] ?>" value="<?= $reg['kit'] ?>"
                                    onchange="window.checkRegaliaChanges(<?= $reg['ipen'] ?>)">
                            </td>
                            <td class="text-center small">
                                <input type="number" class="form-control form-control-sm input-edit-sm regalia-kit-v"
                                    data-old="<?= $reg['regalia_kit'] ?>" value="<?= $reg['regalia_kit'] ?>"
                                    onchange="window.checkRegaliaChanges(<?= $reg['ipen'] ?>)">
                            </td>
                            <td class="text-center small">
                                <input type="text" class="form-control form-control-sm input-edit-sm setor-v"
                                    data-old="<?= htmlspecialchars($reg['regalia_setor']) ?>" value="<?= htmlspecialchars($reg['regalia_setor']) ?>"
                                    onchange="window.checkRegaliaChanges(<?= $reg['ipen'] ?>)">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-success btn-block btn-save-regalia font-weight-bold"
                                    onclick="window.confirmarSalvarRegalia(<?= $reg['ipen'] ?>)">
                                    <i class="fas fa-save"></i> SALVAR
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>

<!-- OFFCANVAS REGALIAS SEMIABERTO -->
<div class='offcanvas-custom' id='off_regalias_semiaberto' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom bg-light'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <h5 class='m-0 text-dark'><i class="fas fa-award mr-2"></i>REGALIAS SEMIABERTO (1-50)</h5>
            <div>
                <button class='btn btn-sm btn-default mr-1' onclick="window.open('modulos/censura/rouparia/gestao_kits/view.php?execute_print=1&mode=regalias_semiaberto', '_blank')" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>><i class='fas fa-print'></i> Imprimir</button>
                <button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_regalias_semiaberto', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button>
            </div>
        </div>
        <div class="input-group input-group-sm mb-3">
            <input type="number" id="buscaRegaliaSemiInput" class="form-control" placeholder="Pesquisar por número de kit..." onkeypress="if(event.key==='Enter') window.buscarRegaliaSemi()">
            <div class="input-group-append">
                <button class="btn btn-primary" onclick="window.buscarRegaliaSemi()"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 120px); overflow-y: auto; color: #333;'>

        <!-- Resumo de Regalias -->
        <div class="row mb-3">
            <div class="col-6">
                <div class="card border-success">
                    <div class="card-body text-center p-2">
                        <h4 class="text-success mb-0"><?= count($regalias_disponiveis_semi) ?></h4>
                        <small class="text-muted">Disponíveis (1-50)</small>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-warning">
                    <div class="card-body text-center p-2">
                        <h4 class="text-warning mb-0"><?= count($regalias_ocupadas_semi) ?></h4>
                        <small class="text-muted">Ocupadas</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de Regalias Disponíveis -->
        <h6 class="font-weight-bold text-success mb-2">
            <i class="fas fa-check-circle mr-1"></i> DISPONÍVEIS (1-50)
        </h6>
        <div class='grid-kits mb-4'>
            <?php foreach ($regalias_disponiveis_semi as $num): ?>
                <div class='kit-badge bg-light text-success border-success'><?= $num ?></div>
            <?php endforeach; ?>
        </div>

        <!-- Lista de Regalias Ocupadas -->
        <?php if (!empty($regalias_semiaberto_tela)): ?>
            <hr class="my-3">
            <h6 class="font-weight-bold text-warning mb-2">
                <i class="fas fa-users mr-1"></i> REGALIAS ATRIBUÍDAS
            </h6>
            <div class="small text-muted mb-2" style="font-size: 0.75rem; line-height: 1.2;">
                Relação de internos do regime semiaberto com regalias atribuídas (apenas galeria S).
            </div>

            <table class="table table-sm table-striped small border">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center">IPEN</th>
                        <th>Nome Completo</th>
                        <th class="text-center">Local</th>
                        <th class="text-center">Kit Padrão</th>
                        <th class="text-center">Kit Regalia</th>
                        <th class="text-center">Setor</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($regalias_semiaberto_tela as $reg):
                        if ($reg['mudou_setor'] && $reg['setor_atual'] !== '') {
                    ?>
                            <tr>
                                <td colspan="7" class="text-center bg-light font-weight-bold" style="border-top: 2px solid #dee2e6;">
                                    <i class="fas fa-briefcase mr-1"></i> SETOR: <?= $reg['setor_atual'] ?>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                        <tr data-reg-semi-ipen="<?= $reg['ipen'] ?>">
                            <td class="text-center font-weight-bold"><?= $reg['ipen'] ?></td>
                            <td class="small"><?= $reg['nome_exib'] ?></td>
                            <td class="text-center small"><?= "{$reg['galeria']}{$reg['bloco']}-{$reg['res']}" ?></td>
                            <td class="text-center small">
                                <input type="number" class="form-control form-control-sm input-edit-sm kit-reg-semi-v"
                                    data-old="<?= $reg['kit'] ?>" value="<?= $reg['kit'] ?>"
                                    onchange="window.checkRegaliaSemiChanges(<?= $reg['ipen'] ?>)">
                            </td>
                            <td class="text-center small">
                                <input type="number" class="form-control form-control-sm input-edit-sm regalia-kit-semi-v"
                                    data-old="<?= $reg['regalia_kit'] ?>" value="<?= $reg['regalia_kit'] ?>"
                                    disabled
                                    title="No semiaberto, o kit de regalia é o mesmo que o kit padrão">
                            </td>
                            <td class="text-center small">
                                <input type="text" class="form-control form-control-sm input-edit-sm setor-semi-v"
                                    data-old="<?= htmlspecialchars($reg['regalia_setor']) ?>" value="<?= htmlspecialchars($reg['regalia_setor']) ?>"
                                    onchange="window.checkRegaliaSemiChanges(<?= $reg['ipen'] ?>)">
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-success btn-block btn-save-regalia-semi font-weight-bold"
                                    onclick="window.confirmarSalvarRegaliaSemi(<?= $reg['ipen'] ?>)">
                                    <i class="fas fa-save"></i> SALVAR
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</div>

<!-- OFFCANVAS HISTÓRICO DE KIT -->
<div class='offcanvas-custom' id='off_historico_kit' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom bg-light'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <h5 class='m-0 text-dark'><i class="fas fa-history mr-2"></i>HISTÓRICO DE KIT</h5>
            <div>
                <button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_historico_kit', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button>
            </div>
        </div>
        <div class="input-group input-group-sm mb-3">
            <input type="number" id="buscaHistoricoKitInput" class="form-control" placeholder="Pesquisar por número do kit..." onkeypress="if(event.key==='Enter') window.buscarHistoricoKit()">
            <div class="input-group-append">
                <button class="btn btn-primary" onclick="window.buscarHistoricoKit()"><i class="fas fa-search"></i></button>
            </div>
        </div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 120px); overflow-y: auto; color: #333;' id="historicoKitContent">
        <!-- Resultados serão carregados aqui via AJAX -->
        <div class="text-center text-muted mt-5">
            <i class="fas fa-search fa-3x mb-3"></i>
            <p>Digite o número do kit e clique em buscar para ver o histórico.</p>
        </div>
    </div>
</div>

<script src="/modulos/censura/rouparia/gestao_kits/assets/js/script.js?v=<?= time() ?>"></script>

<!-- OFFCANVAS ROUPA CIVIL -->
<div class='offcanvas-custom' id='off_roupa_civil' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%);'>
    <div class='p-3 border-bottom bg-light'>
        <div class='d-flex justify-content-between align-items-center mb-2'>
            <h5 class='m-0 text-dark'><i class="fas fa-tshirt mr-2"></i>ROUPA CIVIL</h5>
            <div>
                <button class='btn btn-sm btn-default mr-1' onclick="window.abrirModalEtiquetasRoupaCivil()"><i class='fas fa-tags'></i> Etiquetas</button>
                <button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_roupa_civil', false)" title="Fechar"><i class='fas fa-chevron-right'></i></button>
            </div>
        </div>
        <div class="input-group input-group-sm mb-3">
            <input type="text" id="buscaRoupaCivilInput" class="form-control" placeholder="Pesquisar por IPEN ou nome..." onkeypress="if(event.key==='Enter') window.buscarRoupaCivil()">
            <div class="input-group-append">
                <button class="btn btn-primary" onclick="window.buscarRoupaCivil()"><i class="fas fa-search"></i></button>
                <button class="btn btn-warning" onclick="window.buscarItensPerdidosRoupaCivil()" title="Buscar itens sem descrição"><i class="fas fa-exclamation-triangle"></i></button>
            </div>
        </div>
    </div>
    <div class='p-3' style='max-height: calc(100vh - 120px); overflow-y: auto; color: #333;'>

        <!-- Formulário de Cadastro -->
        <form id="formCadastrarRoupaCivil" onsubmit="window.cadastrarRoupaCivil(event)">
            <div class="form-section-title">
                <i class="fas fa-plus-circle"></i> Novo Cadastro de Roupa Civil
            </div>
            <div class="form-group">
                <label>Selecionar Interno</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="buscaInternoRoupaCivil" placeholder="Digite IPEN, nome ou nome social..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" onclick="limparSelecaoInternoRoupaCivil()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" name="ipen" id="ipenSelecionadoRoupaCivil" required>
                <small class="form-text text-muted" id="infoInternoSelecionado"></small>
            </div>
            <div class="form-group">
                <label>Peças do Kit</label>

                <!-- Itens Pré-definidos -->
                <div class="mb-3">
                    <label class="form-section-title" style="margin-bottom: 10px;">
                        <i class="fas fa-list"></i> Itens Pré-definidos
                    </label>
                    <div id="itensPredefinidos">
                        <!-- Itens serão adicionados dinamicamente -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="adicionarItemPredefinido()">
                        <i class="fas fa-plus"></i> Adicionar Item Pré-definido
                    </button>
                </div>

                <!-- Itens Outros -->
                <div class="mb-3">
                    <label class="form-section-title" style="margin-bottom: 10px;">
                        <i class="fas fa-edit"></i> Outros Itens
                    </label>
                    <div id="itensOutros">
                        <!-- Outros itens serão adicionados dinamicamente -->
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="adicionarItemOutro()">
                        <i class="fas fa-plus"></i> Adicionar Outro Item
                    </button>
                </div>
            </div>

            <!-- Campo hidden para armazenar dados estruturados -->
            <input type="hidden" name="pecas_json" id="pecasJson">
            <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-save"></i> CADASTRAR ROUPA CIVIL
            </button>
        </form>

        <hr class="my-4">

        <!-- Lista de Registros -->
        <div class="form-section-title">
            <i class="fas fa-list"></i> Registros Cadastrados
        </div>
        <div id="roupaCivilList">
            <!-- Conteúdo será carregado dinamicamente -->
        </div>
    </div>
</div>


<!-- MODAL ETIQUETAS ROUPA CIVIL -->
<div class="modal fade" id="modalEtiquetasRoupaCivil" tabindex="-1" role="dialog" aria-labelledby="modalEtiquetasLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalEtiquetasLabel">
                    <i class="fas fa-tags mr-2"></i>Etiquetas - Selecionar Entradas de Roupa Civil
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div class="row mb-2">
                    <div class="col-md-2">
                        <input type="text" id="filtroEtiquetaBusca" class="form-control form-control-sm" placeholder="Pesquisar por IPEN, nome..." oninput="window.filtrarEtiquetasRoupaCivil()">
                    </div>
                    <div class="col-md-2">
                        <select id="filtroEtiquetaStatus" class="form-control form-control-sm" onchange="window.filtrarEtiquetasRoupaCivil()">
                            <option value="">Todos os status</option>
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="filtroCriadoPor" class="form-control form-control-sm" onchange="window.filtrarEtiquetasRoupaCivil()">
                            <option value="">Todos os usuários</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filtroDataInicial" class="form-control form-control-sm" placeholder="Data Inicial" onchange="window.filtrarEtiquetasRoupaCivil()">
                    </div>
                    <div class="col-md-2">
                        <input type="date" id="filtroDataFinal" class="form-control form-control-sm" placeholder="Data Final" onchange="window.filtrarEtiquetasRoupaCivil()">
                    </div>
                    <div class="col-md-2 text-right">
                        <button type="button" class="btn btn-sm btn-info" onclick="window.filtrarHoje()">Só os de Hoje</button>
                        <button type="button" class="btn btn-sm btn-primary" onclick="window.selecionarTodosVisiveis()">Selecionar Todos</button>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.limparFiltrosEtiquetas()">Limpar Filtros</button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="window.marcarTodasEtiquetasVisiveis(true)">Marcar visíveis</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.marcarTodasEtiquetasVisiveis(false)">Desmarcar visíveis</button>
                    </div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-2">
                        <input type="time" id="filtroHoraInicial" class="form-control form-control-sm" placeholder="Hora Inicial" onchange="window.filtrarEtiquetasRoupaCivil()">
                    </div>
                    <div class="col-md-2">
                        <input type="time" id="filtroHoraFinal" class="form-control form-control-sm" placeholder="Hora Final" onchange="window.filtrarEtiquetasRoupaCivil()">
                    </div>
                </div>

                <div class="small text-muted mb-2" id="contadorEtiquetasSelecionadas">0 selecionado(s)</div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th width="50" class="text-center">Sel.</th>
                                <th width="120">IPEN</th>
                                <th>Nome</th>
                                <th width="110">Status</th>
                                <th width="150">Quem Cadastrou</th>
                                <th width="180">Data Cadastro</th>
                            </tr>
                        </thead>
                        <tbody id="listaEtiquetasRoupaCivil">
                            <tr>
                                <td colspan="6" class="text-center text-muted">Carregando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" onclick="window.imprimirEtiquetasSelecionadas()" <?= ($_SESSION['user_nome'] == 'Rouparia' ? 'disabled' : '') ?>>
                    <i class="fas fa-print mr-1"></i> Imprimir
                </button>
            </div>
        </div>
    </div>
</div>
