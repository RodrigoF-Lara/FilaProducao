<?php
header('Content-Type: application/json');
require_once 'config_oracle.php';

$response = ['success' => false, 'message' => ''];

try {
    $oracle = getOracleConnection();
    
    if ($oracle->connect()) {
        $response['success'] = true;
        $response['message'] = 'Conexão com Oracle estabelecida com sucesso!';
        $oracle->close();
    } else {
        $response['message'] = $oracle->getError();
    }
} catch (Exception $e) {
    $response['message'] = 'Exceção: ' . $e->getMessage();
}

echo json_encode($response);
?>
