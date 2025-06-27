<!-- Seção de Exportação - Atualizada para XLS -->
<div class="export-section">
    <h3>📊 Exportação de Dados</h3>
    <div class="export-buttons">
        <!-- Exportar Selecionados -->
        <button type="button" 
                class="btn-export btn-export-selected" 
                onclick="exportSelectedXLS()"
                title="Exportar apenas os registros selecionados">
            <i class="icon-download"></i>
            📋 Exportar Selecionados (XLS)
        </button>
        
        <!-- Exportar Todos -->
        <button type="button" 
                class="btn-export btn-export-all" 
                onclick="exportAllXLS()"
                title="Exportar todos os registros do filtro atual">
            <i class="icon-download-all"></i>
            📊 Exportar Todos (XLS)
        </button>
    </div>
    
    <!-- Informações sobre exportação -->
    <div class="export-info">
        <small>
            <strong>📋 Selecionados:</strong> Exporta apenas os registros marcados<br>
            <strong>📊 Todos:</strong> Exporta todos os registros do filtro atual<br>
            <strong>📄 Formato:</strong> Excel (.xls) com formatação e estilos
        </small>
    </div>
</div>

<!-- CSS para os botões de exportação -->
<style>
.export-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.export-section h3 {
    margin: 0 0 15px 0;
    color: #495057;
    font-size: 1.1em;
}

.export-buttons {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.btn-export {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-width: 200px;
}

.btn-export:hover {
    background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-export:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-export-selected {
    background: linear-gradient(135deg, #007bff 0%, #6610f2 100%);
}

.btn-export-selected:hover {
    background: linear-gradient(135deg, #6610f2 0%, #007bff 100%);
}

.btn-export:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.export-info {
    background: #e9ecef;
    border-radius: 4px;
    padding: 10px;
    border-left: 4px solid #007bff;
}

.export-info small {
    color: #6c757d;
    line-height: 1.4;
}

/* Responsivo */
@media (max-width: 768px) {
    .export-buttons {
        flex-direction: column;
    }
    
    .btn-export {
        width: 100%;
        min-width: auto;
    }
}

/* Ícones para os botões */
.btn-export .icon-download::before {
    content: "⬇️";
    margin-right: 5px;
}

.btn-export .icon-download-all::before {
    content: "📥";
    margin-right: 5px;
}
</style>