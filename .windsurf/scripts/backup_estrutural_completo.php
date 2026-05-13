<?php
/**
 * Backup Estrutural Completo do SIGEP
 * 
 * Realiza backup completo da estrutura do banco de dados
 * incluindo schema, dados, procedures e triggers
 * Comprime o resultado para otimizar espaço
 * Gera checksum para verificação de integridade
 * 
 * @category Scripts SIGEP
 * @package Manutenção
 * @author SIGEP Team
 * @version 1.0
 * @since 2024-03-25
 * 
 * Uso: php backup_estrutural_completo.php [opcoes]
 * Opções:
 *   --dados: Incluir dados no backup (padrão)
 *   --schema: Apenas estrutura (sem dados)
 *   --compress: Usar compressão gzip
 *   --email: Enviar backup por email
 *   --destino: Diretório de destino personalizado
 */

set_time_limit(0);
ini_set('memory_limit', '4G');
date_default_timezone_set('America/Sao_Paulo');

// Argumentos da linha de comando
$options = getopt('', ['dados', 'schema', 'compress', 'email', 'destino:']);
$incluirDados = !isset($options['schema']);
$usarCompressao = isset($options['compress']);
$enviarEmail = isset($options['email']);
$diretorioDestino = $options['destino'] ?? 'C:/backups/sigep';

// Configuração
$config = require __DIR__ . '/../conf/db.php';
$timestamp = date('Y-m-d_H-i-s');
$nomeArquivo = "backup_sigep_{$timestamp}";
$diretorioBackup = $diretorioDestino . '/' . $nomeArquivo;

// Criar diretório de backup se não existir
if (!is_dir($diretorioDestino)) {
    mkdir($diretorioDestino, 0755, true);
    logMessage("Diretório de backup criado: $diretorioDestino");
}

// Funções utilitárias
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

function formatarBytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

function gerarChecksum($arquivo) {
    return hash_file('sha256', $arquivo);
}

function verificarEspacoDisponivel($diretorio) {
    $espacoLivre = disk_free_space($diretorio);
    $espacoTotal = disk_total_space($diretorio);
    
    if ($espacoLivre === false) {
        return false;
    }
    
    $percentualUsado = (($espacoTotal - $espacoLivre) / $espacoTotal) * 100;
    
    logMessage("Espaço disponível: " . formatarBytes($espacoLivre));
    logMessage("Espaço total: " . formatarBytes($espacoTotal));
    logMessage("Uso: " . round($percentualUsado, 2) . "%");
    
    if ($percentualUsado > 90) {
        logMessage("AVISO: Espaço em disco baixo (< 10% disponível)", 'WARNING');
    }
    
    return $espacoLivre > 1024 * 1024 * 1024; // 1GB mínimo
}

function enviarEmailBackup($arquivoBackup, $checksum) {
    global $config;
    
    // Configurações de email (ajustar conforme necessário)
    $para = 'backup@sigep.gov.br';
    $assunto = "Backup SIGEP - " . date('d/m/Y H:i');
    $mensagem = "Backup do sistema SIGEP realizado com sucesso.\n\n";
    $mensagem .= "Arquivo: " . basename($arquivoBackup) . "\n";
    $mensagem .= "Tamanho: " . formatarBytes(filesize($arquivoBackup)) . "\n";
    $mensagem .= "Checksum SHA256: $checksum\n";
    $mensagem .= "Data/Hora: " . date('d/m/Y H:i:s');
    
    // Headers do email
    $headers = [
        'From: noreply@sigep.gov.br',
        'X-Mailer: PHP/' . phpversion(),
        'Content-Type: text/plain; charset=UTF-8'
    ];
    
    // Enviar email
    if (mail($para, $assunto, $mensagem, $headers)) {
        logMessage("Email de notificação enviado para: $para");
        return true;
    } else {
        logMessage("ERRO: Falha ao enviar email de notificação", 'ERROR');
        return false;
    }
}

// Classe de Backup
class BackupEstruturalSIGEP {
    private $pdo;
    private $arquivoBackup;
    private $arquivoChecksum;
    private $estatisticas = [];
    
    public function __construct($config) {
        // Conectar ao banco
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $this->pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        
        $this->pdo->exec("SET time_zone = '-03:00'");
    }
    
    public function executarBackup($incluirDados, $usarCompressao) {
        global $diretorioBackup, $nomeArquivo, $incluirDados;
        
        logMessage("Iniciando backup estrutural completo...");
        
        // Verificar espaço disponível
        if (!verificarEspacoDisponivel($diretorioDestino)) {
            throw new Exception("Espaço insuficiente para backup");
        }
        
        // Determinar nome do arquivo
        $extensao = $usarCompressao ? '.sql.gz' : '.sql';
        $this->arquivoBackup = $diretorioBackup . '/' . $nomeArquivo . $extensao;
        
        logMessage("Arquivo de backup: {$this->arquivoBackup}");
        
        // Iniciar backup
        $inicio = microtime(true);
        
        if ($usarCompressao) {
            $this->backupComGZIP($incluirDados);
        } else {
            $this->backupPlainSQL($incluirDados);
        }
        
        $fim = microtime(true);
        $duracao = round(($fim - $inicio) / 60, 2);
        
        // Gerar checksum
        $this->arquivoChecksum = gerarChecksum($this->arquivoBackup);
        
        // Calcular estatísticas
        $this->calcularEstatisticas($duracao);
        
        $this->mostrarResultados();
        
        return [
            'arquivo' => $this->arquivoBackup,
            'checksum' => $this->arquivoChecksum,
            'estatisticas' => $this->estatisticas
        ];
    }
    
    private function backupPlainSQL($incluirDados) {
        logMessage("Gerando backup SQL sem compressão...");
        
        $handle = fopen($this->arquivoBackup, 'w');
        if (!$handle) {
            throw new Exception("Não foi possível criar arquivo de backup");
        }
        
        try {
            // Cabeçalho do backup
            $this->escreverCabecalho($handle);
            
            // Backup de schema
            $this->backupSchema($handle);
            
            // Backup de procedures e functions
            $this->backupProcedures($handle);
            $this->backupFunctions($handle);
            
            // Backup de triggers
            $this->backupTriggers($handle);
            
            // Backup de dados se solicitado
            if ($incluirDados) {
                $this->backupDados($handle);
            }
            
            // Rodapé
            $this->escreverRodape($handle);
            
        } finally {
            fclose($handle);
        }
        
        logMessage("Backup SQL concluído: " . formatarBytes(filesize($this->arquivoBackup)));
    }
    
    private function backupComGZIP($incluirDados) {
        logMessage("Gerando backup SQL com compressão GZIP...");
        
        $handle = gzopen($this->arquivoBackup, 'w9');
        if (!$handle) {
            throw new Exception("Não foi possível criar arquivo comprimido");
        }
        
        try {
            // Cabeçalho do backup
            $this->escreverCabecalhoGZIP($handle);
            
            // Backup de schema
            $this->backupSchemaGZIP($handle);
            
            // Backup de procedures e functions
            $this->backupProceduresGZIP($handle);
            $this->backupFunctionsGZIP($handle);
            
            // Backup de triggers
            $this->backupTriggersGZIP($handle);
            
            // Backup de dados se solicitado
            if ($incluirDados) {
                $this->backupDadosGZIP($handle);
            }
            
            // Rodapé
            $this->escreverRodapeGZIP($handle);
            
        } finally {
            gzclose($handle);
        }
        
        logMessage("Backup comprimido concluído: " . formatarBytes(filesize($this->arquivoBackup)));
    }
    
    private function escreverCabecalho($handle) {
        $cabecalho = <<<'SQL'
-- ============================================
-- Backup SIGEP - Estrutura Completa
-- Data: {date('Y-m-d H:i:s')}
-- Banco: {$this->getDatabaseName()}
-- Versão: {phpversion()}
-- ============================================

SQL;
        fwrite($handle, $cabecalho);
    }
    
    private function escreverCabecalhoGZIP($handle) {
        $cabecalho = <<<'SQL'
-- ============================================
-- Backup SIGEP - Estrutura Completa (Comprimido)
-- Data: {date('Y-m-d H:i:s')}
-- Banco: {$this->getDatabaseName()}
-- Versão: {phpversion()}
-- ============================================

SQL;
        gzwrite($handle, $cabecalho);
    }
    
    private function backupSchema($handle) {
        logMessage("Fazendo backup do schema...");
        
        $tabelas = $this->getListarTabelas();
        
        foreach ($tabelas as $tabela) {
            logMessage("  - Tabela: $tabela");
            
            $createTable = $this->getCreateTableSQL($tabela);
            fwrite($handle, $createTable . "\n\n");
        }
        
        logMessage("Schema backup concluído");
    }
    
    private function backupSchemaGZIP($handle) {
        logMessage("Fazendo backup do schema (comprimido)...");
        
        $tabelas = $this->getListarTabelas();
        
        foreach ($tabelas as $tabela) {
            $createTable = $this->getCreateTableSQL($tabela);
            gzwrite($handle, $createTable . "\n\n");
        }
        
        logMessage("Schema backup concluído (comprimido)");
    }
    
    private function backupDados($handle) {
        logMessage("Fazendo backup dos dados...");
        
        $tabelas = $this->getListarTabelas();
        $totalDados = 0;
        
        foreach ($tabelas as $tabela) {
            $count = $this->getCountTabela($tabela);
            $totalDados += $count;
            
            if ($count > 0) {
                logMessage("  - Tabela: $tabela ($count registros)");
                $this->escreverInsertsTabela($handle, $tabela);
            }
        }
        
        $this->estatisticas['total_registros'] = $totalDados;
        logMessage("Backup de dados concluído: $totalDados registros");
    }
    
    private function backupDadosGZIP($handle) {
        logMessage("Fazendo backup dos dados (comprimido)...");
        
        $tabelas = $this->getListarTabelas();
        $totalDados = 0;
        
        foreach ($tabelas as $tabela) {
            $count = $this->getCountTabela($tabela);
            $totalDados += $count;
            
            if ($count > 0) {
                $this->escreverInsertsTabelaGZIP($handle, $tabela);
            }
        }
        
        $this->estatisticas['total_registros'] = $totalDados;
        logMessage("Backup de dados concluído (comprimido): $totalDados registros");
    }
    
    private function backupProcedures($handle) {
        logMessage("Fazendo backup de procedures...");
        
        $procedures = $this->getListarProcedures();
        
        foreach ($procedures as $procedure) {
            $createProcedure = $this->getCreateProcedureSQL($procedure);
            fwrite($handle, $createProcedure . "\n\n");
        }
        
        logMessage("Backup de procedures concluído: " . count($procedures) . " procedures");
    }
    
    private function backupProceduresGZIP($handle) {
        $procedures = $this->getListarProcedures();
        
        foreach ($procedures as $procedure) {
            $createProcedure = $this->getCreateProcedureSQL($procedure);
            gzwrite($handle, $createProcedure . "\n\n");
        }
    }
    
    private function backupFunctions($handle) {
        logMessage("Fazendo backup de functions...");
        
        $functions = $this->getListarFunctions();
        
        foreach ($functions as $function) {
            $createFunction = $this->getCreateFunctionSQL($function);
            fwrite($handle, $createFunction . "\n\n");
        }
        
        logMessage("Backup de functions concluído: " . count($functions) . " functions");
    }
    
    private function backupFunctionsGZIP($handle) {
        $functions = $this->getListarFunctions();
        
        foreach ($functions as $function) {
            $createFunction = $this->getCreateFunctionSQL($function);
            gzwrite($handle, $createFunction . "\n\n");
        }
    }
    
    private function backupTriggers($handle) {
        logMessage("Fazendo backup de triggers...");
        
        $triggers = $this->getListarTriggers();
        
        foreach ($triggers as $trigger) {
            $createTrigger = $this->getCreateTriggerSQL($trigger);
            fwrite($handle, $createTrigger . "\n\n");
        }
        
        logMessage("Backup de triggers concluído: " . count($triggers) . " triggers");
    }
    
    private function backupTriggersGZIP($handle) {
        $triggers = $this->getListarTriggers();
        
        foreach ($triggers as $trigger) {
            $createTrigger = $this->getCreateTriggerSQL($trigger);
            gzwrite($handle, $createTrigger . "\n\n");
        }
    }
    
    private function escreverRodape($handle) {
        $rodape = <<<'SQL'

-- ============================================
-- Fim do Backup
-- Data: {date('Y-m-d H:i:s')}
-- ============================================

SQL;
        fwrite($handle, $rodape);
    }
    
    private function escreverRodapeGZIP($handle) {
        $rodape = <<<'SQL'

-- ============================================
-- Fim do Backup (Comprimido)
-- Data: {date('Y-m-d H:i:s')}
-- ============================================

SQL;
        gzwrite($handle, $rodape);
    }
    
    // Métodos auxiliares
    private function getDatabaseName() {
        $stmt = $this->pdo->query("SELECT DATABASE()");
        return $stmt->fetchColumn();
    }
    
    private function getListarTabelas() {
        $stmt = $this->pdo->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getCreateTableSQL($tabela) {
        $stmt = $this->pdo->query("SHOW CREATE TABLE `$tabela`");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['Create Table'] ?? '';
    }
    
    private function getCountTabela($tabela) {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM `$tabela`");
        return $stmt->fetchColumn();
    }
    
    private function escreverInsertsTabela($handle, $tabela) {
        fwrite($handle, "-- Dados da tabela: $tabela\n");
        
        $stmt = $this->pdo->query("SELECT * FROM `$tabela`");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($colunas)) {
            fwrite($handle, "-- Tabela vazia\n\n");
            return;
        }
        
        $nomesColunas = array_keys($colunas[0]);
        $valoresColunas = array_fill(0, count($nomesColunas), '?');
        
        // INSERT INTO
        $insertSQL = "INSERT INTO `$tabela` (`" . implode('`, `', $nomesColunas) . "`) VALUES ";
        $insertSQL .= "(" . implode(', ', $valoresColunas) . ")";
        
        fwrite($handle, "LOCK TABLES `$tabela` WRITE;\n");
        fwrite($handle, $insertSQL . ";\n");
        
        foreach ($colunas as $linha) {
            $valores = array_map([$this, 'escaparValorSQL'], $linha);
            $insertSQL = "INSERT INTO `$tabela` (`" . implode('`, `', $nomesColunas) . "`) VALUES (" . implode(', ', $valores) . ");";
            fwrite($handle, $insertSQL . "\n");
        }
        
        fwrite($handle, "UNLOCK TABLES;\n\n");
    }
    
    private function escreverInsertsTabelaGZIP($handle, $tabela) {
        gzwrite($handle, "-- Dados da tabela: $tabela\n");
        
        $stmt = $this->pdo->query("SELECT * FROM `$tabela`");
        $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($colunas)) {
            gzwrite($handle, "-- Tabela vazia\n\n");
            return;
        }
        
        $nomesColunas = array_keys($colunas[0]);
        $valoresColunas = array_fill(0, count($nomesColunas), '?');
        
        // INSERT INTO
        $insertSQL = "INSERT INTO `$tabela` (`" . implode('`, `', $nomesColunas) . "`) VALUES ";
        $insertSQL .= "(" . implode(', ', $valoresColunas) . ")";
        
        gzwrite($handle, "LOCK TABLES `$tabela` WRITE;\n");
        gzwrite($handle, $insertSQL . ";\n");
        
        foreach ($colunas as $linha) {
            $valores = array_map([$this, 'escaparValorSQL'], $linha);
            $insertSQL = "INSERT INTO `$tabela` (`" . implode('`, `', $nomesColunas) . "`) VALUES (" . implode(', ', $valores) . ");";
            gzwrite($handle, $insertSQL . "\n");
        }
        
        gzwrite($handle, "UNLOCK TABLES;\n\n");
    }
    
    private function getListarProcedures() {
        $stmt = $this->pdo->query("SHOW PROCEDURE STATUS WHERE Db = '" . $this->getDatabaseName() . "'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getCreateProcedureSQL($procedure) {
        $stmt = $this->pdo->query("SHOW CREATE PROCEDURE `$procedure`");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['Create Procedure'] ?? '';
    }
    
    private function getListarFunctions() {
        $stmt = $this->pdo->query("SHOW FUNCTION STATUS WHERE Db = '" . $this->getDatabaseName() . "'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function getCreateFunctionSQL($function) {
        $stmt = $this->pdo->query("SHOW CREATE FUNCTION `$function`");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['Create Function'] ?? '';
    }
    
    private function getListarTriggers() {
        $stmt = $this->pdo->query("SHOW TRIGGERS");
        $triggers = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $triggers[] = $row['Trigger'];
        }
        
        return $triggers;
    }
    
    private function getCreateTriggerSQL($trigger) {
        $stmt = $this->pdo->query("SHOW CREATE TRIGGER `$trigger`");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['SQL'] ?? '';
    }
    
    private function escaparValorSQL($valor) {
        if ($valor === null) {
            return 'NULL';
        }
        
        if (is_bool($valor)) {
            return $valor ? 'TRUE' : 'FALSE';
        }
        
        return "'" . addslashes($valor) . "'";
    }
    
    private function calcularEstatisticas($duracao) {
        $tamanhoArquivo = filesize($this->arquivoBackup);
        
        $this->estatisticas = [
            'duracao_minutos' => $duracao,
            'tamanho_arquivo' => formatarBytes($tamanhoArquivo),
            'tabelas' => count($this->getListarTabelas()),
            'procedures' => count($this->getListarProcedures()),
            'functions' => count($this->getListarFunctions()),
            'triggers' => count($this->getListarTriggers()),
            'data_hora' => date('Y-m-d H:i:s')
        ];
    }
    
    private function mostrarResultados() {
        $stats = $this->estatisticas;
        
        echo "\n=== ESTATÍSTICAS DO BACKUP ===\n";
        echo "Arquivo: " . basename($this->arquivoBackup) . "\n";
        echo "Checksum: {$this->arquivoChecksum}\n";
        echo "Duração: {$stats['duracao_minutos']} minutos\n";
        echo "Tamanho: {$stats['tamanho_arquivo']}\n";
        echo "Data/Hora: {$stats['data_hora']}\n\n";
        
        echo "ESTRUTURA:\n";
        echo "- Tabelas: {$stats['tabelas']}\n";
        echo "- Procedures: {$stats['procedures']}\n";
        echo "- Functions: {$stats['functions']}\n";
        echo "- Triggers: {$stats['triggers']}\n";
        
        if (isset($stats['total_registros'])) {
            echo "- Registros: {$stats['total_registros']}\n";
        }
        
        echo "\nBackup concluído com sucesso!\n";
    }
}

// Execução principal
try {
    logMessage("=== INÍCIO DO BACKUP ESTRUTURAL COMPLETO ===");
    logMessage("Diretório: $diretorioDestino");
    logMessage("Opções: " . json_encode($options));
    
    // Verificar configuração do banco
    if (!isset($config['host']) || !isset($config['dbname'])) {
        logMessage("ERRO: Configuração do banco não encontrada", 'ERROR');
        exit(1);
    }
    
    // Executar backup
    $backup = new BackupEstruturalSIGEP($config);
    $resultado = $backup->executarBackup($incluirDados, $usarCompressao);
    
    // Enviar notificação por email se solicitado
    if ($enviarEmail) {
        enviarEmailBackup($resultado['arquivo'], $resultado['checksum']);
    }
    
    // Salvar informações do backup
    $infoBackup = [
        'arquivo' => $resultado['arquivo'],
        'checksum' => $resultado['checksum'],
        'estatisticas' => $resultado['estatisticas'],
        'opcoes' => $options,
        'data_execucao' => date('Y-m-d H:i:s')
    ];
    
    $infoFile = $diretorioBackup . '/backup_info.json';
    file_put_contents($infoFile, json_encode($infoBackup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    logMessage("Informações do backup salvas em: $infoFile");
    logMessage("=== BACKUP CONCLUÍDO COM SUCESSO ===");
    
} catch (Exception $e) {
    logMessage("ERRO FATAL: " . $e->getMessage(), 'ERROR');
    exit(1);
}
?>
