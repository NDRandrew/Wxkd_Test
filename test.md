# TxtModal - MVC Integration Guide

## 🎯 Perfect for Your Setup!

The updated TxtModal works seamlessly with your MVC structure and custom MSSQL class.

## 🚀 Integration Steps

### Step 1: Add TxtModal to Your Controller

**Your current controller:**
```php
public function index() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // ADD THESE 3 LINES:
    $txtModal = new TxtModal($this->model);
    $txtModal->setConfig('table_name', 'your_actual_table_name'); 
    $txtModal->render(); 
    
    $cardData = $this->model->getCardData();
    $tableData = $this->model->getTableDataByFilter($filter);
    $contractData = $this->model->contractDateCheck();
}
```

### Step 2: Update Your Export Button

**In your view/template:**
```html
<!-- OLD: -->
<button onclick="exportSelectedTXT()">Export TXT</button>

<!-- NEW: -->
<button onclick="TxtModal.show()">Export TXT</button>
```

### Step 3: Add Method to Your Model (Optional but Recommended)

**Add this to your `Wxkd_DashboardModel`:**
```php
public function getTxtModalData($filter, $ids) {
    $idsArray = explode(',', $ids);
    $idsArray = array_map('intval', $idsArray);
    $idsString = implode(',', $idsArray);
    
    if ($filter === 'historico') {
        $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja, 
                       avancado, presenca, unidade_negocio, orgao_pagador,
                       tipo_contrato, data_conclusao, tipo_correspondente
                FROM your_historico_table 
                WHERE chave_lote IN ($idsString)";
    } else {
        $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja,
                       avancado, presenca, unidade_negocio, orgao_pagador,
                       tipo_contrato
                FROM your_main_table 
                WHERE id IN ($idsString)";
    }
    
    // Use your existing MSSQL connection
    return $this->sql->query($sql); // or whatever method your MSSQL class uses
}
```

## ✅ That's it! Zero additional changes needed.

---

## 📋 Configuration for Your Setup

```php
$txtModal = new TxtModal($this->model);

// Configure table names
$txtModal->setConfig('table_name', 'lojas');
$txtModal->setConfig('historico_table', 'lojas_historico');

// Configure field names if different
$txtModal->setConfig('id_field', 'id');
$txtModal->setConfig('chave_lote_field', 'chave_lote');

// Enable debug to see SQL queries
$txtModal->setConfig('debug', true);

$txtModal->render();
```

## 🔧 How It Works with Your MSSQL Class

The TxtModal automatically detects your setup:

1. **Finds your model**: `$this->model`
2. **Finds your MSSQL connection**: `$this->model->sql`
3. **Tries common methods**: `query()`, `fetch_all()`, `get_results()`
4. **Falls back gracefully**: If methods don't exist, shows helpful error

### Supported MSSQL Class Methods

The class will try these methods on your `$this->model->sql` object:

```php
// Method 1: Direct query returning array
$result = $connection->query($sql);

// Method 2: Fetch all method
$result = $connection->fetch_all($sql);

// Method 3: Get results (returns objects, auto-converted to arrays)
$result = $connection->get_results($sql);
```

## 🐛 Troubleshooting Your Setup

### 1. "Database connection not set"
```php
// Make sure you pass your model:
$txtModal = new TxtModal($this->model);
```

### 2. "Method not found" or no data
**Add the recommended method to your model (Step 3 above), or:**

Check what methods your MSSQL class has:
```php
// Enable debug to see what's happening
$txtModal->setConfig('debug', true);

// Check your MSSQL class methods
var_dump(get_class_methods($this->model->sql));
```

### 3. SQL Server specific syntax
Update the model method to use proper SQL Server syntax:
```php
public function getTxtModalData($filter, $ids) {
    // Use SQL Server syntax
    $sql = "SELECT TOP 1000 chave_loja, nome_loja, cod_empresa, cod_loja
            FROM your_table 
            WHERE id IN ($idsString)";
    
    return $this->sql->query($sql);
}
```

## 📁 Complete Example

**Your Controller (`DashboardController.php`):**
```php
<?php
require_once 'TxtModal.php';

class DashboardController {
    private $model;
    
    public function __construct() {
        $this->model = new Wxkd_DashboardModel();
        $this->model->Wxkd_Construct(); 
    }
    
    public function index() {
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        
        // Initialize TXT Modal
        $txtModal = new TxtModal($this->model);
        $txtModal->setConfig('table_name', 'lojas');
        $txtModal->setConfig('historico_table', 'lojas_historico');
        $txtModal->setConfig('debug', false); // Set to true for debugging
        $txtModal->render();
        
        // Your existing code
        $cardData = $this->model->getCardData();
        $tableData = $this->model->getTableDataByFilter($filter);
        $contractData = $this->model->contractDateCheck();
        
        // Load your view
        $this->loadView('dashboard', compact('cardData', 'tableData', 'contractData'));
    }
}
?>
```

**Your Model Addition (`Wxkd_DashboardModel.php`):**
```php
public function getTxtModalData($filter, $ids) {
    try {
        $idsArray = explode(',', $ids);
        $idsArray = array_map('intval', $idsArray);
        $idsString = implode(',', $idsArray);
        
        if ($filter === 'historico') {
            $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja, 
                           avancado, presenca, unidade_negocio, orgao_pagador,
                           tipo_contrato, data_conclusao, tipo_correspondente
                    FROM historico_lojas 
                    WHERE chave_lote IN ($idsString)";
        } else {
            $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja,
                           avancado, presenca, unidade_negocio, orgao_pagador,
                           tipo_contrato
                    FROM lojas 
                    WHERE id IN ($idsString)";
        }
        
        // Use your MSSQL connection
        return $this->sql->query($sql);
        
    } catch (Exception $e) {
        error_log("TxtModal Query Error: " . $e->getMessage());
        return false;
    }
}
```

**Your View Button:**
```html
<button onclick="TxtModal.show()" class="btn btn-primary">
    <i class="fa fa-file-text-o"></i> Gerar TXT Customizado
</button>
```

## 🎉 Benefits for Your Architecture

- **Zero breaking changes** - Your existing code remains untouched
- **MVC compliant** - Follows your existing patterns
- **Database agnostic** - Works with your custom MSSQL class
- **Auto-detection** - Finds your checkboxes and data automatically
- **Error handling** - Graceful fallbacks if methods don't exist
- **Debug friendly** - Enable debug mode to see exactly what's happening

This integration respects your existing MVC structure and works with your custom database abstraction layer!