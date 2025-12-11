<?php
// View/valores_pagamento_lista.php
$valores = $dados['valores'] ?? [];
$base_url = '/sgi_erp';
$tipo_usuario_logado = $_SESSION['funcionario_tipo'] ?? 'convidado';
$pode_editar = Acl::check('ValoresPagamentoController@cadastro', $tipo_usuario_logado);
$pode_excluir = Acl::check('ValoresPagamentoController@excluir', $tipo_usuario_logado);
?>

<div class="pt-4">
    <h1 class="fw-bold mb-3">Gestão de Valores de Pagamento</h1>
    <p>Defina o valor pago por quilo para cada combinação de Ação e Tipo de Produto.</p>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Valores por Ação e Produto</h6>
            <?php if ($pode_editar): ?>
                <a href="<?php echo $base_url; ?>/admin/valores-pagamento/cadastro" class="btn btn-primary btn-sm shadow">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Novo Valor
                </a>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="datatablesSimple" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Ação</th>
                            <th>Tipo de Produto</th>
                            <th class="text-center">Valor por Kg (R$)</th>
                            <?php if ($pode_editar || $pode_excluir): ?>
                                <th class="text-center" style="width: 100px;">Ações</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($valores)): ?>
                            <tr>
                                <td colspan="<?php echo ($pode_editar || $pode_excluir) ? 4 : 3; ?>" class="text-center">
                                    Nenhum valor cadastrado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($valores as $v): ?>
                                <tr>
                                    <td class="align-middle"><?php echo htmlspecialchars($v->acao_nome); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($v->produto_nome); ?></td>
                                    <td class="text-center align-middle">
                                        R$ <?php echo number_format($v->valor_por_quilo, 4, ',', '.'); ?>
                                    </td>
                                    <?php if ($pode_editar || $pode_excluir): ?>
                                        <td class="text-center align-middle">
                                            <?php if ($pode_editar): ?>
                                                <a href="<?php echo $base_url; ?>/admin/valores-pagamento/cadastro?id=<?php echo $v->id; ?>"
                                                    class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($pode_excluir): ?>
                                                <button type="button"
                                                    class="btn btn-sm btn-danger excluir-valor"
                                                    data-id="<?php echo $v->id; ?>"
                                                    data-nome="<?php echo htmlspecialchars($v->acao_nome . ' - ' . $v->produto_nome); ?>"
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // === DATATABLES ===
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

        // === EXCLUSÃO COM SWEETALERT2 (AJAX SEGURO) ===
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
                        // === FETCH SEGURO AQUI ===
                        fetch('/sgi_erp/admin/valores-pagamento/excluir', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'id=' + id
                            })
                            .then(response => {
                                if (!response.ok) {
                                    return response.text().then(text => {
                                        console.error('Resposta do servidor (não OK):', text);
                                        throw new Error('Erro HTTP: ' + response.status);
                                    });
                                }
                                const contentType = response.headers.get('content-type');
                                if (!contentType || !contentType.includes('application/json')) {
                                    return response.text().then(text => {
                                        console.error('Resposta não é JSON:', text);
                                        throw new Error('Resposta inválida do servidor');
                                    });
                                }
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    Swal.fire('Excluído!', data.message, 'success').then(() => location.reload());
                                } else {
                                    Swal.fire('Erro!', data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Erro no fetch:', error);
                                Swal.fire('Erro!', 'Falha ao comunicar com o servidor. Verifique o console.', 'error');
                            });
                        // === FIM DO FETCH ===
                    }
                });
            });
        });
    });
</script>