<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title><?= $titulo ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
        }

        th {
            background: #f0f0f0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .total {
            background: #d4edda !important;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>SISTEMA SGI ERP</h1>
        <h2><?= $titulo ?></h2>
        <p>Período: <?= date('d/m/Y', strtotime($data_inicio)) ?> a <?= date('d/m/Y', strtotime($data_fim)) ?></p>
        <p>Gerado em: <?= date('d/m/Y H:i') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Funcionário</th>
                <?php foreach ($datas as $d): ?>
                    <th><?= date('d/m', strtotime($d)) ?></th>
                <?php endforeach; ?>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($linhas as $l): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($l['funcionario']) ?></strong></td>
                    <?php foreach ($datas as $d): ?>
                        <td><?= $tipo === 'kg'
                                ? number_format($l[$d] ?? 0, 3, ',', '.')
                                : 'R$ ' . number_format($l[$d] ?? 0, 2, ',', '.') ?></td>
                    <?php endforeach; ?>
                    <td class="total">
                        <?= $tipo === 'kg'
                            ? number_format($l['total'], 3, ',', '.')
                            : 'R$ ' . number_format($l['total'], 2, ',', '.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>TOTAL GERAL</th>
                <?php foreach ($datas as $d):
                    $total_dia = array_sum(array_column($linhas, $d)); ?>
                    <th><?= $tipo === 'kg'
                            ? number_format($total_dia, 3, ',', '.')
                            : 'R$ ' . number_format($total_dia, 2, ',', '.') ?></th>
                <?php endforeach; ?>
                <th>R$ <?= number_format($total_geral, 2, ',', '.') ?></th>
            </tr>
        </tfoot>
    </table>
</body>

</html>