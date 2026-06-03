<?php
require_once __DIR__ . '/sidebar_logica.php';
$menuConfig = getMenuConfig();
$userInfo = getUserInfo();
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <!-- ======================================================
                     REGION: BRAND
                ====================================================== -->
    <a href="/" class="brand-link text-center py-3 border-bottom shadow-sm">
        <span class="brand-text">
            <b style="color:#3b82f6;">SIG</b><b style="color:#fff">EP</b><br>
            <small class="text-muted text-bold" style="font-size:0.6rem; letter-spacing:1px;">
                SISTEMA PRISIONAL INTEGRADO
            </small>
        </span>
    </a>

    <div class="sidebar px-2">

        <!-- ======================================================
                     REGION: USUÁRIO
                ====================================================== -->
        <div class="user-panel mt-3 pb-3 mb-3 text-center border-bottom">
            <div class="info d-block">
                <p class="text-white mb-1 small text-bold text-uppercase">
                    <?= $userInfo['nome'] ?>
                </p>
                <span class="badge badge-primary py-1 px-3 shadow-sm"
                    style="font-size:0.68rem; width:95%;">
                    <?= mb_strtoupper($userInfo['setor'], 'UTF-8') ?>
                </span>
            </div>
            <?php if (mostrarPainelInternos()): ?>
                <li class="nav-item">
                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/geral/painel_internos/internos_painel_view.php', 'Painel de Internos', 'TI')" class="nav-link">
                        <i class="nav-icon fas fa-th text-info"></i>
                        <p>Painel de Internos</p>
                    </a>
                </li>
            <?php endif; ?>
        </div>

        <nav class="mt-2 text-sm">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-flat"
                data-widget="treeview" role="menu">


                <li class="nav-item has-treeview mt-1">
                    <a href="#" class="nav-link">
                        <i class="bi bi-people-fill"></i>
                        <p>SETORES</p>
                    </a>
                </li>

                <!-- ======================================================
                     REGION: CENSURA
                ====================================================== -->
                <?php if (isset($menuConfig['censura'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['censura']['icon'] ?>"></i>
                            <p><?= $menuConfig['censura']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['censura']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['censura']['title'] ?>'); return false;" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>

                        </ul>
                    </li>
                <?php endif; ?>


                <!-- ======================================================
                     REGION: ROUPARIA (ATUAL)
                ====================================================== -->
                <?php if (isset($menuConfig['portaria'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['portaria']['icon'] ?>"></i>
                            <p>Recepção<i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['portaria']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','Recepção')" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: MANUTENÇÃO
                ====================================================== -->
                <?php if (isset($menuConfig['manutencao'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['manutencao']['icon'] ?>"></i>
                            <p><?= $menuConfig['manutencao']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['manutencao']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['manutencao']['title'] ?>')" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: ALMOXARIFADO
                ====================================================== -->
                <li class="nav-item has-treeview mt-1">
                    <a href="#" class="nav-link">
                        <i class="nav-icon <?= $menuConfig['almoxarifado']['icon'] ?>"></i>
                        <p><?= $menuConfig['almoxarifado']['title'] ?><i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <?php foreach ($menuConfig['almoxarifado']['items'] as $item): ?>
                            <li class="nav-item">
                                <?php if (isset($item['external']) && $item['external']): ?>
                                    <a href="<?= $item['url'] ?>" target="_blank" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon"></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                <?php else: ?>
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>', '<?= $item['parent'] ?>', '<?= $menuConfig['almoxarifado']['title'] ?>')" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon"></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: SEGURANÇA DO TRABALHO
                ====================================================== -->
                <?php if (isset($menuConfig['seguranca_trabalho'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['seguranca_trabalho']['icon'] ?>"></i>
                            <p><?= $menuConfig['seguranca_trabalho']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['seguranca_trabalho']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['seguranca_trabalho']['title'] ?>')" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon"></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: COORDENAÇÃO
                ====================================================== -->
                <?php if (isset($menuConfig['coordenacao'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['coordenacao']['icon'] ?>"></i>
                            <p><?= $menuConfig['coordenacao']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['coordenacao']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['coordenacao']['title'] ?>')" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: ECLUSA
                ====================================================== -->
                <?php if (isset($menuConfig['eclusa'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['eclusa']['icon'] ?>"></i>
                            <p><?= $menuConfig['eclusa']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['eclusa']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['eclusa']['title'] ?>')" class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: LABORAL
                ====================================================== -->
                <?php if (isset($menuConfig['laboral'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['laboral']['icon'] ?>"></i>
                            <p><?= $menuConfig['laboral']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['laboral']['items'] as $item): ?>
                                <?php if (isset($item['items']) && is_array($item['items'])): ?>
                                    <!-- Submenu com itens aninhados (ex: Financeiro) -->
                                    <li class="nav-item has-treeview">
                                        <a href="#" class="nav-link">
                                            <i class="fas <?= $item['icon'] ?> nav-icon"></i>
                                            <p><?= $item['title'] ?><i class="right fas fa-angle-left"></i></p>
                                        </a>
                                        <ul class="nav nav-treeview">
                                            <?php foreach ($item['items'] as $subItem): ?>
                                                <li class="nav-item">
                                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $subItem['page'] ?>','<?= $subItem['parent'] ?>','<?= $item['title'] ?>')" class="nav-link">
                                                        <i class="fas <?= $subItem['icon'] ?> nav-icon"></i>
                                                        <p><?= $subItem['title'] ?></p>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php else: ?>
                                    <!-- Item simples -->
                                    <li class="nav-item">
                                        <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['laboral']['title'] ?>')" class="nav-link">
                                            <i class="fas <?= $item['icon'] ?> nav-icon"></i>
                                            <p><?= $item['title'] ?></p>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: DEMAIS SETORES
                ====================================================== -->
                <?php if (isset($menuConfig['outros'])): ?>
                    <?php foreach ($menuConfig['outros'] as $setor): ?>
                        <li class="nav-item has-treeview mt-1">
                            <?php if (isset($setor['perm']) && $setor['perm'] === 'perm_ti'): ?>
                                <!-- Menu TI especial com submenu -->
                                <a href="#" class="nav-link">
                                    <i class="nav-icon fas <?= $setor['icon'] ?> <?= $setor['class'] ?>"></i>
                                    <p><?= $setor['title'] ?><i class="right fas fa-angle-left"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item">
                                        <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/censura/rouparia/gestao_kits/view.php','Gestão de Kits (Teste)','TI')" class="nav-link">
                                            <i class="fas fa-box-open nav-icon text-success"></i>
                                            <p>Gestão de Kits (Teste)</p>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/geral/painel_internos/painel_internos_view.php','Painel de Internos','TI')" class="nav-link">
                                            <i class="fas fa-th-large nav-icon text-primary"></i>
                                            <p>Painel de Internos</p>
                                        </a>
                                    </li>
                                    <?php if (isset($_SESSION['user_admin']) && $_SESSION['user_admin'] === true): ?>
                                        <li class="nav-item">
                                            <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/seguranca/cftv/cftv_view.php','CFTV','TI')" class="nav-link">
                                                <i class="fas fa-video nav-icon text-danger"></i>
                                                <p>CFTV</p>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <!-- Demais setores - página em construção -->
                                <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/geral/em_construcao/em_construcao_view.php','<?= $setor['title'] ?>','<?= $setor['title'] ?>')" class="nav-link">
                                    <i class="nav-icon fas <?= $setor['icon'] ?> <?= $setor['class'] ?>"></i>
                                    <p><?= $setor['title'] ?></p>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
                <!-- endregion -->

                <!-- ======================================================
                     REGION: FERRAMENTAS
                ====================================================== -->
                <?php if ($_SESSION['user_admin'] or podeVisualizarSetor('perm_ti') or podeVisualizarSetor('perm_dados')): ?>
                    <li class="nav-header border-top pt-2 mt-2 text-bold uppercase">Ferramentas</li>
                    <!-- NOVO MENU CADASTROS -->
                    <?php if ($_SESSION['user_admin'] or podeVisualizarSetor('perm_dados')): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white">
                                <i class="nav-icon fas fa-database"></i>
                                <p>Cadastros <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/geral/cadastro_internos/cadastro_internos_view.php', 'Cadastro de Internos', 'Ferramentas')" class="nav-link">
                                        <i class="fas fa-user-edit nav-icon text-warning"></i>
                                        <p>Internos</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- FIM NOVO MENU -->

                    <!-- MENU SERVIÇOS -->
                    <?php if ($_SESSION['user_admin']): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white">
                                <i class="nav-icon fas fa-cogs text-info"></i>
                                <p>Serviços <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/servicos/job_manager/job_manager_view.php', 'Agendador de Tarefas', 'Serviços')" class="nav-link">
                                        <i class="fas fa-clock nav-icon text-primary"></i>
                                        <p>Agendador de Tarefas</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/servicos/phpmyadmin/phpmyadmin.php', 'MySQL', 'Serviços')" class="nav-link">
                                        <i class="fas fa-database nav-icon text-warning"></i>
                                        <p>MySQL</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/servicos/canais/canais_view.php', 'Canais de Notificação', 'Serviços')" class="nav-link">
                                        <i class="fas fa-broadcast-tower nav-icon text-purple"></i>
                                        <p>Canais de Notificação</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- FIM MENU SERVIÇOS -->

                    <?php if ($_SESSION['user_admin'] or podeVisualizarSetor('perm_dados') or podeVisualizarSetor('perm_importacao')): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white"><i class="nav-icon fas fa-cogs"></i>
                                <p>Importar Dados <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('paginas/dados_importa_18.php', 'Relatório 1-8 iPEN', 'TI')" class="nav-link">
                                        <i class="fas fa-file-import nav-icon"></i>
                                        <p>Relatório 1-8 iPEN</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('paginas/dados_importa_64.php', 'Relatório 6-4 iPEN', 'TI')" class="nav-link">
                                        <i class="fas fa-file-import nav-icon text-info"></i>
                                        <p>Relatório 6-4 iPEN</p>
                                    </a>
                                </li>
                                <li class="nav-item"><a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/geral/em_construcao/em_construcao_view.php','Em Construção','TI')" class="nav-link"><i class="nav-icon fas fa-cogs"></i>
                                        <p>Relatório 8-13 iPEN</p>
                                    </a></li>
                            </ul>
                        <?php endif; ?>
                        </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_admin']): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white"><i class="nav-icon fas fa-cogs"></i>
                                <p>Gerenciar Acessos <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#" onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/seguranca/gestao_contas/gestao_contas_view.php', 'Gestão de Usuários', 'TI')" class="nav-link">
                                        <i class="fas fa-users-cog nav-icon text-danger"></i>
                                        <p>Controle de Contas</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!-- endregion -->
            </ul>
        </nav>
    </div>
</aside>