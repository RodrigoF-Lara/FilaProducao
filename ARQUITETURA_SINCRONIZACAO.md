# Nova Arquitetura - Sincronização Oracle Focco

## 🎯 Problema Resolvido

A arquitetura anterior tinha problemas:
- ❌ Carregava TODAS as 50.000+ ordens do sistema
- ❌ Tentava sincronizar todas contra Oracle
- ❌ Muito lento e ineficiente

## ✅ Nova Solução

**Usar a tabela `historico_status` que JÁ existe com ordens ativas:**

1. **Filtrar apenas ordens em fila**: `WHERE data_finalizacao IS NULL`
   - Máximo ~50-100 ordens ativas por vez
   - MUITO mais eficiente

2. **Para cada ordem ativa**: Fazer query no Oracle Focco
   - Verificar se `b.final = 1` (finalizada)
   - Se SIM → `UPDATE historico_status SET data_finalizacao = NOW()`
   - Próxima sincronização já pu la pelo WHERE

3. **Na exibição**: Filtrar `ordem.data_finalizacao` 
   - Se preenchida → ordem já foi encerrada
   - Se NULL → ordem ainda está em produção

## 📋 Passos para Implementar

### 1️⃣ Adicionar coluna no Supabase

Abra **Supabase SQL Editor** e execute:

```sql
ALTER TABLE historico_status 
ADD COLUMN IF NOT EXISTS data_finalizacao TIMESTAMP WITH TIME ZONE DEFAULT NULL;

CREATE INDEX IF NOT EXISTS idx_historico_status_ativas 
ON historico_status(data_finalizacao) 
WHERE data_finalizacao IS NULL;
```

Ou copie/execute o arquivo: `migration_add_data_finalizacao.sql`

### 2️⃣ APIs Implementadas

#### **sincronizar_status_focco.php**
- **GET**: Retorna ordens pendentes (`data_finalizacao IS NULL`)
- **POST**: Sincroniza com Focco e atualiza `data_finalizacao`

Fluxo:
```
POST /sincronizar_status_focco.php
  ↓
SELECT ordens com data_finalizacao IS NULL
  ↓
Para cada ordem: Query Oracle FOCCO
  ↓
Se FINAL=1: UPDATE historico_status SET data_finalizacao=NOW()
  ↓
Response com ordens finalizadas
```

#### **Removed (não usam mais)**
- ❌ `sincronizar_ordens_api.php` (a antiga tabela ordens_operacoes_status não é mais usada)

### 3️⃣ JavaScript (script.js)

**Função `sincronizarComFocco()`**
- Chama `POST /sincronizar_status_focco.php`
- Executa a cada 15 minutos
- Execute também ao carregar página (após 2 seg)

**Filtro em `carregarDados()`**
- Verifica `if (ordem.data_finalizacao)` 
- Se preenchida → pula a ordem
- Se NULL → inclui na fila

## 🚀 Fluxo Completo

```
1. Usuário acessa index.html
   ↓
2. Aguarda 2 segundos
   ↓
3. Chama sincronizar_status_focco.php (POST)
   - Busca ordens com data_finalizacao IS NULL
   - Para cada uma: verifica no Oracle Focco
   - Se finalizada: UPDATE historico_status
   ↓
4. Chama carregarDados()
   - Carrega histórico_status
   - Filtra: pula ordens com data_finalizacao preenchida
   - Exibe apenas ordens ativas
   ↓
5. A cada 15 minutos: repete sincronização
```

## 📊 Benefícios

| Aspecto | Antes | Depois |
|---------|-------|--------|
| Ordens sincronizadas | ~8 (mock) ou 50.000+ | ~50-100 (apenas ativas) |
| Queries Oracle/ciclo | ~8 ou 50.000+ | ~50-100 |
| Performance | Lenta | ⚡ Rápida |
| Reutilização de dados | ❌ Cache separado | ✅ Historico_status |
| Manutenção | ❌ Duas tabelas | ✅ Uma tabela |

## 🔍 Debugging

### Logs esperados no Console

```javascript
🚀 PPCP SCRIPT CARREGADO - Versão 20260206-1545 (nova arquitetura)
🚀 SISTEMA PPCP INICIADO
📋 Total de ordens detectadas no sistema: 8
⏳ Aguardando 2 segundos para sincronizar status com Focco...
🔄 Iniciando sincronização de status com Focco...
✅ Sincronização concluída: 1 ordens finalizadas
⏭️ PULANDO ordem finalizada: 18568655-10-ACABAMENTO.MT1 (encerrada em 2026-02-06 15:45:30)
```

### Verificar no Supabase

```sql
-- Ver ordens com data_finalizacao
SELECT numero_ordem, sequencia, operacao, status_novo, data_finalizacao 
FROM historico_status 
WHERE data_finalizacao IS NOT NULL 
ORDER BY data_finalizacao DESC;

-- Ver ordens ainda em produção (NULL)
SELECT numero_ordem, sequencia, operacao, status_novo, data_finalizacao 
FROM historico_status 
WHERE data_finalizacao IS NULL 
ORDER BY data_criacao DESC;
```

## ⚡ Próximos Passos

1. ✅ Execute a migration SQL no Supabase
2. ✅ Teste no navegador (Ctrl+Shift+R para limpar cache)
3. ✅ Aguarde 2 segundos para sincronização
4. ✅ Verifique console para logs
5. ✅ Ordem 18568655 seq 10 deve desaparecer (já finalizada no Focco)

---

**Versão**: 20260206-1545  
**Última atualização**: 06/02/2026
