<?php

/**
 * SIGEP - Conteúdo da Página de Erro (para SPA)
 * Conteúdo apenas da página de erro para ser injetado no SPA
 */

// Obter dados do erro (já processados no erro_view.php)
$erro_codigo = $erro_codigo ?? '404';
$erro_atual = $erros[$erro_codigo] ?? $erros['404'];
$erro_mensagem = $erro_mensagem ?? '';
$esta_autenticado = $esta_autenticado ?? false;
?>

<div class="error-page-spa">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card bg-gradient-warning">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="<?php echo $erro_atual['icone']; ?> mr-2"></i>
                        Erro <?php echo $erro_codigo; ?> - <?php echo $erro_atual['titulo']; ?>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="error-content text-center">
                        <div class="error-icon mb-3">
                            <i class="<?php echo $erro_atual['icone']; ?> fa-4x text-<?php echo $erro_atual['cor']; ?>"></i>
                        </div>

                        <h4 class="mb-3"><?php echo $erro_atual['titulo']; ?></h4>

                        <p class="text-muted mb-4">
                            <?php echo $erro_mensagem ?: $erro_atual['descricao']; ?>
                        </p>

                        <?php if ($erro_codigo === '404'): ?>
                            <div class="search-box mb-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Buscar no SIGEP..." id="searchError">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" onclick="searchInSIGEP()">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="error-actions">
                            <a href="<?php echo $erro_atual['acao_url']; ?>" class="btn btn-<?php echo $erro_atual['cor']; ?> btn-lg">
                                <i class="fas fa-<?php echo $erro_codigo === '403' ? 'sign-out-alt' : 'home'; ?> mr-2"></i>
                                <?php echo $erro_atual['acao_texto']; ?>
                            </a>

                            <?php if ($esta_autenticado): ?>
                                <button class="btn btn-secondary btn-lg ml-2" onclick="history.back()">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Voltar
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($erro_mensagem): ?>
                            <div class="alert alert-<?php echo $erro_atual['cor']; ?> mt-4">
                                <strong>Detalhes:</strong> <?php echo htmlspecialchars($erro_mensagem); ?>
                            </div>
                        <?php endif; ?>

                        <div class="error-info mt-4">
                            <small class="text-muted">
                                <i class="fas fa-clock mr-1"></i>
                                <?php echo date('d/m/Y H:i:s'); ?> |
                                Código: <?php echo $erro_codigo; ?> |
                                Referência: <?php echo $_SERVER['HTTP_REFERER'] ?? 'Acesso direto'; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .error-page-spa {
        padding: 20px;
    }

    .error-icon {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }

        100% {
            transform: scale(1);
        }
    }

    .error-actions {
        display: flex;
        gap: 10px;
        justify-content: center;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .error-actions {
            flex-direction: column;
        }

        .error-actions .btn {
            width: 100%;
        }
    }
</style>

<script>
    // Função de busca para SPA
    function searchInSIGEP() {
        const searchTerm = document.getElementById('searchError').value;
        if (searchTerm.trim()) {
            if (typeof loadPage === 'function') {
                loadPage('/?search=' + encodeURIComponent(searchTerm));
            } else {
                window.location.href = '/?search=' + encodeURIComponent(searchTerm);
            }
        }
    }

    // Auto-redirecionamento para erros 503
    <?php if ($erro_codigo === '503'): ?>
        setTimeout(function() {
            if (typeof loadPage === 'function') {
                loadPage('<?php echo $erro_redirect; ?>');
            } else {
                window.location.href = '<?php echo $erro_redirect; ?>';
            }
        }, 30000);
    <?php endif; ?>

    // Enter no campo de busca
    document.getElementById('searchError')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchInSIGEP();
        }
    });
</script>
