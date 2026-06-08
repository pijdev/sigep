<?php
use Config\App;
require_once __DIR__ . '/../../config/sidebar.php';
$menuConfig = getMenuConfig();
$userInfo = getUserInfo();
?>

<nav class="main-header navbar navbar-expand navbar-light d-flex align-items-center py-0" style="height: 56px;">
  <!--begin::Menus da Esquerda-->
  <ul class="navbar-nav d-flex align-items-center">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <?php if (mostrarPainelInternos()): ?>
      <li class="nav-item" style="list-style: none;">
        <a href="#"
          onclick="event.preventDefault(); event.stopPropagation(); loadPage('modulos/geral/painel_internos/internos_painel_view.php', 'Painel de Internos', 'TI')"
          class="nav-link">
          <i class="nav-icon fas fa-th text-info"></i>
          <span style="margin-left: 5px;">Layout</span>
        </a>
      </li>
    <?php endif; ?>
          <li class="nav-item" style="list-style: none;" id="ia-btn">
        <a href="#"
          onclick="window.exibOff('off_ia', true);"
          class="nav-link">
          <span style="margin-left: 5px;">🤖 IA</span>
        </a>
      </li>
  </ul>
  <!--end::Menus da Esquerda-->
  <!--begin::Menus da Direita-->
  <ul class="navbar-nav ml-auto d-flex align-items-center">
    <li class="nav-item" style="list-style: none;">
      <a class="nav-link" href="/copa2026" role="button" style="
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        font-weight: 800; 
        text-transform: uppercase;
        text-decoration: none;
        transition: transform 0.2s ease;
        padding: 0 1rem;
    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'"
        onclick="event.preventDefault(); abrirAlertaCopa(this.href);">
        <span class="soccer" style="color: #ffdf00; line-height: 1;">⚽</span>
        <span style="
            background: linear-gradient(90deg, #009c3b 10%, #ffdf00 30%, #0447cc 50%, #ffdf00 70%, #009c3b 100%);
            background-clip: text;
            -webkit-text-fill-color: transparent;
            line-height: 1;
        ">Copa do Mundo 2026</span>
      </a>
    </li>
    <!-- Mensagens -->
    <li class="nav-item dropdown">
      <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#">
        <i class="far fa-comments"></i>
        <span class="badge badge-danger navbar-badge"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <a href="#" class="dropdown-item">
          <span class="dropdown-item dropdown-header">Nenhuma nova Mensagem</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">Ver todas</a>
      </div>
    </li>
    <!-- Notificações -->
    <li class="nav-item dropdown">
      <a class="nav-link d-flex align-items-center" data-toggle="dropdown" href="#">
        <i class="far fa-bell"></i>
        <span class="badge badge-warning navbar-badge"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">Nenhuma nova Notificação</span>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">Ver Todas</a>
      </div>
    </li>
    <!--begin::Funções-->
    <li class="nav-item d-flex align-items-center px-2 tooltip;" title='Trocar Tema'>
      <label class="theme-switch" for="checkbox" style="margin: 0; display: block;">
        <input type="checkbox" id="checkbox">
        <span class="slider round">
          <i id="theme-icon" class="fas fa-sun"></i>
        </span>
      </label>
    </li>
    <!-- Tela Cheia -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" data-widget="fullscreen" href="#" role="button"
        title='Alternar Tela Cheia/Normal'>
        <i class="fas fa-expand-arrows-alt"></i>
      </a>
    </li>
    <!-- Logout -->
    <li class="nav-item">
      <a class="nav-link d-flex align-items-center" href="auth/logout.php" role="button"
        title='Sair do <?php echo App::APP_NAME_SHORT; ?>'>
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </li>
    <!--end::Funções-->
  </ul>
  <!--end::Menus da Direita-->
</nav>
<!--end::Header-->