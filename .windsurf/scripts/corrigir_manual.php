<?php
/**
 * Correção Manual - Linhas Específicas
 * Corrige manualmente as linhas com problemas conhecidos
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_ABSOLUTO.csv';
$arquivoManual = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_SUPER_FINAL.csv';

echo "Aplicando correção manual - linhas específicas...\n";

if (!file_exists($arquivoOrigem)) {
    die("Arquivo de origem não encontrado: $arquivoOrigem\n");
}

$handleOrigem = fopen($arquivoOrigem, 'r');
$handleManual = fopen($arquivoManual, 'w');

if (!$handleOrigem || !$handleManual) {
    die("Erro ao abrir os arquivos\n");
}

$totalLinhas = 0;
$correcoesManuais = 0;

// Ler cabeçalho
$cabecalho = fgets($handleOrigem);
$cabecalhoDados = str_getcsv($cabecalho, ';', '"', '\\');
fputcsv($handleManual, $cabecalhoDados, ';', '"', '\\');
$totalLinhas++;

echo "Processando linhas com correção manual...\n";

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

    // Correção MANUAL: Verificar se o nome começa com número (método alternativo)
    $primeirosChars = substr($nome, 0, 10);

    // Se começar com dígito, remover tudo até o primeiro espaço
    if (ctype_digit(substr($nome, 0, 1))) {
        $posPrimeiroEspaco = strpos($nome, ' ');
        if ($posPrimeiroEspaco !== false) {
            $nomeCorrigido = trim(substr($nome, $posPrimeiroEspaco + 1));

            if (strlen($nomeCorrigido) > 3 && strlen($nomeCorrigido) < 100) {
                $nome = $nomeCorrigido;
                $corrigido = true;
                $correcoesManuais++;

                if ($correcoesManuais <= 20) {
                    echo "Correção M#$correcoesManuais: '$nomeCorrigido'\n";
                }
            }
        }
    }

    // Escrever linha processada
    fputcsv($handleManual, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleManual);

echo "\n=== RELATÓRIO MANUAL ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções manuais aplicadas: $correcoesManuais\n";
echo "Arquivo manual salvo em: $arquivoManual\n";
echo "\nProcesso concluído com sucesso!\n";

?>
