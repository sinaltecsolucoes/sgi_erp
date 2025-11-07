<!-- View/producao_editar_massa.php -->
<div class="container-fluid px-4">
    <h1 class="mt-4 text-primary"><?= $title ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <form method="GET" action="/sgi_erp/producao/editar-massa">
                        <div class="input-group">
                            <input type="date" name="data" class="form-control" value="<?= $data_selecionada ?>" required>
                            <button class="btn btn-light">Ir</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-7 text-end">
                    <a href="/sgi_erp/producao/massa" class="btn btn-light">Voltar para Lançamento</a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <form action="/sgi_erp/producao/salvar-massa-edit" method="POST">
                <input type="hidden" name="data" value="<?= $data_selecionada ?>">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Ação</label>
                        <select name="acao_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($acoes as $a): ?>
                                <option value="<?= $a->id ?>" <?= $a->id == $acao_global ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a->nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Produto</label>
                        <select name="tipo_produto_id" class="form-select" required>
                            <option value="">Selecione</option>
                            <?php foreach ($tipos_produto as $tp): ?>
                                <option value="<?= $tp->id ?>" <?= $tp->id == $produto_global ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tp->nome) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Lote</label>
                        <input type="text" name="lote_produto" class="form-control" value="<?= htmlspecialchars($lote_global) ?>">
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>Funcionário</th>
                            <th width="200">Quantidade (Kg)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($membros as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m->nome) ?></td>
                                <td>
                                    <input type="text" name="quantidades[<?= $m->id ?>]"
                                        class="form-control text-end kg-input"
                                        value="<?= number_format($preenchidos[$m->id] ?? 0, 3, ',', '.') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> SALVAR TODAS AS ALTERAÇÕES
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Máscara brasileira
    $('.kg-input').on('input', function() {
        let v = this.value.replace(/\D/g, '');
        v = v.replace(/(\d)(\d{3})$/, '$1,$2');
        v = v.replace(/(?=(\d{3})+(\D))\B/g, '.');
        this.value = v;
    });
</script>