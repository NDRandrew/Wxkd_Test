<?php
require_once '../control/Wxkd_Config.php';
require_once '../model/Wxkd_DashboardModel.php';

class Wxkd_DashboardController {
    private $model;
    
    public function __construct() {
        $this->model = new Wxkd_DashboardModel();
        $this->model->Wxkd_Construct(); 
    }
    
    public function index() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        $cardData = $this->model->getCardData();
        $tableData = $this->model->getTableDataByFilter($filter);
        $contractData = $this->model->contractDateCheck();
        
        $contractChaves = array();
        foreach ($contractData as $item) {
            $contractChaves[] = $item['Chave_Loja'];
        }
        $contractChavesLookup = array_flip($contractChaves);

        return array(
            'cardData' => $cardData, 
            'tableData' => $tableData, 
            'activeFilter' => $filter, 
            'contractChavesLookup' => $contractChavesLookup
        );
    }
    
    public function ajaxGetTableData() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        try {
            $tableData = $this->model->getTableDataByFilter($filter);
            $cardData = $this->model->getCardData();
            
            $xml = '<response>';
            $xml .= '<success>true</success>';
            
            if (is_array($cardData)) {
                $xml .= '<cardData>';
                foreach ($cardData as $key => $value) {
                    $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
                }
                $xml .= '</cardData>';
            }
            
            $xml .= '<tableData>';
            if (is_array($tableData) && count($tableData) > 0) {
                foreach ($tableData as $row) {
                    $xml .= '<row>';
                    foreach ($row as $key => $value) {
                        $xml .= '<' . $key . '>' . addcslashes($value, '"<>&') . '</' . $key . '>';
                    }
                    $xml .= '</row>';
                }
            }
            $xml .= '</tableData>';
            $xml .= '</response>';
            
        } catch (Exception $e) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<error>' . addcslashes($e->getMessage(), '"<>&') . '</error>';
            $xml .= '</response>';
        }

        echo $xml;
        exit;
    }
    
    public function exportCSV() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';
        
        $data = $this->getFilteredData($filter, $selectedIds);
        
        if (empty($data)) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>Nenhum dado encontrado para exportação</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
        
        $xml = '<response><success>true</success><csvData>';
        
        foreach ($data as $row) {
            $xml .= '<row>';
            $xml .= '<chave_loja>' . addcslashes(isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '', '"<>&') . '</chave_loja>';
            $nome_loja = isset($row['Nome_Loja']) ? $row['Nome_Loja'] : '';
            $xml .= '<nome_loja><![CDATA[' . $nome_loja . ']]></nome_loja>';
            $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '', '"<>&') . '</cod_empresa>';
            $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : '', '"<>&') . '</cod_loja>';
            $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
            $xml .= '<tipo_correspondente>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</tipo_correspondente>';
            $xml .= '<data_conclusao>' . addcslashes(isset($row['DATA_CONCLUSAO']) ? $row['DATA_CONCLUSAO'] : '', '"<>&') . '</data_conclusao>';
            
            $this->addDateFieldsToXML($xml, $row);
            
            $xml .= '<data_solicitacao>';
            $timeAndre = isset($row['DATA_SOLICITACAO']) ? strtotime($row['DATA_SOLICITACAO']) : false;
            if ($timeAndre !== false && !empty($row['DATA_SOLICITACAO'])) {
                $xml .= date('d/m/Y', $timeAndre);
            } else {
                $fields = Wxkd_Config::getFieldMappings();
                $cutoff = Wxkd_Config::getCutoffTimestamp();
                $matchingDates = array();

                foreach ($fields as $field => $label) {
                    $raw = isset($row[$field]) ? trim($row[$field]) : '';

                    if (!empty($raw)) {
                        $parts = explode('/', $raw);
                        if (count($parts) === 3) {
                            $day = (int)$parts[0];
                            $month = (int)$parts[1];
                            $year = (int)$parts[2];

                            if (checkdate($month, $day, $year)) {
                                $timestamp = mktime(0, 0, 0, $month, $day, $year);
                                if ($timestamp > $cutoff) {
                                    $matchingDates[] = $raw;
                                }
                            }
                        }
                    }
                }

                $xml .= !empty($matchingDates) ? implode(' / ', $matchingDates) : '—';
            }
            $xml .= '</data_solicitacao>';
            
            $this->addValidationFieldsToXML($xml, $row);
            
            $xml .= '</row>';
        }
        
        $xml .= '</csvData></response>';
        echo $xml;
        exit;
    }
    
    public function exportTXT() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

        try {
            $data = $this->getFilteredData($filter, $selectedIds);
            
            if (empty($data)) {
                $xml = '<response>';
                $xml .= '<success>false</success>';
                $xml .= '<e>Nenhum dado encontrado para exportação</e>';
                $xml .= '</response>';
                echo $xml;
                exit;
            }
            
            $invalidRecords = $this->validateRecordsForTXTExport($data);
            
            if (!empty($invalidRecords)) {
                $this->outputValidationError($invalidRecords);
                return;
            }
            
            $xml = '<response><success>true</success><txtData>';
            
            $recordsToUpdate = array();
            
            foreach ($data as $row) {
                $xml .= '<row>';
                $xml .= '<cod_empresa>' . addcslashes(isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '', '"<>&') . '</cod_empresa>';
                $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
                $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : '', '"<>&') . '</cod_loja>';
                $xml .= '<TIPO_CORRESPONDENTE>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</TIPO_CORRESPONDENTE>';
                $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
                $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
                $xml .= '</row>';
                
                $codEmpresa = (int) (isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : 0);
                $codLoja = (int) (isset($row['Cod_Loja']) ? $row['Cod_Loja'] : 0);
                
                if ($codEmpresa > 0 && $codLoja > 0) {
                    $recordsToUpdate[] = array(
                        'COD_EMPRESA' => $codEmpresa,
                        'COD_LOJA' => $codLoja
                    );
                }
            }
            
            if (!empty($recordsToUpdate)) {
                $updateResult = $this->model->updateWxkdFlag($recordsToUpdate);
                $xml .= '<flagUpdate>';
                $xml .= '<success>' . ($updateResult ? 'true' : 'false') . '</success>';
                $xml .= '<recordsUpdated>' . count($recordsToUpdate) . '</recordsUpdated>';
                $xml .= '</flagUpdate>';
            }
            
            $xml .= '</txtData></response>';
            echo $xml;
            exit;
            
        } catch (Exception $e) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>' . addcslashes($e->getMessage(), '"<>&') . '</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
    }
    
    public function exportAccess() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

        try {
            $data = $this->getFilteredData($filter, $selectedIds);
            
            if (empty($data)) {
                $xml = '<response>';
                $xml .= '<success>false</success>';
                $xml .= '<e>Nenhum dado encontrado para exportação</e>';
                $xml .= '</response>';
                echo $xml;
                exit;
            }
            
            $cutoff = Wxkd_Config::getCutoffTimestamp();
            
            $avUnPrData = array();
            $opData = array();
            
            foreach ($data as $row) {
                $activeTypes = $this->getActiveTypes($row, $cutoff);
                
                $hasOP = in_array('OP', $activeTypes);
                $hasOthers = in_array('AV', $activeTypes) || in_array('PR', $activeTypes) || in_array('UN', $activeTypes);
                
                $codEmpresa = isset($row['Cod_Empresa']) ? $row['Cod_Empresa'] : '';
                
                if (!empty($codEmpresa)) {
                    if ($hasOP) {
                        $opData[] = $codEmpresa;
                    }
                    if ($hasOthers) {
                        $avUnPrData[] = $codEmpresa;
                    }
                }
            }
            
            $avUnPrData = array_unique($avUnPrData);
            $opData = array_unique($opData);
            
            $xml = '<response><success>true</success>';
            
            $xml .= '<avUnPrData>';
            foreach ($avUnPrData as $codEmpresa) {
                $xml .= '<empresa>' . addcslashes($codEmpresa, '"<>&') . '</empresa>';
            }
            $xml .= '</avUnPrData>';
            
            if (!empty($opData)) {
                $xml .= '<opData>';
                foreach ($opData as $codEmpresa) {
                    $xml .= '<empresa>' . addcslashes($codEmpresa, '"<>&') . '</empresa>';
                }
                $xml .= '</opData>';
            }
            
            $xml .= '</response>';
            echo $xml;
            exit;
            
        } catch (Exception $e) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>' . addcslashes($e->getMessage(), '"<>&') . '</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
    }
    
    public function getValidationError($row) {
        $cutoff = Wxkd_Config::getCutoffTimestamp();
        $activeTypes = $this->getActiveTypes($row, $cutoff);
        
        foreach ($activeTypes as $type) {
            if ($type === 'AV' || $type === 'PR' || $type === 'UN') {
                $basicValidation = $this->checkBasicValidations($row);
                if ($basicValidation !== true) {
                    return 'Tipo ' . $type . ' - ' . str_replace(array('ç', 'õ', 'ã'), array('c', 'o', 'a'), $basicValidation);
                }
            } elseif ($type === 'OP') {
                $opValidation = $this->checkOPValidations($row);
                if ($opValidation !== true) {
                    return str_replace(array('ç', 'õ', 'ã'), array('c', 'o', 'a'), $opValidation);
                }
            }
        }
        
        return null; 
    }
    
    // Helper methods
    private function getFilteredData($filter, $selectedIds) {
        if (!empty($selectedIds)) {
            $allData = $this->model->getTableDataByFilter($filter);
            $idsArray = explode(',', $selectedIds);
            $cleanIds = array();
            
            foreach ($idsArray as $id) {
                $cleanId = trim($id);
                $cleanId = preg_replace('/\s+/', '', $cleanId);
                if (!empty($cleanId) && is_numeric($cleanId)) {
                    $cleanIds[] = intval($cleanId); 
                }
            }
            
            $data = array();
            foreach ($cleanIds as $sequentialId) {
                $arrayIndex = $sequentialId - 1; 
                
                if (isset($allData[$arrayIndex])) {
                    $data[] = $allData[$arrayIndex];
                }
            }
            return $data;
        } else {
            return $this->model->getTableDataByFilter($filter);
        }
    }
    
    private function getActiveTypes($row, $cutoff) {
        $activeTypes = array();
        $fields = Wxkd_Config::getFieldMappings();
        
        foreach ($fields as $field => $label) {
            $raw = isset($row[$field]) ? trim($row[$field]) : '';
            if (!empty($raw)) {
                $parts = explode('/', $raw);
                if (count($parts) == 3) {
                    $day = (int)$parts[0];
                    $month = (int)$parts[1];
                    $year = (int)$parts[2];
                    if (checkdate($month, $day, $year)) {
                        $timestamp = mktime(0, 0, 0, $month, $day, $year);
                        if ($timestamp > $cutoff) {
                            $activeTypes[] = $label;
                        }
                    }
                }
            }
        }
        
        return $activeTypes;
    }
    
    private function getEmpresaCode($row) {
        $possibleEmpresaFields = array('COD_EMPRESA', 'cod_empresa', 'Cod_Empresa', 'CODEMPRESA', 'cod_emp');
        foreach ($possibleEmpresaFields as $field) {
            if (isset($row[$field]) && !empty($row[$field])) {
                return $row[$field];
            }
        }
        return '';
    }
    
    private function addDateFieldsToXML(&$xml, $row) {
        $fields = Wxkd_Config::getFieldMappings();
        $cutoff = Wxkd_Config::getCutoffTimestamp();

        foreach ($fields as $field => $label) {
            $raw = isset($row[$field]) ? trim($row[$field]) : '';

            if (!empty($raw)) {
                $parts = explode('/', $raw);
                if (count($parts) == 3) {
                    $day = (int)$parts[0];
                    $month = (int)$parts[1];
                    $year = (int)$parts[2];

                    if (checkdate($month, $day, $year)) {
                        $timestamp = mktime(0, 0, 0, $month, $day, $year);
                        if ($timestamp > $cutoff) {
                            $formattedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                            $xml .= '<date>' . htmlspecialchars($formattedDate) . '</date>';
                        }
                    }
                }
            }
        }
    }
    
    private function addStandardFieldsToXML(&$xml, $row) {
        $xml .= '<cod_loja>' . addcslashes(isset($row['Cod_Loja']) ? $row['Cod_Loja'] : '', '"<>&') . '</cod_loja>';
        $xml .= '<quant_lojas>' . addcslashes(isset($row['QUANT_LOJAS']) ? $row['QUANT_LOJAS'] : '', '"<>&') . '</quant_lojas>';
        $xml .= '<tipo_correspondente>' . addcslashes(isset($row['TIPO_CORRESPONDENTE']) ? $row['TIPO_CORRESPONDENTE'] : '', '"<>&') . '</tipo_correspondente>';
        $xml .= '<data_conclusao>' . addcslashes(isset($row['DATA_CONCLUSAO']) ? $row['DATA_CONCLUSAO'] : '', '"<>&') . '</data_conclusao>';
        
        $xml .= '<data_solicitacao>';
        $timeAndre = strtotime($row['DATA_SOLICITACAO']);
        if ($timeAndre !== false && !empty($row['DATA_SOLICITACAO'])) {
            $xml .= date('d/m/Y', $timeAndre);
        } else {
            $xml .= '—';
        }
        $xml .= '</data_solicitacao>';
    }
    
    private function addValidationFieldsToXML(&$xml, $row) {
        $validationFields = array(
            'dep_dinheiro' => 'DEP_DINHEIRO_VALID',
            'dep_cheque' => 'DEP_CHEQUE_VALID',
            'rec_retirada' => 'REC_RETIRADA_VALID',
            'saque_cheque' => 'SAQUE_CHEQUE_VALID'
        );
        
        foreach ($validationFields as $xmlField => $validField) {
            $xml .= '<' . $xmlField . '>';
            if (isset($row[$validField]) && isset($row['TIPO_LIMITES'])) {
                $isPresencaOrOrgao = (strpos($row['TIPO_LIMITES'], 'PRESENCA') !== false || 
                                strpos($row['TIPO_LIMITES'], 'ORG_PAGADOR') !== false);
                $isAvancadoOrApoio = (strpos($row['TIPO_LIMITES'], 'AVANCADO') !== false || 
                                strpos($row['TIPO_LIMITES'], 'UNIDADE_NEGOCIO') !== false);
                
                $limits = $this->getLimitsForField($xmlField, $isPresencaOrOrgao, $isAvancadoOrApoio);
                
                if ($row[$validField] == 1) {
                    $xml .= $limits['valid'];
                } else {
                    $xml .= $limits['invalid'];
                }
            } else {
                $xml .= 'Tipo não definido';
            }
            $xml .= '</' . $xmlField . '>';
        }
        
        // Special fields
        $xml .= '<segunda_via_cartao>';
        if (isset($row['SEGUNDA_VIA_CARTAO_VALID'])) {
            $xml .= ($row['SEGUNDA_VIA_CARTAO_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
        $xml .= '</segunda_via_cartao>';
        
        $xml .= '<holerite_inss>';
        if (isset($row['HOLERITE_INSS_VALID'])) {
            $xml .= ($row['HOLERITE_INSS_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
        $xml .= '</holerite_inss>';
        
        $xml .= '<cons_inss>';
        if (isset($row['CONSULTA_INSS_VALID'])) {
            $xml .= ($row['CONSULTA_INSS_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
        $xml .= '</cons_inss>';
        
        $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
        $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
    }
    
    private function getLimitsForField($field, $isPresencaOrOrgao, $isAvancadoOrApoio) {
        $limits = Wxkd_Config::getLimitsConfig($field);
    private function getLimitsForField($field, $isPresencaOrOrgao, $isAvancadoOrApoio) {
        $limits = Wxkd_Config::getLimitsConfig($field);
        
        if (!$limits) {
            return array('valid' => '0', 'invalid' => '0*');
        }
        
        if ($isPresencaOrOrgao) {
            return array(
                'valid' => $limits['presenca'],
                'invalid' => $limits['presenca'] . '*'
            );
        } elseif ($isAvancadoOrApoio) {
            return array(
                'valid' => $limits['avancado'],
                'invalid' => $limits['avancado'] . '*'
            );
        } else {
            return array(
                'valid' => '0',
                'invalid' => '0*'
            );
        }
    }
    
    private function validateRecordsForTXTExport($data) {
        $invalidRecords = array();
        $cutoff = Wxkd_Config::getCutoffTimestamp();
        
        foreach ($data as $row) {
            $activeTypes = $this->getActiveTypes($row, $cutoff);
            $codEmpresa = $this->getEmpresaCode($row);
            
            if (empty($codEmpresa) || empty($activeTypes)) {
                continue;
            }
            
            $isValid = true;
            $errorMessage = '';
            
            foreach ($activeTypes as $type) {
                if ($type === 'AV' || $type === 'PR' || $type === 'UN') {
                    $basicValidation = $this->checkBasicValidations($row);
                    if ($basicValidation !== true) {
                        $isValid = false;
                        $errorMessage = 'Tipo ' . $type . ' - ' . $basicValidation;
                        break;
                    }
                } elseif ($type === 'OP') {
                    $opValidation = $this->checkOPValidations($row);
                    if ($opValidation !== true) {
                        $isValid = false;
                        $errorMessage = $opValidation;
                        break;
                    }
                }
            }
            
            if (!$isValid) {
                $invalidRecords[] = array(
                    'cod_empresa' => $codEmpresa,
                    'error' => $errorMessage
                );
            }
        }
        
        return $invalidRecords;
    }
    
    private function checkBasicValidations($row) {
        $requiredFields = Wxkd_Config::getValidationFields('basic');
        
        $missingFields = array();
        foreach ($requiredFields as $field => $name) {
            if (!isset($row[$field]) || $row[$field] != '1') {
                $missingFields[] = $name;
            }
        }
        
        if (!empty($missingFields)) {
            return 'Validações pendentes: ' . implode(', ', $missingFields);
        }
        
        return true;
    }

    private function checkOPValidations($row) {
        $tipoContrato = isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '';
        $version = $this->extractVersionFromContract($tipoContrato);
        
        if ($version === null) {
            return 'Tipo de contrato não pode ser exportado: ' . $tipoContrato;
        }
        
        $requiredFields = Wxkd_Config::getValidationFields('op');
        
        if (Wxkd_Config::requiresSegundaViaValidation($version)) {
            $requiredFields['SEGUNDA_VIA_CARTAO_VALID'] = 'Segunda Via Cartão';
        }
        
        $missingFields = array();
        foreach ($requiredFields as $field => $name) {
            if (!isset($row[$field]) || $row[$field] != '1') {
                $missingFields[] = $name;
            }
        }
        
        if (!empty($missingFields)) {
            return 'Validações OP pendentes (v' . $version . '): ' . implode(', ', $missingFields);
        }
        
        return true;
    }

    private function extractVersionFromContract($tipoContrato) {
        if (preg_match('/(\d+\.\d+)/', $tipoContrato, $matches)) {
            $version = (float)$matches[1];
            if (Wxkd_Config::isValidContractVersion($version)) {
                return $version;
            }
        }
        return null;
    }
    
    private function outputValidationError($invalidRecords) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<validation_error>true</validation_error>';
        $xml .= '<invalid_records>';
        foreach ($invalidRecords as $record) {
            $xml .= '<record>';
            $xml .= '<cod_empresa>' . addcslashes($record['cod_empresa'], '"<>&') . '</cod_empresa>';
            $xml .= '<error>' . addcslashes($record['error'], '"<>&') . '</error>';
            $xml .= '<error_msg>' . str_replace(array('ç','õ','ã'),array('c','o','a'), $record['error']) . '</error_msg>';
            $xml .= '</record>';
        }
        $xml .= '</invalid_records>';
        $xml .= '<message>Alguns registros não atendem aos critérios para exportação TXT. Use o botão Exportar Access.</message>';
        $xml .= '</response>';
        
        echo $xml;
        exit;
    }
}
?>