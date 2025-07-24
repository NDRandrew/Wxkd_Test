<?php
// Replace the ajaxGetHistoricoDetails method in your Wxkd_DashboardController.php

public function ajaxGetHistoricoDetails() {
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    // Clean any output buffer and start fresh
    if (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    try {
        if ($chaveLote <= 0) {
            throw new Exception("CHAVE_LOTE inválido");
        }
        
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = $chaveLote ORDER BY CHAVE_LOJA";
        $detailData = $this->model->sql->select($query);
        
        // Start building clean XML response
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<response>' . "\n";
        $xml .= '<success>true</success>' . "\n";
        $xml .= '<detailData>' . "\n";
        
        if (is_array($detailData) && count($detailData) > 0) {
            foreach ($detailData as $rowIndex => $row) {
                $xml .= '  <row id="' . ($rowIndex + 1) . '">' . "\n";
                
                // IMPORTANT: Clean the row data to remove numeric keys
                $cleanRow = $this->cleanDatabaseRow($row);
                
                // Define expected fields in the order they should appear
                $expectedFields = array(
                    'CHAVE_LOJA' => '',
                    'NOME_LOJA' => '',
                    'COD_EMPRESA' => '',
                    'COD_LOJA' => '',
                    'TIPO_CORRESPONDENTE' => '',
                    'DATA_CONCLUSAO' => '',
                    'DATA_SOLICITACAO' => '',
                    'DEP_DINHEIRO' => '0.00',
                    'DEP_CHEQUE' => '0.00',
                    'REC_RETIRADA' => '0.00',
                    'SAQUE_CHEQUE' => '0.00',
                    'SEGUNDA_VIA_CARTAO' => '',
                    'HOLERITE_INSS' => '',
                    'CONS_INSS' => '',
                    'PROVA_DE_VIDA' => '',
                    'DATA_CONTRATO' => '',
                    'TIPO_CONTRATO' => '',
                    'DATA_LOG' => '',
                    'FILTRO' => ''
                );
                
                // Only include the expected fields, ignore numeric indices
                foreach ($expectedFields as $fieldName => $defaultValue) {
                    $value = isset($cleanRow[$fieldName]) ? $cleanRow[$fieldName] : $defaultValue;
                    $cleanValue = $this->cleanXmlValue($value);
                    $xml .= '    <' . $fieldName . '>' . $cleanValue . '</' . $fieldName . '>' . "\n";
                }
                
                $xml .= '  </row>' . "\n";
            }
        }
        
        $xml .= '</detailData>' . "\n";
        $xml .= '</response>';
        
        // Clear any previous output
        ob_clean();
        
        // Set headers only if they haven't been sent
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }
        
        echo $xml;
        
    } catch (Exception $e) {
        // Clear any previous output
        ob_clean();
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<response>' . "\n";
        $xml .= '<success>false</success>' . "\n";
        $xml .= '<e>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</e>' . "\n";
        $xml .= '</response>';
        
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
        }
        
        echo $xml;
        
        error_log("ajaxGetHistoricoDetails error: " . $e->getMessage());
    }
    
    // End output buffering and flush
    ob_end_flush();
    exit;
}

// Add this helper method to clean database row data
private function cleanDatabaseRow($row) {
    $cleanRow = array();
    
    if (!is_array($row)) {
        return $cleanRow;
    }
    
    foreach ($row as $key => $value) {
        // Only keep non-numeric keys (associative array keys)
        if (!is_numeric($key)) {
            $cleanRow[$key] = $value;
        }
    }
    
    return $cleanRow;
}

// Enhanced helper method to clean XML values
private function cleanXmlValue($value) {
    if ($value === null) {
        return '';
    }
    
    // Convert to string
    $value = (string)$value;
    
    // Remove or replace invalid XML characters
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    
    // Escape special XML characters
    $value = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    
    return $value;
}

// Alternative method using output buffering more aggressively
public function ajaxGetHistoricoDetailsClean() {
    // Start output buffering at the very beginning
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    try {
        if ($chaveLote <= 0) {
            throw new Exception("CHAVE_LOTE inválido: $chaveLote");
        }
        
        // Execute query
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = $chaveLote ORDER BY CHAVE_LOJA";
        $detailData = $this->model->sql->select($query);
        
        // Build response array instead of XML string
        $response = array(
            'success' => true,
            'detailData' => array()
        );
        
        if (is_array($detailData) && count($detailData) > 0) {
            foreach ($detailData as $row) {
                // Clean row data
                $cleanRow = array();
                
                // Extract only the named fields we need
                $fields = array(
                    'CHAVE_LOJA', 'NOME_LOJA', 'COD_EMPRESA', 'COD_LOJA',
                    'TIPO_CORRESPONDENTE', 'DATA_CONCLUSAO', 'DATA_SOLICITACAO',
                    'DEP_DINHEIRO', 'DEP_CHEQUE', 'REC_RETIRADA', 'SAQUE_CHEQUE',
                    'SEGUNDA_VIA_CARTAO', 'HOLERITE_INSS', 'CONS_INSS', 'PROVA_DE_VIDA',
                    'DATA_CONTRATO', 'TIPO_CONTRATO', 'DATA_LOG', 'FILTRO'
                );
                
                foreach ($fields as $field) {
                    $cleanRow[$field] = isset($row[$field]) ? $this->cleanXmlValue($row[$field]) : '';
                }
                
                $response['detailData'][] = $cleanRow;
            }
        }
        
        // Convert to XML
        $xml = $this->arrayToXml($response);
        
        // Clear buffer and send clean XML
        ob_clean();
        
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
        }
        
        echo $xml;
        
    } catch (Exception $e) {
        ob_clean();
        
        $errorResponse = array(
            'success' => false,
            'e' => $e->getMessage()
        );
        
        $xml = $this->arrayToXml($errorResponse);
        
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('HTTP/1.1 500 Internal Server Error');
        }
        
        echo $xml;
        error_log("ajaxGetHistoricoDetails error: " . $e->getMessage());
    }
    
    ob_end_flush();
    exit;
}

// Helper method to convert array to clean XML
private function arrayToXml($array, $rootElement = 'response') {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<' . $rootElement . '>' . "\n";
    
    foreach ($array as $key => $value) {
        if ($key === 'detailData' && is_array($value)) {
            $xml .= '<detailData>' . "\n";
            foreach ($value as $index => $row) {
                $xml .= '  <row id="' . ($index + 1) . '">' . "\n";
                foreach ($row as $fieldName => $fieldValue) {
                    $xml .= '    <' . $fieldName . '>' . $fieldValue . '</' . $fieldName . '>' . "\n";
                }
                $xml .= '  </row>' . "\n";
            }
            $xml .= '</detailData>' . "\n";
        } else {
            $xml .= '<' . $key . '>' . (is_array($value) ? 'Array' : $value) . '</' . $key . '>' . "\n";
        }
    }
    
    $xml .= '</' . $rootElement . '>';
    return $xml;
}
?>