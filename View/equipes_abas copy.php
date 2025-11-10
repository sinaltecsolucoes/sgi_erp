<?php
// View/equipes_abas.php (NOVA ESTRUTURA COM ABAS)

$apontador_id = $_SESSION['funcionario_id'];
$base_url = '/sgi_erp';

// Dados recebidos do Controller (AGORA SERÁ UMA LISTA DE EQUIPES)
$equipes_do_apontador = $dados['equipes_do_apontador'] ?? [];
// Funcionários Presentes E NÃO ALOCADOS em NENHUMA equipe
$funcionarios_disponiveis = $dados['funcionarios_disponiveis'] ?? [];

// -----------------------------------------------------------
// FUNÇÕES AUXILIARES DA VIEW
// -----------------------------------------------------------

// Função auxiliar para renderizar a lista de funcionários com switches
function renderizar_lista_funcionarios($lista, $equipe_id, $is_readonly)
{
    // is_readonly: TRUE para a lista de disponíveis (apenas visualização)
    if (empty($lista)):
        $msg = $equipe_id === 0 ? 'Nenhum funcionário presente e não alocado.' : 'Nenhum membro nesta equipe.';
?>
        <div class="alert alert-warning text-center"><?php echo $msg; ?></div>
    <?php
    else:
    ?>
        <div class="list-group">
            <?php foreach ($lista as $funcionario):
                $id = $funcionario->id;
                $nome = htmlspecialchars($funcionario->nome);

                // Marca como checked se estiver sendo listado dentro de uma aba de equipe (id > 0)
                $checked = $equipe_id > 0;
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
                            name="membros[<?php echo $equipe_id; ?>][]"
                            value="<?php echo $id; ?>"
                            data-id="<?php echo $id; ?>"
                            id="check-equipe-<?php echo $id; ?>"
                            <?php echo $checked ? 'checked' : ''; ?>
                            <?php echo $is_readonly ? 'disabled' : ''; ?>>
                    </div>
                </label>
            <?php endforeach; ?>
        </div>
<?php
    endif;
}

// -----------------------------------------------------------
// ESTRUTURA DE ABAS
// -----------------------------------------------------------
?>

<div class="pt-4">
    <h1 class="mt-4">Gestão de Equipes de Produção</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Gerencie seus grupos de trabalho e lance a produção por aqui.</li>
    </ol>

    <div class="card shadow mb-4">
        <div class="card-body">

            <div class="d-flex justify-content-between mb-3">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#novaEquipeModal">
                    <i class="fas fa-plus me-2"></i> Criar Nova Equipe
                </button>
            </div>

            <ul class="nav nav-pills mb-3" id="equipesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="disponiveis-tab" data-bs-toggle="pill" data-bs-target="#disponiveis-content" type="button" role="tab" aria-controls="disponiveis-content" aria-selected="true">
                        <i class="fas fa-user-check me-1"></i> Disponíveis
                        <span class="badge bg-secondary ms-1"><?php echo count($funcionarios_disponiveis); ?></span>
                    </button>
                </li>

                <?php foreach ($equipes_do_apontador as $equipe): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="equipe-<?php echo $equipe->id; ?>-tab" data-bs-toggle="pill" data-bs-target="#equipe-<?php echo $equipe->id; ?>-content" type="button" role="tab" aria-controls="equipe-<?php echo $equipe->id; ?>-content" aria-selected="false">
                            <i class="fas fa-users me-1"></i> <?php echo htmlspecialchars($equipe->nome); ?>
                            <span class="badge bg-primary ms-1"><?php echo $equipe->total_membros; ?></span>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content" id="equipesTabContent">

                <div class="tab-pane fade show active" id="disponiveis-content" role="tabpanel" aria-labelledby="disponiveis-tab">
                    <h5 class="text-primary mb-3">Funcionários Presentes, Não Alocados</h5>
                    <div class="alert alert-info small">
                        **Esta lista é apenas para visualização.** Para alocar um funcionário, use o botão **Criar Nova Equipe**.
                    </div>

                    <?php renderizar_lista_funcionarios($funcionarios_disponiveis, 0, true); ?>
                </div>

                <?php foreach ($equipes_do_apontador as $equipe): ?>
                    <div class="tab-pane fade" id="equipe-<?php echo $equipe->id; ?>-content" role="tabpanel" aria-labelledby="equipe-<?php echo $equipe->id; ?>-tab">

                        <form action="<?php echo $base_url; ?>/equipes/salvar" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="equipe_id" value="<?php echo $equipe->id; ?>">

                            <div class="mb-3">
                                <label for="nome-<?php echo $equipe->id; ?>" class="form-label font-weight-bold">Nome da Equipe</label>
                                <input type="text" class="form-control" id="nome-<?php echo $equipe->id; ?>" name="nome_equipe" value="<?php echo htmlspecialchars($equipe->nome); ?>" required>
                            </div>

                            <hr>
                            <h5 class="text-primary mb-3">Membros Atuais (Desmarque para liberar vaga)</h5>

                            <?php renderizar_lista_funcionarios($equipe->membros, $equipe->id, false); ?>

                            <div class="d-flex justify-content-between mt-4">
                                <button type="submit" class="btn btn-primary shadow">
                                    <i class="fas fa-save me-2"></i> Salvar Edição da Equipe
                                </button>
                                <button type="button" class="btn btn-danger" onclick="window.excluirEquipe(<?php echo $equipe->id; ?>, '<?php echo htmlspecialchars($equipe->nome); ?>')">
                                    <i class="fas fa-trash me-2"></i> Excluir Equipe
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="novaEquipeModal" tabindex="-1" aria-labelledby="novaEquipeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo $base_url; ?>/equipes/salvar-nova" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="novaEquipeModalLabel">Criar Nova Equipe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal-nome-equipe" class="form-label">Nome da Equipe</label>
                        <input type="text" class="form-control" id="modal-nome-equipe" name="nome_equipe" required placeholder="Ex: Equipe A Tarde">
                    </div>

                    <h6 class="mt-4">Selecione os Membros Iniciais:</h6>
                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($funcionarios_disponiveis)): ?>
                            <div class="alert alert-warning text-center small mb-0">Nenhum funcionário presente e não alocado para adicionar.</div>
                        <?php else: ?>
                            <?php foreach ($funcionarios_disponiveis as $f): ?>
                                <label class="list-group-item d-flex justify-content-between align-items-center" id="row-modal-<?php echo $f->id; ?>">
                                    <span id="text-modal-<?php echo $f->id; ?>">
                                        <i class="fas fa-user me-2"></i>
                                        <?php echo htmlspecialchars($f->nome); ?>
                                    </span>
                                    <input class="form-check-input equipe-check"
                                        type="checkbox"
                                        name="membros[]"
                                        value="<?php echo $f->id; ?>"
                                        data-id="<?php echo $f->id; ?>">
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar e Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>