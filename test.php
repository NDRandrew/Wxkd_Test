' write_sequence.vbs
Option Explicit

Dim session, ps, args
Dim user, pwd, outputField1, outputField2, outputField3

' 1) Read credentials from arguments
Set args = WScript.Arguments
If args.Count < 2 Then
    WScript.StdErr.Write "ERROR: Usage write_sequence.vbs <username> <password>"
    WScript.Quit 1
End If

user = args(0)
pwd  = args(1)

On Error Resume Next

' 2) Attach to session A
Set session = CreateObject("PCOMM.autECLSession")
If session Is Nothing Then
    WScript.StdErr.Write "ERROR: Cannot create autECLSession"
    WScript.Quit 1
End If

session.SetConnectionByName "A"
If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR: Attach session A – " & Err.Description
    WScript.Quit 1
End If
Err.Clear

' 3) Perform your existing keystroke sequence
Set ps = session.autECLPS
With ps
    .WaitForCursor 20, 1
    .SendKeys "CICSSP"
    .SendKeys "[Enter]"

    .WaitForCursor 18, 20
    .SendKeys "[PF4]"

    .WaitForCursor 15, 47
    .SendKeys user

    .WaitForCursor 16, 47
    .SendKeys pwd
    .SendKeys "[Enter]"

    .WaitForCursor 04, 07
    .SendKeys "ymlo"
    .SendKeys "[Enter]"
    .SendKeys "08"
    .SendKeys "[Enter]"

    .WaitForCursor 05, 14
    .SendKeys "163604"
    .SendKeys "[Enter]"
    
    .WaitForCursor 07, 14
    .SendKeys "00001"
    .SendKeys "[Enter]"

    .WaitForCursor 24, 9
    .SendKeys "1"
    .SendKeys "[Enter]"
    .SendKeys "[PF9]"
    .WaitForString "Saldo Operacional Efetivo"

End With

If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR during sequence – " & Err.Description
    WScript.Quit 1
End If
Err.Clear

' 4) Read 3 fields
outputField1 = ps.GetText(18, 53, 7)
outputField2 = ps.GetText(19, 53, 7)
outputField3 = ps.GetText(20, 53, 7)

If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR: GetText failed – " & Err.Description
    WScript.Quit 1
End If

' 5) Emit the 3 fields separated by pipes and exit
WScript.StdOut.Write outputField1 & "|" & outputField2 & "|" & outputField3
WScript.Quit 0


------------

import os
import subprocess
import sys
import time
from flask import Flask, request, jsonify

app = Flask(__name__)

def get_cscript_exe():
    windir = os.environ.get("SystemRoot", r"C:\Windows")
    path32 = os.path.join(windir, "SysWOW64", "cscript.exe")
    return path32 if os.path.isfile(path32) else os.path.join(windir, "System32", "cscript.exe")

def open_session_ws(ws_file, delay=5):
    if not os.path.isfile(ws_file):
        return False
    os.startfile(ws_file)
    time.sleep(delay)
    return True

def run_sequence(username, password, ws_file=None):
    here = os.path.dirname(__file__)
    vbs = os.path.join(here, "write_sequence.vbs")

    if ws_file:
        if not open_session_ws(ws_file):
            return {"error": f"Session file not found: {ws_file}"}

    if not os.path.exists(vbs):
        return {"error": f"VBScript not found: {vbs}"}

    cmd = [get_cscript_exe(), "//NoLogo", vbs, username, password]
    
    proc = subprocess.run(cmd, capture_output=True, text=True)
    if proc.returncode != 0:
        err = (proc.stderr or proc.stdout or "").strip()
        return {"error": f"VBScript error: {err}"}

    field_values = proc.stdout.strip().split("|")
    return {
        "field1": field_values[0] if len(field_values) > 0 else "",
        "field2": field_values[1] if len(field_values) > 1 else "",
        "field3": field_values[2] if len(field_values) > 2 else ""
    }

@app.route('/run', methods=['POST'])
def run_automation():
    USER = "I409841"
    PWD = "tino1202"
    WS_PATH = r"C:\Users\I458363\Desktop\VIDEO 2.ws"
    
    result = run_sequence(USER, PWD, ws_file=WS_PATH)
    return jsonify(result)

if __name__ == "__main__":
    app.run(host='127.0.0.1', port=5000, debug=True

-------------


<!DOCTYPE html>
<html>
<head>
    <title>Automation Runner</title>
</head>
<body>
    <h2>Run Automation</h2>
    
    <form method="post">
        <button type="submit" name="run">Run Sequence</button>
    </form>
    
    <div id="response">
        <?php
        if (isset($_POST['run'])) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:5000/run");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode == 200) {
                $data = json_decode($response, true);
                if (isset($data['error'])) {
                    echo "<p style='color:red'>Error: " . htmlspecialchars($data['error']) . "</p>";
                } else {
                    echo "<h3>Results:</h3>";
                    echo "<p>Field 1: " . htmlspecialchars($data['field1']) . "</p>";
                    echo "<p>Field 2: " . htmlspecialchars($data['field2']) . "</p>";
                    echo "<p>Field 3: " . htmlspecialchars($data['field3']) . "</p>";
                }
            } else {
                echo "<p style='color:red'>Connection error. Make sure Python server is running.</p>";
            }
        }
        ?>
    </div>
</body>
</html>