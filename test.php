<?php
// Add this right after the closing </div> of the table-scrollable div and before the pagination div
// Replace the table section with this conditional rendering:
?>

<div>
    <div>
        <div class="table-scrollable" <?php echo ($activeFilter === 'historico') ? 'style="display:none;"' : ''; ?>>
            <!-- Your existing table code goes here - keep it as is -->
            <table class="table table-striped table-bordered table-hover dataTable no-footer" id="dataTableAndre">
                <!-- Keep all your existing table structure -->
            </table>
        </div>
        
        <?php if ($activeFilter === 'historico'): ?>
            <div class="accordion-container" id="historicoAccordion">
                <?php if (is_array($tableData) && !empty($tableData)): ?>
                    <div class="panel-group accordion" id="accordions">
                        <?php foreach ($tableData as $index => $row): ?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a class="accordion-toggle collapsed" data-toggle="collapse" 
                                           data-parent="#accordions" href="#collapse<?php echo $row['CHAVE_LOTE']; ?>" 
                                           aria-expanded="false">
                                            <i class="fa-fw fa fa-history"></i> 
                                            Lote #<?php echo $row['CHAVE_LOTE']; ?> - 
                                            <?php echo date('d/m/Y H:i', strtotime($row['DATA_LOG'])); ?> - 
                                            <?php echo $row['TOTAL_REGISTROS']; ?> registro(s) - 
                                            <?php echo htmlspecialchars($row['PRIMEIRO_NOME_LOJA']); ?><?php echo ($row['TOTAL_REGISTROS'] > 1) ? ' e outros' : ''; ?>
                                        </a>
                                    </h4>
                                </div>
                                <div id="collapse<?php echo $row['CHAVE_LOTE']; ?>" 
                                     class="panel-collapse collapse" 
                                     aria-expanded="false" style="height: 0px;">
                                    <div class="panel-body border-red">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-sm historico-detail-table">
                                                <thead>
                                                    <tr>
                                                        <th>Chave Loja</th>
                                                        <th>Nome Loja</th>
                                                        <th>Cod Empresa</th>
                                                        <th>Cod Loja</th>
                                                        <th>Tipo Correspondente</th>
                                                        <th>Dep Dinheiro</th>
                                                        <th>Dep Cheque</th>
                                                        <th>Rec Retirada</th>
                                                        <th>Saque Cheque</th>
                                                        <th>2Via Cartão</th>
                                                        <th>Holerite INSS</th>
                                                        <th>Consulta INSS</th>
                                                        <th>Prova de Vida</th>
                                                        <th>Data Contrato</th>
                                                        <th>Tipo Contrato</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="historico-details" data-chave-lote="<?php echo $row['CHAVE_LOTE']; ?>">
                                                    <tr>
                                                        <td colspan="15" class="text-center">
                                                            <button class="btn btn-sm btn-info load-details" 
                                                                    data-chave-lote="<?php echo $row['CHAVE_LOTE']; ?>">
                                                                <i class="fa fa-download"></i> Carregar Detalhes
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <strong>Resumo:</strong> 
                                                Lojas: <?php echo htmlspecialchars($row['CHAVES_LOJAS']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        Nenhum histórico encontrado. Os registros aparecerão aqui após a exportação de arquivos TXT.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Also update the main switch case at the top of your file to handle the new action:
/*
switch($action) {
    case 'exportCSV':
        $controller->exportCSV();
        break;
    case 'exportTXT':
        $controller->exportTXT();
        break;
    case 'exportAccess':  
        $controller->exportAccess();
        break;
    case 'ajaxGetTableData':
        $controller->ajaxGetTableData();
        break;
    case 'ajaxGetHistoricoDetails':  // Add this new case
        $controller->ajaxGetHistoricoDetails();
        break;
    default:
        $data = $controller->index();
        $cardData = $data['cardData'];
        $tableData = $data['tableData'];
        $activeFilter = $data['activeFilter'];
        $contractChavesLookup = $data['contractChavesLookup'];
        break;
}
*/
?>

<style>
/* Add these styles for the accordion */
.accordion-container {
    margin-top: 20px;
}

.historico-detail-table {
    font-size: 12px;
}

.historico-detail-table th,
.historico-detail-table td {
    padding: 8px 4px;
    text-align: center;
    vertical-align: middle;
}

.panel-title a {
    text-decoration: none;
}

.panel-title a:hover {
    text-decoration: none;
}

.border-red {
    border-left: 3px solid #d9534f;
}

.border-gold {
    border-left: 3px solid #f0ad4e;
}

.load-details {
    transition: all 0.3s ease;
}

.load-details:hover {
    transform: scale(1.05);
}
</style><?php
?>