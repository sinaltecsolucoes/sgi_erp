<?php
$perfil = $dados['perfil'] ?? null;
?>
<div class="row">
    <div class="col-lg-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Informações da Conta e Foto</h6>
            </div>
            <div class="card-body">

                <div class="row">
                    <div class="col-md-4 text-center">
                        <img src="<?php echo $base_url; ?>/public/img/undraw_profile_2.svg" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px;">
                        <h4 class="text-gray-800"><?php echo htmlspecialchars($perfil->nome ?? 'Usuário'); ?></h4>
                        <p class="text-muted"><?php echo ucfirst(htmlspecialchars($perfil->tipo ?? 'N/A')); ?></p>

                        <a href="#" class="btn btn-sm btn-primary">Alterar Foto (Futuro)</a>
                    </div>

                    <div class="col-md-8">
                        <form action="<?php echo $base_url; ?>/meu-perfil/salvar-senha" method="POST">
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Nome:</label>
                                <p class="form-control-static"><?php echo htmlspecialchars($perfil->nome); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Login:</label>
                                <p class="form-control-static"><?php echo htmlspecialchars($perfil->login); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label font-weight-bold">Perfil de Acesso:</label>
                                <p class="form-control-static text-primary"><?php echo ucfirst(htmlspecialchars($perfil->tipo)); ?></p>
                            </div>

                            <hr>
                            <h6 class="text-primary">Alterar Senha</h6>
                            <div class="mb-3">
                                <label for="nova_senha" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control" id="nova_senha" name="nova_senha">
                            </div>
                            <button type="submit" class="btn btn-success">Salvar Alterações</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>