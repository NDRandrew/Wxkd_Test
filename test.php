<?php

require_once('\\\\mz-vv-fs-237\D4920\Secoes\D4920S012\Comum_S012\j\Server2Go\htdocs\erp\ClassRepository\geral\MSSQL\MSSQL.class.php');
require_once("../../view/assets/dompdf/dompdf/dompdf_config.inc.php");

@session_start();

class EnhancedInventoryModel {
    
    private $sqlDb;
    private $sqlTeste;
    
    function __construct(){
        $this->sqlDb = new MSSQL("MESU");
        $this->sqlTeste = new MSSQL("TESTE");
    }

    // ==================== INVENTORY METHODS ====================
    
    /**
     * Get all inventory items
     */
    function selectInventario(){
        $query = 'SELECT * FROM INFRA.DBO.TB_INVENTARIO_BE ORDER BY id DESC';
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Get inventory count
     */
    function getInventarioCount(){
        $query = 'SELECT COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE';
        $result = $this->sqlDb->select($query);
        return isset($result[0]['total']) ? $result[0]['total'] : 0;
    }

    /**
     * Get inventory count by status
     */
    function getInventarioCountByStatus($status = null){
        if($status){
            $query = "SELECT COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE WHERE sts_equip = '$status'";
        } else {
            $query = "SELECT sts_equip, COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE GROUP BY sts_equip";
        }
        $result = $this->sqlDb->select($query);
        return $result;
    }

    /**
     * Get inventory with pagination
     */
    function getInventarioPaginated($page = 1, $itemsPerPage = 20){
        $offset = ($page - 1) * $itemsPerPage;
        $query = "SELECT * FROM INFRA.DBO.TB_INVENTARIO_BE 
                  ORDER BY id DESC 
                  OFFSET $offset ROWS 
                  FETCH NEXT $itemsPerPage ROWS ONLY";
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Search equipment by ID
     */
    function searchById($id){
        $query = "SELECT * FROM INFRA.DBO.TB_INVENTARIO_BE WHERE ID = $id";
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Get single equipment with formatted date
     */
    function selectEquip($id){
        $query = "SELECT *,CONVERT(varchar,dt_compra,103) as dt_compra_form 
                  FROM INFRA.DBO.TB_INVENTARIO_BE WHERE id = $id";
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Filter equipment by status
     */
    function filtroTb($tipo){
        $query = "SELECT * FROM INFRA.DBO.TB_INVENTARIO_BE WHERE sts_equip = '$tipo' ORDER BY id";
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Insert new equipment
     */
    function insertNovoEquip($query){
        $dados = $this->sqlDb->insert($query);
        return $dados;
    }

    /**
     * Update equipment
     */
    function updateEquip($query){
        $dados = $this->sqlDb->update($query);
        return $dados;
    }

    /**
     * Delete equipment
     */
    function deletarEquip($id){
        $query = "DELETE FROM INFRA.DBO.TB_INVENTARIO_BE WHERE id = $id";
        $dados = $this->sqlDb->delete($query);
        return $dados;
    }

    // ==================== TRANSACTION METHODS ====================

    /**
     * Get all transactions
     */
    function selectTransacoes(){
        $query = 'SELECT 
                    A.id AS id_trans, 
                    A.sts_ant,
                    A.cod_func_antigo,
                    A.sts_atual, 
                    A.cod_func_atual,
                    B.NOME_FUNC as nome_func,
                    CONVERT(VARCHAR,A.data_modifi,103) AS dt_trans,
                    A.id_equip,
                    A.TERMO_RESP,
                    A.TERMO_DEV
                FROM 
                    INFRA.DBO.TB_TRANSICOES_INV A 
                JOIN 
                    MESU.DBO.FUNCIONARIOS B ON A.cod_func_atual = B.COD_FUNC or A.cod_func_antigo = B.COD_FUNC
                JOIN 
                    INFRA.DBO.TB_INVENTARIO_BE C ON A.id_equip = C.id
                ORDER BY A.id';
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Get transaction count
     */
    function getTransactionCount(){
        $query = 'SELECT COUNT(*) as total FROM INFRA.DBO.TB_TRANSICOES_INV';
        $result = $this->sqlDb->select($query);
        return isset($result[0]['total']) ? $result[0]['total'] : 0;
    }

    /**
     * Get transactions with pagination
     */
    function getTransactionsPaginated($page = 1, $itemsPerPage = 20){
        $offset = ($page - 1) * $itemsPerPage;
        $query = "SELECT 
                    A.id AS id_trans, 
                    A.sts_ant,
                    A.cod_func_antigo,
                    A.sts_atual, 
                    A.cod_func_atual,
                    B.NOME_FUNC as nome_func,
                    CONVERT(VARCHAR,A.data_modifi,103) AS dt_trans,
                    A.id_equip,
                    A.TERMO_RESP,
                    A.TERMO_DEV
                FROM 
                    INFRA.DBO.TB_TRANSICOES_INV A 
                JOIN 
                    MESU.DBO.FUNCIONARIOS B ON A.cod_func_atual = B.COD_FUNC or A.cod_func_antigo = B.COD_FUNC
                JOIN 
                    INFRA.DBO.TB_INVENTARIO_BE C ON A.id_equip = C.id
                ORDER BY A.id
                OFFSET $offset ROWS 
                FETCH NEXT $itemsPerPage ROWS ONLY";
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Search transactions by equipment ID
     */
    function searchByIdTrans($id){
        $query = "SELECT 
                    A.id AS id_trans, 
                    A.sts_ant,
                    A.cod_func_antigo,
                    A.sts_atual, 
                    A.cod_func_atual,
                    B.NOME_FUNC as nome_func,
                    CONVERT(VARCHAR,A.data_modifi,103) AS dt_trans,
                    A.id_equip,
                    A.TERMO_RESP,
                    A.TERMO_DEV
                FROM 
                    INFRA.DBO.TB_TRANSICOES_INV A 
                JOIN 
                    MESU.DBO.FUNCIONARIOS B ON A.cod_func_atual = B.COD_FUNC or A.cod_func_antigo = B.COD_FUNC
                JOIN 
                    INFRA.DBO.TB_INVENTARIO_BE C ON A.id_equip = C.id
                WHERE A.id_equip = $id";
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Get single transaction details
     */
    function selectTransacoesOne($id){
        $query = 'SELECT 
                    A.id as id_trans, 
                    A.sts_ant,
                    A.cod_func_antigo,
                    A.sts_atual, 
                    A.cod_func_atual,
                    B.NOME_FUNC as nome_func,
                    CONVERT(VARCHAR,A.data_modifi,103) AS dt_trans,
                    A.id_equip,
                    A.TERMO_RESP,
                    A.TERMO_DEV,
                    C.*
                FROM 
                    INFRA.DBO.TB_TRANSICOES_INV A 
                JOIN 
                    MESU.DBO.FUNCIONARIOS B ON A.cod_func_atual = B.COD_FUNC or A.cod_func_antigo = B.COD_FUNC
                JOIN 
                    INFRA.DBO.TB_INVENTARIO_BE C ON A.id_equip = C.id
                WHERE A.id = '.$id;

        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Create new transition
     */
    function fazerTransicao($after, $before){
        if($after[0]['sts_equip'] != $before[0]['sts_equip'] || $after[0]['cod_func'] != $before[0]['cod_func']){
            $data = getDate();
            $data = $data['year'].'-'.$data['mon'].'-'.$data['mday'];

            $cod_uso_after = ($after[0]['sts_equip'] == 'EM USO') ? $after[0]['cod_func'] : 'NULL';
            $cod_uso_before = ($before[0]['sts_equip'] == 'EM USO') ? $before[0]['cod_func'] : 'NULL';

            $query = "INSERT INTO INFRA..TB_TRANSICOES_INV (sts_atual, cod_func_atual, sts_ant, cod_func_antigo, data_modifi, id_equip) 
                      VALUES ('".$before[0]['sts_equip']."', ".$cod_uso_before.", '".$after[0]['sts_equip']."', ".$cod_uso_after.", '".$data."', ".$before[0]['id'].")";

            $dados = $this->sqlDb->insert($query);
            return $dados;
        }
        return 0;
    }

    /**
     * Delete transaction
     */
    function deletarTrans($id){
        $query = "DELETE FROM INFRA.DBO.TB_TRANSICOES_INV WHERE id = $id";
        $dados = $this->sqlDb->delete($query);
        return $dados;
    }

    /**
     * Update transaction terms
     */
    function updateTermo($query){ 
        $dados = $this->sqlDb->update($query);
        return $dados;
    }

    /**
     * Get transaction date for return
     */
    function dataDev($id, $cod_func){
        $query = 'SELECT CONVERT(VARCHAR, data_modifi, 103) AS DATA_MODIFI 
                  FROM INFRA.DBO.TB_TRANSICOES_INV
                  WHERE id_equip = '.$id.' AND cod_func_atual = '.$cod_func;
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    // ==================== EMPLOYEE METHODS ====================

    /**
     * Get employee data
     */
    function selectOne($cod){
        $query = "SELECT 
                    A.cod_func,
                    A.nome_func,
                    A.E_MAIL AS Email_Func,
                    RAMAL,
                    RAMAL_INTERNO,
                    DDD_CEL_CORPORATIVO,
                    CELULAR_CORPORATIVO
                FROM MESU..FUNCIONARIOS AS A
                WHERE cod_func = $cod";

        $dados = $this->sqlDb->select($query);

        if($dados){
            return $dados;
        } else {
            $query = "SELECT
                        B.idFuncionario AS cod_func,
                        B.nomeFuncionario AS nome_func,
                        A.E_MAIL as Email_Func,
                        B.foneCelular AS RAMAL,
                        RAMAL_INTERNO,
                        DDD_CEL_CORPORATIVO,
                        CELULAR_CORPORATIVO
                    FROM 
                        MESU..FUNCIONARIOS AS A
                    RIGHT JOIN 
                        RH..STG_FUNCIONARIOS AS B ON A.COD_FUNC = B.idFuncionario
                    WHERE 
                        B.dataDemissao IS NULL AND
                        B.idFuncionario = $cod";
            
            $dados = $this->sqlDb->select($query);
            return $dados;
        }
    }

    // ==================== STATISTICS AND REPORTING METHODS ====================

    /**
     * Get dashboard statistics
     */
    function getDashboardStats(){
        $stats = array();
        
        // Total equipment count
        $stats['total_equipment'] = $this->getInventarioCount();
        
        // Equipment by status
        $statusCount = $this->getInventarioCountByStatus();
        $stats['by_status'] = array();
        if(is_array($statusCount)){
            foreach($statusCount as $status){
                $stats['by_status'][$status['sts_equip']] = $status['total'];
            }
        }
        
        // Total transactions
        $stats['total_transactions'] = $this->getTransactionCount();
        
        // Recent transactions (last 30 days)
        $query = "SELECT COUNT(*) as total FROM INFRA.DBO.TB_TRANSICOES_INV 
                  WHERE data_modifi >= DATEADD(day, -30, GETDATE())";
        $result = $this->sqlDb->select($query);
        $stats['recent_transactions'] = isset($result[0]['total']) ? $result[0]['total'] : 0;
        
        // Equipment by type
        $query = "SELECT tipo, COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE GROUP BY tipo";
        $typeCount = $this->sqlDb->select($query);
        $stats['by_type'] = array();
        if(is_array($typeCount)){
            foreach($typeCount as $type){
                $stats['by_type'][$type['tipo']] = $type['total'];
            }
        }
        
        return $stats;
    }

    /**
     * Get equipment usage report
     */
    function getUsageReport($startDate = null, $endDate = null){
        $whereClause = "";
        if($startDate && $endDate){
            $whereClause = "WHERE A.data_modifi BETWEEN '$startDate' AND '$endDate'";
        }
        
        $query = "SELECT 
                    A.id_equip,
                    C.tipo,
                    C.marca,
                    C.modelo,
                    C.hostname,
                    COUNT(*) as total_transitions,
                    MAX(A.data_modifi) as last_transition
                FROM 
                    INFRA.DBO.TB_TRANSICOES_INV A
                JOIN 
                    INFRA.DBO.TB_INVENTARIO_BE C ON A.id_equip = C.id
                $whereClause
                GROUP BY A.id_equip, C.tipo, C.marca, C.modelo, C.hostname
                ORDER BY total_transitions DESC";
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Get employee equipment history
     */
    function getEmployeeEquipmentHistory($cod_func){
        $query = "SELECT 
                    A.id_trans,
                    A.sts_ant,
                    A.sts_atual,
                    CONVERT(VARCHAR,A.data_modifi,103) AS dt_trans,
                    C.*
                FROM 
                    INFRA.DBO.TB_TRANSICOES_INV A
                JOIN 
                    INFRA.DBO.TB_INVENTARIO_BE C ON A.id_equip = C.id
                WHERE 
                    A.cod_func_atual = $cod_func OR A.cod_func_antigo = $cod_func
                ORDER BY A.data_modifi DESC";
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Search equipment with multiple criteria
     */
    function searchEquipment($criteria){
        $whereClauses = array();
        
        if(!empty($criteria['id'])){
            $whereClauses[] = "id = " . intval($criteria['id']);
        }
        
        if(!empty($criteria['tipo'])){
            $whereClauses[] = "tipo LIKE '%" . $criteria['tipo'] . "%'";
        }
        
        if(!empty($criteria['marca'])){
            $whereClauses[] = "marca LIKE '%" . $criteria['marca'] . "%'";
        }
        
        if(!empty($criteria['modelo'])){
            $whereClauses[] = "modelo LIKE '%" . $criteria['modelo'] . "%'";
        }
        
        if(!empty($criteria['hostname'])){
            $whereClauses[] = "hostname LIKE '%" . $criteria['hostname'] . "%'";
        }
        
        if(!empty($criteria['status'])){
            $whereClauses[] = "sts_equip = '" . $criteria['status'] . "'";
        }
        
        if(!empty($criteria['cod_func'])){
            $whereClauses[] = "cod_func = " . intval($criteria['cod_func']);
        }
        
        $whereClause = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";
        
        $query = "SELECT * FROM INFRA.DBO.TB_INVENTARIO_BE $whereClause ORDER BY id DESC";
        $dados = $this->sqlDb->select($query);
        
        return $dados;
    }

    /**
     * Get equipment value statistics
     */
    function getValueStatistics(){
        $query = "SELECT 
                    COUNT(*) as total_items,
                    SUM(CAST(REPLACE(REPLACE(val_compra, 'R$', ''), ',', '.') AS FLOAT)) as total_value,
                    AVG(CAST(REPLACE(REPLACE(val_compra, 'R$', ''), ',', '.') AS FLOAT)) as average_value,
                    MIN(CAST(REPLACE(REPLACE(val_compra, 'R$', ''), ',', '.') AS FLOAT)) as min_value,
                    MAX(CAST(REPLACE(REPLACE(val_compra, 'R$', ''), ',', '.') AS FLOAT)) as max_value
                FROM INFRA.DBO.TB_INVENTARIO_BE 
                WHERE val_compra IS NOT NULL AND val_compra != ''";
        
        $result = $this->sqlDb->select($query);
        return isset($result[0]) ? $result[0] : array();
    }

    // ==================== UTILITY METHODS ====================

    /**
     * Get row count for any table
     */
    function getRowCount($table, $whereClause = ""){
        $query = "SELECT COUNT(*) as total FROM $table";
        if($whereClause){
            $query .= " WHERE $whereClause";
        }
        
        $result = $this->sqlDb->select($query);
        return isset($result[0]['total']) ? $result[0]['total'] : 0;
    }

    /**
     * Execute custom query with row count
     */
    function executeQueryWithCount($query){
        $dados = $this->sqlDb->select($query);
        $count = is_array($dados) ? count($dados) : 0;
        
        return array(
            'data' => $dados,
            'count' => $count
        );
    }

    /**
     * Validate equipment data before insert/update
     */
    function validateEquipmentData($data){
        $errors = array();
        
        if(empty($data['tipo'])){
            $errors[] = "Tipo é obrigatório";
        }
        
        if(empty($data['marca'])){
            $errors[] = "Marca é obrigatória";
        }
        
        if(empty($data['modelo'])){
            $errors[] = "Modelo é obrigatório";
        }
        
        if(empty($data['sts_equip'])){
            $errors[] = "Status do equipamento é obrigatório";
        }
        
        // Check if hostname already exists (for new equipment)
        if(!empty($data['hostname']) && empty($data['id'])){
            $existing = $this->searchEquipment(array('hostname' => $data['hostname']));
            if(!empty($existing)){
                $errors[] = "Hostname já existe no sistema";
            }
        }
        
        return $errors;
    }

    /**
     * Get equipment count with detailed breakdown
     */
    function getDetailedEquipmentCount(){
        $result = array();
        
        // Total count
        $result['total'] = $this->getInventarioCount();
        
        // Count by status
        $statusQuery = "SELECT sts_equip, COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE GROUP BY sts_equip";
        $statusResult = $this->sqlDb->select($statusQuery);
        $result['by_status'] = array();
        
        if(is_array($statusResult)){
            foreach($statusResult as $row){
                $result['by_status'][$row['sts_equip']] = $row['total'];
            }
        }
        
        // Count by type
        $typeQuery = "SELECT tipo, COUNT(*) as total FROM INFRA.DBO.TB_INVENTARIO_BE GROUP BY tipo";
        $typeResult = $this->sqlDb->select($typeQuery);
        $result['by_type'] = array();
        
        if(is_array($typeResult)){
            foreach($typeResult as $row){
                $result['by_type'][$row['tipo']] = $row['total'];
            }
        }
        
        return $result;
    }

    /**
     * Get equipment that needs attention (old, no recent transactions, etc.)
     */
    function getEquipmentNeedingAttention(){
        $query = "SELECT 
                    inv.*,
                    DATEDIFF(year, inv.dt_compra, GETDATE()) as years_old,
                    (SELECT MAX(data_modifi) FROM INFRA.DBO.TB_TRANSICOES_INV WHERE id_equip = inv.id) as last_transaction
                FROM INFRA.DBO.TB_INVENTARIO_BE inv
                WHERE 
                    DATEDIFF(year, inv.dt_compra, GETDATE()) > 5 
                    OR inv.sts_equip = 'DESCARTE'
                    OR inv.sts_equip = 'PADRONIZAR'
                ORDER BY inv.dt_compra ASC";
        
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Generate simple report with counts
     */
    function generateSimpleReport(){
        $report = array();
        
        $report['inventory_summary'] = array(
            'total_equipment' => $this->getInventarioCount(),
            'total_transactions' => $this->getTransactionCount()
        );
        
        // Equipment counts by status
        $statusCounts = $this->getInventarioCountByStatus();
        $report['equipment_by_status'] = array();
        
        if(is_array($statusCounts)){
            foreach($statusCounts as $status){
                $report['equipment_by_status'][$status['sts_equip']] = $status['total'];
            }
        }
        
        // Recent activity (last 7 days)
        $recentQuery = "SELECT COUNT(*) as total FROM INFRA.DBO.TB_TRANSICOES_INV 
                       WHERE data_modifi >= DATEADD(day, -7, GETDATE())";
        $recentResult = $this->sqlDb->select($recentQuery);
        $report['recent_activity'] = isset($recentResult[0]['total']) ? $recentResult[0]['total'] : 0;
        
        return $report;
    }
}

// Initialize the class (maintain backward compatibility)
$consulta = new EnhancedInventoryModel();
$inventoryModel = new EnhancedInventoryModel();

?>