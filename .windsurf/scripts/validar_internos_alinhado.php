<?php
/**
 * Validador de Internos - Versão Filtros Alinhados
 * CSS corrigido com área de filtros perfeitamente alinhada
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoLista = 'C:\Servicos\ConsultaUnidades\lista_internos_saldo_na_casa.txt';
$arquivoRelatorio = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_COMPLETO.csv';
$arquivoSaida = 'C:\Servicos\ConsultaUnidades\validacao_internos.html';

echo "Iniciando validação com filtros alinhados...\n";

if (!file_exists($arquivoLista)) {
    die("Arquivo da lista não encontrado: $arquivoLista\n");
}

if (!file_exists($arquivoRelatorio)) {
    die("Arquivo do relatório não encontrado: $arquivoRelatorio\n");
}

// 1. Carregar lista de internos
echo "Carregando lista de internos...\n";
$listaInternos = [];
$handleLista = fopen($arquivoLista, 'r');

// Pular cabeçalho
fgets($handleLista);

while (($linha = fgets($handleLista)) !== false) {
    $linha = trim($linha);
    if (empty($linha)) continue;
    
    // Separar por tabulação
    $partes = explode("\t", $linha);
    if (count($partes) >= 3) {
        $prontuario = trim($partes[0]);
        $nome = trim($partes[1]);
        $saldo = trim($partes[2]);
        
        if (!empty($prontuario) && is_numeric($prontuario)) {
            $listaInternos[$prontuario] = [
                'nome' => $nome,
                'saldo' => $saldo,
                'encontrado' => false,
                'dados_relatorio' => null
            ];
        }
    }
}

fclose($handleLista);

echo "Carregados " . count($listaInternos) . " internos da lista.\n";

// 2. Carregar relatório completo em memória
echo "Carregando relatório completo...\n";
$relatorioIndex = [];
$handleRelatorio = fopen($arquivoRelatorio, 'r');

// Pular cabeçalho
fgets($handleRelatorio);

while (($linha = fgets($handleRelatorio)) !== false) {
    $dados = str_getcsv($linha, ';', '"', '\\');
    
    if (count($dados) >= 5) {
        $id_unidade = $dados[0];
        $unidade = $dados[1];
        $prontuario = $dados[2];
        $nome = $dados[3];
        $situacao = $dados[4];
        
        if (!empty($prontuario) && is_numeric($prontuario)) {
            // Extrair número da unidade
            $numero_unidade = preg_replace('/[^0-9]/', '', $id_unidade);
            
            $relatorioIndex[$prontuario] = [
                'id_unidade' => $id_unidade,
                'numero_unidade' => $numero_unidade,
                'unidade' => $unidade,
                'nome' => $nome,
                'situacao' => $situacao
            ];
        }
    }
}

fclose($handleRelatorio);

echo "Carregados " . count($relatorioIndex) . " registros do relatório.\n";

// 3. Fazer a validação
echo "Validando internos...\n";
$encontrados = 0;
$nao_encontrados = 0;

foreach ($listaInternos as $prontuario => &$interno) {
    if (isset($relatorioIndex[$prontuario])) {
        $interno['encontrado'] = true;
        $interno['dados_relatorio'] = $relatorioIndex[$prontuario];
        $encontrados++;
    } else {
        $nao_encontrados++;
    }
}

echo "Validação concluída: $encontrados encontrados, $nao_encontrados não encontrados.\n";

// 4. Gerar HTML com filtros alinhados
echo "Gerando HTML com filtros alinhados...\n";

$html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PIJ / Laboral - Internos com Saldo na Casa</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset e Base */
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 14px;
            line-height: 1.4;
            background-color: #f8fafc;
            color: #1e293b;
            min-height: 100vh;
        }
        
        /* Dark Mode */
        body.dark-mode {
            background-color: #0f172a;
            color: #f1f5f9;
        }
        
        body.dark-mode .bg-white {
            background-color: #1e293b !important;
        }
        
        body.dark-mode .text-gray-900 {
            color: #f1f5f9 !important;
        }
        
        body.dark-mode .text-gray-600 {
            color: #94a3b8 !important;
        }
        
        body.dark-mode .text-gray-500 {
            color: #64748b !important;
        }
        
        body.dark-mode .border-gray-200 {
            border-color: #334155 !important;
        }
        
        body.dark-mode .bg-gray-50 {
            background-color: #1e293b !important;
        }
        
        body.dark-mode .hover\\:bg-gray-50:hover {
            background-color: #334155 !important;
        }
        
        body.dark-mode .card {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
        
        body.dark-mode .btn-outline {
            background-color: transparent !important;
            border-color: #4b5563 !important;
            color: #d1d5db !important;
        }
        
        body.dark-mode .btn-outline:hover {
            background-color: #374151 !important;
        }
        
        body.dark-mode .form-input {
            background-color: #1f2937 !important;
            border-color: #4b5563 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode .form-select {
            background-color: #1f2937 !important;
            border-color: #4b5563 !important;
            color: #f9fafb !important;
        }
        
        body.dark-mode .modal-content {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
        }
        
        /* Container Full Width */
        .container-full {
            width: 100%;
            padding: 1rem;
        }
        
        @media (min-width: 640px) {
            .container-full { padding: 1.5rem; }
        }
        
        /* Flexbox */
        .flex {
            display: flex;
        }
        
        .flex-col {
            flex-direction: column;
        }
        
        .flex-wrap {
            flex-wrap: wrap;
        }
        
        .items-center {
            align-items: center;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .justify-end {
            justify-content: flex-end;
        }
        
        .space-x-2 > * + * {
            margin-left: 0.5rem;
        }
        
        .space-x-3 > * + * {
            margin-left: 0.75rem;
        }
        
        .space-y-2 > * + * {
            margin-top: 0.5rem;
        }
        
        .space-y-4 > * + * {
            margin-top: 1rem;
        }
        
        /* Grid */
        .grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        
        .grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        
        .grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        
        .gap-2 {
            gap: 0.5rem;
        }
        
        .gap-3 {
            gap: 0.75rem;
        }
        
        .gap-4 {
            gap: 1rem;
        }
        
        @media (min-width: 640px) {
            .sm\\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        
        @media (min-width: 768px) {
            .md\\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        
        /* Colors */
        .bg-white {
            background-color: white;
        }
        
        .bg-blue-100 {
            background-color: #dbeafe;
        }
        
        .bg-green-100 {
            background-color: #d1fae5;
        }
        
        .bg-red-100 {
            background-color: #fee2e2;
        }
        
        .bg-purple-100 {
            background-color: #f3e8ff;
        }
        
        .bg-blue-600 {
            background-color: #2563eb;
        }
        
        .bg-green-600 {
            background-color: #059669;
        }
        
        .bg-gray-600 {
            background-color: #4b5563;
        }
        
        .text-gray-900 {
            color: #111827;
        }
        
        .text-gray-600 {
            color: #4b5563;
        }
        
        .text-gray-500 {
            color: #6b7280;
        }
        
        .text-blue-600 {
            color: #2563eb;
        }
        
        .text-green-600 {
            color: #059669;
        }
        
        .text-red-600 {
            color: #dc2626;
        }
        
        .text-purple-600 {
            color: #7c3aed;
        }
        
        .text-white {
            color: white;
        }
        
        /* Borders */
        .border {
            border: 1px solid;
        }
        
        .border-b {
            border-bottom: 1px solid;
        }
        
        .border-gray-200 {
            border-color: #e5e7eb;
        }
        
        /* Spacing */
        .p-2 {
            padding: 0.5rem;
        }
        
        .p-3 {
            padding: 0.75rem;
        }
        
        .p-4 {
            padding: 1rem;
        }
        
        .p-6 {
            padding: 1.5rem;
        }
        
        .py-3 {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
        }
        
        .py-4 {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }
        
        .px-3 {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        .px-4 {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        
        .mb-3 {
            margin-bottom: 0.75rem;
        }
        
        .mb-4 {
            margin-bottom: 1rem;
        }
        
        .mb-6 {
            margin-bottom: 1.5rem;
        }
        
        .mr-1 {
            margin-right: 0.25rem;
        }
        
        .mr-2 {
            margin-right: 0.5rem;
        }
        
        .mr-3 {
            margin-right: 0.75rem;
        }
        
        .ml-1 {
            margin-left: 0.25rem;
        }
        
        .ml-2 {
            margin-left: 0.5rem;
        }
        
        .ml-4 {
            margin-left: 1rem;
        }
        
        /* Typography */
        .text-xs {
            font-size: 0.75rem;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
        
        .text-lg {
            font-size: 1.125rem;
        }
        
        .text-xl {
            font-size: 1.25rem;
        }
        
        .text-2xl {
            font-size: 1.5rem;
        }
        
        .font-bold {
            font-weight: 700;
        }
        
        .font-medium {
            font-weight: 500;
        }
        
        .font-semibold {
            font-weight: 600;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        /* Width */
        .w-full {
            width: 100%;
        }
        
        .w-auto {
            width: auto;
        }
        
        /* Table */
        .table-optimized {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .table-optimized th,
        .table-optimized td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }
        
        .table-optimized th {
            background-color: #f1f5f9;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
            cursor: pointer;
        }
        
        .table-optimized th:hover {
            background-color: #e2e8f0;
        }
        
        .table-optimized tbody tr:hover {
            background-color: #f8fafc;
        }
        
        body.dark-mode .table-optimized th {
            background-color: #334155;
            color: #f1f5f9;
        }
        
        body.dark-mode .table-optimized td {
            border-color: #334155;
        }
        
        body.dark-mode .table-optimized tbody tr:hover {
            background-color: #1e293b;
        }
        
        /* Colunas específicas */
        .col-prontuario { width: 80px; }
        .col-nome-lista { width: 200px; }
        .col-saldo { width: 100px; }
        .col-status { width: 100px; }
        .col-unidade { width: 80px; }
        .col-nome-relatorio { width: 180px; }
        .col-situacao { width: 250px; }
        
        /* Cards */
        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .shadow-sm {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 13px;
            transition: all 0.2s;
            cursor: pointer;
            border: none;
            text-decoration: none;
            white-space: nowrap;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid #d1d5db;
            color: #374151;
        }
        
        .btn-outline:hover {
            background-color: #f9fafb;
        }
        
        /* Form inputs */
        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 13px;
            background-color: white;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 13px;
            background-color: white;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Status badges */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        body.dark-mode .badge-success {
            background-color: #064e3b;
            color: #6ee7b7;
        }
        
        body.dark-mode .badge-danger {
            background-color: #7f1d1d;
            color: #fca5a5;
        }
        
        /* Rounded */
        .rounded {
            border-radius: 0.25rem;
        }
        
        .rounded-lg {
            border-radius: 0.5rem;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 0.5rem;
            max-width: 90vw;
            max-height: 90vh;
            overflow-y: auto;
            padding: 2rem;
        }
        
        /* Loading */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Position */
        .relative {
            position: relative;
        }
        
        .fixed {
            position: fixed;
        }
        
        .top-4 {
            top: 1rem;
        }
        
        .right-4 {
            right: 1rem;
        }
        
        .sticky {
            position: sticky;
        }
        
        /* Hidden */
        .hidden {
            display: none;
        }
        
        /* Overflow */
        .overflow-hidden {
            overflow: hidden;
        }
        
        .overflow-x-auto {
            overflow-x: auto;
        }
        
        .overflow-y-auto {
            overflow-y: auto;
        }
        
        /* Print Styles - A4 Paisagem */
        @media print {
            @page {
                size: A4 landscape;
                margin: 1cm;
            }
            
            .no-print { 
                display: none !important; 
            }
            
            body { 
                font-size: 10px !important;
                line-height: 1.2 !important;
                background: white !important; 
                color: black !important; 
            }
            
            .card { 
                border: 1px solid #000 !important; 
                box-shadow: none !important;
                page-break-inside: avoid;
            }
            
            .table-optimized {
                font-size: 9px !important;
            }
            
            .table-optimized th,
            .table-optimized td {
                padding: 0.3rem !important;
                font-size: 9px !important;
                border: 1px solid #000 !important;
            }
            
            .table-optimized th {
                background: #f0f0f0 !important;
                color: black !important;
            }
            
            .badge {
                border: 1px solid #000 !important;
                background: white !important;
                color: black !important;
            }
            
            h1, h2, h3 {
                color: black !important;
            }
            
            .text-gray-600,
            .text-gray-500 {
                color: #666 !important;
            }
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .table-optimized {
                font-size: 12px;
            }
            
            .col-nome-lista { max-width: 150px; }
            .col-nome-relatorio { max-width: 150px; }
            .col-situacao { max-width: 200px; }
        }
        
        @media (max-width: 768px) {
            .table-optimized {
                font-size: 11px;
            }
            
            .container-full {
                padding: 0.5rem;
            }
            
            .btn {
                padding: 0.375rem 0.75rem;
                font-size: 12px;
            }
            
            .hidden-sm {
                display: none !important;
            }
        }
        
        /* Hover states */
        .hover\\:bg-gray-100:hover {
            background-color: #f3f4f6;
        }
        
        .hover\\:bg-blue-700:hover {
            background-color: #1d4ed8;
        }
        
        .hover\\:bg-green-700:hover {
            background-color: #047857;
        }
        
        .hover\\:bg-gray-700:hover {
            background-color: #374151;
        }
        
        /* Transition */
        .transition-colors {
            transition-property: color, background-color, border-color;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Z-index */
        .z-10 {
            z-index: 10;
        }
        
        .z-50 {
            z-index: 50;
        }
        
        /* Filtros Section - Alinhamento Corrigido */
        .filters-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .filters-row {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group.search {
            flex: 2;
            min-width: 300px;
        }
        
        .filter-group.actions {
            flex: 0 0 auto;
        }
        
        .filter-label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.25rem;
        }
        
        body.dark-mode .filter-label {
            color: #9ca3af;
        }
        
        .filter-input-wrapper {
            position: relative;
        }
        
        .filter-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.75rem;
            pointer-events: none;
        }
        
        .filter-input-with-icon {
            padding-left: 2.5rem !important;
        }
        
        .buttons-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .status-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #6b7280;
            padding: 0.5rem 0;
            border-top: 1px solid #e5e7eb;
        }
        
        body.dark-mode .status-info {
            color: #9ca3af;
            border-color: #374151;
        }
        
        @media (max-width: 768px) {
            .filters-row {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .filter-group {
                width: 100%;
                min-width: unset;
            }
            
            .filter-group.search {
                min-width: unset;
            }
            
            .buttons-group {
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 shadow-sm no-print">
        <div class="container-full">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <i class="fas fa-users text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">PIJ / Laboral</h1>
                        <p class="text-xs text-gray-600">Internos com Saldo na Casa - Lista do SAGEP</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="toggleTheme()" class="btn btn-outline" title="Alternar Tema">
                        <i id="theme-icon" class="fas fa-moon"></i>
                    </button>
                    <button onclick="showHelp()" class="btn btn-outline" title="Orientações">
                        <i class="fas fa-question-circle"></i>
                        <span class="hidden-sm ml-1 text-xs">Ajuda</span>
                    </button>
                    <div class="text-right text-xs text-gray-500">
                        <p>' . date('d/m/Y H:i') . '</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container-full py-4">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4 no-print">
            <div class="card p-4">
                <div class="flex items-center">
                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-list text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Total</p>
                        <p class="text-lg font-bold text-gray-900">' . count($listaInternos) . '</p>
                    </div>
                </div>
            </div>
            
            <div class="card p-4">
                <div class="flex items-center">
                    <div class="bg-green-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-check-circle text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Encontrados</p>
                        <p class="text-lg font-bold text-green-600">' . $encontrados . '</p>
                    </div>
                </div>
            </div>
            
            <div class="card p-4">
                <div class="flex items-center">
                    <div class="bg-red-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-times-circle text-red-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Não Encontrados</p>
                        <p class="text-lg font-bold text-red-600">' . $nao_encontrados . '</p>
                    </div>
                </div>
            </div>
            
            <div class="card p-4">
                <div class="flex items-center">
                    <div class="bg-purple-100 p-2 rounded-lg mr-3">
                        <i class="fas fa-percentage text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-600">Taxa</p>
                        <p class="text-lg font-bold text-purple-600">' . round(($encontrados / count($listaInternos)) * 100, 1) . '%</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters Section - Alinhado -->
        <div class="card p-4 mb-4 no-print">
            <div class="filters-section">
                <div class="filters-row">
                    <!-- Busca Geral -->
                    <div class="filter-group search">
                        <label class="filter-label">Busca Geral</label>
                        <div class="filter-input-wrapper">
                            <i class="fas fa-search filter-icon"></i>
                            <input type="text" id="globalSearch" placeholder="Pesquisar em tudo..." 
                                   class="form-input filter-input-with-icon">
                        </div>
                    </div>
                    
                    <!-- Filtro por Status -->
                    <div class="filter-group">
                        <label class="filter-label">Filtrar por Status</label>
                        <select id="filterStatus" class="form-select">
                            <option value="todos">Todos</option>
                            <option value="encontrados">Encontrados</option>
                            <option value="nao_encontrados">Não Encontrados</option>
                        </select>
                    </div>
                    
                    <!-- Ações -->
                    <div class="filter-group actions">
                        <label class="filter-label">Ações</label>
                        <div class="buttons-group">
                            <button onclick="exportToExcel()" class="btn btn-success text-xs">
                                <i class="fas fa-file-excel mr-1"></i>Excel
                            </button>
                            <button onclick="printReport()" class="btn btn-primary text-xs">
                                <i class="fas fa-print mr-1"></i>Imprimir
                            </button>
                            <button onclick="resetFilters()" class="btn btn-secondary text-xs">
                                <i class="fas fa-redo mr-1"></i>Limpar
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Status Info -->
                <div class="status-info">
                    <span>Registros visíveis: <strong id="visibleCount">0</strong></span>
                    <span>Total de registros: <strong>' . count($listaInternos) . '</strong></span>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-optimized" id="mainTable">
                    <thead>
                        <tr>
                            <th class="col-prontuario" onclick="sortTable(1)">
                                Prontuário <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-nome-lista" onclick="sortTable(2)">
                                Nome (Lista) <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-saldo" onclick="sortTable(3)">
                                Saldo <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-status" onclick="sortTable(4)">
                                Status <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-unidade" onclick="sortTable(5)">
                                Unidade <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-nome-relatorio" onclick="sortTable(6)">
                                Nome (Relatório) <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-situacao" onclick="sortTable(7)">
                                Situação Penal <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">';

// Adicionar dados à tabela
foreach ($listaInternos as $prontuario => $interno) {
    $status = $interno['encontrado'];
    $statusClass = $status ? 'badge-success' : 'badge-danger';
    $statusText = $status ? 'Encontrado' : 'Não Encontrado';
    $statusIcon = $status ? 'fa-check-circle' : 'fa-times-circle';
    
    $numeroUnidade = $status ? $interno['dados_relatorio']['numero_unidade'] : '-';
    $nomeUnidade = $status ? $interno['dados_relatorio']['unidade'] : '';
    $nomeRelatorio = $status ? $interno['dados_relatorio']['nome'] : '-';
    $situacao = $status ? $interno['dados_relatorio']['situacao'] : '-';
    
    // Formatar saldo para reais
    $saldoLimpo = str_replace(',', '.', str_replace('.', '', $interno['saldo']));
    $saldoFormatado = 'R$ ' . number_format($saldoLimpo, 2, ',', '.');
    
    $html .= '
                        <tr data-status="' . ($status ? 'encontrados' : 'nao_encontrados') . '" data-search="' . strtolower($prontuario . ' ' . $interno['nome'] . ' ' . $nomeRelatorio . ' ' . $numeroUnidade . ' ' . $nomeUnidade . ' ' . $situacao . ' ' . $interno['saldo']) . '">
                            <td class="font-medium">' . $prontuario . '</td>
                            <td title="' . htmlspecialchars($interno['nome']) . '">' . htmlspecialchars($interno['nome']) . '</td>
                            <td class="text-right font-medium">' . $saldoFormatado . '</td>
                            <td>
                                <span class="badge ' . $statusClass . '">
                                    <i class="fas ' . $statusIcon . ' mr-1"></i>
                                    ' . $statusText . '
                                </span>
                            </td>
                            <td title="' . htmlspecialchars($nomeUnidade) . '">' . $numeroUnidade . '</td>
                            <td title="' . htmlspecialchars($nomeRelatorio) . '">' . htmlspecialchars($nomeRelatorio) . '</td>
                            <td title="' . htmlspecialchars($situacao) . '">' . htmlspecialchars($situacao) . '</td>
                        </tr>';
}

$html .= '
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="hidden fixed top-4 right-4 bg-blue-600 text-white px-3 py-2 rounded-lg shadow-lg flex items-center space-x-2 no-print">
            <div class="loading"></div>
            <span class="text-xs">Filtrando...</span>
        </div>
    </main>

    <!-- Help Modal -->
    <div id="helpModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-question-circle mr-2"></i>Como Usar o Sistema
                </h2>
                <button onclick="closeHelp()" class="btn btn-outline">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="space-y-4">
                <!-- Como Pesquisar -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h3 class="text-lg font-semibold mb-3 text-blue-900">
                        <i class="fas fa-search mr-2"></i>Como Pesquisar
                    </h3>
                    <div class="space-y-2 text-blue-800">
                        <p><strong>Busca Geral:</strong> No campo "Pesquisar em tudo", digite qualquer palavra (nome, prontuário, unidade, situação). O sistema mostra instantaneamente os resultados.</p>
                        <p><strong>Exemplos:</strong> Digite "joão" para encontrar todos os joões, ou "8093" para encontrar da unidade 8093.</p>
                        <p><strong>Dica:</strong> Não precisa digitar o nome completo, parte do nome já funciona!</p>
                    </div>
                </div>

                <!-- Como Filtrar -->
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <h3 class="text-lg font-semibold mb-3 text-green-900">
                        <i class="fas fa-filter mr-2"></i>Como Filtrar
                    </h3>
                    <div class="space-y-2 text-green-800">
                        <p><strong>Filtrar por Status:</strong> Use o menu "Filtrar por Status" para ver apenas:</p>
                        <ul class="ml-4 list-disc">
                            <li><strong>Encontrados:</strong> Internos que apareceram no relatório</li>
                            <li><strong>Não Encontrados:</strong> Internos que não estão no relatório</li>
                            <li><strong>Todos:</strong> Mostra todos os internos</li>
                        </ul>
                        <p><strong>Contador:</strong> Veja na parte inferior dos filtros quantos registros estão visíveis.</p>
                    </div>
                </div>

                <!-- Como Gerar Relatório -->
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <h3 class="text-lg font-semibold mb-3 text-purple-900">
                        <i class="fas fa-file-alt mr-2"></i>Como Gerar Relatório
                    </h3>
                    <div class="space-y-2 text-purple-800">
                        <p><strong>Relatório em Excel:</strong></p>
                        <ol class="ml-4 list-decimal">
                            <li>Filtre os dados como desejar</li>
                            <li>Clique no botão "Excel"</li>
                            <li>O arquivo será baixado automaticamente</li>
                            <li>Abra no Excel para analisar os dados</li>
                        </ol>
                        
                        <p><strong>Relatório para Impressão:</strong></p>
                        <ol class="ml-4 list-decimal">
                            <li>Filtre os dados que quer imprimir</li>
                            <li>Clique no botão "Imprimir"</li>
                            <li>Escolha "Salvar como PDF" ou imprimir diretamente</li>
                            <li>O relatório sai em formato A4 paisagem</li>
                        </ol>
                        
                        <p><strong>Dica:</strong> Use o filtro "Não Encontrados" para imprimir apenas os problemas!</p>
                    </div>
                </div>

                <!-- Dicas Rápidas -->
                <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                    <h3 class="text-lg font-semibold mb-3 text-yellow-900">
                        <i class="fas fa-lightbulb mr-2"></i>Dicas Rápidas
                    </h3>
                    <div class="space-y-2 text-yellow-800">
                        <p><strong>Ordenar:</strong> Clique no título de qualquer coluna para ordenar (crescente/decrescente).</p>
                        <p><strong>Tema Escuro:</strong> Clique no ícone da lua no canto superior direito.</p>
                        <p><strong>Limpar Tudo:</strong> Clique em "Limpar" para voltar ao estado original.</p>
                        <p><strong>Unidades:</strong> A coluna "Unidade" mostra só o número, mas a busca funciona pelo nome completo.</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button onclick="closeHelp()" class="btn btn-primary">
                    <i class="fas fa-check mr-2"></i>Entendido
                </button>
            </div>
        </div>
    </div>

    <script>
        // Dados para exportação
        const dadosTabela = ' . json_encode(array_values($listaInternos)) . ';
        
        // Theme Management
        function toggleTheme() {
            const body = document.body;
            const icon = document.getElementById(\'theme-icon\');
            const isDark = body.classList.contains(\'dark-mode\');
            
            if (isDark) {
                body.classList.remove(\'dark-mode\');
                icon.className = \'fas fa-moon\';
                localStorage.setItem(\'theme\', \'light\');
            } else {
                body.classList.add(\'dark-mode\');
                icon.className = \'fas fa-sun\';
                localStorage.setItem(\'theme\', \'dark\');
            }
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem(\'theme\');
        if (savedTheme === \'dark\') {
            document.body.classList.add(\'dark-mode\');
            document.getElementById(\'theme-icon\').className = \'fas fa-sun\';
        }
        
        // Modal Management
        function showHelp() {
            document.getElementById(\'helpModal\').classList.add(\'active\');
        }
        
        function closeHelp() {
            document.getElementById(\'helpModal\').classList.remove(\'active\');
        }
        
        // Close modal on outside click
        document.getElementById(\'helpModal\').addEventListener(\'click\', function(e) {
            if (e.target === this) {
                closeHelp();
            }
        });
        
        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Global search
        const globalSearch = debounce(function() {
            filterTable();
        }, 300);
        
        // Event listeners
        document.getElementById(\'filterStatus\').addEventListener(\'change\', filterTable);
        document.getElementById(\'globalSearch\').addEventListener(\'input\', globalSearch);
        
        // Filter table function
        function filterTable() {
            const loadingIndicator = document.getElementById(\'loadingIndicator\');
            loadingIndicator.classList.remove(\'hidden\');
            
            setTimeout(() => {
                const statusFilter = document.getElementById(\'filterStatus\').value;
                const globalSearchValue = document.getElementById(\'globalSearch\').value.toLowerCase();
                const rows = document.querySelectorAll(\'#tableBody tr\');
                
                let visibleCount = 0;
                
                rows.forEach(row => {
                    let visible = true;
                    
                    // Status filter
                    if (statusFilter !== \'todos\' && row.dataset.status !== statusFilter) {
                        visible = false;
                    }
                    
                    // Global search filter
                    if (globalSearchValue && !row.dataset.search.includes(globalSearchValue)) {
                        visible = false;
                    }
                    
                    row.style.display = visible ? \'\' : \'none\';
                    if (visible) visibleCount++;
                });
                
                loadingIndicator.classList.add(\'hidden\');
                document.getElementById(\'visibleCount\').textContent = visibleCount;
            }, 100);
        }
        
        // Sort table function
        let sortDirection = {};
        
        function sortTable(columnIndex) {
            const table = document.querySelector(\'#mainTable\');
            const tbody = table.querySelector(\'tbody\');
            const rows = Array.from(tbody.querySelectorAll(\'tr\'));
            
            sortDirection[columnIndex] = sortDirection[columnIndex] === \'asc\' ? \'desc\' : \'asc\';
            
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                
                if (columnIndex === 1 || columnIndex === 3) { // Prontuário and Saldo
                    const aNum = parseFloat(aValue.replace(/[^\\d.-]/g, \'\')) || 0;
                    const bNum = parseFloat(bValue.replace(/[^\\d.-]/g, \'\')) || 0;
                    return sortDirection[columnIndex] === \'asc\' ? aNum - bNum : bNum - aNum;
                } else {
                    return sortDirection[columnIndex] === \'asc\'
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Reset filters
        function resetFilters() {
            document.getElementById(\'globalSearch\').value = \'\';
            document.getElementById(\'filterStatus\').value = \'todos\';
            filterTable();
        }
        
        // Export to Excel
        function exportToExcel() {
            let csv = \'Prontuário;Nome (Lista);Saldo;Status;Unidade;Nome (Relatório);Situação Penal\\n\';
            
            dadosTabela.forEach(interno => {
                const status = interno.encontrado ? \'Encontrado\' : \'Não Encontrado\';
                const unidade = interno.encontrado ? interno.dados_relatorio.numero_unidade : \'-\';
                const nomeUnidade = interno.encontrado ? interno.dados_relatorio.unidade : \'-\';
                const nomeRelatorio = interno.encontrado ? interno.dados_relatorio.nome : \'-\';
                const situacao = interno.encontrado ? interno.dados_relatorio.situacao : \'-\';
                const saldo = interno.saldo.replace(\'.\', \',\').replace(\',\', \'.\');
                
                csv += `${interno.prontuario};"${interno.nome}";${saldo};${status};${unidade};"${nomeRelatorio}";"${situacao}"\\n`;
            });
            
            const blob = new Blob([csv], { type: \'text/csv;charset=utf-8;\' });
            const link = document.createElement(\'a\');
            link.href = URL.createObjectURL(blob);
            link.download = \'validacao_internos.csv\';
            link.click();
        }
        
        // Print report
        function printReport() {
            const originalTitle = document.title;
            document.title = \'Relatório de Validação de Internos - \' + new Date().toLocaleDateString(\'pt-BR\');
            
            window.print();
            
            setTimeout(() => {
                document.title = originalTitle;
            }, 1000);
        }
        
        // Initialize on load
        document.addEventListener(\'DOMContentLoaded\', function() {
            filterTable();
            
            // Add keyboard shortcuts
            document.addEventListener(\'keydown\', function(e) {
                if (e.ctrlKey && e.key === \'p\') {
                    e.preventDefault();
                    printReport();
                }
                if (e.ctrlKey && e.key === \'h\') {
                    e.preventDefault();
                    showHelp();
                }
            });
        });
    </script>
</body>
</html>';

file_put_contents($arquivoSaida, $html);

echo "\n=== RELATÓRIO FINAL COM FILTROS ALINHADOS ===\n";
echo "Total na lista: " . count($listaInternos) . "\n";
echo "Encontrados: $encontrados\n";
echo "Não encontrados: $nao_encontrados\n";
echo "Taxa de encontro: " . round(($encontrados / count($listaInternos)) * 100, 1) . "%\n";
echo "Arquivo HTML gerado: $arquivoSaida\n";
echo "\n✅ FILTROS TOTALMENTE ALINHADOS:\n";
echo "✅ Layout organizado e profissional\n";
echo "✅ Labels alinhadas acima dos inputs\n";
echo "✅ Busca com ícone posicionado corretamente\n";
echo "✅ Botões alinhados na mesma linha\n";
echo "✅ Informações de status organizadas\n";
echo "✅ Responsividade mantida\n";
echo "✅ Dark mode aplicado aos filtros\n";
echo "\n🚀 SISTEMA 100% ALINHADO E FUNCIONAL!\n";

?>
