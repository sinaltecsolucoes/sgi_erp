<?php
$funcionarios = $dados['funcionarios'] ?? [];
$tipo_usuario_logado = $_SESSION['funcionario_tipo'] ?? 'convidado';

// Verifica permissões de ação para o botão "Novo Cadastro" e "Ações" da tabela
// ACL: FuncionarioController@cadastro (Permite acessar o formulário de cadastro/edição)
$pode_cadastrar_editar = Acl::check('FuncionarioController@cadastro', $tipo_usuario_logado);
?>

<div class="content">
    <h1>Gestão de Funcionários</h1>
    <p>Cadastros e perfis de acesso dos usuários do SGI ERP.</p>

    <?php if ($pode_cadastrar_editar): ?>
        <a href="/sgi_erp/admin/funcionarios/cadastro" class="btn btn-primary" style="float: right;">+ Novo Funcionário</a>
        <div style="clear: both;"></div>
    <?php endif; ?>

    <div class="table-responsive" style="margin-top: 20px;">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Tipo</th>
                    <th>Login</th>
                    <th>Status</th>
                    <?php if ($pode_cadastrar_editar): ?>
                        <th class="text-center" style="width: 100px;">Ações</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($funcionarios)): ?>
                    <tr>
                        <td colspan="6" class="text-center">Nenhum funcionário cadastrado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($funcionarios as $f): ?>
                        <tr class="<?php echo $f->ativo ? '' : 'table-danger'; ?>">
                            <td><?php echo htmlspecialchars($f->id); ?></td>
                            <td><?php echo htmlspecialchars($f->nome); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($f->tipo)); ?></td>
                            <td><?php echo htmlspecialchars($f->login ?? 'N/A'); ?></td>
                            <td><?php echo $f->ativo ? 'Ativo' : 'Inativo'; ?></td>
                            <?php if ($pode_cadastrar_editar): ?>
                                <td class="text-center">
                                    <a href="/sgi_erp/admin/funcionarios/cadastro?id=<?php echo $f->id; ?>"
                                        class="btn btn-sm btn-info" title="Editar">
                                        <i class="fas fa-edit"></i> </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>