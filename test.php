// Replace the formatDate function in HistoricoModule with this:

formatDate: function(dateString) {
    if (!dateString || dateString === '') {
        return '';
    }
    
    // Handle SQL Server format: "Jul 2 2025 11:34AM"
    const sqlServerMatch = dateString.match(/^([A-Za-z]{3}) (\d{1,2}) (\d{4}) (.+)$/);
    if (sqlServerMatch) {
        const months = {
            Jan: '01', Feb: '02', Mar: '03', Apr: '04',
            May: '05', Jun: '06', Jul: '07', Aug: '08',
            Sep: '09', Oct: '10', Nov: '11', Dec: '12'
        };
        
        const monthName = sqlServerMatch[1];
        const day = ('0' + sqlServerMatch[2]).slice(-2);
        const year = sqlServerMatch[3];
        const month = months[monthName];
        
        if (month) {
            return `${day}/${month}/${year}`;
        }
    }
    
    // Handle existing "MMM dd yyyy time" format  
    const parts = dateString.match(/^([A-Za-z]+) (\d{1,2}) (\d{4}) (.+)$/);
    if (parts) {
        const months = {
            Jan: '01', Feb: '02', Mar: '03', Apr: '04',
            May: '05', Jun: '06', Jul: '07', Aug: '08',
            Sep: '09', Oct: '10', Nov: '11', Dec: '12'
        };
        
        const month = months[parts[1]];
        const day = ('0' + parts[2]).slice(-2);
        const year = parts[3];
        
        return month ? `${day}/${month}/${year}` : dateString;
    }
    
    // Handle dd/mm/yyyy format (already correct)
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        return dateString;
    }
    
    // Handle ISO format
    const date = new Date(dateString);
    if (!isNaN(date.getTime())) {
        const day = ('0' + date.getDate()).slice(-2);
        const month = ('0' + (date.getMonth() + 1)).slice(-2);
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }
    
    return dateString;
},