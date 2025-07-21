public function insertLogEntries($records, $chaveLote, $filtro = 'cadastramento') {
    try {
        if (empty($records)) {
            return false;
        }
        
        $insertCount = 0;
        $currentDateTime = date('Y-m-d H:i:s');
        
        foreach ($records as $record) {
            // Use your existing qtdRows pattern but for insert
            $sql = "INSERT INTO PGTOCORSP.dbo.TB_WXKD_LOG 
                    (CHAVE_LOTE, DATA_LOG, CHAVE_LOJA, NOME_LOJA, COD_EMPRESA, COD_LOJA, 
                     TIPO_CORRESPONDENTE, DEP_DINHEIRO, DEP_CHEQUE, REC_RETIRADA, SAQUE_CHEQUE, 
                     SEGUNDA_VIA_CARTAO, HOLERITE_INSS, CONS_INSS, PROVA_DE_VIDA, DATA_CONTRATO, TIPO_CONTRATO, FILTRO) 
                    VALUES 
                    (" . (int)$chaveLote . ", 
                     '" . $currentDateTime . "', 
                     " . (int)$record['Chave_Loja'] . ", 
                     '" . str_replace("'", "''", $record['Nome_Loja']) . "', 
                     " . (int)$record['Cod_Empresa'] . ", 
                     " . (int)$record['Cod_Loja'] . ", 
                     '" . str_replace("'", "''", isset($record['TIPO_CORRESPONDENTE']) ? $record['TIPO_CORRESPONDENTE'] : '') . "', 
                     3000.00, 5000.00, 2000.00, 2000.00, 
                     'Apto', 'Apto', 'Apto', 'Apto', 
                     GETDATE(), 
                     '" . str_replace("'", "''", isset($record['TIPO_CONTRATO']) ? $record['TIPO_CONTRATO'] : '') . "', 
                     '" . $filtro . "')";
            
            // Use your existing SQL execution pattern
            if ($this->sql->insert($sql)) {
                $insertCount++;
            }
        }
        
        return $insertCount > 0;
        
    } catch (Exception $e) {
        error_log("insertLogEntries - Exception: " . $e->getMessage());
        return false;
    }
}

// Replace the historico case in getTableDataByFilter with this simple query:
case 'historico':
    $query = "SELECT CHAVE_LOTE, DATA_LOG, 
                     COUNT(*) as TOTAL_REGISTROS,
                     MIN(NOME_LOJA) as PRIMEIRO_NOME_LOJA,
                     'Cadastramento' as FILTRO
              FROM PGTOCORSP.dbo.TB_WXKD_LOG 
              GROUP BY CHAVE_LOTE, DATA_LOG 
              ORDER BY DATA_LOG DESC";
    break;Fix 2: Update HTML (TestH.txt) - Add the accordion right after your existing table:<?php if ($activeFilter === 'historico'): ?>
    <!-- Hide the regular table for historico -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if ('<?php echo $activeFilter; ?>' === 'historico') {
                document.getElementById('dataTableAndre').style.display = 'none';
            }
        });
    </script>
    
    <!-- Show accordion instead -->
    <div class="panel-group accordion" id="accordions">
        <?php if (is_array($tableData) && !empty($tableData)): ?>
            <?php foreach ($tableData as $index => $row): ?>
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <a class="accordion-toggle collapsed" data-toggle="collapse" 
                               data-parent="#accordions" href="#collapse<?php echo $row['CHAVE_LOTE']; ?>" 
                               aria-expanded="false">
                                <i class="fa-fw fa fa-history"></i> 
                                Lote #<?php echo $row['CHAVE_LOTE']; ?> - 
                                <?php 
                                $timestamp = strtotime($row['DATA_LOG']);
                                echo $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $row['DATA_LOG']; 
                                ?> - 
                                <?php echo $row['TOTAL_REGISTROS']; ?> registro(s) - 
                                <?php echo htmlspecialchars($row['PRIMEIRO_NOME_LOJA']); ?>
                            </a>
                        </h4>
                    </div>
                    <div id="collapse<?php echo $row['CHAVE_LOTE']; ?>" 
                         class="panel-collapse collapse" 
                         aria-expanded="false" style="height: 0px;">
                        <div class="panel-body border-red">
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Chave Loja</th>
                                            <th>Nome Loja</th>
                                            <th>Cod Empresa</th>
                                            <th>Cod Loja</th>
                                            <th>Data Log</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Get details for this CHAVE_LOTE
                                        $detailQuery = "SELECT * FROM PGTOCORSP.dbo.TB_WXKD_LOG WHERE CHAVE_LOTE = " . (int)$row['CHAVE_LOTE'];
                                        $details = $this->sql->select($detailQuery);
                                        if (is_array($details)):
                                            foreach($details as $detail):
                                        ?>
                                        <tr>
                                            <td><?php echo $detail['CHAVE_LOJA']; ?></td>
                                            <td><?php echo htmlspecialchars($detail['NOME_LOJA']); ?></td>
                                            <td><?php echo $detail['COD_EMPRESA']; ?></td>
                                            <td><?php echo $detail['COD_LOJA']; ?></td>
                                            <td><?php echo $detail['DATA_LOG']; ?></td>
                                        </tr>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i>
                Nenhum histórico encontrado.
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>Fix 3: Add this to your main PHP file (TestH.txt) where you have the controller instantiation:// After your existing controller code, add this:
if ($action == 'default' && $activeFilter === 'historico') {
    // For historico, we need to access the SQL connection directly
    require_once('../model/Wxkd_DashboardModel.php');
    $model = new Wxkd_DashboardModel();
    $model->Wxkd_Construct();
}