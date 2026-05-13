<?php
require_once __DIR__ . '/controller.php';
?>

<!-- Content Header -->
<div class="row mb-2">
    <div class="col-sm-6">
    </div>
</div>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">


    <!-- CARDS ESTATÍSTICA -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="internos-sem-kit">0</h3>
                        <p>Internos Sem Kit</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Detalhes <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="conflitos-repetidos">0</h3>
                        <p>Conflitos/Repetidos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Detalhes <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="kits-disponiveis">0</h3>
                        <p>Kits Disponíveis</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <a href="#" class="small-box-footer">Ver Detalhes <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- ÁREA DE FILTROS -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search"></i> Filtros de Pesquisa</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form action="/modulos/censura/rouparia/gestao_kits/view.php" method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Pesquisa (Nome/IPEN)</label>
                                <input type="text" class="form-control" name="search" placeholder="Digite...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Situação</label>
                                <select class="form-control" name="situacao">
                                    <option value="">Tudo</option>
                                    <option value="Ativo">Ativo</option>
                                    <option value="Inativo">Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Galeria</label>
                                <select class="form-control" name="galeria">
                                    <option value="">Todas</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                    <option value="E">E</option>
                                    <option value="F">F</option>
                                    <option value="G">G</option>
                                    <option value="H">H</option>
                                    <option value="S">S</option>
                                    <option value="T">T</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Bloco</label>
                                <select class="form-control" name="bloco">
                                    <option value="">Todos</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="btn-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> FILTRAR AGORA
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="window.location.reload()">
                                        <i class="fas fa-times"></i> LIMPAR
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABELA PRINCIPAL -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Gestão de Kits</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th>IPEN</th>
                                <th>Nome</th>
                                <th>Local</th>
                                <th>Situação</th>
                                <th>Kit</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Carregando dados...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</section>
