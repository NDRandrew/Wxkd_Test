import autoit
import win32gui
import win32con
import win32com.client
import time
import os
import subprocess
import sys

class PCOMMAutomation:
    def __init__(self, ws_file_path=None):
        self.window_handle = None
        self.window_title = "IBM Personal Communications"
        self.ws_file_path = ws_file_path
        self.pcomm_session = None
        self.session_name = "A"
        self.ehllapi = None  # Alternative EHLLAPI interface
    
    def register_pcomm_automation(self):
        """Try to register PCOMM automation components"""
        print("Attempting to register PCOMM automation...")
        
        # Common PCOMM installation paths
        pcomm_paths = [
            r"C:\Program Files\IBM\Personal Communications",
            r"C:\Program Files (x86)\IBM\Personal Communications", 
            r"C:\PCOMM",
            r"C:\IBM\PCOMM"
        ]
        
        for path in pcomm_paths:
            dll_path = os.path.join(path, "autECL32.dll")
            if os.path.exists(dll_path):
                try:
                    print(f"Registering {dll_path}")
                    subprocess.run([
                        "regsvr32", "/s", dll_path
                    ], check=True)
                    print("Registration successful!")
                    return True
                except Exception as e:
                    print(f"Registration failed: {e}")
        
        print("Could not find PCOMM automation DLL to register")
        return False
    
    def open_pcomm_workspace(self):
        """Open PCOMM by launching the .ws file"""
        if not self.ws_file_path:
            print("No workspace file specified")
            return False
            
        if not os.path.exists(self.ws_file_path):
            print(f"Workspace file not found: {self.ws_file_path}")
            return False
        
        try:
            print(f"Opening workspace: {self.ws_file_path}")
            os.startfile(self.ws_file_path)
            
            print("Waiting for PCOMM to load...")
            time.sleep(7)  # Give more time for PCOMM to fully load
            
            return True
            
        except Exception as e:
            print(f"Error opening workspace: {e}")
            return False
    
    def connect_to_pcomm_session(self):
        """Try multiple methods to connect to PCOMM"""
        connection_methods = [
            self._connect_method_1,
            self._connect_method_2,
            self._connect_method_3,
            self._connect_ehllapi
        ]
        
        for i, method in enumerate(connection_methods, 1):
            print(f"Trying connection method {i}...")
            if method():
                return True
        
        print("All connection methods failed")
        return False
    
    def _connect_method_1(self):
        """Standard PCOMM automation"""
        try:
            self.pcomm_session = win32com.client.Dispatch("PCOMM.autECLSession")
            self.pcomm_session.SetConnectionByName(self.session_name)
            print("✓ Connected using PCOMM.autECLSession")
            return True
        except Exception as e:
            print(f"Method 1 failed: {e}")
            return False
    
    def _connect_method_2(self):
        """Alternative PCOMM automation object"""
        try:
            self.pcomm_session = win32com.client.Dispatch("PCOMM.autECLConnMgr")
            print("✓ Connected using PCOMM.autECLConnMgr")
            return True
        except Exception as e:
            print(f"Method 2 failed: {e}")
            return False
    
    def _connect_method_3(self):
        """Try ECL automation"""
        try:
            self.pcomm_session = win32com.client.Dispatch("ECL.autECLSession")
            self.pcomm_session.SetConnectionByName(self.session_name)
            print("✓ Connected using ECL.autECLSession")
            return True
        except Exception as e:
            print(f"Method 3 failed: {e}")
            return False
    
    def _connect_ehllapi(self):
        """Try EHLLAPI interface"""
        try:
            # Try to use EHLLAPI (older but more compatible interface)
            self.ehllapi = win32com.client.Dispatch("WinHLLAPI.WinHLLAPIClass")
            print("✓ Connected using EHLLAPI interface")
            return True
        except Exception as e:
            print(f"EHLLAPI failed: {e}")
            return False
    
    def read_screen_text(self, start_row, start_col, length):
        """Read text from screen using available interface"""
        if self.pcomm_session:
            return self._read_with_pcomm(start_row, start_col, length)
        elif self.ehllapi:
            return self._read_with_ehllapi(start_row, start_col, length)
        else:
            print("No active connection to PCOMM")
            return None
    
    def _read_with_pcomm(self, start_row, start_col, length):
        """Read using PCOMM automation"""
        try:
            text = self.pcomm_session.autECLPS.GetText(start_row, start_col, length)
            return text.strip()
        except Exception as e:
            print(f"Error reading with PCOMM: {e}")
            return None
    
    def _read_with_ehllapi(self, start_row, start_col, length):
        """Read using EHLLAPI interface"""
        try:
            # EHLLAPI uses different positioning (0-based, linear)
            position = ((start_row - 1) * 80) + (start_col - 1)
            
            # This is a simplified EHLLAPI implementation
            # You may need to adjust based on your PCOMM version
            text = self.ehllapi.GetText(position, length)
            return text.strip()
        except Exception as e:
            print(f"Error reading with EHLLAPI: {e}")
            return None
    
    def read_line(self, row, start_col=1, length=80):
        """Read entire line or part of a line"""
        return self.read_screen_text(row, start_col, length)
    
    def read_position(self, row, col, length=1):
        """Read text at specific row/column position"""
        return self.read_screen_text(row, col, length)
    
    def find_pcomm_window(self, max_attempts=10):
        """Find IBM PCOMM window with retry logic"""
        for attempt in range(max_attempts):
            try:
                self.window_handle = win32gui.FindWindow(None, self.window_title)
                
                if self.window_handle == 0:
                    def enum_windows_proc(hwnd, lParam):
                        window_text = win32gui.GetWindowText(hwnd)
                        if any(keyword in window_text.upper() for keyword in 
                              ["IBM", "PCOMM", "PERSONAL COMMUNICATIONS", "MAINFRAME"]):
                            self.window_handle = hwnd
                            return False
                        return True
                    
                    win32gui.EnumWindows(enum_windows_proc, 0)
                
                if self.window_handle != 0:
                    window_title = win32gui.GetWindowText(self.window_handle)
                    print(f"Found PCOMM window: {window_title}")
                    return True
                else:
                    print(f"Attempt {attempt + 1}: PCOMM window not found, retrying...")
                    time.sleep(2)
                    
            except Exception as e:
                print(f"Error finding PCOMM window: {e}")
                time.sleep(2)
        
        return False
    
    def activate_window(self):
        """Bring PCOMM window to foreground"""
        if self.window_handle:
            try:
                win32gui.SetForegroundWindow(self.window_handle)
                win32gui.ShowWindow(self.window_handle, win32con.SW_RESTORE)
                time.sleep(1)
                return True
            except Exception as e:
                print(f"Error activating window: {e}")
                return False
        return False
    
    def send_text(self, text):
        """Send text to PCOMM terminal"""
        if self.activate_window():
            try:
                autoit.send(text)
                print(f"Sent text: {text}")
                return True
            except Exception as e:
                print(f"Error sending text: {e}")
                return False
        return False

def fix_pcomm_registration():
    """Try to fix PCOMM registration issues"""
    print("PCOMM Registration Fix Utility")
    print("=" * 40)
    
    print("Step 1: Trying to register PCOMM automation...")
    
    # Try to find and register PCOMM automation
    pcomm_paths = [
        r"C:\Program Files\IBM\Personal Communications\autECL32.dll",
        r"C:\Program Files (x86)\IBM\Personal Communications\autECL32.dll",
        r"C:\PCOMM\autECL32.dll",
        r"C:\IBM\PCOMM\autECL32.dll"
    ]
    
    registered = False
    for dll_path in pcomm_paths:
        if os.path.exists(dll_path):
            try:
                print(f"Found: {dll_path}")
                print("Registering...")
                
                # Unregister first
                subprocess.run(["regsvr32", "/u", "/s", dll_path], check=False)
                
                # Register
                result = subprocess.run(["regsvr32", "/s", dll_path], check=True)
                print("✓ Registration successful!")
                registered = True
                break
                
            except Exception as e:
                print(f"Registration failed: {e}")
    
    if not registered:
        print("\nStep 2: Manual registration instructions:")
        print("1. Find your PCOMM installation folder")
        print("2. Open Command Prompt as Administrator")
        print("3. Run: regsvr32 \"C:\\path\\to\\pcomm\\autECL32.dll\"")
        print("4. Also try: regsvr32 \"C:\\path\\to\\pcomm\\ECL32.dll\"")
    
    return registered

def test_pcomm_connection():
    """Test PCOMM connection with multiple methods"""
    print("PCOMM Connection Test")
    print("=" * 25)
    
    # Get workspace file
    ws_file_path = input("Enter path to your .ws file: ").strip().strip('"')
    
    if not ws_file_path:
        print("No file specified")
        return
    
    # Try to fix registration first
    print("\nStep 1: Checking PCOMM registration...")
    fix_pcomm_registration()
    
    # Create automation instance
    pcomm = PCOMMAutomation(ws_file_path)
    
    # Open PCOMM workspace
    print("\nStep 2: Opening PCOMM workspace...")
    if not pcomm.open_pcomm_workspace():
        print("Failed to open PCOMM workspace")
        return
    
    # Find PCOMM window
    print("\nStep 3: Finding PCOMM window...")
    if not pcomm.find_pcomm_window():
        print("Could not find PCOMM window")
        return
    
    # Try to connect using multiple methods
    print("\nStep 4: Connecting to PCOMM automation...")
    if not pcomm.connect_to_pcomm_session():
        print("Could not connect to PCOMM automation interface")
        print("\nTroubleshooting suggestions:")
        print("1. Make sure PCOMM session is active")
        print("2. Try running as Administrator")
        print("3. Check if PCOMM automation is installed")
        print("4. Try different session name (A, B, C, etc.)")
        return
    
    print("\n✓ Successfully connected to PCOMM!")
    
    # Test reading
    print("\nStep 5: Testing screen reading...")
    try:
        # Read first line
        line1 = pcomm.read_line(1)
        print(f"Line 1: '{line1}'")
        
        # Read specific position
        pos_text = pcomm.read_position(1, 1, 10)
        print(f"Position (1,1) length 10: '{pos_text}'")
        
        print("\n✓ Screen reading test successful!")
        
        # Interactive mode
        while True:
            print("\nInteractive test:")
            row = input("Enter row to read (or 'quit'): ").strip()
            if row.lower() == 'quit':
                break
            
            try:
                row_num = int(row)
                text = pcomm.read_line(row_num)
                print(f"Row {row_num}: '{text}'")
            except ValueError:
                print("Please enter a valid number")
    
    except Exception as e:
        print(f"Error during testing: {e}")

if __name__ == "__main__":
    try:
        import autoit
        import win32gui
        import win32com.client
        print("All required modules loaded successfully.")
        test_pcomm_connection()
        
    except ImportError as e:
        print(f"Missing required module: {e}")
        print("\nTo install required packages:")
        print("pip install pyautoit pywin32")