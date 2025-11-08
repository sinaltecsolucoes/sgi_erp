document.addEventListener('DOMContentLoaded', function () {
    let dadosOriginais = {};

    function aplicarMascaraKg(input) {
        let v = input.value.replace(/\D/g, '');
        if (!v) return;
        v = v.replace(/(\d)(\d{3})$/, '$1,$2');
        v = v.replace(/(?=(\d{3})+(\D))\B/g, '.');
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

    document.addEventListener('click', function (e) {
        const btnEditar = e.target.closest('.btn-editar');
        const btnCancelar = e.target.closest('.btn-cancelar');
        const tr = e.target.closest('tr');
        if (!tr) return;

        if (btnEditar) {
            if (btnEditar.textContent.trim() === 'Editar') {
                // Salva os valores originais para cancelar
                dadosOriginais[tr.dataset.id] = {
                    acao: tr.querySelector('.acao-view').textContent,
                    produto: tr.querySelector('.produto-view').textContent,
                    kg: tr.querySelector('.kg-view').textContent,
                    inicio: tr.querySelector('.inicio-view').textContent,
                    fim: tr.querySelector('.fim-view').textContent
                };

                // Mostra os campos de edição
                tr.querySelectorAll('.view-mode').forEach(el => el.classList.add('d-none'));
                tr.querySelectorAll('.edit-mode').forEach(el => el.classList.remove('d-none'));

                btnEditar.innerHTML = 'Salvar';
                btnEditar.classList.remove('btn-success');
                btnEditar.classList.add('btn-primary');
                tr.querySelector('.btn-cancelar').classList.remove('d-none');

                // Máscara do KG
                const kgInput = tr.querySelector('.kg-input');
                aplicarMascaraKg(kgInput);
                kgInput.addEventListener('input', () => aplicarMascaraKg(kgInput));

            } else {
                // SALVAR
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
                            // Atualiza os textos visíveis
                            tr.querySelector('.acao-view').textContent = tr.querySelector('.acao-select option:checked').textContent;
                            tr.querySelector('.produto-view').textContent = tr.querySelector('.produto-select option:checked').textContent;
                            tr.querySelector('.kg-view').textContent = tr.querySelector('.kg-input').value;
                            tr.querySelector('.inicio-view').textContent = tr.querySelector('.hora-inicio').value || '--:--';
                            tr.querySelector('.fim-view').textContent = tr.querySelector('.hora-fim').value || '--:--';

                            // Volta pro modo visualização
                            tr.querySelectorAll('.view-mode').forEach(el => el.classList.remove('d-none'));
                            tr.querySelectorAll('.edit-mode').forEach(el => el.classList.add('d-none'));
                            btnEditar.innerHTML = 'Editar';
                            btnEditar.classList.remove('btn-primary');
                            btnEditar.classList.add('btn-success');
                            tr.querySelector('.btn-cancelar').classList.add('d-none');

                            atualizarTotalDia(tr.dataset.funcionario);
                            Swal.fire('Salvo!', 'Alterações gravadas com sucesso.', 'success');
                            tr.style.backgroundColor = '#d4edda';
                            setTimeout(() => tr.style.backgroundColor = '', 1000);
                        } else {
                            Swal.fire('Erro', res.msg || 'Não foi possível salvar.', 'error');
                        }
                    });
            }
        }

        if (btnCancelar) {
            const orig = dadosOriginais[tr.dataset.id];
            if (orig) {
                tr.querySelector('.acao-view').textContent = orig.acao;
                tr.querySelector('.produto-view').textContent = orig.produto;
                tr.querySelector('.kg-view').textContent = orig.kg;
                tr.querySelector('.inicio-view').textContent = orig.inicio;
                tr.querySelector('.fim-view').textContent = orig.fim;
            }

            tr.querySelectorAll('.view-mode').forEach(el => el.classList.remove('d-none'));
            tr.querySelectorAll('.edit-mode').forEach(el => el.classList.add('d-none'));
            tr.querySelector('.btn-editar').innerHTML = 'Editar';
            tr.querySelector('.btn-editar').classList.remove('btn-primary');
            tr.querySelector('.btn-editar').classList.add('btn-success');
            tr.querySelector('.btn-cancelar').classList.add('d-none');
        }
    });
});