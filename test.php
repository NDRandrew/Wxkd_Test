<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Date Input Format</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .date-input-wrapper {
            position: relative;
            width: 100%;
        }
        input[type="date"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            opacity: 0;
            cursor: pointer;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            background: white;
            pointer-events: none;
        }
        input[type="date"]:focus + input[type="text"] {
            border-color: #4CAF50;
        }
        .hint {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        button {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #45a049;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 4px;
            display: none;
        }
        .result.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Date Input with Calendar</h2>
        <form id="dateForm">
            <div class="form-group">
                <label for="dateInput">Select Date:</label>
                <div class="date-input-wrapper">
                    <!-- Native date input for calendar picker (hidden but functional) -->
                    <input 
                        type="date" 
                        id="dateInput" 
                        name="dateValue"
                    >
                    <!-- Display input showing dd-mm-yyyy format -->
                    <input 
                        type="text" 
                        id="dateDisplay" 
                        placeholder="DD-MM-YYYY"
                        readonly
                    >
                </div>
                <div class="hint">Click to open calendar picker</div>
            </div>
            
            <button type="submit">Submit</button>
        </form>

        <div id="result" class="result">
            <strong>Submitted Data:</strong><br>
            Display Format: <span id="displayDate"></span><br>
            Submit Format (yyyy-mm-dd): <span id="submitDate"></span>
        </div>
    </div>

    <script>
        const dateInput = document.getElementById('dateInput');
        const dateDisplay = document.getElementById('dateDisplay');
        const dateForm = document.getElementById('dateForm');
        const result = document.getElementById('result');

        // Convert yyyy-mm-dd to dd-mm-yyyy
        function formatDateForDisplay(dateStr) {
            if (!dateStr) return '';
            const [year, month, day] = dateStr.split('-');
            return `${day}-${month}-${year}`;
        }

        // Update display when date is selected
        dateInput.addEventListener('change', function() {
            dateDisplay.value = formatDateForDisplay(this.value);
        });

        // Also update on input (for better UX on some browsers)
        dateInput.addEventListener('input', function() {
            dateDisplay.value = formatDateForDisplay(this.value);
        });

        // Handle form submission
        dateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!dateInput.value) {
                alert('Please select a date');
                return;
            }

            // Display the results
            document.getElementById('displayDate').textContent = dateDisplay.value;
            document.getElementById('submitDate').textContent = dateInput.value;
            result.classList.add('show');

            // Here you would normally submit the form
            console.log('Display format:', dateDisplay.value);
            console.log('Submit format (yyyy-mm-dd):', dateInput.value);
            
            // Example: Send to server
            // const formData = new FormData();
            // formData.append('date', dateInput.value); // yyyy-mm-dd
            // fetch('/api/submit', { method: 'POST', body: formData });
        });

        // Set today's date as default (optional)
        // const today = new Date().toISOString().split('T')[0];
        // dateInput.value = today;
        // dateDisplay.value = formatDateForDisplay(today);
    </script>
</body>
</html>