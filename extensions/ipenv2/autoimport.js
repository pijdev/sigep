function initAutoImport() {
    console.log("🚀 AutoImport 1-8 Ativado");

    // Classe isolada - não interfere em outros módulos
    class AutoImportManager {
        constructor() {
            this.namespace = 'ipen_autoimport';
            this.isActive = true;
            this.unidade = this.detectUnidade();
            this.lastImport = this.getLastImport();

            console.log(`AutoImport: Unidade ${this.unidade}, última importação: ${new Date(this.lastImport).toLocaleString()}`);
        }

        detectUnidade() {
            const match = document.body.innerText.match(/UNIDADE DE TRABALHO ATUAL\s*(\d+)/);
            return match ? match[1] : '8019';
        }

        getLastImport() {
            const key = `${this.namespace}_${this.unidade}_last_import`;
            return localStorage.getItem(key) || 0;
        }

        async getLastImportFromSIGEP() {
            console.log('AutoImport: Consultando última importação no SIGEP...');

            try {
                // Usa endpoint do SIGEP com CORS configurado
                const response = await fetch('http://10.40.88.200/api/importa18_last_import.php?unidade=' + this.unidade, {
                    method: 'GET',
                    credentials: 'include'
                });

                if (!response.ok) {
                    throw new Error('Falha na consulta ao SIGEP');
                }

                const data = await response.json();

                if (data.success) {
                    console.log('AutoImport: Última importação obtida do SIGEP', data);

                    if (data.ultima_importacao) {
                        this.lastImport = data.ultima_importacao.timestamp;
                        this.saveImportTimestamp(); // Sincroniza localStorage

                        return {
                            timestamp: data.ultima_importacao.timestamp,
                            total: data.ultima_importacao.total,
                            fonte: data.ultima_importacao.fonte,
                            deveImportar: data.deve_importar,
                            tempoDecorrido: data.tempo_decorrido_minutos
                        };
                    } else {
                        console.log('AutoImport: Nenhuma importação encontrada no SIGEP');
                        return { deveImportar: true };
                    }
                } else {
                    throw new Error(data.message || 'Erro na resposta');
                }

            } catch (error) {
                console.warn('AutoImport: Falha ao consultar SIGEP, usando localStorage', error);
                // Fallback para localStorage
                return null;
            }
        }

        async shouldImport() {
            // Fallback para localStorage (sem depender de API)
            const oneHour = 3600000;
            const timeSinceLast = Date.now() - this.lastImport;
            return timeSinceLast > oneHour;
        }

        async getImportStatus() {
            try {
                // Usa endpoint do SIGEP com CORS configurado
                const response = await fetch('http://10.40.88.200/api/importa18_last_import.php?unidade=' + this.unidade, {
                    method: 'GET',
                    credentials: 'include'
                });

                if (response.ok) {
                    const data = await response.json();
                    return data;
                }
            } catch (error) {
                console.warn('AutoImport: Falha ao obter status', error);
                return null;
            }
        }

        updatePopupStatus(status) {
            if (!status || !status.success) {
                this.setPopupStatus('🔴 Desconhecido', 'error');
                return;
            }

            if (!status.ultima_importacao) {
                this.setPopupStatus('🔵 Nunca importado', 'warning');
                return;
            }

            const ultima = status.ultima_importacao;
            const tempoMinutos = status.tempo_decorrido_minutos || 0;

            // Define status baseado no tempo e fonte
            let statusText = '🟢 OK';
            let statusType = 'success';

            if (status.deve_importar) {
                if (tempoMinutos > 120) {
                    statusText = '🔴 Atrasado';
                    statusType = 'error';
                } else if (tempoMinutos > 60) {
                    statusText = '🟡 Aguardando';
                    statusType = 'warning';
                } else {
                    statusText = '🟡 Pronto';
                    statusType = 'info';
                }
            } else {
                if (ultima.fonte === 'AutoImport') {
                    statusText = '🟢 Automático';
                    statusType = 'success';
                } else {
                    statusText = '🟢 Manual';
                    statusType = 'success';
                }
            }

            // Adiciona informações extras
            const info = `${ultima.total} regs | ${ultima.fonte || 'Manual'}`;
            this.setPopupStatus(statusText, statusType, info);
        }

        setPopupStatus(text, type = 'info', details = '') {
            // Envia mensagem para o popup via evento customizado
            window.dispatchEvent(new CustomEvent('autoimportStatusUpdate', {
                detail: { text, type, details }
            }));
        }

        async updatePopupStatusPeriodically() {
            // Atualiza status a cada 30 segundos
            setInterval(async () => {
                const status = await this.getImportStatus();
                if (status) {
                    this.updatePopupStatus(status);
                }
            }, 30000);
        }

        async forceImport() {
            console.log('AutoImport: Forçando importação manual...');

            try {
                this.showNotification('🔄 Forçando importação manual...', 'info');

                // 1. Extrai dados do relatório
                const reportData = await this.extractRelatorio18();

                if (!reportData || reportData.length < 100) {
                    throw new Error('Dados insuficientes no relatório');
                }

                // 2. Envia para SIGEP
                const result = await this.sendToSIGEP(reportData);

                // 3. Salva timestamp
                this.saveImportTimestamp();

                // 4. Mostra modal com resultados
                this.showImportModal(result);

                // 5. Atualiza status no popup
                this.getImportStatus().then(status => {
                    if (status) {
                        this.updatePopupStatus(status);
                    }
                });

                console.log('AutoImport: Importação forçada concluída com sucesso', result);

                return { success: true, result };

            } catch (error) {
                console.error('AutoImport: Erro na importação forçada', error);
                this.showImportModal({ success: false, error: error.message });
                return { success: false, error: error.message };
            }
        }

        async processImport() {
            if (!(await this.shouldImport())) {
                console.log('AutoImport: Não precisa importar ainda');
                return;
            }

            try {
                this.showNotification('🔄 Iniciando importação automática...', 'info');

                // 1. Extrai dados do relatório
                const reportData = await this.extractRelatorio18();

                if (!reportData || reportData.length < 100) {
                    throw new Error('Dados insuficientes no relatório');
                }

                // 2. Envia para SIGEP (via proxy)
                const result = await this.sendToSIGEP(reportData);

                // 3. Salva timestamp
                this.saveImportTimestamp();

                // 4. Mostra modal com resultados
                this.showImportModal(result);

                // 5. Atualiza status no popup
                this.getImportStatus().then(status => {
                    if (status) {
                        this.updatePopupStatus(status);
                    }
                });

                console.log('AutoImport: Importação concluída com sucesso', result);

            } catch (error) {
                console.error('AutoImport: Erro na importação', error);
                this.showImportModal({ success: false, error: error.message });
            }
        }

        async extractRelatorio18() {
            console.log('AutoImport: Extraindo dados do relatório 1-8...');

            try {
                // Abre relatório em nova aba para extração (URL relativa do iPEN)
                const reportUrl = `/ipen/RelatorioIpen_028DetentosAlocadosAlfabeticaImprimir.asp?cd_Unidade=${this.unidade}&Unidades=undefined&cd_Ordenacao=1`;

                // Faz fetch do conteúdo do relatório
                const response = await fetch(reportUrl);
                if (!response.ok) {
                    throw new Error(`Falha ao carregar relatório: ${response.status}`);
                }

                const html = await response.text();

                // Extrai texto do HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';

                console.log(`AutoImport: Extraídos ${textContent.length} caracteres do relatório`);

                return textContent.trim();

            } catch (error) {
                console.error('AutoImport: Erro na extração', error);
                throw new Error('Falha ao extrair dados do relatório');
            }
        }

        async sendToSIGEP(reportData) {
            console.log('AutoImport: Enviando dados para SIGEP...');

            try {
                // Usa endpoint do SIGEP com CORS configurado
                const sigepUrl = 'http://10.40.88.200/api/importa18_auto.php';

                const formData = new FormData();
                formData.append('report_data', reportData);
                formData.append('unidade', this.unidade);
                formData.append('source', 'autoimport');

                const response = await fetch(sigepUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include' // Importante para cookies de sessão
                });

                if (!response.ok) {
                    throw new Error(`Erro SIGEP: ${response.status}`);
                }

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Erro no processamento SIGEP');
                }

                return result;

            } catch (error) {
                console.error('AutoImport: Erro na comunicação SIGEP', error);

                // Fallback: tenta método clipboard
                return this.clipboardFallback(reportData);
            }
        }

        async clipboardFallback(reportData) {
            console.log('AutoImport: Tentando fallback via clipboard...');

            try {
                // Copia para clipboard
                await navigator.clipboard.writeText(reportData);

                // Abre SIGEP em nova aba (ambiente local)
                const sigepWindow = window.open('http://10.40.88.200/paginas/dados_importa_18.php', '_blank');

                if (sigepWindow) {
                    this.showNotification('📋 Dados copiados! Cole no SIGEP e processe manualmente.', 'warning');

                    // Tenta colar automaticamente após carregar
                    setTimeout(() => {
                        try {
                            const textarea = sigepWindow.document.querySelector('textarea[name="report_data"]');
                            if (textarea) {
                                textarea.value = reportData;
                                this.showNotification('📋 Dados colados automaticamente! Clique em "Processar Dados".', 'info');
                            }
                        } catch (e) {
                            console.log('AutoImport: Não foi possível colar automaticamente');
                        }
                    }, 2000);
                }

                return { success: true, method: 'clipboard', message: 'Use o processamento manual' };

            } catch (error) {
                throw new Error('Falha no método alternativo. Use o processo manual.');
            }
        }

        saveImportTimestamp() {
            const key = `${this.namespace}_${this.unidade}_last_import`;
            localStorage.setItem(key, Date.now().toString());
            this.lastImport = Date.now();
        }

        showNotification(message, type = 'info') {
            // Remove notificações anteriores
            document.querySelectorAll('.autoimport-notification').forEach(n => n.remove());

            // Cria nova notificação
            const notification = document.createElement('div');
            notification.className = `autoimport-notification autoimport-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${this.getNotificationColor(type)};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 500;
                z-index: 999999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                max-width: 300px;
                animation: slideIn 0.3s ease;
            `;

            notification.innerHTML = message;

            // Adiciona CSS da animação se não existir
            if (!document.getElementById('autoimport-styles')) {
                const style = document.createElement('style');
                style.id = 'autoimport-styles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    .autoimport-success { background: #10b981; }
                    .autoimport-error { background: #ef4444; }
                    .autoimport-warning { background: #f59e0b; }
                    .autoimport-info { background: #3b82f6; }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(notification);

            // Auto-remove após 5 segundos
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        showImportModal(result) {
            // Remove modais anteriores
            document.querySelectorAll('.autoimport-modal').forEach(m => m.remove());

            // Cria modal Shadcn/UI
            const modal = document.createElement('div');
            modal.className = 'autoimport-modal';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 999999;
                animation: fadeIn 0.3s ease;
                font-family: 'Inter', -apple-system, sans-serif;
            `;

            const isSuccess = result.success;
            const icon = isSuccess ? '✅' : '❌';
            const title = isSuccess ? 'Importação Concluída!' : 'Erro na Importação';
            const titleColor = isSuccess ? '#10b981' : '#ef4444';

            modal.innerHTML = `
                <div class="modal-content" style="
                    background: #ffffff;
                    border-radius: 12px;
                    padding: 24px;
                    max-width: 480px;
                    width: 90%;
                    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                    animation: slideUp 0.3s ease;
                ">
                    <div class="modal-header" style="
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        margin-bottom: 20px;
                    ">
                        <div class="modal-icon" style="
                            font-size: 24px;
                            width: 48px;
                            height: 48px;
                            border-radius: 12px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: ${isSuccess ? 'rgba(16, 185, 129, 0.1)' : 'rgba(239, 68, 68, 0.1)'};
                            color: ${titleColor};
                            font-size: 28px;
                        ">
                            ${icon}
                        </div>
                        <div class="modal-title" style="
                            flex: 1;
                        ">
                            <h3 style="
                                margin: 0;
                                font-size: 20px;
                                font-weight: 700;
                                color: #09090b;
                                line-height: 1.2;
                            ">${title}</h3>
                            <p style="
                                margin: 4px 0 0 0;
                                font-size: 14px;
                                color: #71717a;
                                font-weight: 500;
                            ">${isSuccess ? 'Processamento concluído com sucesso' : 'Ocorreu um erro durante o processamento'}</p>
                        </div>
                        <button class="modal-close" style="
                            background: none;
                            border: none;
                            font-size: 20px;
                            cursor: pointer;
                            color: #71717a;
                            padding: 4px;
                            border-radius: 4px;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='none'">
                            ×
                        </button>
                    </div>

                    ${isSuccess ? this.renderSuccessContent(result) : this.renderErrorContent(result)}

                    <div class="modal-footer" style="
                        margin-top: 24px;
                        display: flex;
                        gap: 12px;
                        justify-content: flex-end;
                    ">
                        <button class="btn-secondary" style="
                            padding: 8px 16px;
                            border: 1px solid #e4e4e7;
                            background: white;
                            color: #09090b;
                            border-radius: 6px;
                            font-size: 14px;
                            font-weight: 500;
                            cursor: pointer;
                            transition: all 0.2s ease;
                        " onmouseover="this.style.background='#f9f9f9'" onmouseout="this.style.background='white'">
                            Fechar
                        </button>
                        ${isSuccess ? `
                            <button class="btn-primary" style="
                                padding: 8px 16px;
                                border: none;
                                background: #2563eb;
                                color: white;
                                border-radius: 6px;
                                font-size: 14px;
                                font-weight: 500;
                                cursor: pointer;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                                Ver Detalhes
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;

            // Adiciona CSS das animações
            if (!document.getElementById('modal-styles')) {
                const style = document.createElement('style');
                style.id = 'modal-styles';
                style.textContent = `
                    @keyframes fadeIn {
                        from { opacity: 0; }
                        to { opacity: 1; }
                    }
                    @keyframes slideUp {
                        from { transform: translateY(20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                `;
                document.head.appendChild(style);
            }

            document.body.appendChild(modal);

            // Event listeners
            const closeBtn = modal.querySelector('.modal-close');
            const closeFooterBtn = modal.querySelector('.btn-secondary');

            const closeModal = () => {
                modal.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => modal.remove(), 300);
            };

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (closeFooterBtn) closeFooterBtn.addEventListener('click', closeModal);

            // Auto-close após 10 segundos se sucesso
            if (isSuccess) {
                setTimeout(closeModal, 10000);
            }

            // Click outside to close
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
        }

        renderSuccessContent(result) {
            const timestamp = new Date().toLocaleString('pt-BR');

            return `
                <div class="modal-body" style="
                    background: #f8fafc;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 20px;
                ">
                    <div class="stats-grid" style="
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 16px;
                        margin-bottom: 20px;
                    ">
                        <div class="stat-item" style="
                            text-align: center;
                            padding: 12px;
                            background: white;
                            border-radius: 6px;
                            border: 1px solid #e2e8f0;
                        ">
                            <div class="stat-value" style="
                                font-size: 24px;
                                font-weight: 700;
                                color: #09090b;
                                margin-bottom: 4px;
                            ">${result.total || 0}</div>
                            <div class="stat-label" style="
                                font-size: 12px;
                                color: #71717a;
                                font-weight: 500;
                            ">Total Processado</div>
                        </div>
                        <div class="stat-item" style="
                            text-align: center;
                            padding: 12px;
                            background: white;
                            border-radius: 6px;
                            border: 1px solid #e2e8f0;
                        ">
                            <div class="stat-value" style="
                                font-size: 24px;
                                font-weight: 700;
                                color: #10b981;
                                margin-bottom: 4px;
                            ">${result.novos || 0}</div>
                            <div class="stat-label" style="
                                font-size: 12px;
                                color: #71717a;
                                font-weight: 500;
                            ">Novos Internos</div>
                        </div>
                        <div class="stat-item" style="
                            text-align: center;
                            padding: 12px;
                            background: white;
                            border-radius: 6px;
                            border: 1px solid #e2e8f0;
                        ">
                            <div class="stat-value" style="
                                font-size: 24px;
                                font-weight: 700;
                                color: #2563eb;
                                margin-bottom: 4px;
                            ">${result.atualizados || 0}</div>
                            <div class="stat-label" style="
                                font-size: 12px;
                                color: #71717a;
                                font-weight: 500;
                            ">Atualizados</div>
                        </div>
                        <div class="stat-item" style="
                            text-align: center;
                            padding: 12px;
                            background: white;
                            border-radius: 6px;
                            border: 1px solid #e2e8f0;
                        ">
                            <div class="stat-value" style="
                                font-size: 24px;
                                font-weight: 700;
                                color: #f59e0b;
                                margin-bottom: 4px;
                            ">${result.inativados || 0}</div>
                            <div class="stat-label" style="
                                font-size: 12px;
                                color: #71717a;
                                font-weight: 500;
                            ">Inativados</div>
                        </div>
                    </div>

                    <div class="import-info" style="
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        padding-top: 16px;
                        border-top: 1px solid #e2e8f0;
                    ">
                        <div>
                            <div style="font-size: 12px; color: #71717a; margin-bottom: 2px;">Fonte</div>
                            <div style="font-size: 14px; font-weight: 600; color: #09090b;">
                                ${result.fonte || 'AutoImport'}
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 12px; color: #71717a; margin-bottom: 2px;">Data/Hora</div>
                            <div style="font-size: 14px; font-weight: 600; color: #09090b;">
                                ${timestamp}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        renderErrorContent(result) {
            return `
                <div class="modal-body" style="
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 20px;
                ">
                    <div class="error-content" style="
                        display: flex;
                        align-items: flex-start;
                        gap: 12px;
                    ">
                        <div style="
                            font-size: 20px;
                            color: #ef4444;
                            margin-top: 2px;
                        ">⚠️</div>
                        <div style="flex: 1;">
                            <h4 style="
                                margin: 0 0 8px 0;
                                font-size: 16px;
                                font-weight: 600;
                                color: #09090b;
                            ">Erro durante a importação</h4>
                            <p style="
                                margin: 0;
                                font-size: 14px;
                                color: #71717a;
                                line-height: 1.5;
                            ">${result.error || 'Ocorreu um erro desconhecido durante o processamento dos dados.'}</p>
                        </div>
                    </div>

                    <div style="
                        margin-top: 16px;
                        padding-top: 16px;
                        border-top: 1px solid #fecaca;
                    ">
                        <h5 style="
                            margin: 0 0 8px 0;
                            font-size: 14px;
                            font-weight: 600;
                            color: #09090b;
                        ">Sugestões:</h5>
                        <ul style="
                            margin: 0;
                            padding-left: 20px;
                            font-size: 13px;
                            color: #71717a;
                            line-height: 1.5;
                        ">
                            <li>Verifique sua conexão com a internet</li>
                            <li>Certifique-se de que está logado no SIGEP</li>
                            <li>Tente usar o processo manual como alternativa</li>
                            <li>Contate o suporte se o problema persistir</li>
                        </ul>
                    </div>
                </div>
            `;
        }

        getNotificationColor(type) {
            const colors = {
                success: '#10b981',
                error: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            return colors[type] || colors.info;
        }

        // Inicia o monitoramento
        startMonitoring() {
            console.log('AutoImport: Iniciando monitoramento...');

            // Verifica imediatamente
            setTimeout(async () => {
                await this.processImport();
            }, 3000);

            // Verifica periodicamente (a cada 5 minutos)
            this.monitoringInterval = setInterval(async () => {
                await this.processImport();
            }, 300000); // 5 minutos

            // Inicia monitoramento de status para popup
            this.updatePopupStatusPeriodically();
        }

        // Para o monitoramento
        stopMonitoring() {
            if (this.monitoringInterval) {
                clearInterval(this.monitoringInterval);
                this.monitoringInterval = null;
                console.log('AutoImport: Monitoramento parado');
            }
        }
    }

    // Inicializa o gerenciador
    const autoImport = new AutoImportManager();

    // Inicia monitoramento se estiver ativo
    if (autoImport.isActive) {
        autoImport.startMonitoring();
    }

    // Listener para mensagens do popup
    chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
        if (request.action === 'getAutoImportStatus') {
            autoImport.getImportStatus().then(status => {
                if (status) {
                    autoImport.updatePopupStatus(status);
                    sendResponse({ status: 'ok' });
                } else {
                    sendResponse({ status: 'error' });
                }
            });
            return true; // Mantém a mensagem aberta para resposta assíncrona
        }

        if (request.action === 'forceImport') {
            autoImport.forceImport().then(result => {
                sendResponse(result);
            });
            return true; // Mantém a mensagem aberta para resposta assíncrona
        }
    });

    // Expõe globalmente para debug
    window.iPENAutoImport = autoImport;

    console.log('AutoImport: Módulo carregado com sucesso');
}
