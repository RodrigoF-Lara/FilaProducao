<?php
require_once 'SupabaseClient.php';

$supabase = new SupabaseClient();

echo "<h2>Testando Query Supabase - data_finalizacao IS NULL</h2>";

// TESTE 1: Query com is.null
echo "<h3>❌ TESTE 1: is.null (REST API)</h3>";
$query1 = "data_finalizacao=is.null&select=id,numero_ordem,sequencia,operacao,setor&limit=100";
$resultado1 = $supabase->select('historico_status', $query1);
echo "<pre>" . json_encode($resultado1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// TESTE 2: Todos os registros
echo "<h3>✅ TESTE 2: Todos os registros</h3>";
$query2 = "select=id,numero_ordem,sequencia,operacao,setor,data_finalizacao&limit=100";
$resultado2 = $supabase->select('historico_status', $query2);
echo "<pre>";
echo "Total de registros: " . count($resultado2) . "\n";
foreach (array_slice($resultado2, 0, 5) as $row) {
    echo "ID: {$row['id']}, Ordem: {$row['numero_ordem']}, Seq: {$row['sequencia']}, Op: {$row['operacao']}, data_fin: {$row['data_finalizacao']}\n";
}
echo "</pre>";

// TESTE 3: Contagem de ordens com data_finalizacao NULL vs NOT NULL
echo "<h3>📊 TESTE 3: Análise de data_finalizacao</h3>";
echo "<pre>";
$comNull = 0;
$semNull = 0;
foreach ($resultado2 as $row) {
    if (isset($row['data_finalizacao']) && $row['data_finalizacao'] !== null && $row['data_finalizacao'] !== '') {
        $semNull++;
    } else {
        $comNull++;
    }
}
echo "Ordens com data_finalizacao = NULL: $comNull\n";
echo "Ordens com data_finalizacao preenchida: $semNull\n";
echo "</pre>";

// TESTE 4: POST ao sincronizar_status_focco.php
echo "<h3>🔄 TESTE 4: Chamando sincronizar_status_focco.php via GET</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/PROJETOS%20DESENVOLVEDOR/Cofelma/Mercado/Gest%C3%A3o%20Filas%20v1.1/sincronizar_status_focco.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
$response = curl_exec($ch);
curl_close($ch);

echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

?>
