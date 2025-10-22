<?php
// View/template/sidebar.php
$tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';
$base_url = '/sgi_erp';

// Lógica para determinar a rota atual
$current_url = strtok($_SERVER['REQUEST_URI'], '?');
// Garante que /sgi_erp/dashboard seja apenas /dashboard
$current_route = str_replace($base_url, '', $current_url);

// Função auxiliar para checar se a rota atual corresponde a um link
function is_active($route, $current_route)
{
    // Retorna 'active' se a rota atual começar com a rota do link (ex: /admin/funcionarios/cadastro é ativo em /admin/funcionarios)
    return (strpos($current_route, $route) === 0) ? 'active' : '';
}
?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">
                <div class="sb-sidenav-menu-heading">CORE</div>

                <?php $route = '/dashboard';
                $is_active = is_active($route, $current_route); ?>
                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-fw fa-tachometer-alt"></i></div>
                    Página Inicial
                </a>

                <div class="sb-sidenav-menu-heading">MÓDULOS OPERACIONAIS</div>

                <?php if (Acl::check('AppController@index', $tipo_usuario)): // Checagem genérica para bloco operacional 
                ?>

                    <?php $route = '/presenca';
                    $is_active = is_active($route, $current_route); ?>
                    <?php if (Acl::check('PresencaController@index', $tipo_usuario)): ?>
                        <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-calendar-check"></i></div>
                            Chamada de Presença
                        </a>
                    <?php endif; ?>

                    <?php $route = '/equipes';
                    $is_active = is_active($route, $current_route); ?>
                    <?php if (Acl::check('EquipeController@index', $tipo_usuario)): ?>
                        <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                            Montar Equipes
                        </a>
                    <?php endif; ?>

                    <?php $route = '/producao';
                    $is_active = is_active($route, $current_route); ?>
                    <?php if (Acl::check('ProducaoController@index', $tipo_usuario)): ?>
                        <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-balance-scale"></i></div>
                            Lançar Produção (Individual)
                        </a>
                    <?php endif; ?>

                    <?php $route = '/producao/massa';
                    $is_active = is_active($route, $current_route); ?>
                    <?php if (Acl::check('ProducaoController@massa', $tipo_usuario)): ?>
                        <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                            <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                            Lançar Prod. em Massa
                        </a>
                    <?php endif; ?>

                <?php endif; ?>

                <div class="sb-sidenav-menu-heading">ADMINISTRAÇÃO / FINANCEIRO</div>

                <?php $route = '/relatorios';
                $is_active = is_active($route, $current_route); ?>
                <?php if (Acl::check('RelatorioController@pagamentos', $tipo_usuario)): ?>
                    <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>
                        Relatórios / Pagamentos
                    </a>
                <?php endif; ?>

                <?php $route = '/admin/valores-pagamento';
                $is_active = is_active($route, $current_route); ?>
                <?php if (Acl::check('ValoresPagamentoController@index', $tipo_usuario)): ?>
                    <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-money-bill-wave"></i></div>
                        Gerenciar Valores Pagto
                    </a>
                <?php endif; ?>


                <?php $route = '/admin/funcionarios';
                $is_active = is_active($route, $current_route); ?>
                <?php if (Acl::check('FuncionarioController@index', $tipo_usuario)): ?>
                    <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-user-friends"></i></div>
                        Gerenciar Funcionários
                    </a>
                <?php endif; ?>

                <?php $route = '/permissoes/gestao';
                $is_active = is_active($route, $current_route); ?>
                <?php if (Acl::check('PermissaoController@index', $tipo_usuario)): ?>
                    <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                        <div class="sb-nav-link-icon"><i class="fas fa-shield-alt"></i></div>
                        Gestão de Permissões (ACL)
                    </a>
                <?php endif; ?>

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logado como:</div>
            <?php echo ucfirst(htmlspecialchars($tipo_usuario)); ?>
        </div>
    </nav>
</div>