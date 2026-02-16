export function abrirModalAdicionarOrdem() {
    // ...função modal...


// Disponibiliza a função no escopo global para uso no index.html
if (typeof window !== 'undefined') {
    window.abrirModalAdicionarOrdem = abrirModalAdicionarOrdem;
}
    // Cria o overlay do modal
    let overlay = document.createElement('div');
    overlay.id = 'modal-adicionar-ordem-overlay';
    overlay.style = `
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(0,0,0,0.4); z-index: 9999; display: flex; align-items: center; justify-content: center;
    `;

    // Cria o conteúdo do modal
    let modal = document.createElement('div');
    modal.id = 'modal-adicionar-ordem';
    modal.style = `
        background: #fff; border-radius: 12px; padding: 36px 32px; min-width: 340px; box-shadow: 0 8px 32px #0002;
        display: flex; flex-direction: column; align-items: center;
    `;
    modal.innerHTML = `
        <h2 style="margin-bottom:24px;">Adicionar Ordem Manualmente</h2>
        <label for="input-numero-ordem" style="font-size:1.1em;margin-bottom:8px;">Número da Ordem</label>
        <input id="input-numero-ordem" type="number" style="padding:10px 16px;font-size:1.2em;border-radius:8px;border:1px solid #ccc;width:220px;margin-bottom:24px;" autofocus />
        <div id="dados-ordem-preenchidos" style="display:none;width:100%;margin-bottom:16px;"></div>
        <div style="display:flex;gap:16px;">
            <button id="btn-continuar-adicionar-ordem" style="background:#1976d2;color:#fff;font-size:1.1em;padding:12px 32px;border:none;border-radius:8px;cursor:pointer;">Continuar</button>
            <button id="btn-cancelar-adicionar-ordem" style="background:#eee;color:#333;font-size:1.1em;padding:12px 32px;border:none;border-radius:8px;cursor:pointer;">Cancelar</button>
        </div>
    `;

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // Fechar modal
    document.getElementById('btn-cancelar-adicionar-ordem').onclick = () => overlay.remove();

    // Continuar (próxima etapa)
    document.getElementById('btn-continuar-adicionar-ordem').onclick = async () => {
        const numeroOrdem = document.getElementById('input-numero-ordem').value.trim();
        if (!numeroOrdem) {
            alert('Digite o número da ordem.');
            return;
        }
        // Busca os dados da ordem
        const btn = document.getElementById('btn-continuar-adicionar-ordem');
        btn.disabled = true;
        btn.textContent = 'Buscando...';
        try {
            const resp = await fetch(`buscar_ordem_manual.php?num_ordem=${encodeURIComponent(numeroOrdem)}`);
            const json = await resp.json();
            if (!json.success) {
                alert(json.message || 'Erro ao buscar ordem.');
                btn.disabled = false;
                btn.textContent = 'Continuar';
                return;
            }
            // Mostra todas as operações e permite escolher setor para cada uma
            const dadosArray = json.dados;
            let html = '';
            dadosArray.forEach((dados, idx) => {
                html += `<div style="border:1px solid #ccc;padding:12px 8px;margin-bottom:10px;border-radius:8px;">
                    <b>Operação:</b> ${dados.OPERACAO}<br>
                    <b>Sequência:</b> ${dados.SEQUENCIA}<br>
                    <b>Cliente:</b> ${dados.CLIENTE}<br>
                    <b>Data entrega:</b> ${dados.DT_ENTREGA}<br>
                    <b>Qtd:</b> ${dados.QTDE}<br>
                    <b>Cód. Item:</b> ${dados.COD_ITEM}<br>
                    <label for="select-setor-${idx}" style="margin-top:8px;">Setor destino:</label>
                    <select id="select-setor-${idx}" style="padding:6px 10px;font-size:1em;border-radius:6px;border:1px solid #ccc;margin-bottom:4px;">
                        <option value="">Selecione...</option>
                        <option value="mt1">MT1</option>
                        <option value="mt2">MT2</option>
                        <option value="mt3">MT3</option>
                        <option value="mt4">MT4</option>
                        <option value="mt_final">MT FINAL</option>
                        <option value="pintura">PINTURA</option>
                        <option value="qualidade">QUALIDADE</option>
                        <option value="nao_salvar">Não Salvar</option>
                    </select>
                </div>`;
            });
            html += `<div style="text-align:center;margin-top:8px;"><button id="btn-salvar-todas-operacoes" style="background:#1976d2;color:#fff;font-size:1.1em;padding:12px 32px;border:none;border-radius:8px;cursor:pointer;">Salvar Todas</button>
            <button id="btn-cancelar-adicionar-ordem" style="background:#eee;color:#333;font-size:1.1em;padding:12px 32px;border:none;border-radius:8px;cursor:pointer;margin-left:12px;">Cancelar</button></div>`;
            const divDados = document.getElementById('dados-ordem-preenchidos');
            divDados.innerHTML = html;
            divDados.style.display = 'block';
            btn.style.display = 'none';

            // Cancelar
            document.getElementById('btn-cancelar-adicionar-ordem').onclick = () => overlay.remove();

            // Salvar todas as operações
            document.getElementById('btn-salvar-todas-operacoes').onclick = async () => {
                // Pega nome do usuário logado do sessionStorage
                const nomeUsuario = sessionStorage.getItem('nome_completo') || sessionStorage.getItem('usuario') || 'Colaborador logado';
                // Monta array de payloads, ignorando as operações marcadas como 'Não Salvar' ou não selecionadas
                const payloads = dadosArray.map((dados, idx) => {
                    const setor = document.getElementById(`select-setor-${idx}`).value;
                    if (!setor || setor === 'nao_salvar') return null;
                    return {
                        numero_ordem: dados.NUMERO_ORDEM,
                        sequencia: dados.SEQUENCIA,
                        operacao: dados.OPERACAO,
                        setor: setor,
                        status_anterior: dados.STATUS_ANTERIOR,
                        status_novo: dados.STATUS_NOVO,
                        posicao_fila: dados.POSICAO_FILA || dados.DT_ENTREGA,
                        cliente: dados.CLIENTE,
                        cod_item: dados.COD_ITEM,
                        qtde: dados.QTDE,
                        dt_entrega: dados.DT_ENTREGA,
                        colaborador: nomeUsuario
                    };
                }).filter(Boolean);
                if (payloads.length === 0) {
                    alert('Selecione ao menos um setor para salvar.');
                    return;
                }
                document.getElementById('btn-salvar-todas-operacoes').disabled = true;
                document.getElementById('btn-salvar-todas-operacoes').textContent = 'Salvando...';
                try {
                    // Salva todas as operações em paralelo
                    const results = await Promise.all(payloads.map(payload =>
                        fetch('status_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        }).then(r => r.json())
                    ));
                    if (results.some(r => !r.success)) {
                        alert('Erro ao salvar uma ou mais operações.');
                        document.getElementById('btn-salvar-todas-operacoes').disabled = false;
                        document.getElementById('btn-salvar-todas-operacoes').textContent = 'Salvar Todas';
                        return;
                    }
                    alert('Todas as operações selecionadas foram salvas com sucesso!');
                    overlay.remove();
                    window.location.reload();
                } catch (e) {
                    alert('Erro ao salvar operações: ' + e.message);
                    document.getElementById('btn-salvar-todas-operacoes').disabled = false;
                    document.getElementById('btn-salvar-todas-operacoes').textContent = 'Salvar Todas';
                }
            };
        } catch (e) {
            alert('Erro ao buscar ordem: ' + e.message);
            btn.disabled = false;
            btn.textContent = 'Continuar';
        }
    };
}
