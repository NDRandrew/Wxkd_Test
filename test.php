<?php 
@session_start();

require_once '../model/alerta_model.php';
$alerta = new Alerta();

#$dados = extract($_GET);

#print_r($_POST);

extract($_GET);
extract($_POST);

// Helper functions for character encoding/decoding (same as in model)
function encode_special_chars($text) {
    $replacements = array(
        "'" => "XSQUOTEX",
        "+" => "XPLUSX", 
        "-" => "XMINUSX",
        "<" => "XLTX",
        ">" => "XGTX"
    );
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

function decode_special_chars($text) {
    $replacements = array(
        "XSQUOTEX" => "'",
        "XPLUSX" => "+",
        "XMINUSX" => "-", 
        "XLTX" => "<",
        "XGTX" => ">"
    );
    return str_replace(array_keys($replacements), array_values($replacements), $text);
}

if($acao == 'cadastrar_alerta'){
    #print 'Alerta cadastrado com sucesso!';
    #die();

    if($alerta->cadastrar_alerta($nome_alerta,$query_alerta,$descricao_alerta,$select_area_resp))
    {
        print 'Alerta cadastrado com sucesso!';
    }else{
         print 'Erro ao cadastrar alerta!';
    }
    die();
}
if($acao == 'editar_alerta'){
    #print_r($_GET);die();

    if($alerta->editar_alerta($id,$nome_alerta,$query_alerta,$descricao_alerta,$select_area_resp))
    {
        print 'Alerta editado com sucesso!';
    }else{
         print 'Erro ao editar alerta!';
    }
    die();
}

if($acao=='exec_query')
{
    $dados =  $alerta->exec_query($query);
    
    print $query;die();
    
}

if($acao=='delete_item')
{
    
    if($alerta->delete_item($id_item))
    {
        print 'Excluido com sucesso!';
    }else{
         print 'Erro ao excluir alerta!';
    }
 
    die();
}
if($acao =='lista_query')
{
    $dados =  $alerta->lista_query(str_replace('item_query', '', $id));
    #print_r($dados);
    print number_format($dados[0]['resultado'],0,',','.')."<br><i style='font-size:10px;font-style:normal;font-weight:normal;' class='pull-right'>".$dados[0]['dt_atualizado']."";
    

    die();
}
if($acao =='lista_query_grafico')
{
    $dados =  $alerta->lista_query_grafico($id);
    #print '<pre>';print_r($dados);die(); 
    for($i=0;$i<count($dados);$i++)
    {
        $dt_atualizado.="'".$dados[$i]['dt_atualizado']."',";
        $resultado.=$dados[$i]['resultado'].",";
    }



    print "

    <script>
        Highcharts.chart('div_return_grafico', {

            legend: {
                    itemStyle: {
                        fontSize: '16px' // Altere o valor conforme necessário
                    }
                },

            chart: {
                type: 'spline'
            },
            title: {
                text: '"; 
                print ''.$dados[0]['nome_alerta'];
                print "',
                style: {
                            fontSize: '16px',
                            fontWeight: 'bold'
                        }
            },
            
            xAxis: {
                categories: [
                    "; 
                    print substr($dt_atualizado,0,strlen($dt_atualizado)-1);
                    print "
                ],
                labels: {
                        style: {
                            fontSize: '16px' // Altere o tamanho conforme necessário
                        }
                    }
            },
            yAxis: {
                title: {
                    text: 'Quantidade de Registros',                    
                    style: {
                                fontSize: '16px'
                            }
                }
            },
            plotOptions: {
                line: {
                    dataLabels: {
                        enabled: true
                    },
                    enableMouseTracking: false
                }
            },
            series: [{
                name: 'Historico resultado por dia (Ultimos 30 dias)',
                data: [
                   "; 
                    print substr($resultado,0,strlen($resultado)-1);
                    print "
                ],
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '16px', // Altere o tamanho conforme necessário
                        fontWeight: 'bold',
                        color: '#000000'
                    }
                }               
            }]
        });
    </script>";
   

    die();
}
if($acao =='lista_query_alerta')
{
    
    $dados =  $alerta->lista_query_alerta($id);
    $_SESSION['exportar_excel']=$dados;
    
    $btnExportar = '<p><a href="../view/exportar_xls.php" target="_blank"><button class="btn btn-success" id="btn_exportar_excel">Exportar</button></a></p>';

    if(!is_array($dados) || count($dados)==0)
    {
        print '<div class="alert alert-info"> <i class="fa-fw fa fa-info"></i> Nenhum registro encontrato!</div>';die();
    }

    $header = array();
    if(isset($dados[0]) && is_array($dados[0])) {
        foreach ($dados[0] as $key => $value) {
            if(!is_int($key))
            {
                $header[]=$key;
            }
        }
    } else {
        print '<div class="alert alert-danger"> <i class="fa-fw fa fa-error"></i> Erro na estrutura dos dados!</div>';die();
    }

    #<th class="th_csv sorting_desc" tabindex="0" aria-controls="tabela_lojas_sem_municipio" rowspan="1" colspan="1" aria-sort="descending" aria-label="ChaveLoja: activate to sort column ascending" style="width: 63.5185px;">ChaveLoja</th>
    
    $html_table = $btnExportar.'<table class="tabela_lista_query">';
    $html_table .= '<thead><tr role="row">';
    
    foreach($header as $key => $value)
    {
       $html_table .= '<th>'.$value.'</th>'; 
    }
    
    $html_table .= '</tr></thead><tbody>';

    
    for($i=0;$i<count($dados);$i++)
    {
        
        if($i%2==0)
        {
            $bg=" bg_par ";
        }else{
            $bg=" bg_impar ";
        }

        $html_table .= '<tr class="'.$bg.'">';
        foreach($header as $key => $value)
        {
            // Decode special characters for display
            $display_value = decode_special_chars($dados[$i][$value]);
            $html_table .= '<td>'.$display_value.'</td>';
        }
        $html_table .= '</tr>';
    }

    $html_table .= "</tbody></table>";
    ?>
        <script>
          $(document).ready(function() {    
            $('.tabela_lista_query').DataTable({
               "sDom": "Tflt<'row DTTTFooter'<'col-sm-6'i><'col-sm-6'p>>",
                "iDisplayLength": 50,
                "oTableTools": {
                    "aButtons": [
                        "copy",
                        ],
                    "sSwfPath": "assets/swf/copy_csv_xls_pdf.swf"
                },
                "aLengthMenu": [
                    [ 50, 100, -1],
                    [ 50, 100]
                ],
                "aaSorting": [[0, 'desc']],
                "language": {
                    "search": "",
                    "sLengthMenu": "_MENU_",
                    "oPaginate": {
                        "sPrevious": "Anterior",
                        "sNext": "Proximo"
                    },
                    "zeroRecords": "Nenhum Resultado Encontrardo!"
                }
            });
          });
        </script>
    <?php


    print $html_table;

    die();
}



?>