<?php
header('Content-Type: application/json');
try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $tipo = $_GET['tipo'] ?? 'total';
    $data = [];
    
    if ($tipo === 'total') {
        $stmt = $pdo->query("SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos WHERE status='A' ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC");
        $data = $stmt->fetchAll();
    } elseif ($tipo === 'lgbt') {
        $stmt = $pdo->query("SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos WHERE status='A' AND lgbt='S' ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC");
        $data = $stmt->fetchAll();
    } elseif ($tipo === 'pix') {
        $stmt = $pdo->query("SELECT ipen, nome, nome_social, galeria, bloco, res FROM internos WHERE status='A' AND forma_pagamento='PIX' ORDER BY COALESCE(NULLIF(nome_social, ''), nome) ASC");
        $data = $stmt->fetchAll();
    } elseif ($tipo === 'salario') {
        $stmt = $pdo->query("SELECT ipen, nome, nome_social, galeria, bloco, res, regalia_setor FROM internos WHERE status='A' AND regalia_setor IS NOT NULL AND regalia_setor != '' ORDER BY regalia_setor ASC, COALESCE(NULLIF(nome_social, ''), nome) ASC");
        $data = $stmt->fetchAll();
    }
    
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
