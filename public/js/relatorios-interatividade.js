// public/js/relatorios-interatividade.js
$(document).ready(function () {
    let ultimoBackup = null; // Para "Desfazer"

    /**
     * Função auxiliar para formatar valores numéricos com base na unidade global.
     * A variável 'unidadeMedida' é definida em cada View PHP ('R$' ou 'KG').
     * @param {number} numero - O valor a ser formatado.
     * @returns {string} O valor formatado (Ex: "R$ 10,00" ou "5,000 kg").
     */
    function formatarTotal(numero) {
        if (typeof numero !== 'number' || isNaN(numero) || numero === 0) {
            return '';
        }

        const unidade = typeof unidadeMedida !== 'undefined' ? unidadeMedida : 'R$';

        let decimais;
        let prefixo = '';
        let sufixo = '';

        if (unidade === 'KG') {
            decimais = 3; // 3 casas decimais para KG
            sufixo = ' kg';
        } else { // Assume 'R$' ou qualquer outro
            decimais = 2; // 2 casas decimais para Moeda
            prefixo = 'R$ ';
        }

        const valorFormatado = numero.toLocaleString('pt-BR', {
            minimumFractionDigits: decimais,
            maximumFractionDigits: decimais
        });

        return prefixo + valorFormatado + sufixo;
    }

    // MÁSCARA BRASILEIRA (para entrada de usuário)
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

    // INICIAR EDIÇÃO
    $(document).on('click', '.btn-editar-produto', function () {
        const $container = $(this).closest('.detalhes-linha');
        ultimoBackup = [];

        $container.find('.celula-valor').each(function () {
            const $cell = $(this);
            const texto = $cell.find('.valor-exibicao').text().trim();

            // Remove R$ ou KG e converte para número
            let num = 0;
            if (texto && texto !== '-') {
                // Remove R$ e KG (se for o caso)
                let textoLimpo = texto.replace('R$', '').replace('kg', '').trim();
                num = parseFloat(textoLimpo.replace(/\./g, '').replace(',', '.')) || 0;
            }

            ultimoBackup.push({
                id: $cell.data('id'),
                data: $cell.data('data'),
                original: num
            });

            $cell.data('original', num);

            // Define quantas casas decimais mostrar no input
            let casasDecimais = (typeof unidadeMedida !== 'undefined' && unidadeMedida === 'KG') ? 3 : 2;

            // Formata o valor para o input (usando vírgula como decimal)
            const valorInput = num > 0 ? num.toFixed(casasDecimais).replace('.', ',') : '';
            $cell.find('.input-edicao').val(valorInput);
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
            const textoExibicao = u.original > 0 ? formatarTotal(u.original) : '-';
            $cell.find('.valor-exibicao').text(textoExibicao).show();
            $cell.find('.input-edicao').val('').hide();
        });

        calcularTotais();
        Swal.fire('Desfeito!', '', 'success');
    });

    $(document).on('click', '.btn-salvar-produto', function () {
        const $botaoSalvar = $(this);
        const $container = $botaoSalvar.closest('.detalhes-linha');
        const updates = [];

        $container.find('.celula-valor').each(function () {
            const $cell = $(this);
            const inputVal = $cell.find('.input-edicao').val().trim();
            let novoValor = 0;

            if (inputVal !== '' && inputVal !== '0,000') {
                // Remove ponto de milhar e troca vírgula por ponto para float
                novoValor = parseFloat(inputVal.replace(/\./g, '').replace(',', '.')) || 0;
            }

            // Define casas decimais para comparação
            let casasDecimaisComp = (typeof unidadeMedida !== 'undefined' && unidadeMedida === 'KG') ? 3 : 2;

            const original = $cell.data('original') || 0;
            if (novoValor.toFixed(casasDecimaisComp) !== original.toFixed(casasDecimaisComp)) {
                updates.push({
                    id: $cell.data('id') || 0,
                    // Garante que enviamos com ponto como separador decimal para o PHP
                    quantidade_kg: novoValor.toFixed(3),
                    data: $cell.data('data'),
                    funcionario_id: $cell.data('funcionario-id'),
                    tipo_produto_id: $cell.data('tipo-produto-id')
                });

                $cell.data('original', novoValor);
            }
        });

        if (updates.length === 0) {
            $container.find('.valor-exibicao').show();
            $container.find('.input-edicao').hide();
            $container.find('.btn-salvar-produto, .btn-cancelar-produto, .btn-desfazer').hide();
            $container.find('.btn-editar-produto').show();
            return;
        }

        Swal.fire({
            title: 'Confirmar alterações?',
            text: `${updates.length} valor(es) será(ão) salvo(s)`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sim, salvar!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({ title: 'Salvando...', didOpen: () => Swal.showLoading() });

            $.ajax({
                url: '/sgi_erp/relatorios/atualizar-producao',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ updates: updates }),
                success: function (res) {
                    Swal.close();

                    if (res.success) {
                        $container.find('.celula-valor').each(function () {
                            const $cell = $(this);
                            const valorAtual = $cell.data('original') || 0;

                            const texto = valorAtual > 0
                                ? formatarTotal(valorAtual) // Usa a função dinâmica
                                : '-';

                            // VOLTA PARA LABEL
                            $cell.find('.valor-exibicao').text(texto).show();
                            $cell.find('.input-edicao').val('').hide();

                            // Atualiza data-id se for novo
                            if (($cell.data('id') || 0) == 0) {
                                const match = (res.novos_ids || []).find(n =>
                                    n.data === $cell.data('data') &&
                                    parseInt(n.func_id) === parseInt($cell.data('funcionario-id')) &&
                                    parseInt(n.tipo_id) === parseInt($cell.data('tipo-produto-id'))
                                );
                                if (match) {
                                    $cell.attr('data-id', match.new_id);
                                }
                            }
                        });

                        calcularTotais(); // Recalcula totais

                        // Restaura botões
                        $container.find('.btn-salvar-produto, .btn-cancelar-produto, .btn-desfazer').hide();
                        $container.find('.btn-editar-produto').show();

                        Swal.fire({
                            title: 'Sucesso!',
                            text: res.msg || 'Tudo salvo!',
                            icon: 'success',
                            timer: 1500
                        });

                    } else {
                        Swal.fire('Erro!', res.msg || 'Falha ao salvar.', 'error');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire('Erro de conexão', 'Tente novamente.', 'error');
                }
            });
        });
    });

    // EXCLUIR LINHA 
    $(document).on('click', '.btn-excluir-linha', function () {
        const $row = $(this).closest('tr');
        const ids = [];

        // Coleta IDs existentes na linha
        $row.find('.celula-valor[data-id]').each(function () {
            const id = parseInt($(this).data('id'));
            if (id > 0) ids.push(id);
        });

        if (ids.length === 0) {
            Swal.fire('Nada para excluir', 'Esta linha não tem dados salvos.', 'info');
            return;
        }

        Swal.fire({
            title: 'Excluir linha?',
            text: 'Isso removerá todos os valores desta linha!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sim, excluir!'
        }).then(result => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Excluindo...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: '/sgi_erp/relatorios/excluir-producao',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ ids: ids }),
                dataType: 'json',
                success: function (res) {
                    Swal.close();
                    if (res.success) {
                        $row.remove();
                        calcularTotais();
                        Swal.fire('Excluído!', res.msg || 'Linha removida.', 'success');
                    } else {
                        Swal.fire('Erro!', res.msg || 'Falha ao excluir.', 'error');
                    }
                },
                error: function () {
                    Swal.close();
                    Swal.fire('Erro de conexão', 'Tente novamente.', 'error');
                }
            });
        });
    });

    // RECALCULAR TOTAIS 
    window.calcularTotais = function () {
        let totalGeral = 0;

        // 1. ZERA TODOS OS TOTAIS VISÍVEIS (evita soma duplicada ao percorrer)
        $('.total-dia').text('');
        $('.total-funcionario').text('');
        $('#total-geral').text('');

        // 2. ATUALIZA TOTAL POR DIA (rodapé) + TOTAL POR FUNCIONÁRIO (linha principal)
        $('.funcionario-linha').each(function () {
            const $linhaFunc = $(this);
            let somaFuncionario = 0;

            // Percorre cada coluna de data
            $linhaFunc.find('td[data-data]').each(function (index) {
                const $celulaDia = $(this);
                const data = $celulaDia.data('data');
                let somaDia = 0;

                // Soma todos os valores editáveis dessa data (na linha de detalhes do mesmo funcionário)
                const $detalhes = $linhaFunc.next('.detalhes-linha');

                $detalhes.find(`.celula-valor[data-data="${data}"] .valor-exibicao`).each(function () {
                    const textoCompleto = $(this).text().trim();

                    // Remove 'R$ ' ou 'kg' para obter o número puro
                    let textoLimpo = textoCompleto.replace('R$', '').replace('kg', '').trim();

                    if (textoLimpo && textoLimpo !== '-') {
                        // Converte para float: remove ponto de milhar e troca vírgula por ponto.
                        const valor = parseFloat(textoLimpo.replace(/\./g, '').replace(',', '.')) || 0;
                        somaDia += valor;
                        somaFuncionario += valor;
                    }
                });

                // === ATUALIZA O TOTAL DO DIA NA LINHA DO FUNCIONÁRIO ===
                const formatoDia = somaDia > 0 ? formatarTotal(somaDia) : '';
                $celulaDia.text(formatoDia);
                if (somaDia > 0) $celulaDia.addClass('text-success fw-bold');
                else $celulaDia.removeClass('text-success fw-bold');

                // === ATUALIZA O TOTAL DO DIA NO RODAPÉ (ACÚMULO) ===
                const $totalDiaRodape = $('.total-dia').eq(index);

                // Le o valor atual do rodapé
                let textoRodape = $totalDiaRodape.text().replace('R$', '').replace('kg', '').trim();

                // Converte o valor atual do rodapé para float (limpando a formatação)
                let totalAtualRodape = parseFloat(textoRodape.replace(/\./g, '').replace(',', '.') || '0');

                // Soma o total do dia do funcionário atual
                totalAtualRodape += somaDia;

                // Escreve o valor acumulado, formatado novamente.
                $totalDiaRodape.text(formatarTotal(totalAtualRodape));
            });

            // === ATUALIZA TOTAL DO FUNCIONÁRIO (LINHA PRINCIPAL) ===
            const formatoFunc = formatarTotal(somaFuncionario);
            $linhaFunc.find('.total-funcionario').text(formatoFunc);

            // Acumula no total geral
            totalGeral += somaFuncionario;
        });

        // === ATUALIZA TOTAL GERAL (RODAPÉ FINAL) ===
        $('#total-geral').text(formatarTotal(totalGeral));
    };

    // Inicializa totais na carga da página
    calcularTotais();

    /**
     * Coleta os parâmetros de filtro e redireciona para a rota de impressão/exportação.
     * @param {string} formato - 'pdf' ou 'excel'
     */
    function acionarExportacao(formato) {
        const path = window.location.pathname;
        const tipoRelatorio = path.split('/').pop();

        // Coleta os valores de data (usando os inputs do filtro)
        const dataInicio = $('input[name="ini"]').val();
        const dataFim = $('input[name="fim"]').val();

        if (!dataInicio || !dataFim) {
            Swal.fire('Filtros obrigatórios', 'Selecione as datas de Início e Fim.', 'warning');
            return;
        }

        const url = `/sgi_erp/relatorios/imprimir?tipo=${tipoRelatorio}&ini=${dataInicio}&fim=${dataFim}&formato=${formato}`;

        // Abre em uma nova aba para não interromper a navegação
        window.open(url, '_blank');
    }

    // PDF & EXCEL
    $('#btn-pdf').off('click').on('click', function () {
        acionarExportacao('pdf');
    });

    $('#btn-excel').off('click').on('click', function () {
        acionarExportacao('excel');
    });
});