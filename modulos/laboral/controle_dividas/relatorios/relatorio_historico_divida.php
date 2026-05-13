<?php
// Relatório de Histórico de Dívida - SIGEP

// Limpar cache do PHP para evitar warnings persistentes
if (function_exists('opcache_reset')) {
    opcache_reset();
}

require_once __DIR__ . '/../../../../conf/db.php';
date_default_timezone_set('America/Sao_Paulo');

// Função para formatar CPF
function formatarCPF($cpf)
{
    if (!$cpf) return '';
    $cpf = preg_replace('/\D/', '', $cpf);
    if (strlen($cpf) !== 11) return $cpf;
    return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
}

if (empty($_GET['multa_id'])) die("Erro: ID da dívida não informado.");

try {
    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass']);

    // Buscar dados da multa
    $stmt = $pdo->prepare("
        SELECT lm.*, i.nome, i.nome_social, i.galeria, i.bloco, i.res, i.cpf
        FROM laboral_controle_dividas lm
        JOIN internos i ON lm.ipen = i.ipen
        WHERE lm.id = ?
    ");
    $stmt->execute([$_GET['multa_id']]);
    $multa = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$multa) die("Erro: Dívida não encontrada.");

    // Buscar histórico de movimentos
    $stmt = $pdo->prepare("
        SELECT lmh.*, u.nome as usuario_nome
        FROM laboral_controle_dividas_historico lmh
        LEFT JOIN users u ON lmh.usuario_movimento = u.id
        WHERE lmh.multa_id = ?
        ORDER BY lmh.data_movimento DESC
    ");
    $stmt->execute([$_GET['multa_id']]);
    $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar descontos mensais
    $stmt = $pdo->prepare("
        SELECT lmd.*, u.nome as usuario_nome
        FROM laboral_controle_dividas_descontos lmd
        LEFT JOIN users u ON lmd.usuario_lancamento = u.id
        WHERE lmd.multa_id = ?
        ORDER BY lmd.data_lancamento DESC
    ");
    $stmt->execute([$_GET['multa_id']]);
    $descontos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totais
    $totalSalarios = array_sum(array_column($descontos, 'salario_real'));
    $totalDescontos = array_sum(array_column($descontos, 'valor_desconto'));

    // Calcular estatísticas de parcelas para pensão
    $estatisticasParcelas = [];
    if ($multa['tipo_divida'] === 'Pensão') {
        // Data de início para cálculo do período
        $dataInicio = new DateTime($multa['data_cadastro']);
        $dataAtual = new DateTime();

        // Calcular o período esperado (meses desde o cadastro até hoje)
        $periodoEsperado = $dataInicio->diff($dataAtual);
        $totalMesesEsperados = $periodoEsperado->y * 12 + $periodoEsperado->m;

        // Identificar meses com e sem lançamento
        $mesesComLancamento = array_column($descontos, 'mes_referencia');

        // Gerar lista de todos os meses esperados
        $mesesEsperados = [];
        $dataTemp = clone $dataInicio;
        while ($dataTemp <= $dataAtual) {
            $mesesEsperados[] = $dataTemp->format('Y-m');
            $dataTemp->modify('+1 month');
        }

        $mesesPulados = array_diff($mesesEsperados, $mesesComLancamento);

        $estatisticasParcelas = [
            'total_lancamentos' => count($descontos),
            'total_descontado' => $totalDescontos,
            'parcelas_pagas' => count($descontos),
            'parcelas_nao_pagas' => count($mesesPulados),
            'meses_pulados' => $mesesPulados,
            'total_meses_esperados' => $totalMesesEsperados,
            'percentual_adimplencia' => $totalMesesEsperados > 0 ? round((count($descontos) / $totalMesesEsperados) * 100, 2) : 0,
            'valor_medio_parcela' => count($descontos) > 0 ? round($totalDescontos / count($descontos), 2) : 0
        ];
    }
} catch (Exception $e) {
    die("Erro no banco de dados: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Relatório de Histórico de Dívida</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        @page {
            size: A4;
            margin: 1.5cm;
        }

        body {
            font-family: "Times New Roman", serif;
            font-size: 12px;
            line-height: 1.4;
        }

        .page-break {
            page-break-after: always;
        }

        .header-table {
            width: 100%;
            text-align: center;
            border-bottom: 2px solid #000;
            margin-bottom: 20px;
        }

        .box {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 10px;
        }

        .bold {
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .table-bordered {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .table-bordered th,
        .table-bordered td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
        }

        .table-bordered th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .table-bordered .text-right {
            text-align: right;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            padding: 5px 0;
            border-bottom: 1px solid #000;
        }

        .assinatura {
            margin-top: 50px;
            text-align: center;
        }

        .no-print {
            display: none;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div class="no-print" style="position: fixed; top: 10px; right: 10px; z-index: 9999;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
            <i class="fas fa-print"></i> Imprimir
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 5px;">
            <i class="fas fa-times"></i> Fechar
        </button>
    </div>

    <table class="header-table">
        <tr>
            <td width="20%"><img src="../assets/img/logo_estado.png" style="max-height:60px;" onerror="this.style.display='none'"></td>
            <td width="60%">
                ESTADO DE SANTA CATARINA<br>
                SECRETARIA DE ADMINISTRAÇÃO PRISIONAL<br>
                <strong>RELATÓRIO DE HISTÓRICO DE DÍVIDA</strong><br>
                <?= date('d/m/Y H:i:s') ?>
            </td>
            <td width="20%"><img src="../assets/img/logo_sap.png" style="max-height:60px;" onerror="this.style.display='none'"></td>
        </tr>
    </table>

    <!-- DADOS DO INTERNO -->
    <div class="box" style="background: #eee;">
        <span class="bold">DADOS DO INTERNO</span><br>
        IPEN: <?= $multa['ipen'] ?><br>
        NOME: <?= $multa['nome_social'] ?: $multa['nome'] ?><br>
        CPF: <?= formatarCPF($multa['cpf']) ?><br>
        GALERIA: <?= $multa['galeria'] ?> | BLOCO: <?= $multa['bloco'] ?> | RES: <?= $multa['res'] ?>
    </div>

    <!-- DADOS DA DÍVIDA -->
    <div class="box">
        <span class="bold">DADOS DA DÍVIDA</span><br>
        <table class="table-bordered">
            <tr>
                <td width="30%"><strong>Autos:</strong></td>
                <td><?= $multa['autos'] ?></td>
            </tr>
            <tr>
                <td><strong>Tipo de Dívida:</strong></td>
                <td><?= $multa['tipo_divida'] ?></td>
            </tr>
            <tr>
                <td><strong>Valor Original:</strong></td>
                <td class="text-right">R$ <?= number_format(!empty($multa['valor_divida']) && $multa['valor_divida'] !== null ? $multa['valor_divida'] : 0, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td><strong>Valor Atual:</strong></td>
                <td class="text-right">R$ <?= number_format(isset($multa['valor_atual']) ? $multa['valor_atual'] : 0, 2, ',', '.') ?></td>
            </tr>
            <tr>
                <td><strong>Percentual de Desconto:</strong></td>
                <td><?= $multa['percentual_desconto'] ?>%</td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td><?= $multa['status_detalhado'] ?></td>
            </tr>
        </table>
    </div>

    <!-- DADOS DA PENSÃO ALIMENTÍCIA (apenas se for pensão) -->
    <?php if ($multa['tipo_divida'] === 'Pensão' && !empty($multa['pensao_favorecido'])): ?>
        <div class="box" style="background: #f9f9f9;">
            <span class="bold">DADOS DA PENSÃO ALIMENTÍCIA</span><br>
            <table class="table-bordered">
                <tr>
                    <td width="30%"><strong>Beneficiário:</strong></td>
                    <td><?= $multa['pensao_favorecido'] ?></td>
                </tr>
                <tr>
                    <td><strong>Banco:</strong></td>
                    <td><?= $multa['pensao_banco'] ?></td>
                </tr>
                <tr>
                    <td><strong>Agência:</strong></td>
                    <td><?= $multa['pensao_agencia'] ?></td>
                </tr>
                <tr>
                    <td><strong>Conta:</strong></td>
                    <td><?= $multa['pensao_conta'] ?></td>
                </tr>
                <?php if (!empty($multa['pensao_op'])): ?>
                    <tr>
                        <td><strong>Operação:</strong></td>
                        <td><?= $multa['pensao_op'] ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Tipo de Conta:</strong></td>
                    <td><?= $multa['pensao_tipo_conta'] ?></td>
                </tr>
                <?php if (!empty($multa['pensao_determinacao'])): ?>
                    <tr>
                        <td><strong>Determinação Judicial/Acordo:</strong></td>
                        <td><?= nl2br(htmlspecialchars($multa['pensao_determinacao'])) ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    <?php endif; ?>

    <!-- HISTÓRICO DE MOVIMENTOS -->
    <div class="section-title">HISTÓRICO DE MOVIMENTOS</div>
    <table class="table-bordered">
        <thead>
            <tr>
                <th>Data</th>
                <th>Valor</th>
                <th>Saldo Anterior</th>
                <th>Saldo Novo</th>
                <th>Descrição</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($historico)): ?>
                <tr>
                    <td colspan="5" class="text-center">Nenhum movimento encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($historico as $item): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($item['data_movimento'])) ?></td>
                        <td class="text-right">R$ <?= number_format(isset($item['valor_movimento']) ? $item['valor_movimento'] : 0, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format(isset($item['saldo_anterior']) ? $item['saldo_anterior'] : 0, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format(isset($item['saldo_novo']) ? $item['saldo_novo'] : 0, 2, ',', '.') ?></td>
                        <td><?= $item['descricao'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- HISTÓRICO DE DESCONTOS -->
    <div class="section-title">HISTÓRICO DE DESCONTOS</div>
    <table class="table-bordered">
        <thead>
            <tr>
                <th>Data Lançamento</th>
                <th>Mês Referência</th>
                <th>Salário Real</th>
                <th>Percentual</th>
                <th class="text-right">Valor Desconto</th>
                <th>Saldo Anterior</th>
                <th>Saldo Novo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($descontos)): ?>
                <tr>
                    <td colspan="8" class="text-center">Nenhum lançamento de desconto encontrado.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($descontos as $desconto): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($desconto['data_lancamento'])) ?></td>
                        <td><?= $desconto['mes_referencia'] ?></td>
                        <td class="text-right">R$ <?= number_format(isset($desconto['salario_real']) ? $desconto['salario_real'] : 0, 2, ',', '.') ?></td>
                        <td class="text-right"><?= $desconto['percentual_desconto'] ?>%</td>
                        <td class="text-right">R$ <?= number_format(isset($desconto['valor_desconto']) ? $desconto['valor_desconto'] : 0, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format(isset($desconto['saldo_anterior']) ? $desconto['saldo_anterior'] : 0, 2, ',', '.') ?></td>
                        <td class="text-right">R$ <?= number_format(isset($desconto['saldo_novo']) ? $desconto['saldo_novo'] : 0, 2, ',', '.') ?></td>
                        <td><?= $desconto['status'] ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr style="font-weight: bold; background-color: #f0f0f0;">
                    <td colspan="4">TOTAL DESCONTADO</td>
                    <td class="text-right">R$ <?= number_format(isset($totalDescontos) ? $totalDescontos : 0, 2, ',', '.') ?></td>
                    <td colspan="3">-</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ESTATÍSTICAS DE PARCELAS (APENAS PARA PENSÃO) -->
    <?php if ($multa['tipo_divida'] === 'Pensão' && !empty($estatisticasParcelas)): ?>
        <div class="section-title">ESTATÍSTICAS DE PARCELAS</div>
        <table class="table-bordered">
            <tr>
                <td width="40%"><strong>Total de Parcelas Esperadas:</strong></td>
                <td class="text-right"><?= $estatisticasParcelas['total_meses_esperados'] ?></td>
            </tr>
            <tr>
                <td><strong>Parcelas Pagas:</strong></td>
                <td class="text-right" style="font-weight: bold; color: #28a745;">
                    <?= $estatisticasParcelas['parcelas_pagas'] ?>
                </td>
            </tr>
            <tr>
                <td><strong>Parcelas Não Pagas:</strong></td>
                <td class="text-right" style="font-weight: bold; color: #dc3545;">
                    <?= $estatisticasParcelas['parcelas_nao_pagas'] ?>
                </td>
            </tr>
            <tr>
                <td><strong>Percentual de Adimplência:</strong></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="flex: 1; background: #e9ecef; border-radius: 4px; height: 20px; position: relative;">
                            <div style="background: #28a745; height: 100%; border-radius: 4px; width: <?= $estatisticasParcelas['percentual_adimplencia'] ?>%; transition: width 0.3s ease;"></div>
                        </div>
                        <span class="font-weight-bold"><?= $estatisticasParcelas['percentual_adimplencia'] ?>%</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td><strong>Valor Total Pago:</strong></td>
                <td class="text-right" style="font-weight: bold; color: #007bff;">
                    R$ <?= number_format($estatisticasParcelas['valor_total_pago'], 2, ',', '.') ?>
                </td>
            </tr>
            <tr>
                <td><strong>Valor Médio por Parcela:</strong></td>
                <td class="text-right">R$ <?= number_format($estatisticasParcelas['valor_medio_parcela'], 2, ',', '.') ?></td>
            </tr>
        </table>

        <?php if (!empty($estatisticasParcelas['meses_pulados'])): ?>
            <div class="section-title" style="color: #dc3545;">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                MESES COM PAGAMENTOS PULADOS
            </div>
            <div class="box" style="background: #fff3cd; border-color: #ffeaa7;">
                <table class="table-bordered">
                    <thead>
                        <tr>
                            <th>Mês</th>
                            <th>Status</th>
                            <th>Observações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estatisticasParcelas['meses_pulados'] as $mes): ?>
                            <tr>
                                <td><?= date('m/Y', strtotime($mes . '-01')) ?></td>
                                <td><span class="badge badge-danger">Sem Pagamento</span></td>
                                <td>Mês sem lançamento de desconto registrado</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- RESUMO FINANCEIRO -->
    <div class="section-title">RESUMO FINANCEIRO</div>
    <table class="table-bordered">
        <tr>
            <td width="40%"><strong>Valor Original da Dívida:</strong></td>
            <td class="text-right">R$ <?= number_format($multa['valor_divida'] ?? 0, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Total Descontado:</strong></td>
            <td class="text-right">R$ <?= number_format($totalDescontos ?? 0, 2, ',', '.') ?></td>
        </tr>
        <tr>
            <td><strong>Saldo Remanescente:</strong></td>
            <td class="text-right" style="font-weight: bold; color: <?= $multa['valor_atual'] > 0 ? 'red' : 'green' ?>;">
                R$ <?= number_format(isset($multa['valor_atual']) ? $multa['valor_atual'] : 0, 2, ',', '.') ?>
            </td>
        </tr>
        <tr>
            <td><strong>Percentual Pago:</strong></td>
            <td class="text-right"><?= number_format(($totalDescontos / ($multa['valor_divida'] ?? 1)) * 100, 2, ',', '.') ?>%</td>
        </tr>
    </table>

    <!-- ASSINATURAS -->
    <div class="assinatura">
        <div style="margin-bottom: 30px;">
            _________________________________________<br>
            <small>Assatura do Responsável</small>
        </div>
        <div>
            _________________________________________<br>
            <small>Data da Emissão: <?= date('d/m/Y H:i:s') ?></small>
        </div>
    </div>

    <script>
        function formatarCPF(cpf) {
            if (!cpf) return '';
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11) return cpf;
            return cpf.substr(0, 3) + '.' + cpf.substr(3, 3) + '.' + cpf.substr(6, 3) + '-' + cpf.substr(9, 2);
        }
    </script>
</body>

</html>
