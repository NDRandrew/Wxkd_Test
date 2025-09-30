<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\NEW_MSSQL.class.php'); 

#[AllowDynamicProperties]
class Analise{
    private $sql;
    
    public function __construct() {
        $this->sql = new MSSQL('ERP');
    }

    // ... (keep all existing methods)

    /**
     * Get total count of records matching the WHERE clause
     */
    public function getTotalCount($where) {
        $query = "
            SELECT COUNT(*) as TOTAL
            FROM 
                TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK) 
                JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK) ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO 
                JOIN TB_ACIONAMENTO_PRESTACAO_CONTA C WITH (NOLOCK) ON A.COD_PRESTACAO_CONTA = C.COD_PRESTACAO_CONTA 
                JOIN TB_ACIONAMENTO_STATUS D WITH (NOLOCK) ON A.COD_STATUS = D.COD_STATUS 
                LEFT JOIN RH..TB_FUNCIONARIOS E WITH (NOLOCK) ON A.COD_SOLICITANTE = E.COD_FUNC
                LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK) ON A.CHAVE_LOJA = F.CHAVE_LOJA 
                JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK) ON A.COD_SOLICITACAO = G.COD_SOLICITACAO  
                LEFT JOIN TB_ACIONAMENTO_RESPOSTAS H WITH (NOLOCK) ON G.COD_SOLUCAO = H.COD_RESPOSTA 
            WHERE
                1=1 AND F.BE_INAUGURADO = 1
                ".$where;
        
        $dados = $this->sql->select($query);
        return $dados[0]['TOTAL'];
    }

    /**
     * Get paginated solicitacoes
     * @param string $where WHERE clause conditions
     * @param int $limit Records per page
     * @param int $offset Starting record offset
     */
    public function solicitacoes($where, $limit = 50, $offset = 0) {
        $query = "
            SELECT
                A.COD_SOLICITACAO COD_SOLICITACAO, 
                A.COD_AG COD_AG, 
                CASE WHEN A.COD_AG = F.COD_AG_LOJA THEN F.NOME_AG ELSE 'AGENCIA' END NOME_AG, 
                A.CHAVE_LOJA CHAVE_LOJA, 
                F.NOME_LOJA NOME_LOJA, 
                G.NR_PACB NR_PACB, 
                F.COD_EMPRESA COD_EMPRESA,
                A.DATA_CAD AS DATA_RECEPCAO, 
                F.RZ_SOCIAL_EMP NOME_EMPRESA,
                F.DATA_RETIRADA_EQPTO,
                F.DATA_BLOQUEIO,
                F.MOTIVO_BLOQUEIO,
                F.DESC_MOTIVO_ENCERRAMENTO,
                G.ORGAO_PAGADOR ORGAO_PAGADOR,
                A.COD_TIPO_SERVICO COD_TIPO_SERVICO,
                CASE 
                    WHEN F.BE_AVANCADO = 'S' THEN 'AVANCADO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_CLUBE = 'S' THEN 'CLUBE,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_COMPLETO = 'S' THEN 'COMPLETO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_EXCLUSIVO = 1 THEN 'EXCLUSIVO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_GERENCIADO = 'S' THEN 'GERENCIADO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_ILHA = 1 THEN 'ILHA,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_INAUGURADO = 1 THEN 'INAUGURADO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_MULTIJUNCAO = 1 THEN 'MULTIJUNCAO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_NEGOCIO = 1 THEN 'NEGOCIO,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_ORG_PAGADOR = 'S' THEN 'ORGAO_PAGADOR,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_PLATAFORMA = 1 THEN 'PLATAFORMA,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_PRESENCA = 'S' THEN 'PRESENCA,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_TEF = 1 THEN 'TEF,'
                    ELSE ''
                END +
                CASE 
                    WHEN F.BE_TRX_NEG = 1 THEN 'TRX_NEGADO,'
                    ELSE ''
                END AS CLUSTER,
                CASE 
                   WHEN K.QTD > 1 THEN 'APTO'
                   ELSE 'NÃO APTO'
                  END AS PARM,
                 CASE
                   WHEN VWATUAL.codAtual IS NOT NULL THEN 'APTO'
                   WHEN VWANTIGA.CodAnt IS NOT NULL THEN 'NÃO APTO'
                   ELSE NULL
                  END AS TRAG,
                G.ORGAO_PAGADOR ORGAO_PAGADOR,
                CONVERT(VARCHAR, G.DATA_LAST_TRANS, 103) DATA_LAST_TRANS 
            FROM 
                TB_ACIONAMENTO_FIN_SOLICITACOES A WITH (NOLOCK) 
                JOIN TB_ACIONAMENTO_SERVICOS B WITH (NOLOCK) ON A.COD_TIPO_SERVICO = B.COD_TIPO_SERVICO 
                JOIN TB_ACIONAMENTO_PRESTACAO_CONTA C WITH (NOLOCK) ON A.COD_PRESTACAO_CONTA = C.COD_PRESTACAO_CONTA 
                JOIN TB_ACIONAMENTO_STATUS D WITH (NOLOCK) ON A.COD_STATUS = D.COD_STATUS 
                LEFT JOIN RH..TB_FUNCIONARIOS E WITH (NOLOCK) ON A.COD_SOLICITANTE = E.COD_FUNC
                LEFT JOIN DATALAKE..DL_BRADESCO_EXPRESSO F WITH (NOLOCK) ON A.CHAVE_LOJA = F.CHAVE_LOJA 
                JOIN TB_ACIONAMENTO_FIN_SOLICITACOES_DADOS G WITH (NOLOCK) ON A.COD_SOLICITACAO = G.COD_SOLICITACAO  
                LEFT JOIN TB_ACIONAMENTO_RESPOSTAS H WITH (NOLOCK) ON G.COD_SOLUCAO = H.COD_RESPOSTA 
                LEFT JOIN (
                    SELECT 
                        A.COD_CHAT, A.COD_SOLICITACAO, A.MSG, A.COD_FUNC, A.MSG_ANEXO 
                    FROM 
                        TB_ACIONAMENTO_CHAT A WITH (NOLOCK) JOIN (
                            SELECT MAX(COD_CHAT) COD_CHAT, COD_SOLICITACAO FROM TB_ACIONAMENTO_CHAT WITH (NOLOCK) WHERE SOLUCAO = 0 AND ENCAMINHADO = 0 AND COD_SISTEMA = 1 GROUP BY COD_SOLICITACAO 
                        ) B ON A.COD_CHAT = B.COD_CHAT 
                ) I ON A.COD_SOLICITACAO = I.COD_SOLICITACAO 
                LEFT JOIN TB_ACIONAMENTO_CHAT_READ J WITH (NOLOCK) ON I.COD_CHAT = J.COD_CHAT AND A.COD_SOLICITANTE = J.COD_FUNC
                LEFT JOIN (
                   SELECT 
                      COUNT(*) AS QTD, 
                      COD_AG_LOJA
                   FROM 
                      DATALAKE..DL_BRADESCO_EXPRESSO DL WITH (NOLOCK)
                   WHERE 
                      DL.BE_INAUGURADO = 1  
                      AND DL.CATEGORIA NOT IN ('PROCESSO DE ENCERRAMENTO', 'ENCERRADO') 
                   GROUP BY 
                      COD_AG_LOJA
                ) K 
                  ON F.COD_AG_LOJA = K.COD_AG_LOJA
                 AND K.COD_AG_LOJA = A.COD_AG
                 LEFT JOIN MESU..VW_TRAGS AS VWATUAL WITH (NOLOCK)
                  ON VWATUAL.CodAtual = A.COD_AG
                    AND VWATUAL.NRAtual = G.NR_PACB
                 LEFT JOIN MESU..VW_TRAGS AS VWANTIGA WITH (NOLOCK)
                  ON VWANTIGA.CodAnt = A.COD_AG
                    AND VWANTIGA.NRAnt = G.NR_PACB
            WHERE
                1=1 AND F.BE_INAUGURADO = 1
                ".$where."
            ORDER BY A.COD_SOLICITACAO DESC
            OFFSET ".$offset." ROWS
            FETCH NEXT ".$limit." ROWS ONLY"; 
        
        $dados = $this->sql->select($query);
        return $dados;
    }
    
    // ... (keep all other existing methods)
}
?>

-------

<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class AnaliseEncerramentoController {
    private $model;
    private $dados;
    private $totalRecords;
    private $totalPages;
    private $currentPage;
    private $recordsPerPage;
    
    public function __construct() {
        $this->model = new Analise();
        
        // Get pagination parameters
        $this->currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $this->recordsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 50;
        
        // Validate records per page (only allow 50, 100, 200)
        if (!in_array($this->recordsPerPage, [50, 100, 200])) {
            $this->recordsPerPage = 50;
        }
        
        $this->loadData();
    }
    
    private function loadData() {
        // Base WHERE clause
        $where = "AND A.COD_TIPO_SERVICO=1";
        
        // Add search filter if exists
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
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
        
        // Add bloqueio filter
        if (isset($_GET['bloqueio']) && $_GET['bloqueio'] !== '') {
            if ($_GET['bloqueio'] === 'bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NOT NULL";
            } else if ($_GET['bloqueio'] === 'nao_bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NULL";
            }
        }
        
        // Add orgao pagador filter
        if (isset($_GET['orgao_pagador']) && !empty($_GET['orgao_pagador'])) {
            $orgao = $_GET['orgao_pagador'];
            $where .= " AND G.ORGAO_PAGADOR LIKE '%$orgao%'";
        }
        
        // Add data range filter
        if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
            $where .= " AND A.DATA_CAD >= '$_GET[data_inicio]'";
        }
        
        if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
            $where .= " AND A.DATA_CAD <= '$_GET[data_fim]'";
        }
        
        // Get total count
        $this->totalRecords = $this->model->getTotalCount($where);
        
        // Calculate pagination
        $this->totalPages = ceil($this->totalRecords / $this->recordsPerPage);
        $offset = ($this->currentPage - 1) * $this->recordsPerPage;
        
        // Get paginated data
        $this->dados = $this->model->solicitacoes($where, $this->recordsPerPage, $offset);
    }
    
    public function getDados() {
        return $this->dados;
    }
    
    public function getTotalRecords() {
        return $this->totalRecords;
    }
    
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    public function getRecordsPerPage() {
        return $this->recordsPerPage;
    }
    
    public function getStartRecord() {
        return ($this->currentPage - 1) * $this->recordsPerPage + 1;
    }
    
    public function getEndRecord() {
        $end = $this->currentPage * $this->recordsPerPage;
        return min($end, $this->totalRecords);
    }
    
    public function renderTableRows($dados) {
        $html = '';
        $length = is_array($dados) ? count($dados) : 0;
        
        if ($length > 0) {
            for ($i = 0; $i < $length; $i++) {
                $html .= '<tr>';
                $html .= '<th><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '</span></th>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
                
                // Data Retirada
                if (!is_null($dados[$i]['DATA_RETIRADA_EQPTO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RETIRADA_EQPTO']->format('d/m/Y') . '</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Data</span></td>';
                }
                
                // Bloqueio
                if (!is_null($dados[$i]['DATA_BLOQUEIO'])) {
                    $html .= '<td><span class="text-green" style="display: block; text-align: center;">Bloqueado</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['DATA_LAST_TRANS']) . '</span></td>';
                
                // Motivo Bloqueio
                if (!is_null($dados[$i]['MOTIVO_BLOQUEIO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['MOTIVO_BLOQUEIO']) .'</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Bloqueio</span></td>';
                }
                
                // Motivo Encerramento
                if (!is_null($dados[$i]['DESC_MOTIVO_ENCERRAMENTO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['DESC_MOTIVO_ENCERRAMENTO']) .'</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Encerramento</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CLUSTER']) . '</span></td>';
                
                // PARM
                if (!is_null($dados[$i]['PARM'])) {
                    if($dados[$i]['PARM'] == 'NÃO APTO'){
                        $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                    } else {
                        $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['PARM']) .'</span></td>';
                    }
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                }
                
                // TRAG
                if (!is_null($dados[$i]['TRAG'])) {
                    if($dados[$i]['TRAG'] == 'NÃO APTO'){
                        $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                    } else {
                        $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['TRAG']) .'</span></td>';
                    }
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                }
                
                $html .= '<td></td><td></td><td></td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="16" class="text-center">Nenhum registro encontrado</td></tr>';
        }
        
        return $html;
    }
}

// Initialize controller for initial page load
$controller = new AnaliseEncerramentoController();
$dados = $controller->getDados();
$totalRecords = $controller->getTotalRecords();
$totalPages = $controller->getTotalPages();
$currentPage = $controller->getCurrentPage();
$recordsPerPage = $controller->getRecordsPerPage();
$startRecord = $controller->getStartRecord();
$endRecord = $controller->getEndRecord();
?>

--------

<?php
/**
 * ajax_encerramento.php
 * Handles AJAX requests for the encerramento analysis table with pagination
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class AjaxEncerramentoHandler {
    private $model;
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    public function loadData($filters = []) {
        // Base WHERE clause
        $where = "AND A.COD_TIPO_SERVICO=1";
        
        // Add search filter
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
        
        // Add bloqueio filter
        if (isset($filters['bloqueio']) && $filters['bloqueio'] !== '') {
            if ($filters['bloqueio'] === 'bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NOT NULL";
            } else if ($filters['bloqueio'] === 'nao_bloqueado') {
                $where .= " AND F.DATA_BLOQUEIO IS NULL";
            }
        }
        
        // Add orgao pagador filter
        if (isset($filters['orgao_pagador']) && !empty($filters['orgao_pagador'])) {
            $orgao = $filters['orgao_pagador'];
            $where .= " AND G.ORGAO_PAGADOR LIKE '%$orgao%'";
        }
        
        // Add data range filter
        if (isset($filters['data_inicio']) && !empty($filters['data_inicio'])) {
            $where .= " AND A.DATA_CAD >= '$filters[data_inicio]'";
        }
        
        if (isset($filters['data_fim']) && !empty($filters['data_fim'])) {
            $where .= " AND A.DATA_CAD <= '$filters[data_fim]'";
        }
        
        // Get pagination parameters
        $page = isset($filters['page']) ? max(1, intval($filters['page'])) : 1;
        $perPage = isset($filters['per_page']) ? intval($filters['per_page']) : 50;
        
        // Validate per_page (only allow 50, 100, 200)
        if (!in_array($perPage, [50, 100, 200])) {
            $perPage = 50;
        }
        
        // Get total count
        $totalRecords = $this->model->getTotalCount($where);
        
        // Calculate pagination
        $totalPages = ceil($totalRecords / $perPage);
        $offset = ($page - 1) * $perPage;
        
        // Get paginated data
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
        $html = '';
        $length = is_array($dados) ? count($dados) : 0;
        
        if ($length > 0) {
            for ($i = 0; $i < $length; $i++) {
                $html .= '<tr>';
                $html .= '<th><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_SOLICITACAO']) . '</span></th>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['COD_AG']) . htmlspecialchars($dados[$i]['NR_PACB']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CHAVE_LOJA']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RECEPCAO']->format('d/m/Y') . '</span></td>';
                
                // Data Retirada
                if (!is_null($dados[$i]['DATA_RETIRADA_EQPTO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">' . $dados[$i]['DATA_RETIRADA_EQPTO']->format('d/m/Y') . '</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Data</span></td>';
                }
                
                // Bloqueio
                if (!is_null($dados[$i]['DATA_BLOQUEIO'])) {
                    $html .= '<td><span class="text-green" style="display: block; text-align: center;">Bloqueado</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Não Bloqueado</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['DATA_LAST_TRANS']) . '</span></td>';
                
                // Motivo Bloqueio
                if (!is_null($dados[$i]['MOTIVO_BLOQUEIO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['MOTIVO_BLOQUEIO']) .'</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Bloqueio</span></td>';
                }
                
                // Motivo Encerramento
                if (!is_null($dados[$i]['DESC_MOTIVO_ENCERRAMENTO'])) {
                    $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['DESC_MOTIVO_ENCERRAMENTO']) .'</span></td>';
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">Sem Motivo de Encerramento</span></td>';
                }
                
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['ORGAO_PAGADOR']) . '</span></td>';
                $html .= '<td><span style="display: block; text-align: center;">' . htmlspecialchars($dados[$i]['CLUSTER']) . '</span></td>';
                
                // PARM
                if (!is_null($dados[$i]['PARM'])) {
                    if($dados[$i]['PARM'] == 'NÃO APTO'){
                        $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                    } else {
                        $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['PARM']) .'</span></td>';
                    }
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                }
                
                // TRAG
                if (!is_null($dados[$i]['TRAG'])) {
                    if($dados[$i]['TRAG'] == 'NÃO APTO'){
                        $html .='<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                    } else {
                        $html .= '<td><span style="display: block; text-align: center;">'. htmlspecialchars($dados[$i]['TRAG']) .'</span></td>';
                    }
                } else {
                    $html .= '<td><span class="text-red" style="display: block; text-align: center;">NÃO APTO</span></td>';
                }
                
                $html .= '<td></td><td></td><td></td>';
                $html .= '</tr>';
            }
        } else {
            $html .= '<tr><td colspan="16" class="text-center">Nenhum registro encontrado</td></tr>';
        }
        
        return $html;
    }
}

try {
    $handler = new AjaxEncerramentoHandler();
    $result = $handler->loadData($_GET);
    
    echo json_encode([
        'success' => true,
        'html' => $handler->renderTableRows($result['dados']),
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

------

<?php
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\control\encerramento\analise_encerramento_control.php';
?>

<head>
    <style>
        :root {
            --thead-encerramento: #d8d8d8;
            --thead-text-encerramento: #262626
        }

        :root[data-theme="light"] {
            --thead-encerramento: #ac193d;
            --thead-text-encerramento: #ffffffff
        }

        :root[data-theme="dark"] {
            --thead-encerramento: #d8d8d8;
            --thead-text-encerramento: #262626
        }

        .thead-encerramento {
            background: var(--thead-encerramento) !important;
            font-size: .75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .04em;
            line-height: 1rem;
            color: var(--thead-text-encerramento) !important;
            padding-top: .5rem;
            padding-bottom: .5rem;
            white-space: nowrap;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .loading-overlay.active {
            display: flex;
        }
        
        [data-theme="dark"] .loading-overlay {
            background: rgba(0, 0, 0, 0.8);
        }
        
        .date-input-wrapper {
          position: relative;
        }

        .date-input-wrapper input[type="date"] {
          position: absolute;
          inset: 0;              
          width: 100%;
          height: 100%;
          opacity: 0;            
          cursor: pointer;
          z-index: 2;            
        }

        .date-input-wrapper input[type="text"] {
          position: relative;
          z-index: 1;
          pointer-events: none;  
        }
        
        .pagination .page-link {
            cursor: pointer;
        }
        
        .pagination .page-item.disabled .page-link {
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <!-- Filter and Search Section -->
    <div class="card mb-3" style="width:100rem; right:100px;">
        <div class="card-header">
            <h3 class="card-title">Filtros e Busca</h3>
        </div>
        <div class="card-body">
            <form id="filterForm" onsubmit="return false;">
                <div class="row g-3">
                    <!-- Search Bar -->
                    <div class="col-md-12">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput" name="search" 
                                   placeholder="Buscar por Solicitação, Agência, Chave Loja, PACB, Motivo..." 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-primary" type="button" id="searchBtn">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                                Buscar
                            </button>
                        </div>
                    </div>
                    
                    <!-- Bloqueio Filter -->
                    <div class="col-md-3">
                        <label class="form-label">Status Bloqueio</label>
                        <select class="form-select" name="bloqueio" id="bloqueioFilter">
                            <option value="">Todos</option>
                            <option value="bloqueado" <?php echo (isset($_GET['bloqueio']) && $_GET['bloqueio'] === 'bloqueado') ? 'selected' : ''; ?>>Bloqueado</option>
                            <option value="nao_bloqueado" <?php echo (isset($_GET['bloqueio']) && $_GET['bloqueio'] === 'nao_bloqueado') ? 'selected' : ''; ?>>Não Bloqueado</option>
                        </select>
                    </div>
                    
                    <!-- Orgao Pagador Filter -->
                    <div class="col-md-3">
                      <label class="form-label">Órgão Pagador</label>
                      <select class="form-control" name="orgao_pagador" id="orgaoPagadorFilter">
                          <option value="">Filtrar por órgão</option>
                          <option value="Sim" <?php echo (isset($_GET['orgao_pagador']) && $_GET['orgao_pagador'] === 'Sim') ? 'selected' : ''; ?>>Sim</option>
                          <option value="Nao" <?php echo (isset($_GET['orgao_pagador']) && $_GET['orgao_pagador'] === 'Nao') ? 'selected' : ''; ?>>Não</option>
                      </select>
                  </div>
                    
                    <!-- Data Inicio -->
                    <div class="col-md-3">
                      <label class="form-label" for="dataInicioFilter">Data Início</label>
                      <div class="date-input-wrapper">
                        <input type="text" class="form-control" id="dataInicioDisplay"
                              placeholder="dd/mm/yyyy" readonly
                              value="<?php 
                                if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
                                  $date = $_GET['data_inicio'];
                                  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                                      $parts = explode('-', $date);
                                      echo $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                                  } else {
                                      echo htmlspecialchars($date);
                                  }
                                }
                              ?>">
                        <input type="date" id="dataInicioFilter" name="data_inicio"
                              value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                      </div>
                    </div>

                    <div class="col-md-3">
                      <label class="form-label" for="dataFimFilter">Data Fim</label>
                      <div class="date-input-wrapper">
                        <input type="text" class="form-control" id="dataFimDisplay"
                              placeholder="dd/mm/yyyy" readonly
                              value="<?php 
                                if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
                                  $date = $_GET['data_fim'];
                                  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                                      $parts = explode('-', $date);
                                      echo $parts[2] . '/' . $parts[1] . '/' . $parts[0];
                                  } else {
                                      echo htmlspecialchars($date);
                                  }
                                }
                              ?>">
                        <input type="date" id="dataFimFilter" name="data_fim"
                              value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                      </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="applyFiltersBtn">
                            Aplicar Filtros
                        </button>
                        <button type="button" class="btn btn-secondary" id="clearFilters">
                            Limpar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DataTable Section -->
    <div class="fs-base border rounded my-5 justify-content-center overflow-auto position-relative" style="height: 50rem; width:100rem; right:100px;" id="tableContainer">
        <!-- Loading Overlay -->
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Carregando...</span>
            </div>
        </div>
        
        <table class="table table-vcenter table-nowrap" id="dataTable">
            <thead class="sticky-top">
                <tr>
                    <th class="thead-encerramento" style="text-align: center;">Solicitação</th>  
                    <th class="thead-encerramento" style="text-align: center;">Agência/PACB</th>
                    <th class="thead-encerramento" style="text-align: center;">Chave Loja</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Recepção</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Retirada Equip.</th>
                    <th class="thead-encerramento" style="text-align: center;">Bloqueio</th>
                    <th class="thead-encerramento" style="text-align: center;">Data Última Tran.</th>
                    <th class="thead-encerramento" style="text-align: center;">Motivo Bloqueio</th>
                    <th class="thead-encerramento" style="text-align: center;">Motivo Encerramento</th>
                    <th class="thead-encerramento" style="text-align: center;">Órgão Pagador</th>
                    <th class="thead-encerramento" style="text-align: center;">Cluster</th>
                    <th class="thead-encerramento" style="text-align: center;">PARM</th>
                    <th class="thead-encerramento" style="text-align: center;">TRAG</th>
                    <th class="thead-encerramento" style="text-align: center;">Média Tran. Contábeis</th>
                    <th class="thead-encerramento" style="text-align: center;">Média Tran. Negócio</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php echo $controller->renderTableRows($dados); ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div class="card-footer" style="position:relative;bottom: 20px;">
        <div class="row g-2 justify-content-center justify-content-sm-between align-items-center">
            <div class="col-auto d-flex align-items-center">
                <p class="m-0 text-secondary">
                    Mostrando <strong><span id="startRecord"><?php echo $startRecord; ?></span> a <span id="endRecord"><?php echo $endRecord; ?></span></strong> 
                    de <strong id="totalRecords"><?php echo $totalRecords; ?></strong> registros
                </p>
            </div>
            
            <!-- Records Per Page Selector -->
            <div class="col-auto">
                <div class="d-flex align-items-center">
                    <label class="me-2 mb-0 text-secondary">Exibir:</label>
                    <select class="form-select form-select-sm" id="perPageSelect" style="width: auto;">
                        <option value="50" <?php echo ($recordsPerPage == 50) ? 'selected' : ''; ?>>50</option>
                        <option value="100" <?php echo ($recordsPerPage == 100) ? 'selected' : ''; ?>>100</option>
                        <option value="200" <?php echo ($recordsPerPage == 200) ? 'selected' : ''; ?>>200</option>
                    </select>
                </div>
            </div>
            
            <!-- Pagination Controls -->
            <div class="col-auto">
                <ul class="pagination m-0 ms-auto" id="paginationControls">
                    <!-- Previous Button -->
                    <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>" id="prevPage">
                        <a class="page-link" href="#" tabindex="-1" aria-disabled="<?php echo ($currentPage <= 1) ? 'true' : 'false'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M15 6l-6 6l6 6"></path>
                            </svg>
                        </a>
                    </li>
                    
                    <!-- Page Numbers -->
                    <?php
                    // Generate page numbers
                    $maxPagesToShow = 5;
                    $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
                    $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
                    
                    if ($endPage - $startPage < $maxPagesToShow - 1) {
                        $startPage = max(1, $endPage - $maxPagesToShow + 1);
                    }
                    
                    if ($startPage > 1) {
                        echo '<li class="page-item"><a class="page-link page-number" data-page="1" href="#">1</a></li>';
                        if ($startPage > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $active = ($i == $currentPage) ? 'active' : '';
                        echo '<li class="page-item '.$active.'"><a class="page-link page-number" data-page="'.$i.'" href="#">'.$i.'</a></li>';
                    }
                    
                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link page-number" data-page="'.$totalPages.'" href="#">'.$totalPages.'</a></li>';
                    }
                    ?>
                    
                    <!-- Next Button -->
                    <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>" id="nextPage">
                        <a class="page-link" href="#" aria-disabled="<?php echo ($currentPage >= $totalPages) ? 'true' : 'false'; ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path d="M9 6l6 6l-6 6"></path>
                            </svg>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Pagination state
        let currentPage = <?php echo $currentPage; ?>;
        let totalPages = <?php echo $totalPages; ?>;
        let perPage = <?php echo $recordsPerPage; ?>;
    </script>



----


// Add this script section to your view file after the pagination state variables

(function() {
    'use strict';

    const filterForm = document.getElementById('filterForm');
    const clearFiltersBtn = document.getElementById('clearFilters');
    const searchBtn = document.getElementById('searchBtn');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');
    const searchInput = document.getElementById('searchInput');
    const bloqueioFilter = document.getElementById('bloqueioFilter');
    const orgaoPagadorFilter = document.getElementById('orgaoPagadorFilter');
    const dataInicioFilter = document.getElementById('dataInicioFilter');
    const dataInicioDisplay = document.getElementById('dataInicioDisplay');
    const dataFimFilter = document.getElementById('dataFimFilter');
    const dataFimDisplay = document.getElementById('dataFimDisplay');
    const tableBody = document.getElementById('tableBody');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const totalRecordsEl = document.getElementById('totalRecords');
    const startRecordEl = document.getElementById('startRecord');
    const endRecordEl = document.getElementById('endRecord');
    const perPageSelect = document.getElementById('perPageSelect');
    const paginationControls = document.getElementById('paginationControls');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    
    const AJAX_URL = '/teste/Andre/tabler_portalexpresso_paginaEncerramento/control/encerramento/roteamento/ajax_encerramento.php';

    // Setup date inputs
    function setupDateInput(dateInput, displayInput) {
        if (!dateInput || !displayInput) return;
        
        dateInput.addEventListener('change', function() {
            if (this.value) {
                const parts = this.value.split('-');
                displayInput.value = parts[2] + '/' + parts[1] + '/' + parts[0];
            } else {
                displayInput.value = '';
            }
        });
    }
    
    setupDateInput(dataInicioFilter, dataInicioDisplay);
    setupDateInput(dataFimFilter, dataFimDisplay);

    // Initialize event listeners
    initializeEventListeners();
    highlightActiveFilters();

    function initializeEventListeners() {
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                clearAllFilters();
            });
        }

        if (searchBtn) {
            searchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = 1; // Reset to page 1 on new search
                handleFormSubmit();
            });
        }
        
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function(e) {
                e.preventDefault();
                currentPage = 1; // Reset to page 1 on new filter
                handleFormSubmit();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    currentPage = 1; // Reset to page 1
                    handleFormSubmit();
                }
            });
        }
        
        // Per page selector
        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                perPage = parseInt(this.value);
                currentPage = 1; // Reset to page 1 when changing per page
                handleFormSubmit();
            });
        }
        
        // Previous page button
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    handleFormSubmit();
                }
            });
        }
        
        // Next page button
        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    handleFormSubmit();
                }
            });
        }
    }

    function handleFormSubmit() {
        if (!validateDateRange()) {
            return;
        }

        const formData = new FormData(filterForm);
        const params = new URLSearchParams();
        
        // Add form data
        for (let [key, value] of formData.entries()) {
            if (value && value.trim() !== '') {
                params.append(key, value);
            }
        }
        
        // Add pagination parameters
        params.append('page', currentPage);
        params.append('per_page', perPage);
        
        showLoading();
        
        fetch(AJAX_URL + '?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update table content
                    tableBody.innerHTML = data.html;
                    
                    // Update pagination info
                    totalRecordsEl.textContent = data.totalRecords;
                    startRecordEl.textContent = data.startRecord;
                    endRecordEl.textContent = data.endRecord;
                    currentPage = data.currentPage;
                    totalPages = data.totalPages;
                    perPage = data.perPage;
                    
                    // Update pagination controls
                    updatePaginationControls();
                    
                    // Update URL without reloading
                    const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                    window.history.pushState({}, '', newUrl);
                    
                    // Update filter highlights
                    highlightActiveFilters();
                    
                    // Scroll to table
                    document.getElementById('tableContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    throw new Error(data.error || 'Erro ao carregar dados');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao carregar os dados: ' + error.message);
            })
            .finally(() => {
                hideLoading();
            });
    }

    function clearAllFilters() {
        // Clear all input values
        if (searchInput) searchInput.value = '';
        if (bloqueioFilter) bloqueioFilter.value = '';
        if (orgaoPagadorFilter) orgaoPagadorFilter.value = '';
        if (dataInicioFilter) dataInicioFilter.value = '';
        if (dataInicioDisplay) dataInicioDisplay.value = '';
        if (dataFimFilter) dataFimFilter.value = '';
        if (dataFimDisplay) dataFimDisplay.value = '';
        
        // Reset pagination
        currentPage = 1;

        showLoading();
        
        const params = new URLSearchParams();
        params.append('page', currentPage);
        params.append('per_page', perPage);
        
        fetch(AJAX_URL + '?' + params.toString())
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    tableBody.innerHTML = data.html;
                    totalRecordsEl.textContent = data.totalRecords;
                    startRecordEl.textContent = data.startRecord;
                    endRecordEl.textContent = data.endRecord;
                    currentPage = data.currentPage;
                    totalPages = data.totalPages;
                    
                    updatePaginationControls();
                    window.history.pushState({}, '', window.location.pathname);
                    highlightActiveFilters();
                } else {
                    throw new Error(data.error || 'Erro ao limpar filtros');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao limpar os filtros: ' + error.message);
            })
            .finally(() => {
                hideLoading();
            });
    }

    function updatePaginationControls() {
        // Update prev/next button states
        if (prevPageBtn) {
            if (currentPage <= 1) {
                prevPageBtn.classList.add('disabled');
                prevPageBtn.querySelector('a').setAttribute('aria-disabled', 'true');
            } else {
                prevPageBtn.classList.remove('disabled');
                prevPageBtn.querySelector('a').setAttribute('aria-disabled', 'false');
            }
        }
        
        if (nextPageBtn) {
            if (currentPage >= totalPages) {
                nextPageBtn.classList.add('disabled');
                nextPageBtn.querySelector('a').setAttribute('aria-disabled', 'true');
            } else {
                nextPageBtn.classList.remove('disabled');
                nextPageBtn.querySelector('a').setAttribute('aria-disabled', 'false');
            }
        }
        
        // Rebuild page numbers
        rebuildPageNumbers();
    }

    function rebuildPageNumbers() {
        // Remove existing page number items
        const existingNumbers = paginationControls.querySelectorAll('.page-number');
        existingNumbers.forEach(item => item.parentElement.remove());
        
        const ellipsisItems = paginationControls.querySelectorAll('.page-item:not(#prevPage):not(#nextPage)');
        ellipsisItems.forEach(item => {
            if (item.querySelector('.page-link')?.textContent === '...') {
                item.remove();
            }
        });
        
        // Calculate page range
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
        
        if (endPage - startPage < maxPagesToShow - 1) {
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        const fragment = document.createDocumentFragment();
        
        // First page + ellipsis
        if (startPage > 1) {
            fragment.appendChild(createPageItem(1, false));
            if (startPage > 2) {
                fragment.appendChild(createEllipsisItem());
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            fragment.appendChild(createPageItem(i, i === currentPage));
        }
        
        // Ellipsis + last page
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                fragment.appendChild(createEllipsisItem());
            }
            fragment.appendChild(createPageItem(totalPages, false));
        }
        
        // Insert before next button
        nextPageBtn.parentNode.insertBefore(fragment, nextPageBtn);
        
        // Add click handlers to new page numbers
        const newPageNumbers = paginationControls.querySelectorAll('.page-number');
        newPageNumbers.forEach(link => {
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
        
        // Reset all borders first
        [searchInput, bloqueioFilter, orgaoPagadorFilter, dataInicioDisplay, dataFimDisplay].forEach(filter => {
            if (filter) {
                filter.style.borderColor = '';
                filter.style.borderWidth = '';
            }
        });
        
        // Highlight active filters
        if (searchInput && urlParams.has('search')) {
            searchInput.style.borderColor = '#206bc4';
            searchInput.style.borderWidth = '2px';
        }

        if (bloqueioFilter && bloqueioFilter.value) {
            bloqueioFilter.style.borderColor = '#206bc4';
            bloqueioFilter.style.borderWidth = '2px';
        }
        
        if (orgaoPagadorFilter && orgaoPagadorFilter.value) {
            orgaoPagadorFilter.style.borderColor = '#206bc4';
            orgaoPagadorFilter.style.borderWidth = '2px';
        }
        
        if (dataInicioDisplay && dataInicioFilter && dataInicioFilter.value) {
            dataInicioDisplay.style.borderColor = '#206bc4';
            dataInicioDisplay.style.borderWidth = '2px';
        }
        
        if (dataFimDisplay && dataFimFilter && dataFimFilter.value) {
            dataFimDisplay.style.borderColor = '#206bc4';
            dataFimDisplay.style.borderWidth = '2px';
        }

        // Update clear button state
        if (clearFiltersBtn) {
            const hasActiveFilters = urlParams.has('search') || 
                                    urlParams.has('bloqueio') || 
                                    urlParams.has('orgao_pagador') || 
                                    urlParams.has('data_inicio') || 
                                    urlParams.has('data_fim');
            
            if (hasActiveFilters) {
                clearFiltersBtn.classList.add('btn-warning');
                clearFiltersBtn.classList.remove('btn-secondary');
            } else {
                clearFiltersBtn.classList.add('btn-secondary');
                clearFiltersBtn.classList.remove('btn-warning');
            }
        }
    }

    function validateDateRange() {
        const dataInicio = dataInicioFilter ? dataInicioFilter.value : null;
        const dataFim = dataFimFilter ? dataFimFilter.value : null;

        if (dataInicio && dataFim) {
            const inicio = new Date(dataInicio);
            const fim = new Date(dataFim);

            if (inicio > fim) {
                alert('A data de início não pode ser maior que a data fim.');
                return false;
            }
        }
        return true;
    }
    
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }
    }
    
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }
    }

})();

// Date picker scripts for data inicio
(function () {
    const dateEl = document.getElementById('dataInicioFilter');
    const displayEl = document.getElementById('dataInicioDisplay');
    const wrapper = dateEl?.closest('.date-input-wrapper');

    if (!dateEl || !displayEl || !wrapper) return;

    function toDisplay(iso) {
        if (!iso) return '';
        const [y, m, d] = iso.split('-');
        return `${d}/${m}/${y}`;
    }

    displayEl.value = toDisplay(dateEl.value);

    function openPickerFromGesture(e) {
        if (e.type === 'pointerdown' && e.button !== 0) return;
        e.preventDefault();

        if (typeof dateEl.showPicker === 'function') {
            try {
                dateEl.showPicker();
            } catch (err) {
                dateEl.focus({ preventScroll: true });
            }
        } else {
            dateEl.focus({ preventScroll: true });
        }
    }

    wrapper.addEventListener('pointerdown', openPickerFromGesture);
    wrapper.addEventListener('click', openPickerFromGesture);

    dateEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            if (typeof dateEl.showPicker === 'function') {
                try { dateEl.showPicker(); } catch { /* ignore */ }
            }
        }
    });

    dateEl.addEventListener('change', function () {
        displayEl.value = toDisplay(dateEl.value);
    });
})();

// Date picker scripts for data fim
(function () {
    const dateEl = document.getElementById('dataFimFilter');
    const displayEl = document.getElementById('dataFimDisplay');
    const wrapper = dateEl?.closest('.date-input-wrapper');

    if (!dateEl || !displayEl || !wrapper) return;

    function toDisplay(iso) {
        if (!iso) return '';
        const [y, m, d] = iso.split('-');
        return `${d}/${m}/${y}`;
    }

    displayEl.value = toDisplay(dateEl.value);

    function openPickerFromGesture(e) {
        if (e.type === 'pointerdown' && e.button !== 0) return;
        e.preventDefault();

        if (typeof dateEl.showPicker === 'function') {
            try {
                dateEl.showPicker();
            } catch (err) {
                dateEl.focus({ preventScroll: true });
            }
        } else {
            dateEl.focus({ preventScroll: true });
        }
    }

    wrapper.addEventListener('pointerdown', openPickerFromGesture);
    wrapper.addEventListener('click', openPickerFromGesture);

    dateEl.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
            e.preventDefault();
            if (typeof dateEl.showPicker === 'function') {
                try { dateEl.showPicker(); } catch { /* ignore */ }
            }
        }
    });

    dateEl.addEventListener('change', function () {
        displayEl.value = toDisplay(dateEl.value);
    });
})();

// Theme handler
(function () {
    const params = new URLSearchParams(window.location.search);
    const rawTheme = (params.get("theme") || "").trim().toLowerCase();
    const allowed = new Set(["light", "dark"]);

    const storedTheme = localStorage.getItem("theme");
    const chosen = allowed.has(rawTheme)
        ? rawTheme
        : (allowed.has(storedTheme) ? storedTheme : "light");

    document.documentElement.setAttribute("data-theme", chosen);
    localStorage.setItem("theme", chosen);
})();