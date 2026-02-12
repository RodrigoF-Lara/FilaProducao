<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Conexão Focco - Ordem 18568655</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #e74c3c;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-weight: bold;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background: #e74c3c;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .query-box {
            background: #f4f4f4;
            padding: 15px;
            border-left: 4px solid #e74c3c;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Teste de Conexão Oracle Focco - Ordem 18568655 Seq 10</h1>
        
        <div class="info">
            <strong>Objetivo:</strong> Verificar se a ordem 18568655 sequência 10 está finalizada no Focco (campo FINAL = 1)
        </div>

        <div class="query-box">
            <strong>Query executada:</strong><br>
            SELECT a.ID, a.num_ordem, a.tipo_ordem, a.dt_inicial, a.dt_final, a.qtde, a.qtde_entregue, 
                   a.qtde_pendente, a.qtde_refugo, a.num_rancho, b.seq, b.tempo, b.final, 
                   c.cod_operacao, c.descricao AS desc_operacao, de.maquina_id, de.cod_maquina, de.desc_maquina
            FROM focco3i.tordens a
            INNER JOIN focco3i.tordens_rot b ON (b.ordem_id=a.id)
            INNER JOIN focco3i.toperacao c ON (c.ID=b.OPERACAO_ID)
            CROSS APPLY (
                SELECT d.maquina_id, e.cod_maquina, e.descricao AS desc_maquina
                FROM focco3i.tord_rot_fab_maq d 
                INNER JOIN focco3i.tmaquinas e ON (e.id=d.maquina_id)
                WHERE d.torden_rot_id = b.id 
                ORDER BY d.maquina_id 
                FETCH FIRST 1 ROW ONLY
            ) de
            WHERE a.num_ordem = '18568655'
            AND b.seq = '10'
            ORDER BY b.seq
        </div>

        <?php
        require_once 'Requisicao/config_oracle.php';

        $oracle = new OracleConnection();
        
        echo '<h2>📡 Status da Conexão</h2>';
        
        if (!$oracle->connect()) {
            echo '<div class="status error">';
            echo '❌ ERRO: Falha ao conectar com Oracle Focco<br>';
            echo 'Detalhes: ' . htmlspecialchars($oracle->getError());
            echo '</div>';
        } else {
            echo '<div class="status success">';
            echo '✅ SUCESSO: Conectado ao Oracle Focco (192.168.2.60:1521/f3ipro)';
            echo '</div>';

            // Executar a query
            $sql = "
                SELECT a.ID, a.num_ordem, a.tipo_ordem, a.dt_inicial, a.dt_final, a.qtde, a.qtde_entregue, 
                       a.qtde_pendente, a.qtde_refugo, a.num_rancho, b.seq, b.tempo, b.final, 
                       c.cod_operacao, c.descricao AS desc_operacao, de.maquina_id, de.cod_maquina, de.desc_maquina
                FROM focco3i.tordens a
                INNER JOIN focco3i.tordens_rot b ON (b.ordem_id=a.id)
                INNER JOIN focco3i.toperacao c ON (c.ID=b.OPERACAO_ID)
                CROSS APPLY (
                    SELECT d.maquina_id, e.cod_maquina, e.descricao AS desc_maquina
                    FROM focco3i.tord_rot_fab_maq d 
                    INNER JOIN focco3i.tmaquinas e ON (e.id=d.maquina_id)
                    WHERE d.torden_rot_id = b.id 
                    ORDER BY d.maquina_id 
                    FETCH FIRST 1 ROW ONLY
                ) de
                WHERE a.num_ordem = :num_ordem
                AND b.seq = :seq
                ORDER BY b.seq
            ";

            echo '<h2>🔄 Executando Query...</h2>';
            
            $params = [
                ':num_ordem' => '18568655',
                ':seq' => '10'
            ];

            $resultados = $oracle->executeQuery($sql, $params);

            if ($resultados === false) {
                echo '<div class="status error">';
                echo '❌ ERRO ao executar query<br>';
                echo 'Detalhes: ' . htmlspecialchars($oracle->getError());
                echo '</div>';
            } else {
                if (empty($resultados)) {
                    echo '<div class="status error">';
                    echo '⚠️ NENHUM RESULTADO ENCONTRADO para ordem 18568655 sequência 10';
                    echo '</div>';
                } else {
                    echo '<div class="status success">';
                    echo '✅ Encontrados ' . count($resultados) . ' registro(s)';
                    echo '</div>';

                    echo '<h2>📊 Resultados</h2>';
                    echo '<table>';
                    echo '<thead><tr>';
                    
                    // Cabeçalhos
                    $primeiraLinha = $resultados[0];
                    foreach (array_keys($primeiraLinha) as $coluna) {
                        echo '<th>' . htmlspecialchars($coluna) . '</th>';
                    }
                    echo '</tr></thead>';
                    
                    echo '<tbody>';
                    foreach ($resultados as $row) {
                        echo '<tr>';
                        foreach ($row as $key => $valor) {
                            // Destaque especial para o campo FINAL
                            if (strtoupper($key) === 'FINAL') {
                                $status = ($valor == 1) ? '✅ FINALIZADA' : '⏳ ATIVA';
                                $style = ($valor == 1) ? 'background: #d4edda; font-weight: bold;' : 'background: #fff3cd; font-weight: bold;';
                                echo '<td style="' . $style . '">' . $status . ' (' . $valor . ')</td>';
                            } else {
                                echo '<td>' . htmlspecialchars($valor ?? '') . '</td>';
                            }
                        }
                        echo '</tr>';
                    }
                    echo '</tbody>';
                    echo '</table>';

                    // Mostrar JSON para debug
                    echo '<h2>📄 JSON (para debug)</h2>';
                    echo '<div class="query-box">';
                    echo '<pre>' . json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
                    echo '</div>';
                }
            }

            $oracle->close();
        }
        ?>

        <hr style="margin: 40px 0;">
        <p style="text-align: center; color: #666;">
            <a href="index.html" style="color: #e74c3c; text-decoration: none; font-weight: bold;">← Voltar para o Sistema PPCP</a>
        </p>
    </div>
</body>
</html>
