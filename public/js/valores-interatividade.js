// public/js/valores-interatividade.js

/**
 * Interatividade para Gestão de Valores de Pagamento
 * - Máscara para valor (R$ com vírgula)
 * - Confirmação de exclusão com SweetAlert
 */

$(document).ready(function () {
    $('.money-mask').each(function () {

        // Lê o atributo data-decimals (quantidade de casas decimais)
        var decimals = $(this).data('decimals') || 2; // padrão: 2 casas

        // Monta a máscara dinamicamente
        var maskPattern = '000.000,' + '0'.repeat(decimals);

        // Aplica a máscara
        $(this).mask(maskPattern, { reverse: true });

    });
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