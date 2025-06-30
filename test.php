// Métodos do Model 100% compatíveis com PHP 5.3

/**
 * Gera conteúdo TXT específico (PHP 5.3 seguro)
 */
public function generateSpecificTXT($tableData) {
    error_log("generateSpecificTXT called with " . count($tableData) . " records");
    
    try {
        // CORREÇÃO PHP 5.3: Verificar dados sem usar empty() em função
        $hasData = (is_array($tableData) && count($tableData) > 0);
        
        if (!$hasData) {
            error_log("generateSpecificTXT - No data provided");
            return '';
        }
        
        $txtContent = '';
        $lineCount = 0;
        
        // Processar cada linha dos dados
        foreach ($tableData as $row) {
            // CORREÇÃO PHP 5.3: Atribuir resultado a variável
            $formattedLine = $this->converRowToSpecificFormat($row);
            $lineIsEmpty = empty($formattedLine);
            
            if (!$lineIsEmpty) {
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
 * Converte uma linha de dados (PHP 5.3 seguro)
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
        $currentLength = strlen($formattedLine);
        
        if ($currentLength > $targetLength) {
            $formattedLine = substr($formattedLine, 0, $targetLength);
        } elseif ($currentLength < $targetLength) {
            $formattedLine = str_pad($formattedLine, $targetLength, ' ');
        }
        
        return $formattedLine;
        
    } catch (Exception $e) {
        error_log("converRowToSpecificFormat - Exception: " . $e->getMessage());
        return '';
    }
}

/**
 * Formata valor do campo (PHP 5.3 seguro)
 */
private function formatFieldValue($value, $config) {
    $type = isset($config['type']) ? $config['type'] : 'text';
    $length = isset($config['length']) ? $config['length'] : 10;
    $padChar = isset($config['pad_char']) ? $config['pad_char'] : ' ';
    $align = isset($config['align']) ? $config['align'] : 'left';
    
    // Converter valor para string
    $value = strval($value);
    
    // Aplicar formatação específica por tipo
    if ($type === 'numeric') {
        // Apenas números
        $value = preg_replace('/[^0-9]/', '', $value);
        $valueIsEmpty = empty($value);
        if ($valueIsEmpty) $value = '0';
    } elseif ($type === 'date') {
        // Data simples
        $value = $this->formatDatePHP53($value, $config);
    } else {
        // Texto limpo
        $value = $this->cleanTextPHP53($value);
    }
    
    // Truncar se necessário
    $currentLength = strlen($value);
    if ($currentLength > $length) {
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
 * Formatar data PHP 5.3 (ultra-simples)
 */
private function formatDatePHP53($dateValue, $config) {
    $valueIsEmpty = empty($dateValue);
    if ($valueIsEmpty) {
        return '0000-00-00';
    }
    
    // Apenas tentar strtotime
    $timestamp = strtotime($dateValue);
    
    if ($timestamp && $timestamp > 0) {
        return date('Y-m-d', $timestamp);
    }
    
    // Se falhar, tentar extrair ano da string
    if (preg_match('/(\d{4})/', $dateValue, $matches)) {
        $year = $matches[1];
        return $year . '-01-01'; // Data padrão
    }
    
    return '0000-00-00';
}

/**
 * Limpar texto PHP 5.3 (ultra-simples)
 */
private function cleanTextPHP53($text) {
    // Converter para string
    $text = strval($text);
    
    // Apenas remover caracteres especiais básicos
    $text = preg_replace('/[^A-Za-z0-9\s]/', '', $text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    return strtoupper($text);
}

/**
 * Mapa de conversão (igual anterior, mas comentado para clareza)
 */
private function getConversionMap() {
    // Array simples sem sintaxe moderna
    $map = array();
    
    $map['CHAVE_LOJA'] = array(
        'type' => 'numeric',
        'length' => 10,
        'position' => 1,
        'pad_char' => '0',
        'align' => 'right'
    );
    
    $map['COD_EMPRESA'] = array(
        'type' => 'numeric', 
        'length' => 8,
        'position' => 2,
        'pad_char' => '0',
        'align' => 'right'
    );
    
    $map['TIPO'] = array(
        'type' => 'text',
        'length' => 20,
        'position' => 3,
        'pad_char' => ' ',
        'align' => 'left'
    );
    
    $map['DATA_CONTRATO'] = array(
        'type' => 'date',
        'length' => 10,
        'position' => 4,
        'pad_char' => '0',
        'align' => 'left'
    );
    
    $map['TIPO_CONTRATO'] = array(
        'type' => 'text',
        'length' => 25,
        'position' => 5,
        'pad_char' => ' ',
        'align' => 'left'
    );
    
    $map['STATUS'] = array(
        'type' => 'text',
        'length' => 10,
        'position' => 6,
        'pad_char' => ' ',
        'align' => 'left'
    );
    
    $map['OBSERVACOES'] = array(
        'type' => 'text',
        'length' => 34,
        'position' => 7,
        'pad_char' => ' ',
        'align' => 'left'
    );
    
    return $map;
}