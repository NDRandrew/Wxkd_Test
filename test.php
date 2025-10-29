Looking at your code, I can see two main issues:

1. **API errors not being stored**: When `verifySingleCNPJ` fails, the error is tracked in JavaScript but not passed to the PHP TXT generation
2. **Chat badge location**: Need to move from modal tab to table cell and show per-chat count

Here are the fixes:

## 1. Fix Error Storage in Database

**encerramento_massa.php** - Update to receive and store API errors:

```php
if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
    $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
    $apiErrors = json_decode($_POST['api_errors'] ?? '[]', true); // NEW
    
    $result = $handler->generateFromSelection($solicitacoes);
    
    // Merge API errors with TXT generation errors
    if (!empty($apiErrors)) {
        foreach ($apiErrors as $apiError) {
            $handler->errors[] = $apiError;
        }
    }
    
    // Insert all errors
    if (!empty($handler->getErrors())) {
        $handler->insertErrorToDatabase($handler->getErrors(), $result['nomeArquivo']);
    }
    
    ob_end_clean();
    
    if ($result['success']) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
        header('Content-Length: ' . strlen($result['conteudo']));
        echo $result['conteudo'];
    }
    exit;
}
```

**analise_encerramento.js** - Pass API errors to TXT generation:

```javascript
verifyAllCNPJs(cnpjList, function(processed, total, updated, errors) {
    updateProgressModal(progressModal, processed, total, updated, errors);
}).then(function(verificationResult) {
    console.log('[10] CNPJ verification complete', verificationResult);
    
    setTimeout(function() {
        progressModal.remove();
    }, 2000);
    
    showNotification('Verificação concluída! Gerando TXT...', 'success');
    
    // Generate TXT with API errors
    $.ajax({
        url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
        method: 'POST',
        data: {
            acao: 'gerar_txt_selection',
            solicitacoes: JSON.stringify(solicitacoes),
            api_errors: JSON.stringify(verificationResult.apiErrors || []) // NEW
        },
        success: function(text, status, xhr) {
            const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'ENCERRAMENTO_' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '.txt';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();
            
            if (verificationResult.errors > 0) {
                showNotification(
                    'TXT gerado. ' + verificationResult.errors + ' erro(s) - email enviado.', 
                    'warning'
                );
            } else {
                showNotification('Arquivo TXT gerado com sucesso!', 'success');
            }
        },
        error: function(xhr, status, error) {
            showNotification('Erro ao gerar TXT: ' + error, 'error');
        }
    });
});
```

## 2. Move Chat Badge to Table Cell

**analise_encerramento_model.class.php** - Add method to get unread count per chat:

```php
public function getUnreadChatCountForSolicitacao($cod_solicitacao, $cod_usu, $userGroup) {
    $query = "SELECT COUNT(*) as TOTAL 
            FROM MESU..ENCERRAMENTO_TB_PORTAL_CHAT c
            JOIN MESU..ENCERRAMENTO_TB_PORTAL p ON c.CHAT_ID = p.LOCAL_CHAT
            WHERE p.COD_SOLICITACAO = " . intval($cod_solicitacao) . "
            AND (c.READ_FLAG IS NULL OR c.READ_FLAG = 0)
            AND c.REMETENTE != " . intval($cod_usu) . "
            AND (
                (c.RECIPIENT_GROUP = '" . addslashes($userGroup) . "')
                OR 
                (c.SENDER_GROUP = '" . addslashes($userGroup) . "' AND c.RECIPIENT_GROUP = 'ENC_MANAGEMENT')
            )";
    $dados = $this->sql->select($query);
    return $dados[0]['TOTAL'];
}
```

**ajax_encerramento.php** - Update renderTableCell to include badge:

```php
private function renderTableCell($row) {
    require_once '../permissions_config.php';
    $cod_usu = isset($_SESSION['cod_usu']) ? $_SESSION['cod_usu'] : 0;
    $canViewBulk = canViewBulkActions($cod_usu);
    $userGroup = getUserGroup($cod_usu);
    
    $cells = '';
    
    if ($canViewBulk) {
        $cells .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important;">
                <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
                    <input class="form-check-input position-static m-0" type="checkbox" onclick="event.stopPropagation();" />
                </label>
            </th>';
    } else {
        $cells .= '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important; width: 30px;"></th>';
    }
    
    // Get unread count for this chat
    $unreadCount = $this->model->getUnreadChatCountForSolicitacao(
        $row['COD_SOLICITACAO'], 
        $cod_usu, 
        $userGroup
    );
    
    $badgeHtml = $unreadCount > 0 
        ? '<span class="badge badge-sm bg-red text-red-fg ms-1">' . $unreadCount . '</span>' 
        : '';
    
    $cells .= '<th><span style="display: block; text-align: center;">' 
            . htmlspecialchars($row['COD_SOLICITACAO']) 
            . $badgeHtml 
            . '</span></th>';
    
    // Rest of cells...
    $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['COD_AG']) . '/' . htmlspecialchars($row['NR_PACB']) . '</span></td>';
    // ... continue with other cells
    
    return $cells;
}
```

**ajax_encerramento.php** - Remove badge from chat tab in buildChat:

```php
private function buildChat($codSolicitacao) {
    // ... existing code ...
    
    $html = '<div class="card flex-fill">
                // ... existing structure ...
                <li class="nav-item">
                    <a href="#tabs-chat-' . $codSolicitacao . '" class="nav-link" data-bs-toggle="tab" onclick="initializeChat(' . $codSolicitacao . ')">
                        <svg>...</svg>
                        Chat
                    </a>
                </li>
                // ... rest of structure
            </div>';
    
    return $html;
}
```

These changes will:
1. Store API verification errors in the database
2. Show unread message count per chat in the table beside COD_SOLICITACAO
3. Update badge count when messages are marked as read