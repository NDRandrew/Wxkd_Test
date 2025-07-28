<?php
/**
 * TXT Generator Modal Class
 * Compatible with PHP 5.3
 * Generates a modal for creating custom TXT files
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
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title">Gerador de Arquivo TXT Personalizado</h4>
                    </div>
                    <div class="modal-body">
                        <form id="txtGeneratorForm" method="post" action="">
                            <input type="hidden" name="action" value="generate_txt">
                            
                            <?php foreach ($this->fields as $fieldKey => $fieldLabel): ?>
                            <div class="form-group">
                                <label for="<?php echo $fieldKey; ?>"><?php echo $fieldLabel; ?>:</label>
                                <input type="number" 
                                       class="form-control" 
                                       id="<?php echo $fieldKey; ?>" 
                                       name="<?php echo $fieldKey; ?>" 
                                       step="any"
                                       required>
                            </div>
                            <?php endforeach; ?>
                            
                            <div class="form-group">
                                <label for="filename">Nome do Arquivo:</label>
                                <input type="text" 
                                       class="form-control" 
                                       id="filename" 
                                       name="filename" 
                                       value="arquivo_personalizado"
                                       required>
                                <small class="help-block">Arquivo será salvo como [nome].txt</small>
                            </div>
                        </form>
                        
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
                        <button type="button" class="btn btn-warning" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" onclick="submitTxtForm()">Gerar TXT</button>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        function submitTxtForm() {
            var form = document.getElementById('txtGeneratorForm');
            var isValid = true;
            
            // Validate all number inputs
            var inputs = form.querySelectorAll('input[type="number"]');
            for (var i = 0; i < inputs.length; i++) {
                if (!inputs[i].value || isNaN(inputs[i].value)) {
                    inputs[i].style.borderColor = 'red';
                    isValid = false;
                } else {
                    inputs[i].style.borderColor = '';
                }
            }
            
            // Validate filename
            var filename = document.getElementById('filename');
            if (!filename.value.trim()) {
                filename.style.borderColor = 'red';
                isValid = false;
            } else {
                filename.style.borderColor = '';
            }
            
            if (isValid) {
                form.submit();
            } else {
                alert('Por favor, preencha todos os campos corretamente com valores numéricos.');
            }
        }
        
        function openTxtGeneratorModal() {
            $('#txtGeneratorModal').modal('show');
        }
        </script>
        <?php
    }
    
    /**
     * Process form submission and generate TXT file
     */
    public function processForm() {
        if ($_POST && isset($_POST['action']) && $_POST['action'] === 'generate_txt') {
            
            // Validate and sanitize input data
            $data = array();
            $hasErrors = false;
            
            foreach ($this->fields as $fieldKey => $fieldLabel) {
                if (!isset($_POST[$fieldKey]) || $_POST[$fieldKey] === '') {
                    $_SESSION['txt_message'] = "Campo '$fieldLabel' é obrigatório.";
                    $_SESSION['txt_message_type'] = 'danger';
                    $hasErrors = true;
                    break;
                }
                
                $value = $_POST[$fieldKey];
                if (!is_numeric($value)) {
                    $_SESSION['txt_message'] = "Campo '$fieldLabel' deve ser um valor numérico.";
                    $_SESSION['txt_message_type'] = 'danger';
                    $hasErrors = true;
                    break;
                }
                
                $data[$fieldKey] = floatval($value);
            }
            
            // Validate filename
            $filename = trim($_POST['filename']);
            if (empty($filename)) {
                $_SESSION['txt_message'] = "Nome do arquivo é obrigatório.";
                $_SESSION['txt_message_type'] = 'danger';
                $hasErrors = true;
            }
            
            if (!$hasErrors) {
                $result = $this->generateTxtFile($data, $filename);
                if ($result['success']) {
                    $_SESSION['txt_message'] = $result['message'];
                    $_SESSION['txt_message_type'] = 'success';
                } else {
                    $_SESSION['txt_message'] = $result['message'];
                    $_SESSION['txt_message_type'] = 'danger';
                }
            }
            
            // Redirect to avoid form resubmission
            $this->redirect($_SERVER['REQUEST_URI']);
        }
    }
    
    /**
     * Generate TXT file with user data
     */
    private function generateTxtFile($data, $filename) {
        try {
            // Create TXT content (similar to extractTXTfromXML format)
            $txtContent = $this->createTxtContent($data);
            
            // Ensure filename has .txt extension
            if (!preg_match('/\.txt$/i', $filename)) {
                $filename .= '.txt';
            }
            
            // Create safe filename
            $safeFilename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $filename);
            
            // Define file path (adjust as needed)
            $filePath = 'generated_files/' . $safeFilename;
            
            // Create directory if it doesn't exist
            $dir = dirname($filePath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Write file
            if (file_put_contents($filePath, $txtContent) !== false) {
                return array(
                    'success' => true,
                    'message' => "Arquivo '$safeFilename' gerado com sucesso!"
                );
            } else {
                return array(
                    'success' => false,
                    'message' => "Erro ao criar o arquivo."
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => "Erro: " . $e->getMessage()
            );
        }
    }
    
    /**
     * Create TXT content from data array
     */
    private function createTxtContent($data) {
        $content = "# Arquivo TXT Personalizado\n";
        $content .= "# Gerado em: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->fields as $fieldKey => $fieldLabel) {
            $value = isset($data[$fieldKey]) ? $data[$fieldKey] : 0;
            $content .= sprintf("%-30s: %s\n", $fieldLabel, $value);
        }
        
        $content .= "\n# Fim do arquivo\n";
        
        return $content;
    }
    
    /**
     * Simple redirect function (PHP 5.3 compatible)
     */
    private function redirect($url) {
        echo "<script>window.location.href = '$url';</script>";
        exit;
    }
    
    /**
     * Display trigger button for the modal
     */
    public function displayTriggerButton() {
        ?>
        <button type="button" class="btn btn-success" onclick="openTxtGeneratorModal()">
            <i class="fa fa-file-text"></i> Gerar TXT Personalizado
        </button>
        <?php
    }
    
    /**
     * Initialize and handle everything
     */
    public function init() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $this->processForm();
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