public function exportAccess() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';

    try {
        if ($filter === 'historico') {
            $data = $this->getHistoricoFilteredData($selectedIds);
        } else {
            $data = $this->getFilteredData($filter, $selectedIds);
        }
        
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
            if ($filter === 'historico') {
                $row = array_change_key_case($row, CASE_UPPER);
                
                // For historico data, use TIPO_CORRESPONDENTE directly
                $tipoCorrespondente = isset($row['TIPO_CORRESPONDENTE']) ? trim($row['TIPO_CORRESPONDENTE']) : '';
                $dataConlusao = isset($row['DATA_CONCLUSAO']) ? $row['DATA_CONCLUSAO'] : '';
                
                // Check if the data_conclusao is after cutoff
                $isAfterCutoff = false;
                if (!empty($dataConlusao)) {
                    $timestamp = strtotime($dataConlusao);
                    if ($timestamp !== false && $timestamp > $cutoff) {
                        $isAfterCutoff = true;
                    }
                }
                
                $activeTypes = array();
                if ($isAfterCutoff && !empty($tipoCorrespondente)) {
                    // Map the tipo_correspondente to the short codes
                    $tipoMapping = array(
                        'AVANCADO' => 'AV',
                        'PRESENCA' => 'PR', 
                        'UNIDADE_NEGOCIO' => 'UN',
                        'ORGAO_PAGADOR' => 'OP'
                    );
                    
                    if (isset($tipoMapping[$tipoCorrespondente])) {
                        $activeTypes[] = $tipoMapping[$tipoCorrespondente];
                    } else {
                        // Try to match partial strings
                        foreach ($tipoMapping as $fullType => $shortType) {
                            if (strpos($tipoCorrespondente, $fullType) !== false) {
                                $activeTypes[] = $shortType;
                                break;
                            }
                        }
                    }
                }
            } else {
                // For regular data, use the existing getActiveTypes method
                $activeTypes = $this->getActiveTypes($row, $cutoff);
            }

            $hasOP = in_array('OP', $activeTypes);
            $hasOthers = in_array('AV', $activeTypes) || in_array('PR', $activeTypes) || in_array('UN', $activeTypes);

            $codEmpresa = $this->getEmpresaCode($row);

            if (!empty($codEmpresa) && !empty($activeTypes)) {
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