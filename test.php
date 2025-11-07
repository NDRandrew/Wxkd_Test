Perfect! Using a pre-aggregated table will be much faster. Here's the updated query:

```sql
WITH TransacoesContadas AS (
    SELECT 
        BE.CHAVE_LOJA,
        BE.MOTIVO_BLOQUEIO,
        BE.DATA_BLOQUEIO,
        (
            SELECT ISNULL(SUM(IND.QTD_ATIVOS), 0)
            FROM DATAWAREHOUSE..TB_INDICADORES_BE IND
            WHERE IND.CHAVE_LOJA = BE.CHAVE_LOJA
            AND CAST(CONCAT(IND.ANO, '-', RIGHT('0' + CAST(IND.MES AS VARCHAR(2)), 2), '-01') AS DATE) >= DATEADD(MONTH, -3, BE.DATA_BLOQUEIO)
            AND CAST(CONCAT(IND.ANO, '-', RIGHT('0' + CAST(IND.MES AS VARCHAR(2)), 2), '-01') AS DATE) <= BE.DATA_BLOQUEIO
        ) as VOLUME_COUNT
    FROM 
        DATALAKE..DL_BRADESCO_EXPRESSO BE
    WHERE 
        BE.DATA_BLOQUEIO BETWEEN @dataInicio AND @dataFim
        AND BE.MOTIVO_BLOQUEIO IS NOT NULL
    GROUP BY 
        BE.CHAVE_LOJA,
        BE.MOTIVO_BLOQUEIO,
        BE.DATA_BLOQUEIO
),
ComFaixa AS (
    SELECT 
        MOTIVO_BLOQUEIO,
        CASE 
            WHEN VOLUME_COUNT = 0 THEN 'Zero'
            WHEN VOLUME_COUNT BETWEEN 1 AND 19 THEN '1 a 19'
            WHEN VOLUME_COUNT BETWEEN 20 AND 59 THEN '20 a 59'
            WHEN VOLUME_COUNT BETWEEN 60 AND 149 THEN '60 a 149'
            WHEN VOLUME_COUNT BETWEEN 150 AND 249 THEN '150 a 249'
            WHEN VOLUME_COUNT BETWEEN 250 AND 499 THEN '250 a 499'
            ELSE 'Acima de 500'
        END as FAIXA_VOLUME
    FROM 
        TransacoesContadas
)
SELECT 
    MOTIVO_BLOQUEIO,
    FAIXA_VOLUME,
    COUNT(*) as QTD_BLOQUEADOS
FROM 
    ComFaixa
GROUP BY 
    MOTIVO_BLOQUEIO,
    FAIXA_VOLUME
ORDER BY 
    MOTIVO_BLOQUEIO,
    CASE FAIXA_VOLUME
        WHEN 'Zero' THEN 1
        WHEN '1 a 19' THEN 2
        WHEN '20 a 59' THEN 3
        WHEN '60 a 149' THEN 4
        WHEN '150 a 249' THEN 5
        WHEN '250 a 499' THEN 6
        WHEN 'Acima de 500' THEN 7
    END
```

## Key Performance Improvements:

**1. Pre-aggregated table:**
```sql
FROM DATAWAREHOUSE..TB_INDICADORES_BE IND
```
- Much smaller table with aggregated data instead of individual transactions

**2. SUM instead of COUNT:**
```sql
SELECT ISNULL(SUM(IND.QTD_ATIVOS), 0)
```
- Simply sums the already-calculated transaction counts
- `ISNULL(..., 0)` handles cases where there are no records (returns 0 instead of NULL)

**3. Join on CHAVE_LOJA:**
```sql
WHERE IND.CHAVE_LOJA = BE.CHAVE_LOJA
```
- Connects using the store key

This should be significantly faster since:
- TB_INDICADORES_BE is pre-aggregated (fewer rows)
- No need to scan millions of individual transaction records
- Simple SUM operation instead of COUNT(*)

Would you like me to update the Model file with this optimized query?