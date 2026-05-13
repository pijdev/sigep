// ==UserScript==
// @name         iPEN - AutoImport 1-8 (Tampermonkey)
// @namespace    ipen.autoimport
// @version      1.0.0
// @description  Importação automática do relatório 1-8 para SIGEP
// @author       SIGEP Dev
// @match        https://www.sc.gov.br/sap/ipen/*
// @match        https://www.sc.gov.br/ipen/*
// @grant        none
// @run-at       document-start
// ==/UserScript==

(function() {
    'use strict';

    console.log("🚀 iPEN AutoImport Tampermonkey Ativado");

    // Classe de gerenciamento do AutoImport
    class AutoImportManager {
        constructor() {
            this.namespace = 'ipen_autoimport';
            this.unidade = this.detectUnidade();
            this.lastImport = this.getLastImport();
            this.isActive = true;

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

        shouldImport() {
            const oneHour = 3600000;
            const timeSinceLast = Date.now() - this.lastImport;
            return timeSinceLast > oneHour;
        }

        async getImportStatus() {
            try {
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

        async processImport() {
            if (!this.shouldImport()) {
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

                // 2. Envia para SIGEP
                const result = await this.sendToSIGEP(reportData);

                // 3. Salva timestamp
                this.saveImportTimestamp();

                // 4. Notifica sucesso
                this.showNotification(`✅ ${result.total || 'N'} internos atualizados!`, 'success');

                console.log('AutoImport: Importação concluída com sucesso', result);

            } catch (error) {
                console.error('AutoImport: Erro na importação', error);
                this.showNotification(`❌ Erro: ${error.message}`, 'error');
            }
        }

        async extractRelatorio18() {
            console.log('AutoImport: Extraindo dados do relatório 1-8...');

            try {
                const reportUrl = `/RelatorioIpen_028DetentosAlocadosAlfabeticaImprimir.asp?cd_Unidade=${this.unidade}&Unidades=undefined&cd_Ordenacao=1`;

                const response = await fetch(reportUrl);
                if (!response.ok) {
                    throw new Error(`Falha ao carregar relatório: ${response.status}`);
                }

                const html = await response.text();

                // Extrai texto do HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                const textContent = tempDiv.textContent || tempDiv.innerText || '';

                console.log(`AutoImport: Extraídos ${textContent.length} caracteres`);

                return textContent.trim();

            } catch (error) {
                console.error('AutoImport: Erro na extração', error);
                throw new Error('Falha ao extrair dados do relatório');
            }
        }

        async sendToSIGEP(reportData) {
            console.log('AutoImport: Enviando dados para SIGEP...');

            try {
                // Tenta endpoint proxy primeiro
                const sigepUrl = 'http://10.40.88.200/api/importa18_auto.php';

                const formData = new FormData();
                formData.append('report_data', reportData);
                formData.append('unidade', this.unidade);
                formData.append('source', 'tampermonkey');

                const response = await fetch(sigepUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        return result;
                    }
                }

                throw new Error('Endpoint proxy não disponível');

            } catch (error) {
                console.log('AutoImport: Usando fallback clipboard');
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

            // Adiciona CSS se não existir
            if (!document.getElementById('autoimport-tm-styles')) {
                const style = document.createElement('style');
                style.id = 'autoimport-tm-styles';
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

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
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

        startMonitoring() {
            console.log('AutoImport: Iniciando monitoramento...');

            // Verifica imediatamente
            setTimeout(() => this.processImport(), 3000);

            // Verifica periodicamente
            this.monitoringInterval = setInterval(() => {
                this.processImport();
            }, 300000); // 5 minutos
        }

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

    // Inicia monitoramento
    autoImport.startMonitoring();

    // Expõe globalmente para debug
    window.iPENAutoImport = autoImport;

    console.log('AutoImport: Tampermonkey script carregado com sucesso');

})();
