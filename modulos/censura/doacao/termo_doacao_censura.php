<?php
// 1. Configura a data automática
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('d/m/Y');

// 2. Converte a imagem local para Base64 automaticamente
// Isso garante que a imagem apareça mesmo se você mover o arquivo ou imprimir
$path = 'logo_solucoes.png';
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Termo de Entrega - Setor Censura</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4; margin: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            padding: 0;
        }

        .document-page {
            background-color: white;
            width: 210mm;
            height: 297mm;
            padding: 25mm;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd; /* Borda fina para visualização */
        }

        /* Marca d'água "CONFIDENCIAL" */
        .document-page::before {
            content: "CONFIDENCIAL";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.02);
            pointer-events: none;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 50px;
        }

        .logo-img {
            max-height: 60px;
            display: block;
        }

        .date-box { font-size: 15px; font-weight: bold; }

        h1 {
            text-align: center;
            color: #003366;
            font-size: 22px;
            margin-bottom: 60px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 40px;
        }

        .text-line {
            font-size: 17px;
            line-height: 2.5;
            color: #000;
        }

        .input-fill {
            border-bottom: 1px solid #000;
            display: inline-block;
            vertical-align: bottom;
        }

        .declaration-card {
            border-left: 5px solid #003366;
            background: #f8f9fa;
            padding: 25px;
            margin: 20px 0;
        }

        .declaration-card p {
            font-size: 15px;
            line-height: 1.6;
            text-align: justify;
        }

        .footer-note {
            text-align: right;
            font-style: italic;
            font-size: 13px;
            color: #666;
        }

        /* Assinaturas */
        .signature-section { margin-top: auto; padding-bottom: 20px; }

        .sig-box-main {
            width: 70%;
            margin: 0 auto 60px;
            text-align: center;
        }

        .sig-row {
            display: flex;
            justify-content: space-between;
            gap: 40px;
        }

        .sig-item { flex: 1; text-align: center; }

        .line { border-top: 1.5px solid #000; margin-bottom: 5px; }
        .label { font-size: 11px; font-weight: bold; text-transform: uppercase; }

        @media print {
            body { background: none; padding: 0; }
            .document-page { border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="document-page">
    <div class="header">
        <!-- O PHP agora injeta a imagem aqui -->
        <img class="logo-img" src="<?php echo $base64; ?>" alt="Logo Soluções">
        <div class="date-box">Joinville, <?php echo $data_atual; ?></div>
    </div>

    <h1>Termo de Doação e Entrega de Item</h1>

    <div class="main-content">
        <div class="text-line">
            O <strong>Setor de Censura</strong> formaliza a entrega do seguinte item:<br>
            <strong>DESCRIÇÃO:</strong> <span class="input-fill" style="width: 75%;"></span><br>
            Ao interno: <span class="input-fill" style="width: 75%;"></span><br>
            IPEN: <span class="input-fill" style="width: 150px;"></span>
            GALERIA: <span class="input-fill" style="width: 100px;"></span>
            CELA: <span class="input-fill" style="width: 100px;"></span>
        </div>

        <div class="declaration-card">
            <p><strong>DECLARAÇÃO:</strong> O recebedor confirma a entrega do item em perfeito estado. Está ciente de que o objeto é de uso <strong>exclusivo e pessoal</strong>, sendo proibida a comercialização ou repasse a terceiros. O descumprimento acarretará apreensão e falta disciplinar.</p>
        </div>

        <p class="footer-note">Documento gerado automaticamente pelo sistema.</p>
    </div>

    <div class="signature-section">
        <div class="sig-box-main">
            <div class="line"></div>
            <div class="label">Assinatura do Interno (Recebedor)</div>
        </div>

        <div class="sig-row">
            <div class="sig-item">
                <div class="line"></div>
                <div class="label">Responsável Censura</div>
            </div>
            <div class="sig-item">
                <div class="line"></div>
                <div class="label">Equipe / Supervisão</div>
            </div>
        </div>
    </div>
</div>

<button onclick="window.print()" class="no-print" style="position: fixed; bottom: 30px; right: 30px; padding: 15px 25px; background: #003366; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">
    IMPRIMIR TERMO
</button>

</body>
</html>
