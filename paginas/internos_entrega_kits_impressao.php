<?php
// paginas/internos_entrega_kits_impressao.php
$config = require __DIR__ . '/../conf/db.php'; 
date_default_timezone_set('America/Sao_Paulo');

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { die("Erro DB"); }

// Verificar parâmetros
if(!isset($_GET['id']) || !isset($_GET['data']) || !isset($_GET['tipo'])) {
    die("Parâmetros inválidos.");
}

$id_termo = $_GET['id'];
$data_assinatura = $_GET['data'];
$tipo_nome = urldecode($_GET['tipo']);

// Tentar buscar dados do termo no log (se tabela existir)
$internos = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM internos_termos_log WHERE id_termo = ?");
    $stmt->execute([$id_termo]);
    $termo = $stmt->fetch();
    
    if($termo) {
        $internos = json_decode($termo['internos_json'], true);
    }
} catch(Exception $e) {
    // Se tabela não existir, tentar obter dados via POST (fallback)
    if(isset($_POST['internos_json'])) {
        $internos = json_decode($_POST['internos_json'], true);
    }
}

// Se ainda não tiver dados, tentar obter via POST direto
if(empty($internos) && isset($_POST['internos'])) {
    $internos = $_POST['internos'];
    if(is_string($internos)) {
        $internos = json_decode($internos, true);
    }
}

// Verificar se temos dados dos internos
if(empty($internos)) {
    die("Dados dos internos não encontrados.");
}

// Formatar data
$data_formatada = date('d/m/Y', strtotime($data_assinatura));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Termo de Entrega - <?= htmlspecialchars($tipo_nome) ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.3cm;
        }
        
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 9pt;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            padding: 6px;
            border: 2px solid #000;
            background-color: #f0f0f0;
        }
        
        .subtitle {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .info {
            font-size: 9pt;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: left;
            vertical-align: middle;
        }
        
        th {
            background-color: #e0e0e0;
            font-weight: bold;
            text-align: center;
            font-size: 9pt;
        }
        
        .col-ipen {
            width: 7%;
            text-align: center;
            font-weight: bold;
        }
        
        .col-nome {
            width: 45%;
        }
        
        .col-local {
            width: 11%;
            text-align: center;
            font-weight: bold;
        }
        
        .col-assinatura {
            width: 37%;
            height: 20px;
        }
        
        .assinatura-line {
            border-bottom: 1px solid #000;
            height: 16px;
            margin-top: 1px;
        }
        
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8pt;
            color: #666;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        /* Para impressão */
        @media print {
            body { margin: 0.2cm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <?= htmlspecialchars($tipo_nome) ?>
        </div>
        <div class="subtitle">
            TERMO DE RECEBIMENTO E RESPONSABILIDADE
        </div>
        <div class="info">
            Data: <?= $data_formatada ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="col-ipen">IPEN</th>
                <th class="col-nome">NOME COMPLETO</th>
                <th class="col-local">LOCAL</th>
                <th class="col-assinatura">ASSINATURA DO INTERNO</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($internos as $index => $interno): ?>
            <tr>
                <td class="col-ipen"><?= htmlspecialchars($interno['ipen']) ?></td>
                <td class="col-nome"><?= htmlspecialchars($interno['nome']) ?></td>
                <td class="col-local"><?= htmlspecialchars($interno['local']) ?></td>
                <td class="col-assinatura">
                    <div class="assinatura-line"></div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <p><strong>DECLARAÇÃO DE RECEBIMENTO:</strong> Eu, interno abaixo assinado, declaro ter recebido os itens descritos neste termo, comprometendo-me a zelar pela sua conservação e responsabilidade.</p>
        <p><em>Este documento tem validade legal como comprovante de entrega.</em></p>
        <br>
        <p>Gerado em <?= date('d/m/Y H:i:s') ?> | ID: <?= htmlspecialchars($id_termo) ?></p>
    </div>
    
    <script>
        // Auto-imprimir ao carregar
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Fechar janela após impressão
        window.onafterprint = function() {
            window.close();
        };
    </script>
</body>
</html>
