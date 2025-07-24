<?php
// Add this at the very top of your Wxkd_DashboardController.php file, right after <?php
// This prevents any accidental output before headers

// Prevent any output before XML headers
if (isset($_GET['action']) && $_GET['action'] === 'ajaxGetHistoricoDetails') {
    // Clean any existing output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Start clean output buffering
    ob_start();
    
    // Turn off error reporting to prevent warnings from appearing in XML
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
}

// Alternative approach - Add this to the very top of wxkd.php (main file)
if (isset($_GET['action']) && $_GET['action'] === 'ajaxGetHistoricoDetails') {
    // Suppress all output until we're ready to send XML
    ob_start();
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Updated ajaxGetHistoricoDetails method with better error handling
public function ajaxGetHistoricoDetails() {
    // Additional cleanup
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    $chaveLote = isset($_GET['chave_lote']) ? (int)$_GET['chave_lote'] : 0;
    
    try {
        if ($chaveLote <= 0) {
            throw new Exception("CHAVE_LOTE inválido");
        }
        
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = $chaveLote ORDER BY CHAVE_LOJA";
        $detailData = $this->model->sql->select($query);
        
        $xmlData = array();
        
        if (is_array($detailData) && count($detailData) > 0) {
            foreach ($detailData as $row) {
                // Clean row - only keep named keys, ignore numeric indices
                $cleanRow = array();
                
                $expectedFields = array(
                    'CHAVE_LOJA', 'NOME_LOJA', 'COD_EMPRESA', 'COD_LOJA',
                    'TIPO_CORRESPONDENTE', 'DATA_CONCLUSAO', 'DATA_SOLICITACAO',
                    'DEP_DINHEIRO', 'DEP_CHEQUE', 'REC_RETIRADA', 'SAQUE_CHEQUE',
                    'SEGUNDA_VIA_CARTAO', 'HOLERITE_INSS', 'CONS_INSS', 'PROVA_DE_VIDA',
                    'DATA_CONTRATO', 'TIPO_CONTRATO', 'DATA_LOG', 'FILTRO'
                );
                
                foreach ($expectedFields as $field) {
                    if (isset($row[$field])) {
                        $cleanRow[$field] = $this->cleanXmlValue($row[$field]);
                    } else {
                        $cleanRow[$field] = '';
                    }
                }
                
                $xmlData[] = $cleanRow;
            }
        }
        
        // Build XML manually to ensure clean structure
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<response>\n<success>true</success>\n<detailData>\n";
        
        foreach ($xmlData as $index => $row) {
            $xml .= '<row id="' . ($index + 1) . '">' . "\n";
            foreach ($row as $key => $value) {
                $xml .= '<' . $key . '>' . $value . '</' . $key . '>' . "\n";
            }
            $xml .= '</row>' . "\n";
        }
        
        $xml .= '</detailData>' . "\n</response>";
        
        // Clear buffer completely
        ob_clean();
        
        // Set headers if possible
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
            header('Cache-Control: no-cache');
        }
        
        echo $xml;
        
    } catch (Exception $e) {
        ob_clean();
        
        $errorXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $errorXml .= '<response>' . "\n";
        $errorXml .= '<success>false</success>' . "\n";
        $errorXml .= '<e>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</e>' . "\n";
        $errorXml .= '</response>';
        
        if (!headers_sent()) {
            header('Content-Type: application/xml; charset=utf-8');
        }
        
        echo $errorXml;
    }
    
    ob_end_flush();
    exit;
}

// Helper method
private function cleanXmlValue($value) {
    if ($value === null) return '';
    $value = (string)$value;
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}
?>

/* JAVASCRIPT FIX - Update your HistoricoModule.loadDetails method */

loadDetails: function(e) {
    e.preventDefault();
    const button = $(e.currentTarget);
    const chaveLote = button.data('chave-lote');
    const tbody = button.closest('tbody');
    
    if (button.prop('disabled')) {
        return;
    }
    
    button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Carregando...');
    
    const self = this;
    
    $.get(`wxkd.php?action=ajaxGetHistoricoDetails&chave_lote=${chaveLote}`)
        .done((xmlData) => {
            console.log('Raw XML Response:', xmlData);
            
            try {
                // Clean the XML response more aggressively
                let cleanedXml = xmlData;
                
                // Remove PHP warnings/errors before XML
                const xmlStart = cleanedXml.indexOf('<?xml');
                if (xmlStart > 0) {
                    cleanedXml = cleanedXml.substring(xmlStart);
                }
                
                // Remove any remaining HTML/PHP output
                cleanedXml = cleanedXml.replace(/^.*?(<\?xml.*<\/response>).*$/s, '$1');
                
                console.log('Cleaned XML:', cleanedXml);
                
                const $xml = $(cleanedXml);
                const success = $xml.find('success').text() === 'true';
                
                if (success) {
                    let detailsHtml = '';
                    let recordCount = 0;
                    
                    $xml.find('detailData row').each(function() {
                        const $row = $(this);
                        recordCount++;
                        const detailId = `${chaveLote}_${recordCount}`;
                        
                        // Build clean row data object
                        const rowData = {
                            CHAVE_LOJA: $row.find('CHAVE_LOJA').text() || '',
                            NOME_LOJA: $row.find('NOME_LOJA').text() || '',
                            COD_EMPRESA: $row.find('COD_EMPRESA').text() || '',
                            COD_LOJA: $row.find('COD_LOJA').text() || '',
                            TIPO_CORRESPONDENTE: $row.find('TIPO_CORRESPONDENTE').text() || '',
                            DATA_CONCLUSAO: $row.find('DATA_CONCLUSAO').text() || '',
                            DATA_SOLICITACAO: $row.find('DATA_SOLICITACAO').text() || '',
                            DEP_DINHEIRO: $row.find('DEP_DINHEIRO').text() || '0',
                            DEP_CHEQUE: $row.find('DEP_CHEQUE').text() || '0',
                            REC_RETIRADA: $row.find('REC_RETIRADA').text() || '0',
                            SAQUE_CHEQUE: $row.find('SAQUE_CHEQUE').text() || '0',
                            SEGUNDA_VIA_CARTAO: $row.find('SEGUNDA_VIA_CARTAO').text() || '',
                            HOLERITE_INSS: $row.find('HOLERITE_INSS').text() || '',
                            CONS_INSS: $row.find('CONS_INSS').text() || '',
                            PROVA_DE_VIDA: $row.find('PROVA_DE_VIDA').text() || '',
                            DATA_CONTRATO: $row.find('DATA_CONTRATO').text() || '',
                            TIPO_CONTRATO: $row.find('TIPO_CONTRATO').text() || '',
                            DATA_LOG: $row.find('DATA_LOG').text() || '',
                            FILTRO: $row.find('FILTRO').text() || ''
                        };
                        
                        console.log(`Row ${recordCount} data:`, rowData);
                        
                        // Build table row HTML
                        detailsHtml += `
                            <tr>
                                <td class="checkbox-column">
                                    <label>
                                        <input type="checkbox" class="form-check-input historico-detail-checkbox" 
                                               value="${detailId}" data-chave-lote="${chaveLote}">
                                        <span class="text"></span>
                                    </label>
                                </td>
                                <td>${self.escapeHtml(rowData.CHAVE_LOJA)}</td>
                                <td>${self.escapeHtml(rowData.NOME_LOJA)}</td>
                                <td>${self.escapeHtml(rowData.COD_EMPRESA)}</td>
                                <td>${self.escapeHtml(rowData.COD_LOJA)}</td>
                                <td>${self.escapeHtml(rowData.TIPO_CORRESPONDENTE)}</td>
                                <td>${self.formatDate(rowData.DATA_CONCLUSAO)}</td>
                                <td>${self.formatDate(rowData.DATA_SOLICITACAO)}</td>
                                <td>${self.formatCurrency(rowData.DEP_DINHEIRO)}</td>
                                <td>${self.formatCurrency(rowData.DEP_CHEQUE)}</td>
                                <td>${self.formatCurrency(rowData.REC_RETIRADA)}</td>
                                <td>${self.formatCurrency(rowData.SAQUE_CHEQUE)}</td>
                                <td>${self.escapeHtml(rowData.SEGUNDA_VIA_CARTAO)}</td>
                                <td>${self.escapeHtml(rowData.HOLERITE_INSS)}</td>
                                <td>${self.escapeHtml(rowData.CONS_INSS)}</td>
                                <td>${self.escapeHtml(rowData.PROVA_DE_VIDA)}</td>
                                <td>${self.formatDate(rowData.DATA_CONTRATO)}</td>
                                <td>${self.escapeHtml(rowData.TIPO_CONTRATO)}</td>
                                <td>${self.formatDate(rowData.DATA_LOG)}</td>
                                <td><span class="badge badge-info">${self.escapeHtml(rowData.FILTRO)}</span></td>
                            </tr>
                        `;
                    });
                    
                    console.log('Generated HTML length:', detailsHtml.length);
                    console.log('Sample HTML:', detailsHtml.substring(0, 200));
                    
                    if (recordCount > 0) {
                        tbody.html(detailsHtml);
                        
                        tbody.append(`
                            <tr class="info">
                                <td colspan="20" class="text-center">
                                    <strong>Total de ${recordCount} registro(s) processado(s) neste lote</strong>
                                </td>
                            </tr>
                        `);
                        
                        // Re-initialize checkbox module
                        setTimeout(() => {
                            HistoricoCheckboxModule.init();
                        }, 100);
                        
                    } else {
                        tbody.html('<tr><td colspan="20" class="text-center text-muted">Nenhum detalhe encontrado</td></tr>');
                    }
                    
                } else {
                    const errorMsg = $xml.find('e').text() || 'Erro desconhecido';
                    console.error('Server error:', errorMsg);
                    tbody.html(`<tr><td colspan="20" class="text-center text-danger">Erro: ${errorMsg}</td></tr>`);
                }
                
            } catch (e) {
                console.error('Error parsing XML response:', e);
                console.error('XML Data:', xmlData);
                tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro ao processar resposta XML</td></tr>');
            }
        })
        .fail((xhr, status, error) => {
            console.error('AJAX request failed:', status, error);
            console.error('Response text:', xhr.responseText);
            tbody.html('<tr><td colspan="20" class="text-center text-danger">Erro na requisição</td></tr>');
        })
        .always(() => {
            button.prop('disabled', false).html('<i class="fa fa-refresh"></i> Recarregar');
        });
}