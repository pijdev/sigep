<?php
/**
 * Analisador de Entradas Quebradas - CSV
 * Gera relatório detalhado dos problemas encontrados
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321.csv';
$arquivoRelatorio = 'C:\Servicos\ConsultaUnidades\RELATORIO_ANALISE_QUEBRADAS.csv';

echo "Analisando entradas quebradas do arquivo CSV...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleRelatorio = fopen($arquivoRelatorio, 'w');

if (!$handleOrigem || !$handleRelatorio) {
    die("Erro ao abrir os arquivos\n");
}

// Cabeçalho do relatório
fputcsv($handleRelatorio, [
    'LINHA', 'TIPO_ERRO', 'PRONTUARIO_ORIGINAL', 'NOME_ORIGINAL', 'SITUACAO_ORIGINAL',
    'PRONTUARIO_CORRIGIDO', 'NOME_CORRIGIDO', 'SITUACAO_CORRIGIDA', 'OBSERVACOES'
], ';', '"', '\\');

// Pular cabeçalho original
fgets($handleOrigem);
$linhaAtual = 1;
$totalQuebradas = 0;
$tiposErros = [];

while (($linha = fgets($handleOrigem)) !== false) {
    $linhaAtual++;
    
    if ($linhaAtual % 10000 == 0) {
        echo "Analisadas: $linhaAtual linhas\n";
    }
    
    $dados = str_getcsv($linha, ';', '"', '\\');
    
    if (count($dados) != 5) {
        continue;
    }
    
    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;
    
    $errosEncontrados = [];
    $prontuarioCorrigido = $prontuario;
    $nomeCorrigido = $nome;
    $situacaoCorrigida = $situacao;
    $observacoes = '';
    
    // Verificar padrões de erro
    if (preg_match('/^[0-9]+\s+[A-ZÀ-Ž]/', $nome)) {
        $errosEncontrados[] = 'NOME_COM_NUMERO';
    }
    
    if (preg_match('/^[0-9]+\s+[A-ZÀ-Ž]/', $situacao)) {
        $errosEncontrados[] = 'SITUACAO_COM_NUMERO';
    }
    
    if (preg_match('/\s{10,}/', $nome)) {
        $errosEncontrados[] = 'NOME_ESPACOS_EXCESSIVOS';
    }
    
    if (preg_match('/\s{10,}/', $situacao)) {
        $errosEncontrados[] = 'SITUACAO_ESPACOS_EXCESSIVOS';
    }
    
    // Se houver erros, tentar corrigir
    if (!empty($errosEncontrados)) {
        $totalQuebradas++;
        
        // Tentar correção específica para cada padrão
        if (preg_match('/^([0-9]+\s+[A-ZÀ-Ž\s]+?)\s{10,}([A-ZÀ-Ž\s]+)$/', $situacao, $matches)) {
            // Padrão ADEMIR MARTINS
            $parteCompleta = $matches[1] . ' ' . trim($matches[2]);
            
            if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)(?:\s+[A-ZÀ-Ž\s]+)?$/', $parteCompleta, $matches2)) {
                $prontuarioCorrigido = $matches2[1];
                $nomeCorrigido = trim($matches2[2]);
                
                if (preg_match('/([A-ZÀ-Ž\s]+)$/', $situacao, $matches3)) {
                    $situacaoCorrigida = trim($matches3[1]);
                }
                
                $observacoes = 'Padrão ADEMIR MARTINS detectado e corrigido';
            }
        }
        elseif (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)(?:\s+([A-ZÀ-Ž\s]+))?$/', $nome, $matches)) {
            // Padrão alternativo
            $prontuarioCorrigido = $matches[1];
            $nomeCorrigido = trim($matches[2]);
            
            if (isset($matches[3]) && !empty($matches[3])) {
                $situacaoCorrigida = trim($matches[3]);
            }
            
            $observacoes = 'Padrão alternativo detectado e corrigido';
        }
        
        // Contabilizar tipos de erro
        foreach ($errosEncontrados as $erro) {
            $tiposErros[$erro] = ($tiposErros[$erro] ?? 0) + 1;
        }
        
        // Escrever no relatório
        fputcsv($handleRelatorio, [
            $linhaAtual,
            implode(', ', $errosEncontrados),
            $prontuario,
            $nome,
            $situacao,
            $prontuarioCorrigido,
            $nomeCorrigido,
            $situacaoCorrigida,
            $observacoes
        ], ';', '"', '\\');
        
        // Mostrar exemplo no console
        if ($totalQuebradas <= 10) {
            echo "\n--- EXEMPLO #$totalQuebradas ---\n";
            echo "Linha: $linhaAtual\n";
            echo "Erros: " . implode(', ', $errosEncontrados) . "\n";
            echo "Original: $prontuario | $nome | $situacao\n";
            echo "Corrigido: $prontuarioCorrigido | $nomeCorrigido | $situacaoCorrigida\n";
            echo "Obs: $observacoes\n";
        }
    }
}

fclose($handleOrigem);
fclose($handleRelatorio);

echo "\n=== RELATÓRIO DE ANÁLISE ===\n";
echo "Total de linhas analisadas: $linhaAtual\n";
echo "Total de entradas quebradas: $totalQuebradas\n";
echo "Taxa de problemas: " . number_format(($totalQuebradas / $linhaAtual) * 100, 2) . "%\n\n";

echo "TIPOS DE ERROS ENCONTRADOS:\n";
foreach ($tiposErros as $erro => $quantidade) {
    echo "- $erro: $quantidade ocorrências\n";
}

echo "\nRelatório detalhado salvo em: $arquivoRelatorio\n";
echo "Análise concluída!\n";

?>
