Perfect! Now I can see the actual structure. Let me provide the correct implementation:

## 1. Add to `simulador_encerramento_model.class.php`

Add these two functions after the existing methods:

```php
public function getAIGLCount($month) {
    // $month format: YYYY-MM (e.g., "2025-10")
    error_log("SimuladorEncerramento::getAIGLCount - Month: " . $month);
    
    // TODO: Replace with actual query
    $query = "
        -- Your query here
        -- Use {$month} to filter by month/year
        -- Should return a single INT value
        SELECT COUNT(*) as total 
        FROM YOUR_TABLE 
        WHERE FORMAT(YOUR_DATE_COLUMN, 'yyyy-MM') = '{$month}'
        -- Add your conditions here
    ";
    
    $result = $this->sql->select($query);
    return $result ? intval($result[0]['total']) : 0;
}

public function getAgenciaCount($month) {
    // $month format: YYYY-MM (e.g., "2025-10")
    error_log("SimuladorEncerramento::getAgenciaCount - Month: " . $month);
    
    // TODO: Replace with actual query
    $query = "
        -- Your query here
        -- Use {$month} to filter by month/year
        -- Should return a single INT value
        SELECT COUNT(*) as total 
        FROM YOUR_TABLE 
        WHERE FORMAT(YOUR_DATE_COLUMN, 'yyyy-MM') = '{$month}'
        -- Add your conditions here
    ";
    
    $result = $this->sql->select($query);
    return $result ? intval($result[0]['total']) : 0;
}
```

## 2. Update `simulador_encerramento_control.php`

Modify the `handleGetMonthData` function:

```php
function handleGetMonthData($model) {
    $month = $_POST['month'];
    $data = $model->getMonthData($month);
    
    // Add AIGL and Agência counts
    $data['aigl_count'] = $model->getAIGLCount($month);
    $data['agencia_count'] = $model->getAgenciaCount($month);
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
}
```

## 3. Update `simulador_encerramento.php`

Add the display section after the "Values Display" card (around line 120):

```php
<!-- Values Display -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row text-center">
            <div class="col-6 mb-2">
                <small class="text-muted d-block">REAL_VALUE</small>
                <strong class="fs-3" id="realValue">0</strong>
            </div>
            <div class="col-6 mb-2">
                <small class="text-muted d-block">Inauguração</small>
                <strong class="fs-3 text-success" id="inauguracaoValue">0</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Cancelamento</small>
                <strong class="fs-3 text-danger" id="cancelamentoValue">0</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">TOTAL</small>
                <strong class="fs-3 text-primary" id="totalValue">0</strong>
            </div>
        </div>
    </div>
</div>

<!-- Add AIGL and Agência Display -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row text-center">
            <div class="col-6">
                <small class="text-muted d-block">AIGL</small>
                <strong class="fs-4" id="aiglValue">0</strong>
            </div>
            <div class="col-6">
                <small class="text-muted d-block">Agência</small>
                <strong class="fs-4" id="agenciaValue">0</strong>
            </div>
        </div>
    </div>
</div>
```

## 4. Update `simulador_encerramento.js`

Modify the `updateCurrentCase` function to include the new values:

```javascript
function updateCurrentCase(data) {
    // Update all active cases with the same month data
    activeCases.forEach(caseData => {
        caseData.realValue = data.real_value;
        caseData.inauguracao = data.inauguracao;
        caseData.aiglCount = data.aigl_count || 0;
        caseData.agenciaCount = data.agencia_count || 0;
        // Recalculate total
        caseData.total = caseData.realValue - caseData.cancelamento + caseData.inauguracao;
    });
    
    updateCaseDisplay();
    updateSimulationChart();
}
```

And update the `updateCaseDisplay` function:

```javascript
function updateCaseDisplay() {
    const currentCase = activeCases[currentCaseIndex];
    document.getElementById('realValue').textContent = currentCase.realValue.toLocaleString();
    document.getElementById('inauguracaoValue').textContent = currentCase.inauguracao.toLocaleString();
    document.getElementById('cancelamentoValue').textContent = currentCase.cancelamento.toLocaleString();
    document.getElementById('totalValue').textContent = currentCase.total.toLocaleString();
    
    // Update AIGL and Agência values
    document.getElementById('aiglValue').textContent = currentCase.aiglCount.toLocaleString();
    document.getElementById('agenciaValue').textContent = currentCase.agenciaCount.toLocaleString();
}
```

Now you can add your SQL queries to the two model functions (`getAIGLCount` and `getAgenciaCount`). The `$month` parameter will be in `YYYY-MM` format (e.g., `"2025-10"`).