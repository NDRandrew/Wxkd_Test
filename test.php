if (filter === 'historico') {
    // Handle historico via AJAX without page reload
    const historico = $xml.find('tableData row');
    let accordionHtml = '';
    
    if (historico.length > 0) {
        accordionHtml = '<div class="panel-group accordion" id="accordions">';
        historico.each(function() {
            const row = {};
            $(this).children().each(function() {
                row[this.tagName] = $(this).text();
            });
            
            const dataLog = new Date(row.DATA_LOG).toLocaleDateString('pt-BR') + ' ' + 
                           new Date(row.DATA_LOG).toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'});
            
            accordionHtml += `
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="accordion-toggle collapsed" data-toggle="collapse" 
                               data-parent="#accordions" href="#collapse${row.CHAVE_LOTE}" 
                               aria-expanded="false">
                                <i class="fa-fw fa fa-history"></i> 
                                Lote #${row.CHAVE_LOTE} - ${dataLog} - 
                                ${row.TOTAL_REGISTROS} registro(s) - 
                                ${row.PRIMEIRO_NOME_LOJA}${row.TOTAL_REGISTROS > 1 ? ' e outros' : ''}
                            </a>
                        </h4>
                    </div>
                    <div id="collapse${row.CHAVE_LOTE}" class="panel-collapse collapse" aria-expanded="false">
                        <div class="panel-body border-red">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm historico-detail-table">
                                    <thead>
                                        <tr>
                                            <th>Chave Loja</th><th>Nome Loja</th><th>Cod Empresa</th><th>Cod Loja</th><th>Tipo Correspondente</th>
                                            <th>Dep Dinheiro</th><th>Dep Cheque</th><th>Rec Retirada</th><th>Saque Cheque</th><th>2Via Cartão</th>
                                            <th>Holerite INSS</th><th>Consulta INSS</th><th>Prova de Vida</th><th>Data Contrato</th><th>Tipo Contrato</th>
                                        </tr>
                                    </thead>
                                    <tbody class="historico-details" data-chave-lote="${row.CHAVE_LOTE}">
                                        <tr><td colspan="15" class="text-center">
                                            <button class="btn btn-sm btn-info load-details" data-chave-lote="${row.CHAVE_LOTE}">
                                                <i class="fa fa-download"></i> Carregar Detalhes
                                            </button>
                                        </td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted"><strong>Resumo:</strong> Lojas: ${row.CHAVES_LOJAS}</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        accordionHtml += '</div>';
    } else {
        accordionHtml = '<div class="alert alert-info"><i class="fa fa-info-circle"></i> Nenhum histórico encontrado.</div>';
    }
    
    $('#historicoAccordion').html(accordionHtml);
    hideLoading();
} else {
    // Handle normal table data (keep existing code)