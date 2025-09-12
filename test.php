Option Explicit

Dim Session, OIA, PS

Function AbrePCOM()
    On Error Resume Next
    Set Session = CreateObject("PCOMM.autECLSession")
    Set OIA = CreateObject("PCOMM.autECLOIA")
    If Err.Number <> 0 Then
       WScript.Echo "Erro criando objetos: " & Err.Description
       AbrePCOM = False
       Exit Function
    End If
    Session.SetConnectionByName "A"
    OIA.SetConnectionByName "A"
    Do While OIA.InputInhibited <> 0
        WScript.Sleep 500
    Loop
    If Err.Number <> 0 Then
       WScript.Echo "Sessão A do PCOM deve ser aberta !"
       AbrePCOM = False
    Else
       AbrePCOM = True
    End If
End Function

If AbrePCOM() Then
    Set PS = CreateObject("PCOMM.autECLPS")
    PS.SetConnectionByName "A"
    WScript.Echo "Texto: " & PS.GetText(1, 1, 20)
    PS.SetCursorPos 3, 1
    PS.SendKeys "Teste VBS"
    PS.SendKeys "[enter]"
    WScript.Echo "OK!"
End If