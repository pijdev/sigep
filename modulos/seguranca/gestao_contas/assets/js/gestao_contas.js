// JavaScript específico do módulo Gestão de Contas e Acessos
window.currentAdminUser = null;

window.copyKioskToken = async (inputId) => {
    const input = document.getElementById(inputId);
    if (!input || !input.value) {
        return;
    }
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

window.renderAdminKioskBox = () => {
    const box = document.getElementById('kioskAdminBox');
    const info = document.getElementById('kioskAdminTokenInfo');
    const btnGen = document.getElementById('btnAdminGenToken');
    const btnRegen = document.getElementById('btnAdminRegenToken');

    const isKiosk = $('#i_kiosk').is(':checked');
    const hasUser = !!$('#i_uid').val();
    box.style.display = (isKiosk && hasUser) ? '' : 'none';

    if (!isKiosk || !hasUser) {
        return;
    }

    const hasToken = !!(window.currentAdminUser && window.currentAdminUser.kiosk_token);
    const updatedAt = window.currentAdminUser && window.currentAdminUser.kiosk_token_updated_at
        ? new Date(window.currentAdminUser.kiosk_token_updated_at.replace(' ', 'T')).toLocaleString('pt-BR')
        : '';

    info.textContent = hasToken
        ? ('Token configurado em: ' + (updatedAt || 'data indisponível'))
        : 'Token não configurado.';

    btnGen.style.display = hasToken ? 'none' : 'inline-block';
    btnRegen.style.display = hasToken ? 'inline-block' : 'none';
};

window.modalPij = (u = null) => {
    window.currentAdminUser = u;
    $('#fPij')[0].reset();
    $('#i_uid').val(u ? u.id : '');
    $('#i_nome').val(u ? u.nome : '');
    $('#i_login').val(u ? u.usuario : '');
    $('#i_setor').val(u ? u.setor : 'Portaria');
    $('#i_status').val(u ? u.status : 'Ativo');
    $('#i_adm').prop('checked', u ? (u.is_admin == 1) : false);
    $('#i_kiosk').prop('checked', u ? ((u.is_kiosk || 0) == 1) : false);

    // Resetar todas as permissões para OFF primeiro
    const permissoes = ['censura', 'almoxarifado', 'laboral', 'seg_trab', 'rh', 'coord', 'eclusa', 'direcao', 'portaria', 'ti', 'serralheria', 'escola', 'carga', 'industria', 'juridico', 'cozinha'];
    permissoes.forEach(slug => {
        if (u && u['perm_' + slug] !== undefined) {
            $(`input[name="perm_${slug}"][value="${u['perm_' + slug]}"]`).prop('checked', true);
        } else {
            $(`input[name="perm_${slug}"][value="0"]`).prop('checked', true);
        }
    });

    document.getElementById('kioskAdminTokenResult').style.display = 'none';
    document.getElementById('kioskAdminTokenValue').value = '';
    window.renderAdminKioskBox();
    $('#mPij').modal('show');
};

$('#i_kiosk').on('change', function () {
    window.renderAdminKioskBox();
});

window.adminKioskToken = async (regenerate) => {
    const userId = parseInt($('#i_uid').val(), 10);
    if (!userId) {
        alert('Salve o usuário antes de gerar token kiosk.');
        return;
    }

    if (!$('#i_kiosk').is(':checked')) {
        alert('Ative o Modo Kiosk antes de gerar token.');
        return;
    }

    if (regenerate && !confirm('Regenerar token irá invalidar o token anterior. Deseja continuar?')) {
        return;
    }

    const fd = new FormData();
    fd.append('action', regenerate ? 'regenerate_kiosk_token_admin' : 'generate_kiosk_token_admin');
    fd.append('user_id', String(userId));

    const res = await fetch('modulos/seguranca/gestao_contas/gestao_contas_logica.php', {
        method: 'POST',
        body: fd
    });
    const json = await res.json();

    if (!json.success) {
        alert(json.message || 'Falha ao processar token kiosk.');
        return;
    }

    const tokenResult = document.getElementById('kioskAdminTokenResult');
    const tokenValue = document.getElementById('kioskAdminTokenValue');
    tokenValue.value = json.token || '';
    tokenResult.style.display = '';

    if (!window.currentAdminUser) {
        window.currentAdminUser = {};
    }
    window.currentAdminUser.kiosk_token = '***';
    window.currentAdminUser.kiosk_token_updated_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
    window.renderAdminKioskBox();
};

window.savePijAcc = async (e) => {
    e.preventDefault();
    const res = await fetch('modulos/seguranca/gestao_contas/gestao_contas_logica.php', {
        method: 'POST',
        body: new FormData(e.target)
    });
    const json = await res.json();
    if (json.success) {
        $('#mPij').modal('hide');
        alert('Configuração sincronizada com sucesso.');
        if (typeof loadPage === 'function') {
            loadPage('modulos/seguranca/gestao_contas/gestao_contas_view.php', 'Gestão de Contas', 'Segurança');
        } else {
            window.location.href = 'modulos/seguranca/gestao_contas/gestao_contas_view.php';
        }
    } else {
        alert('Erro ao gravar: ' + (json.message || 'Falha desconhecida.'));
    }
};

window.delU = async (id) => {
    if (!confirm('CUIDADO: Deseja apagar definitivamente este usuário?')) {
        return;
    }
    const fd = new FormData();
    fd.append('action', 'delete_user');
    fd.append('id', id);
    const res = await fetch('modulos/seguranca/gestao_contas/gestao_contas_logica.php', {
        method: 'POST',
        body: fd
    });
    const j = await res.json();
    if (j.success) {
        if (typeof loadPage === 'function') {
            loadPage('modulos/seguranca/gestao_contas/gestao_contas_view.php', 'Gestão de Contas', 'Segurança');
        } else {
            window.location.href = 'modulos/seguranca/gestao_contas/gestao_contas_view.php';
        }
    } else {
        alert(j.message);
    }
};