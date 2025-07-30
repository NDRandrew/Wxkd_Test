<?php
// In TestH.txt, replace the existing status generation logic with this improved version:

if ($activeFilter === 'descadastramento') {
    $tipo = isset($row['TIPO_CORRESPONDENTE']) ? strtoupper(trim($row['TIPO_CORRESPONDENTE'])) : '';
    
    $dataConclusao = isset($row['DATA_CONCLUSAO']) ? trim($row['DATA_CONCLUSAO']) : '';
    $dataConclusaoTimestamp = false;
    
    if (!empty($dataConclusao)) {
        // Try different date parsing methods
        $dateParts = explode('/', $dataConclusao);
        if (count($dateParts) == 3) {
            $day = (int)$dateParts[0];
            $month = (int)$dateParts[1];
            $year = (int)$dateParts[2];
            
            if (checkdate($month, $day, $year)) {
                $dataConclusaoTimestamp = mktime(0, 0, 0, $month, $day, $year);
            }
        } else {
            // Try strtotime for other formats
            $dataConclusaoTimestamp = strtotime($dataConclusao);
        }
    }
    
    // Only show these four fields for descadastramento (avoid duplicates)
    $displayFields = array(
        'AVANCADO' => 'AV',
        'ORGAO_PAGADOR' => 'OP',
        'PRESENCA' => 'PR',
        'UNIDADE_NEGOCIO' => 'UN'
    );
    
    foreach ($displayFields as $field => $label) {
        $isOn = false;
        
        // Check if this field matches the tipo (handle both ORG_PAGADOR and ORGAO_PAGADOR for OP)
        $fieldMatches = ($field === $tipo) || 
                    ($field === 'ORGAO_PAGADOR' && $tipo === 'ORG_PAGADOR') ||
                    ($field === 'ORG_PAGADOR' && $tipo === 'ORGAO_PAGADOR');
        
        if ($fieldMatches && $dataConclusaoTimestamp !== false && $dataConclusaoTimestamp > $cutoff) {
            $isOn = true;
        }
        
        $color = $isOn ? 'green' : 'gray';
        $status = $isOn ? 'active' : 'inactive';
        
        $debugTitle = "Field: $field, Tipo: $tipo, Match: " . ($fieldMatches ? 'YES' : 'NO') . 
                    ", Date: $dataConclusao, Timestamp: $dataConclusaoTimestamp, Cutoff: $cutoff, IsOn: " . ($isOn ? 'YES' : 'NO');
        
        echo '<div style="display:inline-block;width:30px;height:30px;
                margin-right:5px;text-align:center;line-height:30px;
                font-size:10px;font-weight:bold;color:white;
                background-color:' . $color . ';border-radius:4px;" 
                data-field="' . $field . '" data-status="' . $status . '" 
                title="' . htmlspecialchars($debugTitle) . '">' . $label . '</div>';
    }
} else {
    // Existing logic for other filters
    foreach ($fields as $field => $label) {
        if ($field === 'ORG_PAGADOR') continue; // Skip duplicate to avoid showing OP twice
        
        $raw = isset($row[$field]) ? trim($row[$field]) : '';
        // ... rest of the existing logic
    }
}
?>