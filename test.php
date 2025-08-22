<!-- Import do JavaScript -->
            <script src="../view/assets/js/Wxkd_script.js"></script>

            <!-- Current filter data -->
            <script>
                window.currentFilter = '<?php echo isset($activeFilter) ? $activeFilter : "all"; ?>';
            </script>

            <!-- Contract chaves data -->
            <script type="text/javascript">
                var contractChaves = [<?php
                    $first = true;
                    foreach (array_keys($contractChavesLookup) as $chave) {
                        if (!$first) echo ', ';
                        echo "'" . addslashes($chave) . "'";
                        $first = false;
                    }
                ?>];
                window.contractChaves = contractChaves;
                console.log('Contract chaves loaded:', contractChaves);
            </script>
            
            <!-- ACC_FLAG data -->
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

            <!-- Status toggle function -->
            <script>
                function toggleStatus(button) {
                    const span = button.querySelector('.status-indicator');
                    const isGreen = span.style.backgroundColor === 'rgb(33, 136, 56)' || span.style.backgroundColor === '#218838';
                    span.style.backgroundColor = isGreen ? 'gray' : '#218838';
                }
            </script>

            <!-- Confirmed records management -->
            <script type="text/javascript">
                function updateConfirmedCount() {
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
                }

                function restoreConfirmedRecords() {
                    if (!confirm('Confirmar?')) {
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
                                    setTimeout(function() {
                                        if (typeof CheckboxModule !== 'undefined') {
                                            CheckboxModule.clearSelections();
                                        }
                                        if (typeof FilterModule !== 'undefined') {
                                            var currentFilter = FilterModule.currentFilter;
                                            FilterModule.loadTableData(currentFilter);
                                        }
                                    }, 1000);

                                    setTimeout(function() {
                                        updateConfirmedCount();
                                    }, 1500);
                                    
                                } else {
                                    alert('Erro: ' + message);
                                    if (typeof FilterModule !== 'undefined') {
                                        FilterModule.loadTableData(FilterModule.currentFilter);
                                    }
                                }
                                
                            } catch (e) {
                                console.error('Error processing restore response:', e);
                                if (typeof FilterModule !== 'undefined') {
                                    FilterModule.loadTableData(FilterModule.currentFilter);
                                }
                            }
                        })
                        .fail(function() {
                            hideLoading();
                            $('#restoreConfirmedBtn').prop('disabled', false);
                            alert('Erro na comunicacao com o servidor');
                        });
                }

                $(document).ready(function() {
                    // Wait for modules to load before calling functions
                    setTimeout(function() {
                        updateConfirmedCount();
                    }, 1000);
                    
                    // Monitor filter changes if FilterModule is available
                    setTimeout(function() {
                        if (typeof FilterModule !== 'undefined') {
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
                                setTimeout(function() {
                                    updateConfirmedCount();
                                }, 1500);
                            };
                        }
                    }, 2000);
                });
            </script>

            <!-- Debug function -->
            <script type="text/javascript">
                window.debugAccFlag = function() {
                    console.log('=== ACC_FLAG DEBUG ===');
                    console.log('FilterModule available:', typeof FilterModule !== 'undefined');
                    console.log('ACC_FLAG Data:', window.accFlagData);
                    
                    $('#dataTableAndre tbody tr:visible').slice(0, 3).each(function(index) {
                        const $row = $(this);
                        const chaveLoja = $row.find('td:eq(1)').text();
                        const hasLock = $row.find('.fa-lock').length > 0;
                        const hasCheck = $row.find('.fa-check-circle').length > 0;
                        const accFlag = window.accFlagData ? window.accFlagData[chaveLoja] : 'unknown';
                        
                        console.log(`Row ${index}: ChaveLoja=${chaveLoja}, ACC_FLAG=${accFlag}, HasLock=${hasLock}, HasCheck=${hasCheck}`);
                    });
                    console.log('=== END ACC_FLAG DEBUG ===');
                };
            </script>

        </body>
    </div>
</div>