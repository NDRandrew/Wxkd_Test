<?php
// Replace the updateWxkdFlag method in Wxkd_DashboardModel class

public function updateWxkdFlag($records, $fullData = array(), $chaveLote = 0, $filtro = 'cadastramento') {
    $logs = array();
    
    try {
        $logs[] = "updateWxkdFlag START - Records count: " . count($records) . ", ChaveLote: $chaveLote, Filtro: $filtro";
        
        if (empty($records)) {
            $logs[] = "updateWxkdFlag - No records provided";
            $this->debugLogs = $logs;
            return false;
        }
        
        $updateCount = 0;
        $logInsertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($records as $index => $record) {
            $codEmpresa = (int) $record['COD_EMPRESA'];
            $codLoja = (int) $record['COD_LOJA'];
            
            $logs[] = "updateWxkdFlag - Processing record #$index: CodEmpresa=$codEmpresa, CodLoja=$codLoja";
            
            if ($codEmpresa <= 0 || $codLoja <= 0) {
                $logs[] = "updateWxkdFlag - Invalid empresa/loja codes for record #$index";
                continue;
            }
            
            // Use MERGE statement to avoid race conditions and duplicates
            $mergeSql = "
                MERGE PGTOCORSP.dbo.tb_wxkd_flag AS target
                USING (SELECT $codEmpresa AS COD_EMPRESA, $codLoja AS COD_LOJA) AS source
                ON (target.COD_EMPRESA = source.COD_EMPRESA AND target.COD_LOJA = source.COD_LOJA)
                WHEN MATCHED THEN
                    UPDATE SET WXKD_FLAG = 1
                WHEN NOT MATCHED THEN
                    INSERT (COD_EMPRESA, COD_LOJA, WXKD_FLAG)
                    VALUES (source.COD_EMPRESA, source.COD_LOJA, 1);
            ";
            
            try {
                $result = $this->sql->update($mergeSql);
                
                if ($result) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully processed WXKD_FLAG for record #$index (MERGE)";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to process WXKD_FLAG for record #$index (MERGE)";
                }
            } catch (Exception $mergeEx) {
                $logs[] = "updateWxkdFlag - MERGE exception for record #$index: " . $mergeEx->getMessage();
                
                // Fallback to the old method if MERGE fails
                $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                            WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $checkResult = $this->sql->select($checkSql);
                
                if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                    $insertSql = "INSERT INTO PGTOCORSP.dbo.tb_wxkd_flag (COD_EMPRESA, COD_LOJA, WXKD_FLAG) 
                                VALUES ($codEmpresa, $codLoja, 1)";
                    
                    $insertResult = $this->sql->insert($insertSql);
                    
                    if ($insertResult) {
                        $updateCount++;
                        $logs[] = "updateWxkdFlag - Successfully inserted new WXKD_FLAG record for #$index (fallback)";
                    } else {
                        $logs[] = "updateWxkdFlag - Failed to insert WXKD_FLAG record for #$index (fallback)";
                    }
                } else {
                    $updateSql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                                SET WXKD_FLAG = 1 
                                WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                    
                    $updateResult = $this->sql->update($updateSql);
                    
                    if ($updateResult) {
                        $updateCount++;
                        $logs[] = "updateWxkdFlag - Successfully updated WXKD_FLAG for record #$index (fallback)";
                    } else {
                        $logs[] = "updateWxkdFlag - Failed to update WXKD_FLAG for record #$index (fallback)";
                    }
                }
            }
        }
        
        // Log insertion logic remains the same but with better error handling
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records";
            
            foreach ($fullData as $index => $record) {
                $logs[] = "updateWxkdFlag - Processing log record #$index";
                
                $chaveLoja = $this->getFieldValue($record, array('Chave_Loja', 'CHAVE_LOJA'));
                $nomeLoja = $this->getFieldValue($record, array('Nome_Loja', 'NOME_LOJA'));
                $codEmpresa = $this->getFieldValue($record, array('Cod_Empresa', 'COD_EMPRESA'));
                $codLoja = $this->getFieldValue($record, array('Cod_Loja', 'COD_LOJA'));
                
                if (empty($chaveLoja) || empty($nomeLoja)) {
                    $logs[] = "updateWxkdFlag - Missing required fields in log record #$index";
                    continue;
                }
                
                $chaveLoja = (int)$chaveLoja;
                $nomeLoja = str_replace("'", "''", $nomeLoja);
                $codEmpresa = (int)$codEmpresa;
                $codLoja = (int)$codLoja;
                
                $tipoCorrespondente = $this->getFieldValue($record, array('TIPO_CORRESPONDENTE'));
                $tipoCorrespondente = str_replace("'", "''", $tipoCorrespondente);
                
                $tipoContrato = $this->getFieldValue($record, array('TIPO_CONTRATO'));
                $tipoContrato = str_replace("'", "''", $tipoContrato);
                
                $dataContrato = $this->getFieldValue($record, array('DATA_CONTRATO'));
                $dataContrato = !empty($dataContrato) ? "'" . str_replace("'", "''", $dataContrato) . "'" : 'NULL';
                
                $dataConclusao = $this->getFieldValue($record, array('DATA_CONCLUSAO'));
                if (!empty($dataConclusao) && trim($dataConclusao) !== '') {
                    $dataConclusao = "'" . str_replace("'", "''", trim($dataConclusao)) . "'";
                } else {
                    $dataConclusao = "'" . $currentDateTime . "'";
                }
                
                $dataSolicitacao = $this->getFieldValue($record, array('DATA_SOLICITACAO'));
                if (!empty($dataSolicitacao) && trim($dataSolicitacao) !== '') {
                    $dataSolicitacao = "'" . str_replace("'", "''", trim($dataSolicitacao)) . "'";
                } else {
                    $dataSolicitacao = $dataConclusao;
                }
                
                $depDinheiro = $this->getMonetaryValueForLog($record, 'DEP_DINHEIRO');
                $depCheque = $this->getMonetaryValueForLog($record, 'DEP_CHEQUE');
                $recRetirada = $this->getMonetaryValueForLog($record, 'REC_RETIRADA');
                $saqueCheque = $this->getMonetaryValueForLog($record, 'SAQUE_CHEQUE');
                
                // Check if ORGAO_PAGADOR is empty for this record - Updated part
                $orgaoPagadorEmpty = empty($record['ORGAO_PAGADOR']) || trim($record['ORGAO_PAGADOR']) === '';
                
                $segundaVia = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('SEGUNDA_VIA_CARTAO_VALID', 'SEGUNDA_VIA_CARTAO'));
                $holeriteInss = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('HOLERITE_INSS_VALID', 'HOLERITE_INSS'));
                $consultaInss = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('CONSULTA_INSS_VALID', 'CONS_INSS'));
                $provaVida = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('PROVA_DE_VIDA_VALID', 'PROVA_DE_VIDA'));
                
                $logSql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                        (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                        TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, 
                        SEGUNDA_VIA_CARTAO, HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                        VALUES 
                        ($chaveLote, 
                        '$currentDateTime', 
                        $chaveLoja, 
                        '$nomeLoja', 
                        $codEmpresa, 
                        $codLoja, 
                        '$tipoCorrespondente',
                        $dataConclusao,
                        $dataSolicitacao,
                        $depDinheiro, $depCheque, $recRetirada, $saqueCheque, 
                        '$segundaVia', '$holeriteInss', '$consultaInss', '$provaVida', 
                        $dataContrato, 
                        '$tipoContrato', 
                        '$filtro')";
                
                try {
                    $logResult = $this->sql->insert($logSql);
                    
                    if ($logResult) {
                        $logInsertCount++;
                        $logs[] = "updateWxkdFlag - Log insert SUCCESS for record #$index";
                    } else {
                        $logs[] = "updateWxkdFlag - Log insert FAILED for record #$index";
                    }
                } catch (Exception $logEx) {
                    $logs[] = "updateWxkdFlag - Log insert exception for record #$index: " . $logEx->getMessage();
                }
            }
        }
        
        $logs[] = "updateWxkdFlag END - Flag updates: $updateCount, Log inserts: $logInsertCount";
        $this->debugLogs = $logs;
        
        return $updateCount > 0;
        
    } catch (Exception $e) {
        $logs[] = "updateWxkdFlag - MAIN Exception: " . $e->getMessage();
        $this->debugLogs = $logs;
        return false;
    }
}
?>





----------------------





<?php
// Replace the initializeBaseQuery method in Wxkd_DashboardModel class

private function initializeBaseQuery() {
    $this->baseSelectFields = "
    DISTINCT
        A.Chave_Loja,
        A.Nome_Loja,
        A.Cod_Loja,
        A.Cod_Empresa,
        CONVERT(VARCHAR, B.DT_CADASTRO, 103) AS AVANCADO,
        CONVERT(VARCHAR, C.DT_CADASTRO, 103) AS PRESENCA,
        CONVERT(VARCHAR, D.DT_CADASTRO, 103) AS UNIDADE_NEGOCIO,
        CONVERT(VARCHAR, E.DT_CADASTRO, 103) AS ORGAO_PAGADOR,
        SUBSTRING(
            CASE WHEN B.DT_CADASTRO IS NOT NULL THEN ', AVANCADO' ELSE '' END +
            CASE WHEN C.DT_CADASTRO IS NOT NULL THEN ', PRESENCA' ELSE '' END +
            CASE WHEN D.DT_CADASTRO IS NOT NULL THEN ', UNIDADE_NEGOCIO' ELSE '' END +
            CASE WHEN E.DT_CADASTRO IS NOT NULL THEN ', ORGAO_PAGADOR' ELSE '' END,
            3, 999
        ) AS TIPO_CORRESPONDENTE,
        CASE 
            WHEN B.DT_CADASTRO IS NOT NULL OR D.DT_CADASTRO IS NOT NULL THEN 'AVANCADO/UNIDADE_NEGOCIO'
            WHEN C.DT_CADASTRO IS NOT NULL OR E.DT_CADASTRO IS NOT NULL THEN 'ORG_PAGADOR/PRESENCA'
            ELSE 'N/I'
        END AS TIPO_LIMITES,
        F.DEP_DINHEIRO_VALID,
        F.DEP_CHEQUE_VALID,
        F.REC_RETIRADA_VALID,
        F.SAQUE_CHEQUE_VALID,
        F.SEGUNDA_VIA_CARTAO_VALID,
        F.CONSULTA_INSS_VALID,
        F.HOLERITE_INSS_VALID,
        F.PROVA_DE_VIDA_VALID,
        G.DATA_CONTRATO,
        G.TIPO AS TIPO_CONTRATO,
        COALESCE(H.WXKD_FLAG, 0) AS WXKD_FLAG,
        I.qtd_repeticoes AS QUANT_LOJAS,
        J.data_log AS DATA_SOLICITACAO,
        CASE 
            WHEN B.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, B.DT_CADASTRO, 120)
            WHEN C.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, C.DT_CADASTRO, 120)
            WHEN D.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, D.DT_CADASTRO, 120)
            WHEN E.DT_CADASTRO >= '2025-06-01' THEN CONVERT(VARCHAR, E.DT_CADASTRO, 120)
            ELSE CONVERT(VARCHAR, GETDATE(), 120)
        END AS DATA_CONCLUSAO,
        A.Nome_Loja AS Dep_Dinheiro,
        A.Nome_Loja AS Dep_Cheque,
        A.Nome_Loja AS Rec_Retirada,
        A.Nome_Loja AS Saque_Cheque,
        A.Nome_Loja AS '2Via_Cartao',
        A.Nome_Loja AS Holerite_INSS,
        A.Nome_Loja AS Cons_INSS";
        
    $this->baseJoins = "
        
        FROM DATALAKE..DL_BRADESCO_EXPRESSO A
            LEFT JOIN (
                SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
                FROM PGTOCORSP.DBO.TB_PP_AVANCADO
                GROUP BY CHAVE_LOJA
            ) B ON A.CHAVE_LOJA = B.CHAVE_LOJA
            LEFT JOIN (
                SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
                FROM PGTOCORSP.DBO.TB_PP_PRESENCA
                GROUP BY CHAVE_LOJA
            ) C ON A.CHAVE_LOJA = C.CHAVE_LOJA
            LEFT JOIN (
                SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
                FROM PGTOCORSP.DBO.TB_PP_UNIDADE_NEGOCIO
                GROUP BY CHAVE_LOJA
            ) D ON A.CHAVE_LOJA = D.CHAVE_LOJA
            LEFT JOIN (
                SELECT CHAVE_LOJA_PARA, MAX(DATA_ATT) AS DT_CADASTRO
                FROM PBEN..TB_OP_PBEN_INDICACAO
                WHERE APROVACAO = 1
                GROUP BY CHAVE_LOJA_PARA
            ) E ON A.CHAVE_LOJA = E.CHAVE_LOJA_PARA
            LEFT JOIN (
                SELECT B.Cod_Empresa,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_DINHEIRO_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_CHEQUE_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS REC_RETIRADA_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS SAQUE_CHEQUE_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS SEGUNDA_VIA_CARTAO_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS CONSULTA_INSS_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS HOLERITE_INSS_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS PROVA_DE_VIDA_VALID
                FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                JOIN (
                    SELECT Cod_Empresa, COD_SERVICO
                    FROM MESU.DBO.EMPRESAS_SERVICOS
                    GROUP BY COD_EMPRESA, COD_SERVICO
                ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                WHERE COD_SERVICO_BRAD IN (" . Wxkd_Config::getServiceCodesSQL() . ")
                GROUP BY B.Cod_Empresa
            ) F ON A.Cod_Empresa = F.Cod_Empresa
            LEFT JOIN (
                SELECT KEY_EMPRESA, DATA_CONTRATO, TIPO
                FROM (
                    SELECT A.KEY_EMPRESA, A.DATA_CONTRATO, C.TIPO,
                        ROW_NUMBER() OVER (PARTITION BY A.KEY_EMPRESA ORDER BY A.DATA_CONTRATO DESC) AS rn
                    FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                    LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO = C.COD_VERSAO
                    WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL
                ) SELECIONADO
                WHERE rn = 1
            ) G ON A.COD_EMPRESA = G.KEY_EMPRESA
            LEFT JOIN (
                SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG
                FROM (
                    SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG,
                        ROW_NUMBER() OVER (PARTITION BY COD_EMPRESA, COD_LOJA ORDER BY WXKD_FLAG DESC) AS rn
                    FROM PGTOCORSP.dbo.tb_wxkd_flag
                ) ranked_flags
                WHERE rn = 1
            ) H ON A.COD_EMPRESA = H.COD_EMPRESA AND A.COD_LOJA = H.COD_LOJA
            LEFT JOIN (
                SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                FROM DATALAKE..DL_BRADESCO_EXPRESSO
                WHERE BE_INAUGURADO = 1
                GROUP BY COD_EMPRESA
            ) I ON A.COD_EMPRESA = I.COD_EMPRESA
            LEFT JOIN (
                SELECT A.CHAVE_LOJA, B.DATA_LOG
                FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
                INNER JOIN (
                    SELECT A.CHAVE_LOJA, MAX(B.DATA_LOG) AS MAX_DATA_LOG
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
                    INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B ON A.COD_SOLICITACAO = B.COD_SOLICITACAO
                    GROUP BY A.CHAVE_LOJA
                ) MaxLog ON A.CHAVE_LOJA = MaxLog.CHAVE_LOJA
                INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B 
                    ON A.COD_SOLICITACAO = B.COD_SOLICITACAO AND B.DATA_LOG = MaxLog.MAX_DATA_LOG
            ) J ON A.CHAVE_LOJA = J.CHAVE_LOJA";
}
?>







---------------


-- Database Fix Script for tb_wxkd_flag table
-- This script will remove duplicates and add proper constraints

-- Step 1: Check current duplicates
SELECT 
    COD_EMPRESA, 
    COD_LOJA, 
    COUNT(*) as duplicate_count,
    STRING_AGG(CAST(WXKD_FLAG AS VARCHAR), ', ') as flags
FROM PGTOCORSP.dbo.tb_wxkd_flag 
GROUP BY COD_EMPRESA, COD_LOJA 
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC;

-- Step 2: Backup the current table (optional but recommended)
SELECT * 
INTO PGTOCORSP.dbo.tb_wxkd_flag_backup_$(FORMAT(GETDATE(), 'yyyyMMdd_HHmmss'))
FROM PGTOCORSP.dbo.tb_wxkd_flag;

-- Step 3: Remove duplicates, keeping the record with WXKD_FLAG = 1 (or the latest one)
WITH DuplicateRecords AS (
    SELECT 
        COD_EMPRESA,
        COD_LOJA,
        WXKD_FLAG,
        ROW_NUMBER() OVER (
            PARTITION BY COD_EMPRESA, COD_LOJA 
            ORDER BY WXKD_FLAG DESC, 
                     CASE WHEN WXKD_FLAG IS NOT NULL THEN 0 ELSE 1 END
        ) AS rn
    FROM PGTOCORSP.dbo.tb_wxkd_flag
)
DELETE FROM PGTOCORSP.dbo.tb_wxkd_flag
WHERE EXISTS (
    SELECT 1 
    FROM DuplicateRecords d
    WHERE d.COD_EMPRESA = tb_wxkd_flag.COD_EMPRESA 
    AND d.COD_LOJA = tb_wxkd_flag.COD_LOJA
    AND d.WXKD_FLAG = tb_wxkd_flag.WXKD_FLAG
    AND d.rn > 1
);

-- Step 4: Verify no duplicates remain
SELECT 
    COD_EMPRESA, 
    COD_LOJA, 
    COUNT(*) as duplicate_count
FROM PGTOCORSP.dbo.tb_wxkd_flag 
GROUP BY COD_EMPRESA, COD_LOJA 
HAVING COUNT(*) > 1;

-- Step 5: Add unique constraint to prevent future duplicates
IF NOT EXISTS (
    SELECT 1 
    FROM sys.indexes 
    WHERE object_id = OBJECT_ID('PGTOCORSP.dbo.tb_wxkd_flag') 
    AND name = 'UQ_tb_wxkd_flag_empresa_loja'
)
BEGIN
    ALTER TABLE PGTOCORSP.dbo.tb_wxkd_flag 
    ADD CONSTRAINT UQ_tb_wxkd_flag_empresa_loja 
    UNIQUE (COD_EMPRESA, COD_LOJA);
    
    PRINT 'Unique constraint UQ_tb_wxkd_flag_empresa_loja added successfully.';
END
ELSE
BEGIN
    PRINT 'Unique constraint UQ_tb_wxkd_flag_empresa_loja already exists.';
END

-- Step 6: Check table structure and constraints
SELECT 
    TABLE_NAME,
    CONSTRAINT_NAME,
    CONSTRAINT_TYPE,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
LEFT JOIN INFORMATION_SCHEMA.CONSTRAINT_COLUMN_USAGE ccu 
    ON tc.CONSTRAINT_NAME = ccu.CONSTRAINT_NAME
WHERE tc.TABLE_NAME = 'tb_wxkd_flag'
    AND tc.TABLE_SCHEMA = 'dbo';

-- Step 7: Verify final table state
SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT CONCAT(COD_EMPRESA, '_', COD_LOJA)) as unique_combinations,
    SUM(CASE WHEN WXKD_FLAG = 0 THEN 1 ELSE 0 END) as flag_zero,
    SUM(CASE WHEN WXKD_FLAG = 1 THEN 1 ELSE 0 END) as flag_one
FROM PGTOCORSP.dbo.tb_wxkd_flag;




----------------------------






<?php
// Replace the descadastramento case in getTableDataByFilter method in TestM

case 'descadastramento':
    $query = "
        SELECT DISTINCT
            A.CHAVE_LOJA,
            G.NOME_LOJA,
            G.COD_EMPRESA,
            G.COD_LOJA,
            A.COD_SOLICITACAO,
            DATA_PEDIDO,
            DATA_PEDIDO AS DATA_SOLICITACAO,
            DATA_PEDIDO AS DATA_CONCLUSAO,
            CASE 
                WHEN C.DESC_SOLICITACAO = 'Incentivo Producao' THEN 'UNIDADE_NEGOCIO'
                WHEN C.DESC_SOLICITACAO = 'Presenca' THEN 'PRESENCA'
                WHEN C.DESC_SOLICITACAO = 'Avancado' THEN 'AVANCADO'
                WHEN C.DESC_SOLICITACAO = 'Orgao Pagador' THEN 'ORGAO_PAGADOR'
                WHEN C.DESC_SOLICITACAO = 'ORG_PAGADOR' THEN 'ORGAO_PAGADOR'
                ELSE C.DESC_SOLICITACAO
            END AS TIPO_CORRESPONDENTE,
            CASE 
                WHEN C.DESC_SOLICITACAO = 'Avancado' OR C.DESC_SOLICITACAO = 'Incentivo Producao' THEN 'AVANCADO/UNIDADE_NEGOCIO'
                WHEN C.DESC_SOLICITACAO = 'Presenca' OR C.DESC_SOLICITACAO = 'Orgao Pagador' OR C.DESC_SOLICITACAO = 'ORG_PAGADOR' THEN 'ORGAO_PAGADOR/PRESENCA'
                ELSE 'N/I'
            END AS TIPO_LIMITES,
            H.DEP_DINHEIRO_VALID,
            H.DEP_CHEQUE_VALID,
            H.REC_RETIRADA_VALID,
            H.SAQUE_CHEQUE_VALID,
            H.SEGUNDA_VIA_CARTAO_VALID,
            H.CONSULTA_INSS_VALID,
            H.HOLERITE_INSS_VALID,
            H.PROVA_DE_VIDA_VALID,
            I.DATA_CONTRATO,
            I.TIPO AS TIPO_CONTRATO,
            A.COD_ACAO,
            D.DESC_ACAO,
            -- Use MAX to avoid duplicates from multiple detail records
            MAX(B.COD_ETAPA) AS COD_ETAPA,
            MAX(E.DESC_ETAPA) AS DESC_ETAPA,
            MAX(B.COD_STATUS) AS COD_STATUS,
            MAX(F.DESC_STATUS) AS DESC_STATUS,
            J.QTD_REPETICOES AS QUANT_LOJAS,
            -- Add ORGAO_PAGADOR field for validation logic
            CASE 
                WHEN C.DESC_SOLICITACAO = 'Orgao Pagador' OR C.DESC_SOLICITACAO = 'ORG_PAGADOR' THEN CONVERT(VARCHAR, A.DATA_PEDIDO, 103)
                ELSE ''
            END AS ORGAO_PAGADOR
        FROM PGTOCORSP.dbo.TB_PGTO_SOLICITACAO A
            JOIN PGTOCORSP.dbo.TB_PGTO_SOLICITACAO_DETALHE B ON A.COD_SOLICITACAO=B.COD_SOLICITACAO
            JOIN PGTOCORSP.dbo.PGTOCORSP_TB_TIPO_SOLICITACAO C ON A.COD_TIPO_PAGAMENTO=C.COD_SOLICITACAO
            LEFT JOIN PGTOCORSP.dbo.PGTOCORSP_TB_ACAO_SOLICITACAO D ON A.COD_ACAO=D.COD_ACAO
            LEFT JOIN PGTOCORSP.dbo.TB_PGTO_ETAPA E ON E.COD_ETAPA=B.COD_ETAPA
            LEFT JOIN PGTOCORSP.dbo.TB_PGTO_STATUS F ON F.COD_STATUS=B.COD_STATUS
            LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO G ON A.CHAVE_LOJA=G.CHAVE_LOJA
            LEFT JOIN (
                SELECT B.Cod_Empresa,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_DINHEIRO_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS DEP_CHEQUE_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS REC_RETIRADA_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS SAQUE_CHEQUE_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS SEGUNDA_VIA_CARTAO_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS CONSULTA_INSS_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS HOLERITE_INSS_VALID,
                    MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS PROVA_DE_VIDA_VALID
                FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                JOIN (
                    SELECT Cod_Empresa, COD_SERVICO
                    FROM MESU.DBO.EMPRESAS_SERVICOS
                    GROUP BY COD_EMPRESA, COD_SERVICO
                ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                WHERE COD_SERVICO_BRAD IN  (" . Wxkd_Config::getServiceCodesSQL() . ") 
                GROUP BY B.Cod_Empresa
            ) H ON G.COD_EMPRESA = H.COD_EMPRESA
            LEFT JOIN (
                SELECT KEY_EMPRESA, DATA_CONTRATO, TIPO
                FROM (
                    SELECT A.KEY_EMPRESA, A.DATA_CONTRATO, C.TIPO,
                        ROW_NUMBER() OVER (PARTITION BY A.KEY_EMPRESA ORDER BY A.DATA_CONTRATO DESC) AS rn
                    FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                    LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO = C.COD_VERSAO
                    WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL
                ) SELECIONADO
                WHERE rn = 1
            ) I ON G.COD_EMPRESA = I.KEY_EMPRESA
            LEFT JOIN (
                SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                FROM DATALAKE..DL_BRADESCO_EXPRESSO
                WHERE BE_INAUGURADO = 1
                GROUP BY COD_EMPRESA
            ) J ON G.COD_EMPRESA = J.COD_EMPRESA
        WHERE 
            B.COD_ETAPA=4 AND A.COD_ACAO=2 AND B.COD_STATUS = 1 AND A.DATA_PEDIDO>='20250701'
        GROUP BY 
            A.CHAVE_LOJA, G.NOME_LOJA, G.COD_EMPRESA, G.COD_LOJA, A.COD_SOLICITACAO, 
            DATA_PEDIDO, C.DESC_SOLICITACAO, A.COD_ACAO, D.DESC_ACAO,
            H.DEP_DINHEIRO_VALID, H.DEP_CHEQUE_VALID, H.REC_RETIRADA_VALID, H.SAQUE_CHEQUE_VALID,
            H.SEGUNDA_VIA_CARTAO_VALID, H.CONSULTA_INSS_VALID, H.HOLERITE_INSS_VALID, H.PROVA_DE_VIDA_VALID,
            I.DATA_CONTRATO, I.TIPO, J.QTD_REPETICOES
        ORDER BY A.DATA_PEDIDO DESC";
    break;
?>


---------------


-- Validation Queries to Check for Different Types of Duplicates

-- 1. Check for duplicates in tb_wxkd_flag table
SELECT 
    'tb_wxkd_flag duplicates' as check_type,
    COD_EMPRESA, 
    COD_LOJA, 
    COUNT(*) as duplicate_count,
    STRING_AGG(CAST(WXKD_FLAG AS VARCHAR), ', ') as flags
FROM PGTOCORSP.dbo.tb_wxkd_flag 
GROUP BY COD_EMPRESA, COD_LOJA 
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC;

-- 2. Check for duplicates in cadastramento data (main query result)
WITH CadastramentoData AS (
    SELECT DISTINCT
        A.Chave_Loja,
        A.Cod_Empresa,
        A.Cod_Loja,
        COALESCE(H.WXKD_FLAG, 0) AS WXKD_FLAG
    FROM DATALAKE..DL_BRADESCO_EXPRESSO A
        LEFT JOIN (
            SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
            FROM PGTOCORSP.DBO.TB_PP_AVANCADO
            GROUP BY CHAVE_LOJA
        ) B ON A.CHAVE_LOJA = B.CHAVE_LOJA
        LEFT JOIN (
            SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
            FROM PGTOCORSP.DBO.TB_PP_PRESENCA
            GROUP BY CHAVE_LOJA
        ) C ON A.CHAVE_LOJA = C.CHAVE_LOJA
        LEFT JOIN (
            SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO
            FROM PGTOCORSP.DBO.TB_PP_UNIDADE_NEGOCIO
            GROUP BY CHAVE_LOJA
        ) D ON A.CHAVE_LOJA = D.CHAVE_LOJA
        LEFT JOIN (
            SELECT CHAVE_LOJA_PARA, MAX(DATA_ATT) AS DT_CADASTRO
            FROM PBEN..TB_OP_PBEN_INDICACAO
            WHERE APROVACAO = 1
            GROUP BY CHAVE_LOJA_PARA
        ) E ON A.CHAVE_LOJA = E.CHAVE_LOJA_PARA
        LEFT JOIN (
            SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG
            FROM (
                SELECT COD_EMPRESA, COD_LOJA, WXKD_FLAG,
                    ROW_NUMBER() OVER (PARTITION BY COD_EMPRESA, COD_LOJA ORDER BY WXKD_FLAG DESC) AS rn
                FROM PGTOCORSP.dbo.tb_wxkd_flag
            ) ranked_flags
            WHERE rn = 1
        ) H ON A.COD_EMPRESA = H.COD_EMPRESA AND A.COD_LOJA = H.COD_LOJA
    WHERE (B.DT_CADASTRO>='20250701' OR C.DT_CADASTRO>='20250701' OR D.DT_CADASTRO>='20250701' OR E.DT_CADASTRO>='20250701')
)
SELECT 
    'Cadastramento duplicates' as check_type,
    Chave_Loja,
    COUNT(*) as duplicate_count
FROM CadastramentoData
GROUP BY Chave_Loja
HAVING COUNT(*) > 1;

-- 3. Check for duplicates in descadastramento data
WITH DescadastramentoData AS (
    SELECT DISTINCT
        A.CHAVE_LOJA,
        A.COD_SOLICITACAO,
        G.COD_EMPRESA,
        G.COD_LOJA
    FROM PGTOCORSP.dbo.TB_PGTO_SOLICITACAO A
        JOIN PGTOCORSP.dbo.TB_PGTO_SOLICITACAO_DETALHE B ON A.COD_SOLICITACAO=B.COD_SOLICITACAO
        JOIN PGTOCORSP.dbo.PGTOCORSP_TB_TIPO_SOLICITACAO C ON A.COD_TIPO_PAGAMENTO=C.COD_SOLICITACAO
        LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO G ON A.CHAVE_LOJA=G.CHAVE_LOJA
    WHERE B.COD_ETAPA=4 AND A.COD_ACAO=2 AND B.COD_STATUS = 1 AND A.DATA_PEDIDO>='20250701'
)
SELECT 
    'Descadastramento duplicates' as check_type,
    CHAVE_LOJA,
    COUNT(*) as duplicate_count
FROM DescadastramentoData
GROUP BY CHAVE_LOJA
HAVING COUNT(*) > 1;

-- 4. Check for records that appear in both cadastramento and descadastramento
WITH CadastroLojas AS (
    SELECT DISTINCT A.Chave_Loja, 'cadastramento' as source_type
    FROM DATALAKE..DL_BRADESCO_EXPRESSO A
        LEFT JOIN (SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA) B ON A.CHAVE_LOJA = B.CHAVE_LOJA
        LEFT JOIN (SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO FROM PGTOCORSP.DBO.TB_PP_PRESENCA GROUP BY CHAVE_LOJA) C ON A.CHAVE_LOJA = C.CHAVE_LOJA
        LEFT JOIN (SELECT CHAVE_LOJA, MAX(DT_CADASTRO) AS DT_CADASTRO FROM PGTOCORSP.DBO.TB_PP_UNIDADE_NEGOCIO GROUP BY CHAVE_LOJA) D ON A.CHAVE_LOJA = D.CHAVE_LOJA
        LEFT JOIN (SELECT CHAVE_LOJA_PARA, MAX(DATA_ATT) AS DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E ON A.CHAVE_LOJA = E.CHAVE_LOJA_PARA
    WHERE (B.DT_CADASTRO>='20250701' OR C.DT_CADASTRO>='20250701' OR D.DT_CADASTRO>='20250701' OR E.DT_CADASTRO>='20250701')
),
DescadastroLojas AS (
    SELECT DISTINCT A.CHAVE_LOJA, 'descadastramento' as source_type
    FROM PGTOCORSP.dbo.TB_PGTO_SOLICITACAO A
        JOIN PGTOCORSP.dbo.TB_PGTO_SOLICITACAO_DETALHE B ON A.COD_SOLICITACAO=B.COD_SOLICITACAO
    WHERE B.COD_ETAPA=4 AND A.COD_ACAO=2 AND B.COD_STATUS = 1 AND A.DATA_PEDIDO>='20250701'
)
SELECT 
    'Cross-filter duplicates' as check_type,
    c.Chave_Loja,
    'Both cadastramento and descadastramento' as issue
FROM CadastroLojas c
INNER JOIN DescadastroLojas d ON c.Chave_Loja = d.Chave_Loja;

-- 5. General table statistics
SELECT 
    'Table statistics' as check_type,
    (SELECT COUNT(*) FROM PGTOCORSP.dbo.tb_wxkd_flag) as total_wxkd_records,
    (SELECT COUNT(DISTINCT CONCAT(COD_EMPRESA, '_', COD_LOJA)) FROM PGTOCORSP.dbo.tb_wxkd_flag) as unique_wxkd_combinations,
    (SELECT COUNT(*) FROM PGTOCORSP.dbo.TB_WXKD_LOG) as total_log_records,
    (SELECT COUNT(DISTINCT CHAVE_LOTE) FROM PGTOCORSP.dbo.TB_WXKD_LOG) as unique_lotes;
