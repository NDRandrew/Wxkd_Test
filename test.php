import autoit
import win32gui
import win32con
import time
import os
import subprocess

class PCOMMAutomation:
    def __init__(self, ws_file_path=None):
        self.window_handle = None
        self.window_title = "IBM Personal Communications"  # Adjust if needed
        self.ws_file_path = ws_file_path
    
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
            # Open the .ws file (will launch PCOMM)
            os.startfile(self.ws_file_path)
            
            # Wait for PCOMM to start
            print("Waiting for PCOMM to load...")
            time.sleep(5)  # Adjust timing as needed
            
            return True
            
        except Exception as e:
            print(f"Error opening workspace: {e}")
            return False
    
    def find_pcomm_window(self, max_attempts=10):
        """Find IBM PCOMM window with retry logic"""
        for attempt in range(max_attempts):
            try:
                # Try to find window by title
                self.window_handle = win32gui.FindWindow(None, self.window_title)
                
                if self.window_handle == 0:
                    # Alternative search - look for any window containing PCOMM keywords
                    def enum_windows_proc(hwnd, lParam):
                        window_text = win32gui.GetWindowText(hwnd)
                        if any(keyword in window_text.upper() for keyword in 
                              ["IBM", "PCOMM", "PERSONAL COMMUNICATIONS", "MAINFRAME"]):
                            self.window_handle = hwnd
                            return False  # Stop enumeration
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
        
        print("PCOMM window not found after all attempts")
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
    
    def send_enter(self):
        """Send Enter key"""
        return self.send_text("{ENTER}")
    
    def send_function_key(self, key_number):
        """Send function key (F1-F24)"""
        return self.send_text(f"{{F{key_number}}}")
    
    def send_pf_key(self, pf_number):
        """Send PF key (common in mainframe environments)"""
        return self.send_function_key(pf_number)

def main():
    """Main automation function"""
    print("IBM PCOMM Workspace Automation")
    print("=" * 35)
    
    # CHANGE THIS PATH TO YOUR .WS FILE
    ws_file_path = r"C:\path\to\your\session.ws"  # Update this path!
    
    # You can also prompt user for the path
    # ws_file_path = input("Enter path to your .ws file: ").strip()
    
    # Create automation instance
    pcomm = PCOMMAutomation(ws_file_path)
    
    # Open PCOMM workspace
    if not pcomm.open_pcomm_workspace():
        print("Failed to open PCOMM workspace")
        return
    
    # Find PCOMM window (with retry logic)
    if not pcomm.find_pcomm_window():
        print("Could not find PCOMM window after opening workspace")
        return
    
    # Activate the window
    if not pcomm.activate_window():
        print("Could not activate PCOMM window")
        return
    
    print("PCOMM is ready! Starting automation...")
    time.sleep(2)
    
    # Your automation commands here
    try:
        # Example automation sequence
        print("Sending test commands...")
        
        # Wait a bit more for terminal to be ready
        time.sleep(2)
        
        # Send your commands here
        pcomm.send_text("LOGON")
        time.sleep(1)
        
        pcomm.send_enter()
        time.sleep(2)
        
        # Send function key
        pcomm.send_function_key(3)  # F3
        
        print("Automation completed successfully!")
        
    except Exception as e:
        print(f"Error during automation: {e}")

def quick_test_with_file():
    """Quick test function - modify the file path"""
    # PUT YOUR .WS FILE PATH HERE
    ws_file = input("Enter full path to your .ws file: ").strip().strip('"')
    
    if not ws_file:
        print("No file specified")
        return
    
    pcomm = PCOMMAutomation(ws_file)
    
    print("Opening PCOMM...")
    if pcomm.open_pcomm_workspace():
        if pcomm.find_pcomm_window():
            pcomm.activate_window()
            time.sleep(2)
            
            # Simple test
            print("Sending test message...")
            pcomm.send_text("Hello PCOMM!")
            time.sleep(1)
            pcomm.send_enter()
            
            print("Test completed!")
        else:
            print("Could not find PCOMM window")
    else:
        print("Failed to open workspace")

if __name__ == "__main__":
    # Check if required modules are available
    try:
        import autoit
        import win32gui
        print("All required modules loaded successfully.")
        
        # Run the quick test
        quick_test_with_file()
        
    except ImportError as e:
        print(f"Missing required module: {e}")
        print("\nTo install required packages:")
        print("pip install pyautoit pywin32")