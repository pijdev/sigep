<?php
/**
 * Processar CSV de Internos - Validação e Importação
 * 
 * Lê arquivo CSV de internos, valida dados,
 * corrige problemas e importa para banco SIGEP
 * 
 * @category Scripts SIGEP
 * @package Processamento
 * @author SIGEP Team
 * @version 1.0
 * @since 2024-03-25
 * 
 * Uso: php processar_csv_internos.php [arquivo.csv] [opcoes]
 * Opções:
 *   --dry-run: Apenas valida, não importa
 *   --backup: Cria backup antes de importar
 *   --corrigir: Corrige dados automaticamente
 */

set_time_limit(0);
ini_set('memory_limit', '2G');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['dry-run', 'backup', 'corrigir']);
$dryRun = isset($options['dry-run']);
$createBackup = isset($options['backup']);
$autoCorrigir = isset($options['corrigir']);

// Configuração de arquivos
$arquivoOrigem = $argv[1] ?? 'C:/dados/internos.csv';
$arquivoErros = 'C:/dados/erros_internos.csv';
$arquivoBackup = 'C:/dados/backup_internos_' . date('Y-m-d_H-i-s') . '.csv';
$arquivoLog = 'C:/dados/log_processamento_' . date('Y-m-d') . '.log';

// Funções utilitárias
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message\n";
    echo $logLine;
    
    // Salvar em arquivo de log
    global $arquivoLog;
    file_put_contents($arquivoLog, $logLine, FILE_APPEND);
}

function validarArquivo($caminho, $obrigatorio = true) {
    if (!file_exists($caminho)) {
        if ($obrigatorio) {
            logMessage("ERRO: Arquivo obrigatório não encontrado: $caminho", 'ERROR');
            return false;
        } else {
            logMessage("AVISO: Arquivo opcional não encontrado: $caminho", 'WARNING');
        }
    }
    return true;
}

function mostrarProgresso($atual, $total, $mensagem = '') {
    $percentual = round(($atual / $total) * 100, 2);
    echo "\rProgresso: $percentual% ($atual/$total) $mensagem";
}

function formatarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    if (strlen($cpf) === 11) {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
    return $cpf;
}

function validarCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    // Verificar se todos os dígitos são iguais
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    
    // Validação básica do CPF (pode ser implementada validação completa)
    return true;
}

function formatarData($data) {
    if (empty($data)) return '';
    
    $data = trim($data);
    
    // Tentar diferentes formatos
    $formatos = ['d/m/Y', 'Y-m-d', 'd/m/Y H:i:s', 'Y-m-d H:i:s'];
    
    foreach ($formatos as $formato) {
        $date = DateTime::createFromFormat($formato, $data);
        if ($date !== false) {
            return $date->format('Y-m-d');
        }
    }
    
    return $data; // Retorna original se não conseguir formatar
}

function limparNome($nome) {
    if (empty($nome)) return '';
    
    // Remover caracteres especiais, espaços extras
    $nome = trim($nome);
    $nome = preg_replace('/[^a-zA-ZÀ-ÿ\s]/', '', $nome);
    $nome = preg_replace('/\s+/', ' ', $nome);
    
    return ucwords(strtolower($nome));
}

function criarBackup($origem, $destino) {
    if (!copy($origem, $destino)) {
        logMessage("ERRO: Falha ao criar backup: $destino", 'ERROR');
        return false;
    }
    
    logMessage("Backup criado: $destino", 'INFO');
    return true;
}

// Classe para processamento do CSV
class ProcessadorCSVInternos {
    private $handle;
    private $erros = [];
    private $validos = [];
    private $corrigidos = [];
    private $totalLinhas = 0;
    
    public function __construct($arquivo) {
        $this->handle = fopen($arquivo, 'r');
        if (!$this->handle) {
            throw new Exception("Não foi possível abrir o arquivo: $arquivo");
        }
    }
    
    public function processar() {
        logMessage("Iniciando processamento do CSV...");
        
        // Pular cabeçalho
        $cabecalho = fgetcsv($this->handle, 0, ';');
        if (!$cabecalho) {
            throw new Exception("Arquivo CSV inválido ou vazio");
        }
        
        logMessage("Cabeçalho encontrado: " . implode(', ', $cabecalho));
        
        $linhaAtual = 0;
        while (($linha = fgetcsv($this->handle, 0, ';')) !== false) {
            $linhaAtual++;
            $this->totalLinhas++;
            
            if ($linhaAtual % 1000 === 0) {
                mostrarProgresso($linhaAtual, $this->totalLinhas, 'linhas processadas');
            }
            
            $resultado = $this->processarLinha($linha, $linhaAtual);
            
            if ($resultado['status'] === 'valido') {
                $this->validos[] = $resultado['dados'];
            } elseif ($resultado['status'] === 'corrigido') {
                $this->corrigidos[] = $resultado['dados'];
            } else {
                $this->erros[] = $resultado;
            }
        }
        
        fclose($this->handle);
        
        logMessage("\nProcessamento concluído!");
        $this->mostrarEstatisticas();
        
        return [
            'validos' => $this->validos,
            'corrigidos' => $this->corrigidos,
            'erros' => $this->erros,
            'estatisticas' => $this->getEstatisticas()
        ];
    }
    
    private function processarLinha($linha, $numeroLinha) {
        // Mapear colunas (ajustar conforme estrutura real)
        $dados = [
            'linha' => $numeroLinha,
            'prontuario' => $linha[0] ?? '',
            'nome' => $linha[1] ?? '',
            'cpf' => $linha[2] ?? '',
            'data_nascimento' => $linha[3] ?? '',
            'situacao' => $linha[4] ?? '',
            'unidade' => $linha[5] ?? ''
        ];
        
        $erros = [];
        $corrigido = false;
        
        // Validações
        if (empty($dados['prontuario'])) {
            $erros[] = 'Prontuário obrigatório';
        }
        
        if (empty($dados['nome'])) {
            $erros[] = 'Nome obrigatório';
        } else {
            // Corrigir nome se necessário
            $nomeCorrigido = limparNome($dados['nome']);
            if ($nomeCorrigido !== $dados['nome']) {
                $dados['nome'] = $nomeCorrigido;
                $corrigido = true;
            }
        }
        
        if (empty($dados['cpf'])) {
            $erros[] = 'CPF obrigatório';
        } else {
            // Validar e formatar CPF
            $cpfFormatado = formatarCPF($dados['cpf']);
            if (!validarCPF($dados['cpf'])) {
                $erros[] = 'CPF inválido';
            } elseif ($cpfFormatado !== $dados['cpf']) {
                $dados['cpf'] = $cpfFormatado;
                $corrigido = true;
            }
        }
        
        // Validar data de nascimento
        if (!empty($dados['data_nascimento'])) {
            $dataFormatada = formatarData($dados['data_nascimento']);
            if ($dataFormatada !== $dados['data_nascimento']) {
                $dados['data_nascimento'] = $dataFormatada;
                $corrigido = true;
            }
        }
        
        // Retornar resultado
        if (empty($erros)) {
            return [
                'status' => $corrigido ? 'corrigido' : 'valido',
                'dados' => $dados,
                'erros' => []
            ];
        } else {
            return [
                'status' => 'erro',
                'dados' => $dados,
                'erros' => $erros
            ];
        }
    }
    
    private function mostrarEstatisticas() {
        $stats = $this->getEstatisticas();
        
        echo "\n\n=== ESTATÍSTICAS DO PROCESSAMENTO ===\n";
        echo "Total de linhas: {$stats['total']}\n";
        echo "Válidos: {$stats['validos']} (" . round($stats['validos'] / $stats['total'] * 100, 2) . "%)\n";
        echo "Corrigidos: {$stats['corrigidos']} (" . round($stats['corrigidos'] / $stats['total'] * 100, 2) . "%)\n";
        echo "Erros: {$stats['erros']} (" . round($stats['erros'] / $stats['total'] * 100, 2) . "%)\n";
        echo "Sucesso: " . round($stats['sucesso'] * 100, 2) . "%\n";
    }
    
    private function getEstatisticas() {
        return [
            'total' => $this->totalLinhas,
            'validos' => count($this->validos),
            'corrigidos' => count($this->corrigidos),
            'erros' => count($this->erros),
            'sucesso' => ($this->totalLinhas - count($this->erros)) / $this->totalLinhas
        ];
    }
    
    public function salvarErros($arquivoSaida) {
        if (empty($this->erros)) {
            logMessage("Nenhum erro para salvar");
            return true;
        }
        
        $handle = fopen($arquivoSaida, 'w');
        if (!$handle) {
            logMessage("ERRO: Não foi possível criar arquivo de erros", 'ERROR');
            return false;
        }
        
        // Cabeçalho
        fputcsv($handle, ['Linha', 'Prontuario', 'Nome', 'CPF', 'Data Nascimento', 'Erros'], ';', '"', '\\');
        
        foreach ($this->erros as $erro) {
            fputcsv($handle, [
                $erro['dados']['linha'],
                $erro['dados']['prontuario'],
                $erro['dados']['nome'],
                $erro['dados']['cpf'],
                $erro['dados']['data_nascimento'],
                implode('; ', $erro['erros'])
            ], ';', '"', '\\');
        }
        
        fclose($handle);
        logMessage("Erros salvos em: $arquivoSaida");
        return true;
    }
}

// Função para importar dados válidos para o banco
function importarParaBanco($dados) {
    global $dryRun;
    
    if ($dryRun) {
        logMessage("MODO DRY-RUN: " . count($dados) . " registros seriam importados");
        return true;
    }
    
    try {
        // Conectar ao banco SIGEP
        $config = require __DIR__ . '/../conf/db.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $pdo->exec("SET time_zone = '-03:00'");
        
        // Iniciar transação
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO internos 
            (prontuario, nome, cpf, data_nascimento, situacao, unidade, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
            nome = VALUES(nome),
            cpf = VALUES(cpf),
            data_nascimento = VALUES(data_nascimento),
            situacao = VALUES(situacao),
            unidade = VALUES(unidade),
            updated_at = NOW()
        ");
        
        $importados = 0;
        foreach ($dados as $registro) {
            $stmt->execute([
                $registro['prontuario'],
                $registro['nome'],
                $registro['cpf'],
                $registro['data_nascimento'],
                $registro['situacao'],
                $registro['unidade']
            ]);
            $importados++;
        }
        
        $pdo->commit();
        
        logMessage("Sucesso: $importados registros importados para o banco");
        return $importados;
        
    } catch (Exception $e) {
        logMessage("ERRO: Falha na importação: " . $e->getMessage(), 'ERROR');
        return false;
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DO PROCESSAMENTO DE CSV DE INTERNOS ===");
    logMessage("Arquivo: $arquivoOrigem");
    logMessage("Opções: " . json_encode($options));
    
    // Validar arquivo de origem
    if (!validarArquivo($arquivoOrigem)) {
        exit(1);
    }
    
    // Criar backup se solicitado
    if ($createBackup) {
        if (!criarBackup($arquivoOrigem, $arquivoBackup)) {
            exit(1);
        }
    }
    
    // Processar CSV
    $processador = new ProcessadorCSVInternos($arquivoOrigem);
    $resultado = $processador->processar();
    
    // Salvar erros
    if (!empty($resultado['erros'])) {
        $processador->salvarErros($arquivoErros);
    }
    
    // Importar dados válidos e corrigidos
    $dadosParaImportar = array_merge($resultado['validos'], $resultado['corrigidos']);
    
    if (!empty($dadosParaImportar)) {
        importarParaBanco($dadosParaImportar);
    }
    
    logMessage("=== PROCESSAMENTO CONCLUÍDO COM SUCESSO ===");
    
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
