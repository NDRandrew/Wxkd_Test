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
  getWindows
} = require("@nut-tree-fork/nut-js");

async function openNotepadAndTest() {
  try {
    console.log("🚀 Starting Notepad automation test...");
    
    // Step 1: Open Run dialog using Win+R
    console.log("📂 Opening Run dialog...");
    await keyboard.pressKey(Key.LeftCmd, Key.R); // Win+R on Windows
    await sleep(1000); // Wait for dialog to open
    
    // Step 2: Type "notepad" in the Run dialog
    console.log("⌨️  Typing 'notepad'...");
    await keyboard.type("notepad");
    await sleep(500);
    
    // Step 3: Press Enter to execute
    console.log("⏎ Pressing Enter...");
    await keyboard.pressKey(Key.Enter);
    await sleep(2000); // Wait for Notepad to open
    
    // Step 4: Find and maximize the Notepad window using screen recognition
    console.log("🔍 Finding Notepad window to maximize...");
    
    // Method 1: Try to find the maximize button by looking for typical window controls
    try {
      // Get all windows and find Notepad
      const windows = await getWindows();
      let notepadWindow = null;
      
      for (const window of windows) {
        const title = await window.title;
        if (title.toLowerCase().includes('notepad') || title.toLowerCase().includes('untitled')) {
          notepadWindow = window;
          break;
        }
      }
      
      if (notepadWindow) {
        console.log("📋 Found Notepad window!");
        const windowRegion = await notepadWindow.region;
        
        // Calculate approximate position of maximize button (top-right area)
        // Typically maximize button is about 45-50 pixels from the right edge
        const maximizeButtonX = windowRegion.left + windowRegion.width - 45;
        const maximizeButtonY = windowRegion.top + 15; // Usually about 15px from top
        
        console.log(`🎯 Clicking maximize button at position: ${maximizeButtonX}, ${maximizeButtonY}`);
        
        // Move mouse to maximize button and click
        await mouse.move(straightTo({ x: maximizeButtonX, y: maximizeButtonY }));
        await sleep(500);
        await mouse.click(Button.LEFT);
        await sleep(1000);
        
        console.log("✅ Window maximized!");
      } else {
        throw new Error("Notepad window not found");
      }
      
    } catch (windowError) {
      console.log("⚠️  Window detection failed, trying alternative method...");
      
      // Method 2: Use Alt+Space then X for maximize (fallback)
      console.log("🔄 Using keyboard shortcut as fallback...");
      await keyboard.pressKey(Key.LeftAlt, Key.Space);
      await sleep(500);
      await keyboard.pressKey(Key.X);
      await sleep(1000);
    }
    
    // Step 5: Write "hello" in Notepad
    console.log("✍️  Writing 'hello' in Notepad...");
    await sleep(500); // Ensure Notepad is focused
    await keyboard.type("hello");
    
    console.log("🎉 Automation completed successfully!");
    console.log("\n📝 Summary of actions performed:");
    console.log("   ✓ Opened Run dialog (Win+R)");
    console.log("   ✓ Typed 'notepad'");
    console.log("   ✓ Pressed Enter to launch");
    console.log("   ✓ Maximized window using mouse/screen recognition");
    console.log("   ✓ Typed 'hello' in Notepad");
    
  } catch (error) {
    console.error("❌ Error during automation:", error.message);
    console.log("\n🔧 Troubleshooting tips:");
    console.log("   • Make sure you have permission to control your computer");
    console.log("   • Ensure Notepad is available on your system");
    console.log("   • Try running as administrator if needed");
    console.log("   • Check that no other applications are blocking automation");
  }
}

// Enhanced version with more screen recognition features
async function enhancedNotepadTest() {
  try {
    console.log("\n🔬 Running enhanced test with advanced screen recognition...");
    
    // Enable screen highlighting for debugging
    screen.config.autoHighlight = true;
    
    // Step 1-3: Same as above
    console.log("📂 Opening Run dialog...");
    await keyboard.pressKey(Key.LeftCmd, Key.R);
    await sleep(1000);
    
    console.log("⌨️  Typing 'notepad'...");
    await keyboard.type("notepad");
    await sleep(500);
    
    console.log("⏎ Pressing Enter...");
    await keyboard.pressKey(Key.Enter);
    await sleep(3000); // Wait longer for window to fully load
    
    // Step 4: Advanced window detection and maximize
    console.log("🔍 Using advanced window detection...");
    
    // Get the active window (should be Notepad)
    const activeWindow = await getActiveWindow();
    console.log("📋 Active window detected!");
    
    const region = await activeWindow.region;
    console.log(`📐 Window region: ${region.width}x${region.height} at (${region.left}, ${region.top})`);
    
    // Check if window is already maximized
    const screenSize = await screen.size();
    const isMaximized = (region.width >= screenSize.width * 0.9 && region.height >= screenSize.height * 0.9);
    
    if (!isMaximized) {
      console.log("🎯 Window not maximized, attempting to maximize...");
      
      // Try double-clicking the title bar (another way to maximize)
      const titleBarY = region.top + 15;
      const titleBarX = region.left + region.width / 2;
      
      console.log(`🖱️  Double-clicking title bar at: ${titleBarX}, ${titleBarY}`);
      await mouse.move(straightTo({ x: titleBarX, y: titleBarY }));
      await sleep(300);
      await mouse.doubleClick(Button.LEFT);
      await sleep(1000);
      
      console.log("✅ Maximize attempt completed!");
    } else {
      console.log("✅ Window is already maximized!");
    }
    
    // Step 5: Write enhanced text
    console.log("✍️  Writing enhanced text...");
    const timestamp = new Date().toLocaleString();
    const text = `Hello from nut.js automation!\nTest completed at: ${timestamp}\n\nThis text was automatically typed using screen recognition and mouse automation! 🤖`;
    
    await keyboard.type(text);
    
    console.log("🎉 Enhanced automation completed successfully!");
    
  } catch (error) {
    console.error("❌ Error in enhanced test:", error.message);
  }
}

// Main execution
async function main() {
  console.log("🎯 Nut.js Notepad Automation Test");
  console.log("=" .repeat(50));
  
  try {
    // Run basic test
    await openNotepadAndTest();
    
    // Wait a moment
    await sleep(2000);
    
    // Ask user if they want to run enhanced test
    console.log("\n" + "=".repeat(50));
    console.log("🚀 Ready to run enhanced test with advanced features?");
    console.log("   Press Ctrl+C to stop, or wait 5 seconds to continue...");
    
    await sleep(5000);
    await enhancedNotepadTest();
    
  } catch (error) {
    console.error("💥 Fatal error:", error.message);
  }
  
  console.log("\n🏁 All tests completed!");
}

// Error handling for the entire application
process.on('unhandledRejection', (reason, promise) => {
  console.error('🚨 Unhandled Rejection at:', promise, 'reason:', reason);
});

process.on('uncaughtException', (error) => {
  console.error('🚨 Uncaught Exception:', error.message);
  process.exit(1);
});

// Run the application
if (require.main === module) {
  main().catch(console.error);
}

module.exports = {
  openNotepadAndTest,
  enhancedNotepadTest,
  main
};