console.log('%c🚀 PPCP SCRIPT CARREGADO', 'color: #e74c3c; font-weight: bold; font-size: 14px;');

// ========== VERIFICAÇÃO DE AUTENTICAÇÃO ==========
(async function() {
    // Verificar se está logado no sessionStorage
    if (sessionStorage.getItem('logado') !== 'true') {
        window.location.href = 'login.html';
        return;
    }

    // Verificar sessão no servidor
    try {
        const sessionResponse = await fetch('check_session.php?api=true');
        if (!sessionResponse.ok) {
            sessionStorage.clear();
            window.location.href = 'login.html';
            return;
        }
    } catch (error) {
        console.warn('Não foi possível verificar a sessão do servidor:', error);
    }
})();

// Obter dados do usuário da sessão (com chaves corrigidas)
const usuarioLogado = sessionStorage.getItem('usuario');
const codigoFoccoUsuario = sessionStorage.getItem('codigo_focco');
const nomeCompletoUsuario = sessionStorage.getItem('nome_completo') || usuarioLogado;
const nivel = sessionStorage.getItem('nivel');

// Log de depuração para verificar os dados carregados
// Log simplificado: apenas erro de sessão

// Helper function to add new fields to each data entry
const addExtraFields = (item) => ({
    ...item,
    comentarios: item.comentarios || [],
    separacaoInfo: item.separacaoInfo || null,
    historicoStatus: item.historicoStatus || [],
    historicoMovimentacoes: item.historicoMovimentacoes || [],
    historicoSeparacao: item.historicoSeparacao || [],
    statusAtual: item.statusAtual || null,
});

// Estrutura de dados para cada setor (sem mocks)
const dadosSetores = {
    'mt1': {
        producao: { total: 0, dados: [] },
        separacao: { total: 0, dados: [] },
        componentes: { total: 0, dados: [] }
    },
    'mt2': { producao: {total:0, dados:[]}, separacao: {total:0, dados:[]}, componentes: {total:0, dados:[]} },
    'mt3': { producao: {total:0, dados:[]}, separacao: {total:0, dados:[]}, componentes: {total:0, dados:[]} },
    'mt4': { producao: {total:0, dados:[]}, separacao: {total:0, dados:[]}, componentes: {total:0, dados:[]} },
    'mt-final': { producao: {total:0, dados:[]}, separacao: {total:0, dados:[]}, componentes: {total:0, dados:[]} },
    'pintura': { producao: {total:0, dados:[]}, separacao: {total:0, dados:[]}, componentes: {total:0, dados:[]} },
    'qualidade': { producao: {total:0, dados:[]}, separacao: {total:0, dados:[]}, componentes: {total:0, dados:[]} }
};

// Aplica a estrutura de dados a todos os itens
Object.values(dadosSetores).forEach(setor => {
    Object.values(setor).forEach(tipo => {
        if (tipo.dados) {
            tipo.dados = tipo.dados.map(addExtraFields);
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
        // Event listener para troca de setor (menu lateral)
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', () => {
                const setor = item.getAttribute('data-menu');
                alternarVisaoSetor(setor);
            });
        });

        // Event listener para troca de aba (producao, separacao, componentes)
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                // Remove 'active' de todas as abas
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                // Remove 'active' de todos os conteúdos
                document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'));
                document.getElementById(`tab-${tab}`).classList.add('active');
            });
        });
    // ========== CONFIGURAÇÃO DO USUÁRIO ==========
    const userInfoEl = document.getElementById('user-info');
    const logoutBtn = document.getElementById('logout-btn');
    const usersBtn = document.getElementById('users-btn');

    // Exibe as informações do usuário no header
    if (userInfoEl) {
        userInfoEl.textContent = `${nomeCompletoUsuario} | ${codigoFoccoUsuario}`;
    }

    // Adiciona a funcionalidade de logout ao botão
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            try {
                // Tenta fazer o logout no servidor primeiro
                await fetch('logout.php', { method: 'POST' });
            } catch (error) {
                console.error('Falha ao comunicar com o script de logout, mas prosseguindo com o logout no cliente.', error);
            } finally {
                // Garante que a sessão do navegador seja limpa e redirecione
                sessionStorage.clear();
                window.location.href = 'login.html';
            }
        });
    }

    // Controla a visibilidade do botão de "Usuários" com base no nível de acesso
    if (usersBtn) {
        if (nivel === 'administrador') {
            usersBtn.style.display = 'inline-block';
        } else {
            usersBtn.style.display = 'none';
        }
    }

    // =================================================================================
    // INICIALIZAÇÃO
    // =================================================================================

    const menuItems = document.querySelectorAll('.menu-item');
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    const modalContainer = document.getElementById('modal-container');
    const modalTitle = document.getElementById('modal-title');
    const modalBody = document.getElementById('modal-body');
    const modalCloseBtn = document.getElementById('modal-close-btn');

    // =================================================================================
    // SISTEMA DE FILTRO GLOBAL
    // =================================================================================
    const filtroInput = document.getElementById('filtro-order');
    const btnLimparFiltro = document.getElementById('btn-limpar-filtro');
    const resultadoFiltro = document.getElementById('resultado-filtro');

    let filtroAtivo = '';
    let resultadosFiltro = [];

    /**
     * 🔍 Busca uma ordem em TODOS os setores
     */
    function buscarOrdemEmTodos(numeroOrdem) {
        const resultados = [];
        const numeroStr = String(numeroOrdem).trim().toLowerCase();

        Object.keys(dadosSetores).forEach(setor => {
            const setore = dadosSetores[setor];
            if (!setore) return;

            ['producao', 'separacao', 'componentes'].forEach(tipo => {
                if (setore[tipo] && setore[tipo].dados) {
                    setore[tipo].dados.forEach(item => {
                        if (String(item.ordem).toLowerCase().includes(numeroStr)) {
                            resultados.push({
                                setor: setor,
                                tipo: tipo,
                                item: item,
                                ordem: item.ordem,
                                sequencia: item.sequencia,
                                operacao: item.operacao,
                                codigo: item.codigo,
                                qnt: item.qnt,
                                cliente: item.cliente
                            });
                        }
                    });
                }
            });
        });

        return resultados;
    }

    /**
     * 📊 Agrupa resultados por número de ordem
     */
    function agruparResultadosPorOrdem(resultados) {
        const agrupado = {};

        resultados.forEach(res => {
            if (!agrupado[res.ordem]) {
                agrupado[res.ordem] = {
                    ordem: res.ordem,
                    setores: {},
                    resultados: [],
                    totalSetores: 0,
                    totalOcorrencias: 0
                };
            }

            if (!agrupado[res.ordem].setores[res.setor]) {
                agrupado[res.ordem].setores[res.setor] = [];
                agrupado[res.ordem].totalSetores++;
            }
            agrupado[res.ordem].setores[res.setor].push(res.tipo);

            agrupado[res.ordem].resultados.push(res);
            agrupado[res.ordem].totalOcorrencias++;
        });

        return Object.values(agrupado);
    }

    /**
     * 🎨 Renderiza resultado do filtro
     */
    function renderizarResultadoFiltro(agrupados) {
        if (agrupados.length === 0) {
            resultadoFiltro.innerHTML = '<div class="nenhum-resultado">❌ Nenhuma ordem encontrada</div>';
            return;
        }

        let html = `<div class="filtro-stats-header">📊 ${agrupados.length} ordem(ns) | ${agrupados.reduce((a, b) => a + b.totalOcorrencias, 0)} ocorrência(s)</div>`;

        agrupados.forEach(grupo => {
            const setoresStr = Object.keys(grupo.setores)
                .map(s => `<span class="badge-setor">${s.toUpperCase()}</span>`)
                .join('');

            const ocorrenciasHtml = grupo.resultados.map(res => {
                const tipoLabel = res.tipo === 'producao' ? 'Prod' : res.tipo === 'separacao' ? 'Sep' : 'Comp';
                return `
                    <div class="filtro-ocorrencia-item" onclick="irParaOcorrencia('${res.setor}', '${res.ordem}', '${res.sequencia}', '${res.operacao}')">
                        <div class="filtro-ocorrencia-main">
                            <span class="badge-setor">${res.setor.toUpperCase()}</span>
                            <span class="filtro-ocorrencia-tipo">${tipoLabel}</span>
                            <span class="filtro-ocorrencia-op">${res.operacao}</span>
                        </div>
                        <div class="filtro-ocorrencia-meta">Seq ${res.sequencia}</div>
                    </div>
                `;
            }).join('');

            html += `
                <div class="filtro-resultado-item">
                    <div class="filtro-info">
                        <div class="filtro-ordem" onclick="toggleFiltroOrdem('${grupo.ordem}')">🔧 ${grupo.ordem} <span class="filtro-toggle">(${grupo.totalOcorrencias} ocorr.)</span></div>
                        <div class="filtro-setores">${setoresStr}</div>
                        <div id="ocorrencias-${grupo.ordem}" class="filtro-ocorrencias" style="display: none;">
                            ${ocorrenciasHtml}
                        </div>
                    </div>
                </div>
            `;
        });

        resultadoFiltro.innerHTML = html;
    }

    window.toggleFiltroOrdem = function(numeroOrdem) {
        const container = document.getElementById(`ocorrencias-${numeroOrdem}`);
        if (!container) return;
        const aberto = container.style.display !== 'none';
        container.style.display = aberto ? 'none' : 'block';
    };

    window.irParaOcorrencia = function(setor, ordem, sequencia, operacao) {
        alternarVisaoSetor(setor);

        setTimeout(() => {
            const selector = `[data-ordem="${ordem}"][data-sequencia="${sequencia}"]`;
            const elemento = document.querySelector(selector);
            if (elemento) {
                elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
                elemento.classList.add('pulse-animation');
                setTimeout(() => elemento.classList.remove('pulse-animation'), 3000);
            }

            abrirAcoes(setor, ordem, sequencia, operacao);
        }, 100);
    };

    /**
     * 🎯 Navegação: Ir para a primeira ocorrência da ordem
     */
    window.irParaOrdem = function(numeroOrdem) {
        const resultados = buscarOrdemEmTodos(numeroOrdem);
        if (resultados.length === 0) return;

        const primeiro = resultados[0];
        
        // Mudar para o setor
        alternarVisaoSetor(primeiro.setor);
        
        // Esperar renderização
        setTimeout(() => {
            const selector = `[data-ordem="${primeiro.ordem}"][data-sequencia="${primeiro.sequencia}"]`;
            const elemento = document.querySelector(selector);
            if (elemento) {
                elemento.scrollIntoView({ behavior: 'smooth', block: 'center' });
                elemento.classList.add('pulse-animation');
                setTimeout(() => elemento.classList.remove('pulse-animation'), 3000);
            }
            
            abrirAcoes(primeiro.setor, primeiro.ordem, primeiro.sequencia, primeiro.operacao);
        }, 100);
    };

    /**
     * 🔄 Event listener do filtro
     */
    if (filtroInput) {
        filtroInput.addEventListener('input', (e) => {
            const valor = e.target.value.trim();
            filtroAtivo = valor;

            if (valor === '') {
                resultadoFiltro.style.display = 'none';
                btnLimparFiltro.style.display = 'none';
                return;
            }

            const resultados = buscarOrdemEmTodos(valor);
            resultadosFiltro = agruparResultadosPorOrdem(resultados);

            renderizarResultadoFiltro(resultadosFiltro);
            resultadoFiltro.style.display = 'block';
            btnLimparFiltro.style.display = 'inline-block';
        });
    }

    /**
     * 🗑️ Limpar filtro
     */
    if (btnLimparFiltro) {
        btnLimparFiltro.addEventListener('click', () => {
            filtroInput.value = '';
            filtroAtivo = '';
            resultadosFiltro = [];
            resultadoFiltro.style.display = 'none';
            btnLimparFiltro.style.display = 'none';
            filtroInput.focus();
        });
    }

    // =================================================================================
    // CONTROLE DO MODAL DE AÇÕES (Lógica consolidada)
    // =================================================================================
    
    // Função para abrir o modal genérico com conteúdo dinâmico
    const abrirModal = (title, content) => {
        if (modalContainer && modalTitle && modalBody) {
            modalTitle.innerHTML = title;
            modalBody.innerHTML = content;
            modalContainer.style.display = 'flex';
        }
    };
    
    // Função para fechar o modal genérico
    const fecharModal = () => {
        if (modalContainer) modalContainer.style.display = 'none';
    };

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', fecharModal);
    }
    
    window.onclick = function(event) {
        if (event.target == modalContainer) {
            fecharModal();
        }
    }

    const obterDataHoraAtual = () => {
        const agora = new Date();
        return `${agora.toLocaleDateString()} ${agora.toLocaleTimeString()}`;
    };

    const formatarComentario = (comentario) => {
        if (!comentario) return '';
        if (typeof comentario === 'string') return comentario;
        const data = comentario.data ? `(${comentario.data}) ` : '';
        const autor = comentario.colaborador ? `<strong>${comentario.colaborador}:</strong> ` : '';
        const texto = comentario.texto ? comentario.texto : String(comentario);
        return `${data}${autor}${texto}`;
    };

    const garantirStatusInicial = (item, tipoAtual) => {
        if (!item.statusAtual) {
            item.statusAtual = tipoAtual;
        }
        // Garante que historicoStatus seja um array
        item.historicoStatus = Array.isArray(item.historicoStatus) ? item.historicoStatus : [];
        if (item.historicoStatus.length === 0) {
            item.historicoStatus.push({
                de: null,
                para: item.statusAtual,
                data: obterDataHoraAtual(),
                motivo: 'Status inicial'
            });
        }
    };
    
    // --- FUNÇÕES DE AÇÃO GLOBAIS ---
    window.encontrarItemDetalhado = (setor, ordem, sequencia, operacao) => {
        const dados = dadosSetores[setor];
        if (!dados) return null;
        for (const tipo of ['producao', 'separacao', 'componentes']) {
            const item = dados[tipo].dados.find(i => 
                String(i.numero_ordem).trim() == String(ordem).trim() &&
                Number(i.sequencia) == Number(sequencia) &&
                String(i.operacao).trim() == String(operacao).trim()
            );
            if (item) return { item, tipo };
        }
        return null;
    };

    window.encontrarItem = (setor, ordem, sequencia, operacao) => {
        const resultado = encontrarItemDetalhado(setor, ordem, sequencia, operacao);
        return resultado ? resultado.item : null;
    };
    
    window.mudarStatus = async (setor, ordem, sequencia, operacao, novoStatus) => {
        console.log('[LOG mudarStatus] Início', { setor, ordem, sequencia, operacao, novoStatus });
        const info = encontrarItemDetalhado(setor, ordem, sequencia, operacao);
        if (!info) {
            console.error('[LOG mudarStatus] Não encontrou info detalhada', { setor, ordem, sequencia, operacao });
            return;
        }

        const { tipo: tipoAtual } = info;
        if (tipoAtual === novoStatus) {
            console.log('[LOG mudarStatus] Status já está no valor desejado', { tipoAtual, novoStatus });
            return; // Não faz nada se o status for o mesmo
        }

        // Garantia extra: setor e status_novo nunca vazios
        const setorCorrigido = setor || (info.item && info.item.setor) || 'indefinido';
        const statusNovoCorrigido = novoStatus || 'indefinido';

        // Trunca operacao para no máximo 100 caracteres
        const operacaoTrunc = operacao && operacao.length > 100 ? operacao.substring(0, 100) : operacao;
        const payload = {
            numero_ordem: ordem,
            sequencia: sequencia,
            operacao: operacaoTrunc,
            setor: setorCorrigido,
            status_anterior: tipoAtual,
            status_novo: statusNovoCorrigido
        };
        console.log('[LOG mudarStatus] Payload montado', payload);

        try {
            const resp = await fetch('status_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            console.log('[LOG mudarStatus] Resposta status_api.php', resp);
            if (!resp.ok) {
                const erro = await resp.text();
                console.error('[LOG mudarStatus] Erro HTTP', erro);
                alert('Erro ao mudar status: ' + erro);
                return;
            }
            fecharModal();
            await carregarDados();
            console.log('[LOG mudarStatus] Status alterado e dados recarregados');
        } catch (error) {
            console.error('[LOG mudarStatus] Exceção', error);
            alert('Erro ao mudar status: ' + error.message);
        }
    };

    window.confirmarMudancaStatus = (setor, ordem, sequencia, operacao) => {
        const statusSelect = document.getElementById('status-select');
        const novoStatus = statusSelect ? statusSelect.value : null;
        if (novoStatus) {
            mudarStatus(setor, ordem, sequencia, operacao, novoStatus);
        }
    };

    window.salvarComentario = async (setor, ordem, sequencia, operacao) => {
        const input = document.getElementById('novo-comentario');
        const texto = input ? input.value.trim() : '';
        
        if (!texto) {
            alert('Por favor, digite um comentário.');
            return;
        }

        const item = encontrarItem(setor, ordem, sequencia, operacao);
        if (!item) {
            alert('Erro: não foi possível encontrar os dados da ordem para salvar o log completo.');
            return;
        }

        try {
            await fetch('logs_producao_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    numero_ordem: ordem,
                    sequencia: sequencia,
                    operacao: operacao,
                    comentario: texto,
                    codigo: item.codigo,
                    cliente: item.cliente,
                    descricao: item.descricao || null // 'descricao' não existe nos dados de teste, mas é enviado se existir
                })
            });
            abrirAcoes(setor, ordem, sequencia, operacao);
        } catch (error) {
            alert(`Erro: ${error.message}`);
        }
    };

    window.salvarInicioSeparacao = async (setor, ordem, sequencia, operacao) => {
        const codigoOperador = prompt('Código Focco do operador:');
        if (!codigoOperador || !codigoOperador.trim()) return;

        try {
            await fetch('separacao_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    numero_ordem: ordem,
                    sequencia: sequencia,
                    operacao: operacao,
                    setor: setor,
                    acao: 'INICIO',
                    colaborador_codigo_focco_operador: codigoOperador.trim()
                })
            });
            fecharModal();
            await carregarDados();
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    };

    window.pausarSeparacao = async (setor, ordem, sequencia, operacao) => {
        try {
            await fetch('separacao_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ numero_ordem: ordem, sequencia: sequencia, operacao: operacao, setor: setor, acao: 'PAUSA' })
            });
            fecharModal();
            await carregarDados();
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    };

    window.retomarSeparacao = async (setor, ordem, sequencia, operacao) => {
        const codigoOperador = prompt('Código Focco do operador:');
        if (!codigoOperador || !codigoOperador.trim()) return;

        try {
            await fetch('separacao_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    numero_ordem: ordem,
                    sequencia: sequencia,
                    operacao: operacao,
                    setor: setor,
                    acao: 'RETOMADA',
                    colaborador_codigo_focco_operador: codigoOperador.trim()
                })
            });
            fecharModal();
            await carregarDados();
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    };

    window.finalizarSeparacao = async (setor, ordem, sequencia, operacao) => {
        if (!confirm(`Finalizar separação da ordem ${ordem}?`)) return;
        try {
            await fetch('separacao_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ numero_ordem: ordem, sequencia: sequencia, operacao: operacao, setor: setor, acao: 'FIM' })
            });
            if (confirm('Deseja mover para "Aguardando Produção"?')) {
                await mudarStatus(setor, ordem, sequencia, operacao, 'producao');
            } else {
                fecharModal();
                await carregarDados();
            }
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    };

    window.salvarMudancaSetor = async (setorOrigem, ordem, sequencia, operacao) => {
        const setorDestino = document.getElementById('setor-destino').value;
        if (!setorDestino) return;

        try {
            await fetch('movimentacao_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    numero_ordem: ordem,
                    sequencia: sequencia,
                    operacao: operacao,
                    setor_origem: setorOrigem,
                    setor_destino: setorDestino
                })
            });
            alert(`Ordem movida para ${setorDestino.toUpperCase()}!`);
            fecharModal();
            await carregarDados();
        } catch (error) {
            alert('Erro: ' + error.message);
        }
    };

    window.abrirAcoes = async (setor, ordem, sequencia, operacao) => {
        const info = encontrarItemDetalhado(setor, ordem, sequencia, operacao);
        if (!info) {
            console.error("Item não encontrado:", { setor, ordem, sequencia, operacao });
            return;
        }

        const { item, tipo } = info;
        
        try {
            // Função para buscar histórico: GET para APIs de histórico
            const fetchHistory = async (url, body) => {
                const historicoApis = [
                    'logs_producao_api.php',
                    'movimentacao_api.php',
                    'separacao_api.php',
                    'status_api.php'
                ];
                if (historicoApis.includes(url) && body.numero_ordem) {
                    // Para separacao_api.php, busca só por numero_ordem e sequencia
                    if (url === 'separacao_api.php') {
                        const params = new URLSearchParams({ numero_ordem: body.numero_ordem, sequencia: body.sequencia }).toString();
                        const response = await fetch(`${url}?${params}`, { method: 'GET' });
                        if (!response.ok) {
                            console.error(`Erro na resposta da API ${url}:`, await response.text());
                            throw new Error(`Falha na API ${url}`);
                        }
                        return response.json();
                    } else {
                        // Para as demais, mantém busca só por numero_ordem
                        const params = new URLSearchParams({ numero_ordem: body.numero_ordem }).toString();
                        const response = await fetch(`${url}?${params}`, { method: 'GET' });
                        if (!response.ok) {
                            console.error(`Erro na resposta da API ${url}:`, await response.text());
                            throw new Error(`Falha na API ${url}`);
                        }
                        return response.json();
                    }
                }
                // POST para criação
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                if (!response.ok) {
                    console.error(`Erro na resposta da API ${url}:`, await response.text());
                    throw new Error(`Falha na API ${url}`);
                }
                return response.json();
            };

            // Para histórico de separação, não envia operacao
            const bodyBase = { numero_ordem: ordem, sequencia: sequencia };
            const bodyOnlyNumero = { numero_ordem: ordem };

            const [resComentarios, resMovimentacoes, resSeparacao, resStatus] = await Promise.all([
                fetchHistory('logs_producao_api.php', bodyOnlyNumero),
                fetchHistory('movimentacao_api.php', bodyOnlyNumero),
                fetchHistory('separacao_api.php', bodyBase),
                fetchHistory('status_api.php', bodyOnlyNumero)
            ]);

            // Atribui os dados do histórico ao item ANTES de renderizar
            item.comentarios = resComentarios.success ? resComentarios.data.map(c => ({ 
                data: new Date(c.created_at).toLocaleString('pt-BR'),
                colaborador: c.colaborador,
                texto: c.comentario
            })) : [];
            item.historicoMovimentacoes = resMovimentacoes.success ? resMovimentacoes.data : [];
            item.historicoSeparacao = resSeparacao.success ? resSeparacao.data : [];
            item.historicoStatus = resStatus.success ? resStatus.data : [];

        } catch (error) {
            console.error(`Erro ao buscar históricos para a ordem ${ordem}:`, error);
            // Garante que os arrays existam mesmo com erro para não quebrar a UI
            item.comentarios = item.comentarios || [];
            item.historicoMovimentacoes = item.historicoMovimentacoes || [];
            item.historicoSeparacao = item.historicoSeparacao || [];
            item.historicoStatus = item.historicoStatus || [];
        }
        
        const statusLabels = {
            producao: 'Produção',
            separacao: 'Separação',
            componentes: 'Componentes'
        };

        const setoresDisponiveis = Object.keys(dadosSetores)
            .filter(s => s !== setor)
            .map(s => `<option value="${s}">${s.toUpperCase()}</option>`)
            .join('');
        
        const statusOptions = ['producao', 'separacao', 'componentes']
            .map(s => `<option value="${s}" ${s === tipo ? 'selected' : ''}>${statusLabels[s]}</option>`)
            .join('');

        const historicoStatusHtml = item.historicoStatus.length
            ? `<ul class="modal-list">${item.historicoStatus.map(h => {
                const dataFormatada = new Date(h.data_movimentacao).toLocaleString('pt-BR');
                const origem = h.status_anterior ? statusLabels[h.status_anterior] : 'Inicial';
                const destino = statusLabels[h.status_novo] || h.status_novo;
                return `<li>${dataFormatada}: ${origem} → ${destino} por <strong>${h.colaborador}</strong></li>`;
            }).join('')}</ul>`
            : '<p class="modal-muted">Sem histórico de status.</p>';

        const historicoMovHtml = item.historicoMovimentacoes.length
            ? `<ul class="modal-list">${item.historicoMovimentacoes.map(h => {
                return `<li>${new Date(h.data_movimentacao).toLocaleString('pt-BR')}: ${h.setor_origem.toUpperCase()} → ${h.setor_destino.toUpperCase()} por <strong>${h.colaborador}</strong></li>`;
            }).join('')}</ul>`
            : '<p class="modal-muted">Sem movimentações registradas.</p>';

        const historicoSeparacaoHtml = item.historicoSeparacao.length
            ? `<ul class="modal-list">${item.historicoSeparacao.map(h => {
                const dataFormatada = new Date(h.data_acao).toLocaleString('pt-BR');
                return `<li>${dataFormatada}: Ação <strong>${h.acao}</strong> por Focco: <strong>${h.colaborador_codigo_focco}</strong></li>`;
            }).join('')}</ul>`
            : '<p class="modal-muted">Sem registros de separação.</p>';

        const comentariosHtml = item.comentarios.length
            ? `<ul class="modal-list">${item.comentarios.map(c => `<li>${formatarComentario(c)}</li>`).join('')}</ul>`
            : '<p class="modal-muted">Nenhum comentário adicionado.</p>';
        
        let separacaoEstadoHtml = '';
        let separacaoAcoesHtml = '';
        
        if (tipo !== 'separacao') {
            separacaoEstadoHtml = '<p class="modal-muted">Mova para Separação para iniciar o processo.</p>';
        } else {
            // Lógica para determinar o estado atual da separação com base no histórico
            const ultimoEvento = item.historicoSeparacao[0]; // O mais recente
            if (!ultimoEvento || ultimoEvento.acao === 'FIM') {
                separacaoEstadoHtml = '<p class="modal-muted">Separação não iniciada.</p>';
                separacaoAcoesHtml = `<button class="modal-action-btn" onclick="salvarInicioSeparacao('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Iniciar separação</button>`;
            } else if (ultimoEvento.acao === 'INICIO' || ultimoEvento.acao === 'RETOMADA') {
                separacaoEstadoHtml = `<p><strong>Status:</strong> Em andamento por Focco: ${ultimoEvento.colaborador_codigo_focco}</p>`;
                separacaoAcoesHtml = `
                    <button class="modal-action-btn secondary" onclick="pausarSeparacao('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Pausar</button>
                    <button class="modal-action-btn" onclick="finalizarSeparacao('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Finalizar</button>
                `;
            } else if (ultimoEvento.acao === 'PAUSA') {
                separacaoEstadoHtml = `<p><strong>Status:</strong> Pausada por Focco: ${ultimoEvento.colaborador_codigo_focco}</p>`;
                separacaoAcoesHtml = `
                    <button class="modal-action-btn" onclick="retomarSeparacao('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Retomar</button>
                    <button class="modal-action-btn" onclick="finalizarSeparacao('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Finalizar</button>
                `;
            }
        }

        const content = `
            <div class="modal-summary">
                <div><strong>Ordem:</strong> ${item.numero_ordem}</div>
                <div><strong>Sequência:</strong> ${item.sequencia}</div>
                <div><strong>Operação:</strong> ${item.operacao}</div>
                <div><strong>Código:</strong> ${item.cod_item}</div>
                <div><strong>Cliente:</strong> ${item.cliente}</div>
                <div><strong>Quantidade:</strong> ${item.qtde}</div>
            </div>

            <div class="modal-section">
                <h3>Separação</h3>
                ${separacaoEstadoHtml}
                <div class="modal-actions-row">
                    ${separacaoAcoesHtml}
                </div>
                <div class="modal-subsection">
                    <h4>Histórico de separação</h4>
                    ${historicoSeparacaoHtml}
                </div>
            </div>

            <div class="modal-section">
                <h3>Comentários</h3>
                ${comentariosHtml}
                <div class="form-group">
                    <label for="novo-comentario">Novo comentário</label>
                    <textarea id="novo-comentario" rows="3" placeholder="Digite o comentário"></textarea>
                </div>
                <button class="modal-action-btn" onclick="salvarComentario('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Adicionar</button>
            </div>

            <div class="modal-section">
                <h3>Movimentações</h3>
                <div class="form-group">
                    <label for="setor-destino">Mover para outro setor</label>
                    <select id="setor-destino">${setoresDisponiveis}</select>
                </div>
                <button class="modal-action-btn" onclick="salvarMudancaSetor('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Mover ordem</button>
                <div class="modal-subsection">
                    <h4>Histórico de movimentações</h4>
                    ${historicoMovHtml}
                </div>
            </div>

            <div class="modal-section">
                <h3>Status</h3>
                <div class="form-group">
                    <label for="status-select">Atualizar status</label>
                    <select id="status-select">${statusOptions}</select>
                </div>
                <button class="modal-action-btn" onclick="confirmarMudancaStatus('${setor}', '${ordem}', ${item.sequencia}, '${item.operacao}')">Atualizar status</button>
                <div class="modal-subsection">
                    <h4>Linha do tempo</h4>
                    ${historicoStatusHtml}
                </div>
            </div>
        `;

        abrirModal(`Ações - Ordem ${ordem}`, content);
    };

    // --- ATUALIZAÇÃO DA INTERFACE ---
    // ========== FUNÇÕES AUXILIARES PARA ROTEAMENTO INTELIGENTE ==========
    
    /**
     * Garante que a UI tenha um item para 'SEM SETOR CADASTRADO'
     * se houver ordens que não correspondem a nenhum setor mapeado
     */
    async function garantirSetorSemCadastroUI() {
        const setorSemCadastro = 'setor não cadastrado';
        // Verifica se existe ordens nesse setor
        const temOrdens = (
            (dadosSetores[setorSemCadastro]?.producao?.dados?.length || 0) > 0 ||
            (dadosSetores[setorSemCadastro]?.separacao?.dados?.length || 0) > 0 ||
            (dadosSetores[setorSemCadastro]?.componentes?.dados?.length || 0) > 0
        );
        // Cria o menu se não existir e se houver ordens
        if (temOrdens && !document.querySelector(`.menu-item[data-menu="${setorSemCadastro}"]`)) {
            const novoMenuItem = document.createElement('button');
            novoMenuItem.className = 'menu-item';
            novoMenuItem.setAttribute('data-menu', setorSemCadastro);
            novoMenuItem.innerHTML = `
                <span class="menu-text">SETOR SEM CADASTRO</span>
                <span class="menu-badges">
                    <span class="menu-badge menu-badge-producao" data-setor="${setorSemCadastro}" data-tipo="producao">0</span>
                    <span class="menu-badge menu-badge-separacao" data-setor="${setorSemCadastro}" data-tipo="separacao">0</span>
                    <span class="menu-badge menu-badge-componentes" data-setor="${setorSemCadastro}" data-tipo="componentes">0</span>
                </span>
            `;
            // Adiciona como último item (abaixo de qualidade)
            const menu = document.querySelector('.menu');
            menu.appendChild(novoMenuItem);
            novoMenuItem.addEventListener('click', () => alternarVisaoSetor(setorSemCadastro));
        }
        // Cria a tab se não existir
        if (!document.getElementById(`tab-content-${setorSemCadastro}`)) {
            const novoTabContent = document.createElement('div');
            novoTabContent.id = `tab-content-${setorSemCadastro}`;
            novoTabContent.className = 'tab-content';
            novoTabContent.innerHTML = `
                <table class="dados-table">
                    <thead>
                        <tr><th>Data</th><th>Ordem</th><th>Sequência</th><th>Operação</th><th>Código</th><th>Qnt</th><th>Cliente</th><th>Ações</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            `;
            document.querySelector('.main-content').appendChild(novoTabContent);
        }
        // Força atualização dos contadores para o novo setor
        atualizarTodosOsContadores();
    }
    
    /**
     * Enriquece dados de ordens em separação com informações de colaborador e status
     */
    async function enriquecerDadosSeparacao() {
        try {
            // Buscar mapa de usuários
            const resUsuarios = await fetch('usuarios_api.php');
            const RUsuarios = await resUsuarios.json();
            const mapaUsuarios = RUsuarios.success
                ? new Map(RUsuarios.data.map(u => [u.codigo_focco, u.nome_completo]))
                : new Map();

            for (const setor of Object.keys(dadosSetores)) {
                if (setor === 'sem-setor-cadastrado') continue;
                const ordensEmSeparacao = dadosSetores[setor]['separacao'].dados;
                if (ordensEmSeparacao.length === 0) continue;

                // Garante que os campos estejam corretos
                const promises = ordensEmSeparacao.map(ordem => {
                    const numero_ordem = ordem.numero_ordem || ordem.ordem;
                    const sequencia = ordem.sequencia;
                    const params = new URLSearchParams({
                        numero_ordem: numero_ordem,
                        sequencia: sequencia
                    });
                    return fetch(`separacao_api.php?${params.toString()}`)
                        .then(res => res.json())
                        .catch(e => ({ success: false, error: e }));
                });

                const resultadosHistoricos = await Promise.all(promises);

                ordensEmSeparacao.forEach((ordem, index) => {
                    const numero_ordem = ordem.numero_ordem || ordem.ordem;
                    const sequencia = ordem.sequencia;
                    const historicoResult = resultadosHistoricos[index];
                    if (!historicoResult.success || !historicoResult.data || historicoResult.data.length === 0) {
                        return;
                    }

                    const historico = historicoResult.data;
                    const ultimoEvento = historico[0];

                    if (ultimoEvento.acao === 'FIM') {
                        return;
                    }

                    const ultimoFim = historico.find(e => e.acao === 'FIM');
                    const dataUltimoFim = ultimoFim ? new Date(ultimoFim.data_acao).getTime() : 0;
                    const eventoDeInicio = historico.find(e => e.acao === 'INICIO' && new Date(e.data_acao).getTime() > dataUltimoFim);

                    const statusMapeado = { 'INICIO': 'iniciada', 'RETOMADA': 'iniciada', 'PAUSA': 'pausada' };
                    const nomeColaborador = mapaUsuarios.get(ultimoEvento.colaborador_codigo_focco) || ultimoEvento.colaborador_codigo_focco;

                    ordem.separacaoInfo = {
                        status: statusMapeado[ultimoEvento.acao],
                        colaborador: nomeColaborador,
                        dataInicio: eventoDeInicio ? new Date(eventoDeInicio.data_acao).toLocaleString('pt-BR') : 'N/A',
                        debugHistorico: historico
                    };
                });
            }
        } catch (error) {
            // Erro silencioso
        }
    }
    
    /**
     * Atualiza todos os contadores (badges) dos menus e tabs
     */
    function atualizarTodosOsContadores() {
        Object.keys(dadosSetores).forEach(setor => {
            const dados = dadosSetores[setor];
            ['producao', 'separacao', 'componentes'].forEach(tipo => {
                const menuBadge = document.querySelector(`.menu-badge[data-setor="${setor}"][data-tipo="${tipo}"]`);
                if (menuBadge) menuBadge.textContent = dados[tipo].total || 0;
            });
        });
    }
    
    /**
     * Alterna a visualização para um setor específico
     */
    function alternarVisaoSetor(setor) {
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => item.classList.remove('active'));
        document.querySelector(`.menu-item[data-menu="${setor}"]`).classList.add('active');
        atualizarDados(setor);
    }

    function atualizarDados(setor) {
        // ...log removido...
        const dados = dadosSetores[setor];
        if (!dados) {
            // ...log removido...
            return;
        }

        ['producao', 'separacao', 'componentes'].forEach(tipo => {
            const tabBadge = document.querySelector(`.tab-btn[data-tab="${tipo}"] .tab-badge`);
            if (tabBadge) tabBadge.textContent = dados[tipo].total;
            const menuBadge = document.querySelector(`.menu-badge[data-setor="${setor}"][data-tipo="${tipo}"]`);
            if (menuBadge) menuBadge.textContent = dados[tipo].total;
        });

        const renderTabela = (tipo, tbodySelector) => {
            const tbody = document.querySelector(tbodySelector);
            if (!tbody) {
                // ...log removido...
                return;
            }
            const dadosDoTipo = dados[tipo].dados;

            tbody.innerHTML = dadosDoTipo.map(item => {
                garantirStatusInicial(item, tipo);
                let rowClass = '';
                let infoExtraHtml = '';

                // Corrige: usa separacaoInfo para mostrar colaborador e status
                if (tipo === 'separacao' && item.separacaoInfo) {
                    if (item.separacaoInfo.status === 'iniciada') {
                        rowClass = 'row-in-separation';
                        infoExtraHtml = `<div class="separacao-info">🔸 Separando: <strong>${item.separacaoInfo.colaborador}</strong><br><small>${item.separacaoInfo.dataInicio}</small></div>`;
                    } else if (item.separacaoInfo.status === 'pausada') {
                        rowClass = 'row-paused';
                        infoExtraHtml = `<div class="separacao-info">⏸️ Pausada por: <strong>${item.separacaoInfo.colaborador}</strong><br><small>${item.separacaoInfo.dataInicio}</small></div>`;
                    }
                }

                return `
                    <tr class="${rowClass}" data-ordem="${item.numero_ordem}" data-sequencia="${item.sequencia}" data-operacao="${item.operacao}">
                        <td>${item.dt_entrega}${infoExtraHtml}</td>
                        <td>${item.numero_ordem}</td>
                        <td>${item.sequencia}</td>
                        <td>${item.operacao}</td>
                        <td>${item.cod_item}</td>
                        <td>${item.qtde}</td>
                        <td>${item.cliente}</td>
                        <td><div class="row-actions">
                            <button class="action-btn" onclick="abrirAcoes('${setor}', '${item.numero_ordem}', ${item.sequencia || 0}, '${item.operacao}')">Ações</button>
                            <button class="action-btn secondary" onclick="abrirDemandas('${item.numero_ordem}', ${item.sequencia || 0})">Demandas</button>
                        </div></td>
                    </tr>
                `;
            }).join('');
        };
// ========== MODAL DE DEMANDAS ==========
window.abrirDemandas = async function(numero_ordem, sequencia) {
    abrirModal('Demandas da Ordem', '<div style="padding:20px;text-align:center;">Carregando demandas...</div>');
    try {
        const response = await fetch(`Requisicao/buscar_demandas.php?num_ordem=${numero_ordem}&cod_emp=1`);
        const result = await response.json();
        if (!result.success) {
            abrirModal('Demandas da Ordem', `<div style="color:red;padding:20px;">Erro ao buscar demandas: ${result.error || 'Erro desconhecido'}</div>`);
            return;
        }
        // Agrupar por sequência de operação
        const demandasPorSeq = {};
        result.data.forEach(demanda => {
            const seq = demanda.SEQ_DEMANDA || demanda.SEQ_OPERACAO || demanda.seq_operacao || 'Sem Seq';
            if (!demandasPorSeq[seq]) demandasPorSeq[seq] = { demandas: [], desc_operacao: demanda.DESC_OPERACAO || demanda.desc_operacao || '' };
            demandasPorSeq[seq].demandas.push(demanda);
        });
        let html = '';
        // Campo para código focco do requisitante
        html += `<div style="margin-bottom:18px;display:flex;align-items:center;gap:16px;">
            <label for="codigo-focco-requisitante" style="font-weight:bold;">Código Focco do requisitante:</label>
            <input id="codigo-focco-requisitante" type="text" style="padding:6px 12px;font-size:1em;border-radius:6px;border:1px solid #ccc;width:180px;" placeholder="(opcional)" />
            <span style="color:#888;font-size:0.95em;">Se vazio, será usado o código do usuário logado.</span>
        </div>`;
        Object.keys(demandasPorSeq).sort((a,b)=>parseInt(a)-parseInt(b)).forEach(seq => {
            const grupo = demandasPorSeq[seq];
            html += `
                <div style="margin:18px 0 6px 0;display:flex;align-items:center;gap:16px;">
                    <span style="font-weight:bold;font-size:1.1em;">Sequência de Operação: ${seq} - ${grupo.desc_operacao}</span>
                    <button class="modal-action-btn" style="padding:6px 14px;font-size:0.95em;" onclick="window.requisitarDemandas(${numero_ordem}, ${seq})">Requisitar todas demandas desta sequência</button>
                </div>
                <div style="overflow-x:auto;">
                <table class="dados-table demandas-table" style="width:98vw;max-width:1200px;margin-bottom:10px;border-spacing:0 6px;">
                    <thead>
                        <tr>
                            <th style="padding:8px 12px;min-width:120px;">Item</th>
                            <th style="padding:8px 12px;min-width:220px;">Descrição</th>
                            <th style="padding:8px 12px;min-width:90px;">Necessário</th>
                            <th style="padding:8px 12px;min-width:90px;">Requisitado</th>
                            <th style="padding:8px 12px;min-width:90px;">Pendente</th>
                            <th style="padding:8px 12px;min-width:90px;">Estoque</th>
                            <th style="padding:8px 12px;min-width:110px;">Status</th>
                            <th style="padding:8px 12px;min-width:110px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            grupo.demandas.forEach(demanda => {
                const pendente = parseFloat(demanda.QTDE_PENDENTE || 0);
                const status = pendente > 0 ? '<span style="color:#e74c3c;font-weight:bold;">Não Requisitada</span>' : '<span style="color:#27ae60;">OK</span>';
                html += `
                    <tr style="${pendente > 0 ? 'background:#fff3cd;' : ''}">
                        <td style="padding:8px 12px;">${demanda.COD_ITEM}</td>
                        <td style="padding:8px 12px;">${demanda.DESC_TECNICA || ''}</td>
                        <td style="padding:8px 12px;">${demanda.QTDE_NECES || 0}</td>
                        <td style="padding:8px 12px;">${demanda.QTDE_REQUIS || 0}</td>
                        <td style="padding:8px 12px;">${pendente}</td>
                        <td style="padding:8px 12px;">${typeof demanda.SALDO_ATUAL !== 'undefined' && demanda.SALDO_ATUAL !== null ? demanda.SALDO_ATUAL : 0}</td>
                        <td style="padding:8px 12px;">${status}</td>
                        <td style="padding:8px 12px;">
                            <button class="modal-action-btn" style="padding:4px 10px;font-size:0.9em;" onclick="window.requisitarDemandaLinha('${numero_ordem}', '${seq}', '${demanda.COD_ITEM}', '${demanda.QTDE_PENDENTE}', '${demanda.SEQ_DEMANDA}')" ${pendente === 0 ? 'disabled' : ''}>Requisitar</button>
                        </td>
                    </tr>
                `;
            });
            html += `</tbody></table></div>`;
        });
        // Requisitar demanda linha a linha
        window.requisitarDemandaLinha = async function(numero_ordem, seq, cod_item, qtde_pendente, seq_demanda) {
            if (parseFloat(qtde_pendente) === 0) {
                alert('Demanda já requisitada!');
                return;
            }
            // Log detalhado dos dados coletados e enviados
            const codFoccoInput = document.getElementById('codigo-focco-requisitante');
            const codFocco = codFoccoInput && codFoccoInput.value ? codFoccoInput.value : (sessionStorage.getItem('codigo_focco') || '');
            const dadosColetados = {
                numero_ordem_coletado: numero_ordem,
                seq_coletado: seq,
                cod_item_coletado: cod_item,
                qtde_pendente_coletado: qtde_pendente,
                cod_focco_coletado: codFocco,
                seq_demanda_coletado: seq_demanda
            };
            const dadosPost = {
                cod_emp: 1,
                num_ordem: numero_ordem,
                cod_item: (cod_item || '').trim().toUpperCase(),
                qtde: qtde_pendente,
                cod_func: codFocco,
                codigo_focco: codFocco
                // seq_demanda removido
            };
            const formData = new FormData();
            formData.append('cod_emp', 1);
            formData.append('num_ordem', numero_ordem);
            formData.append('cod_item', (cod_item || '').trim().toUpperCase());
            formData.append('qtde', qtde_pendente);
            formData.append('cod_func', codFocco);
            formData.append('codigo_focco', codFocco);
            // seq_demanda removido
            // ...existing code...
            const resp = await fetch('Requisicao/index.php', { method: 'POST', body: formData });
            const res = await resp.json();
            if (!res.success) {
                alert('Erro ao requisitar item ' + cod_item + ': ' + (res.message || 'Erro desconhecido'));
            } else {
                alert('Demanda requisitada com sucesso!');
                window.abrirDemandas(numero_ordem, seq);
            }
        };
        abrirModal(`Demandas da Ordem ${numero_ordem}`, html);
    } catch (error) {
        abrirModal('Demandas da Ordem', `<div style=\"color:red;padding:20px;\">Erro ao buscar demandas: ${error.message}</div>`);
    }
};

window.requisitarDemandas = async function(numero_ordem, sequencia) {
    const response = await fetch(`Requisicao/buscar_demandas.php?num_ordem=${numero_ordem}&cod_emp=1`);
    const result = await response.json();
    if (!result.success) {
        alert('Erro ao buscar demandas: ' + (result.error || 'Erro desconhecido'));
        return;
    }
    const demandasSeq = result.data.filter(d => parseInt(d.SEQ_DEMANDA) === parseInt(sequencia));
    if (demandasSeq.length === 0) {
        alert('Nenhuma demanda encontrada para esta sequência.');
        return;
    }
    const codFoccoInput = document.getElementById('codigo-focco-requisitante');
    const codFocco = codFoccoInput && codFoccoInput.value ? codFoccoInput.value : (sessionStorage.getItem('codigo_focco') || '');
    for (const demanda of demandasSeq) {
        if (parseFloat(demanda.QTDE_PENDENTE) > 0) {
            const formData = new FormData();
            formData.append('cod_emp', 1);
            formData.append('num_ordem', numero_ordem);
            formData.append('cod_item', demanda.COD_ITEM);
            formData.append('qtde', demanda.QTDE_PENDENTE);
            formData.append('cod_func', codFocco);
            formData.append('codigo_focco', codFocco);
            formData.append('seq_demanda', demanda.SEQ_DEMANDA);
            const resp = await fetch('Requisicao/index.php', { method: 'POST', body: formData });
            const res = await resp.json();
            if (!res.success) {
                alert('Erro ao requisitar item ' + demanda.COD_ITEM + ': ' + (res.message || 'Erro desconhecido'));
            }
        }
    }
    alert('Requisição de demandas enviada!');
    window.abrirDemandas(numero_ordem, sequencia);
};

        renderTabela('producao', '#tab-producao tbody');
        renderTabela('separacao', '#tab-separacao tbody');
        renderTabela('componentes', '#tab-componentes tbody');
        // ...log removido...
    }

    /**
     * Sincroniza situação das ordens com Oracle Focco
     * Atualiza data_finalizacao em historico_status para ordens finalizadas no Focco
     */
    async function sincronizarComFocco() {
        try {
            const response = await fetch('sincronizar_status_focco.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            });
            if (!response.ok) {
                console.error('❌ Erro HTTP:', response.status, response.statusText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            const result = await response.json();
            if (!result.success) {
                console.error('❌ Erro na sincronização:', result.message);
            }
            return result;
        } catch (error) {
            console.error('❌ Erro na sincronização:', error.message);
            return { success: false };
        }
    }

    /**
     * Função auxiliar para fazer requisições em lotes
     * Evita sobrecarregar a conexão HTTP (limite ~6-8 simultâneas por domínio)
     */
    async function fetchInBatches(items, batchSize, fetchFn) {
        const results = new Array(items.length);
        let processed = 0;
        
        for (let i = 0; i < items.length; i += batchSize) {
            const batch = items.slice(i, i + batchSize);
            const batchResults = await Promise.all(batch.map(async (item, idx) => {
                const result = await fetchFn(item);
                processed++;
                const percent = Math.round((processed / items.length) * 100);
                console.log(`⏳ Progresso: ${processed}/${items.length} (${percent}%)`);
                return { item, result, originalIdx: i + idx };
            }));
            
            batchResults.forEach(({ result, originalIdx }) => {
                results[originalIdx] = result;
            });
        }
        
        return results;
    }

    /**
     * Carrega dados com Roteamento Inteligente, priorizando movimentações manuais.
     */
    async function carregarDados() {
        console.log('🔄 Carregando dados com Roteamento Inteligente...');
        try {
            // 1. Limpar todos os dados existentes
            Object.keys(dadosSetores).forEach(setor => {
                Object.keys(dadosSetores[setor]).forEach(tipo => {
                    dadosSetores[setor][tipo].dados = [];
                    dadosSetores[setor][tipo].total = 0;
                });
            });

            // 2. Buscar as duas fontes de ordens
            const resComponentes = await fetch('console_ordens_api.php');
            const resultComponentes = await resComponentes.json();
            const ordensComponentes = (resultComponentes.success && resultComponentes.data) ? resultComponentes.data : [];

            const resHistorico = await fetch('status_api.php?setor=all');
            const resultHistorico = await resHistorico.json();
            const ordensEmProcesso = (resultHistorico.success && resultHistorico.data) ? resultHistorico.data : [];

            // Como a view 'ordens_pendentes' já exclui o que está no histórico, podemos simplesmente juntar as listas.
            const todasAsOrdens = [...ordensComponentes, ...ordensEmProcesso];

            // 3. Buscar o histórico de movimentações manuais (a fonte da verdade para o setor)
            const resMovimentacoes = await fetch('movimentacao_api.php?all=1');
            const resultMovimentacoes = await resMovimentacoes.json();
            const mapaMovimentacoes = new Map();
            if (resultMovimentacoes.success && resultMovimentacoes.data) {
                // A API retorna ordenado pela data mais recente, então o primeiro que encontrarmos para uma chave é o último.
                resultMovimentacoes.data.forEach(m => {
                    const chave = `${m.numero_ordem}-${m.sequencia}-${m.operacao}`;
                    if (!mapaMovimentacoes.has(chave)) {
                        mapaMovimentacoes.set(chave, m.setor_destino.toLowerCase().trim());
                    }
                });
            }

            // 4. Criar um mapa de status para as ordens que já estão em produção/separação
            const statusMap = new Map();
            ordensEmProcesso.forEach(s => {
                const chave = `${s.numero_ordem}-${s.sequencia}-${s.operacao}`;
                statusMap.set(chave, s.status_novo);
            });

            // 5. Roteamento: processar cada ordem para decidir seu setor e status final
            todasAsOrdens.forEach(ordem => {
                const chave = `${ordem.numero_ordem}-${ordem.sequencia}-${ordem.operacao}`;

                // Etapa A: Determinar o STATUS FINAL
                // Se a ordem estiver no mapa de status, ela está em 'producao' ou 'separacao'. Senão, 'componentes'.
                const statusAtual = statusMap.get(chave) || 'componentes';

                // Etapa B: Determinar o SETOR FINAL
                let setorDestino;
                // Prioridade 1: Verificar se houve uma movimentação manual para esta ordem.
                const setorMovidoManualmente = mapaMovimentacoes.get(chave);
                if (setorMovidoManualmente) {
                    setorDestino = setorMovidoManualmente;
                } else {
                    // Prioridade 2: Usar o setor original que veio da API.
                    setorDestino = ordem.setor ? ordem.setor.toLowerCase().trim() : null;
                }

                // Fallback: Se, por algum motivo, não houver setor, vai para 'sem cadastro'.
                if (!setorDestino) {
                    setorDestino = 'setor não cadastrado';
                }

                // Garantir que a estrutura do setor exista antes de adicionar dados
                if (!dadosSetores[setorDestino]) {
                    dadosSetores[setorDestino] = {
                        producao: { total: 0, dados: [] },
                        separacao: { total: 0, dados: [] },
                        componentes: { total: 0, dados: [] }
                    };
                }

                // Etapa C: Adicionar a ordem na lista correta
                dadosSetores[setorDestino][statusAtual].dados.push(addExtraFields(ordem));
            });

            // 6. Recalcular os totais e atualizar a interface
            Object.keys(dadosSetores).forEach(setor => {
                Object.keys(dadosSetores[setor]).forEach(tipo => {
                    dadosSetores[setor][tipo].total = dadosSetores[setor][tipo].dados.length;
                });
            });

            await garantirSetorSemCadastroUI();
            atualizarTodosOsContadores();
            const setorAtivo = document.querySelector('.menu-item.active')?.getAttribute('data-menu') || 'mt1';
            atualizarDados(setorAtivo);

            console.log('✅ Dados carregados com Roteamento Inteligente.');
        } catch (error) {
            console.error('❌ Erro ao carregar dados com roteamento:', error);
        }
    }

    // Inicialização
    console.log('⏳ Inicialização...');
    setTimeout(async () => {
        try {
            console.log('🔄 Carregando dados...');
            await carregarDados();
            await enriquecerDadosSeparacao();
            const setorAtivo = document.querySelector('.menu-item.active')?.getAttribute('data-menu') || 'mt1';
            atualizarDados(setorAtivo);
        } catch (error) {
            console.error('❌ Erro fatal na inicialização:', error.message);
        }
    }, 2000);
    
    // Configura sincronização automática a cada 15 minutos
    const intervaloSincronizacao = 15 * 60 * 1000; // 15 minutos em ms
    setInterval(async () => {
        console.log('🔄 Sincronização automática com Focco (intervalo de 15 min)');
        try {
            await sincronizarComFocco();
            await carregarDados();
            await enriquecerDadosSeparacao();
            const setorAtivo = document.querySelector('.menu-item.active')?.getAttribute('data-menu') || 'mt1';
            atualizarDados(setorAtivo);
        } catch (error) {
            console.error('❌ Erro na sincronização automática:', error);
        }
    }, intervaloSincronizacao);
});
