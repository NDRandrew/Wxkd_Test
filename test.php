const { screen, mouse, keyboard, Button } = require('@nut-tree-fork/nut-js');
const { spawn } = require('child_process');
const path = require('path');

async function extractText(imagePath) {
    return new Promise((resolve, reject) => {
        const absolutePath = path.resolve(imagePath);
        const powershellScript = `
Add-Type -AssemblyName System.Drawing
Add-Type -AssemblyName System.Windows.Forms
[Windows.Media.Ocr.OcrEngine, Windows.winmd, ContentType = WindowsRuntime] | Out-Null
[Windows.Storage.StorageFile, Windows.Storage, ContentType = WindowsRuntime] | Out-Null
[Windows.Graphics.Imaging.BitmapDecoder, Windows.Graphics, ContentType = WindowsRuntime] | Out-Null

$ocrEngine = [Windows.Media.Ocr.OcrEngine]::TryCreateFromUserProfileLanguages()
$file = [Windows.Storage.StorageFile]::GetFileFromPathAsync("${absolutePath}").GetAwaiter().GetResult()
$stream = $file.OpenReadAsync().GetAwaiter().GetResult()
$decoder = [Windows.Graphics.Imaging.BitmapDecoder]::CreateAsync($stream).GetAwaiter().GetResult()
$bitmap = $decoder.GetSoftwareBitmapAsync().GetAwaiter().GetResult()
$result = $ocrEngine.RecognizeAsync($bitmap).GetAwaiter().GetResult()
$result.Text
`;
        
        const ps = spawn('powershell', ['-Command', powershellScript], { 
            stdio: ['pipe', 'pipe', 'pipe'],
            shell: true 
        });
        
        let output = '';
        let error = '';
        
        ps.stdout.on('data', (data) => output += data.toString());
        ps.stderr.on('data', (data) => error += data.toString());
        
        ps.on('close', (code) => {
            if (code === 0) {
                resolve(output.trim());
            } else {
                reject(new Error(`PowerShell error: ${error}`));
            }
        });
    });
}

async function openNotepad() {
    return new Promise((resolve) => {
        spawn('notepad.exe');
        setTimeout(resolve, 2000);
    });
}

async function findAndClickMaximize() {
    try {
        const result = await screen.waitFor('maximize_button.png', 5000, { confidence: 0.8 });
        await mouse.leftClick(result);
        await mouse.releaseButton(Button.LEFT);
    } catch {
        const screenSize = await screen.size();
        const maxButtonX = screenSize.width - 50;
        const maxButtonY = 30;
        await mouse.leftClick({ x: maxButtonX, y: maxButtonY });
        await mouse.releaseButton(Button.LEFT);
    }
}

async function main() {
    const imagePath = process.argv[2];
    if (!imagePath) {
        console.log('Usage: node app.js <image_path>');
        return;
    }

    try {
        console.log('Extracting text...');
        const extractedText = await extractText(imagePath);
        console.log('Extracted:', extractedText);

        console.log('Opening Notepad...');
        await openNotepad();

        console.log('Maximizing window...');
        await findAndClickMaximize();
        
        await keyboard.type(`Extracted text: ${extractedText}`);
        
        console.log('Done!');
    } catch (error) {
        console.error('Error:', error);
    }
}

main();