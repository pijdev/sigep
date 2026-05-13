<?php
require_once __DIR__ . '/../includes/censura_cartas_logica.php';
header('Content-Type: text/html; charset=utf-8');
?>

<script>
window.currentPage = 'censura_cartas.php';
window.pageTitle = 'Cartas - Censura';
</script>

<style>
.search-results{position:absolute;width:100%;z-index:1051;background:#fff;border:1px solid #ddd;max-height:220px;overflow-y:auto;display:none;box-shadow:0 4px 12px rgba(0,0,0,.2)}
.search-item{padding:8px 10px;border-bottom:1px solid #eee;cursor:pointer}
.search-item:hover{background:#f0f7ff}
.status-pill{font-size:.75rem;border-radius:20px;padding:3px 8px;font-weight:700}
.status-liberada{background:#d4edda;color:#155724}
.status-retida{background:#fff3cd;color:#856404}
.status-devolvida{background:#f8d7da;color:#721c24}
.status-cancelado{background:#6c757d;color:#fff}
body.dark-mode .search-results{background:#3f474e;border-color:#6c757d}
body.dark-mode .search-item{color:#fff;border-bottom-color:#56606a}
body.dark-mode .search-item:hover{background:#4b545c}
</style>

<section class="content pt-3">
  <div class="container-fluid">
    <?php if (!$tem_permissao_cartas): ?>
      <div class="alert alert-danger">
        <strong>Acesso negado:</strong> este módulo exige permissão de Censura.
      </div>
    <?php else: ?>
      <div class="row mb-3">
        <div class="col-lg-3 mb-2">
          <button class="btn btn-warning btn-block" onclick="abrirModalNovaCarta()"><i class="fas fa-envelope-open-text"></i> Nova Carta</button>
        </div>
        <div class="col-lg-9">
          <form class="form-inline float-right" id="formFiltros" onsubmit="return false;">
            <div class="input-group input-group-sm mr-2 mb-1">
              <input type="text" class="form-control" name="busca" placeholder="IPEN / interno / correspondente" value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <select class="form-control" name="tipo">
                <option value="">Tipo</option>
                <option value="Entrada" <?= (($_GET['tipo'] ?? '') === 'Entrada') ? 'selected' : '' ?>>Entrada</option>
                <option value="Saida" <?= (($_GET['tipo'] ?? '') === 'Saida') ? 'selected' : '' ?>>Saída</option>
              </select>
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <select class="form-control" name="status_censura">
                <option value="">Status</option>
                <option value="Liberada" <?= (($_GET['status_censura'] ?? '') === 'Liberada') ? 'selected' : '' ?>>Liberada</option>
                <option value="Retida" <?= (($_GET['status_censura'] ?? '') === 'Retida') ? 'selected' : '' ?>>Retida</option>
                <option value="Devolvida" <?= (($_GET['status_censura'] ?? '') === 'Devolvida') ? 'selected' : '' ?>>Devolvida</option>
              </select>
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <select class="form-control" name="status_registro">
                <option value="">Registro</option>
                <option value="Ativo" <?= (($_GET['status_registro'] ?? '') === 'Ativo') ? 'selected' : '' ?>>Ativo</option>
                <option value="Cancelado" <?= (($_GET['status_registro'] ?? '') === 'Cancelado') ? 'selected' : '' ?>>Cancelado</option>
              </select>
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <input type="text" class="form-control" name="monitor" placeholder="Monitor" value="<?= htmlspecialchars($_GET['monitor'] ?? '') ?>">
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <select class="form-control" name="galeria">
                <option value="">Galeria</option>
                <?php foreach ($galerias_cartas as $g): ?>
                  <option value="<?= htmlspecialchars($g) ?>" <?= (($_GET['galeria'] ?? '') == $g) ? 'selected' : '' ?>><?= htmlspecialchars($g) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <select class="form-control" name="bloco">
                <option value="">Bloco</option>
                <?php foreach ($blocos_cartas as $b): ?>
                  <option value="<?= htmlspecialchars($b) ?>" <?= (($_GET['bloco'] ?? '') == $b) ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <input type="text" class="form-control" name="cela" placeholder="Cela" value="<?= htmlspecialchars($_GET['cela'] ?? '') ?>">
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <input type="date" class="form-control" name="data_ini" value="<?= htmlspecialchars($_GET['data_ini'] ?? '') ?>">
            </div>
            <div class="input-group input-group-sm mr-2 mb-1">
              <input type="date" class="form-control" name="data_fim" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
            </div>
            <button type="button" class="btn btn-sm btn-outline-warning mb-1" onclick="aplicarFiltrosCartas()"><i class="fas fa-search"></i></button>
          </form>
        </div>
      </div>

      <div class="card shadow">
        <div class="card-header"><h3 class="card-title">Histórico de Cartas (Censura)</h3></div>
        <div class="card-body table-responsive p-0">
          <table class="table table-sm table-hover table-striped">
            <thead>
            <tr>
              <th>ID</th><th>Data</th><th>Tipo</th><th>Interno</th><th>Remetente</th><th>Destinatário</th><th>Status</th><th>Monitor</th><th>Registro</th><th>Ações</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($registros_cartas)): ?>
              <tr><td colspan="10" class="text-center text-muted py-4">Nenhum registro encontrado.</td></tr>
            <?php else: foreach ($registros_cartas as $r):
              $nomeInterno = $r['interno_nome_social'] ?: $r['interno_nome'];
              $localInterno = trim(($r['interno_galeria'] ?? '') . '-' . ($r['interno_bloco'] ?? '') . '-' . ($r['interno_res'] ?? ''), '-');
              if ($r['tipo_movimentacao'] === 'Entrada') { $rem = $r['correspondente_nome']; $dst = $nomeInterno; } else { $rem = $nomeInterno; $dst = $r['correspondente_nome']; }
              $classStatus = $r['status_censura'] === 'Retida' ? 'status-retida' : ($r['status_censura'] === 'Devolvida' ? 'status-devolvida' : 'status-liberada');
            ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= date('d/m/Y H:i', strtotime($r['recebido_em'])) ?></td>
                <td><?= htmlspecialchars($r['tipo_movimentacao']) ?></td>
                <td><strong><?= (int)$r['id_interno'] ?></strong> - <?= htmlspecialchars($nomeInterno) ?><br><small class="text-muted"><?= htmlspecialchars($localInterno) ?></small></td>
                <td><?= htmlspecialchars($rem) ?></td>
                <td><?= htmlspecialchars($dst) ?></td>
                <td><span class="status-pill <?= $classStatus ?>"><?= htmlspecialchars($r['status_censura']) ?></span></td>
                <td><?= htmlspecialchars($r['monitor_nome']) ?></td>
                <td><?= $r['status_registro'] === 'Cancelado' ? '<span class="status-pill status-cancelado">Cancelado</span>' : '<span class="badge badge-success">Ativo</span>' ?></td>
                <td>
                  <button class="btn btn-xs btn-info" onclick='abrirModalStatus(<?= json_encode((int)$r['id']) ?>, <?= json_encode($r['status_censura']) ?>, <?= json_encode($r['observacoes_censura']) ?>, <?= json_encode($r['motivo_retencao']) ?>, <?= json_encode($r['status_registro']) ?>)'><i class="fas fa-edit"></i></button>
                  <button class="btn btn-xs btn-secondary" onclick='mostrarHistorico(<?= json_encode((int)$r['id']) ?>)'><i class="fas fa-history"></i></button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($tem_permissao_cartas): ?>
<div class="modal fade" id="modalNovaCarta" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <form id="formNovaCarta">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">Cadastro de Carta (Censura)</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="acao" value="registrar_carta">
          <input type="hidden" name="ipen" id="hiddenIpen" required>
          <input type="hidden" name="id_correspondente" id="hiddenCorrespondente">

          <div class="row">
            <div class="col-md-3">
              <label>Tipo</label>
              <select class="form-control" name="tipo_movimentacao" id="tipoMov" required>
                <option value="Entrada">Entrada</option>
                <option value="Saida">Saída</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Status Censura</label>
              <select class="form-control" name="status_censura" id="statusCensura" required>
                <option value="Liberada">Liberada</option>
                <option value="Retida">Retida</option>
                <option value="Devolvida">Devolvida</option>
              </select>
            </div>
            <div class="col-md-3">
              <label>Recebido Em</label>
              <input type="datetime-local" class="form-control" name="recebido_em" id="recebidoEm">
            </div>
          </div>

          <hr>
          <div class="row">
            <div class="col-md-7 position-relative">
              <label>Buscar Interno</label>
              <input type="text" class="form-control" id="buscaInterno" autocomplete="off" placeholder="IPEN ou nome">
              <div class="search-results" id="sugestoesInterno"></div>
            </div>
            <div class="col-md-5">
              <label>Interno Selecionado</label>
              <input type="text" class="form-control font-weight-bold" id="displayInterno" readonly>
              <small id="displayLocal" class="text-muted"></small>
            </div>
          </div>

          <hr>
          <div class="row">
            <div class="col-md-7 position-relative">
              <label>Buscar Correspondente</label>
              <input type="text" class="form-control" id="buscaCorrespondente" autocomplete="off" placeholder="Nome, vínculo, cidade">
              <div class="search-results" id="sugestoesCorrespondente"></div>
            </div>
            <div class="col-md-5 d-flex align-items-end">
              <button type="button" class="btn btn-outline-primary btn-sm mr-2" onclick="sugerirCorrespondente()">Sugerir por interno</button>
              <button type="button" class="btn btn-outline-primary btn-sm" onclick="sugerirInterno()">Sugerir por correspondente</button>
            </div>
          </div>

          <div class="row mt-2">
            <div class="col-md-4"><label>Nome Correspondente</label><input type="text" class="form-control" name="correspondente_nome" id="correspondenteNome"></div>
            <div class="col-md-2"><label>Vínculo</label><input type="text" class="form-control" name="correspondente_vinculo" id="correspondenteVinculo"></div>
            <div class="col-md-4"><label>Logradouro</label><input type="text" class="form-control" name="correspondente_logradouro" id="correspondenteLogradouro"></div>
            <div class="col-md-2"><label>Número</label><input type="text" class="form-control" name="correspondente_numero" id="correspondenteNumero"></div>
          </div>
          <div class="row mt-2">
            <div class="col-md-3"><label>Bairro</label><input type="text" class="form-control" name="correspondente_bairro" id="correspondenteBairro"></div>
            <div class="col-md-3"><label>Cidade</label><input type="text" class="form-control" name="correspondente_cidade" id="correspondenteCidade"></div>
            <div class="col-md-2"><label>UF</label><input type="text" class="form-control text-uppercase" maxlength="2" name="correspondente_uf" id="correspondenteUf"></div>
            <div class="col-md-2"><label>CEP</label><input type="text" class="form-control" name="correspondente_cep" id="correspondenteCep"></div>
            <div class="col-md-2"><label>Complemento</label><input type="text" class="form-control" name="correspondente_complemento" id="correspondenteComplemento"></div>
          </div>

          <div class="row mt-3">
            <div class="col-md-6"><label>Observações</label><textarea class="form-control" rows="2" name="observacoes_censura" id="observacoesCensura"></textarea></div>
            <div class="col-md-6"><label>Motivo Retenção</label><textarea class="form-control" rows="2" name="motivo_retencao" id="motivoRetencao"></textarea></div>
          </div>
          <small id="deParaHint" class="text-muted d-block mt-2"></small>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-warning">Salvar</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalStatusCarta" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="formStatusCarta">
        <div class="modal-header bg-info">
          <h5 class="modal-title">Atualizar Status</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="acao" value="atualizar_status_censura">
          <input type="hidden" name="id_carta" id="statusIdCarta">
          <div class="form-group">
            <label>Status</label>
            <select class="form-control" name="status_censura" id="statusNovo">
              <option value="Liberada">Liberada</option>
              <option value="Retida">Retida</option>
              <option value="Devolvida">Devolvida</option>
            </select>
          </div>
          <div class="form-group"><label>Observações</label><textarea class="form-control" name="observacoes_censura" id="statusObs" rows="2"></textarea></div>
          <div class="form-group"><label>Motivo Retenção</label><textarea class="form-control" name="motivo_retencao" id="statusMotivo" rows="2"></textarea></div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-info">Salvar Status</button>
          <button type="button" class="btn btn-danger" id="btnCancelarRegistro">Cancelar Registro</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalHistoricoCarta" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-secondary">
        <h5 class="modal-title">Histórico de Auditoria</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body" id="historicoConteudo" style="max-height:320px;overflow:auto;"></div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($tem_permissao_cartas): ?>
<script>
function aplicarFiltrosCartas() {
  const form = document.getElementById('formFiltros');
  const params = new URLSearchParams(new FormData(form)).toString();
  if (typeof loadPage === 'function') loadPage('paginas/censura_cartas.php?' + params, 'Cartas', 'Censura');
  else window.location.href = 'paginas/censura_cartas.php?' + params;
}

function nowLocalDateTimeValue() {
  const dt = new Date();
  dt.setMinutes(dt.getMinutes() - dt.getTimezoneOffset());
  return dt.toISOString().slice(0, 16);
}

function preencherCorrespondente(c) {
  $('#hiddenCorrespondente').val(c.id || '');
  $('#correspondenteNome').val(c.nome || '');
  $('#correspondenteVinculo').val(c.vinculo || '');
  $('#correspondenteLogradouro').val(c.logradouro || '');
  $('#correspondenteNumero').val(c.numero || '');
  $('#correspondenteBairro').val(c.bairro || '');
  $('#correspondenteCidade').val(c.cidade || '');
  $('#correspondenteUf').val((c.uf || '').toUpperCase());
  $('#correspondenteCep').val(c.cep || '');
  $('#correspondenteComplemento').val(c.complemento || '');
}

function atualizarDeParaHint() {
  const tipo = $('#tipoMov').val();
  const interno = $('#displayInterno').val() || 'Interno';
  const correspondente = $('#correspondenteNome').val() || 'Correspondente';
  if (tipo === 'Entrada') $('#deParaHint').text('Entrada: Remetente = ' + correspondente + ' | Destinatário = ' + interno);
  else $('#deParaHint').text('Saída: Remetente = ' + interno + ' | Destinatário = ' + correspondente);
}

function abrirModalNovaCarta() {
  document.getElementById('formNovaCarta').reset();
  $('#hiddenIpen').val(''); $('#hiddenCorrespondente').val('');
  $('#displayInterno').val(''); $('#displayLocal').text('');
  $('#recebidoEm').val(nowLocalDateTimeValue());
  atualizarDeParaHint();
  $('#modalNovaCarta').modal('show');
}

function abrirModalStatus(idCarta, status, obs, motivo, statusRegistro) {
  $('#statusIdCarta').val(idCarta);
  $('#statusNovo').val(status || 'Liberada');
  $('#statusObs').val(obs || '');
  $('#statusMotivo').val(motivo || '');
  $('#btnCancelarRegistro').prop('disabled', statusRegistro === 'Cancelado');
  $('#modalStatusCarta').modal('show');
}

async function buscarInterno(termo) {
  const res = await fetch('paginas/censura_cartas.php?acao=buscar_interno&termo=' + encodeURIComponent(termo));
  return await res.json();
}
async function buscarCorrespondente(termo) {
  const res = await fetch('paginas/censura_cartas.php?acao=buscar_correspondente&termo=' + encodeURIComponent(termo));
  return await res.json();
}

function drawSuggest(boxId, rows, renderFn, clickFn) {
  const box = $('#' + boxId);
  if (!rows || !rows.length) return box.hide();
  box.html(rows.map((r, i) => '<div class="search-item" data-i="' + i + '">' + renderFn(r) + '</div>').join(''));
  box.find('.search-item').on('click', function() { clickFn(rows[$(this).data('i')]); box.hide(); });
  box.show();
}

async function sugerirCorrespondente() {
  const ipen = $('#hiddenIpen').val();
  if (!ipen) return alert('Selecione o interno primeiro.');
  const tipo = $('#tipoMov').val();
  const res = await fetch('paginas/censura_cartas.php?acao=sugestao_por_interno&ipen=' + encodeURIComponent(ipen) + '&tipo_movimentacao=' + encodeURIComponent(tipo));
  const data = await res.json();
  if (data.status === 'success' && data.sugestao) { preencherCorrespondente(data.sugestao); atualizarDeParaHint(); }
  else alert('Nenhuma sugestão encontrada.');
}

async function sugerirInterno() {
  const idCorresp = $('#hiddenCorrespondente').val();
  if (!idCorresp) return alert('Selecione o correspondente primeiro.');
  const tipo = $('#tipoMov').val();
  const res = await fetch('paginas/censura_cartas.php?acao=sugestao_por_correspondente&id_correspondente=' + encodeURIComponent(idCorresp) + '&tipo_movimentacao=' + encodeURIComponent(tipo));
  const data = await res.json();
  if (data.status === 'success' && data.sugestao) {
    const i = data.sugestao;
    $('#hiddenIpen').val(i.ipen);
    $('#displayInterno').val((i.ipen || '') + ' - ' + (i.nome_social || i.nome || ''));
    $('#displayLocal').text((i.galeria || '') + '-' + (i.bloco || '') + '-' + (i.res || ''));
    atualizarDeParaHint();
  } else alert('Nenhuma sugestão encontrada.');
}

async function mostrarHistorico(idCarta) {
  const res = await fetch('paginas/censura_cartas.php?acao=listar_historico&id_carta=' + encodeURIComponent(idCarta));
  const data = await res.json();
  const box = $('#historicoConteudo');
  if (data.status !== 'success' || !data.dados || !data.dados.length) {
    box.html('<div class="text-muted">Sem histórico para este registro.</div>');
    return $('#modalHistoricoCarta').modal('show');
  }
  let html = '';
  data.dados.forEach(h => {
    html += '<div class="mb-3 p-2 border rounded">';
    html += '<div><strong>' + (h.operacao || '') + '</strong> - ' + (h.usuario_nome || 'Sistema') + '</div>';
    html += '<div><small class="text-muted">' + (h.data_hora || '') + '</small></div>';
    if (h.valor_antigo) html += '<div class="mt-2"><small>Antes:</small><pre class="small mb-0">' + JSON.stringify(JSON.parse(h.valor_antigo), null, 2) + '</pre></div>';
    if (h.valor_novo) html += '<div class="mt-2"><small>Depois:</small><pre class="small mb-0">' + JSON.stringify(JSON.parse(h.valor_novo), null, 2) + '</pre></div>';
    html += '</div>';
  });
  box.html(html);
  $('#modalHistoricoCarta').modal('show');
}

$(document).on('input', '#buscaInterno', async function() {
  const termo = this.value.trim();
  if (termo.length < 2) return $('#sugestoesInterno').hide();
  const data = await buscarInterno(termo);
  if (data.status !== 'success') return $('#sugestoesInterno').hide();
  drawSuggest('sugestoesInterno', data.dados,
    i => '<strong>' + i.ipen + ' - ' + (i.nome_social || i.nome) + '</strong><br><small>' + (i.galeria || '') + '-' + (i.bloco || '') + '-' + (i.res || '') + '</small>',
    i => { $('#hiddenIpen').val(i.ipen); $('#displayInterno').val((i.ipen || '') + ' - ' + (i.nome_social || i.nome || '')); $('#displayLocal').text((i.galeria || '') + '-' + (i.bloco || '') + '-' + (i.res || '')); atualizarDeParaHint(); }
  );
});

$(document).on('input', '#buscaCorrespondente', async function() {
  const termo = this.value.trim();
  if (termo.length < 2) return $('#sugestoesCorrespondente').hide();
  const data = await buscarCorrespondente(termo);
  if (data.status !== 'success') return $('#sugestoesCorrespondente').hide();
  drawSuggest('sugestoesCorrespondente', data.dados,
    c => '<strong>' + (c.nome || '') + '</strong> <small>' + (c.vinculo || '') + '</small><br><small>' + (c.cidade || '') + ' / ' + (c.uf || '') + '</small>',
    c => { preencherCorrespondente(c); atualizarDeParaHint(); }
  );
});

$('#tipoMov, #correspondenteNome').on('change keyup', atualizarDeParaHint);
$('#statusCensura').on('change', function() {
  if ($(this).val() === 'Retida') $('#motivoRetencao').attr('required', 'required');
  else $('#motivoRetencao').removeAttr('required');
});

$('#formNovaCarta').on('submit', async function(e) {
  e.preventDefault();
  if (!$('#hiddenIpen').val()) return alert('Selecione um interno.');
  if (!$('#correspondenteNome').val().trim() && !$('#hiddenCorrespondente').val()) return alert('Informe ou selecione um correspondente.');
  if ($('#statusCensura').val() === 'Retida' && !$('#motivoRetencao').val().trim()) return alert('Motivo de retenção é obrigatório para Retida.');
  const res = await fetch('paginas/censura_cartas.php', { method: 'POST', body: new FormData(this) });
  const data = await res.json();
  if (data.status === 'success') {
    alert(data.msg || 'Salvo com sucesso.');
    if (typeof loadPage === 'function') loadPage('paginas/censura_cartas.php', 'Cartas', 'Censura');
    else window.location.reload();
  } else alert(data.msg || 'Falha ao salvar.');
});

$('#formStatusCarta').on('submit', async function(e) {
  e.preventDefault();
  if ($('#statusNovo').val() === 'Retida' && !$('#statusMotivo').val().trim()) return alert('Motivo de retenção é obrigatório para Retida.');
  const res = await fetch('paginas/censura_cartas.php', { method: 'POST', body: new FormData(this) });
  const data = await res.json();
  if (data.status === 'success') {
    alert(data.msg || 'Status atualizado.');
    if (typeof loadPage === 'function') loadPage('paginas/censura_cartas.php', 'Cartas', 'Censura');
    else window.location.reload();
  } else alert(data.msg || 'Falha ao atualizar.');
});

$('#btnCancelarRegistro').on('click', async function() {
  const idCarta = $('#statusIdCarta').val();
  const motivo = prompt('Informe o motivo do cancelamento:');
  if (!motivo || !motivo.trim()) return;
  const fd = new FormData();
  fd.append('acao', 'cancelar_registro');
  fd.append('id_carta', idCarta);
  fd.append('motivo_cancelamento', motivo.trim());
  const res = await fetch('paginas/censura_cartas.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.status === 'success') {
    alert(data.msg || 'Registro cancelado.');
    if (typeof loadPage === 'function') loadPage('paginas/censura_cartas.php', 'Cartas', 'Censura');
    else window.location.reload();
  } else alert(data.msg || 'Falha ao cancelar.');
});

document.addEventListener('click', function(e) {
  if (!e.target.closest('#buscaInterno')) $('#sugestoesInterno').hide();
  if (!e.target.closest('#buscaCorrespondente')) $('#sugestoesCorrespondente').hide();
});
</script>
<?php endif; ?>
