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
    
    // Helper function to normalize and prepare query for database storage
    private function prepare_query_for_storage($query_alerta) {
        // First, unescape any existing double quotes to get back to original
        $normalized = str_replace("''", "'", $query_alerta);
        // Then properly escape for SQL insertion
        return $this->escape_sql_string($normalized);
    }
    
    // Helper function to prepare data for form display (unescape for editing)
    private function prepare_for_display($value) {
        // When retrieving from database for editing, ensure quotes are properly unescaped
        return str_replace("''", "'", $value);
    }
    
    public function cadastrar_alerta($nome_alerta,$query_alerta,$descricao_alerta,$select_area_resp){
        // Clean and escape parameters
        $nome_alerta_escaped = $this->escape_sql_string($nome_alerta);
        $query_alerta_escaped = $this->prepare_query_for_storage($query_alerta);
        $descricao_alerta_escaped = $this->escape_sql_string($descricao_alerta);
        
        $query = "INSERT INTO INFRA.DBO.TB_QUERIES_ALERTA (NOME_ALERTA,QUERY,STATUS_QUERY,DESCRICAO_ALERTA,ID_AREA) 
                VALUES ('".$nome_alerta_escaped."','".$query_alerta_escaped."',1,'".$descricao_alerta_escaped."','".$select_area_resp."')";
        $dados = $this->sql->insert($query);
        #print $query;
        return $dados;
    }

    public function editar_alerta($id,$nome_alerta,$query_alerta,$descricao_alerta,$select_area_resp){
        // Clean and escape parameters - this prevents double escaping on edits
        $nome_alerta_escaped = $this->escape_sql_string($nome_alerta);
        $query_alerta_escaped = $this->prepare_query_for_storage($query_alerta);
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
        
        // Prepare query field for display/editing by unescaping quotes
        if(isset($dados[0]['query'])) {
            $dados[0]['query'] = $this->prepare_for_display($dados[0]['query']);
        }
        if(isset($dados[0]['nome_alerta'])) {
            $dados[0]['nome_alerta'] = $this->prepare_for_display($dados[0]['nome_alerta']);
        }
        if(isset($dados[0]['descricao_alerta'])) {
            $dados[0]['descricao_alerta'] = $this->prepare_for_display($dados[0]['descricao_alerta']);
        }
        
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