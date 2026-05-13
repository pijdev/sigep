<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Impressão SAP-SC - 10 Cards</title>
    <style>
        * { box-sizing: border-box; }

        /* Configuração de visualização e Reset */
        body {
            margin: 0;
            padding: 0;
            background-color: #525659;
            font-family: Arial, sans-serif;
        }

        /* Container da Folha A4 */
        .page {
            width: 21cm;
            height: 29.7cm;
            margin: 10px auto;
            padding: 0.5cm; /* Margem de segurança da impressora */
            background: white;
            display: grid;
            grid-template-columns: 1fr 1fr; /* 2 colunas */
            grid-template-rows: repeat(5, 1fr); /* 5 linhas iguais */
            gap: 8px; /* Espaço entre os cards */
        }

        @media print {
            body { background: none; }
            .no-print { display: none !important; }
            .page { margin: 0; border: none; page-break-after: always; }
            @page { size: A4; margin: 0; }
        }

        /* Estilo do Card */
        .card {
            border: 1px solid #000;
            padding: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center; /* Centraliza o conteúdo verticalmente */
            overflow: hidden;
        }

        .header-container { text-align: center; margin-bottom: 5px; }
        .header { font-weight: bold; text-decoration: underline; font-size: 12px; }
        .sub-header { font-weight: bold; font-size: 10px; }

        .item {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
            font-size: 9.5px;
            line-height: 1.1;
        }

        .circle {
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            border-radius: 50%;
            margin-right: 6px;
            flex-shrink: 0;
        }

        .line-other {
            display: flex;
            flex-grow: 1;
            border-bottom: 1px solid #000;
            margin-left: 4px;
            height: 10px;
        }

        /* Botão */
        .btn-print {
            position: fixed; top: 20px; right: 20px;
            padding: 12px 24px; background: #2ecc71; color: white;
            border: none; border-radius: 4px; cursor: pointer;
            font-weight: bold; z-index: 1000;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print();">IMPRIMIR (10 CARDS)</button>

    <div class="page">
        <?php
        for ($i = 1; $i <= 10; $i++) {
            ?>
            <div class="card">
                <div class="header-container">
                    <div class="header">RETORNAR AO REMETENTE!</div>
                    <div class="sub-header">Portaria SAP-SC 1057 de agosto de 2022:</div>
                </div>

                <div class="item"><div class="circle"></div> Identificação de remetente incompleta.</div>
                <div class="item"><div class="circle"></div> Citação de telefone, endereço e/ou dados bancários.</div>
                <div class="item"><div class="circle"></div> Conteúdo censurado (impróprio)</div>
                <div class="item"><div class="circle"></div> Excedeu o limite mensal (2 cartas/mês ou 1 carta/5 dias)</div>
                <div class="item"><div class="circle"></div> Excesso de Envelopes (Máximo 2)</div>
                <div class="item"><div class="circle"></div> Excesso de selos (Máximo 2).</div>
                <div class="item"><div class="circle"></div> Excesso de páginas/folhas (Máximo 2).</div>
                <div class="item"><div class="circle"></div> Excesso de fotos (1 por carta, tamanho 10x15cm).</div>
                <div class="item"><div class="circle"></div> Foto censurada (imprópria).</div>
                <div class="item"><div class="circle"></div> Ausência de carimbo dos Correios.</div>
                <div class="item"><div class="circle"></div> Interno não está mais na unidade.</div>
                <div class="item"><span>Outros:</span> <div class="line-other"></div></div>
            </div>
            <?php
        }
        ?>
    </div>

</body>
</html>
