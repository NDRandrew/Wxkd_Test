const Jimp = require('jimp');
const path = require('path');
const fs = require('fs');

async function analyzeImageForText(imagePath) {
  try {
    console.log('🔍 Starting offline text analysis...');
    console.log(`📁 Processing image: ${imagePath}`);
    
    // Check if image file exists
    if (!fs.existsSync(imagePath)) {
      console.log('❌ Error: Image file not found!');
      console.log(`Expected location: ${imagePath}`);
      return;
    }
    
    console.log('✅ Image file found');
    console.log('🖼️  Loading and analyzing image...');
    
    // Load image with Jimp
    const image = await Jimp.read(imagePath);
    console.log(`📊 Image dimensions: ${image.getWidth()}x${image.getHeight()}`);
    
    // Perform text detection analysis
    const textAnalysis = await performTextAnalysis(image);
    
    console.log('\n🎉 Text Analysis Complete!');
    console.log('=' .repeat(50));
    
    displayAnalysisResults(textAnalysis, path.basename(imagePath));
    
  } catch (error) {
    console.error('💥 Error during text analysis:', error.message);
    console.log('\n🔧 Troubleshooting:');
    console.log('1. Make sure the image file is valid (PNG, JPG, etc.)');
    console.log('2. Check that the image contains readable text');
    console.log('3. Try a different image with clearer text');
  }
}

async function performTextAnalysis(image) {
  console.log('🔍 Analyzing image for text patterns...');
  
  const width = image.getWidth();
  const height = image.getHeight();
  
  // Convert to grayscale for easier analysis
  const grayImage = image.clone().greyscale();
  
  // Analysis results
  const analysis = {
    textRegions: [],
    dominantColors: [],
    contrastLevel: 0,
    textLikeAreas: 0,
    hasHighContrast: false,
    estimatedTextLines: 0,
    imageCharacteristics: {}
  };
  
  // 1. Analyze contrast levels
  analysis.contrastLevel = await analyzeContrast(grayImage);
  analysis.hasHighContrast = analysis.contrastLevel > 50;
  
  // 2. Find text-like regions
  analysis.textRegions = await findTextRegions(grayImage);
  analysis.textLikeAreas = analysis.textRegions.length;
  
  // 3. Estimate text lines
  analysis.estimatedTextLines = await estimateTextLines(grayImage);
  
  // 4. Analyze dominant colors
  analysis.dominantColors = await findDominantColors(image);
  
  // 5. Image characteristics
  analysis.imageCharacteristics = {
    width: width,
    height: height,
    aspectRatio: (width / height).toFixed(2),
    size: `${width}x${height}`
  };
  
  return analysis;
}

async function analyzeContrast(grayImage) {
  console.log('📊 Analyzing image contrast...');
  
  let minBrightness = 255;
  let maxBrightness = 0;
  let totalBrightness = 0;
  let pixelCount = 0;
  
  grayImage.scan(0, 0, grayImage.getWidth(), grayImage.getHeight(), function (x, y, idx) {
    const brightness = this.bitmap.data[idx]; // Red channel (same for grayscale)
    
    minBrightness = Math.min(minBrightness, brightness);
    maxBrightness = Math.max(maxBrightness, brightness);
    totalBrightness += brightness;
    pixelCount++;
  });
  
  const avgBrightness = totalBrightness / pixelCount;
  const contrast = maxBrightness - minBrightness;
  
  console.log(`   Brightness range: ${minBrightness} - ${maxBrightness}`);
  console.log(`   Average brightness: ${Math.round(avgBrightness)}`);
  console.log(`   Contrast level: ${contrast}`);
  
  return contrast;
}

async function findTextRegions(grayImage) {
  console.log('🔍 Scanning for text-like regions...');
  
  const regions = [];
  const width = grayImage.getWidth();
  const height = grayImage.getHeight();
  const blockSize = 20; // Size of blocks to analyze
  
  // Scan image in blocks looking for text patterns
  for (let y = 0; y < height - blockSize; y += blockSize) {
    for (let x = 0; x < width - blockSize; x += blockSize) {
      const regionAnalysis = analyzeRegionForText(grayImage, x, y, blockSize, blockSize);
      
      if (regionAnalysis.isTextLike) {
        regions.push({
          x: x,
          y: y,
          width: blockSize,
          height: blockSize,
          confidence: regionAnalysis.confidence,
          characteristics: regionAnalysis.characteristics
        });
      }
    }
  }
  
  console.log(`   Found ${regions.length} potential text regions`);
  return regions;
}

function analyzeRegionForText(image, x, y, width, height) {
  let blackPixels = 0;
  let whitePixels = 0;
  let totalPixels = 0;
  let edgePixels = 0;
  
  // Sample pixels in the region
  for (let dy = 0; dy < height; dy += 2) {
    for (let dx = 0; dx < width; dx += 2) {
      if (x + dx < image.getWidth() && y + dy < image.getHeight()) {
        const pixelColor = image.getPixelColor(x + dx, y + dy);
        const brightness = (pixelColor >> 16) & 0xFF; // Red channel for grayscale
        
        totalPixels++;
        
        if (brightness < 100) blackPixels++;
        else if (brightness > 200) whitePixels++;
        
        // Check for edges (significant brightness changes)
        if (dx > 0 && dy > 0) {
          const prevPixel = image.getPixelColor(x + dx - 2, y + dy);
          const prevBrightness = (prevPixel >> 16) & 0xFF;
          if (Math.abs(brightness - prevBrightness) > 50) {
            edgePixels++;
          }
        }
      }
    }
  }
  
  // Calculate characteristics that suggest text
  const blackWhiteRatio = totalPixels > 0 ? (blackPixels + whitePixels) / totalPixels : 0;
  const edgeRatio = totalPixels > 0 ? edgePixels / totalPixels : 0;
  const contrast = blackPixels > 0 && whitePixels > 0;
  
  // Text typically has:
  // - Good contrast (black and white pixels)
  // - Moderate edge density (letters have edges)
  // - Not too much noise
  const isTextLike = contrast && blackWhiteRatio > 0.3 && edgeRatio > 0.1 && edgeRatio < 0.8;
  
  const confidence = Math.round((blackWhiteRatio * 0.4 + edgeRatio * 0.6) * 100);
  
  return {
    isTextLike: isTextLike,
    confidence: Math.min(confidence, 100),
    characteristics: {
      blackPixels: blackPixels,
      whitePixels: whitePixels,
      edgePixels: edgePixels,
      blackWhiteRatio: Math.round(blackWhiteRatio * 100) / 100,
      edgeRatio: Math.round(edgeRatio * 100) / 100,
      hasContrast: contrast
    }
  };
}

async function estimateTextLines(grayImage) {
  console.log('📏 Estimating text lines...');
  
  const height = grayImage.getHeight();
  const width = grayImage.getWidth();
  let horizontalTransitions = [];
  
  // Scan horizontal lines for text patterns
  for (let y = 0; y < height; y += 5) {
    let transitions = 0;
    let lastBrightness = null;
    
    for (let x = 0; x < width; x += 3) {
      const pixelColor = grayImage.getPixelColor(x, y);
      const brightness = (pixelColor >> 16) & 0xFF;
      
      if (lastBrightness !== null && Math.abs(brightness - lastBrightness) > 50) {
        transitions++;
      }
      lastBrightness = brightness;
    }
    
    horizontalTransitions.push({ y: y, transitions: transitions });
  }
  
  // Find rows with significant transitions (likely text lines)
  const avgTransitions = horizontalTransitions.reduce((sum, row) => sum + row.transitions, 0) / horizontalTransitions.length;
  const textLines = horizontalTransitions.filter(row => row.transitions > avgTransitions * 1.5);
  
  console.log(`   Estimated ${textLines.length} potential text lines`);
  return textLines.length;
}

async function findDominantColors(image) {
  console.log('🎨 Analyzing color palette...');
  
  const colorCounts = {};
  const sampleRate = 10; // Sample every 10th pixel for performance
  
  image.scan(0, 0, image.getWidth(), image.getHeight(), function (x, y, idx) {
    if (x % sampleRate === 0 && y % sampleRate === 0) {
      const red = this.bitmap.data[idx];
      const green = this.bitmap.data[idx + 1];
      const blue = this.bitmap.data[idx + 2];
      
      // Group similar colors
      const colorKey = `${Math.floor(red/32)*32}-${Math.floor(green/32)*32}-${Math.floor(blue/32)*32}`;
      colorCounts[colorKey] = (colorCounts[colorKey] || 0) + 1;
    }
  });
  
  // Find top 5 colors
  const sortedColors = Object.entries(colorCounts)
    .sort(([,a], [,b]) => b - a)
    .slice(0, 5)
    .map(([color, count]) => {
      const [r, g, b] = color.split('-').map(Number);
      return { color: `rgb(${r}, ${g}, ${b})`, count, hex: rgbToHex(r, g, b) };
    });
  
  return sortedColors;
}

function rgbToHex(r, g, b) {
  return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

function displayAnalysisResults(analysis, filename) {
  console.log(`📝 Analysis Results for "${filename}":`);
  console.log('=' .repeat(40));
  
  // Image characteristics
  console.log('📊 Image Properties:');
  console.log(`   Dimensions: ${analysis.imageCharacteristics.size}`);
  console.log(`   Aspect Ratio: ${analysis.imageCharacteristics.aspectRatio}`);
  console.log(`   Contrast Level: ${analysis.contrastLevel}/255`);
  console.log(`   High Contrast: ${analysis.hasHighContrast ? '✅ Yes' : '❌ No'}`);
  
  // Text detection results
  console.log('\n🔍 Text Detection:');
  console.log(`   Potential text regions: ${analysis.textLikeAreas}`);
  console.log(`   Estimated text lines: ${analysis.estimatedTextLines}`);
  
  if (analysis.textRegions.length > 0) {
    console.log('\n📍 Text Region Details:');
    analysis.textRegions.slice(0, 5).forEach((region, index) => {
      console.log(`   Region ${index + 1}: (${region.x}, ${region.y}) ${region.width}x${region.height}`);
      console.log(`     Confidence: ${region.confidence}%`);
    });
    
    if (analysis.textRegions.length > 5) {
      console.log(`   ... and ${analysis.textRegions.length - 5} more regions`);
    }
  }
  
  // Color analysis
  console.log('\n🎨 Dominant Colors:');
  analysis.dominantColors.forEach((colorInfo, index) => {
    console.log(`   ${index + 1}. ${colorInfo.hex} (${colorInfo.color}) - ${colorInfo.count} pixels`);
  });
  
  // Overall assessment
  console.log('\n🎯 Text Likelihood Assessment:');
  let score = 0;
  const reasons = [];
  
  if (analysis.hasHighContrast) {
    score += 30;
    reasons.push('✅ Good contrast detected');
  } else {
    reasons.push('❌ Low contrast may affect text clarity');
  }
  
  if (analysis.textLikeAreas > 5) {
    score += 25;
    reasons.push('✅ Multiple text-like regions found');
  } else if (analysis.textLikeAreas > 0) {
    score += 10;
    reasons.push('⚠️  Few text-like regions found');
  } else {
    reasons.push('❌ No clear text regions detected');
  }
  
  if (analysis.estimatedTextLines > 1) {
    score += 25;
    reasons.push('✅ Multiple text lines estimated');
  } else if (analysis.estimatedTextLines === 1) {
    score += 15;
    reasons.push('⚠️  Single text line estimated');
  } else {
    reasons.push('❌ No clear text lines detected');
  }
  
  // Check for typical text colors (black/white contrast)
  const hasTypicalTextColors = analysis.dominantColors.some(c => 
    c.hex === '#000000' || c.hex === '#ffffff' || 
    c.hex.startsWith('#00') || c.hex.startsWith('#ff')
  );
  
  if (hasTypicalTextColors) {
    score += 20;
    reasons.push('✅ Typical text colors (black/white) detected');
  }
  
  console.log(`Overall Text Likelihood: ${score}/100`);
  console.log('\nAssessment Details:');
  reasons.forEach(reason => console.log(`   ${reason}`));
  
  console.log('\n💡 Recommendations:');
  if (score < 30) {
    console.log('   - This image may not contain readable text');
    console.log('   - Try images with higher contrast');
    console.log('   - Ensure text is dark on light background (or vice versa)');
  } else if (score < 60) {
    console.log('   - Some text may be present but difficult to read');
    console.log('   - Consider improving image quality or contrast');
  } else {
    console.log('   - Good likelihood of readable text content');
    console.log('   - This image should work well with OCR tools');
  }
}

async function main() {
  console.log('🚀 Offline Image Text Analysis Tool');
  console.log('=' .repeat(50));
  console.log('📝 This tool analyzes images for text content without requiring internet connection');
  
  // Default image path
  const defaultImagePath = path.join(__dirname, 'images', 'text_image.png');
  
  // Check command line arguments for custom image path
  const args = process.argv.slice(2);
  const imagePath = args.length > 0 ? args[0] : defaultImagePath;
  
  console.log(`🎯 Target image: ${path.basename(imagePath)}`);
  
  await analyzeImageForText(imagePath);
  
  console.log('\n✅ Text analysis completed!');
  console.log('\n🔧 Note: This is offline analysis. For actual text extraction, use:');
  console.log('   npm run original  (requires internet for Tesseract.js)');
}

// Run the offline text analysis
main().catch(console.error);


------------

const { screen } = require('@nut-tree-fork/nut-js');
const path = require('path');
const fs = require('fs');

// Simple text detection patterns (works completely offline)
const commonUITexts = [
  // Window controls
  'minimize', 'maximize', 'close', 'restore',
  // File operations  
  'file', 'edit', 'view', 'insert', 'format', 'tools', 'help',
  'new', 'open', 'save', 'save as', 'print', 'exit',
  // Common buttons
  'ok', 'cancel', 'yes', 'no', 'apply', 'submit', 'continue',
  'back', 'next', 'finish', 'done', 'close',
  // Common labels
  'name', 'password', 'email', 'address', 'phone', 'date',
  'username', 'login', 'register', 'search', 'settings',
  // Application names
  'notepad', 'word', 'excel', 'browser', 'chrome', 'firefox',
  'windows', 'microsoft', 'google', 'adobe'
];

async function captureAndAnalyzeScreen() {
  try {
    console.log('📸 Capturing current screen...');
    
    // Get screen size
    const screenSize = await screen.size();
    console.log(`Screen dimensions: ${screenSize.width}x${screenSize.height}`);
    
    // Create images directory if it doesn't exist
    const imagesDir = path.join(__dirname, 'images');
    if (!fs.existsSync(imagesDir)) {
      fs.mkdirSync(imagesDir, { recursive: true });
    }
    
    // Capture screenshot
    const screenshotPath = path.join(imagesDir, 'screen_capture.png');
    await screen.capture(screenshotPath);
    console.log('✅ Screenshot saved:', screenshotPath);
    
    // Wait for file to be written
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    console.log('\n🔍 Analyzing screenshot...');
    
    // Perform simple analysis
    await performSimpleScreenAnalysis(screenshotPath, screenSize);
    
  } catch (error) {
    console.error('💥 Error during screen capture:', error.message);
  }
}

async function performSimpleScreenAnalysis(imagePath, screenSize) {
  console.log('📊 Performing basic screen analysis...');
  
  try {
    // Get file information
    const stats = fs.statSync(imagePath);
    console.log(`📁 Screenshot file size: ${Math.round(stats.size / 1024)} KB`);
    
    // Analyze likely UI regions
    const uiRegions = identifyUIRegions(screenSize);
    
    console.log('\n🖥️  Screen Analysis Results:');
    console.log('=' .repeat(40));
    
    console.log('📐 Screen Properties:');
    console.log(`   Resolution: ${screenSize.width}x${screenSize.height}`);
    console.log(`   Aspect Ratio: ${(screenSize.width / screenSize.height).toFixed(2)}`);
    console.log(`   Estimated DPI: ${estimateDPI(screenSize)}`);
    
    console.log('\n🎯 Likely UI Regions:');
    uiRegions.forEach((region, index) => {
      console.log(`   ${index + 1}. ${region.name}: (${region.x}, ${region.y}) ${region.width}x${region.height}`);
      console.log(`      Purpose: ${region.purpose}`);
    });
    
    // Suggest text areas to check
    console.log('\n🔍 Suggested Areas to Check for Text:');
    const textAreas = suggestTextAreas(screenSize);
    textAreas.forEach((area, index) => {
      console.log(`   ${index + 1}. ${area.name}: (${area.x}, ${area.y}) ${area.width}x${area.height}`);
      console.log(`      Likely to contain: ${area.expectedContent}`);
    });
    
    // Generate commands for region capture
    console.log('\n⚡ Quick Commands to Capture Specific Regions:');
    textAreas.forEach((area, index) => {
      if (index < 3) { // Show first 3 commands
        console.log(`   Region ${index + 1}: npm run screen-region -- ${area.x} ${area.y} ${area.width} ${area.height}`);
      }
    });
    
    // Basic pattern suggestions
    console.log('\n🎨 Visual Patterns Analysis:');
    console.log(`   Title bar height: ~30 pixels`);
    console.log(`   Common button size: ~75x23 pixels`);
    console.log(`   Menu bar height: ~20-25 pixels`);
    console.log(`   Scrollbar width: ~17 pixels`);
    
    console.log('\n💡 Text Detection Tips:');
    console.log('   - Title bars typically contain window titles');
    console.log('   - Menu bars contain File, Edit, View, etc.');
    console.log('   - Status bars show information at bottom');
    console.log('   - Content areas contain the main text');
    
  } catch (error) {
    console.error('Error during analysis:', error.message);
  }
}

function identifyUIRegions(screenSize) {
  const regions = [];
  
  // Top title bar area
  regions.push({
    name: 'Title Bar Area',
    x: 0,
    y: 0,
    width: screenSize.width,
    height: 40,
    purpose: 'Window titles, controls (minimize/maximize/close)'
  });
  
  // Menu bar area
  regions.push({
    name: 'Menu Bar Area',
    x: 0,
    y: 40,
    width: screenSize.width,
    height: 25,
    purpose: 'File, Edit, View menus'
  });
  
  // Toolbar area
  regions.push({
    name: 'Toolbar Area',
    x: 0,
    y: 65,
    width: screenSize.width,
    height: 40,
    purpose: 'Tool buttons, formatting controls'
  });
  
  // Main content area
  regions.push({
    name: 'Content Area',
    x: 0,
    y: 105,
    width: screenSize.width,
    height: screenSize.height - 135,
    purpose: 'Main document or application content'
  });
  
  // Status bar area
  regions.push({
    name: 'Status Bar Area',
    x: 0,
    y: screenSize.height - 30,
    width: screenSize.width,
    height: 30,
    purpose: 'Status information, progress bars'
  });
  
  return regions;
}

function suggestTextAreas(screenSize) {
  const areas = [];
  
  // Window title area (most common place for text)
  areas.push({
    name: 'Window Title',
    x: 100,
    y: 5,
    width: screenSize.width - 200,
    height: 30,
    expectedContent: 'Application name, document title'
  });
  
  // Menu bar
  areas.push({
    name: 'Menu Bar',
    x: 0,
    y: 30,
    width: 500,
    height: 25,
    expectedContent: 'File, Edit, View, Insert, Format, Tools, Help'
  });
  
  // Center content area
  areas.push({
    name: 'Main Content',
    x: Math.floor(screenSize.width * 0.1),
    y: Math.floor(screenSize.height * 0.15),
    width: Math.floor(screenSize.width * 0.8),
    height: Math.floor(screenSize.height * 0.6),
    expectedContent: 'Document text, form fields, main content'
  });
  
  // Dialog center (common for popup dialogs)
  areas.push({
    name: 'Dialog Center',
    x: Math.floor(screenSize.width * 0.25),
    y: Math.floor(screenSize.height * 0.3),
    width: Math.floor(screenSize.width * 0.5),
    height: Math.floor(screenSize.height * 0.4),
    expectedContent: 'Dialog boxes, error messages, prompts'
  });
  
  // Right-click context menu area
  areas.push({
    name: 'Context Menu Area',
    x: Math.floor(screenSize.width * 0.3),
    y: Math.floor(screenSize.height * 0.3),
    width: 200,
    height: 300,
    expectedContent: 'Copy, Paste, Cut, Properties menu items'
  });
  
  return areas;
}

function estimateDPI(screenSize) {
  // Rough DPI estimation based on common resolutions
  if (screenSize.width >= 3840) return '4K (High DPI)';
  if (screenSize.width >= 2560) return 'QHD (Medium-High DPI)';
  if (screenSize.width >= 1920) return 'Full HD (Standard DPI)';
  if (screenSize.width >= 1366) return 'HD (Standard DPI)';
  return 'Lower Resolution';
}

async function captureSpecificRegion(x, y, width, height) {
  try {
    console.log(`📸 Capturing region: (${x}, ${y}) ${width}x${height}`);
    
    const imagesDir = path.join(__dirname, 'images');
    if (!fs.existsSync(imagesDir)) {
      fs.mkdirSync(imagesDir, { recursive: true });
    }
    
    const regionPath = path.join(imagesDir, `region_${x}_${y}_${width}x${height}.png`);
    
    await screen.capture(regionPath, { x, y, width, height });
    console.log('✅ Region captured:', regionPath);
    
    console.log('\n📋 Region Analysis:');
    console.log(`   Coordinates: (${x}, ${y})`);
    console.log(`   Size: ${width}x${height} pixels`);
    console.log(`   Area: ${width * height} pixels`);
    
    // Suggest what this region might contain based on position
    const screenSize = await screen.size();
    const regionPurpose = analyzeRegionPurpose(x, y, width, height, screenSize);
    console.log(`   Likely contains: ${regionPurpose}`);
    
    // Wait for file and get size
    await new Promise(resolve => setTimeout(resolve, 500));
    const stats = fs.statSync(regionPath);
    console.log(`   File size: ${Math.round(stats.size / 1024)} KB`);
    
    console.log('\n💡 Next Steps:');
    console.log(`   1. Open the captured image: ${regionPath}`);
    console.log('   2. Visually inspect the text content');
    console.log('   3. Use online OCR tools if needed for text extraction');
    
  } catch (error) {
    console.error('💥 Error capturing region:', error.message);
  }
}

function analyzeRegionPurpose(x, y, width, height, screenSize) {
  const centerX = x + width / 2;
  const centerY = y + height / 2;
  
  // Top area - likely title bars or menus
  if (y < 100) {
    if (centerX > screenSize.width * 0.8) {
      return 'Window controls (minimize/maximize/close buttons)';
    } else if (y < 40) {
      return 'Window title, application name';
    } else {
      return 'Menu bar items (File, Edit, View, etc.)';
    }
  }
  
  // Bottom area - likely status bars
  if (y > screenSize.height - 100) {
    return 'Status bar, progress information';
  }
  
  // Center area
  if (centerX > screenSize.width * 0.2 && centerX < screenSize.width * 0.8 &&
      centerY > screenSize.height * 0.2 && centerY < screenSize.height * 0.8) {
    return 'Main content area, document text, form fields';
  }
  
  // Small regions might be buttons or labels
  if (width < 200 && height < 50) {
    return 'Buttons, labels, or small text elements';
  }
  
  return 'Generic UI area, mixed content';
}

async function runSimpleTextDetection() {
  console.log('🚀 Simple Text Detection Tool (Offline)');
  console.log('=' .repeat(50));
  console.log('🔧 This tool works completely offline - no internet required!');
  
  const args = process.argv.slice(2);
  
  if (args.length === 4) {
    // Region capture mode
    const [x, y, width, height] = args.map(Number);
    console.log(`🎯 Region capture mode: (${x}, ${y}) ${width}x${height}`);
    await captureSpecificRegion(x, y, width, height);
  } else if (args.length === 0) {
    // Full screen analysis mode
    console.log('🖥️  Full screen analysis mode');
    await captureAndAnalyzeScreen();
  } else {
    console.log('📋 Usage:');
    console.log('   Full screen analysis: npm start');
    console.log('   Capture region: npm start x y width height');
    console.log('   Example: npm start 100 50 400 200');
    console.log('\n💡 This tool helps you identify where text might be on screen');
    console.log('   and captures regions for manual inspection.');
    return;
  }
  
  console.log('\n✅ Simple text detection completed!');
  console.log('\n🔍 For actual text reading:');
  console.log('   - Use the captured images with online OCR tools');
  console.log('   - Or try npm run original (if you have internet access)');
}

// Add this to package.json scripts: "screen-region": "node simple_text_detection.js"
if (require.main === module) {
  runSimpleTextDetection().catch(console.error);
}

module.exports = { captureAndAnalyzeScreen, captureSpecificRegion }; 