' IBM PCOMM Automation Script
' This script opens a PCOMM workspace, reads from screen, and writes data

Option Explicit

Dim autECLSession, autECLPS, autECLOIA
Dim wsFile, sessionId
Dim screenText, row, col

' Configuration
wsFile = "C:\Path\To\Your\Workspace.ws"  ' Update this path
sessionId = "A"  ' Default session ID, change if different
row = 1          ' Row to read from (1-based)
col = 1          ' Column to read from (1-based)

' Initialize PCOMM objects
On Error Resume Next

' Create the session object
Set autECLSession = CreateObject("PCOMM.autECLSession")
If Err.Number <> 0 Then
    WScript.Echo "Error: Could not create PCOMM Session object. Error: " & Err.Description
    WScript.Quit 1
End If

' Start the session with workspace file
autECLSession.SetConnectionByName wsFile
If Err.Number <> 0 Then
    WScript.Echo "Error: Could not open workspace file: " & wsFile & ". Error: " & Err.Description
    WScript.Quit 1
End If

' Create Presentation Space object
Set autECLPS = CreateObject("PCOMM.autECLPS")
If Err.Number <> 0 Then
    WScript.Echo "Error: Could not create Presentation Space object. Error: " & Err.Description
    WScript.Quit 1
End If

' Connect to the session
autECLPS.SetConnectionByName wsFile
If Err.Number <> 0 Then
    WScript.Echo "Error: Could not connect to session. Error: " & Err.Description
    WScript.Quit 1
End If

' Create OIA (Operator Information Area) object for status checking
Set autECLOIA = CreateObject("PCOMM.autECLOIA")
autECLOIA.SetConnectionByName wsFile

' Wait for the session to be ready
WScript.Echo "Waiting for session to be ready..."
Do While autECLOIA.InputInhibited <> 0
    WScript.Sleep 500
Loop

WScript.Echo "Session is ready!"

' Read text from specified position
On Error Resume Next
screenText = autECLPS.GetText(row, col, 20)  ' Read 20 characters from row/col
If Err.Number <> 0 Then
    WScript.Echo "Error reading from screen: " & Err.Description
Else
    WScript.Echo "Text read from row " & row & ", col " & col & ": '" & screenText & "'"
End If

' Write text to the screen at position (3, 1)
On Error Resume Next
autECLPS.SetCursorPos 3, 1
autECLPS.SendKeys "Hello from VBS automation!"
If Err.Number <> 0 Then
    WScript.Echo "Error writing to screen: " & Err.Description
Else
    WScript.Echo "Successfully wrote text to screen at row 3, col 1"
End If

' Send Enter key
autECLPS.SendKeys "[enter]"
WScript.Sleep 1000

' Read the entire screen (optional - for debugging)
Dim fullScreen
fullScreen = autECLPS.GetText(1, 1, autECLPS.NumRows * autECLPS.NumCols)
WScript.Echo "Full screen content preview (first 100 chars): " & Left(fullScreen, 100)

' Clean up
Set autECLSession = Nothing
Set autECLPS = Nothing
Set autECLOIA = Nothing

WScript.Echo "PCOMM automation completed successfully!"


----------


#!/usr/bin/env python3
"""
Python script to call IBM PCOMM automation VBScript
This script executes the VBS file and captures output
"""

import subprocess
import os
import sys
from pathlib import Path

def run_pcomm_automation(vbs_script_path):
    """
    Execute the PCOMM VBScript automation
    
    Args:
        vbs_script_path (str): Path to the VBScript file
    
    Returns:
        tuple: (return_code, stdout, stderr)
    """
    
    # Check if VBS file exists
    if not os.path.exists(vbs_script_path):
        print(f"Error: VBScript file not found: {vbs_script_path}")
        return None, None, None
    
    try:
        # Execute the VBScript using cscript.exe
        print(f"Executing PCOMM automation script: {vbs_script_path}")
        
        # Use cscript.exe to run VBScript in console mode
        cmd = ["cscript.exe", "//NoLogo", vbs_script_path]
        
        # Run the command and capture output
        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=60  # 60 second timeout
        )
        
        return result.returncode, result.stdout, result.stderr
        
    except subprocess.TimeoutExpired:
        print("Error: Script execution timed out (60 seconds)")
        return -1, "", "Timeout expired"
    except FileNotFoundError:
        print("Error: cscript.exe not found. Make sure you're running on Windows.")
        return -1, "", "cscript.exe not found"
    except Exception as e:
        print(f"Error executing script: {str(e)}")
        return -1, "", str(e)

def main():
    """Main function to run the PCOMM automation"""
    
    # Path to the VBScript file (adjust as needed)
    script_dir = Path(__file__).parent
    vbs_script = script_dir / "pcomm_automation.vbs"
    
    print("=" * 50)
    print("IBM PCOMM Automation - Python Caller")
    print("=" * 50)
    
    # Run the automation
    return_code, stdout, stderr = run_pcomm_automation(str(vbs_script))
    
    if return_code is None:
        return 1
    
    # Display results
    print(f"\nScript execution completed with return code: {return_code}")
    
    if stdout:
        print("\n--- Script Output ---")
        print(stdout)
    
    if stderr:
        print("\n--- Error Output ---")
        print(stderr)
    
    # Return appropriate exit code
    return return_code

if __name__ == "__main__":
    exit_code = main()
    sys.exit(exit_code)


---------

# IBM PCOMM Automation Setup Guide

## Prerequisites

1. **IBM Personal Communications (PCOMM)** must be installed on your system
2. **Windows Operating System** (PCOMM automation uses COM objects)
3. **Python 3.x** installed
4. A valid PCOMM workspace file (`.ws`)

## Files Overview

- `pcomm_automation.vbs` - VBScript that handles PCOMM automation
- `python_caller.py` - Python script that executes the VBScript

## Setup Instructions

### 1. Configure the VBScript

Edit `pcomm_automation.vbs` and update these variables:

```vbscript
wsFile = "C:\Path\To\Your\Workspace.ws"  ' Update with your actual workspace file path
sessionId = "A"                          ' Update if your session ID is different
row = 1                                  ' Row to read from (1-based)
col = 1                                  ' Column to read from (1-based)
```

### 2. Place Files in Same Directory

Put both files in the same directory for easy execution.

### 3. Test the Automation

Run from command prompt:
```bash
python python_caller.py
```

Or execute the VBScript directly:
```bash
cscript.exe pcomm_automation.vbs
```

## What the Scripts Do

### VBScript Functions:
1. **Opens PCOMM workspace** - Connects to your mainframe/AS400 session
2. **Waits for ready state** - Ensures the session is ready for input
3. **Reads screen text** - Gets text from specified row/column position
4. **Writes text** - Sends text to the terminal screen
5. **Sends Enter key** - Simulates pressing Enter
6. **Error handling** - Provides detailed error messages

### Python Functions:
1. **Executes VBScript** - Calls the VBS file using `cscript.exe`
2. **Captures output** - Shows all VBS output and errors
3. **Timeout handling** - Prevents hanging scripts
4. **Return codes** - Proper exit codes for automation chains

## Common PCOMM Methods

Here are additional methods you can use in your VBScript:

```vbscript
' Screen Reading
text = autECLPS.GetText(row, col, length)
char = autECLPS.GetTextRect(startRow, startCol, endRow, endCol)

' Writing/Input
autECLPS.SetCursorPos row, col
autECLPS.SendKeys "your text here"
autECLPS.SendKeys "[enter]"    ' Special keys: [enter], [tab], [pf1], etc.

' Screen Properties
rows = autECLPS.NumRows
cols = autECLPS.NumCols
cursorRow = autECLPS.CursorPosRow
cursorCol = autECLPS.CursorPosCol

' Status Checking
isConnected = autECLOIA.CommStarted
isReady = (autECLOIA.InputInhibited = 0)
```

## Troubleshooting

### Common Issues:

1. **"Could not create PCOMM Session object"**
   - Ensure PCOMM is properly installed
   - Check if PCOMM automation is enabled
   - Try running as Administrator

2. **"Could not open workspace file"**
   - Verify the workspace file path is correct
   - Ensure the `.ws` file exists and is accessible
   - Check file permissions

3. **"Session not ready" / Timeout**
   - Increase wait time in the script
   - Check if the host system is accessible
   - Verify network connectivity

4. **"cscript.exe not found"**
   - You're not running on Windows
   - Windows Script Host is disabled

### Debug Tips:

1. **Test workspace manually** - Open your `.ws` file in PCOMM first
2. **Check session ID** - Verify your session identifier (usually "A", "B", etc.)
3. **Use PCOMM macro recorder** - Record actions to see exact syntax
4. **Enable PCOMM logging** - Check PCOMM logs for connection issues

## Extending the Automation

You can extend this basic example to:

- Read/write multiple screen fields
- Navigate through multiple screens
- Handle different session states
- Parse screen data and make decisions
- Create complex workflows
- Add retry logic and error recovery

## Security Notes

- Store credentials securely (not in scripts)
- Use proper error handling
- Test thoroughly before production use
- Consider using PCOMM's built-in security features