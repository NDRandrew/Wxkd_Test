<?php
// Add these methods to your EnhancedInventoryModel class in Testy (3).txt

    /**
     * Get materials currently in use by a specific employee
     */
    function getMaterialsInUseByEmployee($cod_func){
        $query = "SELECT 
                    I.*,
                    F.nome_func,
                    F.E_MAIL as email_func
                  FROM INFRA.DBO.TB_INVENTARIO_BE I
                  LEFT JOIN MESU.DBO.FUNCIONARIOS F ON I.cod_func = F.COD_FUNC
                  WHERE I.cod_func = $cod_func AND I.sts_equip = 'EM USO'
                  ORDER BY I.tipo, I.marca, I.modelo";
        
        $dados = $this->sqlDb->select($query);
        return $dados ? $dados : array();
    }

    /**
     * Get all available materials that can be assigned
     */
    function getAvailableMaterials(){
        $query = "SELECT 
                    I.*
                  FROM INFRA.DBO.TB_INVENTARIO_BE I
                  WHERE I.sts_equip = 'DISPONIVEL' 
                  AND I.tipo IN ('NOTEBOOK', 'DESKTOP', 'DISPOSITIVOS', 'KIT TECLADO/MOUSE')
                  ORDER BY I.tipo, I.marca, I.modelo";
        
        $dados = $this->sqlDb->select($query);
        return $dados ? $dados : array();
    }

    /**
     * Get single equipment data for assignment operations
     */
    function getEquipmentForAssignment($id_equip){
        $query = "SELECT * FROM INFRA.DBO.TB_INVENTARIO_BE WHERE id = $id_equip";
        $dados = $this->sqlDb->select($query);
        return $dados;
    }

    /**
     * Log assignment transaction
     */
    function logAssignmentTransaction($id_equip, $old_status, $new_status, $old_cod_func, $new_cod_func){
        $data = getDate();
        $data = $data['year'].'-'.$data['mon'].'-'.$data['mday'];
        
        $query = "INSERT INTO INFRA.DBO.TB_TRANSICOES_INV (sts_atual, cod_func_atual, sts_ant, cod_func_antigo, data_modifi, id_equip) 
                  VALUES ('$new_status', " . ($new_cod_func ? $new_cod_func : 'NULL') . ", '$old_status', " . ($old_cod_func ? $old_cod_func : 'NULL') . ", '$data', $id_equip)";
        
        $dados = $this->sqlDb->insert($query);
        return $dados;
    }

    /**
     * Assign material to employee
     */
    function assignMaterialToEmployee($id_equip, $cod_func){
        // First get the equipment current state
        $equipBefore = $this->getEquipmentForAssignment($id_equip);
        
        if(!$equipBefore || empty($equipBefore)){
            return false;
        }
        
        // Check if equipment is available
        if($equipBefore[0]['sts_equip'] != 'DISPONIVEL'){
            return false;
        }
        
        // Update equipment status and assign to employee
        $query = "UPDATE INFRA.DBO.TB_INVENTARIO_BE 
                  SET sts_equip = 'EM USO', cod_func = $cod_func 
                  WHERE id = $id_equip AND sts_equip = 'DISPONIVEL'";
        
        $result = $this->sqlDb->update($query);
        
        if($result){
            // Log the transaction
            $old_status = $equipBefore[0]['sts_equip'];
            $old_cod_func = $equipBefore[0]['cod_func'];
            $new_status = 'EM USO';
            $new_cod_func = $cod_func;
            
            $this->logAssignmentTransaction($id_equip, $old_status, $new_status, $old_cod_func, $new_cod_func);
            
            return true;
        }
        
        return false;
    }

    /**
     * Remove material from employee (set back to available)
     */
    function removeMaterialFromEmployee($id_equip, $cod_func){
        // First get the equipment current state
        $equipBefore = $this->getEquipmentForAssignment($id_equip);
        
        if(!$equipBefore || empty($equipBefore)){
            return false;
        }
        
        // Verify the equipment belongs to this employee and is in use
        if($equipBefore[0]['cod_func'] != $cod_func || $equipBefore[0]['sts_equip'] != 'EM USO'){
            return false;
        }
        
        // Update equipment status back to available
        $query = "UPDATE INFRA.DBO.TB_INVENTARIO_BE 
                  SET sts_equip = 'DISPONIVEL', cod_func = NULL 
                  WHERE id = $id_equip AND cod_func = $cod_func";
        
        $result = $this->sqlDb->update($query);
        
        if($result){
            // Log the transaction
            $old_status = $equipBefore[0]['sts_equip'];
            $old_cod_func = $equipBefore[0]['cod_func'];
            $new_status = 'DISPONIVEL';
            $new_cod_func = null;
            
            $this->logAssignmentTransaction($id_equip, $old_status, $new_status, $old_cod_func, $new_cod_func);
            
            return true;
        }
        
        return false;
    }

    /**
     * Search employees by name or code
     */
    function searchEmployees($search = ''){
        $whereClause = "";
        if(!empty($search)){
            $whereClause = "AND (A.nome_func LIKE '%$search%' OR A.cod_func LIKE '%$search%')";
        }
        
        $query = "SELECT DISTINCT
                    A.cod_func,
                    A.nome_func,
                    A.E_MAIL AS Email_Func,
                    A.SECAO
                  FROM MESU..FUNCIONARIOS AS A
                  WHERE A.DT_TransDem IS NULL $whereClause
                  ORDER BY A.nome_func";
        
        $dados = $this->sqlDb->select($query);
        return $dados ? $dados : array();
    }

    /**
     * Get employee details for assignment
     */
    function getEmployeeForAssignment($cod_func){
        $query = "SELECT 
                    A.cod_func,
                    A.nome_func,
                    A.E_MAIL AS Email_Func,
                    A.RAMAL,
                    A.RAMAL_INTERNO,
                    A.DDD_CEL_CORPORATIVO,
                    A.CELULAR_CORPORATIVO,
                    A.SECAO
                FROM MESU..FUNCIONARIOS AS A
                WHERE A.cod_func = $cod_func AND A.DT_TransDem IS NULL";

        $dados = $this->sqlDb->select($query);

        if($dados){
            return $dados;
        } else {
            // Try alternate employee table if not found in main table
            $query = "SELECT
                        B.idFuncionario AS cod_func,
                        B.nomeFuncionario AS nome_func,
                        '' as Email_Func,
                        B.foneCelular AS RAMAL,
                        '' AS RAMAL_INTERNO,
                        '' AS DDD_CEL_CORPORATIVO,
                        '' AS CELULAR_CORPORATIVO,
                        '' AS SECAO
                    FROM 
                        RH..STG_FUNCIONARIOS AS B 
                    WHERE 
                        B.dataDemissao IS NULL AND
                        B.idFuncionario = $cod_func";
            
            $dados = $this->sqlDb->select($query);
            return $dados ? $dados : array();
        }
    }
?>



---------



<?php
// Create this file as: atribuir_material_ajax.php

@session_start();
if($_SESSION['cod_usu'] == ''){   
    echo 'ERROR|Sessão expirada';
    die();
}

require_once('Inventario/indexValueController.php');
$consulta = new EnhancedInventoryModel();

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Helper function to format array data for output
function formatArrayData($data) {
    $output = "";
    if(is_array($data) && count($data) > 0) {
        foreach($data as $row) {
            $rowData = "";
            foreach($row as $key => $value) {
                $rowData .= $key . ":" . $value . ";";
            }
            $output .= rtrim($rowData, ";") . "|";
        }
        return rtrim($output, "|");
    }
    return "";
}

switch($action){
    case 'search_employees':
        $search = isset($_POST['search']) ? $_POST['search'] : '';
        $employees = $consulta->searchEmployees($search);
        if($employees) {
            echo 'SUCCESS|' . formatArrayData($employees);
        } else {
            echo 'SUCCESS|';
        }
        break;
        
    case 'get_employee_materials':
        $cod_func = isset($_POST['cod_func']) ? intval($_POST['cod_func']) : 0;
        if($cod_func > 0){
            $materials = $consulta->getMaterialsInUseByEmployee($cod_func);
            $employee = $consulta->getEmployeeForAssignment($cod_func);
            
            $response = 'SUCCESS|MATERIALS:';
            if($materials) {
                $response .= formatArrayData($materials);
            }
            $response .= '||EMPLOYEE:';
            if($employee) {
                $response .= formatArrayData($employee);
            }
            echo $response;
        } else {
            echo 'ERROR|Código do funcionário inválido';
        }
        break;
        
    case 'get_available_materials':
        $materials = $consulta->getAvailableMaterials();
        if($materials) {
            echo 'SUCCESS|' . formatArrayData($materials);
        } else {
            echo 'SUCCESS|';
        }
        break;
        
    case 'assign_material':
        $id_equip = isset($_POST['id_equip']) ? intval($_POST['id_equip']) : 0;
        $cod_func = isset($_POST['cod_func']) ? intval($_POST['cod_func']) : 0;
        
        if($id_equip > 0 && $cod_func > 0){
            $result = $consulta->assignMaterialToEmployee($id_equip, $cod_func);
            if($result){
                echo 'SUCCESS|Material atribuído com sucesso';
            } else {
                echo 'ERROR|Erro ao atribuir material';
            }
        } else {
            echo 'ERROR|Dados inválidos';
        }
        break;
        
    case 'remove_material':
        $id_equip = isset($_POST['id_equip']) ? intval($_POST['id_equip']) : 0;
        $cod_func = isset($_POST['cod_func']) ? intval($_POST['cod_func']) : 0;
        
        if($id_equip > 0 && $cod_func > 0){
            $result = $consulta->removeMaterialFromEmployee($id_equip, $cod_func);
            if($result){
                echo 'SUCCESS|Material removido com sucesso';
            } else {
                echo 'ERROR|Erro ao remover material';
            }
        } else {
            echo 'ERROR|Dados inválidos';
        }
        break;
        
    default:
        echo 'ERROR|Ação não reconhecida';
        break;
}
?>