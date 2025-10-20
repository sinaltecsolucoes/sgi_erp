<?php
// Extrai os dados passados pelo Controller
$equipe = $dados['equipe'] ?? null;
$funcionarios_disponiveis = $dados['funcionarios_disponiveis'] ?? [];
$membros_equipe_ids = $dados['membros_equipe_ids'] ?? [];

$nome_equipe_atual = $equipe ? htmlspecialchars($equipe->nome) : 'Equipe ' . date('d/m');
?>

<div class="content">
    <h1>Montagem de Equipe (Apontador)</h1>

    <div class="equipe-form">
        <form action="/sgi_erp/equipes/salvar" method="POST">

            <div class="form-group">
                <label for="nome_equipe">Nome da Equipe:</label>
                <input
                    type="text"
                    id="nome_equipe"
                    name="nome_equipe"
                    required
                    value="<?php echo $nome_equipe_atual; ?>"
                    placeholder="Ex: Equipe Vermelha">
            </div>

            <hr>

            <h2>Funcionários Presentes e Disponíveis</h2>
            <p>Selecione abaixo os funcionários que farão parte desta equipe hoje:</p>

            <?php if (empty($funcionarios_disponiveis)): ?>
                <p class="alert alert-error" style="text-align: center;">Nenhum funcionário de produção foi marcado como presente hoje. Faça a chamada primeiro.</p>
            <?php else: ?>
                <div class="equipe-membros-grid">
                    <?php foreach ($funcionarios_disponiveis as $funcionario): ?>
                        <?php
                        $id = $funcionario->id;
                        $checked = in_array($id, $membros_equipe_ids) ? 'checked' : '';
                        ?>
                        <label class="membro-card">
                            <input
                                type="checkbox"
                                name="membros[]"
                                value="<?php echo $id; ?>"
                                <?php echo $checked; ?>>
                            <?php echo htmlspecialchars($funcionario->nome); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary" <?php echo empty($funcionarios_disponiveis) ? 'disabled' : ''; ?>>
                    Salvar Equipe
                </button>
            </div>
        </form>
    </div>
</div>