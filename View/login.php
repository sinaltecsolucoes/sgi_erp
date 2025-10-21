<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SGI - Login</title>
    <link rel="stylesheet" href="/sgi_erp/public/css/style.css">
</head>

<body class="page-center">
    <div class="login-container">
        <h1>SGI - Acesso</h1>

        <?php
        // Exibe a mensagem de erro se houver
        if (isset($_SESSION['erro_login'])) {
            echo '<div class="error-message">' . htmlspecialchars($_SESSION['erro_login']) . '</div>';
            // Limpa a mensagem de erro para que não apareça na próxima visita
            unset($_SESSION['erro_login']);
        }
        ?>

        <form action="/sgi_erp/login" method="POST">
            <div class="form-group">
                <label for="login">Login</label>
                <input type="text" id="login" name="login" required placeholder="apontador.geral">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="123456">
            </div>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>
    </div>
</body>

</html>