import autoit
import win32gui
import win32con
import time

class PCOMMAutomation:
    def __init__(self):
        self.window_handle = None
        self.window_title = "IBM Personal Communications"  # Adjust if needed
    
    def find_pcomm_window(self):
        """Find IBM PCOMM window"""
        try:
            # Try to find window by title (you may need to adjust the title)
            self.window_handle = win32gui.FindWindow(None, self.window_title)
            if self.window_handle == 0:
                # Alternative search - look for any window containing "IBM" or "PCOMM"
                def enum_windows_proc(hwnd, lParam):
                    window_text = win32gui.GetWindowText(hwnd)
                    if "IBM" in window_text or "PCOMM" in window_text or "Personal Communications" in window_text:
                        self.window_handle = hwnd
                        return False  # Stop enumeration
                    return True
                
                win32gui.EnumWindows(enum_windows_proc, 0)
            
            if self.window_handle != 0:
                print(f"Found PCOMM window: {win32gui.GetWindowText(self.window_handle)}")
                return True
            else:
                print("PCOMM window not found. Make sure IBM PCOMM is running.")
                return False
                
        except Exception as e:
            print(f"Error finding PCOMM window: {e}")
            return False
    
    def activate_window(self):
        """Bring PCOMM window to foreground"""
        if self.window_handle:
            try:
                win32gui.SetForegroundWindow(self.window_handle)
                win32gui.ShowWindow(self.window_handle, win32con.SW_RESTORE)
                time.sleep(0.5)
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
        # PF keys often mapped to F keys in PCOMM
        return self.send_function_key(pf_number)

def main():
    """Simple test function"""
    print("IBM PCOMM Automation Test")
    print("=" * 30)
    
    # Create automation instance
    pcomm = PCOMMAutomation()
    
    # Find PCOMM window
    if not pcomm.find_pcomm_window():
        print("Please start IBM PCOMM and try again.")
        return
    
    # Activate the window
    if not pcomm.activate_window():
        print("Could not activate PCOMM window.")
        return
    
    print("PCOMM window activated. Starting automation test...")
    time.sleep(2)
    
    # Simple test automation
    try:
        # Example: Send a command (adjust based on your mainframe/system)
        pcomm.send_text("LOGON")
        time.sleep(1)
        
        pcomm.send_enter()
        time.sleep(1)
        
        # Send a function key
        pcomm.send_function_key(3)  # F3
        
        print("Test automation completed successfully!")
        
    except Exception as e:
        print(f"Error during automation: {e}")

if __name__ == "__main__":
    # Check if required modules are available
    try:
        import autoit
        import win32gui
        print("All required modules loaded successfully.")
        main()
    except ImportError as e:
        print(f"Missing required module: {e}")
        print("\nTo install required packages:")
        print("pip install pyautoit pywin32")
        print("\nNote: For 64-bit Python, if pyautoit causes issues, you can use alternative methods with just pywin32.")


---------


import win32gui
import win32api
import win32con
import time

class SimplePCOMMAutomation:
    def __init__(self):
        self.window_handle = None
    
    def find_pcomm_window(self):
        """Find IBM PCOMM window using only pywin32"""
        def enum_windows_proc(hwnd, lParam):
            window_text = win32gui.GetWindowText(hwnd)
            class_name = win32gui.GetClassName(hwnd)
            
            # Look for IBM PCOMM window
            if any(keyword in window_text.upper() for keyword in ["IBM", "PCOMM", "PERSONAL COMMUNICATIONS"]):
                self.window_handle = hwnd
                print(f"Found window: {window_text} (Class: {class_name})")
                return False  # Stop enumeration
            return True
        
        win32gui.EnumWindows(enum_windows_proc, 0)
        return self.window_handle is not None
    
    def send_keystrokes(self, keys):
        """Send keystrokes using win32api (more compatible with 64-bit)"""
        if self.window_handle:
            # Activate window
            win32gui.SetForegroundWindow(self.window_handle)
            time.sleep(0.5)
            
            # Send each character
            for char in keys:
                if char == '\n':
                    # Send Enter
                    win32api.keybd_event(win32con.VK_RETURN, 0, 0, 0)
                    win32api.keybd_event(win32con.VK_RETURN, 0, win32con.KEYEVENTF_KEYUP, 0)
                else:
                    # Convert char to virtual key code
                    vk_code = win32api.VkKeyScan(char)
                    if vk_code != -1:
                        win32api.keybd_event(vk_code & 0xFF, 0, 0, 0)
                        win32api.keybd_event(vk_code & 0xFF, 0, win32con.KEYEVENTF_KEYUP, 0)
                time.sleep(0.1)  # Small delay between keystrokes
    
    def send_function_key(self, f_key_number):
        """Send function key F1-F12"""
        if self.window_handle:
            win32gui.SetForegroundWindow(self.window_handle)
            time.sleep(0.5)
            
            # F1 = VK_F1 (0x70), F2 = 0x71, etc.
            vk_code = 0x6F + f_key_number  # VK_F1 starts at 0x70
            win32api.keybd_event(vk_code, 0, 0, 0)
            win32api.keybd_event(vk_code, 0, win32con.KEYEVENTF_KEYUP, 0)

def simple_test():
    """Ultra-simple test"""
    print("Simple PCOMM Test (pywin32 only)")
    
    pcomm = SimplePCOMMAutomation()
    
    if pcomm.find_pcomm_window():
        print("PCOMM window found!")
        
        # Wait for user to position cursor if needed
        print("Sending test commands in 3 seconds...")
        time.sleep(3)
        
        # Send simple test
        pcomm.send_keystrokes("TEST")
        time.sleep(1)
        pcomm.send_keystrokes("\n")  # Enter
        time.sleep(1)
        pcomm.send_function_key(3)   # F3
        
        print("Test completed!")
    else:
        print("PCOMM window not found. Make sure it's running.")

if __name__ == "__main__":
    simple_test()