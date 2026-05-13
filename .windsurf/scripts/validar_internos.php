<?php
/**
 * Validador de Internos - Lista vs Relatório
 * Compara lista de internos com relatório completo e gera HTML standalone
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

// 4. Gerar HTML standalone
echo "Gerando HTML...\n";

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
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Validação de Internos</h1>
                            <p class="text-sm text-gray-500">Lista vs Relatório Completo</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Gerado em: ' . date('d/m/Y H:i:s') . '</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Stats Cards -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
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
                
                <div class="bg-white rounded-lg shadow p-6">
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
                
                <div class="bg-white rounded-lg shadow p-6">
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
                
                <div class="bg-white rounded-lg shadow p-6">
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

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex flex-wrap gap-4 items-center">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700">Filtrar:</label>
                        <select id="filterStatus" class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="todos">Todos</option>
                            <option value="encontrados">Encontrados</option>
                            <option value="nao_encontrados">Não Encontrados</option>
                        </select>
                    </div>
                    <div class="flex items-center space-x-2">
                        <input type="text" id="searchInput" placeholder="Buscar por nome ou prontuário..." 
                               class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="exportToExcel()" class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 transition-colors">
                            <i class="fas fa-file-excel mr-2"></i>Exportar Excel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(1)">
                                    Prontuário <i class="fas fa-sort text-gray-400"></i>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(2)">
                                    Nome (Lista) <i class="fas fa-sort text-gray-400"></i>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Saldo
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(4)">
                                    Status <i class="fas fa-sort text-gray-400"></i>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(5)">
                                    Unidade <i class="fas fa-sort text-gray-400"></i>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(6)">
                                    Nome (Relatório) <i class="fas fa-sort text-gray-400"></i>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Situação
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
    
    $html .= '
                            <tr class="hover:bg-gray-50" data-status="' . ($status ? 'encontrados' : 'nao_encontrados') . '" data-search="' . strtolower($prontuario . ' ' . $interno['nome'] . ' ' . $nomeRelatorio) . '">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" class="rounded border-gray-300 row-checkbox">
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ' . $prontuario . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ' . htmlspecialchars($interno['nome']) . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ' . $interno['saldo'] . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . '">
                                        <i class="fas ' . $statusIcon . ' mr-1"></i>
                                        ' . $statusText . '
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ' . htmlspecialchars($unidade) . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ' . htmlspecialchars($nomeRelatorio) . '
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ' . htmlspecialchars($situacao) . '
                                </td>
                            </tr>';
}

$html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Dados para exportação
        const dadosTabela = ' . json_encode(array_values($listaInternos)) . ';
        
        // Função de filtro
        document.getElementById(\'filterStatus\').addEventListener(\'change\', filterTable);
        document.getElementById(\'searchInput\').addEventListener(\'input\', filterTable);
        
        function filterTable() {
            const statusFilter = document.getElementById(\'filterStatus\').value;
            const searchFilter = document.getElementById(\'searchInput\').value.toLowerCase();
            const rows = document.querySelectorAll(\'#tableBody tr\');
            
            rows.forEach(row => {
                const matchesStatus = statusFilter === \'todos\' || row.dataset.status === statusFilter;
                const matchesSearch = row.dataset.search.includes(searchFilter);
                
                row.style.display = matchesStatus && matchesSearch ? \'\' : \'none\';
            });
        }
        
        // Função de ordenação
        let sortDirection = {};
        
        function sortTable(columnIndex) {
            const table = document.querySelector(\'table\');
            const tbody = table.querySelector(\'tbody\');
            const rows = Array.from(tbody.querySelectorAll(\'tr\'));
            
            sortDirection[columnIndex] = sortDirection[columnIndex] === \'asc\' ? \'desc\' : \'asc\';
            
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                
                if (columnIndex === 1 || columnIndex === 4) { // Colunas numéricas
                    return sortDirection[columnIndex] === \'asc\' 
                        ? parseInt(aValue) - parseInt(bValue)
                        : parseInt(bValue) - parseInt(aValue);
                } else {
                    return sortDirection[columnIndex] === \'asc\'
                        ? aValue.localeCompare(bValue)
                        : bValue.localeCompare(aValue);
                }
            });
            
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Selecionar todos
        document.getElementById(\'selectAll\').addEventListener(\'change\', function() {
            const checkboxes = document.querySelectorAll(\'.row-checkbox\');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // Exportar para Excel
        function exportToExcel() {
            let csv = \'Prontuário;Nome (Lista);Saldo;Status;Unidade;Nome (Relatório);Situação\\n\';
            
            dadosTabela.forEach(interno => {
                const status = interno.encontrado ? \'Encontrado\' : \'Não Encontrado\';
                const unidade = interno.encontrado ? interno.dados_relatorio.unidade : \'-\';
                const nomeRelatorio = interno.encontrado ? interno.dados_relatorio.nome : \'-\';
                const situacao = interno.encontrado ? interno.dados_relatorio.situacao : \'-\';
                
                csv += `${interno.prontuario};"${interno.nome}";${interno.saldo};${status};${unidade};"${nomeRelatorio}";${situacao}\\n`;
            });
            
            const blob = new Blob([csv], { type: \'text/csv;charset=utf-8;\' });
            const link = document.createElement(\'a\');
            link.href = URL.createObjectURL(blob);
            link.download = \'validacao_internos.csv\';
            link.click();
        }
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
echo "\nProcesso concluído com sucesso!\n";

?>
