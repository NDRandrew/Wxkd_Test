// Replace the parseDate function in PaginationModule with this updated version:

parseDate: function(dateString) {
    if (!dateString || typeof dateString !== 'string') {
        return null;
    }
    
    dateString = dateString.trim();
    
    // Handle SQL Server datetime format: "Jul 2 2025 11:34AM"
    const sqlServerMatch = dateString.match(/^([A-Za-z]{3}) (\d{1,2}) (\d{4}) (.+)$/);
    if (sqlServerMatch) {
        const months = {
            Jan: 0, Feb: 1, Mar: 2, Apr: 3, May: 4, Jun: 5,
            Jul: 6, Aug: 7, Sep: 8, Oct: 9, Nov: 10, Dec: 11
        };
        
        const monthName = sqlServerMatch[1];
        const day = parseInt(sqlServerMatch[2], 10);
        const year = parseInt(sqlServerMatch[3], 10);
        
        if (months.hasOwnProperty(monthName)) {
            const month = months[monthName];
            const date = new Date(year, month, day);
            
            // Validate the date
            if (date.getFullYear() === year && date.getMonth() === month && date.getDate() === day) {
                console.log(`SQL Server date parsed: ${dateString} -> ${date}`);
                return date;
            }
        }
    }
    
    // Handle dd/mm/yyyy format
    const parts = dateString.split('/');
    if (parts.length === 3) {
        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1; // JavaScript months are 0-based
        const year = parseInt(parts[2], 10);
        
        if (isNaN(day) || isNaN(month) || isNaN(year)) {
            return null;
        }
        
        const date = new Date(year, month, day);
        
        // Validate the date
        if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) {
            return null;
        }
        
        console.log(`DD/MM/YYYY date parsed: ${dateString} -> ${date}`);
        return date;
    }
    
    // Handle ISO format (YYYY-MM-DD with optional time)
    if (dateString.match(/^\d{4}-\d{2}-\d{2}(\s+\d{2}:\d{2}:\d{2})?/)) {
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            console.log(`ISO date parsed: ${dateString} -> ${date}`);
            return date;
        }
    }
    
    // Try generic Date parsing as last resort
    const date = new Date(dateString);
    if (!isNaN(date.getTime())) {
        console.log(`Generic date parsed: ${dateString} -> ${date}`);
        return date;
    }
    
    console.warn(`Could not parse date: ${dateString}`);
    return null;
},