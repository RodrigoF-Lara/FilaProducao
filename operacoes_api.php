<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'check_session.php';
require_once 'SupabaseClient.php';

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    // Criar cliente Supabase
    $supabase = new SupabaseClient();

    // GET: Buscar todos os registros da tabela OPERACAO_POR_SETOR
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $query = "select=*";
        $response['data'] = $supabase->select('OPERACAO_POR_SETOR', $query);
        $response['success'] = true;
    } else {
        http_response_code(400);
        throw new Exception('Método não suportado');
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Erro no servidor: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
