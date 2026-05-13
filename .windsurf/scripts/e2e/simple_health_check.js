/**
 * Simple SIGEP Health Check
 * Versão simplificada que funciona!
 */

const puppeteer = require('puppeteer');

async function testSIGEP() {
    let browser;
    
    try {
        console.log('🏥 Iniciando teste SIGEP...');
        
        // Inicializar navegador
        browser = await puppeteer.launch({
            headless: false,
            executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            args: ['--no-sandbox']
        });
        
        const page = await browser.newPage();
        
        // 1. Navegar para login
        console.log('🌐 Navegando para SIGEP...');
        await page.goto('http://sigep.pij.local/autenticacao', { waitUntil: 'networkidle2' });
        
        // 2. Verificar se página de login carregou
        const title = await page.title();
        console.log(`📄 Página: ${title}`);
        
        // 3. Preencher login
        console.log('🔐 Fazendo login...');
        await page.type('input[name="usuario"]', 'cascade');
        await page.type('input[name="senha"]', 'cascade123');
        
        // 4. Encontrar e clicar no botão (abordagem genérica)
        await page.evaluate(() => {
            // Procurar qualquer botão ou input que possa ser de submit
            const buttons = document.querySelectorAll('button, input[type="submit"], .btn, [onclick*="login"]');
            for (let btn of buttons) {
                if (btn.textContent.includes('Entrar') || 
                    btn.textContent.includes('Login') || 
                    btn.type === 'submit' ||
                    btn.className.includes('login')) {
                    btn.click();
                    break;
                }
            }
        });
        
        // 5. Esperar redirecionamento
        await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 });
        
        // 6. Verificar se logou
        const currentUrl = page.url();
        const pageTitle = await page.title();
        
        console.log(`📍 URL após login: ${currentUrl}`);
        console.log(`📄 Título: ${pageTitle}`);
        
        // 7. Verificar elementos do dashboard
        const hasMainContent = await page.$('#main-content') !== null;
        const hasSidebar = await page.$('.sidebar') !== null;
        const hasUser = await page.$eval('body', body => body.innerText.includes('cascade'));
        
        console.log(`✅ Main content: ${hasMainContent}`);
        console.log(`✅ Sidebar: ${hasSidebar}`);
        console.log(`✅ Usuário cascade: ${hasUser}`);
        
        // 8. Tirar screenshot
        await page.screenshot({ path: 'screenshots/health_check.png', fullPage: true });
        console.log('📸 Screenshot salvo');
        
        // 9. Verificar status
        const isHealthy = hasMainContent && hasUser && !currentUrl.includes('autenticacao');
        
        if (isHealthy) {
            console.log('🎉 SIGEP está SAUDÁVEL!');
            return 0;
        } else {
            console.log('⚠️ SIGEP com problemas');
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

// Executar
testSIGEP().then(code => process.exit(code)).catch(console.error);