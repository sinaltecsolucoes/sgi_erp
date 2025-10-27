<?php
// View/acao_cadastro.php
$acao = $dados['acao'] ?? null;
$is_editing = $acao !== null;
$base_url = '/sgi_erp';

$id = $acao->id ?? '';
$nome = $acao->nome ?? '';
$ativo = $acao->ativo ?? true;
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo $is_editing ? "Editar Ação: " . htmlspecialchars($nome) : "Nova Ação"; ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Preencha os dados da Ação de Produção</h6>
        </div>

        <div class="card-body">
            <form action="<?php echo $base_url; ?>/admin/acoes/salvar" method="POST">

                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome da Ação (Ex: Descascar)</label>
                    <input type="text" class="form-control" id="nome" name="nome" required value="<?php echo htmlspecialchars($nome); ?>" placeholder="Nome da Ação">
                </div>

                <div class="mb-3 form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" <?php echo $ativo ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="ativo">Ativo</label>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow">
                        Salvar Ação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>