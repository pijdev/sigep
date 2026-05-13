<?php
// paginas/imprimir_termo_rouparia.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

if(empty($_GET['ids'])) die("Nenhum ID selecionado.");

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);

    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));

    $sql = "SELECT r.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res, i.situacao,
            (SELECT GROUP_CONCAT(CONCAT(qtd.quantidade, ' UN - ', qtd.item, ' (', qtd.detalhes, ')') SEPARATOR '<br>') 
             FROM internos_recebimento_roupas_itens qtd WHERE qtd.id_recebimento = r.id) as lista_itens,
            (SELECT SUM(quantidade) FROM internos_recebimento_roupas_itens WHERE id_recebimento = r.id) as total_qtd
            FROM internos_recebimento_roupas r
            JOIN internos i ON r.id_interno = i.ipen
            WHERE r.id IN ($ids)";
    
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die($e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Termo de Recebimento</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 1cm; }
        body { font-family: "Times New Roman", Times, serif; font-size: 12px; margin: 0; }
        .page-break { page-break-after: always; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: top; }
        .no-border { border: none !important; }
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
    // Lógica LGBT: Se tem nome social, mostra SÓ o social no relatório
    $nomeRelatorio = !empty($d['nome_social']) ? $d['nome_social'] : $d['nome'];
?>
    <div class="termo-container">
        <!-- CABEÇALHO -->
        <table class="header-table">
            <tr>
                <td width="20%"><img src="../assets/img/logo_estado.png" style="max-height:60px; max-width:80px;" onerror="this.style.display='none'"></td> 
                <td width="60%">
                    ESTADO DE SANTA CATARINA<br>
                    SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                    <strong>UNIDADE PRISIONAL</strong><br>
                    TERMO DE ENTREGA DE ROUPARIA
                </td>
                <td width="20%"><img src="../assets/img/logo_sap.png" style="max-height:60px; max-width:80px;" onerror="this.style.display='none'"></td>
            </tr>
        </table>
        <hr>

        <!-- DADOS -->
        <table style="border: 2px solid #000;">
            <tr class="bg-gray"><td colspan="2" class="bold">DADOS DO INTERNO</td></tr>
            <tr>
                <td width="70%">
                    <strong>IPEN:</strong> <?= $d['id_interno'] ?><br>
                    <strong>Nome:</strong> <?= $nomeRelatorio ?><br>
                    <strong>Local:</strong> Galeria <?= $d['galeria'] ?> - Bloco <?= $d['bloco'] ?> - Cela <?= $d['res'] ?>
                </td>
                <td width="30%"><strong>Situação:</strong> <?= $d['situacao'] ?></td>
            </tr>
        </table>

        <!-- ITENS -->
        <table style="border: 2px solid #000;">
            <tr class="bg-gray"><td colspan="3" class="bold">ITENS ENTREGUES AO INTERNO</td></tr>
            <tr>
                <td class="bold">Data do Recebimento na Portaria</td>
                <td class="bold">Quantidade Total</td>
                <td class="bold">Movimentação</td>
            </tr>
            <tr>
                <td><?= date('d/m/Y', strtotime($d['data_recebimento'])) ?></td>
                <td class="red"><?= $d['total_qtd'] ?> ITENS</td>
                <td class="green">ENTRADA</td>
            </tr>
            <tr><td colspan="3" class="bg-gray bold">Descrição dos Itens:</td></tr>
            <tr><td colspan="3" style="padding: 10px;"><?= $d['lista_itens'] ?></td></tr>
            <tr>
                <td><strong>Origem:</strong> <?= $d['entregue_por_tipo'] ?></td>
                <td><strong>Entregue Por (Familiar):</strong> <?= $d['entregue_por_nome'] ?></td>
                <td><strong>Recebido Por (Agente):</strong> <?= $d['cadastrado_por'] ?></td>
            </tr>
        </table>

        <!-- DECLARAÇÃO -->
        <div style="margin: 20px 0; text-align: justify; font-size: 14px; border: 1px solid #000; padding: 10px;">
            Eu, <strong><?= $nomeRelatorio ?></strong>, prontuário <strong><?= $d['id_interno'] ?></strong>, declaro que recebi os itens acima listados, conferidos e em conformidade com as normas da unidade prisional.
        </div>

        <!-- ASSINATURA -->
        <div class="assinatura-box">
            __________________________________________________________________<br>
            <strong><?= $nomeRelatorio ?></strong><br>
            Data da Entrega: ____/____/________
        </div>

    </div>
    <div class="page-break"></div>
<?php endforeach; ?>
</body>
</html>