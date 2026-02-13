<?php
header('Content-Type: application/json; charset=utf-8');
// require_once 'check_session.php'; // Desabilitado para testes: permite POST sem autenticação
require_once 'SupabaseClient.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$supabase = new SupabaseClient();

try {
    // GET: Busca o estado mais recente das ordens para um setor ou o histórico de uma ordem
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $numero_ordem = trim($_GET['numero_ordem'] ?? '');
        $setor = trim($_GET['setor'] ?? '');
        $setorLower = strtolower($setor);

        // Se um número de ordem for fornecido, busca o histórico completo para essa ordem
        if (!empty($numero_ordem)) {
            $query = "select=status_anterior,status_novo,colaborador,data_movimentacao&numero_ordem=eq.{$numero_ordem}&order=data_movimentacao.asc";
            $response['data'] = $supabase->select('historico_status', $query);
        }
        // Senão, continua com a lógica original para buscar o último status de todas as ordens do setor
        elseif (!empty($setor)) {
            // Usa a view 'latest_status_by_order' que já faz o trabalho de pegar o último status
            // A view precisa ser ajustada no Supabase para incluir os campos adicionais
            $select_fields = 'numero_ordem,sequencia,operacao,setor,status_novo,data_finalizacao,cliente,cod_item,qtde,dt_entrega,posicao_fila';
            if ($setorLower === 'all') {
                $query = "select={$select_fields}";
                $response['data'] = $supabase->select('latest_status_by_order', $query);
            } else {
                $query = "select={$select_fields}&setor=eq.{$setor}";
                $response['data'] = $supabase->select('latest_status_by_order', $query);
            }
        }
        else {
            throw new Exception('É necessário fornecer um setor ou um número de ordem.');
        }
        $response['success'] = true;
    }
    // POST: Insere um novo log de movimentação de status
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $data = [
            'numero_ordem' => $input['numero_ordem'] ?? null,
            'sequencia' => $input['sequencia'] ?? null,
            'operacao' => $input['operacao'] ?? null,
            'setor' => $input['setor'] ?? null,
            'status_anterior' => $input['status_anterior'] ?? null,
            'status_novo' => $input['status_novo'] ?? null,
            'colaborador' => $_SESSION['nome_completo'] ?? 'N/A',
        ];

        if (empty($data['numero_ordem']) || empty($data['setor']) || empty($data['status_novo'])) {
            throw new Exception('Dados insuficientes para registrar a movimentação.');
        }

        $response['data'] = $supabase->insert('historico_status', $data);
        $response['success'] = true;
        $response['message'] = 'Status atualizado com sucesso.';
    } else {
        throw new Exception('Método não permitido.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>