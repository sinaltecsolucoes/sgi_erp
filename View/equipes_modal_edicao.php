<?php
// View/equipes_modal_edicao.php
$equipes_do_apontador_para_editar = $dados['equipes_do_apontador'] ?? [];
$funcionarios_disponiveis_global = $dados['funcionarios_disponiveis'] ?? [];
$base_url = '/sgi_erp';
?>

<?php foreach ($equipes_do_apontador_para_editar as $equipe): ?>
    <div class="modal fade" id="editarEquipeModal-<?php echo $equipe->id; ?>" tabindex="-1" aria-labelledby="editarEquipeModalLabel-<?php echo $equipe->id; ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form action="<?php echo $base_url; ?>/equipes/salvar" method="POST">
                    <input type="hidden" name="equipe_id" value="<?php echo $equipe->id; ?>">
                    <input type="hidden" name="acao_edicao" value="1"> <!-- Identifica que é edição -->

                    <div class="modal-header">
                        <h5 class="modal-title" id="editarEquipeModalLabel-<?php echo $equipe->id; ?>">
                            Editar Equipe: <?php echo htmlspecialchars($equipe->nome); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <!-- RENOMEAR EQUIPE -->
                        <div class="mb-4">
                            <label for="modal-edit-nome-<?php echo $equipe->id; ?>" class="form-label font-weight-bold">
                                Nome da Equipe
                            </label>
                            <input type="text"
                                class="form-control"
                                id="modal-edit-nome-<?php echo $equipe->id; ?>"
                                name="nome_equipe"
                                value="<?php echo htmlspecialchars($equipe->nome); ?>"
                                required>
                        </div>

                        <!-- ADICIONAR NOVOS MEMBROS -->
                        <h6 class="text-primary">Adicionar Membros Disponíveis</h6>
                        <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($funcionarios_disponiveis_global)): ?>
                                <div class="alert alert-info small">Nenhum funcionário disponível para adicionar.</div>
                            <?php else: ?>
                                <?php foreach ($funcionarios_disponiveis_global as $f): ?>
                                    <label class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($f->nome); ?>
                                        </span>
                                        <input class="form-check-input"
                                            type="checkbox"
                                            name="membros_adicionar[]"
                                            value="<?php echo $f->id; ?>">
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>