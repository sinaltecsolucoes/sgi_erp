<?php
// Extrai os dados passados pelo Controller
$dados = $dados ?? [];
$equipe = $dados['equipe'] ?? null;
$membros = $dados['membros'] ?? [];
$acoes = $dados['acoes'] ?? [];
$tipos_produto = $dados['tipos_produto'] ?? [];
$base_url = '/sgi_erp';
?>

<div class="pt-4">
    <h1 class="mt-4">Lançamento de Produção em Massa</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Lançamento otimizado para múltiplos funcionários de uma única vez.</li>
    </ol>

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow mb-4">

                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Equipe Atual: <?php echo htmlspecialchars($equipe->nome ?? 'N/A'); ?>
                    </h6>
                    <p class="small text-danger mb-0 mt-1">
                        (O lançamento em massa aplica a mesma Ação, Produto, Lote e Horário para todos os registros).
                    </p>
                </div>

                <div class="card-body">
                    <form action="<?php echo $base_url; ?>/producao/massa/salvar" method="POST">

                        <div class="row mb-4">

                            <div class="col-md-6 mb-3">
                                <label for="acao_id" class="form-label font-weight-bold">Ação Global:</label>
                                <select id="acao_id" name="acao_id" class="form-select" required>
                                    <option value="">-- Selecione a Ação --</option>
                                    <?php foreach ($acoes as $acao): ?>
                                        <option value="<?php echo $acao->id; ?>">
                                            <?php echo htmlspecialchars($acao->nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="tipo_produto_id" class="form-label font-weight-bold">Tipo de Produto Global:</label>
                                <select id="tipo_produto_id" name="tipo_produto_id" class="form-select" required>
                                    <option value="">-- Selecione o Tipo --</option>
                                    <?php foreach ($tipos_produto as $produto): ?>
                                        <option value="<?php echo $produto->id; ?>">
                                            <?php echo htmlspecialchars($produto->nome); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-12">
                                <div class="mb-3" id="campo-lote">
                                    <label for="lote_produto" class="form-label font-weight-bold">Lote do Produto:</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        id="lote_produto"
                                        name="lote_produto"
                                        placeholder="Ex: LOTE001">
                                    <small class="form-text text-danger">Obrigatório apenas para produtos rastreáveis.</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hora_inicio" class="form-label font-weight-bold">Hora Início (Equipe):</label>
                                <input
                                    type="time"
                                    class="form-control"
                                    id="hora_inicio"
                                    name="hora_inicio"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="hora_fim" class="form-label font-weight-bold">Hora Fim (Equipe):</label>
                                <input
                                    type="time"
                                    class="form-control"
                                    id="hora_fim"
                                    name="hora_fim"
                                    required>
                            </div>
                        </div>

                        <hr class="mt-0">
                        <h5 class="text-primary mb-3">Quantidades por Funcionário (Individual):</h5>

                        <?php if (empty($membros)): ?>
                            <div class="alert alert-warning text-center">A equipe atual não possui membros. Adicione-os na tela de Montagem de Equipes.</div>
                        <?php else: ?>

                            <div class="list-group">
                                <?php foreach ($membros as $membro): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">

                                        <label for="qtd-<?php echo $membro->id; ?>" class="text-gray-800 me-3" style="flex: 1;">
                                            <i class="fas fa-user-tag me-2"></i>
                                            <?php echo htmlspecialchars($membro->nome); ?>
                                        </label>

                                        <div style="flex: 0 0 150px;">
                                            <input
                                                type="number"
                                                class="form-control text-end form-control-sm"
                                                id="qtd-<?php echo $membro->id; ?>"
                                                name="quantidades[<?php echo $membro->id; ?>]"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg shadow" <?php echo empty($membros) ? 'disabled' : ''; ?>>
                                <i class="fas fa-save me-2"></i> Registrar Lançamento em Massa
                            </button>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const selectProduto = document.getElementById('tipo_produto_id');
                                const campoLote = document.getElementById('campo-lote');
                                const inputLote = document.getElementById('lote_produto');

                                // Dados sobre a obrigatoriedade de Lote (vindo do PHP)
                                const produtosData = <?php echo json_encode(
                                                            array_map(fn($p) => ['id' => $p->id, 'usa_lote' => (int)($p->usa_lote ?? 1)], $tipos_produto)
                                                        ); ?>;

                                function toggleLote() {
                                    const selectedId = parseInt(selectProduto.value);
                                    const produto = produtosData.find(p => p.id === selectedId);

                                    const loteObrigatorio = produto ? (produto.usa_lote === 1) : true;

                                    if (loteObrigatorio) {
                                        campoLote.style.display = 'block';
                                        inputLote.setAttribute('required', 'required');
                                    } else {
                                        campoLote.style.display = 'none';
                                        inputLote.removeAttribute('required');
                                        inputLote.value = ''; // Limpa o valor para não enviar lote vazio
                                    }
                                }

                                selectProduto.addEventListener('change', toggleLote);
                                // Inicializa a função na carga da página (útil para edição ou recarga)
                                if (selectProduto.value) {
                                    toggleLote();
                                } else {
                                    // Esconde por padrão se nenhum produto for selecionado
                                    campoLote.style.display = 'none';
                                }
                            });
                        </script>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>