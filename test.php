const { screen, mouse, keyboard, Button, Key } = require('@nut-tree-fork/nut-js');
const { spawn } = require('child_process');
const fs = require('fs');
const path = require('path');
const https = require('https');

async function extractText(imagePath) {
    return new Promise((resolve, reject) => {
        const imageBuffer = fs.readFileSync(imagePath);
        const base64Image = imageBuffer.toString('base64');
        
        const postData = JSON.stringify({
            base64Image: `data:image/jpeg;base64,${base64Image}`,
            language: 'eng'
        });
        
        const options = {
            hostname: 'api.ocr.space',
            port: 443,
            path: '/parse/image',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': postData.length,
                'apikey': 'helloworld' // Free API key
            }
        };
        
        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', (chunk) => data += chunk);
            res.on('end', () => {
                try {
                    const result = JSON.parse(data);
                    if (result.ParsedResults && result.ParsedResults[0]) {
                        resolve(result.ParsedResults[0].ParsedText.trim());
                    } else {
                        resolve('No text found');
                    }
                } catch (error) {
                    resolve('OCR parsing failed');
                }
            });
        });
        
        req.on('error', (error) => {
            resolve('Network error - using fallback');
        });
        
        req.write(postData);
        req.end();
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
        await keyboard.pressKey(Key.LeftAlt, Key.Space);
        await keyboard.releaseKey(Key.LeftAlt, Key.Space);
        await new Promise(resolve => setTimeout(resolve, 500));
        await keyboard.type('x');
        await new Promise(resolve => setTimeout(resolve, 1000));
    } catch (error) {
        console.log('Maximize failed:', error.message);
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
        console.log('Extracting text with OCR...');
        const extractedText = await extractText(imagePath);
        console.log('Extracted:', extractedText);

        console.log('Opening Notepad...');
        await openNotepad();

        console.log('Maximizing window...');
        await maximizeWindow();
        
        await keyboard.type(`Text from image: ${extractedText}`);
        console.log('Done!');
        
    } catch (error) {
        console.error('Error:', error);
    }
}

main();