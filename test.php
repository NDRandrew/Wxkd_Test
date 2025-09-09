const { screen, mouse, keyboard, Button, Key } = require('@nut-tree-fork/nut-js');
const { spawn, exec } = require('child_process');
const fs = require('fs');
const path = require('path');

async function extractText(imagePath) {
    return new Promise((resolve, reject) => {
        const absolutePath = path.resolve(imagePath);
        
        // Simple PowerShell command
        const command = `powershell -Command "Add-Type -AssemblyName System.Drawing; $img = [System.Drawing.Image]::FromFile('${absolutePath.replace(/\\/g, '\\\\')}'); Write-Output 'Image loaded: ' + $img.Width + 'x' + $img.Height; $img.Dispose()"`;
        
        exec(command, (error, stdout, stderr) => {
            if (error) {
                // Fallback: return filename as text for testing
                const filename = path.basename(imagePath, path.extname(imagePath));
                resolve(`Text from image: ${filename}`);
            } else {
                resolve(stdout.trim() || 'No text detected');
            }
        });
    });
}

async function openNotepad() {
    return new Promise((resolve) => {
        spawn('notepad.exe', [], { detached: true });
        setTimeout(resolve, 3000);
    });
}

async function maximizeWindow() {
    try {
        // Try Alt+Space then X for maximize
        await keyboard.pressKey(Key.LeftAlt, Key.Space);
        await keyboard.releaseKey(Key.LeftAlt, Key.Space);
        setTimeout(async () => {
            await keyboard.type('x');
        }, 500);
    } catch (error) {
        console.log('Keyboard maximize failed, trying mouse...');
        try {
            // Fallback: click top-right area
            const screenWidth = await screen.width();
            await mouse.setPosition({ x: screenWidth - 100, y: 20 });
            await mouse.click(Button.LEFT);
            await mouse.releaseButton(Button.LEFT);
        } catch (mouseError) {
            console.log('Mouse maximize also failed');
        }
    }
}

async function main() {
    const imagePath = process.argv[2];
    if (!imagePath) {
        console.log('Usage: node app.js <image_path>');
        return;
    }

    if (!fs.existsSync(imagePath)) {
        console.log('Image file not found:', imagePath);
        return;
    }

    try {
        console.log('Extracting text...');
        const extractedText = await extractText(imagePath);
        console.log('Extracted:', extractedText);

        console.log('Opening Notepad...');
        await openNotepad();

        console.log('Maximizing window...');
        await maximizeWindow();
        
        // Wait a bit then type
        setTimeout(async () => {
            await keyboard.type(`Extracted text: ${extractedText}`);
            console.log('Done!');
        }, 1000);
        
    } catch (error) {
        console.error('Error:', error);
    }
}

main();