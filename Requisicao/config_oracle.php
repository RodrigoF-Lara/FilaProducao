<?php
/**
 * Configuração de Conexão com Oracle (FOCCO)
 * 
 * Este arquivo centraliza as configurações de conexão com o banco de dados Oracle
 * para ser reutilizado em diferentes projetos.
 */

class OracleConnection {
    // Configurações de conexão
    private const DB_USER = 'focco_consulta';
    private const DB_PASS = 'consulta3i08';
    private const DB_CONNECTION_STRING = '192.168.2.60:1521/f3ipro';
    private const DB_CHARSET = 'AL32UTF8';
    
    private $conn = null;
    private $error = null;

    /**
     * Estabelece conexão com o banco Oracle
     * 
     * @return resource|false Retorna a conexão ou false em caso de erro
     */
    public function connect() {
        try {
            $this->conn = oci_connect(
                self::DB_USER,
                self::DB_PASS,
                self::DB_CONNECTION_STRING,
                self::DB_CHARSET
            );

            if (!$this->conn) {
                $e = oci_error();
                $this->error = "Erro de conexão com o Oracle: " . ($e['message'] ?? 'Não foi possível obter detalhes do erro.');
                return false;
            }

            return $this->conn;

        } catch (Exception $e) {
            $this->error = "Exceção ao conectar: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Retorna a conexão ativa
     * 
     * @return resource|null
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Retorna o último erro ocorrido
     * 
     * @return string|null
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Fecha a conexão com o banco
     */
    public function close() {
        if ($this->conn) {
            oci_close($this->conn);
            $this->conn = null;
        }
    }

    /**
     * Executa uma query preparada com bind de parâmetros
     * 
     * @param string $sql Query SQL a ser executada
     * @param array $params Array associativo com os parâmetros (ex: [':codigo' => '123'])
     * @return array|false Retorna array de resultados ou false em caso de erro
     */
    public function executeQuery($sql, $params = []) {
        if (!$this->conn) {
            $this->error = "Conexão não estabelecida";
            return false;
        }

        try {
            // Preparar a query
            $stid = oci_parse($this->conn, $sql);
            if (!$stid) {
                $e = oci_error($this->conn);
                $this->error = 'Erro ao preparar query: ' . ($e['message'] ?? 'Detalhe não disponível.');
                return false;
            }

            // Bind dos parâmetros
            foreach ($params as $key => $value) {
                oci_bind_by_name($stid, $key, $params[$key]);
            }

            // Executar
            $r = oci_execute($stid);
            if (!$r) {
                $e = oci_error($stid);
                $this->error = 'Erro ao executar query: ' . ($e['message'] ?? 'Detalhe não disponível.');
                oci_free_statement($stid);
                return false;
            }

            // Buscar resultados
            $resultados = [];
            while ($row = oci_fetch_assoc($stid)) {
                $resultados[] = $row;
            }

            // Limpar
            oci_free_statement($stid);

            return $resultados;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Executa a procedure COFELMA_REQUISITAR_DEMANDA_OF
     * 
     * @param int $cod_emp Código da empresa (obrigatório)
     * @param int $num_ordem Número da OF (obrigatório)
     * @param string $cod_item Código do item da demanda (obrigatório)
     * @param float $qtde Quantidade a ser requisitada (obrigatório)
     * @param string $cod_func Código do funcionário (obrigatório)
     * @param int|null $tmasc_item_id ID do configurado (opcional - obrigatório para itens configurados)
     * @param int|null $seq_demanda Sequência da demanda (opcional - obrigatório se itens repetidos)
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function executeProcedure($cod_emp, $num_ordem, $cod_item, $qtde, $cod_func, $tmasc_item_id = null, $seq_demanda = null) {
        if (!$this->conn) {
            return ['success' => false, 'error' => 'Conexão não estabelecida'];
        }

        try {
            // Montar o bloco PL/SQL
            $sql = "
                DECLARE
                    v_erro VARCHAR2(4000);
                BEGIN
                    COFELMA_REQUISITAR_DEMANDA_OF(
                        pi_cod_emp => :pi_cod_emp,
                        pi_num_ordem => :pi_num_ordem,
                        pi_cod_item => :pi_cod_item,
                        pi_qtde => :pi_qtde,
                        pi_cod_func => :pi_cod_func,
                        pi_tmasc_item_id => :pi_tmasc_item_id,
                        pi_seq_demanda => :pi_seq_demanda,
                        po_erro => v_erro
                    );
                    :po_erro := v_erro;
                END;
            ";

            // Preparar a query
            $stid = oci_parse($this->conn, $sql);
            if (!$stid) {
                $e = oci_error($this->conn);
                return ['success' => false, 'error' => 'Erro ao preparar procedure: ' . ($e['message'] ?? 'Detalhe não disponível.')];
            }

            // Variável para o erro de saída
            $po_erro = '';

            // Bind dos parâmetros de entrada
            oci_bind_by_name($stid, ':pi_cod_emp', $cod_emp);
            oci_bind_by_name($stid, ':pi_num_ordem', $num_ordem);
            oci_bind_by_name($stid, ':pi_cod_item', $cod_item);
            oci_bind_by_name($stid, ':pi_qtde', $qtde);
            oci_bind_by_name($stid, ':pi_cod_func', $cod_func);
            oci_bind_by_name($stid, ':pi_tmasc_item_id', $tmasc_item_id);
            oci_bind_by_name($stid, ':pi_seq_demanda', $seq_demanda);
            
            // Bind do parâmetro de saída
            oci_bind_by_name($stid, ':po_erro', $po_erro, 4000);

            // Executar
            $r = oci_execute($stid, OCI_COMMIT_ON_SUCCESS);
            if (!$r) {
                $e = oci_error($stid);
                oci_free_statement($stid);
                return ['success' => false, 'error' => 'Erro ao executar procedure: ' . ($e['message'] ?? 'Detalhe não disponível.')];
            }

            // Limpar
            oci_free_statement($stid);

            // Verificar se houve erro retornado pela procedure
            if (!empty($po_erro)) {
                return ['success' => false, 'error' => $po_erro];
            }

            return ['success' => true, 'error' => null];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Exceção: ' . $e->getMessage()];
        }
    }

    /**
     * Destrutor - garante que a conexão seja fechada
     */
    public function __destruct() {
        $this->close();
    }
}

/**
 * Função auxiliar para criar uma nova conexão Oracle
 * 
 * @return OracleConnection
 */
function getOracleConnection() {
    return new OracleConnection();
}
?>
