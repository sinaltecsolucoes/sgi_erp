<?php
// Extrai os dados passados pelo Controller
$equipe = $dados['equipe'] ?? null;
$funcionarios_disponiveis = $dados['funcionarios_disponiveis'] ?? [];
$membros_equipe_ids = $dados['membros_equipe_ids'] ?? [];
$base_url = '/sgi_erp';

$nome_equipe_atual = $equipe ? htmlspecialchars($equipe->nome) : 'Equipe ' . date('d/m');
?>

<div class="pt-4">
    <h1 class="mt-4">Montagem de Equipe</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Gerencie os membros da equipe de produção.</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações da Equipe</h6>
                </div>

                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/equipes/salvar" method="POST">

                        <div class="mb-4">
                            <label for="nome_equipe" class="form-label font-weight-bold">Nome da Equipe Atual:</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nome_equipe"
                                name="nome_equipe"
                                required
                                value="<?php echo $nome_equipe_atual; ?>"
                                placeholder="Ex: Equipe A (Turno Manhã)">
                        </div>

                        <div class="mb-4">
                            <h5 class="text-primary mb-3">Funcionários Presentes e Disponíveis</h5>
                            <p class="text-muted small">Selecione abaixo quem fará parte da equipe hoje. (Apenas funcionários marcados como presentes aparecem aqui).</p>

                            <?php if (empty($funcionarios_disponiveis)): ?>
                                <div class="alert alert-warning text-center">Nenhum funcionário de produção está presente. Faça a Chamada de Presença primeiro.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($funcionarios_disponiveis as $funcionario): ?>
                                        <?php
                                        $id = $funcionario->id;
                                        $is_checked = in_array($id, $membros_equipe_ids);
                                        ?>

                                        <label class="list-group-item d-flex justify-content-between align-items-center">

                                            <span class="text-gray-800">
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($funcionario->nome); ?>
                                            </span>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input"
                                                    type="checkbox"
                                                    role="switch"
                                                    name="membros[]"
                                                    value="<?php echo $id; ?>"
                                                    id="check-<?php echo $id; ?>"
                                                    <?php echo $is_checked ? 'checked' : ''; ?>>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow">
                                <i class="fas fa-save me-2"></i> Salvar Equipe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>