Option Explicit

Dim Session, OIA, PS

Function AbrePCOM()
    On Error GoTo Tratamento
    Set Session = New AutSess
    Set OIA = New AutOIA
    Session.SetConnectionByName "A"
    If Session.autECLWinMetrics.Active Then
       Session.autECLWinMetrics.Active = False
    Else
       Session.autECLWinMetrics.Active = True
    End If
    OIA.SetConnectionByName "A"
    OIA.WaitForInputReady
    AbrePCOM = True
    Exit Function
Tratamento:
    If Err = -2147352567 Then
       WScript.Echo "Sessão A do PCOM deve ser aberta !"
    Else
       WScript.Echo "Erro: " & Err.Description
    End If
    AbrePCOM = False
End Function

If AbrePCOM() Then
    Set PS = New AutPS
    PS.SetConnectionByName "A"
    
    WScript.Echo "Texto na posição 1,1: " & PS.GetText(1, 1, 20)
    
    PS.SetCursorPos 3, 1
    PS.SendKeys "Teste VBS " & Now()
    PS.SendKeys "[enter]"
    
    WScript.Echo "Automação concluída!"
Else
    WScript.Echo "Falha na conexão PCOM"
End If

Set Session = Nothing
Set OIA = Nothing
Set PS = Nothing