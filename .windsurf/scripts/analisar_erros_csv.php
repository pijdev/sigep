<?php
/**
 * Analisar Erros em CSV - Detecção e Relatório
 * 
 * Analisa arquivos CSV em busca de erros estruturais,
 * dados inconsistentes e problemas de formatação
 * Gera relatório detalhado com sugestões de correção
 * 
 * @category Scripts SIGEP
 * @package Análise
 * @author SIGEP Team
 * @version 1.0
 * @since 2024-03-25
 * 
 * Uso: php analisar_erros_csv.php [arquivo.csv] [opcoes]
 * Opções:
 *   --detalhado: Análise profunda com mais validações
 *   --corrigir: Gera sugestões de correção automáticas
 *   --relatorio-html: Gera relatório em formato HTML
 */

set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['detalhado', 'corrigir', 'relatorio-html']);
$analiseDetalhada = isset($options['detalhado']);
$gerarCorrecoes = isset($options['corrigir']);
$relatorioHTML = isset($options['relatorio-html']);

// Configuração de arquivos
$arquivoOrigem = $argv[1] ?? 'C:/dados/dados.csv';
$arquivoRelatorio = 'C:/dados/relatorio_erros_' . date('Y-m-d_H-i-s') . '.csv';
$arquivoCorrecoes = 'C:/dados/sugestoes_correcao_' . date('Y-m-d_H-i-s') . '.csv';
$arquivoHTML = 'C:/dados/relatorio_erros_' . date('Y-m-d_H-i-s') . '.html';

// Funções utilitárias
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function validarArquivo($caminho) {
    if (!file_exists($caminho)) {
        logMessage("ERRO: Arquivo não encontrado: $caminho", 'ERROR');
        return false;
    }
    
    if (!is_readable($caminho)) {
        logMessage("ERRO: Arquivo não pode ser lido: $caminho", 'ERROR');
        return false;
    }
    
    return true;
}

function formatarTamanho($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Classe para análise de erros
class AnalisadorErrosCSV {
    private $handle;
    private $erros = [];
    private $avisos = [];
    private $estatisticas = [];
    private $cabecalho = [];
    private $totalLinhas = 0;
    private $linhasVazias = 0;
    private $linhasIncompletas = 0;
    
    public function __construct($arquivo) {
        $this->handle = fopen($arquivo, 'r');
        if (!$this->handle) {
            throw new Exception("Não foi possível abrir o arquivo: $arquivo");
        }
    }
    
    public function analisar() {
        logMessage("Iniciando análise de erros no CSV...");
        
        // Ler cabeçalho
        $this->cabecalho = fgetcsv($this->handle, 0, ';');
        if (!$this->cabecalho) {
            throw new Exception("Arquivo CSV inválido ou vazio");
        }
        
        $this->validarCabecalho();
        
        // Analisar linhas
        $linhaAtual = 0;
        while (($linha = fgetcsv($this->handle, 0, ';')) !== false) {
            $linhaAtual++;
            $this->totalLinhas++;
            
            if ($linhaAtual % 1000 === 0) {
                echo "\rAnalisando linha $linhaAtual...";
            }
            
            $this->analisarLinha($linha, $linhaAtual);
        }
        
        fclose($this->handle);
        
        // Calcular estatísticas finais
        $this->calcularEstatisticasFinais();
        
        $this->mostrarResultados();
        
        return [
            'erros' => $this->erros,
            'avisos' => $this->avisos,
            'estatisticas' => $this->estatisticas,
            'cabecalho' => $this->cabecalho
        ];
    }
    
    private function validarCabecalho() {
        $numColunas = count($this->cabecalho);
        
        if ($numColunas === 0) {
            $this->erros[] = [
                'tipo' => 'estrutural',
                'linha' => 1,
                'descricao' => 'Cabeçalho vazio',
                'gravidade' => 'crítica'
            ];
        }
        
        // Verificar colunas duplicadas
        $colunasDuplicadas = array_count_values($this->cabecalho);
        foreach ($colunasDuplicadas as $coluna => $count) {
            if ($count > 1) {
                $this->avisos[] = [
                    'tipo' => 'estrutural',
                    'linha' => 1,
                    'descricao' => "Coluna duplicada: '$coluna' ($count ocorrências)",
                    'gravidade' => 'média'
                ];
            }
        }
        
        // Verificar nomes de colunas inválidos
        foreach ($this->cabecalho as $i => $coluna) {
            if (empty(trim($coluna))) {
                $this->avisos[] = [
                    'tipo' => 'estrutural',
                    'linha' => 1,
                    'descricao' => "Coluna " . ($i + 1) . " está vazia",
                    'gravidade' => 'média'
                ];
            }
            
            if (preg_match('/[^a-zA-Z0-9_\s]/', $coluna)) {
                $this->avisos[] = [
                    'tipo' => 'estrutural',
                    'linha' => 1,
                    'descricao' => "Coluna '$coluna' contém caracteres especiais",
                    'gravidade' => 'baixa'
                ];
            }
        }
    }
    
    private function analisarLinha($linha, $numeroLinha) {
        // Verificar linha vazia
        if (empty(array_filter($linha, function($item) { return $item !== null && $item !== ''; }))) {
            $this->linhasVazias++;
            return;
        }
        
        $numColunas = count($linha);
        $numColunasCabecalho = count($this->cabecalho);
        
        // Verificar número de colunas
        if ($numColunas !== $numColunasCabecalho) {
            $this->linhasIncompletas++;
            $gravidade = abs($numColunas - $numColunasCabecalho) > 2 ? 'alta' : 'média';
            
            $this->erros[] = [
                'tipo' => 'estrutural',
                'linha' => $numeroLinha,
                'descricao' => "Número de colunas incorreto: esperado $numColunasCabecalho, encontrado $numColunas",
                'gravidade' => $gravidade,
                'dados' => $linha
            ];
        }
        
        // Análise detalhada se solicitada
        if ($analiseDetalhada) {
            $this->analisarDadosLinha($linha, $numeroLinha);
        }
    }
    
    private function analisarDadosLinha($linha, $numeroLinha) {
        foreach ($linha as $i => $dado) {
            if ($dado === null) {
                continue;
            }
            
            $nomeColuna = $this->cabecalho[$i] ?? "Coluna " . ($i + 1);
            $dado = trim($dado);
            
            // Verificar dados muito longos
            if (strlen($dado) > 1000) {
                $this->avisos[] = [
                    'tipo' => 'dados',
                    'linha' => $numeroLinha,
                    'coluna' => $nomeColuna,
                    'descricao' => "Dado muito longo: " . strlen($dado) . " caracteres",
                    'gravidade' => 'baixa'
                ];
            }
            
            // Verificar caracteres especiais problemáticos
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $dado)) {
                $this->avisos[] = [
                    'tipo' => 'dados',
                    'linha' => $numeroLinha,
                    'coluna' => $nomeColuna,
                    'descricao' => "Contém caracteres de controle",
                    'gravidade' => 'média'
                ];
            }
            
            // Verificar encoding problems
            if (!mb_check_encoding($dado, 'UTF-8')) {
                $this->erros[] = [
                    'tipo' => 'encoding',
                    'linha' => $numeroLinha,
                    'coluna' => $nomeColuna,
                    'descricao' => "Encoding inválido (não UTF-8)",
                    'gravidade' => 'alta',
                    'dados' => $dado
                ];
            }
            
            // Validações específicas por tipo de dado
            $this->validarTipoDado($dado, $nomeColuna, $numeroLinha);
        }
    }
    
    private function validarTipoDado($dado, $nomeColuna, $numeroLinha) {
        $nomeColuna = strtolower($nomeColuna);
        
        // Validar CPF
        if (strpos($nomeColuna, 'cpf') !== false) {
            $cpf = preg_replace('/[^0-9]/', '', $dado);
            if (strlen($cpf) === 11 && !preg_match('/^(\d)\1{10}$/', $cpf)) {
                // CPF válido
            } elseif (strlen($cpf) > 0) {
                $this->erros[] = [
                    'tipo' => 'dados',
                    'linha' => $numeroLinha,
                    'coluna' => $nomeColuna,
                    'descricao' => "CPF inválido: $dado",
                    'gravidade' => 'alta'
                ];
            }
        }
        
        // Validar datas
        if (strpos($nomeColuna, 'data') !== false || strpos($nomeColuna, 'nascimento') !== false) {
            if (!empty($dado)) {
                $formatos = ['d/m/Y', 'Y-m-d', 'd/m/Y H:i:s', 'Y-m-d H:i:s'];
                $dataValida = false;
                
                foreach ($formatos as $formato) {
                    $date = DateTime::createFromFormat($formato, $dado);
                    if ($date !== false) {
                        $dataValida = true;
                        break;
                    }
                }
                
                if (!$dataValida) {
                    $this->avisos[] = [
                        'tipo' => 'dados',
                        'linha' => $numeroLinha,
                        'coluna' => $nomeColuna,
                        'descricao' => "Data inválida: $dado",
                        'gravidade' => 'média'
                    ];
                }
            }
        }
        
        // Validar email
        if (strpos($nomeColuna, 'email') !== false) {
            if (!empty($dado) && !filter_var($dado, FILTER_VALIDATE_EMAIL)) {
                $this->erros[] = [
                    'tipo' => 'dados',
                    'linha' => $numeroLinha,
                    'coluna' => $nomeColuna,
                    'descricao' => "Email inválido: $dado",
                    'gravidade' => 'alta'
                ];
            }
        }
        
        // Validar números
        if (strpos($nomeColuna, 'id') !== false || strpos($nomeColuna, 'codigo') !== false) {
            if (!empty($dado) && !is_numeric($dado)) {
                $this->avisos[] = [
                    'tipo' => 'dados',
                    'linha' => $numeroLinha,
                    'coluna' => $nomeColuna,
                    'descricao' => "Deveria ser numérico: $dado",
                    'gravidade' => 'média'
                ];
            }
        }
    }
    
    private function calcularEstatisticasFinais() {
        $tamanhoArquivo = filesize($this->handle ? stream_get_meta_data($this->handle)['uri'] : 0);
        
        $this->estatisticas = [
            'total_linhas' => $this->totalLinhas,
            'linhas_vazias' => $this->linhasVazias,
            'linhas_incompletas' => $this->linhasIncompletas,
            'total_erros' => count($this->erros),
            'total_avisos' => count($this->avisos),
            'erros_criticos' => count(array_filter($this->erros, fn($e) => $e['gravidade'] === 'crítica')),
            'erros_altos' => count(array_filter($this->erros, fn($e) => $e['gravidade'] === 'alta')),
            'erros_medios' => count(array_filter($this->erros, fn($e) => $e['gravidade'] === 'média')),
            'tamanho_arquivo' => formatarTamanho($tamanhoArquivo),
            'num_colunas' => count($this->cabecalho)
        ];
    }
    
    private function mostrarResultados() {
        $stats = $this->estatisticas;
        
        echo "\n\n=== RELATÓRIO DE ANÁLISE DE ERROS ===\n";
        echo "Arquivo: " . basename($this->handle ? stream_get_meta_data($this->handle)['uri'] : '') . "\n";
        echo "Tamanho: {$stats['tamanho_arquivo']}\n";
        echo "Total de linhas: {$stats['total_linhas']}\n";
        echo "Colunas: {$stats['num_colunas']}\n\n";
        
        echo "ESTATÍSTICAS:\n";
        echo "- Linhas vazias: {$stats['linhas_vazias']}\n";
        echo "- Linhas incompletas: {$stats['linhas_incompletas']}\n";
        echo "- Total de erros: {$stats['total_erros']}\n";
        echo "- Erros críticos: {$stats['erros_criticos']}\n";
        echo "- Erros altos: {$stats['erros_altos']}\n";
        echo "- Erros médios: {$stats['erros_medios']}\n";
        echo "- Total de avisos: {$stats['total_avisos']}\n\n";
        
        // Mostrar erros críticos primeiro
        if (!empty($this->erros)) {
            echo "ERROS ENCONTRADOS:\n";
            echo str_repeat("=", 50) . "\n";
            
            $errosCriticos = array_filter($this->erros, fn($e) => $e['gravidade'] === 'crítica');
            $errosAltos = array_filter($this->erros, fn($e) => $e['gravidade'] === 'alta');
            $errosMedios = array_filter($this->erros, fn($e) => $e['gravidade'] === 'média');
            
            foreach (array_merge($errosCriticos, $errosAltos, $errosMedios) as $erro) {
                echo "Linha {$erro['linha']} [{$erro['gravidade']}]: {$erro['descricao']}\n";
                if (isset($erro['coluna'])) {
                    echo "  Coluna: {$erro['coluna']}\n";
                }
                if (isset($erro['dados'])) {
                    echo "  Dados: " . substr(var_export($erro['dados'], true), 0, 100) . "...\n";
                }
                echo "\n";
            }
        }
        
        // Mostrar avisos principais
        if (!empty($this->avisos)) {
            echo "\nAVISOS PRINCIPAIS:\n";
            echo str_repeat("-", 50) . "\n";
            
            $avisosMostrados = 0;
            foreach ($this->avisos as $aviso) {
                if ($aviso['gravidade'] === 'crítica' || $aviso['gravidade'] === 'alta') {
                    echo "Linha {$aviso['linha']} [{$aviso['gravidade']}]: {$aviso['descricao']}\n";
                    if (isset($aviso['coluna'])) {
                        echo "  Coluna: {$aviso['coluna']}\n";
                    }
                    echo "\n";
                    $avisosMostrados++;
                    
                    if ($avisosMostrados >= 10) break;
                }
            }
            
            if (count($this->avisos) > $avisosMostrados) {
                echo "... e mais " . (count($this->avisos) - $avisosMostrados) . " avisos\n";
            }
        }
    }
    
    public function salvarRelatorioCSV($arquivoSaida) {
        $handle = fopen($arquivoSaida, 'w');
        if (!$handle) {
            logMessage("ERRO: Não foi possível criar arquivo de relatório", 'ERROR');
            return false;
        }
        
        // Cabeçalho do relatório
        fputcsv($handle, [
            'Tipo', 'Gravidade', 'Linha', 'Coluna', 'Descrição', 'Dados'
        ], ';', '"', '\\');
        
        // Salvar erros
        foreach ($this->erros as $erro) {
            fputcsv($handle, [
                $erro['tipo'],
                $erro['gravidade'],
                $erro['linha'],
                $erro['coluna'] ?? '',
                $erro['descricao'],
                isset($erro['dados']) ? substr(var_export($erro['dados'], true), 0, 200) : ''
            ], ';', '"', '\\');
        }
        
        // Salvar avisos
        foreach ($this->avisos as $aviso) {
            fputcsv($handle, [
                $aviso['tipo'],
                $aviso['gravidade'],
                $aviso['linha'],
                $aviso['coluna'] ?? '',
                $aviso['descricao'],
                ''
            ], ';', '"', '\\');
        }
        
        fclose($handle);
        logMessage("Relatório salvo em: $arquivoSaida");
        return true;
    }
    
    public function gerarSugestoesCorrecao($arquivoSaida) {
        $sugestoes = [];
        
        // Analisar padrões de erros e gerar sugestões
        foreach ($this->erros as $erro) {
            $sugestao = $this->gerarSugestaoParaErro($erro);
            if ($sugestao) {
                $sugestoes[] = $sugestao;
            }
        }
        
        if (empty($sugestoes)) {
            logMessage("Nenhuma sugestão de correção gerada");
            return true;
        }
        
        $handle = fopen($arquivoSaida, 'w');
        if (!$handle) {
            logMessage("ERRO: Não foi possível criar arquivo de sugestões", 'ERROR');
            return false;
        }
        
        // Cabeçalho
        fputcsv($handle, [
            'Linha', 'Tipo Erro', 'Problema', 'Sugestão de Correção', 'Comando'
        ], ';', '"', '\\');
        
        foreach ($sugestoes as $sugestao) {
            fputcsv($handle, [
                $sugestao['linha'],
                $sugestao['tipo'],
                $sugestao['problema'],
                $sugestao['correcao'],
                $sugestao['comando']
            ], ';', '"', '\\');
        }
        
        fclose($handle);
        logMessage("Sugestões de correção salvas em: $arquivoSaida");
        return true;
    }
    
    private function gerarSugestaoParaErro($erro) {
        switch ($erro['tipo']) {
            case 'estrutural':
                if (strpos($erro['descricao'], 'número de colunas') !== false) {
                    return [
                        'linha' => $erro['linha'],
                        'tipo' => $erro['tipo'],
                        'problema' => $erro['descricao'],
                        'correcao' => 'Verificar delimitadores e consistência das colunas',
                        'comando' => "sed -i '{$erro['linha']}s/[^;]*//g' arquivo.csv"
                    ];
                }
                break;
                
            case 'encoding':
                return [
                    'linha' => $erro['linha'],
                    'tipo' => $erro['tipo'],
                    'problema' => $erro['descricao'],
                    'correcao' => 'Converter arquivo para UTF-8',
                    'comando' => "iconv -f ISO-8859-1 -t UTF-8 arquivo.csv > arquivo_utf8.csv"
                ];
                break;
                
            case 'dados':
                if (strpos($erro['descricao'], 'CPF inválido') !== false) {
                    return [
                        'linha' => $erro['linha'],
                        'tipo' => $erro['tipo'],
                        'problema' => $erro['descricao'],
                        'correcao' => 'Validar e formatar CPF corretamente',
                        'comando' => "sed -i 's/\\([0-9]\\{3}\\)\\([0-9]\\{3}\\)\\([0-9]\\{3}\\)\\([0-9]\\{2}\\)/\\1.\\2.\\3.\\4/g' arquivo.csv"
                    ];
                }
                break;
        }
        
        return null;
    }
    
    public function gerarRelatorioHTML($arquivoSaida) {
        $html = $this->gerarHTMLRelatorio();
        
        if (file_put_contents($arquivoSaida, $html)) {
            logMessage("Relatório HTML gerado: $arquivoSaida");
            return true;
        }
        
        logMessage("ERRO: Não foi possível gerar relatório HTML", 'ERROR');
        return false;
    }
    
    private function gerarHTMLRelatorio() {
        $stats = $this->estatisticas;
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Análise de Erros CSV</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .erro { background: #ffebee; border-left: 4px solid #f44336; padding: 10px; margin: 10px 0; }
        .erro-critico { border-left-color: #d32f2f; }
        .erro-alto { border-left-color: #f57c00; }
        .erro-medio { border-left-color: #ff9800; }
        .aviso { background: #fff3e0; border-left: 4px solid #ff9800; padding: 10px; margin: 10px 0; }
        .tabela { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabela th, .tabela td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .tabela th { background: #f4f4f4; }
        .tabela tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Relatório de Análise de Erros CSV</h1>
        <p><strong>Arquivo:</strong> arquivo.csv<br>
        <strong>Data:</strong> {date('Y-m-d H:i:s')}<</p>
    </div>
    
    <div class="stats">
        <div class="stat-card">
            <h3>{$stats['total_linhas']}</h3>
            <p>Total de Linhas</p>
        </div>
        <div class="stat-card">
            <h3>{$stats['total_erros']}</h3>
            <p>Total de Erros</p>
        </div>
        <div class="stat-card">
            <h3>{$stats['total_avisos']}</h3>
            <p>Total de Avisos</p>
        </div>
        <div class="stat-card">
            <h3>{$stats['tamanho_arquivo']}</h3>
            <p>Tamanho do Arquivo</p>
        </div>
    </div>
HTML;
        
        // Adicionar erros
        if (!empty($this->erros)) {
            $html .= '<h2>🚨 Erros Encontrados</h2>';
            $html .= '<table class="tabela">';
            $html .= '<tr><th>Linha</th><th>Tipo</th><th>Gravidade</th><th>Descrição</th><th>Coluna</th></tr>';
            
            foreach ($this->erros as $erro) {
                $classeGravidade = $erro['gravidade'] === 'crítica' ? 'erro-critico' : 
                                 ($erro['gravidade'] === 'alta' ? 'erro-alto' : 'erro-medio');
                
                $html .= "<tr class='erro $classeGravidade'>";
                $html .= "<td>{$erro['linha']}</td>";
                $html .= "<td>{$erro['tipo']}</td>";
                $html .= "<td>{$erro['gravidade']}</td>";
                $html .= "<td>{$erro['descricao']}</td>";
                $html .= "<td>" . ($erro['coluna'] ?? '-') . "</td>";
                $html .= "</tr>";
            }
            
            $html .= '</table>';
        }
        
        // Adicionar avisos
        if (!empty($this->avisos)) {
            $html .= '<h2>⚠️ Avisos</h2>';
            $html .= '<table class="tabela">';
            $html .= '<tr><th>Linha</th><th>Tipo</th><th>Gravidade</th><th>Descrição</th><th>Coluna</th></tr>';
            
            foreach ($this->avisos as $aviso) {
                $html .= "<tr class='aviso'>";
                $html .= "<td>{$aviso['linha']}</td>";
                $html .= "<td>{$aviso['tipo']}</td>";
                $html .= "<td>{$aviso['gravidade']}</td>";
                $html .= "<td>{$aviso['descricao']}</td>";
                $html .= "<td>" . ($aviso['coluna'] ?? '-') . "</td>";
                $html .= "</tr>";
            }
            
            $html .= '</table>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DA ANÁLISE DE ERROS CSV ===");
    logMessage("Arquivo: $arquivoOrigem");
    logMessage("Opções: " . json_encode($options));
    
    // Validar arquivo
    if (!validarArquivo($arquivoOrigem)) {
        exit(1);
    }
    
    // Analisar arquivo
    $analisador = new AnalisadorErrosCSV($arquivoOrigem);
    $resultado = $analisador->analisar();
    
    // Salvar relatório CSV
    $analisador->salvarRelatorioCSV($arquivoRelatorio);
    
    // Gerar sugestões de correção se solicitado
    if ($gerarCorrecoes) {
        $analisador->gerarSugestoesCorrecao($arquivoCorrecoes);
    }
    
    // Gerar relatório HTML se solicitado
    if ($relatorioHTML) {
        $analisador->gerarRelatorioHTML($arquivoHTML);
    }
    
    logMessage("=== ANÁLISE CONCLUÍDA COM SUCESSO ===");
    
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
