echo '<script>';
echo '$(document).ready(function(){';
echo '$("#tbResultadoBusca").DataTable({';
echo '"sDom": "Tflt<\'row DTTTFooter\'<\'col-sm-12\'i><\'col-sm-12\'p>>",';
echo '"iDisplayLength": 50,';
echo '"bProcessing": true,';
echo '"bDeferRender": true,';
echo '"oTableTools": {';
echo '"aButtons": ["copy"],';
echo '"sSwfPath": "assets/swf/copy_csv_xls_pdf.swf"';
echo '},';
echo '"aLengthMenu": [[50, 100, 200, -1], [50, 100, 200, "All"]],';
echo '"language": {';
echo '"search": "",';
echo '"sLengthMenu": "_MENU_",';
echo '"oPaginate": {';
echo '"sPrevious": "Anterior",';
echo '"sNext": "Proximo"';
echo '},';
echo '"zeroRecords": "Nenhum Resultado Encontrado!"';
echo '}';
echo '});';

// Re-bind click events after DataTable initialization
echo '$(".equipamento-item").off("click").on("click", function(){';
echo 'var equipId = $(this).data("equip-id");';
echo 'var equipInfo = $(this).data("equip-info");';
echo 'mostrarGraficoTransacoes(equipId, equipInfo);';
echo '});';

echo '});';
echo '</script>';

----------------

function mostrarGraficoTransacoes(equipId, equipInfo) {
    $('#equipamento-selecionado').text(equipInfo);
    
    $.ajax({
        url: 'buscar_transacoes.php',
        type: 'POST',
        data: 'equip_id=' + equipId,
        success: function(response){
            $('#timeline-transacoes').html(response);
            $('#grafico-container').show();
        },
        error: function(){
            $('#timeline-transacoes').html('<p class="text-danger">Erro ao carregar histórico de transações.</p>');
            $('#grafico-container').show();
        }
    });
}
