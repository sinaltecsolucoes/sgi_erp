// public/js/producao-massa-interatividade.js

/**
 * Inicializa a interatividade de lote para um formulário de lançamento em massa específico.
 * @param {string} equipeId O ID da equipe (usado para isolar os elementos do formulário nesta aba).
 * @param {Array<Object>} produtosData Array de objetos de produto contendo {id, usa_lote}.
 */
function inicializarMassaLote(equipeId, produtosData) {
    const selectProduto = document.getElementById('tipo_produto_id-' + equipeId);
    const campoLote = document.getElementById('campo-lote-' + equipeId);
    const inputLote = document.getElementById('lote_produto-' + equipeId);

    // Se algum elemento não for encontrado, encerra a função
    if (!selectProduto || !campoLote || !inputLote) {
        return;
    }

    function toggleLote() {
        const selectedId = parseInt(selectProduto.value);
        // Encontra o objeto do produto com base no ID selecionado
        const produto = produtosData.find(p => p.id === selectedId);

        // Se o produto não for encontrado, ou se usa_lote for 1 (padrão de obrigatoriedade)
        const loteObrigatorio = produto ? (produto.usa_lote === 1) : true;

        if (loteObrigatorio) {
            campoLote.style.display = 'block';
            inputLote.setAttribute('required', 'required');
        } else {
            campoLote.style.display = 'none';
            inputLote.removeAttribute('required');
            inputLote.value = ''; // Limpa o valor para não enviar lote vazio
        }
    }

    // Adiciona o listener para o evento de mudança
    selectProduto.addEventListener('change', toggleLote);

    // Inicializa a função na carga da aba (necessário no sistema de abas)
    // Se a aba estiver ativa, o campo será verificado.
    // Se houver um valor inicial no select, dispara o evento.
    if (selectProduto.value) {
        toggleLote();
    } else {
        // Garante que o campo esteja escondido por padrão se nada estiver selecionado
        campoLote.style.display = 'none';
    }
}