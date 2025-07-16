<?php
// Add these methods to your Wxkd_DashboardController class

private function validateRecordsForTXTExport($data) {
	$invalidRecords = array();
	$cutoff = mktime(0, 0, 0, 6, 1, 2025);
	
	foreach ($data as $row) {
		// Determine active TIPO_CORRESPONDENTE types
		$activeTypes = array();
		$fields = array(
			'AVANCADO' => 'AV',
			'ORGAO_PAGADOR' => 'OP',
			'PRESENCA' => 'PR',
			'UNIDADE_NEGOCIO' => 'UN'
		);
		
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
		
		// Get cod_empresa for error reporting
		$possibleEmpresaFields = array('COD_EMPRESA', 'cod_empresa', 'Cod_Empresa', 'CODEMPRESA', 'cod_emp');
		$codEmpresa = '';
		foreach ($possibleEmpresaFields as $field) {
			if (isset($row[$field]) && !empty($row[$field])) {
				$codEmpresa = $row[$field];
				break;
			}
		}
		
		// Skip if no cod_empresa found
		if (empty($codEmpresa)) {
			continue;
		}
		
		// Skip if no active types
		if (empty($activeTypes)) {
			continue;
		}
		
		// Validate each active type
		$isValid = true;
		$errorMessage = '';
		
		foreach ($activeTypes as $type) {
			error_log("Validating type: " . $type . " for empresa: " . $codEmpresa);
			
			if ($type === 'AV' || $type === 'PR' || $type === 'UN') {
				$basicValidation = $this->checkBasicValidations($row);
				error_log("Basic validation result for " . $type . ": " . ($basicValidation === true ? 'SUCCESS' : $basicValidation));
				
				if ($basicValidation !== true) {
					$isValid = false;
					$errorMessage = 'Tipo ' . $type . ' - ' . $basicValidation;
					break;
				}
			} elseif ($type === 'OP') {
				$opValidation = $this->checkOPValidations($row);
				error_log("OP validation result: " . ($opValidation === true ? 'SUCCESS' : $opValidation));
				
				if ($opValidation !== true) {
					$isValid = false;
					$errorMessage = $opValidation;
					break;
				}
			}
		}
		
		if (!$isValid) {
			error_log("Adding invalid record: " . $codEmpresa . " - " . $errorMessage);
			$invalidRecords[] = array(
				'cod_empresa' => $codEmpresa,
				'error' => $errorMessage
			);
		}
	}
	
	return $invalidRecords;
}

private function checkBasicValidations($row) {
	$requiredFields = array(
		'DEP_DINHEIRO_VALID' => 'Depósito Dinheiro',
		'DEP_CHEQUE_VALID' => 'Depósito Cheque', 
		'REC_RETIRADA_VALID' => 'Recarga/Retirada',
		'SAQUE_CHEQUE_VALID' => 'Saque Cheque'
	);
	
	$missingFields = array();
	foreach ($requiredFields as $field => $name) {
		if (!isset($row[$field]) || $row[$field] != '1') {
			$missingFields[] = $name;
			error_log("Basic validation failed for field: " . $field . " with value: " . (isset($row[$field]) ? $row[$field] : 'NULL'));
		}
	}
	
	if (!empty($missingFields)) {
		return 'Validações pendentes: ' . implode(', ', $missingFields);
	}
	
	return true;
}

private function checkOPValidations($row) {
	$tipoContrato = isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '';
	
	// Extract version number from TIPO_CONTRATO
	$version = $this->extractVersionFromContract($tipoContrato);
	
	if ($version === null) {
		return 'Tipo de contrato não pode ser exportado: ' . $tipoContrato;
	}
	
	$requiredFields = array(
		'DEP_DINHEIRO_VALID' => 'Depósito Dinheiro',
		'DEP_CHEQUE_VALID' => 'Depósito Cheque',
		'REC_RETIRADA_VALID' => 'Recarga/Retirada', 
		'SAQUE_CHEQUE_VALID' => 'Saque Cheque',
		'HOLERITE_INSS_VALID' => 'Holerite INSS',
		'CONSULTA_INSS_VALID' => 'Consulta INSS'
	);
	
	if ($version > 10.1) {
		$requiredFields['SEGUNDA_VIA_CARTAO_VALID'] = 'Segunda Via Cartão';
	}
	
	$missingFields = array();
	foreach ($requiredFields as $field => $name) {
		if (!isset($row[$field]) || $row[$field] != '1') {
			$missingFields[] = $name;
			error_log("OP validation failed for field: " . $field . " with value: " . (isset($row[$field]) ? $row[$field] : 'NULL'));
		}
	}
	
	if (!empty($missingFields)) {
		return 'Validações OP pendentes (v' . $version . '): ' . implode(', ', $missingFields);
	}
	
	return true;
}

private function extractVersionFromContract($tipoContrato) {
	// Extract version number from text like "Termo de Adesão 8.4"
	if (preg_match('/(\d+\.\d+)/', $tipoContrato, $matches)) {
		$version = (float)$matches[1];
		if ($version >= 8.1) {
			return $version;
		}
	}
	
	return null;
}

// Update your existing exportTXT method - add this validation right after data retrieval:

public function exportTXT() {
	$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
	$selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

	error_log("=== EXPORTAR TXT INITIATED ===");
	error_log("exportTXT - filter: " . $filter);
	error_log("exportTXT - selectedIds raw: " . $selectedIds);

	try {
		if (!empty($selectedIds)) {
			error_log("exportTXT - Processing selected IDs");
			
			$allData = $this->model->getTableDataByFilter($filter);
			error_log("exportTXT - All data count: " . count($allData));
			
			$idsArray = explode(',', $selectedIds);
			$cleanIds = array();
			foreach ($idsArray as $id) {
				$cleanId = trim($id);
				$cleanId = preg_replace('/\s+/', '', $cleanId); // Remove todos os espaços
				if (!empty($cleanId) && is_numeric($cleanId)) {
					$cleanIds[] = intval($cleanId); 
				}
			}
			
			error_log("exportTXT - Clean IDs: " . implode('|', $cleanIds));
			
			$data = array();
			foreach ($cleanIds as $sequentialId) {
				$arrayIndex = $sequentialId - 1; 
				
				if (isset($allData[$arrayIndex])) {
					$data[] = $allData[$arrayIndex];
					error_log("exportTXT - MATCH found: sequential ID '$sequentialId' -> array index '$arrayIndex'");
				} else {
					error_log("exportTXT - No data found for sequential ID '$sequentialId' (array index '$arrayIndex')");
				}
			}
			
			error_log("exportTXT - Filtered data count: " . count($data));
		} else {
			error_log("exportTXT - Getting all data");
			$data = $this->model->getTableDataByFilter($filter);
		}
		
		// *** ADD VALIDATION HERE ***
		error_log("=== STARTING TXT EXPORT VALIDATION ===");
		$invalidRecords = $this->validateRecordsForTXTExport($data);
		
		if (!empty($invalidRecords)) {
			error_log("exportTXT - Validation failed for " . count($invalidRecords) . " records");
			
			$xml = '<response>';
			$xml .= '<success>false</success>';
			$xml .= '<validation_error>true</validation_error>';
			$xml .= '<invalid_records>';
			foreach ($invalidRecords as $record) {
				$xml .= '<record>';
				$xml .= '<cod_empresa>' . addcslashes($record['cod_empresa'], '"<>&') . '</cod_empresa>';
				$xml .= '<error>' . addcslashes($record['error'], '"<>&') . '</error>';
				$xml .= '</record>';
			}
			$xml .= '</invalid_records>';
			$xml .= '<message>Alguns registros não atendem aos critérios para exportação TXT. Use o botão Exportar Access.</message>';
			$xml .= '</response>';
			
			echo $xml;
			exit;
		}
		
		error_log("exportTXT - All records passed validation");
		// *** CONTINUE WITH EXISTING EXPORT LOGIC ***
		
		$xml = '<response>';
		$xml .= '<success>true</success>';
		$xml .= '<debug>';
		$xml .= '<totalRecords>' . count($data) . '</totalRecords>';
		$xml .= '<filter>' . addcslashes($filter, '"<>&') . '</filter>';
		$xml .= '<selectedIds>' . addcslashes($selectedIds, '"<>&') . '</selectedIds>';
		$xml .= '</debug>';
		$xml .= '<txtData>';
		
		// ... rest of your existing exportTXT logic remains the same ...
		
	} catch (Exception $e) {
		error_log("exportTXT - Exception: " . $e->getMessage());
		
		$xml = '<response>';
		$xml .= '<success>false</success>';
		$xml .= '<e>' . addcslashes($e->getMessage(), '"<>&') . '</e>';
		$xml .= '</response>';
		
		echo $xml;
		exit;
	}
}
?>