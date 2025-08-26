const { exec } = require('child_process');
const { screen, mouse, keyboard, Key, Button } = require('@nut-tree-fork/nut-js');
const path = require('path');

async function automateNotepad() {
  try {
    console.log('Opening Notepad...');
    
    // Open Notepad
    exec('notepad.exe');
    
    // Wait for Notepad to open
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    console.log('Looking for maximize button...');
    
    // Load the maximize button image and find it on screen
    const maximizeButtonPath = path.join(__dirname, 'images', 'maximize_button.png');
    
    try {
      // Find the maximize button using image recognition
      const maximizeButton = await screen.find(maximizeButtonPath);
      console.log('Maximize button found at:', maximizeButton);
      
      // Click on the maximize button
      await mouse.setPosition(maximizeButton);
      await mouse.pressButton(Button.LEFT);
      await mouse.releaseButton(Button.LEFT);
      console.log('Window maximized');
      
    } catch (error) {
      console.log('Maximize button not found, window might already be maximized or image missing');
      console.log('Make sure to add the maximize_button.png image to the /images folder');
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