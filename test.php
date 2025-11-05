Looking at the code, I found the issue. When importing CSV/Excel, the query needs to include the `COD_TIPO_SERVICO=1` filter that's used in the regular data loading.

Here's the fix:

## Changes to `encerramento_massa.php` (EnMa.txt)

**In `generateFromExcel()` method, add the filter:**

```php
public function generateFromExcel($filePath, $originalFilename = null) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Arquivo não encontrado'];
    }
    
    if ($originalFilename) {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    } else {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }
    
    if ($extension === 'csv') {
        $chaveLojas = $this->parseCSV($filePath);
    } else if (in_array($extension, ['xlsx', 'xls'])) {
        $chaveLojas = $this->parseExcel($filePath);
    } else {
        return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX. Extensão detectada: ' . $extension];
    }
    
    if (empty($chaveLojas)) {
        return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
    }
    
    // FIX: Add COD_TIPO_SERVICO filter
    $where = "AND A.COD_TIPO_SERVICO=1 AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
    $dados = $this->model->solicitacoesEncerramento($where, 9999, 0);
    
    if (empty($dados)) {
        return ['success' => false, 'message' => 'Dados não encontrados para as Chaves Loja fornecidas'];
    }
    
    $result = $this->generateTXT($dados);
    
    $result['errors'] = $this->errors;
    $result['has_errors'] = !empty($this->errors);
    
    return $result;
}
```

**In `getCNPJsForExcelVerification()` method, add the same filter:**

```php
public function getCNPJsForExcelVerification($filePath, $originalFilename = null) {
    if (!file_exists($filePath)) {
        return ['success' => false, 'message' => 'Arquivo não encontrado'];
    }
    
    if ($originalFilename) {
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    } else {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    }
    
    if ($extension === 'csv') {
        $chaveLojas = $this->parseCSV($filePath);
    } else if (in_array($extension, ['xlsx', 'xls'])) {
        $chaveLojas = $this->parseExcel($filePath);
    } else {
        return ['success' => false, 'message' => 'Formato não suportado. Extensão detectada: ' . $extension];
    }
    
    if (empty($chaveLojas)) {
        return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada'];
    }
    
    // FIX: Add COD_TIPO_SERVICO filter
    $where = "AND A.COD_TIPO_SERVICO=1 AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
    $dados = $this->model->solicitacoesEncerramento($where, 9999, 0);
    
    if (empty($dados)) {
        return ['success' => false, 'message' => 'Dados não encontrados'];
    }
    
    $cnpjList = [];
    foreach ($dados as $row) {
        $cnpj = $this->formatCNPJ($row['CNPJ']);
        $dataContrato = is_object($row['DATA_CONTRATO']) 
            ? $row['DATA_CONTRATO']->format('Y-m-d') 
            : date('Y-m-d', strtotime($row['DATA_CONTRATO']));
            
        $cnpjList[] = [
            'cod_solicitacao' => $row['COD_SOLICITACAO'],
            'cnpj' => $cnpj,
            'data_contrato' => $dataContrato,
            'chave_loja' => $row['CHAVE_LOJA'],
            'nome_loja' => $row['NOME_LOJA']
        ];
    }
    
    return [
        'success' => true,
        'cnpjs' => $cnpjList
    ];
}
```

## Summary

The issue was that the CSV import was querying the database without the `COD_TIPO_SERVICO=1` filter. This filter is essential because the main view only shows solicitações with `COD_TIPO_SERVICO=1` (encerramento type). Without this filter, the query was returning empty results even though the CHAVE_LOJA values existed in the database.

The fix adds `AND A.COD_TIPO_SERVICO=1` to the WHERE clause in both methods that handle CSV/Excel imports, making them consistent with the regular data loading logic.