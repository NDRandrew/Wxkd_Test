' IBM PCOMM Automation Script - Connects to EXISTING PCOMM Session
' IMPORTANT: PCOMM must be running with an active session BEFORE running this script

Option Explicit

Dim Session, OIA, System, PS
Dim vSessao, Retval, ERRO
Dim screenText, row, col

' Configuration - Update these values
vSessao = "A"  ' Session name (usually A, B, C, etc.) - MUST EXIST in PCOMM
row = 1        ' Row to read from (1-based)
col = 1        ' Column to read from (1-based)

' Function to connect to existing PCOM session
Function AbrePCOM(sessionName)

On Error Resume Next

WScript.Echo "Attempting to connect to existing PCOMM session: " & sessionName

' Try to connect to existing session objects (these only work when PCOMM is running)
Set Session = GetObject(, "AutSess")
If Err.Number <> 0 Then
    WScript.Echo "Error: Cannot connect to PCOMM session objects."
    WScript.Echo "Make sure PCOMM is running with an active session!"
    WScript.Echo "Error details: " & Err.Description
    AbrePCOM = False
    Exit Function
End If

Set OIA = GetObject(, "AutOIA")
If Err.Number <> 0 Then
    WScript.Echo "Error connecting to OIA: " & Err.Description
    AbrePCOM = False
    Exit Function
End If

Set System = GetObject(, "autSystem")
If Err.Number <> 0 Then
    ' System object might not be critical, continue
    Err.Clear
End If

' Connect to the specific session
Session.SetConnectionByName sessionName
If Err.Number <> 0 Then
    ERRO = Err.Description
    WScript.Echo "Error connecting to session '" & sessionName & "': " & ERRO
    
    If InStr(ERRO, "Sessão do host especificada inválida") > 0 Or InStr(ERRO, "invalid") > 0 Then
        WScript.Echo "ERROR: Session '" & sessionName & "' does not exist in PCOMM!"
        WScript.Echo "Please verify the session name in PCOMM and that it's connected."
        AbrePCOM = False
        Exit Function
    Else
        WScript.Echo "Connection error: " & ERRO
        AbrePCOM = False
        Exit Function
    End If
End If

' Connect OIA to the session
OIA.SetConnectionByName sessionName
If Err.Number <> 0 Then
    WScript.Echo "Warning: Could not connect OIA to session: " & Err.Description
    ' Continue anyway, OIA might still work
    Err.Clear
End If

' Check if session window is active (from your original code)
On Error Resume Next
If Session.autECLWinMetrics.Active Then
    Session.autECLWinMetrics.Active = False
Else  
    Session.autECLWinMetrics.Active = True
End If
Err.Clear

WScript.Echo "Successfully connected to PCOMM session '" & sessionName & "'!"
AbrePCOM = True

End Function

' Main execution
WScript.Echo "=" * 50
WScript.Echo "PCOMM Automation - Connecting to Existing Session"
WScript.Echo "=" * 50
WScript.Echo ""
WScript.Echo "PREREQUISITES:"
WScript.Echo "1. PCOMM must be running"
WScript.Echo "2. Session '" & vSessao & "' must exist and be connected"
WScript.Echo "3. Session should be ready for input"
WScript.Echo ""

If AbrePCOM(vSessao) Then
    
    ' Connect to Presentation Space for the session
    On Error Resume Next
    Set PS = GetObject(, "AutPS")
    If Err.Number <> 0 Then
        WScript.Echo "Error connecting to Presentation Space: " & Err.Description
        WScript.Echo "Make sure PCOMM session is fully loaded and ready."
        WScript.Quit 1
    End If
    
    PS.SetConnectionByName vSessao
    If Err.Number <> 0 Then
        WScript.Echo "Error connecting PS to session '" & vSessao & "': " & Err.Description
        WScript.Quit 1
    End If
    
    ' Wait for session to be ready
    WScript.Echo "Checking if session is ready for input..."
    Dim waitCount
    waitCount = 0
    
    On Error Resume Next
    Do While OIA.InputInhibited <> 0 And waitCount < 20
        WScript.Echo "Waiting... (InputInhibited = " & OIA.InputInhibited & ")"
        WScript.Sleep 500
        waitCount = waitCount + 1
    Loop
    
    If waitCount >= 20 Then
        WScript.Echo "Warning: Session may not be ready (timeout waiting for InputInhibited = 0)"
    Else
        WScript.Echo "Session is ready for input!"
    End If
    
    ' Read text from specified position
    WScript.Echo ""
    WScript.Echo "Reading screen data..."
    On Error Resume Next
    screenText = PS.GetText(row, col, 20)  ' Read 20 characters
    If Err.Number <> 0 Then
        WScript.Echo "Error reading from screen: " & Err.Description
    Else
        WScript.Echo "Text at row " & row & ", col " & col & ": '" & Trim(screenText) & "'"
    End If
    
    ' Get screen dimensions
    Dim screenRows, screenCols
    screenRows = PS.NumRows
    screenCols = PS.NumCols
    WScript.Echo "Screen size: " & screenRows & " rows x " & screenCols & " columns"
    
    ' Write text to screen at position (3, 1)
    WScript.Echo ""
    WScript.Echo "Writing to screen..."
    On Error Resume Next
    PS.SetCursorPos 3, 1
    If Err.Number <> 0 Then
        WScript.Echo "Error setting cursor position: " & Err.Description
    Else
        PS.SendKeys "Test from VBS - " & Now()
        If Err.Number <> 0 Then
            WScript.Echo "Error writing text: " & Err.Description
        Else
            WScript.Echo "Successfully wrote text to row 3, col 1"
        End If
    End If
    
    ' Send Tab key to move cursor
    PS.SendKeys "[tab]"
    WScript.Sleep 500
    
    ' Show current cursor position
    Dim curRow, curCol
    curRow = PS.CursorPosRow
    curCol = PS.CursorPosCol
    WScript.Echo "Current cursor position: row " & curRow & ", col " & curCol
    
    ' Read a section of the screen for verification
    WScript.Echo ""
    WScript.Echo "Screen sample (first 3 lines):"
    Dim sampleText
    On Error Resume Next
    sampleText = PS.GetText(1, 1, screenCols * 3)  ' First 3 lines
    If Err.Number = 0 Then
        ' Split into lines and show first few
        Dim lines, i
        For i = 0 To 2
            Dim lineStart, lineText
            lineStart = (i * screenCols) + 1
            If lineStart <= Len(sampleText) Then
                lineText = Mid(sampleText, lineStart, screenCols)
                WScript.Echo "Line " & (i + 1) & ": " & Left(Trim(lineText), 60) & "..."
            End If
        Next
    End If
    
    WScript.Echo ""
    WScript.Echo "Automation completed successfully!"
    
Else
    WScript.Echo ""
    WScript.Echo "Failed to connect to PCOMM session."
    WScript.Echo ""
    WScript.Echo "TROUBLESHOOTING:"
    WScript.Echo "1. Open PCOMM manually"
    WScript.Echo "2. Verify session '" & vSessao & "' exists and is connected"
    WScript.Echo "3. Make sure the host session is active and responding"
    WScript.Echo "4. Try running this script as Administrator"
    WScript.Quit 1
End If

' Clean up
Set Session = Nothing
Set OIA = Nothing  
Set System = Nothing
Set PS = Nothing