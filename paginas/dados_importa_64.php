<?php
require_once '../includes/dados_importa_64_logica.php';
?>

<div class="content-header px-0">
    <div class="container-fluid px-0">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><i class="fas fa-file-import mr-2 text-primary"></i>Importar Relatório 6-4</h1>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary shadow">
            <div class="card-header">
                <h3 class="card-title">Instruções</h3>
            </div>
            <div class="card-body">
                <div class="callout callout-info mb-4">
                    <h5>Como proceder:</h5>
                    <ol>
                        <li>No iPEN, gere o relatório <b>6-4 (Remições por Trabalho Ativas)</b> da unidade;</li>
                        <li>Pressione <b>Ctrl + A</b> para selecionar todo o texto do relatório;</li>
                        <li>Pressione <b>Ctrl + C</b> para copiar o conteúdo selecionado;</li>
                        <li>Volte a esta tela e pressione <b>Ctrl + V</b> no campo abaixo;</li>
                        <li>Clique em <b>Iniciar Processamento</b> para importar.</li>
                    </ol>
                    <p class="mb-0">
                        O importador vincula o interno via <b>IPEN</b> e grava estabelecimento, tipo de remição,
                        período de remição/liberação e dias da semana.
                    </p>
                </div>

                <form id="import-form-64" action="paginas/dados_importa_64.php" method="POST">
                    <div class="form-group">
                        <label>Conteúdo do Relatório 6-4:</label>
                        <textarea class="form-control font-monospace" name="report_data" rows="12" required
                            placeholder="Cole o texto completo do relatório aqui..."
                            style="background-color: #0c0e10; color: #4ade80; border: 1px solid #333; font-size: 0.8rem;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg mt-2">
                        <i class="fas fa-sync-alt mr-1"></i> Iniciar Processamento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalResultados64" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-check-circle mr-2"></i>Importação Concluída</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h1 class="display-4 font-weight-bold text-success" id="res64_total">0</h1>
                <p class="text-muted text-uppercase small font-weight-bold">Registros Processados</p>
                <hr>
                <div class="row">
                    <div class="col-6 border-right">
                        <h4 class="text-primary" id="res64_encontrados">0</h4>
                        <small class="text-uppercase small">Internos Encontrados</small>
                    </div>
                    <div class="col-6">
                        <h4 class="text-warning" id="res64_nao_encontrados">0</h4>
                        <small class="text-uppercase small">Não Encontrados</small>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6 border-right">
                        <h5 class="text-success" id="res64_regalias_atualizadas">0</h5>
                        <small class="text-uppercase small">Regalias Atualizadas</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-danger" id="res64_regalias_removidas">0</h5>
                        <small class="text-uppercase small">Regalias Removidas</small>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-6 border-right">
                        <h5 class="text-info" id="res64_conveniados_atualizados">0</h5>
                        <small class="text-uppercase small">Conveniados</small>
                    </div>
                    <div class="col-6">
                        <h5 class="text-warning" id="res64_regalias_especiais_atualizadas">0</h5>
                        <small class="text-uppercase small">Regalias Especiais</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="loadPage('paginas/dados_importa_64.php')">FECHAR</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('import-form-64').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = this;
        const btn = form.querySelector('button[type="submit"]');
        const textoOriginal = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processando...';

        try {
            const formData = new FormData(form);
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                let detalhe = '';
                const contentType = response.headers.get('content-type') || '';
                if (contentType.includes('application/json')) {
                    const erroJson = await response.json();
                    if (erroJson && erroJson.message) detalhe = ` - ${erroJson.message}`;
                } else {
                    const erroTexto = (await response.text()).trim();
                    if (erroTexto) detalhe = ` - ${erroTexto.slice(0, 200)}`;
                }
                throw new Error(`Erro HTTP: ${response.status}${detalhe}`);
            }

            const result = await response.json();

            if (result.success) {
                document.getElementById('res64_total').innerText = result.total || 0;
                document.getElementById('res64_encontrados').innerText = result.encontrados || 0;
                document.getElementById('res64_nao_encontrados').innerText = result.nao_encontrados || 0;
                document.getElementById('res64_regalias_atualizadas').innerText = result.regalias_atualizadas || 0;
                document.getElementById('res64_regalias_removidas').innerText = result.regalias_removidas || 0;
                document.getElementById('res64_conveniados_atualizados').innerText = result.conveniados_atualizados || 0;
                document.getElementById('res64_regalias_especiais_atualizadas').innerText = result.regalias_especiais_atualizadas || 0;
                $('#modalResultados64').modal('show');
                form.reset();
            } else {
                alert('Erro do Sistema: ' + (result.message || 'Falha não identificada.'));
            }
        } catch (error) {
            console.error('Erro na requisição:', error);
            alert('Erro Crítico no processamento do relatório 6-4.\n\n' + error.message);
        } finally {
            btn.disabled = false;
            btn.innerHTML = textoOriginal;
        }
    });
</script>
