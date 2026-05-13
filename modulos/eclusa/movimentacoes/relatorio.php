<?php
session_start();

if (!isset($_SESSION['relatorio_eclusa'])) {
    die('Erro: Nenhum relatório encontrado. Gere o relatório novamente.');
}

$relatorio = $_SESSION['relatorio_eclusa'];
$titulo = (string) ($relatorio['titulo'] ?? 'Relatório de Movimentações da Eclusa');
$filtros = is_array($relatorio['filtros'] ?? null) ? $relatorio['filtros'] : [];
$dados = is_array($relatorio['dados'] ?? null) ? $relatorio['dados'] : [];

unset($_SESSION['relatorio_eclusa']);

$entradas = 0;
$saidas = 0;
$placas = [];
$empresas = [];

foreach ($dados as $linha) {
    if (!empty($linha['hora_entrada'])) {
        $entradas++;
    }
    if (!empty($linha['hora_saida'])) {
        $saidas++;
    }
    if (!empty($linha['placa_veiculo'])) {
        $placas[] = $linha['placa_veiculo'];
    }
    if (!empty($linha['empresa'])) {
        $empresas[] = $linha['empresa'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($titulo); ?></title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page { size: A4; margin: 1.5cm; }
        body { font-family: "Times New Roman", serif; font-size: 12px; line-height: 1.4; }
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 14px; text-align: center; }
        .table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .table th, .table td { border: 1px solid #000; padding: 6px; }
        .table th { background: #f2f2f2; }
        .box { border: 1px solid #000; padding: 8px; margin-bottom: 10px; }
        .stats { display: flex; gap: 8px; margin: 14px 0; }
        .stat { flex: 1; border: 1px solid #000; text-align: center; padding: 8px; }
        .stat b { font-size: 16px; }
        .assinatura { margin-top: 28px; text-align: center; }
        .no-print { text-align: center; margin-top: 16px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">
    <table class="header-table">
        <tr>
            <td width="20%">
                <img src="../assets/img/logo_estado.png" style="max-height:56px;" onerror="this.style.display='none'">
            </td>
            <td width="60%">
                ESTADO DE SANTA CATARINA<br>
                SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                <strong><?php echo htmlspecialchars($titulo); ?></strong><br>
                <small>SIGEP - CONTROLE DE ECLUSA</small>
            </td>
            <td width="20%">
                <img src="../assets/img/logo_sap.png" style="max-height:56px;" onerror="this.style.display='none'">
            </td>
        </tr>
    </table>

    <?php if (!empty(array_filter($filtros))): ?>
        <div class="box">
            <strong>Filtros aplicados:</strong>
            <?php
            $descricoes = [];
            if (!empty($filtros['data_inicio'])) {
                $descricoes[] = 'Data início: ' . date('d/m/Y', strtotime($filtros['data_inicio']));
            }
            if (!empty($filtros['data_fim'])) {
                $descricoes[] = 'Data fim: ' . date('d/m/Y', strtotime($filtros['data_fim']));
            }
            if (!empty($filtros['placa'])) {
                $descricoes[] = 'Placa: ' . $filtros['placa'];
            }
            if (!empty($filtros['veiculo'])) {
                $descricoes[] = 'Veículo: ' . $filtros['veiculo'];
            }
            if (!empty($filtros['empresa'])) {
                $descricoes[] = 'Empresa: ' . $filtros['empresa'];
            }
            if (!empty($filtros['motorista'])) {
                $descricoes[] = 'Motorista: ' . $filtros['motorista'];
            }
            if (!empty($filtros['tipo_movimento'])) {
                $descricoes[] = 'Tipo: ' . $filtros['tipo_movimento'];
            }
            echo htmlspecialchars(implode(' | ', $descricoes));
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($dados)): ?>
        <div class="stats">
            <div class="stat"><b><?php echo count($dados); ?></b><br>Total</div>
            <div class="stat"><b><?php echo $entradas; ?></b><br>Entradas</div>
            <div class="stat"><b><?php echo $saidas; ?></b><br>Saídas</div>
            <div class="stat"><b><?php echo count(array_unique($placas)); ?></b><br>Veículos</div>
            <div class="stat"><b><?php echo count(array_unique($empresas)); ?></b><br>Empresas</div>
        </div>

        <div class="box">
            <strong>Movimentações Registradas</strong>
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Chegada</th>
                        <th>Entrada</th>
                        <th>Saída</th>
                        <th>Placa</th>
                        <th>Veículo</th>
                        <th>Empresa</th>
                        <th>Motorista</th>
                        <th>Cadastrado Por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $mov): ?>
                        <tr>
                            <td><?php echo !empty($mov['data_movimentacao']) ? date('d/m/Y', strtotime($mov['data_movimentacao'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars(!empty($mov['hora_chegada']) ? substr($mov['hora_chegada'], 0, 5) : '-'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($mov['hora_entrada']) ? substr($mov['hora_entrada'], 0, 5) : '-'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($mov['hora_saida']) ? substr($mov['hora_saida'], 0, 5) : '-'); ?></td>
                            <td><?php echo htmlspecialchars($mov['placa_veiculo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($mov['tipo_veiculo'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($mov['empresa'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($mov['motorista'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($mov['cadastrado_por'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="box" style="text-align: center;">
            <strong>Nenhuma movimentação encontrada para os filtros selecionados.</strong>
        </div>
    <?php endif; ?>

    <div class="assinatura">
        <hr>
        <strong>Data de emissão:</strong> <?php echo date('d/m/Y H:i'); ?><br>
        <small>SIGEP - Relatório gerado automaticamente</small>
    </div>

    <div class="no-print">
        <button onclick="window.print()">Imprimir</button>
        <button onclick="window.close()">Fechar</button>
    </div>
</body>
</html>
