<?php
header('Content-Type: application/json');
require_once 'config_oracle.php';

// Ativar o report de erros para debug em desenvolvimento
// error_reporting(E_ALL);
// ini_set('display_errors', 1);


$num_ordem = filter_input(INPUT_GET, 'num_ordem', FILTER_VALIDATE_INT);
$cod_emp = filter_input(INPUT_GET, 'cod_emp', FILTER_VALIDATE_INT);
$response = ['success' => false, 'data' => [], 'error' => null];

// LOG: parâmetros recebidos
$logFile = __DIR__ . '/log_buscar_demandas.txt';
$logMsg = date('Y-m-d H:i:s') . " | Parâmetros recebidos: num_ordem={$num_ordem}, cod_emp={$cod_emp}\n";
file_put_contents($logFile, $logMsg, FILE_APPEND);


if (!$num_ordem || !$cod_emp) {
    $response['error'] = 'Número da Ordem e Código da Empresa são obrigatórios.';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ERRO: Parâmetros obrigatórios ausentes\n", FILE_APPEND);
    echo json_encode($response);
    exit;
}

$oracle = getOracleConnection();


if (!$oracle->connect()) {
    $response['error'] = 'Erro de conexão: ' . $oracle->getError();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ERRO: Erro de conexão: " . $oracle->getError() . "\n", FILE_APPEND);
    echo json_encode($response);
    exit;
}

// Query original restaurada
$sql = 'SELECT
    B.SEQ AS seq_operacao,
    C.DESCRICAO AS desc_operacao,
    T_DEM.COD_ITEM AS cod_item,
    T_DEM.DESC_TECNICA AS desc_tecnica,
    G.MASCARA AS mascara_demanda,
    E.QTDE AS qtde_neces,
    E.QTDE_REQUIS AS qtde_requis,
    E.QTDE_PENDENTE AS qtde_pendente,
    B.SEQ AS seq_demanda,
    C.DESCRICAO AS DESC_OPERACAO,
    E.ALMOX_ID AS almox_id,
    F.DESCRICAO AS desc_almoxarifado,
    NVL((
        SELECT SLD_ATUAL FROM (
            SELECT e2.SLD_ATUAL
            FROM focco3i.testq e2
            INNER JOIN focco3i.titens_estoque f2 ON e2.itestq_id = f2.id
            WHERE f2.cod_item = T_DEM.COD_ITEM AND e2.ALMOX_ID NOT IN (749, 2454)
            ORDER BY e2.DT_SIST DESC
        ) WHERE ROWNUM = 1
    ), 0) AS SALDO_ATUAL
FROM
    FOCCO3I.TORDENS A
    INNER JOIN FOCCO3I.TITENS_PLANEJAMENTO TP_ORDEM ON A.ITPL_ID = TP_ORDEM.ID
    INNER JOIN FOCCO3I.TITENS_EMPR TE_ORDEM ON TP_ORDEM.ITEMPR_ID = TE_ORDEM.ID
    INNER JOIN FOCCO3I.TITENS T_ORDEM ON TE_ORDEM.ITEM_ID = T_ORDEM.ID
    INNER JOIN FOCCO3I.TORDENS_ROT B ON A.ID = B.ORDEM_ID
    INNER JOIN FOCCO3I.TOPERACAO C ON B.OPERACAO_ID = C.ID
    INNER JOIN FOCCO3I.TORDENS_CON D ON B.ID = D.TORDEN_ROT_ID
    INNER JOIN FOCCO3I.TDEMANDAS E ON D.DEMANDA_ID = E.ID
    INNER JOIN FOCCO3I.TALMOXARIFADOS F ON E.ALMOX_ID = F.ID
    LEFT JOIN FOCCO3I.TMASC_ITEM G ON E.TMASC_ITEM_ID = G.ID
    INNER JOIN FOCCO3I.TITENS_PLANEJAMENTO TP_DEM ON E.ITPL_ID = TP_DEM.ID
    INNER JOIN FOCCO3I.TITENS_EMPR TE_DEM ON TP_DEM.ITEMPR_ID = TE_DEM.ID
    INNER JOIN FOCCO3I.TITENS T_DEM ON TE_DEM.ITEM_ID = T_DEM.ID
WHERE
    A.FINAL = 0
    AND E.ALMOX_ID IN (610, 630)
    AND A.NUM_ORDEM = :num_ordem
ORDER BY
    B.SEQ,
    T_DEM.COD_ITEM';

$params = [
    ':num_ordem' => $num_ordem
    
];


// LOG: SQL executado
file_put_contents($logFile, date('Y-m-d H:i:s') . " | SQL executado: $sql\nParâmetros: " . print_r($params, true) . "\n", FILE_APPEND);

$resultados = $oracle->executeQuery($sql, $params);


if ($resultados !== false) {
    $response['success'] = true;
    $response['data'] = $resultados;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | Resultados encontrados: " . count($resultados) . "\n", FILE_APPEND);
} else {
    $response['error'] = 'Erro ao buscar demandas: ' . $oracle->getError();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " | ERRO: Erro ao buscar demandas: " . $oracle->getError() . "\n", FILE_APPEND);
}

$oracle->close();

echo json_encode($response);
?>