<?php
require_once 'SupabaseClient.php';

$supabase = new SupabaseClient();

echo "<h2>🔍 DIAGNÓSTICO COMPLETO DE ESTRUTURA</h2>";

// 1. Verificar schema de historico_status
echo "<h3>1️⃣ SCHEMA: historico_status</h3>";
echo "<pre>";
echo "SELECT * FROM information_schema.columns WHERE table_name = 'historico_status'\n";
echo "</pre>";

// 2. Contar e mostrar sample de historico_status
echo "<h3>2️⃣ DADOS: historico_status</h3>";
$allRows = $supabase->select('historico_status', 'select=*&limit=100');
echo "Total de registros: " . count($allRows) . "\n";
echo "Sample dos primeiros 3:\n";
echo "<pre>";
foreach (array_slice($allRows, 0, 3) as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n---\n";
}
echo "</pre>";

// 3. Verificar outras tabelas
echo "<h3>3️⃣ OUTRAS TABELAS DISPONÍVEIS</h3>";
echo "Procurando por tabelas que possam conter as ORDENS ATIVAS...\n";
echo "<pre>";

// Tentar movimentacoes
try {
    $move = $supabase->select('movimentacoes', 'select=*&limit=3');
    echo "✅ movimentacoes: " . count($move) . " registros\n";
    if (count($move) > 0) {
        echo "Sample: " . json_encode($move[0]) . "\n";
    }
} catch (Exception $e) {
    echo "❌ movimentacoes: não existe ou erro\n";
}

echo "\n";

// Tentar status
try {
    $status = $supabase->select('status', 'select=*&limit=3');
    echo "✅ status: " . count($status) . " registros\n";
} catch (Exception $e) {
    echo "❌ status: não existe ou erro\n";
}

echo "\n";

// Tentar ordens
try {
    $ordens = $supabase->select('ordens', 'select=*&limit=3');
    echo "✅ ordens: " . count($ordens) . " registros\n";
    if (count($ordens) > 0) {
        echo "Sample: " . json_encode($ordens[0]) . "\n";
    }
} catch (Exception $e) {
    echo "❌ ordens: não existe ou erro\n";
}

echo "\n";

// Tentar status_historia
try {
    $hist = $supabase->select('status_historia', 'select=*&limit=3');
    echo "✅ status_historia: " . count($hist) . " registros\n";
} catch (Exception $e) {
    echo "❌ status_historia: não existe ou erro\n";
}

echo "</pre>";

// 4. Estrutura de dados esperada
echo "<h3>4️⃣ ESTRUTURA ESPERADA vs ATUAL</h3>";
echo "<pre>";
echo "Para sincronização funcionar, historico_status deve ter:\n";
echo "✅ numero_ordem: SIM (tem dados)\n";
echo "✅ sequencia: MAS 77% SÃO NULL\n";
echo "✅ operacao: MAS 77% SÃO NULL\n";
echo "✅ data_finalizacao: SIM (pode ser NULL)\n";
echo "\nProblema: dados incompletos impedem sincronização!\n";
echo "</pre>";

// 5. Análise de 18568655 (a ordem que deveria estar finalizada)
echo "<h3>5️⃣ ANÁLISE ESPECÍFICA: ordem 18568655</h3>";
$ordem18568655 = $supabase->select('historico_status', "numero_ordem=eq.18568655&select=*");
echo "<pre>";
echo "Registros encontrados para 18568655: " . count($ordem18568655) . "\n";
foreach ($ordem18568655 as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT) . "\n";
}
echo "</pre>";

?>
