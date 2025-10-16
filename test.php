Option Explicit

' ===============================
' === CONFIGURE YOUR LAYOUT  ====
' ===============================
Const ROW_START  = 7      ' First data row
Const ROW_END    = 18     ' Last row to probe for data

' Column positions for validation
Const COL_CHECK  = 4      ' Column to check if row has data
Const COL_FILTER = 80     ' Column to check if row should be excluded

' Field extraction positions
Const COL_AGEN   = 6      ' AGEN starts at column 6
Const LEN_AGEN   = 4      ' AGEN is 4 characters (columns 6-9)
Const COL_CONTA  = 11     ' CONTA starts at column 11
Const LEN_CONTA  = 7      ' CONTA is 7 characters (columns 11-17)

' Token prompt string
Const TOKEN_PROMPT_STR = "DIGITE ABAIXO A CHAVE INFORMADA NO SEU DISPOSITIVO DE SEGURANCA"

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
token       = args(2)
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

' Ensure command area before starting queries
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

    ' Return to command area for next chave
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

    ' Wait for screen to update
    WScript.Sleep 300

    ' Collect all pages by comparing before/after PF8
    rowOutputs = CollectAllPages(psObj)

    HandleOneChave = chaveLojaValue & ": " & rowOutputs
End Function

Function CollectAllPages(psObj)
    Dim allRows, currentPageRows, nextPageRows
    
    allRows = ""
    
    Do
        ' Collect current page
        currentPageRows = CollectOnePageRows(psObj)
        
        ' Add current page to all results
        allRows = ConcatOutputs(allRows, currentPageRows)
        
        ' Press PF8 to try next page
        psObj.SendKeys "[PF8]"
        WScript.Sleep 300
        
        ' Collect what's on screen after PF8
        nextPageRows = CollectOnePageRows(psObj)
        
        ' If same as current page, we're on the last page - exit
        If nextPageRows = currentPageRows Then Exit Do
        
    Loop
    
    CollectAllPages = allRows
End Function

Function CollectOnePageRows(psObj)
    Dim rowOutputs, r, col4Char, col80Char, agen, conta, rowData
    rowOutputs = ""

    ' Loop through all rows
    For r = ROW_START To ROW_END
        ' Read column 04 - check if row has data
        col4Char = psObj.GetText(r, COL_CHECK, 1)
        
        ' If empty, stop (no more rows)
        If Trim(col4Char) = "" Then Exit For
        
        ' Has data, check column 80 filter
        col80Char = psObj.GetText(r, COL_FILTER, 1)
        
        ' Only add if column 80 is empty
        If Trim(col80Char) = "" Then
            ' Extract AGEN (columns 6-9) and CONTA (columns 11-17)
            agen = Trim(psObj.GetText(r, COL_AGEN, LEN_AGEN))
            conta = Trim(psObj.GetText(r, COL_CONTA, LEN_CONTA))
            
            ' Format as "AGEN|CONTA"
            If agen <> "" And conta <> "" Then
                rowData = agen & "|" & conta
                If rowOutputs <> "" Then rowOutputs = rowOutputs & ", "
                rowOutputs = rowOutputs & rowData
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
        If psObj.WaitForCursor(cmdRow, cmdCol, 800) Then
            EnsureCommandArea = True
            Exit Function
        End If

        psObj.SendKeys "[PF3]"
        If psObj.WaitForCursor(cmdRow, cmdCol, 800) Then
            EnsureCommandArea = True
            Exit Function
        End If
    Next

    EnsureCommandArea = psObj.WaitForCursor(cmdRow, cmdCol, timeoutMs)
End Function

Sub Cleanup(sess)
    On Error Resume Next
    If Not sess Is Nothing Then
        sess.autECLConnMgr.StopCommunication
    End If
End Sub


---------


# -*- coding: utf-8 -*-
import os
import subprocess
import sys
import time
import base64
import pyodbc
import pandas as pd
from datetime import datetime

# ====================================
# DATABASE CONFIGURATION
# ====================================
DB_CONFIG = {
    'server': 'your_server_name',      # e.g., 'localhost' or 'SERVER\\INSTANCE'
    'database': 'your_database_name',
    'username': 'your_username',       # Use None for Windows Authentication
    'password': 'your_password',       # Use None for Windows Authentication
    'table': 'Contas_PJ'               # Table name
}

def get_db_connection():
    """Create and return a database connection."""
    try:
        if DB_CONFIG['username'] and DB_CONFIG['password']:
            # SQL Server Authentication
            conn_str = (
                f"DRIVER={{ODBC Driver 17 for SQL Server}};"
                f"SERVER={DB_CONFIG['server']};"
                f"DATABASE={DB_CONFIG['database']};"
                f"UID={DB_CONFIG['username']};"
                f"PWD={DB_CONFIG['password']}"
            )
        else:
            # Windows Authentication
            conn_str = (
                f"DRIVER={{ODBC Driver 17 for SQL Server}};"
                f"SERVER={DB_CONFIG['server']};"
                f"DATABASE={DB_CONFIG['database']};"
                f"Trusted_Connection=yes;"
            )
        
        conn = pyodbc.connect(conn_str)
        return conn
    except pyodbc.Error as e:
        print(f"Database connection error: {e}", file=sys.stderr)
        sys.exit(1)

def loja_exists(cursor, loja):
    """Check if a loja already exists in the database."""
    query = f"SELECT COUNT(*) FROM {DB_CONFIG['table']} WHERE LOJA = ?"
    cursor.execute(query, (loja,))
    count = cursor.fetchone()[0]
    return count > 0

def insert_accounts(cursor, loja, accounts, descricao):
    """
    Insert new accounts for a loja.
    
    Args:
        cursor: database cursor
        loja: loja identifier
        accounts: list of dicts with 'agen' and 'conta' keys
        descricao: description with date
    """
    table = DB_CONFIG['table']
    
    insert_query = f"""
        INSERT INTO {table} (DESCRICAO, LOJA, AGEN, CONTA)
        VALUES (?, ?, ?, ?)
    """
    
    for account in accounts:
        cursor.execute(insert_query, (descricao, loja, account['agen'], account['conta']))
    
    print(f"  [DB] Inserted {len(accounts)} accounts for Loja {loja}")

def update_accounts(cursor, loja, accounts, descricao):
    """
    Update existing accounts for a loja.
    
    Args:
        cursor: database cursor
        loja: loja identifier
        accounts: list of dicts with 'agen' and 'conta' keys
        descricao: description with date
    """
    table = DB_CONFIG['table']
    
    # Delete old records for this loja
    delete_query = f"DELETE FROM {table} WHERE LOJA = ?"
    cursor.execute(delete_query, (loja,))
    
    # Insert updated records
    insert_query = f"""
        INSERT INTO {table} (DESCRICAO, LOJA, AGEN, CONTA)
        VALUES (?, ?, ?, ?)
    """
    
    for account in accounts:
        cursor.execute(insert_query, (descricao, loja, account['agen'], account['conta']))
    
    print(f"  [DB] Updated {len(accounts)} accounts for Loja {loja}")

def save_to_database(parsed_data, chave_to_loja_map):
    """
    Save or update parsed data to the database.
    
    Args:
        parsed_data: dict {chave_loja: [{'agen': '...', 'conta': '...'}, ...]}
        chave_to_loja_map: dict {chave_loja: loja}
    """
    if not parsed_data:
        print("No data to save to database")
        return
    
    # Generate DESCRICAO with today's date
    descricao = datetime.now().strftime("Processado em %d/%m/%Y")
    
    conn = get_db_connection()
    cursor = conn.cursor()
    
    try:
        for chave_loja, accounts in parsed_data.items():
            if not accounts:
                print(f"  [DB] No accounts for ChaveLoja {chave_loja}, skipping")
                continue
            
            # Get the corresponding loja
            loja = chave_to_loja_map.get(chave_loja)
            if not loja:
                print(f"  [DB] No loja mapping found for ChaveLoja {chave_loja}, skipping")
                continue
            
            # Check if loja exists
            if loja_exists(cursor, loja):
                # Update existing records
                update_accounts(cursor, loja, accounts, descricao)
            else:
                # Insert new records
                insert_accounts(cursor, loja, accounts, descricao)
        
        # Commit all changes
        conn.commit()
        print("\n[DB] All changes committed successfully")
        
    except pyodbc.Error as e:
        conn.rollback()
        print(f"Database error: {e}", file=sys.stderr)
        raise
    finally:
        cursor.close()
        conn.close()

def read_excel_file(excel_path):
    """
    Read Excel file with columns 'loja' and 'chave'.
    
    Returns:
        tuple: (chave_list, chave_to_loja_map)
        - chave_list: list of chave values
        - chave_to_loja_map: dict mapping chave to loja
    """
    try:
        # Read Excel file
        df = pd.read_excel(excel_path)
        
        # Check required columns (case-insensitive)
        df.columns = df.columns.str.lower().str.strip()
        required_cols = ['loja', 'chave']
        missing_cols = [col for col in required_cols if col not in df.columns]
        
        if missing_cols:
            print(f"Error: Missing required columns: {missing_cols}", file=sys.stderr)
            print(f"Available columns: {list(df.columns)}", file=sys.stderr)
            sys.exit(1)
        
        # Remove rows with NaN values
        df = df.dropna(subset=['loja', 'chave'])
        
        if df.empty:
            print("Error: No valid data found in Excel file", file=sys.stderr)
            sys.exit(1)
        
        # Convert to strings and strip whitespace
        df['loja'] = df['loja'].astype(str).str.strip()
        df['chave'] = df['chave'].astype(str).str.strip()
        
        # Create chave list and mapping
        chave_list = df['chave'].tolist()
        chave_to_loja_map = dict(zip(df['chave'], df['loja']))
        
        print(f"\n[Excel] Read {len(chave_list)} entries from {excel_path}")
        print(f"[Excel] Sample entries:")
        for i, (chave, loja) in enumerate(zip(chave_list[:3], [chave_to_loja_map[c] for c in chave_list[:3]])):
            print(f"  {i+1}. Loja: {loja}, Chave: {chave}")
        if len(chave_list) > 3:
            print(f"  ... and {len(chave_list) - 3} more")
        
        return chave_list, chave_to_loja_map
        
    except FileNotFoundError:
        print(f"Error: Excel file not found: {excel_path}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"Error reading Excel file: {e}", file=sys.stderr)
        sys.exit(1)

# ====================================
# PCOMM / VBS FUNCTIONS
# ====================================

def get_cscript_exe():
    windir = os.environ.get("SystemRoot", r"C:\Windows")
    path32 = os.path.join(windir, "SysWOW64", "cscript.exe")
    return path32 if os.path.isfile(path32) else os.path.join(windir, "System32", "cscript.exe")

def open_session_ws(ws_file, delay=4):
    if not os.path.isfile(ws_file):
        print(f"Session file not found: {ws_file}", file=sys.stderr)
        sys.exit(1)
    print(f"Launching session: {ws_file}")
    os.startfile(ws_file)
    print(f"Waiting {delay}s for emulator to come up…")
    time.sleep(delay)

def run_sequence(username, password_b64, token, codigoloja, chave_list, chave_to_loja_map, 
                 ws_file=None, session_name="A", parse=False, save_db=False):
    """
    Calls Base_Contas_PJ_read.vbs with:
      <user> <password> <token-or-dash> <codigoloja> <chaveLoja1> [<chaveLoja2> ...]
    - chave_list: list[str] of chave values (used as chaveLoja)
    - chave_to_loja_map: dict mapping chave to loja
    - token: pass "-" if not required
    - parse: if True, return a dict {chave: [{'agen': '...', 'conta': '...'}, ...]}
    - save_db: if True, save parsed data to database
    """
    here = os.path.dirname(os.path.abspath(__file__))
    vbs  = os.path.join(here, "Base_Contas_PJ_read.vbs")

    if ws_file:
        open_session_ws(ws_file)

    if not os.path.exists(vbs):
        print(f"VBScript not found: {vbs}", file=sys.stderr)
        close_session(session_name=session_name)
        sys.exit(1)

    try:
        raw = base64.b64decode(password_b64, validate=True)
        password = raw.decode("utf-8")
    except Exception as e:
        print(f"Invalid Base64 password: {e}", file=sys.stderr)
        close_session(session_name=session_name)
        sys.exit(1)

    if token is None or token == "":
        token = "-"   # VBS expects "-" to skip token

    # Build command
    cmd = [
        get_cscript_exe(),
        "//NoLogo",
        vbs,
        username,
        password,
        token,
        (codigoloja or ""),
    ]

    # Append all chaves
    if not chave_list:
        print("No ChaveLoja provided. Pass at least one.", file=sys.stderr)
        close_session(session_name=session_name)
        sys.exit(1)
    cmd.extend([str(x) for x in chave_list])

    # Run VBS
    print(f"\n[Running VBS with {len(chave_list)} chaveLoja(s)...]")
    proc = subprocess.run(cmd, capture_output=True, text=True)

    if proc.returncode != 0:
        err = (proc.stderr or proc.stdout or "").strip()
        print(f"VBScript error: {err}", file=sys.stderr)
        time.sleep(1)
        close_session(session_name=session_name)
        sys.exit(1)

    output = (proc.stdout or "").strip()

    # Print raw output for logging
    print("\n[VBS Output]:")
    print(output)

    result = None
    if parse:
        result = parse_vbs_output(output, chave_list)
        print("\n[Parsed Data]:")
        for chave, accounts in result.items():
            loja = chave_to_loja_map.get(chave, "?")
            print(f"  Loja {loja} (Chave {chave}): {len(accounts)} accounts")
            for acc in accounts[:3]:  # Show first 3
                print(f"    - AGEN: {acc['agen']}, CONTA: {acc['conta']}")
            if len(accounts) > 3:
                print(f"    ... and {len(accounts) - 3} more")
        
        if save_db:
            print("\n[Saving to Database...]")
            save_to_database(result, chave_to_loja_map)

    time.sleep(1)
    close_session(session_name=session_name)
    return result if parse else output

def parse_vbs_output(output, chave_list):
    """
    Parses the VBS output format:
      "chave1: AGEN1|CONTA1, AGEN2|CONTA2, chave2: AGEN3|CONTA3, ..."
    into:
      { "chave1": [{'agen': 'AGEN1', 'conta': 'CONTA1'}, ...], "chave2": [...], ... }
    """
    starts = {}
    for chave in chave_list:
        needle = f"{chave}:"
        idx = output.find(needle)
        if idx >= 0:
            starts[chave] = idx

    ordered = sorted(starts.items(), key=lambda kv: kv[1])

    result = {}
    for i, (chave, start_idx) in enumerate(ordered):
        after_label = start_idx + len(chave) + 1
        if after_label < len(output) and output[after_label] == " ":
            after_label += 1

        end_idx = len(output)
        if i + 1 < len(ordered):
            end_idx = ordered[i + 1][1]

        chunk = output[after_label:end_idx].strip()
        if chunk.endswith(","):
            chunk = chunk[:-1].strip()

        # Parse "AGEN|CONTA" format
        accounts = []
        for row in chunk.split(","):
            row = row.strip()
            if "|" in row:
                parts = row.split("|")
                if len(parts) == 2:
                    agen = parts[0].strip()
                    conta = parts[1].strip()
                    if agen and conta:
                        accounts.append({'agen': agen, 'conta': conta})
        
        result[chave] = accounts

    return result

def close_session(session_name="A"):
    """
    Attempts to close the PCOMM session window.
    Falls back to killing pcsws.exe via WMIC if needed.
    """
    try:
        import ctypes
        from ctypes import wintypes
        user32 = ctypes.windll.user32

        def enum_windows_proc(hwnd, lParam):
            length = user32.GetWindowTextLengthW(hwnd)
            if length > 0:
                buffer = ctypes.create_unicode_buffer(length + 1)
                user32.GetWindowTextW(hwnd, buffer, length + 1)
                title = buffer.value
                if (f"Sessão {session_name}" in title) or (f"Session {session_name}" in title) or (f"{session_name} - " in title):
                    user32.PostMessageW(hwnd, 0x10, 0, 0)  # WM_CLOSE
            return True

        EnumWindowsProc = ctypes.WINFUNCTYPE(ctypes.c_bool, wintypes.HWND, wintypes.LPARAM)
        user32.EnumWindows(EnumWindowsProc(enum_windows_proc), 0)
    except Exception:
        try:
            subprocess.run(["wmic", "process", "where", "name='pcsws.exe'", "delete"], capture_output=True)
        except Exception:
            pass

if __name__ == "__main__":
    # Configuration
    USER       = "9409841"
    PWD_B64    = "dGlubzEyMDM="  
    TOKEN      = "228326"        
    CODIGOLOJA = "035006"

    WS_PATH    = r"C:\Users\I458363\Desktop\VIDEO 2.ws"
    EXCEL_PATH = r"C:\Users\I458363\Desktop\lojas_chaves.xlsx"

    # Read Excel file
    chave_list, chave_to_loja_map = read_excel_file(EXCEL_PATH)

    # Run with database save enabled
    data = run_sequence(
        USER, 
        PWD_B64, 
        TOKEN, 
        CODIGOLOJA, 
        chave_list,
        chave_to_loja_map,
        ws_file=WS_PATH, 
        parse=True,
        save_db=True
    )
    
    # Optional: print structured result
    if isinstance(data, dict):
        print("\n[Final Results]:")
        for chave, accounts in data.items():
            loja = chave_to_loja_map.get(chave, "?")
            print(f"\nLoja {loja} (Chave {chave}):")
            for acc in accounts:
                print(f"  - AGEN: {acc['agen']}, CONTA: {acc['conta']}")