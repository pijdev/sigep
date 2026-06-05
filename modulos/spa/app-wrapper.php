<?php
use Config\App;
require_once 'includes/index_logica.php';
?>

<!DOCTYPE html>
<html lang="<?php echo App::APP_LOCALE; ?>" style="height: auto;">
<span id="app-name-short" style="display: none;"><?php echo App::APP_NAME_SHORT; ?></span>
<?php include_once 'modulos/spa/app-head.php'; ?>
<body class="hold-transition layout-fixed layout-navbar-fixed layout-footer-fixed">
    <div class="wrapper">
        <?php include_once 'modulos/spa/app-header.php'; ?>
        <?php require_once 'modulos/spa/app-sidebar.php'; ?>
        <?php include_once 'modulos/inicio/inicio_view.php'; ?>
    </div>
    <?php require_once 'modulos/spa/app-footer.php'; ?>
</body>

</html>