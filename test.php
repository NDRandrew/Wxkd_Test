const { screen, mouse, keyboard, Button, Key } = require('@nut-tree-fork/nut-js');
const tf = require('@tensorflow/tfjs-node');
const cocoSsd = require('@tensorflow-models/coco-ssd');
const fs = require('fs');
const { spawn } = require('child_process');

async function loadImage(imagePath) {
    const imageBuffer = fs.readFileSync(imagePath);
    const tfimage = tf.node.decodeImage(imageBuffer, 3);
    return tfimage;
}

async function detectObjects(imagePath) {
    const model = await cocoSsd.load();
    const image = await loadImage(imagePath);
    const predictions = await model.detect(image);
    image.dispose();
    return predictions;
}

async function openNotepad() {
    return new Promise((resolve) => {
        spawn('notepad.exe');
        setTimeout(resolve, 2000);
    });
}

async function findAndClickMaximize() {
    await screen.waitFor('maximize_button.png', 10000, { confidence: 0.8 })
        .then(async (result) => {
            await mouse.leftClick(result);
            await mouse.releaseButton(Button.LEFT);
        })
        .catch(async () => {
            const screenSize = await screen.size();
            const maxButtonX = screenSize.width - 50;
            const maxButtonY = 30;
            await mouse.leftClick({ x: maxButtonX, y: maxButtonY });
            await mouse.releaseButton(Button.LEFT);
        });
}

async function main() {
    const imagePath = process.argv[2];
    if (!imagePath) {
        console.log('Usage: node app.js <image_path>');
        return;
    }

    try {
        console.log('Detecting objects...');
        const predictions = await detectObjects(imagePath);
        
        const detectedObjects = predictions.map(p => p.class).join(', ');
        console.log('Detected:', detectedObjects);

        console.log('Opening Notepad...');
        await openNotepad();

        console.log('Maximizing window...');
        await findAndClickMaximize();
        
        await keyboard.type(`Detected objects: ${detectedObjects}`);
        
        console.log('Done!');
    } catch (error) {
        console.error('Error:', error);
    }
}

main();


