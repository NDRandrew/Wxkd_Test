public function insertLogEntries($records, $chaveLote, $filtro = 'cadastramento') {
    try {
        if (empty($records)) {
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($records as $record) {
            $tipoCorrespondente = $this->generateTipoCorrespondente($record);
            
            // Handle null values properly for PHP 5.3
            $chaveLoja = isset($record['Chave_Loja']) ? (int)$record['Chave_Loja'] : 0;
            $nomeLoja = isset($record['Nome_Loja']) ? addslashes($record['Nome_Loja']) : '';
            $codEmpresa = isset($record['Cod_Empresa']) ? (int)$record['Cod_Empresa'] : 0;
            $codLoja = isset($record['Cod_Loja']) ? (int)$record['Cod_Loja'] : 0;
            
            $dataContrato = 'NULL';
            if (!empty($record['DATA_CONTRATO'])) {
                $dataContrato = "'" . addslashes($record['DATA_CONTRATO']) . "'";
            }
            
            $dataConclusao = 'NULL';
            if (!empty($record['DATA_CONCLUSAO'])) {
                $dataConclusao = "'" . addslashes($record['DATA_CONCLUSAO']) . "'";
            }
            
            $dataSolicitacao = 'NULL';
            if (!empty($record['DATA_SOLICITACAO'])) {
                $dataSolicitacao = "'" . addslashes($record['DATA_SOLICITACAO']) . "'";
            }
            
            $tipoContrato = 'NULL';
            if (!empty($record['TIPO_CONTRATO'])) {
                $tipoContrato = "'" . addslashes($record['TIPO_CONTRATO']) . "'";
            }
            
            $depDinheiro = $this->getMonetaryValue($record, 'DEP_DINHEIRO');
            $depCheque = $this->getMonetaryValue($record, 'DEP_CHEQUE');
            $recRetirada = $this->getMonetaryValue($record, 'REC_RETIRADA');
            $saqueCheque = $this->getMonetaryValue($record, 'SAQUE_CHEQUE');
            
            $segundaVia = (isset($record['SEGUNDA_VIA_CARTAO_VALID']) && $record['SEGUNDA_VIA_CARTAO_VALID'] == 1) ? 'Apto' : 'Nao Apto';
            $holeriteInss = (isset($record['HOLERITE_INSS_VALID']) && $record['HOLERITE_INSS_VALID'] == 1) ? 'Apto' : 'Nao Apto';
            $consultaInss = (isset($record['CONSULTA_INSS_VALID']) && $record['CONSULTA_INSS_VALID'] == 1) ? 'Apto' : 'Nao Apto';
            $provaVida = (isset($record['PROVA_DE_VIDA_VALID']) && $record['PROVA_DE_VIDA_VALID'] == 1) ? 'Apto' : 'Nao Apto';
            
            $sql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                    (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                     TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, 
                     DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, SEGUNDA_VIA_CARTAO, 
                     HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                    VALUES 
                    ($chaveLote, 
                     '$currentDateTime', 
                     $chaveLoja, 
                     '$nomeLoja', 
                     $codEmpresa, 
                     $codLoja, 
                     '" . addslashes($tipoCorrespondente) . "', 
                     $dataConclusao, 
                     $dataSolicitacao, 
                     $depDinheiro, 
                     $depCheque, 
                     $recRetirada, 
                     $saqueCheque, 
                     '$segundaVia', 
                     '$holeriteInss', 
                     '$consultaInss', 
                     '$provaVida', 
                     $dataContrato, 
                     $tipoContrato,
                     '" . addslashes($filtro) . "')";
            
            try {
                $result = $this->sql->insert($sql);
                if ($result) {
                    $insertCount++;
                } else {
                    error_log("Failed insert SQL: " . $sql);
                }
            } catch (Exception $insertEx) {
                error_log("Insert exception: " . $insertEx->getMessage() . " | SQL: " . $sql);
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
    $activeTypes = array();
    
    $fields = array(
        'AVANCADO' => 'AVANCADO',
        'ORGAO_PAGADOR' => 'ORGAO_PAGADOR', 
        'PRESENCA' => 'PRESENCA',
        'UNIDADE_NEGOCIO' => 'UNIDADE_NEGOCIO'
    );
    
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
    $isValid = isset($record[$type . '_VALID']) && $record[$type . '_VALID'] == 1;
    if (!$isValid) return 0.00;
    
    $tipoLimites = isset($record['TIPO_LIMITES']) ? $record['TIPO_LIMITES'] : '';
    $isPresencaOrOrgao = (strpos($tipoLimites, 'PRESENCA') !== false || 
                         strpos($tipoLimites, 'ORG_PAGADOR') !== false);
    $isAvancadoOrApoio = (strpos($tipoLimites, 'AVANCADO') !== false || 
                         strpos($tipoLimites, 'UNIDADE_NEGOCIO') !== false);
    
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