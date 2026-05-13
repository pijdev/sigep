<?php
/**
 * Corretor Específico para Erros Restantes
 * Corrige apenas os casos específicos identificados na validação
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_ABSOLUTO.csv';
$arquivoEspecifico = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_ESPECIFICO_FINAL.csv';

echo "Aplicando correção específica para erros restantes...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleEspecifico = fopen($arquivoEspecifico, 'w');

if (!$handleOrigem || !$handleEspecifico) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesEspecificas = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleEspecifico, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correções específicas...\n";

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
    
    // Correção 1: Situação começa com número (padrão principal)
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|DECURSO DE PRAZO|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        // Corrigir usando o nome extraído da situação
        $nome = $nomeNaSituacao;
        $situacao = $situacaoReal;
        $corrigido = true;
        $correcoesEspecificas++;
        
        if ($correcoesEspecificas <= 10) {
            echo "Correção #$correcoesEspecificas: $prontuarioNaSituacao | $nomeNaSituacao | $situacaoReal\n";
        }
    }
    
    // Correção 2: Situação contém padrão incompleto
    elseif (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(.+)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeNaSituacao = trim($matches[2]);
        $resto = trim($matches[3]);
        
        // Tentar identificar se o resto é uma situação válida
        $situacoesValidas = [
            'HABEAS CORPUS', 'ALVARÁ DE SOLTURA', 'EXTINÇÃO DA PENA', 'DECURSO DE PRAZO',
            'LIBERDADE PROVISÓRIA', 'DECISÃO JUDICIAL', 'PRISÃO DOMICILIAR',
            'PRISÃO ALBERGUE', 'RECOLHIDO', 'PAGAMENTO DA DÍVIDA', 'REVOGAÇÃO DE PRISÃO',
            'RELAXAMENTO DA PRISÃO', 'AUDIÊNCIA DE CUSTÓDIA'
        ];
        
        foreach ($situacoesValidas as $situacaoValida) {
            if (strpos(strtoupper($resto), $situacaoValida) !== false) {
                $nome = $nomeNaSituacao;
                $situacao = $situacaoValida;
                $corrigido = true;
                $correcoesEspecificas++;
                
                if ($correcoesEspecificas <= 10) {
                    echo "Correção #$correcoesEspecificas: $nomeNaSituacao | $situacaoValida\n";
                }
                break;
            }
        }
    }
    
    // Correção 3: Nomes inválidos como "REVOGAÇÃO DE", "RELAXAMENTO DA"
    if (in_array(strtoupper($nome), ['REVOGAÇÃO DE', 'RELAXAMENTO DA', 'PRISÃO', 'DECISÃO', 'LIBERDADE', 'PAGAMENTO'])) {
        // Tentar extrair nome correto de linhas anteriores ou próximas
        // Por enquanto, deixar como está e marcar para revisão manual
        $corrigido = false;
    }
    
    // Escrever linha processada
    fputcsv($handleEspecifico, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleEspecifico);

echo "\n=== RELATÓRIO DE CORREÇÕES ESPECÍFICAS ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções específicas aplicadas: $correcoesEspecificas\n";
echo "Arquivo específico salvo em: $arquivoEspecifico\n";
echo "\nProcesso concluído com sucesso!\n";

?>
