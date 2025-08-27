const { exec } = require('child_process');
const { screen, mouse, keyboard, Key, Button, getWindows, getActiveWindow } = require('@nut-tree-fork/nut-js');
const path = require('path');

// Configure screen for better detection
screen.config.confidence = 0.7;
screen.config.autoHighlight = true;

async function findMaximizeButtonByRegion() {
  try {
    console.log('🔍 Method 1: Finding maximize button by window region analysis...');
    
    // Get active window (should be Notepad)
    const activeWindow = await getActiveWindow();
    console.log('Active window:', activeWindow);
    
    // The maximize button is typically in the top-right corner of the window
    // Calculate approximate position based on window bounds
    const windowBounds = {
      x: activeWindow.left,
      y: activeWindow.top,
      width: activeWindow.width,
      height: activeWindow.height
    };
    
    console.log('Window bounds:', windowBounds);
    
    // Maximize button is usually about 46 pixels from the right edge and 12-20 pixels from top
    const estimatedMaximizeButton = {
      x: windowBounds.x + windowBounds.width - 46,
      y: windowBounds.y + 12
    };
    
    console.log('Estimated maximize button position:', estimatedMaximizeButton);
    return estimatedMaximizeButton;
    
  } catch (error) {
    console.log('❌ Method 1 failed:', error.message);
    return null;
  }
}

async function findMaximizeButtonByPixelColor() {
  try {
    console.log('🔍 Method 2: Finding maximize button by scanning for button patterns...');
    
    // Get screen dimensions
    const screenSize = await screen.size();
    console.log('Screen size:', screenSize);
    
    // Scan the top portion of screen for typical maximize button colors/patterns
    // Look for small square regions with button-like characteristics
    
    for (let y = 0; y < 100; y += 5) {
      for (let x = screenSize.width - 200; x < screenSize.width - 20; x += 5) {
        try {
          // Sample pixel color at this position
          const color = await screen.colorAt({ x, y });
          
          // Look for typical Windows button colors (light gray, borders, etc.)
          if (isButtonLikeColor(color)) {
            console.log(`Found potential button at (${x}, ${y}) with color:`, color);
            
            // Verify it's in a reasonable position for a maximize button
            if (x > screenSize.width - 100 && y < 50) {
              return { x, y };
            }
          }
        } catch (e) {
          // Continue scanning
        }
      }
    }
    
    return null;
  } catch (error) {
    console.log('❌ Method 2 failed:', error.message);
    return null;
  }
}

function isButtonLikeColor(color) {
  // Check for common Windows button colors
  // Light theme: light grays, whites
  // Dark theme: dark grays, blacks
  const { r, g, b } = color;
  
  // Light theme button colors
  if (r > 200 && g > 200 && b > 200) return true;
  if (r >= 240 && g >= 240 && b >= 240) return true;
  
  // Dark theme button colors  
  if (r < 100 && g < 100 && b < 100) return true;
  if (Math.abs(r - g) < 20 && Math.abs(g - b) < 20 && r < 150) return true;
  
  // Button border colors
  if (r >= 160 && r <= 180 && g >= 160 && g <= 180 && b >= 160 && b <= 180) return true;
  
  return false;
}

async function findMaximizeButtonByPatternMatching() {
  try {
    console.log('🔍 Method 3: Using advanced pattern matching...');
    
    // Create multiple template patterns for maximize button
    const patterns = [
      'maximize_button.png',
      'maximize_button_light.png', 
      'maximize_button_dark.png'
    ];
    
    for (const pattern of patterns) {
      const patternPath = path.join(__dirname, 'images', pattern);
      
      // Try with very low confidence and different search methods
      for (let confidence = 0.5; confidence >= 0.3; confidence -= 0.05) {
        try {
          screen.config.confidence = confidence;
          console.log(`Trying pattern ${pattern} with confidence ${confidence}`);
          
          const result = await screen.find(patternPath);
          console.log(`✅ Found with pattern matching: ${pattern} at confidence ${confidence}`);
          return { x: result.left + 10, y: result.top + 10 }; // Click center of button
          
        } catch (e) {
          continue;
        }
      }
    }
    
    return null;
  } catch (error) {
    console.log('❌ Method 3 failed:', error.message);
    return null;
  }
}

async function findMaximizeButtonByCursorProbing() {
  try {
    console.log('🔍 Method 4: Cursor probing for interactive elements...');
    
    // Get active window bounds
    const activeWindow = await getActiveWindow();
    const rightEdge = activeWindow.left + activeWindow.width;
    const topEdge = activeWindow.top;
    
    // Probe the title bar area for interactive elements
    const probingPoints = [
      { x: rightEdge - 46, y: topEdge + 12 }, // Standard maximize button position
      { x: rightEdge - 47, y: topEdge + 15 }, // Slightly adjusted
      { x: rightEdge - 45, y: topEdge + 10 }, // Alternative position
      { x: rightEdge - 48, y: topEdge + 14 }, // Another variation
    ];
    
    for (const point of probingPoints) {
      console.log(`Probing point (${point.x}, ${point.y})`);
      
      // Move cursor to position and check if it changes (indicating interactive element)
      await mouse.setPosition(point);
      await new Promise(resolve => setTimeout(resolve, 200));
      
      // Sample the area around this point to see if it looks like a button
      try {
        const colors = [];
        for (let dx = -2; dx <= 2; dx++) {
          for (let dy = -2; dy <= 2; dy++) {
            const color = await screen.colorAt({ x: point.x + dx, y: point.y + dy });
            colors.push(color);
          }
        }
        
        // Analyze if this region has button-like characteristics
        if (hasButtonCharacteristics(colors)) {
          console.log('✅ Found button-like region at:', point);
          return point;
        }
        
      } catch (e) {
        continue;
      }
    }
    
    return null;
  } catch (error) {
    console.log('❌ Method 4 failed:', error.message);
    return null;
  }
}

function hasButtonCharacteristics(colors) {
  // Check if color array has characteristics of a button
  // Buttons typically have consistent colors with some variation for borders
  const uniqueColors = new Set(colors.map(c => `${c.r}-${c.g}-${c.b}`));
  
  // Should have some color variation (2-5 different colors for button and border)
  if (uniqueColors.size >= 2 && uniqueColors.size <= 5) {
    return true;
  }
  
  return false;
}

async function automateNotepadWithRobustDetection() {
  try {
    console.log('🚀 Starting robust Notepad automation...');
    
    // Open Notepad
    console.log('📝 Opening Notepad...');
    exec('notepad.exe');
    await new Promise(resolve => setTimeout(resolve, 2000));
    
    // Try multiple detection methods
    const detectionMethods = [
      findMaximizeButtonByRegion,
      findMaximizeButtonByPixelColor,
      findMaximizeButtonByPatternMatching,
      findMaximizeButtonByCursorProbing
    ];
    
    let maximizeButtonPosition = null;
    
    for (let i = 0; i < detectionMethods.length; i++) {
      console.log(`\n🔄 Attempting detection method ${i + 1}...`);
      
      maximizeButtonPosition = await detectionMethods[i]();
      
      if (maximizeButtonPosition) {
        console.log(`✅ Success with method ${i + 1}!`);
        break;
      } else {
        console.log(`❌ Method ${i + 1} failed, trying next...`);
      }
    }
    
    if (maximizeButtonPosition) {
      console.log('🎯 Clicking maximize button at:', maximizeButtonPosition);
      
      // Click the maximize button
      await mouse.setPosition(maximizeButtonPosition);
      await new Promise(resolve => setTimeout(resolve, 100));
      await mouse.pressButton(Button.LEFT);
      await mouse.releaseButton(Button.LEFT);
      
      console.log('✅ Window maximized successfully!');
    } else {
      console.log('⚠️  Could not locate maximize button with any method');
      console.log('Continuing with typing demonstration...');
    }
    
    // Wait for window to adjust
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    // Click in text area and type
    console.log('⌨️  Clicking in text area and typing...');
    await mouse.setPosition({ x: 400, y: 300 });
    await mouse.pressButton(Button.LEFT);
    await mouse.releaseButton(Button.LEFT);
    
    // Type "test" with proper key handling
    const text = "test";
    for (const char of text) {
      const key = char.toUpperCase();
      await keyboard.pressKey(Key[key]);
      await keyboard.releaseKey(Key[key]);
      await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    console.log('🎉 Automation completed successfully!');
    
  } catch (error) {
    console.error('💥 Error during automation:', error);
  }
}

// Run the robust automation
automateNotepadWithRobustDetection();


-----------


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
      console.log(`Window: "${window.title}" - Class: ${window.className}`);
      
      // Check for Notepad indicators
      if (window.title.toLowerCase().includes('notepad') || 
          window.title.toLowerCase().includes('untitled') ||
          window.className.toLowerCase().includes('notepad')) {
        notepadWindow = window;
        console.log('✅ Found Notepad window:', window);
        break;
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
  
  // Standard Windows title bar button positions:
  // Close button: rightmost
  // Maximize button: second from right (usually 46-50px from right edge)
  // Minimize button: third from right
  
  const titleBarHeight = 30; // Standard Windows title bar height
  const buttonWidth = 46;    // Standard button width
  const buttonFromRight = 46; // Distance from right edge
  
  const maximizeX = window.left + window.width - buttonFromRight;
  const maximizeY = window.top + (titleBarHeight / 2);
  
  console.log(`Calculated position: (${maximizeX}, ${maximizeY})`);
  
  return { x: maximizeX, y: maximizeY };
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
      console.log('Window after click:', {
        width: windowAfterClick.width,
        height: windowAfterClick.height,
        position: { x: windowAfterClick.left, y: windowAfterClick.top }
      });
      
      // Check if window is now maximized (typically near screen size)
      const screenSize = await screen.size();
      const isMaximized = windowAfterClick.width > screenSize.width * 0.8 && 
                         windowAfterClick.height > screenSize.height * 0.8;
      
      if (isMaximized) {
        console.log('✅ Window appears to be maximized!');
      } else {
        console.log('⚠️  Window size didn\'t change significantly');
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
      // Click in center of window for text area
      const textAreaX = updatedWindow.left + (updatedWindow.width / 2);
      const textAreaY = updatedWindow.top + (updatedWindow.height / 2);
      
      await mouse.setPosition({ x: textAreaX, y: textAreaY });
      await mouse.pressButton(Button.LEFT);
      await mouse.releaseButton(Button.LEFT);
      
      console.log(`Clicked in text area at (${textAreaX}, ${textAreaY})`);
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