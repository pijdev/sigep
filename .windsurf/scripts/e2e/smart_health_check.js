/**
 * SIGEP Smart Health Check - Versão INTELIGENTE
 * Consulta o código-fonte em tempo real para encontrar seletores corretos
 */

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

class SIGEPSmartChecker {
    constructor() {
        this.loginFilePath = path.join(__dirname, '../../../auth/login.php');
        this.selectors = {
            usuario: null,
            senha: null,
            botao: null,
            csrf: null
        };
    }

    // 1. Analisa o código-fonte do login.php
    async analisarCodigoFonte() {
        console.log('🔍 Analisando código-fonte do login...');

        try {
            const loginCode = fs.readFileSync(this.loginFilePath, 'utf8');

            // Encontrar campo usuário
            const usuarioMatch = loginCode.match(/<input[^>]*name=["']([^"']+)["'][^>]*id=["']?usuario["']?[^>]*>/i);
            if (usuarioMatch) {
                this.selectors.usuario = `input[name="${usuarioMatch[1]}"]`;
                console.log(`✅ Campo usuário: ${this.selectors.usuario}`);
            }

            // Encontrar campo senha
            const senhaMatch = loginCode.match(/<input[^>]*type=["']password["'][^>]*name=["']([^"']+)["'][^>]*id=["']?senha["']?[^>]*>/i);
            if (senhaMatch) {
                this.selectors.senha = `input[name="${senhaMatch[1]}"]`;
                console.log(`✅ Campo senha: ${this.selectors.senha}`);
            }

            // Encontrar botão de submit (priorizar botão principal)
            const botaoMatch = loginCode.match(/<button[^>]*class=["']([^"']*\bbtn\b[^"']*)["'][^>]*>([^<]*(?:Entrar|Login|Sign In)[^<]*)<\/button>/i) ||
                               loginCode.match(/<button[^>]*>([^<]*(?:Entrar|Login|Sign In)[^<]*)<\/button>/i) ||
                               loginCode.match(/<button[^>]*class=["']([^"']*\bbtn\b[^"']*)["'][^>]*>([^<]*)<\/button>/i);
            if (botaoMatch) {
                this.selectors.botao = `button.${botaoMatch[1].split(' ')[0]}`;
                console.log(`✅ Botão: ${this.selectors.botao} ("${botaoMatch[2].trim()}")`);
            }

            // Encontrar CSRF token
            const csrfMatch = loginCode.match(/<input[^>]*type=["']hidden["'][^>]*name=["']csrf_token["'][^>]*>/i);
            if (csrfMatch) {
                this.selectors.csrf = 'input[name="csrf_token"]';
                console.log(`✅ CSRF: ${this.selectors.csrf}`);
            }

            // Verificar se encontrou todos os seletores
            const foundAll = this.selectors.usuario && this.selectors.senha && this.selectors.botao;

            if (foundAll) {
                console.log('🎯 Todos os seletores encontrados no código-fonte!');
                return true;
            } else {
                console.log('⚠️ Alguns seletores não encontrados, usando fallback...');
                return this.usarFallback();
            }

        } catch (error) {
            console.log('❌ Erro ao analisar código-fonte:', error.message);
            return this.usarFallback();
        }
    }

    // 2. Fallback para seletores padrão
    usarFallback() {
        console.log('🔄 Usando seletores fallback...');
        this.selectors = {
            usuario: 'input[name="u"]',
            senha: 'input[name="s"]',
            botao: 'button.btn',
            csrf: 'input[name="csrf_token"]'
        };
        return true;
    }

    // 3. Valida seletores na página em tempo real
    async validarSeletoresNaPagina(page) {
        console.log('🔧 Validando seletores na página...');

        try {
            // Verificar se os seletores existem na página carregada
            const validacao = await page.evaluate((seletores) => {
                const resultados = {};

                // Verificar usuário
                const usuarioEl = document.querySelector(seletores.usuario);
                resultados.usuario = usuarioEl ? {
                    encontrado: true,
                    name: usuarioEl.name,
                    id: usuarioEl.id,
                    type: usuarioEl.type
                } : { encontrado: false };

                // Verificar senha
                const senhaEl = document.querySelector(seletores.senha);
                resultados.senha = senhaEl ? {
                    encontrado: true,
                    name: senhaEl.name,
                    id: senhaEl.id,
                    type: senhaEl.type
                } : { encontrado: false };

                // Verificar botão
                const botaoEl = document.querySelector(seletores.botao);
                resultados.botao = botaoEl ? {
                    encontrado: true,
                    text: botaoEl.textContent.trim(),
                    type: botaoEl.type,
                    class: botaoEl.className
                } : { encontrado: false };

                // Verificar CSRF
                const csrfEl = document.querySelector(seletores.csrf);
                resultados.csrf = csrfEl ? {
                    encontrado: true,
                    name: csrfEl.name,
                    value: csrfEl.value ? csrfEl.value.substring(0, 20) + '...' : null
                } : { encontrado: false };

                return resultados;
            }, this.selectors);

            // Exibir resultados
            Object.entries(validacao).forEach(([campo, resultado]) => {
                if (resultado.encontrado) {
                    console.log(`✅ ${campo}: ${JSON.stringify(resultado)}`);
                } else {
                    console.log(`❌ ${campo}: NÃO encontrado na página`);
                }
            });

            // Verificar se campos essenciais existem
            const essenciaisValidos = validacao.usuario.encontrado &&
                                    validacao.senha.encontrado &&
                                    validacao.botao.encontrado;

            return essenciaisValidos ? validacao : null;

        } catch (error) {
            console.log('❌ Erro na validação:', error.message);
            return null;
        }
    }

    // 4. Executa teste completo
    async executarTeste() {
        let browser;

        try {
            console.log('🚀 SIGEP Smart Health Check - Versão INTELIGENTE');
            console.log('');

            // Passo 1: Analisar código-fonte
            const codigoAnalisado = await this.analisarCodigoFonte();
            if (!codigoAnalisado) {
                throw new Error('Não foi possível determinar os seletores');
            }

            // Passo 2: Inicializar navegador
            console.log('🌐 Inicializando navegador...');
            browser = await puppeteer.launch({
                headless: false,
                executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                args: ['--no-sandbox']
            });

            const page = await browser.newPage();

            // Passo 3: Navegar para página
            console.log('📍 Navegando para página de login...');
            await page.goto('http://sigep.pij.local/autenticacao', { waitUntil: 'networkidle2' });

            const title = await page.title();
            console.log(`📄 Página carregada: ${title}`);

            // Passo 4: Validar seletores na página
            const validacao = await this.validarSeletoresNaPagina(page);
            if (!validacao) {
                throw new Error('Seletores inválidos na página');
            }

            // Passo 5: Preencher formulário
            console.log('🔐 Preenchendo formulário...');

            await page.waitForSelector(this.selectors.usuario, { timeout: 5000 });
            await page.type(this.selectors.usuario, 'cascade');
            console.log('✅ Usuário preenchido');

            await page.waitForSelector(this.selectors.senha, { timeout: 5000 });
            await page.type(this.selectors.senha, 'cascade123');
            console.log('✅ Senha preenchida');

            // Passo 6: Clicar no botão
            console.log('🖱️ Clicando no botão...');
            await page.waitForSelector(this.selectors.botao, { timeout: 5000 });
            await page.click(this.selectors.botao);
            console.log('✅ Botão clicado');

            // Passo 7: Aguardar redirecionamento
            console.log('⏳ Aguardando redirecionamento...');
            await page.waitForNavigation({
                waitUntil: 'networkidle2',
                timeout: 10000
            });

            // Passo 8: Verificar sucesso
            const currentUrl = page.url();
            const isLoggedIn = !currentUrl.includes('autenticacao');

            // Verificar elementos do dashboard
            const dashboardCheck = await page.evaluate(() => {
                const hasMainContent = !!document.querySelector('#main-content');
                const hasSidebar = !!document.querySelector('.sidebar');
                const bodyText = document.body.innerText;
                const hasUser = bodyText.includes('cascade') || bodyText.includes('Bem-vindo');

                return { hasMainContent, hasSidebar, hasUser };
            });

            console.log(`📍 URL final: ${currentUrl}`);
            console.log(`✅ Dashboard: ${dashboardCheck.hasMainContent ? 'OK' : 'Não encontrado'}`);
            console.log(`✅ Sidebar: ${dashboardCheck.hasSidebar ? 'OK' : 'Não encontrado'}`);
            console.log(`✅ Usuário: ${dashboardCheck.hasUser ? 'OK' : 'Não encontrado'}`);

            // Passo 9: Screenshot
            const screenshotName = isLoggedIn ? 'smart_success.png' : 'smart_error.png';
            await page.screenshot({ path: `screenshots/${screenshotName}`, fullPage: true });
            console.log(`📸 Screenshot salvo: screenshots/${screenshotName}`);

            // Passo 10: Resultado final
            const sucesso = isLoggedIn && (dashboardCheck.hasMainContent || dashboardCheck.hasUser);

            if (sucesso) {
                console.log('');
                console.log('🎉 SIGEP SMART CHECK - SUCESSO TOTAL!');
                console.log('📊 Resumo:');
                console.log(`   - Código-fonte: ✅ Analisado`);
                console.log(`   - Seletores: ✅ Validados`);
                console.log(`   - Login: ✅ Sucesso`);
                console.log(`   - Dashboard: ✅ Acessível`);
                console.log(`   - URL: ${currentUrl}`);
                return 0;
            } else {
                console.log('');
                console.log('⚠️ SIGEP SMART CHECK - Falha no login');
                return 1;
            }

        } catch (error) {
            console.error('❌ Erro:', error.message);
            return 2;
        } finally {
            if (browser) {
                await browser.close();
            }
        }
    }
}

// Executar teste inteligente
async function main() {
    const checker = new SIGEPSmartChecker();
    const exitCode = await checker.executarTeste();
    console.log(`\n🏁 Finalizado com exit code: ${exitCode}`);
    process.exit(exitCode);
}

// Tratamento de erros global
process.on('unhandledRejection', (reason, promise) => {
    console.error('❌ Unhandled Rejection:', reason);
    process.exit(2);
});

// Executar
main().catch(error => {
    console.error('Erro fatal:', error);
    process.exit(2);
});
