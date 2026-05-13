if(typeof window.reloadContent === 'undefined') { window.reloadContent = (url) => { if(typeof loadPage === 'function') loadPage(url, 'Cadastro de Internos', 'Ferramentas'); else window.location.href = url; }; }

// OFFCANVAS ESTATÍSTICAS
window.abrirOffcanvasStats = (tipo) => {
    // Fechar todos os outros offcanvas de estatísticas
    ['offcanvasTotal', 'offcanvasLGBT', 'offcanvasPIX', 'offcanvasTrabalho'].forEach(id => {
        const canvas = document.getElementById(id);
        if (canvas && canvas.style.visibility !== 'hidden') {
            canvas.style.transform = 'translateX(100%)';
            setTimeout(() => { canvas.style.visibility = 'hidden'; }, 300);
        }
    });

    // Aguardar um pouco para evitar conflitos de animação
    setTimeout(() => {
        const offcanvas = document.getElementById('offcanvas' + (tipo === 'total' ? 'Total' : tipo === 'lgbt' ? 'LGBT' : tipo === 'pix' ? 'PIX' : 'Trabalho'));
        if (!offcanvas) return;

        offcanvas.style.visibility = 'visible';
        offcanvas.style.transform = 'translateX(0)';
    }, 150);

    // Busca dados via AJAX
    fetch(`paginas/ajax_get_internos.php?tipo=${tipo}`)
        .then(r => r.json())
        .then(data => {
            let html = '<table class="table table-sm table-hover" style="color: #fff; background: transparent;"><tbody>';
            data.forEach(i => {
                const nome = i.nome_social ? `<strong style="color: white; background: #d1a7d6; padding: 2px 6px; border-radius: 3px; font-size: 0.95rem;">${i.nome_social}</strong><br/><small style="color: #b0b0b0;">${i.nome}</small>` : `<span style="color: #e0e0e0;">${i.nome}</span>`;
                const local = `<span style="color: #888;">${i.galeria}${i.bloco}-${i.res}</span>`;
                let setor = '';
                if(tipo === 'salario' && i.regalia_setor) setor = ` | <small style="color: #adb5bd;">${i.regalia_setor}</small>`;
                html += `<tr style="border-color: #495057;"><td style="vertical-align: middle; color: #ffc107;"><strong>${i.ipen}</strong></td><td style="vertical-align: middle; font-size: 0.9rem;">${nome}</td><td style="vertical-align: middle; text-align: right; font-size: 0.85rem;">${local}${setor}</td></tr>`;
            });
            html += '</tbody></table>';

            const listId = tipo === 'total' ? 'totalList' : tipo === 'lgbt' ? 'lgbtList' : tipo === 'pix' ? 'pixList' : 'trabalhoList';
            document.getElementById(listId).innerHTML = html;
        })
        .catch(err => console.error(err));
};

window.fecharOffcanvasStats = (id) => {
    const offcanvas = document.getElementById(id);
    if (offcanvas) {
        offcanvas.style.transform = 'translateX(100%)';
        setTimeout(() => { offcanvas.style.visibility = 'hidden'; }, 300);
    }
};

// SYSTEM DE ORDENAÇÃO COM LOCALSTORAGE
window.currentSortBy = 'nome';
window.currentSortOrder = 'ASC';

window.saveSortState = () => {
    localStorage.setItem('cadastro_sort_by', window.currentSortBy);
    localStorage.setItem('cadastro_sort_order', window.currentSortOrder);
};

window.loadSortState = () => {
    const saved_by = localStorage.getItem('cadastro_sort_by');
    const saved_order = localStorage.getItem('cadastro_sort_order');
    if (saved_by) window.currentSortBy = saved_by;
    if (saved_order) window.currentSortOrder = saved_order;
};

window.loadSortState();

// Detecta mudança de ordenação quando a página recarrega
window.updateSortVariables = () => {
    const sortLinks = document.querySelectorAll('thead a');
    sortLinks.forEach(link => {
        const icon = link.querySelector('i');
        if (icon) {
            if (icon.classList.contains('fa-sort-up')) {
                const href = link.getAttribute('href');
                if (href) {
                    const urlParams = new URLSearchParams(href.split('?')[1]);
                    window.currentSortBy = urlParams.get('sort_by') || 'nome';
                    window.currentSortOrder = 'ASC';
                }
            } else if (icon.classList.contains('fa-sort-down')) {
                const href = link.getAttribute('href');
                if (href) {
                    const urlParams = new URLSearchParams(href.split('?')[1]);
                    window.currentSortBy = urlParams.get('sort_by') || 'nome';
                    window.currentSortOrder = 'DESC';
                }
            }
        }
    });
    window.saveSortState();
    console.log('Ordenação carregada:', window.currentSortBy, window.currentSortOrder);
};

window.updateSortVariables();

window.imprimirRelatorio = (mode) => {
    // Restaura ordenação do localStorage
    window.loadSortState();

    let url = 'modulos/geral/cadastro_internos/cadastro_internos_view.php?print=1';
    if(mode === 'geral') {
        url += '&mode=geral&' + $(document.getElementById('formCad')).serialize();
        // Adiciona ordenação
        url += '&sort_by=' + (window.currentSortBy || 'nome');
        url += '&sort_order=' + (window.currentSortOrder || 'ASC');
    } else {
        url += '&mode=' + mode;
    }
    window.open(url, '_blank');
};

window.imprimirHistRange = () => {
    let d1 = document.getElementById('hist_dt_ini').value;
    let d2 = document.getElementById('hist_dt_fim').value;
    if(!d1 || !d2) return alert('Selecione as datas.');
    window.open(`modulos/geral/cadastro_internos/cadastro_internos_view.php?print=1&mode=hist_range&h_ini=${d1}&h_fim=${d2}`, '_blank');
    $('#modalDataHist').modal('hide');
};

// OFFCANVAS DE EDIÇÃO - CADASTRO DE INTERNOS
window.originalDataCadastro = {};

function checkFormChanges() {
    const btn = document.getElementById('btnSalvar');
    if (!btn) return;
    let hasChanges = false;
    document.querySelectorAll('.input-monitor').forEach(input => {
        let fieldName = input.getAttribute('name');
        let newVal = input.value;
        let oldVal = window.originalDataCadastro[fieldName] || '';
        if(fieldName === 'lgbt' && (oldVal === null || oldVal === '')) oldVal = 'N';
        if(fieldName === 'regalia' && (oldVal === null || oldVal === '')) oldVal = 'N';
        if(fieldName === 'cor_roupa' && (oldVal === null || oldVal === '')) oldVal = 'Laranja';
        if(newVal == '' && (oldVal == null || oldVal == '')) return;
        if(newVal != oldVal) hasChanges = true;
    });
    btn.disabled = !hasChanges;
}

window.abrirEdicao = (d) => {
    const offcanvas = document.getElementById('offcanvasCadastro');
    if (!offcanvas) return console.error('Offcanvas não encontrado!');

    window.originalDataCadastro = d;
    document.getElementById('edit_ipen').value = d.ipen;
    document.getElementById('disp_ipen').innerText = d.ipen;
    document.getElementById('disp_nome').innerText = d.nome;
    document.getElementById('disp_situacao').innerText = d.situacao;
    document.getElementById('disp_gal').innerText = d.galeria || '-';
    document.getElementById('disp_blo').innerText = d.bloco || '-';
    document.getElementById('disp_cela').innerText = d.res || '-';

    const map = {
        'edit_social':'nome_social', 'edit_lgbt':'lgbt', 'edit_apelido':'apelido',
        'edit_pagamento':'forma_pagamento', 'edit_regalia':'regalia', 'edit_cor':'cor_roupa',
        'edit_setor':'regalia_setor', 'edit_kit':'kit', 'edit_regkit':'regalia_kit', 'edit_tam':'tamanho_kit'
    };
    for(let id in map) {
        let el = document.getElementById(id);
        if (!el) continue;
        let val = d[map[id]];
        if(id === 'edit_regalia' && !val) val = 'N';
        if(id === 'edit_lgbt' && !val) val = 'N';
        if(id === 'edit_cor' && !val) val = 'Laranja';
        if(id === 'edit_tam' && !val) val = 'G';
        el.value = val || '';
    }

    document.getElementById('btnSalvar').disabled = true;
    document.querySelectorAll('.input-monitor').forEach(el => {
        el.removeEventListener('input', checkFormChanges);
        el.addEventListener('input', checkFormChanges);
    });
    offcanvas.style.transform = 'translateX(0)';
};

window.fecharEdicaoCadastro = () => {
    const offcanvas = document.getElementById('offcanvasCadastro');
    if (offcanvas) offcanvas.style.transform = 'translateX(100%)';
};

window.salvarCadastro = async (e) => {
    e.preventDefault();
    const f = e.target;
    let changes = [];
    const fields = [
        { id: 'edit_social', db: 'nome_social', label: 'Nome Social' },
        { id: 'edit_lgbt', db: 'lgbt', label: 'É LGBT' },
        { id: 'edit_apelido', db: 'apelido', label: 'Apelido' },
        { id: 'edit_pagamento', db: 'forma_pagamento', label: 'Pagamento' },
        { id: 'edit_regalia', db: 'regalia', label: 'Regalia' },
        { id: 'edit_cor', db: 'cor_roupa', label: 'Cor' },
        { id: 'edit_setor', db: 'regalia_setor', label: 'Setor' },
        { id: 'edit_kit', db: 'kit', label: 'Kit' },
        { id: 'edit_regkit', db: 'regalia_kit', label: 'Kit Reg' },
        { id: 'edit_tam', db: 'tamanho_kit', label: 'Tam' }
    ];

    fields.forEach(field => {
        let newVal = document.getElementById(field.id).value;
        let oldVal = window.originalDataCadastro[field.db] || '';
        if(field.db === 'lgbt' && !oldVal) oldVal = 'N';
        if(newVal == '' && oldVal == null) newVal = '';
        if(oldVal == null) oldVal = '';
        if(newVal != oldVal) changes.push(`- ${field.label}: '${oldVal}' > '${newVal}'`);
    });

    if(!confirm("CONFIRMA AS ALTERAÇÕES?\n\n" + changes.join("\n"))) return;

    const btn = document.getElementById('btnSalvar');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SALVANDO...';
    btn.disabled = true;

    try {
        const res = await fetch('modulos/geral/cadastro_internos/cadastro_internos_logica.php', { method: 'POST', body: new FormData(f) });
        const json = await res.json();
        if(json.success) {
            window.fecharEdicaoCadastro();
            alert('Dados atualizados!');
            // Recarrega a página de Cadastro de Internos
            if(typeof loadPage === 'function') {
                loadPage('modulos/geral/cadastro_internos/cadastro_internos_view.php');
            } else {
                window.location.href = 'modulos/geral/cadastro_internos/cadastro_internos_view.php';
            }
        } else {
            alert('Erro: ' + json.error);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-2"></i> SALVAR ALTERAÇÕES';
        }
    } catch(err) {
        alert('Erro de conexão.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i> SALVAR ALTERAÇÕES';
    }
};

// DATEPICKER - Inicializa campos de data com flatpickr
document.addEventListener('DOMContentLoaded', function() {
    if (typeof flatpickr === 'function') {
        flatpickr('input[name="data_ini"]', {
            dateFormat: 'Y-m-d',
            locale: 'pt',
            defaultDate: ''
        });
        flatpickr('input[name="data_fim"]', {
            dateFormat: 'Y-m-d',
            locale: 'pt',
            defaultDate: ''
        });
        flatpickr('#hist_dt_ini', {
            dateFormat: 'Y-m-d',
            locale: 'pt',
            defaultDate: '<?= date("Y-m-01") ?>'
        });
        flatpickr('#hist_dt_fim', {
            dateFormat: 'Y-m-d',
            locale: 'pt',
            defaultDate: '<?= date("Y-m-d") ?>'
        });
    }
});
