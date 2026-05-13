<?php
/**
 * Validador de Internos - Lista vs Relatório (Versão Melhorada)
 * Compara lista de internos com relatório completo e gera HTML standalone com recursos avançados
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoLista = 'C:\Servicos\ConsultaUnidades\lista_internos_saldo_na_casa.txt';
$arquivoRelatorio = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_COMPLETO.csv';
$arquivoSaida = 'C:\Servicos\ConsultaUnidades\validacao_internos.html';

echo "Iniciando validação de internos...\n";

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
            $relatorioIndex[$prontuario] = [
                'id_unidade' => $id_unidade,
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

// 4. Gerar HTML standalone melhorado
echo "Gerando HTML melhorado...\n";

$html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação de Internos - Lista vs Relatório</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        
        /* Dark mode styles */
        .dark { background-color: #1a202c; color: #e2e8f0; }
        .dark .bg-white { background-color: #2d3748; }
        .dark .bg-gray-50 { background-color: #1a202c; }
        .dark .bg-gray-100 { background-color: #2d3748; }
        .dark .text-gray-900 { color: #e2e8f0; }
        .dark .text-gray-800 { color: #cbd5e0; }
        .dark .text-gray-700 { color: #a0aec0; }
        .dark .text-gray-600 { color: #718096; }
        .dark .text-gray-500 { color: #4a5568; }
        .dark .border-gray-200 { border-color: #4a5568; }
        .dark .border-gray-300 { border-color: #4a5568; }
        .dark .hover\\:bg-gray-50:hover { background-color: #2d3748; }
        .dark .hover\\:bg-gray-100:hover { background-color: #4a5568; }
        
        /* Table responsive */
        .table-container { overflow-x: auto; }
        .min-w-max { min-width: max-content; }
        
        /* Filter inputs */
        .filter-input { transition: all 0.2s; }
        .filter-input:focus { transform: scale(1.02); }
        
        /* Loading animation */
        @keyframes spin { to { transform: rotate(360deg); } }
        .animate-spin { animation: spin 1s linear infinite; }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #555; }
        .dark .custom-scrollbar::-webkit-scrollbar-track { background: #2d3748; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #4a5568; }
    </style>
</head>
<body class="bg-gray-50 transition-colors duration-200">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200 transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Validação de Internos</h1>
                            <p class="text-sm text-gray-500">Lista vs Relatório Completo</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors" title="Alternar Modo Escuro/Claro">
                            <i id="theme-icon" class="fas fa-moon text-gray-600"></i>
                        </button>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Gerado em: ' . date('d/m/Y H:i:s') . '</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total na Lista</p>
                            <p class="text-2xl font-bold text-gray-900">' . count($listaInternos) . '</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Encontrados</p>
                            <p class="text-2xl font-bold text-green-600">' . $encontrados . '</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                            <i class="fas fa-times-circle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Não Encontrados</p>
                            <p class="text-2xl font-bold text-red-600">' . $nao_encontrados . '</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 transition-colors duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                            <i class="fas fa-percentage text-purple-600 text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Taxa de Encontro</p>
                            <p class="text-2xl font-bold text-purple-600">' . round(($encontrados / count($listaInternos)) * 100, 1) . '%</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6 transition-colors duration-200">
                <div class="space-y-4">
                    <!-- Global Search -->
                    <div class="flex items-center space-x-4">
                        <div class="flex-1 relative">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="text" id="globalSearch" placeholder="Busca global em todas as colunas..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 filter-input">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Filtrar:</label>
                            <select id="filterStatus" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="todos">Todos</option>
                                <option value="encontrados">Encontrados</option>
                                <option value="nao_encontrados">Não Encontrados</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 transition-colors">
                                <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                            </button>
                            <button onclick="resetFilters()" class="bg-gray-600 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-700 transition-colors">
                                <i class="fas fa-redo mr-2"></i>Limpar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table with Filters -->
            <div class="bg-white rounded-lg shadow overflow-hidden transition-colors duration-200">
                <div class="table-container custom-scrollbar">
                    <table class="min-w-max divide-y divide-gray-200" id="mainTable">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(1)">
                                    Prontuário <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="1">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(2)">
                                    Nome (Lista) <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="2">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(3)">
                                    Saldo <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="3">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(4)">
                                    Status <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="4">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(5)">
                                    Unidade <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="5">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(6)">
                                    Nome (Relatório) <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="6">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" onclick="sortTable(7)">
                                    Situação Penal <i class="fas fa-sort text-gray-400 ml-1"></i>
                                    <input type="text" class="filter-input mt-2 w-full px-2 py-1 text-xs border rounded" placeholder="Filtrar..." data-column="7">
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="tableBody">';

// Adicionar dados à tabela
foreach ($listaInternos as $prontuario => $interno) {
    $status = $interno['encontrado'];
    $statusClass = $status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
    $statusText = $status ? 'Encontrado' : 'Não Encontrado';
    $statusIcon = $status ? 'fa-check-circle' : 'fa-times-circle';
    
    $unidade = $status ? $interno['dados_relatorio']['unidade'] : '-';
    $nomeRelatorio = $status ? $interno['dados_relatorio']['nome'] : '-';
    $situacao = $status ? $interno['dados_relatorio']['situacao'] : '-';
    
    // Formatar saldo para reais
    $saldoFormatado = 'R$ ' . number_format(str_replace(',', '.', str_replace('.', '', $interno['saldo'])), 2, ',', '.');
    
    $html .= '
                            <tr class="hover:bg-gray-50 transition-colors" data-status="' . ($status ? 'encontrados' : 'nao_encontrados') . '" data-search="' . strtolower($prontuario . ' ' . $interno['nome'] . ' ' . $nomeRelatorio . ' ' . $unidade . ' ' . $situacao . ' ' . $interno['saldo']) . '">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="rounded border-gray-300 row-checkbox">
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ' . $prontuario . '
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 max-w-xs truncate" title="' . htmlspecialchars($interno['nome']) . '">
                                    ' . htmlspecialchars($interno['nome']) . '
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                    ' . $saldoFormatado . '
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . '">
                                        <i class="fas ' . $statusIcon . ' mr-1"></i>
                                        ' . $statusText . '
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 max-w-xs truncate" title="' . htmlspecialchars($unidade) . '">
                                    ' . htmlspecialchars($unidade) . '
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 max-w-xs truncate" title="' . htmlspecialchars($nomeRelatorio) . '">
                                    ' . htmlspecialchars($nomeRelatorio) . '
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-900 max-w-xs truncate" title="' . htmlspecialchars($situacao) . '">
                                    ' . htmlspecialchars($situacao) . '
                                </td>
                            </tr>';
}

$html .= '
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="hidden fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2">
                <i class="fas fa-spinner animate-spin"></i>
                <span>Filtrando...</span>
            </div>
        </div>
    </div>

    <script>
        // Dados para exportação
        const dadosTabela = ' . json_encode(array_values($listaInternos)) . ';
        
        // Dark Mode Toggle
        function toggleDarkMode() {
            const body = document.body;
            const icon = document.getElementById(\'theme-icon\');
            
            body.classList.toggle(\'dark\');
            
            if (body.classList.contains(\'dark\')) {
                icon.classList.remove(\'fa-moon\');
                icon.classList.add(\'fa-sun\');
                localStorage.setItem(\'darkMode\', \'true\');
            } else {
                icon.classList.remove(\'fa-sun\');
                icon.classList.add(\'fa-moon\');
                localStorage.setItem(\'darkMode\', \'false\');
            }
        }
        
        // Check for saved dark mode preference
        if (localStorage.getItem(\'darkMode\') === \'true\') {
            document.body.classList.add(\'dark\');
            document.getElementById(\'theme-icon\').classList.remove(\'fa-moon\');
            document.getElementById(\'theme-icon\').classList.add(\'fa-sun\');
        }
        
        // Debounce function for search
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
        
        // Global search with debounce
        const globalSearch = debounce(function() {
            filterTable();
        }, 300);
        
        // Column filters with debounce
        const columnFilters = {};
        document.querySelectorAll(\'[data-column]\').forEach(input => {
            const column = input.dataset.column;
            columnFilters[column] = debounce(function() {
                filterTable();
            }, 300);
            
            input.addEventListener(\'input\', () => columnFilters[column]());
        });
        
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
                    
                    // Column filters
                    document.querySelectorAll(\'[data-column]\').forEach(input => {
                        const column = parseInt(input.dataset.column);
                        const filterValue = input.value.toLowerCase();
                        
                        if (filterValue) {
                            const cellText = row.cells[column].textContent.toLowerCase();
                            if (!cellText.includes(filterValue)) {
                                visible = false;
                            }
                        }
                    });
                    
                    row.style.display = visible ? \'\' : \'none\';
                    if (visible) visibleCount++;
                });
                
                loadingIndicator.classList.add(\'hidden\');
                
                // Update visible count
                updateVisibleCount(visibleCount);
            }, 100);
        }
        
        // Update visible count
        function updateVisibleCount(count) {
            // You could add a counter display here if needed
            console.log(`Visible rows: ${count}`);
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
                
                if (columnIndex === 1 || columnIndex === 4) { // Prontuário and potentially other numeric columns
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
        
        // Select all functionality
        document.getElementById(\'selectAll\').addEventListener(\'change\', function() {
            const checkboxes = document.querySelectorAll(\'.row-checkbox\');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Reset filters
        function resetFilters() {
            document.getElementById(\'globalSearch\').value = \'\';
            document.getElementById(\'filterStatus\').value = \'todos\';
            document.querySelectorAll(\'[data-column]\').forEach(input => {
                input.value = \'\';
            });
            filterTable();
        }
        
        // Export to Excel function
        function exportToExcel() {
            let csv = \'Prontuário;Nome (Lista);Saldo;Status;Unidade;Nome (Relatório);Situação Penal\\n\';
            
            dadosTabela.forEach(interno => {
                const status = interno.encontrado ? \'Encontrado\' : \'Não Encontrado\';
                const unidade = interno.encontrado ? interno.dados_relatorio.unidade : \'-\';
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
        
        // Initialize filters on load
        document.addEventListener(\'DOMContentLoaded\', function() {
            filterTable();
        });
    </script>
</body>
</html>';

file_put_contents($arquivoSaida, $html);

echo "\n=== RELATÓRIO FINAL ===\n";
echo "Total na lista: " . count($listaInternos) . "\n";
echo "Encontrados: $encontrados\n";
echo "Não encontrados: $nao_encontrados\n";
echo "Taxa de encontro: " . round(($encontrados / count($listaInternos)) * 100, 1) . "%\n";
echo "Arquivo HTML gerado: $arquivoSaida\n";
echo "\nMelhorias implementadas:\n";
echo "✅ Modo Dark/Light toggle\n";
echo "✅ Filtros em todas as colunas\n";
echo "✅ Pesquisa AJAX em tempo real (debounce)\n";
echo "✅ Saldo formatado em reais\n";
echo "✅ Coluna Situação Penal adicionada\n";
echo "✅ Tabela responsiva sem cortes\n";
echo "✅ Indicador de carregamento\n";
echo "✅ Botão de limpar filtros\n";
echo "\nProcesso concluído com sucesso!\n";

?>
