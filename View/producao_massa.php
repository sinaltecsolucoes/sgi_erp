<?php
// Conteúdo de View/producao_massa.php (Módulo de Formulário para Inclusão em Abas)

// Extrai os dados que foram injetados por producao_massa_abas.php
$dados = $dados ?? [];
$equipe = $dados['equipe'] ?? null;
$membros = $dados['membros'] ?? [];
$acoes = $dados['acoes'] ?? [];
$tipos_produto = $dados['tipos_produto'] ?? [];
$base_url = '/sgi_erp'; // Base URL

// Dados necessários para o JavaScript externo: ID e status de 'usa_lote'
$produtos_data_js = json_encode(
    array_map(fn($p) => ['id' => $p->id, 'usa_lote' => (int)($p->usa_lote ?? 1)], $tipos_produto)
);
$equipe_id_js = $equipe->id ?? '0';
?>

<div class="row justify-content-center">
    <div class="col-lg-12">

        <form action="<?php echo $base_url; ?>/producao/massa/salvar" method="POST">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-primary text-white">
                    <h6 class="m-0 font-weight-bold">Dados do Lançamento (Comum a todos)</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="acao_id-<?php echo $equipe_id_js; ?>" class="form-label font-weight-bold">Ação Realizada:</label>
                            <select id="acao_id-<?php echo $equipe_id_js; ?>" name="acao_id" class="form-select" required>
                                <option value="">-- Selecione a Ação --</option>
                                <?php foreach ($acoes as $acao): ?>
                                    <option value="<?php echo $acao->id; ?>">
                                        <?php echo htmlspecialchars($acao->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="tipo_produto_id-<?php echo $equipe_id_js; ?>" class="form-label font-weight-bold">Tipo de Produto/Serviço:</label>
                            <select id="tipo_produto_id-<?php echo $equipe_id_js; ?>" name="tipo_produto_id" class="form-select" required>
                                <option value="">-- Selecione o Tipo de Produto/Serviço --</option>
                                <?php foreach ($tipos_produto as $produto): ?>
                                    <option value="<?php echo $produto->id; ?>">
                                        <?php echo htmlspecialchars($produto->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3" id="campo-lote-<?php echo $equipe_id_js; ?>">
                            <label for="lote_produto-<?php echo $equipe_id_js; ?>" class="form-label font-weight-bold">Lote do Produto:</label>
                            <input
                                type="text"
                                class="form-control"
                                id="lote_produto-<?php echo $equipe_id_js; ?>"
                                name="lote_produto"
                                placeholder="Ex: LOTE001">
                            <small class="form-text text-danger">Obrigatório para produtos com rastreabilidade.</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="hora_inicio-<?php echo $equipe_id_js; ?>" class="form-label font-weight-bold">Hora Início:</label>
                            <input
                                type="time"
                                class="form-control"
                                id="hora_inicio-<?php echo $equipe_id_js; ?>"
                                name="hora_inicio"
                                required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="hora_fim-<?php echo $equipe_id_js; ?>" class="form-label font-weight-bold">Hora Fim:</label>
                            <input
                                type="time"
                                class="form-control"
                                id="hora_fim-<?php echo $equipe_id_js; ?>"
                                name="hora_fim"
                                required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-info text-white">
                    <h6 class="m-0 font-weight-bold">Quantidade Produzida por Membro</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($membros)): ?>
                        <div class="alert alert-info text-center">Esta equipe não possui membros.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Funcionário</th>
                                        <th>Quantidade (em Kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($membros as $membro): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-user me-2"></i>
                                                <?php echo htmlspecialchars($membro->nome); ?>
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    class="form-control form-control-sm"
                                                    name="quantidades[<?php echo $membro->id; ?>]"
                                                    step="0.01"
                                                    min="0"
                                                    placeholder="0.00">
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-success btn-lg shadow" <?php echo empty($membros) ? 'disabled' : ''; ?>>
                    <i class="fas fa-cubes me-2"></i> Registrar Lançamento em Massa
                </button>
            </div>
        </form>

        <script>
            // Agora a chamada só acontece depois que o DOM e os scripts externos já foram carregados
            document.addEventListener('DOMContentLoaded', function() {
                inicializarMassaLote(
                    '<?php echo $equipe_id_js; ?>',
                    <?php echo $produtos_data_js; ?>
                );
            });
        </script>
    </div>
</div>