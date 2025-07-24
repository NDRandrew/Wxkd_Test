<?php
// In your Wxkd_DashboardController.php, in the exportTXT method, 
// add this code before calling updateWxkdFlag:

// Around line where you have:
// if (!empty($recordsToUpdate)) {
//     $updateResult = $this->model->updateWxkdFlag($recordsToUpdate, $data, $chaveLote, $filter);

if (!empty($recordsToUpdate)) {
    // ADDED: Ensure DATA_CONCLUSAO is properly populated from table data
    $data = $this->model->populateDataConclusaoFromTable($data);
    
    // Log the data structure for debugging
    error_log("exportTXT - Sample record structure: " . print_r(array_keys($data[0]), true));
    if (isset($data[0]['DATA_CONCLUSAO'])) {
        error_log("exportTXT - First record DATA_CONCLUSAO: " . $data[0]['DATA_CONCLUSAO']);
    }
    
    // Call the updated method with full data for logging
    $updateResult = $this->model->updateWxkdFlag($recordsToUpdate, $data, $chaveLote, $filter);
    
    // Rest of your existing code...
}

// Alternative: If DATA_CONCLUSAO should come from a specific column in your main query,
// you can also modify the base query in getTableDataByFilter to include it properly.
// 
// In your Wxkd_DashboardModel.php, in the baseSelectFields, you might need to add:
// CASE 
//     WHEN B.DT_CADASTRO IS NOT NULL AND B.DT_CADASTRO >= '20250601' THEN CONVERT(VARCHAR, B.DT_CADASTRO, 120)
//     WHEN C.DT_CADASTRO IS NOT NULL AND C.DT_CADASTRO >= '20250601' THEN CONVERT(VARCHAR, C.DT_CADASTRO, 120)
//     WHEN D.DT_CADASTRO IS NOT NULL AND D.DT_CADASTRO >= '20250601' THEN CONVERT(VARCHAR, D.DT_CADASTRO, 120)
//     WHEN E.DT_CADASTRO IS NOT NULL AND E.DT_CADASTRO >= '20250601' THEN CONVERT(VARCHAR, E.DT_CADASTRO, 120)
//     ELSE CONVERT(VARCHAR, GETDATE(), 120)
// END as DATA_CONCLUSAO,

// This would calculate DATA_CONCLUSAO as the latest cadastro date from any of the correspondent types
?>