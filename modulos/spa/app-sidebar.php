<?php

use Config\App;

require_once __DIR__ . '/../../config/sidebar.php';
$menuConfig = getMenuConfig();
$userInfo = getUserInfo();
?>

<aside class="main-sidebar sidebar-dark-primary elevation-4">
<!--begin::Branding-->
<a href="/" class="brand-link d-flex align-items-center justify-content-center">
    <img src="favicon.svg" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8; margin-left: 0; margin-right: 0;">
    <span class="brand-text font-weight-light"><?php echo App::APP_NAME_SHORT; ?> | <?php echo date('Y'); ?></span>
</a>
<!--end::Branding-->


    <div class="sidebar">
        <nav class="mt-2 text-sm">
            <div class="form-inline">
                <div class="input-group" data-widget="sidebar-search">
                    <input class="form-control form-control-sidebar" type="search" placeholder="Buscar..." aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-sidebar">
                            <i class="fas fa-search fa-fw"></i>
                        </button>
                    </div>
                </div>
            </div>
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent nav-flat" data-widget="treeview"
                role="menu">
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
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['censura']['title'] ?>'); return false;"
                                        class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>

                        </ul>
                    </li>
                <?php endif; ?>


                <!-- ======================================================
                     REGION: RECEPÇÃO
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
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','Recepção')"
                                        class="nav-link">
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
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['manutencao']['title'] ?>')"
                                        class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!-- endregion -->

                <!--begin::COORDENAÇÃO-->
                <?php if (isset($menuConfig['coordenacao'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['coordenacao']['icon'] ?>"></i>
                            <p><?= $menuConfig['coordenacao']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['coordenacao']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['coordenacao']['title'] ?>')"
                                        class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!--end::COORDENAÇÃO-->

                <!--begin::ECLUSA-->
                <?php if (isset($menuConfig['eclusa'])): ?>
                    <li class="nav-item has-treeview mt-1">
                        <a href="#" class="nav-link">
                            <i class="nav-icon <?= $menuConfig['eclusa']['icon'] ?>"></i>
                            <p><?= $menuConfig['eclusa']['title'] ?><i class="right fas fa-angle-left"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <?php foreach ($menuConfig['eclusa']['items'] as $item): ?>
                                <li class="nav-item">
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['eclusa']['title'] ?>')"
                                        class="nav-link">
                                        <i class="fas <?= $item['icon'] ?> nav-icon" <?= isset($item['style']) ? "style='{$item['style']}'" : "" ?>></i>
                                        <p><?= $item['title'] ?></p>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!--end::ECLUSA-->

                <!--begin::LABORAL-->
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
                                                    <a href="#"
                                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $subItem['page'] ?>','<?= $subItem['parent'] ?>','<?= $item['title'] ?>')"
                                                        class="nav-link">
                                                        <i class="fas <?= $subItem['icon'] ?> nav-icon"></i>
                                                        <p><?= $subItem['title'] ?></p>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php else: ?>
                                    <li class="nav-item">
                                        <a href="#"
                                            onclick="event.preventDefault(); event.stopPropagation(); loadPage('<?= $item['page'] ?>','<?= $item['parent'] ?>','<?= $menuConfig['laboral']['title'] ?>')"
                                            class="nav-link">
                                            <i class="fas <?= $item['icon'] ?> nav-icon"></i>
                                            <p><?= $item['title'] ?></p>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endif; ?>
                <!--end::LABORAL-->

                <!--begin::FERRAMENTAS-->
                <?php if ($_SESSION['user_admin'] or podeVisualizarSetor('perm_ti') or podeVisualizarSetor('perm_dados')): ?>
                    <li class="nav-header border-top pt-2 mt-2 text-bold uppercase">Ferramentas</li>
                    <?php if ($_SESSION['user_admin'] or podeVisualizarSetor('perm_dados')): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white">
                                <i class="nav-icon fas fa-database text-info"></i>
                                <p>Cadastros <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/geral/cadastro_internos/cadastro_internos_view.php', 'Cadastro de Internos', 'Ferramentas')"
                                        class="nav-link">
                                        <i class="fas fa-user-edit nav-icon text-warning"></i>
                                        <p>Internos</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_admin']): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white">
                                <i class="nav-icon fas fa-cogs text-warning"></i>
                                <p>Serviços <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('/modulos/servicos/job_manager/job_manager_view.php', 'Agendador de Tarefas', 'Serviços')"
                                        class="nav-link">
                                        <i class="fas fa-clock nav-icon text-primary"></i>
                                        <p>Agendador de Tarefas</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_admin'] or podeVisualizarSetor('perm_dados') or podeVisualizarSetor('perm_importacao')): ?>
                        <li class="nav-item has-treeview mt-1">
                            <a href="#" class="nav-link text-white"><i class="nav-icon fas fa-file-import text-danger"></i>
                                <p>Importar Dados <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('paginas/dados_importa_18.php', 'Relatório 1-8 iPEN', 'TI')"
                                        class="nav-link">
                                        <i class="fas fa-file-import nav-icon text-warning"></i>
                                        <p>Relatório 1-8 iPEN</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('paginas/dados_importa_64.php', 'Relatório 6-4 iPEN', 'TI')"
                                        class="nav-link">
                                        <i class="fas fa-file-import nav-icon text-warning"></i>
                                        <p>Relatório 6-4 iPEN</p>
                                    </a>
                                </li>
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
                                    <a href="#"
                                        onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/seguranca/gestao_contas/gestao_contas_view.php', 'Gestão de Usuários', 'TI')"
                                        class="nav-link">
                                        <i class="fas fa-users-cog nav-icon text-danger"></i>
                                        <p>Controle de Contas</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    <!--end::FERRAMENTAS-->
            </ul>
        </nav>
    </div>
    <div class="sidebar-custom">
        <a href="#" class="btn btn-link"><i class="fas fa-cogs"></i></a>
        <a href="#" class="btn btn-secondary hide-on-collapse pos-right">Help</a>
    </div>
</aside>