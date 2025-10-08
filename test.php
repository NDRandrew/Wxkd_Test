The issue is that the query doesn't include a JOIN to `ENCERRAMENTO_TB_PORTAL` yet. You need to:

## 1. Update Model Query (M (5).txt)

Add LEFT JOIN to the `solicitacoes` method query:

```php
public function solicitacoes($where, $limit = 25, $offset = 0) {
    $query = "
        SELECT
            A.COD_SOLICITACAO COD_SOLICITACAO, 
            A.COD_AG COD_AG, 
            CASE WHEN A.COD_AG = F.COD_AG_LOJA THEN F.NOME_AG ELSE 'AGENCIA' END NOME_AG, 
            A.CHAVE_LOJA CHAVE_LOJA, 
            F.NOME_LOJA NOME_LOJA, 
            G.NR_PACB NR_PACB, 
            F.COD_EMPRESA COD_EMPRESA,
            A.DATA_CAD AS DATA_RECEPCAO, 
            F.RZ_SOCIAL_EMP NOME_EMPRESA,
            F.DATA_RETIRADA_EQPTO,
            F.DATA_BLOQUEIO,
            F.MOTIVO_BLOQUEIO,
            F.DESC_MOTIVO_ENCERRAMENTO,
            G.ORGAO_PAGADOR ORGAO_PAGADOR,
            A.COD_TIPO_SERVICO COD_TIPO_SERVICO,
            F.CNPJ CNPJ,
            F.COD_EMPRESA,
            F.COD_EMPRESA_TEF,
            -- ADD THESE FROM ENCERRAMENTO TABLE
            ISNULL(ENC.STATUS_SOLIC, 'ATIVO') AS STATUS_SOLIC,
            ISNULL(ENC.STATUS_OP, 'Não Efetuado') AS STATUS_OP,
            ISNULL(ENC.STATUS_COM, 'Não Efetuado') AS STATUS_COM,
            ISNULL(ENC.STATUS_VAN, 'Não Efetuado') AS STATUS_VAN,
            ISNULL(ENC.STATUS_BLOQ, 'Não Efetuado') AS STATUS_BLOQ,
            ISNULL(ENC.STATUS_ENCERRAMENTO, 'Não Efetuado') AS STATUS_ENCERRAMENTO,
            ENC.MOTIVO_ENC,
            ENC.DATA_ENC,
            -- Rest of existing fields...
            [KEEP ALL EXISTING CLUSTER FIELDS]
            G.ORGAO_PAGADOR ORGAO_PAGADOR,
            CONVERT(VARCHAR, G.DATA_LAST_TRANS, 103) DATA_LAST_TRANS,
            ISNULL(L.QTD_CHAVE, 0)/3 AS MEDIA_CONTABEIS,
            ISNULL(M.CONTAS, 0)/3 AS MEDIA_NEGOCIO
        FROM 
            TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK) 
            JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK) ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO 
            JOIN TB_ACIONAMENTO_PRESTACAO_CONTA C WITH (NOLOCK) ON A.COD_PRESTACAO_CONTA = C.COD_PRESTACAO_CONTA 
            JOIN TB_ACIONAMENTO_STATUS D WITH (NOLOCK) ON A.COD_STATUS = D.COD_STATUS 
            LEFT JOIN RH..TB_FUNCIONARIOS E WITH (NOLOCK) ON A.COD_SOLICITANTE = E.COD_FUNC
            LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK) ON A.CHAVE_LOJA = F.CHAVE_LOJA 
            JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK) ON A.COD_SOLICITACAO = G.COD_SOLICITACAO  
            LEFT JOIN TB_ACIONAMENTO_RESPOSTAS H WITH (NOLOCK) ON G.COD_SOLUCAO = H.COD_RESPOSTA 
            -- ADD THIS LEFT JOIN
            LEFT JOIN ENCERRAMENTO_TB_PORTAL ENC WITH (NOLOCK) ON A.COD_SOLICITACAO = ENC.COD_SOLICITACAO
            -- Keep all other existing LEFT JOINs...
            [KEEP ALL OTHER EXISTING JOINS]
        WHERE
            1=1 AND F.BE_INAUGURADO = 1
            ".$where."
        ORDER BY A.COD_SOLICITACAO DESC
        OFFSET ".$offset." ROWS
        FETCH NEXT ".$limit." ROWS ONLY"; 
    
    $dados = $this->sql->select($query);
    return $dados;
}
```

## 2. Update getTotalCount method too:

```php
public function getTotalCount($where) {
    $query = "
        SELECT COUNT(*) as TOTAL
        FROM 
            TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK) 
            JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK) ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO 
            JOIN TB_ACIONAMENTO_PRESTACAO_CONTA C WITH (NOLOCK) ON A.COD_PRESTACAO_CONTA = C.COD_PRESTACAO_CONTA 
            JOIN TB_ACIONAMENTO_STATUS D WITH (NOLOCK) ON A.COD_STATUS = D.COD_STATUS 
            LEFT JOIN RH..TB_FUNCIONARIOS E WITH (NOLOCK) ON A.COD_SOLICITANTE = E.COD_FUNC
            LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK) ON A.CHAVE_LOJA = F.CHAVE_LOJA 
            JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK) ON A.COD_SOLICITACAO = G.COD_SOLICITACAO  
            LEFT JOIN TB_ACIONAMENTO_RESPOSTAS H WITH (NOLOCK) ON G.COD_SOLUCAO = H.COD_RESPOSTA 
            LEFT JOIN ENCERRAMENTO_TB_PORTAL ENC WITH (NOLOCK) ON A.COD_SOLICITACAO = ENC.COD_SOLICITACAO
        WHERE
            1=1 AND F.BE_INAUGURADO = 1
            ".$where;
    
    $dados = $this->sql->select($query);
    return $dados[0]['TOTAL'];
}
```

## 3. To populate ENCERRAMENTO_TB_PORTAL with initial data:

Run this SQL script to create records for existing solicitacoes:

```sql
-- Insert records for all existing solicitacoes
INSERT INTO ENCERRAMENTO_TB_PORTAL (
    COD_SOLICITACAO, 
    CHAVE_LOJA, 
    STATUS_SOLIC, 
    STATUS_OP, 
    STATUS_COM, 
    STATUS_VAN, 
    STATUS_BLOQ, 
    STATUS_ENCERRAMENTO
)
SELECT 
    A.COD_SOLICITACAO,
    A.CHAVE_LOJA,
    'ATIVO' AS STATUS_SOLIC,
    'Não Efetuado' AS STATUS_OP,
    'Não Efetuado' AS STATUS_COM,
    'Não Efetuado' AS STATUS_VAN,
    'Não Efetuado' AS STATUS_BLOQ,
    'Não Efetuado' AS STATUS_ENCERRAMENTO
FROM 
    TB_ACIONAMENTO_FIN_SOLICITACOES A
    LEFT JOIN ENCERRAMENTO_TB_PORTAL ENC ON A.COD_SOLICITACAO = ENC.COD_SOLICITACAO
WHERE 
    ENC.COD_SOLICITACAO IS NULL
    AND A.COD_TIPO_SERVICO = 1
```

## 4. Update Controller (C (4).txt) - Fix buildStatusShow:

```php
private function buildStatusShow($row) {
    // Data already comes from query with LEFT JOIN
    $statusConfig = [
        'STATUS_OP' => ['label' => 'Órgão Pagador', 'value' => $row['STATUS_OP'] ?? 'Não Efetuado'],
        'STATUS_COM' => ['label' => 'Comercial', 'value' => $row['STATUS_COM'] ?? 'Não Efetuado'],
        'STATUS_VAN' => ['label' => 'Van-Material', 'value' => $row['STATUS_VAN'] ?? 'Não Efetuado'],
        'STATUS_BLOQ' => ['label' => 'Bloqueio', 'value' => $row['STATUS_BLOQ'] ?? 'Não Efetuado'],
        'STATUS_ENCERRAMENTO' => ['label' => 'Encerramento', 'value' => $row['STATUS_ENCERRAMENTO'] ?? 'Não Efetuado']
    ];
    
    $html = '<div class="card"><div class="card-header"><h3 class="card-title">Status</h3></div>';
    $html .= '<div class="card-body"><div class="table-responsive"><table class="table table-vcenter"><thead><tr>';
    
    foreach ($statusConfig as $config) {
        $html .= '<th>' . $config['label'] . '</th>';
    }
    
    $html .= '</tr></thead><tbody><tr>';
    
    foreach ($statusConfig as $config) {
        $statusClass = $config['value'] === 'Efetuado' ? 'green' : ($config['value'] === 'ERRO' ? 'red' : 'yellow');
        $html .= '<td><span class="status status-' . $statusClass . '"><span class="status-dot"></span>' . $config['value'] . '</span></td>';
    }
    
    $html .= '</tr></tbody></table></div></div></div>';
    return $html;
}
```

## Summary:

**The key issue:** You're using **LEFT JOIN** so data will show even without matching records in ENCERRAMENTO_TB_PORTAL. 

**To have data appear:**
1. Add the LEFT JOIN to both `solicitacoes()` and `getTotalCount()` methods
2. Use `ISNULL()` to provide default values
3. Optionally run the INSERT script to populate the table with initial data

**No data is required** in ENCERRAMENTO_TB_PORTAL for records to show - the LEFT JOIN will return NULL and ISNULL will provide defaults like "Não Efetuado" and "ATIVO".