<!-- View/relatorio_quantidades.php -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($title) ?></h1>

    <!-- FILTRO -->
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
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($dados['matriz'])): ?>
        <div class="alert alert-info">Nenhum lançamento no período.</div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Quantidades Produzidas (Kg)</h6>
                <div>
                    <button id="btn-pdf" class="btn btn-danger btn-sm me-2">PDF</button>
                    <button id="btn-excel" class="btn btn-success btn-sm">Excel</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tabelaPrincipal">
                        <thead class="table-dark">
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
                                <tr class="funcionario-linha" style="cursor: pointer; background: #f8f9fa;">
                                    <td class="nome-funcionario">
                                        <i class="fas fa-plus-circle text-primary me-2 icon-expand"></i>
                                        <strong><?= htmlspecialchars($nome) ?></strong>
                                    </td>

                                    <?php foreach ($dados['datas'] as $d): ?>
                                        <?php $kg = $linha['dias'][$d] ?? 0; 
                                        ?>
                                        <td class="text-center <?= $kg > 0 ? 'text-success fw-bold' : '' ?> valor-celula" data-data="<?= $d ?>">
                                            <?= $kg > 0 ? number_format($kg, 3, ',', '.') : '' ?>
                                        </td>
                                    <?php endforeach; ?>

                                    <td class="text-center fw-bold table-success total-funcionario">
                                        <?= number_format($linha['total'], 3, ',', '.') ?>
                                    </td>
                                </tr>

                                <!-- LINHA EXPANDIDA - DETALHES POR PRODUTO -->
                                <tr class="detalhes-linha" style="display: none;">
                                    <td colspan="<?= count($dados['datas']) + 2 ?>">
                                        <div class="p-4 bg-light rounded">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <strong class="text-primary h5 mb-0">
                                                    Lançamentos de <?= htmlspecialchars($nome) ?>
                                                </strong>
                                                <div>
                                                    <button class="btn btn-warning btn-sm btn-editar-produto">Editar</button>
                                                    <button class="btn btn-success btn-sm btn-salvar-produto" style="display:none;">Salvar</button>
                                                    <button class="btn btn-secondary btn-sm btn-cancelar-produto" style="display:none;">Cancelar</button>
                                                    <button class="btn btn-info btn-sm btn-desfazer" style="display:none;">Desfazer</button>
                                                </div>
                                            </div>

                                            <table class="table table-sm table-bordered table-hover">
                                                <thead class="table-secondary">
                                                    <tr>
                                                        <th>Produto</th>
                                                        <?php foreach ($dados['datas'] as $d): ?>
                                                            <th class="text-center"><?= date('d/m', strtotime($d)) ?></th>
                                                        <?php endforeach; ?>
                                                        <th class="coluna-acao">Ação</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="corpo-edicao">
                                                    <?php foreach ($linha['detalhes'] as $prod => $infoProduto): // CORRIGIDO AQUI 
                                                    ?>
                                                        <tr data-produto="<?= htmlspecialchars($prod) ?>">
                                                            <td><strong><?= htmlspecialchars($prod) ?></strong></td>
                                                            <?php foreach ($dados['datas'] as $d): ?>
                                                                <?php
                                                                $kgDia = $infoProduto['dias'][$d] ?? 0;
                                                                // ID não vem no gerarRelatorioCompleto(), então deixamos 0 (novo lançamento)
                                                                $idLancamento = 0;
                                                                $funcId = $dados['funcionario_ids'][$nome] ?? 0;
                                                                $tipoId = $dados['tipo_produto_ids'][$prod] ?? 0;
                                                                ?>
                                                                <td class="text-center celula-valor"
                                                                    data-id="<?= $idLancamento ?>"
                                                                    data-data="<?= $d ?>"
                                                                    data-funcionario-id="<?= $funcId ?>"
                                                                    data-tipo-produto-id="<?= $tipoId ?>">
                                                                    <span class="valor-exibicao">
                                                                        <?= $kgDia > 0 ? number_format($kgDia, 3, ',', '.') : '-' ?>
                                                                    </span>
                                                                    <input type="text" class="form-control form-control-sm input-edicao"
                                                                        value="<?= $kgDia > 0 ? number_format($kgDia, 3, ',', '.') : '' ?>"
                                                                        placeholder="0,000" style="display:none; width:90px;">
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td>
                                                                <button class="btn btn-danger btn-sm btn-excluir-linha" title="Excluir este produto do dia">
                                                                    Excluir
                                                                </button>
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
                        <tfoot class="table-dark">
                            <tr>
                                <th>TOTAL GERAL</th>
                                <?php foreach ($dados['datas'] as $d): ?>
                                    <th class="text-center total-dia">
                                        <?= number_format($dados['total_por_dia'][$d] ?? 0, 3, ',', '.') ?>
                                    </th>
                                <?php endforeach; ?>
                                <th class="text-center" id="total-geral">
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