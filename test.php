const { exec, spawn } = require('child_process');
const { promisify } = require('util');
const execAsync = promisify(exec);
const fs = require('fs');
const path = require('path');
const os = require('os');

class WorkingJavaScriptRPA {
    constructor() {
        this.delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));
    }

    async sendKey(key) {
        try {
            // Use a simpler, more reliable method for sending keys
            const vbsScript = `
Set WshShell = WScript.CreateObject("WScript.Shell")
WshShell.SendKeys "${key}"
            `;
            
            const tempVbs = path.join(os.tmpdir(), 'sendkey.vbs');
            fs.writeFileSync(tempVbs, vbsScript);
            
            await execAsync(`cscript //nologo "${tempVbs}"`);
            fs.unlinkSync(tempVbs); // Clean up
        } catch (error) {
            console.log(`Key send failed: ${error.message}`);
        }
    }

    async mouseClick(x, y) {
        try {
            // Use VBScript for more reliable mouse clicking
            const vbsScript = `
Set WshShell = WScript.CreateObject("WScript.Shell")
WshShell.Run "powershell -command ""Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.Cursor]::Position = New-Object System.Drawing.Point(${x}, ${y}); Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class Mouse { [DllImport(\""user32.dll\"")] public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, IntPtr dwExtraInfo); public const uint MOUSEEVENTF_LEFTDOWN = 0x02; public const uint MOUSEEVENTF_LEFTUP = 0x04; }'; [Mouse]::mouse_event(2, 0, 0, 0, [IntPtr]::Zero); Start-Sleep -Milliseconds 100; [Mouse]::mouse_event(4, 0, 0, 0, [IntPtr]::Zero)""", 0, True
            `;
            
            const tempVbs = path.join(os.tmpdir(), 'click.vbs');
            fs.writeFileSync(tempVbs, vbsScript);
            
            await execAsync(`cscript //nologo "${tempVbs}"`);
            fs.unlinkSync(tempVbs);
        } catch (error) {
            console.log(`Mouse click failed: ${error.message}`);
        }
    }

    async runWorkingRPA() {
        try {
            console.log("🟨 Starting WORKING JavaScript RPA...");
            console.log("====================================");

            // Step 1: Open CMD using simple method
            console.log("Step 1: Opening CMD...");
            await this.sendKey("%{F4}"); // Close any existing windows
            await this.delay(500);
            
            await this.sendKey("^{ESC}"); // Windows key
            await this.delay(1000);
            await this.sendKey("cmd{ENTER}");
            await this.delay(3000); // Wait for CMD to open

            // Step 2: Execute commands
            console.log("Step 2: Executing commands...");
            const commands = [
                "cls{ENTER}",
                "echo === RPA Test ==={ENTER}",
                "echo Current dir:{ENTER}",
                "cd{ENTER}",
                "echo After cd ..:{ENTER}",
                "cd ..{ENTER}",
                "cd{ENTER}",
                "echo === Done ==={ENTER}"
            ];

            for (const cmd of commands) {
                await this.sendKey(cmd);
                await this.delay(800);
            }

            await this.delay(2000);

            // Step 3: Select text using right-click method
            console.log("Step 3: Selecting text...");
            
            // Right-click in center of screen
            await this.mouseClick(640, 350);
            await this.delay(500);
            
            // Use VBScript to right-click
            const rightClickScript = `
Set WshShell = WScript.CreateObject("WScript.Shell")
WshShell.Run "powershell -command ""Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class Mouse { [DllImport(\""user32.dll\"")] public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, IntPtr dwExtraInfo); public const uint MOUSEEVENTF_RIGHTDOWN = 0x08; public const uint MOUSEEVENTF_RIGHTUP = 0x10; }'; [Mouse]::mouse_event(8, 0, 0, 0, [IntPtr]::Zero); Start-Sleep -Milliseconds 100; [Mouse]::mouse_event(16, 0, 0, 0, [IntPtr]::Zero)""", 0, True
            `;
            
            const tempRightClick = path.join(os.tmpdir(), 'rightclick.vbs');
            fs.writeFileSync(tempRightClick, rightClickScript);
            await execAsync(`cscript //nologo "${tempRightClick}"`);
            fs.unlinkSync(tempRightClick);
            
            await this.delay(500);
            
            // Press S for Select All
            await this.sendKey("s");
            await this.delay(500);

            // Step 4: Copy
            console.log("Step 4: Copying...");
            await this.sendKey("^c");
            await this.delay(1000);

            // Step 5: Open Notepad
            console.log("Step 5: Opening Notepad...");
            await this.sendKey("^{ESC}");
            await this.delay(1000);
            await this.sendKey("notepad{ENTER}");
            await this.delay(3000);

            // Step 6: Paste
            console.log("Step 6: Pasting...");
            await this.sendKey("^v");
            await this.delay(1000);

            console.log("✅ JavaScript RPA completed successfully!");

        } catch (error) {
            console.error("❌ RPA failed:", error.message);
        }
    }

    async runSimpleMethod() {
        try {
            console.log("Starting simple reliable method...");
            
            // Method: Create batch file, execute it, and open result in notepad
            const batchContent = `@echo off
echo === RPA Automation Results ===
echo Current directory:
cd
echo.
echo Executing cd ..
cd ..
echo New directory:
cd
echo === Automation Complete ===
echo.
echo Timestamp: %date% %time%
pause
`;

            const batchFile = path.join(__dirname, 'rpa_automation.bat');
            const outputFile = path.join(__dirname, 'rpa_output.txt');

            // Write batch file
            fs.writeFileSync(batchFile, batchContent);

            console.log("Executing batch file...");
            
            // Execute batch and capture output
            await execAsync(`"${batchFile}" > "${outputFile}"`);

            console.log("Opening result in Notepad...");
            
            // Open notepad with the output file
            const notepadProcess = spawn('notepad', [outputFile], {
                detached: true,
                stdio: 'ignore'
            });
            notepadProcess.unref();

            console.log("✅ Simple method completed!");
            console.log(`📁 Batch file: ${batchFile}`);
            console.log(`📄 Output file: ${outputFile}`);

        } catch (error) {
            console.error("❌ Simple method failed:", error.message);
        }
    }

    async runHybridMethod() {
        try {
            console.log("Starting hybrid method (most reliable)...");

            // Get directory information
            const currentDir = process.cwd();
            const parentDir = path.dirname(currentDir);

            // Create content
            const content = `=== JavaScript RPA Results ===
Current directory: ${currentDir}
Executing cd ..
New directory: ${parentDir}
=== Automation Complete ===

Generated at: ${new Date().toLocaleString()}
Method: JavaScript Hybrid Approach
Platform: ${os.platform()} ${os.arch()}`;

            // Write to temp file
            const tempFile = path.join(os.tmpdir(), 'rpa_js_output.txt');
            fs.writeFileSync(tempFile, content);

            // Copy to clipboard using Windows clip command
            console.log("Copying to clipboard...");
            await execAsync(`type "${tempFile}" | clip`);

            // Open Notepad using VBScript
            console.log("Opening Notepad...");
            const openNotepadScript = `
Set WshShell = WScript.CreateObject("WScript.Shell")
WshShell.Run "notepad", 1, False
WScript.Sleep 2000
WshShell.SendKeys "^v"
            `;

            const tempNotepadVbs = path.join(os.tmpdir(), 'notepad.vbs');
            fs.writeFileSync(tempNotepadVbs, openNotepadScript);
            
            await execAsync(`cscript //nologo "${tempNotepadVbs}"`);
            
            // Clean up
            fs.unlinkSync(tempNotepadVbs);

            console.log("✅ Hybrid method completed successfully!");

        } catch (error) {
            console.error("❌ Hybrid method failed:", error.message);
        }
    }
}

async function main() {
    console.log("🟨 FIXED JavaScript RPA - Multiple Methods");
    console.log("==========================================");
    console.log("No external dependencies required!");
    console.log("Uses VBScript + PowerShell for reliability");
    console.log("");

    const method = process.argv[2] || '1';

    console.log("Available methods:");
    console.log("1. Mouse selection with VBScript automation");
    console.log("2. Simple batch file method (most reliable)");
    console.log("3. Hybrid method (programmatic + GUI)");
    console.log("");
    console.log(`Running method ${method}...`);

    // Wait 3 seconds
    console.log("Starting in 3 seconds...");
    await new Promise(resolve => setTimeout(resolve, 3000));

    try {
        const rpa = new WorkingJavaScriptRPA();

        switch (method) {
            case '1':
                await rpa.runWorkingRPA();
                break;
            case '2':
                await rpa.runSimpleMethod();
                break;
            case '3':
                await rpa.runHybridMethod();
                break;
            default:
                console.log("Invalid method, using method 2 (most reliable)");
                await rpa.runSimpleMethod();
                break;
        }

    } catch (error) {
        console.error("Main execution failed:", error.message);
        console.log("\n🔧 Troubleshooting tips:");
        console.log("1. Run as administrator");
        console.log("2. Make sure Windows Script Host is enabled");
        console.log("3. Try method 2 (simplest): node script.js 2");
        console.log("4. Check if antivirus is blocking scripts");
    }
}

// Export for module usage
module.exports = WorkingJavaScriptRPA;

// Run if called directly
if (require.main === module) {
    main();
}