
<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/Requisicao/config_oracle.php';

$response = ['success' => false, 'message' => '', 'dados' => []];

$num_ordem = $_GET['num_ordem'] ?? '';
if (!$num_ordem) {
    $response['message'] = 'Número da ordem não informado.';
    echo json_encode($response);
    exit;
}

try {
    $oracle = getOracleConnection();
    if (!$oracle->connect()) {
        $response['message'] = 'Erro ao conectar ao Oracle: ' . $oracle->getError();
        echo json_encode($response);
        exit;
    }

    // LOG: conexão ok
    file_put_contents(__DIR__ . '/log_buscar_ordem_manual.txt', date('Y-m-d H:i:s') . " | Conectado ao Oracle para ordem {$num_ordem}\n", FILE_APPEND);

    $sql = "
        SELECT 
            a.num_ordem as numero_ordem,
            b.seq as sequencia,
            c.descricao as operacao,
            '' as setor,
            'manual' as status_anterior,
            'componentes' as status_novo,
            :colaborador as colaborador,
            TO_CHAR(a.dt_final, 'YYYY-MM-DD') || ' 12:00:00' as posicao_fila,
            'Projetos/Dispositivos' as cliente,
            h.cod_item as cod_item,
            a.qtde as qtde,
            TO_CHAR(a.dt_final, 'YYYY-MM-DD') as dt_entrega
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
    $params = [
        ':num_ordem' => $num_ordem,
        ':colaborador' => $_SESSION['nome_completo'] ?? 'Colaborador logado'
    ];

    // LOG: query e params
    file_put_contents(__DIR__ . '/log_buscar_ordem_manual.txt', date('Y-m-d H:i:s') . " | SQL: $sql | Params: " . json_encode($params) . "\n", FILE_APPEND);

    $result = $oracle->executeQuery($sql, $params);

    if (!$result || count($result) === 0) {
        $response['message'] = 'Ordem não encontrada.';
        echo json_encode($response);
        exit;
    }

    $response['success'] = true;
    $response['dados'] = $result;

} catch (Exception $e) {
    $response['message'] = 'Erro: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
