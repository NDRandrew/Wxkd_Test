<?php
require_once('\\\\mz-vv-fs-237\D4920\Secoes\D4920S012\Comum_S012\j\Server2Go\htdocs\erp\ClassRepository\geral\MSSQL\MSSQL.class.php');
require_once('../control/Wxkd_Config.php');
 
class Wxkd_DashboardModel {
    public $sql;
    private $baseSelectFields;
    private $baseJoins;
    
    public function Wxkd_Construct() {
        try {
            $this->sql = new MSSQL('PGTOCORSP');
            $this->initializeBaseQuery();
        } catch (Exception $e) {
            throw new Exception("Erro na conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    private function initializeBaseQuery() {
        $this->baseSelectFields = "
            A.Chave_Loja,
            A.Nome_Loja,
            A.Cod_Loja,
            A.Cod_Empresa,
            CONVERT(VARCHAR,B.DT_CADASTRO,103) AVANCADO,
            CONVERT(VARCHAR,C.DT_CADASTRO,103) PRESENCA,
            CONVERT(VARCHAR,D.DT_CADASTRO,103) UNIDADE_NEGOCIO,
            CONVERT(VARCHAR,E.DT_CADASTRO,103) ORGAO_PAGADOR,
            SUBSTRING(CASE WHEN B.DT_CADASTRO IS NOT NULL THEN ', AVANCADO' ELSE '' END +
                     CASE WHEN C.DT_CADASTRO IS NOT NULL THEN ', PRESENCA' ELSE '' END +
                     CASE WHEN D.DT_CADASTRO IS NOT NULL THEN ', UNIDADE_NEGOCIO' ELSE '' END +
                     CASE WHEN E.DT_CADASTRO IS NOT NULL THEN ', ORGAO_PAGADOR' ELSE '' END,3,999) TIPO_CORRESPONDENTE,
            CASE WHEN B.DT_CADASTRO IS NOT NULL OR D.DT_CADASTRO IS NOT NULL THEN 'AVANCADO/UNIDADE_NEGOCIO'
                 WHEN C.DT_CADASTRO IS NOT NULL OR E.DT_CADASTRO IS NOT NULL THEN 'ORG_PAGADOR/PRESENCA'
                 ELSE 'N/I' END TIPO_LIMITES,
            F.DEP_DINHEIRO_VALID,
            F.DEP_CHEQUE_VALID,
            F.REC_RETIRADA_VALID,
            F.SAQUE_CHEQUE_VALID,
            F.SEGUNDA_VIA_CARTAO_VALID,
            F.CONSULTA_INSS_VALID,
            F.HOLERITE_INSS_VALID,
            F.PROVA_DE_VIDA_VALID,
            G.DATA_CONTRATO,
            G.TIPO as TIPO_CONTRATO,
            H.WXKD_FLAG,
            I.qtd_repeticoes as QUANT_LOJAS,
            J.data_log as DATA_SOLICITACAO,
            '' as DATA_CONCLUSAO,
            A.Nome_Loja as Dep_Dinheiro,
            A.Nome_Loja as Dep_Cheque,
            A.Nome_Loja as Rec_Retirada,
            A.Nome_Loja as Saque_Cheque,
            A.Nome_Loja as '2Via_Cartao',
            A.Nome_Loja as Holerite_INSS,
            A.Nome_Loja as Cons_INSS";
            
        $this->baseJoins = "
            FROM DATALAKE..DL_BRADESCO_EXPRESSO A
            LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) B 
                ON A.CHAVE_LOJA=B.CHAVE_LOJA
            LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA C 
                ON C.CHAVE_LOJA=A.CHAVE_LOJA
            LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) D 
                ON D.CHAVE_LOJA=A.CHAVE_LOJA
            LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E 
                ON A.CHAVE_LOJA=E.CHAVE_LOJA_PARA
            LEFT JOIN (
                SELECT B.Cod_Empresa,
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS 'DEP_DINHEIRO_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS 'DEP_CHEQUE_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS 'REC_RETIRADA_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS 'SAQUE_CHEQUE_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS 'SEGUNDA_VIA_CARTAO_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS 'CONSULTA_INSS_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS 'HOLERITE_INSS_VALID',
                    MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS 'PROVA_DE_VIDA_VALID'
                FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                JOIN (SELECT Cod_Empresa, COD_SERVICO FROM MESU.DBO.EMPRESAS_SERVICOS GROUP BY COD_EMPRESA, COD_SERVICO) B 
                    ON A.COD_SERVICO_VAN = B.COD_SERVICO
                WHERE COD_SERVICO_BRAD IN (" . Wxkd_Config::getServiceCodesSQL() . ") 
                GROUP BY B.Cod_Empresa
            ) F ON A.Cod_Empresa=F.Cod_Empresa
            LEFT JOIN (
                SELECT KEY_EMPRESA, DATA_CONTRATO, TIPO
                FROM (
                    SELECT A.KEY_EMPRESA, A.DATA_CONTRATO, C.TIPO,
                           ROW_NUMBER() OVER (PARTITION BY A.KEY_EMPRESA ORDER BY A.DATA_CONTRATO DESC) AS rn
                    FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                    LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO = C.COD_VERSAO
                    WHERE A.COD_VERSAO IS NOT NULL AND C.TIPO IS NOT NULL
                ) SELECIONADO WHERE rn = 1
            ) G ON A.COD_EMPRESA = G.KEY_EMPRESA
            LEFT JOIN (SELECT DISTINCT COD_EMPRESA, WXKD_FLAG FROM PGTOCORSP.dbo.tb_wxkd_flag) H 
                ON A.COD_EMPRESA = H.COD_EMPRESA 
            LEFT JOIN (
                SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
                FROM DATALAKE..DL_BRADESCO_EXPRESSO WHERE BE_INAUGURADO=1 GROUP BY COD_EMPRESA
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
    
    public function getCardData() {
        try {
            $cardData = array();
            
            $cardData['cadastramento'] = $this->sql->qtdRows("
                SELECT A.Chave_Loja
                " . $this->baseJoins . "
                WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                AND H.WXKD_FLAG = 0");
            
            $cardData['descadastramento'] = $this->sql->qtdRows("
                SELECT soli.chave_loja
                FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) BE ON soli.CHAVE_LOJA=BE.CHAVE_LOJA
                LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA CE ON CE.CHAVE_LOJA=soli.CHAVE_LOJA
                LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) DE ON DE.CHAVE_LOJA=soli.CHAVE_LOJA
                LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) EE ON soli.CHAVE_LOJA=EE.CHAVE_LOJA_PARA
                LEFT JOIN (SELECT a.data_detalhe, cod_etapa, cod_status,exclusao, a.cod_solicitacao FROM PGTOCORSP.dbo.tb_pgto_solicitacao_detalhe a 
                    JOIN (SELECT DISTINCT cod_solicitacao, max(data_detalhe) as data_detalhe FROM PGTOCORSP.dbo.tb_pgto_solicitacao_detalhe GROUP BY cod_solicitacao) b 
                    ON b.cod_solicitacao = a.cod_solicitacao AND b.data_detalhe = a.data_detalhe) detalhe ON detalhe.cod_solicitacao = soli.cod_solicitacao 
                LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao 
                LEFT JOIN PGTOCORSP.dbo.tb_pgto_tipo_pagamento ti_pagam ON ti_pagam.cod_tipo_pagamento = soli.cod_tipo_pagamento 
                                        WHERE (BE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR CE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR DE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR EE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                AND exclusao = 0 AND soli.cod_acao IN(1,2) AND ti_pagam.cod_tipo_pagamento != 3 AND soli.wxkd_flag = 0 AND desc_acao = 'Descadastramento'");
            
            $cardData['historico'] = $this->sql->qtdRows("
                SELECT CHAVE_LOG FROM PGTOCORSP.dbo.TB_WXKD_LOG");
            
            return $cardData;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar dados dos cards: " . $e->getMessage());
        }
    }
    
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
                    $query = "
                        SELECT soli.chave_loja as Chave_Loja, nome_loja as Nome_Loja,
                        CONVERT(VARCHAR,BE.DT_CADASTRO,103) AVANCADO,
                        CONVERT(VARCHAR,CE.DT_CADASTRO,103) PRESENCA,
                        CONVERT(VARCHAR,DE.DT_CADASTRO,103) UNIDADE_NEGOCIO,
                        CONVERT(VARCHAR,EE.DT_CADASTRO,103) ORGAO_PAGADOR,
                        loja.cod_empresa as Cod_Empresa, cod_loja as Cod_Loja,
                        SUBSTRING(CASE WHEN B.DT_CADASTRO IS NOT NULL THEN ', AVANCADO' ELSE '' END +
                                 CASE WHEN C.DT_CADASTRO IS NOT NULL THEN ', PRESENCA' ELSE '' END +
                                 CASE WHEN D.DT_CADASTRO IS NOT NULL THEN ', UNIDADE_NEGOCIO' ELSE '' END +
                                 CASE WHEN E.DT_CADASTRO IS NOT NULL THEN ', ORGAO_PAGADOR' ELSE '' END,3,999) TIPO_CORRESPONDENTE,
                        CASE WHEN B.DT_CADASTRO IS NOT NULL OR D.DT_CADASTRO IS NOT NULL THEN 'AVANCADO/APOIO A UN'
                             WHEN C.DT_CADASTRO IS NOT NULL OR E.DT_CADASTRO IS NOT NULL THEN 'ORG.PAGADOR/PRESENÇA'
                             ELSE 'N/I' END TIPO_LIMITES,
                        data_log as Data_Aprovacao, G.DATA_CONTRATO, G.TIPO as TIPO_CONTRATO, F.*, soli.wxkd_flag
                        FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) BE ON soli.CHAVE_LOJA=BE.CHAVE_LOJA
                        LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA CE ON CE.CHAVE_LOJA=soli.CHAVE_LOJA
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) DE ON DE.CHAVE_LOJA=soli.CHAVE_LOJA
                        LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) EE ON soli.CHAVE_LOJA=EE.CHAVE_LOJA_PARA
                        LEFT JOIN (SELECT cod_solicitacao, data_log FROM PGTOCORSP.dbo.tb_pgto_log_sistema) L ON soli.cod_solicitacao = L.cod_solicitacao
                        LEFT JOIN (SELECT a.data_detalhe, cod_etapa, cod_status,exclusao, a.cod_solicitacao FROM PGTOCORSP.dbo.tb_pgto_solicitacao_detalhe a 
                            JOIN (SELECT DISTINCT cod_solicitacao, max(data_detalhe) as data_detalhe FROM PGTOCORSP.dbo.tb_pgto_solicitacao_detalhe GROUP BY cod_solicitacao) b 
                            ON b.cod_solicitacao = a.cod_solicitacao AND b.data_detalhe = a.data_detalhe) detalhe ON detalhe.cod_solicitacao = soli.cod_solicitacao 
                        LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao 
                        LEFT JOIN PGTOCORSP.dbo.tb_pgto_tipo_pagamento ti_pagam ON ti_pagam.cod_tipo_pagamento = soli.cod_tipo_pagamento 
                        LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja 
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) B ON soli.CHAVE_LOJA=B.CHAVE_LOJA
                        LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA C ON C.CHAVE_LOJA=soli.CHAVE_LOJA
                        LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) D ON D.CHAVE_LOJA=soli.CHAVE_LOJA
                        LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E ON soli.CHAVE_LOJA=E.CHAVE_LOJA_PARA
                        LEFT JOIN (
                            SELECT DISTINCT A.KEY_EMPRESA,A.DATA_CONTRATO,C.TIPO
                            FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
                            JOIN (SELECT KEY_EMPRESA,MAX(DATA_CONTRATO) DATA_CONTRATO,MAX(COD_VERSAO)COD_VERSAO
                                 FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A GROUP BY KEY_EMPRESA) B 
                                 ON A.KEY_EMPRESA=B.KEY_EMPRESA AND A.DATA_CONTRATO=B.DATA_CONTRATO AND A.COD_VERSAO=B.COD_VERSAO
                            LEFT JOIN MESU.DBO.TB_VERSAO C ON A.COD_VERSAO=C.COD_VERSAO
                        ) G ON loja.COD_EMPRESA = G.KEY_EMPRESA
                        LEFT JOIN (
                            SELECT B.COD_EMPRESA,
                                MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS 'DEP_DINHEIRO_VALID',
                                MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS 'DEP_CHEQUE_VALID',
                                MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS 'REC_RETIRADA_VALID',
                                MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS 'SAQUE_CHEQUE_VALID',
                                MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS 'SEGUNDA_VIA_CARTAO_VALID',
                                MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS 'CONSULTA_INSS_VALID',
                                MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS 'HOLERITE_INSS_VALID'
                            FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                            JOIN (SELECT COD_EMPRESA, COD_SERVICO FROM MESU.DBO.EMPRESAS_SERVICOS GROUP BY COD_EMPRESA, COD_SERVICO) B 
                                ON A.COD_SERVICO_VAN = B.COD_SERVICO
                            WHERE COD_SERVICO_BRAD IN (" . Wxkd_Config::getServiceCodesSQL() . ") 
                            GROUP BY B.COD_EMPRESA
                        ) F ON loja.COD_EMPRESA=F.COD_EMPRESA
                        WHERE (BE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR CE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR DE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR EE.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                        AND exclusao = 0 AND soli.cod_acao IN(1,2) AND ti_pagam.cod_tipo_pagamento != 3 AND soli.wxkd_flag = 0 AND desc_acao = 'Descadastramento'";
                    break;
                    
                case 'historico':
                    // Fixed query to get actual FILTRO values and proper grouping
                    $query = "SELECT 
                                CHAVE_LOTE,
                                DATA_LOG,
                                COUNT(*) as TOTAL_REGISTROS,
                                FILTRO
                            FROM PGTOCORSP.dbo.TB_WXKD_LOG 
                            GROUP BY CHAVE_LOTE, DATA_LOG, FILTRO 
                            ORDER BY DATA_LOG DESC";
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
            
            // First, update WXKD_FLAG as before
            foreach ($records as $index => $record) {
                $codEmpresa = (int) $record['COD_EMPRESA'];
                $codLoja = (int) $record['COD_LOJA'];
                
                $logs[] = "updateWxkdFlag - Processing record #$index: CodEmpresa=$codEmpresa, CodLoja=$codLoja";
                
                if ($codEmpresa <= 0 || $codLoja <= 0) {
                    $logs[] = "updateWxkdFlag - Invalid empresa/loja codes for record #$index";
                    continue;
                }
                
                $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                            WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $checkResult = $this->sql->select($checkSql);
                
                if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                    $logs[] = "updateWxkdFlag - No matching record found in tb_wxkd_flag for record #$index";
                    continue;
                }
                
                $sql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                        SET WXKD_FLAG = 1 
                        WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $result = $this->sql->update($sql);
                
                if ($result) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully updated WXKD_FLAG for record #$index";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to update WXKD_FLAG for record #$index";
                }
            }
            
            // Now insert into TB_WXKD_LOG if we have full data and chaveLote
            if (!empty($fullData) && $chaveLote > 0) {
                $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records";
                
                foreach ($fullData as $index => $record) {
                    $logs[] = "updateWxkdFlag - Processing log record #$index";
                    $logs[] = "updateWxkdFlag - Record keys: " . implode(', ', array_keys($record));
                    
                    // Check if required fields exist
                    if (!isset($record['Chave_Loja'])) {
                        $logs[] = "updateWxkdFlag - Missing Chave_Loja in log record #$index";
                        continue;
                    }
                    
                    if (!isset($record['Nome_Loja'])) {
                        $logs[] = "updateWxkdFlag - Missing Nome_Loja in log record #$index";
                        continue;
                    }
                    
                    $chaveLoja = (int)$record['Chave_Loja'];
                    $nomeLoja = str_replace("'", "''", $record['Nome_Loja']);
                    $codEmpresa = isset($record['Cod_Empresa']) ? (int)$record['Cod_Empresa'] : 0;
                    $codLoja = isset($record['Cod_Loja']) ? (int)$record['Cod_Loja'] : 0;
                    $tipoCorrespondente = isset($record['TIPO_CORRESPONDENTE']) ? str_replace("'", "''", $record['TIPO_CORRESPONDENTE']) : '';
                    $tipoContrato = isset($record['TIPO_CONTRATO']) ? str_replace("'", "''", $record['TIPO_CONTRATO']) : '';
                    $dataContrato = isset($record['DATA_CONTRATO']) ? "'" . str_replace("'", "''", $record['DATA_CONTRATO']) . "'" : 'NULL';
                    
                    // Get monetary values based on validation status
                    $depDinheiro = $this->getMonetaryValue($record, 'DEP_DINHEIRO');
                    $depCheque = $this->getMonetaryValue($record, 'DEP_CHEQUE');
                    $recRetirada = $this->getMonetaryValue($record, 'REC_RETIRADA');
                    $saqueCheque = $this->getMonetaryValue($record, 'SAQUE_CHEQUE');
                    
                    // Get simple validation status
                    $segundaVia = isset($record['SEGUNDA_VIA_CARTAO_VALID']) && $record['SEGUNDA_VIA_CARTAO_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                    $holeriteInss = isset($record['HOLERITE_INSS_VALID']) && $record['HOLERITE_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                    $consultaInss = isset($record['CONSULTA_INSS_VALID']) && $record['CONSULTA_INSS_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                    $provaVida = isset($record['PROVA_DE_VIDA_VALID']) && $record['PROVA_DE_VIDA_VALID'] == 1 ? 'Apto' : 'Nao Apto';
                    
                    $logs[] = "updateWxkdFlag - Log prepared values: ChaveLoja=$chaveLoja, NomeLoja=$nomeLoja, CodEmpresa=$codEmpresa, CodLoja=$codLoja";
                    
                    $logSql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                            (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                            TIPO_CORRESPONDENTE, DEP_DINHEIRO, DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, 
                            SEGUNDA_VIA_CARTAO, HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                            VALUES 
                            ($chaveLote, 
                            '$currentDateTime', 
                            $chaveLoja, 
                            '$nomeLoja', 
                            $codEmpresa, 
                            $codLoja, 
                            '$tipoCorrespondente', 
                            $depDinheiro, $depCheque, $recRetirada, $saqueCheque, 
                            '$segundaVia', '$holeriteInss', '$consultaInss', '$provaVida', 
                            $dataContrato, 
                            '$tipoContrato', 
                            '$filtro')";
                    
                    $logs[] = "updateWxkdFlag - Log SQL Query: " . $logSql;
                    
                    try {
                        $logResult = $this->sql->insert($logSql);
                        $logs[] = "updateWxkdFlag - Log insert result for record #$index: " . ($logResult ? 'SUCCESS' : 'FAILED');
                        
                        if ($logResult) {
                            $logInsertCount++;
                            $logs[] = "updateWxkdFlag - Log insert count now: $logInsertCount";
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

    public function getDebugLogs() {
        return isset($this->debugLogs) ? $this->debugLogs : array();
    }
    
    public function generateChaveLote() {
        try {
            error_log("generateChaveLote START");
            
            $sql = "SELECT ISNULL(MAX(CHAVE_LOTE), 0) + 1 as NEXT_LOTE FROM PGTOCORSP.dbo.TB_WXKD_LOG";
            error_log("generateChaveLote - SQL: " . $sql);
            
            $result = $this->sql->select($sql);
            error_log("generateChaveLote - Query result: " . print_r($result, true));
            
            if (!empty($result) && isset($result[0]['NEXT_LOTE'])) {
                $nextLote = (int)$result[0]['NEXT_LOTE'];
                error_log("generateChaveLote - Next lote: $nextLote");
                return $nextLote;
            }
            
            error_log("generateChaveLote - Using default value: 1");
            return 1;
            
        } catch (Exception $e) {
            error_log("generateChaveLote - Exception: " . $e->getMessage());
            return 1;
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

    public function getHistoricoSummary() {
        try {
            $query = "SELECT CHAVE_LOTE, DATA_LOG, 
                            COUNT(*) as TOTAL_REGISTROS,
                            MIN(NOME_LOJA) as PRIMEIRO_NOME_LOJA,
                            STUFF((SELECT ', ' + CAST(CHAVE_LOJA AS VARCHAR) 
                                FROM PGTOCORSP.dbo.TB_WXKD_LOG t2 
                                WHERE t2.CHAVE_LOTE = t1.CHAVE_LOTE 
                                FOR XML PATH('')), 1, 2, '') as CHAVES_LOJAS
                    FROM PGTOCORSP.dbo.TB_WXKD_LOG t1
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

    public function contractDateCheck() {
        try {
        $query = "SELECT
                            A.Chave_Loja
                        FROM DATALAKE..DL_BRADESCO_EXPRESSO A
                            
                            LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP.DBO.TB_PP_AVANCADO GROUP BY CHAVE_LOJA,DT_CADASTRO) B ON A.CHAVE_LOJA=B.CHAVE_LOJA
                            LEFT JOIN PGTOCORSP.DBO.TB_PP_PRESENCA  C ON C.CHAVE_LOJA=A.CHAVE_LOJA
                            LEFT JOIN (SELECT DT_CADASTRO,CHAVE_LOJA FROM PGTOCORSP..TB_PP_UNIDADE_NEGOCIO GROUP BY DT_CADASTRO,CHAVE_LOJA) D ON D.CHAVE_LOJA=A.CHAVE_LOJA
                            LEFT JOIN (SELECT CHAVE_LOJA_PARA,MAX(DATA_ATT) DT_CADASTRO  FROM PBEN..TB_OP_PBEN_INDICACAO WHERE APROVACAO = 1 GROUP BY CHAVE_LOJA_PARA) E ON A.CHAVE_LOJA=E.CHAVE_LOJA_PARA
                            LEFT JOIN 
                            (
                                SELECT  
                                    B.Cod_Empresa,
                                        MAX(CASE WHEN COD_SERVICO = 'D'  THEN 1 ELSE 0 END) AS 'DEP_DINHEIRO_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'D' THEN 1 ELSE 0 END) AS 'DEP_CHEQUE_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'R' THEN 1 ELSE 0 END) AS 'REC_RETIRADA_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'K' THEN 1 ELSE 0 END) AS 'SAQUE_CHEQUE_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'PC' THEN 1 ELSE 0 END) AS 'SEGUNDA_VIA_CARTAO_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'CB' THEN 1 ELSE 0 END) AS 'CONSULTA_INSS_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS 'HOLERITE_INSS_VALID',
                                        MAX(CASE WHEN COD_SERVICO = 'Z' THEN 1 ELSE 0 END) AS 'PROVA_DE_VIDA_VALID'
                                FROM PGTOCORSP.DBO.PGTOCORSP_SERVICOS_VANS A
                                JOIN (
                                            SELECT Cod_Empresa, COD_SERVICO 
                                            FROM MESU.DBO.EMPRESAS_SERVICOS 
                                            GROUP BY COD_EMPRESA, COD_SERVICO
                                    ) B ON A.COD_SERVICO_VAN = B.COD_SERVICO
                            

                                WHERE COD_SERVICO_BRAD IN ('DP50', 'DP51', 'DP52', 'DP53', 'PD50', 'PD51', 'PD52', 'PD53', 'RR55', 'RR56', 'RR57', 'RR58', 'RE55','RR50', 'RR51', 'RR52', 'RR53','PECA', 'CPBI', 'HOBI', 'VC23') 
                                GROUP BY B.Cod_Empresa
                            ) F ON A.Cod_Empresa=F.Cod_Empresa
                            LEFT JOIN 
                            (
								SELECT 
									A.KEY_EMPRESA,
									A.DATA_CONTRATO,
									A.COD_VERSAO,
									C.TIPO,
									C.MODELO
								FROM MESU.DBO.TB_EMPRESA_VERSAO_CONTRATO2 A
								LEFT JOIN MESU.DBO.TB_VERSAO C 
									ON A.COD_VERSAO = C.COD_VERSAO
								WHERE A.COD_VERSAO IS NOT NULL 
								  AND C.TIPO IS NOT NULL 
								  AND C.MODELO IS NOT NULL
                            )G ON A.COD_EMPRESA = G.KEY_EMPRESA
							LEFT JOIN 
							( 
								SELECT DISTINCT A.COD_EMPRESA, A.WXKD_FLAG
								FROM PGTOCORSP.dbo.tb_wxkd_flag A
							) H ON A.COD_EMPRESA = H.COD_EMPRESA 
							LEFT JOIN
							(
								SELECT COD_EMPRESA, COUNT(*) AS qtd_repeticoes
								FROM DATALAKE..DL_BRADESCO_EXPRESSO
								WHERE BE_INAUGURADO=1
								GROUP BY COD_EMPRESA
							) I ON A.COD_EMPRESA = I.COD_EMPRESA
							LEFT JOIN (
								SELECT 
									A.CHAVE_LOJA,
									A.COD_SOLICITACAO,
									B.DATA_LOG
								FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
								INNER JOIN (
									SELECT 
										A.CHAVE_LOJA,
										MAX(B.DATA_LOG) AS MAX_DATA_LOG
									FROM PGTOCORSP.dbo.tb_pgto_solicitacao A
									INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B 
										ON A.COD_SOLICITACAO = B.COD_SOLICITACAO
									GROUP BY A.CHAVE_LOJA
								) MaxLog
									ON A.CHAVE_LOJA = MaxLog.CHAVE_LOJA
								INNER JOIN PGTOCORSP.dbo.tb_pgto_log_sistema B 
									ON A.COD_SOLICITACAO = B.COD_SOLICITACAO AND B.DATA_LOG = MaxLog.MAX_DATA_LOG
							) J ON A.CHAVE_LOJA = J.CHAVE_LOJA

                        WHERE (B.DT_CADASTRO>='20250601' OR C.DT_CADASTRO>='20250601' OR D.DT_CADASTRO>='20250601' OR E.DT_CADASTRO>='20250601') AND H.WXKD_FLAG = 0 AND
						(COD_VERSAO IN (65,68,71,79,85,91,99,100,104,22,33,36,39,44,45,46,51,56,80))";
                        $result = $this->sql->select($query);

                        return $result;
        }
        catch (Exception $e) {
            throw new Exception("Erro ao gerar XML: " . $e->getMessage());
        }
        
    }

    public function __destruct() {
        $this->sql = null;
    }
}
?>