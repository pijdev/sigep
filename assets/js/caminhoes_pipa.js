// Controle de Caminhões Pipa - SIGEP
// Baseado na lógica da página de escoltas

(function() {
    'use strict';

    class CaminhoesPipa {
        constructor() {
            const base = (window.SIGEP_BASE_PATH || '').replace(/\/$/, '');
            this.basePath = base;

            // Para SPA, garantir que o endpoint funcione corretamente
            if (!this.basePath) {
                this.endpoint = 'includes/caminhoes_pipa_logica.php';
            } else {
                this.endpoint = `${this.basePath}/includes/caminhoes_pipa_logica.php`;
            }

            this.currentPage = 1;
            this.totalPages = 1;
            this.currentFilters = {};
            this.debounceTimers = {};
            this.saving = false;
            this.deleting = false;

            this.form = document.getElementById('formRegistro');
            this.offcanvasRegistro = document.getElementById('offcanvasRegistro');

            this.init();
        }

        init() {
            // Verificar se os elementos necessários existem
            if (!document.getElementById('kpiTotal')) {
                console.log('Aguardando elementos da página...');
                setTimeout(() => this.init(), 200);
                return;
            }

            // Verificar se o endpoint está acessível
            if (!this.endpoint) {
                console.error('Endpoint não definido');
                return;
            }

            console.log('Inicializando Caminhões Pipa...');
            this.bindEvents();

            // Inicialização robusta com retry
            this.initWithRetry();
        }

        initWithRetry(retryCount = 0) {
            const maxRetries = 3;
            const retryDelay = 1000; // 1 segundo

            this.loadContadores()
                .then(() => {
                    console.log('Contadores carregados com sucesso');
                    return this.loadRegistros();
                })
                .then(() => {
                    console.log('Registros carregados com sucesso');
                    this.loadFilterOptions();
                })
                .catch(error => {
                    console.error(`Erro na inicialização (tentativa ${retryCount + 1}):`, error);

                    if (retryCount < maxRetries) {
                        console.log(`Tentando novamente em ${retryDelay}ms...`);
                        setTimeout(() => this.initWithRetry(retryCount + 1), retryDelay);
                    } else {
                        console.error('Falha na inicialização após múltiplas tentativas');
                        // Fallback: mostrar mensagem de erro mas não quebrar a página
                        this.showToast('Erro ao carregar dados. Tente atualizar a página.', 'warning');
                    }
                });
        }

        bindEvents() {
            document.getElementById('btnNovoRegistro')?.addEventListener('click', () => this.openFormForCreate());
            document.getElementById('btnAbrirRelatorio')?.addEventListener('click', () => this.openRelatorio());
            document.getElementById('btnAtualizarTudo')?.addEventListener('click', () => this.reloadAll());

            document.getElementById('filtrosForm')?.addEventListener('submit', (event) => {
                event.preventDefault();
                this.applyFilters();
            });

            document.getElementById('btnLimparFiltros')?.addEventListener('click', () => {
                const form = document.getElementById('filtrosForm');
                form?.reset();
                this.currentFilters = {};
                this.currentPage = 1;
                this.loadRegistros();
            });

            this.form?.addEventListener('submit', (event) => {
                event.preventDefault();
                this.saveRegistro();
            });

            document.getElementById('regPrev')?.addEventListener('click', () => {
                if (this.currentPage > 1) {
                    this.currentPage -= 1;
                    this.loadRegistros();
                }
            });

            document.getElementById('regNext')?.addEventListener('click', () => {
                if (this.currentPage < this.totalPages) {
                    this.currentPage += 1;
                    this.loadRegistros();
                }
            });

            document.querySelectorAll('[data-offcanvas-close]').forEach((btn) => {
                btn.addEventListener('click', (event) => {
                    const offcanvas = event.target.closest('.offcanvas-caminhoes-pipa');
                    this.closeOffcanvas(offcanvas);
                });
            });
        }

        async reloadAll() {
            await Promise.all([
                this.loadContadores(),
                this.loadRegistros()
            ]);
        }

        async loadContadores() {
            try {
                console.log('loadContadores: iniciando...');
                const response = await this.post({ action: 'get_contadores' });
                console.log('loadContadores: response recebida:', response);

                if (response.success) {
                    console.log('loadContadores: chamando updateKPIs com:', response.data);
                    this.updateKPIs(response.data);
                    console.log('loadContadores: updateKPIs concluído');
                } else {
                    console.error('loadContadores: response.success = false:', response.message);
                }
            } catch (error) {
                console.error('loadContadores: catch error:', error);
            }
        }

        updateKPIs(data) {
            document.getElementById('kpiTotal').textContent = data.totalRegistros || 0;
            document.getElementById('kpiHoje').textContent = data.registrosHoje || 0;
            document.getElementById('kpiLitros').textContent = this.formatNumber(data.totalLitros || 0);
            document.getElementById('kpiMedia').textContent = this.formatNumber(data.mediaLitros || 0, 1);
            document.getElementById('kpiMotoristas').textContent = data.totalMotoristas || 0;
            document.getElementById('kpiVeiculos').textContent = data.totalVeiculos || 0;
        }

        async loadRegistros() {
            try {
                console.log('loadRegistros: iniciando...');
                const filtros = { ...this.currentFilters, page: this.currentPage, action: 'listar' };
                const response = await this.post(filtros);
                console.log('loadRegistros: response recebida:', response);

                if (response.success) {
                    console.log('loadRegistros: chamando updateTable com', response.data.registros.length, 'registros');
                    this.updateTable(response.data.registros);
                    this.updatePagination(response.data.currentPage, response.data.totalPages, response.data.totalRegistros);
                    document.getElementById('regMeta').textContent = `Mostrando ${response.data.registros.length} de ${response.data.totalRegistros} registros`;
                    console.log('loadRegistros: atualização concluída');
                } else {
                    console.error('loadRegistros: response.success = false:', response.message);
                }
            } catch (error) {
                console.error('loadRegistros: catch error:', error);
            }
        }

        updateTable(data) {
            const tbody = document.getElementById('registrosTableBody');
            if (!tbody) return;

            let html = '';

            data.forEach(registro => {
                const statusBadge = this.getStatusBadge(registro.status);
                const kmPercorridos = registro.km_final - registro.km_inicial;

                html += `
                    <tr>
                        <td>${this.formatDate(registro.data_registro)}</td>
                        <td><strong>${registro.placa}</strong></td>
                        <td>${registro.motorista}</td>
                        <td>${registro.empresa}</td>
                        <td>${registro.tipo}</td>
                        <td class="text-center">${this.formatNumber(registro.litros)}</td>
                        <td class="text-center">${this.formatNumber(registro.km_inicial)}</td>
                        <td class="text-center">${this.formatNumber(registro.km_final)}</td>
                        <td class="text-center">${this.formatNumber(kmPercorridos)}</td>
                        <td>${statusBadge}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" onclick="caminhoesPipa.editarRegistro(${registro.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="caminhoesPipa.verDetalhes(${registro.id})">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            if (data.length === 0) {
                html = `
                    <tr>
                        <td colspan="11" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox mr-2"></i>Nenhum registro encontrado
                        </td>
                    </tr>
                `;
            }

            tbody.innerHTML = html;
        }

        getStatusBadge(status) {
            const badges = {
                'Ativo': '<span class="badge badge-ativo">Ativo</span>',
                'Inativo': '<span class="badge badge-inativo">Inativo</span>',
                'Manutenção': '<span class="badge badge-manutencao">Manutenção</span>'
            };
            return badges[status] || status;
        }

        updatePagination(currentPage, totalPages, totalRegistros) {
            document.getElementById('regPageInfo').textContent = `Página ${currentPage} de ${totalPages} (${totalRegistros} registros)`;
            document.getElementById('regPrev').disabled = currentPage <= 1;
            document.getElementById('regNext').disabled = currentPage >= totalPages;
        }

        async loadFilterOptions() {
            try {
                const response = await this.post({ action: 'listar_opcoes' });
                if (response.success) {
                    this.populateFilterSelects(response.data);
                }
            } catch (error) {
                console.error('Erro ao carregar opções de filtro:', error);
            }
        }

        populateFilterSelects(data) {
            // Preencher select de placas
            const placaSelect = document.getElementById('filtro_placa');
            if (placaSelect && data.placas) {
                placaSelect.innerHTML = '<option value="">Todas as placas</option>';
                data.placas.forEach(placa => {
                    placaSelect.innerHTML += `<option value="${placa}">${placa}</option>`;
                });
            }

            // Preencher select de motoristas
            const motoristaSelect = document.getElementById('filtro_motorista');
            if (motoristaSelect && data.motoristas) {
                motoristaSelect.innerHTML = '<option value="">Todos os motoristas</option>';
                data.motoristas.forEach(motorista => {
                    motoristaSelect.innerHTML += `<option value="${motorista}">${motorista}</option>`;
                });
            }

            // Preencher select de empresas
            const empresaSelect = document.getElementById('filtro_empresa');
            if (empresaSelect && data.empresas) {
                empresaSelect.innerHTML = '<option value="">Todas as empresas</option>';
                data.empresas.forEach(empresa => {
                    empresaSelect.innerHTML += `<option value="${empresa}">${empresa}</option>`;
                });
            }
        }

        async applyFilters() {
            const form = document.getElementById('filtrosForm');
            const formData = new FormData(form);

            this.currentFilters = {
                placa: formData.get('placa'),
                motorista: formData.get('motorista'),
                empresa: formData.get('empresa'),
                tipo: formData.get('tipo'),
                data_inicio: formData.get('data_inicio'),
                data_fim: formData.get('data_fim')
            };

            // Remover valores vazios
            Object.keys(this.currentFilters).forEach(key => {
                if (!this.currentFilters[key]) {
                    delete this.currentFilters[key];
                }
            });

            this.currentPage = 1;
            await this.loadRegistros();
        }

        openFormForCreate() {
            if (this.form) {
                this.form.reset();
                // Limpar campos de dados
                this.form.querySelectorAll('input, select, textarea').forEach(field => {
                    field.removeAttribute('data-id');
                });
            }
            this.openOffcanvas(this.offcanvasRegistro);
        }

        async editarRegistro(id) {
            try {
                const response = await this.post({ action: 'buscar', id: id });
                if (response.success && response.data) {
                    this.preencherFormulario(response.data);
                    this.openOffcanvas(this.offcanvasRegistro);
                } else {
                    this.showToast('Registro não encontrado', 'error');
                }
            } catch (error) {
                console.error('Erro ao buscar registro:', error);
                this.showToast('Erro ao buscar registro', 'error');
            }
        }

        preencherFormulario(registro) {
            if (this.form) {
                document.getElementById('placa').value = registro.placa || '';
                document.getElementById('placa').setAttribute('data-id', registro.id);
                document.getElementById('motorista').value = registro.motorista || '';
                document.getElementById('empresa').value = registro.empresa || '';
                document.getElementById('tipo').value = registro.tipo || '';
                document.getElementById('litros').value = registro.litros || '';
                document.getElementById('km_inicial').value = registro.km_inicial || '';
                document.getElementById('km_final').value = registro.km_final || '';
                document.getElementById('status').value = registro.status || '';
                document.getElementById('observacoes').value = registro.observacoes || '';
            }
        }

        async saveRegistro() {
            if (this.saving) return;

            try {
                this.saving = true;

                const formData = new FormData(this.form);
                const dados = {
                    action: formData.get('placa').getAttribute('data-id') ? 'atualizar' : 'salvar',
                    id: formData.get('placa').getAttribute('data-id') || null,
                    placa: formData.get('placa'),
                    motorista: formData.get('motorista'),
                    empresa: formData.get('empresa'),
                    tipo: formData.get('tipo'),
                    litros: formData.get('litros'),
                    km_inicial: formData.get('km_inicial'),
                    km_final: formData.get('km_final'),
                    status: formData.get('status'),
                    observacoes: formData.get('observacoes')
                };

                const response = await this.post(dados);

                if (response.success) {
                    this.showToast(response.message, 'success');
                    this.closeOffcanvas(this.offcanvasRegistro);
                    await this.reloadAll();
                } else {
                    this.showToast(response.message || 'Erro ao salvar registro', 'error');
                }
            } catch (error) {
                console.error('Erro ao salvar registro:', error);
                this.showToast('Erro ao salvar registro', 'error');
            } finally {
                this.saving = false;
            }
        }

        async verDetalhes(id) {
            try {
                const response = await this.post({ action: 'buscar', id: id });
                if (response.success && response.data) {
                    this.mostrarModalDetalhes(response.data);
                } else {
                    this.showToast('Registro não encontrado', 'error');
                }
            } catch (error) {
                console.error('Erro ao buscar detalhes:', error);
                this.showToast('Erro ao buscar detalhes', 'error');
            }
        }

        mostrarModalDetalhes(registro) {
            const kmPercorridos = registro.km_final - registro.km_inicial;

            const detalhes = `
                <div class="row">
                    <div class="col-md-6"><strong>Placa:</strong> ${registro.placa}</div>
                    <div class="col-md-6"><strong>Motorista:</strong> ${registro.motorista}</div>
                    <div class="col-md-6"><strong>Empresa:</strong> ${registro.empresa}</div>
                    <div class="col-md-6"><strong>Tipo:</strong> ${registro.tipo}</div>
                    <div class="col-md-6"><strong>Litros:</strong> ${this.formatNumber(registro.litros)}</div>
                    <div class="col-md-6"><strong>Km Inicial:</strong> ${this.formatNumber(registro.km_inicial)}</div>
                    <div class="col-md-6"><strong>Km Final:</strong> ${this.formatNumber(registro.km_final)}</div>
                    <div class="col-md-6"><strong>Km Percorridos:</strong> ${this.formatNumber(kmPercorridos)}</div>
                    <div class="col-md-6"><strong>Status:</strong> ${this.getStatusBadge(registro.status)}</div>
                    <div class="col-md-12"><strong>Observações:</strong> ${registro.observacoes || 'Nenhuma'}</div>
                    <div class="col-md-6"><strong>Data Registro:</strong> ${this.formatDate(registro.data_registro)}</div>
                </div>
            `;

            // Criar modal para detalhes
            const modalHtml = `
                <div class="modal fade" id="modalDetalhes" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalhes do Registro</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                ${detalhes}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remover modal existente
            const existingModal = document.getElementById('modalDetalhes');
            if (existingModal) {
                existingModal.remove();
            }

            // Adicionar novo modal
            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Mostrar modal
            const modalElement = document.getElementById('modalDetalhes');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }

        openRelatorio() {
            // Implementar lógica de relatório se necessário
            this.showToast('Relatório em desenvolvimento', 'info');
        }

        openOffcanvas(offcanvas) {
            if (offcanvas) {
                offcanvas.classList.add('show');
                document.body.style.overflow = 'hidden';

                // Adicionar backdrop
                const backdrop = document.createElement('div');
                backdrop.className = 'offcanvas-backdrop';
                backdrop.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                    z-index: 1040;
                `;
                document.body.appendChild(backdrop);

                // Adicionar evento de clique no backdrop para fechar
                backdrop.addEventListener('click', () => this.closeOffcanvas(offcanvas));
            }
        }

        closeOffcanvas(offcanvas) {
            if (offcanvas) {
                offcanvas.classList.remove('show');
                document.body.style.overflow = '';

                // Remover backdrop
                const backdrop = document.querySelector('.offcanvas-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
            }
        }

        async post(data) {
            const formData = new FormData();
            Object.entries(data).forEach(([key, value]) => {
                formData.append(key, value);
            });

            try {
                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                return await response.json();
            } catch (error) {
                console.error('Erro na requisição:', error);
                throw error;
            }
        }

        showToast(message, type = 'info') {
            // Implementar toast se necessário
            console.log(`${type}: ${message}`);

            // Implementação simples de toast
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'error' ? 'danger' : type} position-fixed top-0 end-0 m-3`;
            toast.style.zIndex = '9999';
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }

        formatNumber(number, decimals = 0) {
            return Number(number).toLocaleString('pt-BR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }

        formatDate(date) {
            if (!date) return '-';
            const dateObj = new Date(date);
            return dateObj.toLocaleDateString('pt-BR');
        }
    }

    // Inicializar quando o DOM estiver pronto - igual ao Escoltas
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new CaminhoesPipa());
    } else {
        new CaminhoesPipa();
    }
})();
