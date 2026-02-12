-- ========== CRIAR TABELAS NO SUPABASE POSTGRESQL ==========

-- Tabela de Usuários
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

-- Tabela de Requisições
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

-- Tabela de Histórico de Ações
CREATE TABLE IF NOT EXISTS historico_acoes (
    id SERIAL PRIMARY KEY,
    usuario_id INTEGER NOT NULL REFERENCES usuarios(id) ON DELETE CASCADE,
    tipo_acao VARCHAR(100) NOT NULL,
    setor VARCHAR(50),
    numero_ordem VARCHAR(50),
    descricao TEXT,
    data_acao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ========== INSERIR USUÁRIO DE TESTE ==========
INSERT INTO usuarios (usuario, senha, codigo_focco, nome_completo, email)
VALUES ('admin', 'admin123', '001', 'Administrador', 'admin@cofelma.com')
ON CONFLICT (usuario) DO NOTHING;

-- Visualizar dados criados
SELECT 'Tabelas criadas com sucesso!' AS status;
SELECT COUNT(*) as total_usuarios FROM usuarios;
