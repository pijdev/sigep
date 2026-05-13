<?php
/**
 * Corretor Simples e Direto
 * Abordagem simples para corrigir os casos específicos
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_PROCESSADO.csv';
$arquivoSimples = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_SIMPLES.csv';

echo "Aplicando correção simples e direta...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleSimples = fopen($arquivoSimples, 'w');

if (!$handleOrigem || !$handleSimples) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesSimples = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleSimples, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção simples...\n";

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
    
    // Abordagem SIMPLES: Verificar padrões conhecidos
    
    // Padrão 1: Nome termina com "DECISÃO JUDICIAL"
    if (strpos($nome, ' DECISÃO JUDICIAL') !== false) {
        $nomeCorrigido = str_replace(' DECISÃO JUDICIAL', '', $nome);
        
        // Se a situação contém o nome completo, corrigir
        if (strpos($situacao, $nome) !== false) {
            $nome = trim($nomeCorrigido);
            $situacao = 'DECISÃO JUDICIAL';
            $corrigido = true;
            $correcoesSimples++;
            
            if ($correcoesSimples <= 20) {
                echo "Correção S#$correcoesSimples: DECISÃO JUDICIAL - '$nome'\n";
            }
        }
    }
    
    // Padrão 2: Nome termina com "LIBERDADE PROVISÓRIA"
    if (strpos($nome, ' LIBERDADE PROVISÓRIA') !== false) {
        $nomeCorrigido = str_replace(' LIBERDADE PROVISÓRIA', '', $nome);
        
        if (strpos($situacao, $nome) !== false) {
            $nome = trim($nomeCorrigido);
            $situacao = 'LIBERDADE PROVISÓRIA';
            $corrigido = true;
            $correcoesSimples++;
            
            if ($correcoesSimples <= 20) {
                echo "Correção S#$correcoesSimples: LIBERDADE PROVISÓRIA - '$nome'\n";
            }
        }
    }
    
    // Padrão 3: Nome termina com outras situações
    $situacoesConhecidas = [
        'DECURSO DE PRAZO',
        'HABEAS CORPUS',
        'ALVARÁ DE SOLTURA',
        'EXTINÇÃO DA PENA',
        'PRISÃO DOMICILIAR',
        'PRISÃO ALBERGUE',
        'RECOLHIDO',
        'PAGAMENTO DA DÍVIDA',
        'REVOGAÇÃO DE PRISÃO',
        'RELAXAMENTO DA PRISÃO'
    ];
    
    foreach ($situacoesConhecidas as $situacaoConhecida) {
        if (strpos($nome, ' ' . $situacaoConhecida) !== false) {
            $nomeCorrigido = str_replace(' ' . $situacaoConhecida, '', $nome);
            
            if (strpos($situacao, $nome) !== false) {
                $nome = trim($nomeCorrigido);
                $situacao = $situacaoConhecida;
                $corrigido = true;
                $correcoesSimples++;
                
                if ($correcoesSimples <= 20) {
                    echo "Correção S#$correcoesSimples: $situacaoConhecida - '$nome'\n";
                }
                break;
            }
        }
    }
    
    // Padrão 4: Situação começa com número
    if (preg_match('/^[0-9]/', $situacao)) {
        // Tentar extrair do padrão "NUMERO NOME SITUACAO"
        foreach ($situacoesConhecidas as $situacaoConhecida) {
            if (strpos($situacao, $situacaoConhecida) !== false) {
                $partes = explode(' ' . $situacaoConhecida, $situacao);
                if (count($partes) == 2) {
                    $parteNome = trim($partes[0]);
                    // Remover número do início
                    $nomeExtraido = preg_replace('/^[0-9]+\s+/', '', $parteNome);
                    
                    if (!empty($nomeExtraido)) {
                        $nome = $nomeExtraido;
                        $situacao = $situacaoConhecida;
                        $corrigido = true;
                        $correcoesSimples++;
                        
                        if ($correcoesSimples <= 20) {
                            echo "Correção S#$correcoesSimples: Extraído '$nomeExtraido' | '$situacaoConhecida'\n";
                        }
                        break;
                    }
                }
            }
        }
    }
    
    // Escrever linha processada
    fputcsv($handleSimples, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleSimples);

echo "\n=== RELATÓRIO SIMPLES ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções simples aplicadas: $correcoesSimples\n";
echo "Arquivo simples salvo em: $arquivoSimples\n";
echo "\nProcesso concluído com sucesso!\n";

?>
