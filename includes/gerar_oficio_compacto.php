<?php
// Função para gerar HTML do ofício de estoque - Versão Compacta Oficial
function gerarHTMLoficioCompacto($mesAno, $dadosPresidio, $entradas, $saidas, $categorias, $itensAlerta, $itensCritico, $dataInicio, $dataFim) {
    $numeroOficio = rand(100, 999) . '/' . date('Y');
    $dataAtual = date('d/m/Y');

    // Calcular totais
    $saldoTotal = $entradas['quantidade_total'] - $saidas['quantidade_total'];
    $totalAlerta = count($itensAlerta);
    $totalCritico = count($itensCritico);

    // Determinar status
    $status = 'ESTÁVEL';
    if ($totalCritico > 0) {
        $status = 'CRÍTICO';
    } elseif ($totalAlerta > 3) {
        $status = 'ATENÇÃO';
    }

    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ofício - Relatório de Estoque</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            line-height: 1.5;
            margin: 0;
            padding: 40px;
            background: white;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        .header p {
            margin: 3px 0;
            font-size: 12px;
        }
        .content {
            text-align: justify;
        }
        .content p {
            margin-bottom: 10px;
            text-indent: 2em;
            font-size: 12px;
        }
        .content p:first-child {
            text-indent: 0;
        }
        .assinatura {
            margin-top: 60px;
            text-align: right;
        }
        .assinatura p {
            margin: 3px 0;
            font-size: 12px;
        }
        .tabela-resumo {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 10px;
        }
        .tabela-resumo th,
        .tabela-resumo td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }
        .tabela-resumo th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .tabela-resumo .header-col {
            text-align: left;
            font-weight: bold;
            background-color: #f0f0f0;
        }
        .tabela-resumo .total {
            font-weight: bold;
            background-color: #f8f8f8;
        }
        .alerta {
            background-color: #fff3cd;
            font-weight: bold;
        }
        .critico {
            background-color: #f8d7da;
            font-weight: bold;
        }
        @media print {
            body {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>OFÍCIO Nº ' . $numeroOficio . '</h1>
        <p>' . $dataAtual . '</p>
        <p><strong>Exmo(a) Senhor(a) Secretário(a) de Administração Penitenciária,</strong></p>
        <p>' . $dadosPresidio['estado'] . '</p>
    </div>

    <div class="content">
        <p><strong>Assunto:</strong> Relatório Mensal de Movimentação de Estoque - ' . $mesAno . '</p>
        <p>Prezados(as) Senhores(as),</p>

        <p>Apresentamos o relatório mensal das movimentações de estoque do ' . $dadosPresidio['nome'] . ', referente ao período de ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ', em conformidade com as normas administrativas.</p>

        <table class="tabela-resumo">
            <tr>
                <th rowspan="2" class="header-col">PRODUTOS</th>
                <th colspan="2">MOVIMENTAÇÕES</th>
                <th colspan="2" class="alerta">ESTOQUE ATUAL</th>
                <th rowspan="2" class="header-col">STATUS</th>
            </tr>
            <tr>
                <th>ENTRADAS</th>
                <th>SAÍDAS</th>
                <th>QUANTIDADE</th>
                <th>NÍVEL</th>
            </tr>';

    // Adicionar categorias principais
    $categoriasPrincipais = [
        'Vestuário' => ['Camiseta', 'Calça', 'Bermuda', 'Blusa', 'Casaco'],
        'Calçados' => ['Tênis', 'Sapato', 'Chinelo', 'Bota'],
        'Acessórios' => ['Boné', 'Cinto', 'Bolsa', 'Meia']
    ];

    foreach ($categoriasPrincipais as $categoriaNome => $tipos) {
        $html .= '<tr>';
        $html .= '<td class="header-col">' . $categoriaNome . '</td>';

        // Calcular totais para esta categoria
        $entradasCat = 0;
        $saidasCat = 0;
        $saldoCat = 0;

        foreach ($categorias as $cat) {
            if (stripos($cat['produto_nome'], $categoriaNome) !== false ||
                array_filter($tipos, function($tipo) use ($cat) {
                    return stripos($cat['produto_nome'], $tipo) !== false;
                })) {
                $entradasCat += $cat['entradas'];
                $saidasCat += $cat['saidas'];
                $saldoCat += $cat['saldo'];
            }
        }

        $html .= '<td>' . $entradasCat . '</td>';
        $html .= '<td>' . $saidasCat . '</td>';
        $html .= '<td>' . $saldoCat . '</td>';

        // Verificar status desta categoria
        $alertasCat = 0;
        $criticosCat = 0;
        foreach ($itensAlerta as $item) {
            if (stripos($item['produto_nome'], $categoriaNome) !== false ||
                array_filter($tipos, function($tipo) use ($item) {
                    return stripos($item['produto_nome'], $tipo) !== false;
                })) {
                $alertasCat++;
            }
        }
        foreach ($itensCritico as $item) {
            if (stripos($item['produto_nome'], $categoriaNome) !== false ||
                array_filter($tipos, function($tipo) use ($item) {
                    return stripos($item['produto_nome'], $tipo) !== false;
                })) {
                $criticosCat++;
            }
        }

        if ($criticosCat > 0) {
            $html .= '<td class="critico">CRÍTICO</td>';
            $html .= '<td class="critico">CRÍTICO</td>';
        } elseif ($alertasCat > 0) {
            $html .= '<td class="alerta">ALERTA</td>';
            $html .= '<td class="alerta">ALERTA</td>';
        } else {
            $html .= '<td>ESTÁVEL</td>';
            $html .= '<td>ESTÁVEL</td>';
        }

        $html .= '</tr>';
    }

    // Linha de totais
    $html .= '<tr class="total">';
    $html .= '<td class="header-col">TOTAL GERAL</td>';
    $html .= '<td>' . $entradas['total'] . '</td>';
    $html .= '<td>' . $saidas['total'] . '</td>';
    $html .= '<td>' . $saldoTotal . '</td>';
    $html .= '<td class="' . ($totalCritico > 0 ? 'critico' : ($totalAlerta > 0 ? 'alerta' : '')) . '">' . $status . '</td>';
    $html .= '<td class="' . ($totalCritico > 0 ? 'critico' : ($totalAlerta > 0 ? 'alerta' : '')) . '">' . $status . '</td>';
    $html .= '</tr>';

    $html .= '</table>';

    $html .= '<p>Conforme demonstrado na tabela acima, o estoque atual apresenta <strong>' . $saldoTotal . '</strong> itens, com <strong>' . $totalAlerta . '</strong> itens em alerta e <strong>' . $totalCritico . '</strong> em nível crítico, resultando em um status <strong>' . $status . '</strong>.</p>';

    $html .= '<p>Recomendamos a imediata reposição dos itens em nível crítico para garantir a continuidade do atendimento aos internos, bem como a implementação de controles periódicos para evitar futuras interrupções no fornecimento.</p>';

    $html .= '<div class="assinatura">
            <p>Atenciosamente,</p>
            <br><br>
            <p><strong>' . $dadosPresidio['responsavel'] . '</strong></p>
            <p>' . $dadosPresidio['cargo'] . '</p>
            <p>' . $dadosPresidio['nome'] . '</p>
            <p>' . $dadosPresidio['contato'] . '</p>
        </div>
    </div>
</body>
</html>';

    return $html;
}
?>
