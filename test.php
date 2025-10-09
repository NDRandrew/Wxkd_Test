// Add this helper function at the top of ajax_encerramento.php (after json_encode_custom)

function formatMessagesForJson($messages) {
    if (!is_array($messages)) {
        return $messages;
    }
    
    foreach ($messages as &$message) {
        // Convert DateTime objects to strings
        if (isset($message['MESSAGE_DATE'])) {
            if (is_object($message['MESSAGE_DATE'])) {
                // DateTime object - convert to string
                $message['MESSAGE_DATE'] = $message['MESSAGE_DATE']->format('Y-m-d H:i:s');
            }
        }
        
        // Also handle other potential DateTime fields
        $dateFields = ['DATA_RECEPCAO', 'DATA_RETIRADA_EQPTO', 'DATA_BLOQUEIO', 'DATA_CAD'];
        foreach ($dateFields as $field) {
            if (isset($message[$field]) && is_object($message[$field])) {
                $message[$field] = $message[$field]->format('Y-m-d H:i:s');
            }
        }
    }
    
    return $messages;
}

// Then update the load_chat handler to use this function:

// Load chat messages for specific group
if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        
        // Get messages filtered by group
        $messages = $model->getChatMessagesByGroup($chat_id, $userGroup, $target_group);
        
        // Ensure messages is always an array
        if (!$messages) {
            $messages = [];
        } else if (!is_array($messages)) {
            $messages = [$messages];
        }
        
        // *** ADD THIS LINE ***
        // Convert DateTime objects to strings
        $messages = formatMessagesForJson($messages);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'chat_id' => $chat_id,
            'messages' => $messages,
            'target_group' => $target_group
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


---------


// Add this helper function at the top (after json_encode_custom function)

function formatMessagesForJson($messages) {
    if (!is_array($messages)) {
        return $messages;
    }
    
    foreach ($messages as &$message) {
        // Convert DateTime objects to strings
        if (isset($message['MESSAGE_DATE'])) {
            if (is_object($message['MESSAGE_DATE'])) {
                $message['MESSAGE_DATE'] = $message['MESSAGE_DATE']->format('Y-m-d H:i:s');
            }
        }
    }
    
    return $messages;
}

// Replace the chat handlers in ajax_encerramento.php

// Load chat messages for specific group
if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        
        // Get messages filtered by group
        $messages = $model->getChatMessagesByGroup($chat_id, $userGroup, $target_group);
        
        // Ensure messages is always an array
        if (!$messages) {
            $messages = [];
        } else if (!is_array($messages)) {
            $messages = [$messages];
        }
        
        // Convert DateTime objects to strings for JSON
        $messages = formatMessagesForJson($messages);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'chat_id' => $chat_id,
            'messages' => $messages,
            'target_group' => $target_group
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Send chat message to specific group
if (isset($_POST['acao']) && $_POST['acao'] == 'send_message') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        require_once '../permissions_config.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $mensagem = isset($_POST['mensagem']) ? $_POST['mensagem'] : '';
        $target_group = isset($_POST['target_group']) ? $_POST['target_group'] : '';
        $cod_usu = isset($_SESSION['cod_usu']) ? intval($_SESSION['cod_usu']) : 0;
        $userGroup = getUserGroup($cod_usu);
        
        if (empty($mensagem) && (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Mensagem vazia']);
            exit;
        }
        
        // Validate target group
        $valid_targets = ['OP_MANAGEMENT', 'COM_MANAGEMENT', 'BLOQ_MANAGEMENT', 'ENC_MANAGEMENT'];
        if (!in_array($target_group, $valid_targets)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Grupo inválido']);
            exit;
        }
        
        // Validate permissions
        if ($userGroup !== 'ENC_MANAGEMENT' && $target_group !== 'ENC_MANAGEMENT') {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Sem permissão para enviar para este grupo']);
            exit;
        }
        
        $model = new Analise();
        $chat_id = $model->createChatIfNotExists($cod_solicitacao);
        
        $anexo = 0;
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $anexo = 1;
        }
        
        // Determine sender and recipient groups
        $sender_group = $userGroup;
        $recipient_group = $target_group;
        
        $result = $model->sendChatMessageToGroup($chat_id, $mensagem, $cod_usu, $sender_group, $recipient_group, $anexo);
        
        if ($result && $anexo) {
            $message_id = $model->getLastMessageId();
            $upload_dir = 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\view\encerramento\anexos\\';
            $user_dir = $upload_dir . $cod_usu . '\\';
            
            if (!file_exists($user_dir)) {
                mkdir($user_dir, 0777, true);
            }
            
            $file_name = $message_id . '_' . basename($_FILES['arquivo']['name']);
            $file_path = $user_dir . $file_name;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $file_path)) {
                $updateQuery = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL_CHAT 
                               SET MENSAGEM = CASE WHEN MENSAGEM IS NULL OR MENSAGEM = '' 
                                                   THEN '[FILE:" . addslashes($file_name) . "]' 
                                                   ELSE MENSAGEM + ' [FILE:" . addslashes($file_name) . "]' 
                                              END
                               WHERE MESSAGE_ID = " . $message_id;
                $model->update($updateQuery);
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'message' => 'Mensagem enviada'
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


---------

# DateTime Object Fix - [object Object] Issue

## Problem
Chat dates appearing as `[object Object]` instead of formatted dates.

## Root Cause

### SQL Server → PHP → JSON → JavaScript Flow

1. **SQL Server** returns `MESSAGE_DATE` as datetime type
2. **PHP** converts it to a `DateTime` object
3. **json_encode()** serializes DateTime object as:
   ```json
   {
     "date": "2025-01-15 14:30:45.000000",
     "timezone_type": 3,
     "timezone": "America/Sao_Paulo"
   }
   ```
4. **JavaScript** receives an object, not a string
5. **Date display** tries to show the object → `[object Object]`

## Solution

Convert DateTime objects to strings in PHP BEFORE sending to JavaScript.

### Step 1: Add Helper Function

Add this at the top of `ajax_encerramento.php` (after `json_encode_custom`):

```php
function formatMessagesForJson($messages) {
    if (!is_array($messages)) {
        return $messages;
    }
    
    foreach ($messages as &$message) {
        // Convert MESSAGE_DATE from DateTime object to string
        if (isset($message['MESSAGE_DATE'])) {
            if (is_object($message['MESSAGE_DATE'])) {
                // Convert to 'YYYY-MM-DD HH:MM:SS' format
                $message['MESSAGE_DATE'] = $message['MESSAGE_DATE']->format('Y-m-d H:i:s');
            }
        }
    }
    
    return $messages;
}
```

### Step 2: Use Helper in load_chat Handler

Find the `load_chat` handler and add the conversion:

```php
if (isset($_POST['acao']) && $_POST['acao'] == 'load_chat') {
    // ... existing code ...
    
    $messages = $model->getChatMessagesByGroup($chat_id, $userGroup, $target_group);
    
    // Ensure messages is array
    if (!$messages) {
        $messages = [];
    } else if (!is_array($messages)) {
        $messages = [$messages];
    }
    
    // *** ADD THIS LINE ***
    $messages = formatMessagesForJson($messages);
    
    // Send response
    echo json_encode_custom([
        'success' => true,
        'messages' => $messages,
        // ...
    ]);
}
```

## How It Works

### Before Fix
```
PHP DateTime Object → JSON:
{
  "MESSAGE_DATE": {
    "date": "2025-01-15 14:30:45.000000",
    "timezone_type": 3,
    "timezone": "America/Sao_Paulo"
  }
}

JavaScript receives: Object
Display shows: [object Object]
```

### After Fix
```
PHP DateTime Object → Convert to String → JSON:
{
  "MESSAGE_DATE": "2025-01-15 14:30:45"
}

JavaScript receives: String "2025-01-15 14:30:45"
Parse with .replace(' ', 'T') → Valid Date
Display shows: 15/01 14:30
```

## Testing

### 1. Check PHP Output
Add debug before JSON response:
```php
error_log('First message: ' . print_r($messages[0], true));
```

**Before fix:**
```
MESSAGE_DATE => DateTime Object (...)
```

**After fix:**
```
MESSAGE_DATE => 2025-01-15 14:30:45
```

### 2. Check JavaScript Console
```javascript
console.log('MESSAGE_DATE:', data.messages[0]?.MESSAGE_DATE);
console.log('Type:', typeof data.messages[0]?.MESSAGE_DATE);
```

**Before fix:**
```
MESSAGE_DATE: {date: "2025-01-15 14:30:45.000000", ...}
Type: object
```

**After fix:**
```
MESSAGE_DATE: 2025-01-15 14:30:45
Type: string
```

### 3. Visual Check
Chat should now show:
```
✅ 15/01 14:30
✅ 16/01 09:15

NOT:
❌ [object Object]
```

## Alternative Solutions

### Option A: Cast in SQL Query (Not Used)
```php
// In Model's getChatMessagesByGroup()
$query = "SELECT 
            CONVERT(VARCHAR, m.MESSAGE_DATE, 120) as MESSAGE_DATE,
            ...";
```
**Pros:** Handles at database level  
**Cons:** Must modify all queries, loses DateTime features

### Option B: JSON Serializable Class (Overkill)
```php
class Message implements JsonSerializable {
    public function jsonSerialize() {
        return ['MESSAGE_DATE' => $this->date->format('Y-m-d H:i:s')];
    }
}
```
**Pros:** Object-oriented, reusable  
**Cons:** Too complex for this use case

### Option C: Helper Function (Our Choice) ✅
```php
function formatMessagesForJson($messages) {
    // Convert DateTime to string
}
```
**Pros:** Simple, maintainable, one place to fix  
**Cons:** Must remember to call it

## Why This Happens

PHP's `DateTime` class implements `JsonSerializable` interface by default, which makes it serialize as an object with internal properties rather than as a simple string.

### Default Behavior:
```php
$date = new DateTime('2025-01-15 14:30:45');
echo json_encode(['date' => $date]);
// Output: {"date":{"date":"2025-01-15 14:30:45.000000","timezone_type":3,"timezone":"UTC"}}
```

### Fixed Behavior:
```php
$date = new DateTime('2025-01-15 14:30:45');
echo json_encode(['date' => $date->format('Y-m-d H:i:s')]);
// Output: {"date":"2025-01-15 14:30:45"}
```

## Complete File Location

**File:** `control/encerramento/roteamento/ajax_encerramento.php`

**Add at top:** (after `json_encode_custom` function)
```php
function formatMessagesForJson($messages) { ... }
```

**Use in handler:** (inside `load_chat` if block)
```php
$messages = formatMessagesForJson($messages);
```

## If Still Showing [object Object]

### Check 1: Function Added?
```php
// In ajax_encerramento.php
if (!function_exists('formatMessagesForJson')) {
    die('ERROR: formatMessagesForJson not defined');
}
```

### Check 2: Function Called?
```php
// In load_chat handler
error_log('Before format: ' . gettype($messages[0]['MESSAGE_DATE']));
$messages = formatMessagesForJson($messages);
error_log('After format: ' . gettype($messages[0]['MESSAGE_DATE']));
// Should show: object → string
```

### Check 3: Correct Handler?
Make sure you're updating the NEW `load_chat` handler with groups, not the old one.

### Check 4: Cache?
Clear browser cache and do a hard refresh (Ctrl + F5)

## Summary

The `[object Object]` issue occurs because PHP DateTime objects get serialized as complex objects in JSON. The fix converts them to simple strings (`Y-m-d H:i:s` format) before JSON encoding, which JavaScript can then properly parse and display.

**Add one function, call it once, problem solved!** ✅