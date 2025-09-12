Option Explicit

Dim Session, OIA, PS

Function AbrePCOM()
    On Error Resume Next
    Set Session = CreateObject("AutSess")
    Set OIA = CreateObject("AutOIA")
    Session.SetConnectionByName "A"
    If Session.autECLWinMetrics.Active Then
       Session.autECLWinMetrics.Active = False
    Else
       Session.autECLWinMetrics.Active = True
    End If
    OIA.SetConnectionByName "A"
    OIA.WaitForInputReady
    If Err = -2147352567 Then
       WScript.Echo "Sessão A do PCOM deve ser aberta !"
       AbrePCOM = False
    ElseIf Err.Number <> 0 Then
       WScript.Echo "Erro: " & Err.Description
       AbrePCOM = False
    Else
       AbrePCOM = True
    End If
End Function

If AbrePCOM() Then
    Set PS = CreateObject("AutPS")
    PS.SetConnectionByName "A"
    WScript.Echo "Texto: " & PS.GetText(1, 1, 20)
    PS.SetCursorPos 3, 1
    PS.SendKeys "Teste VBS"
    PS.SendKeys "[enter]"
    WScript.Echo "OK!"
End If