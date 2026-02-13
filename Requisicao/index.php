<?php

// ========== VERIFICAÇÃO DE AUTENTICAÇÃO ========== 
session_start();
$codigoFoccoUsuario = $_POST['codigo_focco'] ?? $_GET['codigo_focco'] ?? null;
if (!$codigoFoccoUsuario) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Código Focco não fornecido. Não autenticado.']);
    exit;
}

require_once 'config_oracle.php';

// Processar formulário de requisição (manual ou via AJAX)
$is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log de debug para POST recebido
    file_put_contents(__DIR__ . '/log_requisicao.txt', "POST recebido: " . print_r($_POST, true) . "\n", FILE_APPEND);
    $cod_emp = intval($_POST['cod_emp'] ?? 0);
    $num_ordem = intval($_POST['num_ordem'] ?? 0);
    $cod_item = trim($_POST['cod_item'] ?? '');
    // Força a conversão de vírgula para ponto para garantir que o float seja lido corretamente
    $qtde_str = str_replace(',', '.', $_POST['qtde'] ?? '0');
    $qtde = floatval($qtde_str);
    $cod_func = trim($_POST['cod_func'] ?? '');
    $tmasc_item_id = !empty($_POST['tmasc_item_id']) ? intval($_POST['tmasc_item_id']) : null;
    $seq_demanda = !empty($_POST['seq_demanda']) ? intval($_POST['seq_demanda']) : null;
    
    $erros = [];
    if ($cod_emp <= 0) $erros[] = "Código da Empresa é obrigatório";
    if ($num_ordem <= 0) $erros[] = "Número da OF é obrigatório";
    if (empty($cod_item)) $erros[] = "Código do Item é obrigatório";
    if ($qtde <= 0) $erros[] = "Quantidade deve ser maior que zero";
    if (empty($cod_func)) $erros[] = "Código do Funcionário é obrigatório";
    
    if (!empty($erros)) {
        $mensagem = "Erros de validação:<br>• " . implode("<br>• ", $erros);
        $tipo_mensagem = "error";
        file_put_contents(__DIR__ . '/log_requisicao.txt', "Validação falhou: $mensagem\n", FILE_APPEND);
    } else {
        $oracle = getOracleConnection();
        if ($oracle->connect()) {
            // seq_demanda removido
            $resultado = $oracle->executeProcedure($cod_emp, $num_ordem, trim(strtoupper($cod_item)), $qtde, $cod_func, $tmasc_item_id);
            if ($resultado['success']) {
                $mensagem = "Requisição para o item '{$cod_item}' executada com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro na requisição do item '{$cod_item}': " . $resultado['error'];
                $tipo_mensagem = "error";
            }
            $oracle->close();
            file_put_contents(__DIR__ . '/log_requisicao.txt', "Resultado procedure: " . print_r($resultado, true) . "\n", FILE_APPEND);
        } else {
            $mensagem = "Erro de conexão: " . $oracle->getError();
            $tipo_mensagem = "error";
            file_put_contents(__DIR__ . '/log_requisicao.txt', "Erro de conexão: $mensagem\n", FILE_APPEND);
        }
    }
    // Sempre retorna JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => ($tipo_mensagem === 'success'), 'message' => strip_tags($mensagem)]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisição Automática - COFELMA</title>
    <style>
        /* ... Estilos anteriores ... */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f9; min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { background: white; padding: 20px 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; border-left: 5px solid #1e3c72;}
        .main-content { display: grid; grid-template-columns: 350px 1fr; gap: 20px; }
        .form-container, .demandas-container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .demandas-container { grid-column: 2 / 3; }
        .manual-form-container { grid-column: 1 / 2; grid-row: 1 / 3; }
        .search-container { grid-column: 2 / 3; }
        .table-wrapper { margin-top: 20px; max-height: 500px; overflow-y: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        tr:hover { background: #f1f5f8; }
        .qtde-input { width: 80px; padding: 8px; border: 1px solid #ccc; border-radius: 4px; text-align: center; }
        .btn-requisitar-item { background: #28a745; color: white; padding: 6px 12px; font-size: 12px; border: none; border-radius: 4px; cursor: pointer;}
        .btn-requisitar-item:hover { background: #218838; }
        .status-icon { display: inline-block; width: 20px; height: 20px; border-radius: 50%; text-align: center; line-height: 20px; color: white; font-weight: bold; }
        .status-success { background: #28a745; }
        .status-error { background: #dc3545; }
        .status-pending { background: #ffc107; }
        .total-actions { display: flex; justify-content: flex-end; padding: 15px 0; }
        .loader { text-align: center; padding: 20px; }
        .hidden { display: none; }
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; }
        .btn-success { background: #28a745; color: white; }
        .form-section h3 { color: #1e3c72; margin-bottom: 15px; }
        .form-group label { font-weight: 600; color: #333; margin-bottom: 5px; font-size: 14px; }
        .form-group input { padding: 12px 14px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px; width: 100%;}
        .form-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .required { color: #e74c3c; }
        .optional { color: #6c757d; font-size: 12px; }
        .btn-container { display: flex; justify-content: flex-end; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>🔄 Requisição de Demanda OF</h1>
                <p>Sistema de Requisição Automática - COFELMA / FOCCO</p>
            </div>
        </div>

        <?php if (!$is_ajax && $mensagem): ?>
            <div class="alert alert-<?= $tipo_mensagem ?>" style="margin-bottom: 20px;"><?= $mensagem ?></div>
        <?php endif; ?>

        <div class="main-content">
            <!-- Coluna 1: Requisição Manual -->
            <div class="manual-form-container form-container">
                 <form method="POST" action="" id="formRequisicaoManual">
                    <div class="form-section">
                        <h3>Requisição Manual</h3>
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            <div class="form-group">
                                <label>Código da Empresa <span class="required">*</span></label>
                                <input type="number" name="cod_emp" id="manual_cod_emp" value="1" required>
                            </div>
                             <div class="form-group">
                                <label>Código do Funcionário <span class="required">*</span></label>
                                <input type="text" name="cod_func" id="manual_cod_func" required>
                            </div>
                            <div class="form-group">
                                <label>Número da OF <span class="required">*</span></label>
                                <input type="number" name="num_ordem" required>
                            </div>
                            <div class="form-group">
                                <label>Código do Item <span class="required">*</span></label>
                                <input type="text" name="cod_item" style="text-transform: uppercase;" required>
                            </div>
                            <div class="form-group">
                                <label>Quantidade <span class="required">*</span></label>
                                <input type="number" name="qtde" min="0.01" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Sequência da Demanda <span class="optional">(opcional)</span></label>
                                <input type="number" name="seq_demanda">
                            </div>
                        </div>
                    </div>
                    <div class="btn-container">
                        <button type="submit" class="btn btn-primary">📤 Enviar Requisição</button>
                    </div>
                </form>
            </div>

            <!-- Coluna 2: Requisição por OF -->
            <div class="search-container form-container">
                 <div class="form-section">
                    <h3>Buscar Demandas da OF</h3>
                    <div class="form-row" style="align-items: flex-end;">
                        <div class="form-group" style="flex-grow: 1;">
                            <label>Número da OF</label>
                            <input type="number" id="num_ordem_busca" placeholder="Digite o número da OF">
                        </div>
                        <div class="form-group">
                             <button type="button" class="btn btn-primary" id="btn_buscar_of">🔍 Buscar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="demandas-container hidden" id="demandas_resultado">
                <div id="loader" class="loader hidden"><p>Buscando...</p></div>
                <div class="total-actions">
                    <button class="btn btn-success" id="btn_req_todas">📤 Requisitar Todas Pendentes</button>
                </div>
                <div class="table-wrapper">
                    <table id="demandas_tabela">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Item</th>
                                <th>Descrição</th>
                                <th>Qtde. Nec.</th>
                                <th>Qtde. Requis.</th>
                                <th>Qtde. Pend.</th>
                                <th>Saldo Estoque</th>
                                <th>A Requisitar</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const btnBuscar = document.getElementById('btn_buscar_of');
        const btnReqTodas = document.getElementById('btn_req_todas');
        const numOrdemInput = document.getElementById('num_ordem_busca');
        const resultadoContainer = document.getElementById('demandas_resultado');
        const tabelaBody = document.querySelector('#demandas_tabela tbody');
        const loader = document.getElementById('loader');

        btnBuscar.addEventListener('click', buscarDemandas);
        btnReqTodas.addEventListener('click', requisitarTodasPendentes);

        async function buscarDemandas() {
            const numOrdem = numOrdemInput.value;
            const codEmp = document.getElementById('manual_cod_emp').value;
            if (!numOrdem || !codEmp) {
                alert('Por favor, preencha o número da OF e o código da empresa.');
                return;
            }

            loader.classList.remove('hidden');
            resultadoContainer.classList.add('hidden');
            tabelaBody.innerHTML = '';

            try {
                const response = await fetch(`buscar_demandas.php?num_ordem=${numOrdem}&cod_emp=${codEmp}`);
                const result = await response.json();

                // Lógica original restaurada para preencher a tabela
                if (result.success && result.data.length > 0) {
                    result.data.forEach(item => {
                        // O driver Oracle retorna os nomes das colunas em MAIÚSCULAS
                        const row = document.createElement('tr');
                        row.dataset.codItem = item.COD_ITEM;
                        row.dataset.seqDemanda = item.SEQ_DEMANDA;
                        
                        const qtdePendente = item.QTDE_PENDENTE ?? 0;
                        row.dataset.qtdePendente = qtdePendente;

                        row.innerHTML = `
                            <td><span class="status-icon status-pending" title="Pendente">!</span></td>
                            <td>${item.COD_ITEM}</td>
                            <td>${item.DESC_TECNICA || ''}</td>
                            <td>${item.QTDE_NECES ?? 0}</td>
                            <td class="qtde-requis">${item.QTDE_REQUIS ?? 0}</td>
                            <td class="qtde-pendente">${qtdePendente}</td>
                            <td>${item.SALDO_ATUAL ?? 0}</td>
                            <td><input type="number" class="qtde-input" value="${qtdePendente}" min="0" step="0.01"></td>
                            <td>
                                ${qtdePendente > 0 ? '<button class="btn btn-requisitar-item">Requisitar</button>' : 'OK'}
                            </td>
                        `;
                        tabelaBody.appendChild(row);
                    });
                    resultadoContainer.classList.remove('hidden');
                } else if (result.success) {
                    alert('Nenhuma demanda encontrada para esta OF.');
                } else {
                    alert('Erro: ' + result.error);
                }

            } catch (error) {
                alert('Ocorreu um erro na comunicação com o servidor. Verifique o console para mais detalhes.');
                console.error("Erro de Fetch ou Parse JSON:", error);
            } finally {
                loader.classList.add('hidden');
            }
        }

        tabelaBody.addEventListener('click', async (e) => {
            if (e.target.classList.contains('btn-requisitar-item')) {
                const button = e.target;
                const row = button.closest('tr');
                const qtdeInput = row.querySelector('.qtde-input');
                const qtde = parseFloat(qtdeInput.value);

                if (qtde > 0) {
                    button.disabled = true;
                    button.textContent = '...';
                    await requisitarItem(row, qtde);
                    button.disabled = false;
                    button.textContent = 'Requisitar';
                } else {
                    alert('A quantidade a requisitar deve ser maior que zero.');
                }
            }
        });

        async function requisitarTodasPendentes() {
            const codFunc = document.getElementById('manual_cod_func').value;
            if (!codFunc) {
                alert('Por favor, preencha o Código do Funcionário no formulário de Requisição Manual.');
                document.getElementById('manual_cod_func').focus();
                return;
            }

            const rows = tabelaBody.querySelectorAll('tr');
            this.disabled = true;
            this.textContent = 'Requisitando...';

            for (const row of rows) {
                const qtdeInput = row.querySelector('.qtde-input');
                const qtde = parseFloat(qtdeInput.value);
                const isPendente = parseFloat(row.dataset.qtdePendente) > 0;
                
                // Só requisita se tiver quantidade e se ainda não foi processado com sucesso
                if (qtde > 0 && isPendente) {
                    await requisitarItem(row, qtde);
                }
            }
            
            this.disabled = false;
            this.textContent = '📤 Requisitar Todas Pendentes';
        }

        async function requisitarItem(row, qtde) {
            const statusIcon = row.querySelector('.status-icon');
            const formData = new FormData();
            formData.append('cod_emp', document.getElementById('manual_cod_emp').value);
            formData.append('cod_func', document.getElementById('manual_cod_func').value);
            formData.append('num_ordem', numOrdemInput.value);
            formData.append('cod_item', row.dataset.codItem);
            formData.append('seq_demanda', row.dataset.seqDemanda);
            formData.append('qtde', qtde);

            try {
                const response = await fetch('index.php', { method: 'POST', body: formData });
                const result = await response.json();

                statusIcon.className = 'status-icon'; // Reset class
                if (result.success) {
                    statusIcon.classList.add('status-success');
                    statusIcon.textContent = '✓';
                    statusIcon.title = result.message;
                    
                    // Atualiza UI
                    const qtdeRequisCell = row.querySelector('.qtde-requis');
                    const qtdePendenteCell = row.querySelector('.qtde-pendente');
                    const newRequis = parseFloat(qtdeRequisCell.textContent) + qtde;
                    const newPendente = parseFloat(row.dataset.qtdePendente) - qtde;
                    
                    qtdeRequisCell.textContent = newRequis.toFixed(2);
                    qtdePendenteCell.textContent = newPendente.toFixed(2);
                    row.dataset.qtdePendente = newPendente; // Atualiza pendente original
                    row.querySelector('.qtde-input').value = newPendente.toFixed(2);

                    if (newPendente <= 0) {
                         row.querySelector('td:last-child').innerHTML = 'OK';
                    }

                } else {
                    statusIcon.classList.add('status-error');
                    statusIcon.textContent = 'X';
                    statusIcon.title = result.message;
                }
            } catch (error) {
                statusIcon.className = 'status-icon status-error';
                statusIcon.textContent = 'X';
                statusIcon.title = 'Erro de comunicação.';
                console.error(error);
            }
        }
    </script>
</body>
</html>