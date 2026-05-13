(function () {
    // Previne execução múltipla do script no SPA
    if (window.censuraRoupariaLoaded) return;
    window.censuraRoupariaLoaded = true;

    // Variáveis globais para rastrear ordenação atual (porque loadPage é AJAX)
    window.bootstrapRouparia = (typeof window.CENSURA_ROUPARIA_BOOTSTRAP !== 'undefined') ? window.CENSURA_ROUPARIA_BOOTSTRAP : {};
    window.currentSortBy = window.bootstrapRouparia.sortBy || 'nome';
    window.currentSortOrder = window.bootstrapRouparia.sortOrder || 'ASC';

    // Salva a ordenação em localStorage
    window.saveSortState = () => {
        localStorage.setItem('rouparia_sort_by', window.currentSortBy);
        localStorage.setItem('rouparia_sort_order', window.currentSortOrder);
    };

    // Restaura a ordenação do localStorage
    window.loadSortState = () => {
        const saved_by = localStorage.getItem('rouparia_sort_by');
        const saved_order = localStorage.getItem('rouparia_sort_order');
        if (saved_by) window.currentSortBy = saved_by;
        if (saved_order) window.currentSortOrder = saved_order;
    };

    // Carrega ao inicializar página
    window.loadSortState();

    // Inicializa arrays para regalias
    window.regaliasOcupadas = window.bootstrapRouparia.regaliasOcupadas || [];
    window.regaliasDisponiveis = Array.from({ length: 35 }, (_, i) => i + 1).filter(n => !window.regaliasOcupadas.includes(n));

    // Função unificada para carregar conteúdo (resolve o problema do Painel)
    window.reloadContent = (url) => {
        // Se existir a função loadPage do AdminLTE/Sistema, usa ela
        if (typeof loadPage === 'function') {
            loadPage(url);
        } else {
            // Fallback para reload simples se não houver sistema de rotas
            window.location.href = url;
        }
    };

    // Função para gerar URL de relatório com filtros atuais
    window.getRelatorioUrl = function (mode, sortBy) {
        const form = document.getElementById('formFiltro');
        let url = '/modulos/censura/rouparia/gestao_kits/view.php?execute_print=1&mode=' + mode;

        // Adiciona parâmetros do formulário (se existir)
        if (form) {
            const formData = new FormData(form);
            for (let [key, value] of formData.entries()) {
                if (value) {
                    url += '&' + key + '=' + encodeURIComponent(value);
                }
            }
        }

        // Adiciona ordenação se especificada (sobrescreve a do formulário)
        if (sortBy) {
            // Remove sort_by existente se houver
            url = url.replace(/[&?]sort_by=[^&]*/, '');
            url += '&sort_by=' + sortBy;
        }

        return url;
    };

    // Função para atualizar campos ocultos de ordenação
    window.updateSortFields = function (sortBy, sortOrder) {
        const form = document.getElementById('formFiltro');
        if (form) {
            const sortByField = form.querySelector('input[name="sort_by"]');
            const sortOrderField = form.querySelector('input[name="sort_order"]');

            if (sortByField) sortByField.value = sortBy;
            if (sortOrderField) sortOrderField.value = sortOrder;

            // Atualiza variáveis globais
            window.currentSortBy = sortBy;
            window.currentSortOrder = sortOrder;

            // Salva no localStorage
            window.saveSortState();
        }
    };

    // Intercepta cliques nos links de ordenação para atualizar os campos ocultos
    document.addEventListener('click', function (e) {
        const target = e.target.closest('a[href*="sort_by="]');
        if (target && target.href.includes('view.php')) {
            e.preventDefault();
            const url = new URL(target.href);
            const sortBy = url.searchParams.get('sort_by');
            const sortOrder = url.searchParams.get('sort_order');

            // Atualiza campos ocultos
            window.updateSortFields(sortBy, sortOrder);

            // Carrega a página com novos parâmetros
            window.reloadContent(target.href);
        }
    });

    window.exibOff = (id, show = true) => {
        const el = document.getElementById(id);
        if (show) {
            document.querySelectorAll('.offcanvas-custom').forEach(oc => {
                if (oc.id !== id) {
                    oc.style.transform = 'translateX(100%)';
                    setTimeout(() => { oc.style.visibility = 'hidden'; }, 300);
                }
            });
            el.style.visibility = 'visible';
            el.style.transform = 'translateX(0)';
        } else {
            el.style.transform = 'translateX(100%)';
            setTimeout(() => {
                el.style.visibility = 'hidden';

                // Se for o offcanvas de kits disponíveis, limpa a pesquisa
                if (id === 'off_liv') {
                    document.getElementById('buscaKitInput').value = '';
                    // Remove todos os highlights
                    document.querySelectorAll('.kit-encontrado').forEach(el => el.classList.remove('kit-encontrado'));
                    document.querySelectorAll('.blink-anim').forEach(el => el.classList.remove('blink-anim'));
                }
                // Se for o offcanvas de regalias, limpa a pesquisa
                if (id === 'off_regalias_fechado') {
                    document.getElementById('buscaRegaliaInput').value = '';
                    // Remove todos os highlights
                    document.querySelectorAll('.regalia-encontrado').forEach(el => el.classList.remove('regalia-encontrado'));
                }
                // Se for o offcanvas de regalias semiaberto, limpa a pesquisa
                if (id === 'off_regalias_semiaberto') {
                    document.getElementById('buscaRegaliaSemiInput').value = '';
                    // Remove todos os highlights
                    document.querySelectorAll('.regalia-semi-encontrado').forEach(el => el.classList.remove('regalia-semi-encontrado'));
                }
                // Se for o offcanvas de histórico de kit, limpa a pesquisa
                if (id === 'off_historico_kit') {
                    document.getElementById('buscaHistoricoKitInput').value = '';
                    document.getElementById('historicoKitContent').innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-search fa-3x mb-3"></i><p>Digite o número do kit e clique em buscar para ver o histórico.</p></div>';
                }
            }, 300);
        }
    };
    window.buscarKit = () => {
        const val = document.getElementById('buscaKitInput').value;
        if (!val) return;

        // Remove highlights anteriores
        document.querySelectorAll('.kit-encontrado').forEach(el => el.classList.remove('kit-encontrado'));
        document.querySelectorAll('.blink-anim').forEach(el => el.classList.remove('blink-anim'));

        // Primeiro, busca nos kits disponíveis (badges)
        const badge = document.getElementById('badge-kit-' + val);
        if (badge) {
            badge.classList.add('kit-encontrado');
            badge.scrollIntoView({ behavior: 'smooth', block: 'center' });
            badge.classList.remove('blink-anim');
            void badge.offsetWidth;
            badge.classList.add('blink-anim');
            return;
        }

        // Se não encontrou nos disponíveis, busca na tabela de quarentena
        const tbody = document.querySelector('#off_liv table tbody');
        if (tbody) {
            const rows = tbody.querySelectorAll('tr');
            for (let row of rows) {
                const kitCell = row.querySelector('td:first-child');
                if (kitCell && kitCell.textContent.trim() == val) {
                    row.classList.add('kit-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    row.classList.remove('blink-anim');
                    void row.offsetWidth;
                    row.classList.add('blink-anim');
                    return;
                }
            }
        }

        // Se não encontrou em nenhum lugar
        alert('Número ' + val + ' não localizado nos disponíveis nem em quarentena.');
    };

    window.buscarRegalia = () => {
        const val = document.getElementById('buscaRegaliaInput').value.trim();
        if (!val) return;

        // Remove highlights anteriores
        document.querySelectorAll('.regalia-encontrado').forEach(el => el.classList.remove('regalia-encontrado'));

        // Busca em toda a tabela (IPEN, nome, kit padrão, kit regalia, setor)
        const tbody = document.querySelector('#off_regalias_fechado table tbody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr[data-reg-ipen]');
        let encontrado = false;

        for (let row of rows) {
            const cells = row.querySelectorAll('td');
            const match = Array.from(cells).some(cell => {
                const text = cell.textContent.toLowerCase();
                const search = val.toLowerCase();

                // Busca por IPEN
                if (cell.querySelector('.text-center.font-weight-bold') && text === val) {
                    encontrado = true;
                    row.classList.add('regalia-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por nome ou nome social
                if (cell.querySelector('small') && text.includes(search)) {
                    encontrado = true;
                    row.classList.add('regalia-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por kit padrão
                const kitCell = row.querySelector('.kit-reg-v');
                if (kitCell && kitCell.value && kitCell.value.toString() === val) {
                    encontrado = true;
                    row.classList.add('regalia-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por kit regalia
                const regaliaCell = row.querySelector('.regalia-kit-v');
                if (regaliaCell && regaliaCell.value && regaliaCell.value.toString() === val) {
                    encontrado = true;
                    row.classList.add('regalia-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por setor
                const setorCell = row.querySelector('.setor-v');
                if (setorCell && setorCell.value.toLowerCase().includes(search)) {
                    encontrado = true;
                    row.classList.add('regalia-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }
            });

            if (encontrado) break;
        }

        if (!encontrado) {
            alert(`Nenhum resultado encontrado para "${val}".`);
        }
    };

    window.cadastrarKit = async (e) => {
        e.preventDefault();
        const f = e.target;
        const btn = f.querySelector('button[type="submit"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> CADASTRANDO...';
        btn.disabled = true;

        const fd = new FormData(f);
        fd.append('db_action', 'cadastrar_kit_pronto');

        try {
            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                alert('Kit cadastrado com sucesso!');
                // Update the table
                if (json.rows) {
                    window.renderKitsHistoryTable(json.rows);
                }
                // Reset the form
                f.reset();
                document.querySelector('input[name="kit_id"]').value = '';
                document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> CADASTRAR KIT PRONTO';
            } else {
                alert('Erro: ' + json.error);
            }
        } catch (err) {
            alert('Erro de conexão.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> CADASTRAR KIT PRONTO';
        }
    };

    // Executa ao carregar a página

    // AJAX Salvar Modal
    window.saveOff = async e => {
        e.preventDefault();
        const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: new FormData(e.target) });
        const json = await res.json();
        if (json.success) {
            window.exibOff('painelEdit', false);
            alert('Atualizado com sucesso!');
            // Recarrega apenas a URL atual, sem resetar para Painel
            const form = document.getElementById('formFiltro');
            const qs = $(form).serialize();
            window.reloadContent('/modulos/censura/rouparia/gestao_kits/view.php?' + qs);
        } else { alert('Erro: ' + json.error); }
    };

    window.checkChanges = ipen => { const tr = document.querySelector(`tr[data-ipen="${ipen}"]`); const btn = tr.querySelector('.btn-save'); let changed = false; tr.querySelectorAll('.kit-v, .kr-v, .tam-v').forEach(i => { if (i.value != i.dataset.old) changed = true; }); if (changed) { btn.classList.remove('btn-success'); btn.classList.add('btn-warning', 'btn-pending'); btn.innerHTML = '<i class="fas fa-exclamation"></i> SALVAR'; } else { btn.classList.add('btn-success'); btn.classList.remove('btn-warning', 'btn-pending'); btn.innerHTML = '<i class="fas fa-save"></i> SALVAR'; } };

    // AJAX Salvar Inline
    window.confirmarSalvar = async ipen => {
        const tr = document.querySelector(`tr[data-ipen="${ipen}"]`); const fields = { kit: tr.querySelector('.kit-v'), kr: tr.querySelector('.kr-v'), tam: tr.querySelector('.tam-v') }; let logs = []; if (fields.kit.value != fields.kit.dataset.old) logs.push(`KIT: ${fields.kit.dataset.old} ➔ ${fields.kit.value}`); if (fields.kr.value != fields.kr.dataset.old) logs.push(`KIT REGALIA: ${fields.kr.dataset.old} ➔ ${fields.kr.value}`); if (fields.tam.value != fields.tam.dataset.old) logs.push(`TAMANHO: ${fields.tam.dataset.old} ➔ ${fields.tam.value}`); if (!logs.length) return alert("Nenhuma alteração.");
        if (confirm(`Confirmar alterações?\n\n${logs.join('\n')}`)) {
            console.log('🚀 Iniciando salvamento para IPEN:', ipen, 'Dados:', { kit: fields.kit.value, reg_k: fields.kr.value, tam: fields.tam.value });
            const fd = new FormData(); fd.append('db_action', 'update_inline'); fd.append('ipen', ipen); fd.append('kit', fields.kit.value); fd.append('reg_k', fields.kr.value); fd.append('tam', fields.tam.value);
            try {
                const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: fd });
                console.log('📡 Resposta HTTP:', res.status, res.statusText);
                const json = await res.json();
                console.log('📄 JSON recebido:', json);
                if (json.success) {
                    [fields.kit, fields.kr, fields.tam].forEach(i => i.dataset.old = i.value); window.checkChanges(ipen);
                    if (fields.tam.value != 'G') fields.tam.classList.add('tam-especial'); else fields.tam.classList.remove('tam-especial');
                    console.log('✅ Salvamento bem-sucedido');
                } else {
                    alert('Erro ao salvar: ' + (json.error || 'Erro desconhecido'));
                    console.error('❌ Erro ao salvar:', json);
                }
            } catch (error) {
                console.error('💥 Erro de rede/conexão:', error);
                alert('Erro de conexão: ' + error.message);
            }
        } else { [fields.kit, fields.kr, fields.tam].forEach(i => i.value = i.dataset.old); window.checkChanges(ipen); }
    };

    // Funções para edição de regalias
    window.checkRegaliaChanges = ipen => {
        const tr = document.querySelector(`tr[data-reg-ipen="${ipen}"]`);
        const btn = tr.querySelector('.btn-save-regalia');
        let changed = false;
        tr.querySelectorAll('.kit-reg-v, .regalia-kit-v, .setor-v').forEach(i => {
            if (i.value != i.dataset.old) changed = true;
        });
        if (changed) {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning', 'btn-pending');
            btn.innerHTML = '<i class="fas fa-exclamation"></i> SALVAR';
        } else {
            btn.classList.add('btn-success');
            btn.classList.remove('btn-warning', 'btn-pending');
            btn.innerHTML = '<i class="fas fa-save"></i> SALVAR';
        }
    };

    window.confirmarSalvarRegalia = async ipen => {
        const tr = document.querySelector(`tr[data-reg-ipen="${ipen}"]`);
        const fields = {
            kit: tr.querySelector('.kit-reg-v'),
            regalia_kit: tr.querySelector('.regalia-kit-v'),
            regalia_setor: tr.querySelector('.setor-v')
        };

        let logs = [];
        if (fields.kit.value != fields.kit.dataset.old) logs.push(`KIT PADRÃO: ${fields.kit.dataset.old} ➔ ${fields.kit.value}`);
        if (fields.regalia_kit.value != fields.regalia_kit.dataset.old) logs.push(`KIT REGALIA: ${fields.regalia_kit.dataset.old} ➔ ${fields.regalia_kit.value}`);
        if (fields.regalia_setor.value != fields.regalia_setor.dataset.old) logs.push(`SETOR: ${fields.regalia_setor.dataset.old} ➔ ${fields.regalia_setor.value}`);

        if (!logs.length) return alert("Nenhuma alteração.");

        if (confirm(`Confirmar alterações?\n\n${logs.join('\n')}`)) {
            const fd = new FormData();
            fd.append('db_action', 'update_regalia_inline');
            fd.append('ipen', ipen);
            fd.append('kit', fields.kit.value);
            fd.append('regalia_kit', fields.regalia_kit.value);
            fd.append('regalia_setor', fields.regalia_setor.value);

            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: fd });
            if ((await res.json()).success) {
                [fields.kit, fields.regalia_kit, fields.regalia_setor].forEach(i => i.dataset.old = i.value);
                window.checkRegaliaChanges(ipen);

                // Atualiza os contadores se necessário
                const kitRegaliaValue = fields.regalia_kit.value;
                if (kitRegaliaValue > 0 && kitRegaliaValue <= 35) {
                    // Verifica se este número já estava na lista de ocupadas
                    const oldKitValue = fields.regalia_kit.dataset.old;
                    if (oldKitValue > 0 && oldKitValue <= 35) {
                        // Remove da lista de ocupadas se estava lá antes
                        const index = window.regaliasOcupadas.indexOf(parseInt(oldKitValue));
                        if (index > -1) {
                            window.regaliasOcupadas.splice(index, 1);
                        }
                    }

                    // Adiciona à lista de ocupadas se não estava lá antes
                    if (!window.regaliasOcupadas.includes(parseInt(kitRegaliaValue))) {
                        window.regaliasOcupadas.push(parseInt(kitRegaliaValue));
                    }

                    // Recalcula disponíveis
                    window.regaliasDisponiveis = Array.from({ length: 35 }, (_, i) => i + 1).filter(n => !window.regaliasOcupadas.includes(n));

                    // Atualiza o grid de disponíveis
                    const gridContainer = document.querySelector('#off_regalias_fechado .grid-kits');
                    if (gridContainer) {
                        gridContainer.innerHTML = '';
                        window.regaliasDisponiveis.forEach(num => {
                            const div = document.createElement('div');
                            div.className = 'kit-badge bg-light text-success border-success';
                            div.textContent = num;
                            gridContainer.appendChild(div);
                        });
                    }

                    // Atualiza os cards de contagem
                    const cardDisponiveis = document.querySelector('#off_regalias_fechado .text-success');
                    const cardOcupadas = document.querySelector('#off_regalias_fechado .text-warning');
                    if (cardDisponiveis) cardDisponiveis.textContent = window.regaliasDisponiveis.length;
                } else {
                    alert('Erro ao salvar');
                }
            } else {
                alert('Erro ao salvar');
            }
        } else {
            [fields.kit, fields.regalia_kit, fields.regalia_setor].forEach(i => i.value = i.dataset.old);
            window.checkRegaliaSemiChanges(ipen);
        }
    };

    // Funções para edição de regalias semiaberto
    window.checkRegaliaSemiChanges = ipen => {
        const tr = document.querySelector(`tr[data-reg-semi-ipen="${ipen}"]`);
        const btn = tr.querySelector('.btn-save-regalia-semi');
        let changed = false;
        tr.querySelectorAll('.kit-reg-semi-v, .setor-semi-v').forEach(i => {
            if (i.value != i.dataset.old) changed = true;
        });
        if (changed) {
            btn.classList.remove('btn-success');
            btn.classList.add('btn-warning', 'btn-pending');
            btn.innerHTML = '<i class="fas fa-exclamation"></i> SALVAR';
        } else {
            btn.classList.add('btn-success');
            btn.classList.remove('btn-warning', 'btn-pending');
            btn.innerHTML = '<i class="fas fa-save"></i> SALVAR';
        }
    };

    window.confirmarSalvarRegaliaSemi = async ipen => {
        const tr = document.querySelector(`tr[data-reg-semi-ipen="${ipen}"]`);
        const fields = {
            kit: tr.querySelector('.kit-reg-semi-v'),
            regalia_setor: tr.querySelector('.setor-semi-v')
        };

        let logs = [];
        if (fields.kit.value != fields.kit.dataset.old) logs.push(`KIT PADRÃO: ${fields.kit.dataset.old} ➔ ${fields.kit.value}`);
        if (fields.regalia_setor.value != fields.regalia_setor.dataset.old) logs.push(`SETOR: ${fields.regalia_setor.dataset.old} ➔ ${fields.regalia_setor.value}`);

        if (!logs.length) return alert("Nenhuma alteração.");

        if (confirm(`Confirmar alterações?\n\n${logs.join('\n')}`)) {
            const fd = new FormData();
            fd.append('db_action', 'update_regalia_inline');
            fd.append('ipen', ipen);
            fd.append('kit', fields.kit.value);
            fd.append('regalia_setor', fields.regalia_setor.value);

            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: fd });
            if ((await res.json()).success) {
                [fields.kit, fields.regalia_setor].forEach(i => i.dataset.old = i.value);
                window.checkRegaliaSemiChanges(ipen);
                alert('Dados atualizados com sucesso!');
            } else {
                alert('Erro ao salvar');
            }
        } else {
            [fields.kit, fields.regalia_setor].forEach(i => i.value = i.dataset.old);
            window.checkRegaliaSemiChanges(ipen);
        }
    };

    window.buscarRegaliaSemi = () => {
        const val = document.getElementById('buscaRegaliaSemiInput').value.trim();
        if (!val) return;

        // Remove highlights anteriores
        document.querySelectorAll('.regalia-semi-encontrado').forEach(el => el.classList.remove('regalia-semi-encontrado'));

        // Busca em toda a tabela (IPEN, nome, kit padrão, kit regalia, setor)
        const tbody = document.querySelector('#off_regalias_semiaberto table tbody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr[data-reg-semi-ipen]');
        let encontrado = false;

        for (let row of rows) {
            const cells = row.querySelectorAll('td');
            const match = Array.from(cells).some(cell => {
                const text = cell.textContent.toLowerCase();
                const search = val.toLowerCase();

                // Busca por IPEN
                if (cell.querySelector('.text-center.font-weight-bold') && text === val) {
                    encontrado = true;
                    row.classList.add('regalia-semi-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por nome ou nome social
                if (cell.querySelector('small') && text.includes(search)) {
                    encontrado = true;
                    row.classList.add('regalia-semi-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por kit padrão
                const kitCell = row.querySelector('.kit-reg-semi-v');
                if (kitCell && kitCell.value && kitCell.value.toString() === val) {
                    encontrado = true;
                    row.classList.add('regalia-semi-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por kit regalia
                const regaliaCell = row.querySelector('.regalia-kit-semi-v');
                if (regaliaCell && regaliaCell.value && regaliaCell.value.toString() === val) {
                    encontrado = true;
                    row.classList.add('regalia-semi-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }

                // Busca por setor
                const setorCell = row.querySelector('.setor-semi-v');
                if (setorCell && setorCell.value.toLowerCase().includes(search)) {
                    encontrado = true;
                    row.classList.add('regalia-semi-encontrado');
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return true;
                }
            });

            if (encontrado) break;
        }

        if (!encontrado) {
            alert(`Nenhum resultado encontrado para "${val}".`);
        }
    };

    // OFFCANVAS DE EDIÇÃO - ROUPARIA
    window.originalDataCadastro = {};

    function checkFormChanges() {
        const btn = document.getElementById('btnSalvar');
        if (!btn) return;
        let hasChanges = false;
        document.querySelectorAll('.input-monitor').forEach(input => {
            let fieldName = input.getAttribute('name');
            let newVal = input.value;
            let oldVal = window.originalDataCadastro[fieldName] || '';
            if (fieldName === 'lgbt' && (oldVal === null || oldVal === '')) oldVal = 'N';
            if (fieldName === 'regalia' && (oldVal === null || oldVal === '')) oldVal = 'N';
            if (fieldName === 'cor_roupa' && (oldVal === null || oldVal === '')) oldVal = 'Laranja';
            if (newVal == '' && (oldVal == null || oldVal == '')) return;
            if (newVal != oldVal) hasChanges = true;
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
            'edit_social': 'nome_social', 'edit_lgbt': 'lgbt', 'edit_apelido': 'apelido',
            'edit_pagamento': 'forma_pagamento', 'edit_regalia': 'regalia', 'edit_cor': 'cor_roupa',
            'edit_setor': 'regalia_setor', 'edit_kit': 'kit', 'edit_regkit': 'regalia_kit', 'edit_tam': 'tamanho_kit'
        };
        for (let id in map) {
            let el = document.getElementById(id);
            if (!el) continue;
            let val = d[map[id]];
            if (id === 'edit_regalia' && !val) val = 'N';
            if (id === 'edit_lgbt' && !val) val = 'N';
            if (id === 'edit_cor' && !val) val = 'Laranja';
            if (id === 'edit_tam' && !val) val = 'G';
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
            if (field.db === 'lgbt' && !oldVal) oldVal = 'N';
            if (newVal == '' && oldVal == null) newVal = '';
            if (oldVal == null) oldVal = '';
            if (newVal != oldVal) changes.push(`- ${field.label}: '${oldVal}' > '${newVal}'`);
        });

        if (!confirm("CONFIRMA AS ALTERAÇÕES?\n\n" + changes.join("\n"))) return;

        const btn = document.getElementById('btnSalvar');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SALVANDO...';
        btn.disabled = true;

        try {
            const res = await fetch('paginas/ajax_salvar_cadastro.php', { method: 'POST', body: new FormData(f) });
            const json = await res.json();
            if (json.success) {
                window.fecharEdicaoCadastro();
                alert('Dados atualizados!');
                // Recarrega a página de Rouparia
                if (typeof loadPage === 'function') {
                    loadPage('/modulos/censura/rouparia/gestao_kits/view.php');
                } else {
                    window.location.href = '/modulos/censura/rouparia/gestao_kits/view.php';
                }
            } else {
                alert('Erro: ' + json.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-save mr-2"></i> SALVAR ALTERAÇÕES';
            }
        } catch (err) {
            alert('Erro de conexão.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-2"></i> SALVAR ALTERAÇÕES';
        }
    };

    window.parent.editarKit = async (id) => {
        const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php?acao=get_kit&id=' + id);
        const data = await res.json();
        document.getElementById('kit_numero').value = data.kit_numero;
        document.getElementById('cor').value = data.cor;
        document.getElementById('info_adicional').value = data.info_adicional;
        document.querySelector('input[name="kit_id"]').value = id;
        document.querySelector('button[type="submit"]').innerHTML = '<i class="fas fa-save"></i> ATUALIZAR KIT PRONTO';
        document.getElementById('formCadastrarKit').scrollIntoView();
    };

    window.parent.excluirKit = async (id) => {
        if (confirm('Tem certeza que deseja excluir este kit?')) {
            const fd = new FormData();
            fd.append('db_action', 'excluir_kit_pronto');
            fd.append('id', id);
            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                alert('Kit excluído!');
                window.renderKitsHistoryTable(json.rows || []);
            } else {
                alert('Erro: ' + json.error);
            }
        }
    };


    window.editarKit = window.parent.editarKit;
    window.excluirKit = window.parent.excluirKit;

    window.buscarHistoricoKit = () => {
        const kit = document.getElementById('buscaHistoricoKitInput').value;
        if (!kit) return alert('Digite o número do kit');

        // Show loading
        document.getElementById('historicoKitContent').innerHTML = '<div class="text-center mt-5"><i class="fas fa-spinner fa-spin fa-3x"></i><p>Buscando...</p></div>';

        $.post('/modulos/censura/rouparia/gestao_kits/view.php', { db_action: 'buscar_historico_kit', kit_numero: kit }, function (data) {
            window.renderHistoricoKit(data);
        }, 'json').fail(function () {
            alert('Erro ao buscar histórico');
            document.getElementById('historicoKitContent').innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>Erro ao buscar dados.</p></div>';
        });
    };

    window.buscarItensPerdidosRoupaCivil = () => {
        // Limpar campo de busca
        document.getElementById('buscaRoupaCivilInput').value = '';

        // Buscar registros com itens perdidos
        $.post('/modulos/censura/rouparia/gestao_kits/view.php', { db_action: 'buscar_itens_perdidos_roupa_civil' }, function (data) {
            if (data.success && data.rows && data.rows.length > 0) {
                // Exibir mensagem informativa
                const mensagem = `Encontrados ${data.total} registros com itens sem descrição:

${data.rows.map(row => `• IPEN ${row.ipen} - ${row.nome_exib}`).join('\n')}

Esses registros serão exibidos em destaque vermelho para correção.`;

                if (confirm(mensagem + '\n\nDeseja visualizar esses registros agora?')) {
                    // Renderizar lista com itens perdidos
                    window.renderListaRoupaCivilItensPerdidos(data.rows);

                    // Destacar todos os registros com problemas
                    setTimeout(() => {
                        document.querySelectorAll('.item-perdido').forEach(el => {
                            el.classList.add('roupa-civil-perdido');
                            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        });
                    }, 100);
                }
            } else {
                alert('Nenhum registro com itens perdidos foi encontrado!');
            }
        }, 'json').fail(function () {
            alert('Erro ao buscar itens perdidos.');
        });
    };

    window.renderListaRoupaCivilItensPerdidos = (rows) => {
        const container = document.getElementById('roupaCivilList');
        if (!container) return;

        const body = rows.map((row) => `<tr data-roupa-ipen="${row.ipen}" class="${row.is_inativo ? 'bg-warning text-dark' : ''} item-perdido">
            <td class='text-center font-weight-bold'>${row.ipen}</td>
            <td>${row.nome_exib}${row.is_inativo ? ' <span class="badge badge-danger">Inativo</span>' : ''}</td>
            <td class='small'>${row.pecas_formatadas}</td>
            <td class='small'>${row.criado_por}</td>
            <td class='small'>${row.data_fmt}</td>
            <td class='text-center'>
                <button class='btn btn-xs btn-warning mr-1' onclick='editarRoupaCivil(${row.id})' title='Editar - Corrigir item perdido'>
                    <i class='fas fa-exclamation-triangle'></i>
                </button>
                <button class='btn btn-xs btn-danger' onclick='excluirRoupaCivil(${row.id})' title='Excluir'>
                    <i class='fas fa-trash'></i>
                </button>
            </td>
        </tr>`).join('');

        container.innerHTML = `
            <div class="alert alert-warning mb-3">
                <h6><i class="fas fa-exclamation-triangle"></i> ${rows.length} registros com itens perdidos encontrados!</h6>
                <p class="mb-0">Esses registros contêm itens sem descrição (ex: "1x" sem tipo). Clique em editar para corrigir.</p>
            </div>
            <table class="table table-sm table-striped small border">
                <thead class="bg-light">
                    <tr>
                        <th class="text-center">IPEN</th>
                        <th>Nome</th>
                        <th>Peças</th>
                        <th>Cadastrado por</th>
                        <th>Data</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>${body}</tbody>
            </table>
        `;
    };

    window.buscarRoupaCivil = () => {
        const val = document.getElementById('buscaRoupaCivilInput').value.trim();
        if (!val) return;

        // Remove highlights anteriores
        document.querySelectorAll('.roupa-civil-encontrado').forEach(el => el.classList.remove('roupa-civil-encontrado'));

        // Busca na lista de registros
        const tbody = document.querySelector('#roupaCivilList table tbody');
        if (!tbody) return;

        const rows = tbody.querySelectorAll('tr[data-roupa-ipen]');
        let encontrado = false;

        for (let row of rows) {
            const cells = row.querySelectorAll('td');
            const match = Array.from(cells).some((cell, index) => {
                const text = cell.textContent.toLowerCase();
                const search = val.toLowerCase();

                // Busca por IPEN (primeira coluna)
                if (index === 0 && text === val) {
                    encontrado = true;
                    destacarERolarParaRegistro(row);
                    return true;
                }

                // Busca por nome ou nome social (segunda coluna)
                if (index === 1 && text.includes(search)) {
                    encontrado = true;
                    destacarERolarParaRegistro(row);
                    return true;
                }

                return false;
            });

            if (encontrado) break;
        }

        if (!encontrado) {
            alert(`Nenhum resultado encontrado para "${val}".`);
        }
    };

    window.destacarERolarParaRegistro = (row) => {
        // Adicionar classe de destaque
        row.classList.add('roupa-civil-encontrado');

        // Scroll suave para o registro
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });

        // Adicionar animação de blink
        setTimeout(() => {
            row.classList.add('blink-anim');
        }, 100);

        // Remover animação após um tempo
        setTimeout(() => {
            row.classList.remove('blink-anim');
        }, 2000);
    };

    window.cadastrarRoupaCivil = async (e) => {
        e.preventDefault();
        const btn = e.target.querySelector('button[type="submit"]');
        const isEdicao = window.modoEdicaoRoupaCivil === true;

        // Definir texto do botão baseado no modo
        if (isEdicao) {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ATUALIZANDO...';
        } else {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> CADASTRANDO...';
        }
        btn.disabled = true;

        const fd = new FormData(e.target);

        // Definir ação correta baseada no modo
        if (isEdicao) {
            fd.append('db_action', 'editar_roupa_civil');
            fd.append('id', window.editandoIdRoupaCivil);
        } else {
            fd.append('db_action', 'cadastrar_roupa_civil');
        }

        try {
            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', { method: 'POST', body: fd });
            const json = await res.json();
            if (json.success) {
                const mensagem = isEdicao ? 'Roupa civil atualizada com sucesso!' : 'Roupa civil cadastrada com sucesso!';
                alert(mensagem);

                // Resetar formulário
                e.target.reset();

                // Se estava em modo de edição, cancelar o modo
                if (isEdicao) {
                    window.cancelarEdicaoRoupaCivil();
                }

                // Recarregar lista
                window.carregarListaRoupaCivil();
            } else {
                alert('Erro: ' + json.error);
            }
        } catch (err) {
            alert('Erro de conexão.');
        } finally {
            // Resetar botão para o estado correto
            if (isEdicao) {
                btn.innerHTML = '<i class="fas fa-save"></i> ATUALIZAR ROUPA CIVIL';
            } else {
                btn.innerHTML = '<i class="fas fa-save"></i> CADASTRAR ROUPA CIVIL';
            }
            btn.disabled = false;
        }
    };

    window.carregarListaRoupaCivil = () => {
        $.post('/modulos/censura/rouparia/gestao_kits/view.php', { db_action: 'listar_roupa_civil' }, function (data) {
            window.renderListaRoupaCivil(data.rows || []);
        }, 'json').fail(function () {
            document.getElementById('roupaCivilList').innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-exclamation-triangle fa-3x mb-3"></i><p>Erro ao carregar lista.</p></div>';
        });
    };

    window.renderKitsHistoryTable = (rows) => {
        const table = document.getElementById('kitsHistoryTable');
        if (!table) return;
        table.innerHTML = rows.map((kp) => {
            const badgeClass = kp.cor === 'Laranja' ? 'warning' : 'success';
            return `<tr id="row-${kp.id}">
                <td class="font-weight-bold text-primary">${kp.kit_numero}</td>
                <td><span class="badge badge-${badgeClass}">${kp.cor}</span></td>
                <td class="small">${kp.data_cadastro_fmt}</td>
                <td class="small" title="${kp.info_adicional_full}">${kp.info_adicional_short}</td>
                <td><button class="btn btn-sm btn-danger" onclick="excluirKit(${kp.id})">Excluir</button></td>
            </tr>`;
        }).join('');
    };

    window.renderHistoricoKit = (data) => {
        const container = document.getElementById('historicoKitContent');
        if (!container) return;

        const badges = {
            ATRIBUÍDO: 'success',
            LIBERADO: 'danger',
            ALTERADO: 'warning'
        };

        let html = (data.status_alerts || []).map((a) => `<div class="alert alert-${a.level}"><i class="fas ${a.icon} mr-2"></i>${a.message}</div>`).join('');

        if (data.kit_info) {
            html += `<div class="info-box-static mb-3">
                <h6 class="font-weight-bold"><i class="fas fa-box mr-1"></i>INFORMAÇÕES DO CADASTRO DO KIT</h6>
                <div class="row">
                    <div class="col-sm-6"><span class="info-label">Número do Kit</span><span class="info-value">${data.kit_info.kit_numero}</span></div>
                    <div class="col-sm-6"><span class="info-label">Cor das Roupas</span><span class="info-value">${data.kit_info.cor}</span></div>
                    <div class="col-sm-6"><span class="info-label">Data de Cadastro</span><span class="info-value">${data.kit_info.data_cadastro_fmt}</span></div>
                    ${data.kit_info.info_adicional ? `<div class="col-sm-12"><span class="info-label">Observações</span><span class="info-value">${data.kit_info.info_adicional}</span></div>` : ''}
                </div>
            </div>`;
        }

        if ((data.hist || []).length) {
            html += '<h6 class="font-weight-bold mb-2"><i class="fas fa-history mr-1"></i>HISTÓRICO DE ATRIBUIÇÕES E LIBERAÇÕES</h6>';
            html += '<table class="table table-sm table-striped small"><thead class="bg-light"><tr><th>Data/Hora</th><th>IPEN</th><th>Nome</th><th>Tipo</th><th>Valor Antigo</th><th>Valor Novo</th></tr></thead><tbody>';
            html += data.hist.map((h) => `<tr>
                <td>${h.data_alteracao_fmt}</td><td>${h.ipen}</td><td>${h.nome}</td>
                <td><span class="badge badge-${badges[h.tipo_alteracao] || 'secondary'}">${h.tipo_alteracao}</span></td>
                <td>${h.valor_antigo || ''}</td><td>${h.valor_novo || ''}</td>
            </tr>`).join('');
            html += '</tbody></table>';
        } else {
            html += `<div class="text-center text-muted mt-5"><i class="fas fa-info-circle fa-3x mb-3"></i><p>Nenhum histórico encontrado para o kit ${data.kit_numero || ''}.</p></div>`;
        }

        container.innerHTML = html;
    };

    window.renderListaRoupaCivil = (rows) => {
        const container = document.getElementById('roupaCivilList');
        if (!container) return;
        if (!rows.length) {
            container.innerHTML = '<div class="text-center text-muted mt-5"><i class="fas fa-info-circle fa-3x mb-3"></i><p>Nenhum registro encontrado.</p></div>';
            return;
        }
        const body = rows.map((row) => `<tr data-roupa-ipen="${row.ipen}" class="${row.is_inativo ? 'bg-warning text-dark' : ''}">
            <td class='text-center font-weight-bold'>${row.ipen}</td>
            <td>${row.nome_exib}${row.is_inativo ? ' <span class="badge badge-danger">Inativo</span>' : ''}</td>
            <td class='small'>${row.pecas_formatadas}</td>
            <td class='small'>${row.criado_por}</td>
            <td class='small'>${row.data_fmt}</td>
            <td class='text-center'>
                <button class='btn btn-xs btn-warning mr-1' onclick='editarRoupaCivil(${row.id})' title='Editar'>
                    <i class='fas fa-edit'></i>
                </button>
                <button class='btn btn-xs btn-danger' onclick='excluirRoupaCivil(${row.id})' title='Excluir'>
                    <i class='fas fa-trash'></i>
                </button>
            </td>
        </tr>`).join('');

        container.innerHTML = `<table class="table table-sm table-striped small border"><thead class="bg-light"><tr><th class="text-center">IPEN</th><th>Nome</th><th>Peças</th><th>Cadastrado por</th><th>Data</th><th class="text-center">Ações</th></tr></thead><tbody>${body}</tbody></table>`;
    };

    // Carregar lista quando o offcanvas abrir
    window.exibOff = ((originalFn) => {
        return (id, show = true) => {
            originalFn(id, show);
            if (id === 'off_roupa_civil' && show) {
                setTimeout(() => window.carregarListaRoupaCivil(), 300);
            }
        };
    })(window.exibOff);

    window.editarRoupaCivil = async (id) => {
        try {
            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', {
                method: 'POST',
                body: new URLSearchParams({ db_action: 'obter_roupa_civil', id: id })
            });
            const json = await res.json();

            if (json.success) {
                window.popularFormularioRoupaCivil(json.data);
                // Scroll para o formulário
                document.getElementById('formCadastrarRoupaCivil').scrollIntoView({ behavior: 'smooth' });
            } else {
                alert('Erro: ' + json.error);
            }
        } catch (err) {
            alert('Erro de conexão.');
        }
    };

    window.popularFormularioRoupaCivil = (data) => {
        // Definir modo de edição
        window.modoEdicaoRoupaCivil = true;
        window.editandoIdRoupaCivil = data.id;

        // Atualizar título do formulário
        const titulo = document.querySelector('#formCadastrarRoupaCivil .form-section-title');
        if (titulo) {
            titulo.innerHTML = '<i class="fas fa-edit"></i> Editar Cadastro de Roupa Civil';
        }

        // Preencher campo do interno
        document.getElementById('buscaInternoRoupaCivil').value = `${data.ipen} - ${data.nome_interno}`;
        document.getElementById('ipenSelecionadoRoupaCivil').value = data.ipen;

        // Definir cor e texto do status
        const statusClass = data.status === 'A' ? 'text-success' : 'text-danger';
        const statusText = data.status === 'A' ? 'Ativo' : 'Inativo';

        document.getElementById('infoInternoSelecionado').innerHTML = `
            <strong>IPEN:</strong> ${data.ipen} |
            <strong>Status:</strong> <span class="${statusClass}">${statusText}</span>
        `;

        // Limpar itens existentes
        document.getElementById('itensPredefinidos').innerHTML = '';
        document.getElementById('itensOutros').innerHTML = '';

        // Resetar contadores
        contadorItensPredefinidos = 0;
        contadorItensOutros = 0;

        // Parsear e adicionar peças existentes
        const pecas = JSON.parse(data.pecas);

        // Adicionar itens pré-definidos
        if (pecas.predefinidos && pecas.predefinidos.length > 0) {
            pecas.predefinidos.forEach(item => {
                window.adicionarItemPredefinido();
                const lastItem = document.querySelector(`#item-predefinido-${contadorItensPredefinidos}`);
                if (lastItem) {
                    lastItem.querySelector('.item-tipo').value = item.tipo;
                    lastItem.querySelector('.item-quantidade').value = item.quantidade;
                    if (item.observacao) {
                        lastItem.querySelector('.item-observacao').value = item.observacao;
                    }
                }
            });
        }

        // Adicionar itens outros
        if (pecas.outros && pecas.outros.length > 0) {
            pecas.outros.forEach(item => {
                window.adicionarItemOutro();
                const lastItem = document.querySelector(`#item-outro-${contadorItensOutros}`);
                if (lastItem) {
                    lastItem.querySelector('.item-tipo').value = item.tipo;
                    lastItem.querySelector('.item-quantidade').value = item.quantidade;
                    if (item.observacao) {
                        lastItem.querySelector('.item-observacao').value = item.observacao;
                    }
                }
            });
        }

        // Atualizar dados do formulário
        window.atualizarDadosForm();

        // Mudar texto do botão submit
        const btnSubmit = document.querySelector('#formCadastrarRoupaCivil button[type="submit"]');
        if (btnSubmit) {
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> ATUALIZAR ROUPA CIVIL';
            btnSubmit.className = 'btn btn-warning btn-block';
        }

        // Adicionar botão cancelar
        if (!document.getElementById('btnCancelarEdicao')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.type = 'button';
            cancelBtn.id = 'btnCancelarEdicao';
            cancelBtn.className = 'btn btn-secondary btn-block mt-2';
            cancelBtn.innerHTML = '<i class="fas fa-times"></i> Cancelar Edição';
            cancelBtn.onclick = window.cancelarEdicaoRoupaCivil;
            btnSubmit.parentNode.appendChild(cancelBtn);
        }
    };

    window.cancelarEdicaoRoupaCivil = () => {
        window.modoEdicaoRoupaCivil = false;
        window.editandoIdRoupaCivil = null;

        // Resetar formulário
        document.getElementById('formCadastrarRoupaCivil').reset();

        // Limpar campos
        document.getElementById('buscaInternoRoupaCivil').value = '';
        document.getElementById('ipenSelecionadoRoupaCivil').value = '';
        document.getElementById('infoInternoSelecionado').innerHTML = '';

        // Limpar itens
        document.getElementById('itensPredefinidos').innerHTML = '';
        document.getElementById('itensOutros').innerHTML = '';

        // Resetar contadores
        contadorItensPredefinidos = 0;
        contadorItensOutros = 0;

        // Resetar título
        const titulo = document.querySelector('#formCadastrarRoupaCivil .form-section-title');
        if (titulo) {
            titulo.innerHTML = '<i class="fas fa-plus-circle"></i> Novo Cadastro de Roupa Civil';
        }

        // Resetar botão submit
        const btnSubmit = document.querySelector('#formCadastrarRoupaCivil button[type="submit"]');
        if (btnSubmit) {
            btnSubmit.innerHTML = '<i class="fas fa-save"></i> CADASTRAR ROUPA CIVIL';
            btnSubmit.className = 'btn btn-success btn-block';
        }

        // Remover botão cancelar
        const cancelBtn = document.getElementById('btnCancelarEdicao');
        if (cancelBtn) {
            cancelBtn.remove();
        }

        // Limpar dados do formulário
        document.getElementById('pecasJson').value = '';
    };

    window.excluirRoupaCivil = async (id) => {
        if (confirm('Tem certeza que deseja excluir este registro de roupa civil?')) {
            try {
                const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', {
                    method: 'POST',
                    body: new URLSearchParams({ db_action: 'excluir_roupa_civil', id: id })
                });
                const json = await res.json();
                if (json.success) {
                    alert('Registro excluído com sucesso!');
                    window.carregarListaRoupaCivil();
                } else {
                    alert('Erro: ' + json.error);
                }
            } catch (err) {
                alert('Erro de conexão.');
            }
        }
    };

    // ========== FUNCIONALIDADES PARA BUSCA DE INTERNOS ==========

    // Declarar variável no escopo global para evitar redeclaração
    if (typeof window.buscaInternoTimeout === 'undefined') {
        window.buscaInternoTimeout = null;
    }

    window.initBuscaInternoRoupaCivil = () => {
        const input = document.getElementById('buscaInternoRoupaCivil');
        const dropdown = document.createElement('div');
        dropdown.id = 'dropdownInternosRoupaCivil';
        dropdown.className = 'dropdown-menu w-100 show';
        dropdown.style.maxHeight = '200px';
        dropdown.style.overflowY = 'auto';
        input.parentNode.appendChild(dropdown);

        input.addEventListener('input', function () {
            clearTimeout(window.buscaInternoTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                dropdown.classList.remove('show');
                return;
            }
            window.buscaInternoTimeout = setTimeout(() => buscarInternosRoupaCivil(query), 300);
        });

        input.addEventListener('blur', function () {
            setTimeout(() => dropdown.classList.remove('show'), 200);
        });

        input.addEventListener('focus', function () {
            if (this.value.trim().length >= 2) {
                buscarInternosRoupaCivil(this.value.trim());
            }
        });
    };

    window.buscarInternosRoupaCivil = async (query) => {
        try {
            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', {
                method: 'POST',
                body: new URLSearchParams({ db_action: 'buscar_internos', query: query })
            });
            const data = await res.json();
            mostrarResultadosBuscaInternos(data);
        } catch (err) {
            console.error('Erro na busca:', err);
        }
    };

    window.mostrarResultadosBuscaInternos = (data) => {
        const dropdown = document.getElementById('dropdownInternosRoupaCivil');
        dropdown.innerHTML = '';

        if (!data || !data.length) {
            dropdown.innerHTML = '<div class="dropdown-item text-muted">Nenhum interno encontrado</div>';
            dropdown.classList.add('show');
            return;
        }

        data.forEach(interno => {
            const item = document.createElement('div');
            item.className = 'dropdown-item';
            item.style.cursor = 'pointer';

            // Definir cor do status
            const statusClass = interno.status === 'A' ? 'text-success' : 'text-warning';
            const statusText = interno.status === 'A' ? 'Ativo' : 'Inativo';
            const bgClass = interno.status === 'I' ? 'bg-warning text-dark' : '';

            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center ${bgClass}">
                    <div>
                        <strong>${interno.ipen}</strong> - ${interno.nome_social || interno.nome}
                        <div class="small ${statusClass}">${statusText}</div>
                    </div>
                    <small class="text-muted">${interno.galeria}${interno.bloco}-${interno.res}</small>
                </div>
            `;
            item.onclick = () => selecionarInternoRoupaCivil(interno);
            dropdown.appendChild(item);
        });

        dropdown.classList.add('show');
    };

    window.selecionarInternoRoupaCivil = (interno) => {
        document.getElementById('buscaInternoRoupaCivil').value = `${interno.ipen} - ${interno.nome_social || interno.nome}`;
        document.getElementById('ipenSelecionadoRoupaCivil').value = interno.ipen;

        // Definir cor e texto do status
        const statusClass = interno.status === 'A' ? 'text-success' : 'text-danger';
        const statusText = interno.status === 'A' ? 'Ativo' : 'Inativo';

        document.getElementById('infoInternoSelecionado').innerHTML = `
            <strong>Local:</strong> ${interno.galeria}${interno.bloco}-${interno.res} |
            <strong>Situação:</strong> ${interno.situacao || 'N/A'} |
            <strong>Status:</strong> <span class="${statusClass}">${statusText}</span>
        `;
        document.getElementById('dropdownInternosRoupaCivil').classList.remove('show');
    };

    window.limparSelecaoInternoRoupaCivil = () => {
        document.getElementById('buscaInternoRoupaCivil').value = '';
        document.getElementById('ipenSelecionadoRoupaCivil').value = '';
        document.getElementById('infoInternoSelecionado').innerHTML = '';
    };

    // ========== FUNCIONALIDADES PARA ITENS DO KIT ==========

    const ITENS_PREDEFINIDOS = [
        'Camiseta', 'Bermuda', 'Blusa', 'Casaco', 'Calça', 'Jaqueta',
        'Boné', 'Tênis', 'Sapato', 'Meia', 'Luva', 'Bolsa', 'Mochila', 'Cueca', 'Chapéu'
    ];

    let contadorItensPredefinidos = 0;
    let contadorItensOutros = 0;

    window.adicionarItemPredefinido = () => {
        contadorItensPredefinidos++;
        const container = document.getElementById('itensPredefinidos');
        const itemDiv = document.createElement('div');
        itemDiv.className = 'item-kit border rounded p-2 mb-2 bg-light';
        itemDiv.id = `item-predefinido-${contadorItensPredefinidos}`;

        itemDiv.innerHTML = `
            <div class="row">
                <div class="col-5">
                    <select class="form-control form-control-sm item-tipo" onchange="atualizarDadosForm()">
                        <option value="">Selecione item</option>
                        ${ITENS_PREDEFINIDOS.map(item => `<option value="${item}">${item}</option>`).join('')}
                    </select>
                </div>
                <div class="col-3">
                    <input type="number" class="form-control form-control-sm item-quantidade" placeholder="Qtd" min="1" onchange="atualizarDadosForm()">
                </div>
                <div class="col-3">
                    <input type="text" class="form-control form-control-sm item-observacao" placeholder="Observação" onchange="atualizarDadosForm()">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerItem('${itemDiv.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        container.appendChild(itemDiv);
    };

    window.adicionarItemOutro = () => {
        contadorItensOutros++;
        const container = document.getElementById('itensOutros');
        const itemDiv = document.createElement('div');
        itemDiv.className = 'item-kit border rounded p-2 mb-2 bg-light';
        itemDiv.id = `item-outro-${contadorItensOutros}`;

        itemDiv.innerHTML = `
            <div class="row">
                <div class="col-5">
                    <input type="text" class="form-control form-control-sm item-tipo" placeholder="Nome do item" onchange="atualizarDadosForm()">
                </div>
                <div class="col-3">
                    <input type="number" class="form-control form-control-sm item-quantidade" placeholder="Qtd" min="1" onchange="atualizarDadosForm()">
                </div>
                <div class="col-3">
                    <input type="text" class="form-control form-control-sm item-observacao" placeholder="Observação" onchange="atualizarDadosForm()">
                </div>
                <div class="col-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerItem('${itemDiv.id}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

        container.appendChild(itemDiv);
    };

    window.removerItem = (itemId) => {
        const item = document.getElementById(itemId);
        if (item) {
            item.remove();
            atualizarDadosForm();
        }
    };

    window.atualizarDadosForm = () => {
        const dados = {
            predefinidos: [],
            outros: []
        };

        // Coletar itens pré-definidos
        document.querySelectorAll('#itensPredefinidos .item-kit').forEach(item => {
            const tipo = item.querySelector('.item-tipo').value;
            const quantidade = item.querySelector('.item-quantidade').value;
            const observacao = item.querySelector('.item-observacao').value;

            if (tipo && quantidade) {
                dados.predefinidos.push({
                    tipo: tipo,
                    quantidade: parseInt(quantidade),
                    observacao: observacao || ''
                });
            }
        });

        // Coletar itens outros
        document.querySelectorAll('#itensOutros .item-kit').forEach(item => {
            const tipo = item.querySelector('.item-tipo').value;
            const quantidade = item.querySelector('.item-quantidade').value;
            const observacao = item.querySelector('.item-observacao').value;

            if (tipo && quantidade) {
                dados.outros.push({
                    tipo: tipo,
                    quantidade: parseInt(quantidade),
                    observacao: observacao || ''
                });
            }
        });

        document.getElementById('pecasJson').value = JSON.stringify(dados);
    };

    // Inicializar funcionalidades quando o offcanvas abrir
    window.exibOff = ((originalFn) => {
        return (id, show = true) => {
            originalFn(id, show);

            // Se for o offcanvas de roupa civil
            if (id === 'off_roupa_civil') {
                if (show) {
                    setTimeout(() => {
                        window.initBuscaInternoRoupaCivil();
                        window.carregarListaRoupaCivil();
                    }, 300);
                } else {
                    // Limpar pesquisa e destaques ao fechar
                    document.getElementById('buscaRoupaCivilInput').value = '';
                    document.querySelectorAll('.roupa-civil-encontrado').forEach(el => {
                        el.classList.remove('roupa-civil-encontrado');
                    });
                    document.querySelectorAll('.blink-anim').forEach(el => {
                        el.classList.remove('blink-anim');
                    });
                }
            }
        };
    })(window.exibOff);

    window.abrirModalEtiquetasRoupaCivil = async () => {
        $('#modalEtiquetasRoupaCivil').modal('show');
        await window.carregarEtiquetasRoupaCivil();
    };

    window.carregarEtiquetasRoupaCivil = async () => {
        const tbody = document.getElementById('listaEtiquetasRoupaCivil');
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Carregando...</td></tr>';
        try {
            const res = await fetch('/modulos/censura/rouparia/gestao_kits/view.php', {
                method: 'POST',
                body: new URLSearchParams({ db_action: 'listar_etiquetas_roupa_civil' })
            });
            const json = await res.json();
            if (!json.success) throw new Error('erro');
            window.etiquetasRoupaCivilData = json.rows || [];

            // Preencher select de usuários únicos
            window.preencherSelectUsuarios(window.etiquetasRoupaCivilData);

            window.renderEtiquetasRoupaCivil(window.etiquetasRoupaCivilData);
        } catch (e) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Erro ao carregar entradas.</td></tr>';
        }
    };

    window.preencherSelectUsuarios = (data) => {
        const select = document.getElementById('filtroCriadoPor');
        if (!select) return;

        // Extrair usuários únicos
        const usuariosUnicos = [...new Set(data.map(r => r.criado_por || 'Sistema').filter(u => u && u.trim() !== ''))];

        // Limpar opções existentes (exceto a primeira)
        select.innerHTML = '<option value="">Todos os usuários</option>';

        // Adicionar usuários em ordem alfabética
        usuariosUnicos.sort().forEach(usuario => {
            const option = document.createElement('option');
            option.value = usuario;
            option.textContent = usuario;
            select.appendChild(option);
        });
    };

    window.renderEtiquetasRoupaCivil = (rows) => {
        const tbody = document.getElementById('listaEtiquetasRoupaCivil');
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado.</td></tr>';
            window.atualizarContadorEtiquetas();
            return;
        }
        tbody.innerHTML = rows.map(r => {
            const dt = r.criado_em_fmt || '';
            // Formatar data para o dataset (YYYY-MM-DD HH:MM:SS)
            const dataCadastro = r.criado_em ? r.criado_em.replace(/(\d{2})\/(\d{2})\/(\d{4})\s+(\d{2}):(\d{2})/, '$3-$2-$1 $4:$5:00') : '';
            return `<tr data-etq-row data-search="${(r.ipen + ' ' + r.nome + ' ' + r.status).toLowerCase()}" data-status="${r.status}" data-data-cadastro="${dataCadastro}" data-criado-por="${(r.criado_por || '').toLowerCase()}">
            <td class="text-center"><input type="checkbox" class="etq-check" value="${r.id}" onchange="window.atualizarContadorEtiquetas()"></td>
            <td><strong>${r.ipen}</strong></td>
            <td>${r.nome}</td>
            <td>${r.status === 'INATIVO' ? '<span class="badge badge-danger">INATIVO</span>' : '<span class="badge badge-success">ATIVO</span>'}</td>
            <td><small>${r.criado_por || 'Sistema'}</small></td>
            <td>${dt}</td>
        </tr>`;
        }).join('');
        window.atualizarContadorEtiquetas();
    };

    window.selecionarTodosVisiveis = () => {
        window.marcarTodasEtiquetasVisiveis(true);
    };

    window.filtrarHoje = () => {
        const hoje = new Date().toISOString().split('T')[0]; // YYYY-MM-DD
        document.getElementById('filtroDataInicial').value = hoje;
        document.getElementById('filtroDataFinal').value = hoje;
        document.getElementById('filtroHoraInicial').value = '00:00';
        document.getElementById('filtroHoraFinal').value = '23:59';
        window.filtrarEtiquetasRoupaCivil();
    };

    window.filtrarEtiquetasRoupaCivil = () => {
        const txt = (document.getElementById('filtroEtiquetaBusca').value || '').toLowerCase();
        const st = document.getElementById('filtroEtiquetaStatus').value;
        const criadoPor = document.getElementById('filtroCriadoPor').value;

        // Obter valores dos filtros de data e hora
        const dataInicial = document.getElementById('filtroDataInicial').value;
        const dataFinal = document.getElementById('filtroDataFinal').value;
        const horaInicial = document.getElementById('filtroHoraInicial').value;
        const horaFinal = document.getElementById('filtroHoraFinal').value;

        document.querySelectorAll('#listaEtiquetasRoupaCivil tr[data-etq-row]').forEach(tr => {
            const okTxt = tr.dataset.search.includes(txt);
            const okSt = !st || tr.dataset.status === st;
            const okCriadoPor = !criadoPor || tr.dataset.criadoPor.toLowerCase() === criadoPor.toLowerCase();

            // Filtrar por data e hora se preenchidos
            let okDataHora = true;
            if (dataInicial || dataFinal) {
                const dataCadastro = tr.dataset.dataCadastro;
                if (dataCadastro) {
                    // Criar objeto Date da data de cadastro
                    const dataCadastroObj = new Date(dataCadastro);

                    // Criar objetos para data/hora inicial e final
                    let dataInicialObj = null;
                    let dataFinalObj = null;

                    if (dataInicial) {
                        // Combinar data e hora inicial
                        const dataHoraInicial = dataInicial + 'T' + (horaInicial || '00:00');
                        dataInicialObj = new Date(dataHoraInicial);
                    }
                    if (dataFinal) {
                        // Combinar data e hora final
                        const dataHoraFinal = dataFinal + 'T' + (horaFinal || '23:59:59');
                        dataFinalObj = new Date(dataHoraFinal);
                    }

                    // Verificar se está dentro do período
                    if (dataInicialObj && dataFinalObj) {
                        okDataHora = dataCadastroObj >= dataInicialObj && dataCadastroObj <= dataFinalObj;
                    } else if (dataInicialObj) {
                        okDataHora = dataCadastroObj >= dataInicialObj;
                    } else if (dataFinalObj) {
                        okDataHora = dataCadastroObj <= dataFinalObj;
                    }
                } else {
                    okDataHora = false;
                }
            }

            const resultado = okTxt && okSt && okCriadoPor && okDataHora;
            tr.style.display = resultado ? '' : 'none';
        });

        window.atualizarContadorEtiquetas();
    };

    window.marcarTodasEtiquetasVisiveis = (checked) => {
        document.querySelectorAll('#listaEtiquetasRoupaCivil tr[data-etq-row]').forEach(tr => {
            if (tr.style.display !== 'none') {
                const cb = tr.querySelector('.etq-check');
                if (cb) cb.checked = checked;
            }
        });
        window.atualizarContadorEtiquetas();
    };

    window.atualizarContadorEtiquetas = () => {
        const total = document.querySelectorAll('.etq-check:checked').length;
        document.getElementById('contadorEtiquetasSelecionadas').textContent = `${total} selecionado(s)`;
    };

    window.limparFiltrosEtiquetas = () => {
        document.getElementById('filtroEtiquetaBusca').value = '';
        document.getElementById('filtroEtiquetaStatus').value = '';
        document.getElementById('filtroCriadoPor').value = '';
        document.getElementById('filtroDataInicial').value = '';
        document.getElementById('filtroDataFinal').value = '';
        document.getElementById('filtroHoraInicial').value = '';
        document.getElementById('filtroHoraFinal').value = '';
        window.filtrarEtiquetasRoupaCivil();
    };

    window.imprimirEtiquetasSelecionadas = () => {
        const ids = Array.from(document.querySelectorAll('.etq-check:checked')).map(i => i.value);
        if (!ids.length) return alert('Selecione ao menos uma entrada para imprimir.');
        const url = `includes/internos_impressao_etiquetas_civil.php?ids=${ids.join(',')}`;
        window.open(url, '_blank');
    };

})();
