<?php
/**
 * Processador Direto do Arquivo Original
 * Processa o arquivo original e corrige todos os problemas de uma vez
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOriginal = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321.csv';
$arquivoProcessado = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_PROCESSADO.csv';

echo "Processando arquivo original diretamente...\n";

if (!file_exists($arquivoOriginal)) {
    die("Arquivo original não encontrado: $arquivoOriginal\n");
}

$handleOriginal = fopen($arquivoOriginal, 'r');
$handleProcessado = fopen($arquivoProcessado, 'w');

if (!$handleOriginal || !$handleProcessado) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$linhasCorrigidas = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOriginal);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleProcessado, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas do arquivo original...\n";

$buffer = '';

while (($linha = fgets($handleOriginal)) !== false) {
    $linha = trim($linha);
    
    // Se a linha estiver vazia, continuar
    if (empty($linha)) {
        continue;
    }
    
    // Verificar se a linha começa com número (nova entrada)
    if (preg_match('/^[0-9]+;/', $linha)) {
        // Processar o buffer anterior se houver
        if (!empty($buffer)) {
            $resultado = processarLinhaCompleta($buffer);
            if ($resultado !== null) {
                fputcsv($handleProcessado, $resultado, ';', '"', '\\');
                $totalLinhas++;
                if ($resultado[4] !== $buffer) { // se a situação foi corrigida
                    $linhasCorrigidas++;
                    if ($linhasCorrigidas <= 15) {
                        echo "Correção #$linhasCorrigidas: " . implode(' | ', array_slice($resultado, 2)) . "\n";
                    }
                }
            }
        }
        $buffer = $linha;
    } else {
        // Continuação da linha anterior
        $buffer .= ' ' . $linha;
    }
}

// Processar o último buffer
if (!empty($buffer)) {
    $resultado = processarLinhaCompleta($buffer);
    if ($resultado !== null) {
        fputcsv($handleProcessado, $resultado, ';', '"', '\\');
        $totalLinhas++;
    }
}

fclose($handleOriginal);
fclose($handleProcessado);

echo "\n=== RELATÓRIO DE PROCESSAMENTO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Linhas corrigidas: $linhasCorrigidas\n";
echo "Arquivo processado salvo em: $arquivoProcessado\n";
echo "\nProcesso concluído com sucesso!\n";

function processarLinhaCompleta($linhaCompleta) {
    // Limpar espaços excessivos
    $linhaCompleta = preg_replace('/\s+/', ' ', $linhaCompleta);
    
    // Parse CSV
    $dados = str_getcsv($linhaCompleta, ';', '"', '\\');
    
    if (count($dados) != 5) {
        return null;
    }
    
    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;
    
    // Correção 1: Situação contém "PRONTUARIO NOME SITUACAO"
    if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $situacao, $matches)) {
        
        $prontuarioNaSituacao = $matches[1];
        $nomeExtraido = trim($matches[2]);
        $situacaoReal = trim($matches[3]);
        
        return [$id_unidade, $unidade, $prontuario, $nomeExtraido, $situacaoReal];
    }
    
    // Correção 2: Nome contém situação no final
    if (preg_match('/^(.+?)\s+(DECURSO DE PRAZO|HABEAS CORPUS|ALVARÁ DE SOLTURA|EXTINÇÃO DA PENA|LIBERDADE PROVISÓRIA|DECISÃO JUDICIAL|PRISÃO DOMICILIAR|PRISÃO ALBERGUE|RECOLHIDO|PAGAMENTO DA DÍVIDA|REVOGAÇÃO DE PRISÃO|RELAXAMENTO DA PRISÃO)$/i', $nome, $matches)) {
        
        $nomeCorrigido = trim($matches[1]);
        $situacaoExtraida = trim($matches[2]);
        
        // Verificar se a situação atual é duplicata
        if (preg_match('/^[0-9]+\s+' . preg_quote($nomeCorrigido, '/') . '\s+' . preg_quote($situacaoExtraida, '/') . '$/', $situacao)) {
            return [$id_unidade, $unidade, $prontuario, $nomeCorrigido, $situacaoExtraida];
        }
    }
    
    // Correção 3: Situação começa com número
    if (preg_match('/^[0-9]/', $situacao)) {
        if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)\s+(.+)$/i', $situacao, $matches)) {
            $prontuarioNaSituacao = $matches[1];
            $nomeExtraido = trim($matches[2]);
            $resto = trim($matches[3]);
            
            // Tentar identificar situação válida no resto
            $situacoesValidas = [
                'DECURSO DE PRAZO', 'HABEAS CORPUS', 'ALVARÁ DE SOLTURA', 'EXTINÇÃO DA PENA',
                'LIBERDADE PROVISÓRIA', 'DECISÃO JUDICIAL', 'PRISÃO DOMICILIAR', 'PRISÃO ALBERGUE',
                'RECOLHIDO', 'PAGAMENTO DA DÍVIDA', 'REVOGAÇÃO DE PRISÃO', 'RELAXAMENTO DA PRISÃO'
            ];
            
            foreach ($situacoesValidas as $situacaoValida) {
                if (strpos(strtoupper($resto), $situacaoValida) !== false) {
                    return [$id_unidade, $unidade, $prontuario, $nomeExtraido, $situacaoValida];
                }
            }
        }
    }
    
    // Retornar original se não houver correção
    return [$id_unidade, $unidade, $prontuario, $nome, $situacao];
}

?>
