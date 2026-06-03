<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

if (isset($_GET['acao']) || isset($_POST['acao'])) {
    require_once __DIR__ . '/SolicitacoesController.php';
    exit;
}

require_once __DIR__ . '/SolicitacoesView.php';
