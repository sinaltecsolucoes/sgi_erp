<?php
// View/valores_pagamento_cadastro.php
$acoes = $dados['acoes'] ?? [];
$tipos_produto = $dados['tipos_produto'] ?? [];
$valor_existente = $dados['valor_existente'] ?? null;
$base_url = '/sgi_erp';
$is_editing = $valor_existente !== null;

$id = $valor_existente->id ?? '';
$valor = $valor_existente->valor_por_quilo ?? '';
$acao_id = $valor_existente->acao_id ?? '';
$produto_id = $valor_existente->tipo_produto_id ?? '';
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo $is_editing ? "Editar Valor de Pagamento" : "Novo Valor de Pagamento"; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/valores-pagamento">Valores de Pagamento</a></li>
        <li class="breadcrumb-item active"><?php echo $is_editing ? "Edição" : "Cadastro"; ?></li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Definir Valor por Quilo</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/admin/valores-pagamento/salvar" method="POST">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">

                        <div class="mb-3">
                            <label for="tipo_produto_id" class="form-label font-weight-bold">Tipo de Produto:</label>
                            <select id="tipo_produto_id" name="tipo_produto_id" class="form-select" required <?php echo $is_editing ? 'disabled' : ''; ?>>
                                <option value="">-- Selecione o Tipo --</option>
                                <?php foreach ($tipos_produto as $tp): ?>
                                    <option value="<?php echo $tp->id; ?>" <?php echo $produto_id == $tp->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($tp->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($is_editing): ?><input type="hidden" name="tipo_produto_id" value="<?php echo $produto_id; ?>"><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="acao_id" class="form-label font-weight-bold">Ação Realizada:</label>
                            <select id="acao_id" name="acao_id" class="form-select" required <?php echo $is_editing ? 'disabled' : ''; ?>>
                                <option value="">-- Selecione a Ação --</option>
                                <?php foreach ($acoes as $a): ?>
                                    <option value="<?php echo $a->id; ?>" <?php echo $acao_id == $a->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($a->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($is_editing): ?><input type="hidden" name="acao_id" value="<?php echo $acao_id; ?>"><?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label for="valor_por_quilo" class="form-label font-weight-bold">Valor por Quilo (R$):</label>
                            <input type="text"
                                class="form-control money-mask"
                                id="valor_por_quilo"
                                name="valor_por_quilo"
                                required
                                data-decimals="4"
                                value="<?php echo $is_editing ? htmlspecialchars(number_format($valor, 4, ',', '.')) : ''; ?>"
                                placeholder="Ex: 5,5000">
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary shadow">
                                <i class="fas fa-save me-2"></i> Salvar Valor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>