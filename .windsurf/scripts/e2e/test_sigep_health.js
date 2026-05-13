/**
 * SIGEP E2E Health Check Test
 * Valida se o sistema está online e funcional
 *
 * Uso: node test_sigep_health.js
 *
 * Dependências: npm install puppeteer
 */

const puppeteer = require('puppeteer');

// Configurações do teste
const CONFIG = {
    url: 'http://sigep.pij.local/',
    usuario: 'cascade',
    senha: 'cascade123',
    timeout: 30000,
    headless: process.env.NODE_ENV === 'production' ? 'new' : false
};

// Indicadores de sucesso
const SUCCESS_INDICATORS = [
    'SIGEP',           // Título principal
    'Dashboard',       // Título do dashboard
    'Bem-vindo',       // Mensagem de boas-vindas
    'cascade',         // Nome do usuário
    'sidebar',         // Menu lateral
    'main-content'     // Conteúdo principal
];

const ERROR_INDICATORS = [
    'Error',
    'Fatal error',
    'Access denied',
    'Login inválido',
    'Sessão expirada',
    '404',
    '500'
];

class SIGEPHealthChecker {
    constructor() {
        this.browser = null;
        this.page = null;
        this.results = {
            timestamp: new Date().toISOString(),
            status: 'unknown',
            steps: [],
            errors: [],
            performance: {}
        };
    }

    async init() {
        try {
            console.log('🚀 Inicializando Puppeteer...');
            this.browser = await puppeteer.launch({
                headless: CONFIG.headless,
                executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-web-security',
                    '--ignore-certificate-errors'
                ]
            });

            this.page = await this.browser.newPage();
            await this.page.setViewport({ width: 1366, height: 768 });

            // Configurar timeout
            this.page.setDefaultTimeout(CONFIG.timeout);
            this.page.setDefaultNavigationTimeout(CONFIG.timeout);

            // Capturar console errors
            this.page.on('console', msg => {
                if (msg.type() === 'error') {
                    this.results.errors.push({
                        type: 'console_error',
                        message: msg.text(),
                        timestamp: new Date().toISOString()
                    });
                }
            });

            // Capturar page errors
            this.page.on('pageerror', error => {
                this.results.errors.push({
                    type: 'page_error',
                    message: error.message,
                    timestamp: new Date().toISOString()
                });
            });

            this.addStep('Puppeteer inicializado com sucesso');
            return true;
        } catch (error) {
            this.addStep(`Erro ao inicializar Puppeteer: ${error.message}`, 'error');
            return false;
        }
    }

    async navigateToLogin() {
        try {
            console.log('🌐 Navegando para página de login...');
            const startTime = Date.now();

            await this.page.goto(CONFIG.url, {
                waitUntil: 'networkidle2',
                timeout: CONFIG.timeout
            });

            const loadTime = Date.now() - startTime;
            this.results.performance.pageLoad = loadTime;

            // Verificar se página carregou
            const title = await this.page.title();
            const url = this.page.url();

            this.addStep(`Página carregada: ${title} (${url}) - ${loadTime}ms`);

            // Verificar se é página de login ou se já está logado
            const isLoginPage = await this.page.evaluate(() => {
                return document.body.innerText.includes('Login') ||
                       document.body.innerText.includes('Usuário') ||
                       document.body.innerText.includes('Senha');
            });

            if (isLoginPage) {
                this.addStep('Página de login detectada');
                return 'login';
            } else {
                this.addStep('Usuário já está logado ou redirecionado');
                return 'logged';
            }
        } catch (error) {
            this.addStep(`Erro na navegação: ${error.message}`, 'error');
            return 'error';
        }
    }

    async performLogin() {
        try {
            console.log('🔐 Realizando login...');
            const startTime = Date.now();

            // Preencher formulário de login
            await this.page.waitForSelector('input[name="usuario"], input[id="usuario"]', { timeout: 5000 });
            await this.page.type('input[name="usuario"], input[id="usuario"]', CONFIG.usuario);

            await this.page.waitForSelector('input[name="senha"], input[id="senha"]', { timeout: 5000 });
            await this.page.type('input[name="senha"], input[id="senha"]', CONFIG.senha);

            // Clicar no botão de login
            await this.page.click('button[type="submit"], input[type="submit"], .btn-login, .btn-primary[type="submit"]');

            // Esperar redirecionamento
            await this.page.waitForNavigation({
                waitUntil: 'networkidle2',
                timeout: CONFIG.timeout
            });

            const loginTime = Date.now() - startTime;
            this.results.performance.login = loginTime;

            // Verificar se login foi bem-sucedido
            const loginSuccess = await this.checkLoginSuccess();

            if (loginSuccess) {
                this.addStep(`Login realizado com sucesso - ${loginTime}ms`);
                return true;
            } else {
                this.addStep('Login falhou - credenciais inválidas ou erro', 'error');
                return false;
            }
        } catch (error) {
            this.addStep(`Erro no login: ${error.message}`, 'error');
            return false;
        }
    }

    async checkLoginSuccess() {
        try {
            // Verificar indicadores de erro
            const pageContent = await this.page.content();
            const hasError = ERROR_INDICATORS.some(indicator =>
                pageContent.toLowerCase().includes(indicator.toLowerCase())
            );

            if (hasError) {
                return false;
            }

            // Verificar indicadores de sucesso
            const hasSuccess = SUCCESS_INDICATORS.some(indicator =>
                pageContent.toLowerCase().includes(indicator.toLowerCase())
            );

            // Verificar URL (não deve ser página de login)
            const currentUrl = this.page.url();
            const isNotLoginPage = !currentUrl.includes('login') &&
                                   !currentUrl.includes('auth');

            return hasSuccess && isNotLoginPage;
        } catch (error) {
            console.error('Erro ao verificar sucesso do login:', error);
            return false;
        }
    }

    async validateDashboard() {
        try {
            console.log('📊 Validando dashboard...');
            const startTime = Date.now();

            // Esperar carregamento do dashboard
            await this.page.waitForSelector('#main-content, .content, .dashboard', { timeout: 10000 });

            // Verificar elementos do dashboard
            const dashboardChecks = await this.page.evaluate(() => {
                const checks = {
                    hasMainContent: !!document.querySelector('#main-content'),
                    hasSidebar: !!document.querySelector('.sidebar, aside'),
                    hasUserInfo: !!document.querySelector('.user-info, .navbar-nav'),
                    hasCards: !!document.querySelector('.card, .box'),
                    title: document.title,
                    url: window.location.href
                };

                // Contar elementos dinâmicos
                checks.dynamicElements = {
                    buttons: document.querySelectorAll('button').length,
                    links: document.querySelectorAll('a').length,
                    forms: document.querySelectorAll('form').length,
                    tables: document.querySelectorAll('table').length
                };

                return checks;
            });

            const validationTime = Date.now() - startTime;
            this.results.performance.validation = validationTime;

            // Validar checks
            const validations = [
                dashboardChecks.hasMainContent,
                dashboardChecks.hasSidebar,
                dashboardChecks.hasUserInfo,
                dashboardChecks.dynamicElements.buttons > 0
            ];

            const allValid = validations.every(v => v);

            if (allValid) {
                this.addStep(`Dashboard validado com sucesso - ${validationTime}ms`);
                this.addStep(`Elementos encontrados: ${JSON.stringify(dashboardChecks.dynamicElements)}`);
                return true;
            } else {
                this.addStep('Dashboard incompleto ou com problemas', 'error');
                return false;
            }
        } catch (error) {
            this.addStep(`Erro na validação do dashboard: ${error.message}`, 'error');
            return false;
        }
    }

    async run() {
        try {
            console.log('🏥 Iniciando Health Check do SIGEP...');

            // 1. Inicializar
            const initSuccess = await this.init();
            if (!initSuccess) {
                this.results.status = 'failed';
                return this.results;
            }

            // 2. Navegar
            const navigationResult = await this.navigateToLogin();
            if (navigationResult === 'error') {
                this.results.status = 'failed';
                await this.takeScreenshot('navigation_error.png');
                return this.results;
            }

            // 3. Login (se necessário)
            let loginSuccess = true;
            if (navigationResult === 'login') {
                loginSuccess = await this.performLogin();
                if (!loginSuccess) {
                    this.results.status = 'failed';
                    await this.takeScreenshot('login_error.png');
                    return this.results;
                }
            }

            // 4. Validar Dashboard
            const dashboardValid = await this.validateDashboard();
            if (!dashboardValid) {
                this.results.status = 'partial';
                await this.takeScreenshot('dashboard_error.png');
            } else {
                this.results.status = 'healthy';
                await this.takeScreenshot('dashboard_success.png');
            }

            return this.results;

        } catch (error) {
            this.addStep(`Erro inesperado: ${error.message}`, 'error');
            this.results.status = 'failed';
            await this.takeScreenshot('unexpected_error.png');
            return this.results;
        }
    }

    async cleanup() {
        try {
            if (this.browser) {
                await this.browser.close();
                this.addStep('Browser fechado');
            }
        } catch (error) {
            console.error('Erro no cleanup:', error);
        }
    }

    addStep(message, type = 'info') {
        const step = {
            message,
            type,
            timestamp: new Date().toISOString()
        };
        this.results.steps.push(step);
        console.log(`[${type.toUpperCase()}] ${message}`);
    }

    generateReport() {
        const report = {
            ...this.results,
            summary: {
                status: this.results.status,
                totalSteps: this.results.steps.length,
                errors: this.results.errors.length,
                performance: this.results.performance
            }
        };

        return report;
    }
}

// Função principal
async function main() {
    const checker = new SIGEPHealthChecker();

    try {
        const results = await checker.run();
        const report = checker.generateReport();

        // Exibir relatório
        console.log('\n' + '='.repeat(50));
        console.log('📋 RELATÓRIO FINAL - SIGEP HEALTH CHECK');
        console.log('='.repeat(50));
        console.log(`Status: ${report.status.toUpperCase()}`);
        console.log(`Timestamp: ${report.timestamp}`);
        console.log(`Passos: ${report.summary.totalSteps}`);
        console.log(`Erros: ${report.summary.errors}`);

        if (report.performance.pageLoad) {
            console.log(`Carregamento página: ${report.performance.pageLoad}ms`);
        }
        if (report.performance.login) {
            console.log(`Tempo login: ${report.performance.login}ms`);
        }
        if (report.performance.validation) {
            console.log(`Validação dashboard: ${report.performance.validation}ms`);
        }

        // Salvar relatório
        const fs = require('fs');
        const reportPath = 'health_report.json';
        fs.writeFileSync(reportPath, JSON.stringify(report, null, 2));
        console.log(`\n📄 Relatório salvo: ${reportPath}`);

        // Exit code baseado no status
        process.exit(report.status === 'healthy' ? 0 : 1);

    } catch (error) {
        console.error('Erro fatal:', error);
        process.exit(2);
    } finally {
        await checker.cleanup();
    }
}

// Executar se chamado diretamente
if (require.main === module) {
    main().catch(console.error);
}

module.exports = SIGEPHealthChecker;
