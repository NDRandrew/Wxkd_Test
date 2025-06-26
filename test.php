public function getTableDataByFilter($filter = 'all') {
    error_log("getTableDataByFilter called with: " . $filter);
    
    try {
        // Obter chaves autorizadas
        $chaves = $this->getChaveCadastro($filter);
        error_log("getTableDataByFilter - chaves returned: " . count($chaves));
        
        if (empty($chaves)) {
            error_log("getTableDataByFilter - No authorized chaves found for filter: " . $filter);
            return array();
        }
        
        // Construir WHERE clause
        $whereClause = "chave_loja IN ('" . implode("','", $chaves) . "')";
        error_log("getTableDataByFilter - WHERE clause: " . substr($whereClause, 0, 100) . "...");
        
        // SOLUÇÃO ALTERNATIVA: Usar uma tabela principal com campo tipo
        // Ao invés de tabelas separadas, vamos usar uma query unificada
        
        $query = "";
        
        switch($filter) {
            case 'cadastramento':
                // Buscar dados de cadastramento da tabela principal
                $query = "
                    SELECT 
                        soli.chave_loja as id,
                        loja.nome_loja as nome,
                        loja.email_loja as email, 
                        loja.telefone_loja as telefone,
                        loja.endereco_loja as endereco,
                        munici.nome_munic as cidade,
                        munici.uf as estado,
                        soli.chave_loja,
                        soli.data_solicitacao as data_cadastro,
                        'cadastramento' as tipo
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                    LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                    LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                    LEFT JOIN (
                        SELECT chave_loja, cod_ibge, nome_munic, uf 
                        FROM mesu..tb_lojas lojas 
                        JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                    ) munici ON munici.chave_loja = soli.chave_loja
                    WHERE " . $whereClause . " 
                    AND desc_acao = 'Cadastramento'
                    ORDER BY soli.data_solicitacao DESC";
                break;
                
            case 'descadastramento':
                $query = "
                    SELECT 
                        soli.chave_loja as id,
                        loja.nome_loja as nome,
                        loja.email_loja as email,
                        loja.telefone_loja as telefone,
                        loja.endereco_loja as endereco,
                        munici.nome_munic as cidade,
                        munici.uf as estado,
                        soli.chave_loja,
                        soli.data_solicitacao as data_cadastro,
                        'descadastramento' as tipo
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                    LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                    LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                    LEFT JOIN (
                        SELECT chave_loja, cod_ibge, nome_munic, uf 
                        FROM mesu..tb_lojas lojas 
                        JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                    ) munici ON munici.chave_loja = soli.chave_loja
                    WHERE " . $whereClause . " 
                    AND desc_acao = 'Descadastramento'
                    ORDER BY soli.data_solicitacao DESC";
                break;
                
            case 'historico':
                // Verificar se tabela lojas_historico existe
                try {
                    $testQuery = "SELECT TOP 1 * FROM lojas_historico WHERE " . $whereClause;
                    $testResult = $this->sql->select($testQuery);
                    
                    if ($this->sql->qtdRows() > 0) {
                        // Tabela existe, usar ela
                        $query = "SELECT id, nome, email, telefone, endereco, cidade, estado, 
                                         chave_loja, data_cadastro, 'historico' as tipo
                                  FROM lojas_historico 
                                  WHERE " . $whereClause . "
                                  ORDER BY data_cadastro DESC";
                    } else {
                        // Tabela não existe ou está vazia, usar solução alternativa
                        throw new Exception("Tabela lojas_historico vazia");
                    }
                } catch (Exception $e) {
                    error_log("getTableDataByFilter - lojas_historico not accessible, using alternative");
                    // Usar dados da tabela principal
                    $query = "
                        SELECT 
                            soli.chave_loja as id,
                            loja.nome_loja as nome,
                            loja.email_loja as email,
                            loja.telefone_loja as telefone,
                            loja.endereco_loja as endereco,
                            munici.nome_munic as cidade,
                            munici.uf as estado,
                            soli.chave_loja,
                            soli.data_solicitacao as data_cadastro,
                            'historico' as tipo
                        FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                        LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                        LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                        LEFT JOIN (
                            SELECT chave_loja, cod_ibge, nome_munic, uf 
                            FROM mesu..tb_lojas lojas 
                            JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                        ) munici ON munici.chave_loja = soli.chave_loja
                        WHERE " . $whereClause . " 
                        AND desc_acao IN ('Cadastramento', 'Descadastramento')
                        ORDER BY soli.data_solicitacao DESC";
                }
                break;
                
            default: // 'all'
                // Para 'all', sempre usar a tabela principal
                $query = "
                    SELECT 
                        soli.chave_loja as id,
                        loja.nome_loja as nome,
                        loja.email_loja as email,
                        loja.telefone_loja as telefone,
                        loja.endereco_loja as endereco,
                        munici.nome_munic as cidade,
                        munici.uf as estado,
                        soli.chave_loja,
                        soli.data_solicitacao as data_cadastro,
                        acao.desc_acao as tipo
                    FROM PGTOCORSP.dbo.tb_pgto_solicitacao soli
                    LEFT JOIN PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao ON acao.cod_acao = soli.cod_acao
                    LEFT JOIN mesu.dbo.tb_lojas loja ON loja.chave_loja = soli.chave_loja
                    LEFT JOIN (
                        SELECT chave_loja, cod_ibge, nome_munic, uf 
                        FROM mesu..tb_lojas lojas 
                        JOIN mesu..munic_presenca munic ON cd_munic = cod_ibge
                    ) munici ON munici.chave_loja = soli.chave_loja
                    WHERE " . $whereClause . " 
                    AND desc_acao IN ('Cadastramento', 'Descadastramento')
                    ORDER BY soli.data_solicitacao DESC";
                break;
        }
        
        if (empty($query)) {
            error_log("getTableDataByFilter - No query generated for filter: " . $filter);
            return array();
        }
        
        error_log("getTableDataByFilter - Query length: " . strlen($query));
        error_log("getTableDataByFilter - Query preview: " . substr($query, 0, 200) . "...");
        
        $result = $this->sql->select($query);
        $rowCount = $this->sql->qtdRows();
        
        error_log("getTableDataByFilter - Query returned: " . $rowCount . " rows");
        
        if ($rowCount > 0) {
            error_log("getTableDataByFilter - Sample first row: " . print_r($result[0], true));
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("getTableDataByFilter - Exception: " . $e->getMessage());
        return array();
    }
}
