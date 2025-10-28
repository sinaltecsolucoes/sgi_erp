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

$confirm_action = $_SESSION['confirm_action'] ?? null;

// Limpa a sessão após resgatar os valores
unset($_SESSION['confirm_action']);

if ($confirm_action):
?>
    <script>
        Swal.fire({
            icon: 'question',
            title: 'CPF Duplicado!',
            html: '<?php echo str_replace("\n", '<br>', htmlspecialchars($confirm_action['message'])); ?>',
            showCancelButton: true,
            confirmButtonText: 'Sim, Editar!',
            cancelButtonText: 'Não, Voltar à Lista',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Redireciona para a URL de edição se o usuário confirmar
                window.location.href = '<?php echo htmlspecialchars($confirm_action['confirm_url']); ?>';
            }
            // Se cancelar, a tela permanece na listagem (que é a rota atual)
        });
    </script>
<?php
endif;

?>