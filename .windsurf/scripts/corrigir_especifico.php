<?php
/**
 * Corretor Específico - Linhas Quebradas com Quebra de Linha
 * Identifica e corrige linhas que foram quebradas incorretamente
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321.csv';
$arquivoCorrigido = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_ESPECIFICO.csv';

echo "Iniciando correção específica de linhas quebradas...\n";

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

echo "Processando linhas...\n";

$buffer = '';
$linhaAnterior = '';

while (($linha = fgets($handleOrigem)) !== false) {
    $linhaAtual++;
    
    // Limpar a linha
    $linha = trim($linha);
    
    // Se a linha estiver vazia, continuar
    if (empty($linha)) {
        continue;
    }
    
    // Verificar se a linha começa com número (padrão de nova linha quebrada)
    if (preg_match('/^[0-9]+;/', $linha)) {
        // Processar a linha anterior se houver algo no buffer
        if (!empty($buffer)) {
            $totalLinhas++;
            
            // Tentar parse do buffer acumulado
            $dadosCorrigidos = processarLinhaQuebrada($buffer);
            
            if ($dadosCorrigidos !== null) {
                fputcsv($handleCorrigido, $dadosCorrigidos, ';', '"', '\\');
                $linhasCorrigidas++;
                
                if ($linhasCorrigidas <= 5) {
                    echo "\n--- CORREÇÃO #$linhasCorrigidas ---\n";
                    echo "Buffer original: " . substr($buffer, 0, 200) . "...\n";
                    echo "Corrigido: " . implode(' | ', $dadosCorrigidos) . "\n";
                }
            }
            
            $buffer = '';
        }
        
        // Adicionar a linha atual ao buffer
        $buffer = $linha;
    } else {
        // Continuação da linha anterior
        $buffer .= ' ' . $linha;
    }
    
    if ($linhaAtual % 10000 == 0) {
        echo "Processadas: $linhaAtual linhas\n";
    }
}

// Processar o último buffer se houver
if (!empty($buffer)) {
    $totalLinhas++;
    $dadosCorrigidos = processarLinhaQuebrada($buffer);
    
    if ($dadosCorrigidos !== null) {
        fputcsv($handleCorrigido, $dadosCorrigidos, ';', '"', '\\');
        $linhasCorrigidas++;
    }
}

fclose($handleOrigem);
fclose($handleCorrigido);

echo "\n=== RELATÓRIO FINAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Linhas corrigidas: $linhasCorrigidas\n";
echo "Taxa de correção: " . number_format(($linhasCorrigidas / $totalLinhas) * 100, 2) . "%\n";
echo "Arquivo corrigido salvo em: $arquivoCorrigido\n";
echo "\nProcesso concluído com sucesso!\n";

function processarLinhaQuebrada($linhaCompleta) {
    // Limpar espaços extras
    $linhaCompleta = preg_replace('/\s+/', ' ', $linhaCompleta);
    
    // Tentar parse CSV
    $dados = str_getcsv($linhaCompleta, ';', '"', '\\');
    
    if (count($dados) != 5) {
        echo "Erro ao processar linha: " . substr($linhaCompleta, 0, 100) . "...\n";
        return null;
    }
    
    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;
    
    // Verificar padrões específicos de correção
    
    // Padrão 1: Nome contém "DECISÃO JUDICIAL" no final com espaços
    if (preg_match('/^(.+?)\s+(DECISÃO JUDICIAL|LIBERDADE PROVISÓRIA|PRISÃO.*|RECOLHIDO.*|PAGAMENTO.*|REVOGAÇÃO.*|RELAXAMENTO.*)$/', $nome, $matches)) {
        $nomeCorrigido = trim($matches[1]);
        $situacaoCorrigida = trim($matches[2]);
        
        // Verificar se a situação original é duplicata
        if (preg_match('/^[0-9]+\s+' . preg_quote($nomeCorrigido, '/') . '\s+' . preg_quote($situacaoCorrigida, '/') . '$/', $situacao)) {
            return [$id_unidade, $unidade, $prontuario, $nomeCorrigido, $situacaoCorrigida];
        }
    }
    
    // Padrão 2: Situação contém informações duplicadas
    if (preg_match('/^([0-9]+\s+[A-ZÀ-Ž\s]+?)\s{5,}([A-ZÀ-Ž\s]+)$/', $situacao, $matches)) {
        $parteCompleta = trim($matches[1]);
        $situacaoExtraida = trim($matches[2]);
        
        if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+)$/', $parteCompleta, $matches2)) {
            $prontuarioCorrigido = $matches2[1];
            $nomeCorrigido = $matches2[2];
            
            return [$id_unidade, $unidade, $prontuarioCorrigido, $nomeCorrigido, $situacaoExtraida];
        }
    }
    
    // Padrão 3: Nome começa com número
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)(?:\s+([A-ZÀ-Ž\s]+))?$/', $nome, $matches)) {
        $prontuarioCorrigido = $matches[1];
        $nomeCorrigido = trim($matches[2]);
        
        if (isset($matches[3]) && !empty($matches[3])) {
            $situacaoCorrigida = trim($matches[3]);
            return [$id_unidade, $unidade, $prontuarioCorrigido, $nomeCorrigido, $situacaoCorrigida];
        }
    }
    
    // Se não houver padrão específico, retornar original
    return [$id_unidade, $unidade, $prontuario, $nome, $situacao];
}

?>
