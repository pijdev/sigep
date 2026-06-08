async function enviarMsgIA() {

    const input = document.getElementById('chatInput');
    const msg = input.value.trim();
    if (!msg) return;

    adicionarMsg(msg, 'user');
    input.value = '';

    const typingId = adicionarTyping();

    try {
        const res = await fetch('/modulos/agente_ia/IAController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ mensagem: msg })
        });

        const data = await res.json();

        removerTyping(typingId);

        if (data.erro) {
            adicionarMsg("❌ Erro: " + data.erro, 'ia');
            return;
        }

        adicionarMsg(data.resposta, 'ia');

    } catch (e) {
        removerTyping(typingId);
        adicionarMsg("❌ Falha na conexão", 'ia');
    }
}

function adicionarMsg(texto, tipo) {

    const chat = document.getElementById('chatBody');

    const align = tipo === 'user' ? 'right' : 'left';
    const nome = tipo === 'user' ? 'Você' : 'Agente';
    const img = tipo === 'user'
        ? '/src/img/avatar5.png'
        : '/src/img/ia_agent.png';

    const html = `
        <div class="direct-chat-msg ${tipo === 'user' ? 'right' : ''}">
            <div class="direct-chat-infos clearfix">
                <span class="direct-chat-name float-${align}">${nome}</span>
            </div>
            <img class="direct-chat-img" src="${img}">
            <div class="direct-chat-text">${texto}</div>
        </div>
    `;

    chat.insertAdjacentHTML('beforeend', html);
    chat.scrollTop = chat.scrollHeight;
}

function adicionarTyping() {
    const id = 'typing_' + Date.now();
    const chat = document.getElementById('chatBody');

    const html = `
        <div id="${id}" class="direct-chat-msg">
            <img class="direct-chat-img" src="/src/img/ia_agent.png">
            <div class="direct-chat-text">
                <i class="fas fa-circle-notch fa-spin"></i> digitando...
            </div>
        </div>
    `;

    chat.insertAdjacentHTML('beforeend', html);
    chat.scrollTop = chat.scrollHeight;

    return id;
}

function removerTyping(id) {
    const el = document.getElementById(id);
    if (el) el.remove();
}

// Expor funções para escopo global (necessário para chamadas inline no HTML)
window.enviarMsgIA = enviarMsgIA;
window.adicionarMsg = adicionarMsg;
window.adicionarTyping = adicionarTyping;
window.removerTyping = removerTyping;