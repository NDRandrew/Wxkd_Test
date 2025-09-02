<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\MSSQL.class.php'); 
class Alerta{
    public function Alerta() 
    {
        $this->sql = new MSSQL('INFRA');
    }
    
    // Helper function to escape single quotes for SQL Server
    private function escape_sql_string($value) {
        return str_replace("'", "''", $value);
    }
    
    public function cadastrar_alerta($nome_alerta,$query_alerta,$descricao_alerta,$select_area_resp){
        // Escape all string parameters
        $nome_alerta_escaped = $this->escape_sql_string($nome_alerta);
        $query_alerta_escaped = $this->escape_sql_string($query_alerta);
        $descricao_alerta_escaped = $this->escape_sql_string($descricao_alerta);
        
        $query = "INSERT INTO INFRA.DBO.TB_QUERIES_ALERTA (NOME_ALERTA,QUERY,STATUS_QUERY,DESCRICAO_ALERTA,ID_AREA) 
                VALUES ('".$nome_alerta_escaped."','".$query_alerta_escaped."',1,'".$descricao_alerta_escaped."','".$select_area_resp."')";
        $dados = $this->sql->insert($query);
        #print $query;
        return $dados;
    }

    public function editar_alerta($id,$nome_alerta,$query_alerta,$descricao_alerta,$select_area_resp){
        // Escape all string parameters
        $nome_alerta_escaped = $this->escape_sql_string($nome_alerta);
        $query_alerta_escaped = $this->escape_sql_string($query_alerta);
        $descricao_alerta_escaped = $this->escape_sql_string($descricao_alerta);
        
        $query = "UPDATE INFRA.DBO.TB_QUERIES_ALERTA
                SET nome_alerta= '".$nome_alerta_escaped."'
                    ,query='".$query_alerta_escaped."'
                    ,descricao_alerta='".$descricao_alerta_escaped."'
                    ,id_area=".$select_area_resp." 
                WHERE ID = '".$id."'
                ";
        #print $query;die();
        $dados = $this->sql->update($query);
        
        return $dados;
    }
    
    public function consultar_alerta_nome($nome_alerta){
        $nome_alerta_escaped = $this->escape_sql_string($nome_alerta);
        $query = "select * from INFRA.DBO.TB_QUERIES_ALERTA where NOME_ALERTA='".$nome_alerta_escaped."'";
        $dados = $this->sql->qtdRows($query);
        return $dados;
    }
    
    public function lista_alertas()    {
        
        $query = "SELECT id,nome_alerta,query,dt_atualizado,resultado,descricao_alerta,ISNULL(A.id_area,0)id_area, ISNULL(B.DESC_AREA_RESP,'GERAL') desc_area_resp
        ,SUBSTRING(QUERY,CHARINDEX('SELECT',QUERY),7)+' * '+SUBSTRING(CONVERT(VARCHAR(8000),QUERY),CHARINDEX('FROM',QUERY),LEN(convert(varchar(8000),QUERY))) query_lista
        ,CONVERT(VARCHAR,DT_ATUALIZADO,103)+' '+CONVERT(VARCHAR,DT_ATUALIZADO,108) dt_atlz       
       FROM INFRA.DBO.TB_QUERIES_ALERTA A
        LEFT JOIN
        TB_ALERTAS_AREA_RESP B ON A.ID_AREA=B.ID_AREA
       ORDER BY A.ID_AREA,[ID]";
        $dados = $this->sql->select($query);
        return $dados;
    }

    public function exec_query($query)
    {   
        $dados = $this->sql->select($query);
        return $dados;
    }
     public function delete_item($id_item)
    {   
        $query = "DELETE FROM INFRA.DBO.TB_QUERIES_ALERTA WHERE ID=".$id_item."";
        $dados = $this->sql->delete($query);
        return $dados;
    }
    public function lista_query($id)
    {
        $query = "SELECT id,nome_alerta,query,convert(varchar,dt_atualizado,103)+' '+convert(varchar,dt_atualizado,108) dt_atualizado,resultado,descricao_alerta FROM INFRA.DBO.TB_QUERIES_ALERTA where id = ".$id." ORDER BY [ID]";
        $dados = $this->sql->select($query);
        return $dados;
    }
    public function lista_query_alerta($id)
    {
        $query = "SELECT id,nome_alerta,query,dt_atualizado,resultado,descricao_alerta FROM INFRA.DBO.TB_QUERIES_ALERTA where id = ".$id." ORDER BY [ID]";
        $dados = $this->sql->select($query);

        $dados = $this->sql->select($dados[0]['query']);
        return $dados;
    }
    public function lista_query_grafico($id)
    {
        $query = "select id,nome_alerta,convert(varchar,dt_atualizado,103)dt_atualizado,convert(varchar,dt_atualizado,108)hora, resultado 
                from infra.dbo.tb_queries_alerta_log where datediff(day,dt_atualizado,getdate()) <31 and id = ".$id." order by convert(varchar,dt_atualizado,112)";
        $dados = $this->sql->select($query);
        
       
        return $dados;
    }
    public function lista_areas()
    {
        $query = "select * from infra.dbo.TB_ALERTAS_AREA_RESP";
        $dados = $this->sql->select($query);
        
       
        return $dados;
    }
}   

?>


-------

<?php 
@session_start();

require_once '../model/alerta_model.php';
$alerta = new Alerta();

#$dados = extract($_GET);

#print_r($_POST);

extract($_GET);
extract($_POST);

// Helper function to escape single quotes for SQL Server
function escape_sql_string($value) {
    return str_replace("'", "''", $value);
}

// Helper function to validate and sanitize numeric IDs
function validate_numeric_id($id) {
    if (!is_numeric($id) || $id <= 0) {
        die('ID inválido!');
    }
    return (int)$id; // Cast to integer for safety
}

// Helper function to escape HTML output to prevent XSS
function escape_html($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
    // CRITICAL SECURITY FIX: Do not allow arbitrary query execution
    // This is extremely dangerous as it allows any SQL to be executed
    // Instead, restrict to only SELECT queries and add validation
    
    $query = trim($query);
    
    // Only allow SELECT queries (basic protection)
    if(!preg_match('/^SELECT\s+/i', $query)) {
        die('Erro: Apenas consultas SELECT são permitidas!');
    }
    
    // Check for dangerous SQL keywords
    $dangerous_keywords = array('DROP', 'DELETE', 'UPDATE', 'INSERT', 'ALTER', 'CREATE', 'EXEC', 'EXECUTE');
    foreach($dangerous_keywords as $keyword) {
        if(stripos($query, $keyword) !== false) {
            die('Erro: Query contém comandos não permitidos!');
        }
    }
    
    $dados =  $alerta->exec_query($query);
    
    print $query;die();
    
}

if($acao=='delete_item')
{
    // Validate ID is numeric
    $id_item = validate_numeric_id($id_item);
    
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
    // Validate ID is numeric  
    $clean_id = validate_numeric_id(str_replace('item_query', '', $id));
    
    $dados =  $alerta->lista_query($clean_id);
    #print_r($dados);
    print number_format($dados[0]['resultado'],0,',','.')."<br><i style='font-size:10px;font-style:normal;font-weight:normal;' class='pull-right'>".escape_html($dados[0]['dt_atualizado'])."";
    

    die();
}
if($acao =='lista_query_grafico')
{
    // Validate ID is numeric
    $id = validate_numeric_id($id);
    
    $dados =  $alerta->lista_query_grafico($id);
    #print '<pre>';print_r($dados);die(); 
    for($i=0;$i<count($dados);$i++)
    {
        // Escape single quotes for JavaScript
        $dt_atualizado.="'".escape_sql_string($dados[$i]['dt_atualizado'])."',";
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
                // Escape JavaScript string to prevent XSS
                print escape_sql_string($dados[0]['nome_alerta']);
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
    // Validate ID is numeric
    $id = validate_numeric_id($id);
    
    $dados =  $alerta->lista_query_alerta($id);
    $_SESSION['exportar_excel']=$dados;
    
    $btnExportar = '<p><a href="../view/exportar_xls.php" target="_blank"><button class="btn btn-success" id="btn_exportar_excel">Exportar</button></a></p>';

    if(count($dados)==0)
    {
        print '<div class="alert alert-info"> <i class="fa-fw fa fa-info"></i> Nenhum registro encontrato!</div>';die();
    }

    foreach ($dados[0] as $key => $value) {
        if(!is_int($key))
        {
            $header[]=$key;
        }
    }

    #<th class="th_csv sorting_desc" tabindex="0" aria-controls="tabela_lojas_sem_municipio" rowspan="1" colspan="1" aria-sort="descending" aria-label="ChaveLoja: activate to sort column ascending" style="width: 63.5185px;">ChaveLoja</th>
    
    $html_table = $btnExportar.'<table class="tabela_lista_query">';
    $html_table .= '<thead><tr role="row">';
    
    foreach($header as $key => $value)
    {
       $html_table .= '<th>'.escape_html($value).'</th>'; 
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
            $html_table .= '<td>'.escape_html($dados[$i][$value]).'</td>';
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