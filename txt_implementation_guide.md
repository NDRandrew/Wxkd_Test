# Guia de Implementação - Conversão TXT Específica

## 📋 Visão Geral
Este documento explica como implementar e customizar o sistema de conversão de dados para o formato TXT específico da empresa, que gera linhas de 117 posições numéricas com espaçamento específico.

---

## 🔧 Como Funciona o Sistema

### Estrutura da Linha de Saída
- **Total**: 117 posições/números
- **Espaçamento**: 10 espaços após as primeiras 15 posições
- **Formato**: `[15 números][10 espaços][102 números restantes]`

### Fluxo de Conversão
1. Usuário seleciona linhas via checkboxes
2. Sistema converte cada campo conforme mapa de conversão
3. Valores são concatenados respeitando posições
4. Linha final é ajustada para exatamente 117 caracteres

---

## ⚙️ Configuração do Mapa de Conversão

### Localização
Arquivo: `models/Wxkd_DashboardModel.php`
Método: `convertRowToSpecificFormat()`

### Estrutura do Mapa
```php
$conversionMap = [
    'nome_campo' => [
        'type' => 'tipo_conversao',
        'length' => tamanho_final,
        'position' => posicao_na_linha
    ]
];
```

### Tipos de Conversão Disponíveis

#### 1. **numeric**
- **Uso**: IDs, números gerais
- **Funcionamento**: Remove não-números, preenche com zeros à esquerda
- **Exemplo**: `"123"` → `"0000000123"` (length=10)

#### 2. **numeric_clean**
- **Uso**: Telefones, números com formatação
- **Funcionamento**: Remove símbolos, mantém apenas dígitos
- **Exemplo**: `"(11) 99999-1234"` → `"11999991234"` (length=11)

#### 3. **text_hash**
- **Uso**: Textos que precisam ser convertidos para números
- **Funcionamento**: Gera hash CRC32 do texto
- **Exemplo**: `"João Silva"` → `"1234567890123"` (length=15)

#### 4. **state_code**
- **Uso**: Estados/UF
- **Funcionamento**: Mapeia siglas para códigos numéricos
- **Exemplo**: `"SP"` → `"01"`, `"RJ"` → `"02"`

#### 5. **date_numeric**
- **Uso**: Datas
- **Funcionamento**: Converte para formato YYYYMMDD
- **Exemplo**: `"2024-03-15"` → `"20240315"`

#### 6. **status_code**
- **Uso**: Status/situações
- **Funcionamento**: Mapeia textos para códigos
- **Exemplo**: `"Ativo"` → `"1"`, `"Inativo"` → `"0"`

#### 7. **type_code** e **category_code**
- **Uso**: Tipos e categorias
- **Funcionamento**: Hash do texto limitado por modulo
- **Exemplo**: `"Tipo A"` → `"001"` (length=3)

---

## 🔧 Personalização para sua Empresa

### 1. **Ajustar Campos e Posições**
Edite o array `$conversionMap` conforme suas colunas:

```php
$conversionMap = [
    'id_cliente' => ['type' => 'numeric', 'length' => 8, 'position' => 1],
    'cpf' => ['type' => 'numeric_clean', 'length' => 11, 'position' => 2],
    'nome_completo' => ['type' => 'text_hash', 'length' => 20, 'position' => 3],
    // ... adicionar seus campos
];
```

### 2. **Criar Novos Tipos de Conversão**
Adicione novos cases no método `convertValue()`:

```php
case 'seu_tipo_customizado':
    // Sua lógica específica aqui
    $resultado = suaFuncaoCustomizada($value);
    return str_pad($resultado, $maxLength, '0', STR_PAD_LEFT);
```

### 3. **Ajustar Mapeamentos Específicos**
Para códigos específicos da empresa, edite os arrays:

```php
// Estados
$stateCodes = [
    'SP' => '01', 'RJ' => '02', 'MG' => '03',
    // Adicionar códigos específicos da empresa
];

// Status
$statusCodes = [
    'Ativo' => '1', 'Inativo' => '0', 'Pendente' => '2',
    // Adicionar status específicos
];
```

### 4. **Modificar Formato da Linha**
Para alterar o padrão de 117 posições, edite:

```php
// Alterar posição dos espaços
if ($currentPos == 15) {  // Mudar "15" para sua posição
    $finalLine .= str_repeat(' ', 10);  // Mudar "10" para quantidade de espaços
}

// Alterar tamanho total
while (strlen($finalLine) < 117) {  // Mudar "117" para seu tamanho
    $finalLine .= '0';
}
```

---

## 🚀 Implementação Passo a Passo

### Passo 1: Mapear seus Campos
1. Liste todas as colunas da sua tabela
2. Defina o tipo de conversão para cada uma
3. Determine o tamanho de cada campo na linha final
4. Defina a posição/ordem de cada campo

### Passo 2: Configurar o Mapa
```php
$conversionMap = [
    'campo1' => ['type' => 'tipo1', 'length' => N1, 'position' => 1],
    'campo2' => ['type' => 'tipo2', 'length' => N2, 'position' => 2],
    // ... continuar para todos os campos
];
```

### Passo 3: Testar Conversões
1. Selecione algumas linhas
2. Execute a exportação TXT
3. Verifique se o formato está correto
4. Ajuste conforme necessário

### Passo 4: Validar Resultados
- Confirme que cada linha tem exatamente 117 caracteres
- Verifique se os espaços estão na posição correta
- Teste com diferentes tipos de dados

---

## 🔍 Exemplos Práticos

### Exemplo de Linha Original
```
ID: 123
Nome: João Silva
Email: joao@email.com
Telefone: (11) 99999-1234
Estado: SP
Status: Ativo
```

### Exemplo de Linha Convertida
```
000000123000004          1199999123401100000000000000000000000000000000000000000000000000000000000000000000000000000000000000
```

Quebra:
- `000000123000004` (15 posições)
- `          ` (10 espaços)
- `1199999123401100000...` (102 posições restantes)

---

## ⚠️ Considerações Importantes

### Performance
- Para grandes volumes, considere processamento em lotes
- Monitore uso de memória com muitas seleções

### Validação
- Sempre valide se a linha final tem 117 caracteres
- Implemente logs para rastrear conversões problemáticas

### Backup
- Mantenha backup dos dados originais
- Documente todas as regras de conversão

### Testes
- Teste com dados reais antes da produção
- Valide com equipe que utilizará os arquivos

---

## 🛠️ Troubleshooting

### Problema: Linha não tem 117 caracteres
**Solução**: Verifique soma dos lengths no mapeamento

### Problema: Conversão incorreta
**Solução**: Revise o tipo de conversão usado para o campo

### Problema: Erro ao exportar
**Solução**: Verifique se campos existem na tabela

### Problema: Performance lenta
**Solução**: Otimize queries e considere paginação na exportação

---

## 📞 Suporte Técnico

### Arquivos para Modificar
- `models/Wxkd_DashboardModel.php` - Lógica de conversão
- `views/Wxkd_dashboard.php` - Interface (se necessário)
- `assets/Wxkd_script.js` - Comportamento dos checkboxes

### Logs Recomendados
- Registre conversões realizadas
- Monitore tempo de processamento
- Rastreie erros de conversão

### Versionamento
- Documente mudanças no mapeamento
- Mantenha compatibilidade com versões anteriores
- Teste mudanças em ambiente de desenvolvimento

---

*Este sistema foi projetado para ser flexível e facilmente adaptável às necessidades específicas da empresa.*