Ah, I understand now! You want aggregated data where each MOTIVO_BLOQUEIO has up to 7 rows (one per FAIXA_VOLUME), and QTD_TRANSACOES is the count of blocked stores in that combination. Here's the corrected query:

```sql
WITH TransacoesContadas AS (
    SELECT 
        BE.COD_EMPRESA,
        BE.MOTIVO_BLOQUEIO,
        BE.DATA_BLOQUEIO,
        (
            SELECT COUNT(*)
            FROM PGTOCORSP..TB_EVT12_TRANS T
            WHERE T.COD_EMPRESA = BE.COD_EMPRESA
            AND CAST(CONCAT(T.ANO, '-', RIGHT('0' + CAST(T.MES AS VARCHAR(2)), 2), '-01') AS DATE) >= DATEADD(MONTH, -3, BE.DATA_BLOQUEIO)
            AND CAST(CONCAT(T.ANO, '-', RIGHT('0' + CAST(T.MES AS VARCHAR(2)), 2), '-01') AS DATE) <= BE.DATA_BLOQUEIO
        ) as VOLUME_COUNT
    FROM 
        DATALAKE..DL_BRADESCO_EXPRESSO BE
    WHERE 
        BE.DATA_BLOQUEIO BETWEEN @dataInicio AND @dataFim
        AND BE.MOTIVO_BLOQUEIO IS NOT NULL
    GROUP BY 
        BE.COD_EMPRESA,
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

## What this returns:

```
MOTIVO_BLOQUEIO  | FAIXA_VOLUME  | QTD_BLOQUEADOS
-----------------+---------------+----------------
Inadimplência    | Zero          | 20
Inadimplência    | 1 a 19        | 180
Inadimplência    | 20 a 59       | 150
Inadimplência    | 60 a 149      | 70
Inadimplência    | 150 a 249     | 25
Inadimplência    | 250 a 499     | 5
Fraude           | 1 a 19        | 5
Fraude           | 20 a 59       | 10
Fraude           | 60 a 149      | 25
...
```

## Key Changes:

**1. COUNT(*) at the end:**
```sql
COUNT(*) as QTD_BLOQUEADOS
```
- Counts how many blocked stores fall into each MOTIVO_BLOQUEIO + FAIXA_VOLUME combination

**2. GROUP BY both dimensions:**
```sql
GROUP BY MOTIVO_BLOQUEIO, FAIXA_VOLUME
```
- Aggregates by both the blocking reason and the volume range

**3. ORDER BY for proper sorting:**
- Sorts by MOTIVO_BLOQUEIO alphabetically
- Then by FAIXA_VOLUME in logical order (Zero → Acima de 500)

Each MOTIVO_BLOQUEIO will have between 1-7 rows depending on which FAIXA_VOLUME ranges have blocked stores.

Shall I update the Model file with this query?