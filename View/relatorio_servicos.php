<?php
// Dados necessários da array $dados
$relatorio = $dados['relatorio'] ?? [];
$data_inicio = $dados['data_inicio'] ?? date('Y-m-d');
$data_fim = $dados['data_fim'] ?? date('Y-m-t');
$erro = $dados['erro'] ?? '';
$titulo_relatorio = $dados['titulo_relatorio'] ?? 'Relatório de Serviços - Diárias';
$coluna_principal = $dados['coluna_principal'] ?? 'Valores (R$)';

// Funções Auxiliares de Formatação
function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
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
                            $total_geral_valor = 0;
                            foreach ($relatorio as $funcionario_id => $func):
                                $total_func_valor = array_sum(array_column($func['detalhes'], 'valor_subtotal'));
                                $total_geral_valor += $total_func_valor;
                            ?>
                                <tr class="table-secondary font-weight-bold">
                                    <td class="text-uppercase"><?php echo htmlspecialchars($func['nome']); ?></td>
                                    <td class="text-end"><?php echo formatarMoeda($total_func_valor); ?></td>
                                    <td class="text-end"><?php echo formatarMoeda($total_func_valor); ?></td>
                                </tr>

                                <?php foreach ($func['detalhes'] as $detalhe): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars("{$detalhe['acao_nome']} ({$detalhe['produto_nome']})"); ?></td>
                                        <td class="text-end"><?php echo formatarMoeda($detalhe['valor_subtotal']); ?></td>
                                        <td class="text-end"><?php echo formatarMoeda($detalhe['valor_subtotal']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>

                            <tr class="table-dark font-weight-bold">
                                <td>TOTAL GERAL</td>
                                <td class="text-end"><?php echo formatarMoeda($total_geral_valor); ?></td>
                                <td class="text-end"><?php echo formatarMoeda($total_geral_valor); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info">Nenhum serviço/diária encontrado para este período.</div>
    <?php endif; ?>
</div>