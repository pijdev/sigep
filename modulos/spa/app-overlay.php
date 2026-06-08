<?php
use Config\App;
?>

<div class='offcanvas-custom' id='off_ia' style='visibility: hidden; position: fixed; top: 0; right: 0; width: 600px; height: 100%; z-index: 1050; background: #fff; border-left: 1px solid #ddd; transition: transform .3s ease-in-out; transform: translateX(100%); display:flex; flex-direction:column;'>
    <div class='p-3 border-bottom d-flex justify-content-between align-items-center' id="offcanvasHeader" style="background: #f4f6f9; border-bottom: 1px solid rgba(0,0,0,.12) !important;">
        <div class='d-flex align-items-center'>
            <div class="bg-primary text-white d-flex align-items-center justify-content-center mr-2 shadow-sm" style="width: 32px; height: 32px; border-radius: 8px;">
                <i class='fas fa-robot text-sm'></i>
            </div>
            <div>
                <h6 class='m-0 font-weight-bold d-flex align-items-center' id="offcanvasTitle" style="letter-spacing: 0.5px; color: #343a40;">
                    <?php echo App::APP_NAME_SHORT; ?> AI
                    <span class="badge badge-success ml-2 px-2 py-1 text-xs font-weight-normal d-flex align-items-center" style="border-radius: 10px; font-size: 10px;">
                        <i class="fas fa-circle text-xs mr-1 text-white" style="font-size: 6px;"></i> ONLINE
                    </span>
                </h6>
                <small class="text-muted" style="font-size: 11px; display: block; margin-top: -2px;">Assistente Virtual Integrado</small>
            </div>
        </div>
        <button class='btn btn-sm btn-secondary' onclick="window.exibOff('off_ia', false)">
            <i class='fas fa-chevron-right'></i>
        </button>
    </div>

    <div id="chatBody" style="flex:1; overflow-y:auto; padding:15px; background:#f4f6f9;">
        <div class="direct-chat-msg">
            <div class="direct-chat-infos clearfix">
                <span class="direct-chat-name float-left">Agente</span>
            </div>
            <img class="direct-chat-img" src="/src/img/ia_agent.png">
            <div class="direct-chat-text">
                <span>👋 Olá <strong><?php echo $_SESSION['user_nome']; ?></strong>! Como posso ajudar?</span>
                <span class="badge bg-warning">Mas aviso!</span>
                <span class="text-muted" style="font-size: 0.8em; display:block; margin-top:5px;">Estou em desenvolvimento, posso errar nas respostas! Faça perguntas diretas. Pergunte algo como "Quantos internos na SE-3?", "Quais são os internos LGBT?" ou "Quem está de portaria?"</span>
            </div>
        </div>
    </div>

    <div class="p-2 border-top bg-white" id="offcanvasFooter">
        <div class="input-group">
            <input type="text" id="chatInput" class="form-control" placeholder="Digite sua mensagem..."
                onkeypress="if(event.key==='Enter') enviarMsgIA()">
            <div class="input-group-append">
                <button class="btn btn-primary" onclick="enviarMsgIA()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .dark-mode #off_ia {
        background: #343a40 !important;
        border-left-color: #4b545c !important;
    }

    .dark-mode #offcanvasHeader {
        background: #3f474e !important;
        border-bottom-color: #4b545c !important;
    }

    .dark-mode #offcanvasTitle {
        color: #fff !important;
    }

    .dark-mode #chatBody {
        background: #454d55 !important;
    }

    .dark-mode #offcanvasFooter {
        background: #343a40 !important;
        border-top-color: #4b545c !important;
    }
</style>