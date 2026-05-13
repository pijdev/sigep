<?php
/**
 * Debug - Encontrar linhas com erro
 */

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_CORRIGIDO_FINAL.csv';

echo "Procurando linhas com erro...\n";

$handle = fopen($arquivoOrigem, 'r');
if (!$handle) {
    die("Erro ao abrir o arquivo\n");
}

// Pular cabeçalho
fgets($handle);

$linhaAtual = 0;
$errosEncontrados = 0;

while (($linha = fgets($handle)) !== false) {
    $linhaAtual++;

    $dados = str_getcsv($linha, ';', '"', '\\');

    if (count($dados) >= 4) {
        $nome = $dados[3];

        // Verificar se começa com número
        if (preg_match('/^[0-9]/', $nome)) {
            echo "ERRO na linha $linhaAtual: $nome\n";
            $errosEncontrados++;

            if ($errosEncontrados >= 5) {
                break;
            }
        }
    }
}

fclose($handle);

echo "Total de erros encontrados: $errosEncontrados\n";

?>
