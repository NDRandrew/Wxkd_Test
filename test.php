I found the issues! There are several problems preventing errors from being inserted:

## Issues Found:

1. **SQL Syntax Error** - Trailing comma in INSERT statement
2. **Missing CHAVE_LOTE** - Not being set
3. **No error handling** - Fails silently

<artifact identifier="encerramento-massa-fix" type="application/vnd.ant.code" language="php" title="Fixed Error Insertion in encerramento_massa.php">
<?php
// REPLACE the insertErrorToDatabase method in EnMa.txt with this fixed version:

public function insertErrorToDatabase($errors, $fileName) {
    if (empty($errors)) return;
    
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    
    $model = new Analise();
    
    // Generate a unique batch ID for this file
    $chaveLote = $this->generateChaveLote($model);
    
    foreach ($errors as $error) {
        $ocorrencia = "Arquivo: " . $fileName . "\n";
        $ocorrencia .= "Tipo: " . $error['error_type'] . "\n";
        $ocorrencia .= "Mensagem: " . $error['error_message'] . "\n";
        $ocorrencia .= "Data Contrato: " . $error['data_contrato'] . "\n";
        $ocorrencia .= "Linha TXT: " . $error['txt_line'];
        
        // FIX 1: Remove trailing comma
        // FIX 2: Add CHAVE_LOTE
        $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
                (OCORRENCIA, CNPJs, CHAVE_LOTE)
                VALUES (
                    '" . addslashes($ocorrencia) . "',
                    '" . addslashes($error['cnpj']) . "',
                    " . intval($chaveLote) . "
                )";
        
        try {
            $result = $model->insert($query);
            
            // FIX 3: Add error logging
            if (!$result) {
                error_log("Failed to insert error to database for CNPJ: " . $error['cnpj']);
                error_log("Query: " . $query);
            } else {
                error_log("Successfully inserted error for CNPJ: " . $error['cnpj'] . " to batch: " . $chaveLote);
            }
        } catch (Exception $e) {
            error_log("Exception inserting error to database: " . $e->getMessage());
            error_log("Query: " . $query);
        }
    }
}

// ADD this new method to generate CHAVE_LOTE
private function generateChaveLote($model) {
    // Get the next available batch number
    $query = "SELECT ISNULL(MAX(CHAVE_LOTE), 99) + 1 AS NEXT_LOTE 
              FROM MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA";
    
    try {
        $result = $model->sql->select($query);
        return isset($result[0]['NEXT_LOTE']) ? intval($result[0]['NEXT_LOTE']) : 100;
    } catch (Exception $e) {
        error_log("Error generating CHAVE_LOTE: " . $e->getMessage());
        // Fallback to timestamp-based ID
        return 100 + intval(date('His'));
    }
}
?>
</artifact>

## Testing the Fix

Now let's add better debugging to see what's happening:

<artifact identifier="debugging-updates" type="application/vnd.ant.code" language="php" title="Add Debugging to generateTXT">
<?php
// REPLACE the generateTXT method ending in EnMa.txt with this version that has debugging:

private function generateTXT($dados) {
    $linhas = [];
    $totalLinhas = 0;
    
    // Count total lines
    foreach ($dados as $row) {
        $verifiedDate = null;
        if (isset($row['COD_SOLICITACAO'])) {
            $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
        }
        
        if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
            $dates = explode(',', $verifiedDate);
            $totalLinhas += count($dates);
        } else {
            $totalLinhas++;
        }
    }
    
    $linhas[] = $this->gerarHeader($totalLinhas);
    
    $sequencial = 2;
    foreach ($dados as $row) {
        $verifiedDate = null;
        if (isset($row['COD_SOLICITACAO'])) {
            $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
        }
        
        if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
            $dates = explode(',', $verifiedDate);
            foreach ($dates as $date) {
                $rowCopy = $row;
                $rowCopy['DATA_CONTRATO_OVERRIDE'] = trim($date);
                
                $lineResult = $this->gerarDetalhe($rowCopy, $sequencial);
                $linhas[] = $lineResult['line'];
                
                if ($lineResult['error']) {
                    $this->errors[] = [
                        'cod_solicitacao' => $row['COD_SOLICITACAO'],
                        'chave_loja' => $row['CHAVE_LOJA'],
                        'nome_loja' => $row['NOME_LOJA'],
                        'cnpj' => $row['CNPJ'],
                        'data_contrato' => trim($date),
                        'error_type' => $lineResult['error_type'],
                        'error_message' => $lineResult['error_message'],
                        'txt_line' => $lineResult['line'],
                        'sequencial' => $sequencial
                    ];
                }
                
                $sequencial++;
            }
        } else {
            $lineResult = $this->gerarDetalhe($row, $sequencial);
            $linhas[] = $lineResult['line'];
            
            if ($lineResult['error']) {
                $this->errors[] = [
                    'cod_solicitacao' => $row['COD_SOLICITACAO'],
                    'chave_loja' => $row['CHAVE_LOJA'],
                    'nome_loja' => $row['NOME_LOJA'],
                    'cnpj' => $row['CNPJ'],
                    'data_contrato' => $verifiedDate ?: (is_object($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO']->format('Y-m-d') : $row['DATA_CONTRATO']),
                    'error_type' => $lineResult['error_type'],
                    'error_message' => $lineResult['error_message'],
                    'txt_line' => $lineResult['line'],
                    'sequencial' => $sequencial
                ];
            }
            
            $sequencial++;
        }
    }
    
    $linhas[] = $this->gerarTrailer($totalLinhas);
    
    $conteudo = implode("\r\n", $linhas);
    $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
    
    // DEBUG: Log error count
    error_log("TXT Generation completed. Total errors: " . count($this->errors));
    if (!empty($this->errors)) {
        error_log("Error details: " . print_r($this->errors, true));
    }
    
    $result = [
        'success' => true,
        'conteudo' => $conteudo,
        'nomeArquivo' => $nomeArquivo,
        'totalRegistros' => $totalLinhas,
        'errors' => $this->errors,
        'has_errors' => !empty($this->errors)
    ];

    // Insert errors to database
    if (!empty($this->errors)) {
        error_log("Calling insertErrorToDatabase with " . count($this->errors) . " errors");
        $this->insertErrorToDatabase($this->errors, $nomeArquivo);
    } else {
        error_log("No errors to insert");
    }

    return $result;
}
?>
</artifact>

## How to Test

1. **Check PHP Error Log** - Enable error logging:
```php
// Add to the top of encerramento_massa.php
ini_set('log_errors', 1);
ini_set('error_log', 'X:/path/to/error_log.txt');
```

2. **Force an error** by clearing verified dates:
```sql
-- Run this SQL to clear verified dates for testing
UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
SET DATA_CONT_VERIFIED = NULL 
WHERE COD_SOLICITACAO IN (your_test_ids);
```

3. **Generate TXT** - Now when you generate TXT, it should:
   - Detect missing DATA_CONT_VERIFIED
   - Create `missing_verification` errors
   - Insert them into ENCERRAMENTO_TB_PORTAL_OCORRENCIA
   - Show them in the Ocorrências view

4. **Check the database**:
```sql
SELECT TOP 10 * 
FROM MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
ORDER BY ID DESC;
```

The main issue was the **trailing comma** in the SQL INSERT statement which would cause a syntax error. With these fixes, errors should now be properly inserted into the database.