import autoit
import win32gui
import win32con
import win32com.client
import time
import os

class PCOMMAutomation:
    def __init__(self, ws_file_path=None):
        self.window_handle = None
        self.window_title = "IBM Personal Communications"
        self.ws_file_path = ws_file_path
        self.pcomm_session = None
        self.session_name = "A"  # Default session name, adjust if needed
    
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
            time.sleep(5)
            
            return True
            
        except Exception as e:
            print(f"Error opening workspace: {e}")
            return False
    
    def connect_to_pcomm_session(self):
        """Connect to PCOMM automation object"""
        try:
            # Connect to PCOMM automation object
            print("Connecting to PCOMM automation interface...")
            self.pcomm_session = win32com.client.Dispatch("PCOMM.autECLSession")
            
            # Set the session (A, B, C, etc.)
            self.pcomm_session.SetConnectionByName(self.session_name)
            
            print(f"Connected to PCOMM session: {self.session_name}")
            return True
            
        except Exception as e:
            print(f"Error connecting to PCOMM session: {e}")
            print("Make sure PCOMM is running and session is active")
            return False
    
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
    
    def read_screen_text(self, start_row, start_col, length):
        """Read text from screen at specific position"""
        if not self.pcomm_session:
            print("Not connected to PCOMM session")
            return None
        
        try:
            # Read text from specified position
            text = self.pcomm_session.autECLPS.GetText(start_row, start_col, length)
            return text.strip()
            
        except Exception as e:
            print(f"Error reading screen text: {e}")
            return None
    
    def read_line(self, row, start_col=1, length=80):
        """Read entire line or part of a line"""
        return self.read_screen_text(row, start_col, length)
    
    def read_position(self, row, col, length=1):
        """Read text at specific row/column position"""
        return self.read_screen_text(row, col, length)
    
    def read_field_at_position(self, row, col):
        """Read the entire field starting at specific position"""
        if not self.pcomm_session:
            return None
        
        try:
            # Get field information at position
            field_text = ""
            current_col = col
            
            # Read character by character until we hit end of field or screen
            for i in range(80):  # Maximum line length
                char = self.pcomm_session.autECLPS.GetText(row, current_col, 1)
                if char == "" or char == " ":
                    break
                field_text += char
                current_col += 1
            
            return field_text.strip()
            
        except Exception as e:
            print(f"Error reading field: {e}")
            return None
    
    def get_screen_dimensions(self):
        """Get screen dimensions (rows and columns)"""
        if not self.pcomm_session:
            return None, None
        
        try:
            rows = self.pcomm_session.autECLPS.NumRows
            cols = self.pcomm_session.autECLPS.NumCols
            return rows, cols
        except Exception as e:
            print(f"Error getting screen dimensions: {e}")
            return None, None
    
    def search_text_on_screen(self, search_text):
        """Search for text on screen and return its position"""
        if not self.pcomm_session:
            return None
        
        try:
            rows, cols = self.get_screen_dimensions()
            if not rows or not cols:
                return None
            
            # Search through each line
            for row in range(1, rows + 1):
                line_text = self.read_line(row, 1, cols)
                if line_text and search_text.upper() in line_text.upper():
                    col_position = line_text.upper().find(search_text.upper()) + 1
                    return row, col_position
            
            return None
            
        except Exception as e:
            print(f"Error searching text: {e}")
            return None
    
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
    
    def send_enter(self):
        """Send Enter key"""
        return self.send_text("{ENTER}")
    
    def send_function_key(self, key_number):
        """Send function key (F1-F24)"""
        return self.send_text(f"{{F{key_number}}}")

def test_screen_reading():
    """Test function to demonstrate screen reading capabilities"""
    print("IBM PCOMM Screen Reading Test")
    print("=" * 35)
    
    # Get workspace file from user
    ws_file_path = input("Enter path to your .ws file: ").strip().strip('"')
    
    if not ws_file_path:
        print("No file specified")
        return
    
    # Create automation instance
    pcomm = PCOMMAutomation(ws_file_path)
    
    # Open PCOMM workspace
    if not pcomm.open_pcomm_workspace():
        print("Failed to open PCOMM workspace")
        return
    
    # Find PCOMM window
    if not pcomm.find_pcomm_window():
        print("Could not find PCOMM window")
        return
    
    # Connect to PCOMM automation interface
    if not pcomm.connect_to_pcomm_session():
        print("Could not connect to PCOMM automation interface")
        return
    
    print("PCOMM is ready for screen reading!")
    time.sleep(2)
    
    # Interactive testing
    while True:
        print("\n" + "="*50)
        print("Screen Reading Options:")
        print("1. Read specific position (row, col, length)")
        print("2. Read entire line")
        print("3. Read field at position")
        print("4. Search for text")
        print("5. Show screen dimensions")
        print("6. Exit")
        
        choice = input("\nEnter your choice (1-6): ").strip()
        
        if choice == "1":
            try:
                row = int(input("Enter row number: "))
                col = int(input("Enter column number: "))
                length = int(input("Enter length to read: "))
                
                text = pcomm.read_position(row, col, length)
                print(f"Text at ({row}, {col}) length {length}: '{text}'")
                
            except ValueError:
                print("Please enter valid numbers")
        
        elif choice == "2":
            try:
                row = int(input("Enter row number: "))
                text = pcomm.read_line(row)
                print(f"Line {row}: '{text}'")
                
            except ValueError:
                print("Please enter a valid row number")
        
        elif choice == "3":
            try:
                row = int(input("Enter row number: "))
                col = int(input("Enter column number: "))
                
                text = pcomm.read_field_at_position(row, col)
                print(f"Field at ({row}, {col}): '{text}'")
                
            except ValueError:
                print("Please enter valid numbers")
        
        elif choice == "4":
            search_text = input("Enter text to search for: ")
            result = pcomm.search_text_on_screen(search_text)
            
            if result:
                row, col = result
                print(f"Found '{search_text}' at row {row}, column {col}")
            else:
                print(f"Text '{search_text}' not found on screen")
        
        elif choice == "5":
            rows, cols = pcomm.get_screen_dimensions()
            if rows and cols:
                print(f"Screen dimensions: {rows} rows x {cols} columns")
            else:
                print("Could not get screen dimensions")
        
        elif choice == "6":
            print("Exiting...")
            break
        
        else:
            print("Invalid choice. Please enter 1-6.")

if __name__ == "__main__":
    try:
        import autoit
        import win32gui
        import win32com.client
        print("All required modules loaded successfully.")
        test_screen_reading()
        
    except ImportError as e:
        print(f"Missing required module: {e}")
        print("\nTo install required packages:")
        print("pip install pyautoit pywin32")