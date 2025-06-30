// Métodos no Model (models/Wxkd_DashboardModel.php)

/**
 * Gera conteúdo TXT específico a partir dos dados da tabela
 */
public function generateSpecificTXT($tableData) {
    error_log("generateSpecificTXT called with " . count($tableData) . " records");
    
    try {
        if (empty($tableData)) {
            error_log("generateSpecificTXT - No data provided");
            return '';
        }
        
        $txtContent = '';
        $lineCount = 0;
        
        // Processar cada linha dos dados
        foreach ($tableData as $row) {
            $formattedLine = $this->converRowToSpecificFormat($row);
            
            if (!empty($formattedLine)) {
                $txtContent .= $formattedLine . "\r\n";
                $lineCount++;
            }
        }
        
        error_log("generateSpecificTXT - Generated $lineCount lines");
        error_log("generateSpecificTXT - Content length: " . strlen($txtContent));
        
        return $txtContent;
        
    } catch (Exception $e) {
        error_log("generateSpecificTXT - Exception: " . $e->getMessage());
        return '';
    }
}

/**
 * Converte uma linha de dados para o formato específico
 */
public function converRowToSpecificFormat($row) {
    try {
        // Obter mapa de conversão
        $conversionMap = $this->getConversionMap();
        
        $formattedLine = '';
        
        // Processar cada campo conforme o mapa de conversão
        foreach ($conversionMap as $fieldName => $config) {
            $value = isset($row[$fieldName]) ? $row[$fieldName] : '';
            $formattedValue = $this->formatFieldValue($value, $config);
            $formattedLine .= $formattedValue;
        }
        
        // Garantir que a linha tenha o tamanho correto (117 caracteres)
        $targetLength = 117;
        if (strlen($formattedLine) > $targetLength) {
            $formattedLine = substr($formattedLine, 0, $targetLength);
        } elseif (strlen($formattedLine) < $targetLength) {
            $formattedLine = str_pad($formattedLine, $targetLength, ' ');
        }
        
        return $formattedLine;
        
    } catch (Exception $e) {
        error_log("converRowToSpecificFormat - Exception: " . $e->getMessage());
        return '';
    }
}

/**
 * Mapa de conversão dos campos
 */
private function getConversionMap() {
    return array(
        'CHAVE_LOJA' => array(
            'type' => 'numeric',
            'length' => 10,
            'position' => 1,
            'pad_char' => '0',
            'align' => 'right'
        ),
        'COD_EMPRESA' => array(
            'type' => 'numeric', 
            'length' => 8,
            'position' => 2,
            'pad_char' => '0',
            'align' => 'right'
        ),
        'TIPO' => array(
            'type' => 'text',
            'length' => 20,
            'position' => 3,
            'pad_char' => ' ',
            'align' => 'left'
        ),
        'DATA_CONTRATO' => array(
            'type' => 'date',
            'length' => 10,
            'position' => 4,
            'pad_char' => '0',
            'align' => 'left',
            'format' => 'YYYY-MM-DD'
        ),
        'TIPO_CONTRATO' => array(
            'type' => 'text',
            'length' => 25,
            'position' => 5,
            'pad_char' => ' ',
            'align' => 'left'
        ),
        'STATUS' => array(
            'type' => 'text',
            'length' => 10,
            'position' => 6,
            'pad_char' => ' ',
            'align' => 'left'
        ),
        'OBSERVACOES' => array(
            'type' => 'text',
            'length' => 34,
            'position' => 7,
            'pad_char' => ' ',
            'align' => 'left'
        )
    );
}

/**
 * Formata um valor de campo conforme a configuração
 */
private function formatFieldValue($value, $config) {
    $type = isset($config['type']) ? $config['type'] : 'text';
    $length = isset($config['length']) ? $config['length'] : 10;
    $padChar = isset($config['pad_char']) ? $config['pad_char'] : ' ';
    $align = isset($config['align']) ? $config['align'] : 'left';
    
    // Converter valor para string
    $value = strval($value);
    
    // Aplicar formatação específica por tipo
    switch ($type) {
        case 'numeric':
            // Remover caracteres não numéricos
            $value = preg_replace('/[^0-9]/', '', $value);
            if (empty($value)) $value = '0';
            break;
            
        case 'date':
            // Formatar data
            $value = $this->formatDate($value, $config);
            break;
            
        case 'text':
            // Remover caracteres especiais e acentos
            $value = $this->cleanText($value);
            break;
    }
    
    // Truncar se necessário
    if (strlen($value) > $length) {
        $value = substr($value, 0, $length);
    }
    
    // Aplicar padding
    if ($align === 'right') {
        $value = str_pad($value, $length, $padChar, STR_PAD_LEFT);
    } else {
        $value = str_pad($value, $length, $padChar, STR_PAD_RIGHT);
    }
    
    return $value;
}

/**
 * Formatar data
 */
private function formatDate($dateValue, $config) {
    if (empty($dateValue)) {
        return str_repeat('0', $config['length']);
    }
    
    // Tentar extrair data de diferentes formatos
    $formats = array('Y-m-d', 'd/m/Y', 'M d Y', 'Y-m-d H:i:s');
    $timestamp = false;
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateValue);
        if ($date !== false) {
            $timestamp = $date->getTimestamp();
            break;
        }
    }
    
    if ($timestamp === false) {
        // Se não conseguir parsear, tentar strtotime
        $timestamp = strtotime($dateValue);
    }
    
    if ($timestamp !== false) {
        // Formatar conforme especificado
        $formatOutput = isset($config['format']) ? $config['format'] : 'Y-m-d';
        $formatOutput = str_replace(array('YYYY', 'MM', 'DD'), array('Y', 'm', 'd'), $formatOutput);
        return date($formatOutput, $timestamp);
    }
    
    return str_repeat('0', $config['length']);
}

/**
 * Limpar texto removendo acentos e caracteres especiais
 */
private function cleanText($text) {
    // Remover acentos
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    
    // Remover caracteres especiais, manter apenas alfanuméricos e espaços
    $text = preg_replace('/[^A-Za-z0-9\s]/', '', $text);
    
    // Normalizar espaços
    $text = preg_replace('/\s+/', ' ', trim($text));
    
    return strtoupper($text);
}