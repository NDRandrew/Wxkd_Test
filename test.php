## Fix: Bulk Encerramento Modal & Database Updates

**1. In E.txt (analise_encerramento.php)**, add bulk encerramento modal before `</body>`:

```html
<!-- Bulk Encerramento Modal -->
<div class="modal fade" id="bulkEncerramentoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-2">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M12 9v2m0 4v.01"/>
                        <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                    </svg>
                    Confirmar Encerramento em Massa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bulkEncerramentoContent">
                <!-- Content will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="bulkEncerramentoConfirm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-1">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                        <path d="M18.364 5.636l-12.728 12.728"/>
                    </svg>
                    Encerrar Todos
                </button>
            </div>
        </div>
    </div>
</div>
```

**2. In J.txt**, update `sendBulkEmail` to handle encerramento:

```javascript
window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    const btnElement = event.target.closest('button');

    if (tipo === 'encerramento') {
        // Special handling for encerramento
        checkBulkEncerramentoStatus(solicitacoes, btnElement);
        return;
    }

    const formData = new FormData();
    formData.append('acao', 'check_bulk_status');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.has_pendentes) {
            showBulkWarningModal(data.pendentes, tipo, solicitacoes, btnElement);
        } else {
            executeBulkEmail(tipo, solicitacoes, btnElement);
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        executeBulkEmail(tipo, solicitacoes, btnElement);
    });
};

function checkBulkEncerramentoStatus(solicitacoes, btnElement) {
    const formData = new FormData();
    formData.append('acao', 'check_encerramento_status');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showBulkEncerramentoModal(data.pendentes, data.list, solicitacoes, btnElement);
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        showNotification('Erro ao verificar status', 'error');
    });
}

function showBulkEncerramentoModal(pendentes, lojasList, solicitacoes, btnElement) {
    let content = '<div class="alert alert-danger mb-3">';
    content += '<strong>Atenção!</strong> Você está prestes a encerrar <strong>' + solicitacoes.length + '</strong> correspondente(s) no BACEN.';
    content += '</div>';
    
    // Show list of lojas
    content += '<div class="mb-3"><strong>Correspondentes:</strong></div>';
    content += '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
    content += '<table class="table table-bordered table-sm">';
    content += '<thead class="thead-encerramento">';
    content += '<tr><th>Chave Loja</th><th>Nome</th><th>Status Pendentes</th></tr>';
    content += '</thead><tbody>';
    
    for (const loja of lojasList) {
        const hasPendentes = pendentes[loja.chave_loja];
        content += '<tr>';
        content += '<td class="text-center"><strong>' + loja.chave_loja + '</strong></td>';
        content += '<td>' + loja.nome_loja + '</td>';
        content += '<td>';
        if (hasPendentes && hasPendentes.length > 0) {
            content += '<span class="badge bg-warning text-dark">' + hasPendentes.join(', ') + '</span>';
        } else {
            content += '<span class="badge bg-success">Todos efetuados</span>';
        }
        content += '</td>';
        content += '</tr>';
    }
    
    content += '</tbody></table></div>';
    
    if (Object.keys(pendentes).length > 0) {
        content += '<div class="alert alert-warning mt-3 mb-0">';
        content += '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon me-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v2m0 4v.01"/><path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/></svg>';
        content += 'Alguns correspondentes possuem status pendentes. Deseja continuar?';
        content += '</div>';
    }
    
    document.getElementById('bulkEncerramentoContent').innerHTML = content;
    
    const modalElement = document.getElementById('bulkEncerramentoModal');
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    
    const confirmBtn = document.getElementById('bulkEncerramentoConfirm');
    const newConfirmBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    
    newConfirmBtn.addEventListener('click', function() {
        modal.hide();
        executeBulkEncerramento(solicitacoes, btnElement);
    });
}

function executeBulkEncerramento(solicitacoes, btnElement) {
    const originalText = btnElement.innerHTML;
    btnElement.disabled = true;
    btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Encerrando...';

    const formData = new FormData();
    formData.append('acao', 'encerrar_bulk');
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Correspondentes encerrados com sucesso!', 'success');
            document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => cb.checked = false);
            const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
            if (headerCheckbox) headerCheckbox.checked = false;
            updateBulkActionButtons();
            handleFormSubmit();
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    })
    .catch(error => {
        showNotification('Erro ao encerrar correspondentes', 'error');
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    });
}
```

**3. In JH.txt (ajax_encerramento.php)**, add these handlers:

```php
// Check encerramento status for bulk
if (isset($_POST['acao']) && $_POST['acao'] == 'check_encerramento_status') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $solicitacoes_json = isset($_POST['solicitacoes']) ? $_POST['solicitacoes'] : '';
        $solicitacoes = json_decode($solicitacoes_json, true);
        
        if (!is_array($solicitacoes) || count($solicitacoes) === 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nenhuma solicitação selecionada']);
            exit;
        }
        
        $model = new Analise();
        $pendentes = [];
        $lojasList = [];
        
        foreach ($solicitacoes as $cod) {
            $where = "AND A.COD_SOLICITACAO = " . intval($cod);
            $dados = $model->solicitacoes($where, 1, 0);
            
            if (!empty($dados)) {
                $loja = $dados[0];
                $lojasList[] = [
                    'cod_solicitacao' => $cod,
                    'chave_loja' => $loja['CHAVE_LOJA'],
                    'nome_loja' => $loja['NOME_LOJA']
                ];
                
                $status = $model->getEncerramentoStatus($cod);
                if ($status) {
                    $pendente = [];
                    if ($status['STATUS_OP'] !== 'EFETUADO') $pendente[] = 'Órgão Pagador';
                    if ($status['STATUS_COM'] !== 'EFETUADO') $pendente[] = 'Comercial';
                    if ($status['STATUS_VAN'] !== 'EFETUADO') $pendente[] = 'Van-Material';
                    if ($status['STATUS_BLOQ'] !== 'EFETUADO') $pendente[] = 'Bloqueio';
                    
                    if (!empty($pendente)) {
                        $pendentes[$loja['CHAVE_LOJA']] = $pendente;
                    }
                }
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'pendentes' => $pendentes,
            'list' => $lojasList
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Execute bulk encerramento
if (isset($_POST['acao']) && $_POST['acao'] == 'encerrar_bulk') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $solicitacoes_json = isset($_POST['solicitacoes']) ? $_POST['solicitacoes'] : '';
        $solicitacoes = json_decode($solicitacoes_json, true);
        
        if (!is_array($solicitacoes) || count($solicitacoes) === 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Nenhuma solicitação selecionada']);
            exit;
        }
        
        $model = new Analise();
        $success_count = 0;
        
        foreach ($solicitacoes as $cod) {
            $where = "AND A.COD_SOLICITACAO = " . intval($cod);
            $dados = $model->solicitacoes($where, 1, 0);
            
            if (!empty($dados)) {
                $result = $model->encerrarCorrespondente($cod, $dados[0]['CHAVE_LOJA']);
                if ($result) $success_count++;
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $success_count . ' correspondente(s) encerrado(s)',
            'count' => $success_count
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
```

**4. In EnMa.txt (encerramento_massa.php)**, add error insertion to database:

```php
// At the top after the class definition, add:
private function insertErrorToDatabase($errors, $fileName) {
    if (empty($errors)) return;
    
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    
    $model = new Analise();
    $chave_lote = time(); // Use timestamp as batch key
    
    foreach ($errors as $error) {
        $ocorrencia = "Arquivo: " . $fileName . "\n";
        $ocorrencia .= "Tipo: " . $error['error_type'] . "\n";
        $ocorrencia .= "Mensagem: " . $error['error_message'] . "\n";
        $ocorrencia .= "Data Contrato: " . $error['data_contrato'] . "\n";
        $ocorrencia .= "Linha TXT: " . $error['txt_line'];
        
        $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
                  (DT_OCORRENCIA, OCORRENCIA, CNPJs, CHAVE_LOTE, VIEWED_FLAG)
                  VALUES (
                      GETDATE(),
                      '" . addslashes($ocorrencia) . "',
                      '" . addslashes($error['cnpj']) . "',
                      " . $chave_lote . ",
                      0
                  )";
        $model->insert($query);
    }
}

// Update generateTXT method - after the return statement, add:
private function generateTXT($dados) {
    // ... existing code ...
    
    $result = [
        'success' => true,
        'conteudo' => $conteudo,
        'nomeArquivo' => $nomeArquivo,
        'totalRegistros' => $totalLinhas,
        'errors' => $this->errors,
        'has_errors' => !empty($this->errors)
    ];
    
    // Insert errors to database
    if (!empty($this->errors)) {
        $this->insertErrorToDatabase($this->errors, $nomeArquivo);
    }
    
    return $result;
}
```

**5. Update email sending to also insert to database** - in `sendTXTErrorEmail` in ED.txt, add at the beginning:

```php
// Also insert to database
if (!empty($errors)) {
    $this->insertErrorToDatabase($errors, $fileName);
}
```

These changes will:
1. Show modal for bulk encerramento with pending statuses
2. Update database instead of sending email
3. Insert TXT errors into the ENCERRAMENTO_TB_PORTAL_OCORRENCIA table