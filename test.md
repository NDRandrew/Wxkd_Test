# Manual TxtModal - Super Simple Integration

## 🎯 Perfect! No Database Needed

This version is a pure form modal where users manually input all data. Much simpler!

## 🚀 Integration (Just 2 Steps!)

### Step 1: Add to Your Controller
```php
public function index() {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    
    // ADD THESE 2 LINES:
    $txtModal = new TxtModal();
    $txtModal->render();
    
    $cardData = $this->model->getCardData();
    $tableData = $this->model->getTableDataByFilter($filter);
    $contractData = $this->model->contractDateCheck();
}
```

### Step 2: Add Button Anywhere
```html
<button onclick="TxtModal.show()" class="btn btn-primary">
    <i class="fa fa-edit"></i> Gerar TXT Manual
</button>
```

## ✅ Done! That's literally it!

---

## 🎨 What Users See

### **Form Fields:**
- **Código Empresa** (required, max 10 digits)
- **Código Loja** (required, max 5 digits)  
- **Nome Loja** (optional, for reference)

### **Correspondence Type Selection:**
- ☐ **AV** - Avançado
- ☐ **PR** - Presença  
- ☐ **UN** - Unidade Negócio
- ☐ **OP** - Órgão Pagador

### **OP Services (when OP selected):**
- ☑ Holerite INSS
- ☑ Consulta INSS
- ☐ Segunda Via Cartão
- ☑ Saque Cheque

### **Records Management:**
- Add multiple records
- View added records in table
- Remove individual records
- Clear all records

## 🔧 Features

### ✨ **Smart Validation:**
- Required field validation
- Duplicate record detection
- Maximum length validation
- Numeric-only input for codes

### 🎯 **User-Friendly:**
- Enter key to add records quickly
- Auto-focus after adding
- Visual feedback with badges
- Record counter

### 📁 **TXT Generation:**
- Same logic as your original function
- Proper formatting (101 characters per line)
- File download with timestamp
- BOM support option

## 🛠 Configuration Options

```php
$txtModal = new TxtModal(array(
    'modal_title' => 'Meu Gerador TXT',
    'max_records' => 100, // Maximum records allowed
    'default_limits' => array(
        'AV' => array('dinheiro' => '2000000', 'cheque' => '2000000', 'retirada' => '500000', 'saque' => '500000'),
        // ... customize limits
    )
));
```

## 📋 Generated TXT Logic

### **AV/UN/PR Types:**
- Line 1: `19|01` - Depósito Dinheiro
- Line 2: `19|02` - Depósito Cheque  
- Line 3: `28|04` - Recarga/Retirada
- Line 4: `29|04` - Saque Cheque

### **OP Type (based on selected services):**
- `14|04` - Holerite INSS (if checked)
- `18|04` - Consulta INSS (if checked)
- `31|04` - Segunda Via Cartão (if checked)
- `29|04` - Saque Cheque (if checked)

## 💡 Usage Flow

1. **User clicks button** → Modal opens
2. **User fills form** → Empresa, Loja, Type
3. **User clicks "Adicionar"** → Record added to list
4. **Repeat steps 2-3** → Add more records
5. **User clicks "Gerar TXT"** → File downloads

## 🎉 Benefits

- **Zero database dependencies** - Pure client-side form
- **No server requests** - Everything happens in browser
- **Flexible** - Users can add any data they want
- **Fast** - Instant feedback and generation
- **Reusable** - Users can add multiple records easily

## 📁 Complete Example

```php
<?php
require_once 'TxtModal.php';

class DashboardController {
    public function index() {
        // Your existing code...
        
        // Add TXT Modal (2 lines only!)
        $txtModal = new TxtModal();
        $txtModal->render();
        
        // Your existing code continues...
        $this->loadView('dashboard');
    }
}
?>
```

```html
<!-- In your view template -->
<div class="toolbar">
    <button onclick="TxtModal.show()" class="btn btn-primary">
        <i class="fa fa-edit"></i> Criar TXT Manual
    </button>
    <!-- Your other buttons... -->
</div>
```

## 🔍 No Dependencies

- **No database connection** needed
- **No model methods** required  
- **No AJAX requests** made
- **No server-side processing** needed

Perfect for when users need to create TXT files with custom data that's not in your database!

## 🎮 User Experience

- **Type empresa/loja codes**
- **Select correspondence type** 
- **Click Add** (or press Enter)
- **Repeat for more records**
- **Click Generate TXT**
- **File downloads automatically**

Simple, fast, and intuitive! 🚀