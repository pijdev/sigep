<?php
// paginas/imprimir_recibo_cosmeticos.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

if(empty($_GET['ids'])) die("Erro.");
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));
    
    $sql = "SELECT r.*, i.nome, i.nome_social,
            (SELECT GROUP_CONCAT(CONCAT(qtd.quantidade, ' ', qtd.item) SEPARATOR ' || ') 
             FROM internos_recebimento_cosmeticos_itens qtd WHERE qtd.id_recebimento = r.id) as lista_itens
            FROM internos_recebimento_cosmeticos r JOIN internos i ON r.id_interno = i.ipen WHERE r.id IN ($ids)";
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Erro DB"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recibo de Cosméticos</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 0.5cm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; }
        .recibo-container { width: 100%; height: 32%; border-bottom: 2px dashed #666; margin-bottom: 1%; box-sizing: border-box; padding: 10px; display: block; page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        .header { text-align: center; border-bottom: 2px solid #e83e8c; margin-bottom: 5px; }
        .box { border: 1px solid #333; padding: 5px; margin-bottom: 5px; background: #fff0f6; }
        .label { font-weight: bold; }
    </style>
</head>
<body onload="window.print()">
<?php foreach($registros as $r): $nome = $r['nome_social'] ?: $r['nome']; ?>
    <div class="recibo-container">
        <div class="header">
            <h3>RECIBO DE ENTREGA - COSMÉTICOS (LGBT)</h3>
        </div>
        <table style="margin-bottom: 5px;">
            <tr>
                <td><span class="label">IPEN:</span> <?= $r['id_interno'] ?></td>
                <td><span class="label">NOME:</span> <?= $nome ?></td>
                <td align="right"><span class="label">DATA:</span> <?= date('d/m/Y H:i', strtotime($r['data_recebimento'])) ?></td>
            </tr>
        </table>
        <div class="box">
            <span class="label">ITENS RECEBIDOS:</span><br>
            <?= str_replace(' || ', ' - ', $r['lista_itens']) ?>
        </div>
        <div style="font-size:10px; text-align:center;">
            Entregue por: <?= $r['entregue_por_nome'] ?> (<?= $r['entregue_por_tipo'] ?>) | Recebido por: <?= $r['cadastrado_por'] ?>
            <br><i>Os itens passarão por revista. Embalagens devem ser transparentes.</i>
        </div>
    </div>
<?php endforeach; ?>
</body>
</html>