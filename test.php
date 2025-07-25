<?php
/**
 * TxtModal - MVC Compatible Version
 * 
 * Works with custom database classes and MVC structures
 * Supports: Custom MSSQL class, Model objects, etc.
 * 
 * Usage:
 * 1. Include this file: require_once 'TxtModal.php';
 * 2. Initialize: $txtModal = new TxtModal($this->model);
 * 3. Add to controller: $txtModal->render();
 * 4. Replace export button onclick: TxtModal.show()
 */

class TxtModal {
    
    private $model;
    private $config;
    
    /**
     * Constructor
     * @param mixed $model Your model object with database connection
     * @param array $config Configuration options
     */
    public function __construct($model = null, $config = array()) {
        $this->model = $model;
        
        // Default configuration
        $this->config = array_merge(array(
            'table_name' => 'your_table',
            'historico_table' => 'your_historico_table',
            'id_field' => 'id',
            'chave_lote_field' => 'chave_lote',
            'ajax_action' => 'txtModal',
            'debug' => false
        ), $config);
        
        // Handle AJAX requests automatically
        $this->handleAjax();
    }
    
    /**
     * Set model
     */
    public function setModel($model) {
        $this->model = $model;
    }
    
    /**
     * Set configuration
     */
    public function setConfig($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Handle AJAX requests automatically
     */
    private function handleAjax() {
        if (isset($_GET['action']) && $_GET['action'] === $this->config['ajax_action']) {
            $this->processAjaxRequest();
            exit;
        }
    }
    
    /**
     * Process AJAX request and return XML data
     */
    private function processAjaxRequest() {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<response>';
        
        try {
            $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            $ids = isset($_GET['ids']) ? $_GET['ids'] : '';
            
            if (empty($ids)) {
                echo '<success>false</success>';
                echo '<e>Nenhum ID fornecido</e>';
                echo '</response>';
                return;
            }
            
            $data = $this->fetchData($filter, $ids);
            
            if ($data !== false && is_array($data)) {
                echo '<success>true</success>';
                echo '<data>';
                
                foreach ($data as $row) {
                    echo '<row>';
                    foreach ($row as $key => $value) {
                        echo '<' . strtolower($key) . '>' . htmlspecialchars($value) . '</' . strtolower($key) . '>';
                    }
                    echo '</row>';
                }
                
                echo '</data>';
            } else {
                echo '<success>false</success>';
                echo '<e>Erro ao consultar dados</e>';
            }
            
        } catch (Exception $e) {
            echo '<success>false</success>';
            echo '<e>' . htmlspecialchars($e->getMessage()) . '</e>';
        }
        
        echo '</response>';
    }
    
    /**
     * Fetch data from database via model
     * Override this method to customize data fetching
     */
    protected function fetchData($filter, $ids) {
        if (!$this->model) {
            throw new Exception('Model not set');
        }
        
        // Try to use model method if it exists
        if (method_exists($this->model, 'getTxtModalData')) {
            return $this->model->getTxtModalData($filter, $ids);
        }
        
        // Fallback to direct SQL execution
        return $this->executeDirectSQL($filter, $ids);
    }
    
    /**
     * Execute SQL directly via model's SQL connection
     */
    private function executeDirectSQL($filter, $ids) {
        $idsArray = explode(',', $ids);
        $idsArray = array_map('intval', $idsArray);
        $idsString = implode(',', $idsArray);
        
        if ($filter === 'historico') {
            $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja, 
                           avancado, presenca, unidade_negocio, orgao_pagador,
                           tipo_contrato, data_conclusao, tipo_correspondente
                    FROM " . $this->config['historico_table'] . " 
                    WHERE " . $this->config['chave_lote_field'] . " IN ($idsString)";
        } else {
            $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja,
                           avancado, presenca, unidade_negocio, orgao_pagador,
                           tipo_contrato
                    FROM " . $this->config['table_name'] . " 
                    WHERE " . $this->config['id_field'] . " IN ($idsString)";
        }
        
        return $this->executeQuery($sql);
    }
    
    /**
     * Execute database query - works with various model types
     */
    private function executeQuery($sql) {
        if ($this->config['debug']) {
            error_log("TxtModal SQL: " . $sql);
        }
        
        $result = array();
        
        try {
            // Check if model has sql property (like your MSSQL class)
            if (isset($this->model->sql)) {
                $connection = $this->model->sql;
                
                // Try common methods for custom database classes
                if (method_exists($connection, 'query')) {
                    $queryResult = $connection->query($sql);
                    if (is_array($queryResult)) {
                        return $queryResult;
                    }
                } elseif (method_exists($connection, 'fetch_all')) {
                    $queryResult = $connection->fetch_all($sql);
                    if (is_array($queryResult)) {
                        return $queryResult;
                    }
                } elseif (method_exists($connection, 'get_results')) {
                    $queryResult = $connection->get_results($sql);
                    if (is_array($queryResult)) {
                        // Convert objects to arrays if needed
                        foreach ($queryResult as $row) {
                            if (is_object($row)) {
                                $result[] = (array) $row;
                            } else {
                                $result[] = $row;
                            }
                        }
                        return $result;
                    }
                }
            }
            
            // Try if model itself has query methods
            if (method_exists($this->model, 'query')) {
                $queryResult = $this->model->query($sql);
                if (is_array($queryResult)) {
                    return $queryResult;
                }
            }
            
            // Try if model has executeQuery method
            if (method_exists($this->model, 'executeQuery')) {
                $queryResult = $this->model->executeQuery($sql);
                if (is_array($queryResult)) {
                    return $queryResult;
                }
            }
            
            // If model is actually a direct connection object
            if (is_resource($this->model)) {
                // MySQL resource
                $query_result = mysql_query($sql, $this->model);
                if ($query_result) {
                    while ($row = mysql_fetch_assoc($query_result)) {
                        $result[] = $row;
                    }
                    mysql_free_result($query_result);
                }
            } elseif ($this->model instanceof mysqli) {
                // MySQLi
                $query_result = $this->model->query($sql);
                if ($query_result) {
                    while ($row = $query_result->fetch_assoc()) {
                        $result[] = $row;
                    }
                    $query_result->free();
                }
            } elseif ($this->model instanceof PDO) {
                // PDO
                $stmt = $this->model->query($sql);
                if ($stmt) {
                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            if ($this->config['debug']) {
                error_log("TxtModal Query Error: " . $e->getMessage());
            }
            throw $e;
        }
        
        return false;
    }
    
    /**
     * Render the complete modal and JavaScript
     * Call this once in your controller/view where you want the modal to be available
     */
    public function render() {
        $this->renderModal();
        $this->renderJavaScript();
        $this->renderCSS();
    }
    
    /**
     * Render just the modal HTML
     */
    public function renderModal() {
        ?>
        <div class="modal fade" id="txtGenerationModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            <i class="fa fa-file-text-o"></i> Geração de TXT - Seleção de Registros
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="row" style="margin-bottom: 15px;">
                            <div class="col-md-6">
                                <label>
                                    <input type="checkbox" id="selectAllTxtModal" class="form-check-input"> 
                                    Selecionar Todos
                                </label>
                            </div>
                            <div class="col-md-6 text-right">
                                <span class="badge badge-info" id="selectedCountTxt">0 selecionados</span>
                            </div>
                        </div>
                        
                        <div id="txtModalLoading" class="text-center" style="padding: 40px; display: none;">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p>Carregando dados...</p>
                        </div>
                        
                        <div id="txtModalError" class="alert alert-danger" style="display: none;">
                            <i class="fa fa-exclamation-triangle"></i>
                            <span id="txtModalErrorMessage">Erro ao carregar dados</span>
                        </div>
                        
                        <div id="txtModalContent" style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="headerSelectAll" class="form-check-input">
                                        </th>
                                        <th>Código Empresa</th>
                                        <th>Código Loja</th>
                                        <th>Nome Loja</th>
                                        <th>Tipo Correspondente</th>
                                    </tr>
                                </thead>
                                <tbody id="txtModalTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="generateTxtBtn" disabled>
                            <i class="fa fa-download"></i> Gerar TXT (<span id="selectedCountFooter">0</span>)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the JavaScript
     */
    public function renderJavaScript() {
        $ajaxAction = $this->config['ajax_action'];
        ?>
        <script type="text/javascript">
        window.TxtModal = {
            currentData: [],
            selectedIds: [],
            currentFilter: '',
            
            show: function(selectedIds, filter) {
                // Auto-detect parameters if not provided
                if (!selectedIds) {
                    var currentFilter = (typeof getCurrentFilter === 'function') ? getCurrentFilter() : 'all';
                    if (currentFilter === 'historico' && typeof HistoricoCheckboxModule !== 'undefined') {
                        selectedIds = HistoricoCheckboxModule.getSelectedIds();
                    } else if (typeof CheckboxModule !== 'undefined') {
                        selectedIds = CheckboxModule.getSelectedIds();
                    } else {
                        selectedIds = this.getSelectedIds();
                    }
                    filter = currentFilter;
                }
                
                if (!selectedIds || selectedIds.length === 0) {
                    alert('Selecione pelo menos um registro');
                    return;
                }
                
                this.selectedIds = selectedIds;
                this.currentFilter = filter || 'all';
                
                $('#txtGenerationModal').modal('show');
                this.loadData();
            },
            
            getSelectedIds: function() {
                var ids = [];
                $('.row-checkbox:checked, .historico-lote-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                return ids;
            },
            
            loadData: function() {
                var self = this;
                
                $('#txtModalLoading').show();
                $('#txtModalContent').hide();
                $('#txtModalError').hide();
                
                var idsParam = this.selectedIds.join(',');
                var url = '?action=<?php echo $ajaxAction; ?>&filter=' + this.currentFilter + '&ids=' + encodeURIComponent(idsParam);
                
                $.get(url)
                    .done(function(xmlData) {
                        try {
                            var $xml = $(xmlData);
                            var success = $xml.find('success').text() === 'true';
                            
                            if (success) {
                                self.populateModal($xml);
                            } else {
                                var errorMsg = $xml.find('e').text() || 'Erro ao carregar dados';
                                self.showError(errorMsg);
                            }
                        } catch (e) {
                            console.error('Error parsing XML:', e);
                            self.showError('Erro ao processar resposta do servidor');
                        }
                    })
                    .fail(function(xhr, status, error) {
                        console.error('AJAX failed:', status, error);
                        self.showError('Erro na comunicação com o servidor: ' + error);
                    })
                    .always(function() {
                        $('#txtModalLoading').hide();
                    });
            },
            
            populateModal: function($xml) {
                var self = this;
                this.currentData = [];
                var tableHtml = '';
                
                $xml.find('row').each(function(index) {
                    var row = {};
                    
                    $(this).children().each(function() {
                        row[this.tagName] = $(this).text();
                    });
                    
                    row.tipo_correspondente = self.getTipoCorrespondente(row);
                    self.currentData.push(row);
                    
                    var rowId = 'txtrow_' + index;
                    tableHtml += '<tr>' +
                        '<td><label><input type="checkbox" class="form-check-input record-checkbox" value="' + index + '" id="' + rowId + '" data-index="' + index + '" checked><span class="text"></span></label></td>' +
                        '<td><strong>' + self.escapeHtml(row.cod_empresa) + '</strong></td>' +
                        '<td><strong>' + self.escapeHtml(row.cod_loja) + '</strong></td>' +
                        '<td>' + self.escapeHtml(row.nome_loja) + '</td>' +
                        '<td><span class="badge ' + self.getTipoBadgeClass(row.tipo_correspondente) + '">' + row.tipo_correspondente + '</span></td>' +
                        '</tr>';
                });
                
                $('#txtModalTableBody').html(tableHtml);
                $('#txtModalContent').show();
                
                $('#selectAllTxtModal, #headerSelectAll').prop('checked', true);
                this.updateSelectedCount();
            },
            
            getTipoCorrespondente: function(row) {
                var tipoCampos = {
                    'avancado': 'AV',
                    'presenca': 'PR', 
                    'unidade_negocio': 'UN',
                    'orgao_pagador': 'OP'
                };
                
                var cutoff = new Date(2025, 5, 1);
                var mostRecentDate = null;
                var mostRecentType = '';
                
                if (row.tipo_correspondente && row.tipo_correspondente !== '') {
                    return row.tipo_correspondente;
                }
                
                for (var campo in tipoCampos) {
                    var raw = row[campo];
                    if (raw) {
                        var date = this.parseDate(raw);
                        if (date && date > cutoff) {
                            if (mostRecentDate === null || date > mostRecentDate) {
                                mostRecentDate = date;
                                mostRecentType = tipoCampos[campo];
                            }
                        }
                    }
                }
                
                return mostRecentType || 'N/A';
            },
            
            parseDate: function(dateString) {
                if (!dateString || typeof dateString !== 'string') return null;
                
                var parts = dateString.trim().split('/');
                if (parts.length !== 3) return null;
                
                var day = parseInt(parts[0], 10);
                var month = parseInt(parts[1], 10) - 1;
                var year = parseInt(parts[2], 10);
                
                if (isNaN(day) || isNaN(month) || isNaN(year)) return null;
                
                return new Date(year, month, day);
            },
            
            getTipoBadgeClass: function(tipo) {
                var classes = {
                    'AV': 'badge-success',
                    'PR': 'badge-info',
                    'UN': 'badge-warning',
                    'OP': 'badge-primary'
                };
                return classes[tipo] || 'badge-default';
            },
            
            updateSelectedCount: function() {
                var checkedCount = $('.record-checkbox:checked').length;
                $('#selectedCountTxt').text(checkedCount + ' selecionados');
                $('#selectedCountFooter').text(checkedCount);
                $('#generateTxtBtn').prop('disabled', checkedCount === 0);
            },
            
            generateTXT: function() {
                var self = this;
                var selectedIndexes = [];
                
                $('.record-checkbox:checked').each(function() {
                    selectedIndexes.push(parseInt($(this).data('index')));
                });
                
                if (selectedIndexes.length === 0) {
                    alert('Selecione pelo menos um registro');
                    return;
                }
                
                var txtContent = '';
                
                selectedIndexes.forEach(function(index) {
                    var row = self.currentData[index];
                    var empresa = row.cod_empresa;
                    var codigoLoja = row.cod_loja;
                    var tipoCorrespondente = row.tipo_correspondente;
                    
                    if (['AV', 'PR', 'UN'].includes(tipoCorrespondente)) {
                        var limits;
                        if (tipoCorrespondente === 'AV' || tipoCorrespondente === 'UN') {
                            limits = { dinheiro: '1000000', cheque: '1000000', retirada: '350000', saque: '350000' };
                        } else if (tipoCorrespondente === 'PR') {
                            limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                        }

                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 19, '01', 500, limits.dinheiro, 1, 0, 2, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 19, '02', 500, limits.cheque, 1, 0, 2, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, limits.retirada, 1, 0, 2, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 2, 0) + '\r\n';
                        
                    } else if (tipoCorrespondente === 'OP') {
                        var limits = { dinheiro: '300000', cheque: '500000', retirada: '200000', saque: '200000' };
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 1, 0) + '\r\n';
                    }
                });
                
                if (txtContent) {
                    var filename = 'custom_txt_' + this.getCurrentTimestamp() + '.txt';
                    this.downloadTXTFile(txtContent, filename);
                    
                    $('#txtGenerationModal').modal('hide');
                    this.showSuccessMessage('TXT gerado com sucesso!\n' + selectedIndexes.length + ' registros processados.');
                } else {
                    alert('Erro ao gerar conteúdo TXT');
                }
            },
            
            formatToTXTLine: function(empresa, codigoLoja, codTransacao, meioPagamento, valorMinimo, valorMaximo, situacaoMeioPagamento, valorTotalMaxDiario, tipoManutencao, quantidadeTotalMaxDiaria) {
                var empresaTXT = this.padLeft(this.cleanNumeric(empresa), 10, '0'); 
                var codigoLojaTXT = this.padLeft(this.cleanNumeric(codigoLoja), 5, '0'); 
                var fixo = this.padRight("", 10, ' '); 
                var codTransacaoTXT = this.padLeft(this.cleanNumeric(codTransacao), 5, '0'); 
                var meioPagamTXT = this.padLeft(this.cleanNumeric(meioPagamento), 2, '0'); 
                var valorMinTXT = this.padLeft(this.cleanNumeric(valorMinimo), 17, '0'); 
                var valorMaxTXT = this.padLeft(this.cleanNumeric(valorMaximo), 17, '0'); 
                var sitMeioPTXT = this.padLeft(this.cleanNumeric(situacaoMeioPagamento), 1, '0'); 
                var valorTotalMaxTXT = this.padLeft(this.cleanNumeric(valorTotalMaxDiario), 18, '0'); 
                var tipoManutTXT = this.padLeft(this.cleanNumeric(tipoManutencao), 1, '0'); 
                var quantTotalMaxTXT = this.padLeft(this.cleanNumeric(quantidadeTotalMaxDiaria), 15, '0'); 

                var linha = empresaTXT + codigoLojaTXT + fixo + codTransacaoTXT + meioPagamTXT + 
                    valorMinTXT + valorMaxTXT + sitMeioPTXT + valorTotalMaxTXT + 
                    tipoManutTXT + quantTotalMaxTXT;

                if (linha.length > 101) {
                    return linha.substring(0, 101);
                } else if (linha.length < 101) {
                    return this.padRight(linha, 101, ' ');
                }

                return linha;
            },
            
            cleanNumeric: function(value) {
                return String(value).replace(/[^0-9]/g, '') || '0';
            },
            
            padLeft: function(str, length, char) {
                str = String(str);
                while (str.length < length) {
                    str = char + str;
                }
                return str.length > length ? str.slice(-length) : str;
            },
            
            padRight: function(str, length, char) {
                str = String(str);
                while (str.length < length) {
                    str = str + char;
                }
                return str.substring(0, length);
            },
            
            escapeHtml: function(text) {
                if (!text) return '';
                var div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            },
            
            downloadTXTFile: function(txtContent, filename) {
                var txtWithBOM = '\uFEFF' + txtContent;
                var blob = new Blob([txtWithBOM], { type: 'text/plain;charset=utf-8;' });
                
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
                }
            },
            
            getCurrentTimestamp: function() {
                var now = new Date();
                var year = now.getFullYear();
                var month = String(now.getMonth() + 1).padStart(2, '0');
                var day = String(now.getDate()).padStart(2, '0');
                var hours = String(now.getHours()).padStart(2, '0');
                var minutes = String(now.getMinutes()).padStart(2, '0');
                var seconds = String(now.getSeconds()).padStart(2, '0');
                
                return year + '-' + month + '-' + day + '_' + hours + '-' + minutes + '-' + seconds;
            },
            
            showError: function(message) {
                $('#txtModalErrorMessage').text(message);
                $('#txtModalError').show();
                $('#txtModalContent').hide();
            },
            
            showSuccessMessage: function(message) {
                var alertHtml = '<div class="alert alert-success" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10000; min-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">' +
                    '<button class="close" onclick="$(this).parent().remove()" style="color: #3c763d !important; opacity: 0.7; position: absolute; top: 10px; right: 15px;"><i class="fa fa-times"></i></button>' +
                    '<div style="padding: 20px 40px 20px 20px;"><i class="fa fa-check-circle" style="color: #3c763d; font-size: 20px; margin-right: 10px;"></i><strong>Sucesso!</strong>' +
                    '<pre style="background: none; border: none; color: #3c763d; margin-top: 10px; white-space: pre-wrap;">' + message + '</pre></div></div>';
                
                $('body').append(alertHtml);
                
                setTimeout(function() {
                    $('.alert-success').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        };
        
        // Auto-attach event listeners when DOM is ready
        $(document).ready(function() {
            $(document).on('change', '#selectAllTxtModal, #headerSelectAll', function() {
                var isChecked = $(this).is(':checked');
                $('.record-checkbox').prop('checked', isChecked);
                TxtModal.updateSelectedCount();
            });
            
            $(document).on('change', '.record-checkbox', function() {
                var totalCheckboxes = $('.record-checkbox').length;
                var checkedCheckboxes = $('.record-checkbox:checked').length;
                
                var selectAllCheckboxes = $('#selectAllTxtModal, #headerSelectAll');
                
                if (checkedCheckboxes === 0) {
                    selectAllCheckboxes.prop('indeterminate', false).prop('checked', false);
                } else if (checkedCheckboxes === totalCheckboxes) {
                    selectAllCheckboxes.prop('indeterminate', false).prop('checked', true);
                } else {
                    selectAllCheckboxes.prop('indeterminate', true).prop('checked', false);
                }
                
                TxtModal.updateSelectedCount();
            });
            
            $(document).on('click', '#generateTxtBtn', function() {
                TxtModal.generateTXT();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render additional CSS
     */
    public function renderCSS() {
        ?>
        <style type="text/css">
        #txtGenerationModal .badge-success { background-color: #5cb85c; }
        #txtGenerationModal .badge-info { background-color: #5bc0de; }
        #txtGenerationModal .badge-warning { background-color: #f0ad4e; }
        #txtGenerationModal .badge-primary { background-color: #337ab7; }
        #txtGenerationModal .badge-default { background-color: #777; }
        #txtGenerationModal .form-check-input { margin-right: 8px; }
        #txtGenerationModal .table th { background-color: #f5f5f5; }
        #txtGenerationModal .modal-lg { max-width: 900px; }
        </style>
        <?php
    }
}
?>