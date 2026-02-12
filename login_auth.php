<?php
// ========== AUTENTICAÇÃO DE LOGIN VIA API ==========

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once 'SupabaseClient.php';

$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if (!$input) {
        throw new Exception('Dados inválidos');
    }

    $usuario = trim($input['usuario'] ?? '');
    $senha = trim($input['senha'] ?? '');

    if (empty($usuario) || empty($senha)) {
        throw new Exception('Usuário e senha são obrigatórios');
    }

    $api_connection_error = null;

    try {
        $supabase = new SupabaseClient();
        // Busca um usuário que corresponda ao usuário e senha E esteja ativo
        $query = "select=id,usuario,codigo_focco,nome_completo,nivel&usuario=eq.{$usuario}&senha=eq.{$senha}&ativo=is.true";
        $users = $supabase->select('usuarios', $query);

        // Se a API retornar exatamente um usuário, a autenticação é bem-sucedida
        if (count($users) === 1) {
            $user = $users[0];
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario'] = $user['usuario'];
            $_SESSION['codigo_focco'] = $user['codigo_focco'];
            $_SESSION['nome_completo'] = $user['nome_completo'];
            $_SESSION['nivel'] = $user['nivel']; // Armazena o nível na sessão

            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso';
            $response['data'] = $user;
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit; // Sucesso, não precisa de fallback
        }
    } catch (Exception $e) {
        // Se a API falhar, armazena o erro e tenta o fallback
        $api_connection_error = $e->getMessage();
    }
    
    // Fallback para o usuário de teste se a autenticação via API falhar
    if ($usuario === 'admin' && $senha === 'admin123') {
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario'] = $usuario;
        $_SESSION['codigo_focco'] = '001';
        $_SESSION['nome_completo'] = 'Administrador';
        $_SESSION['nivel'] = 'administrador'; // Define o nível para o usuário de teste
        
        $response['success'] = true;
        $response['message'] = 'Login realizado com sucesso (usuário de teste)';
        $response['data'] = ['usuario' => $usuario, 'codigo_focco' => '001', 'nome_completo' => 'Administrador', 'nivel' => 'administrador'];
    } else {
        // Se o fallback também falhar, lança o erro apropriado.
        if ($api_connection_error) {
            throw new Exception($api_connection_error);
        } else {
            throw new Exception('Usuário ou senha inválido');
        }
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>

