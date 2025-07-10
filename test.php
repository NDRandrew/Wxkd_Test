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

async mouseMove(x, y) {
    const ps = `Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.Cursor]::Position = New-Object System.Drawing.Point(${x}, ${y})`;
    await execAsync(`powershell -command "${ps}"`);
}

async mouseClick(x, y) {
        const ps = `
            Add-Type -AssemblyName System.Windows.Forms;
            Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class Mouse { [DllImport("user32.dll")] public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, IntPtr dwExtraInfo); }';
            [System.Windows.Forms.Cursor]::Position = New-Object System.Drawing.Point(${x}, ${y});
            [Mouse]::mouse_event(0x02, 0, 0, 0, [IntPtr]::Zero);
            Start-Sleep -Milliseconds 50;
            [Mouse]::mouse_event(0x04, 0, 0, 0, [IntPtr]::Zero);
        `;
        await execAsync(`powershell -command "${ps}"`);
    }


async mouseDrag(x1, y1, x2, y2) {
        try {
            // Move to start position
            await execAsync(`powershell -command "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.Cursor]::Position = New-Object System.Drawing.Point(${x1}, ${y1})"`);
            await this.delay(100);
            
            // Press mouse down
            await execAsync(`powershell -command "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class M { [DllImport(\\"user32.dll\\")] public static extern void mouse_event(uint f, uint x, uint y, uint d, IntPtr e); }'; [M]::mouse_event(0x02, 0, 0, 0, [IntPtr]::Zero)"`);
            await this.delay(50);
            
            // Move to end position while holding button
            await execAsync(`powershell -command "Add-Type -AssemblyName System.Windows.Forms; [System.Windows.Forms.Cursor]::Position = New-Object System.Drawing.Point(${x2}, ${y2})"`);
            await this.delay(100);
            
            // Release mouse button
            await execAsync(`powershell -command "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class M { [DllImport(\\"user32.dll\\")] public static extern void mouse_event(uint f, uint x, uint y, uint d, IntPtr e); }'; [M]::mouse_event(0x04, 0, 0, 0, [IntPtr]::Zero)"`);
            
            console.log(`Dragged from (${x1}, ${y1}) to (${x2}, ${y2})`);
        } catch (error) {
            console.error('Drag failed:', error.message);
        }
    }

async mouseShiftDrag(x1, y1, x2, y2) {
        try {
            // Press Shift down
            await execAsync(`powershell -command "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class K { [DllImport(\\"user32.dll\\")] public static extern void keybd_event(byte k, byte s, uint f, IntPtr e); }'; [K]::keybd_event(0x10, 0, 0, [IntPtr]::Zero)"`);
            await this.delay(50);
            
            // Do the drag
            await this.mouseDrag(x1, y1, x2, y2);
            
            // Release Shift
            await execAsync(`powershell -command "Add-Type -TypeDefinition 'using System; using System.Runtime.InteropServices; public class K { [DllImport(\\"user32.dll\\")] public static extern void keybd_event(byte k, byte s, uint f, IntPtr e); }'; [K]::keybd_event(0x10, 0, 0x02, [IntPtr]::Zero)"`);
            
            console.log(`Shift+Dragged from (${x1}, ${y1}) to (${x2}, ${y2})`);
        } catch (error) {
            console.error('Shift+Drag failed:', error.message);
        }
    }    

    async sendWinR() {
        try {
            const vbsScript = `
Set WshShell = CreateObject("WScript.Shell")
WshShell.Run "notepad"`;
            const tempVbs = path.join(os.tmpdir(), 'winr.vbs');
            fs.writeFileSync(tempVbs, vbsScript);
            await execAsync(`cscript //nologo "${tempVbs}"`);
            fs.unlinkSync(tempVbs);
        } catch (error) {
            console.log(`WIN+R failed: ${error.message}`);
        }
    }


    async maximizeCMDWindow() {
        try {
            const vbsScript = `
Set WshShell = CreateObject("WScript.Shell")
WshShell.SendKeys "(%{ })"
WScript.Sleep 200
WshShell.SendKeys "x"
        `;
            const tempVbs = path.join(os.tmpdir(), 'maximize.vbs');
            fs.writeFileSync(tempVbs, vbsScript);
            await execAsync(`cscript //nologo "${tempVbs}"`);
            fs.unlinkSync(tempVbs);
        } catch (error) {
            console.log(`Maximize failed: ${error.message}`);
        }
    }

    async runWorkingRPA() {
        try {

            console.log("Step 1: Opening CMD with Windows + R...");
            await this.delay(500);
            
            // Use Windows + R instead of Ctrl+Esc
            await this.sendWinR();
            await this.delay(1000);

            // Step 1.5: Maximize the CMD window
            await this.maximizeCMDWindow();

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

            console.log("Step 3: Selecting text from top-left to bottom-right...");
            await this.mouseShiftDrag(463, 323, 14, 75);



            console.log("Step 4: Copying...");
            await this.sendKey("^c");
            await this.delay(1000);

            console.log("Step 5: Opening Notepad...");
            await this.sendKey("^{ESC}");
            await this.delay(1000);
            await this.sendKey("run");
            await this.delay(1000);
            await this.sendKey("{ENTER}");
            await this.delay(1000);
            await this.sendKey("notepad{ENTER}");
            await this.delay(3000);

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
            
            // Open notepad with the output file using Windows + R
            await this.sendKey("#{r}");
            await this.delay(1000);
            await this.sendKey(`notepad "${outputFile}"{ENTER}`);

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

            // Open Notepad using Windows + R
            console.log("Opening Notepad...");
            await this.sendKey("#{r}");
            await this.delay(1000);
            await this.sendKey("notepad{ENTER}");
            await this.delay(3000);
            await this.sendKey("^v"); // Paste content

            console.log("✅ Hybrid method completed successfully!");

        } catch (error) {
            console.error("❌ Hybrid method failed:", error.message);
        }
    }
}

async function main() {
    console.log("🟨 MODIFIED JavaScript RPA - Multiple Methods");
    console.log("==========================================");
    console.log("✨ Uses Windows + R for launching applications");
    console.log("✨ Maximizes CMD window automatically");
    console.log("✨ Mouse selection from top-left to bottom-right");
    console.log("No external dependencies required!");
    console.log("Uses VBScript + PowerShell for reliability");
    console.log("");

    const method = process.argv[2] || '1';

    console.log("Available methods:");
    console.log("1. Mouse selection with VBScript automation (MODIFIED)");
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
        console.log("5. Adjust mouse coordinates if selection doesn't work properly");
    }
}

// Export for module usage
module.exports = WorkingJavaScriptRPA;

// Run if called directly
if (require.main === module) {
    main();
}
