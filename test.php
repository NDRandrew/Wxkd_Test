I'll modify the code to work with Excel imports even without COD_SOLICITACAO, using your new model function.

## Changes to Model (M.txt) - Add helper method

```php
public function updateDataContVerifiedByChaveLoja($chaveLoja, $dataContVerified) {
    $query = "UPDATE MESU..ENCERRAMENTO_TB_PORTAL 
            SET DATA_CONT_VERIFIED = '" . addslashes($dataContVerified) . "' 
            WHERE CHAVE_LOJA = " . intval($chaveLoja);
    return $this->sql->update($query);
}

public function getDataContVerifiedByChaveLoja($chaveLoja) {
    $query = "SELECT DATA_CONT_VERIFIED 
            FROM MESU..ENCERRAMENTO_TB_PORTAL 
            WHERE CHAVE_LOJA = " . intval($chaveLoja);
    $result = $this->sql->select($query);
    return $result ? $result[0]['DATA_CONT_VERIFIED'] : null;
}

public function insertEncerramentoStatusByChaveLoja($chaveLoja) {
    $query = "IF NOT EXISTS (SELECT 1 FROM MESU..ENCERRAMENTO_TB_PORTAL WHERE CHAVE_LOJA = " . intval($chaveLoja) . ")
            BEGIN
                INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL (
                    CHAVE_LOJA, STATUS_SOLIC, STATUS_OP, STATUS_COM, 
                    STATUS_VAN, STATUS_BLOQ, STATUS_ENCERRAMENTO
                ) VALUES (
                    " . intval($chaveLoja) . ", 
                    'EM ANDAMENTO', 'NAO EFETUADO', 'NAO EFETUADO', 
                    'NAO EFETUADO', 'NAO EFETUADO', 'NAO EFETUADO'
                )
            END";
    return $this->sql->insert($query);
}
```

## Changes to encerramento_massa.php (EnMa.txt)

Replace the entire class with this updated version:

```php
<?php
@session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';

class EncerramentoMassa {
    private $model;
    private $instituicao = '60746948';
    public $errors = []; 
    
    public function __construct() {
        $this->model = new Analise();
    }
    
    public function getErrors() {
        return $this->errors;
    }

    public function insertErrorToDatabase($errors, $fileName) {
        if (empty($errors)) return;
        
        $model = new Analise();
        
        foreach ($errors as $error) {
            $ocorrencia = "Arquivo: " . $fileName . "\n";
            $ocorrencia .= "Tipo: " . $error['error_type'] . "\n";
            $ocorrencia .= "Mensagem: " . $error['error_message'] . "\n";
            $ocorrencia .= "Data Contrato: " . $error['data_contrato'] . "\n";
            
            $query = "INSERT INTO MESU..ENCERRAMENTO_TB_PORTAL_OCORRENCIA 
                    (OCORRENCIA, CNPJs)
                    VALUES (
                        '" . addslashes($ocorrencia) . "',
                        '" . addslashes($error['cnpj']) . "'
                    )";
            
            try {
                $result = $model->insert($query);
                
                if (!$result) {
                    error_log("Failed to insert error to database for CNPJ: " . $error['cnpj']);
                } else {
                    error_log("Successfully inserted error for CNPJ: " . $error['cnpj']);
                }
            } catch (Exception $e) {
                error_log("Exception inserting error to database: " . $e->getMessage());
            }
        }
    }
    
    public function generateFromSelection($solicitacoes) {
        try {
            if (empty($solicitacoes) || !is_array($solicitacoes)) {
                return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
            }
            
            $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
            $dados = $this->model->solicitacoesEncerramento($where, 999999, 0);
            
            if (empty($dados)) {
                return ['success' => false, 'message' => 'Dados não encontrados'];
            }
            
            $result = $this->generateTXT($dados, true); // true = has COD_SOLICITACAO
            
            $result['errors'] = $this->errors;
            $result['has_errors'] = !empty($this->errors);
            
            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }
    
    public function getCNPJsForSelectionVerification($solicitacoes) {
        try {
            if (empty($solicitacoes) || !is_array($solicitacoes)) {
                return ['success' => false, 'message' => 'Nenhuma solicitação selecionada'];
            }
            
            $where = "AND A.COD_SOLICITACAO IN (" . implode(',', array_map('intval', $solicitacoes)) . ")";
            $dados = $this->model->solicitacoesEncerramento($where, 999999, 0);
            
            if (empty($dados)) {
                return ['success' => false, 'message' => 'Dados não encontrados'];
            }
            
            $cnpjList = [];
            foreach ($dados as $row) {
                $cnpj = $this->formatCNPJ($row['CNPJ']);
                $dataContrato = is_object($row['DATA_CONTRATO']) 
                    ? $row['DATA_CONTRATO']->format('Y-m-d') 
                    : date('Y-m-d', strtotime($row['DATA_CONTRATO']));
                    
                $cnpjList[] = [
                    'cod_solicitacao' => $row['COD_SOLICITACAO'],
                    'cnpj' => $cnpj,
                    'data_contrato' => $dataContrato,
                    'chave_loja' => $row['CHAVE_LOJA'],
                    'nome_loja' => $row['NOME_LOJA']
                ];
            }
            
            return [
                'success' => true,
                'cnpjs' => $cnpjList
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erro ao obter CNPJs: ' . $e->getMessage()];
        }
    }
    
    public function generateFromExcel($filePath, $originalFilename = null) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        if ($originalFilename) {
            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        } else {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }
        
        if ($extension === 'csv') {
            $chaveLojas = $this->parseCSV($filePath);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            $chaveLojas = $this->parseExcel($filePath);
        } else {
            return ['success' => false, 'message' => 'Formato não suportado. Use CSV, XLS ou XLSX. Extensão detectada: ' . $extension];
        }
        
        if (empty($chaveLojas)) {
            return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada no arquivo'];
        }
        
        // Use new solicitacoesExcel function
        $where = implode(',', array_map('intval', $chaveLojas));
        $dados = $this->model->solicitacoesExcel($where);
        
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados para as Chaves Loja fornecidas'];
        }
        
        $result = $this->generateTXT($dados, false); // false = no COD_SOLICITACAO guaranteed
        
        $result['errors'] = $this->errors;
        $result['has_errors'] = !empty($this->errors);
        
        return $result;
    }
    
    public function getCNPJsForExcelVerification($filePath, $originalFilename = null) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado'];
        }
        
        if ($originalFilename) {
            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        } else {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        }
        
        if ($extension === 'csv') {
            $chaveLojas = $this->parseCSV($filePath);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            $chaveLojas = $this->parseExcel($filePath);
        } else {
            return ['success' => false, 'message' => 'Formato não suportado. Extensão detectada: ' . $extension];
        }
        
        if (empty($chaveLojas)) {
            return ['success' => false, 'message' => 'Nenhuma Chave Loja encontrada'];
        }
        
        // Use new solicitacoesExcel function
        $where = implode(',', array_map('intval', $chaveLojas));
        $dados = $this->model->solicitacoesExcel($where);
        
        if (empty($dados)) {
            return ['success' => false, 'message' => 'Dados não encontrados'];
        }
        
        $cnpjList = [];
        foreach ($dados as $row) {
            $cnpj = $this->formatCNPJ($row['CNPJ']);
            $dataContrato = is_object($row['DATA_CONTRATO']) 
                ? $row['DATA_CONTRATO']->format('Y-m-d') 
                : date('Y-m-d', strtotime($row['DATA_CONTRATO']));
                
            $cnpjList[] = [
                'cod_solicitacao' => isset($row['COD_SOLICITACAO']) ? $row['COD_SOLICITACAO'] : null,
                'cnpj' => $cnpj,
                'data_contrato' => $dataContrato,
                'chave_loja' => $row['CHAVE_LOJA'],
                'nome_loja' => $row['NOME_LOJA']
            ];
        }
        
        return [
            'success' => true,
            'cnpjs' => $cnpjList
        ];
    }
    
    private function formatCNPJ($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return str_pad(substr($cnpj, 0, 8), 8, '0', STR_PAD_LEFT);
    }
    
    private function parseCSV($filePath) {
        $chaveLojas = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle) {
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            fclose($handle);
        }
        
        return $chaveLojas;
    }
    
    private function parseExcel($filePath) {
        $phpSpreadsheetPath = '\\\\D4920S010\\D4920_2\\Secoes\\D4920S012\\Comum_S012\\Servidor_Portal_Expresso\\Server2Go\\htdocs\\Lib\\PhpSpreadsheet\\vendor\\autoload.php';
        
        if (!file_exists($phpSpreadsheetPath)) {
            return $this->parseCSV($filePath);
        }
        
        try {
            require_once $phpSpreadsheetPath;
            
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            $chaveLojas = [];
            foreach ($rows as $index => $row) {
                if ($index === 0) continue;
                if (!empty($row[0])) {
                    $chaveLojas[] = $row[0];
                }
            }
            
            return $chaveLojas;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function generateTXT($dados, $hasCodSolicitacao = true) {
        $linhas = [];
        $totalLinhas = 0;
        
        // Count total lines
        foreach ($dados as $row) {
            $verifiedDate = null;
            
            if ($hasCodSolicitacao && isset($row['COD_SOLICITACAO'])) {
                $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
            } else if (isset($row['CHAVE_LOJA'])) {
                $verifiedDate = $this->model->getDataContVerifiedByChaveLoja($row['CHAVE_LOJA']);
            }
            
            if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
                $dates = explode(',', $verifiedDate);
                $totalLinhas += count($dates);
            } else {
                $totalLinhas++;
            }
        }
        
        $linhas[] = $this->gerarHeader($totalLinhas);
        
        $sequencial = 2;
        foreach ($dados as $row) {
            $verifiedDate = null;
            
            if ($hasCodSolicitacao && isset($row['COD_SOLICITACAO'])) {
                $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
            } else if (isset($row['CHAVE_LOJA'])) {
                $verifiedDate = $this->model->getDataContVerifiedByChaveLoja($row['CHAVE_LOJA']);
            }
            
            if ($verifiedDate && strpos($verifiedDate, ',') !== false) {
                $dates = explode(',', $verifiedDate);
                foreach ($dates as $date) {
                    $rowCopy = $row;
                    $rowCopy['DATA_CONTRATO_OVERRIDE'] = trim($date);
                    
                    $lineResult = $this->gerarDetalhe($rowCopy, $sequencial);
                    $linhas[] = $lineResult['line'];
                    
                    if ($lineResult['error']) {
                        $this->errors[] = [
                            'cod_solicitacao' => isset($row['COD_SOLICITACAO']) ? $row['COD_SOLICITACAO'] : null,
                            'chave_loja' => $row['CHAVE_LOJA'],
                            'nome_loja' => $row['NOME_LOJA'],
                            'cnpj' => $row['CNPJ'],
                            'data_contrato' => trim($date),
                            'error_type' => $lineResult['error_type'],
                            'error_message' => $lineResult['error_message'],
                            'txt_line' => $lineResult['line'],
                            'sequencial' => $sequencial
                        ];
                    }
                    
                    $sequencial++;
                }
            } else {
                $lineResult = $this->gerarDetalhe($row, $sequencial);
                $linhas[] = $lineResult['line'];
                
                if ($lineResult['error']) {
                    $this->errors[] = [
                        'cod_solicitacao' => isset($row['COD_SOLICITACAO']) ? $row['COD_SOLICITACAO'] : null,
                        'chave_loja' => $row['CHAVE_LOJA'],
                        'nome_loja' => $row['NOME_LOJA'],
                        'cnpj' => $row['CNPJ'],
                        'data_contrato' => $verifiedDate ?: (is_object($row['DATA_CONTRATO']) ? $row['DATA_CONTRATO']->format('Y-m-d') : $row['DATA_CONTRATO']),
                        'error_type' => $lineResult['error_type'],
                        'error_message' => $lineResult['error_message'],
                        'txt_line' => $lineResult['line'],
                        'sequencial' => $sequencial
                    ];
                }
                
                $sequencial++;
            }
        }
        
        $linhas[] = $this->gerarTrailer($totalLinhas);
        
        $conteudo = implode("\r\n", $linhas);
        $nomeArquivo = 'ENCERRAMENTO_' . date('Ymd_His') . '.txt';
        
        error_log("TXT Generation completed. Total errors: " . count($this->errors));
        
        $result = [
            'success' => true,
            'conteudo' => $conteudo,
            'nomeArquivo' => $nomeArquivo,
            'totalRegistros' => $totalLinhas,
            'errors' => $this->errors,
            'has_errors' => !empty($this->errors)
        ];

        if (!empty($this->errors)) {
            error_log("Calling insertErrorToDatabase with " . count($this->errors) . " errors");
            $this->insertErrorToDatabase($this->errors, $nomeArquivo);
        }

        return $result;
    }
    
    private function gerarHeader($totalRegistros) {
        $tipo = '#A1';
        $codigoDocumento = '5021';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $dataGeracao = date('Ymd');
        $contato = str_pad('YGOR SANTINI', 30, ' ', STR_PAD_RIGHT);
        $ddd = '00011';
        $telefone = '0036849907';
        $livreFiller = str_pad(' ', 112, ' ', STR_PAD_RIGHT);
        $sequencial = '00001';
        
        $linha = $tipo . $codigoDocumento . $instituicao . $dataGeracao . $contato . $ddd . $telefone . $livreFiller . $sequencial;
        return substr($linha, 0, 250);
    }
    
    private function gerarDetalhe($row, $sequencial) {
        $tipo = 'D01';
        $metodo = '02';
        $instituicao = str_pad($this->instituicao, 8, '0', STR_PAD_LEFT);
        $cnpj = str_pad($row['CNPJ'], 8, '0', STR_PAD_LEFT);
        $cnpjSubs = str_pad(' ', 8, ' ', STR_PAD_RIGHT);
        
        $hasError = false;
        $errorType = '';
        $errorMessage = '';
        
        $dataToUse = null;
        
        if (isset($row['DATA_CONTRATO_OVERRIDE']) && !empty($row['DATA_CONTRATO_OVERRIDE'])) {
            $dataToUse = $row['DATA_CONTRATO_OVERRIDE'];
        } else {
            if (isset($row['COD_SOLICITACAO']) && $row['COD_SOLICITACAO']) {
                $verifiedDate = $this->model->getDataContVerified($row['COD_SOLICITACAO']);
                if ($verifiedDate) {
                    $dataToUse = $verifiedDate;
                }
            } else if (isset($row['CHAVE_LOJA'])) {
                $verifiedDate = $this->model->getDataContVerifiedByChaveLoja($row['CHAVE_LOJA']);
                if ($verifiedDate) {
                    $dataToUse = $verifiedDate;
                }
            }
            
            if (!$dataToUse) {
                $dataToUse = $row['DATA_CONTRATO'];
                $hasError = true;
                $errorType = 'missing_verification';
                $errorMessage = 'DATA_CONT_VERIFIED não encontrado - usando DATA_CONTRATO do banco';
            }
        }
        
        $dataContrato = '19700101';
        
        if (!empty($dataToUse)) {
            if (is_object($dataToUse)) {
                $dataContrato = $dataToUse->format('Ymd');
            } else {
                $dateString = trim($dataToUse);
                
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateString)) {
                    $dataContrato = str_replace('-', '', $dateString);
                } else {
                    $timestamp = @strtotime($dateString);
                    if ($timestamp !== false && $timestamp > 0) {
                        $dataContrato = date('Ymd', $timestamp);
                    } else {
                        $hasError = true;
                        $errorType = 'date_parse_failed';
                        $errorMessage = 'Falha ao parsear data: "' . $dateString . '" - usando fallback 19700101';
                    }
                }
            }
        } else {
            $hasError = true;
            $errorType = 'empty_date';
            $errorMessage = 'Data vazia - usando fallback 19700101';
        }
        
        $dataEncerramento = date('Ymd');
        $linhaFiller = str_pad(' ', 135, ' ', STR_PAD_LEFT);
        $sequencialStr = str_pad($sequencial, 5, '0', STR_PAD_LEFT);
        
        $linha = $tipo . $metodo . $instituicao . $cnpj . $cnpjSubs . $dataContrato . $dataEncerramento . $linhaFiller . $sequencialStr;
        
        return [
            'line' => substr($linha, 0, 250),
            'error' => $hasError,
            'error_type' => $errorType,
            'error_message' => $errorMessage
        ];
    }
    
    private function gerarTrailer($totalRegistros) {
        $tipo = '@10';
        $codDoc = '5021';
        $quantidadeRegistros = str_pad($totalRegistros, 5, '0', STR_PAD_LEFT);
        $filler = '000000000000000000000000000000';
        
        $linha = $tipo . $codDoc . $quantidadeRegistros . $filler . str_repeat(' ', 138) . str_pad($totalRegistros + 2, 5, '0', STR_PAD_LEFT);
        
        return substr($linha, 0, 250);
    }
}
```

## Changes to ajax_encerramento.php (JH.txt)

Update the `update_data_cont_verified` handler:

```php
if (isset($_POST['acao']) && $_POST['acao'] === 'update_data_cont_verified') {
    ob_start();
    try {
        require_once 'X:\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\teste\Andre\tabler_portalexpresso_paginaEncerramento\model\encerramento\analise_encerramento_model.class.php';
        
        $codSolicitacao = isset($_POST['cod_solicitacao']) ? intval($_POST['cod_solicitacao']) : 0;
        $chaveLoja = isset($_POST['chave_loja']) ? intval($_POST['chave_loja']) : 0;
        $dataContVerified = isset($_POST['data_cont_verified']) ? $_POST['data_cont_verified'] : '';
        
        if ($codSolicitacao <= 0 && $chaveLoja <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Código de solicitação ou Chave Loja inválidos']);
            exit;
        }
        
        if (empty($dataContVerified)) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode_custom(['success' => false, 'message' => 'Data de verificação vazia']);
            exit;
        }
        
        $model = new Analise();
        $result = false;
        
        if ($codSolicitacao > 0) {
            // Has COD_SOLICITACAO - use original method
            $status = $model->getEncerramentoStatus($codSolicitacao);
            
            if (!$status) {
                $where = "AND A.COD_SOLICITACAO = " . $codSolicitacao;
                $dados = $model->solicitacoes($where, 1, 0);
                if (!empty($dados)) {
                    $model->insertEncerramentoStatus($codSolicitacao, $dados[0]['CHAVE_LOJA']);
                } else {
                    ob_end_clean();
                    header('Content-Type: application/json');
                    echo json_encode_custom(['success' => false, 'message' => 'Solicitação não encontrada']);
                    exit;
                }
            }
            
            $result = $model->updateDataContVerified($codSolicitacao, $dataContVerified);
        } else if ($chaveLoja > 0) {
            // No COD_SOLICITACAO - use CHAVE_LOJA
            $model->insertEncerramentoStatusByChaveLoja($chaveLoja);
            $result = $model->updateDataContVerifiedByChaveLoja($chaveLoja, $dataContVerified);
        }
        
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom([
            'success' => $result ? true : false,
            'message' => $result ? 'Atualizado com sucesso' : 'Erro ao executar UPDATE',
            'cod_solicitacao' => $codSolicitacao,
            'chave_loja' => $chaveLoja,
            'data_verified' => $dataContVerified
        ]);
        
    } catch (Exception $e) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode_custom(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit;
}
```

## Changes to analise_encerramento.js (J.txt)

Update the `verifySingleCNPJ` function to handle NULL COD_SOLICITACAO:

```javascript
async function verifySingleCNPJ(cnpjData) {
    console.log('Verifying CNPJ:', cnpjData.cnpj);
    
    return new Promise((resolve) => {
        $.ajax({
            url: BACEN_API_CONFIG.url,
            method: 'POST',
            contentType: 'application/json',
            dataType: 'json',
            data: JSON.stringify({
                user: BACEN_API_CONFIG.user,
                pwd: BACEN_API_CONFIG.pwd,
                cnpj: cnpjData.cnpj
            }),
            success: function(data) {
                console.log('API Success for CNPJ', cnpjData.cnpj, ':', data);
                
                if (data.success) {
                    const verifiedDate = parseDateFromResponse(data);
                    console.log('Verified date(s):', verifiedDate, 'Original date:', cnpjData.data_contrato);
                    
                    if (verifiedDate) {
                        console.log('Updating database with date(s)...');
                        
                        // Use cod_solicitacao if available, otherwise use chave_loja
                        const updateData = {
                            acao: 'update_data_cont_verified',
                            data_cont_verified: verifiedDate
                        };
                        
                        if (cnpjData.cod_solicitacao) {
                            updateData.cod_solicitacao = cnpjData.cod_solicitacao;
                        } else {
                            updateData.chave_loja = cnpjData.chave_loja;
                        }
                        
                        updateDataContVerified(updateData)
                            .then(() => {
                                console.log('Database updated successfully');
                                resolve({ 
                                    success: true, 
                                    updated: true,
                                    cnpjData: cnpjData,
                                    verifiedDate: verifiedDate
                                });
                            })
                            .catch((err) => {
                                console.error('Database update failed:', err);
                                resolve({ 
                                    success: false, 
                                    updated: false,
                                    error: 'Database update failed',
                                    cnpjData: cnpjData
                                });
                            });
                    } else {
                        console.log('No verified date, skipping update');
                        resolve({ 
                            success: false, 
                            updated: false,
                            error: 'No verified date returned from API',
                            cnpjData: cnpjData
                        });
                    }
                } else {
                    console.error('API returned success:false');
                    resolve({ 
                        success: false, 
                        error: data.result || 'API returned error',
                        cnpjData: cnpjData
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error for CNPJ', cnpjData.cnpj);
                resolve({ 
                    success: false, 
                    error: 'API connection error: ' + error,
                    cnpjData: cnpjData
                });
            }
        });
    });
}

async function updateDataContVerified(updateData) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: AJAX_URL,
            method: 'POST',
            data: updateData,
            success: function(data) {
                resolve(data);
            },
            error: function(xhr, status, error) {
                console.error('Update error:', error);
                reject(error);
            }
        });
    });
}
```

## Summary

These changes enable the system to:

1. **Work without COD_SOLICITACAO**: Uses CHAVE_LOJA as the primary identifier for Excel imports
2. **Verify via Bacen API**: Still validates dates even without COD_SOLICITACAO
3. **Store verified dates**: Creates records in ENCERRAMENTO_TB_PORTAL using CHAVE_LOJA
4. **Generate TXT files**: Works with both COD_SOLICITACAO and CHAVE_LOJA-only data
5. **Track errors properly**: Records all API and date verification errors in the ocorrencias table

The code now handles both scenarios:
- **Selection mode**: Has COD_SOLICITACAO, uses existing logic
- **Excel mode**: May not have COD_SOLICITACAO, uses CHAVE_LOJA-based methods