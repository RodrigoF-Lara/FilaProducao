<?php
// filepath: ver_estrutura_via_api.php
header('Content-Type: application/json; charset=utf-8');

require_once 'SupabaseClient.php';

$response = [
    'success' => false,
    'tabela' => '',
    'estrutura' => [],
    'message' => ''
];

try {
    $tabela = trim($_GET['tabela'] ?? '');
    $response['tabela'] = $tabela;

    if (empty($tabela)) {
        throw new Exception('O nome da tabela deve ser fornecido via parâmetro GET. Ex: ?tabela=logs_producao');
    }

    $supabase = new SupabaseClient();
    
    // Chama a função 'get_table_structure' via RPC (Remote Procedure Call) da API
    $estrutura = $supabase->rpc('get_table_structure', ['p_table_name' => $tabela]);

    if (empty($estrutura)) {
        throw new Exception("A tabela '{$tabela}' não foi encontrada ou a função RPC não retornou dados.");
    }

    $response['success'] = true;
    $response['estrutura'] = $estrutura;
    $response['message'] = 'Estrutura da tabela recuperada com sucesso via API.';
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>