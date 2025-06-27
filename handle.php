// Adicionar no controller principal (views/Wxkd_dashboard.php)
// Na parte de roteamento das actions

public function handleRequest() {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    switch($action) {
        case 'ajaxGetTableData':
            $this->ajaxGetTableData();
            break;
            
        case 'exportXLS':
            $this->exportXLS();
            break;
            
        case 'exportCSV':
            $this->exportCSV();
            break;
            
        case 'exportXML':
            // Redirecionar XML para XLS por compatibilidade
            $this->exportXLS();
            break;
            
        case 'exportTXT':
            $this->exportTXT();
            break;
            
        default:
            $this->index();
            break;
    }
}

// No final da classe, adicionar os métodos CSS e JavaScript inline
private function getExportStyles() {
    return '
    <style>
    .export-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
        border-radius: 12px;
        padding: 25px;
        margin: 25px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    }

    .export-section h3 {
        margin: 0 0 20px 0;
        color: #343a40;
        font-size: 1.2em;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .export-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .export-group {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .export-group h4 {
        margin: 0 0 10px 0;
        color: #495057;
        font-size: 1em;
        font-weight: 500;
    }

    .export-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .btn-export {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.3s ease;
        text-align: left;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-export:hover {
        background: linear-gradient(135deg, #0056b3 0%, #007bff 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,123,255,0.3);
    }

    .btn-export.csv {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    }

    .btn-export.csv:hover {
        background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        box-shadow: 0 4px 8px rgba(40,167,69,0.3);
    }

    .export-info {
        background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        border-radius: 6px;
        padding: 15px;
        border-left: 4px solid #2196f3;
    }

    .export-info small {
        color: #546e7a;
        line-height: 1.5;
        display: block;
    }

    @media (max-width: 768px) {
        .export-options {
            grid-template-columns: 1fr;
        }
    }
    </style>';
}

private function getExportHTML() {
    return '
    <div class="export-section">
        <h3>
            <span>📊</span>
            <span>Exportação de Dados</span>
        </h3>
        
        <div class="export-options">
            <!-- Exportar Selecionados -->
            <div class="export-group">
                <h4>📋 Registros Selecionados</h4>
                <div class="export-buttons">
                    <button type="button" class="btn-export xls" onclick="exportSelectedXLS()">
                        <span>📊</span>
                        <span>Exportar como Excel (XLS)</span>
                    </button>
                    <button type="button" class="btn-export csv" onclick="exportSelectedCSV()">
                        <span>📄</span>
                        <span>Exportar como CSV</span>
                    </button>
                </div>
            </div>
            
            <!-- Exportar Todos -->
            <div class="export-group">
                <h4>📑 Todos os Registros</h4>
                <div class="export-buttons">
                    <button type="button" class="btn-export xls" onclick="exportAllXLS()">
                        <span>📊</span>
                        <span>Exportar como Excel (XLS)</span>
                    </button>
                    <button type="button" class="btn-export csv" onclick="exportAllCSV()">
                        <span>📄</span>
                        <span>Exportar como CSV</span>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="export-info">
            <small>
                <strong>📋 Selecionados:</strong> Exporta apenas os registros marcados na tabela<br>
                <strong>📑 Todos:</strong> Exporta todos os registros do filtro atual<br>
                <strong>📊 XLS:</strong> Formato Excel com formatação e estilos<br>
                <strong>📄 CSV:</strong> Formato compatível com Excel e outros programas
            </small>
        </div>
    </div>';
}