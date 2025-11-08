<!-- View/relatorio_produtividade.php -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($title) ?></h1>

    <!-- FILTRO -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" action="/sgi_erp/relatorios/produtividade">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label>Data Início</label>
                        <input type="date" name="ini" class="form-control" value="<?= $dados['data_inicio'] ?>" required>
                    </div>
                    <div class="col-md-5">
                        <label>Data Fim</label>
                        <input type="date" name="fim" class="form-control" value="<?= $dados['data_fim'] ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label><br>
                        <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($dados['produtividade'])): ?>
        <div class="alert alert-info">Nenhum dado de produção encontrado no período.</div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-info">Produtividade (Kg/Hora)</h6>
                <div>
                    <button id="btn-pdf" class="btn btn-danger btn-sm me-2">PDF</button>
                    <button id="btn-excel" class="btn btn-success btn-sm">Excel</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelaProdutividade">
                        <thead class="table-info">
                            <tr>
                                <th>FUNCIONÁRIO</th>
                                <th class="text-end">TOTAL KG</th>
                                <th class="text-end">TOTAL HORAS</th>
                                <th class="text-end">PRODUTIVIDADE (KG/H)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_geral_kg = 0;
                            $total_geral_horas = 0;
                            foreach ($dados['produtividade'] as $fid => $p):
                                $total_geral_kg += $p['total_kg'];
                                $total_geral_horas += $p['total_horas'];
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['nome']) ?></strong></td>
                                    <td class="text-end">
                                        <?= number_format($p['total_kg'], 3, ',', '.') ?> kg
                                    </td>
                                    <td class="text-end">
                                        <?= number_format($p['total_horas'], 2, ',', '.') ?> h
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?= number_format($p['kg_hora'], 2, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-info">
                            <tr>
                                <th>TOTAL GERAL</th>
                                <th class="text-end">
                                    <?= number_format($total_geral_kg, 3, ',', '.') ?> kg
                                </th>
                                <th class="text-end">
                                    <?= number_format($total_geral_horas, 2, ',', '.') ?> h
                                </th>
                                <th class="text-end fw-bold">
                                    <?php
                                    $produtividade_media = $total_geral_horas > 0 ? $total_geral_kg / $total_geral_horas : 0;
                                    echo number_format($produtividade_media, 2, ',', '.');
                                    ?>
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="alert alert-info mt-3">
                    A produtividade média é calculada como (Total de Kg) / (Total de Horas).
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>