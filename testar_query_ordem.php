<?php
// testar_query_ordem.php
header('Content-Type: application/json');
require_once __DIR__ . '/Requisicao/config_oracle.php';

$num_ordem = isset($_GET['num_ordem']) ? $_GET['num_ordem'] : '18254562';

try {
    $oracle = getOracleConnection();
    $conn = $oracle->connect();
    if (!$conn) throw new Exception($oracle->getError());

    $sql = "
        SELECT 
            a.num_ordem as numero_ordem,
            b.seq as sequencia,
            c.descricao as operacao,
            '' as setor,
            'manual' as status_anterior,
            'componentes' as status_novo,
            'Colaborador logado' as colaborador,
            a.dt_final as posicao_fila,
            'Projetos/Dispositivos' as cliente,
            h.cod_item as cod_item,
            a.qtde as qtde,
            a.dt_final as dt_entrega
        FROM focco3i.tordens a
        INNER JOIN focco3i.tordens_rot b ON (b.ordem_id=a.id)
        INNER JOIN focco3i.toperacao c ON (c.ID=b.OPERACAO_ID)
        CROSS APPLY (
            SELECT d.maquina_id, e.cod_maquina, e.descricao AS desc_maquina
            FROM focco3i.tord_rot_fab_maq d 
            INNER JOIN focco3i.tmaquinas e ON (e.id=d.maquina_id)
            WHERE d.torden_rot_id = b.id 
            ORDER BY d.maquina_id 
            FETCH FIRST 1 ROW ONLY
        ) de
        INNER JOIN focco3i.titens_planejamento g on (g.id=a.itpl_id) 
        INNER JOIN focco3i.titens h on (h.cod_item=g.cod_item) 
        WHERE a.num_ordem = :num_ordem
        ORDER BY b.seq
    ";
    $params = [':num_ordem' => $num_ordem];
    $result = $oracle->executeQuery($sql, $params);

    echo json_encode(['success' => true, 'dados' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
