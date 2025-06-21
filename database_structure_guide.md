# Guia de Estrutura do Banco de Dados - Sistema de Lojas

## 📋 Visão Geral
Este documento descreve a estrutura das tabelas necessárias para o sistema de gerenciamento de lojas com funcionalidades de cadastramento, descadastramento e histórico.

---

## 🗃️ Estrutura das Tabelas

### 1. **Tabela: `lojas_cadastramento`**
Armazena lojas que estão pendentes para cadastramento.

```sql
CREATE TABLE lojas_cadastramento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cidade VARCHAR(100),
    estado CHAR(2),
    data_cadastro DATE,
    status ENUM('Ativo', 'Pendente', 'Inativo') DEFAULT 'Pendente',
    tipo VARCHAR(50),
    categoria VARCHAR(50),
    observacoes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nome (nome),
    INDEX idx_status (status),
    INDEX idx_cidade (cidade),
    INDEX idx_estado (estado)
);
```

### 2. **Tabela: `lojas_descadastramento`**
Armazena lojas que estão pendentes para descadastramento.

```sql
CREATE TABLE lojas_descadastramento (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cidade VARCHAR(100),
    estado CHAR(2),
    data_cadastro DATE,
    status ENUM('Ativo', 'Pendente', 'Inativo') DEFAULT 'Pendente',
    tipo VARCHAR(50),
    categoria VARCHAR(50),
    observacoes TEXT,
    motivo_descadastramento TEXT,
    data_solicitacao_descadastramento DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_nome (nome),
    INDEX idx_status (status),
    INDEX idx_data_solicitacao (data_solicitacao_descadastramento)
);
```

### 3. **Tabela: `lojas_historico`**
Armazena o histórico de todos os processos realizados (cadastramentos e descadastramentos convertidos).

```sql
CREATE TABLE lojas_historico (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefone VARCHAR(20),
    cidade VARCHAR(100),
    estado CHAR(2),
    data_cadastro DATE,
    status VARCHAR(50),
    tipo VARCHAR(50),
    categoria VARCHAR(50),
    observacoes TEXT,
    
    -- Campos específicos do histórico
    processo_origem ENUM('cadastramento', 'descadastramento') NOT NULL,
    data_processamento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_processamento VARCHAR(100),
    arquivo_gerado VARCHAR(255),
    linha_convertida TEXT,
    
    -- Campos adicionais para descadastramento
    motivo_descadastramento TEXT,
    data_solicitacao_descadastramento DATE,
    
    INDEX idx_processo_origem (processo_origem),
    INDEX idx_data_processamento (data_processamento),
    INDEX idx_usuario (usuario_processamento),
    INDEX idx_nome (nome)
);
```

---

## 🔄 Fluxo de Dados

### **Cadastramento**
1. Lojas ficam na tabela `lojas_cadastramento`
2. Usuário seleciona lojas e clica em "Converter para TXT"
3. Sistema gera arquivo TXT
4. Registros são movidos para `lojas_historico`
5. Registros são removidos de `lojas_cadastramento`

### **Descadastramento**
1. Lojas ficam na tabela `lojas_descadastramento`
2. Usuário seleciona lojas e clica em "Converter para TXT"
3. Sistema gera arquivo TXT
4. Registros são movidos para `lojas_historico`
5. Registros são removidos de `lojas_descadastramento`

### **Histórico**
1. Exibe todos os processos já realizados
2. Permite apenas visualização e exportação
3. Não permite conversão (não remove registros)

---

## 📊 Queries Principais

### **Contadores para Cards**
```sql
-- Card Cadastramento
SELECT COUNT(*) as total_cadastramento 
FROM lojas_cadastramento 
WHERE status IN ('Ativo', 'Pendente');

-- Card Descadastramento  
SELECT COUNT(*) as total_descadastramento 
FROM lojas_descadastramento 
WHERE status IN ('Ativo', 'Pendente');

-- Card Histórico
SELECT COUNT(*) as total_historico 
FROM lojas_historico;
```

### **Listagem por Filtro**
```sql
-- Filtro Cadastramento
SELECT * FROM lojas_cadastramento 
ORDER BY created_at DESC;

-- Filtro Descadastramento
SELECT * FROM lojas_descadastramento 
ORDER BY created_at DESC;

-- Filtro Histórico
SELECT *, 
       CASE 
         WHEN processo_origem = 'cadastramento' THEN 'Cadastrado'
         WHEN processo_origem = 'descadastramento' THEN 'Descadastrado'
       END as status_processo
FROM lojas_historico 
ORDER BY data_processamento DESC;

-- Todos (Cadastramento + Descadastramento)
SELECT *, 'cadastramento' as tipo_processo 
FROM lojas_cadastramento
UNION ALL
SELECT *, 'descadastramento' as tipo_processo 
FROM lojas_descadastramento
ORDER BY created_at DESC;
```

### **Movimentação para Histórico**
```sql
-- 1. Inserir no histórico (exemplo para cadastramento)
INSERT INTO lojas_historico 
(nome, email, telefone, cidade, estado, data_cadastro, status, tipo, categoria, 
 observacoes, processo_origem, usuario_processamento, arquivo_gerado, linha_convertida)
SELECT nome, email, telefone, cidade, estado, data_cadastro, status, tipo, categoria,
       observacoes, 'cadastramento', ?, ?, ?
FROM lojas_cadastramento 
WHERE id IN (?, ?, ?);

-- 2. Remover da tabela original
DELETE FROM lojas_cadastramento 
WHERE id IN (?, ?, ?);
```

---

## ⚙️ Implementação no Código

### **Atualizar `getCardData()`**
```php
public function getCardData() {
    $cardData = [];
    
    // Cadastramento
    $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM lojas_cadastramento WHERE status IN ('Ativo', 'Pendente')");
    $stmt->execute();
    $cardData['cadastramento'] = $stmt->fetchColumn();
    
    // Descadastramento
    $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM lojas_descadastramento WHERE status IN ('Ativo', 'Pendente')");
    $stmt->execute();
    $cardData['descadastramento'] = $stmt->fetchColumn();
    
    // Histórico
    $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM lojas_historico");
    $stmt->execute();
    $cardData['historico'] = $stmt->fetchColumn();
    
    return $cardData;
}
```

### **Atualizar `getTableDataByFilter()`**
```php
public function getTableDataByFilter($filter = 'all') {
    switch($filter) {
        case 'cadastramento':
            $stmt = $this->db->prepare("SELECT * FROM lojas_cadastramento ORDER BY created_at DESC");
            break;
            
        case 'descadastramento':
            $stmt = $this->db->prepare("SELECT * FROM lojas_descadastramento ORDER BY created_at DESC");
            break;
            
        case 'historico':
            $stmt = $this->db->prepare("
                SELECT *, 
                       CASE 
                         WHEN processo_origem = 'cadastramento' THEN 'Cadastrado'
                         WHEN processo_origem = 'descadastramento' THEN 'Descadastrado'
                       END as status_processo
                FROM lojas_historico 
                ORDER BY data_processamento DESC
            ");
            break;
            
        default:
            $stmt = $this->db->prepare("
                SELECT *, 'cadastramento' as tipo_processo FROM lojas_cadastramento
                UNION ALL
                SELECT *, 'descadastramento' as tipo_processo FROM lojas_descadastramento
                ORDER BY created_at DESC
            ");
    }
    
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
```

### **Implementar `moveToHistory()`**
```php
public function moveToHistory($selectedIds, $sourceTable) {
    try {
        $this->db->beginTransaction();
        
        // 1. Buscar dados que serão movidos
        $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
        $selectStmt = $this->db->prepare("SELECT * FROM lojas_$sourceTable WHERE id IN ($placeholders)");
        $selectStmt->execute($selectedIds);
        $dataToMove = $selectStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2. Inserir no histórico
        $insertSql = "
            INSERT INTO lojas_historico 
            (nome, email, telefone, cidade, estado, data_cadastro, status, tipo, categoria, 
             observacoes, processo_origem, usuario_processamento, arquivo_gerado, linha_convertida";
        
        // Adicionar campos específicos se for descadastramento
        if ($sourceTable === 'descadastramento') {
            $insertSql .= ", motivo_descadastramento, data_solicitacao_descadastramento";
        }
        
        $insertSql .= ") VALUES ";
        
        foreach ($dataToMove as $index => $row) {
            if ($index > 0) $insertSql .= ", ";
            $insertSql .= "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";
            if ($sourceTable === 'descadastramento') {
                $insertSql .= ", ?, ?";
            }
            $insertSql .= ")";
        }
        
        $insertStmt = $this->db->prepare($insertSql);
        
        $params = [];
        foreach ($dataToMove as $row) {
            $params = array_merge($params, [
                $row['nome'], $row['email'], $row['telefone'], $row['cidade'], $row['estado'],
                $row['data_cadastro'], $row['status'], $row['tipo'], $row['categoria'], $row['observacoes'],
                $sourceTable, $_SESSION['user_id'] ?? 'sistema', 
                'arquivo_' . date('YmdHis') . '.txt',
                '' // linha_convertida será preenchida pelo processo de conversão
            ]);
            
            if ($sourceTable === 'descadastramento') {
                $params = array_merge($params, [
                    $row['motivo_descadastramento'] ?? '',
                    $row['data_solicitacao_descadastramento'] ?? null
                ]);
            }
        }
        
        $insertStmt->execute($params);
        
        // 3. Remover da tabela original
        $deleteStmt = $this->db->prepare("DELETE FROM lojas_$sourceTable WHERE id IN ($placeholders)");
        $deleteStmt->execute($selectedIds);
        
        $this->db->commit();
        return true;
        
    } catch (Exception $e) {
        $this->db->rollback();
        throw new Exception("Erro ao mover dados para histórico: " . $e->getMessage());
    }
}
```

---

## 🚀 Scripts de Migração

### **Script Completo de Criação**
```sql
-- Criar as três tabelas
CREATE DATABASE IF NOT EXISTS sistema_lojas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_lojas;

-- [Inserir aqui as três CREATE TABLE mostradas acima]

-- Inserir dados de exemplo para cadastramento
INSERT INTO lojas_cadastramento (nome, email, telefone, cidade, estado, tipo, categoria, observacoes) VALUES
('Loja Exemplo 1', 'exemplo1@email.com', '(11) 99999-0001', 'São Paulo', 'SP', 'Varejo', 'Categoria A', 'Loja para cadastramento 1'),
('Loja Exemplo 2', 'exemplo2@email.com', '(11) 99999-0002', 'Rio de Janeiro', 'RJ', 'Atacado', 'Categoria B', 'Loja para cadastramento 2');

-- Inserir dados de exemplo para descadastramento  
INSERT INTO lojas_descadastramento (nome, email, telefone, cidade, estado, tipo, categoria, observacoes, motivo_descadastramento) VALUES
('Loja Saindo 1', 'saindo1@email.com', '(11) 88888-0001', 'Belo Horizonte', 'MG', 'Varejo', 'Categoria C', 'Loja para descadastramento 1', 'Fechamento da unidade'),
('Loja Saindo 2', 'saindo2@email.com', '(11) 88888-0002', 'Porto Alegre', 'RS', 'Atacado', 'Categoria D', 'Loja para descadastramento 2', 'Mudança de segmento');
```

---

## ⚠️ Considerações Importantes

### **Backup e Segurança**
- Sempre fazer backup antes de mover dados
- Implementar logs de auditoria
- Validar integridade dos dados antes da movimentação

### **Performance**
- Usar transações para operações de movimentação
- Implementar índices apropriados
- Considerar paginação para grandes volumes

### **Validações**
- Verificar se registros existem antes de mover
- Validar permissões do usuário
- Implementar rollback em caso de erro

---

*Esta estrutura garante integridade dos dados e rastreabilidade completa de todos os processos realizados no sistema.*