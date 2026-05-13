<?php
/**
 * Corretor Final Absoluto - Casos Restantes
 * Corrige os últimos casos onde a situação começa com número
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL.csv';
$arquivoFinalAbsoluto = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_ABSOLUTO.csv';

echo "Aplicando correção final absoluta...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleFinal = fopen($arquivoFinalAbsoluto, 'w');

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

echo "Processando linhas finais...\n";

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
    
    // Correção FINAL: Situação começa com número + nome + situação real
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|DECURSO DE PRAZO|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO.*|RECOLHIDO.*|PAGAMENTO.*|REVOGAÇÃO.*|RELAXAMENTO.*|REVOGAÇÃO.*|AUDIÊNCIA.*|CUMPRIMENTO.*|CONVERSÃO.*|PROGRESSÃO.*|REGRESSÃO.*|LIVRAMENTO.*|REMISSÃO.*|INDULTO.*|ANISTIA.*|ABOLITIO.*|PRESCRIÇÃO.*|DEPURAÇÃO.*|REJEIÇÃO.*|RECEBIMENTO.*|DENÚNCIA.*|CONDENAÇÃO.*|ABSOLVIÇÃO.*|IMPOSIÇÃO.*|SUBSTITUIÇÃO.*|SUSPENSÃO.*|INTERDIÇÃO.*|PRESTAÇÃO.*|PRESTAÇÃO.*|LIMITAÇÃO.*|MULTA.*|ADVERTÊNCIA.*|REPRIMENDA.*)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        // Se o nome na situação for muito diferente do nome atual, pode ser que o nome atual esteja errado
        if (strlen($nomeNaSituacao) > 5 && !preg_match('/\b' . preg_quote(substr($nomeNaSituacao, 0, 10), '/') . '\b/', $nome)) {
            // Provavelmente o nome atual está incompleto ou errado
            // Usar o nome extraído da situação
            $nome = $nomeNaSituacao;
            $situacao = $situacaoReal;
            $corrigido = true;
            $correcoesFinais++;
            
            if ($correcoesFinais <= 10) {
                echo "Correção #$correcoesFinais: Nome corrigido para '$nomeNaSituacao', situação '$situacaoReal'\n";
            }
        } else {
            // A situação está correta, apenas extrair a situação real
            $situacao = $situacaoReal;
            $corrigido = true;
            $correcoesFinais++;
            
            if ($correcoesFinais <= 10) {
                echo "Correção #$correcoesFinais: Situação corrigida para '$situacaoReal'\n";
            }
        }
    }
    
    // Correção adicional: Situação contém padrão "NOME SITUAÇÃO"
    elseif (preg_match('/^([A-ZÀ-Ž\s]{5,})\s+(HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|DECURSO DE PRAZO|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO.*|RECOLHIDO.*|PAGAMENTO.*|REVOGAÇÃO.*|RELAXAMENTO.*)$/i', $situacao, $matches)) {
        $nomeExtraido = trim($matches[1]);
        $situacaoReal = trim($matches[2]);
        
        // Se o nome extraído for razoavelmente diferente do nome atual
        if (strlen($nomeExtraido) > 5 && !preg_match('/\b' . preg_quote(substr($nomeExtraido, 0, 8), '/') . '\b/', $nome)) {
            $nome = $nomeExtraido;
            $situacao = $situacaoReal;
            $corrigido = true;
            $correcoesFinais++;
            
            if ($correcoesFinais <= 10) {
                echo "Correção #$correcoesFinais: Nome extraído '$nomeExtraido', situação '$situacaoReal'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleFinal, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleFinal);

echo "\n=== RELATÓRIO FINAL ABSOLUTO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais aplicadas: $correcoesFinais\n";
echo "Arquivo final absoluto salvo em: $arquivoFinalAbsoluto\n";
echo "\nProcesso concluído com sucesso!\n";

?>
