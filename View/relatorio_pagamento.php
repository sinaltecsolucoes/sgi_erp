<?php
// Extrai dados passados pelo Controller
$relatorio = $dados['relatorio'] ?? [];
$data_inicio = $dados['data_inicio'] ?? date('Y-m-01');
$data_fim = $dados['data_fim'] ?? date('Y-m-t');
$erro = $dados['erro'] ?? '';

// Função auxiliar de formatação
function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>

<div class="pt-4">
    <h1 class="fw-bold mb-3">Relatório de Pagamento por Produtividade</h1>

    <div class="producao-form">
        <form action="/sgi_erp/relatorios" method="GET" class="form-inline">
            <div class="form-group" style="display: inline-block; width: 45%; margin-right: 10px;">
                <label for="data_inicio">Data Início:</label>
                <input type="date" id="data_inicio" name="data_inicio" class="form-select" value="<?php echo htmlspecialchars($data_inicio); ?>" required>
            </div>
            <div class="form-group" style="display: inline-block; width: 45%;">
                <label for="data_fim">Data Fim:</label>
                <input type="date" id="data_fim" name="data_fim" class="form-select" value="<?php echo htmlspecialchars($data_fim); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 20px;">Filtrar Relatório</button>
        </form>
    </div>

    <div style="margin-top: 30px;">
        <?php if ($erro): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($erro); ?></div>
        <?php elseif (!empty($relatorio)): ?>

            <h2>Resultados do Período: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?></h2>

            <?php
            $total_geral = 0;
            foreach ($relatorio as $funcionario):
                $total_geral += $funcionario['total_a_pagar'];
            ?>
                <div class="card mb-4">
                    <div class="card-header" style="background-color: #ecf0f1; padding: 15px; border-bottom: 2px solid #3498db;">
                        <h3 style="margin: 0; display: inline-block;">
                            <?php echo htmlspecialchars($funcionario['nome']); ?>
                        </h3>
                        <span style="float: right; font-size: 1.2em; font-weight: bold; color: #27ae60;">
                            TOTAL A PAGAR: <?php echo formatarMoeda($funcionario['total_a_pagar']); ?>
                        </span>
                    </div>

                    <div class="card-body" style="padding: 15px;">
                        <table class="table table-bordered table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Ação</th>
                                    <th>Produto</th>
                                    <th class="text-end">Quilos (Kg)</th>
                                    <th class="text-end">Valor/Kg</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($funcionario['detalhes'] as $detalhe): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detalhe['acao_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($detalhe['produto_nome']); ?></td>
                                        <td class="text-end"><?php echo number_format($detalhe['total_kg'], 2, ',', '.'); ?></td>
                                        <td class="text-end"><?php echo formatarMoeda($detalhe['valor_unitario']); ?></td>
                                        <td class="text-end"><strong><?php echo formatarMoeda($detalhe['valor_subtotal']); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div style="text-align: right; margin-top: 30px; font-size: 1.5em; border-top: 3px double #2c3e50; padding-top: 10px;">
                TOTAL GERAL CALCULADO: **<?php echo formatarMoeda($total_geral); ?>**
            </div>

        <?php endif; ?>
    </div>
</div>