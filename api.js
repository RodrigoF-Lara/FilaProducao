
// =================================================================================
// MÓDULO DE API
// Este arquivo centraliza todas as chamadas de rede (fetch) da aplicação.
// =================================================================================

/**
 * Verifica se a sessão do usuário no servidor ainda é válida.
 * Redireciona para o login se não for.
 */
export async function verificarSessao() {
    try {
        const sessionResponse = await fetch('check_session.php?api=true');
        if (!sessionResponse.ok) {
            sessionStorage.clear();
            window.location.href = 'login.html';
            return false;
        }
        return true;
    } catch (error) {
        console.warn('Não foi possível verificar a sessão do servidor:', error);
        // Em caso de falha de rede, podemos decidir se o usuário continua ou não.
        // Por segurança, vamos considerar como falha.
        return false;
    }
}

/**
 * Realiza o logout do usuário, limpando a sessão no servidor e no navegador.
 */
export async function fazerLogout() {
    try {
        await fetch('logout.php', { method: 'POST' });
    } catch (error) {
        console.error('Falha ao comunicar com o script de logout, mas prosseguindo com o logout no cliente.', error);
    } finally {
        sessionStorage.clear();
        window.location.href = 'login.html';
    }
}


/**
 * Atualiza o status de uma ordem (producao, separacao, componentes).
 */
export async function mudarStatus(ordem, sequencia, operacao, setor, tipoAtual, novoStatus, posicaoFila = null) {
    const operacaoTrunc = operacao && operacao.length > 100 ? operacao.substring(0, 100) : operacao;
    const payload = {
        numero_ordem: ordem,
        sequencia: sequencia,
        operacao: operacaoTrunc,
        setor: setor,
        status_anterior: tipoAtual,
        status_novo: novoStatus,
        posicao_fila: posicaoFila || new Date().toISOString()
    };

    const resp = await fetch('status_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (!resp.ok) {
        const erro = await resp.text();
        throw new Error('Erro ao mudar status: ' + erro);
    }
    return await resp.json();
}

/**
 * Salva um novo comentário para uma ordem.
 */
export async function salvarComentario(ordem, sequencia, operacao, texto, item) {
    const payload = {
        numero_ordem: ordem,
        sequencia: sequencia,
        operacao: operacao,
        comentario: texto,
        codigo: item.codigo,
        cliente: item.cliente,
        descricao: item.descricao || null
    };

    const resp = await fetch('logs_producao_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (!resp.ok) {
        throw new Error('Erro ao salvar comentário.');
    }
    return await resp.json();
}

/**
 * Gerencia as ações de separação (INICIO, PAUSA, RETOMADA, FIM).
 */
export async function gerenciarSeparacao(acao, setor, ordem, sequencia, operacao, codigoOperador = null) {
    const payload = {
        numero_ordem: ordem,
        sequencia: sequencia,
        operacao: operacao,
        setor: setor,
        acao: acao,
        colaborador_codigo_focco_operador: codigoOperador
    };

    const resp = await fetch('separacao_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (!resp.ok) {
        throw new Error(`Erro ao executar a ação ${acao}.`);
    }
    return await resp.json();
}


/**
 * Move uma ordem para um novo setor.
 */
export async function moverOrdemParaSetor(setorOrigem, ordem, sequencia, operacao, setorDestino) {
    const payload = {
        numero_ordem: ordem,
        sequencia: sequencia,
        operacao: operacao,
        setor_origem: setorOrigem,
        setor_destino: setorDestino
    };

    const resp = await fetch('movimentacao_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    if (!resp.ok) {
        throw new Error('Erro ao mover a ordem.');
    }
    return await resp.json();
}

/**
 * Busca todos os históricos (comentários, movimentações, etc.) de uma ordem.
 */
export async function buscarHistoricos(numero_ordem, sequencia) {
    const [resComentarios, resMovimentacoes, resSeparacao, resStatus] = await Promise.all([
        fetch(`logs_producao_api.php?numero_ordem=${numero_ordem}`).then(r => r.json()),
        fetch(`movimentacao_api.php?numero_ordem=${numero_ordem}`).then(r => r.json()),
        fetch(`separacao_api.php?numero_ordem=${numero_ordem}&sequencia=${sequencia}`).then(r => r.json()),
        fetch(`status_api.php?numero_ordem=${numero_ordem}`).then(r => r.json())
    ]);

    return {
        comentarios: resComentarios.success ? resComentarios.data.map(c => ({
            data: new Date(c.created_at).toLocaleString('pt-BR'),
            colaborador: c.colaborador,
            texto: c.comentario
        })) : [],
        movimentacoes: resMovimentacoes.success ? resMovimentacoes.data : [],
        separacao: resSeparacao.success ? resSeparacao.data : [],
        status: resStatus.success ? resStatus.data : []
    };
}

/**
 * Salva a nova data/hora da posição na fila para uma linha específica.
 */
export async function salvarPosicaoFila(id, novaDataISO) {
    const resp = await fetch('status_api.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: id,
            posicao_fila: novaDataISO,
        })
    });
    
    if (!resp.ok) {
        const erro = await resp.text();
        throw new Error('Erro ao salvar posição na fila: ' + erro);
    }
    return await resp.json();
}

/**
 * Busca o endereço de um item no Supabase.
 */
export async function buscarEnderecoSupabase(codigo_item) {
    const SUPABASE_URL = "https://ysvqatplclssljhicfkw.supabase.co";
    const SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InlzdnFhdHBsY2xzc2xqaGljZmt3Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzA0MDM3OTEsImV4cCI6MjA4NTk3OTc5MX0.EMBnO1YB2iJbbtK3RSGiTu-o9njryiJ4SYeYikVg6f8";
    try {
        const response = await fetch(`${SUPABASE_URL}/rest/v1/estoque?codigo_item=eq.${encodeURIComponent(codigo_item)}`, {
            headers: {
                'apikey': SUPABASE_KEY,
                'Authorization': `Bearer ${SUPABASE_KEY}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        if (data.length > 0) {
            const { estante, prateleira, posicao } = data[0];
            return `Estante: <b>${estante}</b><br>Prateleira: <b>${prateleira}</b><br>Posição: <b>${posicao}</b>`;
        } else {
            return '<span style="color:#888">Endereço não cadastrado</span>';
        }
    } catch (e) {
        return '<span style="color:#e74c3c">Erro ao buscar endereço</span>';
    }
}


/**
 * Busca as demandas de uma ordem específica.
 */
export async function buscarDemandas(numero_ordem) {
    const response = await fetch(`Requisicao/buscar_demandas.php?num_ordem=${numero_ordem}&cod_emp=1`);
    const result = await response.json();
    if (!result.success) {
        throw new Error(result.error || 'Erro desconhecido ao buscar demandas.');
    }
    return result.data;
}

/**
 * Requisita uma única demanda (linha).
 */
export async function requisitarDemanda(numero_ordem, cod_item, qtde_pendente, codFocco) {
    const formData = new FormData();
    formData.append('cod_emp', 1);
    formData.append('num_ordem', numero_ordem);
    formData.append('cod_item', (cod_item || '').trim().toUpperCase());
    formData.append('qtde', qtde_pendente);
    formData.append('cod_func', codFocco);
    formData.append('codigo_focco', codFocco);

    const resp = await fetch('Requisicao/index.php', { method: 'POST', body: formData });
    const res = await resp.json();
    if (!res.success) {
        throw new Error(res.message || 'Erro desconhecido ao requisitar item.');
    }
    return res;
}

/**
 * Requisita todas as demandas de uma sequência.
 */
export async function requisitarDemandasDaSequencia(numero_ordem, sequencia, codFocco) {
    const todasDemandas = await buscarDemandas(numero_ordem);
    const demandasSeq = todasDemandas.filter(d => parseInt(d.SEQ_DEMANDA) === parseInt(sequencia));

    if (demandasSeq.length === 0) {
        throw new Error('Nenhuma demanda encontrada para esta sequência.');
    }

    const resultados = [];
    for (const demanda of demandasSeq) {
        if (parseFloat(demanda.QTDE_PENDENTE) > 0) {
            try {
                const res = await requisitarDemanda(numero_ordem, demanda.COD_ITEM, demanda.QTDE_PENDENTE, codFocco);
                resultados.push({ item: demanda.COD_ITEM, success: true, response: res });
            } catch (error) {
                resultados.push({ item: demanda.COD_ITEM, success: false, error: error.message });
                // Decide se para em caso de erro ou continua
            }
        }
    }
    return resultados;
}

/**
 * Sincroniza a situação das ordens com o Oracle Focco.
 */
export async function sincronizarComFocco() {
    try {
        const response = await fetch('sincronizar_status_focco.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({})
        });
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const result = await response.json();
        if (!result.success) {
            console.error('❌ Erro na sincronização Focco:', result.message);
        }
        return result;
    } catch (error) {
        console.error('❌ Erro na sincronização Focco:', error.message);
        return { success: false, message: error.message };
    }
}

/**
 * Carrega todos os dados iniciais da aplicação.
 */
export async function carregarDadosIniciais() {
    const [resComponentes, resHistorico, resMovimentacoes] = await Promise.all([
        fetch('console_ordens_api.php').then(r => r.json()),
        fetch('status_api.php?setor=all').then(r => r.json()),
        fetch('movimentacao_api.php?all=1').then(r => r.json())
    ]);

    const ordensComponentes = (resComponentes.success && resComponentes.data) ? resComponentes.data : [];
    const ordensEmProcesso = (resHistorico.success && resHistorico.data) ? resHistorico.data : [];
    
    const mapaMovimentacoes = new Map();
    if (resMovimentacoes.success && resMovimentacoes.data) {
        resMovimentacoes.data.forEach(m => {
            const chave = `${m.numero_ordem}-${m.sequencia}-${m.operacao}`;
            if (!mapaMovimentacoes.has(chave)) {
                mapaMovimentacoes.set(chave, m.setor_destino.toLowerCase().trim());
            }
        });
    }

    return { ordensComponentes, ordensEmProcesso, mapaMovimentacoes };
}

/**
 * Busca dados de separação para enriquecer a UI.
 */
export async function buscarDadosSeparacao(ordensEmSeparacao) {
     try {
        const resUsuarios = await fetch('usuarios_api.php');
        const rUsuarios = await resUsuarios.json();
        const mapaUsuarios = rUsuarios.success
            ? new Map(rUsuarios.data.map(u => [u.codigo_focco, u.nome_completo]))
            : new Map();

        const promises = ordensEmSeparacao.map(ordem => {
            const params = new URLSearchParams({
                numero_ordem: ordem.numero_ordem || ordem.ordem,
                sequencia: ordem.sequencia
            });
            return fetch(`separacao_api.php?${params.toString()}`)
                .then(res => res.json())
                .catch(e => ({ success: false, error: e, numero_ordem: ordem.numero_ordem }));
        });

        const resultadosHistoricos = await Promise.all(promises);

        return { mapaUsuarios, resultadosHistoricos };

    } catch (error) {
        console.error("Erro ao buscar dados de separação", error);
        return { mapaUsuarios: new Map(), resultadosHistoricos: [] };
    }
}
