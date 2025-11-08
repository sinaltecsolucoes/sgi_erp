<?php
// View/equipes_modal_mover.php

$base_url = '/sgi_erp';
$equipeModel = new EquipeModel();
$hoje = date('Y-m-d');
$equipes_ativas = $equipeModel->buscarTodasEquipesAtivasHoje();
?>

<div class="modal fade" id="moverMembroModal" tabindex="-1" aria-labelledby="moverMembroModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?php echo $base_url; ?>/equipes/mover" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="moverMembroModalLabel">
                        Mover: <span id="membro-nome-mover" class="text-primary"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <input type="hidden" name="funcionario_id" id="mover-funcionario-id">
                    <input type="hidden" name="equipe_origem_id" id="mover-equipe-origem-id">

                    <p><strong>De:</strong> <span id="equipe-origem-nome" class="text-danger"></span></p>

                    <div class="mb-3">
                        <label for="equipe_destino_id" class="form-label"><strong>Para:</strong></label>
                        <select id="equipe_destino_id" name="equipe_destino_id" class="form-select" required>
                            <option value="">-- Selecione a Equipe de Destino --</option>
                            <?php foreach ($equipes_ativas as $eq): ?>
                                <option value="<?= $eq->id ?>">
                                    <?= htmlspecialchars($eq->nome) ?> (<?= htmlspecialchars($eq->apontador_nome) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="alert alert-danger mt-2" id="alerta-mesma-equipe" style="display:none;">
                        Não é possível mover para a mesma equipe.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btn-mover-membro">Mover Membro</button>
                </div>
            </form>
        </div>
    </div>
</div>