<?php
/**
 * Roteador local do módulo de Solicitações - SPA Architecture
 * Despacha requisições para o Controller e renderiza a View
 * 
 * Este arquivo atua como ponto de entrada único para o módulo,
 * mantendo a arquitetura SPA sem recarregar a página.
 */

// Inicia sessão se não estiver ativa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Configurações de timezone e encoding
date_default_timezone_set('America/Sao_Paulo');
mb_internal_encoding('UTF-8');

// Se houver parâmetro 'acao', é uma requisição de API - despacha para o Controller
if (isset($_GET['acao']) || isset($_POST['acao'])) {
    require_once __DIR__ . '/SolicitacoesController.php';
    exit;
}

// Caso contrário, é uma requisição HTML normal - renderiza a View diretamente
require_once __DIR__ . '/SolicitacoesView.php';
