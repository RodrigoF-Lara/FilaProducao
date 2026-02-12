<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'check_session.php';
require_once 'SupabaseClient.php';
require_once 'Requisicao/config_oracle.php';

$response = ['success' => false, 'message' => '', 'data' => []];
$supabase = new SupabaseClient();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET: Retorna apenas as ordens ATIVAS (não finalizadas)
        $query = "select=numero_ordem,sequencia,operacao,focco_final,data_sincronizacao&focco_final=eq.0&limit=5000&order=data_sincronizacao.desc";
        $response['data'] = $supabase->select('ordens_operacoes_status', $query);
        $response['success'] = true;

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // POST: Força sincronização com Oracle
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'sincronizar_focco') {
            console_log("🔄 Iniciando sincronização com Focco Oracle...");
            
            // 1. Verificar se recebeu ordens do frontend (sincronização inicial)
            $ordensParaSincronizar = [];
            if (isset($input['ordens']) && is_array($input['ordens'])) {
                console_log("📦 Recebeu " . count($input['ordens']) . " ordens do frontend para sincronizar");
                $ordensParaSincronizar = $input['ordens'];
            } else {
                // Buscar todas as ordens ativas do cache Supabase
                $ativasQuery = "select=numero_ordem,sequencia,operacao&focco_final=eq.0&limit=10000";
                $ordensAtivasSupabase = $supabase->select('ordens_operacoes_status', $ativasQuery);

                if (!is_array($ordensAtivasSupabase)) {
                    throw new Exception('Erro ao buscar ordens ativas do Supabase');
                }
                
                $ordensParaSincronizar = $ordensAtivasSupabase;
            }

            console_log("📋 Ordens a sincronizar: " . count($ordensParaSincronizar));

            // 2. Agrupar por número de ordem para economizar queries ao Oracle
            $ordensPorNumero = [];
            foreach ($ordensParaSincronizar as $item) {
                $num_ordem = $item['numero_ordem'];
                if (!isset($ordensPorNumero[$num_ordem])) {
                    $ordensPorNumero[$num_ordem] = [];
                }
                $ordensPorNumero[$num_ordem][] = $item;
            }

            console_log("📊 Ordens distintas a consultar: " . count($ordensPorNumero));

            // 3. Para cada ordem distinta, buscar status do Oracle UMA VEZ
            $atualizacoes = [];
            $oracle = new OracleConnection();
            
            if (!$oracle->connect()) {
                throw new Exception('Falha ao conectar com Oracle: ' . $oracle->getError());
            }

            $contador = 0;
            foreach (array_keys($ordensPorNumero) as $num_ordem) {
                $contador++;
                console_log("🔍 [{$contador}/" . count($ordensPorNumero) . "] Consultando ordem: {$num_ordem}");

                // Query que retorna TODAS as sequências de uma ordem
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
                    console_log("❌ Erro ao consultar ordem {$num_ordem}: " . $oracle->getError());
                    continue;
                }

                if (empty($resultados)) {
                    console_log("⚠️ Nenhum resultado para ordem {$num_ordem}");
                    continue;
                }

                // 4. Mapear resultados e preparar atualizações em lote
                foreach ($resultados as $row) {
                    $seq = intval($row['SEQ']);
                    $final = intval($row['FINAL']);
                    $operacao = trim($row['COD_OPERACAO']);

                    $atualizacoes[] = [
                        'numero_ordem' => $num_ordem,
                        'sequencia' => $seq,
                        'operacao' => $operacao,
                        'focco_final' => $final,
                        'data_sincronizacao' => date('Y-m-d H:i:s'),
                        'data_finalizacao' => ($final === 1) ? date('Y-m-d H:i:s') : null
                    ];

                    $statusLabel = ($final === 1) ? '✅ FINALIZADA' : '⏳ ATIVA';
                    console_log("   Seq {$seq} - {$operacao}: {$statusLabel}");
                }
            }

            $oracle->close();
            console_log("📝 Total de registros a atualizar: " . count($atualizacoes));

            // 5. Fazer upsert em lote no Supabase
            if (!empty($atualizacoes)) {
                $sucessos = 0;
                $erros = 0;
                
                foreach ($atualizacoes as $atualizacao) {
                    try {
                        // Tenta atualizar; se não existir, insere
                        $query = "numero_ordem=eq.{$atualizacao['numero_ordem']}&sequencia=eq.{$atualizacao['sequencia']}&operacao=eq." . urlencode($atualizacao['operacao']);
                        $existing = $supabase->select('ordens_operacoes_status', "select=id&" . $query);
                        
                        if (!empty($existing)) {
                            $supabase->update('ordens_operacoes_status', $query, $atualizacao);
                            $sucessos++;
                        } else {
                            $supabase->insert('ordens_operacoes_status', $atualizacao);
                            $sucessos++;
                        }
                    } catch (Exception $e) {
                        $erros++;
                        error_log("Erro ao atualizar ordem: " . json_encode($atualizacao) . " - " . $e->getMessage());
                    }
                }
                
                console_log("✅ Sincronização concluída: {$sucessos} sucessos, {$erros} erros");
            }

            $response['success'] = true;
            $response['message'] = 'Sincronização com Focco concluída. ' . count($atualizacoes) . ' registros processados.';
            $response['data'] = $atualizacoes;

        } else {
            throw new Exception('Ação não reconhecida: ' . $action);
        }
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Erro sincronizar_ordens_api.php: " . $e->getMessage());
    http_response_code(500);
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

/**
 * Função auxiliar para logging
 */
function console_log($message) {
    error_log("[SINCRONIZAR] " . $message);
}
?>

