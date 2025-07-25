# TXT Generation Modal Class Documentation

## Overview

The `TxtGenerationModal` class creates an interactive modal that allows users to selectively generate TXT files by choosing which `COD_EMPRESA` and `COD_LOJA` records to include. This provides more granular control compared to the automatic `extractTXTfromXML` function.

## Features

- **Interactive Selection**: Users can check/uncheck individual records
- **Select All/None**: Bulk selection options
- **Real-time Counter**: Shows selected record count
- **Type Indicators**: Visual badges showing correspondence type (AV, PR, UN, OP)
- **Error Handling**: Proper error display and loading states
- **File Download**: Automatic TXT file download with proper formatting

## Integration Steps

### 1. Include the JavaScript Class

Add the class to your existing JavaScript file or include it separately:

```javascript
// The TxtGenerationModal class code goes here
```

### 2. Replace Export Function Call

Instead of calling `exportSelectedTXT()`, use:

```javascript
// Old way
// exportSelectedTXT();

// New way
showTxtGenerationModal();
```

### 3. Update HTML Button

Update your export button to call the new function:

```html
<button onclick="showTxtGenerationModal()" class="btn btn-primary">
    <i class="fa fa-file-text-o"></i> Gerar TXT Customizado
</button>
```

### 4. PHP Backend Changes

Create a new action in your `wxkd.php` file to handle the modal data request:

```php
<?php
// In your wxkd.php file, add this new action
if ($action === 'getTxtModalData') {
    $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
    $ids = isset($_GET['ids']) ? $_GET['ids'] : '';
    
    // Start XML response
    header('Content-Type: text/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<response>';
    
    try {
        // Your existing logic to get data based on filter and ids
        // This should be similar to your existing export logic
        
        if ($filter === 'historico') {
            // Handle historico data
            $idsArray = explode(',', $ids);
            // Query your database for historico records
            // Example:
            $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja, 
                           avancado, presenca, unidade_negocio, orgao_pagador,
                           tipo_contrato
                    FROM your_historico_table 
                    WHERE chave_lote IN (" . implode(',', array_map('intval', $idsArray)) . ")";
        } else {
            // Handle normal data
            $idsArray = explode(',', $ids);
            // Query your database for normal records
            // Example:
            $sql = "SELECT chave_loja, nome_loja, cod_empresa, cod_loja,
                           avancado, presenca, unidade_negocio, orgao_pagador,
                           tipo_contrato
                    FROM your_main_table 
                    WHERE id IN (" . implode(',', array_map('intval', $idsArray)) . ")";
        }
        
        // Execute query (adjust based on your database connection method)
        $result = mysql_query($sql); // or mysqli_query, etc.
        
        if ($result) {
            echo '<success>true</success>';
            echo '<data>';
            
            while ($row = mysql_fetch_assoc($result)) {
                echo '<row>';
                echo '<cod_empresa>' . htmlspecialchars($row['cod_empresa']) . '</cod_empresa>';
                echo '<cod_loja>' . htmlspecialchars($row['cod_loja']) . '</cod_loja>';
                echo '<nome_loja>' . htmlspecialchars($row['nome_loja']) . '</nome_loja>';
                echo '<chave_loja>' . htmlspecialchars($row['chave_loja']) . '</chave_loja>';
                
                // Include date fields for tipo correspondente calculation
                echo '<avancado>' . htmlspecialchars($row['avancado']) . '</avancado>';
                echo '<presenca>' . htmlspecialchars($row['presenca']) . '</presenca>';
                echo '<unidade_negocio>' . htmlspecialchars($row['unidade_negocio']) . '</unidade_negocio>';
                echo '<orgao_pagador>' . htmlspecialchars($row['orgao_pagador']) . '</orgao_pagador>';
                
                echo '<tipo_contrato>' . htmlspecialchars($row['tipo_contrato']) . '</tipo_contrato>';
                echo '</row>';
            }
            
            echo '</data>';
        } else {
            echo '<success>false</success>';
            echo '<e>Erro ao consultar dados</e>';
        }
        
    } catch (Exception $e) {
        echo '<success>false</success>';
        echo '<e>' . htmlspecialchars($e->getMessage()) . '</e>';
    }
    
    echo '</response>';
    exit;
}
?>
```

## Class Methods

### Core Methods

- **`init()`**: Initializes the modal and event listeners
- **`show(selectedIds, filter)`**: Opens the modal with specified data
- **`loadData()`**: Fetches data from the server
- **`populateModal($xml)`**: Populates the modal with fetched data
- **`generateTXT()`**: Generates and downloads the TXT file

### Helper Methods

- **`updateSelectedCount()`**: Updates the selection counter
- **`updateSelectAllState()`**: Manages select all checkbox state
- **`getTipoCorrespondenteFromXML($row)`**: Determines correspondence type
- **`formatToTXTLine(...)`**: Formats data into TXT line format
- **`downloadTXTFile(content, filename)`**: Handles file download

## Usage Example

```javascript
// Initialize (done automatically on document ready)
TxtGenerationModal.init();

// Show modal with selected records
const selectedIds = CheckboxModule.getSelectedIds();
const currentFilter = 'cadastramento';
TxtGenerationModal.show(selectedIds, currentFilter);
```

## Modal Structure

The modal includes:

1. **Header**: Title and close button
2. **Body**: 
   - Select all checkbox and counter
   - Loading indicator
   - Error display area
   - Data table with checkboxes
3. **Footer**: Cancel and Generate buttons

## Data Flow

1. User selects records and clicks export button
2. `showTxtGenerationModal()` is called
3. Modal opens and sends AJAX request to `wxkd.php?action=getTxtModalData`
4. PHP returns XML with record data
5. Modal populates with checkboxes for each record
6. User selects desired records
7. `generateTXT()` processes selected records
8. TXT file is generated and downloaded

## Error Handling

- **Loading States**: Shows spinner while fetching data
- **Server Errors**: Displays server error messages
- **Client Errors**: Handles XML parsing and validation errors
- **Empty Selection**: Prevents generation with no records selected

## Customization

### Styling
The modal uses Bootstrap classes and can be customized with additional CSS:

```css
#txtGenerationModal .modal-dialog {
    max-width: 900px; /* Adjust modal width */
}

.record-checkbox {
    transform: scale(1.2); /* Larger checkboxes */
}
```

### Behavior
Modify the `generateTXT()` method to change TXT generation logic:

```javascript
generateTXT: function() {
    // Custom logic here
    // Access selected data via this.currentData[index]
}
```

## Browser Compatibility

- Requires jQuery
- Bootstrap modal support
- Modern browsers with Blob and URL.createObjectURL support
- File download API support

## Security Considerations

- Input sanitization in PHP backend
- SQL injection prevention with prepared statements
- XSS prevention with `htmlspecialchars()`
- Validate user permissions before data access

## Troubleshooting

### Common Issues

1. **Modal doesn't open**: Check if `TxtGenerationModal.init()` was called
2. **No data loads**: Verify PHP action name and XML response format
3. **Download fails**: Check browser file download permissions
4. **Select all not working**: Ensure event delegation is properly set up

### Debug Mode

Add debug logging:

```javascript
// Add to loadData method
.done(function(xmlData) {
    console.log('XML Response:', xmlData); // Debug line
    // ... rest of code
})
```

## Performance Notes

- Modal reuses DOM elements for efficiency
- Large datasets (>1000 records) may impact performance
- Consider pagination for very large result sets
- XML parsing is synchronous and may block UI briefly