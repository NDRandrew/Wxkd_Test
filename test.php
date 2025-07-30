// Replace the formatDateKeepTime function in PaginationModule with this:

formatDateKeepTime: function(dateStr) {
    if (!dateStr || typeof dateStr !== 'string') {
        return dateStr;
    }
    
    // Handle SQL Server format: "Jul 2 2025 11:34AM"
    const sqlServerMatch = dateStr.match(/^([A-Za-z]{3}) (\d{1,2}) (\d{4}) (.+)$/);
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
    
    // Handle existing format: "MMM dd yyyy time"
    const parts = dateStr.match(/^([A-Za-z]+) (\d{1,2}) (\d{4}) (.+)$/);
    if (parts) {
        const months = {
            Jan: '01', Feb: '02', Mar: '03', Apr: '04',
            May: '05', Jun: '06', Jul: '07', Aug: '08',
            Sep: '09', Oct: '10', Nov: '11', Dec: '12'
        };

        const month = months[parts[1]];
        const day = ('0' + parts[2]).slice(-2);
        const year = parts[3];

        if (month) {
            return `${day}/${month}/${year}`;
        }
    }

    return dateStr;
},