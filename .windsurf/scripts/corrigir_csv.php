<?php
/**
 * Corretor de CSV - RELATORIO_ESTADUAL_COMPLETO_304321.csv
 * Corrige entradas quebradas onde o nome e situação penal estão misturados
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321.csv';
$arquivoCorrigido = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO.csv';

echo "Iniciando correção do arquivo CSV...\n";
echo "Arquivo de origem: $arquivoOrigem\n";
echo "Arquivo corrigido: $arquivoCorrigido\n\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

// Abrir arquivos
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

while (($linha = fgets($handleOrigem)) !== false) {
    $linhaAtual++;
    $totalLinhas++;

    // Progresso a cada 10000 linhas
    if ($linhaAtual % 10000 == 0) {
        echo "Processadas: $linhaAtual linhas\n";
    }

    // Parse da linha CSV
    $dados = str_getcsv($linha, ';', '"', '\\');

    // Verificar se temos exatamente 5 colunas
    if (count($dados) != 5) {
        echo "Linha $linhaAtual: Número de colunas inválido (" . count($dados) . ")\n";
        continue;
    }

    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;

    // Verificar se está quebrado (padrão: nome contém números e situação penal)
    $estaQuebrado = false;

    // Padrão 1: Nome contém números no início
    if (preg_match('/^[0-9]+\s+[A-ZÀ-Ž]/', $nome)) {
        $estaQuebrado = true;
    }

    // Padrão 2: Situação contém padrão de "número nome"
    if (preg_match('/^[0-9]+\s+[A-ZÀ-Ž]/', $situacao)) {
        $estaQuebrado = true;
    }

    // Padrão 3: Nome tem espaços excessivos e múltiplos conteúdos
    if (preg_match('/\s{10,}/', $nome) && preg_match('/[A-ZÀ-Ž]{5,}\s+[A-ZÀ-Ž]{5,}/', $nome)) {
        $estaQuebrado = true;
    }

    if ($estaQuebrado) {
        $linhasCorrigidas++;

        // Tentar extrair informações corretas
        $nomeCorrigido = $nome;
        $situacaoCorrigida = $situacao;

        // Padrão ADEMIR MARTINS: extrair do campo situação
        if (preg_match('/^([0-9]+\s+[A-ZÀ-Ž\s]+?)\s{10,}([A-ZÀ-Ž\s]+)$/', $situacao, $matches)) {
            // O campo situação contém: "815399 ADEMIR MARTINS ... DECISÃO JUDICIAL"
            $parteCompleta = $matches[1] . ' ' . trim($matches[2]);

            // Extrair prontuario e nome
            if (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)(?:\s+[A-ZÀ-Ž\s]+)?$/', $parteCompleta, $matches2)) {
                $prontuarioCorrigido = $matches2[1];
                $nomeCorrigido = trim($matches2[2]);

                // Situação é a última parte
                if (preg_match('/([A-ZÀ-Ž\s]+)$/', $situacao, $matches3)) {
                    $situacaoCorrigida = trim($matches3[1]);
                }

                echo "Linha $linhaAtual: CORRIGIDO - Prontuário: $prontuarioCorrigido, Nome: $nomeCorrigido, Situação: $situacaoCorrigida\n";
            }
        }
        // Padrão alternativo: extrair do campo nome
        elseif (preg_match('/^([0-9]+)\s+([A-ZÀ-Ž\s]+?)(?:\s+([A-ZÀ-Ž\s]+))?$/', $nome, $matches)) {
            $prontuarioCorrigido = $matches[1];
            $nomeCorrigido = trim($matches[2]);

            // Se houver terceira parte, pode ser a situação
            if (isset($matches[3]) && !empty($matches[3])) {
                $situacaoCorrigida = trim($matches[3]);
            }

            echo "Linha $linhaAtual: CORRIGIDO (padrão alt) - Prontuário: $prontuarioCorrigido, Nome: $nomeCorrigido, Situação: $situacaoCorrigida\n";
        }

        // Escrever linha corrigida
        fputcsv($handleCorrigido, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
    } else {
        // Escrever linha original
        fputcsv($handleCorrigido, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
    }
}

fclose($handleOrigem);
fclose($handleCorrigido);

echo "\n=== RELATÓRIO FINAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Linhas corrigidas: $linhasCorrigidas\n";
echo "Taxa de correção: " . number_format(($linhasCorrigidas / $totalLinhas) * 100, 2) . "%\n";
echo "Arquivo corrigido salvo em: $arquivoCorrigido\n";
echo "\nProcesso concluído!\n";

?>
