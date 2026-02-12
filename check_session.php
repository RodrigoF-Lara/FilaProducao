<?php
// ========== VERIFICAÇÃO DE SESSÃO ==========

session_start();

// Verificar se está autenticado
if (empty($_SESSION['usuario_id']) || empty($_SESSION['usuario']) || empty($_SESSION['codigo_focco'])) {
    // Se não estiver autenticado, retornar erro JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Não autenticado',
        'redirect' => 'login.html'
    ]);
    exit(1);
}

// Se estiver autenticado, definir headers para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Retornar dados da sessão
$sessionData = [
    'usuario' => $_SESSION['usuario'],
    'codigo_focco' => $_SESSION['codigo_focco'],
    'nome_completo' => $_SESSION['nome_completo'] ?? 'Usuário'
];

// Se for uma requisição de API, retornar JSON
if (!empty($_GET['api'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $sessionData
    ]);
    exit;
}
?>
