<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'check_session.php';
require_once 'SupabaseClient.php';
require_once 'Requisicao/config_oracle.php';

$response = ['success' => false, 'message' => '', 'data' => []];
$supabase = new SupabaseClient();

console_log("🔷 API sincronizar_status_focco.php acionada - Método: " . $_SERVER['REQUEST_METHOD']);

try {
    // GET: Retorna ordens pendentes de sincronização (data_finalizacao IS NULL)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        console_log("📋 Buscando ordens pendentes de sincronização com Focco...");
        
        // Query na tabela CORRETA: ordens_operacoes_status
        $query = "data_finalizacao=is.null&select=id,numero_ordem,sequencia,operacao,setor,operacao_id&limit=100";
        $ordensPendentes = $supabase->select('ordens_operacoes_status', $query);
        
        if (!is_array($ordensPendentes)) {
            console_log("❌ Erro ao buscar ordens: " . var_export($ordensPendentes, true));
            throw new Exception('Erro ao buscar ordens pendentes do Supabase');
        }
        
        console_log("✅ Encontradas " . count($ordensPendentes) . " ordens para sincronizar");
        
        $response['success'] = true;
        $response['data'] = $ordensPendentes;
    }
    // POST: Sincroniza com Focco e atualiza data_finalizacao
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        console_log("🔄 Iniciando sincronização com Focco Oracle...");
        
        // 1. Buscar ordens com data_finalizacao IS NULL da tabela CORRETA
        $query = "data_finalizacao=is.null&select=id,numero_ordem,sequencia,operacao,setor,operacao_id&limit=100";
        $ordensPendentes = $supabase->select('ordens_operacoes_status', $query);
        
        if (!is_array($ordensPendentes)) {
            console_log("❌ Erro ao buscar ordens: " . var_export($ordensPendentes, true));
            throw new Exception('Erro ao buscar ordens pendentes');
        }
        
        console_log("📊 Ordens pendentes encontradas: " . count($ordensPendentes));
        
        if (empty($ordensPendentes)) {
            $response['success'] = true;
            $response['message'] = 'Nenhuma ordem para sincronizar';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 2. Agrupar por número de ordem para economizar queries ao Oracle
        $ordensPorNumero = [];
        foreach ($ordensPendentes as $item) {
            $num_ordem = $item['numero_ordem'];
            if (!isset($ordensPorNumero[$num_ordem])) {
                $ordensPorNumero[$num_ordem] = [];
            }
            $ordensPorNumero[$num_ordem][] = $item;
        }
        
        console_log("📈 Ordens distintas a consultar: " . count($ordensPorNumero));
        
        // 3. Conectar ao Oracle e consultar status de cada ordem
        $oracle = new OracleConnection();
        
        if (!$oracle->connect()) {
            throw new Exception('Falha ao conectar com Oracle: ' . $oracle->getError());
        }
        
        $finalizadas = 0;
        $atualizacoes = [];
        
        foreach ($ordensPorNumero as $num_ordem => $ordensDoNumero) {
            console_log("🔍 Consultando ordem {$num_ordem} no Focco...");
            
            // Query para obter status da ordem (FINAL=1 significa finalizada)
            $sql = "
                SELECT a.ID, a.num_ordem, b.seq, b.final, c.cod_operacao
                FROM focco3i.tordens a
                INNER JOIN focco3i.tordens_rot b ON (b.ordem_id = a.id)
                INNER JOIN focco3i.toperacao c ON (c.ID = b.OPERACAO_ID)
                WHERE a.num_ordem = :num_ordem
                ORDER BY b.seq
            ";
            
            $params = [':num_ordem' => $num_ordem];
            $resultados = $oracle->executeQuery($sql, $params);
            
            if ($resultados === false) {
                console_log("❌ Erro ao consultar {$num_ordem}: " . $oracle->getError());
                continue;
            }
            
            if (empty($resultados)) {
                console_log("⚠️ Nenhum resultado para {$num_ordem}");
                continue;
            }
            
            // 4. Verificar cada sequência/operação
            foreach ($resultados as $row) {
                $seq = intval($row['SEQ']);
                $final = intval($row['FINAL']);
                $operacao = trim($row['COD_OPERACAO']);
                
                // Procurar correspondência no Supabase
                foreach ($ordensDoNumero as $itemSupabase) {
                    if (intval($itemSupabase['sequencia']) === $seq && 
                        strtoupper(trim($itemSupabase['operacao'])) === strtoupper($operacao)) {
                        
                        if ($final === 1) {
                            // Ordem finalizada! Atualizar data_finalizacao
                            console_log("✅ FINALIZADA: {$num_ordem}-{$seq}-{$operacao}");
                            
                            $atualizacoes[] = [
                                'id' => $itemSupabase['id'],
                                'numero_ordem' => $num_ordem,
                                'sequencia' => $seq,
                                'operacao' => $operacao,
                                'status' => 'finalizada'
                            ];
                            
                            $finalizadas++;
                        }
                    }
                }
            }
        }
        
        $oracle->close();
        
        // 5. Atualizar as ordens finalizadas com data_finalizacao na tabela CORRETA
        $atualizacoesSucesso = 0;
        $atualizacoesErro = 0;
        
        foreach ($atualizacoes as $atu) {
            try {
                $updateData = [
                    'data_finalizacao' => date('Y-m-d H:i:s'),
                    'status_novo' => 'finalizado'
                ];
                
                $where = "id=eq." . $atu['id'];
                $supabase->update('ordens_operacoes_status', $where, $updateData);
                
                console_log("💾 Atualizado ordens_operacoes_status para ordem {$atu['numero_ordem']}-{$atu['sequencia']}");
                $atualizacoesSucesso++;
            } catch (Exception $e) {
                console_log("❌ Erro ao atualizar: " . $e->getMessage());
                $atualizacoesErro++;
            }
        }
        
        console_log("✅ Sincronização concluída: {$finalizadas} finalizadas, {$atualizacoesSucesso} atualizadas, {$atualizacoesErro} erros");
        
        $response['success'] = true;
        $response['message'] = "Sincronização concluída: {$finalizadas} ordens finalizadas, {$atualizacoesSucesso} atualizadas";
        $response['data'] = $atualizacoes;
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    console_log("❌ Erro fatal: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Função auxiliar para logging
 */
function console_log($message) {
    error_log("[SYNC_STATUS] " . $message);
}
?>

