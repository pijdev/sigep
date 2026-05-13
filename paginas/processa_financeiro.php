<?php
header('Content-Type: application/json');
ob_start();
try {
    $config = require __DIR__ . '/../conf/db.php'; 
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $action = $_POST['db_action'] ?? '';

    if ($action === 'save_financeiro') {
        $tipo = $_POST['tipo_saldo'];
        $val = str_replace(',', '.', $_POST['valor']);
        $table = ($tipo === 'Salário') ? 'peculio_saldos_trabalho' : 'peculio_saldos_pix';
        
        $sql = "INSERT INTO $table (ipen, mes_referencia, valor) VALUES (?,?,?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)";
        $pdo->prepare($sql)->execute([$_POST['ipen'], $_POST['mes_ref'], $val]);
        
        ob_clean();
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'del_fin') {
        $table = ($_POST['tipo'] === 'sal') ? 'peculio_saldos_trabalho' : 'peculio_saldos_pix';
        $pdo->prepare("DELETE FROM $table WHERE id = ?")->execute([$_POST['id']]);
        ob_clean();
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
exit;