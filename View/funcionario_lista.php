<?php
// View/funcionario_lista.php
$funcionarios = $dados['funcionarios'] ?? [];
$tipo_usuario_logado = $_SESSION['funcionario_tipo'] ?? 'convidado';

// ACL: FuncionarioController@cadastro (Permite acessar o formulário de cadastro/edição)
$pode_cadastrar_editar = Acl::check('FuncionarioController@cadastro', $tipo_usuario_logado);
$base_url = '/sgi_erp'; // Definição da base URL
?>

<div class="pt-4">
    <h1 class="fw-bold mb-3">Gestão de Funcionários</h1>
    <p>Cadastros e perfis de acesso dos usuários do SGI ERP.</p>

    <div class="card shadow mb-4">

        <div class="card-header py-3 d-flex justify-content-between align-items-center">

            <h6 class="m-0 font-weight-bold text-primary">Lista de Funcionários Ativos e Inativos</h6>

            <?php if ($pode_cadastrar_editar): ?>
                <a href="<?php echo $base_url; ?>/admin/funcionarios/cadastro" class="btn btn-primary btn-sm shadow">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Novo Funcionário
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center align-middle">ID</th>
                            <th class="text-center align-middle">Nome</th>
                            <th class="text-center align-middle">CPF</th>
                            <th class="text-center align-middle">Tipo</th>
                            <th class="text-center align-middle">Login</th>
                            <th class="text-center align-middle">Status</th>
                            <?php if ($pode_cadastrar_editar): ?>
                                <th class="text-center align-middle" style="width: 100px;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($funcionarios)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Nenhum funcionário cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($funcionarios as $f):
                                // Definir $is_ativo no escopo do loop
                                $is_ativo = (int)($f->ativo ?? 0);
                            ?>
                                <tr class="<?php echo $is_ativo ? '' : 'table-danger'; ?>">
                                    <td class="text-center align-middle"><?php echo htmlspecialchars($f->id); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($f->nome); ?></td>
                                    <td class="align-middle"><?php echo $f->cpf ? htmlspecialchars(substr($f->cpf, 0, 3) . '.' . substr($f->cpf, 3, 3) . '.' . substr($f->cpf, 6, 3) . '-' . substr($f->cpf, 9, 2)) : 'N/A'; ?></td>
                                    <td class="align-middle"><?php echo ucfirst(htmlspecialchars($f->tipo)); ?></td>
                                    <td class="text-center align-middle"><?php echo htmlspecialchars($f->login ?? 'N/A'); ?></td>

                                    <td class="text-center align-middle">
                                        <span class="badge <?php echo $is_ativo ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $is_ativo ? 'Ativo' : 'Inativo'; ?>
                                        </span>
                                    </td>

                                    <?php if ($pode_cadastrar_editar): ?>
                                        <td class="text-center align-middle">
                                            <a href="<?php echo $base_url; ?>/admin/funcionarios/cadastro?id=<?php echo $f->id; ?>"
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

    <script>
        document.addEventListener('DOMContentLoaded', event => {
            const datatablesSimple = document.getElementById('datatablesSimple');

            if (datatablesSimple) {
                // Configurações de tradução injetadas diretamente na View
                const config = {
                    labels: {
                        placeholder: "Pesquisar...",
                        perPage: "Registros por página",
                        noRows: "Nenhum resultado encontrado",
                        info: "Mostrando de {start} a {end} de {rows} entradas",
                        search: "Buscar:",
                        paginate: {
                            first: "Primeira",
                            last: "Última",
                            next: "Próxima",
                            prev: "Anterior"
                        },
                    },
                    // Desativar ordenação na coluna 'Ações' (índice 5)
                    columns: [{
                            select: 0,
                            sortable: true
                        },
                        {
                            select: 1,
                            sortable: true
                        },
                        {
                            select: 2,
                            sortable: true
                        },
                        {
                            select: 3,
                            sortable: true
                        },
                        {
                            select: 4,
                            sortable: true
                        },
                        {
                            select: 5,
                            sortable: true
                        },
                        {
                            select: 6,
                            sortable: false
                        }
                    ]
                };

                // Inicializa a tabela APENAS uma vez com a nossa configuração
                new simpleDatatables.DataTable(datatablesSimple, config);
            }
        });
    </script>
</div>