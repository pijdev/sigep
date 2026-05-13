<?php
// paginas/imprimir_termo_livros.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

if(empty($_GET['ids'])) die("Nenhum ID.");

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);

    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));

    $sql = "SELECT l.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res,
            (SELECT GROUP_CONCAT(CONCAT('LIVRO: ', li.titulo_livro, IF(li.autor<>'', CONCAT(' (Autor: ',li.autor,')'), '')) SEPARATOR '<br>') 
             FROM internos_recebimento_livros_itens li WHERE li.id_recebimento = l.id) as lista_livros,
            (SELECT COUNT(*) FROM internos_recebimento_livros_itens li WHERE li.id_recebimento = l.id) as total_qtd
            FROM internos_recebimento_livros l
            JOIN internos i ON l.id_interno = i.ipen
            WHERE l.id IN ($ids)";
    
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die($e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Termo de Entrega de Livros</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 1cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; margin: 0; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: top; }
        .header-table td { border: none; text-align: center; }
        .bold { font-weight: bold; }
        .red { color: red; font-weight: bold; }
        .green { color: green; font-weight: bold; }
        .bg-gray { background-color: #eee; }
        .assinatura-box { margin-top: 50px; text-align: center; }
    </style>
</head>
<body onload="window.print()">

<?php foreach($registros as $d): 
    $nomeRelatorio = !empty($d['nome_social']) ? $d['nome_social'] : $d['nome'];
?>
    <div class="termo-container">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="../assets/img/logo_estado.png" style="max-height:60px;" onerror="this.style.display='none'"></td> 
                <td width="60%">
                    ESTADO DE SANTA CATARINA<br>
                    SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                    <strong>UNIDADE PRISIONAL (SIGEP)</strong><br>
                    ENTREGA DE LIVROS - EDUCACIONAL/RELIGIOSO
                </td>
                <td width="20%"><img src="../assets/img/logo_sap.png" style="max-height:60px;" onerror="this.style.display='none'"></td>
            </tr>
        </table>
        <hr>

        <table style="border: 2px solid #000;">
            <tr class="bg-gray"><td colspan="2" class="bold">DADOS DO INTERNO</td></tr>
            <tr>
                <td width="70%">
                    <strong>IPEN:</strong> <?= $d['id_interno'] ?><br>
                    <strong>Nome:</strong> <?= $nomeRelatorio ?><br>
                    <strong>Local:</strong> Galeria <?= $d['galeria'] ?> - Bloco <?= $d['bloco'] ?> - Cela <?= $d['res'] ?>
                </td>
                <td width="30%"><strong>Data Entrega:</strong> <?= date('d/m/Y') ?></td>
            </tr>
        </table>

        <table style="border: 2px solid #000;">
            <tr class="bg-gray"><td colspan="3" class="bold">LIVROS ENTREGUES</td></tr>
            <tr>
                <td class="bold">Data Recebimento na Portaria</td>
                <td class="bold">Quantidade Total</td>
                <td class="bold">Movimentação</td>
            </tr>
            <tr>
                <td><?= date('d/m/Y', strtotime($d['data_recebimento'])) ?></td>
                <td class="red"><?= $d['total_qtd'] ?> LIVROS</td>
                <td class="green">ENTRADA</td>
            </tr>
            <tr><td colspan="3" class="bg-gray bold">Títulos:</td></tr>
            <tr><td colspan="3" style="padding: 10px;"><?= $d['lista_livros'] ?></td></tr>
            <tr>
                <td><strong>Origem:</strong> <?= $d['entregue_por_tipo'] ?></td>
                <td><strong>Entregue Por:</strong> <?= $d['entregue_por_nome'] ?></td>
                <td><strong>Recebido Por:</strong> <?= $d['cadastrado_por'] ?></td>
            </tr>
        </table>

        <div style="margin: 20px 0; text-align: justify; font-size: 14px; border: 1px solid #000; padding: 10px;">
            Eu, <strong><?= $nomeRelatorio ?></strong>, prontuário <strong><?= $d['id_interno'] ?></strong>, declaro que recebi os livros acima descritos. Comprometo-me a mantê-los em bom estado de conservação e devolvê-los ou trocá-los conforme regras da unidade.
        </div>

        <div class="assinatura-box">
            __________________________________________________________________<br>
            <strong><?= $nomeRelatorio ?></strong><br>
            Reeducando(a)
        </div>
    </div>
    <div class="page-break"></div>
<?php endforeach; ?>
</body>
</html>