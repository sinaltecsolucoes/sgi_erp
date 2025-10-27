<?php
// View/acao_lista.php
$acoes = $dados['acoes'] ?? [];
$base_url = '/sgi_erp';
$pode_cadastrar_editar = true; // Simplificado para fins de MVP

?>

<div class="pt-4">
    <h1 class="fw-bold mb-3">Gestão de Ações de Produção</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Ações</h6>
            <?php if ($pode_cadastrar_editar): ?>
                <a href="<?php echo $base_url; ?>/admin/acoes/cadastro" class="btn btn-primary btn-sm shadow">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Nova Ação
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center align-middle">ID</th>
                            <th class="text-center align-middle">Nome da Ação</th>
                            <th class="text-center align-middle">Status</th>
                            <?php if ($pode_cadastrar_editar): ?>
                                <th class="text-center align-middle" style="width: 100px;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($acoes)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Nenhuma ação cadastrada.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($acoes as $a):
                                $is_ativo = (int)($a->ativo ?? 1); // Assume ativo=1 por padrão se não vier do Model
                            ?>
                                <tr class="<?php echo $is_ativo ? '' : 'table-danger'; ?>">
                                    <td class="text-center align-middle"><?php echo htmlspecialchars($a->id); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($a->nome); ?></td>

                                    <td class="text-center align-middle">
                                        <span class="badge <?php echo $is_ativo ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $is_ativo ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>

                                    <?php if ($pode_cadastrar_editar): ?>
                                        <td class="text-center align-middle">
                                            <a href="<?php echo $base_url; ?>/admin/acoes/cadastro?id=<?php echo $a->id; ?>"
                                                class="btn btn-sm btn-info" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>