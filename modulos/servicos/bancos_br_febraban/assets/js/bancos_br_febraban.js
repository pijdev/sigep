/**
 * SIGEP - Módulo de Serviço: Bancos BR - Febraban
 * JavaScript: bancos_br_febraban.js
 * 
 * Responsabilidade: Serviço de sincronização automática
 * Executa a cada 1 hora via footer.js
 * 
 * @module servicos/bancos_br_febraban
 * @version 1.0.0
 * @date 2026-04-08
 */

(function() {
    'use strict';
    
    // Namespace do serviço
    if (typeof window.SIGEP === 'undefined') {
        window.SIGEP = {};
    }
    if (typeof window.SIGEP.Servicos === 'undefined') {
        window.SIGEP.Servicos = {};
    }
    
    window.SIGEP.Servicos.BancosBRFebraban = {
        
        // Configurações
        config: {
            serviceName: 'bancos_br_febraban',
            syncInterval: 60 * 60 * 1000, // 1 hora em ms
            localStorageKey: 'sigep_bancos_br_febraban_last_sync',
            endpointSync: 'modulos/servicos/bancos_br_febraban/bancos_br_febraban_logica.php?action=sincronizar',
            endpointStatus: 'modulos/servicos/bancos_br_febraban/bancos_br_febraban_logica.php?action=status',
            timeout: 30000 // 30 segundos
        },
        
        /**
         * Inicializa o serviço de sincronização
         * Executa no carregamento do footer
         */
        init: function() {
            console.log('[BancosBRFebraban] Serviço iniciado');
            
            // Verificar se já está sincronizado recentemente
            if (this.shouldSync()) {
                // Aguardar 5 segundos após carregamento para não impactar performance
                setTimeout(() => {
                    this.sincronizar();
                }, 5000);
            } else {
                const lastSync = localStorage.getItem(this.config.localStorageKey);
                const nextSync = parseInt(lastSync) + this.config.syncInterval;
                const minutosRestantes = Math.round((nextSync - Date.now()) / 60000);
                console.log(`[BancosBRFebraban] Próxima sincronização em ${minutosRestantes} minutos`);
            }
            
            // Agendar sincronização periódica
            setInterval(() => {
                this.sincronizar();
            }, this.config.syncInterval);
        },
        
        /**
         * Verifica se deve sincronizar baseado no tempo desde última execução
         * @returns {boolean}
         */
        shouldSync: function() {
            const lastSync = localStorage.getItem(this.config.localStorageKey);
            if (!lastSync) {
                return true;
            }
            return (Date.now() - parseInt(lastSync)) >= this.config.syncInterval;
        },
        
        /**
         * Executa sincronização com BrasilAPI
         */
        sincronizar: function() {
            console.log('[BancosBRFebraban] Iniciando sincronização...');
            
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
            
            fetch(this.config.endpointSync, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                signal: controller.signal
            })
            .then(response => {
                clearTimeout(timeoutId);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Registrar sucesso
                    localStorage.setItem(this.config.localStorageKey, Date.now().toString());
                    console.log(
                        '[BancosBRFebraban] ✓ Sincronização concluída |',
                        `Total API: ${data.total_api} |`,
                        `Inseridos: ${data.inseridos} |`,
                        `Atualizados: ${data.atualizados}`
                    );
                } else {
                    console.warn('[BancosBRFebraban] ✗', data.error || 'Erro na sincronização');
                }
            })
            .catch(error => {
                clearTimeout(timeoutId);
                if (error.name === 'AbortError') {
                    console.error('[BancosBRFebraban] Timeout na sincronização');
                } else {
                    console.error('[BancosBRFebraban] Erro:', error.message);
                }
            });
        },
        
        /**
         * Verifica status do serviço
         * @returns {Promise<Object>}
         */
        checkStatus: function() {
            return fetch(this.config.endpointStatus, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .catch(error => ({
                success: false,
                error: error.message
            }));
        },
        
        /**
         * Lista bancos do banco de dados local
         * @returns {Promise<Object>}
         */
        listarBancos: function() {
            return fetch('modulos/servicos/bancos_br_febraban/bancos_br_febraban_logica.php?action=listar', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .catch(error => ({
                success: false,
                error: error.message
            }));
        }
    };
    
    // Auto-inicialização quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.SIGEP.Servicos.BancosBRFebraban.init();
        });
    } else {
        // DOM já carregado
        window.SIGEP.Servicos.BancosBRFebraban.init();
    }
    
})();
