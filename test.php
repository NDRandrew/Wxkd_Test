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
        // Optimized base fields selection
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
            G.DATA_CONTRATO,
            G.TIPO,
            H.WXKD_FLAG,
            I.qtd_repeticoes as QUANT_LOJAS,
            J.data_log as DATA_SOLICITACAO";
            
        // Optimized base joins
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
                    MAX(CASE WHEN COD_SERVICO = 'CO' THEN 1 ELSE 0 END) AS 'HOLERITE_INSS_VALID'
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
            
            // Optimized card queries with better indexing
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
                SELECT A.Chave_Loja
                " . $this->baseJoins . "
                WHERE (B.DT_CADASTRO>='20250601' OR C.DT_CADASTRO>='20250601' OR D.DT_CADASTRO>='20250601' OR E.DT_CADASTRO>='20250601') 
                AND H.WXKD_FLAG = 1");
            
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
                    $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
                            " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                            AND H.WXKD_FLAG IN (0,1)";
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

    public function updateWxkdFlag($records) {
        try {
            if (empty($records)) {
                return false;
            }
            
            $updateCount = 0;
            
            foreach ($records as $record) {
                $codEmpresa = (int) $record['COD_EMPRESA'];
                $codLoja = (int) $record['COD_LOJA'];
                
                if ($codEmpresa <= 0 || $codLoja <= 0) {
                    continue;
                }
                
                // Check if record exists first (optimization)
                $checkSql = "SELECT COUNT(*) as total FROM PGTOCORSP.dbo.tb_wxkd_flag 
                             WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $checkResult = $this->sql->select($checkSql);
                
                if (empty($checkResult) || !isset($checkResult[0]['total']) || $checkResult[0]['total'] == 0) {
                    continue;
                }
                
                $sql = "UPDATE PGTOCORSP.dbo.tb_wxkd_flag 
                        SET WXKD_FLAG = 1 
                        WHERE COD_EMPRESA = $codEmpresa AND COD_LOJA = $codLoja";
                
                $result = $this->sql->update($sql);
                
                if ($result) {
                    $updateCount++;
                }
            }
            
            return $updateCount > 0;
            
        } catch (Exception $e) {
            error_log("updateWxkdFlag - Exception: " . $e->getMessage());
            return false;
        }
    }

    public function contractDateCheck() {
        try {
            // Optimized contract check query
            $query = "SELECT A.Chave_Loja
                     " . $this->baseJoins . "
                     WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                     AND H.WXKD_FLAG = 0 
                     AND G.TIPO IN (
                         'Contrato de Correspondente 8.1',
                         'Contrato de Correspondente 8.2',
                         'Contrato de Correspondente 9.1',
                         'Contrato de Correspondente 9.2',
                         'Contrato de Correspondente 10.1',
                         'Contrato de Correspondente 10.2'
                     )";
                     
            $result = $this->sql->select($query);
            return $result;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao verificar contratos: " . $e->getMessage());
        }
    }

    public function __destruct() {
        $this->sql = null;
    }
}
?>