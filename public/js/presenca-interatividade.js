// public/js/presenca-interatividade.js

/**
 * Lógica de interatividade da tela de Presença (View/presenca.php).
 * Altera cores da linha e do texto ao marcar/desmarcar o switch.
 * * Depende do jQuery.
 */
$(document).ready(function () {

    // As classes de cor de fundo (light/danger-custom) e texto (success/danger) 
    // devem ser definidas no CSS principal (public/css/style.css)

    // Manipulador de evento para cada switch de presença
    $('.presenca-check').on('change', function () {
        const funcId = $(this).data('id');
        const isChecked = $(this).is(':checked');
        const $row = $('#row-' + funcId);
        const $text = $('#text-' + funcId);

        if (isChecked) {
            // Marcar como PRESENTE: Fundo cinza claro e texto verde
            $row.addClass('list-group-item-light-custom').removeClass('list-group-item-danger-custom');
            $text.addClass('text-success font-weight-bold').removeClass('text-danger');
        } else {
            // Marcar como AUSENTE: Fundo transparente e texto vermelho
            $row.removeClass('list-group-item-light-custom').addClass('list-group-item-danger-custom');
            $text.removeClass('text-success font-weight-bold').addClass('text-danger');
        }
    });

    /**
     * Quando a página carrega, aplica as classes customizadas
     * para garantir que a cor inicial esteja correta, caso o status
     * tenha sido carregado pelo PHP (vindo do banco de dados).
     */
    $('.list-group-item').each(function () {
        const $row = $(this);
        const $switch = $row.find('.presenca-check');
        const $text = $row.find('span'); // O span é o texto do funcionário

        if ($switch.is(':checked')) {
            $row.addClass('list-group-item-light-custom').removeClass('list-group-item-danger-custom');
            $text.addClass('text-success font-weight-bold').removeClass('text-danger');
        } else {
            $row.removeClass('list-group-item-light-custom').addClass('list-group-item-danger-custom');
            $text.removeClass('text-success font-weight-bold').addClass('text-danger');
        }
    });

});