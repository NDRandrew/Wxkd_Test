<?php
// models/Wxkd_DashboardModel.php - IMPLEMENTAÇÃO REAL PARA SEU DATABASE WRAPPER
class Wxkd_DashboardModel {
    public $sql;
    
    public function Wxkd_Construct() {
        // Inicializar sua conexão customizada
        try {
            $this->sql = new MSSQL();
        } catch (Exception $e) {
            throw new Exception("Erro na conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    public function getCardData() {
        try {
            $cardData = array();
            
            // Cadastramento - usando qtdRows
            $cardData['cadastramento'] = $this->sql->qtdRows("
                SELECT COUNT(*) as total 
                FROM lojas_cadastramento 
                WHERE status IN ('Ativo', 'Pendente')
            ");
            
            // Descadastramento
            $cardData['descadastramento'] = $this->sql->qtdRows("
                SELECT COUNT(*) as total 
                FROM lojas_descadastramento 
                WHERE status IN ('Ativo', 'Pendente')
            ");
            
            // Histórico
            $cardData['historico'] = $this->sql->qtdRows("
                SELECT COUNT(*) as total 
                FROM lojas_historico
            ");
            
            return $cardData;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar dados dos cards: " . $e->getMessage());
        }
    }
    
    public function getTableData() {
        // Método mantido para compatibilidade
        return $this->getTableDataByFilter('all');
    }
    
    public function getTableDataByFilter($filter = 'all') {
        try {
            // Get the chave_loja values for the filter
            $chaveResults = $this->getChaveCadastro($filter);
            
            // Extract chave_loja values from results
            $chavesLoja = array();
            foreach ($chaveResults as $row) {
                if (isset($row['chave_loja'])) {
                    $chavesLoja[] = "'" . $this->escapeString($row['chave_loja']) . "'";
                }
            }
            
            // Build WHERE clause
            $whereClause = '';
            if (!empty($chavesLoja)) {
                $whereClause = " AND chave_loja IN (" . implode(',', $chavesLoja) . ")";
            } else {
                // If no chaves found, return empty result
                return array();
            }
            
            $sql = "";
            
            switch($filter) {
                case 'cadastramento':
                    $sql = "
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'cadastramento' as tipo_processo,
                               created_at, updated_at, chave_loja
                        FROM lojas_cadastramento 
                        WHERE status IN ('Ativo', 'Pendente') $whereClause
                        ORDER BY created_at DESC
                    ";
                    break;
                    
                case 'descadastramento':
                    $sql = "
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'descadastramento' as tipo_processo,
                               motivo_descadastramento, data_solicitacao_descadastramento,
                               created_at, updated_at, chave_loja
                        FROM lojas_descadastramento 
                        WHERE status IN ('Ativo', 'Pendente') $whereClause
                        ORDER BY created_at DESC
                    ";
                    break;
                    
                case 'historico':
                    $sql = "
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'historico' as tipo_processo,
                               processo_origem, data_processamento, usuario_processamento, 
                               arquivo_gerado, linha_convertida, chave_loja,
                               CASE 
                                 WHEN processo_origem = 'cadastramento' THEN 'Cadastrado'
                                 WHEN processo_origem = 'descadastramento' THEN 'Descadastrado'
                                 ELSE 'Processado'
                               END as status_processo
                        FROM lojas_historico 
                        WHERE 1=1 $whereClause
                        ORDER BY data_processamento DESC
                    ";
                    break;
                    
                default: // 'all' - usando UNION ALL
                    // For 'all', get chaves for both cadastramento and descadastramento
                    $chavesCadastramento = $this->getChaveCadastro('cadastramento');
                    $chavesDescadastramento = $this->getChaveCadastro('descadastramento');
                    
                    // Build separate WHERE clauses
                    $chavesLojaCad = array();
                    foreach ($chavesCadastramento as $row) {
                        if (isset($row['chave_loja'])) {
                            $chavesLojaCad[] = "'" . $this->escapeString($row['chave_loja']) . "'";
                        }
                    }
                    
                    $chavesLojaDesc = array();
                    foreach ($chavesDescadastramento as $row) {
                        if (isset($row['chave_loja'])) {
                            $chavesLojaDesc[] = "'" . $this->escapeString($row['chave_loja']) . "'";
                        }
                    }
                    
                    $whereClauseCad = !empty($chavesLojaCad) ? " AND chave_loja IN (" . implode(',', $chavesLojaCad) . ")" : " AND 1=0";
                    $whereClauseDesc = !empty($chavesLojaDesc) ? " AND chave_loja IN (" . implode(',', $chavesLojaDesc) . ")" : " AND 1=0";
                    
                    $sql = "
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'cadastramento' as tipo_processo,
                               created_at as data_referencia, chave_loja
                        FROM lojas_cadastramento 
                        WHERE status IN ('Ativo', 'Pendente') $whereClauseCad
                        
                        UNION ALL
                        
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'descadastramento' as tipo_processo,
                               created_at as data_referencia, chave_loja
                        FROM lojas_descadastramento 
                        WHERE status IN ('Ativo', 'Pendente') $whereClauseDesc
                        
                        ORDER BY data_referencia DESC
                    ";
            }
            
            // Usando select() do seu wrapper
            $result = $this->sql->select($sql);
            return $result;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar dados da tabela: " . $e->getMessage());
        }
    }
    
    public function getSelectedTableData($selectedIds, $filter = 'all') {
        if (empty($selectedIds)) {
            return array();
        }
        
        try {
            // Criar lista de IDs para SQL
            $idsList = "'" . implode("','", $selectedIds) . "'";
            $sql = "";
            
            switch($filter) {
                case 'cadastramento':
                    $sql = "
                        SELECT * FROM lojas_cadastramento 
                        WHERE id IN ($idsList)
                    ";
                    break;
                    
                case 'descadastramento':
                    $sql = "
                        SELECT * FROM lojas_descadastramento 
                        WHERE id IN ($idsList)
                    ";
                    break;
                    
                case 'historico':
                    $sql = "
                        SELECT * FROM lojas_historico 
                        WHERE id IN ($idsList)
                    ";
                    break;
                    
                default:
                    // Para 'all', buscar em ambas as tabelas
                    $sql = "
                        SELECT *, 'cadastramento' as tipo_processo FROM lojas_cadastramento 
                        WHERE id IN ($idsList)
                        
                        UNION ALL
                        
                        SELECT *, 'descadastramento' as tipo_processo FROM lojas_descadastramento 
                        WHERE id IN ($idsList)
                    ";
            }
            
            $result = $this->sql->select($sql);
            return $result;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar dados selecionados: " . $e->getMessage());
        }
    }
    
    public function moveToHistory($selectedIds, $sourceTable) {
        if (empty($selectedIds)) {
            return false;
        }
        
        try {
            // Iniciar transação - ajuste conforme seu wrapper
            // Pode ser: $this->db->beginTransaction(); ou $this->db->query("BEGIN TRANSACTION");
            $this->startTransaction();
            
            $idsList = "'" . implode("','", $selectedIds) . "'";
            
            // 1. Buscar dados que serão movidos usando select
            $sql = "SELECT * FROM lojas_$sourceTable WHERE id IN ($idsList)";
            $dataToMove = $this->db->select($sql);
            
            if (empty($dataToMove)) {
                $this->rollbackTransaction();
                return false;
            }
            
            // 2. Inserir no histórico usando insert
            foreach ($dataToMove as $row) {
                $insertSql = "
                    INSERT INTO lojas_historico 
                    (nome, email, telefone, cidade, estado, data_cadastro, status, tipo, categoria, 
                     observacoes, processo_origem, data_processamento, usuario_processamento, 
                     arquivo_gerado";
                
                $valuesSql = " VALUES (
                    '" . $this->escapeString($row['nome']) . "',
                    '" . $this->escapeString($row['email']) . "',
                    '" . $this->escapeString($row['telefone']) . "',
                    '" . $this->escapeString($row['cidade']) . "',
                    '" . $this->escapeString($row['estado']) . "',
                    '" . $this->escapeString($row['data_cadastro']) . "',
                    '" . $this->escapeString($row['status']) . "',
                    '" . $this->escapeString($row['tipo']) . "',
                    '" . $this->escapeString($row['categoria']) . "',
                    '" . $this->escapeString($row['observacoes']) . "',
                    '$sourceTable',
                    GETDATE(),
                    '" . $this->getCurrentUser() . "',
                    'arquivo_" . date('YmdHis') . "_" . $row['id'] . ".txt'";
                
                // Adicionar campos específicos do descadastramento
                if ($sourceTable === 'descadastramento' && isset($row['motivo_descadastramento'])) {
                    $insertSql .= ", motivo_descadastramento, data_solicitacao_descadastramento";
                    $valuesSql .= ", '" . $this->escapeString($row['motivo_descadastramento']) . "'";
                    $valuesSql .= ", '" . $this->escapeString($row['data_solicitacao_descadastramento']) . "'";
                }
                
                $valuesSql .= ")";
                $fullInsertSql = $insertSql . ")" . $valuesSql;
                
                // Executar insert
                $this->db->insert($fullInsertSql);
            }
            
            // 3. Remover da tabela original usando delete
            $deleteSql = "DELETE FROM lojas_$sourceTable WHERE id IN ($idsList)";
            $this->db->delete($deleteSql);
            
            // 4. Log da operação
            $this->logOperation($selectedIds, $sourceTable, count($dataToMove));
            
            $this->commitTransaction();
            return true;
            
        } catch (Exception $e) {
            $this->rollbackTransaction();
            throw new Exception("Erro ao mover dados para histórico: " . $e->getMessage());
        }
    }
    
    private function startTransaction() {
        // Ajuste conforme seu wrapper:
        // Opção 1: $this->db->beginTransaction();
        // Opção 2: $this->db->query("BEGIN TRANSACTION");
        // Opção 3: $this->db->execute("BEGIN TRANSACTION");
        
        // Temporário - substitua pela sua implementação:
        try {
            $this->db->select("BEGIN TRANSACTION");
        } catch (Exception $e) {
            // Se select não funcionar, tente outros métodos disponíveis
        }
    }
    
    private function commitTransaction() {
        // Ajuste conforme seu wrapper:
        try {
            $this->db->select("COMMIT TRANSACTION");
        } catch (Exception $e) {
            // Implementar conforme seu wrapper
        }
    }
    
    private function rollbackTransaction() {
        // Ajuste conforme seu wrapper:
        try {
            $this->db->select("ROLLBACK TRANSACTION");
        } catch (Exception $e) {
            // Implementar conforme seu wrapper
        }
    }
    
    private function getCurrentUser() {
        // Pegar usuário atual
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        return 'sistema';
    }
    
    private function escapeString($value) {
        // Escapar strings para SQL - ajuste conforme necessário
        if ($value === null) {
            return '';
        }
        return str_replace("'", "''", $value);
    }
    
    private function logOperation($selectedIds, $sourceTable, $recordCount) {
        try {
            // Verificar se tabela de log existe
            $tableExists = $this->db->qtdRows("
                SELECT COUNT(*) as existe 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME = 'operacoes_log'
            ") > 0;
            
            if (!$tableExists) {
                // Criar tabela de log usando comando disponível
                $createLogTable = "
                    CREATE TABLE operacoes_log (
                        id INT IDENTITY(1,1) PRIMARY KEY,
                        tipo_operacao NVARCHAR(50) NOT NULL,
                        tabela_origem NVARCHAR(50) NOT NULL,
                        ids_processados NVARCHAR(MAX) NOT NULL,
                        quantidade_registros INT NOT NULL,
                        usuario NVARCHAR(100),
                        data_operacao DATETIME DEFAULT GETDATE(),
                        observacoes NVARCHAR(MAX)
                    )
                ";
                $this->db->select($createLogTable); // ou método apropriado para DDL
            }
            
            // Inserir log usando insert
            $logSql = "
                INSERT INTO operacoes_log 
                (tipo_operacao, tabela_origem, ids_processados, quantidade_registros, usuario, observacoes)
                VALUES (
                    'conversao_txt',
                    '$sourceTable',
                    '" . implode(',', $selectedIds) . "',
                    $recordCount,
                    '" . $this->getCurrentUser() . "',
                    'Conversão realizada de $recordCount registro(s) da tabela lojas_$sourceTable para histórico'
                )
            ";
            
            $this->db->insert($logSql);
            
        } catch (Exception $e) {
            // Log não deve interromper a operação principal
            error_log("Erro ao criar log de operação: " . $e->getMessage());
        }
    }
    
    public function generateXML($data) {
        try {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><dados/>');
            $xml->addAttribute('total', count($data));
            $xml->addAttribute('data_geracao', date('Y-m-d H:i:s'));
            
            foreach ($data as $index => $row) {
                $item = $xml->addChild('loja');
                $item->addAttribute('numero', $index + 1);
                
                foreach ($row as $key => $value) {
                    // Sanitizar nome do elemento XML
                    $elementName = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
                    $child = $item->addChild($elementName, htmlspecialchars($value ? $value : ''));
                }
            }
            
            return $xml->asXML();
            
        } catch (Exception $e) {
            throw new Exception("Erro ao gerar XML: " . $e->getMessage());
        }
    }
    
    public function generateTXT($data) {
        try {
            $txt = '';
            $headers = array();
            
            // Gerar cabeçalhos (primeira linha)
            if (!empty($data)) {
                $headers = array_keys($data[0]);
                $txt .= implode("\t", $headers) . "\n";
            }
            
            // Gerar dados
            foreach ($data as $row) {
                $rowData = array();
                foreach ($headers as $header) {
                    $rowData[] = isset($row[$header]) ? $row[$header] : '';
                }
                $txt .= implode("\t", $rowData) . "\n";
            }
            
            return $txt;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao gerar TXT: " . $e->getMessage());
        }
    }
    
    public function generateSpecificTXT($data) {
        try {
            $output = '';
            $header = "# Arquivo de conversão gerado em " . date('Y-m-d H:i:s') . "\n";
            $header .= "# Total de registros: " . count($data) . "\n";
            $header .= "# Formato: 117 posições por linha\n\n";
            
            $output .= $header;
            
            foreach ($data as $row) {
                $convertedLine = $this->convertRowToSpecificFormat($row);
                
                // Salvar linha convertida no histórico se for movimentação
                if (isset($row['id'])) {
                    $this->updateHistoryWithConvertedLine($row['id'], $convertedLine);
                }
                
                $output .= $convertedLine . "\n";
            }
            
            return $output;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao gerar TXT específico: " . $e->getMessage());
        }
    }
    
    private function updateHistoryWithConvertedLine($originalId, $convertedLine) {
        try {
            // Atualizar registro no histórico com a linha convertida usando update
            $updateSql = "
                UPDATE lojas_historico 
                SET linha_convertida = '" . $this->escapeString($convertedLine) . "'
                WHERE id = (
                    SELECT TOP 1 id FROM lojas_historico 
                    WHERE observacoes LIKE '%$originalId%'
                    ORDER BY data_processamento DESC
                )
            ";
            $this->db->update($updateSql);
            
        } catch (Exception $e) {
            // Não interromper processo principal
            error_log("Erro ao atualizar linha convertida: " . $e->getMessage());
        }
    }
    
    private function convertRowToSpecificFormat($row) {
        // MAPA DE CONVERSÃO REAL
        $conversionMap = array(
            'id' => array('type' => 'numeric', 'length' => 10, 'position' => 1),
            'nome' => array('type' => 'text_hash', 'length' => 15, 'position' => 2),
            'email' => array('type' => 'text_hash', 'length' => 20, 'position' => 3),
            'telefone' => array('type' => 'numeric_clean', 'length' => 11, 'position' => 4),
            'cidade' => array('type' => 'text_hash', 'length' => 10, 'position' => 5),
            'estado' => array('type' => 'state_code', 'length' => 2, 'position' => 6),
            'data_cadastro' => array('type' => 'date_numeric', 'length' => 8, 'position' => 7),
            'status' => array('type' => 'status_code', 'length' => 1, 'position' => 8),
            'tipo' => array('type' => 'type_code', 'length' => 3, 'position' => 9),
            'categoria' => array('type' => 'category_code', 'length' => 5, 'position' => 10),
            'observacoes' => array('type' => 'text_hash', 'length' => 25, 'position' => 11)
        );
        
        $convertedValues = array();
        
        // Converter cada campo conforme o mapa
        foreach ($row as $field => $value) {
            if (isset($conversionMap[$field])) {
                $config = $conversionMap[$field];
                $convertedValue = $this->convertValue($value ? $value : '', $config['type'], $config['length']);
                $convertedValues[$config['position']] = $convertedValue;
            }
        }
        
        // Ordenar por posição
        ksort($convertedValues);
        
        // Montar linha final com 117 posições
        $finalLine = '';
        $currentPos = 0;
        
        foreach ($convertedValues as $value) {
            $finalLine .= $value;
            $currentPos += strlen($value);
            
            // Adicionar 10 espaços após os primeiros 15 números
            if ($currentPos == 15) {
                $finalLine .= str_repeat(' ', 10);
                $currentPos += 10;
            }
        }
        
        // Preencher até 117 posições se necessário
        while (strlen($finalLine) < 117) {
            $finalLine .= '0';
        }
        
        // Garantir exatamente 117 posições
        return substr($finalLine, 0, 117);
    }
    
    private function convertValue($value, $type, $maxLength) {
        switch ($type) {
            case 'numeric':
                $number = preg_replace('/[^0-9]/', '', (string)$value);
                return str_pad($number ? $number : '0', $maxLength, '0', STR_PAD_LEFT);
                
            case 'numeric_clean':
                $clean = preg_replace('/[^0-9]/', '', (string)$value);
                return str_pad($clean ? $clean : '0', $maxLength, '0', STR_PAD_LEFT);
                
            case 'text_hash':
                $hash = abs(crc32((string)$value));
                return str_pad((string)$hash, $maxLength, '0', STR_PAD_LEFT);
                
            case 'state_code':
                $stateCodes = array(
                    'AC' => '12', 'AL' => '17', 'AP' => '16', 'AM' => '23', 'BA' => '29', 'CE' => '23',
                    'DF' => '53', 'ES' => '32', 'GO' => '52', 'MA' => '21', 'MT' => '51', 'MS' => '50',
                    'MG' => '31', 'PA' => '15', 'PB' => '25', 'PR' => '41', 'PE' => '26', 'PI' => '22',
                    'RJ' => '33', 'RN' => '24', 'RS' => '43', 'RO' => '11', 'RR' => '14', 'SC' => '42',
                    'SP' => '35', 'SE' => '28', 'TO' => '27'
                );
                $upperValue = strtoupper($value);
                return isset($stateCodes[$upperValue]) ? $stateCodes[$upperValue] : '99';
                
            case 'date_numeric':
                $timestamp = strtotime($value);
                if ($timestamp === false) {
                    return str_pad('0', $maxLength, '0', STR_PAD_LEFT);
                }
                $date = date('Ymd', $timestamp);
                return str_pad($date, $maxLength, '0', STR_PAD_LEFT);
                
            case 'status_code':
                $statusCodes = array(
                    'Ativo' => '1', 'Inativo' => '0', 'Pendente' => '2', 
                    'Processado' => '3', 'Cancelado' => '4'
                );
                return isset($statusCodes[$value]) ? $statusCodes[$value] : '9';
                
            case 'type_code':
                $hash = abs(crc32((string)$value)) % 999;
                return str_pad((string)$hash, $maxLength, '0', STR_PAD_LEFT);
                
            case 'category_code':
                $hash = abs(crc32((string)$value)) % 99999;
                return str_pad((string)$hash, $maxLength, '0', STR_PAD_LEFT);
                
            default:
                $hash = abs(crc32((string)$value));
                return str_pad((string)$hash, $maxLength, '0', STR_PAD_LEFT);
        }
    }
    
    // Métodos utilitários
    
    public function getStatistics() {
        try {
            // Usando qtdRows para contar
            $stats = array();
            $stats['total_cadastramento'] = $this->db->qtdRows("SELECT COUNT(*) FROM lojas_cadastramento");
            $stats['total_descadastramento'] = $this->db->qtdRows("SELECT COUNT(*) FROM lojas_descadastramento");
            $stats['total_historico'] = $this->db->qtdRows("SELECT COUNT(*) FROM lojas_historico");
            $stats['processados_hoje'] = $this->db->qtdRows("
                SELECT COUNT(*) FROM lojas_historico 
                WHERE CAST(data_processamento AS DATE) = CAST(GETDATE() AS DATE)
            ");
            
            return $stats;
            
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar estatísticas: " . $e->getMessage());
        }
    }
    
    public function validateTableStructure() {
        try {
            $requiredTables = array('lojas_cadastramento', 'lojas_descadastramento', 'lojas_historico');
            $existingTables = array();
            
            foreach ($requiredTables as $table) {
                $exists = $this->db->qtdRows("
                    SELECT COUNT(*) as existe 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_NAME = '$table'
                ") > 0;
                
                if ($exists) {
                    $existingTables[] = $table;
                }
            }
            
            return array(
                'required' => $requiredTables,
                'existing' => $existingTables,
                'missing' => array_diff($requiredTables, $existingTables),
                'all_present' => count($existingTables) === count($requiredTables)
            );
            
        } catch (Exception $e) {
            throw new Exception("Erro ao validar estrutura das tabelas: " . $e->getMessage());
        }
    }
    
    public function __destruct() {
        $this->db = null;
    }
}
?>