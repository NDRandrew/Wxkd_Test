import os
import subprocess
import sys
import time
import base64

def get_cscript_exe():
    windir = os.environ.get("SystemRoot", r"C:\Windows")
    path32 = os.path.join(windir, "SysWOW64", "cscript.exe")
    return path32 if os.path.isfile(path32) else os.path.join(windir, "System32", "cscript.exe")

def open_session_ws(ws_file, delay=3):
    if not os.path.isfile(ws_file):
        print(f"Session file not found: {ws_file}", file=sys.stderr)
        sys.exit(1)
    print(f"Launching session: {ws_file}")
    os.startfile(ws_file)
    print(f"Waiting {delay}s for emulator to come up…")
    time.sleep(delay)

def run_sequence(username, password_b64, token, codigoloja, chaveloja, ws_file=None):
    here = os.path.dirname(__file__)
    vbs  = os.path.join(here, "write_sequence.vbs")

    if ws_file:
        open_session_ws(ws_file)

    if not os.path.exists(vbs):
        print(f"VBScript not found: {vbs}", file=sys.stderr)
        sys.exit(1)

    try:
        raw = base64.b64decode(password_b64, validate=True)
        password = raw.decode("utf-8")
    except Exception as e:
        print(f"Invalid Base64 password: {e}", file=sys.stderr)
        sys.exit(1)

    cmd = [
        get_cscript_exe(),
        "//NoLogo",
        vbs,
        username,
        password,
        token,
        codigoloja,
        chaveloja
    ]

    proc = subprocess.run(cmd, capture_output=True, text=True)
    if proc.returncode != 0:
        err = (proc.stderr or proc.stdout or "").strip()
        print(f"VBScript error: {err}", file=sys.stderr)
        sys.exit(1)

    field_value = proc.stdout.strip()
    if field_value == "|         |":
        field_value = "CODIGO EXPIRADO"
        print(field_value)
    else:
        print(field_value)
    
    time.sleep(1)
    close_session()

def close_session():
    try:
        subprocess.run(["taskkill", "/f", "/im", "pcscm.exe"], capture_output=True)
    except:
        pass

if __name__ == "__main__":
    USER = "i458363"
    PWD_B64 = "b3V0dWJhYQ=="
    TOKEN = "027328"
    CODIGOLOJAIN = "00001"
    CHAVELOJA= "163604"

    WS_PATH = r"C:\Users\I458363\Desktop\VIDEO 2.ws"
    run_sequence(USER, PWD_B64, TOKEN, CODIGOLOJAIN, CHAVELOJA, ws_file=WS_PATH)

-----------


Option Explicit

Dim session, ps, args
Dim user, pwd, token, codigoloja, chaveloja, outputField1, outputField2, outputField3

Set args = WScript.Arguments
If args.Count < 2 Then
    WScript.StdErr.Write "ERROR: Usage write_sequence.vbs <username> <password>"
    WScript.Quit 1
End If

user = args(0)
pwd  = args(1)
token = args(2)
codigoloja = args(3)
chaveloja = args(4)

On Error Resume Next

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

Set ps = session.autECLPS
With ps
    If Not .WaitForCursor(20, 1, 3000) Then
        WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
        WScript.Quit 1
    End If
    .SendKeys "CICSSP"
    .SendKeys "[Enter]"

    If Not .WaitForCursor(18, 20, 3000) Then
        WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
        WScript.Quit 1
    End If
    .SendKeys "[PF4]"

    If Not .WaitForCursor(15, 47, 3000) Then
        WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
        WScript.Quit 1
    End If
    .SendKeys user

    If Not .WaitForCursor(16, 47, 3000) Then
        WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
        WScript.Quit 1
    End If
    .SendKeys pwd
    .SendKeys "[Enter]"

    Dim WaitString, RowNum, ColNum, TimeoutMs
    WaitString = "DIGITE ABAIXO A CHAVE INFORMADA NO SEU DISPOSITIVO DE SEGURANCA"
    RowNum     = 08
    ColNum     = 10
    TimeoutMs  = 3000

    If .WaitForString(WaitString, RowNum, ColNum, TimeoutMs) Then
        .SendKeys token
        .SendKeys "[Enter]"

            Dim WaitStringToken, RowNumToken, ColNumToken, TimeoutMsToken
            WaitStringToken = "DIGITE ABAIXO A CHAVE INFORMADA NO SEU DISPOSITIVO DE SEGURANCA"
            RowNumToken     = 08
            ColNumToken     = 10
            TimeoutMsToken  = 3000

            If .WaitForString(WaitStringToken, RowNumToken, ColNumToken, TimeoutMsToken) Then

                .SendKeys " "

            Else
                If Not .WaitForCursor(04, 07, 3000) Then
                    WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
                    WScript.Quit 1
                End If
                .SendKeys "ymlo"
                .SendKeys "[Enter]"
                .SendKeys "08"
                .SendKeys "[Enter]"

                If Not .WaitForCursor(05, 14, 3000) Then
                    WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
                    WScript.Quit 1
                End If
                .SendKeys "163604"
                .SendKeys "[Enter]"
                
                If Not .WaitForCursor(07, 14, 3000) Then
                    WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
                    WScript.Quit 1
                End If
                .SendKeys "00001"
                .SendKeys "[Enter]"

                If Not .WaitForCursor(24, 9, 3000) Then
                    WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
                    WScript.Quit 1
                End If
                .SendKeys "1"
                .SendKeys "[Enter]"
                .SendKeys "[PF9]"
                .WaitForString "Saldo Operacional Efetivo", 1, 1, 3000
                End If
    Else
        If Not .WaitForCursor(04, 07, 3000) Then
            WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
            WScript.Quit 1
        End If
        .SendKeys "ymlo"
        .SendKeys "[Enter]"
        .SendKeys "08"
        .SendKeys "[Enter]"

        If Not .WaitForCursor(05, 14, 3000) Then
            WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
            WScript.Quit 1
        End If
        .SendKeys "163604"
        .SendKeys "[Enter]"
        
        If Not .WaitForCursor(07, 14, 3000) Then
            WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
            WScript.Quit 1
        End If
        .SendKeys "00001"
        .SendKeys "[Enter]"

        If Not .WaitForCursor(24, 9, 3000) Then
            WScript.StdErr.Write "ERROR: Timeout waiting for cursor"
            WScript.Quit 1
        End If
        .SendKeys "1"
        .SendKeys "[Enter]"
        .SendKeys "[PF9]"
        .WaitForString "Saldo Operacional Efetivo", 1, 1, 3000
    End If

End With

If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR during sequence – " & Err.Description
    WScript.Quit 1
End If
Err.Clear

outputField1 = ps.GetText(18, 53, 7)
outputField2 = ps.GetText(19, 53, 7)
outputField3 = ps.GetText(20, 53, 7)

If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR: GetText failed – " & Err.Description
    WScript.Quit 1
End If

WScript.StdOut.Write outputField1 & " | " & outputField2 & " | " & outputField3

session.autECLConnMgr.StopCommunication
Set session = Nothing

WScript.Quit 0