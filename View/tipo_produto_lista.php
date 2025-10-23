<?php
$tipos = $dados['tipos'] ?? [];
$base_url = '/sgi_erp';
$tipo_usuario_logado = $_SESSION['funcionario_tipo'] ?? 'convidado';
$pode_editar = Acl::check('TipoProdutoController@cadastro', $tipo_usuario_logado);
?>

<div class="pt-4">
    <h1 class="mt-4">Cadastro de Tipos de Produto</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Gerencie os tipos de produto e a obrigatoriedade de Lote.</li>
    </ol>

    <div class="mb-4">
        <?php if ($pode_editar): ?>
            <a href="<?php echo $base_url; ?>/admin/tipos-produto/cadastro" class="btn btn-primary shadow float-end btn-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Novo Tipo
            </a>
            <div style="clear: both;"></div>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tipos de Produto Cadastrados</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="datatablesSimple" width="100%" cellspacing="0">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Usa Lote?</th>
                            <?php if ($pode_editar): ?>
                                <th class="text-center" style="width: 100px;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($tipos)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Nenhum tipo de produto cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tipos as $t):
                                $usa_lote = (int)$t->usa_lote === 1;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($t->id); ?></td>
                                    <td><?php echo htmlspecialchars($t->nome); ?></td>
                                    <td>
                                        <span class="badge <?php echo $usa_lote ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $usa_lote ? 'Sim' : 'Não (Serviço)'; ?>
                                        </span>
                                    </td>

                                    <?php if ($pode_editar): ?>
                                        <td class="text-center">
                                            <a href="<?php echo $base_url; ?>/admin/tipos-produto/cadastro?id=<?php echo $t->id; ?>"
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