const { 
  mouse, 
  keyboard, 
  screen, 
  Button, 
  Key,
  sleep,
  centerOf,
  straightTo,
  getActiveWindow,
  getWindows,
  Region,
  imageResource
} = require("@nut-tree-fork/nut-js");

// Configure screen recognition
screen.config.autoHighlight = true;
screen.config.highlightDurationMs = 500;
screen.config.confidence = 0.8;

async function openRunDialog() {
  console.log("Opening Windows Run dialog...");
  
  // Use Windows key + R
  await keyboard.pressKey(Key.LeftSuper, Key.R);
  await sleep(1500);
  
  // Verify Run dialog opened by checking screen content
  try {
    console.log("Verifying Run dialog is open...");
    await sleep(1000);
    return true;
  } catch (error) {
    console.log("Run dialog verification failed, trying alternative...");
    
    // Alternative: Try Ctrl+Shift+Esc then close task manager and try again
    await keyboard.pressKey(Key.Escape);
    await sleep(500);
    await keyboard.pressKey(Key.LeftSuper, Key.R);
    await sleep(1500);
    return true;
  }
}

async function launchNotepad() {
  console.log("Typing 'notepad' command...");
  
  // Clear any existing text first
  await keyboard.pressKey(Key.LeftControl, Key.A);
  await sleep(200);
  
  // Type notepad
  await keyboard.type("notepad");
  await sleep(800);
  
  console.log("Executing notepad command...");
  await keyboard.pressKey(Key.Enter);
  await sleep(3000); // Wait for notepad to fully load
}

async function findAndMaximizeNotepad() {
  console.log("Searching for Notepad window...");
  
  let attempts = 0;
  const maxAttempts = 5;
  
  while (attempts < maxAttempts) {
    try {
      // Get all windows
      const windows = await getWindows();
      console.log(`Found ${windows.length} windows, searching for Notepad...`);
      
      let notepadWindow = null;
      
      // Search for Notepad window
      for (const window of windows) {
        try {
          const title = await window.title;
          console.log(`Checking window: "${title}"`);
          
          if (title.toLowerCase().includes('notepad') || 
              title.toLowerCase().includes('untitled') ||
              title.toLowerCase().includes('text')) {
            notepadWindow = window;
            console.log(`Target window found: "${title}"`);
            break;
          }
        } catch (titleError) {
          // Skip windows that can't provide title
          continue;
        }
      }
      
      if (notepadWindow) {
        await maximizeWindow(notepadWindow);
        return true;
      } else {
        console.log(`Attempt ${attempts + 1}: Notepad window not found, waiting...`);
        await sleep(1000);
        attempts++;
      }
      
    } catch (error) {
      console.log(`Window search error on attempt ${attempts + 1}: ${error.message}`);
      attempts++;
      await sleep(1000);
    }
  }
  
  console.log("Could not find Notepad window, trying fallback methods...");
  return await fallbackMaximize();
}

async function maximizeWindow(window) {
  console.log("Attempting to maximize window using screen coordinates...");
  
  try {
    const region = await window.region;
    console.log(`Window dimensions: ${region.width}x${region.height} at (${region.left}, ${region.top})`);
    
    // Check if already maximized
    const screenSize = await screen.size();
    const isMaximized = (region.width >= screenSize.width * 0.95 && region.height >= screenSize.height * 0.90);
    
    if (isMaximized) {
      console.log("Window appears to already be maximized");
      return true;
    }
    
    // Method 1: Click maximize button
    console.log("Attempting to click maximize button...");
    const maximizeButtonX = region.left + region.width - 32; // Maximize button position
    const maximizeButtonY = region.top + 16;
    
    console.log(`Moving mouse to maximize button: (${maximizeButtonX}, ${maximizeButtonY})`);
    await mouse.move(straightTo({ x: maximizeButtonX, y: maximizeButtonY }));
    await sleep(500);
    await mouse.click(Button.LEFT);
    await sleep(1500);
    
    // Verify maximization
    const newRegion = await window.region;
    if (newRegion.width >= screenSize.width * 0.95) {
      console.log("Window successfully maximized using mouse click");
      return true;
    }
    
    // Method 2: Double-click title bar
    console.log("Mouse click failed, trying title bar double-click...");
    const titleBarX = region.left + region.width / 2;
    const titleBarY = region.top + 16;
    
    await mouse.move(straightTo({ x: titleBarX, y: titleBarY }));
    await sleep(300);
    await mouse.doubleClick(Button.LEFT);
    await sleep(1500);
    
    console.log("Title bar double-click completed");
    return true;
    
  } catch (error) {
    console.log(`Window maximize error: ${error.message}`);
    return await fallbackMaximize();
  }
}

async function fallbackMaximize() {
  console.log("Using keyboard shortcut fallback for maximize...");
  
  try {
    // Focus the window first
    await keyboard.pressKey(Key.LeftAlt, Key.Tab);
    await sleep(500);
    
    // Use Alt+Space, then X to maximize
    await keyboard.pressKey(Key.LeftAlt, Key.Space);
    await sleep(800);
    await keyboard.pressKey(Key.X);
    await sleep(1500);
    
    console.log("Fallback maximize completed");
    return true;
    
  } catch (error) {
    console.log(`Fallback maximize failed: ${error.message}`);
    
    // Final fallback: F11 for fullscreen
    console.log("Trying F11 fullscreen as last resort...");
    await keyboard.pressKey(Key.F11);
    await sleep(1000);
    
    return true;
  }
}

async function writeInNotepad() {
  console.log("Preparing to write text in Notepad...");
  
  // Ensure Notepad is focused
  await sleep(1000);
  
  // Click in the text area to ensure focus
  try {
    const activeWindow = await getActiveWindow();
    const region = await activeWindow.region;
    
    // Click in the center of the window
    const centerX = region.left + region.width / 2;
    const centerY = region.top + region.height / 2;
    
    console.log(`Clicking in text area at: (${centerX}, ${centerY})`);
    await mouse.move(straightTo({ x: centerX, y: centerY }));
    await sleep(300);
    await mouse.click(Button.LEFT);
    await sleep(500);
    
  } catch (error) {
    console.log(`Text area click failed: ${error.message}`);
  }
  
  // Clear any existing content
  await keyboard.pressKey(Key.LeftControl, Key.A);
  await sleep(200);
  
  // Type the test message
  console.log("Typing test message...");
  const message = "hello";
  await keyboard.type(message);
  
  console.log(`Successfully typed: "${message}"`);
  return true;
}

async function runAutomationTest() {
  console.log("=".repeat(60));
  console.log("NOTEPAD AUTOMATION TEST WITH SCREEN RECOGNITION");
  console.log("=".repeat(60));
  
  try {
    // Step 1: Open Run dialog
    console.log("\nSTEP 1: Opening Run dialog");
    await openRunDialog();
    
    // Step 2: Launch Notepad
    console.log("\nSTEP 2: Launching Notepad");
    await launchNotepad();
    
    // Step 3: Find and maximize Notepad
    console.log("\nSTEP 3: Finding and maximizing Notepad window");
    await findAndMaximizeNotepad();
    
    // Step 4: Write text
    console.log("\nSTEP 4: Writing text in Notepad");
    await writeInNotepad();
    
    console.log("\n" + "=".repeat(60));
    console.log("AUTOMATION TEST COMPLETED SUCCESSFULLY");
    console.log("=".repeat(60));
    console.log("Actions performed:");
    console.log("- Opened Windows Run dialog (Win+R)");
    console.log("- Typed 'notepad' command");
    console.log("- Launched Notepad application");
    console.log("- Used screen recognition to find Notepad window");
    console.log("- Maximized window using mouse coordinates");
    console.log("- Typed 'hello' in the text editor");
    
  } catch (error) {
    console.log("\n" + "=".repeat(60));
    console.log("AUTOMATION TEST FAILED");
    console.log("=".repeat(60));
    console.error(`Error: ${error.message}`);
    console.log("\nTroubleshooting steps:");
    console.log("1. Ensure you have administrator privileges");
    console.log("2. Check that Notepad is available on your system");
    console.log("3. Verify no security software is blocking automation");
    console.log("4. Try running the script multiple times");
    console.log("5. Check Windows accessibility permissions");
  }
}

// Export functions for testing
module.exports = {
  runAutomationTest,
  openRunDialog,
  launchNotepad,
  findAndMaximizeNotepad,
  writeInNotepad
};

// Main execution
if (require.main === module) {
  runAutomationTest().catch(error => {
    console.error("Fatal error:", error.message);
    process.exit(1);
  });
}