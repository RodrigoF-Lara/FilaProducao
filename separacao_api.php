<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'SupabaseClient.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$supabase = new SupabaseClient();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $input = json_decode(file_get_contents('php://input'), true);

    // Rota POST para buscar histórico
    if ($method === 'POST' && isset($input['action']) && $input['action'] === 'get_history') {
        require_once 'check_session.php';
        $numero_ordem = trim($input['numero_ordem'] ?? '');
        $sequencia = trim($input['sequencia'] ?? '');
        $operacao = trim($input['operacao'] ?? '');
        if (empty($numero_ordem)) throw new Exception('O número da ordem é obrigatório.');

        $query = "select=id,numero_ordem,setor,acao,colaborador_codigo_focco,data_acao&numero_ordem=eq.{$numero_ordem}&sequencia=eq.{$sequencia}&operacao=eq.\"{$operacao}\"&order=data_acao.desc";
        $response['data'] = $supabase->select('historico_separacao', $query);
        $response['success'] = true;
    }
    // Rota POST para criar um novo evento
    elseif ($method === 'POST') {
        require_once 'check_session.php';
        $acao = $input['acao'] ?? null;
        $codigo_focco_operador = $input['colaborador_codigo_focco_operador'] ?? null;
        
        $colaborador_a_registrar = $_SESSION['codigo_focco'] ?? 'N/A';
        if (($acao === 'INICIO' || $acao === 'RETOMADA') && !empty($codigo_focco_operador)) {
            $colaborador_a_registrar = $codigo_focco_operador;
        }

        $data = [
            'numero_ordem' => $input['numero_ordem'] ?? null,
            'sequencia' => $input['sequencia'] ?? null,
            'operacao' => $input['operacao'] ?? null,
            'setor' => $input['setor'] ?? null,
            'acao' => $acao,
            'colaborador_codigo_focco' => $colaborador_a_registrar,
        ];

        if (empty($data['numero_ordem']) || empty($data['setor']) || empty($data['acao'])) {
            throw new Exception('Dados insuficientes para registrar a ação de separação.');
        }

        $response['data'] = $supabase->insert('historico_separacao', $data);
        $response['success'] = true;
        $response['message'] = 'Ação de separação registrada com sucesso.';
    }
    // Rota GET
    elseif ($method === 'GET') {
        $numero_ordem = trim($_GET['numero_ordem'] ?? '');
        $sequencia = trim($_GET['sequencia'] ?? '');
        if (empty($numero_ordem)) throw new Exception('O número da ordem é obrigatório.');
        $query = "select=id,numero_ordem,sequencia,setor,acao,colaborador_codigo_focco,data_acao&numero_ordem=eq.{$numero_ordem}";
        if (!empty($sequencia)) {
            $query .= "&sequencia=eq.{$sequencia}";
        }
        $query .= "&order=data_acao.desc";
        $response['data'] = $supabase->select('historico_separacao', $query);
        $response['success'] = true;
    } else {
         throw new Exception('Método não permitido');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>