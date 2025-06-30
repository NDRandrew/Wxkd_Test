// Métodos corrigidos para PHP 5.3 - substitua no Model

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
 * Mapa de conversão dos campos (compatível PHP 5.3)
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
            'format' => 'Y-m-d'
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
 * Formata um valor de campo conforme a configuração (PHP 5.3)
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
            // Formatar data (PHP 5.3 compatível)
            $value = $this->formatDatePHP53($value, $config);
            break;
            
        case 'text':
            // Remover caracteres especiais e acentos
            $value = $this->cleanTextPHP53($value);
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
 * Formatar data compatível com PHP 5.3
 */
private function formatDatePHP53($dateValue, $config) {
    if (empty($dateValue)) {
        return str_repeat('0', $config['length']);
    }
    
    // Tentar converter usando strtotime (mais compatível)
    $timestamp = strtotime($dateValue);
    
    if ($timestamp === false || $timestamp === -1) {
        // Se strtotime falhar, tentar extrair manualmente
        $timestamp = $this->parseManualDate($dateValue);
    }
    
    if ($timestamp !== false && $timestamp !== -1) {
        // Formatar conforme especificado
        $formatOutput = isset($config['format']) ? $config['format'] : 'Y-m-d';
        return date($formatOutput, $timestamp);
    }
    
    // Se tudo falhar, retornar zeros
    return str_repeat('0', $config['length']);
}

/**
 * Parser manual de data para casos específicos (PHP 5.3)
 */
private function parseManualDate($dateValue) {
    // Remover texto extra como "12:00AM"
    $dateValue = preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?(AM|PM)?/i', '', $dateValue);
    
    // Tentar diferentes padrões
    $patterns = array(
        '/(\w{3})\s+(\d{1,2})\s+(\d{4})/',  // "Nov 21 2024"
        '/(\d{1,2})\/(\d{1,2})\/(\d{4})/',  // "21/11/2024"
        '/(\d{4})-(\d{1,2})-(\d{1,2})/',    // "2024-11-21"
    );
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $dateValue, $matches)) {
            if (count($matches) >= 4) {
                if (strpos($pattern, '\w{3}') !== false) {
                    // Formato "Nov 21 2024"
                    $months = array(
                        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
                        'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
                        'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12
                    );
                    $month = isset($months[strtolower($matches[1])]) ? $months[strtolower($matches[1])] : 1;
                    $day = intval($matches[2]);
                    $year = intval($matches[3]);
                } elseif (strpos($pattern, '(\d{4})') === 0) {
                    // Formato "2024-11-21"
                    $year = intval($matches[1]);
                    $month = intval($matches[2]);
                    $day = intval($matches[3]);
                } else {
                    // Formato "21/11/2024"
                    $day = intval($matches[1]);
                    $month = intval($matches[2]);
                    $year = intval($matches[3]);
                }
                
                return mktime(0, 0, 0, $month, $day, $year);
            }
        }
    }
    
    return false;
}

/**
 * Limpar texto compatível com PHP 5.3
 */
private function cleanTextPHP53($text) {
    // Converter para string
    $text = strval($text);
    
    // Remover acentos de forma manual (mais compatível)
    $acentos = array(
        'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'õ' => 'o', 'ô' => 'o', 'ö' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n',
        'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'Ó' => 'O', 'Ò' => 'O', 'Õ' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'Ç' => 'C', 'Ñ' => 'N'
    );
    
    $text = strtr($text, $acentos);
    
    // Remover caracteres especiais, manter apenas alfanuméricos e espaços
    $text = preg_replace('/[^A-Za-z0-9\s]/', '', $text);
    
    // Normalizar espaços
    $text = preg_replace('/\s+/', ' ', trim($text));
    
    return strtoupper($text);
}