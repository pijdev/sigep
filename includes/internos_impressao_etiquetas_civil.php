<?php
ob_start();
date_default_timezone_set('America/Sao_Paulo');
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// BLOQUEAR USUÁRIO ROUPARIA DE ACESSAR RELATÓRIOS
if (isset($_SESSION['user_nome']) && $_SESSION['user_nome'] === 'Rouparia') {
    die('<div style="padding: 50px; text-align: center; font-family: Arial; color: #dc3545;">
        <h2><i class="fas fa-exclamation-triangle"></i> Acesso Negado</h2>
        <p>Usuário rouparia não tem permissão para acessar relatórios de etiquetas.</p>
        <p><a href="javascript:history.back()" class="btn btn-primary">Voltar</a></p>
    </div>');
}

try {
    $config = require __DIR__ . '/../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h2>Erro 500</h2><p>Falha na conexão com o Banco de Dados.</p>';
    exit;
}

$idsParam = trim($_GET['ids'] ?? '');
$ids = array_values(array_filter(array_map('intval', explode(',', $idsParam)), static function ($v) {
    return $v > 0;
}));

if (empty($ids)) {
    echo '<h2>Nenhum registro selecionado para impressão.</h2>';
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql = "SELECT
        rc.id,
        rc.ipen,
        rc.pecas as roupa_civil_pecas,
        rc.criado_em,
        i.nome,
        i.nome_social,
        i.status
    FROM internos_rouparia_civil rc
    LEFT JOIN internos i ON i.ipen = rc.ipen
    WHERE rc.id IN ($placeholders)
    ORDER BY rc.ipen ASC, rc.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $dados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Etiquetas - Roupa Civil</title>
        <style>
            @page { size: A4 landscape; margin: 5mm; }
            body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.2; margin: 0; padding: 2mm; color: #000; }
            .etiqueta { display: grid; grid-template-columns: 2fr 1fr; grid-template-rows: auto auto; column-gap: 8px; row-gap: 8px; align-items: center; border-bottom: 2px dashed #ccc; padding: 6px 4px; min-height: 60px; page-break-inside: avoid; }
            .bloco-principal { font-size: 1em; white-space: nowrap; overflow: visible; }
            .nome-principal { font-weight: 800; font-size: 24px; }
            .status { font-size: 1.1em; text-align: right; }
            .itens { font-size: 1.1em; white-space: normal; overflow: visible; }
            .ultima-verificacao { font-size: 1em; text-align: right; white-space: nowrap; }
            .mochila { font-weight: 800; }
        </style>
    </head>
    <body>';

    foreach ($dados as $interno) {
        $nome = mb_strtoupper($interno['nome_social'] ?: $interno['nome']);
        $ipen = $interno['ipen'];
        $statusInterno = ($interno['status'] ?? '') === 'I' ? 'INATIVO' : 'ATIVO';
        $ultimaVerificacao = date('d/m/Y \\à\\s H:i', strtotime($interno['criado_em'])) . 'h';

        $itens_lista = [];
        if (!empty($interno['roupa_civil_pecas'])) {
            $pecas_json = json_decode($interno['roupa_civil_pecas'], true);
            if ($pecas_json) {
                if (!empty($pecas_json['predefinidos'])) {
                    foreach ($pecas_json['predefinidos'] as $item) {
                        $itens_lista[] = [
                            'texto' => $item['quantidade'] . 'x ' . $item['tipo'],
                            'is_mochila' => mb_strtolower($item['tipo']) === 'mochila',
                        ];
                    }
                }
                if (!empty($pecas_json['outros'])) {
                    foreach ($pecas_json['outros'] as $item) {
                        $itens_lista[] = [
                            'texto' => $item['quantidade'] . 'x ' . $item['tipo'],
                            'is_mochila' => mb_strtolower($item['tipo']) === 'mochila',
                        ];
                    }
                }
            }
        }

        usort($itens_lista, static function ($a, $b) {
            if ($a['is_mochila'] === $b['is_mochila']) {
                return 0;
            }
            return $a['is_mochila'] ? -1 : 1;
        });

        $itens_formatados = [];
        foreach ($itens_lista as $item) {
            $txt = htmlspecialchars($item['texto']);
            if ($item['is_mochila']) {
                $txt = "<strong class='mochila'>{$txt}</strong>";
            }
            $itens_formatados[] = $txt;
        }
        $itens_str = implode(', ', $itens_formatados);

        $html .= "
        <div class='etiqueta'>
            <span class='bloco-principal'><span class='nome-principal'>{$ipen} - {$nome}</span></span>
            <span class='ultima-verificacao'>Última Verificação: {$ultimaVerificacao}</span>
            <span class='itens'>{$itens_str}</span>
            <span class='status'>{$statusInterno}</span>
        </div>";
    }


    echo $html;
    exit;
} catch (Exception $e) {
    echo '<h2>Erro ao gerar etiquetas</h2><p>' . $e->getMessage() . '</p>';
    exit;
}
