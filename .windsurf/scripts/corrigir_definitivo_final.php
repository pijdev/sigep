<?php
/**
 * Corretor FINAL DEFINITIVO - Últimos 4 Erros
 * Corrige os últimos 4 casos restantes
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_MAXIMO.csv';
$arquivoDefinitivo = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_DEFINITIVO.csv';

echo "Aplicando correção final definitiva - últimos 4 erros...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleDefinitivo = fopen($arquivoDefinitivo, 'w');

if (!$handleOrigem || !$handleDefinitivo) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesDefinitivas = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleDefinitivo, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção final definitiva...\n";

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
    
    // Correção DEFINITIVA: Nome começa com número (verificar caractere por caractere)
    $primeiroChar = substr($nome, 0, 1);
    if (ctype_digit($primeiroChar)) {
        $posPrimeiroEspaco = strpos($nome, ' ');
        if ($posPrimeiroEspaco !== false) {
            $nomeCorrigido = trim(substr($nome, $posPrimeiroEspaco + 1));
            
            if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
                $nome = $nomeCorrigido;
                $corrigido = true;
                $correcoesDefinitivas++;
                
                echo "Correção D#$correcoesDefinitivas: Nome '$nomeCorrigido'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleDefinitivo, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleDefinitivo);

echo "\n=== RELATÓRIO FINAL DEFINITIVO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções definitivas aplicadas: $correcoesDefinitivas\n";
echo "Arquivo definitivo salvo em: $arquivoDefinitivo\n";
echo "\nProcesso concluído com sucesso!\n";

?>
