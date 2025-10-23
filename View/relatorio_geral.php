<?php
// Dados necessários da array $dados
$relatorio = $dados['relatorio'] ?? []; // Já é o merge de produção + serviços
$data_inicio = $dados['data_inicio'] ?? date('Y-m-d');
$data_fim = $dados['data_fim'] ?? date('Y-m-t');
$erro = $dados['erro'] ?? '';
$titulo_relatorio = $dados['titulo_relatorio'] ?? 'Relatório de Pagamento por Produtividade';
$coluna_principal = $dados['coluna_principal'] ?? 'Valores (R$)';
$visualizacao = $dados['visualizacao'] ?? 'sintetico';

// Funções Auxiliares de Formatação (Assumindo que são definidas no escopo)
function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($titulo_relatorio); ?></h1>
    <p class="mb-4">Resultados do período: <strong><?php echo date('d/m/Y', strtotime($data_inicio)); ?></strong> a <strong><?php echo date('d/m/Y', strtotime($data_fim)); ?></strong></p>

    <div class="card shadow mb-4">

        <div class="card shadow mb-4">
            <div class="card-body">
                <form action="/sgi_erp/relatorios" method="GET">
                    <div class="row align-items-end">

                        <div class="col-md-3 mb-3">
                            <label for="data_inicio" class="form-label small">Data Início:</label>
                            <input type="date" id="data_inicio" name="data_inicio" class="form-control" value="<?php echo htmlspecialchars($data_inicio); ?>" required>
                        </div>

                        <div class="col-md-3 mb-3">
                            <label for="data_fim" class="form-label small">Data Fim:</label>
                            <input type="date" id="data_fim" name="data_fim" class="form-control" value="<?php echo htmlspecialchars($data_fim); ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="visualizacao" class="form-label small">Visualização:</label>
                            <select id="visualizacao" name="visualizacao" class="form-select">
                                <option value="sintetico" <?php echo $visualizacao === 'sintetico' ? 'selected' : ''; ?>>Sintético (Colapsado)</option>
                                <option value="analitico" <?php echo $visualizacao === 'analitico' ? 'selected' : ''; ?>>Analítico (Expandido)</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <button type="submit" class="btn btn-info w-100">
                                <i class="fas fa-filter"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </form>
                <?php if ($erro): ?>
                    <div class="alert alert-danger mt-3"><?php echo htmlspecialchars($erro); ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (!empty($relatorio)): ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Detalhes de Lançamentos</h6>

                <button class="btn btn-secondary btn-sm btn-print" onclick="imprimirRelatorio()">
                    <i class="fas fa-print"></i> Imprimir Relatório
                </button>
            </div>

            <script>
                function imprimirRelatorio() {
                    const dataInicio = document.getElementById('data_inicio').value;
                    const dataFim = document.getElementById('data_fim').value;
                    const visualizacao = document.getElementById('visualizacao').value; // Captura o valor do SELECT

                    const urlBase = '/sgi_erp/relatorios/imprimir';
                    const params = `?data_inicio=${dataInicio}&data_fim=${dataFim}&visualizacao=${visualizacao}`;

                    // Abre a nova View de Impressão com os parâmetros
                    window.open(urlBase + params, '_blank');
                }
            </script>

            <div class="card-body">
                <div class="table-responsive">

                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">

                        <thead>
                            <tr class="bg-light">
                                <th style="width: 40%;">NOMES</th>
                                <th class="text-end"><?php echo date('d/m/Y', strtotime($data_inicio)); ?> Val. (R$)</th>
                                <th class="text-end">Total Val. (R$)</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $total_geral_valor = 0;
                            $index = 0;
                            foreach ($relatorio as $funcionario_id => $func):
                                $total_geral_valor += ($func['total_a_pagar'] ?? 0.00);
                                $target_id = 'detalhes-' . $funcionario_id . '-' . $index;
                                $index++;
                            ?>

                                <tr class="table-secondary font-weight-bold">
                                    <td class="text-uppercase"><?php echo htmlspecialchars($func['nome']); ?></td>
                                    <td class="text-end"><?php echo formatarMoeda($func['total_a_pagar'] ?? 0.00); ?></td>
                                    <td class="text-end"><?php echo formatarMoeda($func['total_a_pagar'] ?? 0.00); ?></td>

                                    <td class="text-center" style="width: 50px;">
                                        <button class="btn btn-sm btn-outline-primary"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#<?php echo $target_id; ?>"
                                            title="Ver Detalhes">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>

                                <tr class="collapse-details">
                                    <td colspan="4" class="p-0">
                                        <div class="collapse <?php echo $visualizacao === 'analitico' ? 'show' : ''; ?>" id="<?php echo $target_id; ?>">
                                            <table class="table table-sm table-borderless mb-0">
                                                <thead class="small">
                                                    <tr class="table-light">
                                                        <th style="width: 40%; padding-left: 2rem;">Ação / Produto / Lote</th>
                                                        <th class="text-end">Valor (R$)</th>
                                                        <th class="text-end">Total (R$)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($func['detalhes'] as $detalhe): ?>
                                                        <tr class="small">
                                                            <td style="padding-left: 2rem;">
                                                                <?php echo htmlspecialchars("{$detalhe['acao_nome']} {$detalhe['produto_nome']} - Lote: {$detalhe['lote']}"); ?>
                                                            </td>
                                                            <td class="text-end"><?php echo formatarMoeda($detalhe['valor_subtotal']); ?></td>
                                                            <td class="text-end"><?php echo formatarMoeda($detalhe['valor_subtotal']); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <tr class="table-dark font-weight-bold">
                                <td>TOTAL GERAL</td>
                                <td class="text-end" colspan="2"><?php echo formatarMoeda($total_geral_valor); ?></td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info">Nenhum pagamento encontrado para este período.</div>
    <?php endif; ?>
</div>