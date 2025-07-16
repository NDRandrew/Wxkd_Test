<?php
/**
 * WXKD Dashboard Configuration File
 * Contains all constants and configuration parameters
 */

class Wxkd_Config {
    
    // Date configuration
    const CUTOFF_DATE = '20250601';
    const CUTOFF_TIMESTAMP = 1717200000; // mktime(0, 0, 0, 6, 1, 2025)
    
    // Service codes for validation
    const VALID_SERVICE_CODES = array(
        'DP50', 'DP51', 'DP52', 'DP53', 
        'PD50', 'PD51', 'PD52', 'PD53', 
        'RR55', 'RR56', 'RR57', 'RR58', 'RE55',
        'RR50', 'RR51', 'RR52', 'RR53',
        'PECA', 'CPBI', 'HOBI', 'VC23'
    );
    
    // Field mappings for validation
    const FIELD_MAPPINGS = array(
        'AVANCADO' => 'AV',
        'ORGAO_PAGADOR' => 'OP',
        'PRESENCA' => 'PR',
        'UNIDADE_NEGOCIO' => 'UN'
    );
    
    // Validation field names
    const BASIC_VALIDATION_FIELDS = array(
        'DEP_DINHEIRO_VALID' => 'Depósito Dinheiro',
        'DEP_CHEQUE_VALID' => 'Depósito Cheque', 
        'REC_RETIRADA_VALID' => 'Recibo Retirada',
        'SAQUE_CHEQUE_VALID' => 'Saque Cheque'
    );
    
    const OP_VALIDATION_FIELDS = array(
        'DEP_DINHEIRO_VALID' => 'Depósito Dinheiro',
        'DEP_CHEQUE_VALID' => 'Depósito Cheque',
        'REC_RETIRADA_VALID' => 'Recibo Retirada', 
        'SAQUE_CHEQUE_VALID' => 'Saque Cheque',
        'HOLERITE_INSS_VALID' => 'Holerite INSS',
        'CONSULTA_INSS_VALID' => 'Consulta INSS'
    );
    
    // Limit configurations
    const LIMITS_CONFIG = array(
        'dep_dinheiro' => array(
            'presenca' => 'R$ 3.000,00',
            'avancado' => 'R$ 10.000,00'
        ),
        'dep_cheque' => array(
            'presenca' => 'R$ 5.000,00',
            'avancado' => 'R$ 10.000,00'
        ),
        'rec_retirada' => array(
            'presenca' => 'R$ 2.000,00',
            'avancado' => 'R$ 3.500,00'
        ),
        'saque_cheque' => array(
            'presenca' => 'R$ 2.000,00',
            'avancado' => 'R$ 3.500,00'
        )
    );
    
    // Contract version configurations
    const MIN_CONTRACT_VERSION = 8.1;
    const SEGUNDA_VIA_MIN_VERSION = 10.1;
    
    // Export configurations
    const TXT_LINE_LENGTH = 101;
    const CSV_DELIMITER = ';';
    const TXT_ENCODING = 'utf-8';
    
    // Pagination configurations
    const DEFAULT_ITEMS_PER_PAGE = 15;
    const MAX_ITEMS_PER_PAGE = 100;
    
    // Cache configurations (for future implementation)
    const CACHE_ENABLED = false;
    const CACHE_DURATION = 300; // 5 minutes
    
    // Error messages
    const ERROR_MESSAGES = array(
        'NO_DATA' => 'Nenhum dado encontrado',
        'INVALID_FILTER' => 'Filtro inválido',
        'EXPORT_ERROR' => 'Erro durante exportação',
        'VALIDATION_ERROR' => 'Erro de validação',
        'DATABASE_ERROR' => 'Erro de conexão com banco de dados'
    );
    
    // Success messages
    const SUCCESS_MESSAGES = array(
        'EXPORT_SUCCESS' => 'Exportação realizada com sucesso',
        'UPDATE_SUCCESS' => 'Atualização realizada com sucesso'
    );
    
    /**
     * Get service codes as SQL IN clause
     */
    public static function getServiceCodesSQL() {
        return "'" . implode("', '", self::VALID_SERVICE_CODES) . "'";
    }
    
    /**
     * Get cutoff timestamp
     */
    public static function getCutoffTimestamp() {
        return self::CUTOFF_TIMESTAMP;
    }
    
    /**
     * Check if contract version is valid for export
     */
    public static function isValidContractVersion($version) {
        return $version >= self::MIN_CONTRACT_VERSION;
    }
    
    /**
     * Check if segunda via validation is required
     */
    public static function requiresSegundaViaValidation($version) {
        return $version > self::SEGUNDA_VIA_MIN_VERSION;
    }
    
    /**
     * Get field mapping
     */
    public static function getFieldMapping($field) {
        return isset(self::FIELD_MAPPINGS[$field]) ? self::FIELD_MAPPINGS[$field] : null;
    }
    
    /**
     * Get validation fields for type
     */
    public static function getValidationFields($type) {
        switch($type) {
            case 'basic':
                return self::BASIC_VALIDATION_FIELDS;
            case 'op':
                return self::OP_VALIDATION_FIELDS;
            default:
                return array();
        }
    }
    
    /**
     * Get limits configuration
     */
    public static function getLimitsConfig($field = null) {
        if ($field) {
            return isset(self::LIMITS_CONFIG[$field]) ? self::LIMITS_CONFIG[$field] : null;
        }
        return self::LIMITS_CONFIG;
    }
    
    /**
     * Get error message
     */
    public static function getErrorMessage($key) {
        return isset(self::ERROR_MESSAGES[$key]) ? self::ERROR_MESSAGES[$key] : 'Erro desconhecido';
    }
    
    /**
     * Get success message
     */
    public static function getSuccessMessage($key) {
        return isset(self::SUCCESS_MESSAGES[$key]) ? self::SUCCESS_MESSAGES[$key] : 'Operação realizada com sucesso';
    }
}
?>