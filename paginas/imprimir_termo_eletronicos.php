<?php
$config = require __DIR__ . '/../conf/db.php'; date_default_timezone_set('America/Sao_Paulo');
if(empty($_GET['ids'])) die("Erro.");
try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass']);
    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));
    $sql = "SELECT e.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res FROM internos_eletronicos e JOIN internos i ON e.id_interno = i.ipen WHERE e.id IN ($ids)";
    $itens = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { die("Erro DB"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Termo de Responsabilidade - Eletrônicos</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: "Times New Roman", serif; font-size: 12px; }
        .page-break { page-break-after: always; }
        .header-table { width: 100%; text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
        .box { border: 1px solid #000; padding: 10px; margin-bottom: 10px; }
        .bold { font-weight: bold; }
        .regras { font-size: 11px; text-align: justify; margin: 15px 0; }
        .assinatura { margin-top: 50px; text-align: center; }
    </style>
</head>
<body onload="window.print()">
    <?php $first = $itens[0]; $nome = $first['nome_social'] ?: $first['nome']; ?>
    
    <div class="termo-container">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="../assets/img/logo_estado.png" style="max-height:60px;" onerror="this.style.display='none'"></td>
                <td width="60%">
                    ESTADO DE SANTA CATARINA<br>
                    SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                    <strong>TERMO DE ENTREGA E RESPONSABILIDADE</strong><br>
                    EQUIPAMENTOS ELETRÔNICOS
                </td>
                <td width="20%"><img src="../assets/img/logo_sap.png" style="max-height:60px;" onerror="this.style.display='none'"></td>
            </tr>
        </table>

        <div class="box" style="background: #eee;">
            <span class="bold">DADOS DO DETENTO</span><br>
            IPEN: <?= $first['id_interno'] ?><br>
            NOME: <?= $nome ?><br>
            LOCAL: Galeria <?= $first['galeria'] ?> - Bloco <?= $first['bloco'] ?> - Cela <?= $first['res'] ?>
        </div>

        <div class="box">
            <span class="bold">EQUIPAMENTOS RECEBIDOS:</span>
            <table width="100%" border="1" style="border-collapse: collapse; margin-top: 5px;">
                <tr>
                    <th>Item / Marca</th>
                    <th>Cor</th>
                    <th>Estado</th>
                    <th>Nota Fiscal</th>
                </tr>
                <?php foreach($itens as $i): ?>
                <tr>
                    <!-- Correção aqui: Concatenação -->
                    <td style="padding:4px"><?= $i['tipo_item'] . ' ' . $i['marca_modelo'] ?></td>
                    <td style="padding:4px"><?= $i['cor'] ?></td>
                    <td style="padding:4px"><?= $i['estado_conservacao'] ?></td>
                    <td style="padding:4px"><?= $i['nota_fiscal'] ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="regras">
            <strong>TERMO DE RESPONSABILIDADE:</strong><br>
            Eu, <strong><?= $nome ?></strong>, declaro ter recebido os equipamentos acima descritos em perfeito estado de funcionamento e com os devidos lacres de segurança da unidade.<br><br>
            <strong>ESTOU CIENTE QUE:</strong><br>
            1. É proibido romper, rasurar ou remover os lacres de segurança afixados nos aparelhos.<br>
            2. É proibido ceder, vender ou alugar estes equipamentos a terceiros.<br>
            3. Em caso de dano ou defeito, devo comunicar imediatamente ao chefe de segurança/plantão.<br>
            4. O rompimento do lacre implicará na apreensão do objeto e abertura de Processo Disciplinar.<br>
            5. A energia elétrica é uma concessão da unidade, devendo ser utilizada de forma consciente.<br><br>
            
            Declaro também que os itens conferem com as Notas Fiscais apresentadas.
        </div>

        <div class="assinatura">
            ___________________________________________________________<br>
            <strong><?= $nome ?></strong><br>
            Reeducando(a)<br><br>
            
            Data da Entrega: <?= date('d/m/Y') ?><br>
            Responsável pela Entrega: _________________________________
        </div>
    </div>
</body>
</html>