(function() {
    'use strict';
    
    const AJAX_URL = './control/encerramento_estat/estatistica_encerramento_control.php';
    
    function initialize() {
        const aplicarFiltrosBtn = document.getElementById('aplicarFiltros');
        if (aplicarFiltrosBtn) {
            aplicarFiltrosBtn.addEventListener('click', carregarDados);
        }
        
        // Auto-load data on page load
        setTimeout(() => {
            carregarDados();
        }, 200);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    function carregarDados() {
        const dataInicio = document.getElementById('dataInicio');
        const dataFim = document.getElementById('dataFim');
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        if (!dataInicio || !dataFim) {
            console.error('Date inputs not found');
            return;
        }
        
        if (!dataInicio.value || !dataFim.value) {
            showNotification('Por favor, selecione ambas as datas', 'warning');
            return;
        }
        
        if (loadingOverlay) loadingOverlay.classList.add('active');
        
        const formData = new FormData();
        formData.append('action', 'getMotivosBloqueio');
        formData.append('data_inicio', dataInicio.value);
        formData.append('data_fim', dataFim.value);
        
        fetch(AJAX_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderizarTabela(data.data);
                showNotification('Dados carregados com sucesso!', 'success');
            } else {
                showNotification('Erro: ' + (data.error || 'Erro desconhecido'), 'error');
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            showNotification('Erro ao carregar dados: ' + error.message, 'error');
        })
        .finally(() => {
            if (loadingOverlay) loadingOverlay.classList.remove('active');
        });
    }
    
    function renderizarTabela(data) {
        const thead = document.querySelector('#tabelaMotivosBloqueio thead tr');
        const tbody = document.getElementById('tableBody');
        const tfoot = document.querySelector('#tabelaMotivosBloqueio tfoot tr');
        
        if (!thead || !tbody || !tfoot) {
            console.error('Table elements not found');
            return;
        }
        
        // Clear existing monthly columns
        while (thead.children.length > 3) {
            thead.removeChild(thead.lastChild);
        }
        while (tfoot.children.length > 3) {
            tfoot.removeChild(tfoot.lastChild);
        }
        
        // Add monthly columns to header
        if (data.meses && data.meses.length > 0) {
            data.meses.forEach(mes => {
                const th = document.createElement('th');
                th.className = 'thead-estat text-center';
                th.textContent = formatarMes(mes);
                thead.appendChild(th);
            });
        }
        
        // Clear tbody
        tbody.innerHTML = '';
        
        // Add rows for each motivo
        const motivos = data.motivos || {};
        const totalGeral = data.total || 0;
        
        Object.keys(motivos).forEach(motivo => {
            const tr = document.createElement('tr');
            
            // Motivo column
            const tdMotivo = document.createElement('td');
            tdMotivo.textContent = motivo;
            tr.appendChild(tdMotivo);
            
            // Calculate total for this motivo
            let totalMotivo = 0;
            if (data.meses) {
                data.meses.forEach(mes => {
                    totalMotivo += motivos[motivo][mes] || 0;
                });
            }
            
            // Skip if no data for this motivo
            if (totalMotivo === 0) return;
            
            // QTDE column
            const tdQtde = document.createElement('td');
            tdQtde.className = 'text-center';
            tdQtde.textContent = totalMotivo;
            tr.appendChild(tdQtde);
            
            // % column
            const tdPerc = document.createElement('td');
            tdPerc.className = 'text-center';
            tdPerc.textContent = totalGeral > 0 ? ((totalMotivo / totalGeral) * 100).toFixed(1) + '%' : '0%';
            tr.appendChild(tdPerc);
            
            // Monthly columns
            if (data.meses) {
                data.meses.forEach(mes => {
                    const td = document.createElement('td');
                    td.className = 'text-center';
                    td.textContent = motivos[motivo][mes] || 0;
                    tr.appendChild(td);
                });
            }
            
            tbody.appendChild(tr);
        });
        
        if (tbody.children.length === 0) {
            const colspan = 3 + (data.meses ? data.meses.length : 0);
            tbody.innerHTML = '<tr><td colspan="' + colspan + '" class="text-center py-5">Nenhum dado encontrado para o período selecionado</td></tr>';
        }
        
        // Update total footer
        const totalQtdeEl = document.getElementById('totalQtde');
        if (totalQtdeEl) {
            totalQtdeEl.textContent = totalGeral;
        }
        
        // Add monthly totals to footer
        if (data.meses) {
            data.meses.forEach(mes => {
                let totalMes = 0;
                Object.keys(motivos).forEach(motivo => {
                    totalMes += motivos[motivo][mes] || 0;
                });
                
                const td = document.createElement('td');
                td.className = 'thead-estat text-center';
                td.textContent = totalMes;
                tfoot.appendChild(td);
            });
        }
    }
    
    function formatarMes(mesAno) {
        if (!mesAno) return '';
        const [ano, mes] = mesAno.split('-');
        const meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        const mesIndex = parseInt(mes) - 1;
        return meses[mesIndex] ? `${meses[mesIndex]}/${ano}` : mesAno;
    }
    
    function showNotification(message, type = 'info') {
        const container = document.createElement('div');
        const alertClass = type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'success';
        container.className = `alert alert-${alertClass} alert-dismissible fade show`;
        
        const existingNotifications = document.querySelectorAll('.alert[style*="position: fixed"]');
        const topOffset = 20 + (existingNotifications.length * 80);
        
        container.style.cssText = `position: fixed; top: ${topOffset}px; right: 20px; z-index: 9999; min-width: 300px; max-width: 500px; transition: all 0.3s ease;`;
        container.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(container);
        
        setTimeout(() => {
            container.style.opacity = '0';
            setTimeout(() => {
                container.remove();
                repositionNotifications();
            }, 300);
        }, 5000);
        
        const closeBtn = container.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                container.style.opacity = '0';
                setTimeout(() => {
                    container.remove();
                    repositionNotifications();
                }, 300);
            });
        }
    }
    
    function repositionNotifications() {
        const notifications = document.querySelectorAll('.alert[style*="position: fixed"]');
        notifications.forEach((notif, index) => {
            notif.style.top = (20 + (index * 80)) + 'px';
        });
    }
})();