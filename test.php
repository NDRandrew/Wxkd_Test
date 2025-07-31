// FIXED: PHP 5.3 Compatible - Helper method to determine TXT export type for descadastramento
private function determineDescadastroTXTType($chaveLoja, $descadastroTipo, $actualTipoData) {
    // If no actual data found, assume descadastramento tipo only
    if (!isset($actualTipoData[$chaveLoja])) {
        return 'ONLY_' . strtoupper($descadastroTipo);
    }
    
    $actualData = $actualTipoData[$chaveLoja];
    $descadastroTipoUpper = strtoupper($descadastroTipo);
    
    // Normalize descadastramento tipo variants
    if ($descadastroTipoUpper === 'ORG_PAGADOR') {
        $descadastroTipoUpper = 'ORGAO_PAGADOR';
    }
    
    // Check for ACTUAL tipos in the data (not NULL)
    $hasAvancado = !empty($actualData['AVANCADO']);
    $hasUnidadeNegocio = !empty($actualData['UNIDADE_NEGOCIO']);
    $hasOrgaoPagador = !empty($actualData['ORGAO_PAGADOR']);  
    $hasPresenca = !empty($actualData['PRESENCA']);
    
    // Collect all ACTUAL tipos that exist (ignoring what descadastramento shows)
    $actualTipos = array();
    if ($hasAvancado) $actualTipos[] = 'AVANCADO';
    if ($hasUnidadeNegocio) $actualTipos[] = 'UNIDADE_NEGOCIO';
    if ($hasOrgaoPagador) $actualTipos[] = 'ORGAO_PAGADOR';
    if ($hasPresenca) $actualTipos[] = 'PRESENCA';
    
    // Remove the descadastramento tipo from actual tipos (assume it's there as per requirement)
    // PHP 5.3 Compatible way instead of array_filter with anonymous function
    $additionalTipos = array();
    foreach ($actualTipos as $tipo) {
        if ($tipo !== $descadastroTipoUpper) {
            $additionalTipos[] = $tipo;
        }
    }
    
    // If no additional tipos beyond what descadastramento shows
    if (empty($additionalTipos)) {
        return 'ONLY_' . $descadastroTipoUpper;
    }
    
    // Return highest priority additional tipo based on hierarchy: AVANCADO/UNIDADE_NEGOCIO > ORGAO_PAGADOR/PRESENCA
    if (in_array('AVANCADO', $additionalTipos)) {
        return 'ADDITIONAL_AVANCADO';
    }
    if (in_array('UNIDADE_NEGOCIO', $additionalTipos)) {
        return 'ADDITIONAL_UNIDADE_NEGOCIO';
    }
    if (in_array('ORGAO_PAGADOR', $additionalTipos)) {
        return 'ADDITIONAL_ORGAO_PAGADOR';
    }
    if (in_array('PRESENCA', $additionalTipos)) {
        return 'ADDITIONAL_PRESENCA';
    }
    
    // Fallback (should not reach here)
    return 'ONLY_' . $descadastroTipoUpper;
}
