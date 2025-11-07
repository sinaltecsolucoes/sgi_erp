// public/js/relatorios-interatividade.js (REFINADO - Corrige duplicação, totais e novos IDs)
$(document).ready(function () {
    let ultimoBackup = null; // Para "Desfazer"

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

    // INICIAR EDIÇÃO
    $(document).on('click', '.btn-editar-produto', function () {
        const $container = $(this).closest('.detalhes-linha');
        ultimoBackup = [];

        $container.find('.celula-valor').each(function () {
            const $cell = $(this);
            const texto = $cell.find('.valor-exibicao').text().trim();
            const num = texto === '-' || texto === '' ? 0 : parseFloat(texto.replace(/\./g, '').replace(',', '.')) || 0;

            ultimoBackup.push({
                id: $cell.data('id'),
                data: $cell.data('data'),
                original: num
            });

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
            const textoExibicao = u.original > 0 ? u.original.toLocaleString('pt-BR', { minimumFractionDigits: 3 }) : '-';
            $cell.find('.valor-exibicao').text(textoExibicao).show();
            $cell.find('.input-edicao').val('').hide();
        });

        calcularTotais();
        Swal.fire('Desfeito!', '', 'success');
    });

    $(document).on('click', '.btn-salvar-produto', function () {
        const $botaoSalvar = $(this); // ← GUARDA O BOTÃO
        const $container = $botaoSalvar.closest('.detalhes-linha'); // ← LINHA CORRETA
        const updates = [];

        $container.find('.celula-valor').each(function () {
            const $cell = $(this);
            const inputVal = $cell.find('.input-edicao').val().trim();
            let novoValor = 0;

            if (inputVal !== '' && inputVal !== '0,000') {
                novoValor = parseFloat(inputVal.replace(/\./g, '').replace(',', '.')) || 0;
            }

            const original = $cell.data('original') || 0;
            if (novoValor !== original) {
                updates.push({
                    id: $cell.data('id') || 0,
                    quantidade_kg: novoValor.toFixed(3),
                    data: $cell.data('data'),
                    funcionario_id: $cell.data('funcionario-id'),
                    tipo_produto_id: $cell.data('tipo-produto-id')
                });

                // Atualiza o data-original para evitar reenvio
                $cell.data('original', novoValor);
            }
        });

        if (updates.length === 0) {
            // Se nada mudou, só fecha a edição
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
                        // === USA O $container CORRETO (do botão original) ===
                        $container.find('.celula-valor').each(function () {
                            const $cell = $(this);
                            const valorAtual = $cell.data('original') || 0;
                            const texto = valorAtual > 0
                                ? parseFloat(valorAtual).toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })
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

                        // Recalcula totais
                        calcularTotais();

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

    // EXCLUIR LINHA (refinado: confirma e remove seletivamente)
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

    // RECALCULAR TOTAIS (REFINADO: Só soma células visíveis/não-zero + parse robusto)
    /*  window.calcularTotais = function () {
          let totalGeral = 0;
  
          // 1. Atualiza TOTAL POR FUNCIONÁRIO
          $('.funcionario-linha').each(function () {
              const $linhaFunc = $(this);
              const $detalhe = $linhaFunc.next('.detalhes-linha');
              let somaFuncionario = 0;
  
              // Pega todas as células visíveis do funcionário (na linha de detalhes)
              $detalhe.find('.celula-valor .valor-exibicao').each(function () {
                  const texto = $(this).text().trim();
                  if (texto !== '' && texto !== '-' && texto !== '0,000') {
                      const valor = parseFloat(texto.replace(/\./g, '').replace(',', '.')) || 0;
                      somaFuncionario += valor;
                  }
              });
  
              // Atualiza o total na linha do funcionário
              const $totalFunc = $linhaFunc.find('.total-funcionario');
              const formatoFunc = somaFuncionario.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
              $totalFunc.text(formatoFunc);
          });
  
          // 2. Atualiza TOTAL POR DIA (rodapé)
          $('.total-dia').each(function (index) {
              let somaDia = 0;
              $(`.celula-valor:nth-child(${index + 2}) .valor-exibicao`).each(function () {
                  const texto = $(this).text().trim();
                  if (texto !== '' && texto !== '-' && texto !== '0,000') {
                      const valor = parseFloat(texto.replace(/\./g, '').replace(',', '.')) || 0;
                      somaDia += valor;
                  }
              });
  
              const formatoDia = somaDia.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
              $(this).text(formatoDia);
              totalGeral += somaDia;
          });
  
          // 3. Atualiza TOTAL GERAL
          $('#total-geral').text(totalGeral.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }));
      }; */

    window.calcularTotais = function () {
        let totalGeral = 0;

        // 1. ZERA TODOS OS TOTAIS VISÍVEIS (evita soma duplicada)
        $('.total-dia').text('0,000');
        $('.total-funcionario').text('0,000');
        $('#total-geral').text('0,000');

        // 2. ATUALIZA TOTAL POR DIA (rodapé) + TOTAL POR FUNCIONÁRIO (linha principal)
        $('.funcionario-linha').each(function () {
            const $linhaFunc = $(this);
            const nomeFunc = $linhaFunc.find('td:first strong').text().trim();
            let somaFuncionario = 0;

            // Percorre cada coluna de data
            $linhaFunc.find('td[data-data]').each(function (index) {
                const $celulaDia = $(this);
                const data = $celulaDia.data('data');
                let somaDia = 0;

                // Soma todos os valores editáveis dessa data (na linha de detalhes do mesmo funcionário)
                const $detalhes = $linhaFunc.next('.detalhes-linha');
                $detalhes.find(`.celula-valor[data-data="${data}"] .valor-exibicao`).each(function () {
                    const texto = $(this).text().trim();
                    if (texto && texto !== '-' && texto !== '0,000') {
                        const valor = parseFloat(texto.replace(/\./g, '').replace(',', '.')) || 0;
                        somaDia += valor;
                        somaFuncionario += valor;
                    }
                });

                // === ATUALIZA O TOTAL DO DIA NA LINHA DO FUNCIONÁRIO ===
                const formatoDia = somaDia > 0
                    ? somaDia.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })
                    : '';
                $celulaDia.text(formatoDia);
                if (somaDia > 0) $celulaDia.addClass('text-success fw-bold');
                else $celulaDia.removeClass('text-success fw-bold');

                // === ATUALIZA O TOTAL DO DIA NO RODAPÉ ===
                const $totalDiaRodape = $('.total-dia').eq(index);
                let totalAtualRodape = parseFloat($totalDiaRodape.text().replace(/\./g, '').replace(',', '.') || '0');
                totalAtualRodape += somaDia;
                $totalDiaRodape.text(totalAtualRodape.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }));
            });

            // === ATUALIZA TOTAL DO FUNCIONÁRIO ===
            const formatoFunc = somaFuncionario.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
            $linhaFunc.find('.total-funcionario').text(formatoFunc);

            // Acumula no total geral
            totalGeral += somaFuncionario;
        });

        // === ATUALIZA TOTAL GERAL ===
        $('#total-geral').text(totalGeral.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 }));
    };

    // Inicializa totais na carga da página
    calcularTotais();

    // PDF & EXCEL (mantidos, assumindo funções existentes)
    $('#btn-pdf').click(() => gerarPDF());
    $('#btn-excel').click(() => exportarExcel());
});