<?php
// SIGEP Job Manager - Controller
// Gerenciador de Tarefas e Serviços

session_start();
require_once __DIR__ . '/../../../conf/db.php';

// Headers para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Tratamento de erros
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Configurar Timezone para São Paulo
date_default_timezone_set('America/Sao_Paulo');

// Função para retornar erro JSON
function returnError($message, $code = 500) {
    http_response_code($code);
    echo json_encode(['error' => $message, 'code' => $code]);
    exit;
}

// Verificar se a execução é via CLI
if (php_sapi_name() === 'cli') {
    $params = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '=') !== false) {
            list($key, $value) = explode('=', $arg);
            $params[$key] = $value;
        }
    }

    // Configuração do banco de dados (necessária para CLI)
    $config = require __DIR__ . '/../../../conf/db.php';
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        // Configurar timezone na conexão MySQL
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
        // Log de erro de conexão CLI para arquivo fixo
        $cliLog = 'C:\Servicos\Backup\SIGEP\log\job_manager_cli_error.log';
        file_put_contents($cliLog, date('[Y-m-d H:i:s] ') . "Erro PDO CLI: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        die('Erro na conexão CLI: ' . $e->getMessage());
    }

    if (isset($params['action'])) {
        if ($params['action'] === 'executar_job_cli') {
            echo json_encode(executarJob($pdo, $params['id']));
            exit;
        }

        if ($params['action'] === 'processar_fila_cli') {
            // Log de início de processamento
            $cliLog = 'C:\Servicos\Backup\SIGEP\log\job_manager_cli.log';
            file_put_contents($cliLog, date('[Y-m-d H:i:s] ') . "Iniciando processar_fila_cli" . PHP_EOL, FILE_APPEND);

            $sql = "SELECT id, nome FROM servicos_jobs WHERE status = 'ativo' AND (proximo_execucao <= NOW() OR proximo_execucao IS NULL) ORDER BY priority DESC";
            $stmt = $pdo->query($sql);
            $jobs = $stmt->fetchAll();

            $resultados = [];
            foreach ($jobs as $job) {
                file_put_contents($cliLog, date('[Y-m-d H:i:s] ') . "Executando Job: " . $job['nome'] . PHP_EOL, FILE_APPEND);
                $resultados[] = [
                    'job' => $job['nome'],
                    'resultado' => executarJob($pdo, $job['id'])
                ];
            }
            echo json_encode($resultados);
            exit;
        }
    }
}

// Verificar autenticação (apenas se não for CLI)
if (php_sapi_name() !== 'cli') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }

    // Verificar permissão
    if (!isset($_SESSION['user_admin']) || $_SESSION['user_admin'] !== true) {
        header('Location: /index.php');
        exit;
    }
}

// Configuração do banco de dados
$config = require __DIR__ . '/../../../conf/db.php';

    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        // Configurar timezone na conexão MySQL
        $pdo->exec("SET time_zone = '-03:00'");
    } catch (PDOException $e) {
    returnError('Erro na conexão com o banco de dados: ' . $e->getMessage(), 500);
}

// Processar requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        try {
            switch ($_POST['action']) {
                case 'listar_jobs':
                    echo json_encode(listarJobs($pdo));
                    break;

                case 'salvar_job':
                    $id = $_POST['id'] ?? null;
                    $nome = $_POST['nome'];
                    $descricao = $_POST['descricao'];
                    $tipo = $_POST['tipo'];
                    $comando = $_POST['comando'];
                    $diretorio_trabalho = $_POST['diretorio_trabalho'];
                    $executar_como = $_POST['executar_como'];
                    $agendamento_tipo = $_POST['agendamento_tipo'];
                    $agendamento_config = $_POST['agendamento_config'];
                    $proximo_execucao = !empty($_POST['proxima_execucao']) ? $_POST['proxima_execucao'] : null;
                    $priority = $_POST['priority'] ?? 5;
                    $timeout = $_POST['timeout'] ?? 3600;
                    $tentativas_max = $_POST['tentativas_max'] ?? 3;
                    $email_notificar = $_POST['email_notificar'] ?? null;
                    $log_arquivo = $_POST['log_arquivo'] ?? null;

                    try {
                        if ($id) {
                            $sql = "UPDATE servicos_jobs SET
                                    nome = ?, descricao = ?, tipo = ?, comando = ?, diretorio_trabalho = ?,
                                    executar_como = ?, agendamento_tipo = ?, agendamento_config = ?,
                                    proximo_execucao = ?, priority = ?, timeout = ?, tentativas_max = ?,
                                    email_notificar = ?, log_arquivo = ?
                                    WHERE id = ?";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $nome, $descricao, $tipo, $comando, $diretorio_trabalho,
                                $executar_como, $agendamento_tipo, $agendamento_config,
                                $proximo_execucao, $priority, $timeout, $tentativas_max,
                                $email_notificar, $log_arquivo, $id
                            ]);
                            echo json_encode(['success' => 'Job atualizado com sucesso']);
                        } else {
                            $sql = "INSERT INTO servicos_jobs (
                                    nome, descricao, tipo, comando, diretorio_trabalho,
                                    executar_como, agendamento_tipo, agendamento_config,
                                    proximo_execucao, status, priority, timeout, tentativas_max,
                                    email_notificar, log_arquivo, criado_em
                                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ativo', ?, ?, ?, ?, ?, NOW())";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                $nome, $descricao, $tipo, $comando, $diretorio_trabalho,
                                $executar_como, $agendamento_tipo, $agendamento_config,
                                $proximo_execucao, $priority, $timeout, $tentativas_max,
                                $email_notificar, $log_arquivo
                            ]);
                            echo json_encode(['success' => 'Job criado com sucesso']);
                        }
                    } catch (PDOException $e) {
                        returnError('Erro ao salvar no banco: ' . $e->getMessage(), 500);
                    }
                    break;

                case 'excluir_job':
                    echo json_encode(excluirJob($pdo, $_POST['id']));
                    break;

                case 'executar_job':
                    echo json_encode(executarJob($pdo, $_POST['id']));
                    break;

                case 'pausar_job':
                    echo json_encode(pausarJob($pdo, $_POST['id']));
                    break;

                case 'ativar_job':
                    echo json_encode(ativarJob($pdo, $_POST['id']));
                    break;

                case 'verificar_progresso':
                    echo json_encode(verificarProgresso($pdo, $_POST['execucao_id']));
                    break;

                case 'listar_execucoes':
                    echo json_encode(listarExecucoes($pdo, $_POST['job_id']));
                    break;

                case 'instalar_servico':
                    echo json_encode(instalarServicoWindows($pdo, $_POST));
                    break;

                case 'remover_servico':
                    echo json_encode(removerServicoWindows($pdo, $_POST));
                    break;

                case 'listar_servicos':
                    echo json_encode(listarServicosWindows($pdo));
                    break;

                case 'ver_log_execucao':
                    echo json_encode(verLogExecucao($pdo, $_POST['id']));
                    break;

                case 'salvar_agendamento':
                    echo json_encode(salvarAgendamento($pdo, $_POST));
                    break;

                case 'listar_agendamentos':
                    echo json_encode(listarAgendamentos($pdo, $_POST['job_id']));
                    break;

                default:
                    returnError('Ação não reconhecida', 400);
            }
        } catch (Exception $e) {
            returnError('Erro ao processar requisição: ' . $e->getMessage(), 500);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Funções do Controller

function verificarProgresso($pdo, $execucao_id) {
    $stmt = $pdo->prepare("SELECT e.*, j.nome as job_nome FROM servicos_execucoes e LEFT JOIN servicos_jobs j ON e.job_id = j.id WHERE e.id = ?");
    $stmt->execute([$execucao_id]);
    $execucao = $stmt->fetch();

    if (!$execucao) {
        return ['error' => 'Execução não encontrada'];
    }

    // Calcular progresso baseado no status
    $progress = 0;
    $status_text = $execucao['status'];

    switch ($execucao['status']) {
        case 'executando':
            // Estimar progresso baseado no tempo decorrido
            if ($execucao['data_inicio']) {
                $inicio = new DateTime($execucao['data_inicio']);
                $agora = new DateTime();
                $decorrido = $agora->getTimestamp() - $inicio->getTimestamp();

                // Estimar tempo total baseado no tipo de job (em segundos)
                $tempo_estimado = 60; // 1 minuto para backup de site
                if (strpos($execucao['job_nome'], 'Banco') !== false) {
                    $tempo_estimado = 30; // 30 segundos para backup de banco
                }

                $progress = min(95, round(($decorrido / $tempo_estimado) * 100));
                $status_text = "Executando... {$progress}%";
            }
            break;
        case 'sucesso':
            $progress = 100;
            $status_text = "Concluído com sucesso";
            break;
        case 'erro':
            $progress = 0;
            $status_text = "Erro na execução";
            break;
        default:
            $progress = 0;
    }

    return [
        'execucao_id' => $execucao_id,
        'status' => $execucao['status'],
        'status_text' => $status_text,
        'progress' => $progress,
        'data_inicio' => $execucao['data_inicio'],
        'duracao_segundos' => $execucao['duracao_segundos'],
        'saida_padrao' => substr($execucao['saida_padrao'] ?? '', 0, 500),
        'saida_erro' => substr($execucao['saida_erro'] ?? '', 0, 500)
    ];
}

function listarJobs($pdo) {
    $sql = "
        SELECT j.*,
               (SELECT COUNT(*) FROM servicos_execucoes e WHERE e.job_id = j.id) as total_execucoes,
               (SELECT COUNT(*) FROM servicos_execucoes e WHERE e.job_id = j.id AND e.status = 'sucesso' AND e.data_inicio >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as execucoes_sucesso_7dias,
               (SELECT MAX(e.data_inicio) FROM servicos_execucoes e WHERE e.job_id = j.id) as ultima_execucao_real
        FROM servicos_jobs j
        ORDER BY j.status DESC, j.priority DESC, j.nome
    ";

    $stmt = $pdo->query($sql);
    $jobs = $stmt->fetchAll();

    // Formatar dados
    foreach ($jobs as &$job) {
        $job['agendamento_config'] = $job['agendamento_config'] ? json_decode($job['agendamento_config'], true) : null;
        $job['parametros'] = $job['parametros'] ? json_decode($job['parametros'], true) : null;

        // Calcular próxima execução baseada no agendamento
        $job['proximo_execucao_formatada'] = 'NÃO AGENDADO';
        if ($job['agendamento_tipo'] && $job['ultima_execucao']) {
            $ultima = new DateTime($job['ultima_execucao']);
            $config = $job['agendamento_config'];

            switch ($job['agendamento_tipo']) {
                case 'horas':
                    $intervalo = $config['intervalo_valor'] ?? 24;
                    $proxima = (clone $ultima)->add(new DateInterval("PT{$intervalo}H"));
                    $job['proximo_execucao_formatada'] = $proxima->format('d/m/Y H:i');
                    break;
                case 'minutos':
                    $intervalo = $config['intervalo_valor'] ?? 60;
                    $proxima = (clone $ultima)->add(new DateInterval("PT{$intervalo}M"));
                    $job['proximo_execucao_formatada'] = $proxima->format('d/m/Y H:i');
                    break;
                case 'diario':
                    $proxima = (clone $ultima)->add(new DateInterval('P1D'));
                    $job['proximo_execucao_formatada'] = $proxima->format('d/m/Y H:i');
                    break;
                case 'semanal':
                    $proxima = (clone $ultima)->add(new DateInterval('P1W'));
                    $job['proximo_execucao_formatada'] = $proxima->format('d/m/Y H:i');
                    break;
                case 'mensal':
                    $proxima = (clone $ultima)->add(new DateInterval('P1M'));
                    $job['proximo_execucao_formatada'] = $proxima->format('d/m/Y H:i');
                    break;
            }
        }

        $job['ultima_execucao_formatada'] = $job['ultima_execucao'] ? date('d/m/Y H:i', strtotime($job['ultima_execucao'])) : '-';
        $job['criado_em_formatada'] = date('d/m/Y H:i', strtotime($job['criado_em']));
    }

    return ['jobs' => $jobs];
}

function salvarJob($pdo, $data) {
    $id = $data['id'] ?? null;

    // Validar dados
    if (empty($data['nome']) || empty($data['comando'])) {
        return ['error' => 'Nome e comando são obrigatórios'];
    }

    // Preparar dados
    $jobData = [
        'nome' => $data['nome'],
        'descricao' => $data['descricao'] ?? '',
        'tipo' => $data['tipo'] ?? 'script',
        'comando' => $data['comando'],
        'parametros' => !empty($data['parametros']) ? json_encode($data['parametros']) : null,
        'diretorio_trabalho' => $data['diretorio_trabalho'] ?? null,
        'executar_como' => $data['executar_como'] ?? 'SYSTEM',
        'agendamento_tipo' => $data['agendamento_tipo'] ?? 'unico',
        'agendamento_config' => !empty($data['agendamento_config']) ? json_encode($data['agendamento_config']) : null,
        'proximo_execucao' => !empty($data['proximo_execucao']) ? date('Y-m-d H:i:s', strtotime($data['proximo_execucao'])) : null,
        'priority' => $data['priority'] ?? 5,
        'timeout' => $data['timeout'] ?? 3600,
        'tentativas_max' => $data['tentativas_max'] ?? 3,
        'email_notificar' => $data['email_notificar'] ?? null,
        'log_arquivo' => $data['log_arquivo'] ?? null,
        'alterado_por' => $_SESSION['user_id'],
        'alterado_em' => date('Y-m-d H:i:s')
    ];

    if ($id) {
        // Atualizar
        $fields = [];
        foreach ($jobData as $field => $value) {
            $fields[] = "$field = ?";
        }

        $sql = "UPDATE servicos_jobs SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $values = array_values($jobData);
        $values[] = $id;
        $stmt->execute($values);

        return ['success' => 'Job atualizado com sucesso', 'id' => $id];
    } else {
        // Inserir
        $jobData['criado_por'] = $_SESSION['user_id'];
        $jobData['criado_em'] = date('Y-m-d H:i:s');
        $jobData['status'] = 'ativo';

        $fields = implode(', ', array_keys($jobData));
        $placeholders = str_repeat('?,', count($jobData) - 1) . '?';

        $sql = "INSERT INTO servicos_jobs ($fields) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($jobData));

        return ['success' => 'Job criado com sucesso', 'id' => $pdo->lastInsertId()];
    }
}

function excluirJob($pdo, $id) {
    // Excluir permanentemente o job
    $stmt = $pdo->prepare("DELETE FROM servicos_jobs WHERE id = ?");
    $stmt->execute([$id]);

    // Excluir execuções relacionadas
    $stmt = $pdo->prepare("DELETE FROM servicos_execucoes WHERE job_id = ?");
    $stmt->execute([$id]);

    // Excluir agendamentos relacionados
    $stmt = $pdo->prepare("DELETE FROM servicos_agendamentos WHERE job_id = ?");
    $stmt->execute([$id]);

    // Excluir dependências relacionadas
    $stmt = $pdo->prepare("DELETE FROM servicos_dependencias WHERE job_id = ? OR job_dependente_id = ?");
    $stmt->execute([$id, $id]);

    return ['success' => 'Job excluído permanentemente com sucesso'];
}

function executarJob($pdo, $id) {
    // Verificar se é execução manual
    $isManual = isset($_POST['manual']) && $_POST['manual'] == 1;

    // Verificar status e carregar TODOS os dados necessários para a execução
    $stmt = $pdo->prepare("SELECT * FROM servicos_jobs WHERE id = ?");
    $stmt->execute([$id]);
    $job = $stmt->fetch();

    if (!$job) {
        return ['error' => 'Job não encontrado'];
    }

    if ($job['status'] === 'executando') {
        return ['error' => 'Job já está em execução'];
    }

    // Criar registro de execução
    $stmt = $pdo->prepare("INSERT INTO servicos_execucoes (job_id, data_inicio, status, processo_id, maquina_execucao) VALUES (?, NOW(), 'executando', ?, ?)");
    $processId = getmypid();
    $hostname = gethostname();
    $stmt->execute([$id, $processId, $hostname]);

    $execucao_id = $pdo->lastInsertId();

    // Atualizar status do job para executando
    $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'executando', ultima_execucao = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    // Retornar imediatamente com o ID da execução para acompanhamento

    // Iniciar executor em background
    $executorPath = __DIR__ . '/job_executor.php';
    $command = "php \"$executorPath\" $id $execucao_id > /dev/null 2>&1 &";

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $command = "start /B php \"$executorPath\" $id $execucao_id";
        pclose(popen($command, 'r'));
    } else {
        // Linux/Unix
        exec($command);
    }

    return ['success' => 'Job enviado para execução em background', 'execucao_id' => $execucao_id, 'status' => 'executando'];

    // Executar o comando real do job
    $startTime = microtime(true);
    $output = "";
    $errorOutput = "";
    $returnCode = 0;

    try {
        $agendamentoConfig = $job['agendamento_config'] ? json_decode($job['agendamento_config'], true) : [];
        $compactar = ($agendamentoConfig['compactar'] ?? 0) == 1;

        $command = $job['comando'];
        $diretorioTrabalho = $job['diretorio_trabalho'] ?: 'C:\\Servicos\\Backup\\SIGEP\\db';

        // 1. Processar carimbos de data/hora no comando
        $timestamp = date('dmY_His');
        $command = str_replace('{timestamp}', $timestamp, $command);

        // Se for mysqldump e tiver redirecionamento, ajustar nome do arquivo se necessário
        if (strpos($command, 'mysqldump') !== false && strpos($command, '>') !== false) {
            // Se o comando não tiver {timestamp}, vamos adicionar antes da extensão .sql
            if (strpos($command, '{timestamp}') === false) {
                $command = preg_replace('/\.sql/i', '_' . $timestamp . '.sql', $command);
            }
        }

        if (!empty($job['diretorio_trabalho'])) {
            $command = 'cd "' . $job['diretorio_trabalho'] . '" && ' . $command;
        }

        if (!empty($job['executar_como']) && $job['executar_como'] !== 'SYSTEM') {
            // Em Windows, usar runas para executar como usuário específico
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = 'runas /user:' . $job['executar_como'] . ' ' . $command;
            }
        }

        // Executar e capturar saída
        $outputLines = [];
        $returnCode = 0;

        // Usar proc_open para melhor controle
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];

        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            // Ler stdout e stderr de forma não bloqueante para evitar deadlocks
            stream_set_blocking($pipes[1], 0);
            stream_set_blocking($pipes[2], 0);

            $output = "";
            $errorOutput = "";

            while (!feof($pipes[1]) || !feof($pipes[2])) {
                $read = [$pipes[1], $pipes[2]];
                $write = null;
                $except = null;

                if (stream_select($read, $write, $except, 5) > 0) {
                    foreach ($read as $pipe) {
                        if ($pipe === $pipes[1]) $output .= fread($pipe, 8192);
                        if ($pipe === $pipes[2]) $errorOutput .= fread($pipe, 8192);
                    }
                }
            }

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            $returnCode = proc_close($process);
        }

    } catch (Exception $e) {
        $output = 'Erro ao executar comando: ' . $e->getMessage();
        $returnCode = 1;
    }

    // Calcular duração
    $endTime = microtime(true);
    $duration = round($endTime - $startTime);

    // Escrever no arquivo de log se configurado
    if (!empty($job['log_arquivo'])) {
        try {
            $logDir = dirname($job['log_arquivo']);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logContent = sprintf(
                "========================================\n" .
                "Job: %s (ID: %d)\n" .
                "Data: %s\n" .
                "Status: %s\n" .
                "Duração: %d segundos\n" .
                "Código de Saída: %d\n" .
                "Comando: %s\n\n" .
                "--- OUTPUT ---\n" .
                "%s\n\n" .
                "--- ERROR OUTPUT ---\n" .
                "%s\n" .
                "========================================\n",
                $job['nome'],
                $id,
                date('Y-m-d H:i:s'),
                ($returnCode === 0) ? 'SUCESSO' : 'ERRO',
                $duration,
                $returnCode,
                $job['comando'],
                $output,
                $errorOutput
            );

            file_put_contents($job['log_arquivo'], $logContent, FILE_APPEND | LOCK_EX);
        } catch (Exception $logError) {
            error_log("Erro ao escrever log do job: " . $logError->getMessage());
        }
    }

    // Atualizar execução com resultado real
    $stmt = $pdo->prepare("
        UPDATE servicos_execucoes
        SET data_fim = NOW(), status = ?, codigo_saida = ?, duracao_segundos = ?, saida_padrao = ?, saida_erro = ?
        WHERE id = ?
    ");

    $status = ($returnCode === 0) ? 'sucesso' : 'erro';

    // 2. Lógica de Compactação (ZIP)
    if ($status === 'sucesso' && $compactar) {
        try {
            // Tentar localizar o arquivo gerado (baseado no redirecionamento > no comando)
            if (preg_match('/>\s*"?(.*\.sql)"?/i', $command, $matches)) {
                $fileToZip = trim($matches[1]);
                if (file_exists($fileToZip)) {
                    $zipFile = str_replace('.sql', '.zip', $fileToZip);
                    $zip = new ZipArchive();
                    if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
                        $zip->addFile($fileToZip, basename($fileToZip));
                        $zip->close();
                        unlink($fileToZip); // Remover original após zipar
                        $output .= "\n[INFO] Arquivo compactado com sucesso: " . basename($zipFile);
                    }
                }
            }
        } catch (Exception $zipEx) {
            $output .= "\n[ERRO] Falha na compactação: " . $zipEx->getMessage();
        }
    }

    // 3. Lógica de Retenção (Versionamento)
    $retencao = $agendamentoConfig['retencao'] ?? ['habilitado' => 0];
    if ($status === 'sucesso' && ($retencao['habilitado'] ?? 0) == 1) {
        try {
            $valor = intval($retencao['valor'] ?? 7);
            $unidade = $retencao['unidade'] ?? 'dias';
            $diretorioRetencao = $diretorioTrabalho;

            // Tentar extrair o diretório do comando se não estiver setado
            if (empty($diretorioRetencao) && preg_match('/>\s*"?(.*)"?/i', $command, $matches)) {
                $diretorioRetencao = dirname(trim($matches[1], '"'));
            }

            if (is_dir($diretorioRetencao)) {
                $arquivos = glob($diretorioRetencao . '/*.{sql,zip}', GLOB_BRACE);

                if ($unidade === 'versoes') {
                    // Retenção por número de versões
                    if (count($arquivos) > $valor) {
                        usort($arquivos, function($a, $b) {
                            return filemtime($a) - filemtime($b);
                        });
                        $paraRemover = array_slice($arquivos, 0, count($arquivos) - $valor);
                        foreach ($paraRemover as $f) {
                            unlink($f);
                            $output .= "\n[RETENÇÃO] Versão antiga removida: " . basename($f);
                        }
                    }
                } else {
                    // Retenção por tempo (dias, semanas, meses)
                    $mapaUnidades = ['dias' => 'day', 'semanas' => 'week', 'meses' => 'month'];
                    $tempoLimite = strtotime("-" . $valor . " " . ($mapaUnidades[$unidade] ?? 'day'));

                    foreach ($arquivos as $f) {
                        if (filemtime($f) < $tempoLimite) {
                            unlink($f);
                            $output .= "\n[RETENÇÃO] Arquivo expirado removido: " . basename($f);
                        }
                    }
                }
            }
        } catch (Exception $retEx) {
            $output .= "\n[ERRO] Falha na retenção: " . $retEx->getMessage();
        }
    }

    $stmt->execute([$status, $returnCode, $duration, $output, $errorOutput, $execucao_id]);

    $finalStatus = ($returnCode === 0) ? 'ativo' : 'erro';

    // Calcular próxima execução se for agendamento recorrente E NÃO for execução manual
    $proximaExecucao = null;
    if (!$isManual && $job['agendamento_tipo'] !== 'unico') {
        $agendamentoConfig = json_decode($job['agendamento_config'], true);
        $intervalo = $agendamentoConfig['intervalo_valor'] ?? 1;

        $now = new DateTime();
        switch ($job['agendamento_tipo']) {
            case 'minutos':
                $now->modify("+$intervalo minutes");
                break;
            case 'horas':
                $now->modify("+$intervalo hours");
                break;
            case 'diario':
                $now->modify("+1 day");
                break;
            case 'semanal':
                $now->modify("+1 week");
                break;
            case 'mensal':
                $now->modify("+1 month");
                break;
        }
        $proximaExecucao = $now->format('Y-m-d H:i:s');
    }

    $sqlJob = "UPDATE servicos_jobs SET status = ?";
    $paramsJob = [$finalStatus];
    if ($proximaExecucao) {
        $sqlJob .= ", proximo_execucao = ?";
        $paramsJob[] = $proximaExecucao;
    }
    $sqlJob .= " WHERE id = ?";
    $paramsJob[] = $id;

    $stmt = $pdo->prepare($sqlJob);
    $stmt->execute($paramsJob);

    return ['success' => 'Job executado', 'status' => $status, 'duration' => $duration, 'output' => substr($output, 0, 500), 'execucao_id' => $execucao_id];
}

function pausarJob($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'pausado' WHERE id = ?");
    $stmt->execute([$id]);
    return ['success' => 'Job pausado com sucesso'];
}

function ativarJob($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'ativo' WHERE id = ?");
    $stmt->execute([$id]);
    return ['success' => 'Job ativado com sucesso'];
}

function listarExecucoes($pdo, $job_id) {
    $sql = "
        SELECT e.*,
               j.nome as job_nome
        FROM servicos_execucoes e
        INNER JOIN servicos_jobs j ON e.job_id = j.id
        WHERE e.job_id = ?
        ORDER BY e.data_inicio DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$job_id]);
    $execucoes = $stmt->fetchAll();

    // Formatar datas
    foreach ($execucoes as &$exec) {
        $exec['data_inicio_formatada'] = date('d/m/Y H:i:s', strtotime($exec['data_inicio']));
        $exec['data_fim_formatada'] = $exec['data_fim'] ? date('d/m/Y H:i:s', strtotime($exec['data_fim'])) : '-';
        $exec['duracao_formatada'] = $exec['duracao_segundos'] ? gmdate('H:i:s', $exec['duracao_segundos']) : '-';
    }

    return ['execucoes' => $execucoes];
}

function verLogExecucao($pdo, $execucao_id) {
    $stmt = $pdo->prepare("
        SELECT e.*, j.nome as job_nome
        FROM servicos_execucoes e
        INNER JOIN servicos_jobs j ON e.job_id = j.id
        WHERE e.id = ?
    ");
    $stmt->execute([$execucao_id]);
    $execucao = $stmt->fetch();

    if (!$execucao) {
        return ['error' => 'Execução não encontrada'];
    }

    return [
        'execucao' => $execucao,
        'saida_padrao' => $execucao['saida_padrao'] ?? 'Nenhuma saída',
        'saida_erro' => $execucao['saida_erro'] ?? 'Nenhum erro'
    ];
}

function salvarAgendamento($pdo, $data) {
    $job_id = $data['job_id'];
    $tipo = $data['tipo'];
    $configuracao = json_encode($data['configuracao']);
    $proximo_execucao = date('Y-m-d H:i:s', strtotime($data['proximo_execucao']));

    // Verificar se já existe agendamento
    $stmt = $pdo->prepare("SELECT id FROM servicos_agendamentos WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $existente = $stmt->fetch();

    if ($existente) {
        // Atualizar
        $stmt = $pdo->prepare("
            UPDATE servicos_agendamentos
            SET tipo = ?, configuracao = ?, proximo_execucao = ?, ativo = 1
            WHERE job_id = ?
        ");
        $stmt->execute([$tipo, $configuracao, $proximo_execucao, $job_id]);
    } else {
        // Inserir
        $stmt = $pdo->prepare("
            INSERT INTO servicos_agendamentos (job_id, tipo, configuracao, proximo_execucao)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$job_id, $tipo, $configuracao, $proximo_execucao]);
    }

    // Atualizar job
    $stmt = $pdo->prepare("
        UPDATE servicos_jobs
        SET agendamento_tipo = ?, agendamento_config = ?, proximo_execucao = ?
        WHERE id = ?
    ");
    $stmt->execute([$tipo, $configuracao, $proximo_execucao, $job_id]);

    return ['success' => 'Agendamento salvo com sucesso'];
}

// Funções para gerenciamento de serviços Windows
function instalarServicoWindows($pdo, $data) {
    $jobId = $data['id'];
    $jobName = $data['nome'];
    $command = $data['comando'];
    $workingDir = $data['diretorio_trabalho'] ?: '';
    $user = $data['executar_como'] ?: 'sigep_service';

    try {
        // Construir comando PowerShell
        $psScript = __DIR__ . '\job_manager_service.ps1';
        $fullCommand = "powershell -ExecutionPolicy Bypass -File \"$psScript\" install -JobId $jobId -JobName \"$jobName\" -Command \"$command\" -WorkingDirectory \"$workingDir\" -User \"$user\"";

        // Executar como administrador
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $fullCommand = 'powershell -Command "Start-Process powershell -ArgumentList \'-ExecutionPolicy Bypass -File \"' . $psScript . '\" install -JobId ' . $jobId . ' -JobName \"' . $jobName . '\" -Command \"' . $command . '\" -WorkingDirectory \"' . $workingDir . '\" -User \"' . $user . '\" -Verb RunAs\"" -Verb RunAs';
        }

        $output = shell_exec($fullCommand . ' 2>&1');

        // Atualizar status do job
        $stmt = $pdo->prepare("UPDATE servicos_jobs SET status = 'ativo' WHERE id = ?");
        $stmt->execute([$jobId]);

        return [
            'success' => true,
            'message' => 'Serviço SIGEP configurado com sucesso',
            'message_detail' => 'Job instalado no serviço sigep_service com estrutura de diretórios organizada',
            'command' => $fullCommand,
            'output' => $output,
            'directory_structure' => [
                'base' => 'C:\Servicos',
                'category' => 'C:\Servicos\Rotinas',
                'job_folder' => "C:\Servicos\Rotinas\\$jobName",
                'logs' => 'C:\Servicos\Log',
                'job_logs' => "C:\Servicos\Log\\$jobName"
            ]
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao configurar serviço: ' . $e->getMessage()
        ];
    }
}

function removerServicoWindows($pdo, $data) {
    $jobName = $data['nome'];

    try {
        // Construir comando PowerShell
        $psScript = __DIR__ . '\job_manager_service.ps1';
        $fullCommand = "powershell -ExecutionPolicy Bypass -File \"$psScript\" remove -JobName \"$jobName\"";

        // Executar como administrador
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $fullCommand = 'powershell -Command "Start-Process powershell -ArgumentList \'-ExecutionPolicy Bypass -File \"' . $psScript . '\" remove -JobName \"' . $jobName . '\" -Verb RunAs" -Verb RunAs';
        }

        $output = shell_exec($fullCommand . ' 2>&1');

        return [
            'success' => true,
            'message' => 'Configuração do serviço removida com sucesso',
            'command' => $fullCommand,
            'output' => $output
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao remover configuração: ' . $e->getMessage()
        ];
    }
}

function listarServicosWindows($pdo) {
    try {
        // Listar serviços instalados
        $psScript = __DIR__ . '\job_manager_service.ps1';
        $fullCommand = 'powershell -ExecutionPolicy Bypass -File "' . $psScript . '" list"';

        // Executar como administrador
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $fullCommand = 'powershell -Command "Start-Process powershell -ArgumentList \'-ExecutionPolicy Bypass -File \"' . $psScript . '\" list -Verb RunAs" -Verb RunAs';
        }

        $output = shell_exec($fullCommand . ' 2>&1');

        return [
            'success' => true,
            'message' => 'Status do serviço SIGEP obtido com sucesso',
            'service_info' => [
                'service_name' => 'sigep_service',
                'display_name' => 'SIGEP - Rotinas',
                'user' => 'sigep_service',
                'base_directory' => 'C:\Servicos'
            ],
            'services' => $output
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Erro ao obter status do serviço: ' . $e->getMessage()
        ];
    }
}

// ... (rest of the code remains the same)
function listarAgendamentos($pdo, $job_id) {
    $stmt = $pdo->prepare("SELECT * FROM servicos_agendamentos WHERE job_id = ? AND ativo = 1");
    $stmt->execute([$job_id]);
    $agendamentos = $stmt->fetchAll();

    foreach ($agendamentos as &$ag) {
        $ag['configuracao'] = json_decode($ag['configuracao'], true);
        $ag['proximo_execucao_formatada'] = date('d/m/Y H:i', strtotime($ag['proximo_execucao']));
    }

    return ['agendamentos' => $agendamentos];
}

// Preparar dados para a view
$viewData = [
    'tipos_job' => [
        'backup' => 'Backup',
        'script' => 'Script',
        'site' => 'Site',
        'relatorio' => 'Relatório',
        'limpeza' => 'Limpeza',
        'outro' => 'Outro'
    ],
    'tipos_agendamento' => [
        'unico' => 'Execução Única',
        'minutos' => 'A cada X minutos',
        'horas' => 'A cada X horas',
        'diario' => 'Diário',
        'semanal' => 'Semanal',
        'mensal' => 'Mensal',
        'intervalo' => 'Intervalo'
    ],
    'status_job' => [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'pausado' => 'Pausado',
        'executando' => 'Executando',
        'erro' => 'Erro'
    ]
];
?>
