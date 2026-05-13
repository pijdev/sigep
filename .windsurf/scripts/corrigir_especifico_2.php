<?php
/**
 * Corretor Específico - Linhas com Número no Nome
 * Corrige apenas as linhas que começam com número
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_LIMPO.csv';
$arquivoCorrigido = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_FINAL.csv';

echo "Aplicando correção específica - linhas com número no nome...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleCorrigido = fopen($arquivoCorrigido, 'w');

if (!$handleOrigem || !$handleCorrigido) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesEspecificas = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleCorrigido, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção específica...\n";

while (($linha = fgets($handleOrigem)) !== false) {
    $totalLinhas++;
    
    if ($totalLinhas % 50000 == 0) {
        echo "Processadas: $totalLinhas linhas\n";
    }
    
    $dados = str_getcsv($linha, ';', '"', '\\');
    
    if (count($dados) != 5) {
        continue;
    }
    
    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;
    
    $corrigido = false;
    
    // Correção ESPECÍFICA: Nome começa com número (usar pattern mais flexível)
    if (preg_match('/^[0-9]+\s+(.+)$/', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        
        if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
            $nome = $nomeCorrigido;
            $corrigido = true;
            $correcoesEspecificas++;
            
            if ($correcoesEspecificas <= 20) {
                echo "Correção E#$correcoesEspecificas: '$nomeCorrigido'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleCorrigido, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleCorrigido);

echo "\n=== RELATÓRIO ESPECÍFICO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções específicas aplicadas: $correcoesEspecificas\n";
echo "Arquivo corrigido salvo em: $arquivoCorrigido\n";
echo "\nProcesso concluído com sucesso!\n";

?>
