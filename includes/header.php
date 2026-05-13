<?php
$dark_class = ($_SESSION['user_theme'] ?? 1) == 1 ? 'dark-mode' : '';
$nav_class  = $dark_class ? 'navbar-dark border-bottom-0' : 'navbar-white navbar-light';

// Buscar notificações não lidas
$notificacoes_nao_lidas = [];
$notificacoes_count = 0;

try {
  $config = require __DIR__ . '/../conf/db.php';
  $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]);

  // Incluir NotificationManager (lib sem renderizar view)
  require_once __DIR__ . '/../modulos/servicos/notificacoes/notificacoes_lib.php';
  $notificationManager = new NotificationManager($pdo);

  // Buscar últimas 5 notificações não lidas
  $notificacoes_nao_lidas = $notificationManager->buscarNaoLidas($_SESSION['user_id'] ?? 0, 5);
  $notificacoes_count = count($notificacoes_nao_lidas);
} catch (Exception $e) {
  // Silenciar erros para não quebrar o header
  $notificacoes_nao_lidas = [];
  $notificacoes_count = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGEP | 2026</title>
  <base href="/"> <!-- Base tag para corrigir caminhos relativos -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="assets/css/pijcensura_style.css">
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
  <link rel="stylesheet" href="assets/css/footer.css">
  <link rel="stylesheet" href="assets/css/index.css">
  <link rel="icon" type="image/svg+xml" href="favicon.svg">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $dark_class ?>">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Definir variável global com estado inicial do tema, priorizando localStorage para mudanças imediatas
    window.sigepInitialTheme = parseInt(localStorage.getItem('sigep_theme')) || <?= $_SESSION['user_theme'] ?? 1 ?>;
  </script>
  <div class="wrapper">
    <!-- Avisos de Manutenção - Sistema Centralizado -->
    <?php
    try {
      require_once __DIR__ . '/../modulos/geral/aviso_manutencao/aviso_manutencao_componente.php';
      echo getCSSAvisosManutencao();
      exibirAvisosManutencao();
    } catch (Exception $e) {
      // Silenciar erros para não quebrar o header
    }
    ?>
    <nav class="main-header navbar navbar-expand <?= $nav_class ?>">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <?php if (!empty($_SESSION['user_admin']) || !empty($_SESSION['perm_ti'])): ?>
        <?php endif; ?>
        <li class="nav-item dropdown">
          <a class="nav-link" data-toggle="dropdown" href="#">
            <i class="far fa-bell"></i>
            <?php if ($notificacoes_count > 0): ?>
              <span class="badge badge-danger navbar-badge"><?= $notificacoes_count ?></span>
            <?php endif; ?>
          </a>
          <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <span class="dropdown-item dropdown-header">
              <i class="fas fa-bell mr-2"></i>
              Central de Notificações
              <?php if ($notificacoes_count > 0): ?>
                <span class="badge badge-danger float-right"><?= $notificacoes_count ?></span>
              <?php endif; ?>
            </span>
            <div class="dropdown-divider"></div>

            <?php if (!empty($notificacoes_nao_lidas)): ?>
              <?php foreach ($notificacoes_nao_lidas as $notif): ?>
                <a href="#" onclick="loadPage('servicos/notificacoes', 'Notificações', 'Serviços')" class="dropdown-item">
                  <div class="media">
                    <div class="media-body">
                      <h6 class="dropdown-item-title">
                        <?php
                        $iconClass = 'fas fa-info-circle';
                        $badgeClass = 'badge-info';
                        switch (strtolower($notif['tipo'])) {
                          case 'erro':
                            $iconClass = 'fas fa-exclamation-triangle';
                            $badgeClass = 'badge-danger';
                            break;
                          case 'alerta':
                            $iconClass = 'fas fa-warning';
                            $badgeClass = 'badge-warning';
                            break;
                          case 'backup':
                            $iconClass = 'fas fa-database';
                            $badgeClass = 'badge-primary';
                            break;
                          case 'tarefa':
                            $iconClass = 'fas fa-tasks';
                            $badgeClass = 'badge-success';
                            break;
                        }
                        ?>
                        <i class="<?= $iconClass ?> mr-2"></i>
                        <span class="badge badge-sm <?= $badgeClass ?> mr-1"><?= ucfirst($notif['tipo']) ?></span>
                        <?= htmlspecialchars(substr($notif['titulo'], 0, 30)) ?>
                      </h6>
                      <p class="text-sm mb-0">
                        <?= htmlspecialchars(substr($notif['mensagem'], 0, 60)) ?>...
                      </p>
                      <p class="text-xs text-muted mb-0">
                        <small><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></small>
                      </p>
                    </div>
                  </div>
                </a>
                <div class="dropdown-divider"></div>
              <?php endforeach; ?>
            <?php else: ?>
              <a href="#" class="dropdown-item small text-muted text-center">
                <i class="fas fa-check-circle text-success mr-2"></i>
                Nenhuma notificação nova
              </a>
            <?php endif; ?>

            <a href="#" onclick="loadPage('servicos/notificacoes', 'Notificações', 'Serviços')" class="dropdown-item text-center text-primary">
              <small>Ver todas as notificações</small>
            </a>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#" id="sigepDarkModeToggle" role="button">
            <i class="fas <?= $dark_class ? 'fa-sun' : 'fa-moon' ?>"></i>
          </a>
        </li>
        <li class="nav-item dropdown user-menu">
          <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
            <i class="fas fa-user-circle mr-1"></i>
            <span class="d-none d-md-inline text-bold"><?= $_SESSION['user_nome'] ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
            <li class="user-header bg-primary shadow">
              <i class="fas fa-id-card-alt fa-3x mt-2"></i>
              <p><?= $_SESSION['user_nome'] ?><small><?= $_SESSION['user_setor'] ?></small></p>
            </li>
            <li class="user-footer">
              <a href="#" onclick="loadPage('paginas/perfil.php', 'Meu Perfil', 'Usuário')" class="btn btn-default btn-flat border">Meu Perfil</a>
              <a href="auth/logout.php" class="btn btn-default btn-flat float-right text-danger border">Sair</a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
