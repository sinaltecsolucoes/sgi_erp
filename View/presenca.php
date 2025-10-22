<?php
// Certifica-se de que a variável $dados foi passada pelo Controller
$funcionarios = $dados['funcionarios'] ?? [];
$data_hoje = $dados['data'] ?? date('Y-m-d');
$base_url = '/sgi_erp';
?>

<div class="pt-4">
    <h1 class="mt-4">Registro de Presença</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Chamada do Dia: <?php echo date('d/m/Y', strtotime($data_hoje)); ?></li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Funcionários de Produção</h6>
                </div>

                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/presenca/salvar" method="POST">

                        <div class="mb-4">
                            <p class="text-muted">Marque os funcionários presentes. Os não marcados serão considerados ausentes.</p>

                            <?php if (empty($funcionarios)): ?>
                                <div class="alert alert-warning text-center">Nenhum funcionário de produção ativo para registro de presença.</div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($funcionarios as $funcionario): ?>
                                        <?php
                                        $id = $funcionario->id;
                                        $is_presente = (int)$funcionario->esta_presente === 1;
                                        ?>

                                        <label class="list-group-item d-flex justify-content-between align-items-center">

                                            <span class="<?php echo $is_presente ? 'text-success font-weight-bold' : 'text-danger'; ?>">
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($funcionario->nome); ?>
                                            </span>

                                            <div class="form-check form-switch">
                                                <input class="form-check-input"
                                                    type="checkbox"
                                                    role="switch"
                                                    name="presente[]"
                                                    value="<?php echo $id; ?>"
                                                    id="check-<?php echo $id; ?>"
                                                    <?php echo $is_presente ? 'checked' : ''; ?>>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow">
                                <i class="fas fa-save me-2"></i> Salvar Chamada do Dia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>