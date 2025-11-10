<?php
// View/producao_massa_abas.php

$base_url = '/sgi_erp';

// Dados recebidos do Controller
$equipes_do_apontador = $dados['equipes_do_apontador'] ?? [];
$acoes = $dados['acoes'] ?? [];
$tipos_produto = $dados['tipos_produto'] ?? [];

// O formulário de lançamento em massa original (producao_massa.php)
// será carregado DENTRO de cada aba.
$view_form_massa = ROOT_PATH . 'View' . DS . 'producao_massa.php';
?>

<div class="pt-4">
    <h1 class="mt-4">Lançamento de Produção em Massa por Equipe</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Selecione uma equipe para lançar a produção em massa.</li>
    </ol>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Selecione a Equipe para Lançamento</h6>
        </div>
        <div class="card-body">

            <?php if (empty($equipes_do_apontador)): ?>
                <div class="alert alert-warning text-center">
                    Nenhuma equipe ativa encontrada para lançamento. Crie uma em "Montagem de Equipes".
                </div>
            <?php else: ?>

                <ul class="nav nav-pills mb-3" id="producaoMassaTab" role="tablist">
                    <?php foreach ($equipes_do_apontador as $index => $equipe): ?>
                        <?php $active_class = ($index === 0) ? 'active' : ''; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $active_class; ?>"
                                id="equipe-<?php echo $equipe->id; ?>-tab"
                                data-bs-toggle="pill"
                                data-bs-target="#equipe-<?php echo $equipe->id; ?>-content"
                                type="button"
                                role="tab"
                                aria-controls="equipe-<?php echo $equipe->id; ?>-content"
                                aria-selected="<?php echo $active_class ? 'true' : 'false'; ?>">
                                <i class="fas fa-users me-1"></i>
                                <?php echo htmlspecialchars($equipe->nome); ?>
                                <span class="badge bg-primary ms-1"><?php echo count($equipe->membros); ?></span>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content" id="producaoMassaTabContent">
                    <?php foreach ($equipes_do_apontador as $index => $equipe): ?>
                        <?php $active_class = ($index === 0) ? 'show active' : ''; ?>

                        <div class="tab-pane fade <?php echo $active_class; ?>"
                            id="equipe-<?php echo $equipe->id; ?>-content"
                            role="tabpanel"
                            aria-labelledby="equipe-<?php echo $equipe->id; ?>-tab">

                            <?php
                            // Dados que serão injetados temporariamente no escopo de producao_massa.php
                            $dados_aba = [
                                'equipe' => $equipe,
                                'membros' => $equipe->membros,
                                'acoes' => $acoes,
                                'tipos_produto' => $tipos_produto,
                            ];

                            $dados_original = $dados;
                            $dados = $dados_aba;
                            require $view_form_massa;
                            $dados = $dados_original;
                            ?>

                        </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Inclui os alertas globais (sucesso/erro/confirm_action)
require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'alerts.php';
?>