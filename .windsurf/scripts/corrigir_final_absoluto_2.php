<?php
/**
 * Corretor Final Absoluto - Últimos Erros
 * Corrige os últimos casos restantes
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_MANUAL.csv';
$arquivoFinalAbsoluto = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_ABSOLUTO.csv';

echo "Aplicando correção final absoluta - últimos erros...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleFinalAbsoluto = fopen($arquivoFinalAbsoluto, 'w');

if (!$handleOrigem || !$handleFinalAbsoluto) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesFinais = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleFinalAbsoluto, $cabecalhoDados, ';', '"', '\\');
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
    
    // Lista COMPLETA de situações possíveis (incluindo as novas)
    $todasSituacoes = [
        'DECURSO DE PRAZO', 'HABEAS CORPUS', 'ALVARÁ DE SOLTURA', 'EXTINÇÃO DA PENA',
        'LIBERDADE PROVISÓRIA', 'DECISÃO JUDICIAL', 'PRISÃO DOMICILIAR', 'PRISÃO ALBERGUE',
        'RECOLHIDO', 'PAGAMENTO DA DÍVIDA', 'REVOGAÇÃO DE PRISÃO', 'RELAXAMENTO DA PRISÃO',
        'TÉRMINO DA PENA', 'TRANSFERÊNCIA PARA OUTRO ESTADO', 'CONVERSÃO DA PENA EM MULTA',
        'RESTITUIÇÃO DE LIBERDADE', 'SUSPENSÃO CONDICIONAL DO PROCESSO', 'AUDIÊNCIA DE CUSTÓDIA',
        'CUMPRIMENTO DE PENA', 'PROGRESSÃO DE REGIME', 'REGRESSÃO DE REGIME',
        'LIVRAMENTO CONDICIONAL', 'REMISSÃO DA PENA', 'INDULTO', 'ANISTIA',
        'ABOLITIO CRIMINIS', 'PRESCRIÇÃO', 'DEPURAÇÃO', 'REJEIÇÃO DE DENÚNCIA',
        'RECEBIMENTO DE DENÚNCIA', 'CONDENAÇÃO', 'ABSOLVIÇÃO',
        'IMPOSIÇÃO DE MEDIDA DE SEGURANÇA', 'SUBSTITUIÇÃO DE PENA',
        'SUSPENSÃO CONDICIONAL DA PENA', 'PRESTAÇÃO DE SERVIÇOS',
        'LIMITAÇÃO DE FIM DE SEMANA', 'MULTA', 'ADVERTÊNCIA', 'REPRIMENDA',
        'EXTINÇÃO DE PUNIBILIDADE', 'REVOGAÇÃO DA PRISÃO TEMPORÁRIA', 'TÉRMINO DA PRISÃO TEMPORÁRIA',
        'MORTE'
    ];
    
    // Correção FINAL ABSOLUTA: Situação começa com número
    if (preg_match('/^[0-9]/', $situacao)) {
        foreach ($todasSituacoes as $situacaoConhecida) {
            if (strpos($situacao, $situacaoConhecida) !== false) {
                $partes = explode($situacaoConhecida, $situacao);
                if (count($partes) >= 2) {
                    $parteNome = trim($partes[0]);
                    // Remover número do início
                    $nomeExtraido = preg_replace('/^[0-9]+\s+/', '', $parteNome);
                    
                    if (!empty($nomeExtraido) && strlen($nomeExtraido) > 3) {
                        $nome = $nomeExtraido;
                        $situacao = $situacaoConhecida;
                        $corrigido = true;
                        $correcoesFinais++;
                        
                        if ($correcoesFinais <= 20) {
                            echo "Correção FA#$correcoesFinais: '$nomeExtraido' | '$situacaoConhecida'\n";
                        }
                        break;
                    }
                }
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleFinalAbsoluto, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleFinalAbsoluto);

echo "\n=== RELATÓRIO FINAL ABSOLUTO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais absolutas aplicadas: $correcoesFinais\n";
echo "Arquivo final absoluto salvo em: $arquivoFinalAbsoluto\n";
echo "\nProcesso concluído com sucesso!\n";

?>
