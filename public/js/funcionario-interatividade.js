// public/js/funcionario-interatividade.js

document.addEventListener('DOMContentLoaded', function () {
    // === ELEMENTOS DO DOM ===
    const radioCpf = document.getElementById('doc_cpf');
    const radioRg = document.getElementById('doc_rg');
    const campoCpf = document.getElementById('campo_cpf');
    const campoRg = document.getElementById('campo_rg');
    const inputCpf = document.getElementById('cpf');
    const inputRg = document.getElementById('rg');

    if (!radioCpf || !radioRg || !campoCpf || !campoRg) {
        return; // Sai se não estiver na página certa
    }

    // === FUNÇÃO: Alternar campos CPF/RG ===
    function toggleCampos() {
        if (radioCpf.checked) {
            campoCpf.style.display = 'block';
            campoRg.style.display = 'none';
            inputCpf.setAttribute('required', 'required');
            inputRg.removeAttribute('required');
            inputRg.value = ''; // limpa RG
        } else {
            campoCpf.style.display = 'none';
            campoRg.style.display = 'block';
            inputRg.setAttribute('required', 'required');
            inputCpf.removeAttribute('required');
            inputCpf.value = ''; // limpa CPF
        }
    }

    // === APLICAR MÁSCARA NO CPF (usando jQuery Mask) ===
    if (typeof $.fn.mask !== 'undefined' && inputCpf) {
        $(inputCpf).mask('000.000.000-00', { reverse: true });
    }

    // === EVENTOS ===
    radioCpf.addEventListener('change', toggleCampos);
    radioRg.addEventListener('change', toggleCampos);

    // Inicializa o estado correto
    toggleCampos();
});