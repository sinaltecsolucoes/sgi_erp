<?php
// Extrai os dados passados pelo Controller
$perfis_disponiveis = $dados['perfis_disponiveis'] ?? [];
$catalogo_acoes = $dados['catalogo_acoes'] ?? [];
$permissoes_atuais = $dados['permissoes_atuais'] ?? [];

// Adicionamos o admin para exibir a coluna, mas ele não pode ser alterado
$perfis_com_admin = array_merge(['admin'], $perfis_disponiveis);
?>

<div class="pt-4">

    <h1 class="fw-bold mb-3">Gerenciar Permissões de Ação (ACL)</h1>
    <p>Defina quais perfis podem executar cada ação (Visualizar, Salvar, Excluir) no sistema.</p>

    <div class="alert alert-info" role="alert">
        <strong>Aviso:</strong> O perfil **'admin'** tem acesso total e irrestrito, garantido por código. Suas permissões são apenas para visualização.
    </div>

    <form action="/sgi_erp/permissoes/salvar" method="POST">

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50%;">Ação (Controller@Método)</th>
                        <?php foreach ($perfis_com_admin as $perfil): ?>
                            <th class="text-center"><?php echo ucfirst(htmlspecialchars($perfil)); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($catalogo_acoes as $acao_chave => $descricao):
                        // Ex: $acao_chave = 'FuncionarioController@index'
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($descricao); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($acao_chave); ?></small>
                            </td>

                            <?php foreach ($perfis_com_admin as $perfil): ?>
                                <td class="text-center">
                                    <?php
                                    $is_admin_column = ($perfil === 'admin');

                                    // Verifica se a permissão está marcada no banco para este perfil
                                    $is_checked = $is_admin_column; // Admin sempre TRUE
                                    if (!$is_admin_column) {
                                        // Verifica o estado atual do banco para outros perfis
                                        $is_checked = $permissoes_atuais[$perfil][$acao_chave] ?? false;
                                    }

                                    // Apenas os perfis não-admin podem ter o checkbox habilitado
                                    $disabled = $is_admin_column ? 'disabled' : '';

                                    // O name é importante: permissoes[perfil][]
                                    $input_name = "permissoes[{$perfil}][]";
                                    ?>

                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                            id="switch-<?php echo htmlspecialchars($perfil . '-' . $acao_chave); ?>"
                                            name="<?php echo $input_name; ?>"
                                            value="<?php echo htmlspecialchars($acao_chave); ?>"
                                            <?php echo $is_checked ? 'checked' : ''; ?>
                                            <?php echo $disabled; ?>>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Salvar Permissões</button>
    </form>

</div>