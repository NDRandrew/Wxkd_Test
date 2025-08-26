const { exec } = require('child_process');
const { screen, mouse, keyboard, Key, Button } = require('@nut-tree-fork/nut-js');
const path = require('path');
const fs = require('fs');

// Configure screen matching with lower confidence for more flexible matching
screen.config.confidence = 0.8; // Lower confidence threshold (default is 0.99)
screen.config.resourceDirectory = path.join(__dirname, 'images');

async function automateNotepad() {
  try {
    console.log('Opening Notepad...');
    
    // Open Notepad
    exec('notepad.exe');
    
    // Wait for Notepad to open
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    console.log('Looking for maximize button...');
    
    // Check if image file exists
    const maximizeButtonPath = path.join(__dirname, 'images', 'maximize_button.png');
    console.log('Image path:', maximizeButtonPath);
    
    if (!fs.existsSync(maximizeButtonPath)) {
      console.log('ERROR: maximize_button.png not found in images folder!');
      console.log('Please add the image and try again.');
      return;
    }
    
    console.log('Image file exists, attempting to find on screen...');
    console.log('Current confidence threshold:', screen.config.confidence);
    
    try {
      // Try with different confidence levels
      for (let confidence = 0.8; confidence >= 0.6; confidence -= 0.1) {
        try {
          screen.config.confidence = confidence;
          console.log(`Trying with confidence: ${confidence}`);
          
          const maximizeButton = await screen.find(maximizeButtonPath);
          console.log('Maximize button found at:', maximizeButton);
          console.log(`Success with confidence: ${confidence}`);
          
          // Click on the maximize button
          await mouse.setPosition(maximizeButton);
          await mouse.pressButton(Button.LEFT);
          await mouse.releaseButton(Button.LEFT);
          console.log('Window maximized');
          break;
          
        } catch (innerError) {
          console.log(`Not found with confidence ${confidence}`);
          continue;
        }
      }
      
    } catch (error) {
      console.log('Maximize button not found with any confidence level');
      console.log('Tips to fix this:');
      console.log('1. Make sure Notepad window is visible and not minimized');
      console.log('2. Take a new screenshot of JUST the maximize button (square icon)');
      console.log('3. Ensure the image is PNG format');
      console.log('4. Try taking the screenshot on the same screen resolution/scale');
      console.log('5. Make sure Windows is not in dark/light mode different from when image was taken');
    }
    
    // Wait a moment for the window to maximize
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Click in the text area to ensure focus
    await mouse.setPosition({ x: 400, y: 300 });
    await mouse.pressButton(Button.LEFT);
    await mouse.releaseButton(Button.LEFT);
    
    console.log('Typing text...');
    
    // Type "test" - using individual key presses as requested
    await keyboard.pressKey(Key.T);
    await keyboard.releaseKey(Key.T);
    
    await keyboard.pressKey(Key.E);
    await keyboard.releaseKey(Key.E);
    
    await keyboard.pressKey(Key.S);
    await keyboard.releaseKey(Key.S);
    
    await keyboard.pressKey(Key.T);
    await keyboard.releaseKey(Key.T);
    
    console.log('Automation completed successfully!');
    
  } catch (error) {
    console.error('An error occurred:', error.message);
  }
}

// Run the automation
automateNotepad();

----------

const { screen } = require('@nut-tree-fork/nut-js');
const path = require('path');
const fs = require('fs');

async function debugImageMatching() {
  console.log('=== Image Matching Debug Tool ===\n');
  
  const maximizeButtonPath = path.join(__dirname, 'images', 'maximize_button.png');
  console.log('Image path:', maximizeButtonPath);
  
  // Check if file exists
  if (!fs.existsSync(maximizeButtonPath)) {
    console.log('❌ ERROR: maximize_button.png not found!');
    console.log('Please add the image to the images folder first.');
    return;
  }
  
  console.log('✅ Image file exists');
  
  // Get file size info
  const stats = fs.statSync(maximizeButtonPath);
  console.log(`📁 File size: ${stats.size} bytes`);
  
  console.log('\nTesting different confidence levels...\n');
  
  // Test with different confidence levels
  const confidenceLevels = [0.99, 0.95, 0.9, 0.85, 0.8, 0.75, 0.7, 0.65, 0.6];
  
  for (const confidence of confidenceLevels) {
    try {
      screen.config.confidence = confidence;
      console.log(`🔍 Testing confidence: ${confidence}`);
      
      const result = await screen.find(maximizeButtonPath);
      console.log(`✅ SUCCESS! Found at position: x=${result.left}, y=${result.top}`);
      console.log(`   Confidence ${confidence} worked!`);
      break;
      
    } catch (error) {
      console.log(`❌ Not found with confidence: ${confidence}`);
    }
  }
  
  console.log('\n=== Tips for better image matching ===');
  console.log('1. Open Notepad and make sure the maximize button is visible');
  console.log('2. Take a screenshot of ONLY the maximize button (crop tightly)');
  console.log('3. Save as PNG format');
  console.log('4. Ensure same Windows theme (dark/light mode)');
  console.log('5. Try different window sizes when taking the screenshot');
  console.log('6. Make sure screen scaling is the same as when image was captured');
}

// Run the debug tool
console.log('Starting image matching debug...');
console.log('Make sure Notepad is open before running this!\n');

setTimeout(() => {
  debugImageMatching().catch(console.error);
}, 1000);