<!-- FORMULÁRIO DE EDIÇÃO DE CADASTRO DE INTERNO - Reutilizável em Cadastro de Internos e Rouparia -->
<input type="hidden" name="acao" value="salvar_cadastro">
<input type="hidden" name="ipen" id="edit_ipen">

<!-- INFO BOX ESTÁTICO -->
<div class="info-box-static">
    <div class="row small">
        <div class="col-6"><span class="info-label">IPEN</span><span class="info-value" id="disp_ipen">-</span></div>
        <div class="col-6"><span class="info-label">Situação</span><span class="info-value" id="disp_situacao">-</span></div>
    </div>
    <div class="row small mt-2">
        <div class="col-12"><span class="info-label">Nome</span><span class="info-value" id="disp_nome">-</span></div>
    </div>
    <div class="row small mt-2">
        <div class="col-6"><span class="info-label">Galeria</span><span class="info-value" id="disp_gal">-</span></div>
        <div class="col-6"><span class="info-label">Bloco</span><span class="info-value" id="disp_blo">-</span></div>
    </div>
    <div class="row small mt-2">
        <div class="col-6"><span class="info-label">Cela</span><span class="info-value" id="disp_cela">-</span></div>
    </div>
</div>

<!-- IDENTIDADE & SOCIAL -->
<div class="form-section-title"><i class="fas fa-id-card"></i> Identidade & Social</div>
<div class="px-3 pb-3">
    <div class="form-group">
        <label class="small font-weight-bold text-muted">Nome Social</label>
        <input type="text" class="form-control form-control-sm input-monitor" name="nome_social" id="edit_social" placeholder="Ex: Silvana">
    </div>
    <div class="form-group">
        <label class="small font-weight-bold text-muted">É LGBT?</label>
        <select class="form-control form-control-sm input-monitor" name="lgbt" id="edit_lgbt">
            <option value="N">Não</option>
            <option value="S">Sim</option>
        </select>
    </div>
    <div class="form-group">
        <label class="small font-weight-bold text-muted">Apelido / Vulgo</label>
        <input type="text" class="form-control form-control-sm input-monitor" name="apelido" id="edit_apelido" placeholder="Ex: Neguinho">
    </div>
</div>

<!-- DADOS LABORAIS -->
<div class="form-section-title"><i class="fas fa-briefcase"></i> Laboral</div>
<div class="px-3 pb-3">
    <div class="form-group">
        <label class="small font-weight-bold text-muted">Forma de Pagamento</label>
        <select class="form-control form-control-sm input-monitor" name="forma_pagamento" id="edit_pagamento">
            <option value="">Não Informado</option>
            <option value="Pecúlio">Pecúlio</option>
            <option value="Pix">Pix</option>
            <option value="Depósito">Depósito</option>
            <option value="Salário">Salário</option>
        </select>
    </div>
</div>

<!-- CENSURA & ROUPARIA -->
<div class="form-section-title"><i class="fas fa-tshirt"></i> Censura & Rouparia</div>
<div class="px-3 pb-3">
    <div class="row">
        <div class="col-6">
            <div class="form-group">
                <label class="small font-weight-bold text-muted">É Regalia?</label>
                <select class="form-control form-control-sm input-monitor" name="regalia" id="edit_regalia">
                    <option value="N">Não</option>
                    <option value="S">Sim</option>
                </select>
            </div>
        </div>
        <div class="col-6">
            <div class="form-group">
                <label class="small font-weight-bold text-muted">Cor Roupa</label>
                <select class="form-control form-control-sm input-monitor" name="cor_roupa" id="edit_cor">
                    <option value="Laranja">Laranja</option>
                    <option value="Verde">Verde</option>
                </select>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="small font-weight-bold text-muted">Setor de Trabalho (Rouparia)</label>
        <input type="text" class="form-control form-control-sm input-monitor" name="regalia_setor" id="edit_setor" placeholder="Ex: Lavanderia">
    </div>

    <div class="row">
        <div class="col-4">
            <div class="form-group">
                <label class="small font-weight-bold text-muted">Kit Nº</label>
                <input type="number" class="form-control form-control-sm input-monitor" name="kit" id="edit_kit">
            </div>
        </div>
        <div class="col-4">
            <div class="form-group">
                <label class="small font-weight-bold text-muted">Kit Reg.</label>
                <input type="number" class="form-control form-control-sm input-monitor" name="regalia_kit" id="edit_regkit">
            </div>
        </div>
        <div class="col-4">
            <div class="form-group">
                <label class="small font-weight-bold text-muted">Tam.</label>
                <select class="form-control form-control-sm input-monitor" name="tamanho_kit" id="edit_tam">
                    <?php foreach(['P','M','G','G1','G2','G3'] as $t) echo "<option value='$t'>$t</option>"; ?>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 pt-2 border-top mx-3 pb-3">
    <button type="submit" id="btnSalvar" class="btn btn-primary btn-block shadow font-weight-bold py-2" disabled>
        <i class="fas fa-save mr-2"></i> SALVAR ALTERAÇÕES
    </button>
</div>