<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die('Acesso Negado.');
}

$pdo = null;
try {
    $config = require_once '../conf/db.php';
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare('SELECT * FROM acesso_seguro WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $usuario = $stmt->fetch();
} catch (PDOException $e) {
    die('Erro ao conectar ao banco.');
}

$isKiosk = (int)($usuario['is_kiosk'] ?? 0) === 1;
$hasKioskToken = !empty($usuario['kiosk_token']);
$tokenUpdatedAt = !empty($usuario['kiosk_token_updated_at']) ? date('d/m/Y H:i', strtotime($usuario['kiosk_token_updated_at'])) : '';
?>

<div class="row">
    <div class="col-md-4 col-lg-3">
        <div class="card card-primary card-outline shadow">
            <div class="card-body box-profile">
                <div class="text-center mb-3">
                    <i class="fas fa-user-circle fa-6x text-primary shadow-sm rounded-circle"></i>
                </div>
                <h3 class="profile-username text-center font-weight-bold"><?= $usuario['nome'] ?></h3>
                <p class="text-muted text-center mb-1"><?= $usuario['usuario'] ?></p>
                <div class="text-center">
                    <span class="badge badge-info uppercase px-3 py-2"><?= $usuario['setor'] ?></span>
                </div>

                <ul class="list-group list-group-unbordered mb-3 mt-4">
                    <li class="list-group-item">
                        <b>Cargo/Nível</b> <a class="float-right text-bold"><?= $usuario['is_admin'] ? 'Administrador' : 'Operacional' ?></a>
                    </li>
                    <li class="list-group-item">
                        <b>Membro desde</b> <a class="float-right small text-muted"><?= date('d/m/Y', strtotime($usuario['criado_em'])) ?></a>
                    </li>
                </ul>

                <a href="auth/logout.php" class="btn btn-outline-danger btn-block font-weight-bold"><b>EFETUAR LOGOUT</b></a>
            </div>
        </div>
    </div>

    <div class="col-md-8 col-lg-9">
        <div class="card card-dark shadow">
            <div class="card-header border-bottom">
                <h3 class="card-title font-weight-bold"><i class="fas fa-user-cog mr-2"></i>Editar Minhas Informações</h3>
            </div>
            <div class="card-body">
                <form id="formPerfilPessoal" onsubmit="window.savePerfilData(event)">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Seu Nome:</label>
                            <input type="text" name="nome" value="<?= $usuario['nome'] ?>" class="form-control form-control-lg font-weight-bold" required>
                            <small class="text-muted">Como você aparecerá no sistema e logs.</small>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Login de Acesso:</label>
                            <input type="text" value="<?= $usuario['usuario'] ?>" class="form-control form-control-lg bg-light" disabled title="Apenas a TI pode mudar o login">
                        </div>
                    </div>

                    <div class="row mt-3 border-top pt-4">
                        <div class="col-md-12 mb-3">
                            <h5 class="text-warning font-weight-bold"><i class="fas fa-key mr-1"></i> Segurança</h5>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Alterar Senha:</label>
                            <input type="password" name="nova_senha" id="new_pass" class="form-control" placeholder="Mínimo 6 caracteres">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Confirmar Nova Senha:</label>
                            <input type="password" id="conf_pass" class="form-control" placeholder="Repita a senha">
                        </div>
                        <div class="col-md-12 mt-2">
                            <p class="small text-muted"><i class="fas fa-info-circle mr-1"></i> Caso não queira alterar sua senha, basta deixar estes campos vazios.</p>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-lg shadow px-5 font-weight-bold">
                                SALVAR ALTERAÇÕES <i class="fas fa-check-circle ml-2"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <div class="row mt-4 border-top pt-4">
                    <div class="col-12 mb-2">
                        <h5 class="text-info font-weight-bold"><i class="fas fa-desktop mr-1"></i> Modo Kiosk</h5>
                        <p class="small text-muted mb-3">Use este modo para acesso automático via URL com token no quiosque.</p>
                    </div>

                    <div class="col-12">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="perfil_kiosk_toggle" <?= $isKiosk ? 'checked' : '' ?>>
                            <label class="custom-control-label font-weight-bold" for="perfil_kiosk_toggle">Ativar Modo Kiosk</label>
                        </div>
                    </div>

                    <div class="col-12" id="perfilKioskArea" style="display: <?= $isKiosk ? 'block' : 'none' ?>;">
                        <div class="alert alert-light border">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <div class="font-weight-bold">Token de Acesso Kiosk</div>
                                    <div class="small text-muted" id="perfilTokenInfo">
                                        <?= $hasKioskToken ? 'Token configurado em: ' . $tokenUpdatedAt : 'Nenhum token configurado.' ?>
                                    </div>
                                </div>
                                <div class="mt-2 mt-md-0">
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnPerfilGerar" style="display: <?= $hasKioskToken ? 'none' : 'inline-block' ?>;" onclick="window.gerarTokenKiosk(false)">Gerar Token</button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" id="btnPerfilRegerar" style="display: <?= $hasKioskToken ? 'inline-block' : 'none' ?>;" onclick="window.gerarTokenKiosk(true)">Regenerar Token</button>
                                </div>
                            </div>

                            <div id="perfilTokenResult" class="mt-3" style="display:none;">
                                <label class="small font-weight-bold mb-1">Token gerado (copie agora):</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" id="perfilTokenValue" class="form-control" readonly>
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.copyPerfilToken()">Copiar</button>
                                    </div>
                                </div>
                                <small class="text-danger">Este token aparece só agora. Salve em local seguro.</small>
                            </div>

                            <div class="small text-muted mt-2">
                                URL do quiosque: <code>http://sigep.pij.local/autenticacao/quiosque?t=SEU_TOKEN</code>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light py-3 border-top text-center text-muted">
                O SIGEP preza pela segurança dos dados. Sua última atualização foi em:
                <strong><?= !empty($usuario['criado_em']) ? date('d/m/Y H:i', strtotime($usuario['criado_em'])) : 'Recém-criado' ?></strong>
            </div>
        </div>
    </div>
</div>

<script>
window.perfilKioskState = {
    isKiosk: <?= $isKiosk ? 'true' : 'false' ?>,
    hasToken: <?= $hasKioskToken ? 'true' : 'false' ?>
};

window.renderPerfilKiosk = () => {
    const area = document.getElementById('perfilKioskArea');
    const btnGerar = document.getElementById('btnPerfilGerar');
    const btnRegerar = document.getElementById('btnPerfilRegerar');
    const tokenResult = document.getElementById('perfilTokenResult');

    area.style.display = window.perfilKioskState.isKiosk ? 'block' : 'none';
    tokenResult.style.display = 'none';

    if (!window.perfilKioskState.isKiosk) {
        return;
    }

    btnGerar.style.display = window.perfilKioskState.hasToken ? 'none' : 'inline-block';
    btnRegerar.style.display = window.perfilKioskState.hasToken ? 'inline-block' : 'none';
};

window.copyPerfilToken = async () => {
    const input = document.getElementById('perfilTokenValue');
    if (!input.value) return;

    input.select();
    input.setSelectionRange(0, 99999);
    try {
        await navigator.clipboard.writeText(input.value);
        alert('Token copiado para a área de transferência.');
    } catch (err) {
        document.execCommand('copy');
        alert('Token copiado para a área de transferência.');
    }
};

document.getElementById('perfil_kiosk_toggle').addEventListener('change', async function () {
    const enabled = this.checked;

    if (!enabled && !confirm('Desativar Modo Kiosk irá revogar o token atual. Deseja continuar?')) {
        this.checked = true;
        return;
    }

    const fd = new FormData();
    fd.append('action', 'toggle_kiosk_mode');
    fd.append('is_kiosk', enabled ? '1' : '0');

    const res = await fetch('auth/config_pessoal.php', { method: 'POST', body: fd });
    const json = await res.json();

    if (!json.success) {
        alert(json.message || 'Falha ao atualizar Modo Kiosk.');
        this.checked = !enabled;
        return;
    }

    window.perfilKioskState.isKiosk = enabled;
    if (!enabled) {
        window.perfilKioskState.hasToken = false;
        document.getElementById('perfilTokenInfo').textContent = 'Nenhum token configurado.';
    }
    window.renderPerfilKiosk();
});

window.gerarTokenKiosk = async (regenerar) => {
    if (!window.perfilKioskState.isKiosk) {
        alert('Ative o Modo Kiosk antes de gerar token.');
        return;
    }

    if (regenerar && !confirm('Regenerar token irá invalidar o token anterior. Deseja continuar?')) {
        return;
    }

    const fd = new FormData();
    fd.append('action', regenerar ? 'regenerate_kiosk_token' : 'generate_kiosk_token');

    const res = await fetch('auth/config_pessoal.php', { method: 'POST', body: fd });
    const json = await res.json();

    if (!json.success) {
        alert(json.message || 'Falha ao gerar token.');
        return;
    }

    window.perfilKioskState.hasToken = true;
    window.renderPerfilKiosk();

    const tokenResult = document.getElementById('perfilTokenResult');
    const tokenValue = document.getElementById('perfilTokenValue');
    const info = document.getElementById('perfilTokenInfo');

    tokenValue.value = json.token || '';
    tokenResult.style.display = 'block';
    info.textContent = 'Token configurado agora mesmo. Copie e guarde com segurança.';
};

window.savePerfilData = async (e) => {
    e.preventDefault();
    const f = e.target;
    const n = document.getElementById('new_pass').value;
    const c = document.getElementById('conf_pass').value;

    if (n !== '' || c !== '') {
        if (n !== c) return alert('Erro: As senhas não conferem!');
        if (n.length < 4) return alert('A senha precisa ter pelo menos 4 caracteres.');
    }

    const btn = f.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Gravando...';

    try {
        const formData = new FormData(f);
        const res = await fetch('auth/config_pessoal.php', { method: 'POST', body: formData });
        const json = await res.json();

        if (json.success) {
            alert('Parabéns! Seus dados foram atualizados no banco.');
            location.reload();
        } else {
            alert('Houve um erro no servidor: ' + (json.message || 'Falha desconhecida.'));
        }
    } catch (err) {
        alert('Erro técnico: Verifique sua conexão com o banco.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
};
</script>
