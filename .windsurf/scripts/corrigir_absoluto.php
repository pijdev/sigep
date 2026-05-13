<?php
/**
 * Corretor FINAL ABSOLUTO - Nomes com Número
 * Corrige nomes que começam com número
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_CORRIGIDO.csv';
$arquivoAbsoluto = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_ABSOLUTO.csv';

echo "Aplicando correção final absoluta - nomes com número...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleAbsoluto = fopen($arquivoAbsoluto, 'w');

if (!$handleOrigem || !$handleAbsoluto) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesAbsolutas = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleAbsoluto, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção final absoluta...\n";

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
    
    // Correção ABSOLUTA: Nome começa com número
    if (preg_match('/^[0-9]+\s+([A-ZÀ-Ž\s]+)$/i', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        
        // Verificar se o nome extraído é razoável (mais de 3 caracteres)
        if (strlen($nomeCorrigido) > 3) {
            $nome = $nomeCorrigido;
            $corrigido = true;
            $correcoesAbsolutas++;
            
            if ($correcoesAbsolutas <= 20) {
                echo "Correção A#$correcoesAbsolutas: '$nomeCorrigido'\n";
            }
        }
    }
    
    // Correção ABSOLUTA 2: Nome contém número no início
    if (preg_match('/^([0-9]+\s+[A-ZÀ-Ž\s]+?)\s+(.+)$/i', $nome, $matches)) {
        $parteComNumero = trim($matches[1]);
        $resto = trim($matches[2]);
        
        // Remover número da parte com número
        $nomeSemNumero = preg_replace('/^[0-9]+\s+/', '', $parteComNumero);
        
        // Verificar se o resto parece com uma situação
        $situacoesPossiveis = [
            'TÉRMINO DA PENA', 'TRANSFERÊNCIA', 'CONVERSÃO', 'RESTITUIÇÃO', 'SUSPENSÃO'
        ];
        
        $ehSituacao = false;
        foreach ($situacoesPossiveis as $situacaoPossivel) {
            if (strpos(strtoupper($resto), $situacaoPossivel) !== false) {
                $ehSituacao = true;
                break;
            }
        }
        
        if ($ehSituacao && strlen($nomeSemNumero) > 3) {
            $nome = $nomeSemNumero;
            $corrigido = true;
            $correcoesAbsolutas++;
            
            if ($correcoesAbsolutas <= 20) {
                echo "Correção A#$correcoesAbsolutas: '$nomeSemNumero' (removido situação)\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleAbsoluto, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleAbsoluto);

echo "\n=== RELATÓRIO FINAL ABSOLUTO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções absolutas aplicadas: $correcoesAbsolutas\n";
echo "Arquivo absoluto salvo em: $arquivoAbsoluto\n";
echo "\nProcesso concluído com sucesso!\n";

?>
