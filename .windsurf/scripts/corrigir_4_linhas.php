<?php
/**
 * Correção Manual - 4 Linhas Específicas
 * Corrige manualmente as 4 linhas restantes
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_MAXIMO.csv';
$arquivoFinal = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_COMPLETO.csv';

echo "Aplicando correção manual - 4 linhas específicas...\n";

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

    // Correção MANUAL: Linhas específicas
    if ($totalLinhas == 101881 && $nome == '819540 EUVÂNIO IOP') {
        $nome = 'EUVÂNIO IOP';
        $corrigido = true;
        $correcoesFinais++;
        echo "Correção FINAL #1: Linha 101881 - 'EUVÂNIO IOP'\n";
    }
    elseif ($totalLinhas == 152659 && $nome == '515915 EMERSON LUZ') {
        $nome = 'EMERSON LUZ';
        $corrigido = true;
        $correcoesFinais++;
        echo "Correção FINAL #2: Linha 152659 - 'EMERSON LUZ'\n";
    }
    elseif ($totalLinhas == 153104 && $nome == '558491 JERÔNIMO TIL') {
        $nome = 'JERÔNIMO TIL';
        $corrigido = true;
        $correcoesFinais++;
        echo "Correção FINAL #3: Linha 153104 - 'JERÔNIMO TIL'\n";
    }
    elseif ($totalLinhas == 273496 && $nome == '711382 JOCEMAR MAI') {
        $nome = 'JOCEMAR MAI';
        $corrigido = true;
        $correcoesFinais++;
        echo "Correção FINAL #4: Linha 273496 - 'JOCEMAR MAI'\n";
    }

    // Escrever linha processada
    fputcsv($handleFinal, [$id_unidade, $unidade, $prontuario, $nome, $situacao], ';', '"', '\\');
}

fclose($handleOrigem);
fclose($handleFinal);

echo "\n=== RELATÓRIO FINAL COMPLETO ===\n";
echo "Total de linhas processadas: $totalLinhas\n";
echo "Correções finais aplicadas: $correcoesFinais\n";
echo "Arquivo final completo salvo em: $arquivoFinal\n";
echo "\nProcesso concluído com sucesso!\n";

?>
