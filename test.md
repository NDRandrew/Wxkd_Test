# TxtModal - Super Easy Integration Guide

## 🚀 Quick Setup (3 steps only!)

### Step 1: Download and Include
```php
<?php
// At the top of your main page
require_once 'TxtModal.php';
?>
```

### Step 2: Initialize (one line!)
```php
<?php
// Somewhere in your page (after database connection)
$txtModal = new TxtModal($your_db_connection);
$txtModal->setConfig('table_name', 'your_actual_table_name'); // Optional: customize table name
$txtModal->render(); // This outputs everything needed
?>
```

### Step 3: Replace Your Export Button
```html
<!-- OLD: -->
<button onclick="exportSelectedTXT()">Export TXT</button>

<!-- NEW: -->
<button onclick="TxtModal.show()">Export TXT</button>
```

## ✅ That's it! You're done!

---

## Configuration Options (Optional)

If you need to customize, you can set these options:

```php
$txtModal = new TxtModal($db_connection, array(
    'table_name' => 'lojas',                    // Your main table
    'historico_table' => 'lojas_historico',     // Your historico table  
    'id_field' => 'id',                         // Primary key field
    'chave_lote_field' => 'chave_lote',         // Historico batch field
    'debug' => true                             // Enable SQL logging
));

// Or set individually:
$txtModal->setConfig('table_name', 'my_stores');
$txtModal->setConfig('debug', true);
```

## Database Field Requirements

Your table should have these fields:
- `chave_loja` - Store key
- `nome_loja` - Store name  
- `cod_empresa` - Company code
- `cod_loja` - Store code
- `avancado` - Advanced date field
- `presenca` - Presence date field
- `unidade_negocio` - Business unit date field
- `orgao_pagador` - Paying agency date field
- `tipo_contrato` - Contract type

## Supported Database Types

Works automatically with:
- **MySQL** (resource): `mysql_connect()`
- **MySQLi** (object): `new mysqli()`  
- **PDO**: `new PDO()`

```php
// Examples:
$db = mysql_connect('localhost', 'user', 'pass');
$txtModal = new TxtModal($db);

// OR
$db = new mysqli('localhost', 'user', 'pass', 'database');
$txtModal = new TxtModal($db);

// OR  
$db = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
$txtModal = new TxtModal($db);
```

## Advanced Customization

### Custom Data Fetching
```php
class MyTxtModal extends TxtModal {
    protected function fetchData($filter, $ids) {
        // Your custom query logic here
        // Must return array of associative arrays
        
        // Example:
        $sql = "SELECT * FROM my_custom_view WHERE id IN ($ids)";
        return $this->executeQuery($sql);
    }
}

$txtModal = new MyTxtModal($db);
```

### Custom Table Structure
```php
$txtModal->setConfig('table_name', 'stores');
$txtModal->setConfig('historico_table', 'store_history'); 
$txtModal->setConfig('id_field', 'store_id');
$txtModal->setConfig('chave_lote_field', 'batch_key');
```

## Troubleshooting

### "Database connection not set"
```php
// Make sure you pass your DB connection:
$txtModal = new TxtModal($your_db_connection);
```

### "Table doesn't exist"  
```php
// Set your actual table name:
$txtModal->setConfig('table_name', 'your_real_table');
```

### "No data appears"
```php
// Enable debug mode to see SQL queries:
$txtModal->setConfig('debug', true);
// Check error_log for SQL queries
```

### Modal doesn't open
- Make sure you have jQuery and Bootstrap 3 loaded
- Check browser console for JavaScript errors
- Ensure you called `$txtModal->render()`

## What It Does Automatically

✅ **Handles AJAX requests** - No need to modify your main PHP file  
✅ **Detects selected records** - Works with your existing checkboxes  
✅ **Auto-detects filter type** - Works with historico and normal modes  
✅ **Generates proper TXT format** - Same logic as your original function  
✅ **Cross-browser compatible** - Works on all modern browsers  
✅ **Mobile responsive** - Bootstrap modal scales properly  

## Requirements

- PHP 5.3+ (works with older PHP!)
- jQuery 1.7+
- Bootstrap 3.x
- Font Awesome (for icons)

## File Size

- **TxtModal.php**: ~15KB
- **Zero dependencies** - Everything included in one file
- **No external libraries** needed

---

## Complete Working Example

```php
<!DOCTYPE html>
<html>
<head>
    <title>My System</title>
    <link rel="stylesheet" href="bootstrap.css">
    <link rel="stylesheet" href="font-awesome.css">
</head>
<body>
    <?php
    require_once 'TxtModal.php';
    
    // Your existing database connection
    $db = mysql_connect('localhost', 'user', 'pass');
    mysql_select_db('your_database', $db);
    
    // Initialize TXT Modal (one line!)
    $txtModal = new TxtModal($db);
    $txtModal->setConfig('table_name', 'stores');
    $txtModal->render();
    ?>
    
    <!-- Your existing page content -->
    <table>
        <!-- Your existing table with checkboxes -->
    </table>
    
    <!-- Updated export button -->
    <button onclick="TxtModal.show()" class="btn btn-primary">
        Export TXT
    </button>
    
    <script src="jquery.js"></script>
    <script src="bootstrap.js"></script>
</body>
</html>
```

That's literally all you need! The class handles everything else automatically.