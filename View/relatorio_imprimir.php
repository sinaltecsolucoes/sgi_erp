<?php
// Dados passados do RelatorioController (já com todas as informações)
$relatorio = $dados['relatorio'] ?? [];
$data_inicio = $dados['data_inicio'] ?? date('Y-m-d');
$data_fim = $dados['data_fim'] ?? date('Y-m-t');
$titulo_relatorio = $dados['titulo_relatorio'] ?? 'Relatório de Pagamento por Produtividade';
$visualizacao = $dados['visualizacao'] ?? 'sintetico';

function formatarMoeda($valor)
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>IMPRESSÃO - <?php echo $titulo_relatorio; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-size: 10pt;
            margin: 0;
            padding: 0.5cm;
        }

        .ficha-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .ficha-table th,
        .ficha-table td {
            border: 1px solid #000;
            padding: 5px;
        }

        .ficha-table th {
            background-color: #f0f0f0;
            text-align: left;
        }

        .section-header {
            background-color: #333;
            color: white;
            text-align: center;
            font-weight: bold;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .uppercase {
            text-transform: uppercase;
        }

        /* Estilos específicos do relatório */
        .header-table td {
            padding: 8px;
            border: none !important;
        }

        .total-row th,
        .total-row td {
            background-color: #ccc !important;
            font-weight: bold;
        }

        .subtotal-row th,
        .subtotal-row td {
            background-color: #e9ecef !important;
            font-weight: bold;
        }

        /* Ocultar elementos de tela */
        .no-print {
            display: none;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <h3 class="text-center fw-bold mb-3"><?php echo htmlspecialchars($titulo_relatorio); ?></h3>
        <p class="text-center small mb-4">Período: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?></p>

        <table class="ficha-table">
            <thead>
                <tr>
                    <th colspan="2" class="section-header">DETALHES DOS PAGAMENTOS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_geral_valor = 0;
                // INÍCIO DO LOOP PRINCIPAL
                foreach ($relatorio as $func):
                    $total_func_valor = $func['total_a_pagar'] ?? 0.00;
                    $total_geral_valor += $total_func_valor;
                ?>

                    <tr class="total-row">
                        <th colspan="2" class="uppercase"><?php echo htmlspecialchars($func['nome']); ?></th>
                    </tr>

                    <?php
                    // BLOCO CONDICIONAL: Detalhes Analíticos
                    if (($dados['visualizacao'] ?? 'sintetico') === 'analitico'):
                    ?>
                        <tr class="subtotal-row">
                            <th>Ação / Produto / Lote</th>
                            <th class="text-end">Subtotal (R$)</th>
                        </tr>

                        <?php foreach ($func['detalhes'] as $detalhe): ?>
                            <tr>
                                <td><?php echo htmlspecialchars("{$detalhe['acao_nome']} ({$detalhe['produto_nome']}) Lote: {$detalhe['lote']}"); ?></td>
                                <td class="text-end"><?php echo formatarMoeda($detalhe['valor_subtotal']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php
                    // FIM DO BLOCO CONDICIONAL
                    endif;
                    ?>
                <?php
                // FIM DO LOOP PRINCIPAL
                endforeach;
                ?>

                <tr class="total-row">
                    <th class="text-end">TOTAL GERAL CALCULADO</th>
                    <th class="text-end"><?php echo formatarMoeda($total_geral_valor); ?></th>
                </tr>
            </tbody>
        </table>

        <p class="small mt-5">Relatório gerado em: <?php echo date('d/m/Y H:i:s'); ?></p>
    </div>

    <div class="container-fluid no-print text-center mb-4">
        <button class="btn btn-lg btn-success" onclick="window.print();">
            <i class="fas fa-print"></i> Clique para Imprimir o Relatório
        </button>
        <button class="btn btn-lg btn-secondary" onclick="window.close();">
            <i class="fas fa-times"></i> Fechar
        </button>
    </div>
</body>

</html>