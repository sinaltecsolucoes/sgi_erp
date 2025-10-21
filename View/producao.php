<?php
// Extrai os dados passados pelo Controller
$dados = $dados ?? [];
$equipe = $dados['equipe'] ?? null;
$membros = $dados['membros'] ?? [];
$acoes = $dados['acoes'] ?? [];
$tipos_produto = $dados['tipos_produto'] ?? [];
?>

<div class="content">
    <h1>Lançamento de Produção</h1>

    <div class="producao-form">

        <?php if ($equipe): ?>
            <p>Lançamento para a equipe: <strong><?php echo htmlspecialchars($equipe->nome); ?></strong></p>
            <p style="font-size: 0.9em; color: #555;">(Se a equipe estiver incorreta, volte ao dashboard e corrija em "Montar Equipes").</p>
        <?php else: ?>
            <p class="alert alert-error">É necessário ter uma equipe montada para lançar a produção.</p>
        <?php endif; ?>

        <form action="/sgi_erp/producao/salvar" method="POST">

            <div class="form-group">
                <label for="funcionario_id">Funcionário Responsável:</label>
                <select id="funcionario_id" name="funcionario_id" class="form-select" required>
                    <option value="">-- Selecione o Membro da Equipe --</option>
                    <?php foreach ($membros as $membro): ?>
                        <option value="<?php echo $membro->id; ?>">
                            <?php echo htmlspecialchars($membro->nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="acao_id">Ação Realizada:</label>
                <select id="acao_id" name="acao_id" class="form-select" required>
                    <option value="">-- Selecione a Ação --</option>
                    <?php foreach ($acoes as $acao): ?>
                        <option value="<?php echo $acao->id; ?>">
                            <?php echo htmlspecialchars($acao->nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tipo_produto_id">Tipo de Produto:</label>
                <select id="tipo_produto_id" name="tipo_produto_id" class="form-select" required>
                    <option value="">-- Selecione o Tipo de Produto --</option>
                    <?php foreach ($tipos_produto as $produto): ?>
                        <option value="<?php echo $produto->id; ?>">
                            <?php echo htmlspecialchars($produto->nome); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantidade_kg">Quantidade (em Quilos):</label>
                <input
                    type="number"
                    id="quantidade_kg"
                    name="quantidade_kg"
                    step="0.01"
                    min="0.1"
                    required
                    placeholder="Ex: 5.50">
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">Registrar Produção</button>
            </div>
        </form>
    </div>
</div>