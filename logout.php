<?php
// ========== LOGOUT ==========

session_start();

// Registrar logout no histórico
if (!empty($_SESSION['usuario_id'])) {
    require_once 'config_db.php';
    
    try {
        $db = new DatabaseConnection();
        $sql = "INSERT INTO historico_acoes (usuario_id, tipo_acao, descricao) VALUES ($1, $2, $3)";
        $db->query($sql, [$_SESSION['usuario_id'], 'logout', 'Usuário realizou logout do sistema']);
    } catch (Exception $e) {
        // Ignorar erros ao registrar logout
    }
}

// Destruir sessão
session_destroy();

// Retornar JSON de sucesso
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Logout realizado com sucesso'
]);
?>
