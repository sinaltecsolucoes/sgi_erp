<?php
// View/relatorio_pagamento_pdf.php

// Define a folha de estilo específica para impressão 
$css = '<style>
    body { font-family: Arial, sans-serif; margin: 20px; font-size: 10pt; }
    h1 { font-size: 16pt; color: #333; }
    .data-header { font-size: 11pt; margin-bottom: 20px; }
    .tabela-relatorio { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .tabela-relatorio th, .tabela-relatorio td {
        border: 1px solid #ccc;
        padding: 5px 8px;
    }
    .tabela-relatorio th {
        background-color: #e6ffe6; /* Cor verde clara para cabeçalho */
        font-weight: bold;
        text-align: center;
    }
    .text-end { text-align: right; }
    .fw-bold { font-weight: bold; }
    .total-funcionario { background-color: #ccffcc; } /* Cor mais escura para o total */
    .total-dia { background-color: #ddffdd; }
    .total-geral { background-color: #aaffaa; }
    .hidden-print { display: none; } /* Elementos que não aparecem no PDF */
</style>';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Relatório de Pagamentos</title>
    <?= $css ?>
</head>

<body>
    <h1><?= htmlspecialchars($title) ?></h1>

    <div class="data-header">
        Período: <?= date('d/m/Y', strtotime($dados['data_inicio'])) ?> a <?= date('d/m/Y', strtotime($dados['data_fim'])) ?>
    </div>

    <table class="tabela-relatorio">
        <thead>
            <tr>
                <th>FUNCIONÁRIO</th>
                <?php foreach ($dados['datas'] as $d): ?>
                    <th class="text-end"><?= date('d/m', strtotime($d)) ?></th>
                <?php endforeach; ?>
                <th class="text-end total-dia">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dados['matriz'] as $nome => $linha): ?>
                <tr>
                    <td><?= htmlspecialchars($nome) ?></td>
                    <?php foreach ($dados['datas'] as $d): ?>
                        <?php $valor = $linha[$d] ?? 0; ?>
                        <td class="text-end">
                            <?= $valor > 0 ? 'R$ ' . number_format($valor, 2, ',', '.') : '' ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-end fw-bold total-funcionario">
                        R$ <?= number_format($linha['total'], 2, ',', '.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="fw-bold">TOTAL GERAL</th>
                <?php foreach ($dados['datas'] as $d): ?>
                    <th class="text-end total-dia">
                        R$ <?= number_format($dados['total_por_dia'][$d], 2, ',', '.') ?>
                    </th>
                <?php endforeach; ?>
                <th class="text-end total-geral">
                    R$ <?= number_format($dados['total_geral'], 2, ',', '.') ?>
                </th>
            </tr>
        </tfoot>
    </table>
</body>

</html>