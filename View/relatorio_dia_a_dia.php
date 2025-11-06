<!-- app/View/relatorio_dia_a_dia.php -->
<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary"><?= $titulo ?></h6>
        <div>
            <button onclick="imprimirPDF()" class="btn btn-success btn-sm">
                <i class="fas fa-file-pdf"></i> Gerar PDF
            </button>
        </div>
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
                    <?php foreach ($linhas as $linha): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($linha['funcionario']) ?></strong></td>
                            <?php foreach ($datas as $d):
                                $valor = $tipo === 'kg' ? ($linha[$d] ?? 0) : ($linha[$d] ?? 0);
                                $class = $valor > 0 ? 'text-success' : '';
                            ?>
                                <td class="text-end <?= $class ?>">
                                    <?= $tipo === 'kg' ? number_format($valor, 3, ',', '.') : 'R$ ' . number_format($valor, 2, ',', '.') ?>
                                </td>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold table-success">
                                <?= $tipo === 'kg' ? number_format($linha['total'], 3, ',', '.') : 'R$ ' . number_format($linha['total'], 2, ',', '.') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark">
                        <th>TOTAL GERAL</th>
                        <?php foreach ($datas as $d):
                            $total_dia = array_sum(array_column($linhas, $d));
                        ?>
                            <th class="text-end"><?= $tipo === 'kg' ? number_format($total_dia, 3, ',', '.') : 'R$ ' . number_format($total_dia, 2, ',', '.') ?></th>
                        <?php endforeach; ?>
                        <th class="text-end">R$ <?= number_format($total_geral, 2, ',', '.') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    function imprimirPDF() {
        const params = new URLSearchParams(window.location.search);
        params.set('pdf', '1');
        window.open('/relatorios/imprimir?' + params.toString(), '_blank');
    }
    $(document).ready(function() {
        $('#tabelaRelatorio').DataTable({
            ordering: true,
            pageLength: 50,
            dom: 'Bfrtip',
            buttons: ['copy', 'excel']
        });
    });
</script>