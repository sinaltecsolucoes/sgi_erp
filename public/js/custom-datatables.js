// public/js/custom-datatables.js (Definir o objeto de configurações)

// Define as configurações de tradução de forma global para que scripts.js possa usá-lo
window.sgiDatatableConfig = {
    labels: {
        placeholder: "Pesquisar...",
        perPage: "{select} entradas por página",
        noRows: "Nenhum resultado encontrado",
        info: "Mostrando de {start} a {end} de {rows} entradas",
        search: "Buscar:",
        paginate: {
            first: "Primeira",
            last: "Última",
            next: "Próxima",
            prev: "Anterior"
        },
    },
    // Desativar ordenação em colunas específicas (como 'Ações')
    columns: [
        { select: 0, sortable: true }, // ID
        { select: 1, sortable: true }, // Nome
        { select: 2, sortable: true }, // Tipo
        { select: 3, sortable: true }, // Login
        { select: 4, sortable: true }, // Status
        { select: 5, sortable: false }  // Ações (Não ordenar)
    ]
};

// Se o tema não inicializar automaticamente, fazemos a inicialização aqui (com atraso)
document.addEventListener('DOMContentLoaded', event => {
    const datatablesSimple = document.getElementById('datatablesSimple');
    if (datatablesSimple && typeof simpleDatatables !== 'undefined') {
        new simpleDatatables.DataTable(datatablesSimple, window.sgiDatatableConfig);
    }
});