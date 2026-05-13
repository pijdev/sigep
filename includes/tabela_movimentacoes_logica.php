<?php
$config = require __DIR__ . '/../conf/db.php';

// Endpoint AJAX para obter movimentações com período
if (isset($_POST['action']) && $_POST['action'] === 'get_todas_movimentacoes') {
    try {
        $periodo = isset($_POST['periodo']) ? (int)$_POST['periodo'] : 30;

        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        // Construir WHERE clause para período
        $whereClause = "";
        $params = [];

        if ($periodo > 0) {
            $whereClause = " WHERE m.data_movimentacao >= DATE_SUB(CURDATE(), INTERVAL :periodo DAY)";
            $params[':periodo'] = $periodo;
        }

        $sql = "
            SELECT
                m.id,
                m.data_movimentacao,
                m.hora_chegada,
                m.hora_entrada,
                m.hora_saida,
                m.observacoes,
                m.cadastrado_por,
                v.placa,
                v.nome as veiculo_nome,
                e.nome as empresa_nome,
                mo.nome as motorista_nome,
                CASE
                    WHEN m.hora_entrada IS NOT NULL AND m.hora_saida IS NOT NULL THEN 'Entrada/Saída'
                    WHEN m.hora_entrada IS NOT NULL THEN 'Entrada'
                    WHEN m.hora_saida IS NOT NULL THEN 'Saída'
                    ELSE 'Indefinido'
                END as tipo_movimento
            FROM eclusa_movimentacoes m
            LEFT JOIN eclusa_veiculos v ON v.id = m.veiculo_id
            LEFT JOIN eclusa_empresas e ON e.id = m.empresa_id
            LEFT JOIN eclusa_motoristas mo ON mo.id = m.motorista_id
            $whereClause
            ORDER BY m.data_movimentacao DESC, m.hora_chegada DESC, m.hora_entrada DESC, m.hora_saida DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $movimentacoes = $stmt->fetchAll();

        $response = [
            'movimentacoes' => $movimentacoes,
            'total' => count($movimentacoes),
            'periodo' => $periodo
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>
