<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'check_session.php';
require_once 'SupabaseClient.php';

$response = ['success' => false, 'message' => '', 'data' => null];
$supabase = new SupabaseClient();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $response['data'] = $supabase->select('usuarios', 'select=*&order=id');
        $response['success'] = true;
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    if (!$input) throw new Exception('Dados invalidos');

    $action = $input['action'] ?? '';
    $id = intval($input['id'] ?? 0);

    switch ($action) {
        case 'create':
            $data = [
                'usuario' => trim($input['usuario'] ?? ''),
                'senha' => trim($input['senha'] ?? ''),
                'codigo_focco' => trim($input['codigo_focco'] ?? ''),
                'nome_completo' => trim($input['nome_completo'] ?? ''),
                'email' => trim($input['email'] ?? ''),
                'ativo' => !empty($input['ativo']),
                'nivel' => trim($input['nivel'] ?? 'operador') // Adiciona o nível
            ];
            if (empty($data['usuario']) || empty($data['senha']) || empty($data['codigo_focco'])) {
                throw new Exception('Usuario, senha e codigo Focco sao obrigatorios');
            }
            $response['data'] = $supabase->insert('usuarios', $data);
            $response['message'] = 'Usuario criado';
            break;

        case 'update':
            if ($id <= 0) throw new Exception('ID invalido');
            $data = [
                'usuario' => trim($input['usuario'] ?? ''),
                'codigo_focco' => trim($input['codigo_focco'] ?? ''),
                'nome_completo' => trim($input['nome_completo'] ?? ''),
                'email' => trim($input['email'] ?? ''),
                'ativo' => !empty($input['ativo']),
                'nivel' => trim($input['nivel'] ?? 'operador') // Adiciona o nível
            ];
            if (empty($data['usuario']) || empty($data['codigo_focco'])) {
                throw new Exception('Usuario e codigo Focco sao obrigatorios');
            }
            // Apenas atualiza a senha se uma nova for fornecida
            if (!empty($input['senha'])) {
                $data['senha'] = trim($input['senha']);
            }
            $response['data'] = $supabase->update('usuarios', "id=eq.{$id}", $data);
            $response['message'] = 'Usuario atualizado';
            break;

        case 'toggle':
            if ($id <= 0) throw new Exception('ID invalido');
            $data = ['ativo' => !empty($input['ativo'])];
            $supabase->update('usuarios', "id=eq.{$id}", $data);
            $response['message'] = 'Status atualizado';
            break;

        case 'delete':
            if ($id <= 0) throw new Exception('ID invalido');
            $supabase->delete('usuarios', "id=eq.{$id}");
            $response['message'] = 'Usuario removido';
            break;

        default:
            throw new Exception('Acao invalida');
    }

    $response['success'] = true;

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
