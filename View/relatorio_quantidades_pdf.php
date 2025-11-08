<?php
// View/relatorio_quantidades_pdf.php

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
        background-color: #e6e6ff; /* Cor azul clara para cabeçalho */
        font-weight: bold;
        text-align: center;
    }
    .text-end { text-align: right; }
    .fw-bold { font-weight: bold; }
    .total-funcionario { background-color: #ccccff; } 
    .total-dia { background-color: #ddddff; }
    .total-geral { background-color: #aaaaff; }
    .hidden-print { display: none; }
</style>';
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Relatório de Quantidades</title>
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
                <th class="text-end total-dia">TOTAL (KG)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dados['matriz'] as $nome => $linha): ?>
                <tr>
                    <td><?= htmlspecialchars($nome) ?></td>
                    <?php foreach ($dados['datas'] as $d): ?>
                        <?php $kg = $linha[$d] ?? 0; ?>
                        <td class="text-end">
                            <?= $kg > 0 ? number_format($kg, 3, ',', '.') . ' kg' : '' ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="text-end fw-bold total-funcionario">
                        <?= number_format($linha['total'], 3, ',', '.') ?> kg
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th class="fw-bold">TOTAL GERAL</th>
                <?php foreach ($dados['datas'] as $d): ?>
                    <th class="text-end total-dia">
                        <?= number_format($dados['total_por_dia'][$d], 3, ',', '.') ?> kg
                    </th>
                <?php endforeach; ?>
                <th class="text-end total-geral">
                    <?= number_format($dados['total_geral'], 3, ',', '.') ?> kg
                </th>
            </tr>
        </tfoot>
    </table>
</body>

</html>