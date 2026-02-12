<?php
// ========== CONEXÃO SUPABASE POSTGRESQL ==========

$host = 'db.sdmvxjyqcvnxddrraupu.supabase.co';
$port = 5432;
$database = 'postgres';
$user = 'postgres';
$password = '124497#Matheus@2026';

try {
    // Tentar conectar
    $conn = pg_connect("host=$host port=$port dbname=$database user=$user password=$password");
    
    if (!$conn) {
        throw new Exception("Erro ao conectar ao banco de dados: " . pg_last_error());
    }

    echo "✓ Conectado ao Supabase PostgreSQL\n\n";

    // ========== CRIAR TABELA DE USUÁRIOS ==========
    $sql = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id SERIAL PRIMARY KEY,
        usuario VARCHAR(100) NOT NULL UNIQUE,
        senha VARCHAR(255) NOT NULL,
        codigo_focco VARCHAR(50) NOT NULL UNIQUE,
        nome_completo VARCHAR(255),
        email VARCHAR(255),
        ativo BOOLEAN DEFAULT TRUE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $result = pg_query($conn, $sql);
    
    if (!$result) {
        throw new Exception("Erro ao criar tabela: " . pg_last_error($conn));
    }

    echo "✓ Tabela 'usuarios' criada com sucesso!\n\n";

    // ========== CRIAR TABELA DE REQUISIÇÕES ==========
    $sql_requisicoes = "
    CREATE TABLE IF NOT EXISTS requisicoes (
        id SERIAL PRIMARY KEY,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
        codigo_focco VARCHAR(50) NOT NULL,
        num_ordem INTEGER NOT NULL,
        cod_item VARCHAR(100) NOT NULL,
        qtde DECIMAL(10, 2) NOT NULL,
        status VARCHAR(50) DEFAULT 'pendente',
        data_requisicao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $result_req = pg_query($conn, $sql_requisicoes);
    
    if (!$result_req) {
        throw new Exception("Erro ao criar tabela de requisições: " . pg_last_error($conn));
    }

    echo "✓ Tabela 'requisicoes' criada com sucesso!\n\n";

    // ========== CRIAR TABELA DE HISTÓRICO DE AÇÕES ==========
    $sql_historico = "
    CREATE TABLE IF NOT EXISTS historico_acoes (
        id SERIAL PRIMARY KEY,
        usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
        tipo_acao VARCHAR(100) NOT NULL,
        setor VARCHAR(50),
        numero_ordem VARCHAR(50),
        descricao TEXT,
        data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ";

    $result_hist = pg_query($conn, $sql_historico);
    
    if (!$result_hist) {
        throw new Exception("Erro ao criar tabela de histórico: " . pg_last_error($conn));
    }

    echo "✓ Tabela 'historico_acoes' criada com sucesso!\n\n";

    // ========== EXIBIR ESTRUTURA DAS TABELAS ==========
    echo "========== ESTRUTURA DAS TABELAS ==========\n\n";

    // Tabela usuarios
    echo "Tabela: usuarios\n";
    $result_desc = pg_query($conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='usuarios' ORDER BY ordinal_position;");
    while ($row = pg_fetch_assoc($result_desc)) {
        echo "  - {$row['column_name']} ({$row['data_type']}) " . ($row['is_nullable'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    echo "\n";

    // Tabela requisicoes
    echo "Tabela: requisicoes\n";
    $result_desc2 = pg_query($conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='requisicoes' ORDER BY ordinal_position;");
    while ($row = pg_fetch_assoc($result_desc2)) {
        echo "  - {$row['column_name']} ({$row['data_type']}) " . ($row['is_nullable'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }
    echo "\n";

    // Tabela historico_acoes
    echo "Tabela: historico_acoes\n";
    $result_desc3 = pg_query($conn, "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_name='historico_acoes' ORDER BY ordinal_position;");
    while ($row = pg_fetch_assoc($result_desc3)) {
        echo "  - {$row['column_name']} ({$row['data_type']}) " . ($row['is_nullable'] === 'NO' ? 'NOT NULL' : 'NULL') . "\n";
    }

    echo "\n✓ Base de dados configurada com sucesso!\n";
    
    pg_close($conn);

} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
