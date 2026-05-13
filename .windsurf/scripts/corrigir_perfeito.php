<?php
/**
 * Corretor FINAL - Últimos 32 Erros
 * Corrige os últimos casos restantes
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_SUPER_FINAL.csv';
$arquivoPerfeito = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_PERFEITO.csv';

echo "Aplicando correção final - últimos 32 erros...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handlePerfeito = fopen($arquivoPerfeito, 'w');

if (!$handleOrigem || !$handlePerfeito) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesFinais = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handlePerfeito, $cabecalhoDados, ';', '"', '\\');
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
    
    // Lista FINAL de situações (incluindo as novas)
    $situacoesFinais = [
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
        'MORTE', 'CONVERSÃO DA PENA PRIVATIVA DE LIBERDADE EM RESTRITIVA DE DIREITOS',
        'EXCESSO DE PRAZO', 'TRABALHO INTERNO', 'MONITORAMENTO ELETRÔNICO'
    ];
    
    // Correção FINAL: Situação começa com número
    if (preg_match('/^[0-9]/', $situacao)) {
        foreach ($situacoesFinais as $situacaoFinal) {
            if (strpos($situacao, $situacaoFinal) !== false) {
                $partes = explode($situacaoFinal, $situacao);
                if (count($partes) >= 2) {
                    $parteNome = trim($partes[0]);
                    // Remover número do início
                    $nomeExtraido = preg_replace('/^[0-9]+\s+/', '', $parteNome);
                    
                    if (!empty($nomeExtraido) && strlen($nomeExtraido) > 3) {
                        $nome = $nomeExtraido;
                        $situacao = $situacaoFinal;
                        $corrigido = true;
                        $correcoesFinais++;
                        
                        if ($correcoesFinais <= 20) {
                            echo "Correção P#$correcoesFinais: '$nomeExtraido' | '$situacaoFinal'\n";
                        }
                        break;
                    }
                }
            }
        }
    }
    
    // Correção FINAL 2: Nome começa com número
    if (ctype_digit(substr($nome, 0, 1))) {
        $posPrimeiroEspaco = strpos($nome, ' ');
        if ($posPrimeiroEspaco !== false) {
            $nomeCorrigido = trim(substr($nome, $posPrimeiroEspaco + 1));
            
            if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
                $nome = $nomeCorrigido;
                $corrigido = true;
                $correcoesFinais++;
                
                if ($correcoesFinais <= 20) {
                    echo "Correção P#$correcoesFinais: Nome '$nomeCorrigido'\n";
                }
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handlePerfeito, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handlePerfeito);

echo "\n=== RELATÓRIO FINAL PERFEITO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais aplicadas: $correcoesFinais\n";
echo "Arquivo perfeito salvo em: $arquivoPerfeito\n";
echo "\nProcesso concluído com sucesso!\n";

?>
