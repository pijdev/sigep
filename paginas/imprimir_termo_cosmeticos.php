<?php
// paginas/imprimir_termo_cosmeticos.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

if(empty($_GET['ids'])) die("Erro.");
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));
    
    $sql = "SELECT r.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res,
            (SELECT GROUP_CONCAT(CONCAT(qtd.quantidade, ' UN - ', qtd.item) SEPARATOR '<br>') 
             FROM internos_recebimento_cosmeticos_itens qtd WHERE qtd.id_recebimento = r.id) as lista_itens
            FROM internos_recebimento_cosmeticos r JOIN internos i ON r.id_interno = i.ipen WHERE r.id IN ($ids)";
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Erro DB"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Termo de Cosméticos</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 1cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 5px; }
        .header-table td { border: none; text-align: center; }
        .bold { font-weight: bold; }
        .assinatura-box { margin-top: 50px; text-align: center; }
    </style>
</head>
<body onload="window.print()">
<?php foreach($registros as $d): $nome = $d['nome_social'] ?: $d['nome']; ?>
    <div class="termo-container">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="../assets/img/logo_estado.png" style="max-height:60px;" onerror="this.style.display='none'"></td> 
                <td width="60%">
                    ESTADO DE SANTA CATARINA<br>
                    SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                    <strong>TERMO DE ENTREGA DE COSMÉTICOS</strong>
                </td>
                <td width="20%"><img src="../assets/img/logo_sap.png" style="max-height:60px;" onerror="this.style.display='none'"></td>
            </tr>
        </table>
        <hr>
        <table style="border: 2px solid #000;">
            <tr style="background:#eee;"><td colspan="2" class="bold">DADOS DO INTERNO</td></tr>
            <tr>
                <td>
                    <strong>IPEN:</strong> <?= $d['id_interno'] ?><br>
                    <strong>Nome:</strong> <?= $nome ?><br>
                    <strong>Local:</strong> <?= $d['galeria'] ?>-<?= $d['bloco'] ?>-<?= $d['res'] ?>
                </td>
                <td><strong>Data Entrega:</strong> <?= date('d/m/Y') ?></td>
            </tr>
        </table>
        <table style="border: 2px solid #000;">
            <tr style="background:#eee;"><td class="bold">ITENS ENTREGUES</td></tr>
            <tr><td style="padding: 10px;"><?= $d['lista_itens'] ?></td></tr>
        </table>
        <div style="margin: 20px 0; text-align: justify; padding: 10px; border: 1px solid #000;">
            Eu, <strong><?= $nome ?></strong>, declaro que recebi os itens de higiene/cosméticos acima listados.
        </div>
        <div class="assinatura-box">
            ________________________________________<br>
            <strong><?= $nome ?></strong><br>Reeducando(a)
        </div>
    </div>
    <div class="page-break"></div>
<?php endforeach; ?>
</body>
</html>