<?php
// Executor de Jobs em Background
// Este arquivo é chamado via execução assíncrona

require_once __DIR__ . '/../../../conf/db.php';

// Configurar Timezone
date_default_timezone_set('America/Sao_Paulo');

// Obter parâmetros da linha de comando
$jobId = $argv[1] ?? null;
$execucaoId = $argv[2] ?? null;

if (!$jobId || !$execucaoId) {
    die("Uso: php job_executor.php <job_id> <execucao_id>\n");
}

try {
    $config = require __DIR__ . '/../../../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Buscar dados do job
    $stmt = $pdo->prepare("SELECT * FROM servicos_jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();

    if (!$job) {
        // Atualizar execução como erro
        $stmt = $pdo->prepare("UPDATE servicos_execucoes SET status = 'erro', data_fim = NOW(), saida_erro = 'Job não encontrado' WHERE id = ?");
        $stmt->execute([$execucaoId]);

        // Atualizar status do job
        $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'erro' WHERE id = ?");
        $stmt->execute([$jobId]);
        exit;
    }

    // Executar o comando
    $startTime = microtime(true);
    $output = "";
    $errorOutput = "";
    $returnCode = 0;

    try {
        $agendamentoConfig = $job['agendamento_config'] ? json_decode($job['agendamento_config'], true) : [];
        $compactar = ($agendamentoConfig['compactar'] ?? 0) == 1;

        $command = $job['comando'];
        $diretorioTrabalho = $job['diretorio_trabalho'] ?: 'C:\\Servicos\\Backup\\SIGEP\\db';

        // Processar carimbos de data/hora
        $timestamp = date('dmY_His');
        $command = str_replace('{timestamp}', $timestamp, $command);

        if (strpos($command, 'mysqldump') !== false && strpos($command, '>') !== false) {
            if (strpos($command, '{timestamp}') === false) {
                $command = preg_replace('/\.sql/i', '_' . $timestamp . '.sql', $command);
            }
        }

        if (!empty($job['diretorio_trabalho'])) {
            $command = 'cd "' . $job['diretorio_trabalho'] . '" && ' . $command;
        }

        // Executar comando
        $descriptorspec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        // Mudar para diretório de trabalho para evitar conflitos
        $workingDir = $job['diretorio_trabalho'] ?: sys_get_temp_dir();

        $process = proc_open($command, $descriptorspec, $pipes, $workingDir);

        if (is_resource($process)) {
            fclose($pipes[0]);

            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $errorOutput = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);
        }

        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Compactação pós-execução se necessário
        if ($compactar) {
            $originalFile = '';
            $zipFile = '';

            // Identificar arquivo gerado pelo backup
            if (strpos($command, 'mysqldump') !== false && strpos($command, '>') !== false) {
                // Backup do banco - encontrar o arquivo SQL gerado
                if (preg_match('/> "([^"]+)"/', $command, $matches)) {
                    $originalFile = $matches[1];
                    $zipFile = preg_replace('/\.sql$/', '.zip', $originalFile);
                }
            } elseif (strpos($command, 'Compress-Archive') !== false) {
                // Backup do site com compressão já no comando - não precisa fazer nada
                $compactar = false; // Desativar compactação dupla
            }

            // Compactar arquivo SQL para ZIP
            if ($compactar && $originalFile && file_exists($originalFile)) {
                $compressCommand = "powershell -Command \"Compress-Archive -Path '$originalFile' -DestinationPath '$zipFile' -Force\"";
                exec($compressCommand, $compressOutput, $compressReturnCode);

                if ($compressReturnCode === 0 && file_exists($zipFile)) {
                    // Remover arquivo SQL original após compactação bem-sucedida
                    unlink($originalFile);
                    $output .= "\n\n=== COMPACTAÇÃO REALIZADA ===\n";
                    $output .= "Arquivo original: " . basename($originalFile) . "\n";
                    $output .= "Arquivo compactado: " . basename($zipFile) . "\n";
                    $output .= "Tamanho ZIP: " . number_format(filesize($zipFile)/1024/1024, 2) . " MB\n";
                    $output .= "========================\n";
                } else {
                    $errorOutput .= "\n\n=== ERRO NA COMPACTAÇÃO ===\n";
                    $errorOutput .= "Comando: $compressCommand\n";
                    $errorOutput .= "Return Code: $compressReturnCode\n";
                    $errorOutput .= "Output: " . implode("\n", $compressOutput) . "\n";
                    $errorOutput .= "========================\n";
                }
            }
        }

        // Retenção de arquivos
        if ($compactar && isset($agendamentoConfig['retencao']['habilitado']) && $agendamentoConfig['retencao']['habilitado']) {
            $retencao = $agendamentoConfig['retencao'];
            $valor = $retencao['valor'] ?? 1;
            $unidade = $retencao['unidade'] ?? 'semanas';

            // Calcular data de corte
            $dataCorte = new DateTime();
            switch ($unidade) {
                case 'dias':
                    $dataCorte->sub(new DateInterval("P{$valor}D"));
                    break;
                case 'semanas':
                    $dataCorte->sub(new DateInterval("P{$valor}W"));
                    break;
                case 'meses':
                    $dataCorte->sub(new DateInterval("P{$valor}M"));
                    break;
                case 'anos':
                    $dataCorte->sub(new DateInterval("P{$valor}Y"));
                    break;
            }

            // Identificar pasta de backup pelo comando
            $backupDir = '';
            if (strpos($command, 'C:\\Servicos\\Backup\\SIGEP\\site') !== false) {
                $backupDir = 'C:\\Servicos\\Backup\\SIGEP\\site';
            } elseif (strpos($command, 'C:\\Servicos\\Backup\\SIGEP\\db') !== false) {
                $backupDir = 'C:\\Servicos\\Backup\\SIGEP\\db';
            }

            if ($backupDir && is_dir($backupDir)) {
                // Listar arquivos de backup
                $arquivos = glob($backupDir . '\\*.zip');
                $arquivosExcluidos = [];

                foreach ($arquivos as $arquivo) {
                    $dataArquivo = new DateTime();
                    $dataArquivo->setTimestamp(filemtime($arquivo));

                    // Se arquivo for mais antigo que data de corte, excluir
                    if ($dataArquivo < $dataCorte) {
                        if (unlink($arquivo)) {
                            $arquivosExcluidos[] = basename($arquivo);
                        }
                    }
                }

                // Log dos arquivos excluídos
                if (!empty($arquivosExcluidos)) {
                    $output .= "\n\n=== RETENÇÃO EXECUTADA ===\n";
                    $output .= "Data de corte: " . $dataCorte->format('d/m/Y H:i') . "\n";
                    $output .= "Arquivos excluídos (" . count($arquivosExcluidos) . "):\n";
                    $output .= "- " . implode("\n- ", $arquivosExcluidos) . "\n";
                    $output .= "========================\n";
                }
            }
        }

        // Atualizar execução como sucesso
        $stmt = $pdo->prepare("UPDATE servicos_execucoes SET status = 'sucesso', data_fim = NOW(), codigo_saida = ?, saida_padrao = ?, saida_erro = ?, duracao_segundos = ? WHERE id = ?");
        $stmt->execute([$returnCode, $output, $errorOutput, $duration, $execucaoId]);

        // Atualizar status do job
        $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'ativo' WHERE id = ?");
        $stmt->execute([$jobId]);

    } catch (Exception $e) {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);

        // Atualizar execução como erro
        $stmt = $pdo->prepare("UPDATE servicos_execucoes SET status = 'erro', data_fim = NOW(), codigo_saida = 1, saida_erro = ?, duracao_segundos = ? WHERE id = ?");
        $stmt->execute([$e->getMessage(), $duration, $execucaoId]);

        // Atualizar status do job
        $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'erro' WHERE id = ?");
        $stmt->execute([$jobId]);
    }

} catch (Exception $e) {
    die("Erro no executor: " . $e->getMessage() . "\n");
}
?>
