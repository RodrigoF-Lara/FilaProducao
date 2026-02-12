<?php
// ========== CONFIGURAÇÃO SUPABASE POSTGRESQL ==========

class DatabaseConnection {
    private $host = 'db.sdmvxjyqcvnxddrraupu.supabase.co';
    private $port = 5432;
    private $database = 'postgres';
    private $user = 'postgres';
    private $password = '124497#Matheus@2026';
    private $conn;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $connStr = "host={$this->host} port={$this->port} dbname={$this->database} user={$this->user} password={$this->password}";
        
        $this->conn = @pg_connect($connStr);
        
        if (!$this->conn) {
            // Lança uma exceção mais clara sem causar um novo erro.
            // Isso ajuda a diagnosticar problemas de firewall ou rede.
            throw new Exception("Não foi possível conectar ao PostgreSQL. Verifique se o host '{$this->host}' está acessível, se a porta {$this->port} não está bloqueada por um firewall e se as credenciais estão corretas.");
        }
    }

    public function query($sql, $params = []) {
        if (empty($params)) {
            $result = pg_query($this->conn, $sql);
        } else {
            $result = pg_query_params($this->conn, $sql, $params);
        }
        
        if (!$result) {
            throw new Exception("Erro na query: " . pg_last_error($this->conn));
        }
        
        return $result;
    }

    public function fetch($result) {
        return pg_fetch_assoc($result);
    }

    public function fetchAll($result) {
        $data = [];
        while ($row = pg_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }

    public function close() {
        if ($this->conn) {
            pg_close($this->conn);
        }
    }

    public function __destruct() {
        $this->close();
    }
}
?>
