<?php
// Extrai os dados passados pelo Controller
$dados = $dados ?? [];
$equipe = $dados['equipe'] ?? null;
$membros = $dados['membros'] ?? [];
$acoes = $dados['acoes'] ?? [];
$tipos_produto = $dados['tipos_produto'] ?? [];
$base_url = '/sgi_erp'; // Base URL
?>

<div class="pt-4">
    <h1 class="mt-4">Lançamento de Produção</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Registre a produtividade por funcionário e tipo de produto.</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-6">

            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Lançamento para a equipe: <?php echo htmlspecialchars($equipe->nome ?? 'N/A'); ?>
                    </h6>
                    <p class="small text-danger mb-0 mt-1">
                        (Se a equipe estiver incorreta, volte ao dashboard e corrija em "Montar Equipes").
                    </p>
                </div>

                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/producao/salvar" method="POST">

                        <div class="mb-3">
                            <label for="funcionario_id" class="form-label font-weight-bold">Funcionário Responsável:</label>
                            <select id="funcionario_id" name="funcionario_id" class="form-select" required>
                                <option value="">-- Selecione o Membro da Equipe --</option>
                                <?php foreach ($membros as $membro): ?>
                                    <option value="<?php echo $membro->id; ?>">
                                        <?php echo htmlspecialchars($membro->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="acao_id" class="form-label font-weight-bold">Ação Realizada:</label>
                            <select id="acao_id" name="acao_id" class="form-select" required>
                                <option value="">-- Selecione a Ação --</option>
                                <?php foreach ($acoes as $acao): ?>
                                    <option value="<?php echo $acao->id; ?>">
                                        <?php echo htmlspecialchars($acao->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tipo_produto_id" class="form-label font-weight-bold">Tipo de Produto:</label>
                            <select id="tipo_produto_id" name="tipo_produto_id" class="form-select" required>
                                <option value="">-- Selecione o Tipo de Produto --</option>
                                <?php foreach ($tipos_produto as $produto): ?>
                                    <option value="<?php echo $produto->id; ?>">
                                        <?php echo htmlspecialchars($produto->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="quantidade_kg" class="form-label font-weight-bold">Quantidade (em Quilos):</label>
                            <input
                                type="number"
                                class="form-control"
                                id="quantidade_kg"
                                name="quantidade_kg"
                                step="0.01"
                                min="0.1"
                                required
                                placeholder="Ex: 5.50">
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary btn-lg shadow">
                                <i class="fas fa-save me-2"></i> Registrar Produção
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>