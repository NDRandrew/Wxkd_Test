<?php
// models/Wxkd_DashboardModel.php
class Wxkd_DashboardModel {
    // Assumindo que a conexão com o banco já existe
    // private $db;
    
    public function __construct() {
        // $this->db = Database::getInstance();
    }
    
    public function getCardData() {
        // Placeholder para dados dos cards
        // Em implementação real, seria algo como:
        // $cadastramento = $this->db->prepare("SELECT COUNT(*) FROM lojas_cadastramento");
        // $descadastramento = $this->db->prepare("SELECT COUNT(*) FROM lojas_descadastramento");
        // $historico = $this->db->prepare("SELECT COUNT(*) FROM lojas_historico");
        
        return [
            'cadastramento' => 25,
            'descadastramento' => 18,
            'historico' => 156
        ];
    }
    
    public function getTableData() {
        // Método mantido para compatibilidade
        return $this->getTableDataByFilter('all');
    }
    
    public function getTableDataByFilter($filter = 'all') {
        // Placeholder para dados da tabela por filtro
        // Em implementação real, seria algo como:
        // switch($filter) {
        //     case 'cadastramento':
        //         $stmt = $this->db->prepare("SELECT * FROM lojas_cadastramento ORDER BY id DESC");
        //         break;
        //     case 'descadastramento':
        //         $stmt = $this->db->prepare("SELECT * FROM lojas_descadastramento ORDER BY id DESC");
        //         break;
        //     case 'historico':
        //         $stmt = $this->db->prepare("SELECT * FROM lojas_historico ORDER BY data_processamento DESC");
        //         break;
        //     default:
        //         $stmt = $this->db->prepare("
        //             SELECT *, 'cadastramento' as tipo_processo FROM lojas_cadastramento
        //             UNION ALL
        //             SELECT *, 'descadastramento' as tipo_processo FROM lojas_descadastramento
        //             ORDER BY id DESC
        //         ");
        // }
        // $stmt->execute();
        // return $stmt->fetchAll();
        
        $baseData = $this->generateSampleData();
        
        switch($filter) {
            case 'cadastramento':
                return array_filter($baseData, function($row) {
                    return $row['tipo_processo'] === 'cadastramento';
                });
            case 'descadastramento':
                return array_filter($baseData, function($row) {
                    return $row['tipo_processo'] === 'descadastramento';
                });
            case 'historico':
                return $this->getHistoricoData();
            default:
                return array_filter($baseData, function($row) {
                    return in_array($row['tipo_processo'], ['cadastramento', 'descadastramento']);
                });
        }
    }
    
    private function generateSampleData() {
        $data = [];
        $tipos = ['cadastramento', 'descadastramento'];
        
        for ($i = 1; $i <= 20; $i++) {
            $tipo = $tipos[($i - 1) % 2];
            $data[] = [
                'id' => $i,
                'nome' => "Loja $i",
                'email' => "loja$i@exemplo.com",
                'telefone' => "(11) 9999-$i$i$i$i",
                'cidade' => "Cidade $i",
                'estado' => "SP",
                'data_cadastro' => date('Y-m-d', strtotime("-$i days")),
                'status' => $i % 3 == 0 ? 'Pendente' : 'Ativo',
                'tipo' => "Tipo $i",
                'categoria' => "Categoria $i",
                'observacoes' => "Observações da loja $i",
                'tipo_processo' => $tipo
            ];
        }
        
        return $data;
    }
    
    private function getHistoricoData() {
        // Placeholder para dados do histórico
        // Em implementação real:
        // $stmt = $this->db->prepare("SELECT * FROM lojas_historico ORDER BY data_processamento DESC");
        
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'id' => $i + 100,
                'nome' => "Loja Processada $i",
                'email' => "processada$i@exemplo.com",
                'telefone' => "(11) 8888-$i$i$i$i",
                'cidade' => "Cidade Histórico $i",
                'estado' => "RJ",
                'data_cadastro' => date('Y-m-d', strtotime("-" . ($i + 30) . " days")),
                'status' => 'Processado',
                'tipo' => "Tipo Histórico $i",
                'categoria' => "Categoria Histórico $i",
                'observacoes' => "Processo realizado em " . date('Y-m-d H:i:s', strtotime("-$i days")),
                'tipo_processo' => 'historico',
                'data_processamento' => date('Y-m-d H:i:s', strtotime("-$i days")),
                'processo_origem' => $i % 2 == 0 ? 'cadastramento' : 'descadastramento'
            ];
        }
        
        return $data;
    }
    
    public function getSelectedTableData($selectedIds, $filter = 'all') {
        // Placeholder para dados selecionados por filtro
        // Em implementação real, seria algo como:
        // $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
        // switch($filter) {
        //     case 'cadastramento':
        //         $stmt = $this->db->prepare("SELECT * FROM lojas_cadastramento WHERE id IN ($placeholders)");
        //         break;
        //     case 'descadastramento':
        //         $stmt = $this->db->prepare("SELECT * FROM lojas_descadastramento WHERE id IN ($placeholders)");
        //         break;
        //     case 'historico':
        //         $stmt = $this->db->prepare("SELECT * FROM lojas_historico WHERE id IN ($placeholders)");
        //         break;
        //     default:
        //         $stmt = $this->db->prepare("
        //             SELECT *, 'cadastramento' as tipo_processo FROM lojas_cadastramento WHERE id IN ($placeholders)
        //             UNION ALL
        //             SELECT *, 'descadastramento' as tipo_processo FROM lojas_descadastramento WHERE id IN ($placeholders)
        //         ");
        // }
        // $stmt->execute($selectedIds);
        // return $stmt->fetchAll();
        
        $allData = $this->getTableDataByFilter($filter);
        $selectedData = [];
        
        foreach ($allData as $row) {
            if (in_array($row['id'], $selectedIds)) {
                $selectedData[] = $row;
            }
        }
        
        return $selectedData;
    }
    
    public function moveToHistory($selectedIds, $sourceTable) {
        // Em implementação real, seria algo como:
        // try {
        //     $this->db->beginTransaction();
        //     
        //     // 1. Buscar dados que serão movidos
        //     $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
        //     $selectStmt = $this->db->prepare("SELECT * FROM lojas_$sourceTable WHERE id IN ($placeholders)");
        //     $selectStmt->execute($selectedIds);
        //     $dataToMove = $selectStmt->fetchAll();
        //     
        //     // 2. Inserir no histórico
        //     foreach ($dataToMove as $row) {
        //         $insertStmt = $this->db->prepare("
        //             INSERT INTO lojas_historico 
        //             (nome, email, telefone, cidade, estado, data_cadastro, status, tipo, categoria, observacoes, 
        //              processo_origem, data_processamento, usuario_processamento)
        //             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        //         ");
        //         $insertStmt->execute([
        //             $row['nome'], $row['email'], $row['telefone'], $row['cidade'], $row['estado'],
        //             $row['data_cadastro'], $row['status'], $row['tipo'], $row['categoria'], $row['observacoes'],
        //             $sourceTable, $_SESSION['user_id'] ?? 'sistema'
        //         ]);
        //     }
        //     
        //     // 3. Remover da tabela original
        //     $deleteStmt = $this->db->prepare("DELETE FROM lojas_$sourceTable WHERE id IN ($placeholders)");
        //     $deleteStmt->execute($selectedIds);
        //     
        //     $this->db->commit();
        //     return true;
        // } catch (Exception $e) {
        //     $this->db->rollback();
        //     throw $e;
        // }
        
        // Placeholder - em implementação real, executaria as queries acima
        return true;
    }
    
    public function generateXML($data) {
        $xml = new SimpleXMLElement('<dados/>');
        
        foreach ($data as $row) {
            $item = $xml->addChild('item');
            foreach ($row as $key => $value) {
                $item->addChild($key, htmlspecialchars($value));
            }
        }
        
        return $xml->asXML();
    }
    
    public function generateTXT($data) {
        $txt = '';
        
        foreach ($data as $row) {
            $txt .= implode("\t", $row) . "\n";
        }
        
        return $txt;
    }
    
    public function generateSpecificTXT($data) {
        $output = '';
        
        foreach ($data as $row) {
            $convertedLine = $this->convertRowToSpecificFormat($row);
            $output .= $convertedLine . "\n";
        }
        
        return $output;
    }
    
    private function convertRowToSpecificFormat($row) {
        // MAPA DE CONVERSÃO - PLACEHOLDER
        // Este mapeamento deve ser ajustado conforme as regras da empresa
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
        $totalLength = 0;
        
        // Converter cada campo conforme o mapa
        foreach ($row as $field => $value) {
            if (isset($conversionMap[$field])) {
                $config = $conversionMap[$field];
                $convertedValue = $this->convertValue($value, $config['type'], $config['length']);
                $convertedValues[$config['position']] = $convertedValue;
                $totalLength += strlen($convertedValue);
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
                // Converter para número, preencher com zeros à esquerda
                $number = preg_replace('/[^0-9]/', '', $value);
                return str_pad($number, $maxLength, '0', STR_PAD_LEFT);
                
            case 'numeric_clean':
                // Limpar e converter telefone/números
                $clean = preg_replace('/[^0-9]/', '', $value);
                return str_pad($clean, $maxLength, '0', STR_PAD_LEFT);
                
            case 'text_hash':
                // Converter texto para hash numérico
                $hash = abs(crc32($value));
                return str_pad($hash, $maxLength, '0', STR_PAD_LEFT);
                
            case 'state_code':
                // Mapear estados para códigos numéricos
                $stateCodes = [
                    'SP' => '01', 'RJ' => '02', 'MG' => '03', 'RS' => '04',
                    'PR' => '05', 'SC' => '06', 'BA' => '07', 'GO' => '08'
                    // Adicionar todos os estados conforme necessário
                ];
                return isset($stateCodes[$value]) ? $stateCodes[$value] : '99';
                
            case 'date_numeric':
                // Converter data para formato numérico YYYYMMDD
                $date = date('Ymd', strtotime($value));
                return str_pad($date, $maxLength, '0', STR_PAD_LEFT);
                
            case 'status_code':
                // Mapear status para códigos
                $statusCodes = ['Ativo' => '1', 'Inativo' => '0'];
                return isset($statusCodes[$value]) ? $statusCodes[$value] : '9';
                
            case 'type_code':
                // Mapear tipos para códigos numéricos
                $hash = abs(crc32($value)) % 999;
                return str_pad($hash, $maxLength, '0', STR_PAD_LEFT);
                
            case 'category_code':
                // Mapear categorias para códigos numéricos
                $hash = abs(crc32($value)) % 99999;
                return str_pad($hash, $maxLength, '0', STR_PAD_LEFT);
                
            default:
                // Fallback - converter para hash numérico
                $hash = abs(crc32($value));
                return str_pad($hash, $maxLength, '0', STR_PAD_LEFT);
        }
    }
}
?>