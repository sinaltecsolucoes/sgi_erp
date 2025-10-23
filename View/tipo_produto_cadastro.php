<?php
$tipo = $dados['tipo'] ?? null;
$base_url = '/sgi_erp';
$is_editing = $tipo !== null;

$id = $tipo->id ?? '';
$nome = $tipo->nome ?? '';
$usa_lote = (int)($tipo->usa_lote ?? 1) === 1; // Padrão: TRUE (1)
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo $is_editing ? "Editar Tipo: " . htmlspecialchars($nome) : "Novo Tipo de Produto"; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/tipos-produto">Tipos de Produto</a></li>
        <li class="breadcrumb-item active"><?php echo $is_editing ? "Edição" : "Cadastro"; ?></li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detalhes do Produto</h6>
                </div>

                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/admin/tipos-produto/salvar" method="POST">

                        <input type="hidden" name="id" value="<?php echo $id; ?>">

                        <div class="mb-3">
                            <label for="nome" class="form-label font-weight-bold">Nome do Tipo:</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nome"
                                name="nome"
                                required
                                value="<?php echo htmlspecialchars($nome); ?>"
                                placeholder="Ex: Camarão Pescado">
                        </div>

                        <div class="mb-4">
                            <label for="usa_lote" class="form-label font-weight-bold">Requer Rastreabilidade (Lote)?</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="usa_lote"
                                    name="usa_lote"
                                    value="1"
                                    <?php echo $usa_lote ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="usa_lote">
                                    Marque se este item é um produto físico. Desmarque se for um Serviço de Apoio (Diária, Limpeza, etc.).
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary shadow">
                                <i class="fas fa-save me-2"></i> Salvar Tipo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>