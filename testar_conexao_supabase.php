<?php
// testar_conexao_supabase.php
header('Content-Type: text/plain');
require_once __DIR__ . '/config_db.php';

try {
    $db = new DatabaseConnection();
    echo "Conexão com o Supabase/Postgres estabelecida com sucesso!\n";
    $result = $db->query('SELECT NOW() as agora');
    $row = $db->fetch($result);
    echo "Data/hora do banco: " . $row['agora'] . "\n";
} catch (Exception $e) {
    echo "Erro ao conectar: " . $e->getMessage() . "\n";
}
?>
