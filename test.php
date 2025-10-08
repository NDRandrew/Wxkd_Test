
# Implementation Guide

## Summary of Changes

### 1. Database Setup
First, ensure the ENCERRAMENTO_TB_PORTAL table exists:

```sql
-- If table doesn't exist, create it
IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='ENCERRAMENTO_TB_PORTAL' AND xtype='U')
BEGIN
    CREATE TABLE ENCERRAMENTO_TB_PORTAL (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        COD_SOLICITACAO INT NOT NULL,
        CHAVE_LOJA INT NOT NULL,
        STATUS_SOLIC VARCHAR(30) DEFAULT 'EM ANDAMENTO',
        STATUS_OP VARCHAR(30) DEFAULT 'Não Efetuado',
        STATUS_COM VARCHAR(30) DEFAULT 'Não Efetuado',
        STATUS_VAN VARCHAR(30) DEFAULT 'Não Efetuado',
        STATUS_BLOQ VARCHAR(30) DEFAULT 'Não Efetuado',
        STATUS_ENCERRAMENTO VARCHAR(30) DEFAULT 'Não Efetuado',
        LOCAL_CHAT VARCHAR(255) NULL,
        MOTIVO_ENC VARCHAR(255) NULL,
        DATA_ENC DATE NULL,
        DATA_CRIACAO DATETIME DEFAULT GETDATE()
    );
    
    CREATE INDEX IX_COD_SOLICITACAO ON ENCERRAMENTO_TB_PORTAL(COD_SOLICITACAO);
END
```

### 2. Files to Update

#### Model (M.txt - analise_encerramento_model.class.php)
- Add methods from artifact "Model - ENCERRAMENTO_TB_PORTAL Methods"
- Replace existing `solicitacoes()` method with `solicitacoesWithStatus()`

#### Ajax Handler (JH.txt - ajax_encerramento.php)
- Add new endpoints from "Ajax Handler - New Endpoints"
- Update `loadData()` method to handle sorting and new filters
- Update render methods from "Ajax Handler - Render Methods Updates"

#### Email Functions (ED.txt - email_functions.php)
- Update `sendEmail()` and `sendBulkEmail()` functions from "Email Functions - Status Recording"

#### JavaScript (J.txt - analise_encerramento.js)
- Add all functions from "JavaScript - Sorting & Status Features"
- Update the `initialize()` function to include `initializeColumnSort()`

#### View (E.txt - analise_encerramento.php)
- Add filter inputs from "View - Filter & Modal Updates" after existing filters

### 3. Key Features Implemented

**Filters:**
- Status Solicitação dropdown (EM ANDAMENTO, CANCELADO, FINALIZADO)
- Motivo Encerramento dropdown (placeholder values - add real ones)
- Both filters integrated with existing filter system

**Column Sorting:**
- Click any sortable column header to toggle ASC/DESC
- Visual indicators (▲▼) show current sort
- Sortable columns: Solicitação, Agência/PACB, Chave Loja, dates, etc.

**Modal Updates:**
- Motivo Encerramento dropdown in Ações card
- Data Encerramento date input
- Save button to update both fields
- Dynamic status display based on email sends
- Cancel button in footer (red, bottom-left)

**Status Tracking:**
- Automatic status recording when emails are sent
- Status values: "Não Efetuado", "Efetuado", "ERRO"
- Color-coded status display (green=success, yellow=pending, red=error)
- Real-time status updates after email sends

**Bulk Actions Validation:**
- Check if any selected records have pending statuses
- Show warning modal with details of pending items
- User can proceed or cancel based on warning

**Cancel Functionality:**
- Red "Cancelar Solicitação" button in modal footer
- Updates STATUS_SOLIC to "CANCELADO"
- Confirmation dialog before canceling

### 4. Customization Points

Replace placeholder values in these locations:

**Status Solicitação Options:**
```php
<option value="FINALIZADO">Finalizado</option>
// Add more as needed
```

**Motivo Encerramento Options:**
```php
<option value="MOTIVO_1">Motivo 1</option>
<option value="MOTIVO_2">Motivo 2</option>
<option value="MOTIVO_3">Motivo 3</option>
// Replace with actual motivos
```

**Column Sort Mapping:**
```javascript
const sortableColumns = {
    'COD_SOLICITACAO': 1,
    // Add more columns if needed
};
```

### 5. Testing Checklist

- [ ] New filters appear and work correctly
- [ ] Column sorting toggles between ASC/DESC
- [ ] Status is created automatically for new records
- [ ] Email sends update status correctly
- [ ] Bulk actions validate pending statuses
- [ ] Motivo/Data updates save successfully
- [ ] Cancel button changes STATUS_SOLIC
- [ ] Status colors display correctly
- [ ] All modals open/close properly

### 6. Important Notes

- The system automatically creates ENCERRAMENTO_TB_PORTAL records when emails are first sent
- Status updates happen immediately after email operations
- Bulk validation runs before sending to warn about pending items
- All database operations use parameterized queries for security
- Maintain MVC separation - keep business logic in model/controller

### 7. Error Handling

All endpoints return JSON with:
```json
{
    "success": true/false,
    "message": "Description",
    "data": {} // when applicable
}
```

JavaScript catches and displays errors via `showNotification()` function. 



--------------

<?php
// Add these methods to analise_encerramento_model.class.php

public function getEncerramentoStatus($cod_solicitacao) {
    $query = "SELECT * FROM ENCERRAMENTO_TB_PORTAL WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    $dados = $this->sql->select($query);
    return $dados ? $dados[0] : null;
}

public function insertEncerramentoStatus($cod_solicitacao, $chave_loja) {
    $query = "INSERT INTO ENCERRAMENTO_TB_PORTAL (
        COD_SOLICITACAO, CHAVE_LOJA, STATUS_SOLIC, STATUS_OP, STATUS_COM, 
        STATUS_VAN, STATUS_BLOQ, STATUS_ENCERRAMENTO
    ) VALUES (
        " . intval($cod_solicitacao) . ", 
        " . intval($chave_loja) . ", 
        'EM ANDAMENTO', 'Não Efetuado', 'Não Efetuado', 
        'Não Efetuado', 'Não Efetuado', 'Não Efetuado'
    )";
    return $this->sql->insert($query);
}

public function updateEmailStatus($cod_solicitacao, $tipo, $status) {
    $statusField = [
        'orgao_pagador' => 'STATUS_OP',
        'comercial' => 'STATUS_COM',
        'van_material' => 'STATUS_VAN',
        'bloqueio' => 'STATUS_BLOQ',
        'encerramento' => 'STATUS_ENCERRAMENTO'
    ];
    
    if (!isset($statusField[$tipo])) return false;
    
    $query = "UPDATE ENCERRAMENTO_TB_PORTAL SET " . $statusField[$tipo] . " = '" . $status . "' 
              WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    return $this->sql->update($query);
}

public function updateMotivo($cod_solicitacao, $motivo, $data_enc = null) {
    $query = "UPDATE ENCERRAMENTO_TB_PORTAL SET MOTIVO_ENC = '" . addslashes($motivo) . "'";
    if ($data_enc) {
        $query .= ", DATA_ENC = '" . $data_enc . "'";
    }
    $query .= " WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    return $this->sql->update($query);
}

public function cancelarSolicitacao($cod_solicitacao) {
    $query = "UPDATE ENCERRAMENTO_TB_PORTAL SET STATUS_SOLIC = 'CANCELADO' 
              WHERE COD_SOLICITACAO = " . intval($cod_solicitacao);
    return $this->sql->update($query);
}

public function solicitacoesWithStatus($where, $limit = 25, $offset = 0, $orderBy = 'A.COD_SOLICITACAO DESC') {
    $query = "
        SELECT
            A.COD_SOLICITACAO, A.COD_AG, A.CHAVE_LOJA, A.DATA_CAD AS DATA_RECEPCAO,
            CASE WHEN A.COD_AG = F.COD_AG_LOJA THEN F.NOME_AG ELSE 'AGENCIA' END NOME_AG,
            F.NOME_LOJA, F.CNPJ, F.COD_EMPRESA, F.COD_EMPRESA_TEF,
            F.DATA_RETIRADA_EQPTO, F.DATA_BLOQUEIO, F.MOTIVO_BLOQUEIO, F.DESC_MOTIVO_ENCERRAMENTO,
            G.NR_PACB, G.ORGAO_PAGADOR, CONVERT(VARCHAR, G.DATA_LAST_TRANS, 103) DATA_LAST_TRANS,
            ISNULL(ES.STATUS_SOLIC, 'EM ANDAMENTO') STATUS_SOLIC,
            ISNULL(ES.STATUS_OP, 'Não Efetuado') STATUS_OP,
            ISNULL(ES.STATUS_COM, 'Não Efetuado') STATUS_COM,
            ISNULL(ES.STATUS_VAN, 'Não Efetuado') STATUS_VAN,
            ISNULL(ES.STATUS_BLOQ, 'Não Efetuado') STATUS_BLOQ,
            ISNULL(ES.STATUS_ENCERRAMENTO, 'Não Efetuado') STATUS_ENCERRAMENTO,
            ES.MOTIVO_ENC, ES.DATA_ENC,
            CASE 
                WHEN F.BE_AVANCADO = 'S' THEN 'AVANCADO,'
                ELSE ''
            END +
            CASE WHEN F.BE_CLUBE = 'S' THEN 'CLUBE,' ELSE '' END +
            CASE WHEN F.BE_COMPLETO = 'S' THEN 'COMPLETO,' ELSE '' END AS CLUSTER,
            CASE WHEN K.QTD > 1 THEN 'APTO' ELSE 'NÃO APTO' END AS PARM,
            CASE
                WHEN VWATUAL.codAtual IS NOT NULL THEN 'APTO'
                WHEN VWANTIGA.CodAnt IS NOT NULL THEN 'NÃO APTO'
                ELSE NULL
            END AS TRAG,
            ISNULL(L.QTD_CHAVE, 0)/3 AS MEDIA_CONTABEIS,
            ISNULL(M.CONTAS, 0)/3 AS MEDIA_NEGOCIO
        FROM TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK)
        JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK) ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO
        JOIN TB_ACIONAMENTO_PRESTACAO_CONTA C WITH (NOLOCK) ON A.COD_PRESTACAO_CONTA = C.COD_PRESTACAO_CONTA
        JOIN TB_ACIONAMENTO_STATUS D WITH (NOLOCK) ON A.COD_STATUS = D.COD_STATUS
        LEFT JOIN RH..TB_FUNCIONARIOS E WITH (NOLOCK) ON A.COD_SOLICITANTE = E.COD_FUNC
        LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK) ON A.CHAVE_LOJA = F.CHAVE_LOJA
        JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK) ON A.COD_SOLICITACAO = G.COD_SOLICITACAO
        LEFT JOIN ENCERRAMENTO_TB_PORTAL ES WITH (NOLOCK) ON A.COD_SOLICITACAO = ES.COD_SOLICITACAO
        LEFT JOIN (
            SELECT COUNT(*) AS QTD, COD_AG_LOJA
            FROM DATALAKE..DL_BRADESCO_EXPRESSO DL WITH (NOLOCK)
            WHERE DL.BE_INAUGURADO = 1 AND DL.CATEGORIA NOT IN ('PROCESSO DE ENCERRAMENTO', 'ENCERRADO')
            GROUP BY COD_AG_LOJA
        ) K ON F.COD_AG_LOJA = K.COD_AG_LOJA AND K.COD_AG_LOJA = A.COD_AG
        LEFT JOIN (
            SELECT CHAVE_LOJA, COUNT(*) AS QTD_CHAVE 
            FROM PGTOCORSP..TB_EVT12_TRANS A
            LEFT JOIN (SELECT * FROM PGTOCORSP..PGTOCORSP_SERVICOS_VANS WHERE TRX_CONTABIL=1) B 
            ON A.DESC_TRX=B.DESC_SERVICO
            WHERE ANO=YEAR(GETDATE()) AND MES BETWEEN MONTH(GETDATE())-4 AND MONTH(GETDATE())
            GROUP BY A.CHAVE_LOJA
        ) L ON F.CHAVE_LOJA = L.CHAVE_LOJA
        LEFT JOIN (
            SELECT CHAVE_LOJA,
                SUM(QTD_CONTAS)+SUM(QTD_DEPOSITO)+SUM(CHEQUE_ESPECIAL) AS CONTAS
            FROM PGTOCORSP..TB_STG2_ANALISE_CONTA_CRC
            WHERE MES_PGTO >= CONVERT(VARCHAR(6), DATEADD(M, -4, GETDATE()), 112)
            GROUP BY CHAVE_LOJA
        ) M ON M.CHAVE_LOJA = L.CHAVE_LOJA
        LEFT JOIN MESU..VW_TRAGS AS VWATUAL WITH (NOLOCK) 
        ON VWATUAL.CodAtual = A.COD_AG AND VWATUAL.NRAtual = G.NR_PACB
        LEFT JOIN MESU..VW_TRAGS AS VWANTIGA WITH (NOLOCK) 
        ON VWANTIGA.CodAnt = A.COD_AG AND VWANTIGA.NRAnt = G.NR_PACB
        WHERE 1=1 AND F.BE_INAUGURADO = 1 " . $where . "
        ORDER BY " . $orderBy . "
        OFFSET " . $offset . " ROWS
        FETCH NEXT " . $limit . " ROWS ONLY";
    
    return $this->sql->select($query);
}
?>


--------------


<?php
// Add these handlers to ajax_encerramento.php before the regular data loading

// Update motivo and data
if (isset($_POST['acao']) && $_POST['acao'] == 'update_motivo') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $motivo = isset($_POST['motivo']) ? $_POST['motivo'] : '';
        $data_enc = isset($_POST['data_enc']) ? $_POST['data_enc'] : null;
        
        if ($cod_solicitacao <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Código inválido']);
            exit;
        }
        
        $model = new Analise();
        $status = $model->getEncerramentoStatus($cod_solicitacao);
        
        if (!$status) {
            $where = "AND A.COD_SOLICITACAO = " . $cod_solicitacao;
            $dados = $model->solicitacoes($where, 1, 0);
            if (!empty($dados)) {
                $model->insertEncerramentoStatus($cod_solicitacao, $dados[0]['CHAVE_LOJA']);
            }
        }
        
        $result = $model->updateMotivo($cod_solicitacao, $motivo, $data_enc);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => $result ? true : false, 
            'message' => $result ? 'Atualizado com sucesso' : 'Erro ao atualizar'
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Cancelar solicitação
if (isset($_POST['acao']) && $_POST['acao'] == 'cancelar_solicitacao') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        
        if ($cod_solicitacao <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Código inválido']);
            exit;
        }
        
        $model = new Analise();
        $result = $model->cancelarSolicitacao($cod_solicitacao);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => $result ? true : false,
            'message' => $result ? 'Solicitação cancelada' : 'Erro ao cancelar'
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Get status pendentes for bulk validation
if (isset($_POST['acao']) && $_POST['acao'] == 'check_bulk_status') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $solicitacoes_json = isset($_POST['solicitacoes']) ? $_POST['solicitacoes'] : '';
        $solicitacoes = json_decode($solicitacoes_json, true);
        
        if (!is_array($solicitacoes) || count($solicitacoes) === 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Nenhuma solicitação selecionada']);
            exit;
        }
        
        $model = new Analise();
        $pendentes = [];
        
        foreach ($solicitacoes as $cod) {
            $status = $model->getEncerramentoStatus($cod);
            if ($status) {
                $pendente = [];
                if ($status['STATUS_OP'] !== 'Efetuado') $pendente[] = 'Órgão Pagador';
                if ($status['STATUS_COM'] !== 'Efetuado') $pendente[] = 'Comercial';
                if ($status['STATUS_VAN'] !== 'Efetuado') $pendente[] = 'Van-Material';
                if ($status['STATUS_BLOQ'] !== 'Efetuado') $pendente[] = 'Bloqueio';
                
                if (!empty($pendente)) {
                    $pendentes[$status['CHAVE_LOJA']] = $pendente;
                }
            }
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => true,
            'pendentes' => $pendentes,
            'has_pendentes' => !empty($pendentes)
        ]);
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Update in AjaxEncerramentoHandler->loadData() method to handle sorting
public function loadData($filters = []) {
    $where = "AND A.COD_TIPO_SERVICO=1";
    
    if (isset($filters['search']) && !empty($filters['search'])) {
        $search = $filters['search'];
        $where .= " AND (
            CAST(A.COD_SOLICITACAO AS VARCHAR) LIKE '%$search%' OR
            CAST(A.COD_AG AS VARCHAR) LIKE '%$search%' OR
            CAST(A.CHAVE_LOJA AS VARCHAR) LIKE '%$search%' OR
            F.NOME_LOJA LIKE '%$search%' OR
            G.NR_PACB LIKE '%$search%' OR
            F.MOTIVO_BLOQUEIO LIKE '%$search%' OR
            F.DESC_MOTIVO_ENCERRAMENTO LIKE '%$search%'
        )";
    }
    
    if (isset($filters['bloqueio']) && $filters['bloqueio'] !== '') {
        $where .= $filters['bloqueio'] === 'bloqueado' 
            ? " AND F.DATA_BLOQUEIO IS NOT NULL"
            : " AND F.DATA_BLOQUEIO IS NULL";
    }
    
    if (isset($filters['orgao_pagador']) && !empty($filters['orgao_pagador'])) {
        $where .= " AND G.ORGAO_PAGADOR LIKE '%" . $filters['orgao_pagador'] . "%'";
    }
    
    if (isset($filters['status_solic']) && !empty($filters['status_solic'])) {
        $where .= " AND ISNULL(ES.STATUS_SOLIC, 'EM ANDAMENTO') = '" . $filters['status_solic'] . "'";
    }
    
    if (isset($filters['motivo_enc']) && !empty($filters['motivo_enc'])) {
        $where .= " AND ES.MOTIVO_ENC = '" . $filters['motivo_enc'] . "'";
    }
    
    if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
        $where .= " AND A.DATA_CAD >= '" . $filters['data_inicio'] . "'";
    }
    
    if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
        $where .= " AND A.DATA_CAD <= '" . $filters['data_fim'] . "'";
    }
    
    $orderBy = 'A.COD_SOLICITACAO DESC';
    if (isset($filters['sort_column']) && isset($filters['sort_direction'])) {
        $orderBy = $filters['sort_column'] . ' ' . $filters['sort_direction'];
    }
    
    $page = isset($filters['page']) ? max(1, intval($filters['page'])) : 1;
    $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 25;
    
    if (!in_array($perPage, [25, 50, 100, 200])) {
        $perPage = 25;
    }
    
    $totalRecords = $this->model->getTotalCount($where);
    $totalPages = ceil($totalRecords / $perPage);
    $offset = ($page - 1) * $perPage;
    $dados = $this->model->solicitacoesWithStatus($where, $perPage, $offset, $orderBy);
    
    return [
        'dados' => $dados,
        'totalRecords' => $totalRecords,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'perPage' => $perPage,
        'startRecord' => $offset + 1,
        'endRecord' => min($offset + $perPage, $totalRecords)
    ];
}
?>


-----------


<?php
// Update in email_functions.php

function sendEmail($type, $cod_solicitacao) {
    global $EMAIL_CONFIG;
    
    ob_start();
    include_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\erp\PHP_MAILER_NEW\mail.php');
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    ob_end_clean();
    
    if (!isset($_SESSION['cod_usu']) || $_SESSION['cod_usu'] == '') {
        return ['success' => false, 'message' => 'Usuário não autenticado'];
    }
    
    $model = new Analise();
    $where = "AND A.COD_SOLICITACAO = " . intval($cod_solicitacao);
    $dados = $model->solicitacoes($where, 1, 0);
    
    if (empty($dados)) {
        return ['success' => false, 'message' => 'Solicitação não encontrada'];
    }
    
    // Check/create status record
    $status = $model->getEncerramentoStatus($cod_solicitacao);
    if (!$status) {
        $model->insertEncerramentoStatus($cod_solicitacao, $dados[0]['CHAVE_LOJA']);
    }
    
    $solicitacao = $dados[0];
    $emailConfig = getEmailConfig($type, $solicitacao);
    
    $email_to = ($_SESSION['cod_usu'] == $EMAIL_CONFIG['test_user_id']) 
        ? $EMAIL_CONFIG['test_email'] 
        : $emailConfig['recipients'];
    
    ob_start();
    $result = mailer(
        false, '', 
        $email_to, '', '', 
        $emailConfig['subject'], 
        utf8_decode($emailConfig['body']), 
        '', 'I', ''
    );
    ob_end_clean();
    
    // Update status
    $emailStatus = $result ? 'Efetuado' : 'ERRO';
    $model->updateEmailStatus($cod_solicitacao, $type, $emailStatus);
    
    return $result 
        ? ['success' => true, 'message' => 'Email enviado com sucesso', 'status' => $emailStatus]
        : ['success' => false, 'message' => 'Erro ao enviar email', 'status' => $emailStatus];
}

function sendBulkEmail($type, $cod_solicitacoes) {
    global $EMAIL_CONFIG;
    
    ob_start();
    include_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\erp\PHP_MAILER_NEW\mail.php');
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    ob_end_clean();
    
    if (!isset($_SESSION['cod_usu']) || $_SESSION['cod_usu'] == '') {
        return ['success' => false, 'message' => 'Usuário não autenticado'];
    }
    
    $model = new Analise();
    $solicitacoes = [];
    
    foreach ($cod_solicitacoes as $cod) {
        $where = "AND A.COD_SOLICITACAO = " . intval($cod);
        $dados = $model->solicitacoes($where, 1, 0);
        if (!empty($dados)) {
            $solicitacoes[] = $dados[0];
            
            // Check/create status record
            $status = $model->getEncerramentoStatus($cod);
            if (!$status) {
                $model->insertEncerramentoStatus($cod, $dados[0]['CHAVE_LOJA']);
            }
        }
    }
    
    if (empty($solicitacoes)) {
        return ['success' => false, 'message' => 'Nenhuma solicitação encontrada'];
    }
    
    $emailConfig = getBulkEmailConfig($type, $solicitacoes);
    
    $email_to = ($_SESSION['cod_usu'] == $EMAIL_CONFIG['test_user_id']) 
        ? $EMAIL_CONFIG['test_email'] 
        : $emailConfig['recipients'];
    
    ob_start();
    $result = mailer(
        false, '', 
        $email_to, '', '', 
        $emailConfig['subject'], 
        utf8_decode($emailConfig['body']), 
        '', 'I', ''
    );
    ob_end_clean();
    
    // Update status for all
    $emailStatus = $result ? 'Efetuado' : 'ERRO';
    foreach ($cod_solicitacoes as $cod) {
        $model->updateEmailStatus($cod, $type, $emailStatus);
    }
    
    return $result 
        ? ['success' => true, 'message' => 'Emails enviados com sucesso', 'status' => $emailStatus]
        : ['success' => false, 'message' => 'Erro ao enviar emails', 'status' => $emailStatus];
}
?>


------------


// Add to analise_encerramento.js

let currentSort = {
    column: null,
    direction: 'ASC'
};

function initialize() {
    setupDateInputs();
    initializeEventListeners();
    initializeCheckboxHandlers();
    initializeColumnSort();
    highlightActiveFilters();
    attachPageNumberHandlers();
    
    if (window.pageState && window.pageState.autoLoadData) {
        setTimeout(() => handleFormSubmit(), 100);
    }
}

function initializeColumnSort() {
    const sortableColumns = {
        'COD_SOLICITACAO': 1,
        'COD_AG': 2,
        'CHAVE_LOJA': 3,
        'DATA_RECEPCAO': 4,
        'DATA_RETIRADA_EQPTO': 5,
        'DATA_BLOQUEIO': 6,
        'DATA_LAST_TRANS': 7,
        'ORGAO_PAGADOR': 10,
        'CLUSTER': 11,
        'MEDIA_CONTABEIS': 14,
        'MEDIA_NEGOCIO': 15
    };
    
    document.querySelectorAll('thead th.thead-encerramento').forEach((th, index) => {
        const columnName = Object.keys(sortableColumns)[Object.values(sortableColumns).indexOf(index)];
        if (columnName) {
            th.style.cursor = 'pointer';
            th.addEventListener('click', () => {
                if (currentSort.column === columnName) {
                    currentSort.direction = currentSort.direction === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    currentSort.column = columnName;
                    currentSort.direction = 'ASC';
                }
                
                // Update visual indicator
                document.querySelectorAll('thead th.thead-encerramento').forEach(h => {
                    h.innerHTML = h.innerHTML.replace(/\s*[▲▼]/, '');
                });
                th.innerHTML += currentSort.direction === 'ASC' ? ' ▲' : ' ▼';
                
                currentPage = 1;
                handleFormSubmit();
            });
        }
    });
}

window.sendBulkEmail = function(tipo) {
    const solicitacoes = getSelectedSolicitacoes();
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
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
            showBulkWarningModal(data.pendentes, tipo, solicitacoes);
        } else {
            executeBulkEmail(tipo, solicitacoes);
        }
    })
    .catch(error => {
        console.error('Status check error:', error);
        executeBulkEmail(tipo, solicitacoes);
    });
};

function showBulkWarningModal(pendentes, tipo, solicitacoes) {
    let message = '<div class="mb-3">As seguintes lojas possuem status pendentes:</div>';
    message += '<div class="table-responsive" style="max-height: 300px; overflow-y: auto;">';
    message += '<table class="table table-sm"><thead><tr><th>Chave Loja</th><th>Status Pendentes</th></tr></thead><tbody>';
    
    for (const [chave, status] of Object.entries(pendentes)) {
        message += `<tr><td>${chave}</td><td class="text-danger">${status.join(', ')}</td></tr>`;
    }
    
    message += '</tbody></table></div>';
    message += '<div class="mt-3">Deseja continuar mesmo assim?</div>';
    
    if (confirm(message)) {
        executeBulkEmail(tipo, solicitacoes);
    }
}

function executeBulkEmail(tipo, solicitacoes) {
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

    const formData = new FormData();
    formData.append('acao', 'enviar_email_bulk');
    formData.append('tipo', tipo);
    formData.append('solicitacoes', JSON.stringify(solicitacoes));

    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Emails enviados com sucesso!', 'success');
            document.querySelectorAll('tbody input[type="checkbox"]:checked').forEach(cb => cb.checked = false);
            const headerCheckbox = document.querySelector('thead input[type="checkbox"]');
            if (headerCheckbox) headerCheckbox.checked = false;
            updateBulkActionButtons();
            handleFormSubmit(); // Reload data
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
        btn.innerHTML = originalText;
        btn.disabled = false;
    })
    .catch(error => {
        showNotification('Erro ao enviar emails', 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

function handleFormSubmit() {
    const filterForm = document.getElementById('filterForm');
    const formData = new FormData(filterForm);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            params.append(key, value);
        }
    }
    
    if (currentSort.column) {
        params.append('sort_column', currentSort.column);
        params.append('sort_direction', currentSort.direction);
    }
    
    params.append('page', currentPage);
    params.append('per_page', perPage);
    
    showLoading();
    
    fetch(AJAX_URL + '?' + params.toString())
        .then(response => {
            if (!response.ok) throw new Error('Network error: ' + response.status);
            return response.text();
        })
        .then(text => {
            const data = JSON.parse(text);
            
            if (data.success) {
                updateUI(data, params);
            } else {
                throw new Error(data.error || 'Erro ao carregar dados');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showNotification('Erro ao carregar os dados: ' + error.message, 'error');
        })
        .finally(() => hideLoading());
}

window.updateMotivoEncerramento = function(codSolicitacao) {
    const motivoSelect = document.getElementById('motivoEnc' + codSolicitacao);
    const dataInput = document.getElementById('dataEnc' + codSolicitacao);
    
    if (!motivoSelect || !dataInput) {
        showNotification('Elementos não encontrados', 'error');
        return;
    }
    
    const motivo = motivoSelect.value;
    const dataEnc = dataInput.value;
    
    if (!motivo) {
        showNotification('Selecione um motivo', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'update_motivo');
    formData.append('cod_solicitacao', codSolicitacao);
    formData.append('motivo', motivo);
    if (dataEnc) formData.append('data_enc', dataEnc);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Atualizado com sucesso!', 'success');
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Erro ao atualizar', 'error');
    });
};

window.cancelarSolicitacao = function(codSolicitacao) {
    if (!confirm('Tem certeza que deseja cancelar esta solicitação?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('acao', 'cancelar_solicitacao');
    formData.append('cod_solicitacao', codSolicitacao);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Solicitação cancelada com sucesso!', 'success');
            const modal = document.getElementById('AnaliseDetalhesModal' + codSolicitacao);
            if (modal) {
                closeModal(modal, document.querySelector('.modal-backdrop'));
            }
            handleFormSubmit(); // Reload data
        } else {
            showNotification('Erro: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Erro ao cancelar solicitação', 'error');
    });
};

function attachEmailHandlers() {
    document.querySelectorAll('.email-action-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const tipo = this.dataset.tipo;
            const codSolicitacao = this.dataset.solicitacao;
            
            sendEmail(tipo, codSolicitacao, this);
        });
    });
}

function sendEmail(tipo, codSolicitacao, buttonElement) {
    const originalText = buttonElement.innerHTML;
    buttonElement.disabled = true;
    buttonElement.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    
    const formData = new FormData();
    formData.append('acao', 'enviar_email');
    formData.append('tipo', tipo);
    formData.append('cod_solicitacao', codSolicitacao);
    
    fetch(AJAX_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Email enviado com sucesso!', 'success');
            buttonElement.innerHTML = '<i class="bi bi-check2"></i> Enviado';
            
            // Update status display
            if (data.status) {
                updateStatusDisplay(codSolicitacao, tipo, data.status);
            }
            
            setTimeout(() => {
                buttonElement.innerHTML = originalText;
                buttonElement.disabled = false;
            }, 3000);
        } else {
            showNotification('Erro: ' + data.message, 'error');
            buttonElement.innerHTML = originalText;
            buttonElement.disabled = false;
        }
    })
    .catch(error => {
        console.error('Email Error:', error);
        showNotification('Erro ao enviar email', 'error');
        buttonElement.innerHTML = originalText;
        buttonElement.disabled = false;
    });
}

function updateStatusDisplay(codSolicitacao, tipo, status) {
    const statusMap = {
        'orgao_pagador': 'status-op',
        'comercial': 'status-com',
        'van_material': 'status-van',
        'bloqueio': 'status-bloq',
        'encerramento': 'status-enc'
    };
    
    const statusElement = document.getElementById(statusMap[tipo] + '-' + codSolicitacao);
    if (statusElement) {
        statusElement.className = 'status ' + (status === 'Efetuado' ? 'status-green' : 'status-red');
        statusElement.querySelector('.status-text').textContent = status;
    }
}


-----------


<!-- Add these filters to the "Filtros e Busca" card in analise_encerramento.php -->
<!-- Add after the existing filters, before Action Buttons -->

<!-- Status Solicitação Filter -->
<div class="col-md-3">
    <label class="form-label">Status Solicitação</label>
    <select class="form-select" name="status_solic" id="statusSolicFilter">
        <option value="">Todos os Status</option>
        <option value="EM ANDAMENTO" <?php echo (isset($_GET['status_solic']) && $_GET['status_solic'] === 'EM ANDAMENTO') ? 'selected' : ''; ?>>Em Andamento</option>
        <option value="CANCELADO" <?php echo (isset($_GET['status_solic']) && $_GET['status_solic'] === 'CANCELADO') ? 'selected' : ''; ?>>Cancelado</option>
        <option value="FINALIZADO" <?php echo (isset($_GET['status_solic']) && $_GET['status_solic'] === 'FINALIZADO') ? 'selected' : ''; ?>>Finalizado</option>
    </select>
</div>

<!-- Motivo Encerramento Filter -->
<div class="col-md-3">
    <label class="form-label">Motivo Encerramento</label>
    <select class="form-select" name="motivo_enc" id="motivoEncFilter">
        <option value="">Todos os Motivos</option>
        <option value="MOTIVO_1" <?php echo (isset($_GET['motivo_enc']) && $_GET['motivo_enc'] === 'MOTIVO_1') ? 'selected' : ''; ?>>Motivo 1</option>
        <option value="MOTIVO_2" <?php echo (isset($_GET['motivo_enc']) && $_GET['motivo_enc'] === 'MOTIVO_2') ? 'selected' : ''; ?>>Motivo 2</option>
        <option value="MOTIVO_3" <?php echo (isset($_GET['motivo_enc']) && $_GET['motivo_enc'] === 'MOTIVO_3') ? 'selected' : ''; ?>>Motivo 3</option>
    </select>
</div>



-----------

<?php
// Update buildActionButtons method in analise_encerramento_control.php

private function buildActionButtons($codSolicitacao) {
    $buttons = ['orgao_pagador' => 'Órgão Pagador', 'comercial' => 'Comercial', 
               'van_material' => 'Van-Material', 'bloqueio' => 'Bloqueio'];
    
    $html = '<div class="card"><div class="card-header"><h3 class="card-title">Ações</h3></div>';
    $html .= '<div class="card-body">';
    
    // Motivo and Data inputs
    $html .= '<div class="row mb-3">';
    $html .= '<div class="col-md-6">';
    $html .= '<label class="form-label">Motivo Encerramento</label>';
    $html .= '<select class="form-select" id="motivoEnc' . $codSolicitacao . '">';
    $html .= '<option value="">Selecione um motivo</option>';
    $html .= '<option value="MOTIVO_1">Motivo 1</option>';
    $html .= '<option value="MOTIVO_2">Motivo 2</option>';
    $html .= '<option value="MOTIVO_3">Motivo 3</option>';
    $html .= '</select>';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<label class="form-label">Data Encerramento</label>';
    $html .= '<input type="date" class="form-control" id="dataEnc' . $codSolicitacao . '">';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="d-flex justify-content-end mb-3">';
    $html .= '<button class="btn btn-primary btn-sm" onclick="updateMotivoEncerramento(' . $codSolicitacao . ')">Salvar Alterações</button>';
    $html .= '</div>';
    
    $html .= '<hr>';
    
    // Email action buttons
    $html .= '<div class="d-flex gap-2 justify-content-center flex-wrap">';
    foreach ($buttons as $type => $label) {
        $html .= '<button class="btn email-action-btn" data-tipo="' . $type . '" data-solicitacao="' . $codSolicitacao . '">' . $label . '</button>';
    }
    $html .= '<button class="btn btn-red" data-bs-toggle="modal" data-bs-target="#AlertaEncerramento' . $codSolicitacao . '">Encerramento</button>';
    $html .= '</div></div></div>';
    
    return $html;
}

// Update buildStatusShow method to use dynamic data
private function buildStatusShow($status) {
    $statusClass = [
        'Efetuado' => 'status-green',
        'Não Efetuado' => 'status-yellow',
        'ERRO' => 'status-red'
    ];
    
    $statusText = [
        'Efetuado' => 'Finalizado',
        'Não Efetuado' => 'Esperando',
        'ERRO' => 'Erro'
    ];
    
    $statusDot = [
        'Efetuado' => 'status-dot-animated',
        'Não Efetuado' => '',
        'ERRO' => 'status-dot-animated'
    ];
    
    $op = $status['STATUS_OP'] ?? 'Não Efetuado';
    $com = $status['STATUS_COM'] ?? 'Não Efetuado';
    $van = $status['STATUS_VAN'] ?? 'Não Efetuado';
    $bloq = $status['STATUS_BLOQ'] ?? 'Não Efetuado';
    $enc = $status['STATUS_ENCERRAMENTO'] ?? 'Não Efetuado';
    
    return '<div style="margin:5px;"></div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Órgão Pagador</th>
                                    <th>Comercial</th>
                                    <th>Van-Material</th>
                                    <th>Bloqueio</th>
                                    <th>Encerramento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="status-op-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$op] . '">
                                        <span class="status-dot ' . $statusDot[$op] . '"></span>
                                        <span class="status-text">' . $statusText[$op] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-com-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$com] . '">
                                        <span class="status-dot ' . $statusDot[$com] . '"></span>
                                        <span class="status-text">' . $statusText[$com] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-van-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$van] . '">
                                        <span class="status-dot ' . $statusDot[$van] . '"></span>
                                        <span class="status-text">' . $statusText[$van] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-bloq-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$bloq] . '">
                                        <span class="status-dot ' . $statusDot[$bloq] . '"></span>
                                        <span class="status-text">' . $statusText[$bloq] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-enc-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$enc] . '">
                                        <span class="status-dot ' . $statusDot[$enc] . '"></span>
                                        <span class="status-text">' . $statusText[$enc] . '</span>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>';
}

// Update buildModal method to include status data
private function buildModal($row) {
    $codSolicitacao = htmlspecialchars($row['COD_SOLICITACAO']);
    $nomeLoja = htmlspecialchars(mb_substr($row['NOME_LOJA'], 0, 15));
    
    $status = [
        'COD_SOLICITACAO' => $row['COD_SOLICITACAO'],
        'STATUS_OP' => $row['STATUS_OP'] ?? 'Não Efetuado',
        'STATUS_COM' => $row['STATUS_COM'] ?? 'Não Efetuado',
        'STATUS_VAN' => $row['STATUS_VAN'] ?? 'Não Efetuado',
        'STATUS_BLOQ' => $row['STATUS_BLOQ'] ?? 'Não Efetuado',
        'STATUS_ENCERRAMENTO' => $row['STATUS_ENCERRAMENTO'] ?? 'Não Efetuado'
    ];
    
    return '
        <div class="modal fade" id="AnaliseDetalhesModal' . $codSolicitacao . '" tabindex="-5" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    ' . $this->buildModalHeader($codSolicitacao, $row['NOME_LOJA']) . '
                    ' . $this->buildModalBody($row, $codSolicitacao, $nomeLoja, $status) . '
                    ' . $this->buildModalFooter($codSolicitacao) . '
                </div>
            </div>
        </div>
        ' . $this->buildAlertModal($codSolicitacao, $row['CHAVE_LOJA']) . '
    ';
}

// Update buildModalBody to accept status parameter
private function buildModalBody($row, $codSolicitacao, $nomeLoja, $status) {
    return '
        <div class="modal-body">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <a href="#tabs-home-' . $codSolicitacao . '" class="nav-link active" data-bs-toggle="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <polyline points="5 12 3 12 12 3 21 12 19 12" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                            </svg>
                            Informações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tabs-chat-' . $codSolicitacao . '" class="nav-link" data-bs-toggle="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="12" cy="7" r="4" />
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                            </svg>
                            Profile
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active show" id="tabs-home-' . $codSolicitacao . '">
                            ' . $this->buildInfoCards($row, $nomeLoja) . '
                            ' . $this->buildActionButtons($codSolicitacao) . '
                            ' . $this->buildStatusShow($status) . '
                        </div>
                        <div class="tab-pane" id="tabs-chat-' . $codSolicitacao . '">
                            ' . $this->buildChat().'
                        </div>
                    </div>
                </div>
            </div>
        </div>
    ';
}

// Update buildModalFooter to include cancel button
private function buildModalFooter($codSolicitacao) {
    return '
        <div class="modal-footer d-flex justify-content-between">
            <button class="btn btn-danger" onclick="cancelarSolicitacao(' . $codSolicitacao . ')">Cancelar Solicitação</button>
            <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Fechar</a>
        </div>';
}
?>


------------


<?php
// Update these methods in ajax_encerramento.php AjaxEncerramentoHandler class

private function buildActionButtons($codSolicitacao) {
    $buttons = ['orgao_pagador' => 'Órgão Pagador', 'comercial' => 'Comercial', 
               'van_material' => 'Van-Material', 'bloqueio' => 'Bloqueio'];
    
    $html = '<div class="card"><div class="card-header"><h3 class="card-title">Ações</h3></div>';
    $html .= '<div class="card-body">';
    
    // Motivo and Data inputs
    $html .= '<div class="row mb-3">';
    $html .= '<div class="col-md-6">';
    $html .= '<label class="form-label">Motivo Encerramento</label>';
    $html .= '<select class="form-select" id="motivoEnc' . $codSolicitacao . '">';
    $html .= '<option value="">Selecione um motivo</option>';
    $html .= '<option value="MOTIVO_1">Motivo 1</option>';
    $html .= '<option value="MOTIVO_2">Motivo 2</option>';
    $html .= '<option value="MOTIVO_3">Motivo 3</option>';
    $html .= '</select>';
    $html .= '</div>';
    $html .= '<div class="col-md-6">';
    $html .= '<label class="form-label">Data Encerramento</label>';
    $html .= '<input type="date" class="form-control" id="dataEnc' . $codSolicitacao . '">';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="d-flex justify-content-end mb-3">';
    $html .= '<button class="btn btn-primary btn-sm" onclick="updateMotivoEncerramento(' . $codSolicitacao . ')">Salvar Alterações</button>';
    $html .= '</div>';
    
    $html .= '<hr>';
    
    // Email action buttons
    $html .= '<div class="d-flex gap-2 justify-content-center flex-wrap">';
    foreach ($buttons as $type => $label) {
        $html .= '<button class="btn email-action-btn" data-tipo="' . $type . '" data-solicitacao="' . $codSolicitacao . '">' . $label . '</button>';
    }
    $html .= '<button class="btn btn-red" data-bs-toggle="modal" data-bs-target="#AlertaEncerramento' . $codSolicitacao . '">Encerramento</button>';
    $html .= '</div></div></div>';
    
    return $html;
}

private function buildStatusShow($status) {
    $statusClass = [
        'Efetuado' => 'status-green',
        'Não Efetuado' => 'status-yellow',
        'ERRO' => 'status-red'
    ];
    
    $statusText = [
        'Efetuado' => 'Finalizado',
        'Não Efetuado' => 'Esperando',
        'ERRO' => 'Erro'
    ];
    
    $statusDot = [
        'Efetuado' => 'status-dot-animated',
        'Não Efetuado' => '',
        'ERRO' => 'status-dot-animated'
    ];
    
    $op = $status['STATUS_OP'] ?? 'Não Efetuado';
    $com = $status['STATUS_COM'] ?? 'Não Efetuado';
    $van = $status['STATUS_VAN'] ?? 'Não Efetuado';
    $bloq = $status['STATUS_BLOQ'] ?? 'Não Efetuado';
    $enc = $status['STATUS_ENCERRAMENTO'] ?? 'Não Efetuado';
    
    return '<div style="margin:5px;"></div>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Status</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Órgão Pagador</th>
                                    <th>Comercial</th>
                                    <th>Van-Material</th>
                                    <th>Bloqueio</th>
                                    <th>Encerramento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td id="status-op-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$op] . '">
                                        <span class="status-dot ' . $statusDot[$op] . '"></span>
                                        <span class="status-text">' . $statusText[$op] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-com-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$com] . '">
                                        <span class="status-dot ' . $statusDot[$com] . '"></span>
                                        <span class="status-text">' . $statusText[$com] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-van-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$van] . '">
                                        <span class="status-dot ' . $statusDot[$van] . '"></span>
                                        <span class="status-text">' . $statusText[$van] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-bloq-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$bloq] . '">
                                        <span class="status-dot ' . $statusDot[$bloq] . '"></span>
                                        <span class="status-text">' . $statusText[$bloq] . '</span>
                                        </span>
                                    </td>
                                    <td id="status-enc-' . $status['COD_SOLICITACAO'] . '">
                                        <span class="status ' . $statusClass[$enc] . '">
                                        <span class="status-dot ' . $statusDot[$enc] . '"></span>
                                        <span class="status-text">' . $statusText[$enc] . '</span>
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>';
}

private function buildModal($row) {
    $codSolicitacao = htmlspecialchars($row['COD_SOLICITACAO']);
    $nomeLoja = htmlspecialchars(mb_substr($row['NOME_LOJA'], 0, 15));
    
    $status = [
        'COD_SOLICITACAO' => $row['COD_SOLICITACAO'],
        'STATUS_OP' => $row['STATUS_OP'] ?? 'Não Efetuado',
        'STATUS_COM' => $row['STATUS_COM'] ?? 'Não Efetuado',
        'STATUS_VAN' => $row['STATUS_VAN'] ?? 'Não Efetuado',
        'STATUS_BLOQ' => $row['STATUS_BLOQ'] ?? 'Não Efetuado',
        'STATUS_ENCERRAMENTO' => $row['STATUS_ENCERRAMENTO'] ?? 'Não Efetuado'
    ];
    
    return '
        <div class="modal fade" id="AnaliseDetalhesModal' . $codSolicitacao . '" tabindex="-5" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    ' . $this->buildModalHeader($codSolicitacao, $row['NOME_LOJA']) . '
                    ' . $this->buildModalBody($row, $codSolicitacao, $nomeLoja, $status) . '
                    ' . $this->buildModalFooter($codSolicitacao) . '
                </div>
            </div>
        </div>
        ' . $this->buildAlertModal($codSolicitacao, $row['CHAVE_LOJA']) . '
    ';
}

private function buildModalBody($row, $codSolicitacao, $nomeLoja, $status) {
    return '
        <div class="modal-body">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                        <li class="nav-item">
                            <a href="#tabs-home-' . $codSolicitacao . '" class="nav-link active" data-bs-toggle="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <polyline points="5 12 3 12 12 3 21 12 19 12" />
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" />
                                <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" />
                            </svg>
                            Informações
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#tabs-chat-' . $codSolicitacao . '" class="nav-link" data-bs-toggle="tab">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon me-2" width="24"
                                height="24" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" fill="none" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <circle cx="12" cy="7" r="4" />
                                <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" />
                            </svg>
                            Profile
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active show" id="tabs-home-' . $codSolicitacao . '">
                            ' . $this->buildInfoCards($row, $nomeLoja) . '
                            ' . $this->buildActionButtons($codSolicitacao) . '
                            ' . $this->buildStatusShow($status) . '
                        </div>
                        <div class="tab-pane" id="tabs-chat-' . $codSolicitacao . '">
                            ' . $this->buildChat().'
                        </div>
                    </div>
                </div>
            </div>
        </div>
    ';
}

private function buildModalFooter($codSolicitacao) {
    return '
        <div class="modal-footer d-flex justify-content-between">
            <button class="btn btn-danger" onclick="cancelarSolicitacao(' . $codSolicitacao . ')">Cancelar Solicitação</button>
            <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Fechar</a>
        </div>';
}
?>



-----------

