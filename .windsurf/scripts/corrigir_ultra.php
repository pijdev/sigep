<?php
/**
 * Corretor Ultra-Específico
 * Corrige o padrão exato: "NOME SITUACAO" | "PRONTUARIO NOME SITUACAO"
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_PROCESSADO.csv';
$arquivoUltra = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_ULTRA.csv';

echo "Aplicando correção ultra-específica...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleUltra = fopen($arquivoUltra, 'w');

if (!$handleOrigem || !$handleUltra) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesUltra = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleUltra, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção ultra-específica...\n";

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
    
    // Correção ULTRA: Nome contém situação no final E situação duplica
    if (preg_match('/^(.+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $nome, $matches)) {
        
        $nomeCorrigido = trim($matches[1]);
        $situacaoExtraida = trim($matches[2]);
        
        // Verificar se a situação atual contém o nome completo
        if (preg_match('/^[0-9]+\s+' . preg_quote($nome, '/') . '$/', $situacao)) {
            $nome = $nomeCorrigido;
            $situacao = $situacaoExtraida;
            $corrigido = true;
            $correcoesUltra++;
            
            if ($correcoesUltra <= 20) {
                echo "Correção U#$correcoesUltra: '$nomeCorrigido' | '$situacaoExtraida'\n";
            }
        }
        // Verificar se a situação atual contém o padrão "PRONTUARIO NOME SITUACAO"
        elseif (preg_match('/^([0-9]+)\s+' . preg_quote($nome, '/') . '$/', $situacao, $matchesSituacao)) {
            $nome = $nomeCorrigido;
            $situacao = $situacaoExtraida;
            $corrigido = true;
            $correcoesUltra++;
            
            if ($correcoesUltra <= 20) {
                echo "Correção U#$correcoesUltra: '$nomeCorrigido' | '$situacaoExtraida'\n";
            }
        }
    }
    
    // Correção ULTRA 2: Situação começa com número e contém nome + situação
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        // Se o nome atual contém o mesmo padrão, corrigir
        if (preg_match('/^' . preg_quote($nomeNaSituacao, '/') . '\s+(' . preg_quote($situacaoReal, '/') . ')$/i', $nome)) {
            $nome = $nomeNaSituacao;
            $situacao = $situacaoReal;
            $corrigido = true;
            $correcoesUltra++;
            
            if ($correcoesUltra <= 20) {
                echo "Correção U#$correcoesUltra: '$nomeNaSituacao' | '$situacaoReal'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleUltra, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleUltra);

echo "\n=== RELATÓRIO ULTRA-ESPECÍFICO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções ultra aplicadas: $correcoesUltra\n";
echo "Arquivo ultra salvo em: $arquivoUltra\n";
echo "\nProcesso concluído com sucesso!\n";

?>
