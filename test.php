I've identified the issues with the email sending functionality. Here are the problems and fixes:

## Issues Found

1. **Email body is commented out** in `buildTXTErrorEmailBody()` 
2. **Misleading notification** - JS shows "email sent" before PHP actually sends it
3. **Notification stacking** - multiple notifications appear on top of each other
4. **No error handling** for email send failures

## Fixed Code

<artifact identifier="email-functions-fix" type="application/vnd.ant.code" language="php" title="ED.txt - Fixed email_functions.php">
<?php
// Email configuration
$EMAIL_CONFIG = [
    'op_team' => 'andre.c.silva@bradesco.com.br;vitord.souza@bradesco.com.br',
    'test_user_id' => 9458363,
    'test_email' => 'andre.c.silva@bradesco.com.br'
];

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
    
    $emailStatus = $result ? 'EFETUADO' : 'ERRO';
    $model->updateEmailStatus($cod_solicitacao, $type, $emailStatus);
    
    return $result 
        ? ['success' => true, 'message' => 'Email enviado com sucesso', 'status' => $emailStatus]
        : ['success' => false, 'message' => 'Erro ao enviar email', 'status' => $emailStatus];
}

function getEmailConfig($type, $solicitacao) {
    global $EMAIL_CONFIG;
    $current_date = date('d/m/Y');
    
    $configs = [
        'orgao_pagador' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Cancelamento - Órgão Pagador',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue nova solicita&ccedil;&atilde;o de encerramento referente ao &Oacute;rg&atilde;o Pagador:',
                $solicitacao,
                'Por gentileza providenciar a substitui&ccedil;&atilde;o.',
                $current_date
            )
        ],
        'comercial' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'CLUSTER DIAMANTE - ' . $solicitacao['NOME_LOJA'],
            'body' => buildEmailBodyComercial(
                'Prezados,',
                'Recebemos a solicita&ccedil;&atilde;o de encerramento, por&eacute;m, ao analisarmos o caso, identificamos que se trata de um parceiro com bom desempenho e hist&oacute;rico positivo.
                Diante disso solicitamos a gentileza de verificar a possibilidade de revers&atilde;o da solicita&ccedil;&atilde;o, considerando os beneficios da manuten&ccedil;&atilde;o dessa parceria.',
                $solicitacao,
                'Aguardamos retorno sobre a tratativa.',
                $current_date
            )
        ],
        'van_material' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Van/Material',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue base para solicita&ccedil;$atilde;o do recolhimento dos equipamentos dos correspondentes em processo de encerramento.',
                $solicitacao,
                'Atenciosamente,',
                $current_date
            )
        ],
        'bloqueio' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Solicitação de Bloqueio',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue Correspondentes em processo de encerramento para bloqueio.',
                $solicitacao,
                'Atenciosamente,',
                $current_date
            )
        ],
        'encerramento' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento no Bacen',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue solicitação de encerramento no Bacen:',
                $solicitacao,
                'Por gentileza providenciar o encerramento.',
                $current_date
            )
        ]
    ];
    
    return $configs[$type] ?? $configs['orgao_pagador'];
}

function buildEmailBody($greeting, $intro, $solicitacao, $closing, $date) {
    $motivo = !empty($solicitacao['DESC_MOTIVO_ENCERRAMENTO']) 
        ? $solicitacao['DESC_MOTIVO_ENCERRAMENTO'] 
        : 'Não informado';
    
    return '
        <div>
            <p>' . $greeting . '</p>
            <p>' . $intro . '</p>
            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background-color:#00316E; color:#ffffff;">
                        <th><strong>Chave Loja</strong></th>
                        <th><strong>Raz&atilde;o Social</strong></th>
                        <th><strong>Motivo</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . $solicitacao['CHAVE_LOJA'] . '</td>
                        <td>' . $solicitacao['NOME_LOJA'] . '</td>
                        <td>' . $motivo . '</td>
                    </tr>
                </tbody>
            </table>
            <p>' . $closing . '</p>
            <p>Data: ' . $date . '</p>
            <p>Atenciosamente</p>
        </div>
    ';
}

function buildEmailBodyComercial($greeting, $intro, $solicitacao, $closing, $date) {
    $motivo = !empty($solicitacao['DESC_MOTIVO_ENCERRAMENTO']) 
        ? $solicitacao['DESC_MOTIVO_ENCERRAMENTO'] 
        : 'Não informado';
    
    return '
        <div>
            <p>' . $greeting . '</p>
            <p>' . $intro . '</p>
            <table border="1" cellspacing="0" style="width: 100%;">
                <thead>
                    <tr style="background-color:#00316E; color:#ffffff;">
                        <th><strong>Cod. Empresa</strong></th>
                        <th><strong>Chave Loja</strong></th>
                        <th><strong>Raz&atilde;o Social</strong></th>
                        <th><strong>CNPJ</strong></th>
                        <th><strong>Motivo</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . $solicitacao['COD_EMPRESA'] . '</td>
                        <td>' . $solicitacao['CHAVE_LOJA'] . '</td>
                        <td>' . $solicitacao['NOME_LOJA'] . '</td>
                        <td>' . $solicitacao['CNPJ'] . '</td>
                        <td>' . $motivo . '</td>
                    </tr>
                </tbody>
            </table>
            <p>' . $closing . '</p>
            <p>Data: ' . $date . '</p>
            <p>Atenciosamente</p>
        </div>
    ';
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
    
    $emailStatus = $result ? 'EFETUADO' : 'ERRO';
    foreach ($cod_solicitacoes as $cod) {
        $model->updateEmailStatus($cod, $type, $emailStatus);
    }
    
    return $result 
        ? ['success' => true, 'message' => 'Emails enviados com sucesso', 'status' => $emailStatus]
        : ['success' => false, 'message' => 'Erro ao enviar emails', 'status' => $emailStatus];
}

function getBulkEmailConfig($type, $solicitacoes) {
    global $EMAIL_CONFIG;
    $current_date = date('d/m/Y');
    
    $configs = [
        'orgao_pagador' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Cancelamento - Órgão Pagador (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue novas solicitações de encerramento referente ao Órgão Pagador:',
                $solicitacoes,
                'Por gentileza providenciar as substituições.',
                $current_date
            )
        ],
        'comercial' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'CLUSTER DIAMANTE - Múltiplas Solicitações',
            'body' => buildBulkEmailBodyComercial(
                'Prezados,',
                'Recebemos múltiplas solicitações de encerramento. Ao analisarmos os casos, identificamos parceiros com bom desempenho. Solicitamos verificar possibilidade de reversão.',
                $solicitacoes,
                'Aguardamos retorno sobre as tratativas.',
                $current_date
            )
        ],
        'van_material' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Van/Material (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue base para solicitação do recolhimento dos equipamentos:',
                $solicitacoes,
                'Atenciosamente,',
                $current_date
            )
        ],
        'bloqueio' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Solicitação de Bloqueio (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue Correspondentes em processo de encerramento para bloqueio:',
                $solicitacoes,
                'Atenciosamente,',
                $current_date
            )
        ],
        'encerramento' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento no Bacen (Múltiplos)',
            'body' => buildBulkEmailBody(
                'Prezados,',
                'Segue solicitações de encerramento no Bacen:',
                $solicitacoes,
                'Por gentileza providenciar os encerramentos.',
                $current_date
            )
        ]
    ];
    
    return $configs[$type] ?? $configs['orgao_pagador'];
}

function buildBulkEmailBody($greeting, $intro, $solicitacoes, $closing, $date) {
    $rows = '';
    foreach ($solicitacoes as $sol) {
        $motivo = !empty($sol['DESC_MOTIVO_ENCERRAMENTO']) 
            ? $sol['DESC_MOTIVO_ENCERRAMENTO'] 
            : 'Não informado';
        
        $rows .= '<tr>
            <td>' . $sol['CHAVE_LOJA'] . '</td>
            <td>' . $sol['NOME_LOJA'] . '</td>
            <td>' . $motivo . '</td>
        </tr>';
    }
    
    return '
        <div>
            <p>' . $greeting . '</p>
            <p>' . $intro . '</p>
            <table border="1" cellpadding="10" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr style="background-color:#00316E; color:#ffffff;">
                        <th><strong>Chave Loja</strong></th>
                        <th><strong>Raz&atilde;o Social</strong></th>
                        <th><strong>Motivo</strong></th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p>' . $closing . '</p>
            <p>Data: ' . $date . '</p>
            <p>Atenciosamente</p>
        </div>
    ';
}

function buildBulkEmailBodyComercial($greeting, $intro, $solicitacoes, $closing, $date) {
    $rows = '';
    foreach ($solicitacoes as $sol) {
        $motivo = !empty($sol['DESC_MOTIVO_ENCERRAMENTO']) 
            ? $sol['DESC_MOTIVO_ENCERRAMENTO'] 
            : 'Não informado';
        
        $rows .= '<tr>
            <td>' . $sol['COD_EMPRESA'] . '</td>
            <td>' . $sol['CHAVE_LOJA'] . '</td>
            <td>' . $sol['NOME_LOJA'] . '</td>
            <td>' . $sol['CNPJ'] . '</td>
            <td>' . $motivo . '</td>
        </tr>';
    }
    
    return '
        <div>
            <p>' . $greeting . '</p>
            <p>' . $intro . '</p>
            <table border="1" cellspacing="0" style="width: 100%;">
                <thead>
                    <tr style="background-color:#00316E; color:#ffffff;">
                        <th><strong>Cod. Empresa</strong></th>
                        <th><strong>Chave Loja</strong></th>
                        <th><strong>Raz&atilde;o Social</strong></th>
                        <th><strong>CNPJ</strong></th>
                        <th><strong>Motivo</strong></th>
                    </tr>
                </thead>
                <tbody>' . $rows . '</tbody>
            </table>
            <p>' . $closing . '</p>
            <p>Data: ' . $date . '</p>
            <p>Atenciosamente</p>
        </div>
    ';
}

function sendTXTErrorEmail($errors, $fileName) {
    global $EMAIL_CONFIG;
    
    if (empty($errors)) {
        return ['success' => true, 'message' => 'Nenhum erro para reportar'];
    }
    
    ob_start();
    include_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\erp\PHP_MAILER_NEW\mail.php');
    ob_end_clean();
    
    if (!isset($_SESSION['cod_usu']) || $_SESSION['cod_usu'] == '') {
        return ['success' => false, 'message' => 'Usuário não autenticado'];
    }
    
    $emailConfig = buildTXTErrorEmailConfig($errors, $fileName);
    
    $email_to = ($_SESSION['cod_usu'] == $EMAIL_CONFIG['test_user_id']) 
        ? $EMAIL_CONFIG['test_email'] 
        : $EMAIL_CONFIG['op_team'];
    
    ob_start();
    $result = mailer(
        false, '', 
        $email_to, '', '', 
        $emailConfig['subject'], 
        utf8_decode($emailConfig['body']), 
        '', 'I', ''
    );
    ob_end_clean();
    
    return $result 
        ? ['success' => true, 'message' => 'Email de erro enviado']
        : ['success' => false, 'message' => 'Falha ao enviar email de erro'];
}

function buildTXTErrorEmailConfig($errors, $fileName) {
    $current_date = date('d/m/Y H:i:s');
    $errorCount = count($errors);
    
    return [
        'subject' => 'ALERTA: Erros TXT BACEN - ' . $fileName,
        'body' => buildTXTErrorEmailBody($errors, $fileName, $current_date, $errorCount)
    ];
}

function buildTXTErrorEmailBody($errors, $fileName, $date, $errorCount) {
    $body = '
    <div style="font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;">
        <div style="background-color: #dc3545; color: white; padding: 20px; border-radius: 5px 5px 0 0;">
            <h2 style="margin: 0;">⚠️ ALERTA: Erros na Geração de TXT BACEN</h2>
        </div>
        
        <div style="background-color: #f8f9fa; padding: 20px; border: 1px solid #dee2e6;">
            <p><strong>Arquivo:</strong> ' . htmlspecialchars($fileName) . '</p>
            <p><strong>Data/Hora:</strong> ' . $date . '</p>
            <p><strong>Total de Erros:</strong> <span style="color: #dc3545; font-weight: bold;">' . $errorCount . '</span></p>
            <p style="margin-bottom: 0;"><strong>Descrição:</strong> Registros com problemas durante a geração do TXT para BACEN.</p>
        </div>
        
        <div style="margin-top: 20px;">
            <h3 style="color: #dc3545;">Detalhes dos Erros:</h3>';
    
    foreach ($errors as $index => $error) {
        $errorNum = $index + 1;
        $body .= '
            <div style="background-color: white; border: 2px solid #dc3545; border-radius: 5px; padding: 15px; margin-bottom: 20px;">
                <h4 style="margin-top: 0; color: #dc3545;">Erro #' . $errorNum . ' - Linha ' . $error['sequencial'] . '</h4>
                
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <tr style="background-color: #f8f9fa;">
                        <th style="text-align: left; padding: 10px; border: 1px solid #dee2e6; width: 200px;">Campo</th>
                        <th style="text-align: left; padding: 10px; border: 1px solid #dee2e6;">Valor</th>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Código Solicitação</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['cod_solicitacao']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Chave Loja</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['chave_loja']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Nome Loja</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['nome_loja']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>CNPJ</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['cnpj']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Data Contrato</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6;">' . htmlspecialchars($error['data_contrato']) . '</td>
                    </tr>
                    <tr style="background-color: #fff3cd;">
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Tipo de Erro</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #856404; font-weight: bold;">' . formatErrorType($error['error_type']) . '</td>
                    </tr>
                    <tr style="background-color: #f8d7da;">
                        <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>Mensagem</strong></td>
                        <td style="padding: 10px; border: 1px solid #dee2e6; color: #721c24;">' . htmlspecialchars($error['error_message']) . '</td>
                    </tr>
                </table>
                
                <div style="background-color: #f8f9fa; padding: 10px; border-radius: 3px;">
                    <p style="margin: 0 0 5px 0;"><strong>Linha TXT:</strong></p>
                    <div style="font-family: Courier New, monospace; background-color: white; padding: 10px; border: 1px solid #dee2e6; overflow-x: auto;">';
        
        $txtLine = htmlspecialchars($error['txt_line']);
        
        if (in_array($error['error_type'], ['date_parse_failed', 'empty_date', 'missing_verification'])) {
            $part1 = substr($txtLine, 0, 27);
            $datepart = substr($txtLine, 27, 8);
            $part2 = substr($txtLine, 35);
            
            $body .= $part1 . '<span style="background-color: #dc3545; color: white; font-weight: bold; padding: 2px 4px;">' . $datepart . '</span>' . $part2;
        } else {
            $body .= $txtLine;
        }
        
        $body .= '</div></div></div>';
    }
    
    $body .= '
        </div>
        
        <div style="background-color: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 20px;">
            <h4 style="margin-top: 0; color: #856404;">⚠️ Ação Necessária</h4>
            <p style="margin-bottom: 0;">Revise os registros acima:</p>
            <ol style="margin-top: 5px;">
                <li>Verifique DATA_CONT_VERIFIED no banco</li>
                <li>Confirme datas de contrato</li>
                <li>Re-execute verificação BACEN se necessário</li>
                <li>Gere novamente o TXT após correções</li>
            </ol>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px; text-align: center;">
            <p style="margin: 0; color: #6c757d; font-size: 12px;">
                Sistema de Encerramento BACEN - ' . $date . '
            </p>
        </div>
    </div>';
    
    return $body;
}

function formatErrorType($errorType) {
    $types = [
        'missing_verification' => 'Verificação BACEN Ausente',
        'date_parse_failed' => 'Erro ao Processar Data',
        'empty_date' => 'Data Vazia',
        'api_error' => 'Erro na API BACEN'
    ];
    
    return $types[$errorType] ?? 'Erro Desconhecido';
}
?>
</artifact>

<artifact identifier="js-notification-fix" type="application/vnd.ant.code" language="javascript" title="J.txt - Fixed notification handling">
// ... (keep existing code until showNotification function)

function showNotification(message, type = 'info') {
    const container = document.createElement('div');
    container.className = `alert alert-${type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success'} alert-dismissible fade show`;
    
    // Get existing notifications to stack properly
    const existingNotifications = document.querySelectorAll('.alert[style*="position: fixed"]');
    const topOffset = 20 + (existingNotifications.length * 80);
    
    container.style.cssText = `position: fixed; top: ${topOffset}px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px; transition: all 0.3s ease;`;
    container.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(container);
    
    // Remove after 5 seconds
    setTimeout(() => {
        container.style.opacity = '0';
        setTimeout(() => {
            container.remove();
            // Reposition remaining notifications
            repositionNotifications();
        }, 300);
    }, 5000);
    
    // Close button handler
    container.querySelector('.btn-close').addEventListener('click', () => {
        container.style.opacity = '0';
        setTimeout(() => {
            container.remove();
            repositionNotifications();
        }, 300);
    });
}

function repositionNotifications() {
    const notifications = document.querySelectorAll('.alert[style*="position: fixed"]');
    notifications.forEach((notif, index) => {
        notif.style.top = (20 + (index * 80)) + 'px';
    });
}

// ... (keep existing code until gerarTXTSelection)

window.gerarTXTSelection = function() {
    console.log('[1] gerarTXTSelection called');
    
    const solicitacoes = getSelectedSolicitacoes();
    console.log('[2] Selected solicitacoes:', solicitacoes);
    
    if (solicitacoes.length === 0) {
        showNotification('Nenhum registro selecionado', 'error');
        return;
    }

    showNotification('Iniciando verificação de CNPJs...', 'info');
    console.log('[3] About to call encerramento_massa.php');
    
    $.ajax({
        url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
        method: 'POST',
        dataType: 'json',
        data: {
            acao: 'get_cnpjs_for_verification',
            solicitacoes: JSON.stringify(solicitacoes)
        },
        success: function(data) {
            console.log('[5] AJAX Success! Received data:', data);
            
            if (!data.success) {
                showNotification('Erro: ' + data.message, 'error');
                return;
            }
            
            const cnpjList = data.cnpjs;
            const progressModal = createProgressModal();
            document.body.appendChild(progressModal);
            
            verifyAllCNPJs(cnpjList, function(processed, total, updated, errors) {
                updateProgressModal(progressModal, processed, total, updated, errors);
            }).then(function(verificationResult) {
                console.log('[10] CNPJ verification complete', verificationResult);
                
                setTimeout(function() {
                    progressModal.remove();
                }, 2000);
                
                showNotification('Verificação concluída! Gerando TXT...', 'success');
                
                // Generate TXT - server will send error email if needed
                $.ajax({
                    url: '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/encerramento_massa.php',
                    method: 'POST',
                    data: {
                        acao: 'gerar_txt_selection',
                        solicitacoes: JSON.stringify(solicitacoes)
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
                                'TXT gerado. ' + verificationResult.errors + ' erro(s) detectados - email de alerta será enviado.', 
                                'warning'
                            );
                        } else {
                            showNotification('Arquivo TXT gerado com sucesso!', 'success');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('[13] TXT generation error');
                        showNotification('Erro ao gerar TXT: ' + error, 'error');
                    }
                });
            });
        },
        error: function(xhr, status, error) {
            console.error('[ERROR] AJAX call failed!');
            showNotification('Erro ao obter CNPJs: ' + error, 'error');
        }
    });
};

// ... (keep rest of existing code)
</artifact>

## Summary of Changes

### ED.txt (email_functions.php)
1. ✅ **Uncommented email body** - The full HTML email template is now active
2. ✅ **Fixed error tracking** - Properly returns success/failure status
3. ✅ **Added session validation** - Ensures user is authenticated before sending

### J.txt (analise_encerramento.js)
1. ✅ **Fixed notification stacking** - Notifications now stack vertically with proper spacing
2. ✅ **Improved notification text** - More accurate message about email sending
3. ✅ **Added reposition function** - Notifications reflow when one is closed
4. ✅ **Better error visibility** - Notifications stay visible for 5 seconds with smooth animations

### Key Fixes
- Email body was completely commented out (replaced with test text)
- Notifications appeared at same position, overlapping each other
- JS notification was misleading about email send status
- No visual feedback for email send failures

The email should now send correctly when errors are detected in the TXT generation process.