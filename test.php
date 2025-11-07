Perfect! Now I understand the correct table structure. Here's the updated query:

```sql
WITH TransacoesPeriodo AS (
    SELECT 
        BE.COD_EMPRESA,
        BE.MOTIVO_BLOQUEIO,
        BE.DATA_BLOQUEIO,
        (
            SELECT COUNT(*)
            FROM PGTOCORSP..TB_EVT12_TRANS T
            WHERE T.COD_EMPRESA = BE.COD_EMPRESA
            AND CONCAT(T.ANO, '-', LPAD(T.MES, 2, '0'), '-01') >= DATE_SUB(BE.DATA_BLOQUEIO, INTERVAL 3 MONTH)
            AND CONCAT(T.ANO, '-', LPAD(T.MES, 2, '0'), '-01') <= BE.DATA_BLOQUEIO
        ) as QTD_TRANSACOES
    FROM 
        DATALAKE..DL_BRADESCO_EXPRESSO BE
    WHERE 
        BE.DATA_BLOQUEIO BETWEEN :dataInicio AND :dataFim
        AND BE.MOTIVO_BLOQUEIO IS NOT NULL
    GROUP BY 
        BE.COD_EMPRESA,
        BE.MOTIVO_BLOQUEIO,
        BE.DATA_BLOQUEIO
)
SELECT 
    MOTIVO_BLOQUEIO,
    QTD_TRANSACOES,
    CASE 
        WHEN QTD_TRANSACOES = 0 THEN 'Zero'
        WHEN QTD_TRANSACOES BETWEEN 1 AND 19 THEN '1 a 19'
        WHEN QTD_TRANSACOES BETWEEN 20 AND 59 THEN '20 a 59'
        WHEN QTD_TRANSACOES BETWEEN 60 AND 149 THEN '60 a 149'
        WHEN QTD_TRANSACOES BETWEEN 150 AND 249 THEN '150 a 249'
        WHEN QTD_TRANSACOES BETWEEN 250 AND 499 THEN '250 a 499'
        ELSE 'Acima de 500'
    END as FAIXA_VOLUME
FROM 
    TransacoesPeriodo
```

## Key Changes:

**1. Correct tables:**
- **Main table:** `DATALAKE..DL_BRADESCO_EXPRESSO` (contains MOTIVO_BLOQUEIO and DATA_BLOQUEIO)
- **Transaction table:** `PGTOCORSP..TB_EVT12_TRANS` (contains ANO, MES for volume counting)

**2. Join on COD_EMPRESA:**
```sql
WHERE T.COD_EMPRESA = BE.COD_EMPRESA
```

**3. Date handling with ANO/MES:**
```sql
CONCAT(T.ANO, '-', LPAD(T.MES, 2, '0'), '-01')
```
- Converts ANO (year) and MES (month) into a date format
- `LPAD(T.MES, 2, '0')` ensures month is always 2 digits (01, 02, etc.)
- Creates dates like '2025-10-01', '2025-07-01'

**4. 3-month comparison:**
```sql
AND CONCAT(T.ANO, '-', LPAD(T.MES, 2, '0'), '-01') >= DATE_SUB(BE.DATA_BLOQUEIO, INTERVAL 3 MONTH)
AND CONCAT(T.ANO, '-', LPAD(T.MES, 2, '0'), '-01') <= BE.DATA_BLOQUEIO
```

## Example:

**User selects:** 2025-10-26 to 2025-10-30

**Blocked store found:** 
- COD_EMPRESA = 12345
- DATA_BLOQUEIO = 2025-10-27
- MOTIVO_BLOQUEIO = "Inadimplência"

**Volume count logic:**
- 3 months back: 2025-07-27
- Counts all rows in TB_EVT12_TRANS where:
  - COD_EMPRESA = 12345
  - (ANO, MES) from 2025-07 to 2025-10
  - This includes: ANO=2025 & MES IN (7, 8, 9, 10)

---

Would you like me to update the Model file with this corrected query?