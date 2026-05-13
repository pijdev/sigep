<?php
/**
 * Validador Final do CSV Corrigido
 * Verifica se todas as linhas estão no formato correto
 */

set_time_limit(0);
ini_set('memory_limit', '2G');

$arquivoFinal = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_FINAL_COMPLETO.csv';

echo "Validando arquivo CSV final...\n";

if (!file_exists($arquivoFinal)) {
    die("Arquivo não encontrado: $arquivoFinal\n");
}

$handle = fopen($arquivoFinal, 'r');
if (!$handle) {
    die("Erro ao abrir o arquivo\n");
}

$totalLinhas = 0;
$linhasValidas = 0;
$errosEncontrados = 0;
$exemplosErros = [];

// Ler cabeçalho
$cabecalho = fgets($handle);
$totalLinhas++;

echo "Validando linhas...\n";

while (($linha = fgets($handle)) !== false) {
    $totalLinhas++;

    if ($totalLinhas % 50000 == 0) {
        echo "Validadas: $totalLinhas linhas\n";
    }

    $dados = str_getcsv($linha, ';', '"', '\\');

    if (count($dados) != 5) {
        $errosEncontrados++;
        if ($errosEncontrados <= 5) {
            $exemplosErros[] = "Linha $totalLinhas: Número de colunas incorreto (" . count($dados) . ")";
        }
        continue;
    }

    list($id_unidade, $unidade, $prontuario, $nome, $situacao) = $dados;

    $errosLinha = [];

    // Validar ID da unidade (deve ser numérico)
    if (!is_numeric($id_unidade)) {
        $errosLinha[] = "ID_unidade não é numérico: $id_unidade";
    }

    // Validar prontuário (deve ser numérico)
    if (!is_numeric($prontuario)) {
        $errosLinha[] = "Prontuário não é numérico: $prontuario";
    }

    // Validar nome (não deve começar com número)
    if (preg_match('/^[0-9]/', $nome)) {
        $errosLinha[] = "Nome começa com número: $nome";
    }

    // Validar situação (não deve começar com número)
    if (preg_match('/^[0-9]/', $situacao)) {
        $errosLinha[] = "Situação começa com número: $situacao";
    }

    // Validar espaços excessivos
    if (preg_match('/\s{5,}/', $nome)) {
        $errosLinha[] = "Nome com espaços excessivos";
    }

    if (preg_match('/\s{5,}/', $situacao)) {
        $errosLinha[] = "Situação com espaços excessivos";
    }

    // Validar campos vazios
    if (empty(trim($nome))) {
        $errosLinha[] = "Nome vazio";
    }

    if (empty(trim($situacao))) {
        $errosLinha[] = "Situação vazia";
    }

    if (!empty($errosLinha)) {
        $errosEncontrados++;
        if ($errosEncontrados <= 10) {
            $exemplosErros[] = "Linha $totalLinhas: " . implode(', ', $errosLinha);
        }
    } else {
        $linhasValidas++;
    }
}

fclose($handle);

echo "\n=== RELATÓRIO DE VALIDAÇÃO ===\n";
echo "Total de linhas validadas: $totalLinhas\n";
echo "Linhas válidas: $linhasValidas\n";
echo "Linhas com erros: $errosEncontrados\n";
echo "Taxa de validade: " . number_format(($linhasValidas / $totalLinhas) * 100, 2) . "%\n";

if ($errosEncontrados > 0) {
    echo "\nEXEMPLOS DE ERROS ENCONTRADOS:\n";
    foreach ($exemplosErros as $erro) {
        echo "- $erro\n";
    }
    echo "\n⚠️  Arquivo ainda contém erros que precisam ser corrigidos.\n";
} else {
    echo "\n✅ Arquivo está 100% validado e pronto para uso!\n";
}

echo "\nValidação concluída!\n";

?>
