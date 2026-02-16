<?php
// console_ordens_api.php
// Endpoint para buscar dados da tabela console_ordens no Supabase

header('Content-Type: application/json');

require_once 'SupabaseClient.php'; // Certifique-se de que este arquivo existe e está configurado

try {
    // Instanciar o cliente Supabase
    $supabase = new SupabaseClient();

    // Buscar todos os dados da tabela console_ordens em lotes de 1000
    $ordens = [];
    $limit = 1000;
    $offset = 0;
    while (true) {
        // Sintaxe de alias correta para PostgREST (Supabase): 'novo_nome:nome_original'
        // Inclui o campo posicao_fila (timestampz) no select
        $select_fields = 'id,numero_ordem:num_ordem,sequencia:primeira_seq_pendente,operacao:operacao_pend,setor:setor_atual,cliente,cod_item,qtde,dt_entrega,posicao_fila';
        // Ordena por posicao_fila se disponível, senão por dt_entrega
        $query = "select={$select_fields}&order=posicao_fila.desc.nullslast,dt_entrega.desc&limit={$limit}&offset={$offset}";
        $lote = $supabase->select('ordens_pendentes', $query);
        if (!$lote || count($lote) === 0) break;
        $ordens = array_merge($ordens, $lote);
        if (count($lote) < $limit) break;
        $offset += $limit;
    }

    // Ordenar por posicao_fila (timestampz) se existir, senão por dt_entrega
    usort($ordens, function($a, $b) {
        $a_data = !empty($a['posicao_fila']) ? strtotime($a['posicao_fila']) : strtotime($a['dt_entrega'] ?? '');
        $b_data = !empty($b['posicao_fila']) ? strtotime($b['posicao_fila']) : strtotime($b['dt_entrega'] ?? '');
        return $a_data - $b_data;
    });

    // Não formata posicao_fila aqui! Deixe o frontend formatar (mantém ISO do banco)
    foreach ($ordens as &$ordem) {
        // Apenas dt_entrega pode ser formatada para compatibilidade visual antiga
        if (!empty($ordem['dt_entrega'])) {
            $data = date_create($ordem['dt_entrega']);
            if ($data) {
                $ordem['dt_entrega'] = date_format($data, 'd/m');
            }
        }
    }
    unset($ordem);

    // Retornar resposta JSON
    echo json_encode([
        'success' => true,
        'data' => $ordens
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar dados: ' . $e->getMessage()
    ]);
}
