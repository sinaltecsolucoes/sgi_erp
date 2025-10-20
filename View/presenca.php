<?php
// Certifica-se de que a variável $dados foi passada pelo Controller (via require_once do main.php)
$funcionarios = $dados['funcionarios'] ?? [];
$data_hoje = $dados['data'] ?? date('Y-m-d');
?>

<div class="content">
    <h1>Registro de Presença</h1>
    <p>Data: <strong><?php echo date('d/m/Y', strtotime($data_hoje)); ?></strong></p>

    <form action="/sgi_erp/presenca/salvar" method="POST">

        <ul class="chamada-lista">
            <?php if (empty($funcionarios)): ?>
                <p style="text-align: center;">Não há funcionários de produção ativos para registrar.</p>
            <?php else: ?>
                <?php foreach ($funcionarios as $funcionario): ?>
                    <li class="chamada-item">
                        <label for="f_<?php echo $funcionario->id; ?>">
                            <?php echo htmlspecialchars($funcionario->nome); ?>
                        </label>
                        <input
                            type="checkbox"
                            id="f_<?php echo $funcionario->id; ?>"
                            name="presente[]"
                            value="<?php echo $funcionario->id; ?>"
                            <?php echo ($funcionario->esta_presente == 1) ? 'checked' : ''; ?>>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>

        <div style="text-align: center; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Salvar Chamada do Dia</button>
        </div>
    </form>
</div>