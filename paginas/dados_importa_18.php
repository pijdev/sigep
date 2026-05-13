<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Configurações para garantir resposta JSON limpa
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
    ob_start();
    header('Content-Type: application/json');

    $pdo = null;

    try {
        $configPath = __DIR__ . '/../conf/db.php';
        if (!file_exists($configPath)) throw new Exception("Configuração do banco não encontrada.");

        $config = require_once $configPath;
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $rawData = $_POST['report_data'] ?? '';
        if (empty(trim($rawData))) throw new Exception("Nenhum dado informado.");

        // Carrega nomes do banco apenas para o caso de "Fallback" (quando não houver Tabs)
        $stmtNomes = $pdo->query("SELECT ipen, nome FROM internos");
        $mapaNomes = $stmtNomes->fetchAll(PDO::FETCH_KEY_PAIR);

        $linhas = explode("\n", $rawData);
        $dadosImportados = [];
        $ipensImportados = [];
        $linhasReconhecidas = 0;

        // Aceita IPEN com pontuação ("853.708"), hífen ou apenas dígitos no início da linha.
        $extrairIpen = function (string $linha): ?int {
            if (!preg_match('/^\s*([0-9][0-9\.\-]{4,})\b/u', $linha, $m)) {
                return null;
            }
            $apen = preg_replace('/\D+/', '', $m[1]);
            if ($apen === '' || strlen($apen) < 5) {
                return null;
            }
            return (int)$apen;
        };

        foreach ($linhas as $linha) {
            $linhaLimpa = trim($linha);
            if ($linhaLimpa === '') continue;

            $ipen = $extrairIpen($linhaLimpa);
            if ($ipen === null) continue;
            $linhasReconhecidas++;

            // Remove o IPEN e traços do início para limpar a linha
            // Transforma "853708 - FABIO MARTINS..." em "FABIO MARTINS..."
            $textoDados = trim(preg_replace('/^\s*[0-9][0-9\.\-]{4,}\s*(?:-\s*)?/u', '', $linhaLimpa));

            $nomeFinal = '';
            $situacaoFinal = '';
            $ala = ''; $galeria = ''; $bloco = ''; $piso = 0; $tipo_residencia = ''; $res = null;

            // =================================================================================
            // ESTRATÉGIA 1: DETECÇÃO DE TABULAÇÃO (A "VISUALMENTE FÁCIL")
            // =================================================================================
            // Se houver tabs (\t), o usuário copiou mantendo as colunas. A separação é exata.
            if (strpos($textoDados, "\t") !== false) {
                $cols = explode("\t", $textoDados);
                $cols = array_map('trim', $cols); // Limpa espaços extras
                $cols = array_values(array_filter($cols)); // Remove colunas vazias

                $qtdCols = count($cols);

                if ($qtdCols >= 5) {
                    // Mapeamento baseado no relatório padrão 1-8
                    // [0] Nome | [1] Situação | ... [Localização no fim]

                    $nomeFinal = $cols[0];
                    $situacaoFinal = $cols[1]; // A situação é sempre a segunda coluna visual

                    // Localização: Pegamos do final para o começo para garantir,
                    // pois podem existir colunas extras no meio (como Sexo, Facção, etc)

                    // Estrutura esperada no fim: [ALA] [GAL] [BLOCO] [PISO] [TIPO] [RES]
                    // Exemplo: M	S	C	1	Cela	9

                    // Índice do último elemento
                    $last = $qtdCols - 1;

                    // Tenta identificar o RES (último número)
                    $resIndex = is_numeric($cols[$last]) ? $last : -1;

                    if ($resIndex > 0) {
                        $res = (int)$cols[$resIndex];
                        // Retrocede para achar o resto
                        // Padrão iPEN: ... ALA, GAL, BL, PISO, TIPO, RES
                        // Índices relativos ao fim:
                        $tipo_residencia = $cols[$resIndex - 1]; // "Cela"
                        $piso    = (int)$cols[$resIndex - 2];    // "1"
                        $bloco   = $cols[$resIndex - 3];         // "C"
                        $galeria = $cols[$resIndex - 4];         // "S"
                        $ala     = $cols[$resIndex - 5];         // "M"
                    }
                }
            }

            // =================================================================================
            // ESTRATÉGIA 2: FLUXO DE TEXTO (SE OS TABS FORAM PERDIDOS)
            // =================================================================================
            // Se nomeFinal ainda estiver vazio, significa que não tinha Tabs ou a estrutura falhou.
            // Usamos a lógica de Âncora Reversa + Banco de Dados.
            if (empty($nomeFinal)) {
                $partes = preg_split('/\s+/', $textoDados);
                $qtdPartes = count($partes);

                // 1. Acha a localização no fim (Igual código anterior, muito robusto)
                $resIndex = is_numeric(end($partes)) ? $qtdPartes - 1 : $qtdPartes;
                $pisoIndex = -1;

                // Busca Piso (numérico) perto do fim
                for ($k = $resIndex - 1; $k >= max(0, $resIndex - 7); $k--) {
                    if (is_numeric($partes[$k]) && strlen($partes[$k]) <= 2 && ($k - 3) >= 0) {
                        $pisoIndex = $k;
                        break;
                    }
                }

                if ($pisoIndex !== -1) {
                    $ala     = $partes[$pisoIndex - 3];
                    $galeria = $partes[$pisoIndex - 2];
                    $bloco   = $partes[$pisoIndex - 1];
                    $piso    = (int)$partes[$pisoIndex];
                    $res     = isset($partes[$resIndex]) && $resIndex < $qtdPartes ? (int)$partes[$resIndex] : null;

                    $tipoParts = [];
                    for ($j = $pisoIndex + 1; $j < $resIndex; $j++) $tipoParts[] = $partes[$j];
                    $tipo_residencia = implode(' ', $tipoParts);

                    // 2. Separa Nome vs Situação usando Banco de Dados (Fallback inteligente)
                    $textoMeio = implode(' ', array_slice($partes, 0, $pisoIndex - 3));

                    if (isset($mapaNomes[$ipen])) {
                        $nomeDB = trim($mapaNomes[$ipen]);
                        if (stripos($textoMeio, $nomeDB) === 0) {
                            $nomeFinal = $nomeDB;
                            $situacaoFinal = trim(substr($textoMeio, strlen($nomeDB)));
                        }
                    }

                    // Se ainda assim não achou (Novo interno sem tabs), assume tudo como nome para segurança
                    if (empty($nomeFinal)) {
                        $nomeFinal = $textoMeio;
                        $situacaoFinal = "VERIFICAR (NOVO)";
                    }
                }
            }

            // Se falhou tudo, pula
            if (empty($nomeFinal)) continue;

            $situacaoFinal = trim($situacaoFinal, "- ");

            $ipensImportados[] = $ipen;
            $dadosImportados[$ipen] = [
                'nome' => $nomeFinal,
                'situacao' => $situacaoFinal,
                'ala' => $ala,
                'galeria' => $galeria,
                'bloco' => $bloco,
                'piso' => $piso,
                'tipo_residencia' => $tipo_residencia,
                'res' => $res
            ];
        }

        if (empty($dadosImportados)) {
            throw new Exception("Nenhum registro válido identificado no texto colado.");
        }

        // Proteção anti-perda: bloqueia importação muito abaixo do volume esperado.
        $ativosAtuais = (int)$pdo->query("SELECT COUNT(*) FROM internos WHERE status = 'A'")->fetchColumn();
        $historicoReferencia = (int)$pdo->query("SELECT COALESCE(MAX(total_importados), 0) FROM internos_historico WHERE total_importados >= 100")->fetchColumn();
        $baseComparacao = max($ativosAtuais, $historicoReferencia);
        if ($baseComparacao > 0) {
            $minimoSeguro = (int)floor($baseComparacao * 0.70);
            if (count($dadosImportados) < $minimoSeguro) {
                throw new Exception(
                    "Importação bloqueada por segurança: {$linhasReconhecidas} linhas com IPEN, " .
                    count($dadosImportados) . " registros válidos para uma base esperada de {$baseComparacao}. " .
                    "Isso indica possível mudança no formato do relatório. Nenhum dado foi alterado."
                );
            }
        }

        // =================================================================================
        // GRAVAÇÃO NO BANCO
        // =================================================================================
        $pdo->beginTransaction();
        $agora = date('Y-m-d H:i:s');
        $novos = 0; $atualizados = 0; $inativados = 0;

        $stmtCheck = $pdo->prepare("SELECT nome, situacao, ala, galeria, bloco, res, status FROM internos WHERE ipen = ?");
        $stmtInsert = $pdo->prepare("INSERT INTO internos (ipen, nome, nome_social, situacao, ala, galeria, bloco, piso, tipo_residencia, res, status, data_ativo, kit) VALUES (?, ?, '', ?, ?, ?, ?, ?, ?, ?, 'A', ?, 0)");
        $stmtUpdate = $pdo->prepare("UPDATE internos SET nome=?, situacao=?, ala=?, galeria=?, bloco=?, piso=?, tipo_residencia=?, res=?, status='A', data_alterado=?, data_inativo=NULL WHERE ipen=?");

        foreach ($dadosImportados as $ipen => $d) {
            $stmtCheck->execute([$ipen]);
            $ex = $stmtCheck->fetch();

            if (!$ex) {
                $stmtInsert->execute([$ipen, $d['nome'], $d['situacao'], $d['ala'], $d['galeria'], $d['bloco'], $d['piso'], $d['tipo_residencia'], $d['res'], $agora]);
                $novos++;
            } else {
                $mudouLocal = ($ex['galeria'] != $d['galeria'] || $ex['bloco'] != $d['bloco'] || $ex['res'] != $d['res'] || $ex['ala'] != $d['ala']);

                // Só atualiza situação se não for o valor de fallback
                $sitValida = ($d['situacao'] !== 'VERIFICAR (NOVO)');
                $mudouSituacao = ($sitValida && trim($ex['situacao']) != trim($d['situacao']));

                $reativado = ($ex['status'] == 'I');

                if ($mudouLocal || $mudouSituacao || $reativado) {
                    // Se a importação veio do fallback "Verificar", mantém os dados antigos de nome/situação e atualiza só local
                    $nSave = $sitValida ? $d['nome'] : $ex['nome'];
                    $sSave = $sitValida ? $d['situacao'] : $ex['situacao'];

                    $stmtUpdate->execute([$nSave, $sSave, $d['ala'], $d['galeria'], $d['bloco'], $d['piso'], $d['tipo_residencia'], $d['res'], $agora, $ipen]);
                    $atualizados++;
                }
            }
        }

        // Inativação
        $stmtAtivos = $pdo->query("SELECT ipen FROM internos WHERE status = 'A'");
        $ativosNoBanco = $stmtAtivos->fetchAll(PDO::FETCH_COLUMN);
        $stmtInativar = $pdo->prepare("UPDATE internos SET status = 'I', data_inativo = ? WHERE ipen = ?");

        foreach ($ativosNoBanco as $ipenAtivo) {
            if (!in_array($ipenAtivo, $ipensImportados)) {
                $stmtInativar->execute([$agora, $ipenAtivo]);
                $inativados++;
            }
        }

        // Histórico
        try {
            $stmtResumo = $pdo->prepare("INSERT INTO internos_historico (data_importacao, registros_novos, registros_atualizados, registros_inativados, total_importados) VALUES (NOW(), ?, ?, ?, ?)");
            $stmtResumo->execute([$novos, $atualizados, $inativados, count($dadosImportados)]);
        } catch (Exception $e) {}

        $pdo->commit();
        ob_clean();
        echo json_encode([
            'success' => true,
            'total' => count($dadosImportados),
            'novos' => $novos,
            'atualizados' => $atualizados,
            'inativados' => $inativados
        ]);

    } catch (Exception $e) {
        if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
        ob_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}
?>

<!-- CONTEÚDO VISUAL -->
<div class="content-header px-0">
    <div class="container-fluid px-0">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-import mr-2 text-primary"></i>Importar Relatório 1-8</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="https://www.sc.gov.br/ipen/RelatorioIpen_028DetentosAlocadosAlfabeticaImprimir.asp?cd_Unidade=8019&Unidades=undefined&cd_Ordenacao=1"
                   target="_blank" class="btn btn-info font-weight-bold shadow-sm">
                   <i class="fas fa-external-link-alt mr-1"></i> Acessar Relatório iPEN
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title">Instruções</h3>
            </div>
            <div class="card-body">
                <div class="callout callout-info mb-4">
                    <h5>Como proceder:</h5>
                    <ol>
                        <li>Clique no botão azul acima para abrir o sistema iPEN em uma nova aba;</li>
                        <li>No iPEN, gere o relatório 1-8 da unidade;</li>
                        <li>Pressione <b>Ctrl + A</b> para selecionar todo o texto do relatório;</li>
                        <li>Pressione <b>Ctrl + C</b> para copiar o conteúdo selecionado;</li>
                        <li>Volte a esta tela e pressione <b>Ctrl + V</b> no campo abaixo;</li>
                        <li>Clique no botão "Processar Dados" para iniciar a atualização.</li>
                    </ol>
                </div>

                <!--
                     ATENÇÃO: A action aqui deve apontar para ONDE este código PHP está rodando.
                     Se este arquivo é incluído via 'paginas/dados_importa_18.php', mantenha.
                -->
                <form id="import-form" action="paginas/dados_importa_18.php" method="POST">
                    <div class="form-group">
                        <label>Conteúdo do Relatório:</label>
                        <textarea class="form-control font-monospace" name="report_data" rows="12" required
                                  placeholder="Cole o texto completo do relatório aqui..."
                                  style="background-color: #0c0e10; color: #4ade80; border: 1px solid #333; font-size: 0.8rem;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg mt-2">
                        <i class="fas fa-sync-alt mr-1"></i> Iniciar Processamento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DE RESULTADO -->
<div class="modal fade" id="modalResultados" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-check-circle mr-2"></i>Importação Concluída</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h1 class="display-4 font-weight-bold text-success" id="res_total">0</h1>
                <p class="text-muted text-uppercase small font-weight-bold">Internos Processados</p>
                <hr>
                <div class="row">
                    <div class="col-4 border-right"><h4 class="text-primary" id="res_novos">0</h4><small class="text-uppercase small">Novos</small></div>
                    <div class="col-4 border-right"><h4 class="text-info" id="res_atualizados">0</h4><small class="text-uppercase small">Atualizados</small></div>
                    <div class="col-4"><h4 class="text-danger" id="res_inativos">0</h4><small class="text-uppercase small">Inativados</small></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="loadPage('paginas/dados_importa_18.php')">FECHAR</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('import-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = this;
    const btn = form.querySelector('button[type="submit"]');

    // Salva o texto original
    const originalText = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processando...';

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, { method: 'POST', body: formData });

        // Verifica se a resposta HTTP foi OK antes de tentar ler o JSON
        if (!response.ok) {
            let detail = '';
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const errJson = await response.json();
                if (errJson && errJson.message) detail = ` - ${errJson.message}`;
            } else {
                const errText = (await response.text()).trim();
                if (errText) detail = ` - ${errText.slice(0, 200)}`;
            }
            throw new Error(`Erro HTTP: ${response.status}${detail}`);
        }

        const result = await response.json();

        if (result.success) {
            document.getElementById('res_total').innerText = result.total;
            document.getElementById('res_novos').innerText = result.novos;
            document.getElementById('res_atualizados').innerText = result.atualizados;
            document.getElementById('res_inativos').innerText = result.inativados;
            $('#modalResultados').modal('show');
            form.reset();
        } else {
            alert("Erro do Sistema: " + result.message);
        }
    } catch (error) {
        console.error("Erro na requisição:", error);
        // Tenta mostrar uma mensagem amigável, mesmo se o JSON falhar
        alert("Erro Crítico: Ocorreu uma falha no processamento. Verifique o console (F12) para detalhes.\n\n" + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});
</script>
