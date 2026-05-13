<?php
// Função para gerar HTML do ofício de estoque
function gerarHTMLoficio($mesAno, $dadosPresidio, $entradas, $saidas, $categorias, $itensAlerta, $itensCritico, $dataInicio, $dataFim) {
    $numeroOficio = rand(100, 999) . '/' . date('Y');
    $dataAtual = date('d/m/Y');
    
    // Agrupar categorias
    $categoriasAgrupadas = [];
    foreach ($categorias as $cat) {
        $tipo = $cat['tipo_nome'] ?: 'Sem Categoria';
        if (!isset($categoriasAgrupadas[$tipo])) {
            $categoriasAgrupadas[$tipo] = [
                'entradas' => 0,
                'saidas' => 0,
                'saldo' => 0,
                'itens' => []
            ];
        }
        $categoriasAgrupadas[$tipo]['entradas'] += $cat['entradas'];
        $categoriasAgrupadas[$tipo]['saidas'] += $cat['saidas'];
        $categoriasAgrupadas[$tipo]['saldo'] += $cat['saldo'];
        $categoriasAgrupadas[$tipo]['itens'][] = $cat;
    }
    
    $saldoTotal = $entradas['quantidade_total'] - $saidas['quantidade_total'];
    $totalAlerta = count($itensAlerta);
    $totalCritico = count($itensCritico);
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ofício - Relatório de Estoque</title>
    <style>
        body { font-family: "Times New Roman", serif; line-height: 1.6; margin: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; font-size: 14px; }
        .section { margin-bottom: 30px; }
        .section h2 { font-size: 16px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .section h3 { font-size: 14px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .assinatura { margin-top: 50px; }
        .alerta { background-color: #fff3cd; border: 1px solid #ffeaa7; }
        .critico { background-color: #f8d7da; border: 1px solid #f5c6cb; }
        .normal { background-color: #d4edda; border: 1px solid #c3e6cb; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>OFÍCIO Nº ' . $numeroOficio . '</h1>
        <p>' . $dataAtual . '</p>
        <p><strong>Exmo(a) Senhor(a) Secretário(a) de Administração Penitenciária,</p>
        <p>' . $dadosPresidio['estado'] . '</p>
    </div>

    <div class="section">
        <h2>Assunto: Relatório Mensal de Movimentação de Estoque - ' . $mesAno . '</h2>
        <p>Prezados(as) Senhores(as),</p>
        
        <h3>1. INTRODUÇÃO</h3>
        <p>O presente ofício tem como objetivo apresentar o relatório mensal das movimentações de estoque do ' . $dadosPresidio['nome'] . ', referente ao período de ' . date('d/m/Y', strtotime($dataInicio)) . ' a ' . date('d/m/Y', strtotime($dataFim)) . ', em conformidade com as normas administrativas e os procedimentos de controle patrimonial.</p>
        
        <h3>2. RESUMO DAS MOVIMENTAÇÕES</h3>
        
        <h4>2.1 Entradas</h4>
        <table>
            <tr><th>Total de Entradas</th><th>Quantidade Total</th></tr>
            <tr><td>' . $entradas['total'] . '</td><td>' . $entradas['quantidade_total'] . '</td></tr>
        </table>
        
        <h4>2.2 Saídas</h4>
        <table>
            <tr><th>Total de Saídas</th><th>Quantidade Total</th></tr>
            <tr><td>' . $saidas['total'] . '</td><td>' . $saidas['quantidade_total'] . '</td></tr>
        </table>
        
        <h4>2.3 Saldo Atual</h4>
        <table>
            <tr><th>Total de Itens em Estoque</th><th>Itens em Alerta</th><th>Itens em Nível Crítico</th></tr>
            <tr>
                <td class="bold">' . $saldoTotal . '</td>
                <td class="alerta bold">' . $totalAlerta . '</td>
                <td class="critico bold">' . $totalCritico . '</td>
            </tr>
        </table>
        
        <h3>3. DETALHAMENTO POR CATEGORIA</h3>';

    // Adicionar categorias
    foreach ($categoriasAgrupadas as $tipo => $dados) {
        $html .= '<h4>3.1 ' . htmlspecialchars($tipo) . '</h4>';
        $html .= '<table>';
        $html .= '<tr><th>Produto</th><th>Entradas</th><th>Saídas</th><th>Saldo</th></tr>';
        
        foreach ($dados['itens'] as $item) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($item['produto_nome']) . '</td>';
            $html .= '<td class="center">' . $item['entradas'] . '</td>';
            $html .= '<td class="center">' . $item['saidas'] . '</td>';
            $html .= '<td class="center bold">' . $item['saldo'] . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</table>';
    }
    
    $html .= '<h3>4. ANÁLISE DE ESTOQUE</h3>';
    
    $html .= '<h4>4.1 Itens em Alerta</h4>';
    $html .= '<p>Os seguintes itens apresentam estoque abaixo do nível de alerta e necessitam reposição imediata:</p>';
    $html .= '<table>';
    $html .= '<tr><th>Produto</th><th>Variante</th><th>Saldo Atual</th><th>Nível Alerta</th><th>Nível Mínimo</th></tr>';

    foreach ($itensAlerta as $item) {
        $html .= '<tr class="alerta">';
        $html .= '<td>' . htmlspecialchars($item['produto_nome']) . '</td>';
        $html .= '<td>' . $item['cor'] . ' / ' . $item['tamanho'] . '</td>';
        $html .= '<td class="center bold">' . $item['quantidade_atual'] . '</td>';
        $html .= '<td class="center">' . $item['quantidade_alerta'] . '</td>';
        $html .= '<td class="center">' . $item['quantidade_minima'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $html .= '<h4>4.2 Itens em Nível Crítico</h4>';
    $html .= '<p>Os seguintes itens apresentam estoque em nível crítico, podendo comprometer o atendimento:</p>';
    $html .= '<table>';
    $html .= '<tr><th>Produto</th><th>Variante</th><th>Saldo Atual</th><th>Nível Alerta</th><th>Nível Mínimo</th></tr>';
    
    foreach ($itensCritico as $item) {
        $html .= '<tr class="critico">';
        $html .= '<td>' . htmlspecialchars($item['produto_nome']) . '</td>';
        $html .= '<td>' . $item['cor'] . ' / ' . $item['tamanho'] . '</td>';
        $html .= '<td class="center bold">' . $item['quantidade_atual'] . '</td>';
        $html .= '<td class="center">' . $item['quantidade_alerta'] . '</td>';
        $html .= '<td class="center">' . $item['quantidade_minima'] . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    $statusGeral = 'ESTÁVEL';
    $acoesRecomendadas = 'manutenção regular dos controles';
    
    if (count($itensCritico) > 0) {
        $statusGeral = 'CRÍTICO';
        $acoesRecomendadas = 'reposição imediata dos itens em nível crítico';
    } elseif (count($itensAlerta) > 3) {
        $statusGeral = 'ATENÇÃO';
        $acoesRecomendadas = 'priorizar aquisição de itens em alerta';
    }
    
    $html .= '<h3>5. RECOMENDAÇÕES</h3>';
    
    $html .= '<h4>5.1 Reposição Imediata</h4>
        <ul>
            <li>Priorizar aquisição dos itens em nível crítico</li>
            <li>Solicitar autorização para compra emergencial</li>
        </ul>
        
        <h4>5.2 Controle de Qualidade</h4>
        <ul>
            <li>Implementar sistema de verificação mensal</li>
            <li>Estabelecer parâmetros mínimos de estoque</li>
        </ul>
        
        <h4>5.3 Otimização</h4>
        <ul>
            <li>Analisar padrões de consumo</li>
            <li>Ajustar níveis de reposição</li>
        </ul>
        
        <h3>6. CONCLUSÃO</h3>
        <p>O estoque atual apresenta <strong>' . $statusGeral . '</strong>, com necessidade de <strong>' . $acoesRecomendadas . '</strong>. As providências sugeridas visam garantir a continuidade do atendimento aos internos sem interrupções no fornecimento de itens essenciais.</p>
        
        <h3>7. ANEXOS</h3>
        <ul>
            <li>Anexo I: Relatório detalhado de movimentações</li>
            <li>Anexo II: Planilha de controle de estoque</li>
        </ul>
        
        <div class="assinatura">
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
