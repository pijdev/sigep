(function() {
    'use strict';

class EclusaEscolta {
    constructor() {
        const base = (window.SIGEP_BASE_PATH || '').replace(/\/$/, '');
        this.basePath = base;

        // Para SPA, garantir que o endpoint funcione corretamente
        if (!this.basePath) {
            this.endpoint = 'includes/eclusa_escolta_logica.php';
        } else {
            this.endpoint = `${this.basePath}/includes/eclusa_escolta_logica.php`;
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

        this.form = document.getElementById('movimentacaoForm');
        this.offcanvasMov = document.getElementById('offcanvasMovimentacao');
        this.offcanvasRelatorio = document.getElementById('offcanvasRelatorio');
        this.offcanvasKpi = document.getElementById('offcanvasKpiDetalhes');

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
            this.loadEscoltas();
        });

        this.form?.addEventListener('submit', (event) => {
            event.preventDefault();
            this.saveEscolta();
        });

        document.getElementById('movPrev')?.addEventListener('click', () => {
            if (this.currentPage > 1) {
                this.currentPage -= 1;
                this.loadEscoltas();
            }
        });

        document.getElementById('movNext')?.addEventListener('click', () => {
            if (this.currentPage < this.totalPages) {
                this.currentPage += 1;
                this.loadEscoltas();
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
                this.deleteEscolta(id);
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
                const term = input.value.trim();
                clearTimeout(this.debounceTimers[field]);
                this.debounceTimers[field] = setTimeout(() => {
                    this.fetchAutocomplete(field, term, input);
                }, 300);
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
            list.style.cssText = 'position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; z-index: 1000;';
            container.appendChild(list);
        }

        list.innerHTML = '';
        if (items.length === 0) {
            list.style.display = 'none';
            return;
        }

        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.style.cssText = 'padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #eee;';
            div.textContent = item.label || item.value;
            div.addEventListener('click', () => {
                input.value = item.value;
                list.style.display = 'none';
            });
            div.addEventListener('mouseenter', () => {
                div.style.backgroundColor = '#f5f5f5';
            });
            div.addEventListener('mouseleave', () => {
                div.style.backgroundColor = 'white';
            });
            list.appendChild(div);
        });

        list.style.display = 'block';
    }

    hideAutocompleteResults(input) {
        const container = input.closest('.busca-inteligente');
        if (!container) {
            return;
        }

        const list = container.querySelector('.busca-inteligente-resultados');
        if (list) {
            list.style.display = 'none';
        }
    }

    performAutocomplete(input) {
        const field = input.getAttribute('data-campo');
        const term = input.value.trim();

        if (term.length >= 0) {
            this.fetchAutocomplete(field, term, input);
        }
    }

    async loadFilterOptions() {
        try {
            const [destinos, motoristas, placas, internos] = await Promise.all([
                this.post({ db_action: 'busca_autocomplete', campo: 'destino', termo: '' }),
                this.post({ db_action: 'busca_autocomplete', campo: 'motorista', termo: '' }),
                this.post({ db_action: 'busca_autocomplete', campo: 'placa', termo: '' }),
                this.post({ db_action: 'busca_autocomplete', campo: 'interno', termo: '' })
            ]);

            this.populateSelect('filtro_destino', destinos.data || []);
            this.populateSelect('filtro_motorista', motoristas.data || []);
            this.populateSelect('filtro_placa', placas.data || []);
            this.populateSelect('filtro_interno', internos.data || []);
        } catch (error) {
            console.error('Erro ao carregar filtros:', error);
        }
    }

    populateSelect(selectId, items) {
        const select = document.getElementById(selectId);
        if (!select) return;

        const currentValue = select.value;

        // Limpa opções exceto a primeira
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        items.forEach(item => {
            const option = document.createElement('option');
            option.value = item.value;
            option.textContent = item.label || item.value;
            select.appendChild(option);
        });

        select.value = currentValue;
    }

    setDefaultFormDate() {
        if (this.form) {
            const dataInput = this.form.querySelector('input[name="data_cadastro"]');
            if (dataInput && !dataInput.value) {
                dataInput.value = new Date().toISOString().split('T')[0];
            }
        }
    }

    async reloadAll() {
        await Promise.all([
            this.loadContadores(),
            this.loadEscoltas()
        ]);
    }

    async loadContadores() {
        try {
            const result = await this.post({ db_action: 'get_contadores' });
            const data = result.data || {};

            document.getElementById('kpiTotal').textContent = data.totalEscoltas || 0;
            document.getElementById('kpiHoje').textContent = data.escoltasHoje || 0;
            document.getElementById('kpiEntradas').textContent = data.finalizadasHoje || 0;
            document.getElementById('kpiSaidas').textContent = data.pendentesHoje || 0;
        } catch (error) {
            console.error('Erro ao carregar contadores:', error);
        }
    }

    async loadEscoltas() {
        try {
            const tbody = document.getElementById('movTableBody');
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="fas fa-spinner fa-spin mr-2"></i>Carregando...
                        </td>
                    </tr>
                `;
            }

            const result = await this.post({
                db_action: 'listar',
                page: this.currentPage,
                limit: 20,
                ...this.currentFilters
            });

            this.renderEscoltasTable(result.data);
            this.updatePagination(result.data);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao carregar escoltas.');
        }
    }

    renderEscoltasTable(data) {
        const tbody = document.getElementById('movTableBody');
        if (!tbody) return;

        const rows = data.dados || [];

        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle mr-2"></i>Nenhuma escolta encontrada.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = rows.map(row => this.renderEscoltaRow(row)).join('');
    }

    renderEscoltaRow(row) {
        const statusBadge = this.getStatusBadge(row.status);
        const notBadge = row.eh_not === 'Sim' ?
            '<span class="badge badge-warning">NOT</span>' :
            '<span class="badge badge-secondary">Normal</span>';

        return `
            <tr>
                <td>${this.formatDate(row.data_cadastro)}</td>
                <td>${row.interno || '-'}</td>
                <td>${row.destino || '-'}</td>
                <td>${row.motorista || '-'}</td>
                <td>${row.placa || '-'}</td>
                <td>${statusBadge}</td>
                <td>${row.hora_prevista || '-'}</td>
                <td>${row.hora_chegada || '-'}</td>
                <td>${row.hora_retorno || '-'}</td>
                <td>${notBadge}</td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${row.id}" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${row.id}" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    getStatusBadge(status) {
        const badges = {
            'Pendente': '<span class="badge badge-warning">Pendente</span>',
            'Finalizado': '<span class="badge badge-success">Finalizado</span>',
            'Cancelado': '<span class="badge badge-danger">Cancelado</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Desconhecido</span>';
    }

    updatePagination(data) {
        this.currentPage = data.pagina || 1;
        this.totalPages = data.total_paginas || 1;

        const pageInfo = document.getElementById('movPageInfo');
        if (pageInfo) {
            pageInfo.textContent = `Página ${this.currentPage} de ${this.totalPages} (${data.total} registros)`;
        }

        const prevBtn = document.getElementById('movPrev');
        const nextBtn = document.getElementById('movNext');

        if (prevBtn) prevBtn.disabled = this.currentPage <= 1;
        if (nextBtn) nextBtn.disabled = this.currentPage >= this.totalPages;
    }

    applyFilters() {
        const form = document.getElementById('filtrosMovForm');
        if (!form) return;

        const formData = new FormData(form);
        this.currentFilters = {};

        for (const [key, value] of formData.entries()) {
            if (value.trim()) {
                this.currentFilters[key] = value.trim();
            }
        }

        this.currentPage = 1;
        this.loadEscoltas();
    }

    openFormForCreate() {
        if (!this.form) return;

        this.form.reset();
        this.form.querySelector('input[name="db_action"]').value = 'salvar';
        this.form.querySelector('input[name="id"]').value = '';
        document.getElementById('tituloMovForm').textContent = 'Nova Escolta';

        this.setDefaultFormDate();
        this.openOffcanvas(this.offcanvasMov);
    }

    async openFormForEdit(id) {
        try {
            const result = await this.post({ db_action: 'obter', id });
            const escolta = result.data;

            if (!escolta) {
                this.notifyError('Escolta não encontrada.');
                return;
            }

            this.populateForm(escolta);
            document.getElementById('tituloMovForm').textContent = 'Editar Escolta';
            this.openOffcanvas(this.offcanvasMov);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao carregar escolta.');
        }
    }

    populateForm(data) {
        if (!this.form) return;

        this.form.querySelector('input[name="db_action"]').value = 'salvar';
        this.form.querySelector('input[name="id"]').value = data.id || '';
        this.form.querySelector('input[name="data_cadastro"]').value = data.data_cadastro || '';
        this.form.querySelector('input[name="interno"]').value = data.interno || '';
        this.form.querySelector('input[name="destino"]').value = data.destino || '';
        this.form.querySelector('select[name="status"]').value = data.status || 'Pendente';
        this.form.querySelector('input[name="hora_prevista"]').value = data.hora_prevista || '';
        this.form.querySelector('input[name="motivo"]').value = data.motivo || '';
        this.form.querySelector('input[name="placa"]').value = data.placa || '';
        this.form.querySelector('input[name="motorista"]').value = data.motorista || '';
        this.form.querySelector('select[name="eh_not"]').value = data.eh_not || 'Não';
        this.form.querySelector('input[name="cadastrado_por"]').value = data.cadastrado_por || '';
    }

    async saveEscolta() {
        if (this.saving) return;

        try {
            this.saving = true;
            this.showLoading(true);

            const formData = new FormData(this.form);
            const data = Object.fromEntries(formData.entries());

            const result = await this.post(data);
            this.notifySuccess(result.message || 'Escolta salva com sucesso.');

            this.closeOffcanvas(this.offcanvasMov);
            await this.reloadAll();
        } catch (error) {
            this.notifyError(error.message || 'Erro ao salvar escolta.');
        } finally {
            this.saving = false;
            this.showLoading(false);
        }
    }

    async deleteEscolta(id) {
        if (this.deleting) return;

        if (!confirm('Tem certeza que deseja excluir esta escolta?')) {
            return;
        }

        try {
            this.deleting = true;
            this.showLoading(true);

            const result = await this.post({ db_action: 'excluir', id });
            this.notifySuccess(result.message || 'Escolta excluída com sucesso.');

            await this.reloadAll();
        } catch (error) {
            this.notifyError(error.message || 'Erro ao excluir escolta.');
        } finally {
            this.deleting = false;
            this.showLoading(false);
        }
    }

    async gerarRelatorio() {
        try {
            this.showLoading(true);

            const form = document.getElementById('relatorioForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            const result = await this.post({
                db_action: 'gerar_relatorio',
                ...data
            });

            this.notifySuccess(result.message || 'Relatório gerado com sucesso.');

            if (result.redirect) {
                window.location.href = this.basePath + '/' + result.redirect;
            }
        } catch (error) {
            this.notifyError(error.message || 'Erro ao gerar relatório.');
        } finally {
            this.showLoading(false);
        }
    }

    async showKpiDetails(kpi) {
        try {
            let data, title;

            switch(kpi) {
                case 'total':
                    const resultTotal = await this.post({ db_action: 'get_contadores' });
                    data = resultTotal.data;
                    title = 'Total de Escoltas';
                    break;
                case 'veiculos':
                    const resultDestinos = await this.post({ db_action: 'get_top_destinos' });
                    data = resultDestinos.data;
                    title = 'Top Destinos';
                    break;
                case 'empresas':
                    const resultMotoristas = await this.post({ db_action: 'get_top_motoristas' });
                    data = resultMotoristas.data;
                    title = 'Top Motoristas';
                    break;
                default:
                    return;
            }

            this.renderKpiDetails(title, data);
            this.openOffcanvas(this.offcanvasKpi);
        } catch (error) {
            this.notifyError(error.message || 'Erro ao carregar detalhes.');
        }
    }

    renderKpiDetails(title, data) {
        const content = document.getElementById('kpiDetalhesConteudo');
        const titleElement = document.getElementById('kpiDetalhesTitulo');

        if (titleElement) titleElement.textContent = title;
        if (!content) return;

        let html = '<div class="list-group">';

        if (Array.isArray(data)) {
            data.forEach(item => {
                const label = item.destino || item.motorista || item.value || 'Desconhecido';
                const count = item.frequencia || 0;
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${label}</span>
                        <span class="badge badge-primary badge-pill">${count}</span>
                    </div>
                `;
            });
        } else {
            Object.entries(data).forEach(([key, value]) => {
                const label = this.formatKpiLabel(key);
                html += `
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${label}</span>
                        <span class="badge badge-primary badge-pill">${value}</span>
                    </div>
                `;
            });
        }

        html += '</div>';
        content.innerHTML = html;

        this.lastKpiTitle = title;
        this.lastKpiContentHtml = html;
    }

    formatKpiLabel(key) {
        const labels = {
            'totalEscoltas': 'Total de Escoltas',
            'escoltasHoje': 'Escoltas Hoje',
            'finalizadasHoje': 'Finalizadas Hoje',
            'pendentesHoje': 'Pendentes Hoje',
            'escoltasMes': 'Escoltas no Mês',
            'totalNot': 'Total NOT'
        };
        return labels[key] || key;
    }

    printKpiDetails() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>${this.lastKpiTitle}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .list-group { border: 1px solid #ddd; }
                        .list-group-item { padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
                        .badge { background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; }
                        h1 { color: #333; }
                    </style>
                </head>
                <body>
                    <h1>${this.lastKpiTitle}</h1>
                    ${this.lastKpiContentHtml}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }

    openOffcanvas(offcanvas) {
        if (!offcanvas) return;

        offcanvas.classList.add('show');
        document.body.classList.add('modal-open');

        // Criar backdrop
        let backdrop = document.querySelector('.offcanvas-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'offcanvas-backdrop';
            backdrop.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1040;';
            document.body.appendChild(backdrop);
        }

        backdrop.addEventListener('click', () => this.closeOffcanvas(offcanvas));
    }

    closeOffcanvas(offcanvas) {
        if (!offcanvas) return;

        offcanvas.classList.remove('show');
        document.body.classList.remove('modal-open');

        const backdrop = document.querySelector('.offcanvas-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = show ? 'flex' : 'none';
        }
    }

    notifySuccess(message) {
        this.showToast(message, 'success');
    }

    notifyError(message) {
        this.showToast(message, 'error');
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 5000);

        toast.querySelector('.close')?.addEventListener('click', () => {
            toast.remove();
        });
    }

    formatDate(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('pt-BR');
    }

    buildEndpointCandidates() {
        const candidates = [
            this.endpoint,
            `${window.location.origin}/${this.endpoint}`,
            `${window.location.origin}/sigep/${this.endpoint}`
        ];

        return candidates.filter((url, index, self) => self.indexOf(url) === index);
    }

    async post(data) {
        const formData = new FormData();
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        let lastError;
        for (const url of this.endpointCandidates) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erro na resposta do servidor');
                }

                return result;
            } catch (error) {
                lastError = error;
                continue;
            }
        }

        throw lastError || new Error('Não foi possível conectar ao servidor');
    }
}

// Inicializar quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => new EclusaEscolta());
} else {
    new EclusaEscolta();
}

})();
