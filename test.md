# TXT Generator Modal Class - Documentation

## Overview

The `TxtGeneratorModal` class creates a user-friendly modal interface that allows users to generate custom TXT files by inputting their own numeric values, similar to the `extractTXTfromXML` functionality but with user-defined content.

## Features

- **PHP 5.3 Compatible**: Works with older PHP versions without requiring modern features
- **Modal Interface**: Clean, responsive modal using Bootstrap styling
- **Form Validation**: Client-side and server-side validation for numeric inputs
- **File Generation**: Automatically creates TXT files with user data
- **Session Messages**: Feedback system for success/error notifications
- **Safe File Handling**: Secure filename sanitization and directory management

## Installation & Setup

### 1. Prerequisites
- PHP 5.3 or higher
- Bootstrap CSS/JS framework
- jQuery (for modal functionality)
- Session support enabled

### 2. Directory Structure
```
your-project/
├── generated_files/          # Auto-created for TXT output
├── TxtGeneratorModal.php     # The main class file
└── your-page.php            # Your implementation page
```

### 3. Basic Implementation

```php
<?php
require_once 'TxtGeneratorModal.php';

// Initialize the class
$txtGenerator = new TxtGeneratorModal();
$txtGenerator->init();
?>

<!DOCTYPE html>
<html>
<head>
    <title>TXT Generator</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="path/to/bootstrap.css">
</head>
<body>
    <!-- Trigger Button -->
    <?php $txtGenerator->displayTriggerButton(); ?>
    
    <!-- Modal -->
    <?php $txtGenerator->displayModal(); ?>
    
    <!-- Bootstrap JS & jQuery -->
    <script src="path/to/jquery.js"></script>
    <script src="path/to/bootstrap.js"></script>
</body>
</html>
```

## User Input Fields

The modal includes the following numeric input fields:

| Field Name | Label | Description |
|------------|-------|-------------|
| `empresa` | Empresa | Company identifier |
| `codigoLoja` | Código Loja | Store code |
| `codTransacao` | Código Transação | Transaction code |
| `meioPagamento` | Meio Pagamento | Payment method |
| `valorMinimo` | Valor Mínimo | Minimum value |
| `valorMaximo` | Valor Máximo | Maximum value |
| `situacaoMeioPagamento` | Situação Meio Pagamento | Payment method status |
| `valorTotalMaxDiario` | Valor Total Max Diário | Maximum daily total |
| `TipoManutencao` | Tipo Manutenção | Maintenance type |
| `quantidadeTotalMaxDiaria` | Quantidade Total Max Diária | Maximum daily quantity |

## Class Methods

### Public Methods

#### `displayModal()`
Renders the complete modal HTML with form inputs and JavaScript validation.

#### `displayTriggerButton()`
Shows a Bootstrap button that opens the modal when clicked.

#### `init()`
Initializes the class, starts session, and processes form submissions.

#### `processForm()`
Handles form submission, validates data, and generates the TXT file.

### Private Methods

#### `generateTxtFile($data, $filename)`
Creates the TXT file with user data and returns success/error status.

#### `createTxtContent($data)`
Formats the data into a readable TXT format with headers and timestamps.

#### `redirect($url)`
PHP 5.3 compatible redirect using JavaScript (since header modifications are limited).

## Generated TXT Format

The generated TXT file follows this structure:

```
# Arquivo TXT Personalizado
# Gerado em: 2025-01-28 15:30:45

Empresa                       : 123
Código Loja                   : 456
Código Transação              : 789
Meio Pagamento                : 1
Valor Mínimo                  : 10.50
Valor Máximo                  : 1000.00
Situação Meio Pagamento       : 2
Valor Total Max Diário        : 5000.00
Tipo Manutenção               : 3
Quantidade Total Max Diária   : 100

# Fim do arquivo
```

## Validation Features

### Client-Side Validation
- Checks that all fields are filled
- Validates numeric input format
- Visual feedback with red borders for invalid fields
- Alert messages for validation errors

### Server-Side Validation
- Verifies all required fields are present
- Ensures numeric values using `is_numeric()`
- Sanitizes filename input
- Session-based error messaging

## File Management

### File Location
- Files are saved in the `generated_files/` directory
- Directory is automatically created if it doesn't exist
- Permissions set to 0755 for proper access

### Filename Handling
- Automatic `.txt` extension addition
- Special characters replaced with underscores
- Safe filename generation prevents directory traversal

## Error Handling

The class provides comprehensive error handling:

- **Validation Errors**: Field-specific error messages
- **File Creation Errors**: Permission and directory issues
- **Session Messages**: User-friendly feedback system
- **Exception Handling**: Graceful error recovery

## Customization Options

### Modifying Fields
To add/remove fields, update the `$fields` array:

```php
private $fields = array(
    'empresa' => 'Empresa',
    'customField' => 'Custom Field Label',
    // Add your fields here
);
```

### Styling
The modal uses Bootstrap classes. Customize by:
- Modifying CSS classes in `displayModal()`
- Adding custom CSS rules
- Changing modal size with `modal-lg` or `modal-sm`

### File Path
Change the output directory by modifying:
```php
$filePath = 'your_custom_path/' . $safeFilename;
```

## Troubleshooting

### Common Issues

1. **"Permission denied" errors**
   - Ensure web server has write permissions to the target directory
   - Check PHP `open_basedir` restrictions

2. **Modal not opening**
   - Verify jQuery and Bootstrap JS are loaded
   - Check browser console for JavaScript errors

3. **Form not submitting**
   - Ensure all numeric fields have valid values
   - Check that filename field is not empty

4. **Session messages not displaying**
   - Verify session support is enabled in PHP
   - Check that `session_start()` is called

### Debugging Tips

- Enable PHP error reporting: `error_reporting(E_ALL);`
- Check file permissions: `ls -la generated_files/`
- Verify form data: `var_dump($_POST);`
- Test file writing: `is_writable('generated_files/')`

## Security Considerations

- Input validation prevents SQL injection
- Filename sanitization prevents directory traversal
- Session-based messaging prevents XSS
- File permissions restrict unauthorized access

## Browser Compatibility

- Internet Explorer 8+
- Chrome (all versions)
- Firefox (all versions)
- Safari (all versions)
- Mobile browsers with Bootstrap support