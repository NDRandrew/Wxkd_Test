<!DOCTYPE html>
<html>
<head>
    <title>Automation Runner</title>
    <script>
        function runAutomation() {
            const serverIp = "192.168.1.100"; // Change to your Python server IP
            const button = document.getElementById("runBtn");
            const response = document.getElementById("response");
            
            button.disabled = true;
            button.textContent = "Running...";
            response.innerHTML = "<p>Processing...</p>";
            
            fetch(`http://${serverIp}:5000/run`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'}
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById("response").innerHTML = 
                        `<p style='color:red'>Error: ${data.error}</p>`;
                } else {
                    document.getElementById("response").innerHTML = 
                        `<h3>Results:</h3>
                         <p>Field 1: ${data.field1}</p>
                         <p>Field 2: ${data.field2}</p>
                         <p>Field 3: ${data.field3}</p>`;
                }
            })
            .catch(error => {
                document.getElementById("response").innerHTML = 
                    `<p style='color:red'>Connection error: ${error.message}</p>`;
            })
            .finally(() => {
                button.disabled = false;
                button.textContent = "Run Sequence";
            });
        }
    </script>
</head>
<body>
    <h2>Run Automation</h2>
    
    <button id="runBtn" onclick="runAutomation()">Run Sequence</button>
    
    <div id="response"></div>
</body>
</html>