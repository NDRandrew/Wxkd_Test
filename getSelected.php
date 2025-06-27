public function getSelectedRecords($idsArray, $filter = 'all') {
    error_log("getSelectedRecords called with " . count($idsArray) . " IDs and filter: " . $filter);
    
    try {
        if (empty($idsArray)) {
            error_log("getSelectedRecords - No IDs provided");
            return array();
        }
        
        // Obter chaves autorizadas para o filtro
        $chaves = $this->getChaveCadastro($filter);
        error_log("getSelectedRecords - Authorized chaves: " . count($chaves));
        
        if (empty($chaves)) {
            error_log("getSelectedRecords - No authorized chaves found");
            return array();
        }
        
        // Construir WHERE clause para chaves autorizadas
        $whereChaves = "chave_loja IN ('" . implode("','", $chaves) . "')";
        
        // Construir WHERE clause para IDs selecionados
        $whereIds = "chave_loja IN ('" . implode("','", $idsArray) . "')";
        
        // Combinar ambas as condições (autorização + seleção)
        $whereClause = "(" . $whereChaves . ") AND (" . $whereIds . ")";
        
        error_log("getSelectedRecords - WHERE clause length: " . strlen($whereClause));
        
        // Usar a mesma lógica do getTableDataByFilter mas com IDs específicos
        $query = "";
        
        switch($filter) {
            case 'cadastramento':
                $query = "
                    SELECT 
                        soli.chave_loja as id,
                        ISNULL(loja.nome_loja, 'N/A') as nome,
                        ISNULL(loja.email_loja, 'N/A') as email, 
                        ISNULL(loja.telefone_loja, 'N/A') as telefone,
                        ISNULL(loja.endereco_loja, 'N/A') as endereco,
                        ISNULL(munici.nome_munic, 'N/A') as cidade,
                        ISNULL(munici.uf, 'N/A') as estado,
                        soli.chave_loja,
                        CONVERT(varchar, soli.data_solicitacao, 103) as data_cadastro,
                        'Cadastramento' as tipo
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                    LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                    LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                    LEFT JOIN (
                        SELECT chave_loja, cod_ibge, nome_munic, uf 
                        FROM mesu..tb_lojas lojas 
                        JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                    ) munici ON munici.chave_loja = soli.chave_loja
                    WHERE " . $whereClause . " 
                    AND acao.desc_acao = 'Cadastramento'
                    ORDER BY soli.data_solicitacao DESC";
                break;
                
            case 'descadastramento':
                $query = "
                    SELECT 
                        soli.chave_loja as id,
                        ISNULL(loja.nome_loja, 'N/A') as nome,
                        ISNULL(loja.email_loja, 'N/A') as email,
                        ISNULL(loja.telefone_loja, 'N/A') as telefone,
                        ISNULL(loja.endereco_loja, 'N/A') as endereco,
                        ISNULL(munici.nome_munic, 'N/A') as cidade,
                        ISNULL(munici.uf, 'N/A') as estado,
                        soli.chave_loja,
                        CONVERT(varchar, soli.data_solicitacao, 103) as data_cadastro,
                        'Descadastramento' as tipo
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                    LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                    LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                    LEFT JOIN (
                        SELECT chave_loja, cod_ibge, nome_munic, uf 
                        FROM mesu..tb_lojas lojas 
                        JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                    ) munici ON munici.chave_loja = soli.chave_loja
                    WHERE " . $whereClause . " 
                    AND acao.desc_acao = 'Descadastramento'
                    ORDER BY soli.data_solicitacao DESC";
                break;
                
            case 'historico':
                // Tentar tabela lojas_historico primeiro
                try {
                    $testQuery = "SELECT TOP 1 * FROM lojas_historico WHERE " . $whereIds;
                    $testResult = $this->sql->select($testQuery);
                    
                    if ($this->sql->qtdRows() > 0) {
                        $query = "
                            SELECT 
                                id, 
                                ISNULL(nome, 'N/A') as nome,
                                ISNULL(email, 'N/A') as email, 
                                ISNULL(telefone, 'N/A') as telefone,
                                ISNULL(endereco, 'N/A') as endereco,
                                ISNULL(cidade, 'N/A') as cidade,
                                ISNULL(estado, 'N/A') as estado,
                                chave_loja,
                                CONVERT(varchar, data_cadastro, 103) as data_cadastro,
                                'Histórico' as tipo
                            FROM lojas_historico 
                            WHERE " . $whereClause . "
                            ORDER BY data_cadastro DESC";
                    } else {
                        throw new Exception("Tabela lojas_historico vazia");
                    }
                } catch (Exception $e) {
                    // Usar solução alternativa
                    $query = "
                        SELECT 
                            soli.chave_loja as id,
                            ISNULL(loja.nome_loja, 'N/A') as nome,
                            ISNULL(loja.email_loja, 'N/A') as email,
                            ISNULL(loja.telefone_loja, 'N/A') as telefone,
                            ISNULL(loja.endereco_loja, 'N/A') as endereco,
                            ISNULL(munici.nome_munic, 'N/A') as cidade,
                            ISNULL(munici.uf, 'N/A') as estado,
                            soli.chave_loja,
                            CONVERT(varchar, soli.data_solicitacao, 103) as data_cadastro,
                            'Histórico' as tipo
                        FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                        LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                        LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                        LEFT JOIN (
                            SELECT chave_loja, cod_ibge, nome_munic, uf 
                            FROM mesu..tb_lojas lojas 
                            JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                        ) munici ON munici.chave_loja = soli.chave_loja
                        WHERE " . $whereClause . " 
                        AND acao.desc_acao IN ('Cadastramento', 'Descadastramento')
                        ORDER BY soli.data_solicitacao DESC";
                }
                break;
                
            default: // 'all'
                $query = "
                    SELECT 
                        soli.chave_loja as id,
                        ISNULL(loja.nome_loja, 'N/A') as nome,
                        ISNULL(loja.email_loja, 'N/A') as email,
                        ISNULL(loja.telefone_loja, 'N/A') as telefone,
                        ISNULL(loja.endereco_loja, 'N/A') as endereco,
                        ISNULL(munici.nome_munic, 'N/A') as cidade,
                        ISNULL(munici.uf, 'N/A') as estado,
                        soli.chave_loja,
                        CONVERT(varchar, soli.data_solicitacao, 103) as data_cadastro,
                        ISNULL(acao.desc_acao, 'N/A') as tipo
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                    LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                    LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                    LEFT JOIN (
                        SELECT chave_loja, cod_ibge, nome_munic, uf 
                        FROM mesu..tb_lojas lojas 
                        JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                    ) munici ON munici.chave_loja = soli.chave_loja
                    WHERE " . $whereClause . " 
                    AND acao.desc_acao IN ('Cadastramento', 'Descadastramento')
                    ORDER BY soli.data_solicitacao DESC";
                break;
        }
        
        if (empty($query)) {
            error_log("getSelectedRecords - No query generated for filter: " . $filter);
            return array();
        }
        
        error_log("getSelectedRecords - Executing query...");
        
        $result = $this->sql->select($query);
        $rowCount = $this->sql->qtdRows();
        
        error_log("getSelectedRecords - Query returned: " . $rowCount . " rows");
        
        return $result;
        
    } catch (Exception $e) {
        error_log("getSelectedRecords - Exception: " . $e->getMessage());
        return array();
    }
}