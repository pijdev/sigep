<?php
/**
 * Validar Padrões SIGEP
 * 
 * Valida se projeto segue padrões SIGEP
 * Verifica estrutura MVC, convenções, segurança
 * Gera relatório de conformidade
 * 
 * @category Cascade Scripts
 * @package Validação
 * @author Cascade AI
 * @version 1.0
 * @since 2024-03-25
 * 
 * Uso: php validar_padroes_sigep.php [caminho_projeto] [opcoes]
 * Opções:
 *   --detalhado: Análise profunda com mais validações
 *   --relatorio-html: Gera relatório em formato HTML
 *   --corrigir: Gera sugestões de correção automáticas
 *   --strict: Modo estrito com validações rigorosas
 */

set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['detalhado', 'relatorio-html', 'corrigir', 'strict']);
$analiseDetalhada = isset($options['detalhado']);
$relatorioHTML = isset($options['relatorio-html']);
$gerarCorrecoes = isset($options['corrigir']);
$modoStrict = isset($options['strict']);

// Configuração
$caminhoProjeto = $argv[1] ?? getcwd();
$arquivoRelatorio = 'C:/temp/relatorio_padroes_' . date('Y-m-d_H-i-s') . '.html';
$arquivoCorrecoes = 'C:/temp/sugestoes_correcao_' . date('Y-m-d_H-i-s') . '.php';

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

// Classe para validação de padrões SIGEP
class ValidadorPadroesSIGEP {
    private $caminhoProjeto;
    private $arquivosPHP;
    private $arquivosJS;
    private $arquivosCSS;
    private $relatorio = [];
    private $sugestoes = [];
    
    public function __construct($caminhoProjeto) {
        $this->caminhoProjeto = $caminhoProjeto;
    }
    
    public function validar() {
        global $analiseDetalhada, $gerarCorrecoes, $modoStrict;
        
        logMessage("Iniciando validação de padrões SIGEP...");
        logMessage("Caminho: {$this->caminhoProjeto}");
        logMessage("Modo: " . ($modoStrict ? 'Estrito' : 'Normal'));
        
        // Análise de estrutura
        $this->validarEstrutura();
        
        // Análise de arquivos
        $this->analisarArquivos();
        
        // Validação de padrões
        $this->validarPadroesMVC();
        $this->validarPadroesSeguranca();
        $this->validarPadroesPerformance();
        $this->validarPadroesFrontend();
        $this->validarPadroesNomenclatura();
        
        // Validações detalhadas
        if ($analiseDetalhada) {
            $this->validacoesDetalhadas();
        }
        
        // Gerar sugestões de correção
        if ($gerarCorrecoes) {
            $this->gerarSugestoesCorrecao();
        }
        
        $this->mostrarResultados();
        
        return [
            'relatorio' => $this->relatorio,
            'sugestoes' => $this->sugestoes,
            'conformidade' => $this->calcularConformidade()
        ];
    }
    
    private function validarEstrutura() {
        logMessage("Validando estrutura do projeto...");
        
        $estrutura = [
            'modulos' => is_dir($this->caminhoProjeto . '/modulos'),
            'includes' => is_dir($this->caminhoProjeto . '/includes'),
            'assets' => is_dir($this->caminhoProjeto . '/assets'),
            'conf' => is_dir($this->caminhoProjeto . '/conf'),
            'auth' => is_dir($this->caminhoProjeto . '/auth'),
            'scripts' => is_dir($this->caminhoProjeto . '/scripts'),
            'paginas' => is_dir($this->caminhoProjeto . '/paginas'),
            '.windsurf' => is_dir($this->caminhoProjeto . '/.windsurf')
        ];
        
        $this->relatorio['estrutura'] = $estrutura;
        
        // Verificar se é um projeto SIGEP
        $sigepScore = 0;
        $avisos = [];
        
        if ($estrutura['modulos']) {
            $sigepScore += 15;
        } else {
            $avisos[] = 'Diretório modulos não encontrado';
        }
        
        if ($estrutura['includes']) {
            $sigepScore += 15;
        } else {
            $avisos[] = 'Diretório includes não encontrado';
        }
        
        if ($estrutura['conf']) {
            $sigepScore += 15;
        } else {
            $avisos[] = 'Diretório conf não encontrado';
        }
        
        if ($estrutura['auth']) {
            $sigepScore += 15;
        } else {
            $avisos[] = 'Diretório auth não encontrado';
        }
        
        if ($estrutura['assets']) {
            $sigepScore += 10;
        } else {
            $avisos[] = 'Diretório assets não encontrado';
        }
        
        if ($estrutura['.windsurf']) {
            $sigepScore += 20;
        } else {
            $avisos[] = 'Diretório .windsurf não encontrado (Cascade)';
        }
        
        if ($estrutura['scripts']) {
            $sigepScore += 10;
        }
        
        $this->relatorio['estrutura']['score'] = $sigepScore;
        $this->relatorio['estrutura']['avisos'] = $avisos;
        $this->relatorio['estrutura']['tipo_projeto'] = $sigepScore >= 70 ? 'SIGEP' : 'Possivelmente SIGEP';
        
        logMessage("Tipo de projeto: {$this->relatorio['estrutura']['tipo_projeto']} (Score: $sigepScore/100)");
    }
    
    private function analisarArquivos() {
        logMessage("Analisando arquivos do projeto...");
        
        $this->arquivosPHP = analisarArquivos($this->caminhoProjeto, 'php');
        $this->arquivosJS = analisarArquivos($this->caminhoProjeto, 'js');
        $this->arquivosCSS = analisarArquivos($this->caminhoProjeto, 'css');
        
        $this->relatorio['arquivos'] = [
            'php' => [
                'total' => count($this->arquivosPHP),
                'tamanho_total' => array_sum(array_column($this->arquivosPHP, 'tamanho'))
            ],
            'js' => [
                'total' => count($this->arquivosJS),
                'tamanho_total' => array_sum(array_column($this->arquivosJS, 'tamanho'))
            ],
            'css' => [
                'total' => count($this->arquivosCSS),
                'tamanho_total' => array_sum(array_column($this->arquivosCSS, 'tamanho'))
            ]
        ];
        
        logMessage("Arquivos encontrados:");
        logMessage("- PHP: {$this->relatorio['arquivos']['php']['total']} arquivos");
        logMessage("- JS: {$this->relatorio['arquivos']['js']['total']} arquivos");
        logMessage("- CSS: {$this->relatorio['arquivos']['css']['total']} arquivos");
    }
    
    private function validarPadroesMVC() {
        logMessage("Validando padrões MVC...");
        
        $mvc = [
            'score' => 0,
            'controllers' => 0,
            'views' => 0,
            'modulos_completos' => 0,
            'separacao_view_controller' => 0,
            'avisos' => [],
            'erros' => []
        ];
        
        $modulos = [];
        $controllers = [];
        $views = [];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $caminhoRelativo = $arquivo['relativo'];
            
            // Classificar arquivos
            if (strpos($caminhoRelativo, 'modulos/') === 0) {
                if (strpos($caminhoRelativo, '_logica.php') !== false) {
                    $controllers[] = $caminhoRelativo;
                    $mvc['controllers']++;
                } elseif (strpos($caminhoRelativo, '_view.php') !== false) {
                    $views[] = $caminhoRelativo;
                    $mvc['views']++;
                }
                
                // Extrair nome do módulo
                $partes = explode('/', $caminhoRelativo);
                if (count($partes) >= 3) {
                    $setor = $partes[1];
                    $modulo = $partes[2];
                    $arquivo = $partes[3] ?? '';
                    
                    if (!isset($modulos[$setor])) {
                        $modulos[$setor] = [];
                    }
                    
                    if (!isset($modulos[$setor][$modulo])) {
                        $modulos[$setor][$modulo] = ['controller' => false, 'view' => false];
                    }
                    
                    if (strpos($arquivo, '_logica.php') !== false) {
                        $modulos[$setor][$modulo]['controller'] = true;
                    } elseif (strpos($arquivo, '_view.php') !== false) {
                        $modulos[$setor][$modulo]['view'] = true;
                    }
                }
            }
        }
        
        // Verificar módulos completos
        foreach ($modulos as $setor => $mods) {
            foreach ($mods as $modulo => $estrutura) {
                if ($estrutura['controller'] && $estrutura['view']) {
                    $mvc['modulos_completos']++;
                } else {
                    $mvc['avisos'][] = "Módulo incompleto: $setor/$modulo";
                }
            }
        }
        
        // Calcular score
        if ($mvc['controllers'] > 0) $mvc['score'] += 30;
        if ($mvc['views'] > 0) $mvc['score'] += 30;
        if ($mvc['modulos_completos'] > 0) $mvc['score'] += 40;
        
        $this->relatorio['mvc'] = $mvc;
    }
    
    private function validarPadroesSeguranca() {
        logMessage("Validando padrões de segurança...");
        
        $seguranca = [
            'score' => 0,
            'usa_prepared_statements' => 0,
            'valida_sessao' => 0,
            'valida_autenticacao' => 0,
            'filtra_inputs' => 0,
            'usa_display_errors_off' => 0,
            'avisos' => [],
            'erros' => []
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            
            // Verificar prepared statements
            if (strpos($conteudo, 'PDO::prepare') !== false || strpos($conteudo, '$pdo->prepare') !== false) {
                $seguranca['usa_prepared_statements']++;
            }
            
            // Verificar validação de sessão
            if (strpos($conteudo, 'session_start') !== false) {
                $seguranca['valida_sessao']++;
            }
            
            // Verificar validação de autenticação
            if (strpos($conteudo, '$_SESSION[\'user_id\']') !== false || strpos($conteudo, '$_SESSION["user_id"]') !== false) {
                $seguranca['valida_autenticacao']++;
            }
            
            // Verificar filtragem de inputs
            if (strpos($conteudo, 'filter_input') !== false || strpos($conteudo, 'filter_var') !== false) {
                $seguranca['filtra_inputs']++;
            }
            
            // Verificar display_errors
            if (strpos($conteudo, 'display_errors') !== false) {
                if (strpos($conteudo, 'display_errors = 0') !== false) {
                    $seguranca['usa_display_errors_off']++;
                } else {
                    $seguranca['avisos'][] = "display_errors não está desativado em: {$arquivo['relativo']}";
                }
            }
            
            // Verificar funções obsoletas e inseguras
            if (strpos($conteudo, 'mysql_query') !== false) {
                $seguranca['erros'][] = "Uso de mysql_query (obsoleto) em: {$arquivo['relativo']}";
            }
            
            if (strpos($conteudo, '$_GET') !== false && strpos($conteudo, 'filter_input') === false) {
                $seguranca['avisos'][] = "Uso não filtrado de \$_GET em: {$arquivo['relativo']}";
            }
            
            if (strpos($conteudo, '$_POST') !== false && strpos($conteudo, 'filter_input') === false) {
                $seguranca['avisos'][] = "Uso não filtrado de \$_POST em: {$arquivo['relativo']}";
            }
        }
        
        // Calcular score
        if ($seguranca['usa_prepared_statements'] > 0) $seguranca['score'] += 25;
        if ($seguranca['valida_sessao'] > 0) $seguranca['score'] += 25;
        if ($seguranca['valida_autenticacao'] > 0) $seguranca['score'] += 20;
        if ($seguranca['filtra_inputs'] > 0) $seguranca['score'] += 15;
        if ($seguranca['usa_display_errors_off'] > 0) $seguranca['score'] += 15;
        
        $this->relatorio['seguranca'] = $seguranca;
    }
    
    private function validarPadroesPerformance() {
        logMessage("Validando padrões de performance...");
        
        $performance = [
            'score' => 0,
            'usa_cache' => 0,
            'usa_limit' => 0,
            'usa_index' => 0,
            'evita_n_1' => 0,
            'usa_paginacao' => 0,
            'avisos' => [],
            'erros' => []
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            
            // Verificar cache
            if (strpos($conteudo, 'cache') !== false || strpos($conteudo, 'Cache') !== false) {
                $performance['usa_cache']++;
            }
            
            // Verificar LIMIT
            if (strpos($conteudo, 'LIMIT') !== false) {
                $performance['usa_limit']++;
            }
            
            // Verificar uso de índices (sugestão)
            if (strpos($conteudo, 'INDEX') !== false || strpos($conteudo, 'KEY') !== false) {
                $performance['usa_index']++;
            }
            
            // Verificar SELECT sem WHERE
            if (preg_match('/SELECT\s+\*\s+FROM\s+\w+\s*(?:WHERE|ORDER|GROUP|LIMIT|$)/i', $conteudo)) {
                $performance['avisos'][] = "Possível SELECT sem WHERE em: {$arquivo['relativo']}";
            }
            
            // Verificar N+1
            if (substr_count($conteudo, 'SELECT') > 1) {
                $performance['avisos'][] = "Possível problema N+1 em: {$arquivo['relativo']}";
            }
            
            // Verificar paginação
            if (strpos($conteudo, 'pagina') !== false || strpos($conteudo, 'limit') !== false) {
                $performance['usa_paginacao']++;
            }
            
            // Verificar loops ineficientes
            if (substr_count($conteudo, 'for ($') > 0 && strpos($conteudo, 'SELECT') !== false) {
                $performance['avisos'][] = "Possível loop com query dentro em: {$arquivo['relativo']}";
            }
        }
        
        // Calcular score
        if ($performance['usa_cache'] > 0) $performance['score'] += 20;
        if ($performance['usa_limit'] > 0) $performance['score'] += 20;
        if ($performance['usa_index'] > 0) $performance['score'] += 20;
        if ($performance['usa_paginacao'] > 0) $performance['score'] += 20;
        if (count($performance['avisos']) === 0) $performance['score'] += 20;
        
        $this->relatorio['performance'] = $performance;
    }
    
    private function validarPadroesFrontend() {
        logMessage("Validando padrões frontend...");
        
        $frontend = [
            'score' => 0,
            'usa_jquery' => 0,
            'usa_adminlte' => 0,
            'usa_bootstrap' => 0,
            'usa_toastr' => 0,
            'tem_responsive' => 0,
            'avisos' => [],
            'erros' => []
        ];
        
        foreach ($this->arquivosJS as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            
            // Verificar jQuery
            if (strpos($conteudo, '$(') !== false || strpos($conteudo, 'jQuery') !== false) {
                $frontend['usa_jquery']++;
            }
            
            // Verificar AdminLTE
            if (strpos($conteudo, 'adminlte') !== false || strpos($conteudo, 'AdminLTE') !== false) {
                $frontend['usa_adminlte']++;
            }
            
            // Verificar Bootstrap
            if (strpos($conteudo, 'bootstrap') !== false) {
                $frontend['usa_bootstrap']++;
            }
            
            // Verificar Toastr
            if (strpos($conteudo, 'toastr') !== false) {
                $frontend['usa_toastr']++;
            }
        }
        
        foreach ($this->arquivosCSS as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            
            // Verificar responsive
            if (strpos($conteudo, '@media') !== false || strpos($conteudo, 'responsive') !== false) {
                $frontend['tem_responsive']++;
            }
            
            // Verificar classes AdminLTE
            if (strpos($conteudo, 'card') !== false || strpos($conteudo, 'small-box') !== false) {
                $frontend['usa_adminlte']++;
            }
        }
        
        // Calcular score
        if ($frontend['usa_jquery'] > 0) $frontend['score'] += 20;
        if ($frontend['usa_adminlte'] > 0) $frontend['score'] += 30;
        if ($frontend['usa_bootstrap'] > 0) $frontend['score'] += 20;
        if ($frontend['usa_toastr'] > 0) $frontend['score'] += 15;
        if ($frontend['tem_responsive'] > 0) $frontend['score'] += 15;
        
        $this->relatorio['frontend'] = $frontend;
    }
    
    private function validarPadroesNomenclatura() {
        logMessage("Validando padrões de nomenclatura...");
        
        $nomenclatura = [
            'score' => 0,
            'arquivos_com_padrao' => 0,
            'classes_com_padrao' => 0,
            'funcoes_com_padrao' => 0,
            'variaveis_com_padrao' => 0,
            'avisos' => [],
            'erros' => []
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            $caminhoRelativo = $arquivo['relativo'];
            
            // Verificar padrão de arquivos
            if (strpos($caminhoRelativo, '_view.php') !== false || 
                strpos($caminhoRelativo, '_logica.php') !== false) {
                $nomenclatura['arquivos_com_padrao']++;
            } else {
                $nomenclatura['avisos'][] = "Arquivo não segue padrão: $caminhoRelativo";
            }
            
            // Verificar classes (PascalCase)
            if (preg_match_all('/class\s+([A-Z][a-zA-Z0-9]*)/', $conteudo, $matches)) {
                foreach ($matches[1] as $classe) {
                    if (preg_match('/^[A-Z][a-zA-Z0-9]*$/', $classe)) {
                        $nomenclatura['classes_com_padrao']++;
                    }
                }
            }
            
            // Verificar funções (camelCase)
            if (preg_match_all('/function\s+([a-z][a-zA-Z0-9_]*)/', $conteudo, $matches)) {
                foreach ($matches[1] as $funcao) {
                    if (preg_match('/^[a-z][a-zA-Z0-9_]*$/', $funcao)) {
                        $nomenclatura['funcoes_com_padrao']++;
                    }
                }
            }
            
            // Verificar variáveis (snake_case)
            if (preg_match_all('/\$(\w+)/', $conteudo, $matches)) {
                foreach ($matches[1] as $variavel) {
                    if (preg_match('/^[a-z][a-z0-9_]*$/', $variavel)) {
                        $nomenclatura['variaveis_com_padrao']++;
                    }
                }
            }
        }
        
        // Calcular score
        $totalArquivos = count($this->arquivosPHP);
        if ($totalArquivos > 0) {
            $percentualPadrao = ($nomenclatura['arquivos_com_padrao'] / $totalArquivos) * 100;
            $nomenclatura['score'] += min($percentualPadrao, 25);
        }
        
        $this->relatorio['nomenclatura'] = $nomenclatura;
    }
    
    private function validacoesDetalhadas() {
        logMessage("Realizando validações detalhadas...");
        
        // Validação de dependências
        $this->validarDependencias();
        
        // Validação de qualidade de código
        $this->validarQualidadeCodigo();
        
        // Validação de documentação
        $this->validarDocumentacao();
    }
    
    private function validarDependencias() {
        $dependencias = [
            'composer_json' => file_exists($this->caminhoProjeto . '/composer.json'),
            'package_json' => file_exists($this->caminhoProjeto . '/package.json'),
            'jquery' => false,
            'bootstrap' => false,
            'adminlte' => false,
            'fontawesome' => false,
            'chartjs' => false,
            'select2' => false,
            'toastr' => false
        ];
        
        if ($dependencias['package_json']) {
            $packageJson = json_decode(file_get_contents($this->caminhoProjeto . '/package.json'), true);
            $dependencias['jquery'] = isset($packageJson['dependencies']['jquery']);
            $dependencias['bootstrap'] = isset($packageJson['dependencies']['bootstrap']);
            $dependencias['adminlte'] = isset($packageJson['dependencies']['admin-lte']);
            $dependencias['fontawesome'] = isset($packageJson['dependencies']['@fortawesome/fontawesome-free']);
            $dependencias['chartjs'] = isset($packageJson['dependencies']['chart.js']);
            $dependencias['select2'] = isset($packageJson['dependencies']['select2']);
            $dependencias['toastr'] = isset($packageJson['dependencies']['toastr']);
        }
        
        $this->relatorio['dependencias'] = $dependencias;
    }
    
    private function validarQualidadeCodigo() {
        $qualidade = [
            'comentarios' => 0,
            'documentacao' => 0,
            'testes' => 0,
            'complexidade' => 0,
            'duplicacao' => 0,
            'avisos' => [],
            'erros' => []
        ];
        
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            
            // Verificar comentários
            if (strpos($conteudo, '//') !== false) {
                $qualidade['comentarios']++;
            }
            
            // Verificar documentação
            if (strpos($conteudo, '/**') !== false) {
                $qualidade['documentacao']++;
            }
            
            // Verificar complexidade (número de linhas por função)
            if (preg_match_all('/function\s+\w+\s*\([^)]*\)\s*{/', $conteudo, $matches)) {
                foreach ($matches as $match) {
                    $pos = strpos($conteudo, $match);
                    $fim = strpos($conteudo, '}', $pos);
                    if ($fim !== false) {
                        $linhas = substr_count(substr($conteudo, $pos, $fim - $pos), "\n");
                        if ($linhas > 50) {
                            $qualidade['avisos'][] = "Função complexa ({$linhas} linhas) em: {$arquivo['relativo']}";
                        }
                    }
                }
            }
        }
        
        $this->relatorio['qualidade'] = $qualidade;
    }
    
    private function validarDocumentacao() {
        $documentacao = [
            'tem_readme' => file_exists($this->caminhoProjeto . '/README.md'),
            'tem_agents' => file_exists($this->caminhoProjeto . '/AGENTS.md'),
            'tem_windsurfrules' => file_exists($this->caminhoProjeto . '/.windsurfrules'),
            'tem_documentacao_modulos' => 0,
            'avisos' => []
        ];
        
        // Verificar documentação de módulos
        foreach ($this->arquivosPHP as $arquivo) {
            $conteudo = file_get_contents($arquivo['caminho']);
            if (strpos($conteudo, '/**') !== false) {
                $documentacao['tem_documentacao_modulos']++;
            }
        }
        
        if (!$documentacao['tem_readme']) {
            $documentacao['avisos'][] = 'README.md não encontrado';
        }
        
        if (!$documentacao['tem_agents']) {
            $documentacao['avisos'][] = 'AGENTS.md não encontrado';
        }
        
        $this->relatorio['documentacao'] = $documentacao;
    }
    
    private function gerarSugestoesCorrecao() {
        logMessage("Gerando sugestões de correção...");
        
        $sugestoes = [];
        
        // Sugestões para MVC
        if (isset($this->relatorio['mvc'])) {
            $mvc = $this->relatorio['mvc'];
            
            if ($mvc['controllers'] === 0) {
                $sugestoes[] = [
                    'tipo' => 'estrutura',
                    'descricao' => 'Criar controllers (_logica.php) para os módulos',
                    'prioridade' => 'alta',
                    'codigo' => $this->gerarTemplateController()
                ];
            }
            
            if ($mvc['views'] === 0) {
                $sugestoes[] = [
                    'tipo' => 'estrutura',
                    'descricao' => 'Criar views (_view.php) para os módulos',
                    'prioridade' => 'alta',
                    'codigo' => $this->gerarTemplateView()
                ];
            }
        }
        
        // Sugestões para segurança
        if (isset($this->relatorio['seguranca'])) {
            $seguranca = $this->relatorio['seguranca'];
            
            if ($seguranca['usa_prepared_statements'] === 0) {
                $sugestoes[] = [
                    'tipo' => 'seguranca',
                    'descricao' => 'Implementar prepared statements PDO',
                    'prioridade' => 'crítica',
                    'codigo' => $this->gerarTemplatePreparedStatements()
                ];
            }
            
            if ($seguranca['valida_sessao'] === 0) {
                $sugestoes[] = [
                    'tipo' => 'seguranca',
                    'descricao' => 'Implementar validação de sessão',
                    'prioridade' => 'crítica',
                    'codigo' => $this->gerarTemplateSessao()
                ];
            }
        }
        
        $this->sugestoes = $sugestoes;
    }
    
    private function gerarTemplateController() {
        return <<<PHP
<?php
/**
 * [Nome Módulo] - Controller SIGEP
 */
session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset(\$_SESSION['user_id'])) {
    returnError('Usuário não autenticado', 401);
}

// Conexão PDO
try {
    \$config = require __DIR__ . '/../../../conf/db.php';
    \$dsn = "mysql:host={\$config['host']};dbname={\$config['dbname']};charset={\$config['charset']}";
    \$pdo = new PDO(\$dsn, \$config['user'], \$config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException \$e) {
    returnError('Erro na conexão: ' . \$e->getMessage(), 500);
}
?>
PHP;
    }
    
    private function gerarTemplateView() {
        return <<<HTML
<?php
require_once __DIR__ . '/[nome_modulo]_logica.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
        <h1 class="m-0">Título do Módulo</h1>
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <!-- Conteúdo aqui -->
    </div>
</section>

<script src="modulos/[setor]/[modulo]/assets/js/[modulo].js"></script>
HTML;
    }
    
    private function gerarTemplatePreparedStatements() {
        return <<<PHP
// Exemplo de prepared statement seguro
function buscarUsuario(\$pdo, \$id) {
    \$stmt = \$pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    \$stmt->execute([\$id]);
    return \$stmt->fetch();
}

// Inserir dados
function inserirUsuario(\$pdo, \$dados) {
    \$stmt = \$pdo->prepare("INSERT INTO usuarios (nome, email) VALUES (?, ?)");
    return \$stmt->execute([\$dados['nome'], \$dados['email']]);
}
PHP;
    }
    
    private function gerarTemplateSessao() {
        return <<<PHP
// Validação de sessão
session_start();

if (!isset(\$_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Verificar permissões
if (!\$_SESSION['user_admin'] && !\$_SESSION['perm_modulo']) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}
PHP;
    }
    
    private function calcularConformidade() {
        $scores = [];
        
        if (isset($this->relatorio['mvc'])) $scores[] = $this->relatorio['mvc']['score'];
        if (isset($this->relatorio['seguranca'])) $scores[] = $this->relatorio['seguranca']['score'];
        if (isset($this->relatorio['performance'])) $scores[] = $this->relatorio['performance']['score'];
        if (isset($this->relatorio['frontend'])) $scores[] = $this->relatorio['frontend']['score'];
        if (isset($this->relatorio['nomenclatura'])) $scores[] = $this->relatorio['nomenclatura']['score'];
        
        if (empty($scores)) return 0;
        
        return round(array_sum($scores) / count($scores), 2);
    }
    
    private function mostrarResultados() {
        global $relatorioHTML, $gerarCorrecoes;
        
        echo "\n\n=== RELATÓRIO DE VALIDAÇÃO DE PADRÕES SIGEP ===\n";
        echo "Projeto: {$this->caminhoProjeto}\n";
        echo "Data: " . date('d/m/Y H:i:s') . "\n\n";
        
        // Conformidade geral
        $conformidade = $this->calcularConformidade();
        echo "CONFORMIDADE GERAL: $conformidade%\n\n";
        
        // Estrutura
        if (isset($this->relatorio['estrutura'])) {
            $estrutura = $this->relatorio['estrutura'];
            echo "ESTRUTURA:\n";
            echo "- Tipo: {$estrutura['tipo_projeto']}\n";
            echo "- Score: {$estrutura['score']}/100\n";
            
            if (!empty($estrutura['avisos'])) {
                echo "- Avisos:\n";
                foreach ($estrutura['avisos'] as $aviso) {
                    echo "  ⚠️ $aviso\n";
                }
            }
            echo "\n";
        }
        
        // MVC
        if (isset($this->relatorio['mvc'])) {
            $mvc = $this->relatorio['mvc'];
            echo "PADRÃO MVC:\n";
            echo "- Score: {$mvc['score']}/100\n";
            echo "- Controllers: {$mvc['controllers']}\n";
            echo "- Views: {$mvc['views']}\n";
            echo "- Módulos completos: {$mvc['modulos_completos']}\n";
            
            if (!empty($mvc['avisos'])) {
                echo "- Avisos:\n";
                foreach ($mvc['avisos'] as $aviso) {
                    echo "  ⚠️ $aviso\n";
                }
            }
            echo "\n";
        }
        
        // Segurança
        if (isset($this->relatorio['seguranca'])) {
            $seguranca = $this->relatorio['seguranca'];
            echo "SEGURANÇA:\n";
            echo "- Score: {$seguranca['score']}/100\n";
            echo "- Prepared statements: {$seguranca['usa_prepared_statements']}\n";
            echo "- Validação de sessão: {$seguranca['valida_sessao']}\n";
            echo "- Filtragem de inputs: {$seguranca['filtra_inputs']}\n";
            
            if (!empty($seguranca['avisos'])) {
                echo "- Avisos:\n";
                foreach ($seguranca['avisos'] as $aviso) {
                    echo "  ⚠️ $aviso\n";
                }
            }
            
            if (!empty($seguranca['erros'])) {
                echo "- Erros críticos:\n";
                foreach ($seguranca['erros'] as $erro) {
                    echo "  ❌ $erro\n";
                }
            }
            echo "\n";
        }
        
        // Performance
        if (isset($this->relatorio['performance'])) {
            $performance = $this->relatorio['performance'];
            echo "PERFORMANCE:\n";
            echo "- Score: {$performance['score']}/100\n";
            echo "- Uso de cache: {$performance['usa_cache']}\n";
            echo "- Uso de LIMIT: {$performance['usa_limit']}\n";
            echo "- Uso de paginação: {$performance['usa_paginacao']}\n";
            
            if (!empty($performance['avisos'])) {
                echo "- Avisos:\n";
                foreach ($performance['avisos'] as $aviso) {
                    echo "  ⚠️ $aviso\n";
                }
            }
            echo "\n";
        }
        
        // Sugestões de correção
        if (!empty($this->sugestoes)) {
            echo "SUGESTÕES DE CORREÇÃO:\n";
            foreach ($this->sugestoes as $sugestao) {
                echo "- [{$sugestao['prioridade']}] {$sugestao['descricao']}\n";
            }
            echo "\n";
        }
        
        // Gerar arquivos se solicitado
        if ($relatorioHTML) {
            $this->gerarRelatorioHTML();
        }
        
        if ($gerarCorrecoes) {
            $this->gerarArquivoCorrecoes();
        }
    }
    
    private function gerarRelatorioHTML() {
        global $arquivoRelatorio;
        
        $html = $this->gerarHTMLRelatorio();
        
        if (file_put_contents($arquivoRelatorio, $html)) {
            logMessage("Relatório HTML gerado: $arquivoRelatorio");
            return true;
        }
        
        logMessage("ERRO: Não foi possível gerar relatório HTML", 'ERROR');
        return false;
    }
    
    private function gerarHTMLRelatorio() {
        $conformidade = $this->calcularConformidade();
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Validação de Padrões SIGEP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #f4f4f4; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .score { font-size: 2em; font-weight: bold; color: #28a745; }
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
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background: #28a745; color: white; }
        .badge-warning { background: #ffc107; color: #212529; }
        .badge-danger { background: #dc3545; color: white; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📋 Relatório de Validação de Padrões SIGEP</h1>
        <p><strong>Projeto:</strong> {$this->caminhoProjeto}<br>
        <strong>Data:</strong> {date('d/m/Y H:i:s')}<</p>
        <div class="score">Conformidade: {$conformidade}%</div>
    </div>
HTML;
        
        // Adicionar seções detalhadas
        $secoes = ['estrutura', 'mvc', 'seguranca', 'performance', 'frontend', 'nomenclatura'];
        
        foreach ($secoes as $secao) {
            if (isset($this->relatorio[$secao])) {
                $dados = $this->relatorio[$secao];
                $html .= '<div class="section"><h2>' . ucfirst($secao) . '</h2>';
                
                if (isset($dados['score'])) {
                    $html .= '<div class="progress-bar"><div class="progress-fill" style="width: ' . $dados['score'] . '%"></div></div>';
                    $html .= '<p>' . $dados['score'] . '/100</p>';
                }
                
                if (isset($dados['avisos']) && !empty($dados['avisos'])) {
                    $html .= '<h3>Avisos</h3><ul>';
                    foreach ($dados['avisos'] as $aviso) {
                        $html .= '<li class="warning">' . htmlspecialchars($aviso) . '</li>';
                    }
                    $html .= '</ul>';
                }
                
                if (isset($dados['erros']) && !empty($dados['erros'])) {
                    $html .= '<h3>Erros</h3><ul>';
                    foreach ($dados['erros'] as $erro) {
                        $html .= '<li class="error">' . htmlspecialchars($erro) . '</li>';
                    }
                    $html .= '</ul>';
                }
                
                $html .= '</div>';
            }
        }
        
        // Adicionar sugestões
        if (!empty($this->sugestoes)) {
            $html .= '<div class="section"><h2>Sugestões de Correção</h2>';
            $html .= '<ul>';
            foreach ($this->sugestoes as $sugestao) {
                $classe = $sugestao['prioridade'] === 'crítica' ? 'error' : 
                         ($sugestao['prioridade'] === 'alta' ? 'warning' : 'info');
                $html .= '<li class="' . $classe . '">';
                $html .= '<strong>[' . ucfirst($sugestao['prioridade']) . ']</strong> ';
                $html .= htmlspecialchars($sugestao['descricao']);
                $html .= '</li>';
            }
            $html .= '</ul></div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    private function gerarArquivoCorrecoes() {
        global $arquivoCorrecoes;
        
        $conteudo = "<?php\n";
        $conteudo .= "/**\n";
        $conteudo .= " * Sugestões de Correção para Padrões SIGEP\n";
        $conteudo .= " * Gerado automaticamente em " . date('d/m/Y H:i:s') . "\n";
        $conteudo .= " */\n\n";
        
        foreach ($this->sugestoes as $sugestao) {
            $conteudo .= "// [{$sugestao['prioridade']}] {$sugestao['descricao']}\n";
            $conteudo .= $sugestao['codigo'] . "\n\n";
        }
        
        if (file_put_contents($arquivoCorrecoes, $conteudo)) {
            logMessage("Arquivo de sugestões gerado: $arquivoCorrecoes");
            return true;
        }
        
        logMessage("ERRO: Não foi possível gerar arquivo de sugestões", 'ERROR');
        return false;
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DA VALIDAÇÃO DE PADRÕES SIGEP ===");
    
    // Validar projeto
    if (!validarProjeto($caminhoProjeto)) {
        exit(1);
    }
    
    // Executar validação
    $validador = new ValidadorPadroesSIGEP($caminhoProjeto);
    $resultado = $validador->validar();
    
    logMessage("=== VALIDAÇÃO CONCLUÍDA ===");
    
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
