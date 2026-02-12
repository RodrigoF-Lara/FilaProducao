<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'check_session.php';
require_once 'SupabaseClient.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$supabase = new SupabaseClient();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    // Rota POST para buscar histórico
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'get_history') {
        $numero_ordem = trim($input['numero_ordem'] ?? '');
        $sequencia = trim($input['sequencia'] ?? '');
        $operacao = trim($input['operacao'] ?? '');
        if (empty($numero_ordem)) throw new Exception('O número da ordem é obrigatório.');

        $query = "select=id,comentario,colaborador,created_at&numero_ordem=eq.{$numero_ordem}&sequencia=eq.{$sequencia}&operacao=eq.\"{$operacao}\"&order=created_at.desc";
        $response['data'] = $supabase->select('logs_producao', $query);
        $response['success'] = true;
    }
    // Rota POST para criar um novo comentário
    elseif ($method === 'POST') {
        if (!$input) throw new Exception('Dados inválidos');

        $data = [
            'numero_ordem' => trim($input['numero_ordem'] ?? ''),
            'sequencia' => $input['sequencia'] ?? null,
            'operacao' => $input['operacao'] ?? null,
            'comentario' => trim($input['comentario'] ?? ''),
            'colaborador' => $_SESSION['nome_completo'] ?? 'Usuário Desconhecido',
            'codigo' => trim($input['codigo'] ?? null),
            'cliente' => trim($input['cliente'] ?? null),
            'descricao' => trim($input['descricao'] ?? null),
            'data_hora' => date('d/m/Y H:i:s')
        ];
        
        if (empty($data['numero_ordem']) || empty($data['comentario'])) {
            throw new Exception('O número da ordem e o comentário são obrigatórios.');
        }

        $response['data'] = $supabase->insert('logs_producao', $data);
        $response['success'] = true;
        $response['message'] = 'Comentário adicionado com sucesso.';
    }
    // Rota GET para buscar comentários
    elseif ($method === 'GET') {
        $numero_ordem = trim($_GET['numero_ordem'] ?? '');
        if (empty($numero_ordem)) throw new Exception('O número da ordem é obrigatório.');
        $query = "select=id,comentario,colaborador,created_at&numero_ordem=eq.{$numero_ordem}&order=created_at.desc";
        $response['data'] = $supabase->select('logs_producao', $query);
        $response['success'] = true;
    }
    else {
        throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
