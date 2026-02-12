-- ============================================================ 
-- MIGRATION: Adicionar coluna data_finalizacao em historico_status
-- ============================================================
-- Execute este SQL no Supabase SQL Editor
-- 
-- Objetivo: Marcar ordens que já foram finalizadas no Focco 
-- para evitar re-sincronizações repetidas

-- 1. Adicionar coluna data_finalizacao (se ainda não existe)
ALTER TABLE historico_status 
ADD COLUMN IF NOT EXISTS data_finalizacao TIMESTAMP WITH TIME ZONE DEFAULT NULL;

-- 2. Adicionar comentário para documentação
COMMENT ON COLUMN historico_status.data_finalizacao IS 'Data/hora quando a ordem foi finalizada no Focco. NULL = ainda em produção';

-- 3. Criar índice para otimizar queries WHERE data_finalizacao IS NULL
CREATE INDEX IF NOT EXISTS idx_historico_status_ativas 
ON historico_status(data_finalizacao) 
WHERE data_finalizacao IS NULL;

-- Verificар se funcionou
SELECT column_name, data_type, is_nullable 
FROM information_schema.columns 
WHERE table_name = 'historico_status' 
AND column_name IN ('data_criacao', 'data_finalizacao', 'status_novo');
