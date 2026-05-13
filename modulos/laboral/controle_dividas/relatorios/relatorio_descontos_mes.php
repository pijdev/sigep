<?php
// Relatório de Descontos do Mês - KPI Detalhado
session_start();
require_once __DIR__ . '/../../../../conf/db.php';

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    die('Acesso negado. Sessão expirada.');
}

// Configurar timezone
date_default_timezone_set('America/Sao_Paulo');

// Conexão com banco
try {
    $config = require __DIR__ . '/../../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erro 500: Falha na conexão com o Banco de Dados.");
}

// Obter parâmetros
$mes = $_GET['mes'] ?? date('Y-m');
$mes_formatado = date('m/Y', strtotime($mes . '-01'));

// Query para buscar descontos do mês
$sql = "
    SELECT
        ld.mes_referencia,
        ld.salario_real,
        ld.valor_desconto,
        ld.data_lancamento,
        lm.ipen,
        lm.tipo_divida,
        lm.percentual_desconto,
        i.nome as interno_nome,
        i.nome_social as interno_nome_social,
        u.nome as usuario_lancamento
    FROM laboral_controle_dividas_descontos ld
    JOIN laboral_controle_dividas lm ON ld.multa_id = lm.id
    LEFT JOIN internos i ON lm.ipen = i.ipen
    LEFT JOIN users u ON ld.usuario_lancamento = u.id
    WHERE ld.mes_referencia LIKE ?
    ORDER BY ld.data_lancamento DESC, i.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$mes . '%']);
$descontos = $stmt->fetchAll();

// Calcular totais
$total_descontos = count($descontos);
$total_valor_descontado = array_sum(array_column($descontos, 'valor_desconto'));
$total_salarios = array_sum(array_column($descontos, 'salario_real'));
$media_desconto = $total_descontos > 0 ? $total_valor_descontado / $total_descontos : 0;

// Título do relatório
$titulo_relatorio = "RELATÓRIO DE DESCONTOS DO MÊS - $mes_formatado - " . date("d/m/Y H:i");
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title><?= $titulo_relatorio ?></title>
    <link rel="icon" type="image/svg+xml" href="../../../favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5cm;
        }

        body {
            font-size: 9pt;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: white;
            padding: 15px;
            color: black;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid black !important;
            padding: 4px 6px !important;
        }

        th {
            background: #e9ecef !important;
            font-weight: bold;
            text-align: center;
        }

        .header-img {
            max-height: 60px;
            margin-bottom: 10px;
        }

        .text-center {
            text-align: center !important;
        }

        .text-right {
            text-align: right !important;
        }

        .font-bold {
            font-weight: bold;
        }

        .valor-destaque {
            font-weight: bold;
            color: #155724;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                padding: 0;
                font-size: 8pt;
            }

            th,
            td {
                padding: 3px 4px !important;
            }
        }
    </style>
</head>

<body>
    <!-- Cabeçalho -->
    <div class="text-center mb-3">
        <h4 class="font-bold mb-1"><?= $titulo_relatorio ?></h4>
        <small class="text-muted">Sistema SIGEP - Módulo Laboral</small>
    </div>

    <!-- Resumo -->
    <div style="margin-bottom: 15px; padding: 10px; background: #d4edda; border-radius: 5px; border-left: 4px solid #155724;">
        <strong>Período:</strong> <?= $mes_formatado ?> |
        <strong>Total de Descontos:</strong> <?= $total_descontos ?> |
        <strong>Valor Total Descontado:</strong> R$ <?= number_format($total_valor_descontado ?? 0, 2, ',', '.') ?> |
        <strong>Média por Desconto:</strong> R$ <?= number_format($media_desconto ?? 0, 2, ',', '.') ?>
    </div>

    <!-- Botão Imprimir -->
    <div class="no-print mb-3 text-center">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Imprimir / Salvar PDF
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> Fechar
        </button>
    </div>

    <!-- Tabela -->
    <table>
        <thead>
            <tr>
                <th style="width: 60px;">IPEN</th>
                <th>Nome do Interno</th>
                <th style="width: 100px;">Tipo Dívida</th>
                <th style="width: 60px;">% Desc.</th>
                <th style="width: 100px;">Salário Informado</th>
                <th style="width: 100px;">Valor Descontado</th>
                <th style="width: 90px;">Data Lançamento</th>
                <th style="width: 120px;">Usuário</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($descontos as $d):
                $nome = $d['interno_nome_social'] ?: $d['interno_nome'];
            ?>
                <tr>
                    <td class="text-center font-bold"><?= $d['ipen'] ?></td>
                    <td><?= htmlspecialchars($nome ?: 'N/A') ?></td>
                    <td class="text-center"><?= $d['tipo_divida'] ?></td>
                    <td class="text-center"><?= $d['percentual_desconto'] ?>%</td>
                    <td class="text-right">R$ <?= number_format($d['salario_real'] ?? 0, 2, ',', '.') ?></td>
                    <td class="text-right valor-destaque">R$ <?= number_format($d['valor_desconto'] ?? 0, 2, ',', '.') ?></td>
                    <td class="text-center"><?= date('d/m/Y H:i', strtotime($d['data_lancamento'])) ?></td>
                    <td><?= htmlspecialchars($d['usuario_lancamento'] ?: 'N/A') ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($descontos)): ?>
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px;">
                        <em>Nenhum desconto encontrado para este período.</em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td colspan="5" class="text-right">TOTAIS:</td>
                <td class="text-right">R$ <?= number_format($total_salarios, 2, ',', '.') ?></td>
                <td class="text-right valor-destaque">R$ <?= number_format($total_valor_descontado, 2, ',', '.') ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Rodapé -->
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 0.8em;">
        <strong>Relatório gerado em:</strong> <?= date('d/m/Y H:i:s') ?> |
        <strong>Usuário:</strong> <?= htmlspecialchars($_SESSION['user_nome'] ?? 'Sistema') ?> |
        <strong>Período:</strong> <?= $mes_formatado ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>
