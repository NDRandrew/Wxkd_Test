<?php
// Add these methods to analise_encerramento_model.class.php

public function updateDataContVerified($codSolicitacao, $dataContVerified) {
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
              SET DATA_CONT_VERIFIED = '" . $dataContVerified . "' 
              WHERE COD_SOLICITACAO = " . intval($codSolicitacao);
    return $this->sql->update($query);
}

public function getDataContVerified($codSolicitacao) {
    $query = "SELECT DATA_CONT_VERIFIED 
              FROM MESU..ENCERRAMENTO_TB_PORTAL 
              WHERE COD_SOLICITACAO = " . intval($codSolicitacao);
    $result = $this->sql->select($query);
    return $result ? $result[0]['DATA_CONT_VERIFIED'] : null;
}

public function solicitacoesEncerramentoWithVerified($where, $limit = 25, $offset = 0) {
    $query = "
        ;WITH Q AS (
            SELECT
                A.COD_SOLICITACAO AS COD_SOLICITACAO, 
                A.COD_AG AS COD_AG, 
                CASE WHEN A.COD_AG = F.COD_AG_LOJA THEN F.NOME_AG ELSE 'AGENCIA' END AS NOME_AG, 
                A.CHAVE_LOJA AS CHAVE_LOJA, 
                F.NOME_LOJA AS NOME_LOJA, 
                G.NR_PACB AS NR_PACB, 
                F.COD_EMPRESA AS COD_EMPRESA,
                A.DATA_CAD AS DATA_RECEPCAO, 
                F.RZ_SOCIAL_EMP AS NOME_EMPRESA,
                F.CNPJ AS CNPJ,
                N.DATA_CONTRATO,
                ES.DATA_CONT_VERIFIED,
                ROW_NUMBER() OVER (
                    PARTITION BY A.COD_SOLICITACAO
                    ORDER BY COALESCE(G.DATA_LAST_TRANS, A.DATA_CAD) DESC, A.COD_SOLICITACAO DESC
                ) AS rn
            FROM 
                TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK)
                JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK)
                    ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO 
                LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK)
                    ON A.CHAVE_LOJA = F.CHAVE_LOJA 
                JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK)
                    ON A.COD_SOLICITACAO = G.COD_SOLICITACAO
                LEFT JOIN (
                    SELECT KEY_EMPRESA, DATA_CONTRATO
                    FROM MESU..TB_EMPRESA_VERSAO_CONTRATO2
                ) N ON F.COD_EMPRESA = N.KEY_EMPRESA
                LEFT JOIN MESU..ENCERRAMENTO_TB_PORTAL ES WITH (NOLOCK)
                    ON A.COD_SOLICITACAO = ES.COD_SOLICITACAO
            WHERE 1 = 1
            AND F.BE_INAUGURADO = 1
            " . $where . "
        )
        SELECT
            COD_SOLICITACAO, 
            COD_AG, 
            NOME_AG, 
            CHAVE_LOJA, 
            NOME_LOJA, 
            NR_PACB, 
            COD_EMPRESA,
            DATA_RECEPCAO, 
            NOME_EMPRESA,
            CNPJ,
            DATA_CONTRATO,
            DATA_CONT_VERIFIED
        FROM Q
        WHERE rn = 1
        ORDER BY COD_SOLICITACAO DESC
        OFFSET " . (int)$offset . " ROWS
        FETCH NEXT " . (int)$limit . " ROWS ONLY;
    ";

    $dados = $this->sql->select($query);
    return $dados;
}
?>