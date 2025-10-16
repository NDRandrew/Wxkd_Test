# -*- coding: utf-8 -*-
import os
import subprocess
import sys
import time
import base64
import pyodbc

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

def chave_exists(cursor, chave_loja):
    """Check if a chaveLoja already exists in the database."""
    query = f"SELECT COUNT(*) FROM {DB_CONFIG['table']} WHERE ChaveLoja = ?"
    cursor.execute(query, (chave_loja,))
    count = cursor.fetchone()[0]
    return count > 0

def insert_rows(cursor, chave_loja, rows):
    """Insert new rows for a chaveLoja."""
    table = DB_CONFIG['table']
    
    # Clear any existing rows for this chave first (optional)
    delete_query = f"DELETE FROM {table} WHERE ChaveLoja = ?"
    cursor.execute(delete_query, (chave_loja,))
    
    # Insert each row
    insert_query = f"""
        INSERT INTO {table} (ChaveLoja, RowData, InsertedAt)
        VALUES (?, ?, GETDATE())
    """
    
    for row in rows:
        cursor.execute(insert_query, (chave_loja, row))
    
    print(f"  [DB] Inserted {len(rows)} rows for ChaveLoja {chave_loja}")

def update_rows(cursor, chave_loja, rows):
    """Update existing rows for a chaveLoja."""
    table = DB_CONFIG['table']
    
    # Delete old rows
    delete_query = f"DELETE FROM {table} WHERE ChaveLoja = ?"
    cursor.execute(delete_query, (chave_loja,))
    
    # Insert updated rows
    insert_query = f"""
        INSERT INTO {table} (ChaveLoja, RowData, UpdatedAt)
        VALUES (?, ?, GETDATE())
    """
    
    for row in rows:
        cursor.execute(insert_query, (chave_loja, row))
    
    print(f"  [DB] Updated {len(rows)} rows for ChaveLoja {chave_loja}")

def save_to_database(parsed_data):
    """Save or update parsed data to the database."""
    if not parsed_data:
        print("No data to save to database")
        return
    
    conn = get_db_connection()
    cursor = conn.cursor()
    
    try:
        for chave_loja, rows in parsed_data.items():
            if not rows:
                print(f"  [DB] No rows for ChaveLoja {chave_loja}, skipping")
                continue
            
            # Check if chaveLoja exists
            if chave_exists(cursor, chave_loja):
                # Update existing records
                update_rows(cursor, chave_loja, rows)
            else:
                # Insert new records
                insert_rows(cursor, chave_loja, rows)
        
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

def run_sequence(username, password_b64, token, codigoloja, chave_list, ws_file=None, session_name="A",
                 parse=False, save_db=False):
    """
    Calls Base_Contas_PJ_read.vbs with:
      <user> <password> <token-or-dash> <codigoloja> <chaveLoja1> [<chaveLoja2> ...]
    - chave_list: list[str] of loja keys
    - token: pass "-" if not required
    - parse: if True, return a dict {chave: [rowStr, ...]} parsed from VBS output
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
        for chave, rows in result.items():
            print(f"  {chave}: {len(rows)} rows")
        
        if save_db:
            print("\n[Saving to Database...]")
            save_to_database(result)

    time.sleep(1)
    close_session(session_name=session_name)
    return result if parse else output

def parse_vbs_output(output, chave_list):
    """
    Parses the VBS output format:
      "chave1: [V] | [V] | [V], [V] | [V] | [V], chave2: [V] | ... , chave3: ..."
    into:
      { "chave1": ["[V] | [V] | [V]", "..."], "chave2": [...], ... }
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

        rows = [x.strip() for x in chunk.split(",") if x.strip()]
        result[chave] = rows

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
    # Example usage
    USER       = "9409841"
    PWD_B64    = "dGlubzEyMDM="  
    TOKEN      = "228326"        
    CODIGOLOJA = "035006"              
    CHAVES     = ["17167508", "11981669", "6181137"]  

    WS_PATH = r"C:\Users\I458363\Desktop\VIDEO 2.ws"

    # Run with database save enabled
    data = run_sequence(
        USER, 
        PWD_B64, 
        TOKEN, 
        CODIGOLOJA, 
        CHAVES, 
        ws_file=WS_PATH, 
        parse=True,
        save_db=True  # Enable database saving
    )
    
    # Optional: print structured result
    if isinstance(data, dict):
        print("\n[Final Results]:")
        for k, rows in data.items():
            print(f"\n{k}:")
            for row in rows:
                print(f"  - {row}")