<?php
require_once __DIR__ . '/gestao_contas_logica.php';
?>

<!-- CSS específico do módulo -->
<link rel="stylesheet" href="/modulos/seguranca/gestao_contas/assets/css/gestao_contas.css?v=<?= time() ?>">

<div class="content-header px-0">
    <div class="container-fluid px-0">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 font-weight-bold text-dark text-uppercase"><i class="fas fa-users-cog mr-2"></i>Gestão de Contas e Acessos</h1>
            </div>
            <div class="col-sm-6 text-right">
                <button class="btn btn-primary shadow" onclick="modalPij(null)">
                    <i class="fas fa-user-plus mr-1"></i> NOVO USUÁRIO
                </button>
            </div>
        </div>
    </div>
</div>
<div class="card card-dark shadow-lg border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped m-0 text-sm align-middle">
                <thead class="bg-dark">
                    <tr>
                        <th class="pl-3">NOME COMPLETO</th>
                        <th>LOGIN</th>
                        <th>SETOR</th>
                        <th class="text-center">ACESSO</th>
                        <th class="text-center">KIOSK</th>
                        <th class="text-center">OPÇÕES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($viewData['usuarios'] as $u):
                        $status_class = ($u['status'] == 'Inativo') ? 'text-muted' : '';
                        $tokenInfo = !empty($u['kiosk_token_updated_at']) ? date('d/m/Y H:i', strtotime($u['kiosk_token_updated_at'])) : '';
                    ?>
                        <tr class="<?= $status_class ?>">
                            <td class="pl-3 font-weight-bold"><?= $u['nome'] ?> <?= ($u['status'] == 'Inativo' ? '<span class="badge badge-warning">BLOQUEADO</span>' : '') ?></td>
                            <td><?= $u['usuario'] ?></td>
                            <td><small class="badge badge-light border uppercase"><?= $u['setor'] ?></small></td>
                            <td class="text-center"><?= $u['is_admin'] ? '<span class="badge badge-danger">ADMIN</span>' : '<span class="badge badge-secondary">COMUM</span>' ?></td>
                            <td class="text-center">
                                <?php if ((int)($u['is_kiosk'] ?? 0) === 1): ?>
                                    <span class="badge badge-success">ATIVO</span>
                                    <?php if ($tokenInfo !== ''): ?>
                                        <div class="small text-muted mt-1"><?= $tokenInfo ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-light border">DESLIGADO</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-xs btn-primary px-3 shadow-sm mr-1" onclick='modalPij(<?= json_encode($u) ?>)'><i class="fas fa-edit"></i> CONFIGURAR</button>
                                <button class="btn btn-xs btn-danger shadow-sm" onclick="delU(<?= $u['id'] ?>)"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de Configuração -->
<div class="modal fade" id="mPij">
    <div class="modal-dialog modal-xl">
        <form id="fPij" onsubmit="savePijAcc(event)" class="modal-content shadow-lg border-0">
            <div class="modal-header bg-dark font-weight-bold py-2 shadow text-uppercase">Configuração Setorial de Acesso</div>
            <div class="modal-body py-3">
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="user_id" id="i_uid">
                <div class="row">
                    <div class="col-md-3 form-group"><label class="small font-weight-bold">NOME COMPLETO:</label><input type="text" name="nome" id="i_nome" class="form-control" required></div>
                    <div class="col-md-2 form-group"><label class="small font-weight-bold">LOGIN (USUÁRIO):</label><input type="text" name="usuario" id="i_login" class="form-control" required></div>
                    <div class="col-md-2 form-group"><label class="small font-weight-bold text-danger">SENHA:</label><input type="password" name="senha" class="form-control" placeholder="Deixe vazio se manter"></div>
                    <div class="col-md-3 form-group"><label class="small font-weight-bold">SETOR DE ORIGEM:</label>
                        <select name="setor" id="i_setor" class="form-control font-weight-bold text-primary">
                            <?php foreach ($viewData['matriz_acesso'] as $sl => $label) echo "<option value='$label'>$label</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2 form-group"><label class="small font-weight-bold">STATUS:</label><select name="status" id="i_status" class="form-control">
                            <option value="Ativo">ATIVO (LIBERADO)</option>
                            <option value="Inativo">INATIVO (BLOQUEADO)</option>
                        </select></div>
                </div>

                <div class="row bg-light border p-2 mb-3 align-items-center no-gutters">
                    <div class="col-md-8 font-weight-bold text-muted small"><i class="fas fa-shield-alt mr-2 text-primary"></i> ESTE USUÁRIO É UM ADMINISTRADOR DO SIGEP? (ACESSO TOTAL EM TUDO)</div>
                    <div class="col-md-4 text-right pr-4">
                        <div class="custom-control custom-switch"><input type="checkbox" name="is_admin" value="1" class="custom-control-input" id="i_adm"><label class="custom-control-label" for="i_adm">NÍVEL MASTER</label></div>
                    </div>
                </div>

                <div class="row bg-light border p-2 mb-3 align-items-center no-gutters">
                    <div class="col-md-8 font-weight-bold text-muted small"><i class="fas fa-desktop mr-2 text-info"></i> MODO KIOSK PARA AUTO-LOGIN VIA TOKEN</div>
                    <div class="col-md-4 text-right pr-4">
                        <div class="custom-control custom-switch"><input type="checkbox" name="is_kiosk" value="1" class="custom-control-input" id="i_kiosk"><label class="custom-control-label" for="i_kiosk">ATIVAR MODO KIOSK</label></div>
                    </div>
                </div>

                <div class="alert alert-light border" id="kioskAdminBox" style="display:none;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <div class="font-weight-bold">Token Kiosk</div>
                            <div class="small text-muted" id="kioskAdminTokenInfo">Token não configurado.</div>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAdminGenToken" onclick="adminKioskToken(false)">Gerar Token</button>
                            <button type="button" class="btn btn-sm btn-outline-warning" id="btnAdminRegenToken" onclick="adminKioskToken(true)">Regenerar Token</button>
                        </div>
                    </div>
                    <div id="kioskAdminTokenResult" class="mt-3" style="display:none;">
                        <label class="small font-weight-bold mb-1">Token gerado (copie agora):</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="kioskAdminTokenValue" class="form-control" readonly>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary" onclick="copyKioskToken('kioskAdminTokenValue')">Copiar</button>
                            </div>
                        </div>
                        <small class="text-danger">Este token aparece só agora. Salve em local seguro.</small>
                    </div>
                </div>

                <h6 class="text-center font-weight-bold border-bottom pb-2 text-muted mb-4 uppercase">Permissões Específicas por Setor</h6>
                <div class="row"><?php foreach ($viewData['matriz_acesso'] as $slug => $label): $colname = 'perm_' . $slug; ?>
                        <div class="col-md-4 mb-2 px-2">
                            <div class="p-2 border rounded bg-white shadow-sm" style="border-left: 5px solid #64748b !important;">
                                <div class="font-weight-bold small text-uppercase text-truncate mb-2"><?= $label ?>:</div>
                                <div class="d-flex justify-content-between">
                                    <div class="custom-control custom-radio"><input type="radio" class="custom-control-input" name="<?= $colname ?>" id="<?= $slug ?>_0" value="0" checked><label class="custom-control-label small" for="<?= $slug ?>_0">OFF</label></div>
                                    <div class="custom-control custom-radio text-info"><input type="radio" class="custom-control-input" name="<?= $colname ?>" id="<?= $slug ?>_1" value="1"><label class="custom-control-label small" for="<?= $slug ?>_1">READ</label></div>
                                    <div class="custom-control custom-radio text-success"><input type="radio" class="custom-control-input" name="<?= $colname ?>" id="<?= $slug ?>_2" value="2"><label class="custom-control-label small font-weight-bold" for="<?= $slug ?>_2">FULL</label></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary btn-block shadow py-3 font-weight-bold">SALVAR CONFIGURAÇÕES NO SIGEP <i class="fas fa-check-double ml-1"></i></button></div>
        </form>
    </div>
</div>

<!-- JavaScript específico do módulo -->
<script src="/modulos/seguranca/gestao_contas/assets/js/gestao_contas.js?v=<?= time() ?>"></script>