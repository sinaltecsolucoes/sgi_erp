<?php
// View/funcionario_cadastro.php
$funcionario = $dados['funcionario'] ?? null;
$is_editing = $funcionario !== null;
$base_url = '/sgi_erp';

// Valores padrão para o formulário
$id = $funcionario->id ?? '';
$nome = $funcionario->nome ?? '';
$cpf = $funcionario->cpf ?? '';
$tipo = $funcionario->tipo ?? 'producao';
$login = $funcionario->login ?? '';
$ativo = $funcionario->ativo ?? true;

$tipos_validos = ['admin', 'apontador', 'porteiro', 'producao', 'financeiro'];
?>

<div class="pt-4">
    <h1 class="mt-4"><?php echo $is_editing ? "Editar Funcionário: " . htmlspecialchars($nome) : "Novo Funcionário"; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>/admin/funcionarios">Gestão de Funcionários</a></li>
        <li class="breadcrumb-item active"><?php echo $is_editing ? "Edição" : "Cadastro"; ?></li>
    </ol>

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Preencha os dados de <?php echo $is_editing ? "edição" : "cadastro"; ?></h6>
        </div>

        <div class="card-body">
            <form action="<?php echo $base_url; ?>/admin/funcionarios/salvar" method="POST">

                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="row">

                    <div class="col-md-6">
                        <fieldset class="p-3 border rounded mb-4">
                            <legend class="float-none w-auto px-3 h6 text-primary">Dados Pessoais</legend>

                            <div class="form-floating mb-3">
                                <input type="text"
                                    class="form-control"
                                    id="nome"
                                    name="nome"
                                    required
                                    value="<?php echo htmlspecialchars($nome); ?>"
                                    placeholder="Nome Completo"
                                    style="text-transform: uppercase;"> <label for="nome">Nome Completo</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text"
                                    class="form-control"
                                    id="cpf" name="cpf" required
                                    value="<?php echo htmlspecialchars($cpf); ?>"
                                    placeholder="CPF" maxlength="14">
                                <label for="cpf">CPF</label>
                            </div>

                            <div class="mb-3">
                                <label for="tipo" class="form-label">Perfil / Tipo de Acesso:</label>
                                <select id="tipo" name="tipo" class="form-select" required>
                                    <?php foreach ($tipos_validos as $t): ?>
                                        <option value="<?php echo $t; ?>" <?php echo $tipo === $t ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($t); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 form-check form-switch">
                                <input class="form-check-input"
                                    type="checkbox"
                                    role="switch"
                                    id="ativo"
                                    name="ativo"
                                    value="1" <?php echo $ativo ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativo">Ativo no Sistema</label>
                            </div>

                        </fieldset>
                    </div>

                    <div class="col-md-6">
                        <fieldset class="p-3 border rounded mb-4">
                            <legend class="float-none w-auto px-3 h6 text-primary">Dados de Login e Senha</legend>

                            <?php if ($is_editing && !($funcionario->usuario_id)): ?>
                                <div class="alert alert-warning small">Este funcionário ainda não tem login associado. Preencha abaixo para criar.</div>
                            <?php endif; ?>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="login" name="login" value="<?php echo htmlspecialchars($login); ?>" placeholder="Login (Nome de Usuário)">
                                <label for="login">Login (Nome de Usuário)</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="senha" name="senha" <?php echo $is_editing ? '' : ''; ?> placeholder="Nova Senha">
                                <label for="senha">Nova Senha <?php echo $is_editing ? '(Deixe em branco para não alterar)' : ''; ?></label>
                            </div>

                        </fieldset>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary btn-lg shadow">
                        <i class="fas fa-save me-2"></i> Salvar Cadastro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Biblioteca jquery.mask.min.js já está carregada no template main.php
        $('#cpf').mask('000.000.000-00', {
            reverse: true
        });
    });
</script>