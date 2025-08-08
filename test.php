<?php
// Replace the existing validation fields display section (around lines for 2Via_Cartao, Holerite_INSS, etc.)

// Check if ORGAO_PAGADOR is empty for this row
$orgaoPagadorEmpty = empty($row['ORGAO_PAGADOR']) || trim($row['ORGAO_PAGADOR']) === '';

// SEGUNDA_VIA_CARTAO (2Via_Cartao)
$bgColor = empty($row['SEGUNDA_VIA_CARTAO_VALID']) ? '#ffb7bb' : 'transparent';
$value = '';

if ($orgaoPagadorEmpty) {
    $value = ' - ';
    $bgColor = 'transparent'; // No red background for horizontal line
} else {
    if(isset($row['SEGUNDA_VIA_CARTAO_VALID'])){
        $value = ($row['SEGUNDA_VIA_CARTAO_VALID'] === 1) ? 'Apto' : 'Nao Apto';
    }
}
echo '<td style="background-color: ' . $bgColor . '; text-align: center; vertical-align: middle;">' . $value . '</td>';

// HOLERITE_INSS
$bgColor = empty($row['HOLERITE_INSS_VALID']) ? '#ffb7bb' : 'transparent';
$value = '';

if ($orgaoPagadorEmpty) {
    $value = ' - ';
    $bgColor = 'transparent'; // No red background for horizontal line
} else {
    if(isset($row['HOLERITE_INSS_VALID'])){
        $value = ($row['HOLERITE_INSS_VALID'] === 1) ? 'Apto' : 'Nao Apto';
    }
}
echo '<td style="background-color: ' . $bgColor . '; text-align: center; vertical-align: middle;">' . $value . '</td>';

// CONSULTA_INSS (Cons_INSS)
$bgColor = empty($row['CONSULTA_INSS_VALID']) ? '#ffb7bb' : 'transparent';
$value = '';

if ($orgaoPagadorEmpty) {
    $value = ' - ';
    $bgColor = 'transparent'; // No red background for horizontal line
} else {
    if(isset($row['CONSULTA_INSS_VALID'])){
        $value = ($row['CONSULTA_INSS_VALID'] === 1) ? 'Apto' : 'Nao Apto';
    }
}
echo '<td style="background-color: ' . $bgColor . '; text-align: center; vertical-align: middle;">' . $value . '</td>';

// PROVA_DE_VIDA
$bgColor = empty($row['PROVA_DE_VIDA_VALID']) ? '#ffb7bb' : 'transparent';
$value = '';

if ($orgaoPagadorEmpty) {
    $value = ' - ';
    $bgColor = 'transparent'; // No red background for horizontal line
} else {
    if(isset($row['PROVA_DE_VIDA_VALID'])){
        $value = ($row['PROVA_DE_VIDA_VALID'] === 1) ? 'Apto' : 'Nao Apto';
    }
}
echo '<td style="background-color: ' . $bgColor . '; text-align: center; vertical-align: middle;">' . $value . '</td>';
?>




----------------------







// Replace the existing addValidationFields method in PaginationModule
addValidationFields: function(rowData, row) {
    const validationConfigs = [
        { field: 'dep_dinheiro', limits: { presenca: 'R$ 3.000,00', avancado: 'R$ 10.000,00' }, validField: 'DEP_DINHEIRO_VALID' },
        { field: 'dep_cheque', limits: { presenca: 'R$ 5.000,00', avancado: 'R$ 10.000,00' }, validField: 'DEP_CHEQUE_VALID' },
        { field: 'rec_retirada', limits: { presenca: 'R$ 2.000,00', avancado: 'R$ 3.500,00' }, validField: 'REC_RETIRADA_VALID' },
        { field: 'saque_cheque', limits: { presenca: 'R$ 2.000,00', avancado: 'R$ 3.500,00' }, validField: 'SAQUE_CHEQUE_VALID' }
    ];
    
    validationConfigs.forEach(config => {
        rowData.push(this.createValidationCell(row, config));
    });
    
    // Check if ORGAO_PAGADOR is empty for this row
    const orgaoPagadorEmpty = !row.ORGAO_PAGADOR || row.ORGAO_PAGADOR.toString().trim() === '';
    
    // Create the 4 special validation cells that depend on ORGAO_PAGADOR
    rowData.push(this.createConditionalValidationCell(row, 'SEGUNDA_VIA_CARTAO_VALID', orgaoPagadorEmpty));
    rowData.push(this.createConditionalValidationCell(row, 'HOLERITE_INSS_VALID', orgaoPagadorEmpty));
    rowData.push(this.createConditionalValidationCell(row, 'CONSULTA_INSS_VALID', orgaoPagadorEmpty));
    rowData.push(this.createConditionalValidationCell(row, 'PROVA_DE_VIDA_VALID', orgaoPagadorEmpty));
},

// Add this new method to PaginationModule
createConditionalValidationCell: function(row, validField, orgaoPagadorEmpty) {
    let value = '';
    let style = 'text-align: center; vertical-align: middle;';
    
    if (orgaoPagadorEmpty) {
        value = ' - ';
        // No red background for horizontal line
    } else {
        const isValid = row[validField] === '1';
        value = isValid ? 'Apto' : 'Não Apto';
        
        if (!isValid) {
            style += ' background-color: #ffb7bb;';
        }
    }
    
    return $('<td>').attr('style', style).text(value);
},

// Replace the existing createSimpleValidationCell method
createSimpleValidationCell: function(row, validField) {
    // This method is now only used for the 4 monetary validation fields
    // The ORGAO_PAGADOR dependent fields use createConditionalValidationCell instead
    const isValid = row[validField] === '1';
    const value = isValid ? 'Apto' : 'Não Apto';
    const style = isValid ? 'text-align: center; vertical-align: middle;' : 'text-align: center; vertical-align: middle; background-color: #ffb7bb;';
    
    return $('<td>').attr('style', style).text(value);
}




--------------------








<?php
// Replace the addValidationFieldsToXML method in Wxkd_DashboardController class

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
    
    // Check if ORGAO_PAGADOR is empty for this row
    $orgaoPagadorEmpty = empty($row['ORGAO_PAGADOR']) || trim($row['ORGAO_PAGADOR']) === '';
    
    // Handle the 4 validation fields that depend on ORGAO_PAGADOR
    $xml .= '<segunda_via_cartao>';
    if ($orgaoPagadorEmpty) {
        $xml .= ' - ';
    } else {
        if (isset($row['SEGUNDA_VIA_CARTAO_VALID'])) {
            $xml .= ($row['SEGUNDA_VIA_CARTAO_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
    }
    $xml .= '</segunda_via_cartao>';
    
    $xml .= '<holerite_inss>';
    if ($orgaoPagadorEmpty) {
        $xml .= ' - ';
    } else {
        if (isset($row['HOLERITE_INSS_VALID'])) {
            $xml .= ($row['HOLERITE_INSS_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
    }
    $xml .= '</holerite_inss>';
    
    $xml .= '<cons_inss>';
    if ($orgaoPagadorEmpty) {
        $xml .= ' - ';
    } else {
        if (isset($row['CONSULTA_INSS_VALID'])) {
            $xml .= ($row['CONSULTA_INSS_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
    }
    $xml .= '</cons_inss>';

    $xml .= '<prova_de_vida>';
    if ($orgaoPagadorEmpty) {
        $xml .= ' - ';
    } else {
        if (isset($row['PROVA_DE_VIDA_VALID'])) {
            $xml .= ($row['PROVA_DE_VIDA_VALID'] === 1) ? 'Apto' : 'Nao Apto';
        }
    }
    $xml .= '</prova_de_vida>';
    
    $xml .= '<data_contrato>' . addcslashes(isset($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO'] : '', '"<>&') . '</data_contrato>';
    $xml .= '<tipo_contrato>' . addcslashes(isset($row['TIPO_CONTRATO']) ? $row['TIPO_CONTRATO'] : '', '"<>&') . '</tipo_contrato>';
}

// Also update the getValidationErrorMessage method to handle the ORGAO_PAGADOR check
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
            // For OP type, we need to check if ORGAO_PAGADOR has a value
            $orgaoPagadorEmpty = empty($row['ORGAO_PAGADOR']) || trim($row['ORGAO_PAGADOR']) === '';
            
            if ($orgaoPagadorEmpty) {
                return 'Tipo OP - ORGAO_PAGADOR sem data valida';
            }
            
            $opValidation = $this->checkOPValidations($row);
            if ($opValidation !== true) {
                return str_replace(array('ç', 'õ', 'ã'), array('c', 'o', 'a'), $opValidation);
            }
        }
    }
    
    return null; 
}
?>





---------------------






<?php
// Replace the getValidationValue method in Wxkd_DashboardModel class

private function getValidationValue($record, $fieldNames) {
    // Check if this is one of the ORGAO_PAGADOR dependent fields
    $orgaoPagadorDependentFields = array('SEGUNDA_VIA_CARTAO_VALID', 'SEGUNDA_VIA_CARTAO', 'HOLERITE_INSS_VALID', 'HOLERITE_INSS', 'CONSULTA_INSS_VALID', 'CONS_INSS', 'PROVA_DE_VIDA_VALID', 'PROVA_DE_VIDA');
    
    $isOrgaoPagadorDependent = false;
    foreach ($fieldNames as $fieldName) {
        if (in_array($fieldName, $orgaoPagadorDependentFields)) {
            $isOrgaoPagadorDependent = true;
            break;
        }
    }
    
    // If this is an ORGAO_PAGADOR dependent field, check if ORGAO_PAGADOR is empty
    if ($isOrgaoPagadorDependent) {
        $orgaoPagadorEmpty = empty($record['ORGAO_PAGADOR']) || trim($record['ORGAO_PAGADOR']) === '';
        
        if ($orgaoPagadorEmpty) {
            return ' - ';
        }
    }
    
    // Continue with normal validation logic
    foreach ($fieldNames as $fieldName) {
        if (isset($record[$fieldName])) {
            $value = $record[$fieldName];
            if (is_string($value) && (strpos($value, 'Apto') !== false || strpos($value, 'apto') !== false)) {
                return $value;
            }
            if (is_numeric($value)) {
                return $value == 1 ? 'Apto' : 'Nao Apto';
            }
        }
    }
    return 'Nao Apto';
}

// Also update the updateWxkdFlag method to properly handle ORGAO_PAGADOR dependent validations
// Replace the validation value retrieval section in updateWxkdFlag method:

// Check if ORGAO_PAGADOR is empty for this record
$orgaoPagadorEmpty = empty($record['ORGAO_PAGADOR']) || trim($record['ORGAO_PAGADOR']) === '';

$segundaVia = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('SEGUNDA_VIA_CARTAO_VALID', 'SEGUNDA_VIA_CARTAO'));
$holeriteInss = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('HOLERITE_INSS_VALID', 'HOLERITE_INSS'));
$consultaInss = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('CONSULTA_INSS_VALID', 'CONS_INSS'));
$provaVida = $orgaoPagadorEmpty ? ' - ' : $this->getValidationValue($record, array('PROVA_DE_VIDA_VALID', 'PROVA_DE_VIDA'));
?>




-------------------------




// Add this method to the HistoricoModule object in TestJ
formatValidationField: function(rowData, fieldName, orgaoPagadorValue) {
    // Check if ORGAO_PAGADOR is empty
    const orgaoPagadorEmpty = !orgaoPagadorValue || orgaoPagadorValue.toString().trim() === '';
    
    // These fields depend on ORGAO_PAGADOR having a value
    const orgaoPagadorDependentFields = ['SEGUNDA_VIA_CARTAO', 'HOLERITE_INSS', 'CONS_INSS', 'PROVA_DE_VIDA'];
    
    if (orgaoPagadorDependentFields.includes(fieldName) && orgaoPagadorEmpty) {
        return ' - ';
    }
    
    // For other fields or when ORGAO_PAGADOR has value, return the original value
    return this.escapeHtml(rowData[fieldName]);
},

// Update the buildTableRow method to use the new validation logic
buildTableRow: function(rowData, detailId, chaveLote) {
    if (rowData.RAW_DATA) {
        return `
            <tr class="debug-row">
                <td class="checkbox-column">
                    <label>
                        <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                               value="${detailId}" data-chave-lote="${chaveLote}">
                        <span class="text"></span>
                    </label>
                </td>
                <td colspan="19" style="background-color: #fff3cd; padding: 10px;">
                    <strong>Dados não estruturados (debug):</strong><br>
                    <pre style="margin-top: 5px; font-size: 11px;">${this.escapeHtml(rowData.RAW_DATA)}</pre>
                    <small><em>Contate o desenvolvedor para corrigir a estrutura dos dados.</em></small>
                </td>
            </tr>
        `;
    }
    
    // Get ORGAO_PAGADOR value for validation logic
    const orgaoPagadorValue = rowData.ORGAO_PAGADOR || '';
    
    return `
        <tr>
            <td class="checkbox-column">
                <label>
                    <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                           value="${detailId}" data-chave-lote="${chaveLote}">
                    <span class="text"></span>
                </label>
            </td>
            <td>${this.escapeHtml(rowData.CHAVE_LOJA)}</td>
            <td>${this.escapeHtml(rowData.NOME_LOJA)}</td>
            <td>${this.escapeHtml(rowData.COD_EMPRESA)}</td>
            <td>${this.escapeHtml(rowData.COD_LOJA)}</td>
            <td>${this.escapeHtml(rowData.TIPO_CORRESPONDENTE)}</td>
            <td>${this.formatDate(rowData.DATA_CONCLUSAO)}</td>
            <td>${this.formatDate(rowData.DATA_SOLICITACAO)}</td>
            <td>${this.formatCurrency(rowData.DEP_DINHEIRO)}</td>
            <td>${this.formatCurrency(rowData.DEP_CHEQUE)}</td>
            <td>${this.formatCurrency(rowData.REC_RETIRADA)}</td>
            <td>${this.formatCurrency(rowData.SAQUE_CHEQUE)}</td>
            <td>${this.formatValidationField(rowData, 'SEGUNDA_VIA_CARTAO', orgaoPagadorValue)}</td>
            <td>${this.formatValidationField(rowData, 'HOLERITE_INSS', orgaoPagadorValue)}</td>
            <td>${this.formatValidationField(rowData, 'CONS_INSS', orgaoPagadorValue)}</td>
            <td>${this.formatValidationField(rowData, 'PROVA_DE_VIDA', orgaoPagadorValue)}</td>
            <td>${this.formatDate(rowData.DATA_CONTRATO)}</td>
            <td>${this.escapeHtml(rowData.TIPO_CONTRATO)}</td>
            <td>${this.formatDate(rowData.DATA_LOG)}</td>
            <td><span class="badge badge-info">${this.escapeHtml(rowData.FILTRO)}</span></td>
        </tr>
    `;
}




--------------------------




