(function() {
    'use strict';

    class EclusaMovimentacoes {
        constructor() {
            const base = (window.SIGEP_BASE_PATH || '').replace(/\/$/, '');
            this.basePath = base;

            // Para SPA, garantir que o endpoint funcione corretamente
            if (!this.basePath) {
                this.endpoint = '/modulos/eclusa/movimentacoes/movimentacoes_logica.php';
            } else {
                this.endpoint = `${this.basePath}/modulos/eclusa/movimentacoes/movimentacoes_logica.php`;
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
                    const kpi = card.dataset.kpi;
                    this.showKpiDetails(kpi);
                });
            });

            // Botões para novo cadastro
            this.btnNovoVeiculo?.addEventListener('click', () => this.openNovoVeiculoForm());
            this.btnNovaEmpresa?.addEventListener('click', () => this.openNovaEmpresaForm());
            this.btnNovoMotorista?.addEventListener('click', () => this.openNovoMotoristaForm());

            // Fechar offcanvas com ESC
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    const openOffcanvas = document.querySelector('.offcanvas-eclusa.show');
                    if (openOffcanvas) {
                        this.closeOffcanvas(openOffcanvas);
                    }
                }
            });

            // Fechar offcanvas clicando fora
            document.addEventListener('click', (event) => {
                if (event.target.classList.contains('offcanvas-eclusa') ||
                    event.target.classList.contains('loading-overlay')) {
                    const openOffcanvas = document.querySelector('.offcanvas-eclusa.show');
                    if (openOffcanvas) {
                        this.closeOffcanvas(openOffcanvas);
                    }
                }
            });
        }

        bindAutocomplete() {
            const campos = ['placa', 'veiculo', 'empresa', 'motorista'];

            campos.forEach(campo => {
                const input = document.querySelector(`input[data-campo="${campo}"]`);
                if (!input) return;

                const resultadosDiv = document.createElement('div');
                resultadosDiv.className = 'busca-inteligente-resultados';
                input.parentNode.appendChild(resultadosDiv);

                input.addEventListener('input', (event) => {
                    this.debounceAutocomplete(campo, event.target.value, resultadosDiv);
                });

                input.addEventListener('focus', () => {
                    if (input.value.trim().length >= 1) {
                        this.debounceAutocomplete(campo, input.value, resultadosDiv);
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!event.target.closest('.busca-inteligente')) {
                        resultadosDiv.style.display = 'none';
                    }
                });
            });
        }

        debounceAutocomplete(campo, termo, resultadosDiv) {
            clearTimeout(this.debounceTimers[campo]);
            this.debounceTimers[campo] = setTimeout(() => {
                this.buscarAutocomplete(campo, termo, resultadosDiv);
            }, 300);
        }

        async buscarAutocomplete(campo, termo, resultadosDiv) {
            try {
                const formData = new FormData();
                formData.append('db_action', 'busca_autocomplete');
                formData.append('campo', campo);
                formData.append('termo', termo);

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data && data.data.length > 0) {
                    this.renderAutocompleteResults(data.data, resultadosDiv, campo);
                } else {
                    resultadosDiv.style.display = 'none';
                }
            } catch (error) {
                console.error('Erro no autocomplete:', error);
                resultadosDiv.style.display = 'none';
            }
        }

        renderAutocompleteResults(items, resultadosDiv, campo) {
            resultadosDiv.innerHTML = '';

            items.forEach(item => {
                const div = document.createElement('div');
                div.className = 'busca-inteligente-item';
                div.textContent = item.label;

                div.addEventListener('click', () => {
                    this.selectAutocompleteItem(item, campo);
                    resultadosDiv.style.display = 'none';
                });

                resultadosDiv.appendChild(div);
            });

            resultadosDiv.style.display = 'block';
        }

        selectAutocompleteItem(item, campo) {
            const input = document.querySelector(`input[data-campo="${campo}"]`);
            if (!input) return;

            input.value = item.label;

            // Preencher campos hidden
            if (campo === 'placa') {
                document.getElementById('veiculo_id').value = item.id || '';
                document.getElementById('placa_veiculo').value = item.value || '';

                // Preencher veículo se disponível
                if (item.tipo_veiculo) {
                    const veiculoInput = document.querySelector('input[data-campo="veiculo"]');
                    if (veiculoInput) veiculoInput.value = item.tipo_veiculo;
                }

                // Preencher empresa se disponível
                if (item.empresa_id) {
                    document.getElementById('empresa_id').value = item.empresa_id;
                    // Buscar nome da empresa
                    this.buscarNomeEmpresa(item.empresa_id);
                }
            } else if (campo === 'veiculo') {
                document.getElementById('veiculo_id').value = item.id || '';
                document.getElementById('tipo_veiculo').value = item.value || '';

                // Preencher placa se disponível
                if (item.placa) {
                    const placaInput = document.querySelector('input[data-campo="placa"]');
                    if (placaInput) placaInput.value = item.placa;
                }

                // Preencher empresa se disponível
                if (item.empresa_id) {
                    document.getElementById('empresa_id').value = item.empresa_id;
                    this.buscarNomeEmpresa(item.empresa_id);
                }
            } else if (campo === 'empresa') {
                document.getElementById('empresa_id').value = item.id || '';
                document.getElementById('empresa').value = item.value || '';
            } else if (campo === 'motorista') {
                document.getElementById('motorista_id').value = item.id || '';
                document.getElementById('motorista').value = item.value || '';
            }
        }

        async buscarNomeEmpresa(empresaId) {
            try {
                const formData = new FormData();
                formData.append('db_action', 'busca_autocomplete');
                formData.append('campo', 'empresa');
                formData.append('termo', '');

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    const empresa = data.data.find(e => e.id == empresaId);
                    if (empresa) {
                        document.getElementById('empresa').value = empresa.value;
                    }
                }
            } catch (error) {
                console.error('Erro ao buscar nome da empresa:', error);
            }
        }

        setDefaultFormDate() {
            const dataInput = this.form?.querySelector('input[name="data_movimentacao"]');
            if (dataInput && !dataInput.value) {
                dataInput.value = new Date().toISOString().split('T')[0];
            }
        }

        async loadFilterOptions() {
            try {
                const formData = new FormData();
                formData.append('db_action', 'busca_autocomplete');
                formData.append('campo', 'placa');
                formData.append('termo', '');

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    this.populateFilterSelects(data.data);
                }
            } catch (error) {
                console.error('Erro ao carregar filtros:', error);
            }
        }

        populateFilterSelects(data) {
            const placas = [...new Set(data.map(item => item.placa).filter(Boolean))];
            const veiculos = [...new Set(data.map(item => item.tipo_veiculo).filter(Boolean))];
            const empresas = [...new Set(data.map(item => item.empresa).filter(Boolean))];
            const motoristas = [...new Set(data.map(item => item.motorista).filter(Boolean))];

            this.populateSelect('filtro_placa', placas);
            this.populateSelect('filtro_veiculo', veiculos);
            this.populateSelect('filtro_empresa', empresas);
            this.populateSelect('filtro_motorista', motoristas);
        }

        populateSelect(selectId, options) {
            const select = document.getElementById(selectId);
            if (!select) return;

            // Limpar opções exceto a primeira
            while (select.children.length > 1) {
                select.removeChild(select.lastChild);
            }

            options.forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option;
                select.appendChild(optionElement);
            });
        }

        async reloadAll() {
            await Promise.all([
                this.loadContadores(),
                this.loadTopVeiculos(),
                this.loadTopEmpresas(),
                this.loadMovimentacoes()
            ]);
        }

        async loadContadores() {
            try {
                const formData = new FormData();
                formData.append('db_action', 'get_contadores');

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    this.updateContadores(data.data);
                }
            } catch (error) {
                console.error('Erro ao carregar contadores:', error);
            }
        }

        updateContadores(contadores) {
            document.getElementById('kpiTotal').textContent = contadores.totalMovimentacoes || 0;
            document.getElementById('kpiHoje').textContent = contadores.movimentacoesHoje || 0;
            document.getElementById('kpiEntradas').textContent = contadores.entradasHoje || 0;
            document.getElementById('kpiSaidas').textContent = contadores.saidasHoje || 0;
        }

        async loadTopVeiculos() {
            try {
                const formData = new FormData();
                formData.append('db_action', 'get_top_veiculos');

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    document.getElementById('kpiVeiculos').textContent = data.data.length;
                }
            } catch (error) {
                console.error('Erro ao carregar top veículos:', error);
            }
        }

        async loadTopEmpresas() {
            try {
                const formData = new FormData();
                formData.append('db_action', 'get_top_empresas');

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    document.getElementById('kpiEmpresas').textContent = data.data.length;
                }
            } catch (error) {
                console.error('Erro ao carregar top empresas:', error);
            }
        }

        async loadMovimentacoes() {
            try {
                this.showLoading();

                const formData = new FormData();
                formData.append('db_action', 'listar');
                formData.append('page', this.currentPage);

                Object.keys(this.currentFilters).forEach(key => {
                    formData.append(key, this.currentFilters[key]);
                });

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    this.renderMovimentacoes(data.data);
                    this.updatePagination(data.data);
                } else {
                    this.showError('Erro ao carregar movimentações');
                }
            } catch (error) {
                console.error('Erro ao carregar movimentações:', error);
                this.showError('Erro ao carregar movimentações');
            } finally {
                this.hideLoading();
            }
        }

        renderMovimentacoes(data) {
            const tbody = document.getElementById('movTableBody');
            if (!tbody) return;

            if (!data.dados || data.dados.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox mr-2"></i>Nenhuma movimentação encontrada
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = data.dados.map(mov => `
                <tr>
                    <td>${this.formatDate(mov.data_movimentacao)}</td>
                    <td>${mov.hora_chegada || '-'}</td>
                    <td>${mov.hora_entrada || '-'}</td>
                    <td>${mov.hora_saida || '-'}</td>
                    <td>${mov.placa_veiculo || '-'}</td>
                    <td>${mov.tipo_veiculo || '-'}</td>
                    <td>${mov.empresa || '-'}</td>
                    <td>${mov.motorista || '-'}</td>
                    <td>${this.getTipoBadge(mov.tipo_movimento)}</td>
                    <td>${mov.cadastrado_por || '-'}</td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="window.eclusaMov.editarMovimentacao(${mov.id})" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="window.eclusaMov.excluirMovimentacao(${mov.id})" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        getTipoBadge(tipo) {
            const badges = {
                'entrada': '<span class="badge badge-success">Entrada</span>',
                'saida': '<span class="badge badge-danger">Saída</span>',
                'entrada_saida': '<span class="badge badge-info">Entrada/Saída</span>',
                'indefinido': '<span class="badge badge-secondary">Indefinido</span>'
            };
            return badges[tipo] || badges.indefinido;
        }

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('pt-BR');
        }

        updatePagination(data) {
            this.currentPage = data.pagina;
            this.totalPages = data.total_paginas;

            document.getElementById('movPageInfo').textContent =
                `Página ${this.currentPage} de ${this.totalPages} (${data.total} registros)`;

            document.getElementById('movPrev').disabled = this.currentPage <= 1;
            document.getElementById('movNext').disabled = this.currentPage >= this.totalPages;
        }

        applyFilters() {
            const form = document.getElementById('filtrosMovForm');
            if (!form) return;

            const formData = new FormData(form);
            this.currentFilters = {};

            for (const [key, value] of formData.entries()) {
                if (value.trim()) {
                    this.currentFilters[key] = value;
                }
            }

            this.currentPage = 1;
            this.loadMovimentacoes();
        }

        openFormForCreate() {
            if (this.form) {
                this.form.reset();
                this.form.querySelector('input[name="db_action"]').value = 'salvar';
                this.form.querySelector('input[name="id"]').value = '';
                this.setDefaultFormDate();

                // Limpar campos hidden
                document.getElementById('veiculo_id').value = '';
                document.getElementById('empresa_id').value = '';
                document.getElementById('motorista_id').value = '';

                // Resetar modos de edição
                this.editandoVeiculo = false;
                this.editandoEmpresa = false;
                this.editandoMotorista = false;

                // Resetar textos dos botões
                if (this.btnNovoVeiculo) this.btnNovoVeiculo.innerHTML = '<i class="fas fa-plus"></i>';
                if (this.btnNovaEmpresa) this.btnNovaEmpresa.innerHTML = '<i class="fas fa-plus"></i>';
                if (this.btnNovoMotorista) this.btnNovoMotorista.innerHTML = '<i class="fas fa-plus"></i>';

                document.getElementById('tituloMovForm').textContent = 'Nova Movimentação';
            }

            this.openOffcanvas(this.offcanvasMov);
        }

        async editarMovimentacao(id) {
            try {
                const formData = new FormData();
                formData.append('db_action', 'obter');
                formData.append('id', id);

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    this.preencherFormulario(data.data);
                    document.getElementById('tituloMovForm').textContent = 'Editar Movimentação';
                    this.openOffcanvas(this.offcanvasMov);
                } else {
                    this.showError('Movimentação não encontrada');
                }
            } catch (error) {
                console.error('Erro ao carregar movimentação:', error);
                this.showError('Erro ao carregar movimentação');
            }
        }

        preencherFormulario(mov) {
            if (!this.form) return;

            this.form.querySelector('input[name="db_action"]').value = 'salvar';
            this.form.querySelector('input[name="id"]').value = mov.id;
            this.form.querySelector('input[name="data_movimentacao"]').value = mov.data_movimentacao || '';
            this.form.querySelector('input[name="hora_chegada"]').value = mov.hora_chegada || '';
            this.form.querySelector('input[name="hora_entrada"]').value = mov.hora_entrada || '';
            this.form.querySelector('input[name="hora_saida"]').value = mov.hora_saida || '';
            this.form.querySelector('input[name="observacoes"]').value = mov.observacoes || '';
            this.form.querySelector('input[name="cadastrado_por"]').value = mov.cadastrado_por || '';

            // Preencher campos de autocomplete
            document.getElementById('veiculo_id').value = mov.veiculo_id || '';
            document.getElementById('empresa_id').value = mov.empresa_id || '';
            document.getElementById('motorista_id').value = mov.motorista_id || '';

            const placaInput = document.querySelector('input[data-campo="placa"]');
            if (placaInput && mov.placa_veiculo) placaInput.value = mov.placa_veiculo;

            const veiculoInput = document.querySelector('input[data-campo="veiculo"]');
            if (veiculoInput && mov.tipo_veiculo) veiculoInput.value = mov.tipo_veiculo;

            const empresaInput = document.querySelector('input[data-campo="empresa"]');
            if (empresaInput && mov.empresa) empresaInput.value = mov.empresa;

            const motoristaInput = document.querySelector('input[data-campo="motorista"]');
            if (motoristaInput && mov.motorista) motoristaInput.value = mov.motorista;
        }

        async saveMovimentacao() {
            if (this.saving) return;

            try {
                this.saving = true;
                this.showLoading();

                const formData = new FormData(this.form);

                // Se estiver editando veículo, empresa ou motorista, tratar como novo cadastro
                if (this.editandoVeiculo) {
                    await this.salvarNovoVeiculo(formData);
                    return;
                }

                if (this.editandoEmpresa) {
                    await this.salvarNovaEmpresa(formData);
                    return;
                }

                if (this.editandoMotorista) {
                    await this.salvarNovoMotorista(formData);
                    return;
                }

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message || 'Movimentação salva com sucesso');
                    this.closeOffcanvas(this.offcanvasMov);
                    this.reloadAll();
                } else {
                    this.showError(data.message || 'Erro ao salvar movimentação');
                }
            } catch (error) {
                console.error('Erro ao salvar movimentação:', error);
                this.showError('Erro ao salvar movimentação');
            } finally {
                this.saving = false;
                this.hideLoading();
            }
        }

        async salvarNovoVeiculo(formData) {
            formData.set('db_action', 'salvar_veiculo');
            formData.set('placa', formData.get('placa_veiculo'));
            formData.set('nome', formData.get('tipo_veiculo'));
            formData.set('modelo', formData.get('tipo_veiculo'));

            const response = await this.fetchWithRetry(formData);
            const data = await response.json();

            if (data.success) {
                this.showSuccess('Veículo cadastrado com sucesso');
                this.editandoVeiculo = false;

                // Resetar botão
                if (this.btnNovoVeiculo) {
                    this.btnNovoVeiculo.innerHTML = '<i class="fas fa-plus"></i>';
                }

                // Preencher campos com novo veículo
                document.getElementById('veiculo_id').value = data.data.id;

                // Recarregar filtros
                this.loadFilterOptions();
            } else {
                this.showError(data.message || 'Erro ao cadastrar veículo');
            }
        }

        async salvarNovaEmpresa(formData) {
            formData.set('db_action', 'salvar_empresa');

            const response = await this.fetchWithRetry(formData);
            const data = await response.json();

            if (data.success) {
                this.showSuccess('Empresa cadastrada com sucesso');
                this.editandoEmpresa = false;

                // Resetar botão
                if (this.btnNovaEmpresa) {
                    this.btnNovaEmpresa.innerHTML = '<i class="fas fa-plus"></i>';
                }

                // Preencher campos com nova empresa
                document.getElementById('empresa_id').value = data.data.id;

                // Recarregar filtros
                this.loadFilterOptions();
            } else {
                this.showError(data.message || 'Erro ao cadastrar empresa');
            }
        }

        async salvarNovoMotorista(formData) {
            formData.set('db_action', 'salvar_motorista');

            const response = await this.fetchWithRetry(formData);
            const data = await response.json();

            if (data.success) {
                this.showSuccess('Motorista cadastrado com sucesso');
                this.editandoMotorista = false;

                // Resetar botão
                if (this.btnNovoMotorista) {
                    this.btnNovoMotorista.innerHTML = '<i class="fas fa-plus"></i>';
                }

                // Preencher campos com novo motorista
                document.getElementById('motorista_id').value = data.data.id;

                // Recarregar filtros
                this.loadFilterOptions();
            } else {
                this.showError(data.message || 'Erro ao cadastrar motorista');
            }
        }

        openNovoVeiculoForm() {
            this.editandoVeiculo = true;
            this.editandoEmpresa = false;
            this.editandoMotorista = false;

            // Limpar campos
            const placaInput = document.querySelector('input[data-campo="placa"]');
            const veiculoInput = document.querySelector('input[data-campo="veiculo"]');

            if (placaInput) placaInput.value = '';
            if (veiculoInput) veiculoInput.value = '';

            // Mudar botão
            if (this.btnNovoVeiculo) {
                this.btnNovoVeiculo.innerHTML = '<i class="fas fa-save"></i>';
            }

            // Focar na placa
            if (placaInput) placaInput.focus();
        }

        openNovaEmpresaForm() {
            this.editandoVeiculo = false;
            this.editandoEmpresa = true;
            this.editandoMotorista = false;

            // Limpar campo
            const empresaInput = document.querySelector('input[data-campo="empresa"]');
            if (empresaInput) empresaInput.value = '';

            // Mudar botão
            if (this.btnNovaEmpresa) {
                this.btnNovaEmpresa.innerHTML = '<i class="fas fa-save"></i>';
            }

            // Focar na empresa
            if (empresaInput) empresaInput.focus();
        }

        openNovoMotoristaForm() {
            this.editandoVeiculo = false;
            this.editandoEmpresa = false;
            this.editandoMotorista = true;

            // Limpar campo
            const motoristaInput = document.querySelector('input[data-campo="motorista"]');
            if (motoristaInput) motoristaInput.value = '';

            // Mudar botão
            if (this.btnNovoMotorista) {
                this.btnNovoMotorista.innerHTML = '<i class="fas fa-save"></i>';
            }

            // Focar no motorista
            if (motoristaInput) motoristaInput.focus();
        }

        async excluirMovimentacao(id) {
            if (!confirm('Tem certeza que deseja excluir esta movimentação?')) {
                return;
            }

            try {
                this.showLoading();

                const formData = new FormData();
                formData.append('db_action', 'excluir');
                formData.append('id', id);

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success) {
                    this.showSuccess(data.message || 'Movimentação excluída com sucesso');
                    this.reloadAll();
                } else {
                    this.showError(data.message || 'Erro ao excluir movimentação');
                }
            } catch (error) {
                console.error('Erro ao excluir movimentação:', error);
                this.showError('Erro ao excluir movimentação');
            } finally {
                this.hideLoading();
            }
        }

        async gerarRelatorio() {
            try {
                this.showLoading();

                const form = document.getElementById('relatorioForm');
                const formData = new FormData(form);
                formData.append('db_action', 'gerar_relatorio');

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success) {
                    this.showSuccess('Relatório gerado com sucesso');
                    this.closeOffcanvas(this.offcanvasRelatorio);

                    // Redirecionar para página de relatório
                    if (data.redirect) {
                        window.location.href = '/eclusa/movimentacoes/relatorio/';
                    }
                } else {
                    this.showError(data.message || 'Erro ao gerar relatório');
                }
            } catch (error) {
                console.error('Erro ao gerar relatório:', error);
                this.showError('Erro ao gerar relatório');
            } finally {
                this.hideLoading();
            }
        }

        async showKpiDetails(kpi) {
            try {
                this.showLoading();

                const formData = new FormData();
                formData.append('db_action', 'get_kpi_detalhes');
                formData.append('kpi', kpi);

                const response = await this.fetchWithRetry(formData);
                const data = await response.json();

                if (data.success && data.data) {
                    document.getElementById('kpiDetalhesTitulo').textContent = data.titulo || 'Detalhes';

                    // Renderizar conteúdo baseado no tipo de KPI
                    let content = '';

                    switch (kpi) {
                        case 'total':
                            content = this.renderKpiTotal(data.data);
                            break;
                        case 'hoje':
                            content = this.renderKpiHoje(data.data);
                            break;
                        case 'entradas':
                            content = this.renderKpiEntradas(data.data);
                            break;
                        case 'saidas':
                            content = this.renderKpiSaidas(data.data);
                            break;
                        case 'veiculos':
                            content = this.renderKpiVeiculos(data.data);
                            break;
                        case 'empresas':
                            content = this.renderKpiEmpresas(data.data);
                            break;
                        default:
                            content = '<div class="text-center py-4"><p class="text-muted">Dados não disponíveis</p></div>';
                    }

                    document.getElementById('kpiDetalhesConteudo').innerHTML = content;
                    this.openOffcanvas(this.offcanvasKpi);
                } else {
                    this.showError('Erro ao carregar detalhes do KPI');
                }
            } catch (error) {
                console.error('Erro ao carregar detalhes do KPI:', error);
                this.showError('Erro ao carregar detalhes');
            } finally {
                this.hideLoading();
            }
        }

        renderKpiTotal(data) {
            if (!data.mensal || data.mensal.length === 0) {
                return '<div class="text-center py-4"><p class="text-muted">Nenhum dado encontrado</p></div>';
            }

            let rows = data.mensal.map(item => `
                <tr>
                    <td>${item.mes}</td>
                    <td class="text-right">${item.quantidade}</td>
                </tr>
            `).join('');

            return `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mês</th>
                                <th class="text-right">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Dados dos últimos 12 meses</small>
                </div>
            `;
        }

        renderKpiHoje(data) {
            if (!data.tipos || data.tipos.length === 0) {
                return '<div class="text-center py-4"><p class="text-muted">Nenhuma movimentação hoje</p></div>';
            }

            let rows = data.tipos.map(item => {
                const tipoLabel = {
                    'entrada': '<span class="badge badge-success">Entrada</span>',
                    'saida': '<span class="badge badge-danger">Saída</span>',
                    'entrada_saida': '<span class="badge badge-info">Entrada/Saída</span>',
                    'indefinido': '<span class="badge badge-secondary">Indefinido</span>'
                };
                return `
                    <tr>
                        <td>${tipoLabel[item.tipo] || item.tipo}</td>
                        <td class="text-right">${item.quantidade}</td>
                    </tr>
                `;
            }).join('');

            return `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th class="text-right">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
        }

        renderKpiEntradas(data) {
            if (!data.entradas || data.entradas.length === 0) {
                return '<div class="text-center py-4"><p class="text-muted">Nenhuma entrada hoje</p></div>';
            }

            let rows = data.entradas.map(item => `
                <tr>
                    <td>${item.hora_entrada}</td>
                    <td>${item.placa}</td>
                    <td>${item.veiculo}</td>
                    <td>${item.empresa}</td>
                    <td>${item.motorista}</td>
                </tr>
            `).join('');

            return `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Placa</th>
                                <th>Veículo</th>
                                <th>Empresa</th>
                                <th>Motorista</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
        }

        renderKpiSaidas(data) {
            if (!data.saidas || data.saidas.length === 0) {
                return '<div class="text-center py-4"><p class="text-muted">Nenhuma saída hoje</p></div>';
            }

            let rows = data.saidas.map(item => `
                <tr>
                    <td>${item.hora_saida}</td>
                    <td>${item.placa}</td>
                    <td>${item.veiculo}</td>
                    <td>${item.empresa}</td>
                    <td>${item.motorista}</td>
                </tr>
            `).join('');

            return `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Placa</th>
                                <th>Veículo</th>
                                <th>Empresa</th>
                                <th>Motorista</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
            `;
        }

        renderKpiVeiculos(data) {
            if (!data.top_veiculos || data.top_veiculos.length === 0) {
                return '<div class="text-center py-4"><p class="text-muted">Nenhum dado encontrado</p></div>';
            }

            let rows = data.top_veiculos.map(item => `
                <tr>
                    <td>${item.placa}</td>
                    <td>${item.tipo_veiculo}</td>
                    <td class="text-right">${item.frequencia}</td>
                    <td>${item.ultima_movimentacao ? new Date(item.ultima_movimentacao).toLocaleString('pt-BR') : '-'}</td>
                </tr>
            `).join('');

            return `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Veículo</th>
                                <th class="text-right">Frequência</th>
                                <th>Última Movimentação</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Top 10 veículos mais frequentes</small>
                </div>
            `;
        }

        renderKpiEmpresas(data) {
            if (!data.top_empresas || data.top_empresas.length === 0) {
                return '<div class="text-center py-4"><p class="text-muted">Nenhum dado encontrado</p></div>';
            }

            let rows = data.top_empresas.map(item => `
                <tr>
                    <td>${item.empresa}</td>
                    <td class="text-right">${item.frequencia}</td>
                    <td>${item.ultima_movimentacao ? new Date(item.ultima_movimentacao).toLocaleString('pt-BR') : '-'}</td>
                </tr>
            `).join('');

            return `
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th class="text-right">Frequência</th>
                                <th>Última Movimentação</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows}
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <small class="text-muted">Top 10 empresas mais frequentes</small>
                </div>
            `;
        }

        printKpiDetails() {
            const conteudo = document.getElementById('kpiDetalhesConteudo').innerHTML;
            const titulo = document.getElementById('kpiDetalhesTitulo').textContent;

            const janela = window.open('', '_blank');
            janela.document.write(`
                <html>
                    <head>
                        <title>${titulo}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            h1 { color: #333; }
                            @media print { body { margin: 10px; } }
                        </style>
                    </head>
                    <body>
                        <h1>${titulo}</h1>
                        ${conteudo}
                        <script>
                            window.onload = function() {
                                window.print();
                                window.close();
                            }
                        </script>
                    </body>
                </html>
            `);
            janela.document.close();
        }

        openOffcanvas(offcanvas) {
            if (offcanvas) {
                offcanvas.classList.add('show');
                document.body.classList.add('offcanvas-open');
            }
        }

        closeOffcanvas(offcanvas) {
            if (offcanvas) {
                offcanvas.classList.remove('show');
                document.body.classList.remove('offcanvas-open');
            }
        }

        showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.add('show');
            }
        }

        hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.classList.remove('show');
            }
        }

        showSuccess(message) {
            // Usar alert simples como fallback
            alert('✅ ' + message);
        }

        showError(message) {
            // Usar alert simples como fallback
            alert('❌ ' + message);
        }

        buildEndpointCandidates() {
            const base = this.basePath;
            return [
                `${base}/modulos/eclusa/movimentacoes/movimentacoes_logica.php`,
                '/modulos/eclusa/movimentacoes/movimentacoes_logica.php'
            ];
        }

        async fetchWithRetry(formData, maxRetries = 3) {
            for (let i = 0; i < maxRetries; i++) {
                for (const endpoint of this.endpointCandidates) {
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            body: formData
                        });

                        if (response.ok) {
                            return response;
                        }
                    } catch (error) {
                        console.warn(`Tentativa ${i + 1} falhou para ${endpoint}:`, error);
                    }
                }
            }
            throw new Error('Todas as tentativas de conexão falharam');
        }
    }

    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.eclusaMov = new EclusaMovimentacoes();
        });
    } else {
        window.eclusaMov = new EclusaMovimentacoes();
    }
})();
