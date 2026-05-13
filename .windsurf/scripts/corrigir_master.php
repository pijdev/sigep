<?php
/**
 * Corretor Final Master - Todos os Padrões
 * Corrige todos os padrões restantes de uma vez
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_PROCESSADO.csv';
$arquivoMaster = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_MASTER.csv';

echo "Aplicando correção master final...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleMaster = fopen($arquivoMaster, 'w');

if (!$handleOrigem || !$handleMaster) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesMaster = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleMaster, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção master...\n";

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
    
    // Correção MASTER 1: Nome contém situação no final
    if (preg_match('/^(.+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $nome, $matches)) {
        
        $nomeCorrigido = trim($matches[1]);
        $situacaoExtraida = trim($matches[2]);
        
        // Verificar se a situação atual é duplicata
        if (preg_match('/^[0-9]+\s+' . preg_quote($nome, '/') . '$/', $situacao)) {
            $nome = $nomeCorrigido;
            $situacao = $situacaoExtraida;
            $corrigido = true;
            $correcoesMaster++;
            
            if ($correcoesMaster <= 15) {
                echo "Correção M#$correcoesMaster: Nome='$nomeCorrigido', Situação='$situacaoExtraida'\n";
            }
        }
    }
    
    // Correção MASTER 2: Situação começa com número e contém nome duplicado
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        // Se o nome na situação corresponde ao nome atual (ou contém o nome atual)
        if (strpos($nomeNaSituacao, $nome) !== false || strpos($nome, $nomeNaSituacao) !== false) {
            $nome = $nomeNaSituacao;
            $situacao = $situacaoReal;
            $corrigido = true;
            $correcoesMaster++;
            
            if ($correcoesMaster <= 15) {
                echo "Correção M#$correcoesMaster: Nome='$nomeNaSituacao', Situação='$situacaoReal'\n";
            }
        }
    }
    
    // Correção MASTER 3: Situação é apenas "PRONTUARIO NOME"
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+)$/i', $situacao, $matches)) {
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        
        // Se o nome atual contém uma situação válida, extrair
        if (preg_match('/^(.+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $nome, $matchesNome)) {
            $nomeCorrigido = trim($matchesNome[1]);
            $situacaoExtraida = trim($matchesNome[2]);
            
            $nome = $nomeCorrigido;
            $situacao = $situacaoExtraida;
            $corrigido = true;
            $correcoesMaster++;
            
            if ($correcoesMaster <= 15) {
                echo "Correção M#$correcoesMaster: Extraído do nome - '$nomeCorrigido', '$situacaoExtraida'\n";
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleMaster, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleMaster);

echo "\n=== RELATÓRIO MASTER FINAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções master aplicadas: $correcoesMaster\n";
echo "Arquivo master salvo em: $arquivoMaster\n";
echo "\nProcesso concluído com sucesso!\n";

?>
