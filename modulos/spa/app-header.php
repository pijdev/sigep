<?php include __DIR__ . '/components/app-head.php'; ?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
    <li class="nav-item" style="list-style: none;">
      <a class="nav-link" href="/copa2026" role="button" style="
        display: inline-flex; 
        align-items: center; 
        gap: 8px; 
        font-weight: 800; 
        text-transform: uppercase;
        text-decoration: none;
        transition: transform 0.2s ease;
    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'"
        onclick="event.preventDefault(); abrirAlertaCopa(this.href);">
        <span class="soccer" style="color: #ffdf00;">⚽</span>
        <span style="
            background: linear-gradient(90deg, #009c3b 10%, #ffdf00 30%, #0447cc 50%, #ffdf00 70%, #009c3b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        ">Copa do Mundo 2026</span>
      </a>
    </li>
  </ul>

  <!--begin::Menus da Direita-->
  <ul class="navbar-nav ml-auto">

    <!--begin::Mensagens-->
    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-comments"></i>
        <span class="badge badge-danger navbar-badge"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <a href="#" class="dropdown-item">
          <span class="dropdown-item dropdown-header">Nenhuma nova Mensagen</span>
        </a>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">Ver todas</a>
      </div>
    </li>

    <!--begin::Notificações-->
    <li class="nav-item dropdown">
      <a class="nav-link" data-toggle="dropdown" href="#">
        <i class="far fa-bell"></i>
        <span class="badge badge-warning navbar-badge"></span>
      </a>
      <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
        <span class="dropdown-item dropdown-header">Nenhuma nova Notificação</span>
        <div class="dropdown-divider"></div>
        <a href="#" class="dropdown-item dropdown-footer">Ver Todas</a>
      </div>
    </li>
    <!--end::Notificações-->

    <!--begin::Funções-->
    <li class="nav-item">
      <a class="nav-link" data-widget="fullscreen" href="#" role="button">
        <i class="fas fa-expand-arrows-alt"></i>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link text-danger" href="auth/logout.php" role="button">
        <i class="fas fa-sign-out-alt"></i>
      </a>
    </li>
    <!--end::Funções-->

  </ul>
  <!--end::Menus da Direita-->

</nav>
<!--end::Header-->
<script src="../assets/js/app.js"></script>