<?php
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('d/m/Y');

$path = 'logo_solucoes.png';
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Termo de Doação</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
        }

        /* Página */
        .document-page {
            width: 210mm;
            padding: 18mm;
            background: #fff;
            position: relative;
        }

        /* Marca d’água */
        .document-page::before {
            content: "CONFIDENCIAL";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 70px;
            color: rgba(0, 0, 0, 0.02);
        }

        /* Cabeçalho */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .logo-img {
            max-height: 55px;
        }

        .date-box {
            font-size: 14px;
            font-weight: bold;
        }

        /* Título */
        h1 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 30px;
            color: #003366;
            text-transform: uppercase;
        }

        /* Conteúdo */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .text-line {
            font-size: 15px;
            line-height: 2;
        }

        /* Linhas para preenchimento */
        .input-fill {
            border-bottom: 1px solid #000;
            display: inline-block;
            height: 20px;
            vertical-align: bottom;
        }

        /* Blocos */
        .declaration-card {
            border-left: 4px solid #003366;
            background: #f8f9fa;
            padding: 15px;
        }

        .declaration-card p {
            font-size: 14px;
            line-height: 1.4;
            text-align: justify;
        }

        /* Rodapé */
        .footer-note {
            text-align: right;
            font-size: 12px;
            color: #666;
        }

        /* Assinaturas */
        .signature-section {
            margin-top: 30px;
        }

        .sig-box-main {
            width: 70%;
            margin: 0 auto 30px;
            text-align: center;
        }

        .sig-row {
            display: flex;
            gap: 30px;
        }

        .sig-item {
            flex: 1;
            text-align: center;
        }

        .line {
            border-top: 1.5px solid #000;
            margin-bottom: 5px;
        }

        .label {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Impressão */
        @media print {
            body {
                background: none;
            }

            .no-print {
                display: none;
            }

            .document-page {
                padding: 15mm;
            }

            h1 {
                font-size: 18px;
            }

            .text-line {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="document-page">

        <div class="header">
            <img class="logo-img" src="<?php echo $base64; ?>">
            <div class="date-box">Joinville, <?php echo $data_atual; ?></div>
        </div>

        <h1>Termo de Doação do Interno</h1>

        <div class="main-content">

            <div class="text-line">
                O <strong>Setor de Censura</strong> formaliza a doação do seguinte item:<br><br>

                <strong>DESCRIÇÃO DO ITEM:</strong><br>
                <span class="input-fill" style="width: 100%;"></span><br><br>

                <strong>NOME DO DOADOR:</strong>
                <span class="input-fill" style="width: 45%;"></span>
                IPEN:
                <span class="input-fill" style="width: 20%;"></span><br>

                GALERIA:
                <span class="input-fill" style="width: 20%;"></span>
                CELA:
                <span class="input-fill" style="width: 20%;"></span>

                <br><br>

                <strong>NOME DO RECEBEDOR:</strong>
                <span class="input-fill" style="width: 45%;"></span>
                IPEN:
                <span class="input-fill" style="width: 20%;"></span><br>

                GALERIA:
                <span class="input-fill" style="width: 20%;"></span>
                CELA:
                <span class="input-fill" style="width: 20%;"></span>
            </div>

            <div class="declaration-card">
                <p><strong>DECLARAÇÃO DO DOADOR:</strong> Declaro que realizo a doação de forma voluntária e irreversível.</p>
            </div>

            <div class="declaration-card">
                <p><strong>DECLARAÇÃO DO RECEBEDOR:</strong> Declaro que recebi o item e estou ciente de que é de uso pessoal, vedada sua comercialização ou repasse, podendo ser apreendido caso haja descumprimento.</p>
            </div>

        </div>

        <div class="signature-section">

            <div class="sig-box-main">
                <div class="line"></div>
                <div class="label">Assinatura do Doador</div>
            </div>

            <div class="sig-box-main">
                <div class="line"></div>
                <div class="label">Assinatura do Recebedor</div>
            </div>

            <div class="sig-row">
                <div class="sig-item">
                    <div class="line"></div>
                    <div class="label">Responsável</div>
                </div>
                <div class="sig-item">
                    <div class="line"></div>
                    <div class="label">Supervisão</div>
                </div>
            </div>

        </div>

    </div>

    <button onclick="window.print()" class="no-print"
        style="position: fixed; bottom: 20px; right: 20px; padding: 12px 20px; background:#003366; color:#fff; border:none; border-radius:5px; cursor:pointer;">
        IMPRIMIR
    </button>

</body>

</html>
