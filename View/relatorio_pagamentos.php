<!-- View/relatorio_pagamentos.php -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($title) ?></h1>

    <!-- FILTRO -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="/sgi_erp/relatorios/pagamentos">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Data Início</label>
                        <input type="date" name="ini" class="form-control" value="<?= $dados['data_inicio'] ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Data Fim</label>
                        <input type="date" name="fim" class="form-control" value="<?= $dados['data_fim'] ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Funcionário</label>
                        <select name="funcionario_id" class="form-select">
                            <option value="">Todos os Funcionários</option>
                            <?php foreach ($dados['lista_funcionarios'] as $f): ?>
                                <option value="<?= $f->id ?>" <?= ($dados['funcionario_id'] == $f->id) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f->nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($dados['matriz'])): ?>
        <div class="alert alert-info">Nenhum valor a pagar no período.</div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-success">Valores a Pagar (R$)</h6>
                <div>
                    <button id="btn-pdf" class="btn btn-danger btn-sm me-2">PDF</button>
                    <button id="btn-excel" class="btn btn-success btn-sm">Excel</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tabelaPrincipal">
                        <thead class="table-success">
                            <tr>
                                <th>FUNCIONÁRIO</th>
                                <?php foreach ($dados['datas'] as $d): ?>
                                    <th class="text-center"><?= date('d/m', strtotime($d)) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center coluna-total">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dados['matriz'] as $nome => $linha): ?>
                                <tr class="funcionario-linha" style="cursor: pointer; background: #f8fff8;">
                                    <td>
                                        <i class="fas fa-plus-circle text-success me-2 icon-expand"></i>
                                        <strong><?= htmlspecialchars($nome) ?></strong>
                                    </td>

                                    <?php foreach ($dados['datas'] as $d): ?>
                                        <?php $valor = $linha['dias'][$d] ?? 0; // CORRIGIDO AQUI 
                                        ?>
                                        <td class="text-center <?= $valor > 0 ? 'text-success fw-bold' : '' ?>" data-data="<?= $d ?>">
                                            <?= $valor > 0 ? 'R$ ' . number_format($valor, 2, ',', '.') : '' ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="text-end fw-bold table-success total-funcionario">
                                        R$ <?= number_format($linha['total'], 2, ',', '.') ?>
                                    </td>
                                </tr>

                                <!-- DETALHES POR PRODUTO -->
                                <tr class="detalhes-linha" style="display: none;">
                                    <td colspan="<?= count($dados['datas']) + 2 ?>">
                                        <div class="p-4 bg-light rounded">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <strong class="text-success h5 mb-0">
                                                    Valores de <?= htmlspecialchars($nome) ?>
                                                </strong>
                                                <div>
                                                    <button class="btn btn-warning btn-sm btn-editar-produto">Editar</button>
                                                    <button class="btn btn-success btn-sm btn-salvar-produto" style="display:none;">Salvar</button>
                                                    <button class="btn btn-secondary btn-sm btn-cancelar-produto" style="display:none;">Cancelar</button>
                                                    <button class="btn btn-info btn-sm btn-desfazer" style="display:none;">Desfazer</button>
                                                </div>
                                            </div>

                                            <table class="table table-sm table-bordered table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Produto / Serviço</th>
                                                        <?php foreach ($dados['datas'] as $d): ?>
                                                            <th class="text-center"><?= date('d/m', strtotime($d)) ?></th>
                                                        <?php endforeach; ?>
                                                        <th>Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="corpo-edicao">
                                                    <?php foreach ($linha['detalhes'] as $item => $info): // CORRIGIDO 
                                                    ?>
                                                        <tr>
                                                            <td><strong><?= htmlspecialchars($item) ?></strong></td>
                                                            <?php foreach ($dados['datas'] as $d): ?>
                                                                <?php
                                                                $valorDia = $info['dias'][$d] ?? 0;
                                                                $idLancamento = 0; // mesmo caso do KG
                                                                $funcId = $dados['funcionario_ids'][$nome] ?? 0;
                                                                $tipoId = $dados['tipo_produto_ids'][$item] ?? 0;
                                                                ?>
                                                                <td class="text-center celula-valor"
                                                                    data-id="<?= $idLancamento ?>"
                                                                    data-data="<?= $d ?>"
                                                                    data-funcionario-id="<?= $funcId ?>"
                                                                    data-tipo-produto-id="<?= $tipoId ?>">
                                                                    <span class="valor-exibicao">
                                                                        <?= $valorDia > 0 ? 'R$ ' . number_format($valorDia, 2, ',', '.') : '-' ?>
                                                                    </span>
                                                                    <input type="text" class="form-control form-control-sm input-edicao"
                                                                        value="<?= $valorDia > 0 ? number_format($valorDia, 2, ',', '.') : '' ?>"
                                                                        placeholder="0,00" style="display:none; width:100px;">
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td>
                                                                <button class="btn btn-danger btn-sm btn-excluir-linha">Excluir</button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-success">
                            <tr>
                                <th>TOTAL GERAL</th>
                                <?php foreach ($dados['datas'] as $d): ?>
                                    <th class="text-center total-dia">
                                        R$ <?= number_format($dados['total_por_dia'][$d] ?? 0, 2, ',', '.') ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-center" id="total-geral">
                                    R$ <?= number_format($dados['total_geral'] ?? 0, 2, ',', '.') ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    var relatorioDatas = <?= json_encode($dados['datas']) ?>;
    var unidadeMedida = 'R$';
</script>