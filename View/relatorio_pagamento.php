<?php $this->extend('layout/principal'); ?>

<?php $this->section('content'); ?>

<h1 class="mt-4"><?= $titulo ?></h1>

<form method="GET" class="mb-4">
    <div class="row g-3">
        <div class="col-md-3">
            <input type="date" name="data_inicio" class="form-control" value="<?= $data_inicio ?>" required>
        </div>
        <div class="col-md-3">
            <input type="date" name="data_fim" class="form-control" value="<?= $data_fim ?>" required>
        </div>
        <div class="col-md-3">
            <select name="visualizacao" class="form-select">
                <option value="sintetico" <?= $visualizacao == 'sintetico' ? 'selected' : '' ?>>Sintético</option>
                <option value="analitico" <?= $visualizacao == 'analitico' ? 'selected' : '' ?>>Analítico</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100">Filtrar</button>
        </div>
    </div>
</form>

<?php if (empty($linhas)): ?>
    <div class="alert alert-info">Nenhum dado encontrado no período.</div>
<?php else: ?>
    <div class="card shadow">
        <div class="card-header d-flex justify-content-between">
            <h5>Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></h5>
            <button onclick="window.open('/relatorios/imprimir?<?= http_build_query($_GET) ?>', '_blank')"
                class="btn btn-danger">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="tabelaRelatorio">
                    <thead class="table-dark">
                        <tr>
                            <th>Funcionário</th>
                            <?php foreach ($datas as $d): ?>
                                <th class="text-center"><?= date('d/m', strtotime($d)) ?></th>
                            <?php endforeach; ?>
                            <th class="text-end">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($linhas as $l): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($l['funcionario']) ?></strong></td>
                                <?php foreach ($datas as $d): ?>
                                    <td class="text-end <?= ($l[$d] ?? 0) > 0 ? 'text-success' : '' ?>">
                                        <?= $tipo === 'kg'
                                            ? number_format($l[$d] ?? 0, 3, ',', '.')
                                            : 'R$ ' . number_format($l[$d] ?? 0, 2, ',', '.') ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="text-end fw-bold table-success">
                                    <?= $tipo === 'kg'
                                        ? number_format($l['total'], 3, ',', '.')
                                        : 'R$ ' . number_format($l['total'], 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <th>TOTAL GERAL</th>
                            <?php foreach ($datas as $d):
                                $total_dia = array_sum(array_column($linhas, $d)); ?>
                                <th class="text-end">
                                    <?= $tipo === 'kg'
                                        ? number_format($total_dia, 3, ',', '.')
                                        : 'R$ ' . number_format($total_dia, 2, ',', '.') ?>
                                </th>
                            <?php endforeach; ?>
                            <th class="text-end">R$ <?= number_format($total_geral, 2, ',', '.') ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    $(document).ready(function() {
        $('#tabelaRelatorio').DataTable({
            ordering: true,
            pageLength: 100,
            dom: 'Bfrtip',
            buttons: ['excel', 'print']
        });
    });
</script>

<?php $this->endSection(); ?>