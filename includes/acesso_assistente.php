<?php
// includes/acesso_assistente.php
// Controle de acesso específico para usuário assistente

// Verificar se é o usuário assistente e bloquear acessos não autorizados
if (isset($_SESSION['user_nome']) && strpos($_SESSION['user_nome'], 'Assistente') !== false) {
    // Permitir apenas a página de painel de internos
    $pagina_atual = $_SERVER['REQUEST_URI'] ?? '';
    $pagina_permitida = 'modulos/geral/painel_internos/internos_painel_view.php';

    // Permitir também requisições AJAX para o painel e arquivos relacionados
    $eh_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    $referer_permitido = isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'sigep') !== false;

    // Verificar se está tentando acessar página não permitida
    if (
        !$eh_ajax &&
        strpos($pagina_atual, $pagina_permitida) === false &&
        strpos($pagina_atual, 'auth/login.php') === false &&
        strpos($pagina_atual, 'auth/logout.php') === false &&
        strpos($pagina_atual, 'modulos/geral/painel_internos/internos_painel_logica.php') === false &&
        strpos($pagina_atual, 'assets/js/') === false &&
        strpos($pagina_atual, 'assets/css/') === false &&
        $pagina_atual !== '/' &&
        $pagina_atual !== '/index.php'
    ) {

        // Redirecionar para a página permitida
        header('Location: /');
        exit;
    }
}
