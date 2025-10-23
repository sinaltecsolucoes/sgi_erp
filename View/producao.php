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
    <h1 class="mt-4">Lançamento de Produção (Individual)</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Registre a produtividade, rastreabilidade e tempo de produção.</li>
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
                            <label for="tipo_produto_id" class="form-label font-weight-bold">Tipo de Produto/Serviço:</label>
                            <select id="tipo_produto_id" name="tipo_produto_id" class="form-select" required>
                                <option value="">-- Selecione o Tipo de Produto/Serviço --</option>
                                <?php foreach ($tipos_produto as $produto): ?>
                                    <option value="<?php echo $produto->id; ?>">
                                        <?php echo htmlspecialchars($produto->nome); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Se for um serviço (sem Lote), marque essa opção no Cadastro de Tipos de Produto.</small>
                        </div>

                        <div class="mb-3" id="campo-lote">
                            <label for="lote_produto" class="form-label font-weight-bold">Lote do Produto:</label>
                            <input
                                type="text"
                                class="form-control"
                                id="lote_produto"
                                name="lote_produto"
                                placeholder="Ex: LOTE001">
                            <small class="form-text text-danger">Obrigatório para rastreabilidade de produtos.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="hora_inicio" class="form-label font-weight-bold">Hora Início:</label>
                                <input
                                    type="time"
                                    class="form-control"
                                    id="hora_inicio"
                                    name="hora_inicio"
                                    required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="hora_fim" class="form-label font-weight-bold">Hora Fim:</label>
                                <input
                                    type="time"
                                    class="form-control"
                                    id="hora_fim"
                                    name="hora_fim"
                                    required>
                            </div>
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

                                    // Se o produto não for encontrado, assumimos Lote Obrigatório (usa_lote = 1)
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