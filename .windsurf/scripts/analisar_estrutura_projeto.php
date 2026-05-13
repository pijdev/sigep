<?php
/**
 * Analisar Estrutura de Projeto SIGEP
 * 
 * Analisa estrutura completa de um projeto SIGEP
 * Verifica padrões MVC, dependências, organização
 * Gera relatório detalhado da arquitetura
 * 
 * @category Cascade Scripts
 * @package Análise
 * @author Cascade AI
 * @version 1.0
 * @since 2024-03-25
 * 
 * Uso: php analisar_estrutura_projeto.php [caminho_projeto] [opcoes]
 * Opções:
 *   --detalhado: Análise profunda com mais validações
 *   --relatorio-html: Gera relatório em formato HTML
 *   --validar-padroes: Valida padrões SIGEP específicos
 */

set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['detalhado', 'relatorio-html', 'validar-padroes']);
$analiseDetalhada = isset($options['detalhado']);
$relatorioHTML = isset($options['relatorio-html']);
$validarPadroes = isset($options['validar-padroes']);

// Configuração
$caminhoProjeto = $argv[1] ?? getcwd();
$arquivoRelatorio = 'C:/temp/relatorio_estrutura_' . date('Y-m-d_H-i-s') . '.html';

// Funções utilitárias
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function validarProjeto($caminho) {
    if (!is_dir($caminho)) {
        logMessage("ERRO: Projeto não encontrado: $caminho", 'ERROR');
        return false;
    }
    return true;
}

function analisarArquivos($diretorio, $extensao = 'php') {
    $arquivos = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($diretorio, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $arquivo) {
        if ($arquivo->isFile() && $arquivo->getExtension() === $extensao) {
            $arquivos[] = [
                'caminho' => $arquivo->getPathname(),
                'tamanho' => $arquivo->getSize(),
                'modificado' => $arquivo->getMTime(),
                'relativo' => str_replace($diretorio . '/', '', $arquivo->getPathname())
            ];
        }
    }
    
    return $arquivos;
}

function validarCodigoPHP($arquivo) {
    $erros = [];
    $avisos = [];
    
    $conteudo = file_get_contents($arquivo);
    
    // Verificar sintaxe
    if (!@php_check_syntax($conteudo)) {
        $erros[] = 'Erro de sintaxe PHP';
    }
    
    // Verificar tag PHP
    if (strpos($conteudo, '<?php') !== 0) {
        $avisos[] = 'Tag PHP não está no início do arquivo';
    }
    
    // Verificar prepared statements
    if (strpos($conteudo, 'PDO::prepare') === false && strpos($conteudo, '$pdo->prepare') === false) {
        $avisos[] = 'Não foram encontrados prepared statements PDO';
    }
    
    // Verificar session_start
    if (strpos($conteudo, 'session_start') === false && strpos($conteudo, 'modulos/') !== false) {
        $avisos[] = 'Módulo não contém session_start()';
    }
    
    // Verificar charset UTF-8
    if (strpos($conteudo, 'utf8') === false && strpos($conteudo, 'UTF-8') === false) {
        $avisos[] = 'Não foi definido charset UTF-8';
    }
    
    return ['erros' => $erros, 'avisos' => $avisos];
}

// Classe para análise de estrutura
class AnalisadorEstruturaSIGEP {
    private $caminhoProjeto;
    private $arquivosPHP;
    private $arquivosJS;
    private $arquivosCSS;
    private $relatorio = [];
    
    public function __construct($caminhoProjeto) {
        $this->caminhoProjeto = $caminhoProjeto;
    }
    
    public function analisar() {
        global $analiseDetalhada, $validarPadroes;
        
        logMessage("Iniciando análise da estrutura do projeto...");
        logMessage("Caminho: {$this->caminhoProjeto}");
        
        // Análise básica
        $this->analisarEstruturaBasica();
        
        // Análise de arquivos
        $this->analisarArquivos();
        
        // Análise de padrões SIGEP se solicitado
        if ($validarPadroes) {
            $this->validarPadroesSIGEP();
        }
        
        // Análise detalhada se solicitado
        if ($analiseDetalhada) {
            $this->analisarDetalhado();
        }
        
        $this->mostrarResultados();
        
        return $this->relatorio;
    }
    
    private function analisarEstruturaBasica() {
        logMessage("Analisando estrutura básica...");
        
        $estrutura = [
            'modulos' => is_dir($this->caminhoProjeto . '/modulos'),
            'includes' => is_dir($this->caminhoProjeto . '/includes'),
            'assets' => is_dir($this->caminhoProjeto . '/assets'),
            'conf' => is_dir($this->caminhoProjeto . '/conf'),
            'auth' => is_dir($this->caminhoProjeto . '/auth'),
            'scripts' => is_dir($this->caminhoProjeto . '/scripts'),
            'paginas' => is_dir($this->caminhoProjeto . '/paginas')
        ];
        
        $this->relatorio['estrutura'] = $estrutura;
        
        // Verificar se é um projeto SIGEP
        $sigepScore = 0;
        if ($estrutura['modulos']) $sigepScore += 20;
        if ($estrutura['includes']) $sigepScore += 20;
        if ($estrutura['conf']) $sigepScore += 20;
        if ($estrutura['auth']) $sigepScore += 20;
        if ($estrutura['assets']) $sigepScore += 20;
        
        $this->relatorio['sigep_score'] = $sigepScore;
        $this->relatorio['tipo_projeto'] = $sigepScore >= 80 ? 'SIGEP' : 'Genérico';
        
        logMessage("Tipo de projeto: {$this->relatorio['tipo_projeto']} (Score: $sigepScore/100)");
    }
    
    private function analisarArquivos() {
        logMessage("Analisando arquivos do projeto...");
        
        // Analisar arquivos PHP
        $this->arquivosPHP = analisarArquivos($this->caminhoProjeto, 'php');
        $this->relatorio['arquivos']['php'] = [
            'total' => count($this->arquivosPHP),
            'tamanho_total' => array_sum(array_column($this->arquivosPHP, 'tamanho')),
            'analise' => $this->analisarArquivosPHP()
        ];
        
        // Analisar arquivos JavaScript
        $this->arquivosJS = analisarArquivos($this->caminhoProjeto, 'js');
        $this->relatorio['arquivos']['js'] = [
            'total' => count($this->arquivosJS),
            'tamanho_total' => array_sum(array_column($this->arquivosJS, 'tamanho'))
        ];
        
        // Analisar arquivos CSS
        $this->arquivosCSS = analisarArquivos($this->caminhoProjeto, 'css');
        $this->relatorio['arquivos']['css'] = [
            'total' => count($this->arquivosCSS),
            'tamanho_total' => array_sum(array_column($this->arquivosCSS, 'tamanho'))
        ];
        
        logMessage("Arquivos encontrados:");
        logMessage("- PHP: {$this->relatorio['arquivos']['php']['total']} arquivos");
        logMessage("- JS: {$this->relatorio['arquivos']['js']['total']} arquivos");
        logMessage("- CSS: {$this->relatorio['arquivos']['css']['total']} arquivos");
    }
    
    private function analisarArquivosPHP() {
        $analise = [
            'erros_sintaxe' => 0,
            'avisos' => 0,
            'arquivos_com_erros' => [],
            'modulos' => [],
            'controllers' => [],
            'views' => [],
            'includes' => []
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $validacao = validarCodigoPHP($arquivo['caminho']);
            
            if (!empty($validacao['erros'])) {
                $analise['erros_sintaxe']++;
                $analise['arquivos_com_erros'][] = $arquivo['relativo'];
            }
            
            if (!empty($validacao['avisos'])) {
                $analise['avisos']++;
            }
            
            // Classificar tipo de arquivo
            $caminhoRelativo = $arquivo['relativo'];
            
            if (strpos($caminhoRelativo, 'modulos/') === 0) {
                if (strpos($caminhoRelativo, '_logica.php') !== false) {
                    $analise['controllers'][] = $caminhoRelativo;
                } elseif (strpos($caminhoRelativo, '_view.php') !== false) {
                    $analise['views'][] = $caminhoRelativo;
                } else {
                    $analise['modulos'][] = $caminhoRelativo;
                }
            } elseif (strpos($caminhoRelativo, 'includes/') === 0) {
                $analise['includes'][] = $caminhoRelativo;
            }
        }
        
        return $analise;
    }
    
    private function validarPadroesSIGEP() {
        logMessage("Validando padrões SIGEP...");
        
        $padroes = [
            'mvc' => $this->validarPadraoMVC(),
            'seguranca' => $this->validarPadraoSeguranca(),
            'performance' => $this->validarPadraoPerformance(),
            'organizacao' => $this->validarPadraoOrganizacao()
        ];
        
        $this->relatorio['padroes_sigep'] = $padroes;
        
        // Calcular score de conformidade
        $scoreTotal = 0;
        $scoreMaximo = 0;
        
        foreach ($padroes as $categoria => $dados) {
            $scoreTotal += $dados['score'];
            $scoreMaximo += 100;
        }
        
        $this->relatorio['conformidade_sigep'] = round(($scoreTotal / $scoreMaximo) * 100, 2);
        
        logMessage("Conformidade com padrões SIGEP: {$this->relatorio['conformidade_sigep']}%");
    }
    
    private function validarPadraoMVC() {
        $score = 0;
        $avisos = [];
        
        // Verificar estrutura MVC
        if (count($this->relatorio['arquivos']['php']['analise']['controllers']) > 0) {
            $score += 30;
        } else {
            $avisos[] = 'Nenhum controller (_logica.php) encontrado';
        }
        
        if (count($this->relatorio['arquivos']['php']['analise']['views']) > 0) {
            $score += 30;
        } else {
            $avisos[] = 'Nenhuma view (_view.php) encontrada';
        }
        
        // Verificar separação view-controller
        $modulosComMVC = 0;
        foreach ($this->relatorio['arquivos']['php']['analise']['modulos'] as $modulo) {
            if (strpos($modulo, '_view.php') !== false || strpos($modulo, '_logica.php') !== false) {
                $modulosComMVC++;
            }
        }
        
        if ($modulosComMVC > 0) {
            $score += 40;
        } else {
            $avisos[] = 'Módulos não seguem padrão MVC';
        }
        
        return ['score' => $score, 'avisos' => $avisos];
    }
    
    private function validarPadraoSeguranca() {
        $score = 0;
        $avisos = [];
        
        // Verificar prepared statements
        $usaPreparedStatements = false;
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            if (strpos($conteudo, 'PDO::prepare') !== false || strpos($conteudo, '$pdo->prepare') !== false) {
                $usaPreparedStatements = true;
                break;
            }
        }
        
        if ($usaPreparedStatements) {
            $score += 40;
        } else {
            $avisos[] = 'Não foram encontrados prepared statements PDO';
        }
        
        // Verificar autenticação
        if ($this->relatorio['estrutura']['auth']) {
            $score += 30;
        } else {
            $avisos[] = 'Diretório auth não encontrado';
        }
        
        // Verificar validação de sessão
        $usaSessao = false;
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            if (strpos($conteudo, 'session_start') !== false) {
                $usaSessao = true;
                break;
            }
        }
        
        if ($usaSessao) {
            $score += 30;
        } else {
            $avisos[] = 'Não foi encontrada validação de sessão';
        }
        
        return ['score' => $score, 'avisos' => $avisos];
    }
    
    private function validarPadraoPerformance() {
        $score = 0;
        $avisos = [];
        
        // Verificar cache
        $usaCache = false;
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            if (strpos($conteudo, 'cache') !== false || strpos($conteudo, 'Cache') !== false) {
                $usaCache = true;
                break;
            }
        }
        
        if ($usaCache) {
            $score += 50;
        } else {
            $avisos[] = 'Não foi encontrado mecanismo de cache';
        }
        
        // Verificar otimização de queries
        $usaLimit = false;
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            if (strpos($conteudo, 'LIMIT') !== false) {
                $usaLimit = true;
                break;
            }
        }
        
        if ($usaLimit) {
            $score += 50;
        } else {
            $avisos[] = 'Não foram encontradas cláusulas LIMIT';
        }
        
        return ['score' => $score, 'avisos' => $avisos];
    }
    
    private function validarPadraoOrganizacao() {
        $score = 0;
        $avisos = [];
        
        // Verificar estrutura de diretórios
        if ($this->relatorio['estrutura']['modulos']) {
            $score += 25;
        } else {
            $avisos[] = 'Diretório modulos não encontrado';
        }
        
        if ($this->relatorio['estrutura']['includes']) {
            $score += 25;
        } else {
            $avisos[] = 'Diretório includes não encontrado';
        }
        
        if ($this->relatorio['estrutura']['assets']) {
            $score += 25;
        } else {
            $avisos[] = 'Diretório assets não encontrado';
        }
        
        if ($this->relatorio['estrutura']['conf']) {
            $score += 25;
        } else {
            $avisos[] = 'Diretório conf não encontrado';
        }
        
        return ['score' => $score, 'avisos' => $avisos];
    }
    
    private function analisarDetalhado() {
        logMessage("Realizando análise detalhada...");
        
        // Análise de dependências
        $this->analisarDependencias();
        
        // Análise de complexidade
        $this->analisarComplexidade();
        
        // Análise de qualidade de código
        $this->analisarQualidadeCodigo();
    }
    
    private function analisarDependencias() {
        $dependencias = [
            'composer' => file_exists($this->caminhoProjeto . '/composer.json'),
            'package' => file_exists($this->caminhoProjeto . '/package.json'),
            'jquery' => false,
            'bootstrap' => false,
            'adminlte' => false
        ];
        
        // Verificar dependências frontend
        if ($dependencias['package']) {
            $packageJson = json_decode(file_get_contents($this->caminhoProjeto . '/package.json'), true);
            $dependencias['jquery'] = isset($packageJson['dependencies']['jquery']);
            $dependencias['bootstrap'] = isset($packageJson['dependencies']['bootstrap']);
            $dependencias['adminlte'] = isset($packageJson['dependencies']['admin-lte']);
        }
        
        $this->relatorio['dependencias'] = $dependencias;
    }
    
    private function analisarComplexidade() {
        $complexidade = [
            'classes' => 0,
            'funcoes' => 0,
            'linhas_codigo' => 0,
            'complexidade_media' => 0
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            $linhas = count(explode("\n", $conteudo));
            
            $complexidade['linhas_codigo'] += $linhas;
            $complexidade['classes'] += substr_count($conteudo, 'class ');
            $complexidade['funcoes'] += substr_count($conteudo, 'function ');
        }
        
        if (count($this->arquivosPHP) > 0) {
            $complexidade['complexidade_media'] = round($complexidade['linhas_codigo'] / count($this->arquivosPHP));
        }
        
        $this->relatorio['complexidade'] = $complexidade;
    }
    
    private function analisarQualidadeCodigo() {
        $qualidade = [
            'comentarios' => 0,
            'documentacao' => 0,
            'testes' => 0,
            'erros_potenciais' => []
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            
            // Verificar comentários
            if (strpos($conteudo, '/**') !== false) {
                $qualidade['documentacao']++;
            }
            
            if (strpos($conteudo, '//') !== false) {
                $qualidade['comentarios']++;
            }
            
            // Verificar possíveis erros
            if (strpos($conteudo, 'mysql_query') !== false) {
                $qualidade['erros_potenciais'][] = 'Uso de mysql_query obsoleto em ' . $arquivo['relativo'];
            }
            
            if (strpos($conteudo, '$_GET') !== false && strpos($conteudo, 'filter_input') === false) {
                $qualidade['erros_potenciais'][] = 'Uso não filtrado de $_GET em ' . $arquivo['relativo'];
            }
        }
        
        $this->relatorio['qualidade'] = $qualidade;
    }
    
    private function mostrarResultados() {
        echo "\n\n=== RELATÓRIO DE ANÁLISE DE ESTRUTURA ===\n";
        echo "Projeto: {$this->caminhoProjeto}\n";
        echo "Tipo: {$this->relatorio['tipo_projeto']}\n";
        echo "Data: " . date('d/m/Y H:i:s') . "\n\n";
        
        // Estatísticas básicas
        echo "ESTATÍSTICAS:\n";
        echo "- Arquivos PHP: {$this->relatorio['arquivos']['php']['total']}\n";
        echo "- Arquivos JS: {$this->relatorio['arquivos']['js']['total']}\n";
        echo "- Arquivos CSS: {$this->relatorio['arquivos']['css']['total']}\n";
        echo "- Tamanho total PHP: " . $this->formatarBytes($this->relatorio['arquivos']['php']['tamanho_total']) . "\n\n";
        
        // Análise MVC
        if (isset($this->relatorio['arquivos']['php']['analise'])) {
            $analise = $this->relatorio['arquivos']['php']['analise'];
            echo "ANÁLISE MVC:\n";
            echo "- Controllers: " . count($analise['controllers']) . "\n";
            echo "- Views: " . count($analise['views']) . "\n";
            echo "- Módulos: " . count($analise['modulos']) . "\n";
            echo "- Erros de sintaxe: {$analise['erros_sintaxe']}\n";
            echo "- Avisos: {$analise['avisos']}\n\n";
        }
        
        // Padrões SIGEP
        if (isset($this->relatorio['padroes_sigep'])) {
            echo "PADRÕES SIGEP:\n";
            echo "- Conformidade: {$this->relatorio['conformidade_sigep']}%\n";
            
            foreach ($this->relatorio['padroes_sigep'] as $padrao => $dados) {
                echo "- " . ucfirst($padrao) . ": {$dados['score']}/100\n";
                if (!empty($dados['avisos'])) {
                    foreach ($dados['avisos'] as $aviso) {
                        echo "  ⚠️ $aviso\n";
                    }
                }
            }
            echo "\n";
        }
        
        // Dependências
        if (isset($this->relatorio['dependencias'])) {
            echo "DEPENDÊNCIAS:\n";
            foreach ($this->relatorio['dependencias'] as $dep => $existe) {
                echo "- " . ucfirst($dep) . ": " . ($existe ? '✅' : '❌') . "\n";
            }
            echo "\n";
        }
        
        // Complexidade
        if (isset($this->relatorio['complexidade'])) {
            $complexidade = $this->relatorio['complexidade'];
            echo "COMPLEXIDADE:\n";
            echo "- Classes: {$complexidade['classes']}\n";
            echo "- Funções: {$complexidade['funcoes']}\n";
            echo "- Linhas de código: {$complexidade['linhas_codigo']}\n";
            echo "- Média por arquivo: {$complexidade['complexidade_media']} linhas\n\n";
        }
        
        // Qualidade
        if (isset($this->relatorio['qualidade'])) {
            $qualidade = $this->relatorio['qualidade'];
            echo "QUALIDADE:\n";
            echo "- Arquivos com documentação: {$qualidade['documentacao']}\n";
            echo "- Arquivos com comentários: {$qualidade['comentarios']}\n";
            
            if (!empty($qualidade['erros_potenciais'])) {
                echo "- Erros potenciais:\n";
                foreach ($qualidade['erros_potenciais'] as $erro) {
                    echo "  ⚠️ $erro\n";
                }
            }
        }
    }
    
    private function formatarBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
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
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Análise de Estrutura</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .section { margin-bottom: 30px; }
        .section h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .progress-bar { background: #e9ecef; border-radius: 4px; height: 20px; margin: 5px 0; }
        .progress-fill { background: #007bff; height: 100%; border-radius: 4px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f4f4f4; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Relatório de Análise de Estrutura</h1>
        <p><strong>Projeto:</strong> {$this->caminhoProjeto}<br>
        <strong>Data:</strong> {date('d/m/Y H:i:s')}<</p>
    </div>
HTML;
        
        // Adicionar estatísticas
        $html .= '<div class="stats">';
        $html .= '<div class="stat-card"><h3>' . $this->relatorio['arquivos']['php']['total'] . '</h3><p>Arquivos PHP</p></div>';
        $html .= '<div class="stat-card"><h3>' . $this->relatorio['arquivos']['js']['total'] . '</h3><p>Arquivos JS</p></div>';
        $html .= '<div class="stat-card"><h3>' . $this->relatorio['arquivos']['css']['total'] . '</h3><p>Arquivos CSS</p></div>';
        
        if (isset($this->relatorio['conformidade_sigep'])) {
            $html .= '<div class="stat-card"><h3>' . $this->relatorio['conformidade_sigep'] . '%</h3><p>Conformidade SIGEP</p></div>';
        }
        
        $html .= '</div>';
        
        // Adicionar seções detalhadas
        if (isset($this->relatorio['padroes_sigep'])) {
            $html .= '<div class="section"><h2>Padrões SIGEP</h2>';
            foreach ($this->relatorio['padroes_sigep'] as $padrao => $dados) {
                $html .= '<h3>' . ucfirst($padrao) . '</h3>';
                $html .= '<div class="progress-bar"><div class="progress-fill" style="width: ' . $dados['score'] . '%"></div></div>';
                $html .= '<p>' . $dados['score'] . '/100</p>';
                
                if (!empty($dados['avisos'])) {
                    $html .= '<ul>';
                    foreach ($dados['avisos'] as $aviso) {
                        $html .= '<li class="warning">' . htmlspecialchars($aviso) . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DA ANÁLISE DE ESTRUTURA ===");
    
    // Validar projeto
    if (!validarProjeto($caminhoProjeto)) {
        exit(1);
    }
    
    // Executar análise
    $analisador = new AnalisadorEstruturaSIGEP($caminhoProjeto);
    $resultado = $analisador->analisar();
    
    // Gerar relatório HTML se solicitado
    if ($relatorioHTML) {
        $analisador->gerarRelatorioHTML($arquivoRelatorio);
    }
    
    logMessage("=== ANÁLISE CONCLUÍDA COM SUCESSO ===");
    
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
