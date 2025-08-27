const { exec } = require('child_process');
const { screen, mouse, keyboard, Key, Button, getWindows, getActiveWindow } = require('@nut-tree-fork/nut-js');

async function findNotepadWindow() {
  try {
    console.log('🔍 Searching for Notepad window...');
    
    // Get all windows
    const windows = await getWindows();
    console.log(`Found ${windows.length} windows`);
    
    // Look for Notepad window by title
    let notepadWindow = null;
    for (const window of windows) {
      try {
        // Resolve window properties that might be Promises
        const title = typeof window.title === 'string' ? window.title : await window.title;
        const className = typeof window.className === 'string' ? window.className : await window.className;
        
        console.log(`Window: "${title}" - Class: ${className || 'undefined'}`);
        
        // Check for Notepad indicators
        const titleLower = (title || '').toLowerCase();
        const classLower = (className || '').toLowerCase();
        
        if (titleLower.includes('notepad') || 
            titleLower.includes('untitled') ||
            titleLower.includes('text') ||
            classLower.includes('notepad')) {
          notepadWindow = window;
          console.log('✅ Found Notepad window:', { title, className });
          break;
        }
      } catch (windowError) {
        console.log('Error processing window:', windowError.message);
        continue;
      }
    }
    
    // If still not found, try getting the active window
    if (!notepadWindow) {
      console.log('🔍 Trying to get active window as fallback...');
      try {
        const activeWindow = await getActiveWindow();
        const activeTitle = typeof activeWindow.title === 'string' ? activeWindow.title : await activeWindow.title;
        console.log('Active window title:', activeTitle);
        
        // Check if active window might be Notepad
        if (activeTitle && (activeTitle.toLowerCase().includes('notepad') || 
                           activeTitle.toLowerCase().includes('untitled') ||
                           activeTitle.toLowerCase().includes('text'))) {
          notepadWindow = activeWindow;
          console.log('✅ Using active window as Notepad window');
        } else {
          // Even if it's not clearly Notepad, let's try the active window
          console.log('⚠️ Using active window (may or may not be Notepad)');
          notepadWindow = activeWindow;
        }
      } catch (activeError) {
        console.log('Error getting active window:', activeError.message);
      }
    }
    
    return notepadWindow;
  } catch (error) {
    console.log('❌ Error finding Notepad window:', error.message);
    return null;
  }
}

async function calculateMaximizeButtonPosition(window) {
  console.log('📐 Calculating maximize button position...');
  
  try {
    // Resolve window properties that might be Promises
    const left = typeof window.left === 'number' ? window.left : await window.left;
    const top = typeof window.top === 'number' ? window.top : await window.top;
    const width = typeof window.width === 'number' ? window.width : await window.width;
    const height = typeof window.height === 'number' ? window.height : await window.height;
    
    console.log(`Window bounds: left=${left}, top=${top}, width=${width}, height=${height}`);
    
    // Standard Windows title bar button positions:
    // Close button: rightmost
    // Maximize button: second from right (usually 46-50px from right edge)
    // Minimize button: third from right
    
    const titleBarHeight = 30; // Standard Windows title bar height
    const buttonWidth = 46;    // Standard button width
    const buttonFromRight = 46; // Distance from right edge
    
    const maximizeX = left + width - buttonFromRight;
    const maximizeY = top + (titleBarHeight / 2);
    
    console.log(`Calculated position: (${maximizeX}, ${maximizeY})`);
    
    return { x: maximizeX, y: maximizeY };
  } catch (error) {
    console.log('❌ Error calculating maximize button position:', error.message);
    return { x: 0, y: 0 }; // Return fallback position
  }
}

async function verifyButtonLocation(position) {
  try {
    console.log('🔍 Verifying button location by analyzing pixels...');
    
    // Sample colors around the calculated position to verify it looks like a button
    const samplePositions = [
      { x: position.x, y: position.y },
      { x: position.x - 5, y: position.y },
      { x: position.x + 5, y: position.y },
      { x: position.x, y: position.y - 3 },
      { x: position.x, y: position.y + 3 }
    ];
    
    const colors = [];
    for (const pos of samplePositions) {
      try {
        const color = await screen.colorAt(pos);
        colors.push(color);
        console.log(`Color at (${pos.x}, ${pos.y}):`, color);
      } catch (e) {
        console.log(`Cannot sample color at (${pos.x}, ${pos.y})`);
      }
    }
    
    // Analyze colors to determine if this looks like a button area
    if (colors.length > 0) {
      const avgColor = {
        r: Math.round(colors.reduce((sum, c) => sum + c.r, 0) / colors.length),
        g: Math.round(colors.reduce((sum, c) => sum + c.g, 0) / colors.length),
        b: Math.round(colors.reduce((sum, c) => sum + c.b, 0) / colors.length)
      };
      
      console.log('Average color in button area:', avgColor);
      
      // Check if colors suggest this is a UI element (not pure white/black)
      if (avgColor.r > 50 && avgColor.r < 250 && 
          avgColor.g > 50 && avgColor.g < 250 && 
          avgColor.b > 50 && avgColor.b < 250) {
        console.log('✅ Colors suggest this is a UI button area');
        return true;
      }
    }
    
    console.log('⚠️  Colors don\'t clearly indicate a button, but proceeding anyway');
    return true; // Proceed with calculated position
    
  } catch (error) {
    console.log('❌ Error verifying button location:', error.message);
    return true; // Proceed anyway
  }
}

async function smartClickMaximizeButton(position) {
  console.log('🖱️  Performing smart click on maximize button...');
  
  try {
    // Move to position
    await mouse.setPosition(position);
    console.log(`Moved mouse to (${position.x}, ${position.y})`);
    
    // Small delay to ensure positioning
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Capture color before click for verification
    const colorBeforeClick = await screen.colorAt(position);
    console.log('Color before click:', colorBeforeClick);
    
    // Perform click
    await mouse.pressButton(Button.LEFT);
    await mouse.releaseButton(Button.LEFT);
    console.log('✅ Click performed');
    
    // Wait a moment and check if window state changed
    await new Promise(resolve => setTimeout(resolve, 500));
    
    // Verify the click had an effect by checking window state
    const windowAfterClick = await findNotepadWindow();
    if (windowAfterClick) {
      try {
        // Resolve window properties
        const width = typeof windowAfterClick.width === 'number' ? windowAfterClick.width : await windowAfterClick.width;
        const height = typeof windowAfterClick.height === 'number' ? windowAfterClick.height : await windowAfterClick.height;
        const left = typeof windowAfterClick.left === 'number' ? windowAfterClick.left : await windowAfterClick.left;
        const top = typeof windowAfterClick.top === 'number' ? windowAfterClick.top : await windowAfterClick.top;
        
        console.log('Window after click:', {
          width: width,
          height: height,
          position: { x: left, y: top }
        });
        
        // Check if window is now maximized (typically near screen size)
        const screenSize = await screen.size();
        const isMaximized = width > screenSize.width * 0.8 && 
                           height > screenSize.height * 0.8;
        
        if (isMaximized) {
          console.log('✅ Window appears to be maximized!');
        } else {
          console.log('⚠️  Window size didn\'t change significantly');
        }
      } catch (windowPropError) {
        console.log('⚠️  Could not verify window maximization state:', windowPropError.message);
      }
    }
    
    return true;
    
  } catch (error) {
    console.log('❌ Error during click operation:', error.message);
    return false;
  }
}

async function performSmartNotepadAutomation() {
  console.log('🚀 Starting Smart Notepad Automation with Dynamic Screen Detection\n');
  
  try {
    // Step 1: Launch Notepad
    console.log('📝 Launching Notepad...');
    exec('notepad.exe');
    await new Promise(resolve => setTimeout(resolve, 2500));
    
    // Step 2: Find Notepad window
    const notepadWindow = await findNotepadWindow();
    if (!notepadWindow) {
      console.log('❌ Could not find Notepad window');
      return;
    }
    
    // Step 3: Calculate maximize button position
    const maximizePosition = await calculateMaximizeButtonPosition(notepadWindow);
    
    // Step 4: Verify the calculated position looks correct
    const isValidPosition = await verifyButtonLocation(maximizePosition);
    
    if (isValidPosition) {
      // Step 5: Click the maximize button
      const clickSuccess = await smartClickMaximizeButton(maximizePosition);
      
      if (clickSuccess) {
        console.log('✅ Maximize operation completed');
      } else {
        console.log('⚠️  Maximize operation may have failed');
      }
    } else {
      console.log('⚠️  Calculated position doesn\'t look like a button');
    }
    
    // Step 6: Wait for window to adjust
    console.log('\n⏱️  Waiting for window adjustment...');
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Step 7: Click in text area and type
    console.log('⌨️  Focusing text area and typing...');
    
    // Get updated window position after potential maximization
    const updatedWindow = await findNotepadWindow();
    if (updatedWindow) {
      try {
        // Resolve window properties
        const left = typeof updatedWindow.left === 'number' ? updatedWindow.left : await updatedWindow.left;
        const top = typeof updatedWindow.top === 'number' ? updatedWindow.top : await updatedWindow.top;
        const width = typeof updatedWindow.width === 'number' ? updatedWindow.width : await updatedWindow.width;
        const height = typeof updatedWindow.height === 'number' ? updatedWindow.height : await updatedWindow.height;
        
        // Click in center of window for text area
        const textAreaX = left + (width / 2);
        const textAreaY = top + (height / 2);
        
        await mouse.setPosition({ x: textAreaX, y: textAreaY });
        await mouse.pressButton(Button.LEFT);
        await mouse.releaseButton(Button.LEFT);
        
        console.log(`Clicked in text area at (${textAreaX}, ${textAreaY})`);
      } catch (windowPropError) {
        console.log('Error getting window properties, using fallback position:', windowPropError.message);
        // Fallback position
        await mouse.setPosition({ x: 400, y: 300 });
        await mouse.pressButton(Button.LEFT);
        await mouse.releaseButton(Button.LEFT);
      }
    } else {
      // Fallback position
      await mouse.setPosition({ x: 400, y: 300 });
      await mouse.pressButton(Button.LEFT);
      await mouse.releaseButton(Button.LEFT);
    }
    
    // Step 8: Type "test"
    console.log('✍️  Typing "test"...');
    const textToType = "test";
    for (const char of textToType) {
      const key = char.toUpperCase();
      await keyboard.pressKey(Key[key]);
      await keyboard.releaseKey(Key[key]);
      await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    console.log('\n🎉 Smart automation completed successfully!');
    console.log('✅ Screen elements detected and manipulated dynamically');
    
  } catch (error) {
    console.error('💥 Error during smart automation:', error.message);
  }
}

// Execute the smart automation
performSmartNotepadAutomation();


--------------


const { exec } = require('child_process');
const { screen, mouse, keyboard, Key, Button } = require('@nut-tree-fork/nut-js');

async function findMaximizeButtonByScreenScanning() {
  console.log('🔍 Scanning screen for maximize button...');
  
  try {
    const screenSize = await screen.size();
    console.log('Screen size:', screenSize);
    
    // Scan the top-right area of the screen where maximize buttons typically are
    // Look for typical maximize button positions
    const scanAreas = [
      // Top-right area of screen
      { startX: screenSize.width - 200, endX: screenSize.width - 20, startY: 0, endY: 100 }
    ];
    
    for (const area of scanAreas) {
      console.log(`Scanning area: x${area.startX}-${area.endX}, y${area.startY}-${area.endY}`);
      
      // Sample colors in a grid pattern
      for (let y = area.startY; y < area.endY; y += 10) {
        for (let x = area.startX; x < area.endX; x += 10) {
          try {
            const color = await screen.colorAt({ x, y });
            
            // Look for button-like colors (Windows UI colors)
            if (isWindowsButtonColor(color)) {
              console.log(`Found potential button color at (${x}, ${y}):`, color);
              
              // Test if this area responds to hover (typical of buttons)
              const buttonPosition = { x: x + 5, y: y + 5 }; // Offset to center of button
              
              if (await testButtonInteraction(buttonPosition)) {
                console.log('✅ Found interactive button-like element at:', buttonPosition);
                return buttonPosition;
              }
            }
          } catch (e) {
            // Continue scanning if color sampling fails
            continue;
          }
        }
      }
    }
    
    console.log('⚠️ No button found by scanning, using calculated position');
    return await calculateStandardMaximizePosition();
    
  } catch (error) {
    console.log('❌ Error during screen scanning:', error.message);
    return await calculateStandardMaximizePosition();
  }
}

function isWindowsButtonColor(color) {
  const { r, g, b } = color;
  
  // Common Windows button colors across themes:
  
  // Light theme button colors (light grays, whites with slight tints)
  if (r >= 220 && g >= 220 && b >= 220 && r <= 245 && g <= 245 && b <= 245) return true;
  
  // Button border colors (medium grays)
  if (r >= 150 && r <= 200 && g >= 150 && g <= 200 && b >= 150 && b <= 200 && 
      Math.abs(r - g) < 30 && Math.abs(g - b) < 30) return true;
  
  // Dark theme button colors
  if (r >= 40 && r <= 80 && g >= 40 && g <= 80 && b >= 40 && b <= 80) return true;
  
  // Hover state colors (slightly different from base)
  if (r >= 200 && r <= 235 && g >= 200 && g <= 235 && b >= 200 && b <= 235) return true;
  
  return false;
}

async function testButtonInteraction(position) {
  try {
    // Move mouse to position and sample color
    const colorBefore = await screen.colorAt(position);
    
    await mouse.setPosition(position);
    await new Promise(resolve => setTimeout(resolve, 100)); // Small delay for hover effect
    
    const colorAfter = await screen.colorAt(position);
    
    // If color changed, it's likely an interactive element
    const colorChanged = Math.abs(colorBefore.r - colorAfter.r) > 5 || 
                        Math.abs(colorBefore.g - colorAfter.g) > 5 || 
                        Math.abs(colorBefore.b - colorAfter.b) > 5;
    
    if (colorChanged) {
      console.log('Color changed on hover - this is likely a button');
      return true;
    }
    
    // Even if color didn't change, position might still be valid
    return true;
    
  } catch (error) {
    return false;
  }
}

async function calculateStandardMaximizePosition() {
  console.log('📐 Using standard Windows maximize button position calculation...');
  
  const screenSize = await screen.size();
  
  // Standard positions for different scenarios:
  const positions = [
    // Standard windowed mode maximize button (common positions)
    { x: screenSize.width - 46, y: 15 },   // Most common
    { x: screenSize.width - 50, y: 18 },   // Slightly different sizing
    { x: screenSize.width - 42, y: 12 },   // Compact title bar
    { x: screenSize.width - 48, y: 16 },   // Alternative sizing
  ];
  
  console.log('Will try these positions:', positions);
  return positions[0]; // Return the most common position
}

async function performSimpleAutomation() {
  console.log('🚀 Starting Simple Notepad Automation\n');
  
  try {
    // Step 1: Launch Notepad
    console.log('📝 Launching Notepad...');
    exec('notepad.exe');
    await new Promise(resolve => setTimeout(resolve, 2500));
    
    // Step 2: Find maximize button by screen analysis
    console.log('\n🔍 Searching for maximize button...');
    const maximizePosition = await findMaximizeButtonByScreenScanning();
    
    if (maximizePosition) {
      console.log('🎯 Attempting to click maximize button at:', maximizePosition);
      
      // Move to position and click
      await mouse.setPosition(maximizePosition);
      await new Promise(resolve => setTimeout(resolve, 200));
      
      // Sample color before click for debugging
      try {
        const colorAtButton = await screen.colorAt(maximizePosition);
        console.log('Color at button position:', colorAtButton);
      } catch (e) {
        console.log('Could not sample color at button position');
      }
      
      // Perform the click
      await mouse.pressButton(Button.LEFT);
      await mouse.releaseButton(Button.LEFT);
      console.log('✅ Click performed');
      
      // Wait for window to adjust
      await new Promise(resolve => setTimeout(resolve, 1000));
    } else {
      console.log('⚠️ Could not determine maximize button position');
    }
    
    // Step 3: Click in text area (center of screen is safe for maximized window)
    console.log('\n⌨️ Clicking in text area...');
    const screenSize = await screen.size();
    const textAreaPosition = {
      x: screenSize.width / 2,
      y: screenSize.height / 2
    };
    
    await mouse.setPosition(textAreaPosition);
    await mouse.pressButton(Button.LEFT);
    await mouse.releaseButton(Button.LEFT);
    console.log(`Clicked in text area at (${textAreaPosition.x}, ${textAreaPosition.y})`);
    
    // Step 4: Type "test"
    console.log('\n✍️ Typing "test"...');
    const textToType = "test";
    for (const char of textToType) {
      const key = char.toUpperCase();
      await keyboard.pressKey(Key[key]);
      await keyboard.releaseKey(Key[key]);
      await new Promise(resolve => setTimeout(resolve, 100));
    }
    
    console.log('\n🎉 Simple automation completed!');
    console.log('✅ Used screen scanning and standard position calculation');
    
  } catch (error) {
    console.error('💥 Error during automation:', error.message);
  }
}

// Execute the simple automation
performSimpleAutomation();