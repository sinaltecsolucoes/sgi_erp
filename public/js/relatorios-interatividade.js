$(document).ready(function () {
    let ultimoBackup = null; // Para o "Desfazer"

    // MÁSCARA BRASILEIRA
    $(document).on('input', '.input-edicao', function () {
        let v = this.value.replace(/\D/g, '');
        v = v.replace(/(\d)(\d{3})$/, '$1,$2');
        v = v.replace(/(?=(\d{3})+(\D))\B/g, '.');
        this.value = v;
    });

    // EXPANSÃO
    $(document).on('click', '.funcionario-linha', function () {
        const $detalhe = $(this).next('.detalhes-linha');
        const $icon = $(this).find('.icon-expand');
        $detalhe.slideToggle(300);
        $icon.toggleClass('fa-plus-circle fa-minus-circle');
    });

    // EDITAR
    $(document).on('click', '.btn-editar-produto', function () {
        const $container = $(this).closest('.detalhes-linha');
        $container.find('.celula-valor').each(function () {
            const $cell = $(this);
            const texto = $cell.find('.valor-exibicao').text().trim();
            const num = texto === '-' ? 0 : parseFloat(texto.replace(/\./g, '').replace(',', '.')) || 0;
            $cell.data('original', num);
            $cell.find('.input-edicao').val(num > 0 ? num.toFixed(3).replace('.', ',') : '');
        });

        $container.find('.valor-exibicao').hide();
        $container.find('.input-edicao').show();
        $container.find('.btn-editar-produto').hide();
        $container.find('.btn-salvar-produto, .btn-cancelar-produto, .btn-desfazer').show();
    });

    // CANCELAR
    $(document).on('click', '.btn-cancelar-produto', function () {
        location.reload();
    });

    // DESFAZER
    $(document).on('click', '.btn-desfazer', function () {
        if (!ultimoBackup) {
            Swal.fire('Nada para desfazer', '', 'info');
            return;
        }
        ultimoBackup.forEach(u => {
            const $cell = $(`td[data-id="${u.id}"][data-data="${u.data}"]`);
            const valorOriginal = u.original;
            $cell.find('.valor-exibicao').text(valorOriginal > 0 ? valorOriginal.toLocaleString('pt-BR', { minimumFractionDigits: 3 }) : '-');
            $cell.find('.input-edicao').val(valorOriginal > 0 ? valorOriginal.toFixed(3).replace('.', ',') : '');
        });
        calcularTotais();
        Swal.fire({ title: 'Desfeito!', icon: 'info', timer: 1500, showConfirmButton: false });
    });

    // SALVAR
    $(document).on('click', '.btn-salvar-produto', function () {
        const $container = $(this).closest('.detalhes-linha');
        const updates = [];

        $container.find('.celula-valor').each(function () {
            const $cell = $(this);
            const id = parseInt($cell.data('id')) || 0;
            const data = $cell.data('data');
            const original = parseFloat($cell.data('original')) || 0;

            const raw = $cell.find('.input-edicao').val().replace(/[^\d,-]/g, '').replace(',', '.');
            const digitado = parseFloat(raw) || 0;

            if (digitado !== original || (id === 0 && digitado > 0)) {
                updates.push({
                    id: id,
                    quantidade_kg: digitado,
                    data: data,
                    funcionario_id: $cell.data('funcionario-id'),
                    tipo_produto_id: $cell.data('tipo-produto-id')
                });
            }
        });

        if (updates.length === 0) {
            Swal.fire('Nenhum valor alterado', '', 'info');
            return;
        }

        // Backup antes de enviar
        ultimoBackup = updates.map(u => ({
            id: u.id,
            data: u.data,
            original: $(`td[data-id="${u.id}"][data-data="${u.data}"]`).data('original')
        }));

        Swal.fire({
            title: 'Salvar alterações?',
            text: `${updates.length} lançamento(s) serão salvos`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, salvar!',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '/sgi_erp/relatorios/atualizar-producao',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ updates: updates }),
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        // 1. Atualiza exibição e data-original dos valores que foram editados (existentes e novos)
                        updates.forEach(u => {
                            const $cell = $(`td[data-id="${u.id}"][data-data="${u.data}"]`);
                            const novoValor = u.quantidade_kg;
                            $cell.find('.valor-exibicao').text(novoValor > 0 ? novoValor.toLocaleString('pt-BR', { minimumFractionDigits: 3 }) : '-');
                            $cell.data('original', novoValor);

                            // 2. Garante que os valores de Kg > 0 sejam formatados corretamente
                            $cell.find('.input-edicao').val(novoValor > 0 ? novoValor.toFixed(3).replace('.', ',') : '');
                        });

                        // *** MUDANÇA AQUI: Atualiza os data-id para novos lançamentos ***
                        if (res.novos_ids && res.novos_ids.length > 0) {
                            res.novos_ids.forEach(novo => {
                                // Seleciona a célula que tinha id=0, data e os IDs de FKs (func_id e tipo_id)
                                // Usamos as FKs para garantir que pegamos a célula correta
                                const $cell = $(`td[data-id="0"][data-data="${novo.data}"][data-funcionario-id="${novo.func_id}"][data-tipo-produto-id="${novo.tipo_id}"]`);
                                if ($cell.length) {
                                    $cell.data('id', novo.new_id); // Atualiza o data-id
                                    // A partir de agora, esta célula fará um UPDATE
                                }
                            });
                        }

                        calcularTotais();

                        // Toast
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: res.message || 'Salvo com sucesso!'
                        });
                    } else {
                        Swal.fire('Erro', res.message, 'error');
                    }
                },
                error: function () {
                    Swal.fire('Erro', 'Falha na comunicação com o servidor', 'error');
                }
            });
        });
    });

    // EXCLUIR LINHA (produto inteiro)
    $(document).on('click', '.btn-excluir-linha', function () {
        const $row = $(this).closest('tr');
        const produto = $row.data('produto');

        Swal.fire({
            title: 'Excluir produto?',
            text: `"${produto}" será removido de todos os dias!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                const ids = [];
                $row.find('.celula-valor').each(function () {
                    const id = parseInt($(this).data('id')) || 0;
                    if (id > 0) ids.push(id);
                });

                $.ajax({
                    url: '/sgi_erp/relatorios/excluir-producao',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ ids: ids }),
                    dataType: 'json',
                    success: function (res) {
                        if (res.success) {
                            $row.remove();
                            calcularTotais();
                            Swal.fire('Excluído!', '', 'success');
                        }
                    }
                });
            }
        });
    });

    // RECALCULAR TOTAIS
    window.calcularTotais = function () {
        // Totais por dia
        $('.total-dia').each(function () {
            const data = $(this).closest('th').prevAll('th').not(':first').index() - 1;
            let soma = 0;
            $(`td[data-data]:nth-child(${data + 2}) .valor-exibicao`).each(function () {
                const txt = $(this).text().trim();
                if (txt !== '-' && txt !== '') {
                    soma += parseFloat(txt.replace(/\./g, '').replace(',', '.'));
                }
            });
            $(this).text(soma.toLocaleString('pt-BR', { minimumFractionDigits: 3 }));
        });

        // Total geral
        let totalGeral = 0;
        $('.total-dia').each(function () {
            const v = parseFloat($(this).text().replace(/\./g, '').replace(',', '.')) || 0;
            totalGeral += v;
        });
        $('#total-geral').text(totalGeral.toLocaleString('pt-BR', { minimumFractionDigits: 3 }));
    };

    // PDF & EXCEL (já existiam)
    $('#btn-pdf').click(() => gerarPDF());
    $('#btn-excel').click(() => exportarExcel());
});