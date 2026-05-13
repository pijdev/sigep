<?php

/**
 * SIGEP - Componente de Avisos de Manutenção
 * Componente reutilizável para exibir avisos de manutenção
 *
 * Uso: include_once 'modulos/geral/aviso_manutencao/aviso_manutencao_componente.php';
 */

// Função simplificada para buscar avisos ativos (sem dependência do _logica.php)
function buscarAvisosAtivosSimplificado()
{
    try {
        // Verificar se já tem avisos carregados globalmente
        if (isset($GLOBALS['avisos_manutencao_ativos'])) {
            return $GLOBALS['avisos_manutencao_ativos'];
        }

        // Carregar configuração do banco
        $config = require __DIR__ . '/../../../conf/db.php';
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '-03:00'");

        // Buscar avisos ativos
        $stmt = $pdo->prepare("
            SELECT * FROM avisos_manutencao
            WHERE ativo = 1
            AND data_inicio <= NOW()
            AND data_fim >= NOW()
            ORDER BY severidade DESC, data_inicio ASC
        ");
        $stmt->execute();
        $avisos = $stmt->fetchAll();

        // Cache global
        $GLOBALS['avisos_manutencao_ativos'] = $avisos;

        return $avisos;
    } catch (Exception $e) {
        // Silenciar erros para não quebrar o header
        return [];
    }
}

/**
 * Renderiza os avisos de manutenção ativos
 */
function renderizarAvisosManutencao($avisos)
{
    if (empty($avisos)) {
        return ''; // Sem avisos ativos
    }

    $html = '';
    foreach ($avisos as $aviso) {
        $classeAlerta = 'alert-' . $aviso['severidade'];
        $iconeSeveridade = getIconeSeveridade($aviso['severidade']);

        // Calcular tempo restante
        $dataFim = new DateTime($aviso['data_fim']);
        $agora = new DateTime();
        $tempoRestante = calcularTempoRestante($agora, $dataFim);

        // Decodificar arrays JSON
        $setoresImpactados = $aviso['setores_impactados'] ? json_decode($aviso['setores_impactados'], true) : [];
        $sistemasImpactados = $aviso['sistemas_impactados'] ? json_decode($aviso['sistemas_impactados'], true) : [];

        $html .= "
        <div class='alert {$classeAlerta} alert-dismissible fade show m-0' role='alert'>
            <div class='container-fluid'>
                <div class='d-flex align-items-center'>
                    <i class='{$iconeSeveridade} mr-3 fa-2x'></i>
                    <div class='flex-grow-1'>
                        <h5 class='alert-heading mb-1'>
                            <i class='{$iconeSeveridade} mr-2'></i>
                            " . htmlspecialchars($aviso['titulo']) . "
                        </h5>
                        <p class='mb-2'>" . nl2br(htmlspecialchars($aviso['mensagem'])) . "</p>
                        <p class='mb-0'>
                            <small class='text-muted'>
                                <i class='fas fa-clock mr-1'></i>
                                Previsão de retorno: <strong>{$tempoRestante}</strong> |
                                <i class='fas fa-tools mr-1'></i>
                                Severidade: <span class='badge badge-{$aviso['severidade']}'>" . ucfirst($aviso['severidade']) . "</span>";

        // Adicionar informações de impacto se houver
        if (!empty($sistemasImpactados)) {
            $html .= " | <i class='fas fa-exclamation-triangle mr-1'></i>
                Sistemas impactados: <strong>" . implode(', ', $sistemasImpactados) . "</strong>";
        }

        $html .= "
                            </small>
                        </p>
                    </div>
                    <button type='button' class='close' data-dismiss='alert' aria-label='Fechar'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>
            </div>
        </div>";
    }

    return $html;
}

/**
 * Retorna o ícone FontAwesome baseado na severidade
 */
function getIconeSeveridade($severidade)
{
    $icones = [
        'info' => 'fas fa-info-circle',
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'danger' => 'fas fa-exclamation-circle'
    ];

    return $icones[$severidade] ?? 'fas fa-info-circle';
}

/**
 * Calcula o tempo restante de forma amigável
 */
function calcularTempoRestante($agora, $dataFim)
{
    if ($agora >= $dataFim) {
        return 'Sistema normalizado';
    }

    $diff = $agora->diff($dataFim);

    if ($diff->days > 0) {
        if ($diff->days == 1) {
            return 'Amanhã - ' . $dataFim->format('H:i');
        } else {
            return $diff->days . ' dias - ' . $dataFim->format('d/m H:i');
        }
    } else if ($diff->h > 0) {
        return 'Hoje - ' . $dataFim->format('H:i') . ' (faltam ' . $diff->h . ' horas)';
    } else {
        return 'Hoje - ' . $dataFim->format('H:i') . ' (faltam ' . $diff->i . ' minutos)';
    }
}

/**
 * Função para uso no header - renderiza todos os avisos ativos
 */
function exibirAvisosManutencao()
{
    $avisos = buscarAvisosAtivosSimplificado();
    echo renderizarAvisosManutencao($avisos);
}

/**
 * Verifica se há avisos ativos (para uso em lógica condicional)
 */
function haAvisosAtivos()
{
    $avisos = buscarAvisosAtivosSimplificado();
    return !empty($avisos);
}

/**
 * Retorna o CSS necessário para os avisos
 */
function getCSSAvisosManutencao()
{
    return "
    <style>
    .alert {
        margin-bottom: 0 !important;
        border-radius: 0 !important;
        border-left: none !important;
        border-right: none !important;
        border-top: none !important;
    }
    .alert .alert-heading {
        font-size: 1.1rem;
        font-weight: 600;
    }
    .alert .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    </style>";
}
