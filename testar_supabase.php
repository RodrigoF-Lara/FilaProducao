<?php
// ========== TESTE DE CONEXÃO COM SUPABASE VIA API REST ==========

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

$supabase_url = 'https://sdmvxjyqcvnxddrraupu.supabase.co';
$anon_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNkbXZ4anlxY3ZueGRkcnJhdXB1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3Njc2NTgwMjYsImV4cCI6MjA4MzIzNDAyNn0.QTfk8SEc15lWqecGiLgM6lAWicHIz-HxKdWC9ZwUNz4';

$response = [
    'conexao' => false,
    'mensagem' => '',
    'usuarios' => null
];

try {
    // Endpoint para selecionar todos os dados da tabela 'usuarios'
    $endpoint = $supabase_url . '/rest/v1/usuarios?select=*';

    $ch = curl_init($endpoint);

    // Configura os headers da requisição
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $anon_key,
        'Authorization: Bearer ' . $anon_key
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Executa a requisição
    $api_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception('Erro de cURL: ' . $curl_error);
    }

    if ($http_code >= 200 && $http_code < 300) {
        $response['conexao'] = true;
        $response['mensagem'] = '✓ Conexão via API com o Supabase bem-sucedida! (HTTP ' . $http_code . ')';
        $response['usuarios'] = json_decode($api_response, true);
    } else {
        $error_details = json_decode($api_response, true);
        $error_message = $error_details['message'] ?? 'Erro desconhecido na API.';
        throw new Exception('Falha na API do Supabase (HTTP ' . $http_code . '): ' . $error_message);
    }

} catch (Exception $e) {
    $response['conexao'] = false;
    $response['mensagem'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
