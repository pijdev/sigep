<?php
/**
 * Corretor FINAL - Prontuário Duplicado no Nome
 * Remove prontuário duplicado do campo nome
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_ABSOLUTO.csv';
$arquivoFinalReal = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_REAL.csv';

echo "Aplicando correção final - prontuário duplicado no nome...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleFinalReal = fopen($arquivoFinalReal, 'w');

if (!$handleOrigem || !$handleFinalReal) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesFinais = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleFinalReal, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção final real...\n";

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
    
    // Correção FINAL REAL: Nome começa com o mesmo prontuário
    if (preg_match('/^' . preg_quote($prontuario, '/') . '\s+(.+)$/i', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        
        // Verificar se o nome corrigido é razoável
        if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
            $nome = $nomeCorrigido;
            $corrigido = true;
            $correcoesFinais++;
            
            if ($correcoesFinais <= 20) {
                echo "Correção FR#$correcoesFinais: $prontuario | '$nomeCorrigido'\n";
            }
        }
    }
    
    // Correção FINAL REAL 2: Nome contém o prontuário no início
    if (preg_match('/^' . preg_quote($prontuario, '/') . '\s+(.+)$/i', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        
        if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
            $nome = $nomeCorrigido;
            $corrigido = true;
            $correcoesFinais++;
            
            if ($correcoesFinais <= 20) {
                echo "Correção FR#$correcoesFinais: $prontuario | '$nomeCorrigido'\n";
            }
        }
    }
    
    // Correção FINAL REAL 3: Nome começa com número (qualquer número)
    if (preg_match('/^[0-9]+\s+([A-ZÀ-Ž\s]+)$/i', $nome, $matches)) {
        $numeroNoNome = trim(substr($nome, 0, strpos($nome, ' ')));
        $nomeCorrigido = trim($matches[1]);
        
        // Se o número no nome corresponde ao prontuário, remover
        if ($numeroNoNome == $prontuario && strlen($nomeCorrigido) > 3) {
            $nome = $nomeCorrigido;
            $corrigido = true;
            $correcoesFinais++;
            
            if ($correcoesFinais <= 20) {
                echo "Correção FR#$correcoesFinais: Removido prontuário $numeroNoNome | '$nomeCorrigido'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleFinalReal, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleFinalReal);

echo "\n=== RELATÓRIO FINAL REAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais reais aplicadas: $correcoesFinais\n";
echo "Arquivo final real salvo em: $arquivoFinalReal\n";
echo "\nProcesso concluído com sucesso!\n";

?>
