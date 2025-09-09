const { screen, mouse, keyboard, Button } = require('@nut-tree-fork/nut-js');
const tesseract = require('node-tesseract-ocr');
const { spawn } = require('child_process');

async function extractText(imagePath) {
    const config = {
        lang: 'eng',
        oem: 1,
        psm: 3,
    };
    const text = await tesseract.recognize(imagePath, config);
    return text.trim();
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


-------


# OCR Text Extraction with Notepad Automation

## Install Tesseract
Download and install: https://github.com/UB-Mannheim/tesseract/wiki
Add to PATH: `C:\Program Files\Tesseract-OCR`

## Install Dependencies
```bash
npm install
```

## Run
```bash
node app.js path/to/your/image.jpg
```

Uses Tesseract OCR to extract text from images and write it to Notepad. 