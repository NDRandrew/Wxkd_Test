<?php
// Add these methods to your Wxkd_DashboardModel class

public function generateChaveLote() {
    try {
        $sql = "SELECT ISNULL(MAX(CHAVE_LOTE), 0) + 1 as NEXT_LOTE FROM PGTOCORSP.dbo.TB_WXKD_LOG";
        $result = $this->sql->select($sql);
        
        if (!empty($result) && isset($result[0]['NEXT_LOTE'])) {
            return (int)$result[0]['NEXT_LOTE'];
        }
        
        return 1;
        
    } catch (Exception $e) {
        error_log("generateChaveLote - Exception: " . $e->getMessage());
        return 1;
    }
}

public function insertLogEntries($records, $chaveLote) {
    try {
        if (empty($records)) {
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($records as $record) {
            // Generate TIPO_CORRESPONDENTE string
            $tipoCorrespondente = $this->generateTipoCorrespondente($record);
            
            $sql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                    (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                     TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, 
                     DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, SEGUNDA_VIA_CARTAO, 
                     HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO) 
                    VALUES 
                    ($chaveLote, '$currentDateTime', " . (int)$record['Chave_Loja'] . ", 
                     '" . addslashes($record['Nome_Loja']) . "', " . (int)$record['Cod_Empresa'] . ", 
                     " . (int)$record['Cod_Loja'] . ", '" . addslashes($tipoCorrespondente) . "', 
                     " . ($record['DATA_CONCLUSAO'] ? "'" . addslashes($record['DATA_CONCLUSAO']) . "'" : 'NULL') . ", 
                     " . ($record['DATA_SOLICITACAO'] ? "'" . addslashes($record['DATA_SOLICITACAO']) . "'" : 'NULL') . ", 
                     " . $this->getMonetaryValue($record, 'DEP_DINHEIRO') . ", 
                     " . $this->getMonetaryValue($record, 'DEP_CHEQUE') . ", 
                     " . $this->getMonetaryValue($record, 'REC_RETIRADA') . ", 
                     " . $this->getMonetaryValue($record, 'SAQUE_CHEQUE') . ", 
                     '" . ($record['SEGUNDA_VIA_CARTAO_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['HOLERITE_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['CONSULTA_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['PROVA_DE_VIDA_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     " . ($record['DATA_CONTRATO'] ? "'" . addslashes($record['DATA_CONTRATO']) . "'" : 'NULL') . ", 
                     '" . addslashes($record['TIPO_CONTRATO']) . "')";
            
            $result = $this->sql->insert($sql);
            if ($result) {
                $insertCount++;
            }
        }
        
        return $insertCount > 0;
        
    } catch (Exception $e) {
        error_log("insertLogEntries - Exception: " . $e->getMessage());
        return false;
    }
}

private function generateTipoCorrespondente($record) {
    $cutoff = mktime(0, 0, 0, 6, 1, 2025);
    $activeTypes = [];
    
    $fields = [
        'AVANCADO' => 'AVANCADO',
        'ORGAO_PAGADOR' => 'ORGAO_PAGADOR', 
        'PRESENCA' => 'PRESENCA',
        'UNIDADE_NEGOCIO' => 'UNIDADE_NEGOCIO'
    ];
    
    foreach ($fields as $field => $label) {
        $raw = isset($record[$field]) ? trim($record[$field]) : '';
        if (!empty($raw)) {
            $parts = explode('/', $raw);
            if (count($parts) == 3) {
                $day = (int)$parts[0];
                $month = (int)$parts[1];
                $year = (int)$parts[2];
                
                if (checkdate($month, $day, $year)) {
                    $timestamp = mktime(0, 0, 0, $month, $day, $year);
                    if ($timestamp > $cutoff) {
                        $activeTypes[] = $label;
                    }
                }
            }
        }
    }
    
    return implode(', ', $activeTypes);
}

private function getMonetaryValue($record, $type) {
    $isValid = $record[$type . '_VALID'] == 1;
    if (!$isValid) return 0.00;
    
    $isPresencaOrOrgao = (strpos($record['TIPO_LIMITES'], 'PRESENCA') !== false || 
                         strpos($record['TIPO_LIMITES'], 'ORG_PAGADOR') !== false);
    $isAvancadoOrApoio = (strpos($record['TIPO_LIMITES'], 'AVANCADO') !== false || 
                         strpos($record['TIPO_LIMITES'], 'UNIDADE_NEGOCIO') !== false);
    
    switch($type) {
        case 'DEP_DINHEIRO':
            if ($isPresencaOrOrgao) return 3000.00;
            if ($isAvancadoOrApoio) return 10000.00;
            break;
        case 'DEP_CHEQUE':
            if ($isPresencaOrOrgao) return 5000.00;
            if ($isAvancadoOrApoio) return 10000.00;
            break;
        case 'REC_RETIRADA':
        case 'SAQUE_CHEQUE':
            if ($isPresencaOrOrgao) return 2000.00;
            if ($isAvancadoOrApoio) return 3500.00;
            break;
    }
    
    return 0.00;
}

public function getHistoricoSummary() {
    try {
        $query = "SELECT CHAVE_LOTE, DATA_LOG, COUNT(*) as TOTAL_REGISTROS,
                         MIN(NOME_LOJA) as PRIMEIRO_NOME_LOJA,
                         STRING_AGG(CAST(CHAVE_LOJA AS VARCHAR), ', ') as CHAVES_LOJAS
                  FROM PGTOCORSP.dbo.TB_WXKD_LOG 
                  GROUP BY CHAVE_LOTE, DATA_LOG 
                  ORDER BY DATA_LOG DESC";
        
        $result = $this->sql->select($query);
        return $result;
        
    } catch (Exception $e) {
        error_log("getHistoricoSummary - Exception: " . $e->getMessage());
        return array();
    }
}

public function getHistoricoDetails($chaveLote) {
    try {
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = " . (int)$chaveLote . " ORDER BY CHAVE_LOJA";
        
        $result = $this->sql->select($query);
        return $result;
        
    } catch (Exception $e) {
        error_log("getHistoricoDetails - Exception: " . $e->getMessage());
        return array();
    }
}

// Update the existing getTableDataByFilter method to handle historico differently
// Replace the 'historico' case in the existing method:
/*
case 'historico':
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    if ($chaveLote > 0) {
        return $this->getHistoricoDetails($chaveLote);
    } else {
        return $this->getHistoricoSummary();
    }
    break;
*/
?>