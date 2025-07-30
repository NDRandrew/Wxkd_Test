<td>
                                        <div class="status-container">
                                            <?php
                                            $fields = array(
                                                'AVANCADO' => 'AV',
                                                'ORGAO_PAGADOR' => 'OP',
                                                'PRESENCA' => 'PR',
                                                'UNIDADE_NEGOCIO' => 'UN'
                                            );

                                            $cutoff = mktime(0, 0, 0, 6, 1, 2025); // June 1, 2025

                                            if ($activeFilter === 'descadastramento') {
                                                $tipo = isset($row['TIPO_CORRESPONDENTE']) ? strtoupper(trim($row['TIPO_CORRESPONDENTE'])) : '';
                                                
                                                // Get DATA_CONCLUSAO for date checking
                                                $dataConclusao = isset($row['DATA_CONCLUSAO']) ? trim($row['DATA_CONCLUSAO']) : '';
                                                $dataConclusaoTimestamp = false;
                                                
                                                if (!empty($dataConclusao)) {
                                                    // Handle both dd/mm/yyyy and YYYY-MM-DD HH:MM:SS.mmm formats
                                                    $dateParts = explode('/', $dataConclusao);
                                                    if (count($dateParts) == 3) {
                                                        // dd/mm/yyyy format
                                                        $day = (int)$dateParts[0];
                                                        $month = (int)$dateParts[1];
                                                        $year = (int)$dateParts[2];
                                                        
                                                        if (checkdate($month, $day, $year)) {
                                                            $dataConclusaoTimestamp = mktime(0, 0, 0, $month, $day, $year);
                                                        }
                                                    } else {
                                                        // Try database format YYYY-MM-DD HH:MM:SS.mmm
                                                        $dataConclusaoTimestamp = strtotime($dataConclusao);
                                                    }
                                                }
                                                
                                                // Debug output - remove this after testing
                                                // echo "<!-- DEBUG: tipo=$tipo, dataConclusao=$dataConclusao, timestamp=$dataConclusaoTimestamp, cutoff=$cutoff -->";
                                                
                                                // Show all four indicators
                                                foreach ($fields as $field => $label) {
                                                    $isOn = false;
                                                    
                                                    if ($field === $tipo && $dataConclusaoTimestamp !== false && $dataConclusaoTimestamp > $cutoff) {
                                                        $isOn = true;
                                                    }
                                                    
                                                    $color = $isOn ? 'green' : 'gray';
                                                    $status = $isOn ? 'active' : 'inactive';
                                                    
                                                    // Debug title - remove this after testing
                                                    $debugTitle = "Field: $field, Tipo: $tipo, Match: " . ($field === $tipo ? 'YES' : 'NO') . 
                                                                 ", Timestamp: $dataConclusaoTimestamp, Cutoff: $cutoff, IsOn: " . ($isOn ? 'YES' : 'NO');
                                                    
                                                    echo '<div style="display:inline-block;width:30px;height:30px;
                                                            margin-right:5px;text-align:center;line-height:30px;
                                                            font-size:10px;font-weight:bold;color:white;
                                                            background-color:' . $color . ';border-radius:4px;" 
                                                            data-field="' . $field . '" data-status="' . $status . '" 
                                                            title="' . $debugTitle . '">' . $label . '</div>';
                                                }
                                            } else {
                                                // For other filters, use individual date fields as before
                                                foreach ($fields as $field => $label) {
                                                    $raw = isset($row[$field]) ? trim($row[$field]) : '';

                                                    $timestamp = false;
                                                    if (!empty($raw)) {
                                                        $parts = explode('/', $raw);
                                                        if (count($parts) == 3) {
                                                            $day = (int)$parts[0];
                                                            $month = (int)$parts[1];
                                                            $year = (int)$parts[2];

                                                            if (checkdate($month, $day, $year)) {
                                                                $timestamp = mktime(0, 0, 0, $month, $day, $year);
                                                            }
                                                        }
                                                    }

                                                    $isOn = $timestamp !== false && $timestamp > $cutoff;
                                                    $color = $isOn ? 'green' : 'gray';
                                                    $status = $isOn ? 'active' : 'inactive';

                                                    echo '<div style="display:inline-block;width:30px;height:30px;
                                                            margin-right:5px;text-align:center;line-height:30px;
                                                            font-size:10px;font-weight:bold;color:white;
                                                            background-color:' . $color . ';border-radius:4px;" 
                                                            data-field="' . $field . '" data-status="' . $status . '">' . $label . '</div>';
                                                }
                                            }
                                            ?>
                                        </div>
                                    </td>