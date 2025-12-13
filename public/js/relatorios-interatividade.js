// public/js/relatorios-interatividade.js

$(document).ready(function () {

    // --- FUNÇÕES AUXILIARES ---

    function stringParaFloat(str) {
        if (!str || str === '-' || str.trim() === '') return 0;
        // Remove R$, kg e espaços
        let limpo = str.replace('R$', '').replace('kg', '').trim();
        // Remove pontos de milhar e troca vírgula decimal por ponto
        return parseFloat(limpo.replace(/\./g, '').replace(',', '.')) || 0;
    }

    function floatParaString(num) {
        if (num === 0) return '';
        const decimais = (typeof unidadeMedida !== 'undefined' && unidadeMedida === 'KG') ? 3 : 2;
        return num.toLocaleString('pt-BR', { minimumFractionDigits: decimais, maximumFractionDigits: decimais });
    }

    // --- LÓGICA DE CÁLCULO DE TOTAIS ---

    window.recalcularTotaisGeral = function () {
        let totalGeralzao = 0;

        // 1. Array para armazenar totais por dia (para o footer)
        let totaisPorDia = {};

        // 2. Loop pelos Funcionários (Linhas Pai)
        $('.funcionario-linha').each(function () {
            let $linhaPai = $(this);
            let parentId = $linhaPai.data('target'); // ex: func_1
            let totalFuncionario = 0;

            // Para cada dia deste funcionário...
            $linhaPai.find('.total-dia-func').each(function () {
                let $celulaPai = $(this);
                let dataDia = $celulaPai.data('data');
                let somaDiaFuncionario = 0;

                // Encontra todas as linhas filhas VISÍVEIS ou OCULTAS deste funcionário
                // E soma os valores das células correspondentes à data
                $(`.linha-filho.${parentId}`).each(function () {
                    let $linhaFilho = $(this);

                    // Procura a célula de valor correspondente a data
                    let $celulaFilho = $linhaFilho.find(`.celula-valor[data-data="${dataDia}"]`);

                    // Verifica se está em modo edição (pega do input) ou visualização (pega do span)
                    let valor = 0;
                    if ($celulaFilho.find('.input-edicao').is(':visible')) {
                        valor = stringParaFloat($celulaFilho.find('.input-edicao').val());
                    } else {
                        valor = stringParaFloat($celulaFilho.find('.valor-exibicao').text());
                    }

                    somaDiaFuncionario += valor;
                });

                // Atualiza a célula do Pai (Linha cinza)
                $celulaPai.text(somaDiaFuncionario > 0 ? floatParaString(somaDiaFuncionario) : '');
                if (somaDiaFuncionario > 0) $celulaPai.addClass('text-success');
                else $celulaPai.removeClass('text-success');

                // Acumula para o total do funcionário
                totalFuncionario += somaDiaFuncionario;

                // Acumula para o total do dia (Footer)
                if (!totaisPorDia[dataDia]) totaisPorDia[dataDia] = 0;
                totaisPorDia[dataDia] += somaDiaFuncionario;
            });

            // Atualiza Total Geral do Funcionário (Última coluna da linha cinza)
            $linhaPai.find('.total-funcionario-geral').text(floatParaString(totalFuncionario));
            totalGeralzao += totalFuncionario;
        });

        // 3. Atualiza o Footer (Total por Dia Geral)
        $('.total-dia-footer').each(function () {
            let data = $(this).data('data');
            let valor = totaisPorDia[data] || 0;
            $(this).text(floatParaString(valor));
        });

        // 4. Atualiza o Totalzão do Footer
        $('#total-geral-final').text(floatParaString(totalGeralzao));
    };

    // --- EVENTOS DE INTERFACE ---

    // 1. Expandir / Recolher
    $(document).on('click', '.funcionario-linha', function () {
        const targetId = $(this).data('target');
        const $linhasFilhas = $(`.linha-filho.${targetId}`);
        const $icon = $(this).find('.icon-expand');

        let estaoVisiveis = $linhasFilhas.first().is(':visible');

        if (estaoVisiveis) {
            $linhasFilhas.hide();
            $icon.removeClass('fa-minus-circle text-danger').addClass('fa-plus-circle');
        } else {
            $linhasFilhas.show(); // Mostra como table-row
            $icon.removeClass('fa-plus-circle').addClass('fa-minus-circle text-danger');
        }
    });

    // 2. Máscara de Input
    $(document).on('input', '.input-edicao', function () {
        let v = this.value.replace(/\D/g, '');
        v = v.replace(/(\d)(\d{3})$/, '$1,$2');
        v = v.replace(/(?=(\d{3})+(\D))\B/g, '.');
        this.value = v;

        // Opcional: Recalcular totais em tempo real enquanto digita?
        // recalcularTotaisGeral(); // Descomente se quiser cálculo live (pode pesar se tiver muitos dados)
    });

    // 3. Botão Editar
    $(document).on('click', '.btn-editar-linha', function () {
        const id = $(this).data('id');
        const $row = $(`#linha_${id}`);

        // Alterna Inputs e Spans
        $row.find('.valor-exibicao').hide();
        $row.find('.input-edicao').show();

        // Alterna Botões
        $row.find('.grupo-botoes-view').hide();
        $row.find('.grupo-botoes-edit').show();
    });

    // 4. Botão Cancelar
    $(document).on('click', '.btn-cancelar-linha', function () {
        const id = $(this).data('id');
        const $row = $(`#linha_${id}`);

        // Restaura valores originais nos inputs
        $row.find('.celula-valor').each(function () {
            let valOriginal = $(this).find('.valor-exibicao').text();
            let num = stringParaFloat(valOriginal);
            $(this).find('.input-edicao').val(num > 0 ? floatParaString(num) : '');
        });

        // Alterna visibilidade
        $row.find('.valor-exibicao').show();
        $row.find('.input-edicao').hide();
        $row.find('.grupo-botoes-view').show();
        $row.find('.grupo-botoes-edit').hide();

        // Garante que os totais voltem ao original (caso tenha editado sem salvar)
        recalcularTotaisGeral();
    });

    // 5. Botão Salvar (AJAX)
    $(document).on('click', '.btn-salvar-linha', function () {
        const id = $(this).data('id');
        const $row = $(`#linha_${id}`);
        let updates = [];

        console.clear(); // Limpa o console para facilitar
        console.log("=== INICIANDO SALVAMENTO ===");
        console.log("Linha HTML ID:", id);

        $row.find('.celula-valor').each(function () {
            let $cell = $(this);
            let valorInput = stringParaFloat($cell.find('.input-edicao').val());
            let valorOriginal = stringParaFloat($cell.find('.valor-exibicao').text());

            // Só processa se houve mudança ou se tem valor
            // (Para debug, vamos listar tudo que tem valor)
            if (valorInput > 0 || valorOriginal > 0) {
                let lancamentoId = $cell.attr('data-lancamento-id');

                console.log(`> Célula [${$cell.data('data')}]:`);
                console.log(`   - Valor Input: ${valorInput}`);
                console.log(`   - ID no HTML (data-lancamento-id): ${lancamentoId}`);
                console.log(`   - Func ID: ${$cell.attr('data-funcionario-id')}`);
                console.log(`   - Prod ID: ${$cell.attr('data-tipo-produto-id')}`);

                updates.push({
                    id: lancamentoId,
                    data: $cell.attr('data-data'),
                    funcionario_id: $cell.attr('data-funcionario-id'),
                    tipo_produto_id: $cell.attr('data-tipo-produto-id'),
                    quantidade_kg: valorInput.toFixed(3)
                });
            }
        });

        console.log("PAYLOAD JSON A SER ENVIADO:", JSON.stringify(updates));
        // Verificação de segurança
        if (updates.length > 0 && (!updates[0].funcionario_id || updates[0].funcionario_id == 0)) {
            Swal.fire('Erro Técnico', 'ID do funcionário inválido.', 'error');
            return;
        }

        Swal.fire({ title: 'Salvando...', didOpen: () => Swal.showLoading() });

        $.ajax({
            url: '/sgi_erp/relatorios/atualizar-producao',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ updates: updates }),
            success: function (res) {
                Swal.close();
                if (res.success) {
                    // Atualiza visualmente
                    $row.find('.celula-valor').each(function () {
                        let novoValor = stringParaFloat($(this).find('.input-edicao').val());
                        $(this).find('.valor-exibicao').text(novoValor > 0 ? floatParaString(novoValor) : '-');
                    });

                    // Se o PHP retornou novos IDs (para o que acabou de ser criado), atualizamos o HTML
                    // Isso evita duplicar se o usuário salvar 2 vezes seguidas sem recarregar a pág
                    if (res.novos_ids && res.novos_ids.length > 0) {
                        res.novos_ids.forEach(novo => {
                            // Procura a célula correspondente e atualiza o ID
                            let $celula = $row.find(`.celula-valor[data-data="${novo.data}"]`);
                            $celula.attr('data-lancamento-id', novo.new_id);
                        });
                    }

                    $row.find('.btn-cancelar-linha').trigger('click');
                    recalcularTotaisGeral();

                    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                    Toast.fire({ icon: 'success', title: 'Salvo com sucesso' });
                } else {
                    Swal.fire('Erro', res.msg || 'Erro ao salvar', 'error');
                }
            },
            error: function () {
                Swal.fire('Erro', 'Falha na comunicação com o servidor', 'error');
            }
        });
    });

    // 6. Botão Excluir
    $(document).on('click', '.btn-excluir-linha', function () {
        const id = $(this).data('id');
        const nomeProduto = $(this).data('nome'); // Corrigido para pegar nome
        const $row = $(`#linha_${id}`);

        // Array para guardar os IDs de banco (producao.id) que vamos apagar
        let idsParaDeletar = [];

        // Varre todas as células da linha
        $row.find('.celula-valor').each(function () {
            // Pega o ID que colocamos no PHP (data-lancamento-id)
            let lancamentoId = $(this).attr('data-lancamento-id');

            // Só adiciona se for um ID válido (maior que 0)
            if (lancamentoId && parseInt(lancamentoId) > 0) {
                idsParaDeletar.push(parseInt(lancamentoId));
            }
        });

        // Se não achou nenhum ID (linha visualmente preenchida mas sem IDs, ou linha vazia)
        if (idsParaDeletar.length === 0) {
            Swal.fire('Aviso', 'Não há lançamentos salvos nesta linha para excluir.', 'info');
            return;
        }

        Swal.fire({
            title: 'Excluir?',
            text: `Deseja apagar todos os lançamentos de ${nomeProduto} desta linha?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sim, excluir'
        }).then((result) => {
            if (result.isConfirmed) {

                // Debug
                console.log("Enviando IDs para exclusão:", idsParaDeletar);

                $.ajax({
                    url: '/sgi_erp/relatorios/excluir-producao', // Rota correta que você informou
                    type: 'POST',
                    contentType: 'application/json',
                    // Envia exatamente o formato que o Controller espera: { ids: [...] }
                    data: JSON.stringify({ ids: idsParaDeletar }),
                    success: function (res) {
                        if (res.success) {
                            $row.remove(); // Remove a linha da tela
                            recalcularTotaisGeral(); // Atualiza os totais
                            Swal.fire('Excluído!', res.msg || 'Registros removidos.', 'success');
                        } else {
                            Swal.fire('Erro', res.msg || 'Não foi possível excluir.', 'error');
                        }
                    },
                    error: function (xhr) {
                        console.error(xhr);
                        Swal.fire('Erro', 'Falha técnica ao excluir.', 'error');
                    }
                });
            }
        });
    });

    // Inicialização
    // Garante que os totais estejam corretos na carga (caso o PHP tenha vindo zerado ou cache)
    // recalcularTotaisGeral(); 
});