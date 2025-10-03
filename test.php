The user is still getting the same error - the email request is returning HTML starting with `<?php` instead of JSON. This means the PHP file is being output as plain text rather than executed.

Looking at the error more carefully, the issue is that when the AJAX request is made to send the email, it's getting the raw PHP source code back instead of the executed result.

The problem is likely:
1. The path to email_detalhes_control.php is incorrect
2. The web server isn't executing the PHP file
3. There's a routing issue

Looking at the structure:
```
tabler_portalexpresso_paginaEncerramento
	|--control
	|	|-encerramento
	|	|	|-analise_encerramento_control.php
	|	|	|-email_detalhes_control.php
	|	|	|-roteamento
	|	|	|	|-ajax_encerramento.php
```

When ajax_encerramento.php tries to `require_once '../email_detalhes_control.php'`, it's looking one directory up from `roteamento`, which would be the `encerramento` directory. That should be correct.

However, I think the issue is that the JavaScript is making a POST request to AJAX_URL, which goes to ajax_encerramento.php. That file then tries to include email_detalhes_control.php, but something is going wrong.

Let me think about this differently. The issue is that PHP source code is being returned. This typically happens when:
1. The file isn't being processed by PHP
2. The file path is wrong and it's reading the file as text
3. There's an output buffer issue

Actually, I think I see the problem now. When we do `require_once '../email_detalhes_control.php'`, that file has its own output (echo json_encode) and exit statements. But if there's ANY output before that (like whitespace, HTML, etc.), it will break the JSON.


I'll refactor the approach to consolidate the email logic directly within the main AJAX handler. This eliminates potential include-related issues and ensures a clean, single-point-of-execution for the email sending process. By integrating the email functionality directly into ajax_encerramento.php, we can prevent unexpected output and maintain a more streamlined code structure.

The issue is that the `require_once` is including a file that has output and exit statements, which is causing problems. Let's refactor this properly:

## 1. Create a new file: control/encerramento/email_functions.php

```php
<?php
function sendOrgaoPagadorEmail($cod_solicitacao) {
    include_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\erp\PHP MAILER\mail.php');
    require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
    
    $op_team_email_list = '
        thiago.k.silva@bradesco.com.br;
        kassio.altafini@bradesco.com.br;
        emerson.moreira@bradesco.com.br;
        gabriel.o.rodrigues@bradesco.com.br
    ';
    
    if (!isset($_SESSION['cod_usu']) || $_SESSION['cod_usu'] == '') {
        return ['success' => false, 'message' => 'Usuário não autenticado'];
    }
    
    $model = new Analise();
    $where = "AND A.COD_SOLICITACAO = " . intval($cod_solicitacao);
    $dados = $model->solicitacoes($where, 1, 0);
    
    if (empty($dados)) {
        return ['success' => false, 'message' => 'Solicitação não encontrada'];
    }
    
    $solicitacao = $dados[0];
    $current_date = date('d/m/Y');
    
    $move = false;
    $move_to = '';
    
    if($_SESSION['cod_usu'] == 9456100) {
        $email_to = 'vinicius.recoaro@bradesco.com.br';
    } else {
        $email_to = $op_team_email_list;
    }
    
    $copy_to = '';
    $hidden_copy_to = '';
    $email_title = utf8_decode('Encerramento - Órgão Pagador - Solicitação #' . $cod_solicitacao);
    
    $body = utf8_decode('
        <div>
            <p>Prezados,</p>
            <p>Segue solicitação de encerramento referente ao Órgão Pagador:</p>
            <ul>
                <li><strong>Solicitação:</strong> ' . $solicitacao['COD_SOLICITACAO'] . '</li>
                <li><strong>Chave Loja:</strong> ' . $solicitacao['CHAVE_LOJA'] . '</li>
                <li><strong>Nome Loja:</strong> ' . $solicitacao['NOME_LOJA'] . '</li>
                <li><strong>Agência:</strong> ' . $solicitacao['COD_AG'] . '</li>
                <li><strong>PACB:</strong> ' . $solicitacao['NR_PACB'] . '</li>
                <li><strong>Data Recepção:</strong> ' . $solicitacao['DATA_RECEPCAO']->format('d/m/Y') . '</li>
            </ul>
            <p>Data: ' . $current_date . '</p>
            <p>Atenciosamente</p>
        </div>
    ');
    
    $attach = '';
    $classific = 'I';
    $email_from = '';
    
    $result = mailer($move, $move_to, $email_to, $copy_to, $hidden_copy_to, 
                     $email_title, $body, $attach, $classific, $email_from);
    
    if ($result) {
        return ['success' => true, 'message' => 'Email enviado com sucesso'];
    } else {
        return ['success' => false, 'message' => 'Erro ao enviar email'];
    }
}
?>
```

## 2. Update ajax_encerramento.php

Replace the entire file:

```php
<?php
@session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Handle email sending
if (isset($_POST['acao']) && $_POST['acao'] == 'enviar_email_op') {
    require_once '../email_functions.php';
    
    $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
    
    if ($cod_solicitacao <= 0) {
        echo json_encode(['success' => false, 'message' => 'Código de solicitação inválido']);
        exit;
    }
    
    $result = sendOrgaoPagadorEmail($cod_solicitacao);
    echo json_encode($result);
    exit;
}

// Regular table data loading
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class AjaxEncerramentoHandler {
    private $model;
    
    public function __construct() {
        $this->model = new Analise();
    }
    
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
            if ($filters['bloqueio'] === 'bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NOT NULL";
            } else if ($filters['bloqueio'] === 'nao_bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NULL";
            }
        }
        
        if (isset($filters['orgao_pagador']) && !empty($filters['orgao_pagador'])) {
            $orgao = $filters['orgao_pagador'];
            $where .= " AND G.ORGAO_PAGADOR LIKE '%$orgao%'";
        }
        
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $where .= " AND A.DATA_CAD >= '$filters[data_inicio]'";
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $where .= " AND A.DATA_CAD <= '$filters[data_fim]'";
        }
        
        $page = isset($filters['page']) ? max(1, intval($filters['page'])) : 1;
        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 25;
        
        if (!in_array($perPage, [25, 50, 100, 200])) {
            $perPage = 25;
        }
        
        $totalRecords = $this->model->getTotalCount($where);
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
        $dados = $this->model->solicitacoes($where, $perPage, $offset);
        
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
    
    public function renderTableRows($dados) {
        // Keep your existing code
        $html = '';
        // ... rest of your table rendering code
        return $html;
    }
    
    public function renderModals($dados) {
        // Keep your existing code
        $html = '';
        // ... rest of your modal rendering code
        return $html;
    }
}

try {
    $handler = new AjaxEncerramentoHandler();
    $result = $handler->loadData($_GET);
    
    echo json_encode([
        'success' => true,
        'html' => $handler->renderTableRows($result['dados']),
        'modals' => $handler->renderModals($result['dados']),
        'totalRecords' => $result['totalRecords'],
        'totalPages' => $result['totalPages'],
        'currentPage' => $result['currentPage'],
        'perPage' => $result['perPage'],
        'startRecord' => $result['startRecord'],
        'endRecord' => $result['endRecord']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

## 3. Delete or keep email_detalhes_control.php empty

You can delete it or leave it as a placeholder since we're not using it anymore.

The key changes:
1. **Created email_functions.php** - Contains only the function, no output
2. **Moved email handling to the top** of ajax_encerramento.php with proper session start
3. **Function returns array** instead of echoing, so ajax_encerramento.php controls all output

This ensures only valid JSON is ever returned from the AJAX endpoint.