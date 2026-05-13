(function() {
    'use strict';

class EclusaMovimentacoes {
    constructor() {
        const base = (window.SIGEP_BASE_PATH || '').replace(/\/$/, '');
        this.basePath = base;

        // Para SPA, garantir que o endpoint funcione corretamente
        if (!this.basePath) {
            this.endpoint = '/includes/eclusa_movimentacoes_logica.php';
        } else {
            this.endpoint = `${this.basePath}/includes/eclusa_movimentacoes_logica.php`;
        }

        this.endpointCandidates = this.buildEndpointCandidates();
        this.lastKpiTitle = 'Detalhes';
        this.lastKpiContentHtml = '';
        this.currentPage = 1;
        this.totalPages = 1;
        this.currentFilters = {};
        this.debounceTimers = {};
        this.saving = false;
        this.deleting = false;

        // Modos de edição para novos cadastros
        this.editandoVeiculo = false;
        this.editandoEmpresa = false;
        this.editandoMotorista = false;

        this.form = document.getElementById('movimentacaoForm');
        this.offcanvasMov = document.getElementById('offcanvasMovimentacao');
        this.offcanvasRelatorio = document.getElementById('offcanvasRelatorio');
        this.offcanvasKpi = document.getElementById('offcanvasKpiDetalhes');

        // Botões para novo cadastro
        this.btnNovoVeiculo = document.getElementById('btnNovoVeiculo');
        this.btnNovaEmpresa = document.getElementById('btnNovaEmpresa');
        this.btnNovoMotorista = document.getElementById('btnNovoMotorista');

        this.init();
    }

    init() {
        this.bindEvents();
        this.bindAutocomplete();
        this.setDefaultFormDate();
        this.loadFilterOptions();
        this.reloadAll();
    }

    bindEvents() {
        document.getElementById('btnNovaMovimentacao')?.addEventListener('click', () => this.openFormForCreate());
        document.getElementById('btnAbrirRelatorio')?.addEventListener('click', () => this.openOffcanvas(this.offcanvasRelatorio));
        document.getElementById('btnAtualizarTudo')?.addEventListener('click', () => this.reloadAll());

        document.getElementById('filtrosMovForm')?.addEventListener('submit', (event) => {
            event.preventDefault();
            this.applyFilters();
        });

        document.getElementById('btnLimparFiltros')?.addEventListener('click', () => {
            const form = document.getElementById('filtrosMovForm');
            form?.reset();
            this.currentFilters = {};
            this.currentPage = 1;
            this.loadMovimentacoes();
        });

        this.form?.addEventListener('submit', (event) => {
            event.preventDefault();
            this.saveMovimentacao();
        });

        document.getElementById('movPrev')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage -= 1;
                this.loadMovimentacoes();
            }
        });

        document.getElementById('movNext')?.addEventListener('click', () => {
            if (this.currentPage < this.totalPages) {
                this.currentPage += 1;
                this.loadMovimentacoes();
            }
        });

        document.getElementById('btnGerarRelatorio')?.addEventListener('click', () => this.gerarRelatorio());
        document.getElementById('btnImprimirKpi')?.addEventListener('click', () => this.printKpiDetails());

        document.querySelectorAll('[data-offcanvas-close]').forEach((btn) => {
            btn.addEventListener('click', (event) => {
                const offcanvas = event.target.closest('.offcanvas-eclusa');
                this.closeOffcanvas(offcanvas);
            });
        });

        document.querySelectorAll('.kpi-card').forEach((card) => {
            card.addEventListener('click', () => {
                const kpi = card.getAttribute('data-kpi') || '';
                this.showKpiDetails(kpi);
            });
        });

        // Botões de novo cadastro
        this.btnNovoVeiculo?.addEventListener('click', () => this.toggleNovoVeiculo());
        this.btnNovaEmpresa?.addEventListener('click', () => this.toggleNovaEmpresa());
        this.btnNovoMotorista?.addEventListener('click', () => this.toggleNovoMotorista());

        this.form?.addEventListener('submit', (event) => {
            event.preventDefault();
            this.saveMovimentacao();
        });

        document.getElementById('movTableBody')?.addEventListener('click', (event) => {
            const button = event.target.closest('button[data-action]');
            if (!button) {
                return;
            }

            const action = button.getAttribute('data-action');
            const id = Number(button.getAttribute('data-id'));
            if (!id) {
                return;
            }

            if (action === 'edit') {
                this.openFormForEdit(id);
            }
            if (action === 'delete') {
                this.deleteMovimentacao(id);
            }
        });
    }

    bindAutocomplete() {
        document.querySelectorAll('.busca-inteligente input[data-campo]').forEach((input) => {
            const field = input.getAttribute('data-campo');
            if (!field) {
                return;
            }

            input.addEventListener('input', () => {
                this.clearLinkedIds(input);
                const term = input.value.trim();
                if (term.length >= 1) {
                    this.performAutocomplete(input);
                } else {
                    this.hideAutocompleteResults(input);
                }
                return;

                clearTimeout(this.debounceTimers[field]);
                this.debounceTimers[field] = setTimeout(() => {
                    this.fetchAutocomplete(field, term, input);
                }, 250);
            });

            input.addEventListener('focus', () => {
                const term = input.value.trim();
                if (term.length >= 0) {
                    this.performAutocomplete(input);
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('.busca-inteligente')) {
                document.querySelectorAll('.busca-inteligente-resultados').forEach((box) => {
                    box.style.display = 'none';
                });
            }
        });
    }

    async fetchAutocomplete(field, term, input) {
        try {
            const result = await this.post({
                db_action: 'busca_autocomplete',
                campo: field,
                termo: term,
            });

            this.showAutocompleteResults(input, Array.isArray(result.data) ? result.data : []);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao buscar sugestões.');
        }
    }

    showAutocompleteResults(input, items) {
        const container = input.closest('.busca-inteligente');
        if (!container) {
            return;
        }

        let list = container.querySelector('.busca-inteligente-resultados');
        if (!list) {
            list = document.createElement('div');
            list.className = 'busca-inteligente-resultados';
            container.appendChild(list);
        }

        if (!items.length) {
            list.innerHTML = '<div class="busca-inteligente-item text-muted">Nenhum resultado</div>';
            list.style.display = 'block';
            return;
        }

        list.innerHTML = items.map((item) => {
            const id = Number(item.id || 0);
            const value = this.escapeHtml(item.value || '');
            const label = this.escapeHtml(item.label || item.value || '');
            const placa = this.escapeHtml(item.placa || '');
            const tipoVeiculo = this.escapeHtml(item.tipo_veiculo || item.value || '');
            const empresaId = Number(item.empresa_id || 0);

            return `
                <div class="busca-inteligente-item" data-id="${id}" data-value="${value}" data-placa="${placa}" data-tipo-veiculo="${tipoVeiculo}" data-empresa-id="${empresaId}">
                    ${label}
                </div>
            `;
        }).join('');

        list.style.display = 'block';

        list.querySelectorAll('.busca-inteligente-item').forEach((row) => {
            row.addEventListener('click', () => {
                this.applyAutocompleteSelection(input, row);
                list.style.display = 'none';
            });
        });
    }

    applyAutocompleteSelection(input, row) {
        const selectedValue = row.getAttribute('data-value') || '';
        const selectedId = Number(row.getAttribute('data-id') || 0);

        input.value = selectedValue;

        const name = input.getAttribute('name');
        if (name === 'placa_veiculo') {
            this.setValue('veiculo_id', selectedId || '');
            const tipo = row.getAttribute('data-tipo-veiculo') || '';
            if (tipo && !this.getValue('tipo_veiculo')) {
                this.setValue('tipo_veiculo', tipo);
                // Tornar veículo readonly quando selecionado via placa
                const veiculoInput = document.getElementById('tipo_veiculo');
                if (veiculoInput) {
                    veiculoInput.readOnly = true;
                    veiculoInput.classList.add('bg-light');
                }
            }
        }

        if (name === 'tipo_veiculo') {
            this.setValue('veiculo_id', selectedId || '');
            const placa = row.getAttribute('data-placa') || '';
            if (placa && !this.getValue('placa_veiculo')) {
                this.setValue('placa_veiculo', placa);
            }
        }

        if (name === 'empresa') {
            this.setValue('empresa_id', selectedId || '');
        }

        if (name === 'motorista') {
            this.setValue('motorista_id', selectedId || '');
        }
    }

    async toggleNovoVeiculo() {
        if (!this.editandoVeiculo) {
            // Entrar no modo de edição
            this.editandoVeiculo = true;
            this.setValue('placa_veiculo', '');
            this.setValue('tipo_veiculo', '');
            this.setValue('veiculo_id', '');

            const veiculoInput = document.getElementById('tipo_veiculo');
            if (veiculoInput) {
                veiculoInput.readOnly = false;
                veiculoInput.classList.remove('bg-light');
            }

            if (this.btnNovoVeiculo) {
                this.btnNovoVeiculo.innerHTML = '<i class="fas fa-save"></i>';
                this.btnNovoVeiculo.title = 'Salvar novo veículo';
                this.btnNovoVeiculo.classList.remove('btn-outline-secondary');
                this.btnNovoVeiculo.classList.add('btn-success');
            }
        } else {
            // Salvar novo veículo
            const placa = this.getValue('placa_veiculo').trim();
            const tipo = this.getValue('tipo_veiculo').trim();

            if (!placa || !tipo) {
                this.notifyError('Preencha a placa e o tipo do veículo.');
                return;
            }

            try {
                const result = await this.post({
                    db_action: 'salvar_veiculo',
                    placa: placa,
                    nome: tipo,
                    modelo: tipo
                });

                this.notifySuccess('Veículo cadastrado com sucesso!');

                // Sair do modo de edição
                this.editandoVeiculo = false;
                this.setValue('veiculo_id', result.data?.id || '');

                if (this.btnNovoVeiculo) {
                    this.btnNovoVeiculo.innerHTML = '<i class="fas fa-plus"></i>';
                    this.btnNovoVeiculo.title = 'Cadastrar novo veículo';
                    this.btnNovoVeiculo.classList.remove('btn-success');
                    this.btnNovoVeiculo.classList.add('btn-outline-secondary');
                }

                // Tornar veículo readonly novamente
                const veiculoInput = document.getElementById('tipo_veiculo');
                if (veiculoInput) {
                    veiculoInput.readOnly = true;
                    veiculoInput.classList.add('bg-light');
                }
            } catch (error) {
                this.notifyError(error.message || 'Erro ao salvar veículo.');
            }
        }
    }

    async toggleNovaEmpresa() {
        if (!this.editandoEmpresa) {
            // Entrar no modo de edição
            this.editandoEmpresa = true;
            this.setValue('empresa', '');
            this.setValue('empresa_id', '');

            if (this.btnNovaEmpresa) {
                this.btnNovaEmpresa.innerHTML = '<i class="fas fa-save"></i>';
                this.btnNovaEmpresa.title = 'Salvar nova empresa';
                this.btnNovaEmpresa.classList.remove('btn-outline-secondary');
                this.btnNovaEmpresa.classList.add('btn-success');
            }
        } else {
            // Salvar nova empresa
            const nome = this.getValue('empresa').trim();

            if (!nome) {
                this.notifyError('Preencha o nome da empresa.');
                return;
            }

            try {
                const result = await this.post({
                    db_action: 'salvar_empresa',
                    nome: nome
                });

                this.notifySuccess('Empresa cadastrada com sucesso!');

                // Sair do modo de edição
                this.editandoEmpresa = false;
                this.setValue('empresa_id', result.data?.id || '');

                if (this.btnNovaEmpresa) {
                    this.btnNovaEmpresa.innerHTML = '<i class="fas fa-plus"></i>';
                    this.btnNovaEmpresa.title = 'Cadastrar nova empresa';
                    this.btnNovaEmpresa.classList.remove('btn-success');
                    this.btnNovaEmpresa.classList.add('btn-outline-secondary');
                }
            } catch (error) {
                this.notifyError(error.message || 'Erro ao salvar empresa.');
            }
        }
    }

    async toggleNovoMotorista() {
        if (!this.editandoMotorista) {
            // Entrar no modo de edição
            this.editandoMotorista = true;
            this.setValue('motorista', '');
            this.setValue('motorista_id', '');

            if (this.btnNovoMotorista) {
                this.btnNovoMotorista.innerHTML = '<i class="fas fa-save"></i>';
                this.btnNovoMotorista.title = 'Salvar novo motorista';
                this.btnNovoMotorista.classList.remove('btn-outline-secondary');
                this.btnNovoMotorista.classList.add('btn-success');
            }
        } else {
            // Salvar novo motorista
            const nome = this.getValue('motorista').trim();

            if (!nome) {
                this.notifyError('Preencha o nome do motorista.');
                return;
            }

            try {
                const result = await this.post({
                    db_action: 'salvar_motorista',
                    nome: nome
                });

                this.notifySuccess('Motorista cadastrado com sucesso!');

                // Sair do modo de edição
                this.editandoMotorista = false;
                this.setValue('motorista_id', result.data?.id || '');

                if (this.btnNovoMotorista) {
                    this.btnNovoMotorista.innerHTML = '<i class="fas fa-plus"></i>';
                    this.btnNovoMotorista.title = 'Cadastrar novo motorista';
                    this.btnNovoMotorista.classList.remove('btn-success');
                    this.btnNovoMotorista.classList.add('btn-outline-secondary');
                }
            } catch (error) {
                this.notifyError(error.message || 'Erro ao salvar motorista.');
            }
        }
    }

    async loadFilterOptions() {
        const campos = ['placa', 'veiculo', 'empresa', 'motorista'];

        for (const campo of campos) {
            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `db_action=busca_autocomplete&campo=${campo}&termo=`
                });

                const result = await response.json();
                const data = result.data || [];

                const select = document.getElementById(`filtro_${campo}`);
                if (select) {
                    // Clear existing options except the first
                    while (select.options.length > 1) {
                        select.remove(1);
                    }

                    // Add new options
                    data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item.value;
                        option.textContent = item.label || item.value;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error(`Erro ao carregar opções de ${campo}:`, error);
            }
        }
    }

    async performAutocomplete(input) {
        const campo = input.getAttribute('data-campo');
        const termo = input.value.trim();
        const endpoint = this.endpoint;
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `db_action=busca_autocomplete&campo=${campo}&termo=${encodeURIComponent(termo)}`
            });
            const result = await response.json();
            this.showAutocompleteResults(input, result.data || []);
        } catch (error) {
            console.error('Erro no autocomplete:', error);
        }
    }

    clearLinkedIds(input) {
        const name = input.getAttribute('name');
        if (!name) {
            return;
        }

        if (name === 'placa_veiculo' || name === 'tipo_veiculo') {
            this.setValue('veiculo_id', '');
        }
        if (name === 'empresa') {
            this.setValue('empresa_id', '');
        }
        if (name === 'motorista') {
            this.setValue('motorista_id', '');
        }
    }

    applyFilters() {
        const form = document.getElementById('filtrosMovForm');
        if (!form) {
            return;
        }

        const formData = new FormData(form);
        this.currentFilters = {
            search: (formData.get('search') || '').toString().trim(),
            placa: (formData.get('placa') || '').toString().trim(),
            veiculo: (formData.get('veiculo') || '').toString().trim(),
            empresa: (formData.get('empresa') || '').toString().trim(),
            motorista: (formData.get('motorista') || '').toString().trim(),
            tipo_movimento: (formData.get('tipo_movimento') || '').toString().trim(),
            data_inicio: (formData.get('data_inicio') || '').toString().trim(),
            data_fim: (formData.get('data_fim') || '').toString().trim(),
        };

        this.currentPage = 1;
        this.loadMovimentacoes();
    }

    async reloadAll() {
        await Promise.all([this.loadKpis(), this.loadMovimentacoes()]);
    }

    async loadKpis() {
        try {
            const result = await this.post({ db_action: 'get_contadores' });
            const data = result.data || {};

            this.setText('kpiTotal', data.totalMovimentacoes ?? 0);
            this.setText('kpiHoje', data.movimentacoesHoje ?? 0);
            this.setText('kpiEntradas', data.entradasHoje ?? 0);
            this.setText('kpiSaidas', data.saidasHoje ?? 0);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao carregar indicadores.');
        }
    }

    async loadMovimentacoes() {
        this.setLoading(true);

        try {
            const result = await this.post({
                db_action: 'listar',
                page: this.currentPage,
                limit: 20,
                ...this.currentFilters,
            });

            const payload = result.data || {};
            const rows = Array.isArray(payload.dados) ? payload.dados : [];
            const total = Number(payload.total || 0);
            const pagina = Number(payload.pagina || this.currentPage);
            const totalPaginas = Number(payload.total_paginas || 1);

            this.currentPage = pagina;
            this.totalPages = totalPaginas;

            this.renderTable(rows);
            this.updatePagination(total, rows.length, pagina, totalPaginas);
        } catch (error) {
            this.renderTable([]);
            this.notifyError(error.message || 'Erro ao carregar movimentações.');
        } finally {
            this.setLoading(false);
        }
    }

    renderTable(rows) {
        const tbody = document.getElementById('movTableBody');
        if (!tbody) {
            return;
        }

        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4 text-muted">Nenhuma movimentação encontrada.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map((row) => {
            const tipoBadge = this.renderTipoBadge(row.tipo_movimento || 'indefinido');
            return `
                <tr>
                    <td>${this.formatDate(row.data_movimentacao)}</td>
                    <td>${this.formatTime(row.hora_chegada)}</td>
                    <td>${this.formatTime(row.hora_entrada)}</td>
                    <td>${this.formatTime(row.hora_saida)}</td>
                    <td><strong>${this.escapeHtml(row.placa_veiculo || '-')}</strong></td>
                    <td>${this.escapeHtml(row.tipo_veiculo || '-')}</td>
                    <td>${this.escapeHtml(row.empresa || '-')}</td>
                    <td>${this.escapeHtml(row.motorista || '-')}</td>
                    <td>${tipoBadge}</td>
                    <td>${this.escapeHtml(row.cadastrado_por || '-')}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" data-action="edit" data-id="${Number(row.id)}" title="Editar"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-outline-danger" data-action="delete" data-id="${Number(row.id)}" title="Excluir"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    updatePagination(total, shown, page, totalPages) {
        this.setText('movMeta', `Mostrando ${shown} de ${total} registro(s)`);
        this.setText('movPageInfo', `Página ${page} de ${totalPages}`);

        const prev = document.getElementById('movPrev');
        const next = document.getElementById('movNext');

        if (prev) {
            prev.disabled = page <= 1;
        }
        if (next) {
            next.disabled = page >= totalPages;
        }
    }

    openFormForCreate() {
        this.form?.reset();
        this.setValue('mov_id', '');
        this.setValue('veiculo_id', '');
        this.setValue('empresa_id', '');
        this.setValue('motorista_id', '');
        this.setValue('cadastrado_por', window.SIGEP_USER_NAME || '');

        // Resetar modos de edição
        this.editandoVeiculo = false;
        this.editandoEmpresa = false;
        this.editandoMotorista = false;

        // Resetar botões
        if (this.btnNovoVeiculo) {
            this.btnNovoVeiculo.innerHTML = '<i class="fas fa-plus"></i>';
            this.btnNovoVeiculo.title = 'Cadastrar novo veículo';
            this.btnNovoVeiculo.classList.remove('btn-success');
            this.btnNovoVeiculo.classList.add('btn-outline-secondary');
        }

        if (this.btnNovaEmpresa) {
            this.btnNovaEmpresa.innerHTML = '<i class="fas fa-plus"></i>';
            this.btnNovaEmpresa.title = 'Cadastrar nova empresa';
            this.btnNovaEmpresa.classList.remove('btn-success');
            this.btnNovaEmpresa.classList.add('btn-outline-secondary');
        }

        if (this.btnNovoMotorista) {
            this.btnNovoMotorista.innerHTML = '<i class="fas fa-plus"></i>';
            this.btnNovoMotorista.title = 'Cadastrar novo motorista';
            this.btnNovoMotorista.classList.remove('btn-success');
            this.btnNovoMotorista.classList.add('btn-outline-secondary');
        }

        // Resetar readonly do veículo
        const veiculoInput = document.getElementById('tipo_veiculo');
        if (veiculoInput) {
            veiculoInput.readOnly = false;
            veiculoInput.classList.remove('bg-light');
        }

        this.setText('tituloMovForm', 'Nova Movimentação');
        this.setDefaultFormDate();
        this.openOffcanvas(this.offcanvasMov);
    }

    async openFormForEdit(id) {
        this.setLoading(true);

        try {
            const result = await this.post({ db_action: 'obter', id });
            const row = result.data || {};

            this.form?.reset();
            this.setValue('mov_id', row.id || '');
            this.setValue('veiculo_id', row.veiculo_id || '');
            this.setValue('empresa_id', row.empresa_id || '');
            this.setValue('motorista_id', row.motorista_id || '');
            this.setValue('data_movimentacao', row.data_movimentacao || '');
            this.setValue('hora_chegada', this.sliceTime(row.hora_chegada));
            this.setValue('hora_entrada', this.sliceTime(row.hora_entrada));
            this.setValue('hora_saida', this.sliceTime(row.hora_saida));
            this.setValue('placa_veiculo', row.placa_veiculo || '');
            this.setValue('tipo_veiculo', row.tipo_veiculo || '');
            this.setValue('empresa', row.empresa || '');
            this.setValue('motorista', row.motorista || '');
            this.setValue('observacoes', row.observacoes || '');
            this.setValue('cadastrado_por', row.cadastrado_por || '');

            this.setText('tituloMovForm', `Editar Movimentação #${row.id}`);
            this.openOffcanvas(this.offcanvasMov);
        } catch (error) {
            this.notifyError(error.message || 'Não foi possível carregar a movimentação.');
        } finally {
            this.setLoading(false);
        }
    }

    async saveMovimentacao() {
        if (!this.form) {
            return;
        }

        if (this.saving) {
            return; // Previne submissões duplas
        }

        const formData = new FormData(this.form);
        this.setLoading(true);
        this.saving = true;

        try {
            const payload = {};
            formData.forEach((value, key) => {
                payload[key] = value;
            });

            const result = await this.post(payload);
            this.notifySuccess(result.message || 'Movimentação salva com sucesso.');
            this.closeOffcanvas(this.offcanvasMov);
            await this.reloadAll();
        } catch (error) {
            this.notifyError(error.message || 'Erro ao salvar movimentação.');
        } finally {
            this.setLoading(false);
            this.saving = false;
        }
    }

    async deleteMovimentacao(id) {
        if (!window.confirm('Deseja realmente excluir esta movimentação?')) {
            return;
        }

        if (this.deleting) {
            return; // Previne exclusões duplas
        }

        this.setLoading(true);
        this.deleting = true;

        try {
            const result = await this.post({ db_action: 'excluir', id });
            this.notifySuccess(result.message || 'Movimentação excluída.');
            await this.reloadAll();
        } catch (error) {
            this.notifyError(error.message || 'Erro ao excluir movimentação.');
        } finally {
            this.setLoading(false);
            this.deleting = false;
        }
    }

    async gerarRelatorio() {
        const form = document.getElementById('relatorioForm');
        if (!form) {
            return;
        }

        const formData = new FormData(form);
        const payload = { db_action: 'gerar_relatorio' };

        formData.forEach((value, key) => {
            payload[key] = value;
        });

        this.setLoading(true);

        try {
            const result = await this.post(payload);
            const target = result.redirect ? `${this.basePath}/${String(result.redirect).replace(/^\//, '')}` : `${this.basePath}/paginas/eclusa_relatorio.php`;
            window.open(target, '_blank');
            this.closeOffcanvas(this.offcanvasRelatorio);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao gerar relatório.');
        } finally {
            this.setLoading(false);
        }
    }

    async showKpiDetails(kpi) {
        if (kpi === 'veiculos') {
            await this.showTopVeiculos();
            return;
        }

        if (kpi === 'empresas') {
            await this.showTopEmpresas();
            return;
        }

        const blocks = {
            total: 'Total de movimentações registradas na base.',
            hoje: 'Movimentações registradas na data atual.',
            entradas: 'Movimentações com horário de entrada preenchido hoje.',
            saidas: 'Movimentações com horário de saída preenchido hoje.',
        };

        this.lastKpiTitle = 'Resumo do Indicador';
        this.lastKpiContentHtml = `<p class="mb-0">${blocks[kpi] || 'Sem dados adicionais.'}</p>`;
        this.setText('kpiDetalhesTitulo', this.lastKpiTitle);
        document.getElementById('kpiDetalhesConteudo').innerHTML = this.lastKpiContentHtml;
        this.openOffcanvas(this.offcanvasKpi);
    }

    async showTopVeiculos() {
        this.setLoading(true);

        try {
            const result = await this.post({ db_action: 'get_top_veiculos' });
            const rows = Array.isArray(result.data) ? result.data : [];

            this.lastKpiTitle = 'Top 10 Veículos';
            this.lastKpiContentHtml = this.renderKpiTable(rows, [
                { key: 'placa', label: 'Placa' },
                { key: 'tipo_veiculo', label: 'Veículo' },
                { key: 'frequencia', label: 'Frequência' },
                { key: 'ultima_movimentacao', label: 'Última Mov.' },
            ]);
            this.setText('kpiDetalhesTitulo', this.lastKpiTitle);
            document.getElementById('kpiDetalhesConteudo').innerHTML = this.lastKpiContentHtml;
            this.openOffcanvas(this.offcanvasKpi);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao carregar top veículos.');
        } finally {
            this.setLoading(false);
        }
    }

    async showTopEmpresas() {
        this.setLoading(true);

        try {
            const result = await this.post({ db_action: 'get_top_empresas' });
            const rows = Array.isArray(result.data) ? result.data : [];

            this.lastKpiTitle = 'Top 10 Empresas';
            this.lastKpiContentHtml = this.renderKpiTable(rows, [
                { key: 'empresa', label: 'Empresa' },
                { key: 'frequencia', label: 'Frequência' },
                { key: 'ultima_movimentacao', label: 'Última Mov.' },
            ]);
            this.setText('kpiDetalhesTitulo', this.lastKpiTitle);
            document.getElementById('kpiDetalhesConteudo').innerHTML = this.lastKpiContentHtml;
            this.openOffcanvas(this.offcanvasKpi);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao carregar top empresas.');
        } finally {
            this.setLoading(false);
        }
    }

    renderKpiTable(rows, cols) {
        if (!rows.length) {
            return '<div class="text-muted">Nenhum dado encontrado.</div>';
        }

        const head = cols.map((col) => `<th>${this.escapeHtml(col.label)}</th>`).join('');
        const body = rows.map((row) => {
            const tds = cols.map((col) => {
                if (col.key === 'ultima_movimentacao') {
                    return `<td>${this.formatDateTime(row[col.key])}</td>`;
                }
                return `<td>${this.escapeHtml(row[col.key] ?? '-')}</td>`;
            }).join('');
            return `<tr>${tds}</tr>`;
        }).join('');

        return `
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr>${head}</tr></thead>
                    <tbody>${body}</tbody>
                </table>
            </div>
        `;
    }

    renderTipoBadge(tipo) {
        if (tipo === 'entrada') {
            return '<span class="badge badge-success">Entrada</span>';
        }
        if (tipo === 'saida') {
            return '<span class="badge badge-danger">Saída</span>';
        }
        if (tipo === 'entrada_saida') {
            return '<span class="badge badge-info">Entrada/Saída</span>';
        }
        return '<span class="badge badge-secondary">Indefinido</span>';
    }

    async post(payload) {
        let lastError = null;

        for (const url of this.endpointCandidates) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    },
                    body: new URLSearchParams(payload),
                });

                const raw = await response.text();
                const data = JSON.parse(raw);

                if (!response.ok || !data.success) {
                    throw new Error(data.message || `Falha na requisição (${response.status})`);
                }

                this.endpoint = url;
                return data;
            } catch (error) {
                lastError = error;
            }
        }

        throw new Error(lastError?.message || 'Não foi possível comunicar com o backend do módulo.');
    }

    buildEndpointCandidates() {
        const unique = new Set();
        const add = (value) => {
            if (value) {
                unique.add(value.replace(/([^:]\/)\/+/g, '$1'));
            }
        };

        add(`${this.basePath}/includes/eclusa_movimentacoes_logica.php`);
        add('includes/eclusa_movimentacoes_logica.php');
        add('../includes/eclusa_movimentacoes_logica.php');

        const path = window.location.pathname || '';
        const parts = path.split('/').filter(Boolean);
        if (parts.length > 0) {
            add(`/${parts[0]}/includes/eclusa_movimentacoes_logica.php`);
        }

        return Array.from(unique);
    }

    printKpiDetails() {
        if (!this.lastKpiContentHtml) {
            this.notifyError('Nenhum dado para impressão.');
            return;
        }

        const win = window.open('', '_blank', 'width=1024,height=768');
        if (!win) {
            this.notifyError('Não foi possível abrir a janela de impressão.');
            return;
        }

        const title = this.escapeHtml(this.lastKpiTitle || 'Relatório');
        const issuedAt = new Date().toLocaleString('pt-BR');
        win.document.write(`
            <!doctype html>
            <html lang="pt-BR">
            <head>
                <meta charset="utf-8">
                <title>${title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 24px; color: #1f2937; }
                    h1 { font-size: 22px; margin: 0 0 8px; }
                    .meta { color: #6b7280; font-size: 12px; margin-bottom: 16px; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid #d1d5db; padding: 8px; font-size: 12px; text-align: left; }
                    th { background: #f3f4f6; }
                    @media print { body { margin: 10mm; } }
                </style>
            </head>
            <body>
                <h1>${title}</h1>
                <div class="meta">Emitido em ${issuedAt} | SIGEP - Controle de Eclusa</div>
                ${this.lastKpiContentHtml}
            </body>
            </html>
        `);
        win.document.close();
        win.focus();
        win.print();
    }

    openOffcanvas(offcanvas) {
        if (!offcanvas) {
            return;
        }

        offcanvas.classList.add('show');
        document.body.classList.add('offcanvas-open');
    }

    closeOffcanvas(offcanvas) {
        if (!offcanvas) {
            return;
        }

        offcanvas.classList.remove('show');
        if (!document.querySelector('.offcanvas-eclusa.show')) {
            document.body.classList.remove('offcanvas-open');
        }
    }

    setLoading(enabled) {
        const overlay = document.getElementById('loadingOverlay');
        if (!overlay) {
            return;
        }
        overlay.classList.toggle('show', Boolean(enabled));
    }

    setDefaultFormDate() {
        if (!this.form) {
            return;
        }

        if (!this.getValue('data_movimentacao')) {
            const today = new Date().toISOString().slice(0, 10);
            this.setValue('data_movimentacao', today);
        }
    }

    notifySuccess(message) {
        if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 2200,
                showConfirmButton: false,
                icon: 'success',
                title: message,
            });
            return;
        }

        alert(message);
    }

    notifyError(message) {
        if (window.Swal) {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 2800,
                showConfirmButton: false,
                icon: 'error',
                title: message,
            });
            return;
        }

        alert(message);
    }

    setValue(name, value) {
        const element = this.form?.querySelector(`[name="${name}"]`) || document.querySelector(`[name="${name}"]`);
        if (element) {
            element.value = value;
        }
    }

    getValue(name) {
        const element = this.form?.querySelector(`[name="${name}"]`);
        return element ? element.value : '';
    }

    setText(id, text) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = String(text);
        }
    }

    formatDate(dateValue) {
        if (!dateValue) {
            return '-';
        }

        const [year, month, day] = String(dateValue).split('-');
        if (!year || !month || !day) {
            return this.escapeHtml(String(dateValue));
        }

        return `${day}/${month}/${year}`;
    }

    formatTime(timeValue) {
        if (!timeValue) {
            return '-';
        }

        return String(timeValue).slice(0, 5);
    }

    formatDateTime(value) {
        if (!value) {
            return '-';
        }

        const date = new Date(value.replace(' ', 'T'));
        if (Number.isNaN(date.getTime())) {
            return this.escapeHtml(String(value));
        }

        return date.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    sliceTime(value) {
        if (!value) {
            return '';
        }
        return String(value).slice(0, 5);
    }

    escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
}

// Inicialização para SPA (DOM já pronto) e carregamento normal
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.eclusaMov = new EclusaMovimentacoes();
    });
} else {
    // SPA: DOM já está pronto, inicializar imediatamente
    window.eclusaMov = new EclusaMovimentacoes();
}

})();
