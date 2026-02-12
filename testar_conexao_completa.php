<?php
header('Content-Type: application/json; charset=utf-8');

$resultado = [
    'sistema' => [],
    'postgresql' => [],
    'oracle' => [],
    'sessao' => []
];

// ========== VERIFICAR SISTEMA ==========
$resultado['sistema']['php_version'] = phpversion();
$resultado['sistema']['os'] = php_uname();
$resultado['sistema']['extensoes'] = [
    'pdo' => extension_loaded('pdo'),
    'pdo_pgsql' => extension_loaded('pdo_pgsql'),
    'postgresql' => extension_loaded('pgsql'),
    'oci8' => extension_loaded('oci8'),
    'curl' => extension_loaded('curl')
];

// ========== TESTAR POSTGRESQL (SUPABASE) ==========
try {
    $host = 'db.sdmvxjyqcvnxddrraupu.supabase.co';
    $port = 5432;
    $database = 'postgres';
    $user = 'postgres';
    $password = '124497#Matheus@2026';
    
    if (extension_loaded('pgsql')) {
        $connStr = "host={$host} port={$port} dbname={$database} user={$user} password={$password}";
        $conn = pg_connect($connStr);
        
        if ($conn) {
            $resultado['postgresql']['conexao'] = true;
            $resultado['postgresql']['mensagem'] = '✓ Conectado ao Supabase';
            
            // Listar tabelas
            $result = pg_query($conn, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;");
            $tabelas = [];
            while ($row = pg_fetch_assoc($result)) {
                $tabelas[] = $row['table_name'];
            }
            $resultado['postgresql']['tabelas'] = $tabelas;
            
            // Contar usuários
            $result_users = pg_query($conn, "SELECT COUNT(*) as total FROM usuarios;");
            $row = pg_fetch_assoc($result_users);
            $resultado['postgresql']['total_usuarios'] = $row['total'];
            
            pg_close($conn);
        } else {
            $resultado['postgresql']['conexao'] = false;
            $resultado['postgresql']['erro'] = pg_last_error();
        }
    } else {
        $resultado['postgresql']['conexao'] = false;
        $resultado['postgresql']['erro'] = 'Extensão pgsql não instalada';
    }
} catch (Exception $e) {
    $resultado['postgresql']['conexao'] = false;
    $resultado['postgresql']['erro'] = $e->getMessage();
}

// ========== TESTAR ORACLE ==========
try {
    if (extension_loaded('oci8')) {
        $oracle_user = 'focco_consulta';
        $oracle_pass = 'consulta3i08';
        $oracle_conn = 'db.sdmvxjyqcvnxddrraupu.supabase.co:1521/f3ipro';
        
        $conn = oci_connect($oracle_user, $oracle_pass, $oracle_conn, 'AL32UTF8');
        
        if ($conn) {
            $resultado['oracle']['conexao'] = true;
            $resultado['oracle']['mensagem'] = '✓ Conectado ao Oracle';
            oci_close($conn);
        } else {
            $e = oci_error();
            $resultado['oracle']['conexao'] = false;
            $resultado['oracle']['erro'] = $e['message'] ?? 'Erro desconhecido';
        }
    } else {
        $resultado['oracle']['conexao'] = false;
        $resultado['oracle']['erro'] = 'Extensão oci8 não instalada. Para usar Oracle, instale via PECL';
    }
} catch (Exception $e) {
    $resultado['oracle']['conexao'] = false;
    $resultado['oracle']['erro'] = $e->getMessage();
}

// ========== VERIFICAR SESSÃO ==========
session_start();
$resultado['sessao']['logado'] = !empty($_SESSION['usuario_id']);
$resultado['sessao']['usuario'] = $_SESSION['usuario'] ?? 'não definido';
$resultado['sessao']['codigo_focco'] = $_SESSION['codigo_focco'] ?? 'não definido';

echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
