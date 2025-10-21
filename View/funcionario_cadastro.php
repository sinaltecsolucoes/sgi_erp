<?php
$funcionario = $dados['funcionario'] ?? null;
$is_editing = $funcionario !== null;

// Valores padrão para o formulário
$id = $funcionario->id ?? '';
$nome = $funcionario->nome ?? '';
$tipo = $funcionario->tipo ?? 'producao';
$login = $funcionario->login ?? '';
$ativo = $funcionario->ativo ?? true;

$tipos_validos = ['admin', 'apontador', 'producao', 'financeiro'];
?>

<div class="content">
    <h1><?php echo $is_editing ? "Editar Funcionário" : "Novo Funcionário"; ?></h1>
    <p>Preencha os dados do funcionário e configure seu login e perfil de acesso.</p>

    <div class="producao-form">
        <form action="/sgi_erp/admin/funcionarios/salvar" method="POST">

            <input type="hidden" name="id" value="<?php echo $id; ?>">

            <fieldset>
                <legend>Dados Pessoais</legend>
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required value="<?php echo htmlspecialchars($nome); ?>">
                </div>

                <div class="form-group">
                    <label for="tipo">Perfil / Tipo de Acesso:</label>
                    <select id="tipo" name="tipo" class="form-select" required>
                        <?php foreach ($tipos_validos as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $tipo === $t ? 'selected' : ''; ?>>
                                <?php echo ucfirst($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="ativo">Status:</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="ativo" name="ativo" value="1" <?php echo $ativo ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="ativo">Ativo no Sistema</label>
                    </div>
                </div>
            </fieldset>

            <?php if ($is_editing && !($funcionario->usuario_id)): ?>
                <div class="alert alert-warning">Este funcionário ainda não tem login associado. Preencha abaixo para criar.</div>
            <?php endif; ?>

            <fieldset style="margin-top: 30px;">
                <legend>Dados de Login e Senha</legend>

                <div class="form-group">
                    <label for="login">Login (Nome de Usuário):</label>
                    <input type="text" id="login" name="login" required value="<?php echo htmlspecialchars($login); ?>">
                </div>

                <div class="form-group">
                    <label for="senha">Nova Senha <?php echo $is_editing ? '(Deixe em branco para não alterar)' : ''; ?>:</label>
                    <input type="password" id="senha" name="senha" <?php echo $is_editing ? '' : 'required'; ?>>
                </div>
            </fieldset>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Salvar Cadastro
                </button>
            </div>
        </form>
    </div>
</div>