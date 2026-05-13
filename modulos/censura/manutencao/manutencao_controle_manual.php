<?php
// modulos/censura/manutencao/manutencao_controle_manual.php
date_default_timezone_set('America/Sao_Paulo');
?>
<!DOCTYPE html>
<html>
<head>
    <title>FICHA DE CONTROLE DE MANUTENÇÃO</title>
    <link rel="icon" type="image/svg+xml" href="../../../favicon.svg">
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5cm;
        }
        body {
            font-family: "Times New Roman", Times, serif;
            font-size: 10px;
            margin: 0;
            line-height: 1.2;
        }
        .page-break { page-break-after: always; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            text-align: left;
            height: 10px;
        }
        .no-border { border: none !important; }
        .header-table td { border: none; text-align: center; }
        .bold { font-weight: bold; }
        .bg-gray { background-color: #f0f0f0; }
        .center { text-align: center; }
        .titulo-principal {
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin: 5px 0;
            text-transform: uppercase;
        }
        .campo-texto {
            width: 100%;
            border: none;
            background: transparent;
            font-family: inherit;
            font-size: inherit;
        }
        .assinatura-linha {
            border-bottom: 1px solid #000;
            height: 30px;
            margin-bottom: 5px;
        }
        .data-campo {
            font-size: 10px;
            margin-top: 5px;
        }
        .data-linha {
            border-bottom: 1px solid #000;
            display: inline-block;
            width: 80px;
            margin-left: 5px;
        }
        .tabela-principal th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
        }
        .tabela-principal td {
            font-size: 10px;
        }
    </style>
</head>
<body onload="window.print()">

<div class="documento-container">
    <!-- CABEÇALHO OFICIAL -->
    <table class="header-table">
        <tr>
            <td width="20%"><img src="../../../assets/img/logo_estado.png" style="max-height:40px; max-width:60px;" onerror="this.style.display='none'"></td>
            <td width="60%">
                ESTADO DE SANTA CATARINA<br>
                SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                <strong>UNIDADE PRISIONAL</strong><br>
                <div class="titulo-principal">FICHA DE CONTROLE DE MANUTENÇÃO</div>
            </td>
            <td width="20%"><img src="../../../assets/img/logo_sap.png" style="max-height:40px; max-width:60px;" onerror="this.style.display='none'"></td>
        </tr>
    </table>
    <hr>

    <!-- DADOS DE CONTROLE -->
    <table style="border: 2px solid #000;">
        <tr class="bg-gray">
            <td colspan="4" class="bold center">DADOS DE CONTROLE</td>
        </tr>
        <tr>
            <td width="20%" class="bold">DATA:</td>
            <td width="30%"><input type="text" class="campo-texto"></td>
            <td width="20%" class="bold">TURNO:</td>
            <td width="30%">
                <input type="text" class="campo-texto" placeholder="Manhã / Tarde / Noite">
            </td>
        </tr>
        <tr>
            <td class="bold">EQUIPE:</td>
            <td colspan="3"><input type="text" class="campo-texto"></td>
        </tr>
    </table>

    <!-- TABELA PRINCIPAL DE ATIVIDADES -->
    <table style="border: 2px solid #000;" class="tabela-principal">
        <tr class="bg-gray">
            <th width="12%">ATIVIDADE</th>
            <th width="15%">LOCAL</th>
            <th width="13%">DATA/HORA INÍCIO</th>
            <th width="25%">DESCRIÇÃO</th>
            <th width="15%">MATERIAL UTILIZADO</th>
            <th width="10%">EXECUTOR</th>
            <th width="10%">DATA/HORA FIM</th>
        </tr>

        <!-- Linhas 1-20 -->
        <?php for($i = 1; $i <= 20; $i++): ?>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <?php endfor; ?>
    </table>

    <!-- RESUMO DO DIA -->
    <table style="border: 2px solid #000;">
        <tr class="bg-gray">
            <td colspan="4" class="bold center">RESUMO DO DIA</td>
        </tr>
        <tr>
            <td width="25%" class="bold">TOTAL DE ATIVIDADES:</td>
            <td width="25%"></td>
            <td width="25%" class="bold">CONCLUÍDAS:</td>
            <td width="25%"></td>
        </tr>
        <tr>
            <td class="bold">PENDENTES:</td>
            <td></td>
            <td class="bold">OBSERVAÇÕES:</td>
            <td></td>
        </tr>
    </table>

    <!-- ASSINATURAS -->
    <div style="margin-top: 10px;">
        <table>
            <tr>
                <td width="50%" class="center">
                    <div class="assinatura-linha"></div>
                    <div><strong>MONITOR DA MANUTENÇÃO</strong></div>
                    <div class="data-campo">Data: <span class="data-linha"></span></div>
                </td>
                <td width="50%" class="center">
                    <div class="assinatura-linha"></div>
                    <div><strong>SOLICITANTE DA MANUTENÇÃO</strong></div>
                    <div class="data-campo">Data: <span class="data-linha"></span></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- INSTRUÇÕES -->
    <div style="margin-top: 8px; border: 1px solid #000; padding: 5px; font-size: 9px;">
        <strong>INSTRUÇÕES:</strong> Preencher todas as atividades de manutenção realizadas durante o turno.
        Anotar horários de início e fim, localização, descrição detalhada dos serviços, materiais utilizados e responsável pela execução.
        Entregar a ficha preenchida ao final do turno ao Monitor da Manutenção.
    </div>

</div>

</body>
</html>
