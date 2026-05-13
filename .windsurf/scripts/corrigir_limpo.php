<?php
/**
 * Corretor Simples - Remover Número do Nome
 * Remove qualquer número do início do nome
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_REAL.csv';
$arquivoLimpo = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_LIMPO.csv';

echo "Aplicando correção simples - remover número do nome...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleLimpo = fopen($arquivoLimpo, 'w');

if (!$handleOrigem || !$handleLimpo) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesLimpo = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleLimpo, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção simples...\n";

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
    
    // Correção SIMPLES: Remover qualquer número do início do nome
    if (preg_match('/^[0-9]+\s+(.+)$/', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        
        if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
            $nome = $nomeCorrigido;
            $corrigido = true;
            $correcoesLimpo++;
            
            if ($correcoesLimpo <= 20) {
                echo "Correção L#$correcoesLimpo: '$nomeCorrigido'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleLimpo, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleLimpo);

echo "\n=== RELATÓRIO LIMPO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções limpas aplicadas: $correcoesLimpo\n";
echo "Arquivo limpo salvo em: $arquivoLimpo\n";
echo "\nProcesso concluído com sucesso!\n";

?>
