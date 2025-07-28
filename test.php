<?php
/**
 * TXT Generator Modal Class
 * Compatible with PHP 5.3
 * Generates a modal for creating custom TXT files with multiple lines
 */
class TxtGeneratorModal {
    
    private $fields = array(
        'empresa' => 'Empresa',
        'codigoLoja' => 'Código Loja', 
        'codTransacao' => 'Código Transação',
        'meioPagamento' => 'Meio Pagamento',
        'valorMinimo' => 'Valor Mínimo',
        'valorMaximo' => 'Valor Máximo',
        'situacaoMeioPagamento' => 'Situação Meio Pagamento',
        'valorTotalMaxDiario' => 'Valor Total Max Diário',
        'TipoManutencao' => 'Tipo Manutenção',
        'quantidadeTotalMaxDiaria' => 'Quantidade Total Max Diária'
    );
    
    /**
     * Display the modal HTML
     */
    public function displayModal() {
        ?>
        <div id="txtGeneratorModal" class="modal modal-primary">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title">
                            <i class="fa fa-file-text"></i> Gerador de Arquivo TXT Personalizado
                        </h4>
                    </div>
                    <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="filename">Nome do Arquivo:</label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="filename" 
                                           name="filename" 
                                           value="arquivo_personalizado"
                                           placeholder="Digite o nome do arquivo">
                                    <small class="help-block">Arquivo será salvo como [nome].txt</small>
                                </div>
                            </div>
                            <div class="col-md-4" style="padding-top: 25px;">
                                <button type="button" class="btn btn-success btn-sm" onclick="addNewLine()">
                                    <i class="fa fa-plus"></i> Adicionar Linha
                                </button>
                                <button type="button" class="btn btn-warning btn-sm" onclick="clearAllLines()">
                                    <i class="fa fa-trash"></i> Limpar Tudo
                                </button>
                            </div>
                        </div>
                        
                        <div class="widget-header">
                            <span class="widget-caption">Linhas do Arquivo TXT</span>
                            <div class="widget-buttons">
                                <span class="badge" id="lineCounter">1</span>
                            </div>
                        </div>
                        
                        <div id="txtLinesContainer" class="widget-body">
                            <!-- First line will be added by JavaScript -->
                        </div>
                        
                        <div class="alert alert-info" style="margin-top: 15px;">
                            <i class="fa fa-info-circle"></i>
                            <strong>Dica:</strong> Cada linha representa uma entrada no arquivo TXT. Use o botão "Adicionar Linha" para criar múltiplas entradas.
                        </div>
                        
                        <?php if (isset($_SESSION['txt_message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['txt_message_type']; ?>">
                            <?php 
                            echo $_SESSION['txt_message']; 
                            unset($_SESSION['txt_message'], $_SESSION['txt_message_type']);
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-dismiss="modal">
                            <i class="fa fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-info" onclick="previewTXT()">
                            <i class="fa fa-eye"></i> Visualizar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="generateAndDownloadTXT()">
                            <i class="fa fa-download"></i> Gerar e Baixar TXT
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Preview Modal -->
        <div id="txtPreviewModal" class="modal modal-info">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title">
                            <i class="fa fa-eye"></i> Visualização do Arquivo TXT
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Conteúdo do Arquivo:</label>
                            <textarea id="txtPreviewContent" class="form-control" rows="15" readonly 
                                      style="font-family: 'Courier New', monospace; font-size: 12px; background-color: #f5f5f5;"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    Total de linhas: <span id="previewLineCount">0</span>
                                </small>
                            </div>
                            <div class="col-md-6 text-right">
                                <small class="text-muted">
                                    Caracteres por linha: 101
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" data-dismiss="modal">
                            <i class="fa fa-arrow-left"></i> Voltar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="downloadPreviewedTXT()">
                            <i class="fa fa-download"></i> Baixar Arquivo
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        var lineCounter = 0;
        var txtPreviewContent = '';
        
        // Field definitions in JavaScript
        var txtFields = {
            'empresa': 'Empresa',
            'codigoLoja': 'Código Loja',
            'codTransacao': 'Código Transação',
            'meioPagamento': 'Meio Pagamento',
            'valorMinimo': 'Valor Mínimo',
            'valorMaximo': 'Valor Máximo',
            'situacaoMeioPagamento': 'Situação Meio Pagamento',
            'valorTotalMaxDiario': 'Valor Total Max Diário',
            'TipoManutencao': 'Tipo Manutenção',
            'quantidadeTotalMaxDiaria': 'Quantidade Total Max Diária'
        };
        
        // Add first line when modal opens
        $(document).ready(function() {
            addNewLine();
        });
        
        function addNewLine() {
            lineCounter++;
            updateLineCounter();
            
            var fieldsHtml = '';
            for (var fieldKey in txtFields) {
                var fieldLabel = txtFields[fieldKey];
                fieldsHtml += `
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="${fieldKey}_${lineCounter}">${fieldLabel}:</label>
                            <input type="number" 
                                   class="form-control input-sm txt-field" 
                                   id="${fieldKey}_${lineCounter}" 
                                   name="${fieldKey}_${lineCounter}" 
                                   step="any"
                                   data-field="${fieldKey}"
                                   data-line="${lineCounter}"
                                   placeholder="Digite o valor numérico">
                        </div>
                    </div>
                `;
            }
            
            var lineHtml = `
                <div class="txt-line-container" data-line="${lineCounter}" style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 15px; background-color: #fafafa;">
                    <div class="row">
                        <div class="col-md-10">
                            <h5 style="margin-top: 0; color: #337ab7;">
                                <i class="fa fa-file-text-o"></i> Linha ${lineCounter}
                            </h5>
                        </div>
                        <div class="col-md-2 text-right">
                            <button type="button" class="btn btn-danger btn-xs" onclick="removeLine(${lineCounter})" ${lineCounter === 1 ? 'style="display:none;"' : ''}>
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        ${fieldsHtml}
                    </div>
                </div>
            `;
            
            $('#txtLinesContainer').append(lineHtml);
            
            // Auto-focus on first field of new line
            setTimeout(function() {
                $(`#empresa_${lineCounter}`).focus();
            }, 100);
        }
        
        function removeLine(lineNumber) {
            if ($('.txt-line-container').length <= 1) {
                alert('Deve haver pelo menos uma linha.');
                return;
            }
            
            $(`.txt-line-container[data-line="${lineNumber}"]`).fadeOut(300, function() {
                $(this).remove();
                updateLineCounter();
                renumberLines();
            });
        }
        
        function clearAllLines() {
            if (confirm('Tem certeza que deseja limpar todas as linhas?')) {
                $('#txtLinesContainer').empty();
                lineCounter = 0;
                addNewLine();
            }
        }
        
        function updateLineCounter() {
            var totalLines = $('.txt-line-container').length;
            $('#lineCounter').text(totalLines);
        }
        
        function renumberLines() {
            var counter = 1;
            $('.txt-line-container').each(function() {
                var $container = $(this);
                var oldLine = $container.data('line');
                
                // Update container
                $container.attr('data-line', counter);
                $container.find('h5').html(`<i class="fa fa-file-text-o"></i> Linha ${counter}`);
                
                // Update remove button
                $container.find('.btn-danger').attr('onclick', `removeLine(${counter})`);
                if (counter === 1) {
                    $container.find('.btn-danger').hide();
                } else {
                    $container.find('.btn-danger').show();
                }
                
                // Update input fields
                $container.find('.txt-field').each(function() {
                    var $input = $(this);
                    var fieldName = $input.data('field');
                    var newId = fieldName + '_' + counter;
                    var newName = fieldName + '_' + counter;
                    
                    $input.attr('id', newId);
                    $input.attr('name', newName);
                    $input.data('line', counter);
                    
                    // Update label
                    $input.closest('.form-group').find('label').attr('for', newId);
                });
                
                counter++;
            });
            
            lineCounter = counter - 1;
        }
        
        function collectTxtData() {
            var txtData = [];
            var isValid = true;
            var filename = $('#filename').val().trim();
            
            if (!filename) {
                alert('Nome do arquivo é obrigatório.');
                $('#filename').focus();
                return null;
            }
            
            $('.txt-line-container').each(function() {
                var lineNumber = $(this).data('line');
                var lineData = {};
                var hasData = false;
                
                $(this).find('.txt-field').each(function() {
                    var $field = $(this);
                    var fieldName = $field.data('field');
                    var value = $field.val().trim();
                    
                    if (value !== '') {
                        if (!$.isNumeric(value)) {
                            alert(`Linha ${lineNumber}: Campo "${$field.closest('.form-group').find('label').text().replace(':', '')}" deve ser numérico.`);
                            $field.focus();
                            isValid = false;
                            return false;
                        }
                        lineData[fieldName] = parseFloat(value);
                        hasData = true;
                    } else {
                        lineData[fieldName] = 0;
                    }
                });
                
                if (!isValid) return false;
                
                if (hasData) {
                    txtData.push(lineData);
                }
            });
            
            if (!isValid) return null;
            
            if (txtData.length === 0) {
                alert('Adicione pelo menos uma linha com dados válidos.');
                return null;
            }
            
            return {
                filename: filename,
                lines: txtData
            };
        }
        
        function previewTXT() {
            var data = collectTxtData();
            if (!data) return;
            
            var txtContent = generateTxtContent(data.lines);
            txtPreviewContent = txtContent;
            
            $('#txtPreviewContent').val(txtContent);
            $('#previewLineCount').text(data.lines.length);
            
            $('#txtGeneratorModal').modal('hide');
            $('#txtPreviewModal').modal('show');
        }
        
        function generateAndDownloadTXT() {
            var data = collectTxtData();
            if (!data) return;
            
            var txtContent = generateTxtContent(data.lines);
            var filename = data.filename;
            
            if (!filename.toLowerCase().endsWith('.txt')) {
                filename += '.txt';
            }
            
            downloadTXTFile(txtContent, filename);
            
            // Show success message
            showSuccessMessage(`Arquivo "${filename}" gerado com sucesso!\\nTotal de linhas: ${data.lines.length}`);
            
            $('#txtGeneratorModal').modal('hide');
        }
        
        function downloadPreviewedTXT() {
            var filename = $('#filename').val().trim();
            if (!filename.toLowerCase().endsWith('.txt')) {
                filename += '.txt';
            }
            
            downloadTXTFile(txtPreviewContent, filename);
            showSuccessMessage(`Arquivo "${filename}" baixado com sucesso!`);
            
            $('#txtPreviewModal').modal('hide');
        }
        
        function generateTxtContent(lines) {
            var txtContent = '';
            
            lines.forEach(function(line) {
                var txtLine = formatToTXTLine(
                    line.empresa || 0,
                    line.codigoLoja || 0,
                    line.codTransacao || 0,
                    line.meioPagamento || 0,
                    line.valorMinimo || 0,
                    line.valorMaximo || 0,
                    line.situacaoMeioPagamento || 0,
                    line.valorTotalMaxDiario || 0,
                    line.TipoManutencao || 0,
                    line.quantidadeTotalMaxDiaria || 0
                );
                txtContent += txtLine + '\\r\\n';
            });
            
            return txtContent;
        }
        
        // Utility functions (copied from your existing code)
        function formatToTXTLine(empresa, codigoLoja, codTransacao, meioPagamento, valorMinimo, valorMaximo, situacaoMeioPagamento, valorTotalMaxDiario, tipoManutencao, quantidadeTotalMaxDiaria) {
            var empresaTXT = padLeft(cleanNumeric(empresa), 10, '0');
            var codigoLojaTXT = padLeft(cleanNumeric(codigoLoja), 5, '0');
            var fixo = padRight("", 10, ' ');
            var codTransacaoTXT = padLeft(cleanNumeric(codTransacao), 5, '0');
            var meioPagamTXT = padLeft(cleanNumeric(meioPagamento), 2, '0');
            var valorMinTXT = padLeft(cleanNumeric(valorMinimo), 17, '0');
            var valorMaxTXT = padLeft(cleanNumeric(valorMaximo), 17, '0');
            var sitMeioPTXT = padLeft(cleanNumeric(situacaoMeioPagamento), 1, '0');
            var valorTotalMaxTXT = padLeft(cleanNumeric(valorTotalMaxDiario), 18, '0');
            var tipoManutTXT = padLeft(cleanNumeric(tipoManutencao), 1, '0');
            var quantTotalMaxTXT = padLeft(cleanNumeric(quantidadeTotalMaxDiaria), 15, '0');

            var linha = empresaTXT + codigoLojaTXT + fixo + codTransacaoTXT + meioPagamTXT + 
                       valorMinTXT + valorMaxTXT + sitMeioPTXT + valorTotalMaxTXT + 
                       tipoManutTXT + quantTotalMaxTXT;

            if (linha.length > 101) {
                return linha.substring(0, 101);
            } else if (linha.length < 101) {
                return padRight(linha, 101, ' ');
            }
            return linha;
        }
        
        function cleanNumeric(value) {
            return String(value).replace(/[^0-9]/g, '') || '0';
        }
        
        function padLeft(str, length, char) {
            str = String(str);
            while (str.length < length) {
                str = char + str;
            }
            return str.length > length ? str.slice(-length) : str;
        }
        
        function padRight(str, length, char) {
            str = String(str);
            while (str.length < length) {
                str = str + char;
            }
            return str.substring(0, length);
        }
        
        function downloadTXTFile(txtContent, filename) {
            var txtWithBOM = '\\uFEFF' + txtContent;
            var blob = new Blob([txtWithBOM], { type: 'text/plain;charset=utf-8;' });
            downloadFile(blob, filename);
        }
        
        function downloadFile(blob, filename) {
            var link = document.createElement('a');
            if (link.download !== undefined) {
                var url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', filename);
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                setTimeout(function() {
                    URL.revokeObjectURL(url);
                }, 1000);
            } else {
                alert('Seu navegador não suporta download automático.');
            }
        }
        
        function showSuccessMessage(message) {
            var alertHtml = `
                <div class="alert alert-success success-alert" style="
                    position: fixed; 
                    top: 50%; 
                    left: 50%; 
                    transform: translate(-50%, -50%); 
                    z-index: 9999; 
                    min-width: 400px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    border-radius: 5px;
                ">
                    <button class="close" onclick="$(this).parent().remove()" style="
                        color: #3c763d !important; 
                        opacity: 0.7; 
                        position: absolute; 
                        top: 10px; 
                        right: 15px;
                    ">
                        <i class="fa fa-times"></i>
                    </button>
                    <div style="padding: 20px 40px 20px 20px;">
                        <i class="fa fa-check-circle" style="color: #3c763d; font-size: 20px; margin-right: 10px;"></i>
                        <strong>Sucesso!</strong>
                        <pre style="background: none; border: none; color: #3c763d; margin-top: 10px; white-space: pre-wrap;">${message}</pre>
                    </div>
                </div>
            `;
            
            $('body').append(alertHtml);
            
            setTimeout(function() {
                $('.success-alert').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        function openTxtGeneratorModal() {
            $('#txtGeneratorModal').modal('show');
        }
        </script>
        <?php
    }
    
    /**
     * Display trigger button for the modal
     */
    public function displayTriggerButton() {
        ?>
        <button type="button" class="btn btn-success btn-sm" onclick="openTxtGeneratorModal()" style="margin-bottom:10px;position:relative; left:20px;">
            <i class="fa fa-file-text"></i> Gerar TXT Personalizado
        </button>
        <?php
    }
    
    /**
     * Initialize the class
     */
    public function init() {
        if (!isset($_SESSION)) {
            session_start();
        }
        // No server-side processing needed anymore - everything is handled by JavaScript
    }
}

// Usage example:
/*
$txtGenerator = new TxtGeneratorModal();
$txtGenerator->init();

// Display the button and modal somewhere in your HTML
$txtGenerator->displayTriggerButton();
$txtGenerator->displayModal();
*/
?>