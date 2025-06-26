public function getChaveCadastro($filter = 'all') {
    error_log("getChaveCadastro called with filter: " . $filter);
    
    try {
        // Query base (sem filtro final)
        $baseQuery = "select soli.CHAVE_LOJA
            from PGTOCORSP.dbo.tb_pgto_solicitacao soli
            left join (
                select a.data_detalhe, cod_etapa, cod_status, exclusao, a.cod_solicitacao, cod_detalhe,
                       datediff(day,a.data_detalhe,getdate()) as dias_ocorrencia 
                from PGTOCORSP.dbo.tb_pgto_solicitacao_detalhe a 
                join (
                    select distinct cod_solicitacao, max(data_detalhe) as data_detalhe 
                    from PGTOCORSP.dbo.tb_pgto_solicitacao_detalhe 
                    group by cod_solicitacao
                ) b on b.cod_solicitacao = a.cod_solicitacao and b.data_detalhe = a.data_detalhe
            ) detalhe on detalhe.cod_solicitacao = soli.cod_solicitacao 
            left join PGTOCORSP.dbo.tb_pgto_acao_solicitacao acao on acao.cod_acao = soli.cod_acao 
            left join PGTOCORSP.dbo.tb_pgto_tipo_pagamento ti_pagam on ti_pagam.cod_tipo_pagamento = soli.cod_tipo_pagamento 
            left join mesu.dbo.tb_lojas loja on loja.chave_loja = soli.chave_loja 
            left join mesu.dbo.funcionarios funcionarios on funcionarios.cod_func = soli.cod_usu
            left join (
                select chave_loja, cod_ibge, nome_munic, uf 
                from mesu..tb_lojas lojas 
                join mesu..munic_presenca munic on cd_munic = cod_ibge
            ) munici on munici.chave_loja = soli.chave_loja
            where cod_status = 3 and detalhe.cod_etapa in(1) and exclusao = 0 and not ti_pagam.cod_tipo_pagamento = 3";
        
        // Adicionar filtro específico baseado no parâmetro
        $finalQuery = $baseQuery;
        
        switch($filter) {
            case 'cadastramento':
                $finalQuery .= " and desc_acao = 'Cadastramento'";
                error_log("getChaveCadastro - Using CADASTRAMENTO filter");
                break;
                
            case 'descadastramento':
                $finalQuery .= " and desc_acao = 'Descadastramento'";
                error_log("getChaveCadastro - Using DESCADASTRAMENTO filter");
                break;
                
            case 'historico':
                $finalQuery .= " and desc_acao IN ('Cadastramento', 'Descadastramento')";
                error_log("getChaveCadastro - Using HISTORICO filter");
                break;
                
            case 'all':
            default:
                $finalQuery .= " and desc_acao IN ('Cadastramento', 'Descadastramento')";
                error_log("getChaveCadastro - Using ALL filter (default)");
                break;
        }
        
        error_log("getChaveCadastro - Executing query...");
        
        // Executar query
        $result = $this->sql->select($finalQuery);
        $rowCount = $this->sql->qtdRows();
        
        error_log("getChaveCadastro - Query executed, rows returned: " . $rowCount);
        
        // CORREÇÃO: Processar resultado corretamente
        $chaves = array();
        if ($rowCount > 0 && is_array($result)) {
            error_log("getChaveCadastro - Processing result array...");
            error_log("getChaveCadastro - Sample result structure: " . print_r($result[0], true));
            
            foreach ($result as $row) {
                // Tentar diferentes formas de acessar o valor
                $chave_valor = null;
                
                if (isset($row['CHAVE_LOJA']) && !empty($row['CHAVE_LOJA'])) {
                    $chave_valor = $row['CHAVE_LOJA'];
                } elseif (isset($row[0]) && !empty($row[0])) {
                    // Caso retorne por índice numérico
                    $chave_valor = $row[0];
                } elseif (is_string($row)) {
                    // Caso retorne string direta
                    $chave_valor = $row;
                }
                
                if ($chave_valor !== null) {
                    // Garantir que é string e não array
                    $chave_str = is_array($chave_valor) ? 
                                 (isset($chave_valor[0]) ? $chave_valor[0] : '') : 
                                 strval($chave_valor);
                    
                    if (!empty($chave_str)) {
                        $chaves[] = $chave_str;
                    }
                }
            }
        }
        
        // Remover duplicatas
        $uniqueChaves = array_unique($chaves);
        
        error_log("getChaveCadastro - Raw chaves count: " . count($chaves));
        error_log("getChaveCadastro - Unique chaves count: " . count($uniqueChaves));
        error_log("getChaveCadastro - Sample unique chaves: " . implode(', ', array_slice($uniqueChaves, 0, 5)));
        
        return array_values($uniqueChaves); // Reindexar array
        
    } catch (Exception $e) {
        error_log("getChaveCadastro - Exception: " . $e->getMessage());
        return array();
    }
}
