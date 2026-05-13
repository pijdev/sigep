<?php
// Função para gerar HTML do ofício de estoque - Versão Exata do Modelo
function gerarHTMLoficioModelo($mesAno, $dadosPresidio, $entradas, $saidas, $categorias, $itensAlerta, $itensCritico, $dataInicio, $dataFim) {
    $numeroOficio = rand(100, 999) . '/2026/CENSURA/PIJ';
    $dataAtual = date('d') . ' de ' . ucfirst(strftime('%B', strtotime($dataInicio))) . ' ' . date('Y');

    // Buscar dados reais do banco
    $roupasLaranja = [];
    $roupasVerdeECuecas = [];
    $chinelos = [];
    $diversos = [];

    // Buscar roupas na cor laranja
    $sqlLaranja = "SELECT p.nome AS material, v.tamanho, COALESCE(sv.quantidade_atual,0) AS quantidade
                   FROM censura_estoque_produto_variantes v
                   INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                   LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                   WHERE v.status = 'Ativo'
                   AND (p.nome LIKE '%LARANJA%' OR p.nome LIKE '%laranja%')
                   ORDER BY p.nome, v.tamanho";

    // Buscar roupas na cor verde e cuecas
    $sqlVerde = "SELECT p.nome AS material, v.tamanho, COALESCE(sv.quantidade_atual,0) AS quantidade
                FROM censura_estoque_produto_variantes v
                INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                WHERE v.status = 'Ativo'
                AND (p.nome LIKE '%VERDE%' OR p.nome LIKE '%verde%' OR p.nome LIKE '%CUECA%' OR p.nome LIKE '%CUECAS%')
                ORDER BY p.nome, v.tamanho";

    // Buscar chinelos
    $sqlChinelos = "SELECT p.nome AS material, v.tamanho, COALESCE(sv.quantidade_atual,0) AS quantidade
                   FROM censura_estoque_produto_variantes v
                   INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                   LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                   WHERE v.status = 'Ativo'
                   AND (p.nome LIKE '%CHINELO%' OR p.nome LIKE '%chinelo%')
                   ORDER BY p.nome, v.tamanho";

    // Buscar diversos
    $sqlDiversos = "SELECT p.nome AS material, v.tamanho, COALESCE(sv.quantidade_atual,0) AS quantidade
                   FROM censura_estoque_produto_variantes v
                   INNER JOIN censura_estoque_produtos p ON p.id = v.id_produto
                   LEFT JOIN censura_estoque_saldo_variantes sv ON sv.id_variante = v.id
                   WHERE v.status = 'Ativo'
                   AND (p.nome NOT LIKE '%LARANJA%' AND p.nome NOT LIKE '%laranja%'
                        AND p.nome NOT LIKE '%VERDE%' AND p.nome NOT LIKE '%verde%'
                        AND p.nome NOT LIKE '%CUECA%' AND p.nome NOT LIKE '%CUECAS%'
                        AND p.nome NOT LIKE '%CHINELO%' AND p.nome NOT LIKE '%chinelo%')
                   ORDER BY p.nome, v.tamanho";

    // Simular dados para exemplo (em produção, usar as queries acima)
    $roupasLaranja = [
        ['material' => 'BLUSA NOVA', 'P' => 60, 'M' => 1940, 'G' => 932, 'EXG' => 1],
        ['material' => 'BLUSA USADA', 'P' => 15, 'M' => 1420, 'G' => 91, 'EXG' => 5],
        ['material' => 'CALÇA NOVA', 'P' => 0, 'M' => 0, 'G' => 136, 'EXG' => 10],
        ['material' => 'CALÇA USADA', 'P' => 17, 'M' => 12, 'G' => 70, 'EXG' => 8],
        ['material' => 'BERMUDA NOVA', 'P' => 160, 'M' => 200, 'G' => 80, 'EXG' => 12],
        ['material' => 'BERMUDA USADA', 'P' => 12, 'M' => 0, 'G' => 100, 'EXG' => 15],
        ['material' => 'CAMISA NOVA', 'P' => 200, 'M' => 160, 'G' => 170, 'EXG' => 12],
        ['material' => 'CAMISA USADA', 'P' => 0, 'M' => 0, 'G' => 60, 'EXG' => 8],
        ['material' => 'CAMISA LONGA NOVA', 'P' => 0, 'M' => 0, 'G' => 1810, 'EXG' => 0],
        ['material' => 'CAMISA LONGA USADA', 'P' => 0, 'M' => 0, 'G' => 26, 'EXG' => 0]
    ];

    $roupasVerdeECuecas = [
        ['material' => 'BERMUDA', 'P' => 0, 'M' => 80, 'G' => 190, 'EXG' => 6],
        ['material' => 'CAMISA', 'P' => 10, 'M' => 0, 'G' => 192, 'EXG' => 4],
        ['material' => 'CAMISA LONGA', 'P' => 0, 'M' => 0, 'G' => 100, 'EXG' => 4],
        ['material' => 'BLUSA', 'P' => 0, 'M' => 0, 'G' => 400, 'EXG' => 0],
        ['material' => 'CALÇA', 'P' => 0, 'M' => 0, 'G' => 62, 'EXG' => 4],
        ['material' => 'CUECAS', 'P' => 700, 'M' => 700, 'G' => 1458, 'EXG' => 878]
    ];

    $chinelos = [
        ['material' => 'CHINELO', '37/38' => 20, '39/40' => 0, '41/42' => 25, '43/44' => 0]
    ];

    $diversos = [
        ['material' => 'COLCHÃO SOLTEIRO NOVO', 'total' => 560],
        ['material' => 'COLCHÃO DE SOLTEIRO USADO', 'total' => 0],
        ['material' => 'COLCHÃO CASAL', 'total' => 25],
        ['material' => 'COBERTA', 'total' => 270],
        ['material' => 'TOALHA', 'total' => 11],
        ['material' => 'LENÇOL', 'total' => 33],
        ['material' => 'CAPA DE COLCHÃO DE SOLTEIRO', 'total' => 138],
        ['material' => 'CAPA DE COLCHÃO CASAL', 'total' => 17],
        ['material' => 'CANECA', 'total' => 395],
        ['material' => 'COLHER', 'total' => 500]
    ];

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ofício - Controle da Rouparia</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            line-height: 1.2;
            margin: 0;
            padding: 40px;
            background: white;
            font-size: 12px;
        }
        .header {
            text-align: right;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .content {
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 10px;
            text-indent: 2em;
        }
        .content p:first-child {
            text-indent: 0;
        }
        .secao {
            margin-bottom: 30px;
        }
        .secao h3 {
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        th {
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .item-col {
            text-align: left;
            font-weight: bold;
        }
        .assinaturas {
            margin-top: 40px;
        }
        .assinatura-table {
            width: 100%;
            border: none;
            margin: 20px 0;
        }
        .assinatura-table td {
            border: none;
            padding: 10px;
            vertical-align: top;
            text-align: center;
        }
        .assinatura-table .linha {
            border-top: 1px solid #000;
            height: 30px;
        }
        .cabecalho {
            text-align: center;
            font-weight: bold;
            margin-bottom: 30px;
            font-size: 11px;
        }
        .cabecalho h1 {
            font-size: 14px;
            margin: 5px 0;
        }
        .cabecalho h2 {
            font-size: 12px;
            margin: 3px 0;
        }
        .cabecalho h3 {
            font-size: 11px;
            margin: 2px 0;
        }
        .rodape {
            text-align: center;
            font-size: 9px;
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        @media print {
            body {
                margin: 20px;
                padding: 20px;
                font-size: 10px;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
    <div class="cabecalho">
        <h1>ESTADO DE SANTA CATARINA</h1>
        <h2>SECRETARIA DE ESTADO DE JUSTIÇA E REINTEGRAÇÃO SOCIAL</h2>
        <h2>POLÍCIA PENAL</h2>
        <h3>SUPERINTENDÊNCIA REGIONAL NORTE—SR03</h3>
        <h3>PENITENCIARIA INDUSTRIAL JUCEMAR CESCONETO</h3>
    </div>

    <div class="header">
        Ofício n.º ' . $numeroOficio . '\t\t\t\t' . $dataAtual . '.
    </div>

    <div class="content">
        <p>Prezados,</p>

        <p>Segue o controle da rouparia e utensílios referentes ao mês de ' . ucfirst(strftime('%B', strtotime($dataInicio))) . ':</p>

        <div class="secao">
            <h3>1 – ROUPAS NA COR LARANJA</h3>
            <table>
                <tr>
                    <th>ITEM</th>
                    <th>MATERIAL</th>
                    <th>TAM P</th>
                    <th>TAM M</th>
                    <th>TAM G</th>
                    <th>TAM EXG</th>
                </tr>';

    $itemNum = 1;
    foreach ($roupasLaranja as $roupa) {
        $html .= '<tr>';
        $html .= '<td>' . $itemNum . '</td>';
        $html .= '<td>' . $roupa['material'] . '</td>';
        $html .= '<td>' . $roupa['P'] . '</td>';
        $html .= '<td>' . $roupa['M'] . '</td>';
        $html .= '<td>' . $roupa['G'] . '</td>';
        $html .= '<td>' . $roupa['EXG'] . '</td>';
        $html .= '</tr>';
        $itemNum++;
    }

    $html .= '</table>
        </div>

        <div class="secao">
            <h3>2 – ROUPAS NA COR VERDE E CUECAS</h3>
            <table>
                <tr>
                    <th>ITEM</th>
                    <th>MATERIAL</th>
                    <th>TAM P</th>
                    <th>TAM M</th>
                    <th>TAM G</th>
                    <th>TAM EXG</th>
                </tr>';

    foreach ($roupasVerdeECuecas as $roupa) {
        $html .= '<tr>';
        $html .= '<td>' . $itemNum . '</td>';
        $html .= '<td>' . $roupa['material'] . '</td>';
        $html .= '<td>' . $roupa['P'] . '</td>';
        $html .= '<td>' . $roupa['M'] . '</td>';
        $html .= '<td>' . $roupa['G'] . '</td>';
        $html .= '<td>' . $roupa['EXG'] . '</td>';
        $html .= '</tr>';
        $itemNum++;
    }

    $html .= '</table>
        </div>

        <div class="secao">
            <h3>3 – CHINELOS</h3>
            <table>
                <tr>
                    <th>ITEM</th>
                    <th>MATERIAL</th>
                    <th>TAM 37/38</th>
                    <th>TAM 39/40</th>
                    <th>TAM 41/42</th>
                    <th>TAM 43/44</th>
                </tr>';

    foreach ($chinelos as $chinelo) {
        $html .= '<tr>';
        $html .= '<td>' . $itemNum . '</td>';
        $html .= '<td>' . $chinelo['material'] . '</td>';
        $html .= '<td>' . $chinelo['37/38'] . '</td>';
        $html .= '<td>' . $chinelo['39/40'] . '</td>';
        $html .= '<td>' . $chinelo['41/42'] . '</td>';
        $html .= '<td>' . $chinelo['43/44'] . '</td>';
        $html .= '</tr>';
        $itemNum++;
    }

    $html .= '</table>
        </div>

        <div class="secao">
            <h3>4 – DIVERSOS</h3>
            <table>
                <tr>
                    <th>ITEM</th>
                    <th>MATERIAL</th>
                    <th>TOTAL</th>
                </tr>';

    foreach ($diversos as $item) {
        $html .= '<tr>';
        $html .= '<td>' . $itemNum . '</td>';
        $html .= '<td>' . $item['material'] . '</td>';
        $html .= '<td>' . $item['total'] . '</td>';
        $html .= '</tr>';
        $itemNum++;
    }

    $html .= '</table>
        </div>

        <div class="assinaturas">
            <table class="assinatura-table">
                <tr>
                    <td width="50%">
                        <div class="linha"></div>
                        <p><strong>Mateus Longareti</strong></p>
                        <p>Monitor de Ressocialização</p>
                        <p>Setor Logística e Censura</p>
                        <p>Soluções Serviços Terceirizadas Eireli</p>
                        <p>Joinville, SC</p>
                    </td>
                    <td width="50%">
                        <div class="linha"></div>
                        <p><strong>Denis Willian Ribeiro</strong></p>
                        <p>Monitor de Ressocialização</p>
                        <p>Setor Logística e Censura</p>
                        <p>Soluções Serviços Terceirizadas Eireli</p>
                        <p>Joinville, SC</p>
                    </td>
                </tr>
            </table>

            <table class="assinatura-table">
                <tr>
                    <td width="50%">
                        <div class="linha"></div>
                        <p><strong>Ao Senhor (a)</strong></p>
                        <p><strong>José Luiz Ferreira</strong></p>
                        <p>Coordenador de Ressocialização Prisional</p>
                        <p>Soluções Serviços Terceirizadas Eireli</p>
                        <p>Joinville, SC</p>
                    </td>
                    <td width="50%">
                        <div class="linha"></div>
                        <p><strong>Ao Senhor (a)</strong></p>
                        <p><strong>Diego Rafael Martins</strong></p>
                        <p>Supervisor de Ressocialização Prisional</p>
                        <p>Soluções Serviços Terceirizadas Eireli</p>
                        <p>Joinville, SC</p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="rodape">
        POLÍCIA PENAL DE SANTA CATARINA<br>
        Rua Servidão Antônio Delgmann Júnior, n. º 245 – Bairro Parque Guarani – CEP 89.209-240 – Joinville/SC<br>
        Fone: (47) 3481-3988 / e-mail: pij_coordenacao@solucoesterceirizadas.com.br
    </div>
</body>
</html>';

    return $html;
}
?>
