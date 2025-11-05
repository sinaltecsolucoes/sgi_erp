// public/js/acao-interatividade.js

/**
 * Interatividade para a tela de Gestão de Ações
 * - Confirmação de exclusão com SweetAlert2
 */

function confirmarExclusaoAcao(id, nome) {
    Swal.fire({
        title: `Excluir **${nome}**?`,
        text: "Esta ação não pode ser desfeita!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sim, excluir!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/sgi_erp/admin/acoes/excluir?id=${id}`;
        }
    });
}