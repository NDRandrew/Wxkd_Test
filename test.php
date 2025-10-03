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
    
    return $result 
        ? ['success' => true, 'message' => 'Email enviado com sucesso']
        : ['success' => false, 'message' => 'Erro ao enviar email'];
}

function getEmailConfig($type, $solicitacao) {
    global $EMAIL_CONFIG;
    $current_date = date('d/m/Y');
    
    $configs = [
        'orgao_pagador' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Órgão Pagador',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue nova solicitação de encerramento referente ao Órgão Pagador:',
                $solicitacao,
                'Por gentileza providenciar a substituição.',
                $current_date
            )
        ],
        'comercial' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Comercial',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue nova solicitação de encerramento - Área Comercial:',
                $solicitacao,
                'Por gentileza providenciar as ações necessárias.',
                $current_date
            )
        ],
        'van_material' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Encerramento - Van/Material',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue solicitação para retirada de equipamentos:',
                $solicitacao,
                'Por gentileza agendar a retirada dos materiais.',
                $current_date
            )
        ],
        'bloqueio' => [
            'recipients' => $EMAIL_CONFIG['op_team'],
            'subject' => 'Solicitação de Bloqueio',
            'body' => buildEmailBody(
                'Prezados,',
                'Segue solicitação de bloqueio de correspondente:',
                $solicitacao,
                'Por gentileza providenciar o bloqueio.',
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
                        <th><strong>Razão Social</strong></th>
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
?>


-----------

<?php
@session_start();

function json_encode_custom($data) {
    if (is_null($data)) return 'null';
    if ($data === true) return 'true';
    if ($data === false) return 'false';
    if (is_int($data) || is_float($data)) return (string)$data;
    
    if (is_string($data)) {
        return '"' . trim(addslashes($data)) . '"';
    }
    
    if (is_array($data)) {
        $isAssoc = array_keys($data) !== range(0, count($data) - 1);
        $items = [];
        foreach ($data as $key => $value) {
            $encodedValue = json_encode_custom($value);
            $items[] = $isAssoc ? '"' . addslashes($key) . '":' . $encodedValue : $encodedValue;
        }
        return $isAssoc ? '{' . implode(',', $items) . '}' : '[' . implode(',', $items) . ']';
    }
    
    if (is_object($data)) {
        $items = [];
        foreach (get_object_vars($data) as $key => $value) {
            $items[] = '"' . addslashes($key) . '":' . json_encode_custom($value);
        }
        return '{' . implode(',', $items) . '}';
    }
    
    return 'null';
}

// Handle email requests
if (isset($_POST['acao']) && $_POST['acao'] == 'enviar_email') {
    ob_start();
    
    try {
        require_once '../email_functions.php';
        
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $cod_solicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        
        if ($cod_solicitacao <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Código de solicitação inválido']);
            exit;
        }
        
        if (empty($tipo)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Tipo de email não especificado']);
            exit;
        }
        
        $result = sendEmail($tipo, $cod_solicitacao);
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom($result);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}

// Regular data loading
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
            $where .= $filters['bloqueio'] === 'bloqueado' 
                ? " AND F.DATA_BLOQUEIO IS NOT NULL"
                : " AND F.DATA_BLOQUEIO IS NULL";
        }
        
        if (isset($filters['orgao_pagador']) && !empty($filters['orgao_pagador'])) {
            $where .= " AND G.ORGAO_PAGADOR LIKE '%" . $filters['orgao_pagador'] . "%'";
        }
        
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $where .= " AND A.DATA_CAD >= '" . $filters['data_inicio'] . "'";
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $where .= " AND A.DATA_CAD <= '" . $filters['data_fim'] . "'";
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
        if (empty($dados)) {
            return '<tr><td colspan="16" class="text-center">Nenhum registro encontrado</td></tr>';
        }
        
        $html = '';
        foreach ($dados as $row) {
            $html .= '<tr data-bs-toggle="modal" data-bs-target="#AnaliseDetalhesModal' . htmlspecialchars($row['COD_SOLICITACAO']) . '" style="cursor:pointer;">';
            $html .= $this->renderTableCell($row);
            $html .= '</tr>';
        }
        
        return $html;
    }
    
    private function renderTableCell($row) {
        $cells = '<th class="text-center align-middle" style="background-color: #d8d8d8; border-style:none !important;">
                <label class="form-check d-inline-flex justify-content-center align-items-center p-0 m-0">
                    <input class="form-check-input position-static m-0" type="checkbox" onclick="event.stopPropagation();" />
                    <span class="form-check-label d-none"></span>
                </label>
            </th>';
        
        $cells .= '<th><span style="display: block; text-align: center;">' . htmlspecialchars($row['COD_SOLICITACAO']) . '</span></th>';
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['COD_AG']) . htmlspecialchars($row['NR_PACB']) . '</span></td>';
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['CHAVE_LOJA']) . '</span></td>';
        $cells .= '<td><span style="display: block; text-align: center;">' . $row['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
        $cells .= $this->renderDateCell($row['DATA_RETIRADA_EQPTO']);
        $cells .= $this->renderBloqueioCell($row['DATA_BLOQUEIO']);
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['DATA_LAST_TRANS']) . '</span></td>';
        $cells .= $this->renderTextCell($row['MOTIVO_BLOQUEIO'], 'Sem Motivo de Bloqueio');
        $cells .= $this->renderTextCell($row['DESC_MOTIVO_ENCERRAMENTO'], 'Sem Motivo de Encerramento');
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['ORGAO_PAGADOR']) . '</span></td>';
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['CLUSTER']) . '</span></td>';
        $cells .= $this->renderAptoCell($row['PARM']);
        $cells .= $this->renderAptoCell($row['TRAG']);
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['MEDIA_CONTABEIS']) . '</span></td>';
        $cells .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($row['MEDIA_NEGOCIO']) . '</span></td>';
        
        return $cells;
    }
    
    private function renderDateCell($date) {
        return $date 
            ? '<td><span style="display: block; text-align: center;">' . $date->format('d/m/Y') . '</span></td>'
            : '<td><span class="text-red" style="display: block; text-align: center;">Sem Data</span></td>';
    }
    
    private function renderBloqueioCell($date) {
        return $date
            ? '<td><span class="text-green" style="display: block; text-align: center;">Bloqueado</span></td>'
            : '<td><span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span></td>';
    }
    
    private function renderTextCell($value, $emptyText) {
        return $value
            ? '<td><span style="display: block; text-align: center;">' . htmlspecialchars($value) . '</span></td>'
            : '<td><span class="text-red" style="display: block; text-align: center;">' . $emptyText . '</span></td>';
    }
    
    private function renderAptoCell($value) {
        if (!$value || $value == 'NÃO APTO') {
            return '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
        }
        return '<td><span style="display: block; text-align: center;">' . htmlspecialchars($value) . '</span></td>';
    }
    
    public function renderModals($dados) {
        if (empty($dados)) return '';
        
        $html = '';
        foreach ($dados as $row) {
            $html .= $this->buildModal($row);
        }
        return $html;
    }
    
    private function buildModal($row) {
        $codSolicitacao = htmlspecialchars($row['COD_SOLICITACAO']);
        $nomeLoja = htmlspecialchars(mb_substr($row['NOME_LOJA'], 0, 15));
        
        return '
            <div class="modal fade" id="AnaliseDetalhesModal' . $codSolicitacao . '" tabindex="-5" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        ' . $this->buildModalHeader($codSolicitacao, $row['NOME_LOJA']) . '
                        ' . $this->buildModalBody($row, $codSolicitacao, $nomeLoja) . '
                        ' . $this->buildModalFooter() . '
                    </div>
                </div>
            </div>
            ' . $this->buildAlertModal($codSolicitacao, $row['CHAVE_LOJA']) . '
        ';
    }
    
    private function buildModalHeader($codSolicitacao, $nomeLoja) {
        return '
            <div class="modal-header">
                <h5 class="modal-title">Detalhes - Solicitação ' . $codSolicitacao . ' - ' . htmlspecialchars($nomeLoja) . '</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
        ';
    }
    
    private function buildModalBody($row, $codSolicitacao, $nomeLoja) {
        return '
            <div class="modal-body">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                            <li class="nav-item">
                                <a href="#tabs-home-' . $codSolicitacao . '" class="nav-link active" data-bs-toggle="tab">Informações</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="tabs-home-' . $codSolicitacao . '">
                                ' . $this->buildInfoCards($row, $nomeLoja) . '
                                ' . $this->buildActionButtons($codSolicitacao) . '
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
    }
    
    private function buildInfoCards($row, $nomeLoja) {
        return '
            <div class="row">
                <div class="col mb-3">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Chave Loja</h3></div>
                        <div class="card-body"><p class="text-secondary text-center">' . htmlspecialchars($row['CHAVE_LOJA']) . '</p></div>
                    </div>
                </div>
                <div class="col mb-3">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Agência/PACB</h3></div>
                        <div class="card-body"><p class="text-secondary text-center">' . htmlspecialchars($row['COD_AG']) . '|' . htmlspecialchars($row['NR_PACB']) . '</p></div>
                    </div>
                </div>
                <div class="col mb-3">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Nome Loja</h3></div>
                        <div class="card-body"><p class="text-secondary text-center">' . $nomeLoja . '...</p></div>
                    </div>
                </div>
            </div>
        ';
    }
    
    private function buildActionButtons($codSolicitacao) {
        $buttons = ['orgao_pagador' => 'Órgão Pagador', 'comercial' => 'Comercial', 
                   'van_material' => 'Van-Material', 'bloqueio' => 'Bloqueio'];
        
        $html = '<div class="card"><div class="card-header"><h3 class="card-title">Ações</h3></div>';
        $html .= '<div class="card-body"><div class="d-flex gap-2 justify-content-center flex-wrap">';
        
        foreach ($buttons as $type => $label) {
            $html .= '<button class="btn email-action-btn" data-tipo="' . $type . '" data-solicitacao="' . $codSolicitacao . '">' . $label . '</button>';
        }
        
        $html .= '<button class="btn btn-red" data-bs-toggle="modal" data-bs-target="#AlertaEncerramento' . $codSolicitacao . '">Encerramento</button>';
        $html .= '</div></div></div>';
        
        return $html;
    }
    
    private function buildModalFooter() {
        return '<div class="modal-footer"><a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">Fechar</a></div>';
    }
    
    private function buildAlertModal($codSolicitacao, $chaveLoja) {
        return '
            <div class="modal" id="AlertaEncerramento' . $codSolicitacao . '" tabindex="-6">
                <div class="modal-dialog modal-sm">
                    <div class="modal-content">
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        <div class="modal-status bg-danger"></div>
                        <div class="modal-body text-center py-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 9v2m0 4v.01"/>
                                <path d="M5 19h14a2 2 0 0 0 1.84 -2.75l-7.1 -12.25a2 2 0 0 0 -3.5 0l-7.1 12.25a2 2 0 0 0 1.75 2.75"/>
                            </svg>
                            <h3>Tem Certeza?</h3>
                            <div class="text-secondary">Você irá encerrar o correspondente ' . htmlspecialchars($chaveLoja) . ' no Bacen.</div>
                        </div>
                        <div class="modal-footer">
                            <div class="w-100">
                                <div class="row">
                                    <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">Cancelar</a></div>
                                    <div class="col"><button class="btn btn-danger w-100 email-action-btn" data-tipo="encerramento" data-solicitacao="' . $codSolicitacao . '">Encerrar</button></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
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
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


-----------

// analise_encerramento.js - Clean version
(function() {
    'use strict';

    const AJAX_URL = '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/roteamento/ajax_encerramento.php';

    let currentPage = window.pageState ? window.pageState.currentPage : 1;
    let totalPages = window.pageState ? window.pageState.totalPages : 0;
    let perPage = window.pageState ? window.pageState.perPage : 25;

    function initialize() {
        setupDateInputs();
        initializeEventListeners();
        highlightActiveFilters();
        attachPageNumberHandlers();
        
        if (window.pageState && window.pageState.autoLoadData) {
            setTimeout(() => handleFormSubmit(), 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

    function setupDateInputs() {
        setupDateInput('dataInicioFilter', 'dataInicioDisplay');
        setupDateInput('dataFimFilter', 'dataFimDisplay');
        setupDatePicker('dataInicioFilter', 'dataInicioDisplay');
        setupDatePicker('dataFimFilter', 'dataFimDisplay');
    }

    function setupDateInput(dateId, displayId) {
        const dateEl = document.getElementById(dateId);
        const displayEl = document.getElementById(displayId);
        if (!dateEl || !displayEl) return;
        
        dateEl.addEventListener('change', function() {
            if (this.value) {
                const [y, m, d] = this.value.split('-');
                displayEl.value = `${d}/${m}/${y}`;
            } else {
                displayEl.value = '';
            }
        });
    }

    function setupDatePicker(dateId, displayId) {
        const dateEl = document.getElementById(dateId);
        const displayEl = document.getElementById(displayId);
        const wrapper = dateEl?.closest('.date-input-wrapper');
        if (!dateEl || !displayEl || !wrapper) return;

        function toDisplay(iso) {
            if (!iso) return '';
            const [y, m, d] = iso.split('-');
            return `${d}/${m}/${y}`;
        }

        displayEl.value = toDisplay(dateEl.value);

        function openPicker(e) {
            if (e.type === 'pointerdown' && e.button !== 0) return;
            e.preventDefault();
            try {
                dateEl.showPicker?.() || dateEl.focus({ preventScroll: true });
            } catch { dateEl.focus({ preventScroll: true }); }
        }

        wrapper.addEventListener('pointerdown', openPicker);
        wrapper.addEventListener('click', openPicker);
        
        dateEl.addEventListener('keydown', (e) => {
            if (['Enter', ' ', 'ArrowDown'].includes(e.key)) {
                e.preventDefault();
                try { dateEl.showPicker?.(); } catch {}
            }
        });

        dateEl.addEventListener('change', () => displayEl.value = toDisplay(dateEl.value));
    }

    function initializeEventListeners() {
        const elements = {
            clearFilters: () => clearAllFilters(),
            searchBtn: () => { currentPage = 1; handleFormSubmit(); },
            applyFiltersBtn: () => { currentPage = 1; handleFormSubmit(); },
            perPageSelect: function() { perPage = parseInt(this.value); currentPage = 1; handleFormSubmit(); },
            prevPage: () => { if (currentPage > 1) { currentPage--; handleFormSubmit(); } },
            nextPage: () => { if (currentPage < totalPages) { currentPage++; handleFormSubmit(); } }
        };

        Object.entries(elements).forEach(([id, handler]) => {
            const el = document.getElementById(id);
            if (el) el.addEventListener('click', handler);
        });

        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1;
                    handleFormSubmit();
                }
            });
        }
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

    function updateUI(data, params) {
        const tableBody = document.getElementById('tableBody');
        const modalsContainer = document.getElementById('modalsContainer');
        
        if (tableBody) tableBody.innerHTML = data.html;
        if (modalsContainer) {
            modalsContainer.innerHTML = data.modals;
            attachEmailHandlers();
        }
        
        updatePaginationInfo(data);
        updatePaginationControls();
        
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.pushState({}, '', newUrl);
        
        highlightActiveFilters();
        
        if (window.pageState && !window.pageState.autoLoadData) {
            document.getElementById('tableContainer')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        if (window.pageState) window.pageState.autoLoadData = false;
    }

    function updatePaginationInfo(data) {
        const els = {
            totalRecords: data.totalRecords,
            startRecord: data.startRecord,
            endRecord: data.endRecord
        };
        
        Object.entries(els).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        });
        
        currentPage = data.currentPage;
        totalPages = data.totalPages;
        perPage = data.perPage;
    }

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

    function showNotification(message, type = 'info') {
        const container = document.createElement('div');
        container.className = `alert alert-${type === 'error' ? 'danger' : 'success'} alert-dismissible fade show`;
        container.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        container.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(container);
        setTimeout(() => container.remove(), 5000);
    }

    function clearAllFilters() {
        ['searchInput', 'bloqueioFilter', 'orgaoPagadorFilter', 'dataInicioFilter', 'dataInicioDisplay', 'dataFimFilter', 'dataFimDisplay']
            .forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
        
        currentPage = 1;
        handleFormSubmit();
    }

    function updatePaginationControls() {
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        
        if (prevBtn) {
            prevBtn.classList.toggle('disabled', currentPage <= 1);
            prevBtn.querySelector('a')?.setAttribute('aria-disabled', currentPage <= 1);
        }
        
        if (nextBtn) {
            nextBtn.classList.toggle('disabled', currentPage >= totalPages);
            nextBtn.querySelector('a')?.setAttribute('aria-disabled', currentPage >= totalPages);
        }
        
        rebuildPageNumbers();
    }

    function attachPageNumberHandlers() {
        document.getElementById('paginationControls')?.querySelectorAll('.page-number').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page && page !== currentPage) {
                    currentPage = page;
                    handleFormSubmit();
                }
            });
        });
    }

    function rebuildPageNumbers() {
        const paginationControls = document.getElementById('paginationControls');
        if (!paginationControls) return;
        
        paginationControls.querySelectorAll('.page-number').forEach(item => item.parentElement.remove());
        paginationControls.querySelectorAll('.page-item:not(#prevPage):not(#nextPage)').forEach(item => {
            if (item.querySelector('.page-link')?.textContent === '...') item.remove();
        });
        
        const maxPages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPages / 2));
        let endPage = Math.min(totalPages, startPage + maxPages - 1);
        
        if (endPage - startPage < maxPages - 1) {
            startPage = Math.max(1, endPage - maxPages + 1);
        }
        
        const fragment = document.createDocumentFragment();
        
        if (startPage > 1) {
            fragment.appendChild(createPageItem(1, false));
            if (startPage > 2) fragment.appendChild(createEllipsisItem());
        }
        
        for (let i = startPage; i <= endPage; i++) {
            fragment.appendChild(createPageItem(i, i === currentPage));
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) fragment.appendChild(createEllipsisItem());
            fragment.appendChild(createPageItem(totalPages, false));
        }
        
        const nextBtn = document.getElementById('nextPage');
        nextBtn.parentNode.insertBefore(fragment, nextBtn);
        
        paginationControls.querySelectorAll('.page-number').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const page = parseInt(this.getAttribute('data-page'));
                if (page !== currentPage) {
                    currentPage = page;
                    handleFormSubmit();
                }
            });
        });
    }

    function createPageItem(pageNum, isActive) {
        const li = document.createElement('li');
        li.className = 'page-item' + (isActive ? ' active' : '');
        
        const a = document.createElement('a');
        a.className = 'page-link page-number';
        a.href = '#';
        a.setAttribute('data-page', pageNum);
        a.textContent = pageNum;
        
        li.appendChild(a);
        return li;
    }

    function createEllipsisItem() {
        const li = document.createElement('li');
        li.className = 'page-item disabled';
        
        const span = document.createElement('span');
        span.className = 'page-link';
        span.textContent = '...';
        
        li.appendChild(span);
        return li;
    }

    function highlightActiveFilters() {
        const urlParams = new URLSearchParams(window.location.search);
        
        ['searchInput', 'bloqueioFilter', 'orgaoPagadorFilter', 'dataInicioDisplay', 'dataFimDisplay'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.style.borderColor = '';
                el.style.borderWidth = '';
            }
        });
        
        const filters = {
            searchInput: 'search',
            bloqueioFilter: 'bloqueio',
            orgaoPagadorFilter: 'orgao_pagador'
        };
        
        Object.entries(filters).forEach(([elId, param]) => {
            const el = document.getElementById(elId);
            if (el && (urlParams.has(param) || el.value)) {
                el.style.borderColor = '#206bc4';
                el.style.borderWidth = '2px';
            }
        });
        
        ['dataInicioDisplay', 'dataFimDisplay'].forEach(displayId => {
            const displayEl = document.getElementById(displayId);
            const filterId = displayId.replace('Display', '');
            const filterEl = document.getElementById(filterId);
            
            if (displayEl && filterEl && filterEl.value) {
                displayEl.style.borderColor = '#206bc4';
                displayEl.style.borderWidth = '2px';
            }
        });

        const clearBtn = document.getElementById('clearFilters');
        if (clearBtn) {
            const hasFilters = ['search', 'bloqueio', 'orgao_pagador', 'data_inicio', 'data_fim']
                .some(param => urlParams.has(param));
            
            clearBtn.classList.toggle('btn-warning', hasFilters);
            clearBtn.classList.toggle('btn-secondary', !hasFilters);
        }
    }

    function showLoading() {
        document.getElementById('loadingOverlay')?.classList.add('active');
    }

    function hideLoading() {
        document.getElementById('loadingOverlay')?.classList.remove('active');
    }

})();



--------


How to Add New Email Types
1. Add to email_functions.php:
'new_type' => [
    'recipients' => $EMAIL_CONFIG['op_team'],
    'subject' => 'New Email Type',
    'body' => buildEmailBody(/* ... */)
]
2. Add button in modal (ajax_encerramento.php):
'new_type' => 'Button Label' 