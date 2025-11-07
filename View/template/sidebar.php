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
    // O SB Admin usa a classe 'active' também nos colapsos (nav-link) para mantê-los abertos se um sublink estiver ativo.
    return (strpos($current_route, $route) === 0) ? 'active' : '';
}

// Função auxiliar para checar se algum item do grupo está ativo (para manter o menu pai aberto)
function is_group_active($routes_array, $current_route)
{
    foreach ($routes_array as $route) {
        if (strpos($current_route, $route) === 0) {
            return 'show'; // Retorna a classe que expande o menu (Bootstrap's 'show')
        }
    }
    return '';
}

?>

<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <div class="sb-sidenav-menu-heading">HOME</div>

                <?php $route = '/dashboard';
                $is_active = is_active($route, $current_route); ?>
                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">
                    <div class="sb-nav-link-icon"><i class="fas fa-fw fa-tachometer-alt"></i></div>
                    Página Inicial
                </a>

                <?php
                // Rotas do grupo Operacional para checagem de "ativo"
                $op_routes = ['/presenca', '/equipes', '/producao'];
                $op_is_active = is_group_active($op_routes, $current_route);

                // Checa se pelo menos uma permissão do grupo existe
                if (Acl::check('PresencaController@index', $tipo_usuario) || Acl::check('EquipeController@index', $tipo_usuario)):
                ?>
                    <div class="sb-sidenav-menu-heading">MÓDULOS OPERACIONAIS</div>

                    <a class="nav-link collapsed <?php echo $op_is_active; ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseOperacional" aria-expanded="false" aria-controls="collapseOperacional">
                        <div class="sb-nav-link-icon"><i class="fas fa-wrench"></i></div>
                        Operacional
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>

                    <div class="collapse <?php echo $op_is_active; ?>" id="collapseOperacional" aria-labelledby="headingOperacional" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">

                            <?php $route = '/presenca';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('PresencaController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Chamada de Presença</a>
                            <?php endif; ?>

                            <?php $route = '/equipes';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('EquipeController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Montar Equipes</a>
                            <?php endif; ?>

                            <?php $route = '/producao';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('ProducaoController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Lançar Produção (Individual)</a>
                            <?php endif; ?>

                            <?php $route = '/producao/massa';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('ProducaoController@massa', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Lançar Prod. em Massa</a>
                            <?php endif; ?>

                            <?php $route = '/producao/editar-massa';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('ProducaoController@editarMassa', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Editar Produção em Massa</a>
                            <?php endif; ?>

                            <?php $route = '/producao/editar-dia';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('ProducaoController@editarDia', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Editar Produção do Dia</a>
                            <?php endif; ?>

                        </nav>
                    </div>

                    <hr class="sidebar-divider">
                <?php endif; ?>


                <?php
                $admin_routes = ['/admin/funcionarios', '/admin/tipos-produto', '/admin/acoes', '/admin/valores-pagamento', '/permissoes/gestao'];
                $admin_is_active = is_group_active($admin_routes, $current_route);

                if (Acl::check('FuncionarioController@index', $tipo_usuario) || Acl::check('PermissaoController@index', $tipo_usuario)):
                ?>
                    <div class="sb-sidenav-menu-heading">ADMINISTRAÇÃO / CADASTROS</div>

                    <a class="nav-link collapsed <?php echo $admin_is_active; ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCadastros" aria-expanded="false" aria-controls="collapseCadastros">
                        <div class="sb-nav-link-icon"><i class="fas fa-boxes"></i></div>
                        Cadastros
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>

                    <div class="collapse <?php echo $admin_is_active; ?>" id="collapseCadastros" aria-labelledby="headingCadastros" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">

                            <?php $route = '/admin/funcionarios';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('FuncionarioController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Gerenciar Funcionários</a>
                            <?php endif; ?>

                            <?php $route = '/admin/tipos-produto';
                            $is_active = is_active($route, $current_route);
                            if (Acl::check('TipoProdutoController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Gerenciar Tipos de Produto</a>
                            <?php endif; ?>

                            <?php $route = '/admin/acoes';
                            $is_active = is_active($route, $current_route);
                            if (Acl::check('AcaoController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Gerenciar Ações</a>
                            <?php endif; ?>

                            <?php $route = '/admin/valores-pagamento';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('ValoresPagamentoController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Gerenciar Valores Pagto</a>
                            <?php endif; ?>

                            <?php $route = '/permissoes/gestao';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('PermissaoController@index', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Gestão de Permissões (ACL)</a>
                            <?php endif; ?>

                        </nav>
                    </div>
                    <hr class="sidebar-divider">
                <?php endif; ?>


                <?php
                $rel_routes = ['/relatorios', '/relatorios/produtividade', '/relatorios/quantidades', '/relatorios/servicos'];
                $rel_is_active = is_group_active($rel_routes, $current_route);

                if (Acl::check('RelatorioController@pagamentos', $tipo_usuario) || Acl::check('RelatorioController@produtividade', $tipo_usuario)):
                ?>
                    <div class="sb-sidenav-menu-heading">RELATÓRIOS</div>

                    <a class="nav-link collapsed <?php echo $rel_is_active; ?>" href="#" data-bs-toggle="collapse" data-bs-target="#collapseRelatorios" aria-expanded="false" aria-controls="collapseRelatorios">
                        <div class="sb-nav-link-icon"><i class="fas fa-chart-line"></i></div>
                        Financeiro / Relatórios
                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                    </a>

                    <div class="collapse <?php echo $rel_is_active; ?>" id="collapseRelatorios" aria-labelledby="headingRelatorios" data-bs-parent="#sidenavAccordion">
                        <nav class="sb-sidenav-menu-nested nav">

                            <?php $route = '/relatorios/pagamentos';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('RelatorioController@pagamentos', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Pagamento Total (R$)</a>
                            <?php endif; ?>

                            <?php $route = '/relatorios/produtividade';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('RelatorioController@produtividade', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Análise Produtividade/Hora</a>
                            <?php endif; ?>

                            <?php $route = '/relatorios/quantidades';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('RelatorioController@quantidades', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Quantidades Produzidas (Kg)</a>
                            <?php endif; ?>

                            <?php $route = '/relatorios/servicos';
                            $is_active = is_active($route, $current_route); ?>
                            <?php if (Acl::check('RelatorioController@servicos', $tipo_usuario)): ?>
                                <a class="nav-link <?php echo $is_active; ?>" href="<?php echo $base_url . $route; ?>">Serviços / Diárias (Apoio)</a>
                            <?php endif; ?>

                        </nav>
                    </div>
                <?php endif; ?>

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <div class="small">Logado como:</div>
            <?php echo ucfirst(htmlspecialchars($tipo_usuario)); ?>
        </div>
    </nav>
</div>