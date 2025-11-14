// public/js/valores-interatividade.js

/**
 * Interatividade para Gestão de Valores de Pagamento
 * - Máscara para valor (R$ com vírgula)
 * - Confirmação de exclusão com SweetAlert
 */

$(document).ready(function () {
    // Máscara para valor por quilo (ex: 5,50)
    $('.money-mask').mask('000.000,00', { reverse: true });
});

// Confirmação de exclusão
function confirmarExclusaoValor(id, nome) {
    Swal.fire({
        title: `Excluir valor para **${nome}**?`,
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then(result => {
        if (result.isConfirmed) {
            fetch('/sgi_erp/admin/valores-pagamento/excluir', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + id
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Excluído!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro!', data.message, 'error');
                    }
                });
        }
    });
}