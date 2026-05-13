<?php
/**
 * Corretor Final - Casos Específicos
 * Corrige os casos restantes de linhas mal formatadas
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_ESPECIFICO.csv';
$arquivoFinal = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL.csv';

echo "Aplicando correção final nos casos específicos...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleFinal = fopen($arquivoFinal, 'w');

if (!$handleOrigem || !$handleFinal) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesFinais = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleFinal, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

while (($linha = fgets($handleOrigem)) !== false) {
    $totalLinhas++;
    
    $dados = str_getcsv($linha, ';', '"', '\\');
    
    if (count($dados) != 5) {
        continue;
    }
    
    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;
    
    $corrigido = false;
    
    // Correção 1: Nome contém número + nome + situação
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+([A-ZÀ-Ž\s]+)$/', $nome, $matches)) {
        $prontuarioNovo = $matches[1];
        $nomeCorrigido = trim($matches[2]);
        $situacaoNova = trim($matches[3]);
        
        // Verificar se a situação atual é duplicata
        if (preg_match('/^' . preg_quote($prontuarioNovo, '/') . '\s+' . preg_quote($nomeCorrigido, '/') . '\s+' . preg_quote($situacaoNova, '/') . '$/', $situacao)) {
            $prontuario = $prontuarioNovo;
            $nome = $nomeCorrigido;
            $situacao = $situacaoNova;
            $corrigido = true;
            $correcoesFinais++;
            
            echo "Correção #$correcoesFinais: $prontuarioNovo | $nomeCorrigido | $situacaoNova\n";
        }
    }
    
    // Correção 2: Nome contém situação no final
    elseif (preg_match('/^(.+?)\s+(DECISÃO JUDICIAL|LIBERDADE PROVISÓRIA|PRISÃO.*|RECOLHIDO.*|PAGAMENTO.*|REVOGAÇÃO.*|RELAXAMENTO.*)$/', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        $situacaoNova = trim($matches[2]);
        
        $nome = $nomeCorrigido;
        $situacao = $situacaoNova;
        $corrigido = true;
        $correcoesFinais++;
        
        echo "Correção #$correcoesFinais: Nome corrigido para '$nomeCorrigido', situação '$situacaoNova'\n";
    }
    
    // Correção 3: Situação contém duplicata de prontuário + nome
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+([A-ZÀ-Ž\s]+)$/', $situacao, $matches)) {
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        // Se o prontuario e nome na situação coincidirem, usar a situação real
        if ($prontuarioNaSituacao == $prontuario && strpos($nomeNaSituacao, $nome) !== false) {
            $situacao = $situacaoReal;
            $corrigido = true;
            $correcoesFinais++;
            
            echo "Correção #$correcoesFinais: Situação corrigida para '$situacaoReal'\n";
        }
    }
    
    // Escrever linha processada
    fputcsv($handleFinal, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleFinal);

echo "\n=== RELATÓRIO FINAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais aplicadas: $correcoesFinais\n";
echo "Arquivo final salvo em: $arquivoFinal\n";
echo "\nProcesso concluído com sucesso!\n";

?>
