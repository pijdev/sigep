<?php
require_once __DIR__ . '/cartas_logica.php';
header('Content-Type: text/html; charset=utf-8');
?>

<script>
  window.currentPage = 'cartas_view.php';
  window.pageTitle = 'Cartas - Censura';
</script>

<!-- CSS específico do módulo cartas -->
<link rel="stylesheet" href="/modulos/censura/cartas/assets/css/cartas.css?v=<?= time() ?>">

<section class="content pt-3">
  <div class="container-fluid">
    <?php if (!$tem_permissao_cartas): ?>
      <div class="alert alert-danger">
        <strong>Acesso negado:</strong> este módulo exige permissão de Censura.
      </div>
    <?php else: ?>
      <div class="row mb-3">
        <div class="col-lg-2 mb-2">
          <button class="btn btn-warning btn-block" onclick="abrirModalNovaCarta()"><i class="fas fa-envelope-open-text"></i> Nova Carta</button>
        </div>
        <div class="col-lg-1 mb-2">
          <button class="btn btn-info btn-block" onclick="window.open('/censura/cartas/recibo-retorno/', '_blank')"><i class="fas fa-print"></i> Recibos</button>
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
            <div class="input-group-append">
              <button class="btn btn-primary" onclick="aplicarFiltrosCartas()"><i class="fas fa-search"></i></button>
            </div>
          </form>
        </div>
      </div>

      <!-- Cards de Estatísticas -->
      <div class="row mb-3">
        <div class="col-lg-3 col-6">
          <div class="small-box bg-info pointer">
            <div class="inner">
              <h3 id="stats-total"><?= number_format($total_cartas) ?></h3>
              <p>Total de Cartas</p>
            </div>
            <div class="icon">
              <i class="fas fa-envelope"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-success pointer">
            <div class="inner">
              <h3 id="stats-liberadas"><?= number_format($cartas_liberadas) ?></h3>
              <p>Liberadas</p>
            </div>
            <div class="icon">
              <i class="fas fa-check-circle"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-warning pointer">
            <div class="inner">
              <h3 id="stats-retidas"><?= number_format($cartas_retidas) ?></h3>
              <p>Retidas</p>
            </div>
            <div class="icon">
              <i class="fas fa-clock"></i>
            </div>
          </div>
        </div>
        <div class="col-lg-3 col-6">
          <div class="small-box bg-danger pointer">
            <div class="inner">
              <h3 id="stats-devolvidas"><?= number_format($cartas_devolvidas) ?></h3>
              <p>Devolvidas</p>
            </div>
            <div class="icon">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabela Principal -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">
            <i class="fas fa-list mr-2"></i>
            Cartas Cadastradas
          </h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover" id="tabelaCartas">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Data</th>
                  <th>Tipo</th>
                  <th>IPEN</th>
                  <th>Interno</th>
                  <th>Correspondente</th>
                  <th>Status</th>
                  <th>Observações</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($cartas as $carta): ?>
                  <tr>
                    <td><?= $carta['id'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($carta['recebido_em'])) ?></td>
                    <td>
                      <span class="status-pill <?= $carta['tipo_movimentacao'] === 'Entrada' ? 'status-liberada' : 'status-retida' ?>">
                        <?= $carta['tipo_movimentacao'] ?>
                      </span>
                    </td>
                    <td><?= $carta['id_interno'] ?></td>
                    <td><?= htmlspecialchars($carta['interno_nome_social'] ?: $carta['interno_nome']) ?></td>
                    <td><?= htmlspecialchars($carta['correspondente_nome']) ?></td>
                    <td>
                      <span class="status-pill status-<?= strtolower($carta['status_censura']) ?>">
                        <?= $carta['status_censura'] ?>
                      </span>
                    </td>
                    <td><?= htmlspecialchars(substr($carta['observacoes_censura'] ?? '', 0, 30)) ?></td>
                    <td>
                      <div class="btn-group">
                        <button class="btn btn-sm btn-info" onclick="abrirModalStatus(<?= $carta['id'] ?>, '<?= $carta['status_censura'] ?>', '<?= htmlspecialchars($carta['observacoes_censura'] ?? '') ?>', '<?= htmlspecialchars($carta['motivo_retencao'] ?? '') ?>', '<?= $carta['status_registro'] ?>')">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-warning" onclick="mostrarHistorico(<?= $carta['id'] ?>)">
                          <i class="fas fa-history"></i>
                        </button>
                        <?php if ($carta['status_registro'] === 'Ativo'): ?>
                          <button class="btn btn-sm btn-success" onclick="imprimirCarta(<?= $carta['id'] ?>)">
                            <i class="fas fa-print"></i>
                          </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Modal Nova Carta -->
<div class="modal fade" id="modalNovaCarta" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Nova Carta</h5>
        <button type="button" class="close" data-bs-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="formNovaCarta" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tipo de Movimentação</label>
                <select class="form-control" id="tipoMov" name="tipo_movimentacao" required>
                  <option value="">Selecione...</option>
                  <option value="Entrada">Entrada</option>
                  <option value="Saida">Saída</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Data de Recebimento</label>
                <input type="datetime-local" class="form-control" id="recebidoEm" name="recebido_em" required>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>IPEN do Interno</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="ipenInterno" name="id_interno" placeholder="Digite IPEN..." required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="buscarInterno($('#ipenInterno').val())">
                      <i class="fas fa-search"></i>
                    </button>
                  </div>
                </div>
                <div id="resultadosBusca" class="position-absolute w-100 bg-white border rounded shadow-sm" style="z-index: 1050; display: none;"></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Nome do Interno</label>
                <input type="text" class="form-control" id="displayInterno" readonly>
                <input type="hidden" id="hiddenIpen" name="id_interno">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Localização</label>
                <input type="text" class="form-control" id="displayLocal" readonly>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label id="deParaLabel">Fluxo da Carta:</label>
                <input type="text" class="form-control" id="deParaHint" readonly placeholder="Selecione tipo e interno para ver o fluxo">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Correspondente</label>
                <div class="input-group">
                  <input type="text" class="form-control" id="correspondenteNome" name="correspondente_nome" placeholder="Digite nome..." required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" disabled title="Digite para buscar automaticamente">
                      <i class="fas fa-search"></i>
                    </button>
                  </div>
                </div>
                <div id="resultadosCorrespondente" class="position-absolute w-100 bg-white border rounded shadow-sm" style="z-index: 1050; display: none;"></div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Vínculo</label>
                <input type="text" class="form-control" id="correspondenteVinculo" readonly>
                <input type="hidden" id="hiddenCorrespondente" name="id_correspondente">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Endereço do Correspondente</label>
                <div class="row">
                  <div class="col-md-3">
                    <input type="text" class="form-control" id="correspondenteLogradouro" name="correspondente_logradouro" placeholder="Logradouro">
                  </div>
                  <div class="col-md-2">
                    <input type="text" class="form-control" id="correspondenteNumero" name="correspondente_numero" placeholder="Número">
                  </div>
                  <div class="col-md-3">
                    <input type="text" class="form-control" id="correspondenteBairro" name="correspondente_bairro" placeholder="Bairro">
                  </div>
                  <div class="col-md-2">
                    <input type="text" class="form-control" id="correspondenteCidade" name="correspondente_cidade" placeholder="Cidade">
                  </div>
                  <div class="col-md-1">
                    <input type="text" class="form-control" id="correspondenteUf" name="correspondente_uf" placeholder="UF" maxlength="2">
                  </div>
                  <div class="col-md-1">
                    <input type="text" class="form-control" id="correspondenteCep" name="correspondente_cep" placeholder="CEP">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Complemento</label>
                <input type="text" class="form-control" id="correspondenteComplemento" name="correspondente_complemento" placeholder="Complemento">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>CEP</label>
                <input type="text" class="form-control" id="correspondenteCep2" name="correspondente_cep" placeholder="CEP">
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Status da Censura</label>
                <select class="form-control" name="status_censura" required>
                  <option value="Liberada">Liberada</option>
                  <option value="Retida">Retida</option>
                  <option value="Devolvida">Devolvida</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Observações da Censura</label>
                <textarea class="form-control" name="observacoes_censura" rows="2"></textarea>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-md-12">
              <div class="form-group">
                <label>Motivo da Retenção</label>
                <textarea class="form-control" name="motivo_retencao" rows="2"></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Salvar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Status -->
<div class="modal fade" id="modalStatusCarta" tabindex="-1" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Alterar Status</h5>
        <button type="button" class="close" data-bs-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="formStatusCarta" method="POST">
        <div class="modal-body">
          <input type="hidden" id="statusIdCarta" name="id_carta">
          <div class="form-group">
            <label>Novo Status</label>
            <select class="form-control" id="statusNovo" name="status_censura" required>
              <option value="Liberada">Liberada</option>
              <option value="Retida">Retida</option>
              <option value="Devolvida">Devolvida</option>
            </select>
          </div>
          <div class="form-group">
            <label>Observações</label>
            <textarea class="form-control" id="statusObs" name="observacoes_censura" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>Motivo da Retenção</label>
            <textarea class="form-control" id="statusMotivo" name="motivo_retencao" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Atualizar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Histórico -->
<div class="modal fade" id="modalHistoricoCarta" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Histórico da Carta</h5>
        <button type="button" class="close" data-bs-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="historicoConteudo">
          <div class="text-center">
            <div class="spinner-border" role="status"></div>
            <p>Carregando histórico...</p>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="/modulos/censura/cartas/assets/js/cartas.js?v=<?= time() ?>"></script>
