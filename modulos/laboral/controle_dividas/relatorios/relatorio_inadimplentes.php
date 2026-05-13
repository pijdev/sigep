<?php
// Relatório de Inadimplentes - KPI Detalhado
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

// Query para buscar inadimplentes do mês
$sql = "
    SELECT DISTINCT
        lm.ipen,
        i.nome as interno_nome,
        i.nome_social as interno_nome_social,
        i.status as interno_status,
        COUNT(lm.id) as qtd_dividas,
        SUM(lm.valor_atual) as total_devido,
        GROUP_CONCAT(DISTINCT lm.tipo_divida SEPARATOR ', ') as tipos_divida
    FROM laboral_controle_dividas lm
    LEFT JOIN internos i ON lm.ipen = i.ipen
    WHERE lm.status_detalhado = 'Pendente'
    AND lm.status = 'A'
    AND NOT EXISTS (
        SELECT 1 FROM laboral_controle_dividas_descontos ld
        WHERE ld.multa_id = lm.id
        AND ld.mes_referencia LIKE ?
    )
    GROUP BY lm.ipen, i.nome, i.nome_social, i.status
    ORDER BY i.nome
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$mes . '%']);
$inadimplentes = $stmt->fetchAll();

// Calcular totais
$total_inadimplentes = count($inadimplentes);
$total_geral_devido = array_sum(array_column($inadimplentes, 'total_devido'));
$total_dividas_geral = array_sum(array_column($inadimplentes, 'qtd_dividas'));
$media_dividas = $total_inadimplentes > 0 ? $total_dividas_geral / $total_inadimplentes : 0;
$media_devida = $total_inadimplentes > 0 ? $total_geral_devido / $total_inadimplentes : 0;

// Título do relatório
$titulo_relatorio = "RELATÓRIO DE INADIMPLENTES - $mes_formatado - " . date("d/m/Y H:i");
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

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .valor-destaque {
            font-weight: bold;
            color: #dc3545;
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
    <div style="margin-bottom: 15px; padding: 10px; background: #f8d7da; border-radius: 5px; border-left: 4px solid #dc3545;">
        <strong>Período:</strong> <?= $mes_formatado ?> |
        <strong>Total de Inadimplentes:</strong> <?= $total_inadimplentes ?> |
        <strong>Total de Dívidas:</strong> <?= $total_dividas_geral ?> |
        <strong>Valor Total Devido:</strong> R$ <?= number_format($total_geral_devido ?? 0, 2, ',', '.') ?> |
        <strong>Média Dívidas por Interno:</strong> <?= number_format($media_dividas ?? 0, 1) ?> |
        <strong>Média Devida por Interno:</strong> R$ <?= number_format($media_devida ?? 0, 2, ',', '.') ?>
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
                <th style="width: 80px;">Qtd. Dívidas</th>
                <th style="width: 120px;">Total Devido</th>
                <th style="width: 200px;">Tipos de Dívida</th>
                <th style="width: 80px;">Status Interno</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inadimplentes as $i):
                $nome = $i['interno_nome_social'] ?: $i['interno_nome'];
                $status_interno = $i['interno_status'] === 'A' ?
                    '<span class="badge badge-success">Ativo</span>' :
                    '<span class="badge badge-danger">Inativo</span>';
            ?>
                <tr>
                    <td class="text-center font-bold"><?= $i['ipen'] ?></td>
                    <td><?= htmlspecialchars($nome ?: 'N/A') ?></td>
                    <td class="text-center"><?= $i['qtd_dividas'] ?></td>
                    <td class="text-right valor-destaque">R$ <?= number_format($i['total_devido'] ?? 0, 2, ',', '.') ?></td>
                    <td><?= htmlspecialchars($i['tipos_divida'] ?: 'N/A') ?></td>
                    <td class="text-center"><?= $status_interno ?></td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($inadimplentes)): ?>
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">
                        <em>Nenhum inadimplente encontrado para este período.</em>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAIS:</td>
                <td class="text-center"><?= $total_dividas_geral ?></td>
                <td class="text-right valor-destaque">R$ <?= number_format($total_geral_devido ?? 0, 2, ',', '.') ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <!-- Rodapé -->
    <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 0.8em;">
        <strong>Relatório gerado em:</strong> <?= date('d/m/Y H:i:s') ?> |
        <strong>Usuário:</strong> <?= htmlspecialchars($_SESSION['user_nome'] ?? 'Sistema') ?> |
        <strong>Período:</strong> <?= $mes_formatado ?> |
        <strong>Total de Registros:</strong> <?= $total_inadimplentes ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</body>

</html>
