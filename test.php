public function insertLogEntries($records, $chaveLote, $filtro = 'cadastramento') {
    try {
        if (empty($records)) {
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($records as $record) {
            $tipoCorrespondente = $this->generateTipoCorrespondente($record);
            
            // Build the insert with proper null handling
            $dataContrato = !empty($record['DATA_CONTRATO']) ? "'" . addslashes($record['DATA_CONTRATO']) . "'" : 'NULL';
            $dataConclusao = !empty($record['DATA_CONCLUSAO']) ? "'" . addslashes($record['DATA_CONCLUSAO']) . "'" : 'NULL';
            $dataSolicitacao = !empty($record['DATA_SOLICITACAO']) ? "'" . addslashes($record['DATA_SOLICITACAO']) . "'" : 'NULL';
            $tipoContrato = !empty($record['TIPO_CONTRATO']) ? "'" . addslashes($record['TIPO_CONTRATO']) . "'" : 'NULL';
            
            $sql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                    (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                     TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, 
                     DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, SEGUNDA_VIA_CARTAO, 
                     HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                    VALUES 
                    ($chaveLote, 
                     '$currentDateTime', 
                     " . (int)$record['Chave_Loja'] . ", 
                     '" . addslashes($record['Nome_Loja']) . "', 
                     " . (int)$record['Cod_Empresa'] . ", 
                     " . (int)$record['Cod_Loja'] . ", 
                     '" . addslashes($tipoCorrespondente) . "', 
                     $dataConclusao, 
                     $dataSolicitacao, 
                     " . $this->getMonetaryValue($record, 'DEP_DINHEIRO') . ", 
                     " . $this->getMonetaryValue($record, 'DEP_CHEQUE') . ", 
                     " . $this->getMonetaryValue($record, 'REC_RETIRADA') . ", 
                     " . $this->getMonetaryValue($record, 'SAQUE_CHEQUE') . ", 
                     '" . ($record['SEGUNDA_VIA_CARTAO_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['HOLERITE_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['CONSULTA_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
                     '" . ($record['PROVA_DE_VIDA_VALID'] == 1 ? 'Apto' : 'Nao Apto') . "', 
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
}Update the Controller (TestC.txt) - Update the exportTXT method call:// In the exportTXT method, replace this line:
$logResult = $this->model->insertLogEntries($recordsToLog, $chaveLote);

// With this:
$logResult = $this->model->insertLogEntries($recordsToLog, $chaveLote, $filter);Also update the historico query in Model to include FILTRO column:// In getTableDataByFilter method, update the historico case:
case 'historico':
    $query = "SELECT 
                CHAVE_LOTE,
                DATA_LOG,
                FILTRO,
                COUNT(*) as TOTAL_REGISTROS,
                MIN(NOME_LOJA) as PRIMEIRO_NOME_LOJA,
                STUFF((SELECT ', ' + CAST(CHAVE_LOJA AS VARCHAR) 
                       FROM PGTOCORSP.dbo.TB_WXKD_LOG t2 
                       WHERE t2.CHAVE_LOTE = t1.CHAVE_LOTE 
                       FOR XML PATH('')), 1, 2, '') as CHAVES_LOJAS
              FROM PGTOCORSP.dbo.TB_WXKD_LOG t1 
              GROUP BY CHAVE_LOTE, DATA_LOG, FILTRO
              ORDER BY DATA_LOG DESC";
    break;