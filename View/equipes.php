<?php
// View/equipes.php
// Extrai os dados passados pelo Controller
$equipe = $dados['equipe'] ?? null;
$funcionariosDisponiveis = $dados['funcionarios_disponiveis'] ?? [];
$funcionariosNaEquipe = $dados['funcionariosNaEquipe'] ?? []; // Array de IDs
$base_url = '/sgi_erp';

// Valores iniciais para o formulário
$equipe_id = $equipe['id'] ?? null;
$nome_equipe_atual = $equipe['nome'] ?? ('Equipe ' . date('d/m'));

// Verifica se a lista de funcionários na equipe tem o ID, para marcar o switch como 'checked'
function is_funcionario_in_equipe($id, $funcionariosNaEquipe)
{
    // Note: $funcionariosNaEquipe agora é um array simples de IDs (inteiros)
    return in_array((int)$id, $funcionariosNaEquipe);
}
?>

<div class="pt-4">
    <h1 class="mt-4">Montagem de Equipe</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/equipes">Montar Equipes</a></li>
        <li class="breadcrumb-item active">Cadastro</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informações da Equipe</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/equipes/salvar" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($equipe_id); ?>">

                        <div class="mb-4">
                            <label for="nome_equipe" class="form-label font-weight-bold">Nome da Equipe Atual:</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nome_equipe"
                                name="nome_equipe"
                                required
                                value="<?php echo htmlspecialchars($nome_equipe_atual); ?>"
                                placeholder="Ex: Equipe A (Turno Manhã)">
                        </div>

                        <div class="mb-4">
                            <h5 class="text-primary mb-3">Funcionários Presentes e Disponíveis</h5>
                            <p class="text-muted small">Selecione abaixo quem fará parte da equipe hoje. (Apenas funcionários presentes aparecem aqui).</p>

                            <?php if (empty($funcionariosDisponiveis)): ?>
                                <div class="alert alert-warning text-center">Nenhum funcionário de produção está presente. Faça a Chamada de Presença primeiro.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($funcionariosDisponiveis as $funcionario):
                                        $id = $funcionario->id;
                                        $nome = htmlspecialchars($funcionario->nome);
                                        $checked = is_funcionario_in_equipe($id, $funcionariosNaEquipe);

                                        // As classes iniciais não precisam mais de PHP, pois o JS faz isso no load.
                                        // Mantemos apenas os IDs e data-id para o JS (equipes-interatividade.js) funcionar
                                    ?>

                                        <label class="list-group-item d-flex justify-content-between align-items-center" id="row-equipe-<?php echo $id; ?>">

                                            <span id="text-equipe-<?php echo $id; ?>" class="flex-grow-1">
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo $nome; ?>
                                            </span>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input equipe-check"
                                                    type="checkbox"
                                                    role="switch"
                                                    name="membros[]"
                                                    value="<?php echo $id; ?>"
                                                    data-id="<?php echo $id; ?>"
                                                    id="check-equipe-<?php echo $id; ?>"
                                                    <?php echo $checked ? 'checked' : ''; ?>>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow" <?php echo empty($funcionariosDisponiveis) ? 'disabled' : ''; ?>>
                                <i class="fas fa-save me-2"></i> Salvar Equipe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>