<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'check_session.php';
require_once 'SupabaseClient.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$supabase = new SupabaseClient();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    // Rota POST para buscar histórico (nova, para evitar problemas de URL)
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'get_history') {
        $numero_ordem = trim($input['numero_ordem'] ?? '');
        $sequencia = trim($input['sequencia'] ?? '');
        $operacao = trim($input['operacao'] ?? '');
        if (empty($numero_ordem)) throw new Exception('O número da ordem é obrigatório.');

        $query = "select=id,numero_ordem,setor_origem,setor_destino,colaborador,data_movimentacao&numero_ordem=eq.{$numero_ordem}&sequencia=eq.{$sequencia}&operacao=eq.\"{$operacao}\"&order=data_movimentacao.desc";
        $response['data'] = $supabase->select('historico_movimentacoes', $query);
        $response['success'] = true;
    }
    // Rota POST para criar uma nova movimentação (existente)
    elseif ($method === 'POST') {
        $data = [
            'numero_ordem' => $input['numero_ordem'] ?? null,
            'sequencia' => $input['sequencia'] ?? null,
            'operacao' => $input['operacao'] ?? null,
            'setor_origem' => $input['setor_origem'] ?? null,
            'setor_destino' => $input['setor_destino'] ?? null,
            'colaborador' => $_SESSION['nome_completo'] ?? 'N/A',
        ];

        if (empty($data['numero_ordem']) || empty($data['setor_origem']) || empty($data['setor_destino'])) {
            throw new Exception('Dados insuficientes para registrar a movimentação.');
        }

        $response['data'] = $supabase->insert('historico_movimentacoes', $data);
        $response['success'] = true;
        $response['message'] = 'Movimentação registrada com sucesso.';
    }
    // Rota GET (mantida para outras funcionalidades, se houver)
    elseif ($method === 'GET') {
        $all = ($_GET['all'] ?? '') === '1';
        if ($all) {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20000;
            $query = "select=numero_ordem,sequencia,operacao,setor_destino,data_movimentacao&order=data_movimentacao.desc&limit={$limit}";
            $response['data'] = $supabase->select('historico_movimentacoes', $query);
            $response['success'] = true;
        } else {
            $numero_ordem = trim($_GET['numero_ordem'] ?? '');
            if (empty($numero_ordem)) throw new Exception('O número da ordem é obrigatório.');
            // Consulta apenas pelo número da ordem
            $query = "select=id,numero_ordem,setor_origem,setor_destino,colaborador,data_movimentacao&numero_ordem=eq.{$numero_ordem}&order=data_movimentacao.desc";
            $response['data'] = $supabase->select('historico_movimentacoes', $query);
            $response['success'] = true;
        }
    } else {
        throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>