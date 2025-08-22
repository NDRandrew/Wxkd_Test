<?php
// In TestC (Controller), replace the validateRecordsForTXTExport method with this:

private function validateRecordsForTXTExport($data, $filter = 'cadastramento') {
    $invalidRecords = array();
    $cutoff = Wxkd_Config::getCutoffTimestamp();
    
    foreach ($data as $row) {
        // CHECK ACC_FLAG FIRST - if it's 1, skip all validation
        if (isset($row['ACC_FLAG']) && $row['ACC_FLAG'] == '1') {
            continue; // Skip validation for this record - it's already approved by Access export
        }
        
        $activeTypes = $this->getActiveTypes($row, $cutoff);
        $codEmpresa = $this->getEmpresaCode($row);
        
        if (empty($codEmpresa) || empty($activeTypes)) {
            continue;
        }
        
        $isValid = true;
        $errorMessage = '';
        
        foreach ($activeTypes as $type) {
            if ($type === 'AV' || $type === 'PR' || $type === 'UN') {
                $basicValidation = $this->checkBasicValidations($row);
                if ($basicValidation !== true) {
                    $isValid = false;
                    $errorMessage = 'Tipo ' . $type . ' - ' . $basicValidation;
                    break;
                }
            } elseif ($type === 'OP') {
                $opValidation = $this->checkOPValidations($row);
                if ($opValidation !== true) {
                    $isValid = false;
                    $errorMessage = $opValidation;
                    break;
                }
            }
        }
        
        // For descadastramento, add additional validation if needed
        if ($filter === 'descadastramento' && $isValid) {
            // Add any descadastramento-specific validation here
            // For now, keep same validation rules
        }
        
        if (!$isValid) {
            $invalidRecords[] = array(
                'cod_empresa' => $codEmpresa,
                'error' => $errorMessage
            );
        }
    }
    
    return $invalidRecords;
}

// Also update the checkBasicValidations method to check ACC_FLAG:
private function checkBasicValidations($row) {
    // CHECK ACC_FLAG FIRST
    if (isset($row['ACC_FLAG']) && $row['ACC_FLAG'] == '1') {
        return true; // Bypass validation if ACC_FLAG is set
    }
    
    $requiredFields = Wxkd_Config::getValidationFields('basic');
    
    $missingFields = array();
    foreach ($requiredFields as $field => $name) {
        if (!isset($row[$field]) || $row[$field] != '1') {
            $missingFields[] = $name;
        }
    }
    
    if (!empty($missingFields)) {
        return 'Validações pendentes: ' . implode(', ', $missingFields);
    }
    
    return true;
}

// And update the checkOPValidations method to check ACC_FLAG:
private function checkOPValidations($row) {
    // CHECK ACC_FLAG FIRST
    if (isset($row['ACC_FLAG']) && $row['ACC_FLAG'] == '1') {
        return true; // Bypass validation if ACC_FLAG is set
    }
    
    $tipoContrato = isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '';
    $version = $this->extractVersionFromContract($tipoContrato);
    
    if ($version === null) {
        return 'Tipo de contrato não pode ser exportado: ' . $tipoContrato;
    }
    
    $requiredFields = Wxkd_Config::getValidationFields('op');
    
    if (Wxkd_Config::requiresSegundaViaValidation($version)) {
        $requiredFields['SEGUNDA_VIA_CARTAO_VALID'] = 'Segunda Via Cartão';
    }
    
    $missingFields = array();
    foreach ($requiredFields as $field => $name) {
        if (!isset($row[$field]) || $row[$field] != '1') {
            $missingFields[] = $name;
        }
    }
    
    if (!empty($missingFields)) {
        return 'Validações OP pendentes (v' . $version . '): ' . implode(', ', $missingFields);
    }
    
    return true;
}
?>


----------


<?php
// In TestM (Model), update the getTableDataByFilter method
// Make sure the 'all' and 'cadastramento' cases include ACC_FLAG:

case 'cadastramento':
    $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
            " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
            AND H.WXKD_FLAG = 0";
    break;

// And for the 'all' case:
default: 
    $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
            " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
            AND H.WXKD_FLAG IN (0,1)";
    break;

// For historico case, make sure ACC_FLAG is included in the log table query as well:
case 'historico':
    $query = "SELECT 
                CHAVE_LOTE,
                DATA_LOG,
                COUNT(*) as TOTAL_REGISTROS,
                FILTRO
            FROM PGTOCORSP.dbo.TB_WXKD_LOG
            WHERE CONFIRMADO_FLAG = 0 
            GROUP BY CHAVE_LOTE, DATA_LOG, FILTRO 
            ORDER BY DATA_LOG DESC";
    break;
?>


---------


<?php
// In TestC (Controller), update the getHistoricoFilteredData method:

private function getHistoricoFilteredData($selectedIds) {
    if (!empty($selectedIds)) {
        $idsArray = explode(',', $selectedIds);
        $cleanIds = array();
        
        foreach ($idsArray as $id) {
            $cleanId = trim($id);
            if (!empty($cleanId) && is_numeric($cleanId)) {
                $cleanIds[] = intval($cleanId);
            }
        }
        
        if (!empty($cleanIds)) {
            $idsStr = implode(',', $cleanIds);
            $query = "SELECT LOG.*, 
                             ISNULL(FLAG.ACC_FLAG, 0) AS ACC_FLAG
                      FROM PGTOCORSP.dbo.TB_WXKD_LOG LOG
                      LEFT JOIN PGTOCORSP.dbo.tb_wxkd_flag FLAG 
                        ON LOG.COD_EMPRESA = FLAG.COD_EMPRESA 
                        AND LOG.COD_LOJA = FLAG.COD_LOJA
                      WHERE LOG.CHAVE_LOTE IN ($idsStr) 
                      ORDER BY LOG.CHAVE_LOTE, LOG.CHAVE_LOJA";
            return $this->model->sql->select($query);
        }
    }
    
    $query = "SELECT LOG.*, 
                     ISNULL(FLAG.ACC_FLAG, 0) AS ACC_FLAG
              FROM PGTOCORSP.dbo.TB_WXKD_LOG LOG
              LEFT JOIN PGTOCORSP.dbo.tb_wxkd_flag FLAG 
                ON LOG.COD_EMPRESA = FLAG.COD_EMPRESA 
                AND LOG.COD_LOJA = FLAG.COD_LOJA
              ORDER BY LOG.DATA_LOG DESC, LOG.CHAVE_LOJA";
    return $this->model->sql->select($query);
}
?>