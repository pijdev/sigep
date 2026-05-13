<?php
// paginas/imprimir_recibo_livros.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

if(empty($_GET['ids'])) die("Erro: Nenhum ID informado.");

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);

    $ids = implode(',', array_map('intval', explode(',', $_GET['ids'])));
    
    $sql = "SELECT l.*, i.nome, i.nome_social,
            (SELECT GROUP_CONCAT(CONCAT(li.titulo_livro, IF(li.autor<>'', CONCAT(' (',li.autor,')'), '')) SEPARATOR ' || ') 
             FROM internos_recebimento_livros_itens li WHERE li.id_recebimento = l.id) as lista_livros
            FROM internos_recebimento_livros l
            JOIN internos i ON l.id_interno = i.ipen
            WHERE l.id IN ($ids)";
    
    $registros = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die("Erro DB"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Recibo de Entrega de Livros</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 0.5cm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; color: #000; }
        
        .recibo-container {
            width: 100%; height: 32%; border-bottom: 2px dashed #666;
            margin-bottom: 1%; box-sizing: border-box; padding: 15px;
            display: block; page-break-inside: avoid;
        }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        td { vertical-align: top; padding: 2px 4px; }
        
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 5px; padding-bottom: 5px; }
        .header h3 { margin: 0; font-size: 14px; text-transform: uppercase; }
        .header p { margin: 0; font-size: 10px; }

        .box { border: 1px solid #000; padding: 5px; margin-bottom: 5px; background: #f9f9f9; }
        .box-title { font-weight: bold; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #ccc; margin-bottom: 3px; }
        .items-list { font-size: 11px; font-weight: bold; padding: 5px; }
        .footer { font-size: 9px; text-align: center; margin-top: 5px; font-style: italic; }
        .label { font-weight: bold; color: #333; }
        .value { text-transform: uppercase; }
    </style>
</head>
<body onload="window.print()">

<?php foreach($registros as $r): 
    $nomeExibicao = !empty($r['nome_social']) ? $r['nome_social'] : $r['nome'];
?>
    <div class="recibo-container">
        <div class="header">
            <h3>Estado de Santa Catarina</h3>
            <p>SISTEMA PRISIONAL INTEGRADO - CENSURA</p>
            <strong>RECIBO DE ENTREGA DE LIVROS (<?= $r['entregue_por_tipo'] ?>)</strong>
        </div>

        <table style="margin-bottom: 5px;">
            <tr>
                <td width="60%">
                    <div class="box" style="height: 55px;">
                        <div class="box-title">DADOS DO INTERNO</div>
                        <span class="label">IPEN:</span> <span class="value"><?= $r['id_interno'] ?></span><br>
                        <span class="label">NOME:</span> <span class="value"><?= $nomeExibicao ?></span>
                    </div>
                </td>
                <td width="40%">
                    <div class="box" style="height: 55px;">
                        <div class="box-title">PROTOCOLO</div>
                        <span class="label">DATA:</span> <?= date('d/m/Y H:i', strtotime($r['data_recebimento'])) ?><br>
                        <span class="label">Nº:</span> <?= str_pad($r['id'], 6, '0', STR_PAD_LEFT) ?>
                    </div>
                </td>
            </tr>
        </table>

        <div class="box">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <span class="label">ENTREGUE POR:</span><br>
                        <span class="value"><?= $r['entregue_por_nome'] ?></span>
                    </td>
                    <td width="50%">
                        <span class="label">RECEBIDO POR (AGENTE):</span><br>
                        <span class="value"><?= $r['cadastrado_por'] ?></span>
                    </td>
                </tr>
            </table>
        </div>

        <div class="box" style="flex: 1;">
            <div class="box-title">LIVROS RECEBIDOS</div>
            <div class="items-list">
                <?= str_replace(' || ', '<br>', $r['lista_livros']) ?>
            </div>
        </div>

        <div class="footer">
            Declaro ter entregue os livros acima listados. Os mesmos passarão por revista. Livros não autorizados ou com anotações serão devolvidos.
        </div>
    </div>
<?php endforeach; ?>
</body>
</html>