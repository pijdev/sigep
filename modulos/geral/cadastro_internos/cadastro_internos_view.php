<?php
require_once __DIR__ . '/cadastro_internos_logica.php';
?>

<script>
    window.currentPage = 'cadastro_internos_view.php';
    window.pageTitle = 'Cadastro de Internos';
</script>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/geral/cadastro_internos/assets/css/cadastro_internos.css?v=<?= time() ?>">

<section class="content pt-3">
    <div class="container-fluid">

        <!-- CARDS ESTATÍSTICA -->
        <div class="row mb-3 no-print">
            <div class="col-lg-3 col-12" onclick="loadPage('paginas/internos_painel.php', 'Painel de Internos', 'TI')" style="cursor:pointer">
                <div class="small-box bg-primary">
                    <div class="inner"><h3><?= number_format($stats_data['total'] ?? 0, 0, ',', '.') ?></h3><p>Total de Internos</p></div>
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <a href="#" class="small-box-footer">Cadastros Ativos <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-12" onclick="window.abrirOffcanvasStats('lgbt')" style="cursor:pointer">
                <div class="small-box bg-rainbow">
                    <div class="inner"><h3><?= number_format($stats_data['total_lgbt'] ?? 0, 0, ',', '.') ?></h3><p>Internos LGBT</p></div>
                    <div class="icon"><i class="fas fa-rainbow"></i></div>
                    <a href="#" class="small-box-footer">População LGBT <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-12" onclick="window.abrirOffcanvasStats('pix')" style="cursor:pointer">
                <div class="small-box bg-warning">
                    <div class="inner"><h3><?= number_format($stats_data['total_pix'] ?? 0, 0, ',', '.') ?></h3><p>Recebem PIX</p></div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                    <a href="#" class="small-box-footer">Forma Pagamento <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-12" onclick="window.abrirOffcanvasStats('salario')" style="cursor:pointer">
                <div class="small-box bg-success">
                    <div class="inner"><h3><?= number_format($stats_data['total_salario'] ?? 0, 0, ',', '.') ?></h3><p>Trabalhadores</p></div>
                    <div class="icon"><i class="fas fa-briefcase"></i></div>
                    <a href="#" class="small-box-footer">Com Setor <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <h4 class="m-0 text-dark dark-mode-text font-weight-bold"><i class="fas fa-users-cog text-primary mr-2"></i> Cadastro de Internos</h4>
            <div>
                <!-- MENU DE RELATÓRIOS -->
                <div class="btn-group shadow-sm">
                    <button type="button" class="btn btn-default btn-sm dropdown-toggle font-weight-bold" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-print mr-1"></i> Relatórios
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow border-0">
                        <a class="dropdown-item" href="#" onclick="window.imprimirRelatorio('geral')"><i class="fas fa-filter text-muted mr-2"></i> Filtros Atuais</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="window.imprimirRelatorio('apelido')"><i class="fas fa-user-tag text-info mr-2"></i> Local e Apelido</a>
                        <a class="dropdown-item" href="#" onclick="window.imprimirRelatorio('trabalham')"><i class="fas fa-briefcase text-success mr-2"></i> Trabalhadores (Setor)</a>
                        <a class="dropdown-item" href="#" onclick="window.imprimirRelatorio('lgbt')"><i class="fas fa-transgender text-purple mr-2" style="color: #6f42c1;"></i> Internos LGBT</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#" onclick="window.imprimirRelatorio('hist_volta')"><i class="fas fa-undo text-warning mr-2"></i> Saíram e Voltaram</a>
                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalDataHist"><i class="fas fa-calendar-alt text-secondary mr-2"></i> Histórico de Saídas</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FILTROS (SEMPRE ABERTO) -->
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-body py-3">
                <form id="formCad" onsubmit="window.reloadContent('modulos/geral/cadastro_internos/cadastro_internos_view.php?'+$(this).serialize()); return false;">
                    <div class="row">
                        <div class="col-md-3 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Pesquisa Global</label><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-search"></i></span></div><input type="text" class="form-control" name="search" placeholder="Nome, Social, Apelido, IPEN" value="<?= htmlspecialchars($f['search']) ?>"></div></div>
                        <div class="col-md-2 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Situação</label><select class="form-control form-control-sm" name="situacao"><option value="">Todas</option><?php foreach($situacoes_db as $s) echo "<option value='$s' ".($f['situacao']==$s?'selected':'').">$s</option>"; ?></select></div>
                        <div class="col-md-1 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Galeria</label><select class="form-control form-control-sm" name="galeria"><option value="">Todas</option><?php foreach($galerias_db as $g) echo "<option value='$g' ".($f['galeria']==$g?'selected':'').">$g</option>"; ?></select></div>
                        <div class="col-md-1 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Bloco</label><select class="form-control form-control-sm" name="bloco"><option value="">Todos</option><?php foreach($blocos_db as $b) echo "<option value='$b' ".($f['bloco']==$b?'selected':'').">$b</option>"; ?></select></div>
                        <div class="col-md-1 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Cela</label><input type="text" class="form-control form-control-sm text-center" name="res" value="<?= htmlspecialchars($f['res']) ?>"></div>
                        <div class="col-md-1 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Regalia</label><select class="form-control form-control-sm" name="regalia"><option value="">Todos</option><option value="S" <?= $f['regalia']=='S'?'selected':'' ?>>Sim</option><option value="N" <?= $f['regalia']=='N'?'selected':'' ?>>Não</option></select></div>
                        <div class="col-md-1 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Kit Nº</label><input type="number" class="form-control form-control-sm text-center" name="kit_num" value="<?= htmlspecialchars($f['kit_num']) ?>"></div>
                        <div class="col-md-2 form-group mb-2"><label class="small text-muted mb-0 font-weight-bold">Setor Trabalho</label><select class="form-control form-control-sm" name="setor"><option value="">Todos</option><?php foreach($setores_db as $s) echo "<option value='$s' ".($f['setor']==$s?'selected':'').">$s</option>"; ?></select></div>
                    </div>
                    <div class="row border-top pt-2 mt-2">
                        <div class="col-md-2 form-group mb-0"><label class="small text-muted mb-0 font-weight-bold">Status</label><select class="form-control form-control-sm" name="status"><option value="A" <?= $f['status']=='A'?'selected':'' ?>>ATIVOS</option><option value="I" <?= $f['status']=='I'?'selected':'' ?>>INATIVOS</option></select></div>
                        <div class="col-md-3 form-group mb-0">
                            <label class="small text-muted mb-0 font-weight-bold">Data Cadastro (De - Até)</label>
                            <div class="d-flex gap-1"><input type="date" class="form-control form-control-sm" name="data_ini" value="<?= $f['data_ini'] ?>"><input type="date" class="form-control form-control-sm" name="data_fim" value="<?= $f['data_fim'] ?>"></div>
                        </div>
                        <div class="col-md-7 text-right d-flex align-items-end justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.reloadContent('modulos/geral/cadastro_internos/cadastro_internos_view.php')">Limpar</button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 font-weight-bold shadow-sm"><i class="fas fa-search"></i> FILTRAR RESULTADOS</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABELA -->
        <div class="card shadow border-0">
            <div class="card-body table-responsive p-0">
                <table class="table table-head-fixed table-hover text-nowrap table-sm align-middle">
                    <thead class="thead-dark-custom text-center">
                        <tr>
                            <th width="70"><?= sortLink('ipen', 'IPEN') ?></th>
                            <th style="text-align: left; padding-left: 15px;"><?= sortLink('nome', 'NOME / SOCIAL') ?></th>
                            <th width="80"><?= sortLink('local', 'LOCAL') ?></th>
                            <th><?= sortLink('situacao', 'SITUAÇÃO') ?></th>
                            <th width="50">REG.</th>
                            <th width="50"><?= sortLink('kit', 'KIT') ?></th>
                            <th><?= sortLink('setor', 'SETOR') ?></th>
                            <th><?= sortLink('data', 'CADASTRO') ?></th>
                            <th width="50">AÇÃO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($internos as $i):
                            $hasSocial = !empty($i['nome_social']);
                            $clsSocial = $hasSocial ? 'row-social' : '';
                            $nome_exib = $hasSocial ? "<span class='nome-social-destaque'>{$i['nome_social']}</span> <span class='nome-masculino'>({$i['nome']})</span>" : "<span class='font-weight-bold text-dark'>{$i['nome']}</span>";
                            if(!empty($i['apelido'])) $nome_exib .= " <small class='text-info ml-1'><i class='fas fa-tag'></i> {$i['apelido']}</small>";
                            $data_cad = $i['data_ativo'] ? date('d/m/Y', strtotime($i['data_ativo'])) : '-';
                            $kit_show = ($i['regalia'] == 'S' && $i['regalia_kit']) ? $i['regalia_kit'] : $i['kit'];
                        ?>
                        <tr class="<?= $clsSocial ?>">
                            <td class="text-center font-weight-bold text-secondary"><?= $i['ipen'] ?></td>
                            <td style="vertical-align: middle; padding-left: 15px;"><?= $nome_exib ?></td>
                            <td class="text-center font-weight-bold"><?= "{$i['galeria']}{$i['bloco']}-{$i['res']}" ?></td>
                            <td class="small text-muted font-weight-bold"><?= $i['situacao'] ?></td>
                            <td class="text-center"><span class="<?= $i['regalia']=='S'?'badge-regalia-s':'badge-regalia-n' ?>"><?= $i['regalia'] ?></span></td>
                            <td class="text-center font-weight-bold text-primary"><?= $kit_show ?></td>
                            <td class="small"><?= $i['regalia_setor'] ?></td>
                            <td class="text-center small text-muted"><?= $data_cad ?></td>
                            <td class="text-center"><button class="btn btn-action btn-outline-primary" onclick="window.abrirEdicao(<?= htmlspecialchars(json_encode($i)) ?>)"><i class="fas fa-pen"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer clearfix">
                <ul class="pagination pagination-sm m-0 float-right">
                    <?php if($page > 1): ?><li class="page-item"><a class="page-link" href="#" onclick="window.reloadContent('modulos/geral/cadastro_internos/cadastro_internos_view.php?page=<?= $page-1 ?>&<?= http_build_query($f) ?>'); return false;">«</a></li><?php endif; ?>
                    <li class="page-item disabled"><a class="page-link" href="#">Pág <?= $page ?> de <?= $total_paginas ?></a></li>
                    <?php if($page < $total_paginas): ?><li class="page-item"><a class="page-link" href="#" onclick="window.reloadContent('modulos/geral/cadastro_internos/cadastro_internos_view.php?page=<?= $page+1 ?>&<?= http_build_query($f) ?>'); return false;">»</a></li><?php endif; ?>
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
            <?php include_once __DIR__ . '/formulario_edicao.php'; ?>
        </form>
    </div>
</div>

<!-- OFFCANVAS - TOTAL DE INTERNOS -->
<div class="offcanvas-right offcanvas-custom" id="offcanvasTotal" style="visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #343a40; border-left: 1px solid #495057; transition: transform .3s ease-in-out; transform: translateX(100%); overflow-y: auto;">
    <div class="p-3 border-bottom bg-primary text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="m-0">TOTAL DE INTERNOS</h5>
            <button class="btn btn-sm btn-light" onclick="window.fecharOffcanvasStats('offcanvasTotal')"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div id="totalList" class="p-3" style="max-height: calc(100vh - 120px); overflow-y: auto;"></div>
</div>

<!-- OFFCANVAS - LGBT -->
<div class="offcanvas-right offcanvas-custom" id="offcanvasLGBT" style="visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #343a40; border-left: 1px solid #495057; transition: transform .3s ease-in-out; transform: translateX(100%); overflow-y: auto;">
    <div class="p-3 border-bottom bg-danger text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="m-0">POPULAÇÃO LGBT</h5>
            <button class="btn btn-sm btn-light" onclick="window.fecharOffcanvasStats('offcanvasLGBT')"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div id="lgbtList" class="p-3" style="max-height: calc(100vh - 120px); overflow-y: auto;"></div>
</div>

<!-- OFFCANVAS - PIX -->
<div class="offcanvas-right offcanvas-custom" id="offcanvasPIX" style="visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #343a40; border-left: 1px solid #495057; transition: transform .3s ease-in-out; transform: translateX(100%); overflow-y: auto;">
    <div class="p-3 border-bottom bg-warning text-dark font-weight-bold">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="m-0">RECEBEM PIX</h5>
            <button class="btn btn-sm btn-default" onclick="window.fecharOffcanvasStats('offcanvasPIX')"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div id="pixList" class="p-3" style="max-height: calc(100vh - 120px); overflow-y: auto;"></div>
</div>

<!-- OFFCANVAS - TRABALHADORES -->
<div class="offcanvas-right offcanvas-custom" id="offcanvasTrabalho" style="visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #343a40; border-left: 1px solid #495057; transition: transform .3s ease-in-out; transform: translateX(100%); overflow-y: auto;">
    <div class="p-3 border-bottom bg-success text-white">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="m-0">TRABALHADORES</h5>
            <button class="btn btn-sm btn-light" onclick="window.fecharOffcanvasStats('offcanvasTrabalho')"><i class="fas fa-times"></i></button>
        </div>
    </div>
    <div id="trabalhoList" class="p-3" style="max-height: calc(100vh - 120px); overflow-y: auto;"></div>
</div>

<!-- MODAL PARA DATA DO HISTÓRICO -->
<div class="modal fade" id="modalDataHist" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-dark text-white py-2">
                <h6 class="modal-title font-weight-bold"><i class="fas fa-calendar-alt"></i> Período do Relatório</h6>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light">
                <div class="form-group">
                    <label class="small font-weight-bold text-muted">Data Inicial</label>
                    <input type="date" id="hist_dt_ini" class="form-control form-control-sm" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-muted">Data Final</label>
                    <input type="date" id="hist_dt_fim" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer justify-content-between py-2 bg-white">
                <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-sm font-weight-bold shadow-sm" onclick="window.imprimirHistRange()">Gerar Relatório</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico do módulo -->
<script src="/modulos/geral/cadastro_internos/assets/js/cadastro_internos.js?v=<?= time() ?>"></script>

<!-- FLATPICKR - Datepicker -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
