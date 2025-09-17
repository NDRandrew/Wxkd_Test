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
        import ctypes
        from ctypes import wintypes
        user32 = ctypes.windll.user32
        
        def enum_windows_proc(hwnd, lParam):
            length = user32.GetWindowTextLengthW(hwnd)
            if length > 0:
                buffer = ctypes.create_unicode_buffer(length + 1)
                user32.GetWindowTextW(hwnd, buffer, length + 1)
                if "Sessão A" in buffer.value:
                    user32.PostMessageW(hwnd, 0x10, 0, 0)  # WM_CLOSE
            return True
            
        EnumWindowsProc = ctypes.WINFUNCTYPE(ctypes.c_bool, wintypes.HWND, wintypes.LPARAM)
        user32.EnumWindows(EnumWindowsProc(enum_windows_proc), 0)
    except:
        try:
            subprocess.run(["wmic", "process", "where", "name='pcsws.exe'", "delete"], capture_output=True)
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