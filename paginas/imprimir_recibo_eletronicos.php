<?php
$config = require __DIR__ . '/../conf/db.php'; date_default_timezone_set('America/Sao_Paulo');
if(empty($_GET['ids'])) die("Erro.");
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass']);
    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));
    $sql = "SELECT e.*, i.nome, i.nome_social FROM internos_eletronicos e JOIN internos i ON e.id_interno = i.ipen WHERE e.id IN ($ids)";
    $itens = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Erro DB"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recibo Eletrônicos</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 0.5cm; }
        body { font-family: sans-serif; margin: 0; padding: 0; }
        .recibo { width: 100%; height: 32%; border-bottom: 2px dashed #000; margin-bottom: 1%; box-sizing: border-box; padding: 15px; display: block; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #6f42c1; margin-bottom: 5px; }
        .box { border: 1px solid #333; padding: 5px; background: #f3f0ff; margin-bottom: 5px; }
        .bold { font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
    <div class="recibo">
        <div class="header"><h3>RECIBO DE ENTRADA - ELETRÔNICOS</h3></div>
        <?php $first = $itens[0]; $nome = $first['nome_social'] ?: $first['nome']; ?>
        <table style="margin-bottom: 10px;">
            <tr>
                <td><span class="bold">IPEN:</span> <?= $first['id_interno'] ?></td>
                <td><span class="bold">NOME:</span> <?= $nome ?></td>
                <td align="right"><span class="bold">DATA:</span> <?= date('d/m/Y H:i', strtotime($first['data_entrada'])) ?></td>
            </tr>
        </table>
        
        <div class="box">
            <span class="bold">ITENS RECEBIDOS NA PORTARIA:</span><br>
            <table width="100%">
            <?php foreach($itens as $i): ?>
                <tr>
                    <!-- Correção aqui: Concatenação -->
                    <td>• <strong><?= $i['tipo_item'] . ' ' . $i['marca_modelo'] ?></strong></td>
                    <td>NF: <?= $i['nota_fiscal'] ?></td>
                </tr>
            <?php endforeach; ?>
            </table>
        </div>
        
        <div style="font-size: 10px; text-align: center; margin-top: 10px;">
            Entregue por: <?= $first['entregue_por'] ?> | Recebido por: <?= $first['cadastrado_por'] ?><br>
            <i>Os itens passarão por vistoria técnica e lacre de segurança na CENSURA antes da entrega ao interno.</i>
        </div>
    </div>
</body>
</html>