/**
 * SIGEP Health Check - Versão CORRIGIDA
 * Seletores corretos encontrados no código-fonte!
 */

const puppeteer = require('puppeteer');

async function testSIGEPLogin() {
    let browser;
    
    try {
        console.log('🏥 Iniciando teste SIGEP E2E...');
        
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
        
        // 2. Verificar se página carregou
        const title = await page.title();
        console.log(`📄 Página: ${title}`);
        
        // 3. Preencher formulário COM SELETORES CORRETOS
        console.log('🔐 Preenchendo formulário...');
        
        // Esperar campos aparecerem
        await page.waitForSelector('input[name="u"]', { timeout: 5000 });
        await page.waitForSelector('input[name="s"]', { timeout: 5000 });
        
        // Preencher usuário
        await page.type('input[name="u"]', 'cascade');
        console.log('✅ Usuário preenchido');
        
        // Preencher senha
        await page.type('input[name="s"]', 'cascade123');
        console.log('✅ Senha preenchida');
        
        // 4. Clicar no botão CORRETO
        console.log('🖱️ Clicando no botão Entrar...');
        
        // Esperar o botão e clicar
        await page.waitForSelector('button.btn', { timeout: 5000 });
        await page.click('button.btn');
        console.log('✅ Botão clicado');
        
        // 5. Esperar redirecionamento
        console.log('⏳ Aguardando redirecionamento...');
        await page.waitForNavigation({ 
            waitUntil: 'networkidle2', 
            timeout: 10000 
        });
        
        // 6. Verificar se logou com sucesso
        const currentUrl = page.url();
        const pageTitle = await page.title();
        
        console.log(`📍 URL após login: ${currentUrl}`);
        console.log(`📄 Título: ${pageTitle}`);
        
        // 7. Verificar elementos do dashboard
        const hasMainContent = await page.$('#main-content') !== null;
        const hasSidebar = await page.$('.sidebar') !== null;
        const pageContent = await page.content();
        const hasUser = pageContent.includes('cascade') || pageContent.includes('Bem-vindo');
        
        console.log(`✅ Main content: ${hasMainContent}`);
        console.log(`✅ Sidebar: ${hasSidebar}`);
        console.log(`✅ Usuário logado: ${hasUser}`);
        
        // 8. Tirar screenshot
        await page.screenshot({ path: 'screenshots/login_success.png', fullPage: true });
        console.log('📸 Screenshot salvo: screenshots/login_success.png');
        
        // 9. Determinar status
        const isLoggedIn = !currentUrl.includes('autenticacao') && 
                          (hasMainContent || hasUser);
        
        if (isLoggedIn) {
            console.log('🎉 SIGEP LOGIN REALIZADO COM SUCESSO!');
            console.log('📊 Resumo:');
            console.log(`   - Login: SUCESSO`);
            console.log(`   - Dashboard: ${hasMainContent ? 'OK' : 'Não encontrado'}`);
            console.log(`   - Usuário: ${hasUser ? 'OK' : 'Não encontrado'}`);
            console.log(`   - URL final: ${currentUrl}`);
            return 0;
        } else {
            console.log('⚠️ Login falhou ou redirecionado para página de login');
            
            // Verificar se tem mensagem de erro
            const hasError = pageContent.includes('incorretas') || 
                            pageContent.includes('erro') || 
                            pageContent.includes('inválidas');
            
            if (hasError) {
                console.log('❌ Mensagem de erro detectada');
            }
            
            await page.screenshot({ path: 'screenshots/login_error.png', fullPage: true });
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
console.log('🚀 SIGEP E2E Health Check - Versão CORRIGIDA');
console.log('📍 Seletores: input[name="u"], input[name="s"], button.btn');
console.log('');

testSIGEPLogin().then(code => {
    console.log(`\n🏁 Finalizado com exit code: ${code}`);
    process.exit(code);
}).catch(error => {
    console.error('Erro fatal:', error);
    process.exit(2);
});