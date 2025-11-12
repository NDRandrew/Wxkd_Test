<?php
require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\ClassRepository\geral\MSSQL\NEW_MSSQL.class.php');

#[AllowDynamicProperties]
class SimuladorEncerramento {
    private $sql;
    
    public function __construct() {
        $this->sql = new MSSQL('ERP');
    }
    
    public function getSql() {
        return $this->sql;
    }

    /**
     * QUERY DESCRIPTIONS NEEDED:
     * 
     * 1. QUERY_REAL_VALUE: Get total count for a given period
     *    Return: COUNT of existing correspondentes
     * 
     * 2. QUERY_INAUGURACAO: Get inaugurated count for period
     *    Return: COUNT of new correspondentes
     * 
     * 3. QUERY_CANCELAMENTO: Get cancelled count for period  
     *    Return: COUNT of cancelled correspondentes
     * 
     * 4. QUERY_CANCELAMENTO_TYPES: Get cancellation categories
     *    Return: List of cancellation reason types
     */
    
    public function getHistoricalData($month) {
        list($year, $monthNum) = explode('-', $month);
        $periods = $this->calculateHistoricalPeriods($year, $monthNum);
        
        $data = [];
        foreach ($periods as $period) {
            $realValue = $this->getRealValue($period['value']);
            $inauguracao = $this->getInauguracao($period['value']);
            $cancelamento = $this->getCancelamento($period['value']);
            
            $data[] = [
                'label' => $period['label'],
                'real_value' => $realValue,
                'inauguracao' => $inauguracao,
                'cancelamento' => $cancelamento,
                'total' => $realValue - $cancelamento + $inauguracao
            ];
        }
        
        return $data;
    }

    private function calculateHistoricalPeriods($year, $month) {
        $periods = [];
        
        // Previous month
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        $periods[] = [
            'label' => date('M/Y', mktime(0, 0, 0, $prevMonth, 1, $prevYear)),
            'value' => sprintf('%04d-%02d', $prevYear, $prevMonth),
            'type' => 'month'
        ];
        
        // Last 4 quarters
        $currentQuarter = ceil($month / 3);
        for ($i = 1; $i <= 4; $i++) {
            $quarter = $currentQuarter - $i;
            $qYear = $year;
            
            if ($quarter < 1) {
                $quarter += 4;
                $qYear--;
            }
            
            $qLabel = "Q{$quarter}/{$qYear}";
            $qMonth = ($quarter * 3);
            
            $periods[] = [
                'label' => $qLabel,
                'value' => sprintf('%04d-Q%d', $qYear, $quarter),
                'type' => 'quarter'
            ];
        }
        
        // Same month last year
        $periods[] = [
            'label' => date('M/Y', mktime(0, 0, 0, $month, 1, $year - 1)),
            'value' => sprintf('%04d-%02d', $year - 1, $month),
            'type' => 'month'
        ];
        
        return $periods;
    }

    private function getRealValue($period) {
        error_log("SimuladorEncerramento::getRealValue - Period: " . $period);
        
        // TODO: Replace with actual query
        $query = "
            SELECT COUNT(*) as total 
            FROM DATALAKE..DL_BRADESCO_EXPRESSO 
            WHERE BE_INAUGURADO = 1 
            AND FORMAT(DATA_INAUGURACAO, 'yyyy-MM') = '{$period}'
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result[0]['total'] : 0;
    }

    private function getInauguracao($period) {
        error_log("SimuladorEncerramento::getInauguracao - Period: " . $period);
        
        // TODO: Replace with actual query
        $query = "
            SELECT COUNT(*) as total 
            FROM DATALAKE..DL_BRADESCO_EXPRESSO 
            WHERE FORMAT(DATA_INAUGURACAO, 'yyyy-MM') = '{$period}'
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result[0]['total'] : 0;
    }

    private function getCancelamento($period) {
        error_log("SimuladorEncerramento::getCancelamento - Period: " . $period);
        
        // TODO: Replace with actual query
        $query = "
            SELECT COUNT(*) as total 
            FROM MESU..TB_LOJAS 
            WHERE FORMAT(DT_ENCERRAMENTO, 'yyyy-MM') = '{$period}'
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result[0]['total'] : 0;
    }

    public function getMonthData($month) {
        $realValue = $this->getRealValue($month);
        $inauguracao = $this->getInauguracao($month);
        
        return [
            'real_value' => $realValue,
            'inauguracao' => $inauguracao,
            'cancelamento' => 0
        ];
    }

    public function getCancelamentoTypes() {
        // TODO: Replace with actual query to get cancellation reason categories
        $query = "
            SELECT DISTINCT MOTIVO_ENCERRAMENTO 
            FROM MESU..ENCERRAMENTO_TB_PORTAL 
            WHERE MOTIVO_ENCERRAMENTO IS NOT NULL
            ORDER BY MOTIVO_ENCERRAMENTO
        ";
        
        $result = $this->sql->select($query);
        return $result;
    }

    public function saveCase($cod_func, $name, $month, $data) {
        $month = date('Y-m-01', strtotime($month));
        
        // Don't use addslashes on JSON - just escape single quotes for SQL
        $dataEscaped = str_replace("'", "''", $data);
        
        $query = "
            INSERT INTO MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            (COD_FUNC, NOME_CASO, MES_REF, DADOS_JSON, DATA_CAD) 
            VALUES (
                {$cod_func}, 
                '" . addslashes($name) . "', 
                '{$month}', 
                '{$dataEscaped}', 
                GETDATE()
            )
        ";
        
        return $this->sql->insert($query);
    }

    public function getSavedCases($cod_func) {
        $query = "
            SELECT 
                ID as id,
                NOME_CASO as name,
                MES_REF as month,
                DATA_CAD as created_at
            FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            WHERE COD_FUNC = {$cod_func}
            ORDER BY DATA_CAD DESC
        ";
        
        $result = $this->sql->select($query);
        return $result ? $result : [];
    }

    public function loadCase($case_id) {
        $query = "SELECT * FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS WHERE ID = " . intval($case_id);
        
        $result = $this->sql->select($query);
        
        if ($result && count($result) > 0) {
            $row = $result[0];
            
            // Try all possible column names (lowercase wins based on your output)
            $jsonData = $row['dados_json'] ?? $row['DADOS_JSON'] ?? $row['Dados_Json'] ?? null;
            
            if ($jsonData) {
                // CRITICAL FIX: Strip slashes from escaped JSON
                $jsonData = stripslashes($jsonData);
                
                $decoded = json_decode($jsonData, true);
                return $decoded;
            }
        }
        
        return null;
    }

    public function loadCaseWithMonth($case_id) {
        $query = "SELECT *, FORMAT(MES_REF, 'yyyy-MM') as MONTH_STR FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS WHERE ID = " . intval($case_id);
        
        $result = $this->sql->select($query);
        
        if ($result && count($result) > 0) {
            $row = $result[0];
            
            // Try all possible column name variations
            $month = $row['MONTH_STR'] ?? $row['month_str'] ?? $row['Month_Str'] ?? null;
            
            // Get JSON data
            $jsonData = $row['dados_json'] ?? $row['DADOS_JSON'] ?? $row['Dados_Json'] ?? null;
            
            if ($jsonData) {
                $jsonData = stripslashes($jsonData);
                $decoded = json_decode($jsonData, true);
                
                return [
                    'data' => $decoded,
                    'month' => $month
                ];
            }
        }
        
        return null;
    }

    public function deleteCase($case_id) {
        $query = "
            DELETE FROM MESU..TB_SIMULADOR_ENCERRAMENTO_CASOS 
            WHERE ID = " . intval($case_id);
        
        return $this->sql->delete($query);
    }

    public function insert($query) {
        return $this->sql->insert($query);
    }

    public function update($query) {
        return $this->sql->update($query);
    }

    public function delete($query) {
        return $this->sql->delete($query);
    }

    public function generatePDF($data) {
        require_once('\\\\D4920S010\D4920_2\Secoes\D4920S012\Comum_S012\Servidor_Portal_Expresso\Server2Go\htdocs\Lib\fpdf\fpdf.php');
        
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);
        
        // Title
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->Cell(0, 10, utf8_decode('Simulador de Encerramento'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, utf8_decode('Mês de Referência: ' . $data['month']), 0, 1, 'C');
        $pdf->Ln(5);
        
        // Historical Chart Image
        if (isset($data['charts']['historical']) && $data['charts']['historical']) {
            $this->addChartImage($pdf, $data['charts']['historical'], 'Histórico de Encerramentos');
            $pdf->Ln(5);
        }
        
        // Historical Section
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, utf8_decode('Histórico'), 0, 1);
        $pdf->Ln(2);
        
        // Historical table header
        $pdf->SetFillColor(172, 25, 71);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 7, utf8_decode('Período'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Real', 1, 0, 'C', true);
        $pdf->Cell(30, 7, utf8_decode('Inauguração'), 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Cancelamento', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'Total', 1, 1, 'C', true);
        
        // Historical table body
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('Arial', '', 9);
        foreach ($data['historical'] as $hist) {
            $pdf->Cell(40, 6, utf8_decode($hist['label']), 1, 0, 'L');
            $pdf->Cell(30, 6, number_format($hist['real_value'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($hist['inauguracao'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($hist['cancelamento'], 0, ',', '.'), 1, 0, 'R');
            $pdf->Cell(30, 6, number_format($hist['total'], 0, ',', '.'), 1, 1, 'R');
        }
        
        $pdf->Ln(8);
        
        // Cases Section
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 8, 'Casos Simulados', 0, 1);
        $pdf->Ln(2);
        
        // Simulation Chart Image
        if (isset($data['charts']['simulation']) && $data['charts']['simulation']) {
            $this->addChartImage($pdf, $data['charts']['simulation'], 'Simulação');
            $pdf->Ln(5);
        }
        
        foreach ($data['cases'] as $case) {
            // Case title
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 7, utf8_decode($case['name']), 0, 1);
            $pdf->Ln(1);
            
            // Case data table
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, 'Real Value:', 1, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(60, 6, number_format($case['realValue'], 0, ',', '.'), 1, 1, 'R');
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, utf8_decode('Inauguração:'), 1, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(60, 6, number_format($case['inauguracao'], 0, ',', '.'), 1, 1, 'R');
            
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(100, 6, 'Cancelamento:', 1, 0, 'L');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(60, 6, number_format($case['cancelamento'], 0, ',', '.'), 1, 1, 'R');
            
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(100, 7, 'Total:', 1, 0, 'L', true);
            $pdf->Cell(60, 7, number_format($case['total'], 0, ',', '.'), 1, 1, 'R', true);
            
            // Breakdown
            if (!empty($case['values'])) {
                $pdf->Ln(2);
                $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell(0, 6, 'Detalhamento:', 0, 1);
                $pdf->SetFont('Arial', '', 9);
                
                foreach ($case['values'] as $type => $value) {
                    if ($value > 0) {
                        $pdf->Cell(100, 5, utf8_decode('  ' . $type), 1, 0, 'L');
                        $pdf->Cell(60, 5, number_format($value, 0, ',', '.'), 1, 1, 'R');
                    }
                }
            }
            
            $pdf->Ln(5);
        }
        
        return $pdf->Output('S');
    }

    private function addChartImage($pdf, $base64Image, $title) {
        // Remove data:image/png;base64, prefix
        $base64Image = preg_replace('/^data:image\/[a-z]+;base64,/', '', $base64Image);
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'chart_') . '.png';
        file_put_contents($tempFile, base64_decode($base64Image));
        
        // Add title
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, utf8_decode($title), 0, 1, 'C');
        $pdf->Ln(2);
        
        // Add image (centered, max width 180mm)
        $pdf->Image($tempFile, 15, $pdf->GetY(), 180, 0, 'PNG');
        
        // Clean up
        unlink($tempFile);
        
        // Move Y position
        $pdf->Ln(5);
    }
}
?>
