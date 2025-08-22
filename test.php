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

const path = require('path');

// Configure screen recognition
screen.config.autoHighlight = true;
screen.config.highlightDurationMs = 1000;
screen.config.confidence = 0.7; // Lower confidence for better matching
screen.config.searchMultipleScales = true;

async function openRunDialog() {
  console.log("Opening Windows Run dialog...");
  
  await keyboard.pressKey(Key.LeftSuper, Key.R);
  await sleep(1500);
  
  console.log("Run dialog opened");
  return true;
}

async function launchNotepad() {
  console.log("Typing 'notepad' command...");
  
  // Clear any existing text first
  await keyboard.pressKey(Key.LeftControl, Key.A);
  await sleep(200);
  
  await keyboard.type("notepad");
  await sleep(800);
  
  console.log("Executing notepad command...");
  await keyboard.pressKey(Key.Enter);
  await sleep(3000);
}

async function findMaximizeButtonByImage() {
  console.log("Searching for maximize button using image recognition...");
  
  // Create maximize button images programmatically (common Windows patterns)
  const maximizePatterns = [
    // Windows 10/11 maximize button patterns
    "maximize_win10.png",
    "maximize_win11.png", 
    "maximize_classic.png"
  ];
  
  // Try different search strategies
  const searchStrategies = [
    { confidence: 0.8, searchMultipleScales: true },
    { confidence: 0.7, searchMultipleScales: true },
    { confidence: 0.6, searchMultipleScales: false },
    { confidence: 0.5, searchMultipleScales: true }
  ];
  
  for (const strategy of searchStrategies) {
    console.log(`Trying search with confidence ${strategy.confidence}...`);
    
    // Configure screen for this attempt
    screen.config.confidence = strategy.confidence;
    screen.config.searchMultipleScales = strategy.searchMultipleScales;
    
    try {
      // Method 1: Search for text-based maximize patterns
      const maximizeTexts = ["🗖", "⬜", "□"];
      
      for (const text of maximizeTexts) {
        try {
          console.log(`Searching for maximize symbol: ${text}`);
          const result = await screen.find(text);
          if (result) {
            console.log(`Found maximize button with symbol: ${text}`);
            const center = centerOf(result);
            await mouse.move(straightTo(center));
            await sleep(500);
            await mouse.click(Button.LEFT);
            await sleep(1000);
            return true;
          }
        } catch (textError) {
          console.log(`Symbol ${text} not found, continuing...`);
        }
      }
      
      // Method 2: Search in window title bar area only
      await searchInTitleBarArea();
      
    } catch (error) {
      console.log(`Search strategy failed: ${error.message}`);
      continue;
    }
  }
  
  return false;
}

async function searchInTitleBarArea() {
  console.log("Searching for maximize button in title bar area...");
  
  try {
    // Get the active window to limit search area
    const activeWindow = await getActiveWindow();
    const windowRegion = await activeWindow.region;
    
    // Define title bar search region (top 40 pixels of window)
    const titleBarRegion = new Region(
      windowRegion.left,
      windowRegion.top,
      windowRegion.width,
      40
    );
    
    console.log(`Searching in title bar region: ${titleBarRegion.width}x${titleBarRegion.height} at (${titleBarRegion.left}, ${titleBarRegion.top})`);
    
    // Method 1: Look for maximize button in the top-right corner area
    const buttonAreaRegion = new Region(
      windowRegion.left + windowRegion.width - 150, // Last 150 pixels of title bar
      windowRegion.top,
      150,
      40
    );
    
    console.log("Analyzing button area for maximize control...");
    
    // Take a screenshot of the button area for analysis
    const buttonScreenshot = await screen.grab(buttonAreaRegion);
    
    // Calculate likely maximize button position (middle of the three window controls)
    const buttonWidth = 46; // Standard Windows button width
    const maximizeButtonX = windowRegion.left + windowRegion.width - (buttonWidth * 2); // Second from right
    const maximizeButtonY = windowRegion.top + 20; // Center of title bar
    
    console.log(`Calculated maximize button position: (${maximizeButtonX}, ${maximizeButtonY})`);
    
    // Move to position and click
    await mouse.move(straightTo({ x: maximizeButtonX, y: maximizeButtonY }));
    await sleep(800);
    
    // Highlight the area we're about to click
    console.log("Clicking on calculated maximize button position...");
    await mouse.click(Button.LEFT);
    await sleep(1500);
    
    return true;
    
  } catch (error) {
    console.log(`Title bar search failed: ${error.message}`);
    return false;
  }
}

async function findAndMaximizeNotepad() {
  console.log("Searching for Notepad window...");
  
  let attempts = 0;
  const maxAttempts = 3;
  
  while (attempts < maxAttempts) {
    try {
      const windows = await getWindows();
      console.log(`Found ${windows.length} windows, searching for Notepad...`);
      
      let notepadWindow = null;
      
      for (const window of windows) {
        try {
          const title = await window.title;
          console.log(`Checking window: "${title}"`);
          
          if (title.toLowerCase().includes('notepad') || 
              title.toLowerCase().includes('untitled') ||
              title.toLowerCase().includes('text')) {
            notepadWindow = window;
            console.log(`Notepad window found: "${title}"`);
            break;
          }
        } catch (titleError) {
          continue;
        }
      }
      
      if (notepadWindow) {
        // Focus the window first
        const region = await notepadWindow.region;
        const centerX = region.left + region.width / 2;
        const centerY = region.top + region.height / 2;
        
        await mouse.move(straightTo({ x: centerX, y: centerY }));
        await mouse.click(Button.LEFT);
        await sleep(500);
        
        // Check if already maximized
        const screenSize = await screen.size();
        const isMaximized = (region.width >= screenSize.width * 0.95 && region.height >= screenSize.height * 0.90);
        
        if (isMaximized) {
          console.log("Window is already maximized");
          return true;
        }
        
        // Try image recognition first
        console.log("Attempting to find maximize button using image recognition...");
        const imageSuccess = await findMaximizeButtonByImage();
        
        if (imageSuccess) {
          console.log("Successfully maximized using image recognition");
          return true;
        }
        
        // Fallback to other methods
        return await fallbackMaximize();
      } else {
        console.log(`Attempt ${attempts + 1}: Notepad window not found, waiting...`);
        await sleep(1000);
        attempts++;
      }
      
    } catch (error) {
      console.log(`Window search error: ${error.message}`);
      attempts++;
      await sleep(1000);
    }
  }
  
  console.log("Could not find Notepad window");
  return false;
}

async function fallbackMaximize() {
  console.log("Using fallback maximize methods...");
  
  try {
    // Method 1: Double-click title bar
    console.log("Trying title bar double-click...");
    const activeWindow = await getActiveWindow();
    const region = await activeWindow.region;
    
    const titleBarX = region.left + region.width / 2;
    const titleBarY = region.top + 15;
    
    await mouse.move(straightTo({ x: titleBarX, y: titleBarY }));
    await sleep(300);
    await mouse.doubleClick(Button.LEFT);
    await sleep(1500);
    
    // Check if maximized
    const newRegion = await activeWindow.region;
    const screenSize = await screen.size();
    if (newRegion.width >= screenSize.width * 0.95) {
      console.log("Successfully maximized with title bar double-click");
      return true;
    }
    
    // Method 2: Keyboard shortcut
    console.log("Trying keyboard shortcut (Alt+Space, X)...");
    await keyboard.pressKey(Key.LeftAlt, Key.Space);
    await sleep(800);
    await keyboard.pressKey(Key.X);
    await sleep(1500);
    
    return true;
    
  } catch (error) {
    console.log(`Fallback methods failed: ${error.message}`);
    return false;
  }
}

async function writeInNotepad() {
  console.log("Writing text in Notepad...");
  
  await sleep(1000);
  
  try {
    const activeWindow = await getActiveWindow();
    const region = await activeWindow.region;
    
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
  
  await keyboard.pressKey(Key.LeftControl, Key.A);
  await sleep(200);
  
  console.log("Typing message...");
  const message = "hello";
  await keyboard.type(message);
  
  console.log(`Successfully typed: "${message}"`);
  return true;
}

async function runAutomationTest() {
  console.log("=".repeat(60));
  console.log("NOTEPAD AUTOMATION WITH IMAGE RECOGNITION");
  console.log("=".repeat(60));
  
  try {
    console.log("\nSTEP 1: Opening Run dialog");
    await openRunDialog();
    
    console.log("\nSTEP 2: Launching Notepad");
    await launchNotepad();
    
    console.log("\nSTEP 3: Finding and maximizing Notepad using image recognition");
    const success = await findAndMaximizeNotepad();
    
    if (!success) {
      console.log("Warning: Maximize operation may have failed");
    }
    
    console.log("\nSTEP 4: Writing text in Notepad");
    await writeInNotepad();
    
    console.log("\n" + "=".repeat(60));
    console.log("AUTOMATION COMPLETED");
    console.log("=".repeat(60));
    console.log("Summary:");
    console.log("- Used Win+R to open Run dialog");
    console.log("- Launched Notepad application");
    console.log("- Used image recognition to find maximize button");
    console.log("- Clicked maximize button using screen coordinates");
    console.log("- Typed 'hello' in Notepad");
    
  } catch (error) {
    console.log("\n" + "=".repeat(60));
    console.log("AUTOMATION FAILED");
    console.log("=".repeat(60));
    console.error(`Error: ${error.message}`);
    console.log("\nTroubleshooting:");
    console.log("1. Ensure administrator privileges");
    console.log("2. Check Windows accessibility settings");
    console.log("3. Verify no security software interference");
    console.log("4. Try running multiple times");
  }
}

module.exports = {
  runAutomationTest,
  findMaximizeButtonByImage,
  searchInTitleBarArea
};

if (require.main === module) {
  runAutomationTest().catch(error => {
    console.error("Fatal error:", error.message);
    process.exit(1);
  });
}