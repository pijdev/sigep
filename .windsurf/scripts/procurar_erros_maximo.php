<?php
/**
 * Debug - Procurar linhas com erro no arquivo máximo
 */

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_MAXIMO.csv';

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
        $situacao = $dados[4] ?? '';
        
        // Verificar se começa com número
        if (ctype_digit(substr($nome, 0, 1))) {
            echo "ERRO NA LINHA $linhaAtual: Nome=$nome\n";
            $errosEncontrados++;
            
            if ($errosEncontrados >= 5) {
                break;
            }
        }
        
        if (ctype_digit(substr($situacao, 0, 1))) {
            echo "ERRO NA LINHA $linhaAtual: Situação=$situacao\n";
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
