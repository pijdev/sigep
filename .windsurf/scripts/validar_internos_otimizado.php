<?php
/**
 * Validador de Internos - Versão Otimizada Final
 * Layout otimizado para mostrar todos os dados com fonte ajustada e impressão A4 paisagem
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoLista = 'C:\Servicos\ConsultaUnidades\lista_internos_saldo_na_casa.txt';
$arquivoRelatorio = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_COMPLETO.csv';
$arquivoSaida = 'C:\Servicos\ConsultaUnidades\validacao_internos.html';

echo "Iniciando validação de internos versão otimizada final...\n";

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

// 4. Gerar HTML otimizado final
echo "Gerando HTML otimizado final...\n";

$html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Internos - Sistema Otimizado</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        [data-theme="dark"] {
            background-color: #0f172a;
            color: #f1f5f9;
        }
        
        [data-theme="dark"] .bg-white {
            background-color: #1e293b !important;
        }
        
        [data-theme="dark"] .text-gray-900 {
            color: #f1f5f9 !important;
        }
        
        [data-theme="dark"] .text-gray-600 {
            color: #94a3b8 !important;
        }
        
        [data-theme="dark"] .text-gray-500 {
            color: #64748b !important;
        }
        
        [data-theme="dark"] .border-gray-200 {
            border-color: #334155 !important;
        }
        
        [data-theme="dark"] .bg-gray-50 {
            background-color: #1e293b !important;
        }
        
        [data-theme="dark"] .hover\\:bg-gray-50:hover {
            background-color: #334155 !important;
        }
        
        /* Container Full Width */
        .container-full {
            width: 100%;
            padding: 1rem;
        }
        
        @media (min-width: 640px) {
            .container-full { padding: 1.5rem; }
        }
        
        /* Table Otimizada */
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
        }
        
        .table-optimized tbody tr:hover {
            background-color: #f8fafc;
        }
        
        [data-theme="dark"] .table-optimized th {
            background-color: #334155;
            color: #f1f5f9;
        }
        
        [data-theme="dark"] .table-optimized td {
            border-color: #334155;
        }
        
        [data-theme="dark"] .table-optimized tbody tr:hover {
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
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 13px;
        }
        
        .form-input:focus {
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
        
        [data-theme="dark"] .badge-success {
            background-color: #064e3b;
            color: #6ee7b7;
        }
        
        [data-theme="dark"] .badge-danger {
            background-color: #7f1d1d;
            color: #fca5a5;
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
        
        [data-theme="dark"] .modal-content {
            background: #1e293b;
            color: #f1f5f9;
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
        }
    </style>
</head>
<body data-theme="light">
    <!-- Header -->
    <header class="bg-white border-b border-gray-200 shadow-sm no-print">
        <div class="container-full">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-100 p-2 rounded-lg">
                        <i class="fas fa-users text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">Validação de Internos</h1>
                        <p class="text-xs text-gray-600">Lista vs Relatório Completo</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <button onclick="toggleTheme()" class="btn btn-outline" title="Alternar Tema">
                        <i id="theme-icon" class="fas fa-moon"></i>
                    </button>
                    <button onclick="showHelp()" class="btn btn-outline" title="Orientações">
                        <i class="fas fa-question-circle"></i>
                        <span class="hidden sm:inline ml-1 text-xs">Ajuda</span>
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

        <!-- Filters -->
        <div class="card p-4 mb-4 no-print">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Busca Geral</label>
                    <div class="relative">
                        <i class="fas fa-search absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" id="globalSearch" placeholder="Pesquisar em tudo..." 
                               class="form-input pl-7 text-xs">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Filtrar por Status</label>
                    <select id="filterStatus" class="form-input text-xs">
                        <option value="todos">Todos</option>
                        <option value="encontrados">Encontrados</option>
                        <option value="nao_encontrados">Não Encontrados</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Ações</label>
                    <div class="flex gap-2">
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
            <div class="text-xs text-gray-500">
                <span id="visibleCount">0</span> registros visíveis
            </div>
        </div>

        <!-- Table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table-optimized" id="mainTable">
                    <thead>
                        <tr>
                            <th class="col-prontuario" onclick="sortTable(1)" class="cursor-pointer hover:bg-gray-100">
                                Prontuário <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-nome-lista" onclick="sortTable(2)" class="cursor-pointer hover:bg-gray-100">
                                Nome (Lista) <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-saldo" onclick="sortTable(3)" class="cursor-pointer hover:bg-gray-100">
                                Saldo <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-status" onclick="sortTable(4)" class="cursor-pointer hover:bg-gray-100">
                                Status <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-unidade" onclick="sortTable(5)" class="cursor-pointer hover:bg-gray-100">
                                Unidade <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-nome-relatorio" onclick="sortTable(6)" class="cursor-pointer hover:bg-gray-100">
                                Nome (Relatório) <i class="fas fa-sort text-gray-400 ml-1 text-xs"></i>
                            </th>
                            <th class="col-situacao" onclick="sortTable(7)" class="cursor-pointer hover:bg-gray-100">
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
                        <p><strong>Contador:</strong> Olhe no canto inferior direito para ver quantos registros estão visíveis.</p>
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
            const currentTheme = body.getAttribute(\'data-theme\');
            const newTheme = currentTheme === \'light\' ? \'dark\' : \'light\';
            
            body.setAttribute(\'data-theme\', newTheme);
            icon.className = newTheme === \'dark\' ? \'fas fa-sun\' : \'fas fa-moon\';
            localStorage.setItem(\'theme\', newTheme);
        }
        
        // Load saved theme
        const savedTheme = localStorage.getItem(\'theme\') || \'light\';
        document.body.setAttribute(\'data-theme\', savedTheme);
        document.getElementById(\'theme-icon\').className = savedTheme === \'dark\' ? \'fas fa-sun\' : \'fas fa-moon\';
        
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

echo "\n=== RELATÓRIO FINAL VERSÃO OTIMIZADA ===\n";
echo "Total na lista: " . count($listaInternos) . "\n";
echo "Encontrados: $encontrados\n";
echo "Não encontrados: $nao_encontrados\n";
echo "Taxa de encontro: " . round(($encontrados / count($listaInternos)) * 100, 1) . "%\n";
echo "Arquivo HTML gerado: $arquivoSaida\n";
echo "\n✅ MELHORIAS FINAIS IMPLEMENTADAS:\n";
echo "✅ Fonte reduzida para caber todos os dados\n";
echo "✅ Layout full width (100% da página)\n";
echo "✅ Coluna unidade só com número (busca pelo nome completo)\n";
echo "✅ Filtros removidos das colunas (área dedicada)\n";
echo "✅ Impressão A4 paisagem otimizada para toner\n";
echo "✅ Orientações simples e não técnicas\n";
echo "✅ Design limpo e profissional\n";
echo "\n🚀 SISTEMA OTIMIZADO FINAL CONCLUÍDO!\n";

?>
