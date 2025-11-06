<?php
// View/valores_pagamento_lista.php
$valores = $dados['valores'] ?? [];
$base_url = '/sgi_erp';
$pode_editar = $dados['pode_editar'] ?? false;
?>

<div class="pt-4">
    <h1 class="mt-4">Gestão de Valores de Pagamento</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Defina o valor pago por quilo para cada Ação e Tipo de Produto.</li>
    </ol>

    <div class="mb-4">
        <?php if ($pode_editar): ?>
            <a href="<?php echo $base_url; ?>/admin/valores-pagamento/cadastro" class="btn btn-primary shadow float-end btn-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Novo Valor
            </a>
            <div style="clear: both;"></div>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Valores por Ação e Produto</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="datatablesSimple">
                    <thead>
                        <tr>
                            <th>Ação</th>
                            <th>Tipo de Produto</th>
                            <th>Valor por Quilo (R$)</th>
                            <?php if ($pode_editar): ?>
                                <th class="text-center" style="width: 120px;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($valores)): ?>
                            <tr>
                                <td colspan="<?php echo $pode_editar ? 4 : 3; ?>" class="text-center">Nenhum valor cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($valores as $v): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v->acao_nome); ?></td>
                                    <td><?php echo htmlspecialchars($v->produto_nome); ?></td>
                                    <td>R$ <?php echo number_format($v->valor_por_quilo, 2, ',', '.'); ?></td>
                                    <?php if ($pode_editar): ?>
                                        <td class="text-center">
                                            <a href="<?php echo $base_url; ?>/admin/valores-pagamento/cadastro?id=<?php echo $v->id; ?>"
                                                class="btn btn-sm btn-warning" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)" onclick="confirmarExclusaoValor(<?php echo $v->id; ?>, '<?php echo addslashes($v->acao_nome . ' - ' . $v->produto_nome); ?>')"
                                                class="btn btn-sm btn-danger" title="Excluir">
                                                <i class="fas fa-trash"></i>
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