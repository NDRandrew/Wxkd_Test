public function exportXLSX() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $selectedIds = isset($_GET['ids']) ? $_GET['ids'] : '';
    
    if ($filter === 'historico') {
        $this->exportHistoricoXLSX($selectedIds);
        return;
    }
    
    $data = $this->getFilteredData($filter, $selectedIds);
    
    if (empty($data)) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<e>Nenhum dado encontrado para exportação</e>';
        $xml .= '</response>';
        echo $xml;
        exit;
    }
    
    try {
        // Include PHPExcel library
        require_once '../lib/PHPExcel/PHPExcel.php';
        
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("WXKD Dashboard")
            ->setLastModifiedBy("WXKD Dashboard")
            ->setTitle("Dashboard Export")
            ->setSubject("Dashboard Data Export")
            ->setDescription("Exported data from WXKD Dashboard");
        
        $objPHPExcel->setActiveSheetIndex(0);
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheet->setTitle('Dashboard Data');
        
        // Set headers
        $headers = array(
            'A1' => 'CHAVE_LOJA',
            'B1' => 'NOME_LOJA', 
            'C1' => 'COD_EMPRESA',
            'D1' => 'COD_LOJA',
            'E1' => 'QUANT_LOJAS',
            'F1' => 'TIPO_CORRESPONDENTE',
            'G1' => 'DATA_CONCLUSAO',
            'H1' => 'DATA_SOLICITACAO',
            'I1' => 'DEP_DINHEIRO',
            'J1' => 'DEP_CHEQUE',
            'K1' => 'REC_RETIRADA',
            'L1' => 'SAQUE_CHEQUE',
            'M1' => '2VIA_CARTAO',
            'N1' => 'HOLERITE_INSS',
            'O1' => 'CONS_INSS',
            'P1' => 'PROVA_DE_VIDA',
            'Q1' => 'DATA_CONTRATO',
            'R1' => 'TIPO_CONTRATO'
        );
        
        foreach ($headers as $cell => $header) {
            $activeSheet->setCellValue($cell, $header);
            $activeSheet->getStyle($cell)->getFont()->setBold(true);
        }
        
        // Set data
        $row = 2;
        foreach ($data as $dataRow) {
            $activeSheet->setCellValue('A' . $row, isset($dataRow['Chave_Loja']) ? $dataRow['Chave_Loja'] : '');
            $activeSheet->setCellValue('B' . $row, isset($dataRow['Nome_Loja']) ? $dataRow['Nome_Loja'] : '');
            $activeSheet->setCellValue('C' . $row, isset($dataRow['Cod_Empresa']) ? $dataRow['Cod_Empresa'] : '');
            $activeSheet->setCellValue('D' . $row, isset($dataRow['Cod_Loja']) ? $dataRow['Cod_Loja'] : '');
            $activeSheet->setCellValue('E' . $row, isset($dataRow['QUANT_LOJAS']) ? $dataRow['QUANT_LOJAS'] : '');
            $activeSheet->setCellValue('F' . $row, isset($dataRow['TIPO_CORRESPONDENTE']) ? $dataRow['TIPO_CORRESPONDENTE'] : '');
            $activeSheet->setCellValue('G' . $row, isset($dataRow['DATA_CONCLUSAO']) ? $dataRow['DATA_CONCLUSAO'] : '');
            
            // Format DATA_SOLICITACAO
            $dataSolicitacao = $this->formatDataSolicitacaoForExport($dataRow);
            $activeSheet->setCellValue('H' . $row, $dataSolicitacao);
            
            // Add validation fields with version-specific logic
            $this->addValidationFieldsToXLSX($activeSheet, $row, $dataRow);
            
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'R') as $col) {
            $activeSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "dashboard_{$filter}_{$timestamp}.xlsx";
        
        // Create Excel2007 writer
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $objWriter->save('php://output');
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

private function exportHistoricoXLSX($selectedIds) {
    try {
        $data = $this->getHistoricoFilteredData($selectedIds);
        
        if (empty($data)) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<e>Nenhum dado encontrado para exportação</e>';
            $xml .= '</response>';
            echo $xml;
            exit;
        }
        
        // Include PHPExcel library
        require_once '../lib/PHPExcel/PHPExcel.php';
        
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
            ->setCreator("WXKD Dashboard")
            ->setTitle("Histórico Export");
        
        $objPHPExcel->setActiveSheetIndex(0);
        $activeSheet = $objPHPExcel->getActiveSheet();
        $activeSheet->setTitle('Histórico Data');
        
        // Set headers for historico
        $headers = array(
            'A1' => 'CHAVE_LOTE',
            'B1' => 'DATA_LOG',
            'C1' => 'CHAVE_LOJA',
            'D1' => 'NOME_LOJA',
            'E1' => 'COD_EMPRESA',
            'F1' => 'COD_LOJA',
            'G1' => 'TIPO_CORRESPONDENTE',
            'H1' => 'DATA_CONCLUSAO',
            'I1' => 'DATA_SOLICITACAO',
            'J1' => 'DEP_DINHEIRO',
            'K1' => 'DEP_CHEQUE',
            'L1' => 'REC_RETIRADA',
            'M1' => 'SAQUE_CHEQUE',
            'N1' => 'SEGUNDA_VIA_CARTAO',
            'O1' => 'HOLERITE_INSS',
            'P1' => 'CONS_INSS',
            'Q1' => 'PROVA_DE_VIDA',
            'R1' => 'DATA_CONTRATO',
            'S1' => 'TIPO_CONTRATO',
            'T1' => 'FILTRO'
        );
        
        foreach ($headers as $cell => $header) {
            $activeSheet->setCellValue($cell, $header);
            $activeSheet->getStyle($cell)->getFont()->setBold(true);
        }
        
        // Set data
        $row = 2;
        foreach ($data as $dataRow) {
            $activeSheet->setCellValue('A' . $row, isset($dataRow['CHAVE_LOTE']) ? $dataRow['CHAVE_LOTE'] : '');
            
            // Format DATA_LOG
            $timeAndre = strtotime($dataRow['DATA_LOG']);
            $formattedDataLog = ($timeAndre !== false && !empty($dataRow['DATA_LOG'])) ? date('d/m/Y', $timeAndre) : '—';
            $activeSheet->setCellValue('B' . $row, $formattedDataLog);
            
            $activeSheet->setCellValue('C' . $row, isset($dataRow['CHAVE_LOJA']) ? $dataRow['CHAVE_LOJA'] : '');
            $activeSheet->setCellValue('D' . $row, isset($dataRow['NOME_LOJA']) ? $dataRow['NOME_LOJA'] : '');
            $activeSheet->setCellValue('E' . $row, isset($dataRow['COD_EMPRESA']) ? $dataRow['COD_EMPRESA'] : '');
            $activeSheet->setCellValue('F' . $row, isset($dataRow['COD_LOJA']) ? $dataRow['COD_LOJA'] : '');
            $activeSheet->setCellValue('G' . $row, isset($dataRow['TIPO_CORRESPONDENTE']) ? $dataRow['TIPO_CORRESPONDENTE'] : '');
            
            // Format DATA_CONCLUSAO
            $timeAndre = strtotime($dataRow['DATA_CONCLUSAO']);
            $formattedDataConclusao = ($timeAndre !== false && !empty($dataRow['DATA_CONCLUSAO'])) ? date('d/m/Y', $timeAndre) : '—';
            $activeSheet->setCellValue('H' . $row, $formattedDataConclusao);
            
            // Format DATA_SOLICITACAO
            $timeAndre = strtotime($dataRow['DATA_SOLICITACAO']);
            $formattedDataSolicitacao = ($timeAndre !== false && !empty($dataRow['DATA_SOLICITACAO'])) ? date('d/m/Y', $timeAndre) : '—';
            $activeSheet->setCellValue('I' . $row, $formattedDataSolicitacao);
            
            $activeSheet->setCellValue('J' . $row, isset($dataRow['DEP_DINHEIRO']) ? $dataRow['DEP_DINHEIRO'] : '');
            $activeSheet->setCellValue('K' . $row, isset($dataRow['DEP_CHEQUE']) ? $dataRow['DEP_CHEQUE'] : '');
            $activeSheet->setCellValue('L' . $row, isset($dataRow['REC_RETIRADA']) ? $dataRow['REC_RETIRADA'] : '');
            $activeSheet->setCellValue('M' . $row, isset($dataRow['SAQUE_CHEQUE']) ? $dataRow['SAQUE_CHEQUE'] : '');
            $activeSheet->setCellValue('N' . $row, isset($dataRow['SEGUNDA_VIA_CARTAO']) ? $dataRow['SEGUNDA_VIA_CARTAO'] : '');
            $activeSheet->setCellValue('O' . $row, isset($dataRow['HOLERITE_INSS']) ? $dataRow['HOLERITE_INSS'] : '');
            $activeSheet->setCellValue('P' . $row, isset($dataRow['CONS_INSS']) ? $dataRow['CONS_INSS'] : '');
            $activeSheet->setCellValue('Q' . $row, isset($dataRow['PROVA_DE_VIDA']) ? $dataRow['PROVA_DE_VIDA'] : '');
            
            // Format DATA_CONTRATO
            $timeAndre = strtotime($dataRow['DATA_CONTRATO']);
            $formattedDataContrato = ($timeAndre !== false && !empty($dataRow['DATA_CONTRATO'])) ? date('d/m/Y', $timeAndre) : '—';
            $activeSheet->setCellValue('R' . $row, $formattedDataContrato);
            
            $activeSheet->setCellValue('S' . $row, isset($dataRow['TIPO_CONTRATO']) ? $dataRow['TIPO_CONTRATO'] : '');
            $activeSheet->setCellValue('T' . $row, isset($dataRow['FILTRO']) ? $dataRow['FILTRO'] : '');
            
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'T') as $col) {
            $activeSheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Generate filename
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "dashboard_historico_{$timestamp}.xlsx";
        
        // Create Excel2007 writer
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
        // Output to browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $objWriter->save('php://output');
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

private function addValidationFieldsToXLSX($activeSheet, $row, $dataRow) {
    // Get standard validation fields
    $validationFields = array(
        'dep_dinheiro' => array('field' => 'DEP_DINHEIRO_VALID', 'col' => 'I'),
        'dep_cheque' => array('field' => 'DEP_CHEQUE_VALID', 'col' => 'J'),
        'rec_retirada' => array('field' => 'REC_RETIRADA_VALID', 'col' => 'K'),
        'saque_cheque' => array('field' => 'SAQUE_CHEQUE_VALID', 'col' => 'L')
    );
    
    foreach ($validationFields as $xmlField => $config) {
        $value = '';
        if (isset($dataRow[$config['field']]) && isset($dataRow['TIPO_LIMITES'])) {
            $isPresencaOrOrgao = (strpos($dataRow['TIPO_LIMITES'], 'PRESENCA') !== false || 
                            strpos($dataRow['TIPO_LIMITES'], 'ORG_PAGADOR') !== false);
            $isAvancadoOrApoio = (strpos($dataRow['TIPO_LIMITES'], 'AVANCADO') !== false || 
                            strpos($dataRow['TIPO_LIMITES'], 'UNIDADE_NEGOCIO') !== false);
            
            $limits = $this->getLimitsForField($xmlField, $isPresencaOrOrgao, $isAvancadoOrApoio);
            $value = ($dataRow[$config['field']] == 1) ? $limits['valid'] : $limits['invalid'];
        } else {
            $value = 'Tipo não definido';
        }
        $activeSheet->setCellValue($config['col'] . $row, $value);
    }
    
    // Check if ORGAO_PAGADOR is empty for this row
    $orgaoPagadorEmpty = empty($dataRow['ORGAO_PAGADOR']) || trim($dataRow['ORGAO_PAGADOR']) === '';
    
    // Handle the 4 validation fields that depend on ORGAO_PAGADOR with version-specific logic
    $tipoContrato = isset($dataRow['TIPO_CONTRATO']) ? $dataRow['TIPO_CONTRATO'] : '';
    $version = $this->extractVersionFromContract($tipoContrato);
    
    // SEGUNDA_VIA_CARTAO (Column M)
    if ($orgaoPagadorEmpty) {
        $activeSheet->setCellValue('M' . $row, ' ----- ');
    } else {
        $isValid = isset($dataRow['SEGUNDA_VIA_CARTAO_VALID']) && $dataRow['SEGUNDA_VIA_CARTAO_VALID'] === '1';
        $value = $this->getVersionSpecificTextForSegundaVia($version, $isValid);
        $activeSheet->setCellValue('M' . $row, $value);
    }
    
    // HOLERITE_INSS (Column N)
    if ($orgaoPagadorEmpty) {
        $activeSheet->setCellValue('N' . $row, ' ----- ');
    } else {
        $isValid = isset($dataRow['HOLERITE_INSS_VALID']) && $dataRow['HOLERITE_INSS_VALID'] === '1';
        $value = $this->getVersionSpecificTextForHolerite($version, $isValid);
        $activeSheet->setCellValue('N' . $row, $value);
    }
    
    // CONSULTA_INSS (Column O)
    if ($orgaoPagadorEmpty) {
        $activeSheet->setCellValue('O' . $row, ' ----- ');
    } else {
        $isValid = isset($dataRow['CONSULTA_INSS_VALID']) && $dataRow['CONSULTA_INSS_VALID'] === '1';
        $value = $this->getVersionSpecificTextForConsulta($version, $isValid);
        $activeSheet->setCellValue('O' . $row, $value);
    }
    
    // PROVA_DE_VIDA (Column P) - Keep original logic
    if ($orgaoPagadorEmpty) {
        $activeSheet->setCellValue('P' . $row, ' ----- ');
    } else {
        $isValid = isset($dataRow['PROVA_DE_VIDA_VALID']) && $dataRow['PROVA_DE_VIDA_VALID'] === '1';
        $value = $isValid ? 'Apurado' : 'Não Apurado';
        $activeSheet->setCellValue('P' . $row, $value);
    }
    
    // DATA_CONTRATO and TIPO_CONTRATO (Columns Q and R)
    $activeSheet->setCellValue('Q' . $row, isset($dataRow['DATA_CONTRATO']) ? $dataRow['DATA_CONTRATO'] : '');
    $activeSheet->setCellValue('R' . $row, isset($dataRow['TIPO_CONTRATO']) ? $dataRow['TIPO_CONTRATO'] : '');
}

private function getVersionSpecificTextForSegundaVia($version, $isValid) {
    if ($version === null) {
        return 'Sem Contrato';
    }
    
    if ($version < 8.1) {
        return 'Nao Apto';
    } else if ($version >= 8.1 && $version < 10.1) {
        return 'Nao Apto';
    } else if ($version >= 10.1) {
        return 'Apto';
    } else {
        return 'Nao Apto';
    }
}

private function getVersionSpecificTextForHolerite($version, $isValid) {
    if ($version === null) {
        return 'Sem Contrato';
    }
    
    // For HOLERITE_INSS, all versions are "Apto" regardless of version
    return 'Apto';
}

private function getVersionSpecificTextForConsulta($version, $isValid) {
    if ($version === null) {
        return 'Sem Contrato';
    }
    
    if ($version < 8.1) {
        return 'Nao Apto';
    } else if ($version >= 8.1 && $version < 10.1) {
        return 'Apto';
    } else if ($version >= 10.1) {
        return 'Apto';
    } else {
        return 'Nao Apto';
    }
}

private function formatDataSolicitacaoForExport($row) {
    $timeAndre = isset($row['DATA_SOLICITACAO']) ? strtotime($row['DATA_SOLICITACAO']) : false;
    if ($timeAndre !== false && !empty($row['DATA_SOLICITACAO'])) {
        return date('d/m/Y', $timeAndre);
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

        return !empty($matchingDates) ? implode(' / ', $matchingDates) : '—';
    }
}