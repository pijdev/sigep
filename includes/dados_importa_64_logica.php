<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start();
header('Content-Type: application/json; charset=utf-8');

$pdo = null;

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sessao nao iniciada.');
    }

    $configPath = __DIR__ . '/../conf/db.php';
    if (!file_exists($configPath)) {
        throw new Exception('Configuracao do banco nao encontrada.');
    }

    $config = require $configPath;
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensureLaboralSchema($pdo);

    $rawData = $_POST['report_data'] ?? '';
    if (trim($rawData) === '') {
        throw new Exception('Nenhum dado informado para importacao.');
    }

    $registros = parseRelatorio64($rawData);
    if (empty($registros)) {
        throw new Exception('Nenhum registro valido identificado no texto colado.');
    }

    $ipens = array_values(array_unique(array_map(static fn($r) => (int)$r['ipen'], $registros)));
    $internos = [];

    if (!empty($ipens)) {
        $placeholders = implode(',', array_fill(0, count($ipens), '?'));
        $stmtInternos = $pdo->prepare("SELECT ipen, nome, situacao FROM internos WHERE ipen IN ({$placeholders})");
        $stmtInternos->execute($ipens);
        foreach ($stmtInternos->fetchAll() as $row) {
            $internos[(int)$row['ipen']] = $row;
        }
    }

    $agora = date('Y-m-d H:i:s');
    $usuarioId = (int)$_SESSION['user_id'];
    $total = count(array_unique(array_map(static fn($r) => (int)$r['ipen'], $registros)));
    $encontrados = 0;
    $naoEncontrados = 0;
    $novos = 0;
    $atualizados = 0;
    $inativados = 0;
    $conveniadosAtualizados = 0;
    $regaliasEspeciaisAtualizadas = 0;

    $pdo->beginTransaction();

    $stmtHistorico = $pdo->prepare(
        "INSERT INTO internos_laboral_historico (
            data_importacao, total_importados, registros_novos, registros_atualizados, registros_inativados,
            internos_encontrados, internos_nao_encontrados, importado_por
        ) VALUES (NOW(), ?, 0, 0, 0, 0, 0, ?)"
    );
    $stmtHistorico->execute([$total, $usuarioId]);
    $idHistorico = (int)$pdo->lastInsertId();

    // Disponibiliza contexto para os triggers de historico detalhado.
    $pdo->exec("SET @laboral_import_id = {$idHistorico}");
    $pdo->exec("SET @laboral_user_id = {$usuarioId}");

    $stmtSelect = $pdo->prepare(
        "SELECT * FROM internos_laboral
         WHERE ipen = ? AND status = 'A'
         LIMIT 1"
    );

    $stmtInsert = $pdo->prepare(
        "INSERT INTO internos_laboral (
            ipen, estabelecimento, remicao_inicio, remicao_fim, liberacao_inicio, liberacao_fim,
            dias_semana, dias_semana_json, status, data_ativo, importado_em, importado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'A', ?, ?, ?)"
    );

    $stmtUpdate = $pdo->prepare(
        "UPDATE internos_laboral SET
            remicao_inicio = ?,
            remicao_fim = ?,
            liberacao_inicio = ?,
            liberacao_fim = ?,
            dias_semana = ?,
            dias_semana_json = ?,
            data_alterado = ?,
            importado_em = ?,
            importado_por = ?
         WHERE id = ?"
    );

    $stmtInativar = $pdo->prepare(
        "UPDATE internos_laboral
         SET status = 'I', data_inativo = ?, data_alterado = ?, importado_em = ?, importado_por = ?
         WHERE id = ? AND status = 'A'"
    );

    $stmtAtivos = $pdo->query("SELECT id, ipen FROM internos_laboral WHERE status = 'A'");
    $ativosNoBanco = $stmtAtivos->fetchAll();
    $ativosMap = [];
    foreach ($ativosNoBanco as $ativo) {
        $ativosMap[laboralKey((int)$ativo['ipen'])] = (int)$ativo['id'];
    }

    $processadosMap = [];

    // Coletar todos os IPENs do relatório para atualização de regalia
    $ipensRelatorio = [];
    foreach ($registros as $r) {
        $ipensRelatorio[] = (int)$r['ipen'];
    }

    // Preparar statements para atualização de regalia
    $stmtUpdateRegalia = $pdo->prepare(
        "UPDATE internos SET
            regalia = ?,
            regalia_setor = ?,
            data_alterado = ?
         WHERE ipen = ?"
    );

    $stmtUpdateRegaliaN = $pdo->prepare(
        "UPDATE internos SET
            regalia = 'N',
            regalia_setor = NULL,
            data_alterado = ?
         WHERE ipen = ? AND regalia = 'S'"
    );

    foreach ($registros as $r) {
        $ipen = (int)$r['ipen'];
        $interno = $internos[$ipen] ?? null;
        $internoEncontrado = $interno ? 1 : 0;

        if ($internoEncontrado) {
            $encontrados++;
        } else {
            $naoEncontrados++;
        }

        $estabelecimento = trim((string)$r['estabelecimento']);
        $key = laboralKey($ipen);
        $processadosMap[$key] = true;

        // Verificar se é regalia e definir setor - Lógica Hierárquica
        $isRegalia = false;
        $regaliaSetor = null;

        // 1. Prioridade 1: Conveniados (SECRETARIA DE ESTADO)
        if (stripos($estabelecimento, 'SECRETARIA DE ESTADO') !== false) {
            $isRegalia = true;
            $regaliaSetor = 'Conveniado - Hospital';
        }
        // 2. Prioridade 2: Regalias Especiais (SOLUÇÕES SERVIÇOS GERAIS - REGALIAS)
        elseif (stripos($estabelecimento, 'SOLUÇÕES SERVIÇOS GERAIS - REGALIAS') !== false) {
            $isRegalia = true;
            // Mantém setor original - não altera regalia_setor
            // Para isso, precisamos buscar o valor atual se existir
            if ($internoEncontrado) {
                $stmtAtual = $pdo->prepare("SELECT regalia_setor FROM internos WHERE ipen = ?");
                $stmtAtual->execute([$ipen]);
                $regaliaSetor = $stmtAtual->fetchColumn();
            }
        }
        // 3. Prioridade 3: Regalias Padrão (REMIÇÃO e FUNDO ROTATIVO)
        elseif (stripos($estabelecimento, 'REMIÇÃO') !== false) {
            $isRegalia = true;

            // Definir setor com base no tipo de trabalho
            if (stripos($estabelecimento, 'CORTE DE CABELO') !== false) {
                $regaliaSetor = 'Corte de Cabelo';
            } elseif (stripos($estabelecimento, 'ALIMENTAÇÃO') !== false) {
                $regaliaSetor = 'Alimentação';
            } else {
                $regaliaSetor = 'Remição';
            }
        } elseif (stripos($estabelecimento, 'FUNDO ROTATIVO') !== false) {
            $isRegalia = true;
            $regaliaSetor = 'Fundo Rotativo';
        }

        // Atualizar campos de regalia na tabela internos
        if ($isRegalia && $internoEncontrado) {
            $stmtUpdateRegalia->execute(['S', $regaliaSetor, $agora, $ipen]);

            // Incrementar contadores específicos
            if ($regaliaSetor === 'Conveniado - Hospital') {
                $conveniadosAtualizados++;
            } elseif (stripos($estabelecimento, 'SOLUÇÕES SERVIÇOS GERAIS - REGALIAS') !== false) {
                $regaliasEspeciaisAtualizadas++;
            }
        }

        $stmtSelect->execute([$ipen]);
        $existente = $stmtSelect->fetch();

        if (!$existente) {
            $stmtInsert->execute([
                $ipen,
                $estabelecimento,
                $r['remicao_inicio'],
                $r['remicao_fim'],
                $r['liberacao_inicio'],
                $r['liberacao_fim'],
                $r['dias_semana'],
                $r['dias_semana_json'],
                $agora,
                $agora,
                $usuarioId,
            ]);
            $novos++;
            continue;
        }

        $mudou = false;
        $reativado = ($existente['status'] === 'I');

        $comparar = [
            ['remicao_inicio', $r['remicao_inicio']],
            ['remicao_fim', $r['remicao_fim']],
            ['liberacao_inicio', $r['liberacao_inicio']],
            ['liberacao_fim', $r['liberacao_fim']],
            ['dias_semana', $r['dias_semana']],
            ['dias_semana_json', $r['dias_semana_json']],
        ];

        foreach ($comparar as [$campo, $valorNovo]) {
            $valorAtual = $existente[$campo];
            $valorAtualNorm = $valorAtual === null ? null : (string)$valorAtual;
            $valorNovoNorm = $valorNovo === null ? null : (string)$valorNovo;
            if ($valorAtualNorm !== $valorNovoNorm) {
                $mudou = true;
                break;
            }
        }

        if ($mudou || $reativado) {
            $stmtUpdate->execute([
                $r['remicao_inicio'],
                $r['remicao_fim'],
                $r['liberacao_inicio'],
                $r['liberacao_fim'],
                $r['dias_semana'],
                $r['dias_semana_json'],
                $agora,
                $agora,
                $usuarioId,
                $existente['id'],
            ]);
            $atualizados++;
        }
    }

    foreach ($ativosMap as $key => $idAtivo) {
        if (!isset($processadosMap[$key])) {
            $stmtInativar->execute([$agora, $agora, $agora, $usuarioId, $idAtivo]);
            if ($stmtInativar->rowCount() > 0) {
                $inativados++;
            }
        }
    }

    // Atualizar regalia=N para internos que não estão mais no relatório
    // Buscar todos os internos que são regalia atualmente
    $stmtRegaliasAtuais = $pdo->query("SELECT ipen FROM internos WHERE regalia = 'S'");
    $regaliasAtuais = $stmtRegaliasAtuais->fetchAll(PDO::FETCH_COLUMN, 0);

    // Marcar como N os regalias que não estão no relatório
    $regaliasRemovidas = 0;
    foreach ($regaliasAtuais as $ipenRegalia) {
        if (!in_array($ipenRegalia, $ipensRelatorio)) {
            $stmtUpdateRegaliaN->execute([$agora, $ipenRegalia]);
            if ($stmtUpdateRegaliaN->rowCount() > 0) {
                $regaliasRemovidas++;
            }
        }
    }

    $stmtHistoricoUpdate = $pdo->prepare(
        "UPDATE internos_laboral_historico SET
            registros_novos = ?,
            registros_atualizados = ?,
            registros_inativados = ?,
            internos_encontrados = ?,
            internos_nao_encontrados = ?
         WHERE id = ?"
    );
    $stmtHistoricoUpdate->execute([
        $novos,
        $atualizados,
        $inativados,
        $encontrados,
        $naoEncontrados,
        $idHistorico,
    ]);

    $pdo->exec("SET @laboral_import_id = NULL");
    $pdo->exec("SET @laboral_user_id = NULL");

    $pdo->commit();

    ob_clean();
    echo json_encode([
        'success' => true,
        'total' => $total,
        'novos' => $novos,
        'atualizados' => $atualizados,
        'inativados' => $inativados,
        'encontrados' => $encontrados,
        'nao_encontrados' => $naoEncontrados,
        'regalias_atualizadas' => count(array_filter($ipensRelatorio, function ($ipen) use ($pdo) {
            $stmt = $pdo->prepare("SELECT regalia FROM internos WHERE ipen = ?");
            $stmt->execute([$ipen]);
            return $stmt->fetchColumn() === 'S';
        })),
        'regalias_removidas' => $regaliasRemovidas,
        'conveniados_atualizados' => $conveniadosAtualizados,
        'regalias_especiais_atualizadas' => $regaliasEspeciaisAtualizadas,
    ]);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($pdo) {
        try {
            $pdo->exec("SET @laboral_import_id = NULL");
            $pdo->exec("SET @laboral_user_id = NULL");
        } catch (Exception $ignored) {
        }
    }
    ob_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

exit();

function laboralKey(int $ipen): string
{
    return (string)$ipen;
}

function ensureLaboralSchema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS internos_laboral (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ipen INT NOT NULL,
            estabelecimento VARCHAR(255) NOT NULL,
            remicao_inicio DATE NULL,
            remicao_fim DATE NULL,
            liberacao_inicio DATE NULL,
            liberacao_fim DATE NULL,
            dias_semana VARCHAR(50) NULL,
            dias_semana_json JSON NULL,
            status ENUM('A','I') NOT NULL DEFAULT 'A',
            data_ativo DATETIME NOT NULL,
            data_alterado DATETIME NULL,
            data_inativo DATETIME NULL,
            importado_em DATETIME NOT NULL,
            importado_por INT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_interno_laboral_ipen (ipen, status),
            INDEX idx_laboral_status (status),
            INDEX idx_laboral_ipen (ipen)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS internos_laboral_historico (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            data_importacao DATETIME NOT NULL,
            total_importados INT NOT NULL DEFAULT 0,
            registros_novos INT NOT NULL DEFAULT 0,
            registros_atualizados INT NOT NULL DEFAULT 0,
            registros_inativados INT NOT NULL DEFAULT 0,
            internos_encontrados INT NOT NULL DEFAULT 0,
            internos_nao_encontrados INT NOT NULL DEFAULT 0,
            importado_por INT NULL,
            PRIMARY KEY (id),
            INDEX idx_laboral_hist_data (data_importacao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS internos_laboral_historico_detalhado (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            id_historico BIGINT UNSIGNED NULL,
            id_interno_laboral BIGINT UNSIGNED NULL,
            ipen INT NOT NULL,
            campo VARCHAR(80) NOT NULL,
            valor_antigo TEXT NULL,
            valor_novo TEXT NULL,
            data_alteracao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            operacao ENUM('INSERIDO','ATUALIZADO','INATIVADO','REATIVADO','EXCLUIDO') NOT NULL,
            alterado_por INT NULL,
            PRIMARY KEY (id),
            INDEX idx_laboral_det_hist (id_historico),
            INDEX idx_laboral_det_ipen (ipen),
            INDEX idx_laboral_det_data (data_alteracao)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    ensureLaboralTriggers($pdo);
}

function ensureLaboralTriggers(PDO $pdo): void
{
    $triggerInsert = 'trg_internos_laboral_ai';
    $triggerUpdate = 'trg_internos_laboral_au';
    $triggerDelete = 'trg_internos_laboral_ad';

    if (!triggerExists($pdo, $triggerInsert)) {
        $pdo->exec(
            "CREATE TRIGGER {$triggerInsert}
            AFTER INSERT ON internos_laboral
            FOR EACH ROW
            BEGIN
                INSERT INTO internos_laboral_historico_detalhado
                    (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                VALUES
                    (@laboral_import_id, NEW.id, NEW.ipen, 'registro',
                    JSON_OBJECT(
                        'estabelecimento', NEW.estabelecimento,
                        'remicao_inicio', NEW.remicao_inicio,
                        'remicao_fim', NEW.remicao_fim,
                        'liberacao_inicio', NEW.liberacao_inicio,
                        'liberacao_fim', NEW.liberacao_fim,
                        'dias_semana', NEW.dias_semana,
                        'dias_semana_json', NEW.dias_semana_json,
                        'status', NEW.status
                    ),
                    NULL,
                    NOW(), 'INSERIDO', COALESCE(@laboral_user_id, NEW.importado_por));
            END"
        );
    }

    if (!triggerExists($pdo, $triggerUpdate)) {
        $pdo->exec(
            "CREATE TRIGGER {$triggerUpdate}
            AFTER UPDATE ON internos_laboral
            FOR EACH ROW
            BEGIN
                DECLARE op VARCHAR(20) DEFAULT 'ATUALIZADO';

                IF (OLD.status = 'A' AND NEW.status = 'I') THEN
                    SET op = 'INATIVADO';
                ELSEIF (OLD.status = 'I' AND NEW.status = 'A') THEN
                    SET op = 'REATIVADO';
                END IF;

                IF NOT (OLD.remicao_inicio <=> NEW.remicao_inicio) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'remicao_inicio', OLD.remicao_inicio, NEW.remicao_inicio, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;

                IF NOT (OLD.remicao_fim <=> NEW.remicao_fim) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'remicao_fim', OLD.remicao_fim, NEW.remicao_fim, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;

                IF NOT (OLD.liberacao_inicio <=> NEW.liberacao_inicio) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'liberacao_inicio', OLD.liberacao_inicio, NEW.liberacao_inicio, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;

                IF NOT (OLD.liberacao_fim <=> NEW.liberacao_fim) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'liberacao_fim', OLD.liberacao_fim, NEW.liberacao_fim, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;

                IF NOT (OLD.dias_semana <=> NEW.dias_semana) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'dias_semana', OLD.dias_semana, NEW.dias_semana, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;

                IF NOT (OLD.dias_semana_json <=> NEW.dias_semana_json) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'dias_semana_json', OLD.dias_semana_json, NEW.dias_semana_json, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;

                IF NOT (OLD.status <=> NEW.status) THEN
                    INSERT INTO internos_laboral_historico_detalhado
                        (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                    VALUES
                        (@laboral_import_id, NEW.id, NEW.ipen, 'status', OLD.status, NEW.status, NOW(), op, COALESCE(@laboral_user_id, NEW.importado_por));
                END IF;
            END"
        );
    }

    if (!triggerExists($pdo, $triggerDelete)) {
        $pdo->exec(
            "CREATE TRIGGER {$triggerDelete}
            AFTER DELETE ON internos_laboral
            FOR EACH ROW
            BEGIN
                INSERT INTO internos_laboral_historico_detalhado
                    (id_historico, id_interno_laboral, ipen, campo, valor_antigo, valor_novo, data_alteracao, operacao, alterado_por)
                VALUES
                    (@laboral_import_id, OLD.id, OLD.ipen, 'registro',
                    JSON_OBJECT(
                        'estabelecimento', OLD.estabelecimento,
                        'remicao_inicio', OLD.remicao_inicio,
                        'remicao_fim', OLD.remicao_fim,
                        'liberacao_inicio', OLD.liberacao_inicio,
                        'liberacao_fim', OLD.liberacao_fim,
                        'dias_semana', OLD.dias_semana,
                        'dias_semana_json', OLD.dias_semana_json,
                        'status', OLD.status
                    ),
                    NULL,
                    NOW(), 'EXCLUIDO', COALESCE(@laboral_user_id, OLD.importado_por));
            END"
        );
    }
}

function triggerExists(PDO $pdo, string $triggerName): bool
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM information_schema.TRIGGERS
         WHERE TRIGGER_SCHEMA = DATABASE()
           AND TRIGGER_NAME = ?"
    );
    $stmt->execute([$triggerName]);
    return (int)$stmt->fetchColumn() > 0;
}

function parseRelatorio64(string $rawData): array
{
    $normalizado = str_replace(["\r\n", "\r"], "\n", $rawData);
    $linhas = explode("\n", $normalizado);

    $registros = [];
    $estabelecimentoAtual = '';
    $pendente = null;

    foreach ($linhas as $linhaOriginal) {
        $linha = trim(preg_replace('/\s+/u', ' ', $linhaOriginal));
        if ($linha === '') {
            continue;
        }

        // Ignorar linhas de cabeçalho/rodapé
        if (preg_match('/^(www\.|https:|\d{2}\/\d{2}\/\d{4},|Impresso em:)/', $linha)) {
            continue;
        }

        // Extrair estabelecimento
        if (preg_match('/^Estabelecimento:\s*(.+?)\s+\d+\s+detentos$/iu', $linha, $m)) {
            $estabelecimentoAtual = trim($m[1]);
            continue;
        }

        // Extrair IPEN e nome do interno
        if (preg_match('/^(\d{6})\s+(.+)$/u', $linha, $m)) {
            // Verificar se é o mesmo IPEN do registro pendente
            if ($pendente !== null && $pendente['ipen'] === $m[1]) {
                // Mesmo IPEN - apenas acumular as linhas de detalhe
                // Não faz nada aqui, as linhas serão adicionadas abaixo
            } else {
                // IPEN diferente - finalizar registro anterior
                if ($pendente !== null) {
                    $registro = buildRemicaoRecord($pendente);
                    if ($registro !== null) {
                        $registros[] = $registro;
                    }
                }

                // Iniciar novo registro
                $pendente = [
                    'ipen' => $m[1],
                    'nome_interno' => $m[2],
                    'estabelecimento' => $estabelecimentoAtual,
                    'linhas_detalhe' => []
                ];
            }
            continue;
        }

        // Adicionar linhas de detalhes ao registro pendente
        if ($pendente !== null) {
            $pendente['linhas_detalhe'][] = $linha;
        }
    }

    // Finalizar último registro
    if ($pendente !== null) {
        $registro = buildRemicaoRecord($pendente);
        if ($registro !== null) {
            $registros[] = $registro;
        }
    }

    return $registros;
}

function isLinhaCabecalho64(string $linha): bool
{
    $padroes = [
        '/^ESTADO DE /iu',
        '/^Secretaria de Estado/iu',
        '/^POL[ÍI]CIA PENAL/iu',
        '/^Sistema de Identifica/iu',
        '/^Unidade:/iu',
        '/^REMI[ÇC][ÕO]ES POR TRABALHO ATIVAS/iu',
        '/^Impresso em /iu',
        '/^\d{2}\/\d{2}\/\d{4}\s*-\s*REMI[ÇC][ÕO]ES/iu',
        '/^PRONTU[ÁA]RIO\s*\|/iu',
    ];

    foreach ($padroes as $padrao) {
        if (preg_match($padrao, $linha)) {
            return true;
        }
    }

    return false;
}

function buildRemicaoRecord(array $pendente): ?array
{
    if (empty($pendente['linhas_detalhe'])) {
        return null;
    }

    // Consolidar todas as linhas de remição do mesmo interno
    $todasAsDatas = [];
    $todosOsDias = [];
    $datasConsolidadas = [
        'remicao_inicio' => null,
        'remicao_fim' => null,
        'liberacao_inicio' => null,
        'liberacao_fim' => null
    ];

    foreach ($pendente['linhas_detalhe'] as $linha) {
        if (preg_match('/\b(TRABALHO|ESTUDO|LEITURA)\b.*\d{2}\/\d{2}\/\d{4}/u', $linha)) {
            // Regex para extrair dados da remição
            $match = preg_match(
                '/\b(TRABALHO\s+(?:EXTERNO|INTERNO)|ESTUDO\s+[A-ZÀ-Ú\s]+|LEITURA(?:\s+[A-ZÀ-Ú\s]*)?)\s+(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})\s+(\d{2}\/\d{2}\/\d{4})\s+(.+)$/u',
                $linha,
                $m
            );

            if ($match) {
                $todasAsDatas[] = [
                    'remicao_inicio' => formatDateToSql($m[2]),
                    'remicao_fim' => formatDateToSql($m[3]),
                    'liberacao_inicio' => formatDateToSql($m[4]),
                    'liberacao_fim' => formatDateToSql($m[5])
                ];

                // Extrair dias da semana desta linha - regex direta
                $parteFinal = trim($m[6]);
                $diasEncontrados = [];

                // Regex para encontrar todos os dias da semana
                preg_match_all('/\b(?:D|2ª|3ª|4ª|5ª|6ª|S)\b/u', $parteFinal, $matches);
                $diasEncontrados = $matches[0] ?? [];

                // Normalizar codificação UTF-8
                $diasEncontrados = array_map(function ($dia) {
                    return mb_convert_encoding($dia, 'UTF-8', 'UTF-8');
                }, $diasEncontrados);

                $todosOsDias = array_merge($todosOsDias, $diasEncontrados);
            }
        }
    }

    if (empty($todasAsDatas)) {
        return null;
    }

    // Consolidar datas - usar a data mais antiga para início e mais recente para fim
    $datasInicio = array_column($todasAsDatas, 'remicao_inicio');
    $datasFim = array_column($todasAsDatas, 'remicao_fim');
    $datasLiberacaoInicio = array_column($todasAsDatas, 'liberacao_inicio');
    $datasLiberacaoFim = array_column($todasAsDatas, 'liberacao_fim');

    // Remover nulos e ordenar
    $datasInicio = array_filter($datasInicio);
    $datasFim = array_filter($datasFim);
    $datasLiberacaoInicio = array_filter($datasLiberacaoInicio);
    $datasLiberacaoFim = array_filter($datasLiberacaoFim);

    sort($datasInicio);
    rsort($datasFim);
    sort($datasLiberacaoInicio);
    rsort($datasLiberacaoFim);

    // Consolidar dias únicos em ordem
    $diasUnicos = array_unique($todosOsDias);
    $ordem = ['D', '2ª', '3ª', '4ª', '5ª', '6ª', 'S'];
    $diasOrdenados = [];
    foreach ($ordem as $dia) {
        if (in_array($dia, $diasUnicos)) {
            $diasOrdenados[] = $dia;
        }
    }

    // Se não encontrou dias na ordem padrão, mantém a ordem original
    if (empty($diasOrdenados) && !empty($diasUnicos)) {
        $diasOrdenados = array_values($diasUnicos);
    }

    $diasTexto = implode(' ', $diasOrdenados);
    $diasJson = json_encode($diasOrdenados, JSON_UNESCAPED_UNICODE);

    if (empty($pendente['ipen'])) {
        return null;
    }

    return [
        'ipen' => (int)$pendente['ipen'],
        'estabelecimento' => trim((string)($pendente['estabelecimento'] ?? 'NAO INFORMADO')),
        'remicao_inicio' => $datasInicio[0] ?? null,
        'remicao_fim' => $datasFim[0] ?? null,
        'liberacao_inicio' => $datasLiberacaoInicio[0] ?? null,
        'liberacao_fim' => $datasLiberacaoFim[0] ?? null,
        'dias_semana' => $diasTexto,
        'dias_semana_json' => $diasJson,
    ];
}

function extrairTipoRemicaoFallback(string $prefixo): ?string
{
    if ($prefixo === '') {
        return null;
    }

    if (preg_match('/\b(TRABALHO\s+[A-ZÀ-Ú\s]+)$/u', $prefixo, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/\b(ESTUDO\s+[A-ZÀ-Ú\s]+)$/u', $prefixo, $m)) {
        return trim($m[1]);
    }
    if (preg_match('/\b(LEITURA(?:\s+[A-ZÀ-Ú\s]+)?)$/u', $prefixo, $m)) {
        return trim($m[1]);
    }

    return null;
}

function extrairDiasSemana(string $texto): array
{
    if (trim($texto) === '') {
        return [null, []];
    }

    preg_match_all('/\b(?:D|2ª|3ª|4ª|5ª|6ª|S)\b/u', $texto, $matches);
    $tokens = $matches[0] ?? [];

    if (empty($tokens)) {
        return [null, []];
    }

    $ordem = ['D', '2ª', '3ª', '4ª', '5ª', '6ª', 'S'];
    $vistos = [];
    foreach ($tokens as $token) {
        $vistos[$token] = true;
    }

    $normalizados = [];
    foreach ($ordem as $dia) {
        if (isset($vistos[$dia])) {
            $normalizados[] = $dia;
        }
    }

    if (empty($normalizados)) {
        return [null, []];
    }

    return [implode(' ', $normalizados), $normalizados];
}

function formatDateToSql(?string $date): ?string
{
    if ($date === null || trim($date) === '') {
        return null;
    }

    $dt = DateTime::createFromFormat('d/m/Y', trim($date));
    if (!$dt) {
        return null;
    }

    return $dt->format('Y-m-d');
}
