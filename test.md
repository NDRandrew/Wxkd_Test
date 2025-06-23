<?php
// models/Wxkd_DashboardModel.php - IMPLEMENTAÇÃO REAL
class Wxkd_DashboardModel {
    private $db;
    
    public function __construct() {
        // Configurar conexão com banco de dados
        try {
            $host = 'localhost';
            $dbname = 'sistema_lojas';
            $username = 'root';
            $password = '';
            
            $this->db = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Erro na conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    public function getCardData() {
        try {
            $cardData = [];
            
            // Cadastramento - lojas ativas e pendentes
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM lojas_cadastramento 
                WHERE status IN ('Ativo', 'Pendente')
            ");
            $stmt->execute();
            $cardData['cadastramento'] = $stmt->fetchColumn();
            
            // Descadastramento - lojas ativas e pendentes
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as total 
                FROM lojas_descadastramento 
                WHERE status IN ('Ativo', 'Pendente')
            ");
            $stmt->execute();
            $cardData['descadastramento'] = $stmt->fetchColumn();
            
            // Histórico - todos os processos realizados
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM lojas_historico");
            $stmt->execute();
            $cardData['historico'] = $stmt->fetchColumn();
            
            return $cardData;
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar dados dos cards: " . $e->getMessage());
        }
    }
    
    public function getTableData() {
        // Método mantido para compatibilidade
        return $this->getTableDataByFilter('all');
    }
    
    public function getTableDataByFilter($filter = 'all') {
        try {
            switch($filter) {
                case 'cadastramento':
                    $stmt = $this->db->prepare("
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'cadastramento' as tipo_processo,
                               created_at, updated_at
                        FROM lojas_cadastramento 
                        WHERE status IN ('Ativo', 'Pendente')
                        ORDER BY created_at DESC
                    ");
                    break;
                    
                case 'descadastramento':
                    $stmt = $this->db->prepare("
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'descadastramento' as tipo_processo,
                               motivo_descadastramento, data_solicitacao_descadastramento,
                               created_at, updated_at
                        FROM lojas_descadastramento 
                        WHERE status IN ('Ativo', 'Pendente')
                        ORDER BY created_at DESC
                    ");
                    break;
                    
                case 'historico':
                    $stmt = $this->db->prepare("
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'historico' as tipo_processo,
                               processo_origem, data_processamento, usuario_processamento, 
                               arquivo_gerado, linha_convertida,
                               CASE 
                                 WHEN processo_origem = 'cadastramento' THEN 'Cadastrado'
                                 WHEN processo_origem = 'descadastramento' THEN 'Descadastrado'
                                 ELSE 'Processado'
                               END as status_processo
                        FROM lojas_historico 
                        ORDER BY data_processamento DESC
                    ");
                    break;
                    
                default: // 'all' - mostra cadastramento e descadastramento
                    $stmt = $this->db->prepare("
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'cadastramento' as tipo_processo,
                               created_at as data_referencia
                        FROM lojas_cadastramento 
                        WHERE status IN ('Ativo', 'Pendente')
                        
                        UNION ALL
                        
                        SELECT id, nome, email, telefone, cidade, estado, data_cadastro, 
                               status, tipo, categoria, observacoes, 'descadastramento' as tipo_processo,
                               created_at as data_referencia
                        FROM lojas_descadastramento 
                        WHERE status IN ('Ativo', 'Pendente')
                        
                        ORDER BY data_referencia DESC
                    ");
            }
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar dados da tabela: " . $e->getMessage());
        }
    }
    
    public function getSelectedTableData($selectedIds, $filter = 'all') {
        if (empty($selectedIds)) {
            return [];
        }
        
        try {
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            
            switch($filter) {
                case 'cadastramento':
                    $stmt = $this->db->prepare("
                        SELECT * FROM lojas_cadastramento 
                        WHERE id IN ($placeholders)
                    ");
                    break;
                    
                case 'descadastramento':
                    $stmt = $this->db->prepare("
                        SELECT * FROM lojas_descadastramento 
                        WHERE id IN ($placeholders)
                    ");
                    break;
                    
                case 'historico':
                    $stmt = $this->db->prepare("
                        SELECT * FROM lojas_historico 
                        WHERE id IN ($placeholders)
                    ");
                    break;
                    
                default:
                    // Para 'all', buscar em ambas as tabelas
                    $stmt = $this->db->prepare("
                        SELECT *, 'cadastramento' as tipo_processo FROM lojas_cadastramento 
                        WHERE id IN ($placeholders)
                        
                        UNION ALL
                        
                        SELECT *, 'descadastramento' as tipo_processo FROM lojas_descadastramento 
                        WHERE id IN ($placeholders)
                    ");
            }
            
            $stmt->execute($selectedIds);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar dados selecionados: " . $e->getMessage());
        }
    }
    
    public function moveToHistory($selectedIds, $sourceTable) {
        if (empty($selectedIds)) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            
            // 1. Buscar dados que serão movidos
            $selectStmt = $this->db->prepare("SELECT * FROM lojas_$sourceTable WHERE id IN ($placeholders)");
            $selectStmt->execute($selectedIds);
            $dataToMove = $selectStmt->fetchAll();
            
            if (empty($dataToMove)) {
                $this->db->rollback();
                return false;
            }
            
            // 2. Inserir no histórico
            foreach ($dataToMove as $row) {
                $insertSql = "
                    INSERT INTO lojas_historico 
                    (nome, email, telefone, cidade, estado, data_cadastro, status, tipo, categoria, 
                     observacoes, processo_origem, data_processamento, usuario_processamento, 
                     arquivo_gerado";
                
                $values = [
                    $row['nome'], $row['email'], $row['telefone'], $row['cidade'], $row['estado'],
                    $row['data_cadastro'], $row['status'], $row['tipo'], $row['categoria'], 
                    $row['observacoes'], $sourceTable, date('Y-m-d H:i:s'), 
                    $_SESSION['user_id'] ?? 'sistema',
                    'arquivo_' . date('YmdHis') . '_' . $row['id'] . '.txt'
                ];
                
                // Adicionar campos específicos do descadastramento
                if ($sourceTable === 'descadastramento') {
                    $insertSql .= ", motivo_descadastramento, data_solicitacao_descadastramento";
                    $values[] = $row['motivo_descadastramento'] ?? null;
                    $values[] = $row['data_solicitacao_descadastramento'] ?? null;
                }
                
                $insertSql .= ") VALUES (" . str_repeat('?,', count($values) - 1) . "?)";
                
                $insertStmt = $this->db->prepare($insertSql);
                $insertStmt->execute($values);
            }
            
            // 3. Remover da tabela original
            $deleteStmt = $this->db->prepare("DELETE FROM lojas_$sourceTable WHERE id IN ($placeholders)");
            $deleteStmt->execute($selectedIds);
            
            // 4. Log da operação
            $this->logOperation($selectedIds, $sourceTable, count($dataToMove));
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollback();
            throw new Exception("Erro ao mover dados para histórico: " . $e->getMessage());
        }
    }
    
    private function logOperation($selectedIds, $sourceTable, $recordCount) {
        try {
            // Criar tabela de log se não existir
            $createLogTable = "
                CREATE TABLE IF NOT EXISTS operacoes_log (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    tipo_operacao VARCHAR(50) NOT NULL,
                    tabela_origem VARCHAR(50) NOT NULL,
                    ids_processados TEXT NOT NULL,
                    quantidade_registros INT NOT NULL,
                    usuario VARCHAR(100),
                    data_operacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    observacoes TEXT
                )
            ";
            $this->db->exec($createLogTable);
            
            // Inserir log
            $logStmt = $this->db->prepare("
                INSERT INTO operacoes_log 
                (tipo_operacao, tabela_origem, ids_processados, quantidade_registros, usuario, observacoes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $logStmt->execute([
                'conversao_txt',
                $sourceTable,
                implode(',', $selectedIds),
                $recordCount,
                $_SESSION['user_id'] ?? 'sistema',
                "Conversão realizada de $recordCount registro(s) da tabela lojas_$sourceTable para histórico"
            ]);
            
        } catch (PDOException $e) {
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
                    $child = $item->addChild($elementName, htmlspecialchars($value ?? ''));
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
            $headers = [];
            
            // Gerar cabeçalhos (primeira linha)
            if (!empty($data)) {
                $headers = array_keys($data[0]);
                $txt .= implode("\t", $headers) . "\n";
            }
            
            // Gerar dados
            foreach ($data as $row) {
                $rowData = [];
                foreach ($headers as $header) {
                    $rowData[] = $row[$header] ?? '';
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
            // Atualizar registro no histórico com a linha convertida
            $stmt = $this->db->prepare("
                UPDATE lojas_historico 
                SET linha_convertida = ? 
                WHERE id = (
                    SELECT MAX(id) FROM lojas_historico 
                    WHERE observacoes LIKE CONCAT('%', ?, '%')
                )
            ");
            $stmt->execute([$convertedLine, $originalId]);
            
        } catch (PDOException $e) {
            // Não interromper processo principal
            error_log("Erro ao atualizar linha convertida: " . $e->getMessage());
        }
    }
    
    private function convertRowToSpecificFormat($row) {
        // MAPA DE CONVERSÃO REAL
        $conversionMap = [
            'id' => ['type' => 'numeric', 'length' => 10, 'position' => 1],
            'nome' => ['type' => 'text_hash', 'length' => 15, 'position' => 2],
            'email' => ['type' => 'text_hash', 'length' => 20, 'position' => 3],
            'telefone' => ['type' => 'numeric_clean', 'length' => 11, 'position' => 4],
            'cidade' => ['type' => 'text_hash', 'length' => 10, 'position' => 5],
            'estado' => ['type' => 'state_code', 'length' => 2, 'position' => 6],
            'data_cadastro' => ['type' => 'date_numeric', 'length' => 8, 'position' => 7],
            'status' => ['type' => 'status_code', 'length' => 1, 'position' => 8],
            'tipo' => ['type' => 'type_code', 'length' => 3, 'position' => 9],
            'categoria' => ['type' => 'category_code', 'length' => 5, 'position' => 10],
            'observacoes' => ['type' => 'text_hash', 'length' => 25, 'position' => 11]
        ];
        
        $convertedValues = [];
        
        // Converter cada campo conforme o mapa
        foreach ($row as $field => $value) {
            if (isset($conversionMap[$field])) {
                $config = $conversionMap[$field];
                $convertedValue = $this->convertValue($value ?? '', $config['type'], $config['length']);
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
                return str_pad($number ?: '0', $maxLength, '0', STR_PAD_LEFT);
                
            case 'numeric_clean':
                $clean = preg_replace('/[^0-9]/', '', (string)$value);
                return str_pad($clean ?: '0', $maxLength, '0', STR_PAD_LEFT);
                
            case 'text_hash':
                $hash = abs(crc32((string)$value));
                return str_pad((string)$hash, $maxLength, '0', STR_PAD_LEFT);
                
            case 'state_code':
                $stateCodes = [
                    'AC' => '12', 'AL' => '17', 'AP' => '16', 'AM' => '23', 'BA' => '29', 'CE' => '23',
                    'DF' => '53', 'ES' => '32', 'GO' => '52', 'MA' => '21', 'MT' => '51', 'MS' => '50',
                    'MG' => '31', 'PA' => '15', 'PB' => '25', 'PR' => '41', 'PE' => '26', 'PI' => '22',
                    'RJ' => '33', 'RN' => '24', 'RS' => '43', 'RO' => '11', 'RR' => '14', 'SC' => '42',
                    'SP' => '35', 'SE' => '28', 'TO' => '27'
                ];
                return $stateCodes[strtoupper($value)] ?? '99';
                
            case 'date_numeric':
                $timestamp = strtotime($value);
                if ($timestamp === false) {
                    return str_pad('0', $maxLength, '0', STR_PAD_LEFT);
                }
                $date = date('Ymd', $timestamp);
                return str_pad($date, $maxLength, '0', STR_PAD_LEFT);
                
            case 'status_code':
                $statusCodes = [
                    'Ativo' => '1', 'Inativo' => '0', 'Pendente' => '2', 
                    'Processado' => '3', 'Cancelado' => '4'
                ];
                return $statusCodes[$value] ?? '9';
                
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
    
    // Métodos utilitários adicionais
    
    public function getStatistics() {
        try {
            $stats = [];
            
            // Estatísticas gerais
            $stmt = $this->db->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM lojas_cadastramento) as total_cadastramento,
                    (SELECT COUNT(*) FROM lojas_descadastramento) as total_descadastramento,
                    (SELECT COUNT(*) FROM lojas_historico) as total_historico,
                    (SELECT COUNT(*) FROM lojas_historico WHERE DATE(data_processamento) = CURDATE()) as processados_hoje
            ");
            $stmt->execute();
            $stats = $stmt->fetch();
            
            // Top 5 cidades com mais lojas
            $stmt = $this->db->prepare("
                SELECT cidade, COUNT(*) as total FROM (
                    SELECT cidade FROM lojas_cadastramento
                    UNION ALL
                    SELECT cidade FROM lojas_descadastramento
                    UNION ALL
                    SELECT cidade FROM lojas_historico
                ) as todas_cidades
                GROUP BY cidade
                ORDER BY total DESC
                LIMIT 5
            ");
            $stmt->execute();
            $stats['top_cidades'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao buscar estatísticas: " . $e->getMessage());
        }
    }
    
    public function validateTableStructure() {
        try {
            $requiredTables = ['lojas_cadastramento', 'lojas_descadastramento', 'lojas_historico'];
            $existingTables = [];
            
            foreach ($requiredTables as $table) {
                $stmt = $this->db->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if ($stmt->rowCount() > 0) {
                    $existingTables[] = $table;
                }
            }
            
            return [
                'required' => $requiredTables,
                'existing' => $existingTables,
                'missing' => array_diff($requiredTables, $existingTables),
                'all_present' => count($existingTables) === count($requiredTables)
            ];
            
        } catch (PDOException $e) {
            throw new Exception("Erro ao validar estrutura das tabelas: " . $e->getMessage());
        }
    }
    
    public function __destruct() {
        $this->db = null;
    }
}
?>