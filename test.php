' IBM PCOMM Automation Script - Based on Working Access VBA Pattern
' This script follows the same pattern as the provided Access VBA code

Option Explicit

Dim Session, OIA, System, PS
Dim vSessao, Retval, ERRO
Dim screenText, row, col

' Configuration - Update these values
vSessao = "A"  ' Session name (usually A, B, C, etc.)
row = 1        ' Row to read from (1-based)
col = 1        ' Column to read from (1-based)

' Function to open PCOM session (based on your Access VBA)
Function AbrePCOM(sessionName)

On Error Resume Next

Set Session = CreateObject("AutSess")
Set OIA = CreateObject("AutOIA")
Set System = CreateObject("autSystem")

If Err.Number <> 0 Then
    WScript.Echo "Error creating PCOM objects: " & Err.Description
    ' Try alternative object creation
    Set Session = CreateObject("PCOMM.autECLSession")
    Set OIA = CreateObject("PCOMM.autECLOIA")
    Set System = CreateObject("PCOMM.autSystem")
    If Err.Number <> 0 Then
        WScript.Echo "Failed with alternative objects too: " & Err.Description
        AbrePCOM = False
        Exit Function
    End If
End If

Session.SetConnectionByName sessionName

If InStr(Err.Description, "objeto 'IAutSess' falhou") > 0 Or InStr(Err.Description, "invalid") > 0 Then
    WScript.Echo "Attempting to start PCOM Monitor..."
    Retval = System.Shell("C:\Program Files\Personal Communications\private\Monitor.WS", 1)
    If Retval = 0 Then
        ' Try alternative path
        Retval = System.Shell("C:\Arquivos de programas\Personal Communications\private\Monitor.WS", 1)
    End If
    WScript.Sleep 5000
    ' Try connection again
    Session.SetConnectionByName sessionName
End If

If Err.Number <> 0 Then
    ERRO = Err.Description
    If InStr(ERRO, "Sessão do host especificada inválida") > 0 Or InStr(ERRO, "invalid") > 0 Then
        WScript.Echo "ERROR: Invalid host session specified: " & sessionName
        WScript.Echo "Please open PCOM session '" & sessionName & "' manually and try again."
        AbrePCOM = False
        Exit Function
    Else
        WScript.Echo "Other error: " & Err.Description
        AbrePCOM = False
        Exit Function
    End If
End If

' Activate/Deactivate window metrics (from your code)
If Session.autECLWinMetrics.Active Then
    Session.autECLWinMetrics.Active = False
Else
    Session.autECLWinMetrics.Active = True
End If

OIA.SetConnectionByName sessionName

' Window management (commented out as in your code)
'If Session.autECLWinMetrics.Minimized Then
'    Session.autECLWinMetrics.Minimized = False
'Else
'    Session.autECLWinMetrics.Minimized = True
'End If

WScript.Echo "PCOM session '" & sessionName & "' opened successfully!"
AbrePCOM = True

End Function

' Main execution
WScript.Echo "Starting PCOM Automation..."
WScript.Echo "Attempting to connect to session: " & vSessao

If AbrePCOM(vSessao) Then
    
    ' Create Presentation Space object
    On Error Resume Next
    Set PS = CreateObject("AutPS")
    If Err.Number <> 0 Then
        Set PS = CreateObject("PCOMM.autECLPS")
    End If
    
    If Err.Number <> 0 Then
        WScript.Echo "Error creating Presentation Space object: " & Err.Description
        WScript.Quit 1
    End If
    
    PS.SetConnectionByName vSessao
    
    If Err.Number <> 0 Then
        WScript.Echo "Error connecting PS to session: " & Err.Description
        WScript.Quit 1
    End If
    
    ' Wait for session to be ready
    WScript.Echo "Waiting for session to be ready..."
    Do While OIA.InputInhibited <> 0
        WScript.Sleep 500
    Loop
    
    WScript.Echo "Session is ready for input!"
    
    ' Read text from specified position
    On Error Resume Next
    screenText = PS.GetText(row, col, 20)  ' Read 20 characters
    If Err.Number <> 0 Then
        WScript.Echo "Error reading from screen: " & Err.Description
    Else
        WScript.Echo "Text read from row " & row & ", col " & col & ": '" & Trim(screenText) & "'"
    End If
    
    ' Write text to screen at position (3, 1)
    On Error Resume Next
    PS.SetCursorPos 3, 1
    If Err.Number = 0 Then
        PS.SendKeys "Hello from VBS automation - " & Now()
        If Err.Number <> 0 Then
            WScript.Echo "Error writing text: " & Err.Description
        Else
            WScript.Echo "Successfully wrote text to screen at row 3, col 1"
        End If
    Else
        WScript.Echo "Error setting cursor position: " & Err.Description
    End If
    
    ' Send Enter key
    PS.SendKeys "[enter]"
    WScript.Sleep 1000
    
    ' Read a larger section for debugging
    Dim fullScreen
    On Error Resume Next
    fullScreen = PS.GetText(1, 1, 80 * 5)  ' Read first 5 lines
    If Err.Number = 0 Then
        WScript.Echo "Screen preview (first 100 chars): " & Left(fullScreen, 100)
    End If
    
    WScript.Echo "Automation completed successfully!"
    
Else
    WScript.Echo "Failed to open PCOM session. Exiting..."
    WScript.Quit 1
End If

' Clean up
Set Session = Nothing
Set OIA = Nothing
Set System = Nothing
Set PS = Nothing