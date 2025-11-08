<?php
// View/equipes_abas.php (CORRIGIDO com Abas e nova UX)

$apontador_id = $_SESSION['funcionario_id'];
$base_url = '/sgi_erp';

// Dados recebidos do Controller
$equipes_do_apontador = $dados['equipes_do_apontador'] ?? [];
// Funcionários Presentes E NÃO ALOCADOS em NENHUMA equipe
$funcionarios_disponiveis = $dados['funcionarios_disponiveis'] ?? [];

// -----------------------------------------------------------
// FUNÇÕES AUXILIARES DA VIEW
// -----------------------------------------------------------

/**
 * Renderiza a lista de funcionários para a aba 'Disponíveis'.
 * Esta lista é apenas para visualização.
 */
function renderizar_lista_funcionarios_disponiveis($lista)
{
    if (empty($lista)):
?>
        <div class="alert alert-warning text-center">Nenhum funcionário presente e não alocado.</div>
    <?php
    else:
    ?>
        <div class="list-group">
            <?php foreach ($lista as $funcionario):
                $id = $funcionario->id;
                $nome = htmlspecialchars($funcionario->nome);
            ?>
                <label class="list-group-item d-flex justify-content-between align-items-center" id="row-equipe-<?php echo $id; ?>">
                    <span id="text-equipe-<?php echo $id; ?>" class="flex-grow-1 text-danger font-weight-bold">
                        <i class="fas fa-user me-2"></i>
                        <?php echo $nome; ?>
                    </span>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" disabled>
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
                        **Esta lista é apenas para visualização.** Use o botão **Criar Nova Equipe** para adicionar membros.
                    </div>

                    <?php renderizar_lista_funcionarios_disponiveis($funcionarios_disponiveis); ?>
                </div>

                <?php foreach ($equipes_do_apontador as $equipe): ?>
                    <div class="tab-pane fade" id="equipe-<?php echo $equipe->id; ?>-content" role="tabpanel" aria-labelledby="equipe-<?php echo $equipe->id; ?>-tab">

                        <form action="<?php echo $base_url; ?>/equipes/salvar" method="POST" class="needs-validation" novalidate>
                            <input type="hidden" name="equipe_id" value="<?php echo $equipe->id; ?>">
                            <input type="hidden" name="remover_membros" value="1">

                            <div class="mb-3">
                                <label class="form-label font-weight-bold d-block">Nome da Equipe</label>
                                <h4 class="text-primary"><?php echo htmlspecialchars($equipe->nome); ?></h4>
                            </div>

                            <div class="d-flex justify-content-start gap-2 mb-4">
                                <button type="button" class="btn btn-warning btn-sm shadow"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editarEquipeModal-<?php echo $equipe->id; ?>"
                                    data-equipe-id="<?php echo $equipe->id; ?>"
                                    data-equipe-nome="<?php echo htmlspecialchars($equipe->nome); ?>">
                                    <i class="fas fa-edit me-1"></i> Editar Membros/Nome
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="window.excluirEquipe(<?php echo $equipe->id; ?>, '<?php echo htmlspecialchars($equipe->nome); ?>')">
                                    <i class="fas fa-trash me-1"></i> Excluir Equipe
                                </button>
                            </div>

                            <hr>
                            <h5 class="text-primary mb-3">Membros Atuais (Desmarque para remover da equipe)</h5>

                            <div class="list-group">
                                <?php if (empty($equipe->membros)): ?>
                                    <div class="alert alert-info text-center">Esta equipe não possui membros. Use o botão Editar para adicionar.</div>
                                <?php else: ?>
                                    <?php foreach ($equipe->membros as $membro):
                                        $id = $membro->id;
                                        $nome = htmlspecialchars($membro->nome);
                                    ?>
                                        <label class="list-group-item d-flex justify-content-between align-items-center list-group-item-light-custom" id="row-equipe-<?php echo $id; ?>">

                                            <span id="text-equipe-<?php echo $id; ?>" class="flex-grow-1 text-success font-weight-bold">
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo $nome; ?>
                                            </span>

                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-info"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#moverMembroModal"
                                                    data-func-id="<?php echo $id; ?>"
                                                    data-func-nome="<?php echo $nome; ?>"
                                                    data-origem-equipe="<?php echo $equipe->id; ?>">
                                                    <i class="fas fa-exchange-alt"></i> Mover
                                                </button>

                                                <div class="form-check form-switch ms-2">
                                                    <input class="form-check-input equipe-check"
                                                        type="checkbox"
                                                        role="switch"
                                                        name="membros[<?php echo $equipe->id; ?>][]"
                                                        value="<?php echo $id; ?>"
                                                        data-id="<?php echo $id; ?>"
                                                        id="check-equipe-<?php echo $id; ?>"
                                                        checked>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary shadow">
                                    <i class="fas fa-save me-2"></i> Salvar Edição de Membros
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

<?php
// Passamos os dados para os modais, garantindo que o escopo seja correto
$dados_para_modais = $dados;

require_once 'equipes_modal_edicao.php';
require_once 'equipes_modal_mover.php';
?>