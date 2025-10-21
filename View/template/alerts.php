<?php
$sucesso = $_SESSION['sucesso'] ?? null;
$erro = $_SESSION['erro'] ?? null;

// Limpa as sessões após resgatar os valores
unset($_SESSION['sucesso']);
unset($_SESSION['erro']);

// Lógica de exibição com SweetAlert2
if ($sucesso):
?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            html: '<?php echo str_replace("\n", '<br>', htmlspecialchars($sucesso)); ?>',
            showConfirmButton: false,
            timer: 3500
        });
    </script>
<?php
endif;

if ($erro):
?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Erro!',
            html: '<?php echo str_replace("\n", '<br>', htmlspecialchars($erro)); ?>',
            showConfirmButton: true
        });
    </script>
<?php
endif;
?>