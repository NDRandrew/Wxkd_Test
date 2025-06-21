# Guia de Implementação - Sistema Dashboard Wxkd

## 📋 Visão Geral
Este documento contém instruções detalhadas para implementar o sistema de dashboard Wxkd no ambiente de produção da empresa. O sistema foi desenvolvido seguindo a arquitetura MVC com módulos independentes para facilitar manutenção e escalabilidade.

---

## 🏗️ Estrutura do Sistema

### Arquivos e Pastas
```
sistema_wxkd/
├── models/
│   └── Wxkd_DashboardModel.php     # Modelo de dados e lógica de negócio
├── views/
│   └── Wxkd_dashboard.php          # Interface principal (Controller + View)
└── assets/
    ├── Wxkd_script.js              # Módulos JavaScript
    └── Wxkd_style.css              # Estilos personalizados
```

### Tecnologias Utilizadas
- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript (jQuery)
- **Framework CSS**: Bootstrap 5.3.0
- **Arquitetura**: MVC (Model-View-Controller)

---

## ⚙️ Pré-requisitos

### Servidor Web
- ✅ Apache 2.4+ ou Nginx 1.18+
- ✅ PHP 7.4+ (recomendado PHP 8.0+)
- ✅ Extensões PHP: PDO, mysqli (para conexão com banco)
- ✅ mod_rewrite habilitado (Apache)

### Banco de Dados
- ✅ MySQL 5.7+ ou MariaDB 10.3+
- ✅ Conexão existente configurada no ambiente

### Navegadores Suportados
- ✅ Chrome 90+, Firefox 88+, Safari 14+, Edge 90+

---

## 📦 Processo de Instalação

### Passo 1: Preparação do Ambiente
1. **Criar diretório do projeto**
   ```bash
   mkdir /caminho/para/servidor/sistema_wxkd
   cd /caminho/para/servidor/sistema_wxkd
   ```

2. **Criar estrutura de pastas**
   ```bash
   mkdir models views assets
   ```

### Passo 2: Upload dos Arquivos
1. **Fazer upload dos arquivos** via FTP/SFTP ou painel de controle:
   - `models/Wxkd_DashboardModel.php`
   - `views/Wxkd_dashboard.php`
   - `assets/Wxkd_script.js`
   - `assets/Wxkd_style.css`

2. **Verificar permissões** (Linux/Unix):
   ```bash
   chmod 644 models/*.php views/*.php assets/*
   chmod 755 models/ views/ assets/
   ```

### Passo 3: Configuração da Conexão com Banco
1. **Editar o arquivo** `models/Wxkd_DashboardModel.php`
2. **Localizar a seção de conexão** (linhas 6-8):
   ```php
   // Assumindo que a conexão com o banco já existe
   // private $db;
   ```

3. **Substituir pela conexão real**:
   ```php
   private $db;
   
   public function __construct() {
       // Exemplo de conexão - ajustar conforme ambiente da empresa
       $this->db = new PDO(
           'mysql:host=localhost;dbname=nome_banco;charset=utf8',
           'usuario_banco',
           'senha_banco',
           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
       );
   }
   ```

### Passo 4: Configuração das Queries
1. **Editar método `getCardData()`** (linha 15):
   ```php
   public function getCardData() {
       $stmt = $this->db->prepare("
           SELECT 
               (SELECT COUNT(*) FROM sua_tabela) as card1,
               (SELECT COUNT(*) FROM sua_tabela WHERE status = 'ativo') as card2,
               (SELECT COUNT(*) FROM sua_tabela WHERE status = 'pendente') as card3
       ");
       $stmt->execute();
       return $stmt->fetch(PDO::FETCH_ASSOC);
   }
   ```

2. **Editar método `getTableData()`** (linha 27):
   ```php
   public function getTableData() {
       $stmt = $this->db->prepare("
           SELECT 
               id, nome, email, telefone, cidade, estado, 
               data_cadastro, status, tipo, categoria, observacoes
           FROM sua_tabela_principal 
           ORDER BY id DESC 
           LIMIT 100
       ");
       $stmt->execute();
       return $stmt->fetchAll(PDO::FETCH_ASSOC);
   }
   ```

---

## 🔧 Configurações Avançadas

### Ajuste de Colunas da Tabela
Se sua tabela tiver colunas diferentes, editar `views/Wxkd_dashboard.php` na seção da tabela (linhas 98-108):

```html
<th class="sortable" data-column="0">Sua_Coluna_1</th>
<th class="sortable" data-column="1">Sua_Coluna_2</th>
<!-- ... adicionar/remover conforme necessário -->
```

### Customização de Exportação
Para alterar o formato TXT, editar `generateTXT()` em `Wxkd_DashboardModel.php`:

```php
public function generateTXT($data) {
    $txt = '';
    foreach ($data as $row) {
        // Customizar formato conforme necessário
        $txt .= implode("|", $row) . "\n"; // Exemplo: separador por pipe
    }
    return $txt;
}
```

### Configuração de Segurança
1. **Validação de dados**:
   ```php
   // Adicionar no início do controller
   if (!isset($_SESSION['user_logged'])) {
       header('Location: /login.php');
       exit;
   }
   ```

2. **Sanitização adicional**:
   ```php
   // No modelo, sempre usar prepared statements
   $stmt = $this->db->prepare("SELECT * FROM tabela WHERE id = ?");
   $stmt->execute([$id]);
   ```

---

## 🌐 Configuração do Servidor Web

### Apache (.htaccess)
Criar arquivo `.htaccess` na raiz do projeto:
```apache
RewriteEngine On
DirectoryIndex views/Wxkd_dashboard.php

# Proteção de arquivos sensíveis
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>

<FilesMatch "^(Wxkd_DashboardModel\.php)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>
```

### Nginx
Adicionar ao arquivo de configuração:
```nginx
location /sistema_wxkd/ {
    try_files $uri $uri/ /sistema_wxkd/views/Wxkd_dashboard.php?$query_string;
}

location ~ /sistema_wxkd/models/ {
    deny all;
    return 403;
}
```

---

## 📊 Testes e Validação

### Checklist de Implementação
- [ ] ✅ Upload de todos os arquivos concluído
- [ ] ✅ Estrutura de pastas criada corretamente
- [ ] ✅ Conexão com banco configurada
- [ ] ✅ Queries adaptadas para tabelas da empresa
- [ ] ✅ Permissões de arquivo configuradas
- [ ] ✅ Acesso via navegador funcionando
- [ ] ✅ Cards exibindo dados corretos
- [ ] ✅ Tabela carregando dados do banco
- [ ] ✅ Funcionalidades de pesquisa operacionais
- [ ] ✅ Ordenação por colunas funcionando
- [ ] ✅ Exportação XML/TXT testada
- [ ] ✅ Responsividade em dispositivos móveis

### URLs de Teste
- **Principal**: `https://seudominio.com/sistema_wxkd/views/Wxkd_dashboard.php`
- **Export XML**: `https://seudominio.com/sistema_wxkd/views/Wxkd_dashboard.php?action=exportXML`
- **Export TXT**: `https://seudominio.com/sistema_wxkd/views/Wxkd_dashboard.php?action=exportTXT`

---

## 🚨 Solução de Problemas

### Erro: "Página não encontrada"
- **Verificar**: Caminho dos arquivos e estrutura de pastas
- **Solução**: Confirmar que `views/Wxkd_dashboard.php` existe e tem permissões corretas

### Erro: "Conexão com banco falhou"
- **Verificar**: Credenciais do banco em `Wxkd_DashboardModel.php`
- **Solução**: Testar conexão separadamente e ajustar parâmetros

### Cards/Tabela sem dados
- **Verificar**: Queries SQL no modelo
- **Solução**: Executar queries diretamente no banco para testar

### CSS/JavaScript não carregam
- **Verificar**: Caminhos relativos em `Wxkd_dashboard.php`
- **Solução**: Ajustar paths conforme estrutura do servidor

### Performance lenta
- **Verificar**: Quantidade de dados na tabela
- **Solução**: Adicionar paginação ou LIMIT nas queries

---

## 📈 Manutenção e Atualizações

### Backup Regular
```bash
# Backup dos arquivos
tar -czf wxkd_backup_$(date +%Y%m%d).tar.gz sistema_wxkd/

# Backup do banco (ajustar conforme necessário)
mysqldump -u usuario -p nome_banco > wxkd_db_backup_$(date +%Y%m%d).sql
```

### Monitoramento
- **Logs de erro**: Verificar logs do Apache/PHP regularmente
- **Performance**: Monitorar tempo de resposta das queries
- **Uso**: Acompanhar estatísticas de acesso

### Atualizações Futuras
Para adicionar novos módulos, seguir o padrão:
1. Criar novo arquivo `assets/Wxkd_nome_modulo.js`
2. Adicionar CSS específico em `assets/Wxkd_style.css`
3. Incluir na view principal `views/Wxkd_dashboard.php`

---

## 👥 Contatos e Suporte

### Desenvolvedor Responsável
- **Implementação**: [Seu Nome/Equipe]
- **Data**: [Data da implementação]
- **Versão**: 1.0

### Documentação Técnica
- **Arquitetura**: MVC com módulos independentes
- **Compatibilidade**: PHP 7.4+, MySQL 5.7+
- **Dependências**: Bootstrap 5.3.0, jQuery 3.7.0

---

*Este documento deve ser atualizado sempre que houver modificações no sistema.*
