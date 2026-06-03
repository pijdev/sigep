<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once 'includes/index_logica.php';

$start = $_GET['start'] ?? '';
if ($start === 'rouparia') {

    //include_once 'includes/header.php';
    include_once 'modulos/spa/app-header.php';

    $autoloadConfig = [
        'page' => 'paginas/censura_rouparia_numeros.php',
        'title' => 'Rouparia',
        'parent' => 'Censura',
    ];

    include_once 'paginas/censura_rouparia_numeros.php';
    include_once 'includes/footer.php';
} else {
    if (isset($_SESSION['kiosk_mode']) && (int)$_SESSION['kiosk_mode'] === 1) {
        //include_once 'includes/header.php';
include_once 'modulos/spa/app-header.php';
        $autoloadConfig = [
            'page' => 'paginas/censura_rouparia_numeros.php',
            'title' => 'Rouparia',
            'parent' => 'Censura',
        ];

        include_once 'modulos/inicio/inicio_view.php';
        include_once 'includes/footer.php';
    } else {
        //include_once 'includes/header.php';
        include_once 'modulos/spa/app-header.php';
        include_once 'modulos/inicio/inicio_view.php';
        //include_once 'includes/footer.php';
    }
}
