<?php
@session_start();
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948'; // Código Bradesco
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    // Generate TXT from selected checkboxes
    public function generateFromSelection($solicitacoes) {
        if (empty($solicitacoes) || !is_array($solicitacoes)) {
            return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
        }
        
        $dados = $this->getDadosFromSolicitacoes($solicitacoes);
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        return $this->generateTXT($dados);
    }
    
    // Generate TXT from Excel import
    public function generateFromExcel($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\PhpSpreadsheet\vendor\autoload.php';
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $chaveLojas = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            
            if (empty($chaveLojas)) {
                return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
            }
            
            $dados = $this->getDadosFromChaveLojas($chaveLojas);
            return $this->generateTXT($dados);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao processar Excel: ' . $e->getMessage()];
        }
    }
    
    private function getDadosFromSolicitacoes($solicitacoes) {
        $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
        return $this->model->solicitacoes($where, 9999, 0);
    }
    
    private function getDadosFromChaveLojas($chaveLojas) {
        $where = "AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
        return $this->model->solicitacoes($where, 9999, 0);
    }
    
    private function generateTXT($dados) {
        $linhas = [];
        
        // Header (Leiaute 1)
        $linhas[] = $this->gerarHeader(count($dados));
        
        // Details (Leiaute 2)
        $sequencial = 1;
        foreach ($dados as $row) {
            $linhas[] = $this->gerarDetalhe($row, $sequencial);
            $sequencial++;
        }
        
        // Trailer (Leiaute 9)
        $linhas[] = $this->gerarTrailer(count($dados));
        
        $conteudo = implode("\r\n", $linhas);
        $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
        
        return [
            'success' => true,
            'conteudo' => $conteudo,
            'nomeArquivo' => $nomeArquivo,
            'totalRegistros' => count($dados)
        ];
    }
    
    private function gerarHeader($totalRegistros) {
        $tipo = '0';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $tipoDocumento = '02'; // Encerramento
        $dataGeracao = date('Ymd');
        $horaGeracao = date('His');
        $sequencial = str_pad('1', 6, '0', STR_PAD_LEFT);
        $versaoLayout = '001';
        
        $linha = $tipo;
        $linha .= $instituicao;
        $linha .= $tipoDocumento;
        $linha .= $dataGeracao;
        $linha .= $horaGeracao;
        $linha .= $sequencial;
        $linha .= $versaoLayout;
        $linha .= str_repeat(' ', 250 - strlen($linha)); // Preencher até 250 caracteres
        
        return substr($linha, 0, 250);
    }
    
    private function gerarDetalhe($row, $sequencial) {
        $tipo = '2';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $agencia = str_pad($row['COD_AG'], 4, '0', STR_PAD_LEFT);
        $chaveLoja = str_pad($row['CHAVE_LOJA'], 10, '0', STR_PAD_LEFT);
        $cnpj = str_pad(preg_replace('/\D/', '', $row['CNPJ']), 14, '0', STR_PAD_LEFT);
        $razaoSocial = str_pad(substr($row['NOME_LOJA'], 0, 60), 60, ' ', STR_PAD_RIGHT);
        $dataEncerramento = date('Ymd'); // Data atual
        $motivoEncerramento = $this->getMotivoCodigoBacen($row['MOTIVO_ENC']);
        $sequencialStr = str_pad($sequencial, 6, '0', STR_PAD_LEFT);
        
        $linha = $tipo;
        $linha .= $instituicao;
        $linha .= $agencia;
        $linha .= $chaveLoja;
        $linha .= $cnpj;
        $linha .= $razaoSocial;
        $linha .= $dataEncerramento;
        $linha .= $motivoEncerramento;
        $linha .= $sequencialStr;
        $linha .= str_repeat(' ', 250 - strlen($linha));
        
        return substr($linha, 0, 250);
    }
    
    private function gerarTrailer($totalRegistros) {
        $tipo = '9';
        $quantidadeRegistros = str_pad($totalRegistros, 10, '0', STR_PAD_LEFT);
        
        $linha = $tipo;
        $linha .= $quantidadeRegistros;
        $linha .= str_repeat(' ', 250 - strlen($linha));
        
        return substr($linha, 0, 250);
    }
    
    private function getMotivoCodigoBacen($motivoInterno) {
        // Mapear códigos internos para códigos BACEN
        $mapeamento = [
            '56' => '01', // Baixa Performance
            '61' => '01', // Abaixo ponto equilíbrio
            '34' => '02', // Apropriação indébita
            '37' => '02', // Cobrança indevida
            '39' => '02', // Suspeita fraude
            '55' => '03', // Desvio finalidade
            '62' => '04', // Falta prestação contas
            '32' => '05', // Inadimplência
            '33' => '05', // Restrições financeiras
            '64' => '05', // Saldo maior que limite
            '63' => '06', // Inoperante
            '38' => '06', // Tempo inoperância
            '69' => '06', // Inoperância 24+ meses
            '3' => '07',  // Encerramento atividades
            '25' => '07', // Fechou estabelecimento
            '8' => '08',  // Vigilância sanitária
        ];
        
        return str_pad($mapeamento[$motivoInterno] ?? '99', 2, '0', STR_PAD_LEFT);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new EncerramentoMassa();
    
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
        $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
        $result = $handler->generateFromSelection($solicitacoes);
        
        if ($result['success']) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
            header('Content-Length: ' . strlen($result['conteudo']));
            echo $result['conteudo'];
        } else {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        exit;
    }
    
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_excel') {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $result = $handler->generateFromExcel($_FILES['excel_file']['tmp_name']);
            
            if ($result['success']) {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
                header('Content-Length: ' . strlen($result['conteudo']));
                echo $result['conteudo'];
            } else {
                header('Content-Type: application/json');
                echo json_encode($result);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Arquivo não enviado']);
        }
        exit;
    }
}
?>


-----------

<?php
@session_start();
require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948'; // Código Bradesco
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    // Generate TXT from selected checkboxes
    public function generateFromSelection($solicitacoes) {
        if (empty($solicitacoes) || !is_array($solicitacoes)) {
            return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
        }
        
        $dados = $this->getDadosFromSolicitacoes($solicitacoes);
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        return $this->generateTXT($dados);
    }
    
    // Generate TXT from Excel import
    public function generateFromExcel($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\PhpSpreadsheet\vendor\autoload.php';
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $chaveLojas = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            
            if (empty($chaveLojas)) {
                return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
            }
            
            $dados = $this->getDadosFromChaveLojas($chaveLojas);
            return $this->generateTXT($dados);
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao processar Excel: ' . $e->getMessage()];
        }
    }
    
    private function getDadosFromSolicitacoes($solicitacoes) {
        $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
        return $this->model->solicitacoes($where, 9999, 0);
    }
    
    private function getDadosFromChaveLojas($chaveLojas) {
        $where = "AND A.CHAVE_LOJA IN (" . implode(',', array_map('intval', $chaveLojas)) . ")";
        return $this->model->solicitacoes($where, 9999, 0);
    }
    
    private function generateTXT($dados) {
        $linhas = [];
        
        // Header (Leiaute 1)
        $linhas[] = $this->gerarHeader(count($dados));
        
        // Details (Leiaute 2)
        $sequencial = 1;
        foreach ($dados as $row) {
            $linhas[] = $this->gerarDetalhe($row, $sequencial);
            $sequencial++;
        }
        
        // Trailer (Leiaute 9)
        $linhas[] = $this->gerarTrailer(count($dados));
        
        $conteudo = implode("\r\n", $linhas);
        $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
        
        return [
            'success' => true,
            'conteudo' => $conteudo,
            'nomeArquivo' => $nomeArquivo,
            'totalRegistros' => count($dados)
        ];
    }
    
    private function gerarHeader($totalRegistros) {
        $tipo = '0';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $tipoDocumento = '02'; // Encerramento
        $dataGeracao = date('Ymd');
        $horaGeracao = date('His');
        $sequencial = str_pad('1', 6, '0', STR_PAD_LEFT);
        $versaoLayout = '001';
        
        $linha = $tipo;
        $linha .= $instituicao;
        $linha .= $tipoDocumento;
        $linha .= $dataGeracao;
        $linha .= $horaGeracao;
        $linha .= $sequencial;
        $linha .= $versaoLayout;
        $linha .= str_repeat(' ', 250 - strlen($linha)); // Preencher até 250 caracteres
        
        return substr($linha, 0, 250);
    }
    
    private function gerarDetalhe($row, $sequencial) {
        $tipo = '2';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $agencia = str_pad($row['COD_AG'], 4, '0', STR_PAD_LEFT);
        $chaveLoja = str_pad($row['CHAVE_LOJA'], 10, '0', STR_PAD_LEFT);
        $cnpj = str_pad(preg_replace('/\D/', '', $row['CNPJ']), 14, '0', STR_PAD_LEFT);
        $razaoSocial = str_pad(substr($row['NOME_LOJA'], 0, 60), 60, ' ', STR_PAD_RIGHT);
        $dataEncerramento = date('Ymd'); // Data atual
        $motivoEncerramento = $this->getMotivoCodigoBacen($row['MOTIVO_ENC']);
        $sequencialStr = str_pad($sequencial, 6, '0', STR_PAD_LEFT);
        
        $linha = $tipo;
        $linha .= $instituicao;
        $linha .= $agencia;
        $linha .= $chaveLoja;
        $linha .= $cnpj;
        $linha .= $razaoSocial;
        $linha .= $dataEncerramento;
        $linha .= $motivoEncerramento;
        $linha .= $sequencialStr;
        $linha .= str_repeat(' ', 250 - strlen($linha));
        
        return substr($linha, 0, 250);
    }
    
    private function gerarTrailer($totalRegistros) {
        $tipo = '9';
        $quantidadeRegistros = str_pad($totalRegistros, 10, '0', STR_PAD_LEFT);
        
        $linha = $tipo;
        $linha .= $quantidadeRegistros;
        $linha .= str_repeat(' ', 250 - strlen($linha));
        
        return substr($linha, 0, 250);
    }
    
    private function getMotivoCodigoBacen($motivoInterno) {
        // Mapear códigos internos para códigos BACEN
        $mapeamento = [
            '56' => '01', // Baixa Performance
            '61' => '01', // Abaixo ponto equilíbrio
            '34' => '02', // Apropriação indébita
            '37' => '02', // Cobrança indevida
            '39' => '02', // Suspeita fraude
            '55' => '03', // Desvio finalidade
            '62' => '04', // Falta prestação contas
            '32' => '05', // Inadimplência
            '33' => '05', // Restrições financeiras
            '64' => '05', // Saldo maior que limite
            '63' => '06', // Inoperante
            '38' => '06', // Tempo inoperância
            '69' => '06', // Inoperância 24+ meses
            '3' => '07',  // Encerramento atividades
            '25' => '07', // Fechou estabelecimento
            '8' => '08',  // Vigilância sanitária
        ];
        
        return str_pad($mapeamento[$motivoInterno] ?? '99', 2, '0', STR_PAD_LEFT);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $handler = new EncerramentoMassa();
    
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_selection') {
        $solicitacoes = json_decode($_POST['solicitacoes'] ?? '[]', true);
        $result = $handler->generateFromSelection($solicitacoes);
        
        if ($result['success']) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
            header('Content-Length: ' . strlen($result['conteudo']));
            echo $result['conteudo'];
        } else {
            header('Content-Type: application/json');
            echo json_encode($result);
        }
        exit;
    }
    
    if (isset($_POST['acao']) && $_POST['acao'] === 'gerar_txt_excel') {
        if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
            $result = $handler->generateFromExcel($_FILES['excel_file']['tmp_name']);
            
            if ($result['success']) {
                header('Content-Type: text/plain');
                header('Content-Disposition: attachment; filename="' . $result['nomeArquivo'] . '"');
                header('Content-Length: ' . strlen($result['conteudo']));
                echo $result['conteudo'];
            } else {
                header('Content-Type: application/json');
                echo json_encode($result);
            }
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Arquivo não enviado']);
        }
        exit;
    }
}
?>


-------

<?php if ($canViewBulk): ?>
<div class="card mb-3" id="bulkActions" style="display: none;">
  <div class="card-header">
      <h3 class="card-title">Ações em Massa</h3>
  </div>
  <div class="card-body">
      <div class="d-flex gap-2 mb-3">
          <button class="btn btn-blue" onclick="sendBulkEmail('orgao_pagador')">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
              </svg>
              Órgão Pagador
          </button>
          <button class="btn btn-indigo" onclick="sendBulkEmail('comercial')">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
              </svg>
              Comercial
          </button>
          <button class="btn btn-teal" onclick="sendBulkEmail('van_material')">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
              </svg>
              Van-Material
          </button>
          <button class="btn btn-lime" onclick="sendBulkEmail('bloqueio')">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope me-1" viewBox="0 0 16 16">
                  <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4Zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2Zm13 2.383-4.708 2.825L15 11.105V5.383Zm-.034 6.876-5.64-3.471L8 9.583l-1.326-.795-5.64 3.47A1 1 0 0 0 2 13h12a1 1 0 0 0 .966-.741ZM1 11.105l4.708-2.897L1 5.383v5.722Z"/>
              </svg>
              Bloqueio
          </button>
          <button class="btn btn-red ml-auto p-2" onclick="sendBulkEmail('encerramento')">
              <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-cancel"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M18.364 5.636l-12.728 12.728" /></svg>
              Encerramento
          </button>
      </div>
      
      <hr class="my-3">
      
      <div class="d-flex gap-2 align-items-center">
          <button class="btn btn-success" onclick="gerarTXTSelection()">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-text me-1" viewBox="0 0 16 16">
                  <path d="M5.5 7a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5zM5 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5zm0 2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                  <path d="M9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.5L9.5 0zm0 1v2A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5z"/>
              </svg>
              Gerar TXT BACEN (Selecionados)
          </button>
          
          <div class="vr"></div>
          
          <button class="btn btn-primary" onclick="uploadExcelAndGenerateTXT()">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark-excel me-1" viewBox="0 0 16 16">
                  <path d="M5.884 6.68a.5.5 0 1 0-.768.64L7.349 10l-2.233 2.68a.5.5 0 0 0 .768.64L8 10.781l2.116 2.54a.5.5 0 0 0 .768-.641L8.651 10l2.233-2.68a.5.5 0 0 0-.768-.64L8 9.219l-2.116-2.54z"/>
                  <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
              </svg>
              Importar Excel e Gerar TXT
          </button>
          
          <small class="text-muted ms-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-info-circle me-1" viewBox="0 0 16 16">
                  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                  <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
              </svg>
              Excel deve conter colunas: CHAVE_LOJA e AGENCIA
          </small>
      </div>
  </div>
</div>
<?php endif; ?>


-----------

# Geração de Arquivo TXT BACEN - Encerramento de Correspondentes

## Visão Geral
Este módulo permite gerar arquivos TXT no formato exigido pelo Banco Central (BACEN) para encerramento de contratos de correspondentes no país (Transação 02 - DOC5021).

## Formas de Uso

### 1. Geração a partir de Registros Selecionados
1. Na tela principal, selecione as solicitações desejadas usando os checkboxes
2. Clique no botão **"Gerar TXT BACEN (Selecionados)"** na seção de Ações em Massa
3. O arquivo TXT será gerado e baixado automaticamente

### 2. Geração a partir de Importação Excel
1. Prepare um arquivo Excel (.xlsx, .xls ou .csv) com as seguintes colunas:
   - **CHAVE_LOJA** (coluna A): Código da chave loja
   - **AGENCIA** (coluna B): Código da agência (opcional)
   
   Exemplo:
   ```
   CHAVE_LOJA | AGENCIA
   12345      | 1234
   67890      | 5678
   ```

2. Clique no botão **"Importar Excel e Gerar TXT"**
3. Selecione o arquivo Excel
4. O sistema processará e gerará o arquivo TXT automaticamente

## Formato do Arquivo TXT

O arquivo gerado segue o layout oficial do BACEN (DOC5021) e contém:

### Header (Tipo 0)
- Tipo de registro: 0
- Instituição: 60746948 (Bradesco)
- Tipo documento: 02 (Encerramento)
- Data/hora de geração
- Versão do layout: 001
- Tamanho: 250 caracteres

### Detalhes (Tipo 2)
Para cada correspondente:
- Tipo de registro: 2
- Instituição
- Agência
- Chave Loja
- CNPJ (14 dígitos)
- Razão Social (60 caracteres)
- Data encerramento
- Motivo encerramento (código BACEN)
- Sequencial
- Tamanho: 250 caracteres

### Trailer (Tipo 9)
- Tipo de registro: 9
- Quantidade total de registros
- Tamanho: 250 caracteres

## Mapeamento de Motivos

### Códigos Internos → Códigos BACEN

| Código Interno | Descrição | Código BACEN |
|----------------|-----------|--------------|
| 56 | Baixa Performance em Transações | 01 |
| 61 | Correspondente abaixo do ponto de equilíbrio | 01 |
| 34 | Apropriação do dinheiro do Banco | 02 |
| 37 | Cobrança tarifa de Clientes | 02 |
| 39 | Suspeita de fraude | 02 |
| 55 | Desvio de Finalidade | 03 |
| 62 | Falta de Prestação de Contas | 04 |
| 32 | Inadimplência | 05 |
| 33 | Restrições financeiras | 05 |
| 64 | Saldo maior que Limite | 05 |
| 63 | Inoperantes | 06 |
| 38 | Tempo da inoperância | 06 |
| 69 | Inoperância superior a 24 meses | 06 |
| 3 | Encerramento Atividades | 07 |
| 25 | Fechou o estabelecimento | 07 |
| 8 | Vigilância Sanitária | 08 |
| Outros | - | 99 |

## Estrutura de Arquivos

```
control/encerramento/
├── encerramento_massa.php         # Gerador de TXT
├── email_functions.php            # Funções de email
└── roteamento/
    └── ajax_encerramento.php      # Handler AJAX

view/encerramento/
└── analise_encerramento.php       # Interface principal

js/encerramento/
└── analise_encerramento.js        # JavaScript
```

## Validações

O sistema realiza as seguintes validações:
- Verificação de dados obrigatórios (CHAVE_LOJA, CNPJ, etc.)
- Validação do formato do CNPJ
- Preenchimento correto de campos com tamanho fixo
- Validação do motivo de encerramento

## Formato do Nome do Arquivo

O arquivo gerado segue o padrão:
```
ENCERRAMENTO_YYYYMMDD_HHMMSS.txt
```

Exemplo: `ENCERRAMENTO_20250110_143052.txt`

## Observações Importantes

1. **Permissões**: Apenas usuários do grupo ENC_MANAGEMENT podem gerar arquivos TXT
2. **Tamanho**: Cada linha do arquivo possui exatamente 250 caracteres
3. **Codificação**: ASCII padrão
4. **Quebra de linha**: CR+LF (Windows)
5. **Status**: Os correspondentes devem ter passado pelas etapas anteriores antes do encerramento
6. **CNPJ**: Apenas números, sem formatação (14 dígitos)

## Tratamento de Erros

Possíveis erros e soluções:

| Erro | Causa | Solução |
|------|-------|---------|
| "Nenhum registro selecionado" | Nenhum checkbox marcado | Selecione ao menos um registro |
| "Nenhuma Chave Loja encontrada" | Excel sem dados válidos | Verifique o formato do Excel |
| "Dados não encontrados" | Chaves Loja inexistentes | Verifique os códigos informados |
| "Arquivo não enviado" | Falha no upload | Tente novamente |

## Referências

- [Documentação BACEN DOC5021](https://www.bcb.gov.br/estabilidadefinanceira/leiautedoc5021mai)
- Transação 02: Encerramento de Contrato de Correspondente no País

## Suporte

Em caso de dúvidas ou problemas, entre em contato com a equipe de desenvolvimento.