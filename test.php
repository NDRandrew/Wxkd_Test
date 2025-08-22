<!-- EXISTING contractChaves script -->
            <script type="text/javascript">
                var contractChaves = [<?php
                    $first = true;
                    foreach (array_keys($contractChavesLookup) as $chave) {
                        if (!$first) echo ', ';
                        echo "'" . addslashes($chave) . "'";
                        $first = false;
                    }
                ?>];
                console.log(contractChaves)
            </script>
            
            <!-- FIXED accFlagData script -->
            <script type="text/javascript">
                var accFlagData = {<?php
                    if (is_array($tableData) && !empty($tableData)) {
                        $first = true;
                        foreach ($tableData as $index => $row) {
                            if (!$first) echo ',';
                            $chaveLoja = isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '';
                            $accFlag = isset($row['ACC_FLAG']) ? $row['ACC_FLAG'] : '0';
                            echo "'" . addslashes($chaveLoja) . "':'" . addslashes($accFlag) . "'";
                            $first = false;
                        }
                    }
                ?>};
                window.accFlagData = accFlagData;
                console.log('ACC_FLAG data loaded:', accFlagData);
            </script>