    // ===============================
    // GERADOR DE TELA DE ERRO (NOVO)
    // ===============================
    function gerarTelaErro(codigo) {

        let config = {
            titulo: 'Erro inesperado',
            mensagem: 'Algo deu errado.',
            cor: '#343a40',
            icone: '⚠️',
            animacao: 'shake'
        };

        switch (parseInt(codigo)) {
            case 404:
                config = {
                    titulo: 'Página não encontrada',
                    mensagem: 'A página que você procura não existe ou foi movida.',
                    cor: '#17a2b8',
                    icone: '🔍',
                    animacao: 'float'
                };
                break;

            case 403:
                config = {
                    titulo: 'Acesso negado',
                    mensagem: 'Você não tem permissão para acessar esta página.',
                    cor: '#ffc107',
                    icone: '⛔',
                    animacao: 'pulse'
                };
                break;

            case 500:
                config = {
                    titulo: 'Erro interno do servidor',
                    mensagem: 'Estamos trabalhando para corrigir isso.',
                    cor: '#dc3545',
                    icone: '💥',
                    animacao: 'shake'
                };
                break;

            case 503:
                config = {
                    titulo: 'Serviço indisponível',
                    mensagem: 'Servidor temporariamente fora do ar.',
                    cor: '#6c757d',
                    icone: '🛠️',
                    animacao: 'spin'
                };
                break;
        }

        return `
        <style>
            .erro-container {
                display:flex;
                flex-direction:column;
                align-items:center;
                justify-content:center;
                height:70vh;
                text-align:center;
                animation: fadeIn 0.4s ease;
            }

            .erro-icon {
                font-size:80px;
                margin-bottom:10px;
                animation: ${config.animacao} 1.5s infinite;
            }

            .erro-codigo {
                font-size:80px;
                margin:10px 0;
                color:${config.cor};
            }

            .erro-btn {
                margin-top:20px;
                padding:10px 20px;
                border:none;
                background:${config.cor};
                color:white;
                border-radius:6px;
                cursor:pointer;
            }

            @keyframes fadeIn {
                from {opacity:0; transform:translateY(10px);}
                to {opacity:1; transform:translateY(0);}
            }

            @keyframes shake {
                0%,100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }

            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }

            @keyframes float {
                0% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
                100% { transform: translateY(0); }
            }

            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

        <div class="erro-container">
            <div class="erro-icon">${config.icone}</div>
            <div class="erro-codigo">${codigo}</div>
            <h3>${config.titulo}</h3>
            <p class="text-muted">${config.mensagem}</p>

            <button class="erro-btn" onclick="location.reload()">Tentar novamente</button>
            <a href="/" class="mt-2" style="color:${config.cor}">Voltar ao início</a>
        </div>
        `;
    }

    function mostrarErro(codigo) {
        const mainContent = document.getElementById('main-content');
        mainContent.innerHTML = gerarTelaErro(codigo);
    }

    // ===============================
    // FUNÇÃO ORIGINAL (EDITADA)
    // ===============================
    async function loadPage(pageUrl, title = 'Painel', parent = 'SIGEP') {
        console.log('loadPage chamada com:', pageUrl, title, parent);

        if ($('body').hasClass('sidebar-open')) $('[data-widget="pushmenu"]').PushMenu('toggle');

        document.getElementById('breadcrumb-parent').innerText = parent;
        document.getElementById('breadcrumb-title').innerText = title;
        document.getElementById('content-main-title').innerText = title;

        const mainContent = document.getElementById('main-content');
        mainContent.innerHTML = `<div class="text-center p-5"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i></div>`;

        try {
            const response = await fetch(pageUrl);

            // 👇 MELHOR DETECÇÃO DE ERRO
            if (!response.ok) {
                throw new Error(response.status);
            }

            const html = await response.text();

            // Detecta JSON de erro (mantido)
            if (html.trim().startsWith('{') && html.includes('Acesso Negado')) {
                mostrarErro(403);
                return;
            }

            mainContent.innerHTML = html;

            setTimeout(() => {
                document.getElementById('breadcrumb-parent').innerText = parent;
                document.getElementById('breadcrumb-title').innerText = title;
                document.getElementById('content-main-title').innerText = title;
            }, 100);

            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });

            await new Promise(resolve => setTimeout(resolve, 100));

            if (typeof window.updateSortVariables === 'function') {
                window.updateSortVariables();
            }

            if (window.pageTitle) {
                title = window.pageTitle;
                document.getElementById('breadcrumb-title').innerText = title;
                document.getElementById('content-main-title').innerText = title;
            }

        } catch (e) {
            console.error('Erro ao carregar página:', e);

            let codigoErro = parseInt(e.message) || 500;

            // 👇 AGORA USA SISTEMA INTERNO (SEM PHP)
            mostrarErro(codigoErro);
        }
    }

    // ===============================
    // ERRO GLOBAL JS
    // ===============================
    window.onerror = function() {
        mostrarErro(500);
    };

    document.addEventListener('DOMContentLoaded', function() {
        // AUTLOAD (mantido)
    });