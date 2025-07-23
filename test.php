<?php
// Replace the ajaxGetHistoricoDetails method in your Wxkd_DashboardController with this enhanced version

public function ajaxGetHistoricoDetails() {
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    // Set proper headers for XML response
    header('Content-Type: application/xml; charset=utf-8');
    
    try {
        if ($chaveLote <= 0) {
            throw new Exception("CHAVE_LOTE inválido");
        }
        
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = $chaveLote ORDER BY CHAVE_LOJA";
        $detailData = $this->model->sql->select($query);
        
        // Start building XML response
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<response>' . "\n";
        $xml .= '<success>true</success>' . "\n";
        $xml .= '<detailData>' . "\n";
        
        if (is_array($detailData) && count($detailData) > 0) {
            foreach ($detailData as $rowIndex => $row) {
                $xml .= '  <row id="' . ($rowIndex + 1) . '">' . "\n";
                
                // Ensure we have all expected fields with proper defaults
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
                
                // Merge actual data with expected fields
                $normalizedRow = array_merge($expectedFields, $row);
                
                foreach ($normalizedRow as $key => $value) {
                    // Clean the key name (remove spaces, special chars)
                    $cleanKey = preg_replace('/[^A-Za-z0-9_]/', '_', $key);
                    $cleanKey = strtoupper($cleanKey);
                    
                    // Clean and escape the value
                    $cleanValue = $this->cleanXmlValue($value);
                    
                    $xml .= '    <' . $cleanKey . '>' . $cleanValue . '</' . $cleanKey . '>' . "\n";
                }
                
                $xml .= '  </row>' . "\n";
            }
        }
        
        $xml .= '</detailData>' . "\n";
        $xml .= '</response>';
        
        // Log the generated XML for debugging
        error_log("Generated XML for CHAVE_LOTE $chaveLote: " . substr($xml, 0, 500) . '...');
        
    } catch (Exception $e) {
        error_log("ajaxGetHistoricoDetails error: " . $e->getMessage());
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<response>' . "\n";
        $xml .= '<success>false</success>' . "\n";
        $xml .= '<e>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</e>' . "\n";
        $xml .= '</response>';
    }
    
    // Ensure we only output XML (no PHP errors or warnings)
    ob_clean();
    echo $xml;
    exit;
}

// Helper method to clean XML values
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

// Alternative method using SimpleXML (more robust)
public function ajaxGetHistoricoDetailsXML() {
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    header('Content-Type: application/xml; charset=utf-8');
    
    try {
        if ($chaveLote <= 0) {
            throw new Exception("CHAVE_LOTE inválido");
        }
        
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = $chaveLote ORDER BY CHAVE_LOJA";
        $detailData = $this->model->sql->select($query);
        
        // Create XML using SimpleXML
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response></response>');
        $xml->addChild('success', 'true');
        
        $detailDataNode = $xml->addChild('detailData');
        
        if (is_array($detailData) && count($detailData) > 0) {
            foreach ($detailData as $rowIndex => $row) {
                $rowNode = $detailDataNode->addChild('row');
                $rowNode->addAttribute('id', $rowIndex + 1);
                
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
                
                $normalizedRow = array_merge($expectedFields, $row);
                
                foreach ($normalizedRow as $key => $value) {
                    $cleanKey = preg_replace('/[^A-Za-z0-9_]/', '_', $key);
                    $cleanKey = strtoupper($cleanKey);
                    
                    $cleanValue = $this->cleanXmlValue($value);
                    $rowNode->addChild($cleanKey, $cleanValue);
                }
            }
        }
        
        $xmlOutput = $xml->asXML();
        
        // Format the XML nicely
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlOutput);
        $xmlOutput = $dom->saveXML();
        
        error_log("Generated SimpleXML for CHAVE_LOTE $chaveLote: " . substr($xmlOutput, 0, 500) . '...');
        
    } catch (Exception $e) {
        error_log("ajaxGetHistoricoDetailsXML error: " . $e->getMessage());
        
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response></response>');
        $xml->addChild('success', 'false');
        $xml->addChild('e', htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        $xmlOutput = $xml->asXML();
    }
    
    ob_clean();
    echo $xmlOutput;
    exit;
}
?>