<?php
// Determina o tipo de usuário logado
$tipo_usuario = $_SESSION['funcionario_tipo'] ?? 'convidado';
$base_url = '/sgi_erp';
?>

<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?php echo $base_url; ?>/dashboard">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-shrimp"></i>
        </div>
        <div class="sidebar-brand-text mx-3">SGI <sup>ERP</sup></div>
    </a>

    <hr class="sidebar-divider my-0">

    <li class="nav-item active">
        <a class="nav-link" href="<?php echo $base_url; ?>/dashboard">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span></a>
    </li>

    <hr class="sidebar-divider">

    <div class="sidebar-heading">
        Módulos Operacionais
    </div>

    <?php if ($tipo_usuario === 'apontador' || $tipo_usuario === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/presenca">
                <i class="fas fa-fw fa-calendar-check"></i>
                <span>Chamada de Presença</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/equipes">
                <i class="fas fa-fw fa-users"></i>
                <span>Montar Equipes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/producao">
                <i class="fas fa-fw fa-balance-scale"></i>
                <span>Lançar Produção</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if ($tipo_usuario === 'financeiro' || $tipo_usuario === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/relatorios/pagamentos">
                <i class="fas fa-fw fa-chart-line"></i>
                <span>Relatórios / Pagamentos</span>
            </a>
        </li>
    <?php endif; ?>

    <?php if ($tipo_usuario === 'admin'): ?>
        <hr class="sidebar-divider">

        <div class="sidebar-heading">
            Administração
        </div>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/admin/funcionarios">
                <i class="fas fa-fw fa-user-friends"></i>
                <span>Gerenciar Funcionários</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="<?php echo $base_url; ?>/permissoes/gestao">
                <i class="fas fa-fw fa-shield-alt"></i>
                <span>Gestão de Permissões (ACL)</span>
            </a>
        </li>
    <?php endif; ?>

    <hr class="sidebar-divider d-none d-md-block">

    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>