// public/js/global-scripts.js

$(document).ready(function () {
    // Aplica a máscara de CPF (999.999.999-99)
    // O seletor ID que usaremos é o '#cpf' da tela de cadastro de funcionário.
    // Pode ser adaptado para usar classes para outras telas.
    $('#cpf').mask('000.000.000-00', { reverse: true });
});