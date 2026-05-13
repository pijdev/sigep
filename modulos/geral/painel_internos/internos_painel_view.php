<?php
require_once __DIR__ . '/internos_painel_controller.php';
?>

<link rel="stylesheet" href="/modulos/geral/painel_internos/assets/css/internos_painel.css">

<section class="content pt-3">
    <div class="container-fluid">

        <!-- GALERIAS -->
        <div class="galerias-container">
            <?php
            // Ordem customizada: A-H em primeiro, depois Semi-Aberto (SA, SB, SC, SD, SE, ST), depois T
            $ordem_galerias = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'T'];
            $ordem_semi_aberto = ['SA', 'SB', 'SC', 'SD', 'SE', 'ST'];

            foreach ($ordem_galerias as $galeria):
                if (!isset($estrutura_com_blocos[$galeria])) continue;

                $blocos = $estrutura_com_blocos[$galeria];
                $info_gal = obterInfoGaleria($galeria);

                // Calcular total de ativos
                $total_gal = 0;
                foreach ($blocos as $bloco_celas) {
                    foreach ($bloco_celas as $cela_data) {
                        $total_gal += $cela_data['total_ativos'];
                    }
                }
            ?>
                <div class="galeria-card" style="border-top: 5px solid <?= $info_gal['bg'] ?>;">
                    <div class="galeria-header" style="background: <?= $info_gal['bg'] ?>; color: <?= $info_gal['text'] ?>;">
                        <span><?= htmlspecialchars($info_gal['label']) ?></span>
                        <div class="galeria-contador">
                            <i class="fas fa-users mr-1"></i> <?= $total_gal ?>
                        </div>
                    </div>

                    <div class="galeria-content">
                        <?php
                        // Renderizar blocos (ou corredor se sem blocos)
                        foreach ($blocos as $bloco_key => $celas):

                            // Determinar nome do bloco
                            if (empty($bloco_key)) {
                                // Sem blocos (F, G, H, etc)
                                $nome_bloco = '';
                                $mostra_header_bloco = false;
                            } else {
                                // Com blocos
                                $nome_bloco = "Bloco $bloco_key";
                                $mostra_header_bloco = true;
                            }

                            // Calcular total do bloco
                            $total_bloco = 0;
                            foreach ($celas as $cela_data) {
                                $total_bloco += $cela_data['total_ativos'];
                            }
                        ?>
                            <div class="ala-section">
                                <?php if ($mostra_header_bloco): ?>
                                    <div class="ala-header" style="border-left: 4px solid <?= $info_gal['bg'] ?>;">
                                        <i class="fas fa-layer-group mr-2"></i> <?= htmlspecialchars($nome_bloco) ?> (<?= $total_bloco ?> internos)
                                        <?php
                                        // Obter tipo de residência predominante do bloco
                                        $tipos_residencia_bloco = [];
                                        foreach ($celas as $cela_data) {
                                            if ($cela_data['tipo_residencia']) {
                                                $tipos_residencia_bloco[] = $cela_data['tipo_residencia'];
                                            }
                                        }
                                        if (!empty($tipos_residencia_bloco)) {
                                            // Contar frequência de cada tipo
                                            $contagem_tipos = array_count_values($tipos_residencia_bloco);
                                            // Pegar o mais frequente
                                            $tipo_predominante = array_keys($contagem_tipos, max($contagem_tipos))[0];
                                            $tipo_info = getTipoResidenciaInfo($tipo_predominante);
                                            $nome_exibicao = $tipo_info['nome_exibicao'] ?? $tipo_predominante;
                                        ?>
                                            <span class="badge <?= $tipo_info['classe'] ?> ml-2"
                                                style="background-color: <?= $tipo_info['cor'] ?>; color: <?= $tipo_info['cor_texto'] ?>; font-size: 0.75rem; padding: 3px 8px;"
                                                title="Tipo de residência: <?= htmlspecialchars($tipo_info['descricao'] ?? $nome_exibicao) ?>">
                                                <i class="<?= $tipo_info['icone'] ?> mr-1"></i><?= htmlspecialchars($nome_exibicao) ?>
                                            </span>
                                        <?php
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <div class="celas-grid">
                                    <?php
                                    // Ordenar celas numericamente
                                    $celas_ordenadas = [];
                                    foreach ($celas as $cela_num => $cela_data) {
                                        $num = (int)preg_replace('/[^0-9]/', '', $cela_num);
                                        $celas_ordenadas[$num] = [$cela_num, $cela_data];
                                    }
                                    ksort($celas_ordenadas);

                                    foreach ($celas_ordenadas as $num_key => $cela_info):
                                        [$cela_num, $cela_data] = $cela_info;

                                        // Determinar cor do contador (vermelho se tem mudança, azul se não)
                                        $tem_mudanca = $cela_data['tem_mudanca'] ?? false;
                                        $classe_contador = $tem_mudanca ? 'contador-alterado' : 'contador-normal';
                                        $total = $cela_data['total_ativos'];
                                        $ala_real = $cela_data['ala']; // Manter ala da cela
                                        $eh_lgbt = false;
                                        if (($galeria === 'E' && in_array((int)$cela_num, [9, 10])) ||
                                            ($galeria === 'SE' && $cela_num == 1)
                                        ) {
                                            $eh_lgbt = true;
                                        }
                                    ?>
                                        <div class="cela <?= $eh_lgbt ? 'lgbt' : '' ?> <?= $tem_mudanca ? 'com-mudanca' : '' ?>"
                                            data-contador="<?= $classe_contador ?>"
                                            onclick="window.abrirCela('<?= htmlspecialchars($galeria) ?>', '<?= htmlspecialchars($ala_real) ?>', '<?= htmlspecialchars($cela_num) ?>', '<?= htmlspecialchars($bloco_key) ?>')"
                                            title="<?= htmlspecialchars($info_gal['label']) ?> - Cela <?= htmlspecialchars($cela_num) ?> - <?= $total ?> interno(s) ativo(s)<?= $eh_lgbt ? ' (LGBT)' : '' ?><?= $tem_mudanca ? ' - Com mudanças recentes' : '' ?><?= $cela_data['tipo_residencia'] ? ' - Tipo: ' . htmlspecialchars($cela_data['tipo_residencia']) : '' ?>">
                                            <span class="cela-numero">Cela <?= htmlspecialchars($cela_num) ?></span>
                                            <span class="cela-total <?= $classe_contador ?>"><?= $total ?></span>
                                            <?php if ($cela_data['tipo_residencia']): ?>
                                                <?php $tipo_info = getTipoResidenciaInfo($cela_data['tipo_residencia']); ?>
                                                <?php $nome_exibicao = $tipo_info['nome_exibicao'] ?? $cela_data['tipo_residencia']; ?>
                                                <span class="badge <?= $tipo_info['classe'] ?> ml-1"
                                                    style="background-color: <?= $tipo_info['cor'] ?>; color: <?= $tipo_info['cor_texto'] ?>; font-size: 0.7rem; padding: 2px 6px;"
                                                    title="Tipo de residência: <?= htmlspecialchars($tipo_info['descricao'] ?? $nome_exibicao) ?>">
                                                    <i class="<?= $tipo_info['icone'] ?>"></i>
                                                </span>
                                            <?php endif; ?>
                                            <div class="cela-itens" id="itens-<?= htmlspecialchars($galeria) ?>-<?= htmlspecialchars($ala_real) ?>-<?= htmlspecialchars($cela_num) ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach;

            // SEMI-ABERTO E TRIAGEM - EXIBIR GALERIAS SA, SB, SC, SD, SE, ST, T
            foreach ($ordem_semi_aberto as $galeria_especial):
                if (!isset($estrutura_com_blocos[$galeria_especial])) continue;

                $blocos = $estrutura_com_blocos[$galeria_especial];
                $info_gal = obterInfoGaleria($galeria_especial);

                // Calcular total de ativos
                $total_gal = 0;
                foreach ($blocos as $bloco_celas) {
                    foreach ($bloco_celas as $cela_data) {
                        $total_gal += $cela_data['total_ativos'];
                    }
                }
            ?>
                <div style="width: 100%; margin-top: 20px;">
                    <div class="galeria-card" style="border-top: 5px solid <?= $info_gal['bg'] ?>;">
                        <div class="galeria-header" style="background: <?= $info_gal['bg'] ?>; color: <?= $info_gal['text'] ?>;">
                            <span><?= htmlspecialchars($info_gal['label']) ?></span>
                            <div class="galeria-contador">
                                <i class="fas fa-users mr-1"></i> <?= $total_gal ?>
                            </div>
                        </div>

                        <div class="galeria-content">
                            <div class="ala-section">
                                <div class="celas-grid">
                                    <?php
                                    // Ordenar celas numericamente
                                    $celas_ordenadas = [];
                                    foreach ($blocos[''] ?? [] as $cela_num => $cela_data) {
                                        $num = (int)preg_replace('/[^0-9]/', '', $cela_num);
                                        $celas_ordenadas[$num] = [$cela_num, $cela_data];
                                    }
                                    ksort($celas_ordenadas);

                                    foreach ($celas_ordenadas as $num_key => $cela_info):
                                        [$cela_num, $cela_data] = $cela_info;

                                        // Determinar cor do contador
                                        $tem_mudanca = $cela_data['tem_mudanca'] ?? false;
                                        $tem_md = $cela_data['tem_md'] ?? false;
                                        $classe_contador = $tem_mudanca ? 'contador-alterado' : ($tem_md ? 'contador-md' : 'contador-normal');
                                        $total = $cela_data['total_ativos'];
                                        $ala_real = $cela_data['ala'];

                                        // Verificar se é cela LGBT
                                        $eh_lgbt = ($galeria_especial === 'ST' && $cela_num == 1);
                                    ?>
                                        <div class="cela <?= $eh_lgbt ? 'lgbt' : '' ?> <?= $tem_mudanca ? 'com-mudanca' : '' ?> <?= $tem_md ? 'com-md' : '' ?>"
                                            data-contador="<?= $classe_contador ?>"
                                            onclick="window.abrirCela('<?= htmlspecialchars($galeria_especial) ?>', '<?= htmlspecialchars($ala_real) ?>', '<?= htmlspecialchars($cela_num) ?>', '<?= htmlspecialchars($galeria_especial) ?>')"
                                            title="<?= htmlspecialchars($info_gal['label']) ?> - Cela <?= htmlspecialchars($cela_num) ?> - <?= $total ?> interno(s) ativo(s)<?= $eh_lgbt ? ' (LGBT)' : '' ?><?= $tem_mudanca ? ' - Com mudanças recentes' : '' ?><?= $tem_md ? ' - Com MD ativa' : '' ?><?= $cela_data['tipo_residencia'] ? ' - Tipo: ' . htmlspecialchars($cela_data['tipo_residencia']) : '' ?>">
                                            <span class="cela-numero">Cela <?= htmlspecialchars($cela_num) ?></span>
                                            <span class="cela-total <?= $classe_contador ?>"><?= $total ?></span>
                                            <?php if ($cela_data['tipo_residencia']): ?>
                                                <?php $tipo_info = getTipoResidenciaInfo($cela_data['tipo_residencia']); ?>
                                                <?php $nome_exibicao = $tipo_info['nome_exibicao'] ?? $cela_data['tipo_residencia']; ?>
                                                <span class="badge <?= $tipo_info['classe'] ?> ml-1"
                                                    style="background-color: <?= $tipo_info['cor'] ?>; color: <?= $tipo_info['cor_texto'] ?>; font-size: 0.7rem; padding: 2px 6px;"
                                                    title="Tipo de residência: <?= htmlspecialchars($tipo_info['descricao'] ?? $nome_exibicao) ?>">
                                                    <i class="<?= $tipo_info['icone'] ?>"></i>
                                                </span>
                                            <?php endif; ?>
                                            <div class="cela-itens" id="itens-<?= htmlspecialchars($galeria_especial) ?>-<?= htmlspecialchars($ala_real) ?>-<?= htmlspecialchars($cela_num) ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<!-- OVERLAY (fundo escuro ao abrir offcanvas) -->
<div class="overlay-offcanvas" id="overlayOffcanvasCela" onclick="window.fecharOffcanvasCela()"></div>

<!-- OFFCANVAS DETALHES DA CELA -->
<aside class="control-sidebar control-sidebar-dark offcanvas-prison p-3" style="width: 600px;" id="offcanvasCela">
    <div class="offcanvas-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid rgba(255,255,255,0.2);">
        <h5 class="text-white m-0">Detalhes da Cela</h5>
        <button type="button" class="close text-white" onclick="window.fecharOffcanvasCela()" style="font-size: 24px; opacity: 0.8;">&times;</button>
    </div>
    <div class="offcanvas-body-cela" id="offcanvasBodyCela">
        <div style="text-align: center; padding: 30px;">
            <div class="loading-spinner"></div>
            <p style="margin-top: 15px;">Carregando dados...</p>
        </div>
    </div>
</aside>

<!-- MODAL ELETRÔNICOS -->
<div class="modal fade" id="modalItens" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes dos Itens</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalItensBody">
                <!-- Conteúdo será inserido via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- MODAL HISTÓRICO COMPLETO -->
<div id="historicoModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border: 1px solid #ccc; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 1060; max-width: 800px; width: 90%;">
    <div style="padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <h5 style="margin: 0; font-weight: bold;">Histórico Completo de Movimentações</h5>
        <button type="button" style="background: none; border: none; font-size: 24px; cursor: pointer;" onclick="fecharModal('historicoModal')">&times;</button>
    </div>
    <div id="historicoModalBody" style="padding: 20px; max-height: 500px; overflow-y: auto;">
        <div style="text-align: center; padding: 30px;">
            <div class="loading-spinner"></div>
            <p>Carregando histórico...</p>
        </div>
    </div>
</div>

<script src="/modulos/geral/painel_internos/assets/js/internos_painel.js"></script>
</body>

</html>
