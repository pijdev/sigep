<?php
session_start();

if (!isset($_SESSION['relatorio_escolta'])) {
    die('Erro: Nenhum relatório encontrado. Gere o relatório novamente.');
}

$relatorio = $_SESSION['relatorio_escolta'];
$titulo = (string) ($relatorio['titulo'] ?? 'Relatório de Escoltas');
$filtros = is_array($relatorio['filtros'] ?? null) ? $relatorio['filtros'] : [];
$dados = is_array($relatorio['dados'] ?? null) ? $relatorio['dados'] : [];

unset($_SESSION['relatorio_escolta']);

$finalizadas = 0;
$pendentes = 0;
$placas = [];
$destinos = [];
$motoristas = [];
$notSim = 0;

foreach ($dados as $linha) {
    if (!empty($linha['status']) && $linha['status'] === 'Finalizado') {
        $finalizadas++;
    }
    if (!empty($linha['status']) && $linha['status'] === 'Pendente') {
        $pendentes++;
    }
    if (!empty($linha['placa'])) {
        $placas[] = $linha['placa'];
    }
    if (!empty($linha['destino'])) {
        $destinos[] = $linha['destino'];
    }
    if (!empty($linha['motorista'])) {
        $motoristas[] = $linha['motorista'];
    }
    if (!empty($linha['eh_not']) && $linha['eh_not'] === 'Sim') {
        $notSim++;
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
                <img src="/assets/img/logo_estado.svg" style="max-height:56px;" onerror="this.style.display='none'">
            </td>
            <td width="60%">
                ESTADO DE SANTA CATARINA<br>
                SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                <strong><?php echo htmlspecialchars($titulo); ?></strong><br>
                <small>SIGEP - GESTÃO DE ESCOLTAS</small>
            </td>
            <td width="20%">
                <img src="/assets/img/logo_sap.svg" style="max-height:56px;" onerror="this.style.display='none'">
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
            if (!empty($filtros['destino'])) {
                $descricoes[] = 'Destino: ' . $filtros['destino'];
            }
            if (!empty($filtros['interno'])) {
                $descricoes[] = 'Interno: ' . $filtros['interno'];
            }
            if (!empty($filtros['status'])) {
                $descricoes[] = 'Status: ' . $filtros['status'];
            }
            if (!empty($filtros['eh_not'])) {
                $descricoes[] = 'NOT: ' . $filtros['eh_not'];
            }
            echo htmlspecialchars(implode(' | ', $descricoes));
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($dados)): ?>
        <div class="stats">
            <div class="stat"><b><?php echo count($dados); ?></b><br>Total</div>
            <div class="stat"><b><?php echo $finalizadas; ?></b><br>Finalizadas</div>
            <div class="stat"><b><?php echo $pendentes; ?></b><br>Pendentes</div>
            <div class="stat"><b><?php echo count(array_unique($placas)); ?></b><br>Veículos</div>
            <div class="stat"><b><?php echo $notSim; ?></b><br>NOT</div>
        </div>

        <div class="box">
            <strong>Escoltas Registradas</strong>
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Interno</th>
                        <th>Destino</th>
                        <th>Motorista</th>
                        <th>Placa</th>
                        <th>Status</th>
                        <th>H. Prevista</th>
                        <th>H. Chegada</th>
                        <th>H. Retorno</th>
                        <th>NOT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dados as $escolta): ?>
                        <tr>
                            <td><?php echo !empty($escolta['data_cadastro']) ? date('d/m/Y', strtotime($escolta['data_cadastro'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($escolta['interno'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($escolta['destino'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($escolta['motorista'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($escolta['placa'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($escolta['status'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($escolta['hora_prevista']) ? substr($escolta['hora_prevista'], 0, 5) : '-'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($escolta['hora_chegada']) ? substr($escolta['hora_chegada'], 0, 5) : '-'); ?></td>
                            <td><?php echo htmlspecialchars(!empty($escolta['hora_retorno']) ? substr($escolta['hora_retorno'], 0, 5) : '-'); ?></td>
                            <td><?php echo htmlspecialchars($escolta['eh_not'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="box" style="text-align: center;">
            <strong>Nenhuma escolta encontrada para os filtros selecionados.</strong>
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
