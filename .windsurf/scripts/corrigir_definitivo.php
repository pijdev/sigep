<?php
/**
 * Corretor Definitivo - Padrão Exato
 * Corrige o padrão específico: "NOME SITUACAO" | "PRONTUARIO NOME SITUACAO"
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_ABSOLUTO.csv';
$arquivoDefinitivo = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_DEFINITIVO.csv';

echo "Aplicando correção definitiva - padrão exato...\n";

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

echo "Processando linhas com correção definitiva...\n";

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
    
    // Correção DEFINITIVA: Situação contém "PRONTUARIO NOME SITUACAO"
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeExtraido = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        // Aplicar correção
        $nome = $nomeExtraido;
        $situacao = $situacaoReal;
        $corrigido = true;
        $correcoesDefinitivas++;
        
        if ($correcoesDefinitivas <= 15) {
            echo "Correção #$correcoesDefinitivas: $prontuarioNaSituacao | $nomeExtraido | $situacaoReal\n";
        }
    }
    
    // Correção 2: Nome contém situação no final
    elseif (preg_match('/^(.+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $nome, $matches)) {
        
        $nomeCorrigido = trim($matches[1]);
        $situacaoExtraida = trim($matches[2]);
        
        // Verificar se a situação atual é duplicata
        if (preg_match('/^[0-9]+\s+' . preg_quote($nomeCorrigido, '/') . '\s+' . preg_quote($situacaoExtraida, '/') . '$/', $situacao)) {
            $nome = $nomeCorrigido;
            $situacao = $situacaoExtraida;
            $corrigido = true;
            $correcoesDefinitivas++;
            
            if ($correcoesDefinitivas <= 15) {
                echo "Correção #$correcoesDefinitivas: Nome corrigido para '$nomeCorrigido', situação '$situacaoExtraida'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleDefinitivo, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleDefinitivo);

echo "\n=== RELATÓRIO DEFINITIVO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções definitivas aplicadas: $correcoesDefinitivas\n";
echo "Arquivo definitivo salvo em: $arquivoDefinitivo\n";
echo "\nProcesso concluído com sucesso!\n";

?>
