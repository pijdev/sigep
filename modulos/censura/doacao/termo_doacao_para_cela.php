<?php
// 1. Configura a data automática
setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'portuguese');
date_default_timezone_set('America/Sao_Paulo');
$data_atual = date('d/m/Y');

// 2. Converte a imagem local para Base64 automaticamente
$path = 'logo_solucoes.png';
$base64 = '';
if (file_exists($path)) {
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Termo de Doação para a Cela</title>
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
            padding: 20mm 25mm;
            position: relative;
            display: flex;
            flex-direction: column;
            border: 1px solid #ddd;
        }

        /* Marca d'água */
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
            margin-bottom: 40px;
        }

        .logo-img { max-height: 55px; display: block; }
        .date-box { font-size: 14px; font-weight: bold; color: #000; }

        h1 {
            text-align: center;
            color: #003366;
            font-size: 22px;
            margin-bottom: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Ajuste fino nas linhas de texto e campos */
        .text-line {
            font-size: 16px;
            line-height: 2.8; /* Aumentado para melhor legibilidade */
            color: #000;
        }

        .input-fill {
            border-bottom: 1px solid #000;
            display: inline-block;
            height: 20px;
            margin-left: 5px;
            margin-right: 5px;
        }

        .declaration-card {
            border-left: 4px solid #003366;
            background: #f8f9fa;
            padding: 20px;
            margin-top: 30px;
        }

        .declaration-card p {
            font-size: 14px;
            line-height: 1.6;
            text-align: justify;
        }

        .footer-note {
            text-align: right;
            font-style: italic;
            font-size: 11px;
            color: #888;
            margin-top: 15px;
        }

        /* Seção de Assinaturas Otimizada */
        .signature-section { margin-top: auto; padding-bottom: 10px; }

        .sig-full-width {
            width: 75%;
            margin: 0 auto 45px;
            text-align: center;
        }

        .sig-row {
            display: flex;
            justify-content: space-between;
            gap: 50px;
            margin-bottom: 45px;
        }

        .sig-item { flex: 1; text-align: center; }
        .line { border-top: 1.2px solid #000; margin-bottom: 5px; }
        .label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #333; }

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
        <img class="logo-img" src="<?php echo $base64; ?>" alt="Logo Soluções">
        <div class="date-box">Joinville, <?php echo $data_atual; ?></div>
    </div>

    <h1>Termo de Doação para a CELA</h1>

    <div class="main-content">
        <div class="text-line">
            Eu, <span class="input-fill" style="width: 500px;"></span> IPEN: <span class="input-fill" style="width: 140px;"></span>,
            <br>
            atualmente alocado na <strong>CELA:</strong> <span class="input-fill" style="width: 130px;"></span> da <strong>GALERIA:</strong> <span class="input-fill" style="width: 130px;"></span>,
            <br>
            venho por meio deste, expressar o meu interesse em doar o seguinte item:
            <br>
            <strong>ITEM:</strong> <span class="input-fill" style="width: 530px;"></span>
            <br>
            para a <strong>CELA:</strong> <span class="input-fill" style="width: 130px;"></span> da <strong>GALERIA:</strong> <span class="input-fill" style="width: 130px;"></span>.
        </div>

        <div class="declaration-card">
            <p><strong>DECLARAÇÃO:</strong> Estou ciente de que se trata de uma doação <strong>IRREVOGÁVEL E INTRANSFERÍVEL</strong>, não podendo mais requerer o mesmo item futuramente. Declaro que a doação é feita por livre e espontânea vontade, seguindo os procedimentos da Penitenciária Industrial de Joinville.</p>
        </div>

        <p class="footer-note">Documento gerado automaticamente pelo sistema.</p>
    </div>

    <div class="signature-section">
        <!-- Doador -->
        <div class="sig-full-width">
            <div class="line"></div>
            <div class="label">Assinatura do Doador (Interno)</div>
        </div>

        <!-- Monitor e Equipe -->
        <div class="sig-row">
            <div class="sig-item">
                <div class="line"></div>
                <div class="label">Monitor Censura</div>
            </div>
            <div class="sig-item">
                <div class="line"></div>
                <div class="label">Equipe / Supervisão</div>
            </div>
        </div>

        <!-- Supervisor Final -->
        <div class="sig-full-width" style="width: 50%; margin-bottom: 0;">
            <div class="line"></div>
            <div class="label">Assistente / Supervisor</div>
        </div>
    </div>
</div>

<button onclick="window.print()" class="no-print" style="position: fixed; bottom: 30px; right: 30px; padding: 15px 25px; background: #003366; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
    🖨️ IMPRIMIR TERMO
</button>

</body>
</html>
