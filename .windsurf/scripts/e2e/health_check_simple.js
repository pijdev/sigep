/**
 * SIGEP Health Check - Versão Simples
 * Verifica se o sistema está online via HTTP
 */

const http = require('http');
const https = require('https');

function checkURL(url) {
    return new Promise((resolve, reject) => {
        const client = url.startsWith('https') ? https : http;

        const req = client.get(url, (res) => {
            let data = '';
            res.on('data', chunk => data += chunk);
            res.on('end', () => {
                resolve({
                    status: res.statusCode,
                    headers: res.headers,
                    body: data.substring(0, 1000) // Primeiros 1000 chars
                });
            });
        });

        req.on('error', reject);
        req.setTimeout(10000, () => {
            req.destroy();
            reject(new Error('Timeout'));
        });
    });
}

async function testSIGEPHealth() {
    try {
        console.log('🏥 Verificando saúde do SIGEP...');

        // 1. Testar página principal
        console.log('🌐 Testando página principal...');
        const mainPage = await checkURL('http://sigep.pij.local/');
        console.log(`✅ Página principal: ${mainPage.status}`);

        // 2. Testar página de login
        console.log('🔐 Testando página de login...');
        const loginPage = await checkURL('http://sigep.pij.local/autenticacao');
        console.log(`✅ Página login: ${loginPage.status}`);

        // 3. Verificar conteúdo da página de login
        const hasLoginForm = loginPage.body.includes('usuario') ||
                           loginPage.body.includes('senha') ||
                           loginPage.body.includes('login') ||
                           loginPage.body.includes('Login');

        console.log(`✅ Formulário login: ${hasLoginForm ? 'Encontrado' : 'Não encontrado'}`);

        // 4. Verificar se é SIGEP
        const isSIGEP = loginPage.body.includes('SIGEP') ||
                       mainPage.body.includes('SIGEP');

        console.log(`✅ Sistema SIGEP: ${isSIGEP ? 'Identificado' : 'Não identificado'}`);

        // 5. Status final
        const isHealthy = (mainPage.status === 200 || mainPage.status === 302) &&
                         loginPage.status === 200 &&
                         hasLoginForm &&
                         isSIGEP;

        if (isHealthy) {
            console.log('🎉 SIGEP está SAUDÁVEL e ONLINE!');
            console.log('📊 Resumo:');
            console.log(`   - Página principal: ${mainPage.status}`);
            console.log(`   - Página login: ${loginPage.status}`);
            console.log(`   - Formulário: ${hasLoginForm ? 'OK' : 'ERRO'}`);
            console.log(`   - Sistema: ${isSIGEP ? 'SIGEP' : 'Desconhecido'}`);
            return 0;
        } else {
            console.log('⚠️ SIGEP com problemas:');
            if (mainPage.status !== 200) console.log(`   - Página principal: ${mainPage.status}`);
            if (loginPage.status !== 200) console.log(`   - Página login: ${loginPage.status}`);
            if (!hasLoginForm) console.log('   - Formulário login: Não encontrado');
            if (!isSIGEP) console.log('   - Sistema: Não é SIGEP');
            return 1;
        }

    } catch (error) {
        console.error('❌ Erro ao verificar SIGEP:', error.message);
        return 2;
    }
}

// Executar teste
testSIGEPHealth().then(code => {
    process.exit(code);
}).catch(error => {
    console.error('Erro fatal:', error);
    process.exit(2);
});
