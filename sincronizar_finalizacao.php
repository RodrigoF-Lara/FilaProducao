echo json_encode(['success' => true, 'message' => 'Sincronização concluída.']);
<?php
// sincronizar_finalizacao.php
// Sincroniza o campo Final da tabela historico_status com o status do Oracle

header('Content-Type: application/json');
require_once __DIR__ . '/SupabaseClient.php'; // Supabase REST client
require_once __DIR__ . '/Requisicao/config_oracle.php'; // Oracle connection

$response = ['success' => true, 'message' => 'Sincronização concluída.'];
try {
    $supabase = new SupabaseClient();
    $oracle = getOracleConnection();
    // Buscar pendências via REST
    $pendentes = $supabase->select('historico_status', 'id,numero_ordem,sequencia,Final&or=(Final.is.null,Final.neq.s)');
    if (!is_array($pendentes)) throw new Exception('Erro ao buscar pendências no Supabase.');
    foreach ($pendentes as $linha) {
        $num_ordem = $linha['numero_ordem'];
        $seq = $linha['sequencia'];
        $sql = "SELECT b.final FROM focco3i.tordens a
                INNER JOIN focco3i.tordens_rot b ON (b.ordem_id=a.id)
                WHERE a.num_ordem = :num_ordem AND b.seq = :seq";
        $params = [':num_ordem' => $num_ordem, ':seq' => $seq];
        if ($oracle->connect()) {
            $resultado = $oracle->executeQuery($sql, $params);
            if ($resultado && intval($resultado[0]['FINAL']) === 1) {
                $supabase->update('historico_status', ['Final' => 's'], 'id=eq.'.$linha['id']);
            }
        }
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Erro: ' . $e->getMessage();
}
echo json_encode($response);
