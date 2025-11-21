<div class="container-fluid px-4">
    <h1 class="mt-4 text-success fw-bold">
        Lançamento de Diárias e Serviços Extras
    </h1>

    <form action="/sgi_erp/servicos-extras/salvar" method="POST" class="mt-4">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0 font-weight-bold text-primary">Informações do Lançamento</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Ação Realizada</label>
                        <select name="acao_id" class="form-select" required>
                            <option value="">:: Selecione a Ação ::</option>
                            <?php foreach ($acoes as $acao): ?>
                                <option value="<?= $acao->id ?>"><?= htmlspecialchars($acao->nome) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Tipo de Serviço/Diária</label>
                        <select name="descricao" class="form-select" required>
                            <option value="">:: Selecione o Serviço ::</option>
                            <?php foreach ($servicos as $s): ?>
                                <option value="<?= htmlspecialchars($s->nome) ?>">
                                    <?= htmlspecialchars($s->nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="m-0 font-weight-bold text-primary">Valor por Funcionário (R$)</h5>
            </div>
            <div class="card-body p-0">
                <div style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-bordered table-hover mb-0">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th width="60%">Funcionário</th>
                                <th width="40%" class="text-center">Valor (R$)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($funcionarios as $f): ?>
                                <tr>
                                    <td class="align-middle">
                                        <strong><?= htmlspecialchars($f['nome']) ?></strong>
                                    </td>
                                    <td>
                                        <input type="text"
                                            name="valores[<?= $f['id'] ?>]"
                                            class="form-control text-end money-mask"
                                            placeholder="0,00">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($funcionarios)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-info-circle"></i> Nenhum funcionário presente hoje.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-end">
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="fas fa-save me-2"></i> Lançar Valores
            </button>
        </div>
    </form>
</div>

<script>
    // Máscara brasileira de dinheiro (99.999,99)
    document.querySelectorAll('.money-mask').forEach(el => {
        el.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            v = (v / 100).toFixed(2) + '';
            v = v.replace(".", ",");
            v = v.replace(/(\d)(?=(\d{3})+,)/g, "$1.");
            e.target.value = v === '0,00' ? '' : v;
        });

    });
</script>