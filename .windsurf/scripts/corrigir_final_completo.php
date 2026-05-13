<?php
/**
 * Corretor Final - Todas as Situações
 * Corrige todos os padrões restantes incluindo novas situações
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_SIMPLES.csv';
$arquivoFinal = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_CORRIGIDO.csv';

echo "Aplicando correção final - todas as situações...\n";

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

echo "Processando linhas com correção final...\n";

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
    
    // Lista COMPLETA de situações possíveis
    $todasSituacoes = [
        'DECURSO DE PRAZO',
        'HABEAS CORPUS',
        'ALVARÁ DE SOLTURA',
        'EXTINÇÃO DA PENA',
        'LIBERDADE PROVISÓRIA',
        'DECISÃO JUDICIAL',
        'PRISÃO DOMICILIAR',
        'PRISÃO ALBERGUE',
        'RECOLHIDO',
        'PAGAMENTO DA DÍVIDA',
        'REVOGAÇÃO DE PRISÃO',
        'RELAXAMENTO DA PRISÃO',
        'TÉRMINO DA PENA',
        'TRANSFERÊNCIA PARA OUTRO ESTADO',
        'CONVERSÃO DA PENA EM MULTA',
        'RESTITUIÇÃO DE LIBERDADE',
        'SUSPENSÃO CONDICIONAL DO PROCESSO',
        'AUDIÊNCIA DE CUSTÓDIA',
        'CUMPRIMENTO DE PENA',
        'PROGRESSÃO DE REGIME',
        'REGRESSÃO DE REGIME',
        'LIVRAMENTO CONDICIONAL',
        'REMISSÃO DA PENA',
        'INDULTO',
        'ANISTIA',
        'ABOLITIO CRIMINIS',
        'PRESCRIÇÃO',
        'DEPURAÇÃO',
        'REJEIÇÃO DE DENÚNCIA',
        'RECEBIMENTO DE DENÚNCIA',
        'CONDENAÇÃO',
        'ABSOLVIÇÃO',
        'IMPOSIÇÃO DE MEDIDA DE SEGURANÇA',
        'SUBSTITUIÇÃO DE PENA',
        'SUSPENSÃO CONDICIONAL DA PENA',
        'PRESTAÇÃO DE SERVIÇOS',
        'LIMITAÇÃO DE FIM DE SEMANA',
        'MULTA',
        'ADVERTÊNCIA',
        'REPRIMENDA'
    ];
    
    // Correção FINAL: Situação começa com número
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
                            echo "Correção F#$correcoesFinais: '$nomeExtraido' | '$situacaoConhecida'\n";
                        }
                        break;
                    }
                }
            }
        }
    }
    
    // Correção FINAL 2: Nome contém situação no final
    foreach ($todasSituacoes as $situacaoConhecida) {
        if (strpos($nome, ' ' . $situacaoConhecida) !== false) {
            $nomeCorrigido = str_replace(' ' . $situacaoConhecida, '', $nome);
            
            // Verificar se a situação atual contém referência ao nome
            if (strpos($situacao, $nome) !== false || preg_match('/^[0-9]/', $situacao)) {
                $nome = trim($nomeCorrigido);
                $situacao = $situacaoConhecida;
                $corrigido = true;
                $correcoesFinais++;
                
                if ($correcoesFinais <= 20) {
                    echo "Correção F#$correcoesFinais: Nome '$nomeCorrigido' | '$situacaoConhecida'\n";
                }
                break;
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleFinal, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleFinal);

echo "\n=== RELATÓRIO FINAL COMPLETO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais aplicadas: $correcoesFinais\n";
echo "Arquivo final salvo em: $arquivoFinal\n";
echo "\nProcesso concluído com sucesso!\n";

?>
