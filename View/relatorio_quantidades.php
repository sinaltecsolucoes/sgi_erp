<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($title) ?></h1>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="/sgi_erp/relatorios/quantidades">
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
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($dados['matriz'])): ?>
        <div class="alert alert-info">Nenhum lançamento encontrado para o período.</div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Quantidades Produzidas (Kg)</h6>
                <div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelaPrincipal">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th style="width: 350px;">FUNCIONÁRIO / PRODUTO</th>
                                <?php foreach ($dados['datas'] as $d): ?>
                                    <th class="text-center" style="width: 100px;"><?= date('d/m', strtotime($d)) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center bg-secondary" style="width: 140px;">TOTAL</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $funcIndex = 0;
                            // O loop percorre a matriz já consolidada pelo PHP
                            foreach ($dados['matriz'] as $nome => $linha):
                                $funcIndex++;
                                $funcRowId = "func_" . $funcIndex;
                                $funcId = $dados['funcionario_ids'][$nome] ?? 0;
                            ?>
                                <tr class="funcionario-linha table-secondary" data-target="<?= $funcRowId ?>" style="cursor: pointer;">
                                    <td class="nome-funcionario fw-bold text-primary">
                                        <i class="fas fa-plus-circle me-2 icon-expand"></i>
                                        <?= htmlspecialchars($nome) ?>
                                    </td>

                                    <?php foreach ($dados['datas'] as $d): ?>
                                        <?php $kg = $linha['dias'][$d] ?? 0; ?>
                                        <td class="text-center fw-bold total-dia-func <?= $kg > 0 ? 'text-success' : '' ?>"
                                            data-data="<?= $d ?>">
                                            <?= $kg > 0 ? number_format($kg, 3, ',', '.') : '' ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="text-center fw-bold bg-dark text-white total-funcionario-geral">
                                        <?= number_format($linha['total'], 3, ',', '.') ?>
                                    </td>
                                </tr>

                                <?php
                                $prodIndex = 0;
                                foreach ($linha['detalhes'] as $prod => $infoProduto):
                                    $prodIndex++;
                                    $uniqueProdId = $funcRowId . "_prod_" . $prodIndex;
                                    $tipoId = $dados['tipo_produto_ids'][$prod] ?? 0;
                                ?>
                                    <tr class="detalhe-produto linha-filho <?= $funcRowId ?>" id="linha_<?= $uniqueProdId ?>" style="display: none;">
                                        <td style="padding-left: 3rem;" class="text-muted align-middle">
                                            <i class="fas fa-box-open me-2 small"></i>
                                            <?= htmlspecialchars($prod) ?>
                                        </td>

                                        <?php foreach ($dados['datas'] as $d): ?>
                                            <?php
                                            $kgDia = $infoProduto['dias'][$d] ?? 0;
                                            // Pega o ID Individual guardado no array separado
                                            $idLancamento = $dados['ids'][$nome][$d][$prod] ?? 0;
                                            ?>
                                            <td class="text-center celula-valor align-middle"
                                                data-data="<?= $d ?>"
                                                data-funcionario-id="<?= $funcId ?>"
                                                data-tipo-produto-id="<?= $tipoId ?>"
                                                data-lancamento-id="<?= $idLancamento ?>">

                                                <span class="valor-exibicao text-secondary">
                                                    <?= $kgDia > 0 ? number_format($kgDia, 3, ',', '.') : '-' ?>
                                                </span>

                                                <input type="text" class="form-control form-control-sm input-edicao mx-auto"
                                                    value="<?= $kgDia > 0 ? number_format($kgDia, 3, ',', '.') : '' ?>"
                                                    style="display:none; width:80px; text-align:center;">
                                            </td>
                                        <?php endforeach; ?>

                                        <td class="text-center align-middle">
                                            <div class="grupo-botoes-view">
                                                <button class="btn btn-warning btn-sm py-0 px-2 btn-editar-linha" data-id="<?= $uniqueProdId ?>"><i class="fas fa-edit"></i></button>
                                                <button class="btn btn-outline-danger btn-sm py-0 px-2 btn-excluir-linha" data-id="<?= $uniqueProdId ?>" data-nome="<?= htmlspecialchars($prod) ?>"><i class="fas fa-trash"></i></button>
                                            </div>
                                            <div class="grupo-botoes-edit" style="display:none;">
                                                <button class="btn btn-success btn-sm py-0 px-2 btn-salvar-linha" data-id="<?= $uniqueProdId ?>"><i class="fas fa-check"></i></button>
                                                <button class="btn btn-secondary btn-sm py-0 px-2 btn-cancelar-linha" data-id="<?= $uniqueProdId ?>"><i class="fas fa-times"></i></button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>

                        <tfoot class="table-dark">
                            <tr>
                                <th>TOTAL GERAL</th>
                                <?php foreach ($dados['datas'] as $d): ?>
                                    <th class="text-center total-dia-footer" data-data="<?= $d ?>">
                                        <?= number_format($dados['total_por_dia'][$d] ?? 0, 3, ',', '.') ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-center" id="total-geral-final">
                                    <?= number_format($dados['total_geral'] ?? 0, 3, ',', '.') ?>
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
    var unidadeMedida = 'KG';
</script>