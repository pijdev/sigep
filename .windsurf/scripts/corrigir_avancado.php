<?php
/**
 * Corretor Avançado de CSV - RELATORIO_ESTADUAL_COMPLETO_304321.csv
 * Corrige entradas com espaços excessivos e problemas de formatação
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321.csv';
$arquivoCorrigido = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_FINAL.csv';

echo "Iniciando correção avançada do arquivo CSV...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleCorrigido = fopen($arquivoCorrigido, 'w');

if (!$handleOrigem || !$handleCorrigido) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$linhasCorrigidas = 0;
$linhaAtual = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleCorrigido, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção avançada...\n";

while (($linha = fgets($handleOrigem)) !== false) {
    $linhaAtual++;
    $totalLinhas++;
    
    if ($linhaAtual % 10000 == 0) {
        echo "Processadas: $linhaAtual linhas\n";
    }
    
    $dados = str_getcsv($linha, ';', '"', '\\');
    
    if (count($dados) != 5) {
        echo "Linha $linhaAtual: Número de colunas inválido (" . count($dados) . ")\n";
        continue;
    }
    
    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;
    
    $corrigido = false;
    $observacoes = '';
    
    // Limpar espaços em excesso
    $nomeOriginal = $nome;
    $situacaoOriginal = $situacao;
    
    // Detectar e corrigir problemas de espaços excessivos
    if (preg_match('/\s{5,}/', $nome)) {
        // Tentar extrair informações corretas do campo nome
        if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s{5,}(.+)$/', $nome, $matches)) {
            $prontuarioCorrigido = $matches[1];
            $nomeCorrigido = trim($matches[2]);
            $resto = trim($matches[3]);
            
            // Verificar se o resto contém a situação
            if (preg_match('/^[A-ZÀ-Ž\s]{10,}$/', $resto)) {
                $situacaoCorrigida = $resto;
                $prontuario = $prontuarioCorrigido;
                $nome = $nomeCorrigido;
                $situacao = $situacaoCorrigida;
                $corrigido = true;
                $observacoes = 'Extraído do campo nome';
            }
        }
    }
    
    // Detectar e corrigir problemas no campo situação
    if (!$corrigido && preg_match('/\s{5,}/', $situacao)) {
        // Padrão: "815399 ADEMIR MARTINS ... DECISÃO JUDICIAL"
        if (preg_match('/^([0-9]+\s+[A-ZÀ-Ž\s]+?)\s{5,}([A-ZÀ-Ž\s]+)$/', $situacao, $matches)) {
            $parteCompleta = trim($matches[1]);
            $situacaoExtraida = trim($matches[2]);
            
            // Extrair prontuário e nome
            if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+)$/', $parteCompleta, $matches2)) {
                $prontuarioCorrigido = $matches2[1];
                $nomeCorrigido = $matches2[2];
                $situacaoCorrigida = $situacaoExtraida;
                
                $prontuario = $prontuarioCorrigido;
                $nome = $nomeCorrigido;
                $situacao = $situacaoCorrigida;
                $corrigido = true;
                $observacoes = 'Extraído do campo situação';
            }
        }
    }
    
    // Verificar se o nome começa com número (padrão incorreto)
    if (!$corrigido && preg_match('/^[0-9]+\s+[A-ZÀ-Ž]/', $nome)) {
        if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)(?:\s+([A-ZÀ-Ž\s]+))?$/', $nome, $matches)) {
            $prontuarioCorrigido = $matches[1];
            $nomeCorrigido = trim($matches[2]);
            
            if (isset($matches[3]) && !empty($matches[3])) {
                $situacaoCorrigida = trim($matches[3]);
                $situacao = $situacaoCorrigida;
            }
            
            $prontuario = $prontuarioCorrigido;
            $nome = $nomeCorrigido;
            $corrigido = true;
            $observacoes = 'Nome começava com número';
        }
    }
    
    // Limpar espaços extras no final e início
    $nome = trim($nome);
    $situacao = trim($situacao);
    
    // Remover espaços múltiplos
    $nome = preg_replace('/\s+/', ' ', $nome);
    $situacao = preg_replace('/\s+/', ' ', $situacao);
    
    if ($corrigido) {
        $linhasCorrigidas++;
        
        // Mostrar alguns exemplos
        if ($linhasCorrigidas <= 10) {
            echo "\n--- CORREÇÃO #$linhasCorrigidas ---\n";
            echo "Linha: $linhaAtual\n";
            echo "Original: $prontuario | $nomeOriginal | $situacaoOriginal\n";
            echo "Corrigido: $prontuario | $nome | $situacao\n";
            echo "Obs: $observacoes\n";
        }
    }
    
    // Escrever linha processada
    fputcsv($handleCorrigido, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleCorrigido);

echo "\n=== RELATÓRIO FINAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Linhas corrigidas: $linhasCorrigidas\n";
echo "Taxa de correção: " . number_format(($linhasCorrigidas / $totalLinhas) * 100, 2) . "%\n";
echo "Arquivo corrigido salvo em: $arquivoCorrigido\n";
echo "\nProcesso concluído com sucesso!\n";

?>
