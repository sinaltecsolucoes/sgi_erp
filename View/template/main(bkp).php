<?php
// View/template/main.php
// Layout Padrão do Sistema (Header, Navegação e Includes)

// As variáveis $title e $content_view devem ser definidas antes de incluir este template.
$title = $title ?? 'SGI ERP';
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - SGI ERP</title>

    <link rel="stylesheet" href="/sgi_erp/public/css/style.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>

<body>
    <header class="header">
        <h2>SGI ERP - Gestão de Produção</h2>
        <div>
            <?php if (isset($_SESSION['logado'])): ?>
                <span>
                    Bem-vindo(a),
                    <strong><?php echo htmlspecialchars($_SESSION['funcionario_nome'] ?? 'Usuário'); ?></strong>
                </span>
                <a href="/sgi_erp/logout" style="margin-left: 20px;">Sair</a>
            <?php endif; ?>
        </div>
    </header>

    <div class="main-content">
        <?php
        // Inclui a view específica (dashboard.php, presenca.php, etc.)
        if (isset($content_view) && file_exists($content_view)) {
            require_once $content_view;
        } else {
            echo '<div class="content">Erro: Conteúdo da View não encontrado.</div>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        <?php
        // Verifica e exibe a mensagem de sucesso
        if (isset($_SESSION['sucesso'])) {
            $mensagem = htmlspecialchars($_SESSION['sucesso']);
            echo "
                Swal.fire({
                    icon: 'success',
                    title: 'Sucesso!',
                    text: '{$mensagem}',
                    timer: 3000,
                    showConfirmButton: false
                });
            ";
            unset($_SESSION['sucesso']);
        }

        // Verifica e exibe a mensagem de erro
        if (isset($_SESSION['erro'])) {
            $mensagem = htmlspecialchars($_SESSION['erro']);
            echo "
                Swal.fire({
                    icon: 'error',
                    title: 'Atenção!',
                    text: '{$mensagem}',
                    confirmButtonText: 'OK'
                });
            ";
            unset($_SESSION['erro']);
        }
        ?>
    </script>
</body>

</html>