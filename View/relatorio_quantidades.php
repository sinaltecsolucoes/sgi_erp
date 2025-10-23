<?php
// Dados necessários da array $dados
$relatorio = $dados['relatorio'] ?? [];
$data_inicio = $dados['data_inicio'] ?? date('Y-m-d');
$data_fim = $dados['data_fim'] ?? date('Y-m-t');
$erro = $dados['erro'] ?? '';
$titulo_relatorio = $dados['titulo_relatorio'] ?? 'Quantidades Produzidas';
$coluna_principal = $dados['coluna_principal'] ?? 'Quant. (Kg)';

// Funções Auxiliares de Formatação (Definidas no Controller para escopo da View)
function formatarNumero($valor)
{
    return number_format($valor, 2, ',', '.');
}
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($titulo_relatorio); ?></h1>
    <div class="card shadow mb-4">
        <div class="card-body">
        </div>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
    <?php elseif (!empty($relatorio)): ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detalhes de Quantidades</h6>
                <button class="btn btn-secondary btn-sm btn-print" onclick="window.print();">
                    <i class="fas fa-print"></i> Imprimir Relatório
                </button>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                        <thead>
                            <tr class="bg-light">
                                <th style="width: 40%;">NOMES</th>
                                <th class="text-end"><?php echo date('d/m/Y', strtotime($data_inicio)); ?> <?php echo $coluna_principal; ?></th>
                                <th class="text-end">Total <?php echo $coluna_principal; ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_geral_kg = 0;
                            foreach ($relatorio as $funcionario_id => $func):
                                $total_func_kg = array_sum(array_column($func['detalhes'], 'total_kg'));
                                $total_geral_kg += $total_func_kg;
                            ?>
                                <tr class="table-secondary font-weight-bold">
                                    <td class="text-uppercase"><?php echo htmlspecialchars($func['nome']); ?></td>
                                    <td class="text-end"><?php echo formatarNumero($total_func_kg); ?></td>
                                    <td class="text-end"><?php echo formatarNumero($total_func_kg); ?></td>
                                </tr>

                                <?php foreach ($func['detalhes'] as $detalhe): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars("{$detalhe['acao_nome']} {$detalhe['produto_nome']} - lote {$detalhe['lote']}"); ?></td>
                                        <td class="text-end"><?php echo formatarNumero($detalhe['total_kg']); ?></td>
                                        <td class="text-end"><?php echo formatarNumero($detalhe['total_kg']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <tr class="table-dark font-weight-bold">
                                <td>TOTAL GERAL</td>
                                <td class="text-end"><?php echo formatarNumero($total_geral_kg); ?></td>
                                <td class="text-end"><?php echo formatarNumero($total_geral_kg); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info">Nenhuma produção rastreável encontrada para este período.</div>
    <?php endif; ?>
</div>