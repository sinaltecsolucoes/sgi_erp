// public/js/equipes-interatividade.js

/**
 * Lógica de interatividade da tela de Montagem de Equipes (View/equipes_abas.php).
 * Altera cores da linha e do texto com base no estado do switch em ABAS e MODAL.
 * * Depende do jQuery.
 */
$(document).ready(function () {

    // Seletor geral que cobre switches em todas as abas (equipe-check) e switches dentro do modal (membros[])
    const ALL_SWITCHES_SELECTOR = '.equipe-check, #novaEquipeModal input[name="membros[]"]';

    /**
     * Função central para alternar a cor da linha e do texto
     * @param {jQuery} $switch O elemento input[type="checkbox"]
     */
    function toggleMemberStyle($switch) {
        const isChecked = $switch.is(':checked');
        const $row = $switch.closest('.list-group-item');
        const $text = $row.find('span'); // O span é o elemento que contém o nome

        if (isChecked) {
            // Membro Selecionado/Alocado: Fundo cinza claro e texto verde
            $row.addClass('list-group-item-light-custom').removeClass('list-group-item-danger-custom');
            $text.addClass('text-success font-weight-bold').removeClass('text-danger');
        } else {
            // Não Membro/Desalocado: Fundo transparente e texto vermelho
            $row.removeClass('list-group-item-light-custom').addClass('list-group-item-danger-custom');
            $text.removeClass('text-success font-weight-bold').addClass('text-danger font-weight-bold');
        }
    }

    // --- 1. APLICAÇÃO INICIAL (CARGA DA PÁGINA) ---
    // Aplica o estilo inicial para todas as abas abertas e no modal (se estiver visível)
    $(ALL_SWITCHES_SELECTOR).each(function () {
        toggleMemberStyle($(this));
    });

    // --- 2. INTERATIVIDADE (EVENTO CHANGE) ---
    // Ativa o listener para mudanças nos switches
    $(ALL_SWITCHES_SELECTOR).on('change', function () {
        toggleMemberStyle($(this));
    });

    // --- 3. LÓGICA DO MODAL (Para garantir que a interatividade funcione quando ele for aberto) ---
    // Quando o modal é exibido, forçamos o re-check de todos os switches dele
    $('#novaEquipeModal').on('show.bs.modal', function () {
        $(this).find('input[name="membros[]"]').each(function () {
            // Garante que o estilo seja aplicado ao abrir o modal
            toggleMemberStyle($(this));
        });
    });// === LÓGICA DO MODAL MOVER ===
    const modalMover = document.getElementById('moverMembroModal');
    if (modalMover) {
        modalMover.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const funcId = button.getAttribute('data-func-id');
            const funcNome = button.getAttribute('data-func-nome');
            const origemId = button.getAttribute('data-origem-equipe');

            // Pega o nome da equipe de origem (do título da aba ativa)
            const abaAtiva = document.querySelector('.tab-pane.fade.show');
            const nomeEquipeOrigem = abaAtiva ? abaAtiva.querySelector('h4.text-primary').textContent.trim() : 'Equipe Desconhecida';

            // Preenche os campos
            document.getElementById('mover-funcionario-id').value = funcId;
            document.getElementById('mover-equipe-origem-id').value = origemId;
            document.getElementById('membro-nome-mover').textContent = funcNome;
            document.getElementById('equipe-origem-nome').textContent = nomeEquipeOrigem;

            // Configura o select
            const select = document.getElementById('equipe_destino_id');
            const alerta = document.getElementById('alerta-mesma-equipe');
            const btnMover = document.getElementById('btn-mover-membro');

            // Reseta
            select.value = '';
            alerta.style.display = 'none';
            btnMover.disabled = false;

            // Bloqueia a mesma equipe
            for (let option of select.options) {
                if (option.value == origemId) {
                    option.disabled = true;
                } else {
                    option.disabled = false;
                }
            }

            // Validação ao selecionar
            select.addEventListener('change', function () {
                if (this.value == origemId) {
                    alerta.style.display = 'block';
                    btnMover.disabled = true;
                } else {
                    alerta.style.display = 'none';
                    btnMover.disabled = false;
                }
            });
        });
    }

    // === EXCLUSÃO COM SWEETALERT (mantido) ===
    window.excluirEquipe = function (equipeId, nomeEquipe) {
        Swal.fire({
            title: `Excluir Equipe **${nomeEquipe}**?`,
            text: "Você não poderá reverter isso! Apenas a equipe será removida, os lançamentos de produção serão mantidos.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, Excluir!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `/sgi_erp/equipes/excluir?id=${equipeId}`;
            }
        });
    };
});