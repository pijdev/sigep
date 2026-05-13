<?php
// index.php - Entry point SPA principal
// Sempre carrega o layout SPA na raiz /

require_once 'includes/index_logica.php';

// Verificar se há parâmetro start (manter compatibilidade)
$start = $_GET['start'] ?? '';
if ($start === 'rouparia') {
    // Manter comportamento original para rouparia (temporário)
    include_once 'includes/header.php';
    include_once 'includes/sidebar.php';

    $autoloadConfig = [
        'page' => 'paginas/censura_rouparia_numeros.php',
        'title' => 'Rouparia',
        'parent' => 'Censura',
    ];

    // Incluir o conteúdo original
    include_once 'paginas/censura_rouparia_numeros.php';
    include_once 'includes/footer.php';
} else {
    // Verificar se é modo quiosque e redirecionar para Rouparia
    if (isset($_SESSION['kiosk_mode']) && (int)$_SESSION['kiosk_mode'] === 1) {
        // Modo quiosque: carregar SPA e depois fazer autoload da Rouparia
        include_once 'includes/header.php';
        include_once 'includes/sidebar.php';

        $autoloadConfig = [
            'page' => 'paginas/censura_rouparia_numeros.php',
            'title' => 'Rouparia',
            'parent' => 'Censura',
        ];

        // Carregar o módulo início como conteúdo inicial
        include_once 'modulos/inicio/inicio_view.php';
        include_once 'includes/footer.php';
    } else {
        // Carregar o SPA na raiz - SEMPRE!
        include_once 'includes/header.php';
        include_once 'includes/sidebar.php';

        // Carregar o módulo início como conteúdo inicial
        include_once 'modulos/inicio/inicio_view.php';
        include_once 'includes/footer.php';
    }
}
?>
