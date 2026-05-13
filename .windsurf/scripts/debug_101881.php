<?php
/**
 * Debug - Linha 101881
 */

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_MAXIMO.csv';

echo "Debugando linha 101881...\n";

$handle = fopen($arquivoOrigem, 'r');
if (!$handle) {
    die("Erro ao abrir o arquivo\n");
}

// Pular cabeçalho
fgets($handle);

$linhaAtual = 0;
while (($linha = fgets($handle)) !== false) {
    $linhaAtual++;
    
    if ($linhaAtual == 101881) {
        echo "Linha $linhaAtual encontrada!\n";
        echo "Conteúdo bruto: " . var_export($linha, true) . "\n";
        
        $dados = str_getcsv($linha, ';', '"', '\\');
        echo "Dados parseados: " . print_r($dados, true) . "\n";
        
        if (count($dados) >= 4) {
            $nome = $dados[3];
            echo "Nome: " . var_export($nome, true) . "\n";
            echo "Length: " . strlen($nome) . "\n";
            echo "Primeiro char: '" . substr($nome, 0, 1) . "'\n";
            echo "Primeiro char é dígito: " . (ctype_digit(substr($nome, 0, 1)) ? 'SIM' : 'NÃO') . "\n";
            
            // Mostrar primeiros caracteres em hex
            echo "Primeiros 20 chars hex: " . bin2hex(substr($nome, 0, 20)) . "\n";
        }
        break;
    }
}

fclose($handle);

?>
