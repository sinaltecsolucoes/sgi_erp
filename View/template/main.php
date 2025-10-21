<?php
// Variáveis esperadas: $title, $content_view, $dados (opcional)
// O AppController garante que o usuário está logado aqui.
$theme_prefix = '/sgi_erp/public/theme/startbootstrap-sb-admin-2-4.1.4';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistema de Gestão Integrada">
    <meta name="author" content="Parceiro de Programação">

    <title>SGI ERP - <?php echo htmlspecialchars($title ?? 'Sistema'); ?></title>

    <link href="<?php echo $theme_prefix; ?>/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $theme_prefix; ?>/vendor/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $theme_prefix; ?>/css/sb-admin-2.min.css">
    <link rel="stylesheet" href="/sgi_erp/public/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

</head>

<body id="page-top">

    <div id="wrapper">

        <?php require_once 'sidebar.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">

            <div id="content">

                <?php require_once 'topbar.php'; ?>

                <div class="container-fluid">

                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($title ?? 'Dashboard'); ?></h1>
                    </div>

                    <?php
                    if (file_exists($content_view)) {
                        // Passa a variável $dados para o escopo da View
                        require_once $content_view;
                    } else {
                        echo "<h1 class='text-danger'>Erro: View **`" . htmlspecialchars($content_view) . "`** não encontrada.</h1>";
                    }
                    ?>

                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; SGI ERP <?php echo date('Y'); ?></span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>


    <script src="<?php echo $theme_prefix; ?>/vendor/jquery/jquery.min.js"></script>
    <script src="<?php echo $theme_prefix; ?>/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $theme_prefix; ?>/vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="<?php echo $theme_prefix; ?>/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <?php require_once ROOT_PATH . 'View' . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'alerts.php'; ?>

    
</body>

</html>