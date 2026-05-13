<?php
/**
 * Debug - Verificar conteúdo exato da linha
 */

$arquivoOrigem = 'C:\Servicos\ConsultaUnidades\RELATORIO_ESTADUAL_COMPLETO_304321_LIMPO.csv';

echo "Debugando linha específica...\n";

$handle = fopen($arquivoOrigem, 'r');
if (!$handle) {
    die("Erro ao abrir o arquivo\n");
}

// Pular cabeçalho
fgets($handle);

$linhaAtual = 0;
while (($linha = fgets($handle)) !== false) {
    $linhaAtual++;
    
    if ($linhaAtual == 1937) { // Linha 1937 (index 1936)
        echo "Linha $linhaAtual encontrada!\n";
        echo "Conteúdo bruto: " . var_export($linha, true) . "\n";
        
        $dados = str_getcsv($linha, ';', '"', '\\');
        echo "Dados parseados: " . print_r($dados, true) . "\n";
        
        if (count($dados) >= 4) {
            $nome = $dados[3];
            echo "Nome: " . var_export($nome, true) . "\n";
            echo "Length: " . strlen($nome) . "\n";
            echo "Hex: " . bin2hex($nome) . "\n";
            
            // Testar regex
            if (preg_match('/^[0-9]+\s+(.+)$/', $nome, $matches)) {
                echo "Regex funcionou!\n";
                echo "Matches: " . print_r($matches, true) . "\n";
            } else {
                echo "Regex não funcionou!\n";
                
                // Tentar outros patterns
                if (preg_match('/^[0-9]/', $nome)) {
                    echo "Começa com número!\n";
                }
                
                // Mostrar primeiros caracteres
                echo "Primeiros 10 chars: " . substr($nome, 0, 10) . "\n";
            }
        }
        break;
    }
}

fclose($handle);

?>
