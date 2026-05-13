<?php
// paginas/ajax_salvar_cadastro.php
// Handler AJAX unificado para salvar cadastro de internos
// Pode ser chamado tanto por internos_cadastro.php quanto por censura_rouparia_numeros.php

ob_start();
date_default_timezone_set('America/Sao_Paulo');
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

// CONEXÃO
$pdo = null;
try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    ob_clean();
    die(json_encode(['success' => false, 'error' => 'Erro na conexão com banco.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_cadastro') {
    ob_clean();
    try {
        $sql = "UPDATE internos SET
                nome_social = ?,
                lgbt = ?,
                apelido = ?,
                forma_pagamento = ?,
                regalia = ?,
                cor_roupa = ?,
                regalia_setor = ?,
                kit = ?,
                regalia_kit = ?,
                tamanho_kit = ?
            WHERE ipen = ?";

        $kit = !empty($_POST['kit']) ? $_POST['kit'] : null;
        $regkit = !empty($_POST['regalia_kit']) ? $_POST['regalia_kit'] : null;

        $pdo->prepare($sql)->execute([
            $_POST['nome_social'] ?? '',
            $_POST['lgbt'],
            $_POST['apelido'] ?? '',
            $_POST['forma_pagamento'] ?? '',
            $_POST['regalia'],
            $_POST['cor_roupa'],
            $_POST['regalia_setor'] ?? '',
            $kit,
            $regkit,
            $_POST['tamanho_kit'],
            $_POST['ipen']
        ]);

        echo json_encode(['success' => true]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Se chegar aqui, método inválido
echo json_encode(['success' => false, 'error' => 'Requisição inválida.']);
exit;
?>
