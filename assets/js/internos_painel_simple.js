// assets/js/internos_painel_simple.js
// JavaScript simplificado para o painel de internos - Versão SPA segura

// Proteger contra múltiplos carregamentos no SPA
if (typeof window.internosPainelLoaded === 'undefined') {
    window.internosPainelLoaded = true;

    // Variáveis globais
    window.pageTitle = 'Painel de Internos';
    window.timeoutBusca = null;

    // Inicialização quando o documento estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        inicializarBusca();
        inicializarEventListeners();
    });

    // Função de inicialização da busca
    function inicializarBusca() {
        const inputBusca = document.getElementById('busca_interno');
        const resultadosBusca = document.getElementById('busca_resultados');

        if (inputBusca && resultadosBusca) {
            inputBusca.addEventListener('input', function(e) {
                clearTimeout(window.timeoutBusca);
                const termo = e.target.value.trim();

                if (termo.length < 2) {
                    resultadosBusca.style.display = 'none';
                    inputBusca.classList.remove('busca-loading');
                    return;
                }

                inputBusca.classList.add('busca-loading');

                window.timeoutBusca = setTimeout(async function() {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'search_interno');
                        formData.append('termo', termo);

                        const response = await fetch('includes/internos_painel_logica.php', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();
                        
                        inputBusca.classList.remove('busca-loading');

                        if (data.success && data.data.length > 0) {
                            exibirResultados(data.data);
                        } else {
                            resultadosBusca.style.display = 'none';
                        }
                    } catch (error) {
                        console.error('Erro na busca:', error);
                        inputBusca.classList.remove('busca-loading');
                    }
                }, 300);
            });

            // Fechar resultados ao clicar fora
            document.addEventListener('click', function(e) {
                if (!inputBusca.contains(e.target) && !resultadosBusca.contains(e.target)) {
                    resultadosBusca.style.display = 'none';
                }
            });
        }
    }

    // Exibir resultados da busca
    function exibirResultados(internos) {
        const resultadosBusca = document.getElementById('busca_resultados');
        if (!resultadosBusca) return;

        let html = '';
        internos.forEach(function(interno) {
            html += `
                <div class="busca-item" onclick="selecionarInterno(${interno.id}, '${interno.nome}', '${interno.galeria}', '${interno.cela}')">
                    <div class="busca-item-nome">${interno.nome}</div>
                    <div class="busca-item-info">${interno.galeria} - Cela ${interno.cela}</div>
                </div>
            `;
        });

        resultadosBusca.innerHTML = html;
        resultadosBusca.style.display = 'block';
    }

    // Selecionar interno da busca
    window.selecionarInterno = function(id, nome, galeria, cela) {
        const inputBusca = document.getElementById('busca_interno');
        const resultadosBusca = document.getElementById('busca_resultados');

        if (inputBusca) {
            inputBusca.value = nome;
        }
        
        if (resultadosBusca) {
            resultadosBusca.style.display = 'none';
        }

        // Abrir informações do interno
        abrirInformacoesInterno(id);
    };

    // Abrir informações do interno
    function abrirInformacoesInterno(internoId) {
        // Implementar lógica para abrir modal ou offcanvas
        console.log('Abrindo informações do interno:', internoId);
    }

    // Inicializar event listeners gerais
    function inicializarEventListeners() {
        // Tecla ESC para fechar modais
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharModais();
            }
        });
    }

    // Fechar todos os modais
    function fecharModais() {
        // Implementar lógica para fechar modais
        console.log('Fechando modais');
    }

    // Exportar funções para uso global
    window.internosPainelUtils = {
        selecionarInterno: window.selecionarInterno,
        abrirInformacoesInterno: abrirInformacoesInterno,
        fecharModais: fecharModais
    };

} // fim do bloco de proteção
