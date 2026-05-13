<?php
// imprimir_termo_doacao_eletronicos.php
$config = require __DIR__ . '/../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

if(empty($_GET['id'])) die("Erro: ID da doação não informado.");

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass']);
    $id_doacao = (int)$_GET['id'];

    // Buscar dados da doação
    $stmt = $pdo->prepare("
        SELECT d.*,
               i_doador.nome as nome_doador, i_doador.nome_social as nome_social_doador,
               i_doador.galeria as galeria_doador, i_doador.bloco as bloco_doador, i_doador.res as cela_doador,
               i_receptor.nome as nome_receptor, i_receptor.nome_social as nome_social_receptor,
               i_receptor.galeria as galeria_receptor_interno, i_receptor.bloco as bloco_receptor_interno, i_receptor.res as cela_receptor_interno
        FROM internos_doacao_eletronicos d
        LEFT JOIN internos i_doador ON d.id_doador = i_doador.ipen
        LEFT JOIN internos i_receptor ON d.id_receptor = i_receptor.ipen
        WHERE d.id = ? AND d.termo_assinado = TRUE
    ");
    $stmt->execute([$id_doacao]);
    $doacao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doacao) {
        die("Erro: Doação não encontrada ou termo não assinado.");
    }

    // Buscar itens da doação
    $stmtItens = $pdo->prepare("
        SELECT di.*, e.data_entrada
        FROM internos_doacao_eletronicos_itens di
        JOIN internos_eletronicos e ON di.id_eletronico_original = e.id
        WHERE di.id_doacao = ?
        ORDER BY di.tipo_item
    ");
    $stmtItens->execute([$id_doacao]);
    $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) { die("Erro DB: " . $e->getMessage()); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Termo de Doação - Eletrônicos</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: "Times New Roman", serif; font-size: 12px; }
        .page-break { page-break-after: always; }
        .header-table { width: 100%; text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
        .box { border: 1px solid #000; padding: 10px; margin-bottom: 10px; }
        .bold { font-weight: bold; }
        .regras { font-size: 11px; text-align: justify; margin: 15px 0; }
        .assinatura { margin-top: 50px; text-align: center; }
        .doacao-title { background: #e8f5e8; padding: 10px; text-align: center; font-size: 14px; font-weight: bold; margin: 20px 0; }
    </style>
</head>
<body onload="window.print()">

    <div class="termo-container">
        <table class="header-table">
            <tr>
                <td width="20%"><img src="/assets/img/logo_estado.svg" style="max-height:60px;" onerror="this.style.display='none'"></td>
                <td width="60%">
                    ESTADO DE SANTA CATARINA<br>
                    SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                    <strong>TERMO DE DOAÇÃO DE EQUIPAMENTOS ELETRÔNICOS</strong><br>
                    <?php if($doacao['tipo_receptor'] === 'CELA'): ?>
                        PARA A CELA
                    <?php else: ?>
                        PARA INTERNO
                    <?php endif; ?>
                </td>
                <td width="20%"><img src="/assets/img/logo_sap.svg" style="max-height:60px;" onerror="this.style.display='none'"></td>
            </tr>
        </table>

        <div class="doacao-title">
            TERMO DE DOAÇÃO <?php echo $doacao['tipo_receptor'] === 'CELA' ? 'PARA A CELA' : 'PARA INTERNO'; ?>
        </div>

        <div class="box" style="background: #eee;">
            <span class="bold">DADOS DO DOADOR</span><br>
            IPEN: <?= $doacao['id_doador'] ?><br>
            NOME: <?= $doacao['nome_social_doador'] ?: $doacao['nome_doador'] ?><br>
            LOCAL: Galeria <?= $doacao['galeria_doador'] ?> - Bloco <?= $doacao['bloco_doador'] ?> - Cela <?= $doacao['cela_doador'] ?>
        </div>

        <?php if($doacao['tipo_receptor'] === 'CELA'): ?>
        <div class="box" style="background: #f0f8ff;">
            <span class="bold">DESTINO DA DOAÇÃO: CELA</span><br>
            Galeria: <?= $doacao['galeria_receptor'] ?><br>
            Bloco: <?= $doacao['bloco_receptor'] ?><br>
            Cela: <?= $doacao['cela_receptor'] ?>
        </div>
        <?php else: ?>
        <div class="box" style="background: #f0f8ff;">
            <span class="bold">DESTINO DA DOAÇÃO: INTERNO</span><br>
            IPEN: <?= $doacao['id_receptor'] ?><br>
            NOME: <?= $doacao['nome_social_receptor'] ?: $doacao['nome_receptor'] ?><br>
            LOCAL: Galeria <?= $doacao['galeria_receptor_interno'] ?> - Bloco <?= $doacao['bloco_receptor_interno'] ?> - Cela <?= $doacao['cela_receptor_interno'] ?>
        </div>
        <?php endif; ?>

        <div class="box">
            <span class="bold">EQUIPAMENTOS DOADOS:</span>
            <table width="100%" border="1" style="border-collapse: collapse; margin-top: 5px;">
                <tr>
                    <th>Item / Marca</th>
                    <th>Cor</th>
                    <th>Estado</th>
                    <th>Nota Fiscal</th>
                    <th>Data Entrada</th>
                </tr>
                <?php foreach($itens as $i): ?>
                <tr>
                    <td style="padding:4px"><?= $i['tipo_item'] . ' ' . $i['marca_modelo'] ?></td>
                    <td style="padding:4px"><?= $i['cor'] ?: 'Não informado' ?></td>
                    <td style="padding:4px"><?= $i['estado_conservacao'] ?: 'Não informado' ?></td>
                    <td style="padding:4px"><?= $i['nota_fiscal'] ?: 'Não informado' ?></td>
                    <td style="padding:4px"><?= date('d/m/Y', strtotime($i['data_entrada'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="regras">
            <strong>TERMO DE DOAÇÃO:</strong><br><br>

            Eu, <strong><?= $doacao['nome_social_doador'] ?: $doacao['nome_doador'] ?></strong>, IPEN <strong><?= $doacao['id_doador'] ?></strong>,
            alocado na cela <strong><?= $doacao['cela_doador'] ?></strong> da galeria <strong><?= $doacao['galeria_doador'] ?></strong> desta unidade prisional,
            venho por meio deste, expressar o meu interesse em doar
            <?php
            $tipos = array_unique(array_column($itens, 'tipo_item'));
            $qtd = count($itens);
            if ($qtd === 1) {
                echo "o seguinte item: <strong>" . $itens[0]['tipo_item'] . "</strong>";
            } else {
                echo "os seguintes itens: <strong>" . implode(', ', $tipos) . "</strong> (total de $qtd itens)";
            }
            ?>
            <?php if($doacao['tipo_receptor'] === 'CELA'): ?>
                para a cela <strong><?= $doacao['cela_receptor'] ?></strong> da galeria <strong><?= $doacao['galeria_receptor'] ?></strong>.
            <?php else: ?>
                para <strong><?= $doacao['nome_social_receptor'] ?: $doacao['nome_receptor'] ?></strong> (IPEN: <strong><?= $doacao['id_receptor'] ?></strong>).
            <?php endif; ?>

            <br><br>Estou ciente de que se trata de uma doação <strong>IRREVOGÁVEL E INTRANSFERÍVEL</strong>, não podendo mais requerer o mesmo item (ou os mesmos itens) futuramente.

            <br><br>Estou ciente de que se trata do procedimento da <strong>PENITENCIÁRIA INDUSTRIAL DE JOINVILLE</strong>.

            <br><br>Para maior clareza, firmo o presente.
        </div>

        <div class="assinatura">
            _____________________________________________<br>
            <strong><?= $doacao['nome_social_doador'] ?: $doacao['nome_doador'] ?></strong><br>
            Doador(a)<br><br>

            Data da Doação: <?= date('d/m/Y', strtotime($doacao['data_doacao'])) ?><br><br><br>

            _____________________________________________<br>
            <strong>MONITOR DA CENSURA</strong><br><br>

            _____________________________________________<br>
            <strong>EQUIPE</strong><br>
            (Assistente / Supervisor)
        </div>

        <div style="margin-top: 30px; font-size: 10px; text-align: center; border-top: 1px solid #000; padding-top: 10px;">
            <strong>DOAÇÃO PROCESSADA EM: <?= date('d/m/Y H:i:s', strtotime($doacao['data_doacao'])) ?></strong><br>
            <strong>RESPONSÁVEL PELO CADASTRO: <?= $doacao['usuario_cadastro'] ?></strong><br>
            <strong>ID DA DOAÇÃO: <?= $doacao['id'] ?></strong>
        </div>
    </div>

</body>
</html>
