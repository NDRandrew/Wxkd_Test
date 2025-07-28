<?php
/**
 * TXT Generator Class
 * Mass produces TXT lines with fixed-width formatting
 * Compatible with PHP 5.3+
 */
class TxtGenerator {
    
    /**
     * Field mappings for validation and code lookup
     */
    private $transactionCodes = array(
        'abertura' => '01',
        'aberturaNova' => '41',
        'aberturaDigital' => '37',
        'acertoFinanceiro' => '49',
        'acertoFinanceiroQRCODE' => '48',
        'antecipacaoRendaINSS' => '02',
        'cartaoDeCreditoNova' => '44',
        'cartaoPrePagoIn' => '04',
        'cartaoPrePagoDe' => '05',
        'cartaoPrePagoEx' => '06',
        'cartaoPrePagoSal' => '08',
        'cartaoPrePagoSaq' => '09',
        'cartaoPrePagoTr' => '10',
        'cestaDeServicos' => '11',
        'cestaDeManutencao' => '33',
        'cobrancaTitulos' => '12',
        'concessionarias' => '13',
        'consultaSDO' => '47',
        'consultaINSS' => '14',
        'consultaExtrato' => '15',
        'consultaSaldo' => '16',
        'darf' => '17',
        'demonstrativoDeCreditoINSS' => '18',
        'deposito' => '19',
        'depositoIdentificado' => '20',
        'desbloqueioCheques' => '21',
        'emprestimoConsignado' => '34',
        'emprestimoConsignadoINSS' => '45',
        'emprestimoParcelado' => '36',
        'emprestimoPreAprov' => '22',
        'gps' => '23',
        'licenciamentoBahia' => '24',
        'licenciamentoMinas' => '32',
        'limeNova' => '46',
        'microsseguro' => '40',
        'pagueFacil' => '25',
        'pedidoCart' => '31',
        'provaDeVida' => '26',
        'recebimentos' => '35',
        'saqueEmp' => '30',
        'saqueCar' => '27',
        'saqueDin' => '28',
        'saqueRecRet' => '29',
        'saquePix' => '38',
        'seguroDeVida' => '43',
        'transfEntreContas' => '03',
        'tributos' => '07',
        'vendaCartaoNova' => '42',
        'vendaCartao' => '39'
    );
    
    private $paymentMethods = array(
        'dinheiro' => '01',
        'cheque' => '02',
        'cartao' => '03',
        'nao_se_aplica' => '04'
    );
    
    private $paymentStatus = array(
        'ativo' => '1',
        'inativo' => '2'
    );
    
    private $maintenanceTypes = array(
        'inclusao' => '1',
        'alteracao' => '2',
        'exclusao' => '3'
    );
    
    private $errors = array();
    
    /**
     * Generate a single TXT line from data array
     * 
     * @param array $data Associative array with line data
     * @return string|false Formatted TXT line or false on error
     */
    public function generateLine($data) {
        if (!$this->validateLineData($data)) {
            return false;
        }
        
        return $this->formatToTXTLine(
            isset($data['empresa']) ? $data['empresa'] : 0,
            isset($data['codigoLoja']) ? $data['codigoLoja'] : 0,
            isset($data['codTransacao']) ? $this->resolveTransactionCode($data['codTransacao']) : 0,
            isset($data['meioPagamento']) ? $this->resolvePaymentMethod($data['meioPagamento']) : 0,
            isset($data['valorMinimo']) ? $data['valorMinimo'] : 0,
            isset($data['valorMaximo']) ? $data['valorMaximo'] : 0,
            isset($data['situacaoMeioPagamento']) ? $this->resolvePaymentStatus($data['situacaoMeioPagamento']) : 0,
            isset($data['valorTotalMaxDiario']) ? $data['valorTotalMaxDiario'] : 0,
            isset($data['TipoManutencao']) ? $this->resolveMaintenanceType($data['TipoManutencao']) : 0,
            isset($data['quantidadeTotalMaxDiaria']) ? $data['quantidadeTotalMaxDiaria'] : 0
        );
    }
    
    /**
     * Generate multiple TXT lines from array of data
     * 
     * @param array $dataArray Array of associative arrays
     * @return string|false Complete TXT content or false on error
     */
    public function generateLines($dataArray) {
        if (!is_array($dataArray) || empty($dataArray)) {
            $this->addError('Data array is empty or invalid');
            return false;
        }
        
        $txtContent = '';
        $lineNumber = 1;
        
        foreach ($dataArray as $data) {
            $line = $this->generateLine($data);
            if ($line === false) {
                $this->addError("Error in line {$lineNumber}");
                return false;
            }
            $txtContent .= $line . "\r\n";
            $lineNumber++;
        }
        
        return $txtContent;
    }
    
    /**
     * Generate TXT file and save to disk
     * 
     * @param array $dataArray Array of data
     * @param string $filename Output filename
     * @param bool $withBOM Include BOM in file
     * @return bool Success status
     */
    public function generateFile($dataArray, $filename, $withBOM = true) {
        $content = $this->generateLines($dataArray);
        if ($content === false) {
            return false;
        }
        
        if ($withBOM) {
            $content = "\xEF\xBB\xBF" . $content;
        }
        
        $result = file_put_contents($filename, $content);
        if ($result === false) {
            $this->addError("Failed to write file: {$filename}");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate line data
     * 
     * @param array $data Line data
     * @return bool Validation result
     */
    private function validateLineData($data) {
        if (!is_array($data)) {
            $this->addError('Data must be an array');
            return false;
        }
        
        // Validate transaction code if provided
        if (isset($data['codTransacao']) && !is_numeric($data['codTransacao'])) {
            if (!isset($this->transactionCodes[$data['codTransacao']])) {
                $this->addError("Invalid transaction code: {$data['codTransacao']}");
                return false;
            }
        }
        
        // Validate payment method if provided
        if (isset($data['meioPagamento']) && !is_numeric($data['meioPagamento'])) {
            if (!isset($this->paymentMethods[$data['meioPagamento']])) {
                $this->addError("Invalid payment method: {$data['meioPagamento']}");
                return false;
            }
        }
        
        // Validate payment status if provided
        if (isset($data['situacaoMeioPagamento']) && !is_numeric($data['situacaoMeioPagamento'])) {
            if (!isset($this->paymentStatus[$data['situacaoMeioPagamento']])) {
                $this->addError("Invalid payment status: {$data['situacaoMeioPagamento']}");
                return false;
            }
        }
        
        // Validate maintenance type if provided
        if (isset($data['TipoManutencao']) && !is_numeric($data['TipoManutencao'])) {
            if (!isset($this->maintenanceTypes[$data['TipoManutencao']])) {
                $this->addError("Invalid maintenance type: {$data['TipoManutencao']}");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Resolve transaction code (accept both numeric codes and string keys)
     */
    private function resolveTransactionCode($value) {
        if (is_numeric($value)) {
            return intval($value);
        }
        return isset($this->transactionCodes[$value]) ? intval($this->transactionCodes[$value]) : 0;
    }
    
    /**
     * Resolve payment method
     */
    private function resolvePaymentMethod($value) {
        if (is_numeric($value)) {
            return intval($value);
        }
        return isset($this->paymentMethods[$value]) ? intval($this->paymentMethods[$value]) : 0;
    }
    
    /**
     * Resolve payment status
     */
    private function resolvePaymentStatus($value) {
        if (is_numeric($value)) {
            return intval($value);
        }
        return isset($this->paymentStatus[$value]) ? intval($this->paymentStatus[$value]) : 0;
    }
    
    /**
     * Resolve maintenance type
     */
    private function resolveMaintenanceType($value) {
        if (is_numeric($value)) {
            return intval($value);
        }
        return isset($this->maintenanceTypes[$value]) ? intval($this->maintenanceTypes[$value]) : 0;
    }
    
    /**
     * Format data into fixed-width TXT line (101 characters)
     * 
     * @param mixed $empresa Company code
     * @param mixed $codigoLoja Store code
     * @param mixed $codTransacao Transaction code
     * @param mixed $meioPagamento Payment method
     * @param mixed $valorMinimo Minimum value
     * @param mixed $valorMaximo Maximum value
     * @param mixed $situacaoMeioPagamento Payment status
     * @param mixed $valorTotalMaxDiario Max daily total value
     * @param mixed $tipoManutencao Maintenance type
     * @param mixed $quantidadeTotalMaxDiaria Max daily quantity
     * @return string Fixed-width formatted line
     */
    private function formatToTXTLine($empresa, $codigoLoja, $codTransacao, $meioPagamento, $valorMinimo, $valorMaximo, $situacaoMeioPagamento, $valorTotalMaxDiario, $tipoManutencao, $quantidadeTotalMaxDiaria) {
        $empresaTXT = $this->padLeft($this->cleanNumeric($empresa), 10, '0');
        $codigoLojaTXT = $this->padLeft($this->cleanNumeric($codigoLoja), 5, '0');
        $fixo = $this->padRight("", 10, ' ');
        $codTransacaoTXT = $this->padLeft($this->cleanNumeric($codTransacao), 5, '0');
        $meioPagamTXT = $this->padLeft($this->cleanNumeric($meioPagamento), 2, '0');
        $valorMinTXT = $this->padLeft($this->cleanNumeric($valorMinimo), 17, '0');
        $valorMaxTXT = $this->padLeft($this->cleanNumeric($valorMaximo), 17, '0');
        $sitMeioPTXT = $this->padLeft($this->cleanNumeric($situacaoMeioPagamento), 1, '0');
        $valorTotalMaxTXT = $this->padLeft($this->cleanNumeric($valorTotalMaxDiario), 18, '0');
        $tipoManutTXT = $this->padLeft($this->cleanNumeric($tipoManutencao), 1, '0');
        $quantTotalMaxTXT = $this->padLeft($this->cleanNumeric($quantidadeTotalMaxDiaria), 15, '0');

        $linha = $empresaTXT . $codigoLojaTXT . $fixo . $codTransacaoTXT . $meioPagamTXT . 
                $valorMinTXT . $valorMaxTXT . $sitMeioPTXT . $valorTotalMaxTXT . 
                $tipoManutTXT . $quantTotalMaxTXT;

        if (strlen($linha) > 101) {
            return substr($linha, 0, 101);
        } else if (strlen($linha) < 101) {
            return $this->padRight($linha, 101, ' ');
        }
        
        return $linha;
    }
    
    /**
     * Clean numeric value (remove non-numeric characters)
     * 
     * @param mixed $value Input value
     * @return string Cleaned numeric string
     */
    private function cleanNumeric($value) {
        return preg_replace('/[^0-9]/', '', (string)$value) ?: '0';
    }
    
    /**
     * Pad string to the left
     * 
     * @param string $str Input string
     * @param int $length Target length
     * @param string $char Padding character
     * @return string Padded string
     */
    private function padLeft($str, $length, $char) {
        $str = (string)$str;
        while (strlen($str) < $length) {
            $str = $char . $str;
        }
        return strlen($str) > $length ? substr($str, -$length) : $str;
    }
    
    /**
     * Pad string to the right
     * 
     * @param string $str Input string
     * @param int $length Target length
     * @param string $char Padding character
     * @return string Padded string
     */
    private function padRight($str, $length, $char) {
        $str = (string)$str;
        while (strlen($str) < $length) {
            $str = $str . $char;
        }
        return substr($str, 0, $length);
    }
    
    /**
     * Add error message
     * 
     * @param string $message Error message
     */
    private function addError($message) {
        $this->errors[] = $message;
    }
    
    /**
     * Get all errors
     * 
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get last error
     * 
     * @return string|null Last error message
     */
    public function getLastError() {
        return empty($this->errors) ? null : end($this->errors);
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = array();
    }
    
    /**
     * Get available transaction codes
     * 
     * @return array Transaction codes mapping
     */
    public function getTransactionCodes() {
        return $this->transactionCodes;
    }
    
    /**
     * Get available payment methods
     * 
     * @return array Payment methods mapping
     */
    public function getPaymentMethods() {
        return $this->paymentMethods;
    }
    
    /**
     * Get available payment status options
     * 
     * @return array Payment status mapping
     */
    public function getPaymentStatus() {
        return $this->paymentStatus;
    }
    
    /**
     * Get available maintenance types
     * 
     * @return array Maintenance types mapping
     */
    public function getMaintenanceTypes() {
        return $this->maintenanceTypes;
    }
}

// Usage Examples:

/*
// Example 1: Generate single line
$generator = new TxtGenerator();

$lineData = array(
    'empresa' => 123,
    'codigoLoja' => 456,
    'codTransacao' => 'deposito', // or numeric code like 19
    'meioPagamento' => 'dinheiro', // or numeric code like 01
    'valorMinimo' => 1000,
    'valorMaximo' => 5000,
    'situacaoMeioPagamento' => 'ativo', // or numeric code like 1
    'valorTotalMaxDiario' => 10000,
    'TipoManutencao' => 'inclusao', // or numeric code like 1
    'quantidadeTotalMaxDiaria' => 100
);

$line = $generator->generateLine($lineData);
if ($line !== false) {
    echo $line . "\n";
} else {
    echo "Error: " . $generator->getLastError() . "\n";
}

// Example 2: Generate multiple lines
$multipleData = array(
    array(
        'empresa' => 123,
        'codigoLoja' => 456,
        'codTransacao' => 'deposito',
        'meioPagamento' => 'dinheiro',
        'valorMinimo' => 1000,
        'valorMaximo' => 5000,
        'situacaoMeioPagamento' => 'ativo',
        'valorTotalMaxDiario' => 10000,
        'TipoManutencao' => 'inclusao',
        'quantidadeTotalMaxDiaria' => 100
    ),
    array(
        'empresa' => 789,
        'codigoLoja' => 101,
        'codTransacao' => 'saque',
        'meioPagamento' => 'cartao',
        'valorMinimo' => 500,
        'valorMaximo' => 2000,
        'situacaoMeioPagamento' => 'ativo',
        'valorTotalMaxDiario' => 8000,
        'TipoManutencao' => 'alteracao',
        'quantidadeTotalMaxDiaria' => 50
    )
);

$txtContent = $generator->generateLines($multipleData);
if ($txtContent !== false) {
    echo $txtContent;
} else {
    echo "Error: " . $generator->getLastError() . "\n";
}

// Example 3: Generate and save to file
$success = $generator->generateFile($multipleData, 'output.txt');
if ($success) {
    echo "File generated successfully!\n";
} else {
    echo "Error generating file: " . $generator->getLastError() . "\n";
}

// Example 4: Using numeric codes directly
$numericData = array(
    'empresa' => 123,
    'codigoLoja' => 456,
    'codTransacao' => 19, // numeric code for 'deposito'
    'meioPagamento' => 1,  // numeric code for 'dinheiro'
    'valorMinimo' => 1000,
    'valorMaximo' => 5000,
    'situacaoMeioPagamento' => 1, // numeric code for 'ativo'
    'valorTotalMaxDiario' => 10000,
    'TipoManutencao' => 1, // numeric code for 'inclusao'
    'quantidadeTotalMaxDiaria' => 100
);

$line = $generator->generateLine($numericData);
echo $line . "\n";
*/
?>