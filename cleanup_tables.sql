-- ============================================
-- LIMPEZA COMPLETA PARA NOVO CICLO DE TESTES
-- ============================================

-- 1. Limpar historico_status (dados antigos incompletos)
DELETE FROM historico_status;
ALTER SEQUENCE historico_status_id_seq RESTART WITH 1;

-- 2. Limpar ordens_operacoes_status (dados de teste)
DELETE FROM ordens_operacoes_status;
ALTER SEQUENCE ordens_operacoes_status_id_seq RESTART WITH 1;

-- ============================================
-- VERIFICAR ESTRUTURA CORRETA
-- ============================================

-- Verificar colunas de ordens_operacoes_status
-- SELECT * FROM ordens_operacoes_status LIMIT 0;

-- Verificar colunas de historico_status
-- SELECT * FROM historico_status LIMIT 0;

-- ============================================
-- Após executar, recarregue a página do sistem
-- As 8 ordens vão ser carregadas de novo do Oracle
-- E histórico_status ficará vazio, pronto para sync
-- ============================================
