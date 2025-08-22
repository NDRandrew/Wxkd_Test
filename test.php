<script type="text/javascript">
                // Make ACC_FLAG data available to JavaScript for each row
                var accFlagData = {
                    <?php
                    if (is_array($tableData) && !empty($tableData)) {
                        $first = true;
                        foreach ($tableData as $index => $row) {
                            if (!$first) echo ', ';
                            $chaveLoja = isset($row['Chave_Loja']) ? $row['Chave_Loja'] : '';
                            $accFlag = isset($row['ACC_FLAG']) ? $row['ACC_FLAG'] : '0';
                            echo "'" . addslashes($chaveLoja) . "': '" . addslashes($accFlag) . "'";
                            $first = false;
                        }
                    }
                    ?>
                };
                
                // Make it globally available
                window.accFlagData = accFlagData;
                console.log('ACC_FLAG data loaded:', accFlagData);
            </script>