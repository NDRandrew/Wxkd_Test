Option Explicit

' ===============================
' === CONFIGURE YOUR LAYOUT  ====
' ===============================
Const ROW_START  = 7      ' First data row
Const ROW_END    = 18     ' Last row to probe for data
Const LINE_WIDTH = 80     ' Full line width

' Column positions for validation
Const COL_CHECK  = 4      ' Column to check if row has data
Const COL_FILTER = 80     ' Column to check if row should be excluded

' Token prompt string and end-of-sample marker
Const TOKEN_PROMPT_STR    = "DIGITE ABAIXO A CHAVE INFORMADA NO SEU DISPOSITIVO DE SEGURANCA"
Const AMOSTRAGEM_END_STR  = "FIM DA AMOSTRAGEM"
Const AMOSTRAGEM_ROW      = 24
Const AMOSTRAGEM_COL      = 8

' Debug mode - set to True to see progress messages on stderr
Const DEBUG_MODE = False

' ===============================
' ============= MAIN ============
' ===============================
Dim args, user, pwd, token, codigoloja
Dim session, ps
Dim i, chaveCount, chaveList()
Dim allOutputs, perChaveOutput

Set args = WScript.Arguments
If args.Count < 5 Then
    WScript.StdErr.Write "ERROR: Usage: cscript //nologo Base_Contas_PJ_read.vbs <username> <password> <token-or-dash> <codigoloja> <chaveLoja1> [<chaveLoja2> ...]" & vbCrLf
    WScript.Quit 1
End If

user        = args(0)
pwd         = args(1)
token       = args(2)      ' Pass "-" to skip token
codigoloja  = args(3)

chaveCount = args.Count - 4
ReDim chaveList(chaveCount - 1)
For i = 0 To chaveCount - 1
    chaveList(i) = args(4 + i)
Next

On Error Resume Next

' ==== Attach PCOMM session A ====
Set session = CreateObject("PCOMM.autECLSession")
If session Is Nothing Then
    WScript.StdErr.Write "ERROR: Cannot create autECLSession" & vbCrLf
    WScript.Quit 1
End If

session.SetConnectionByName "A"
If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR: Attach session A – " & Err.Description & vbCrLf
    WScript.Quit 1
End If
Err.Clear

Set ps = session.autECLPS

' ==== Open and login ====
If Not ps.WaitForCursor(20, 1, 10000) Then
    WScript.StdErr.Write "ERROR: Timeout to open IBM PCOMM" & vbCrLf
    Cleanup session
    WScript.Quit 1
End If

ps.SendKeys "IMS12"
ps.SendKeys "[Enter]"
ps.SendKeys "[PF4]"

If Not ps.WaitForCursor(15, 46, 10000) Then
    WScript.StdErr.Write "ERROR: Usuario ou senha incorreta (usuario field)" & vbCrLf
    Cleanup session
    WScript.Quit 1
End If
ps.SendKeys user

If Not ps.WaitForCursor(16, 46, 10000) Then
    WScript.StdErr.Write "ERROR: Usuario ou senha incorreta (senha field)" & vbCrLf
    Cleanup session
    WScript.Quit 1
End If
ps.SendKeys pwd
ps.SendKeys "[Enter]"

' Optional token step
If token <> "-" Then
    If WaitForString(ps, TOKEN_PROMPT_STR, 07, 10, 8000) Then
        ps.SendKeys token
        ps.SendKeys "[Enter]"
        If WaitForString(ps, TOKEN_PROMPT_STR, 07, 10, 4000) Then
            WScript.StdErr.Write "ERROR: Token incorreto" & vbCrLf
            Cleanup session
            WScript.Quit 1
        End If
    End If
End If

' Ensure command area (cursor at 4,7) before starting queries
If Not EnsureCommandArea(ps, 4, 06, 10000) Then
    WScript.StdErr.Write "ERROR: Nao foi possivel chegar na area de comando" & vbCrLf
    Cleanup session
    WScript.Quit 1
End If

allOutputs = ""

' ===============================
' Loop each ChaveLoja
' ===============================
For i = 0 To UBound(chaveList)
    perChaveOutput = HandleOneChave(ps, codigoloja, CStr(chaveList(i)))
    If perChaveOutput = "" Then perChaveOutput = chaveList(i) & ": "

    If allOutputs <> "" Then allOutputs = allOutputs & ", "
    allOutputs = allOutputs & perChaveOutput

    ' After finishing this chave, return to command area for next chave
    EnsureCommandArea ps, 4, 6, 5000
Next

If Err.Number <> 0 Then
    WScript.StdErr.Write "ERROR during sequence – " & Err.Description & vbCrLf
    Cleanup session
    WScript.Quit 1
End If

WScript.StdOut.Write allOutputs
Cleanup session
WScript.Quit 0

' ===============================
' ========= FUNCTIONS ===========
' ===============================

Function HandleOneChave(psObj, codigoLojaValue, chaveLojaValue)
    Dim rowOutputs

    ' Go to transaction and enter chave
    psObj.SendKeys "clie"
    psObj.SendKeys "[Enter]"
    psObj.WaitForCursor 1, 1, 3000

    ' Two Tabs then enter chave da loja
    psObj.SendKeys "[Tab]"
    psObj.SendKeys "[Tab]"
    psObj.SendKeys chaveLojaValue
    psObj.SendKeys "[Enter]"

    ' Wait just a moment for screen to update (not for cursor)
    WScript.Sleep 300

    ' Collect ONLY first page for testing
    rowOutputs = CollectOnePageRows(psObj)

    HandleOneChave = chaveLojaValue & ": " & rowOutputs
End Function

' TEMPORARILY DISABLED FOR TESTING - Multi-page collection
' Function CollectRowsUntilEnd(psObj)
'     Dim allRows, pageRows, isLastPage, pageNum
'     allRows = ""
'     pageNum = 1
'     Do
'         pageRows = CollectOnePageRows(psObj)
'         allRows = ConcatOutputs(allRows, pageRows)
'         isLastPage = HasEndOfAmostragem(psObj)
'         If isLastPage Then Exit Do
'         psObj.SendKeys "[PF8]"
'         psObj.WaitForCursor ROW_START, 1, 5000
'         pageNum = pageNum + 1
'     Loop
'     CollectRowsUntilEnd = allRows
' End Function

' TEMPORARILY DISABLED FOR TESTING
' Function HasEndOfAmostragem(psObj)
'     Dim textAtMarker
'     textAtMarker = psObj.GetText(AMOSTRAGEM_ROW, AMOSTRAGEM_COL, Len(AMOSTRAGEM_END_STR))
'     HasEndOfAmostragem = (Trim(textAtMarker) = AMOSTRAGEM_END_STR)
' End Function

Function CollectOnePageRows(psObj)
    Dim rowOutputs, r, col4Char, col80Char, rowText
    rowOutputs = ""

    ' Simple loop through all rows
    For r = ROW_START To ROW_END
        ' Read column 04 - check if row has data
        col4Char = psObj.GetText(r, COL_CHECK, 1)
        
        ' If empty, stop (no more rows)
        If Trim(col4Char) = "" Then Exit For
        
        ' Has data, check column 80 filter
        col80Char = psObj.GetText(r, COL_FILTER, 1)
        
        ' Only add if column 80 is empty
        If Trim(col80Char) = "" Then
            rowText = RTrim(psObj.GetText(r, 1, LINE_WIDTH))
            If Trim(rowText) <> "" Then
                If rowOutputs <> "" Then rowOutputs = rowOutputs & ", "
                rowOutputs = rowOutputs & rowText
            End If
        End If
    Next

    CollectOnePageRows = rowOutputs
End Function

Function ConcatOutputs(base, add)
    If Trim(add) = "" Then
        ConcatOutputs = base
    ElseIf Trim(base) = "" Then
        ConcatOutputs = add
    Else
        ConcatOutputs = base & ", " & add
    End If
End Function

Function WaitForString(psObj, textToWait, rowNum, colNum, timeoutMs)
    WaitForString = psObj.WaitForString(textToWait, rowNum, colNum, timeoutMs)
End Function

Function EnsureCommandArea(psObj, cmdRow, cmdCol, timeoutMs)
    Dim tries
    If psObj.WaitForCursor(cmdRow, cmdCol, 800) Then
        EnsureCommandArea = True
        Exit Function
    End If

    For tries = 1 To 4
        psObj.SendKeys "[PF5]"
        If psObj.WaitForCursor(cmdRow, cmdCol, 800) Then EnsureCommandArea = True : Exit Function

        psObj.SendKeys "[PF3]"
        If psObj.WaitForCursor(cmdRow, cmdCol, 800) Then EnsureCommandArea = True : Exit Function
    Next

    EnsureCommandArea = psObj.WaitForCursor(cmdRow, cmdCol, timeoutMs)
End Function

Sub Cleanup(sess)
    On Error Resume Next
    If Not sess Is Nothing Then
        sess.autECLConnMgr.StopCommunication
    End If
End Sub