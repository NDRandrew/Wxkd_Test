<?php
/**
 * TxtModal - Manual Form Version
 * 
 * Simple modal where users manually input all data for TXT generation
 * No database connection needed - pure form-based input
 * 
 * Usage:
 * 1. Include: require_once 'TxtModal.php';
 * 2. Initialize: $txtModal = new TxtModal();
 * 3. Render: $txtModal->render();
 * 4. Show modal: TxtModal.show()
 */

class TxtModal {
    
    private $config;
    
    /**
     * Constructor
     * @param array $config Configuration options
     */
    public function __construct($config = array()) {
        // Ensure config is an array
        if (!is_array($config)) {
            $config = array();
        }
        
        // Default configuration
        $defaults = array(
            'modal_title' => 'Geração Manual de TXT',
            'max_records' => 50, // Maximum number of records user can add
            'default_limits' => array(
                'AV' => array('dinheiro' => '1000000', 'cheque' => '1000000', 'retirada' => '350000', 'saque' => '350000'),
                'UN' => array('dinheiro' => '1000000', 'cheque' => '1000000', 'retirada' => '350000', 'saque' => '350000'),
                'PR' => array('dinheiro' => '300000', 'cheque' => '500000', 'retirada' => '200000', 'saque' => '200000'),
                'OP' => array('dinheiro' => '300000', 'cheque' => '500000', 'retirada' => '200000', 'saque' => '200000')
            )
        );
        
        $this->config = array_merge($defaults, $config);
    }
    
    /**
     * Set configuration
     */
    public function setConfig($key, $value) {
        $this->config[$key] = $value;
    }
    
    /**
     * Render the complete modal and JavaScript
     */
    public function render() {
        $this->renderModal();
        $this->renderJavaScript();
        $this->renderCSS();
    }
    
    /**
     * Render the modal HTML
     */
    public function renderModal() {
        ?>
        <div class="modal fade" id="txtManualModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            <i class="fa fa-edit"></i> <?php echo $this->config['modal_title']; ?>
                        </h4>
                    </div>
                    <div class="modal-body">
                        
                        <!-- Add New Record Section -->
                        <div class="panel panel-primary">
                            <div class="panel-heading">
                                <h5 class="panel-title"><i class="fa fa-plus"></i> Adicionar Novo Registro</h5>
                            </div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="newCodEmpresa">Código Empresa *</label>
                                            <input type="text" class="form-control" id="newCodEmpresa" placeholder="Ex: 1234567890" maxlength="10">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="newCodLoja">Código Loja *</label>
                                            <input type="text" class="form-control" id="newCodLoja" placeholder="Ex: 12345" maxlength="5">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="newNomeLoja">Nome Loja</label>
                                            <input type="text" class="form-control" id="newNomeLoja" placeholder="Nome da loja (opcional)">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-primary btn-block" id="addRecordBtn">
                                                <i class="fa fa-plus"></i> Adicionar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tipo Correspondente Selection -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <label>Tipo de Correspondente *</label>
                                        <div class="checkbox-group" style="margin-top: 10px;">
                                            <label class="checkbox-inline">
                                                <input type="radio" name="tipoCorrespondente" value="AV" id="tipo_av"> 
                                                <span class="badge badge-success">AV</span> Avançado
                                            </label>
                                            <label class="checkbox-inline">
                                                <input type="radio" name="tipoCorrespondente" value="PR" id="tipo_pr"> 
                                                <span class="badge badge-info">PR</span> Presença
                                            </label>
                                            <label class="checkbox-inline">
                                                <input type="radio" name="tipoCorrespondente" value="UN" id="tipo_un"> 
                                                <span class="badge badge-warning">UN</span> Unidade Negócio
                                            </label>
                                            <label class="checkbox-inline">
                                                <input type="radio" name="tipoCorrespondente" value="OP" id="tipo_op"> 
                                                <span class="badge badge-primary">OP</span> Órgão Pagador
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Services Selection (only for OP) -->
                                <div id="opServicesSection" style="display: none; margin-top: 15px;">
                                    <label>Serviços OP (Órgão Pagador)</label>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="op_holerite" checked> Holerite INSS
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="op_consulta" checked> Consulta INSS
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="op_segunda_via"> Segunda Via Cartão
                                            </label>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="checkbox-inline">
                                                <input type="checkbox" id="op_saque" checked> Saque Cheque
                                            </label>
                                        </div>
                                    </div>
                                    <small class="text-muted">Segunda Via Cartão é adicionada automaticamente para contratos > v10.1</small>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Records List -->
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h5 class="panel-title">
                                    <i class="fa fa-list"></i> Registros Adicionados 
                                    <span class="badge" id="recordCount">0</span>
                                </h5>
                                <div class="pull-right">
                                    <button type="button" class="btn btn-xs btn-danger" id="clearAllBtn" style="margin-top: -5px;">
                                        <i class="fa fa-trash"></i> Limpar Todos
                                    </button>
                                </div>
                            </div>
                            <div class="panel-body">
                                <div id="recordsList">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> 
                                        Nenhum registro adicionado ainda. Use o formulário acima para adicionar registros.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advanced Options -->
                        <div class="panel panel-default">
                            <div class="panel-heading" data-toggle="collapse" data-target="#advancedOptions">
                                <h5 class="panel-title">
                                    <i class="fa fa-cog"></i> Opções Avançadas 
                                    <i class="fa fa-chevron-down pull-right"></i>
                                </h5>
                            </div>
                            <div id="advancedOptions" class="panel-collapse collapse">
                                <div class="panel-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6><strong>Limites Personalizados</strong></h6>
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" id="customLimits"> Usar limites personalizados
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6><strong>Formato de Arquivo</strong></h6>
                                            <select class="form-control" id="fileFormat">
                                                <option value="txt">TXT (Padrão)</option>
                                                <option value="txt_with_bom">TXT com BOM</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary" id="generateManualTxtBtn" disabled>
                            <i class="fa fa-download"></i> Gerar TXT (<span id="totalRecords">0</span> registros)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render JavaScript
     */
    public function renderJavaScript() {
        // Convert PHP array to JavaScript object (PHP 5.3 compatible)
        $defaultLimits = '{';
        foreach ($this->config['default_limits'] as $type => $limits) {
            $defaultLimits .= '"' . $type . '": {';
            foreach ($limits as $key => $value) {
                $defaultLimits .= '"' . $key . '": "' . $value . '",';
            }
            $defaultLimits = rtrim($defaultLimits, ',') . '},';
        }
        $defaultLimits = rtrim($defaultLimits, ',') . '}';
        
        $maxRecords = $this->config['max_records'];
        ?>
        <script type="text/javascript">
        window.TxtModal = {
            records: [],
            defaultLimits: <?php echo $defaultLimits; ?>,
            maxRecords: <?php echo $maxRecords; ?>,
            
            show: function() {
                $('#txtManualModal').modal('show');
                this.updateUI();
            },
            
            addRecord: function() {
                var codEmpresa = $('#newCodEmpresa').val().trim();
                var codLoja = $('#newCodLoja').val().trim();
                var nomeLoja = $('#newNomeLoja').val().trim() || 'Loja ' + codEmpresa + '-' + codLoja;
                var tipoCorrespondente = $('input[name="tipoCorrespondente"]:checked').val();
                
                // Validation
                if (!codEmpresa) {
                    alert('Código da Empresa é obrigatório');
                    $('#newCodEmpresa').focus();
                    return;
                }
                
                if (!codLoja) {
                    alert('Código da Loja é obrigatório');
                    $('#newCodLoja').focus();
                    return;
                }
                
                if (!tipoCorrespondente) {
                    alert('Selecione o Tipo de Correspondente');
                    return;
                }
                
                if (codEmpresa.length > 10) {
                    alert('Código da Empresa deve ter no máximo 10 dígitos');
                    return;
                }
                
                if (codLoja.length > 5) {
                    alert('Código da Loja deve ter no máximo 5 dígitos');
                    return;
                }
                
                if (this.records.length >= this.maxRecords) {
                    alert('Máximo de ' + this.maxRecords + ' registros permitidos');
                    return;
                }
                
                // Check for duplicates
                var duplicate = this.records.find(function(record) {
                    return record.codEmpresa === codEmpresa && record.codLoja === codLoja;
                });
                
                if (duplicate) {
                    alert('Este registro já foi adicionado (Empresa: ' + codEmpresa + ', Loja: ' + codLoja + ')');
                    return;
                }
                
                // Collect OP services if OP type
                var opServices = {};
                if (tipoCorrespondente === 'OP') {
                    opServices = {
                        holerite: $('#op_holerite').is(':checked'),
                        consulta: $('#op_consulta').is(':checked'),
                        segundaVia: $('#op_segunda_via').is(':checked'),
                        saque: $('#op_saque').is(':checked')
                    };
                }
                
                // Add record
                var record = {
                    id: Date.now(),
                    codEmpresa: codEmpresa,
                    codLoja: codLoja,
                    nomeLoja: nomeLoja,
                    tipoCorrespondente: tipoCorrespondente,
                    opServices: opServices
                };
                
                this.records.push(record);
                
                // Clear form
                $('#newCodEmpresa, #newCodLoja, #newNomeLoja').val('');
                $('input[name="tipoCorrespondente"]').prop('checked', false);
                $('#opServicesSection').hide();
                
                this.updateUI();
                
                // Focus back to first field
                $('#newCodEmpresa').focus();
            },
            
            removeRecord: function(id) {
                this.records = this.records.filter(function(record) {
                    return record.id !== id;
                });
                this.updateUI();
            },
            
            clearAll: function() {
                if (this.records.length === 0) return;
                
                if (confirm('Tem certeza que deseja remover todos os registros?')) {
                    this.records = [];
                    this.updateUI();
                }
            },
            
            updateUI: function() {
                this.updateRecordsList();
                this.updateCounters();
            },
            
            updateRecordsList: function() {
                var self = this;
                var container = $('#recordsList');
                
                if (this.records.length === 0) {
                    container.html('<div class="alert alert-info"><i class="fa fa-info-circle"></i> Nenhum registro adicionado ainda. Use o formulário acima para adicionar registros.</div>');
                    return;
                }
                
                var html = '<div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th width="80">Empresa</th><th width="60">Loja</th><th>Nome</th><th width="80">Tipo</th><th width="80">Ações</th></tr></thead><tbody>';
                
                this.records.forEach(function(record) {
                    var badgeClass = self.getTipoBadgeClass(record.tipoCorrespondente);
                    var opServicesText = '';
                    
                    if (record.tipoCorrespondente === 'OP') {
                        var services = [];
                        if (record.opServices.holerite) services.push('Holerite');
                        if (record.opServices.consulta) services.push('Consulta');
                        if (record.opServices.segundaVia) services.push('2ª Via');
                        if (record.opServices.saque) services.push('Saque');
                        opServicesText = '<br><small class="text-muted">' + services.join(', ') + '</small>';
                    }
                    
                    html += '<tr>' +
                        '<td><strong>' + record.codEmpresa + '</strong></td>' +
                        '<td><strong>' + record.codLoja + '</strong></td>' +
                        '<td>' + record.nomeLoja + '</td>' +
                        '<td><span class="badge ' + badgeClass + '">' + record.tipoCorrespondente + '</span>' + opServicesText + '</td>' +
                        '<td><button class="btn btn-xs btn-danger" onclick="TxtModal.removeRecord(' + record.id + ')"><i class="fa fa-trash"></i></button></td>' +
                        '</tr>';
                });
                
                html += '</tbody></table></div>';
                container.html(html);
            },
            
            updateCounters: function() {
                var count = this.records.length;
                $('#recordCount').text(count);
                $('#totalRecords').text(count);
                $('#generateManualTxtBtn').prop('disabled', count === 0);
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
            
            generateTXT: function() {
                if (this.records.length === 0) {
                    alert('Adicione pelo menos um registro');
                    return;
                }
                
                var txtContent = '';
                var self = this;
                
                this.records.forEach(function(record) {
                    var empresa = record.codEmpresa;
                    var codigoLoja = record.codLoja;
                    var tipo = record.tipoCorrespondente;
                    
                    if (['AV', 'PR', 'UN'].includes(tipo)) {
                        var limits = self.defaultLimits[tipo];
                        
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 19, '01', 500, limits.dinheiro, 1, 0, 2, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 19, '02', 500, limits.cheque, 1, 0, 2, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 28, '04', 1000, limits.retirada, 1, 0, 2, 0) + '\r\n';
                        txtContent += self.formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, limits.saque, 1, 0, 2, 0) + '\r\n';
                        
                    } else if (tipo === 'OP') {
                        var services = record.opServices;
                        
                        if (services.holerite) {
                            txtContent += self.formatToTXTLine(empresa, codigoLoja, 14, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        }
                        if (services.consulta) {
                            txtContent += self.formatToTXTLine(empresa, codigoLoja, 18, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        }
                        if (services.segundaVia) {
                            txtContent += self.formatToTXTLine(empresa, codigoLoja, 31, '04', 0, 0, 1, 0, 1, 0) + '\r\n';
                        }
                        if (services.saque) {
                            txtContent += self.formatToTXTLine(empresa, codigoLoja, 29, '04', 1000, self.defaultLimits.OP.saque, 1, 0, 1, 0) + '\r\n';
                        }
                    }
                });
                
                if (txtContent) {
                    var filename = 'manual_txt_' + this.getCurrentTimestamp() + '.txt';
                    this.downloadTXTFile(txtContent, filename);
                    
                    $('#txtManualModal').modal('hide');
                    this.showSuccessMessage('TXT gerado com sucesso!\n' + this.records.length + ' registros processados.');
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
            
            downloadTXTFile: function(txtContent, filename) {
                var fileFormat = $('#fileFormat').val();
                var content = txtContent;
                
                if (fileFormat === 'txt_with_bom') {
                    content = '\uFEFF' + txtContent;
                }
                
                var blob = new Blob([content], { type: 'text/plain;charset=utf-8;' });
                
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
        
        // Event Listeners
        $(document).ready(function() {
            // Add record button
            $(document).on('click', '#addRecordBtn', function() {
                TxtModal.addRecord();
            });
            
            // Enter key in form fields
            $(document).on('keypress', '#newCodEmpresa, #newCodLoja, #newNomeLoja', function(e) {
                if (e.which === 13) {
                    TxtModal.addRecord();
                }
            });
            
            // Show/hide OP services when OP is selected
            $(document).on('change', 'input[name="tipoCorrespondente"]', function() {
                if ($(this).val() === 'OP') {
                    $('#opServicesSection').show();
                } else {
                    $('#opServicesSection').hide();
                }
            });
            
            // Generate TXT button
            $(document).on('click', '#generateManualTxtBtn', function() {
                TxtModal.generateTXT();
            });
            
            // Clear all button
            $(document).on('click', '#clearAllBtn', function() {
                TxtModal.clearAll();
            });
            
            // Only allow numbers in empresa and loja fields
            $(document).on('input', '#newCodEmpresa, #newCodLoja', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render CSS
     */
    public function renderCSS() {
        ?>
        <style type="text/css">
        #txtManualModal .badge-success { background-color: #5cb85c; }
        #txtManualModal .badge-info { background-color: #5bc0de; }
        #txtManualModal .badge-warning { background-color: #f0ad4e; }
        #txtManualModal .badge-primary { background-color: #337ab7; }
        #txtManualModal .badge-default { background-color: #777; }
        #txtManualModal .form-check-input { margin-right: 8px; }
        #txtManualModal .panel-heading { cursor: pointer; }
        #txtManualModal .checkbox-group { margin-bottom: 15px; }
        #txtManualModal .checkbox-inline { margin-right: 20px; }
        #txtManualModal .table th { background-color: #f5f5f5; }
        #txtManualModal .modal-lg { max-width: 900px; }
        #txtManualModal .panel { margin-bottom: 15px; }
        #txtManualModal .panel-heading h5 { margin: 0; }
        #txtManualModal .alert { margin-bottom: 0; }
        </style>
        <?php
    }
}
?>