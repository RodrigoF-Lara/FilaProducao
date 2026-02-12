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
        $select_fields = 'numero_ordem:num_ordem,sequencia:primeira_seq_pendente,operacao:operacao_pend,setor:setor_atual,cliente,cod_item,qtde,dt_entrega';
        $query = "select={$select_fields}&order=dt_entrega.desc&limit={$limit}&offset={$offset}";
        $lote = $supabase->select('ordens_pendentes', $query);
        if (!$lote || count($lote) === 0) break;
        $ordens = array_merge($ordens, $lote);
        if (count($lote) < $limit) break;
        $offset += $limit;
    }

    // Ordenar por dt_entrega original (ISO) antes de formatar
    usort($ordens, function($a, $b) {
        return strtotime($a['dt_entrega']) - strtotime($b['dt_entrega']);
    });

    // Agora formate a data para dd/mm
    foreach ($ordens as &$ordem) {
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
