document.addEventListener('DOMContentLoaded', function () {
    let dadosOriginais = {};

    function aplicarMascaraKg(input) {
        let v = input.value.replace(/\D/g, '');
        if (v.length === 0) {
            input.value = '';
            return;
        }
        while (v.length < 4) v = '0' + v;
        v = v.replace(/(\d)(\d{3})$/, '$1,$2');
        v = v.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        input.value = v;
    }

    function atualizarTotalDia(funcionarioNome) {
        let total = 0;
        document.querySelectorAll(`tr[data-funcionario="${funcionarioNome}"]`).forEach(row => {
            const kgText = row.querySelector('.kg-view').textContent;
            const kg = parseFloat(kgText.replace(/\./g, '').replace(',', '.') || 0);
            total += kg;
        });
        const formato = total.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        document.querySelectorAll(`tr[data-funcionario="${funcionarioNome}"] .total-dia`)
            .forEach(el => el.textContent = formato + ' kg');
    }

    // === FORÇA O ESTADO CORRETO DOS BOTÕES NO CARREGAMENTO ===
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.dataset.action = 'editar';
        btn.textContent = 'Editar';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-success');
    });

    // === EVENTO DE CLIQUE COM DEBUG COMPLETO ===
    document.addEventListener('click', function (e) {
        const botaoEditar = e.target.closest('.btn-editar');
        const botaoCancelar = e.target.closest('.btn-cancelar');
        const botaoExcluir = e.target.closest('.btn-excluir');

        if (!botaoEditar && !botaoCancelar && !botaoExcluir) return;

        const tr = botaoEditar?.closest('tr') || botaoCancelar?.closest('tr') || botaoExcluir?.closest('tr');

        // ==============================================================
        // 1. EXCLUIR
        // ==============================================================
        if (botaoExcluir) {
            const id = tr.dataset.id;
            const funcionarioNome = tr.dataset.funcionario;

            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não pode ser desfeita!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/sgi_erp/producao/excluir', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                tr.remove();
                                atualizarTotalDia(funcionarioNome);
                                Swal.fire('Excluído!', 'Lançamento removido.', 'success');
                            } else {
                                Swal.fire('Erro', res.msg || 'Falha ao excluir', 'error');
                            }
                        })
                        .catch(() => Swal.fire('Erro', 'Erro de comunicação com o servidor', 'error'));
                }
            });
            return;
        }

        // ==============================================================
        // 2. ENTRAR NO MODO EDIÇÃO
        // ==============================================================
        if (botaoEditar && botaoEditar.dataset.action === 'editar') {
            dadosOriginais[tr.dataset.id] = {
                acao: tr.querySelector('.acao-view').textContent.trim(),
                produto: tr.querySelector('.produto-view').textContent.trim(),
                kg: tr.querySelector('.kg-view').textContent.trim(),
                inicio: tr.querySelector('.inicio-view').textContent.trim(),
                fim: tr.querySelector('.fim-view').textContent.trim()
            };

            tr.classList.add('modo-edicao');
            tr.querySelectorAll('.view-mode').forEach(el => el.classList.add('d-none'));
            tr.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('d-none'));
            tr.querySelector('.btn-cancelar').classList.remove('d-none');
            tr.querySelector('.btn-excluir').classList.add('d-none'); // esconde excluir

            botaoEditar.textContent = 'Salvar';
            botaoEditar.classList.remove('btn-success');
            botaoEditar.classList.add('btn-primary');
            botaoEditar.dataset.action = 'salvar';

            const inputKg = tr.querySelector('.kg-input');
            aplicarMascaraKg(inputKg);
            inputKg.addEventListener('input', () => aplicarMascaraKg(inputKg));
            inputKg.focus();

            return;
        }

        // ==============================================================
        // 3. SALVAR
        // ==============================================================
        if (botaoEditar && botaoEditar.dataset.action === 'salvar') {
            const dados = {
                id: tr.dataset.id,
                acao_id: tr.querySelector('.acao-select').value,
                tipo_produto_id: tr.querySelector('.produto-select').value,
                quantidade_kg: parseFloat(tr.querySelector('.kg-input').value.replace(/\./g, '').replace(',', '.') || 0),
                hora_inicio: tr.querySelector('.hora-inicio').value || null,
                hora_fim: tr.querySelector('.hora-fim').value || null
            };

            fetch('/sgi_erp/producao/salvar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Atualiza visualização
                        tr.querySelector('.acao-view').textContent = tr.querySelector('.acao-select option:checked').textContent;
                        tr.querySelector('.produto-view').textContent = tr.querySelector('.produto-select option:checked').textContent;
                        tr.querySelector('.kg-view').textContent = tr.querySelector('.kg-input').value || '0,000';
                        tr.querySelector('.inicio-view').textContent = tr.querySelector('.hora-inicio').value || '--:--';
                        tr.querySelector('.fim-view').textContent = tr.querySelector('.hora-fim').value || '--:--';

                        // Volta ao modo normal
                        tr.classList.remove('modo-edicao');
                        tr.querySelectorAll('.view-mode').forEach(el => el.classList.remove('d-none'));
                        tr.querySelectorAll('.edit-mode').forEach(el => el.classList.add('d-none'));
                        tr.querySelector('.btn-cancelar').classList.add('d-none');
                        tr.querySelector('.btn-excluir').classList.remove('d-none');

                        botaoEditar.textContent = 'Editar';
                        botaoEditar.classList.remove('btn-primary');
                        botaoEditar.classList.add('btn-success');
                        botaoEditar.dataset.action = 'editar';

                        atualizarTotalDia(tr.dataset.funcionario);
                        Swal.fire('Sucesso!', 'Salvo com sucesso.', 'success');
                    } else {
                        Swal.fire('Erro', res.msg || 'Erro ao salvar', 'error');
                    }
                })
                .catch(() => Swal.fire('Erro', 'Falha na comunicação com o servidor', 'error'));

            return;
        }

        // ==============================================================
        // 4. CANCELAR (agora funciona 100%)
        // ==============================================================
        if (botaoCancelar) {
            const orig = dadosOriginais[tr.dataset.id];
            if (orig) {
                tr.querySelector('.acao-view').textContent = orig.acao;
                tr.querySelector('.produto-view').textContent = orig.produto;
                tr.querySelector('.kg-view').textContent = orig.kg;
                tr.querySelector('.inicio-view').textContent = orig.inicio;
                tr.querySelector('.fim-view').textContent = orig.fim;
            }

            tr.classList.remove('modo-edicao');
            tr.querySelectorAll('.view-mode').forEach(el => el.classList.remove('d-none'));
            tr.querySelectorAll('.edit-mode').forEach(el => el.classList.add('d-none'));
            tr.querySelector('.btn-cancelar').classList.add('d-none');
            tr.querySelector('.btn-excluir').classList.remove('d-none'); // volta o excluir

            const btnEditar = tr.querySelector('.btn-editar');
            btnEditar.textContent = 'Editar';
            btnEditar.classList.remove('btn-primary');
            btnEditar.classList.add('btn-success');
            btnEditar.dataset.action = 'editar';

            delete dadosOriginais[tr.dataset.id];
            return;
        }
    });

    // ==============================================================
    // FILTRO EM TEMPO REAL POR NOME DO FUNCIONÁRIO
    // ==============================================================
    document.getElementById('filtro-funcionario')?.addEventListener('input', function () {
        const termo = this.value.trim().toLowerCase();
        const linhas = document.querySelectorAll('#tabela-producao tbody tr');

        linhas.forEach(tr => {
            const nomeSpan = tr.querySelector('.nome-funcionario');
            if (!nomeSpan) return;

            const nomeCompleto = nomeSpan.textContent;
            const nomeLower = nomeCompleto.toLowerCase();

            if (termo === '' || nomeLower.includes(termo)) {
                tr.style.display = '';
                tr.classList.remove('filtro-oculto');

                // Destaca o texto encontrado
                if (termo !== '') {
                    const regex = new RegExp(`(${termo.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
                    nomeSpan.innerHTML = nomeCompleto.replace(regex, '<mark>$1</mark>');
                } else {
                    nomeSpan.innerHTML = nomeCompleto; // remove destaque
                }
            } else {
                tr.style.display = 'none';
                tr.classList.add('filtro-oculto');
                nomeSpan.innerHTML = nomeCompleto; // limpa destaque
            }
        });

        // Quando limpar o campo, restaura os totais originais
        if (termo === '') {
            Object.keys(window.totaisIniciais).forEach(funcNome => {
                const valor = window.totaisIniciais[funcNome];
                const cells = document.querySelectorAll(`tr[data-funcionario="${funcNome}"] .total-dia`);
                cells.forEach(cell => {
                    cell.textContent = parseFloat(valor).toLocaleString('pt-BR', {
                        minimumFractionDigits: 3,
                        maximumFractionDigits: 3
                    }) + ' kg';
                });
            });
        }
    });
});