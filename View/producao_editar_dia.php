<div class="container-fluid px-4">
    <h1 class="mt-4 text-primary"><?= $title ?></h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <form method="GET" action="/sgi_erp/producao/editar-dia" class="d-flex align-items-center gap-3">
                <input type="date" name="data" class="form-control w-auto" value="<?= $data_selecionada ?>" required>
                <button class="btn btn-light">Buscar</button>
                <div class="ms-auto">
                    <a href="/sgi_erp/producao" class="btn btn-light">Voltar</a>
                </div>
            </form>
        </div>

        <div class="card-body">
            <?php if (empty($lancamentos)): ?>
                <div class="alert alert-info text-center">
                    Nenhum lançamento encontrado para <strong><?= date('d/m/Y', strtotime($data_selecionada)) ?></strong>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabela-producao">
                        <thead class="table-primary">
                            <tr>
                                <th class="text-center align-middle">Funcionário</th>
                                <th class="text-center align-middle">Ação</th>
                                <th class="text-center align-middle">Produto</th>
                                <th class="text-center align-middle">Kg</th>
                                <th class="text-center align-middle">Início</th>
                                <th class="text-center align-middle">Fim</th>
                                <th class="text-center align-middle">Total Dia</th>
                                <th class="text-center align-middle">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $totais = [];
                            foreach ($lancamentos as $l):
                                $func = $l['funcionario_nome'];
                                if (!isset($totais[$func])) $totais[$func] = 0;
                                $totais[$func] += $l['quantidade_kg'];
                            ?>
                                <tr data-id="<?= $l['id'] ?>"
                                    data-funcionario="<?= htmlspecialchars($l['funcionario_nome']) ?>">

                                    <td class="fw-bold align-middle"><?= htmlspecialchars($l['funcionario_nome']) ?></td>

                                    <td class="text-center align-middle">
                                        <span class="view-mode acao-view"><?= htmlspecialchars($l['acao_nome']) ?></span>
                                        <!-- SELECT DE AÇÃO -->
                                        <select class="form-select form-select-sm edit-mode d-none acao-select">
                                            <?php foreach ($acoes as $a): ?>
                                                <option value="<?= $a->id ?>"
                                                    <?= ((string) ($l['acao_id'] ?? '')) === ((string) $a->id) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($a->nome) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td class="align-middle">
                                        <span class="view-mode produto-view"><?= htmlspecialchars($l['produto_nome']) ?></span>
                                        <select class="form-select form-select-sm edit-mode d-none produto-select">
                                            <?php foreach ($tipos_produto as $tp): ?>
                                                <option value="<?= $tp->id ?>"
                                                    <?= ((string) ($l['tipo_produto_id'] ?? '')) === ((string) $tp->id) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($tp->nome) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td class="text-center align-middle">
                                        <span class="view-mode kg-view"><?= number_format($l['quantidade_kg'], 3, ',', '.') ?></span>
                                        <input type="text" class="form-control form-control-sm edit-mode d-none kg-input text-end"
                                            value="<?= number_format($l['quantidade_kg'], 3, ',', '.') ?>">
                                    </td>

                                    <td class="text-center align-middle">
                                        <span class="view-mode inicio-view"><?= $l['hora_inicio'] ?: '--:--' ?></span>
                                        <input type="time" class="form-control form-control-sm edit-mode d-none hora-inicio"
                                            value="<?= $l['hora_inicio'] ?>">
                                    </td>

                                    <td class="text-center align-middle">
                                        <span class="view-mode fim-view"><?= $l['hora_fim'] ?: '--:--' ?></span>
                                        <input type="time" class="form-control form-control-sm edit-mode d-none hora-fim"
                                            value="<?= $l['hora_fim'] ?>">
                                    </td>

                                    <td class="text-center align-middle fw-bold text-primary total-dia"
                                        data-funcionario="<?= htmlspecialchars($l['funcionario_nome']) ?>">
                                        <?= number_format($totais[$l['funcionario_nome']], 3, ',', '.') ?> kg
                                    </td>

                                    <td class="text-center align-middle">
                                        <button class="btn btn-success btn-sm btn-editar">
                                            Editar
                                        </button>
                                        <button class="btn btn-secondary btn-sm btn-cancelar d-none ms-1">
                                            Cancelar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <script>
                // Passa os totais reais do PHP pro JS de forma segura
                const totaisIniciais = <?= json_encode($totais ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
            </script>
        </div>
    </div>
</div>