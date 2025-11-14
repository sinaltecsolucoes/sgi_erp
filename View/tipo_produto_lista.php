<?php
// View/tipo_produto_lista.php
$tipos = $dados['tipos'] ?? [];
$base_url = '/sgi_erp';
$tipo_usuario_logado = $_SESSION['funcionario_tipo'] ?? 'convidado';
$pode_editar = Acl::check('TipoProdutoController@cadastro', $tipo_usuario_logado);
$pode_excluir = Acl::check('TipoProdutoController@excluir', $tipo_usuario_logado);
?>

<div class="pt-4">
    <h1 class="fw-bold mb-3">Cadastro de Tipos de Produto</h1>
    <p>Gerencie os tipos de produto e a obrigatoriedade de rastreabilidade por lote.</p>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Tipos de Produto Cadastrados</h6>
            <?php if ($pode_editar): ?>
                <a href="<?php echo $base_url; ?>/admin/tipos-produto/cadastro" class="btn btn-primary btn-sm shadow">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Novo Tipo
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th class="text-center">ID</th>
                            <th>Nome</th>
                            <th class="text-center">Usa Lote?</th>
                            <?php if ($pode_editar || $pode_excluir): ?>
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
                                    <td class="text-center align-middle"><?php echo htmlspecialchars($t->id); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($t->nome); ?></td>
                                    <td class="text-center align-middle">
                                        <span class="badge <?php echo $usa_lote ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $usa_lote ? 'Sim' : 'Não'; ?>
                                        </span>
                                    </td>
                                    <?php if ($pode_editar || $pode_excluir): ?>
                                        <td class="text-center align-middle">
                                            <?php if ($pode_editar): ?>
                                                <a href="<?php echo $base_url; ?>/admin/tipos-produto/cadastro?id=<?php echo $t->id; ?>"
                                                    class="btn btn-sm btn-info" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($pode_excluir): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-danger excluir-tipo"
                                                    data-id="<?php echo $t->id; ?>"
                                                    data-nome="<?php echo htmlspecialchars($t->nome); ?>"
                                                    title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
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

<!-- JS para DataTables e Exclusão -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('datatablesSimple');
        if (table) {
            new simpleDatatables.DataTable(table, {
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
                    }
                },
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
                        sortable: false
                    }
                ]
            });
        }

        // Exclusão com SweetAlert2
        document.querySelectorAll('.excluir-valor').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nome = this.dataset.nome;

                Swal.fire({
                    title: 'Excluir valor?',
                    text: `O valor para **${nome}** será removido permanentemente.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Sim, excluir!',
                    cancelButtonText: 'Cancelar'
                }).then(result => {
                    if (result.isConfirmed) {
                        fetch('/sgi_erp/admin/valores-pagamento/excluir', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'id=' + id
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Excluído!', data.message, 'success')
                                        .then(() => location.reload());
                                } else {
                                    // MENSAGEM DO SERVIDOR AQUI!
                                    Swal.fire('Erro!', data.message, 'error');
                                }
                            })
                            .catch(() => {
                                Swal.fire('Erro!', 'Falha na comunicação com o servidor.', 'error');
                            });
                    }
                });
            });
        });
    });
</script>