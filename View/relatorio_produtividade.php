<?php
$relatorio = $dados['relatorio'] ?? [];
$data_inicio = $dados['data_inicio'] ?? date('Y-m-d');
$data_fim = $dados['data_fim'] ?? date('Y-m-t');
$erro = $dados['erro'] ?? '';
$titulo_relatorio = $dados['titulo_relatorio'] ?? 'Análise de Produtividade';
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($titulo_relatorio); ?></h1>
    <p class="mb-4">Resultados do período: <strong><?php echo date('d/m/Y', strtotime($data_inicio)); ?></strong> a <strong><?php echo date('d/m/Y', strtotime($data_fim)); ?></strong></p>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php elseif (!empty($relatorio)): ?>

        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="datatablesSimple" width="100%" cellspacing="0">

                        <thead>
                            <tr class="bg-light">
                                <th>Funcionário</th>
                                <th class="text-end">Total Produzido (Kg)</th>
                                <th class="text-end">Total Horas Trabalhadas</th>
                                <th class="text-end text-primary">Produtividade (Kg/Hora)</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($relatorio as $r): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($r['nome']); ?></td>
                                    <td class="text-end"><?php echo number_format($r['total_kg'], 2, ',', '.'); ?></td>
                                    <td class="text-end"><?php echo number_format($r['total_horas'], 2, ',', '.'); ?></td>
                                    <td class="text-end font-weight-bold text-primary"><?php echo number_format($r['produtividade_hora'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info">Nenhuma produção registrada no período para análise de produtividade.</div>
    <?php endif; ?>
</div>