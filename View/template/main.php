<?php
// View/template/main.php
// As variáveis $title, $content_view e $dados já devem estar definidas pelo Controller.

// A variável deve apontar para a pasta 'dist'
$theme_prefix = '/sgi_erp/public/theme/sb-admin-themewagon/dist';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Sistema de Gestão Integrada" />
    <meta name="author" content="MD Soluções" />

    <title>SGI ERP - <?php echo htmlspecialchars($title ?? 'Página Principal'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="<?php echo $theme_prefix; ?>/css/styles.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/js/all.min.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="/sgi_erp/public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

</head>

<body class="sb-nav-fixed">

    <?php require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'topbar.php'; ?>

    <div id="layoutSidenav">

        <?php require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'sidebar.php'; ?>

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <?php
                    if (file_exists($content_view)) {
                        require_once $content_view;
                    } else {
                        echo "<div class='alert alert-danger'>Erro: View <strong>" . htmlspecialchars($content_view) . "</strong> não encontrada.</div>";
                    }
                    ?>
                </div>
            </main>

            <footer class="py-4 bg-light mt-auto">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; SGI ERP <?php echo date('Y'); ?></div>
                        <div>
                            <a href="#">Política de Privacidade</a>
                            &middot;
                            <a href="#">Termos &amp; Condições</a>
                        </div>
                    </div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="<?php echo $theme_prefix; ?>/js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="/sgi_erp/public/js/global-scripts.js"></script>
    <script src="/sgi_erp/public/js/presenca-interatividade.js"></script>
    <script src="/sgi_erp/public/js/equipes-interatividade.js"></script>

    <<?php
        // Carrega scripts específicos por página
        $pode_editar = $dados['pode_editar'] ?? false;
        $pagina_atual = $_SERVER['REQUEST_URI'] ?? '';
        $is_relatorio = strpos($pagina_atual, '/relatorios/') !== false;
        $is_acoes = strpos($pagina_atual, '/admin/acoes') !== false;
        ?>

        <?php if ($pode_editar || $is_acoes): ?>
        <script src="/sgi_erp/public/js/acao-interatividade.js">
        </script>
        <script src="/sgi_erp/public/js/valores-interatividade.js"></script>
    <?php endif; ?>

    <?php if ($is_relatorio): ?>
        <script src="/sgi_erp/public/js/relatorios-interatividade.js"></script>
    <?php endif; ?>

    <?php require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'alerts.php'; ?>
    <?php require_once ROOT_PATH . 'View' . DS . 'template' . DS . 'logout_modal.php'; ?>
</body>

</html>