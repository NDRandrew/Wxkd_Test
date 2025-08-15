<?php
// Add this method to the Wxkd_DashboardModel class

public function getTableDataByFilter($filter = 'all') {
    try {
        $query = "";
        
        switch($filter) {
            case 'cadastramento':
                $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
                        " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                        AND H.WXKD_FLAG = 0";
                break;
                
            case 'descadastramento':
                // ... existing descadastramento query ...
                break;
                
            case 'historico':
                // MODIFIED: Only show records with CONFIRMADO_FLAG = 0
                $query = "SELECT 
                            CHAVE_LOTE,
                            DATA_LOG,
                            COUNT(*) as TOTAL_REGISTROS,
                            FILTRO
                        FROM PGTOCORSP.dbo.TB_WXKD_LOG 
                        WHERE CONFIRMADO_FLAG = 0
                        GROUP BY CHAVE_LOTE, DATA_LOG, FILTRO 
                        ORDER BY DATA_LOG DESC";
                break;
                
            default: 
                $query = "SELECT " . $this->baseSelectFields . $this->baseJoins . 
                        " WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
                        AND H.WXKD_FLAG IN (0,1)";
                break;
        }
        
        if (empty($query)) {
            return array();
        }
        
        $result = $this->sql->select($query);
        return $result;
        
    } catch (Exception $e) {
        error_log("getTableDataByFilter - Exception: " . $e->getMessage());
        return array();
    }
}

// NEW METHOD: Restore confirmed records
public function restoreConfirmedRecords() {
    try {
        // Step 1: Find the minimum CHAVE_LOTE among confirmed records
        $minLoteQuery = "SELECT MIN(CHAVE_LOTE) as MIN_LOTE 
                        FROM PGTOCORSP.dbo.TB_WXKD_LOG 
                        WHERE CONFIRMADO_FLAG = 1";
        
        $minLoteResult = $this->sql->select($minLoteQuery);
        
        if (empty($minLoteResult) || !isset($minLoteResult[0]['MIN_LOTE'])) {
            return false; // No confirmed records found
        }
        
        $minLote = (int)$minLoteResult[0]['MIN_LOTE'];
        
        if ($minLote <= 0) {
            return false; // Invalid minimum lote
        }
        
        // Step 2: Update all confirmed records
        $updateQuery = "UPDATE PGTOCORSP.dbo.TB_WXKD_LOG 
                       SET CONFIRMADO_FLAG = 0, CHAVE_LOTE = $minLote 
                       WHERE CONFIRMADO_FLAG = 1";
        
        $result = $this->sql->update($updateQuery);
        
        if ($result) {
            error_log("restoreConfirmedRecords - Successfully restored records to LOTE $minLote");
            return $minLote;
        } else {
            error_log("restoreConfirmedRecords - Failed to update records");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("restoreConfirmedRecords - Exception: " . $e->getMessage());
        return false;
    }
}

// NEW METHOD: Get count of confirmed records
public function getConfirmedRecordsCount() {
    try {
        $query = "SELECT COUNT(*) as TOTAL 
                 FROM PGTOCORSP.dbo.TB_WXKD_LOG 
                 WHERE CONFIRMADO_FLAG = 1";
        
        $result = $this->sql->select($query);
        
        if (!empty($result) && isset($result[0]['TOTAL'])) {
            return (int)$result[0]['TOTAL'];
        }
        
        return 0;
        
    } catch (Exception $e) {
        error_log("getConfirmedRecordsCount - Exception: " . $e->getMessage());
        return 0;
    }
}

// MODIFIED: Update the cardData method to only count non-confirmed records
public function getCardData() {
    try {
        $cardData = array();
        
        $cardData['cadastramento'] = $this->sql->qtdRows("
            SELECT A.Chave_Loja
            " . $this->baseJoins . "
            WHERE (B.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR C.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR D.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "' OR E.DT_CADASTRO>='" . Wxkd_Config::CUTOFF_DATE . "') 
            AND H.WXKD_FLAG = 0");
        
        $cardData['descadastramento'] = $this->sql->qtdRows("
            -- existing descadastramento query --
        ");
        
        // MODIFIED: Only count non-confirmed historico records
        $cardData['historico'] = $this->sql->qtdRows("
            SELECT CHAVE_LOTE FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CONFIRMADO_FLAG = 0");
        
        return $cardData;
        
    } catch (Exception $e) {
        throw new Exception("Erro ao buscar dados dos cards: " . $e->getMessage());
    }
}

// MODIFIED: Update the getHistoricoDetails method to filter by CONFIRMADO_FLAG
public function getHistoricoDetails($chaveLote) {
    try {
        $query = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG 
                 WHERE CHAVE_LOTE = " . (int)$chaveLote . " 
                 AND CONFIRMADO_FLAG = 0 
                 ORDER BY CHAVE_LOJA";
        
        $result = $this->sql->select($query);
        return $result;
        
    } catch (Exception $e) {
        error_log("getHistoricoDetails - Exception: " . $e->getMessage());
        return array();
    }
}
?>

-----------


<?php
// Add this method to the Wxkd_DashboardController class

public function ajaxRestoreConfirmedRecords() {
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    try {
        // Get count of confirmed records before restoring
        $confirmedCount = $this->model->getConfirmedRecordsCount();
        
        if ($confirmedCount === 0) {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<message>Nenhum registro confirmado encontrado para restaurar</message>';
            $xml .= '</response>';
            echo $xml;
            ob_end_flush();
            exit;
        }
        
        // Restore confirmed records
        $restoredLote = $this->model->restoreConfirmedRecords();
        
        if ($restoredLote !== false) {
            $xml = '<response>';
            $xml .= '<success>true</success>';
            $xml .= '<restoredCount>' . $confirmedCount . '</restoredCount>';
            $xml .= '<restoredLote>' . $restoredLote . '</restoredLote>';
            $xml .= '<message>Restauracao realizada com sucesso! ' . $confirmedCount . ' registro(s) restaurado(s) para o Lote ' . $restoredLote . '</message>';
            $xml .= '</response>';
        } else {
            $xml = '<response>';
            $xml .= '<success>false</success>';
            $xml .= '<message>Erro ao restaurar registros confirmados</message>';
            $xml .= '</response>';
        }
        
        echo $xml;
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<message>Erro interno: ' . addcslashes($e->getMessage(), '"<>&') . '</message>';
        $xml .= '</response>';
        echo $xml;
    }
    
    ob_end_flush();
    exit;
}

// Add this method to get confirmed records count for UI
public function ajaxGetConfirmedCount() {
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    
    try {
        $count = $this->model->getConfirmedRecordsCount();
        
        $xml = '<response>';
        $xml .= '<success>true</success>';
        $xml .= '<count>' . $count . '</count>';
        $xml .= '</response>';
        
        echo $xml;
        
    } catch (Exception $e) {
        $xml = '<response>';
        $xml .= '<success>false</success>';
        $xml .= '<message>Erro ao buscar contagem: ' . addcslashes($e->getMessage(), '"<>&') . '</message>';
        $xml .= '</response>';
        echo $xml;
    }
    
    ob_end_flush();
    exit;
}

// MODIFIED: Update the updateWxkdFlag method to set CONFIRMADO_FLAG = 1 when updating flags
public function updateWxkdFlag($records, $fullData = array(), $chaveLote = 0, $filtro = 'cadastramento') {
    $logs = array();
    
    try {
        $logs[] = "updateWxkdFlag START - Records count: " . count($records) . ", ChaveLote: $chaveLote, Filtro: $filtro";
        
        if (empty($records)) {
            $logs[] = "updateWxkdFlag - No records provided";
            $this->debugLogs = $logs;
            return false;
        }
        
        $updateCount = 0;
        $logInsertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        // Determine which flag to update based on filter
        $flagToUpdate = ($filtro === 'descadastramento') ? 'WXKD_FLAG_DES' : 'WXKD_FLAG';
        $logs[] = "updateWxkdFlag - Will update flag: $flagToUpdate for filter: $filtro";
        
        foreach ($records as $index => $record) {
            $codEmpresa = (int) $record['COD_EMPRESA'];
            $codLoja = (int) $record['COD_LOJA'];
            
            $logs[] = "updateWxkdFlag - Processing record #$index: CodEmpresa=$codEmpresa, CodLoja=$codLoja";
            
            if ($codEmpresa <= 0 || $codLoja <= 0) {
                $logs[] = "updateWxkdFlag - Invalid empresa/loja codes for record #$index";
                continue;
            }
            
            // Use MERGE statement to avoid race conditions and duplicates
            $mergeSql = "
                MERGE PGTOCORSP.dbo.tb_wxkd_flag AS target
                USING (SELECT $codEmpresa AS COD_EMPRESA, $codLoja AS COD_LOJA) AS source
                ON (target.COD_EMPRESA = source.COD_EMPRESA AND target.COD_LOJA = source.COD_LOJA)
                WHEN MATCHED THEN
                    UPDATE SET $flagToUpdate = 1
                WHEN NOT MATCHED THEN
                    INSERT (COD_EMPRESA, COD_LOJA, WXKD_FLAG, WXKD_FLAG_DES)
                    VALUES (source.COD_EMPRESA, source.COD_LOJA, 
                        " . ($flagToUpdate === 'WXKD_FLAG' ? '1' : '0') . ", 
                        " . ($flagToUpdate === 'WXKD_FLAG_DES' ? '1' : '0') . ");
            ";
            
            try {
                $result = $this->sql->update($mergeSql);
                
                if ($result) {
                    $updateCount++;
                    $logs[] = "updateWxkdFlag - Successfully processed $flagToUpdate for record #$index (MERGE)";
                } else {
                    $logs[] = "updateWxkdFlag - Failed to process $flagToUpdate for record #$index (MERGE)";
                }
            } catch (Exception $mergeEx) {
                $logs[] = "updateWxkdFlag - MERGE exception for record #$index: " . $mergeEx->getMessage();
                // ... fallback logic as before ...
            }
        }
        
        // MODIFIED: Log insertion logic with CONFIRMADO_FLAG = 1
        if (!empty($fullData) && $chaveLote > 0) {
            $logs[] = "updateWxkdFlag - Starting log insertion with " . count($fullData) . " records for filter: $filtro";
            
            foreach ($fullData as $index => $record) {
                // ... existing field extraction logic ...
                
                $logSql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                        (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                        TIPO_CORRESPONDENTE, DATA_CONCLUSAO, DATA_SOLICITACAO, DEP_DINHEIRO, DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, 
                        SEGUNDA_VIA_CARTAO, HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO, CONFIRMADO_FLAG) 
                        VALUES 
                        ($chaveLote, 
                        '$currentDateTime', 
                        $chaveLoja, 
                        '$nomeLoja', 
                        $codEmpresa, 
                        $codLoja, 
                        '$tipoCorrespondente',
                        $dataConclusao,
                        $dataSolicitacao,
                        $depDinheiro, $depCheque, $recRetirada, $saqueCheque, 
                        '$segundaVia', '$holeriteInss', '$consultaInss', '$provaVida', 
                        $dataContrato, 
                        '$tipoContrato', 
                        '$filtro', 
                        1)"; // CONFIRMADO_FLAG = 1 for new exports
                
                try {
                    $logResult = $this->sql->insert($logSql);
                    
                    if ($logResult) {
                        $logInsertCount++;
                        $logs[] = "updateWxkdFlag - Log insert SUCCESS for record #$index";
                    } else {
                        $logs[] = "updateWxkdFlag - Log insert FAILED for record #$index";
                    }
                } catch (Exception $logEx) {
                    $logs[] = "updateWxkdFlag - Log insert exception for record #$index: " . $logEx->getMessage();
                }
            }
        }
        
        $logs[] = "updateWxkdFlag END - Flag ($flagToUpdate) updates: $updateCount, Log inserts: $logInsertCount";
        $this->debugLogs = $logs;
        
        return $updateCount > 0;
        
    } catch (Exception $e) {
        $logs[] = "updateWxkdFlag - MAIN Exception: " . $e->getMessage();
        $this->debugLogs = $logs;
        return false;
    }
}
?>

----------

<?php
// MODIFIED: Update the switch case to handle new actions
switch($action) {
    case 'exportCSV':
        $controller->exportCSV();
        break;
    case 'exportTXT':
        $controller->exportTXT();
        break;
    case 'exportAccess':  
        $controller->exportAccess();
        break;
    case 'ajaxGetTableData':
        $controller->ajaxGetTableData();
        break;
    case 'ajaxGetHistoricoDetails':  
        $controller->ajaxGetHistoricoDetails();
        break;
    case 'ajaxRestoreConfirmedRecords':  // NEW ACTION
        $controller->ajaxRestoreConfirmedRecords();
        break;
    case 'ajaxGetConfirmedCount':  // NEW ACTION
        $controller->ajaxGetConfirmedCount();
        break;
    default:
        $data = $controller->index();
        $cardData = $data['cardData'];
        $tableData = $data['tableData'];
        $activeFilter = $data['activeFilter'];
        $contractChavesLookup = $data['contractChavesLookup'];
        break;
}

// ... existing HTML structure until the dropdown section ...

?>

<!-- MODIFIED: Add the restore button below the dropdown -->
<div class="col-md-4" style="position:relative; left:25%;" >
    <div class="d-flex justify-content-end align-items-center"  >
        <label for="itemsPerPage" class="me-2 text-sm" ></label>
        <select class="form-select form-select-sm" id="itemsPerPage" style="width: auto; cursor:pointer;">
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="50">50</option>
        </select>
        <span class="ms-2 text-sm"></span>
        
        <!-- NEW: Restore Confirmed Records Button -->
        <div style="margin-left: 15px;" id="restoreButtonContainer">
            <button type="button" class="btn btn-warning btn-sm" id="restoreConfirmedBtn" 
                    onclick="restoreConfirmedRecords()" style="display: none;">
                <i class="fa fa-undo"></i> Restaurar (<span id="confirmedCount">0</span>)
            </button>
        </div>
    </div>
</div>

<!-- Add this JavaScript section before closing body tag -->
<script type="text/javascript">
// Check and update confirmed count on page load and filter changes
function updateConfirmedCount() {
    if (FilterModule.currentFilter === 'historico') {
        $.get('wxkd.php?action=ajaxGetConfirmedCount')
            .done(function(xmlData) {
                try {
                    var $xml = $(xmlData);
                    var success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        var count = parseInt($xml.find('count').text()) || 0;
                        $('#confirmedCount').text(count);
                        
                        if (count > 0) {
                            $('#restoreConfirmedBtn').show();
                        } else {
                            $('#restoreConfirmedBtn').hide();
                        }
                    }
                } catch (e) {
                    console.error('Error updating confirmed count:', e);
                }
            })
            .fail(function() {
                console.error('Failed to get confirmed count');
            });
    } else {
        $('#restoreConfirmedBtn').hide();
    }
}

// Restore confirmed records function
function restoreConfirmedRecords() {
    if (!confirm('Tem certeza que deseja restaurar todos os registros confirmados? Esta acao nao pode ser desfeita.')) {
        return;
    }
    
    showLoading();
    $('#restoreConfirmedBtn').prop('disabled', true);
    
    $.get('wxkd.php?action=ajaxRestoreConfirmedRecords')
        .done(function(xmlData) {
            hideLoading();
            $('#restoreConfirmedBtn').prop('disabled', false);
            
            try {
                var $xml = $(xmlData);
                var success = $xml.find('success').text() === 'true';
                var message = $xml.find('message').text();
                
                if (success) {
                    var restoredCount = $xml.find('restoredCount').text();
                    var restoredLote = $xml.find('restoredLote').text();
                    
                    alert('Sucesso! ' + restoredCount + ' registro(s) restaurado(s) para o Lote ' + restoredLote);
                    
                    // Reload the historico data
                    FilterModule.loadTableData('historico');
                    
                    // Update confirmed count
                    setTimeout(function() {
                        updateConfirmedCount();
                    }, 1000);
                    
                } else {
                    alert('Erro: ' + message);
                }
                
            } catch (e) {
                console.error('Error processing restore response:', e);
                alert('Erro ao processar resposta do servidor');
            }
        })
        .fail(function() {
            hideLoading();
            $('#restoreConfirmedBtn').prop('disabled', false);
            alert('Erro na comunicacao com o servidor');
        });
}

// Update confirmed count when filter changes
$(document).ready(function() {
    // Initial check
    setTimeout(function() {
        updateConfirmedCount();
    }, 1000);
    
    // Monitor filter changes
    var originalApplyFilter = FilterModule.applyFilter;
    FilterModule.applyFilter = function(filter) {
        originalApplyFilter.call(this, filter);
        setTimeout(function() {
            updateConfirmedCount();
        }, 1500);
    };
    
    var originalLoadTableData = FilterModule.loadTableData;
    FilterModule.loadTableData = function(filter) {
        originalLoadTableData.call(this, filter);
        if (filter === 'historico') {
            setTimeout(function() {
                updateConfirmedCount();
            }, 1500);
        }
    };
});
</script>

<?php
// ... rest of existing HTML structure ...
?>


------------


// ADD this new module for handling confirmed records
const ConfirmedRecordsModule = {
    init: function() {
        this.updateConfirmedCount();
        this.bindEvents();
    },
    
    bindEvents: function() {
        // Update count when filter changes
        const originalApplyFilter = FilterModule.applyFilter;
        FilterModule.applyFilter = function(filter) {
            originalApplyFilter.call(this, filter);
            setTimeout(() => {
                ConfirmedRecordsModule.updateConfirmedCount();
            }, 1500);
        };
        
        const originalLoadTableData = FilterModule.loadTableData;
        FilterModule.loadTableData = function(filter) {
            originalLoadTableData.call(this, filter);
            if (filter === 'historico') {
                setTimeout(() => {
                    ConfirmedRecordsModule.updateConfirmedCount();
                }, 1500);
            }
        };
    },
    
    updateConfirmedCount: function() {
        if (FilterModule.currentFilter === 'historico') {
            $.get('wxkd.php?action=ajaxGetConfirmedCount')
                .done((xmlData) => {
                    try {
                        const $xml = $(xmlData);
                        const success = $xml.find('success').text() === 'true';
                        
                        if (success) {
                            const count = parseInt($xml.find('count').text()) || 0;
                            $('#confirmedCount').text(count);
                            
                            if (count > 0) {
                                $('#restoreConfirmedBtn').show();
                            } else {
                                $('#restoreConfirmedBtn').hide();
                            }
                        }
                    } catch (e) {
                        console.error('Error updating confirmed count:', e);
                    }
                })
                .fail(() => {
                    console.error('Failed to get confirmed count');
                });
        } else {
            $('#restoreConfirmedBtn').hide();
        }
    },
    
    restoreConfirmedRecords: function() {
        if (!confirm('Tem certeza que deseja restaurar todos os registros confirmados? Esta acao nao pode ser desfeita.')) {
            return;
        }
        
        showLoading();
        $('#restoreConfirmedBtn').prop('disabled', true);
        
        $.get('wxkd.php?action=ajaxRestoreConfirmedRecords')
            .done((xmlData) => {
                hideLoading();
                $('#restoreConfirmedBtn').prop('disabled', false);
                
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    const message = $xml.find('message').text();
                    
                    if (success) {
                        const restoredCount = $xml.find('restoredCount').text();
                        const restoredLote = $xml.find('restoredLote').text();
                        
                        alert(`Sucesso! ${restoredCount} registro(s) restaurado(s) para o Lote ${restoredLote}`);
                        
                        // Reload the historico data
                        FilterModule.loadTableData('historico');
                        
                        // Update confirmed count
                        setTimeout(() => {
                            this.updateConfirmedCount();
                        }, 1000);
                        
                    } else {
                        alert('Erro: ' + message);
                    }
                    
                } catch (e) {
                    console.error('Error processing restore response:', e);
                    alert('Erro ao processar resposta do servidor');
                }
            })
            .fail(() => {
                hideLoading();
                $('#restoreConfirmedBtn').prop('disabled', false);
                alert('Erro na comunicacao com o servidor');
            });
    }
};

// MODIFIED: Update the FilterModule to handle confirmed count updates
const FilterModule = {
    currentFilter: 'all',
    
    init: function() {
        this.currentFilter = window.currentFilter || 'all';
        this.updateFilterUI();
        
        $('.card-filter').on('click', this.handleCardClick.bind(this));
        
        if (this.currentFilter !== 'all') {
            this.setActiveCard(this.currentFilter);
            console.log('FilterModule.init - Loading data for initial filter:', this.currentFilter);
            this.loadTableData(this.currentFilter);
        }
    },
    
    handleCardClick: function(e) {
        const filter = $(e.currentTarget).data('filter');
        this.applyFilter(filter);
    },
    
    applyFilter: function(filter) {
        if (this.currentFilter === filter && filter !== 'all') {
            this.clearFilter();
            return;
        }
        
        this.currentFilter = filter;
        this.setActiveCard(filter);
        this.updateFilterUI();
        this.loadTableData(filter);
        
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('filter', filter);
        window.history.pushState({filter: filter}, '', newUrl);
    },

    clearFilter: function() {
        this.currentFilter = 'all';
        $('.card-filter').removeClass('active');
        $('#filterIndicator').fadeOut();
        
        $('.table-scrollable').show();
        $('#historicoAccordion').hide();
        $('.row.mt-3').show(); 
        
        this.loadTableData('all');
        
        const newUrl = new URL(window.location);
        newUrl.searchParams.delete('filter');
        window.history.pushState({filter: 'all'}, '', newUrl);
    },
    
    setActiveCard: function(filter) {
        $('.card-filter').removeClass('active');
        $(`#card-${filter}`).addClass('active');
    },
    
    updateFilterUI: function() {
        if (this.currentFilter === 'all') {
            $('#filterIndicator').hide();
        } else {
            const filterNames = {
                'cadastramento': 'Cadastramento',
                'descadastramento': 'Descadastramento',
                'historico': 'Histórico'
            };
            
            const filterDescriptions = {
                'cadastramento': 'Mostrando apenas lojas para cadastramento',
                'descadastramento': 'Mostrando apenas lojas para descadastramento',
                'historico': 'Mostrando histórico de processos realizados (apenas não confirmados)'
            };
            
            $('#activeFilterName').text(filterNames[this.currentFilter]);
            $('#filterDescription').text(filterDescriptions[this.currentFilter]);
            $('#filterIndicator').fadeIn();
        }
    },
    
    loadTableData: function(filter) {
        showLoading();
        
        if (filter === 'historico') {
            $('.table-scrollable').hide();
            $('#historicoAccordion').show();
            $('.row.mt-3').hide(); 
            $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
        } else {
            $('#historicoAccordion').hide();
            $('.table-scrollable').show();
            $('.row.mt-3').show(); 
            $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center">Carregando...</td></tr>');
        }

        $.get('wxkd.php?action=ajaxGetTableData&filter=' + filter)
            .done((xmlData) => {
                try {
                    const $xml = $(xmlData);
                    const success = $xml.find('success').text() === 'true';
                    
                    if (success) {
                        const cardData = {
                            cadastramento: $xml.find('cardData cadastramento').text(),
                            descadastramento: $xml.find('cardData descadastramento').text(),
                            historico: $xml.find('cardData historico').text()
                        };
                        
                        this.updateCardCounts(cardData);
                        
                        const newContractChaves = [];
                        $xml.find('contractChaves chave').each(function() {
                            const chave = $(this).text();
                            if (chave) {
                                newContractChaves.push(chave);
                            }
                        });
                        
                        if (typeof window.contractChaves !== 'undefined') {
                            window.contractChaves = newContractChaves;
                        } else {
                            window.contractChaves = newContractChaves;
                        }
                        
                        console.log('Updated contractChaves for filter ' + filter + ':', window.contractChaves);
                        
                        if (filter === 'historico') {
                            this.buildHistoricoAccordion($xml);
                            setTimeout(() => {
                                HistoricoCheckboxModule.init();
                                hideLoading();
                                // ADDED: Update confirmed count after loading historico
                                ConfirmedRecordsModule.updateConfirmedCount();
                            }, 100);
                        } else {
                            const tableData = [];
                            $xml.find('tableData row').each(function() {
                                const row = {};
                                $(this).children().each(function() {
                                    row[this.tagName] = $(this).text();
                                });
                                tableData.push(row);
                            });

                            PaginationModule.replaceTableDataEnhanced(tableData);
                            PaginationModule.currentPage = 1;
                            PaginationModule.updateTable();
                            
                            CheckboxModule.clearSelections();
                            
                            setTimeout(() => {
                                CheckboxModule.init();
                                CheckboxModule.updateSelectAllState();
                                CheckboxModule.updateExportButton();
                            }, 200);
                            
                            setTimeout(() => {
                                StatusFilterModule.reapplyAfterDataLoad();
                                hideLoading();
                            }, 400);
                        }
                    }
                } catch (e) {
                    console.error('Error parsing XML: ', e);
                    if (filter !== 'historico') {
                        $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao processar dados</td></tr>');
                    }
                    hideLoading();
                }
            })
            .fail((jqXHR, textStatus, errorThrown) => {
                console.error('AJAX failed:', textStatus, errorThrown);
                if (filter !== 'historico') {
                    $('#dataTableAndre tbody').html('<tr><td colspan="12" class="text-center text-danger">Erro ao carregar dados</td></tr>');
                }
                hideLoading();
            });
    },
    
    // ... rest of the existing FilterModule methods remain the same ...
    
    updateCardCounts: function(cardData) {
        $('#card-cadastramento .databox-number').text(cardData.cadastramento);
        $('#card-descadastramento .databox-number').text(cardData.descadastramento);
        $('#card-historico .databox-number').text(cardData.historico);
    },
    
    buildHistoricoAccordion: function($xml) {
        // ... existing buildHistoricoAccordion implementation ...
        // No changes needed here as the PHP query already filters by CONFIRMADO_FLAG = 0
    }
};

// Add the global function for the button onclick
function restoreConfirmedRecords() {
    ConfirmedRecordsModule.restoreConfirmedRecords();
}

// MODIFIED: Update the document ready function
$(document).ready(() => {
    if (typeof contractChaves !== 'undefined') {
        window.contractChaves = contractChaves;
    } else {
        window.contractChaves = [];
    }
    
    console.log('Initial contractChaves:', window.contractChaves);
    
    SearchModule.init();
    SortModule.init();
    PaginationModule.init();
    CheckboxModule.init(); 
    FilterModule.init();
    StatusFilterModule.init();
    HistoricoModule.init();
    HistoricoCheckboxModule.init();
    ConfirmedRecordsModule.init(); // ADDED: Initialize confirmed records module
    
    $('.card-filter').on('click', function() {
        const newFilter = $(this).data('filter');
        if (newFilter !== FilterModule.currentFilter) {
            $('#searchInput').val('');
            SearchModule.hideNoResultsMessage();
        }
    });
    
    $(document).on('hidden.bs.collapse', '.panel-collapse', function() {
        const $panel = $(this).closest('.panel');
        $panel.find('.search-highlight').each(function() {
            const $this = $(this);
            $this.replaceWith($this.text());
        });
        $panel.find('.search-highlight-row').removeClass('search-highlight-row');
    });
});