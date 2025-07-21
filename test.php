<?php
// Update the exportTXT method in your Wxkd_DashboardController class

public function exportTXT() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

    try {
        $data = $this->getFilteredData($filter, $selectedIds);
        
        if (empty($data)) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>Nenhum dado encontrado para exportação</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
        
        $invalidRecords = $this->validateRecordsForTXTExport($data);
        
        if (!empty($invalidRecords)) {
            $this->outputValidationError($invalidRecords);
            return;
        }
        
        $xml = '<response><success>true</success><txtData>';
        
        $recordsToUpdate = array();
        
        foreach ($data as $row) {
            $xml .= '<row>';
            $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '', '"<>&') . '</cod_empresa>';
            $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
            $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : '', '"<>&') . '</cod_loja>';
            $xml .= '<TIPO_CORRESPONDENTE>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</TIPO_CORRESPONDENTE>';
            $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
            $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
            $xml .= '</row>';
            
            $codEmpresa = (int) (isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : 0);
            $codLoja = (int) (isset($row['Cod_Loja']) ? $row['Cod_Loja'] : 0);
            
            if ($codEmpresa > 0 && $codLoja > 0) {
                $recordsToUpdate[] = array(
                    'COD_EMPRESA' => $codEmpresa,
                    'COD_LOJA' => $codLoja
                );
            }
        }
        
        if (!empty($recordsToUpdate)) {
            // Generate new CHAVE_LOTE for this export batch
            $chaveLote = $this->model->generateChaveLote();
            
            // Update WXKD_FLAG
            $updateResult = $this->model->updateWxkdFlag($recordsToUpdate);
            
            // Insert log entries
            $logResult = false;
            if ($updateResult) {
                $logResult = $this->model->insertLogEntries($data, $chaveLote);
            }
            
            $xml .= '<flagUpdate>';
            $xml .= '<success>' . ($updateResult ? 'true' : 'false') . '</success>';
            $xml .= '<recordsUpdated>' . count($recordsToUpdate) . '</recordsUpdated>';
            $xml .= '</flagUpdate>';
            
            $xml .= '<logInsert>';
            $xml .= '<success>' . ($logResult ? 'true' : 'false') . '</success>';
            $xml .= '<chaveLote>' . $chaveLote . '</chaveLote>';
            $xml .= '<recordsLogged>' . count($data) . '</recordsLogged>';
            $xml .= '</logInsert>';
        }
        
        $xml .= '</txtData></response>';
        echo $xml;
        exit;
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<e>' . addcslashes($e->getMessage(), '"<>&') . '</e>';
        $xml .= '</response>';
        echo $xml;
        exit;
    }
}

// Update the getTableDataByFilter method in your model - add this case:
public function getTableDataByFilter($filter = 'all') {
    try {
        $query = "";
        
        switch($filter) {
            case 'cadastramento':
                $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
                        " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                        AND H.WXKD_FLAG = 0";
                break;
                
            case 'descadastramento':
                // ... existing descadastramento query
                break;
                
            case 'historico':
                $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
                
                if ($chaveLote > 0) {
                    return $this->getHistoricoDetails($chaveLote);
                } else {
                    return $this->getHistoricoSummary();
                }
                break;
                
            default: 
                $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
                        " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                        AND H.WXKD_FLAG IN (0,1)";
                break;
        }
        
        if (empty($query)) {
            return array();
        }
        
        $result = $this->sql->select($query);
        return $result;
        
    } catch (Exception $e) {
        error_log("getTableDataByFilter - Exception: " . $e->getMessage());
        return array();
    }
}

// Add a new method to handle historico AJAX requests specifically
public function ajaxGetHistoricoDetails() {
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    try {
        $detailData = $this->model->getHistoricoDetails($chaveLote);
        
        $xml = '<response>';
        $xml .= '<success>true</success>';
        $xml .= '<detailData>';
        
        if (is_array($detailData) && count($detailData) > 0) {
            foreach ($detailData as $row) {
                $xml .= '<row>';
                foreach ($row as $key => $value) {
                    $xml .= '<' . $key . '>' . addcslashes($value, '"<>&') . '</' . $key . '>';
                }
                $xml .= '</row>';
            }
        }
        
        $xml .= '</detailData>';
        $xml .= '</response>';
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<error>' . addcslashes($e->getMessage(), '"<>&') . '</error>';
        $xml .= '</response>';
    }

    echo $xml;
    exit;
}
?>