<?php
// ========== CLIENTE PARA API REST DO SUPABASE ==========

class SupabaseClient {
    private $supabase_url = 'https://sdmvxjyqcvnxddrraupu.supabase.co';
    private $anon_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNkbXZ4anlxY3ZueGRkcnJhdXB1Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3Njc2NTgwMjYsImV4cCI6MjA4MzIzNDAyNn0.QTfk8SEc15lWqecGiLgM6lAWicHIz-HxKdWC9ZwUNz4';

    private function execute($method, $endpoint, $data = null) {
        $ch = curl_init($this->supabase_url . '/rest/v1/' . $endpoint);

        $headers = [
            'apikey: ' . $this->anon_key,
            'Authorization: Bearer ' . $this->anon_key,
            'Prefer: return=representation' // Retorna o dado modificado na resposta
        ];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if ($data) {
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if ($data) {
                    $headers[] = 'Content-Type: application/json';
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            throw new Exception('Erro de cURL: ' . $curl_error);
        }

        if ($http_code < 200 || $http_code >= 300) {
            $error_details = json_decode($response_body, true);
            $error_message = $error_details['message'] ?? 'Erro desconhecido na API.';
            throw new Exception("Erro na API do Supabase (HTTP {$http_code}): " . $error_message);
        }

        return json_decode($response_body, true);
    }

    public function select($table, $query = '') {
        return $this->execute('GET', "{$table}?{$query}");
    }

    public function insert($table, $data) {
        return $this->execute('POST', $table, $data);
    }

    public function update($table, $query, $data) {
        return $this->execute('PATCH', "{$table}?{$query}", $data);
    }

    public function delete($table, $query) {
        return $this->execute('DELETE', "{$table}?{$query}");
    }

    public function rpc($function_name, $data) {
        return $this->execute('POST', "rpc/{$function_name}", $data);
    }
}
?>