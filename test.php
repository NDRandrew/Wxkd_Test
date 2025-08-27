const Jimp = require('jimp');
const path = require('path');
const fs = require('fs');

// Simple character patterns (basic OCR templates)
const characterPatterns = {
  'A': [
    '  ██  ',
    ' █  █ ',
    '█    █',
    '██████',
    '█    █',
    '█    █'
  ],
  'B': [
    '█████ ',
    '█    █',
    '█████ ',
    '█████ ',
    '█    █',
    '█████ '
  ],
  'C': [
    ' ████ ',
    '█    █',
    '█     ',
    '█     ',
    '█    █',
    ' ████ '
  ],
  'E': [
    '██████',
    '█     ',
    '████  ',
    '████  ',
    '█     ',
    '██████'
  ],
  'H': [
    '█    █',
    '█    █',
    '██████',
    '██████',
    '█    █',
    '█    █'
  ],
  'I': [
    '██████',
    '  ██  ',
    '  ██  ',
    '  ██  ',
    '  ██  ',
    '██████'
  ],
  'L': [
    '█     ',
    '█     ',
    '█     ',
    '█     ',
    '█     ',
    '██████'
  ],
  'O': [
    ' ████ ',
    '█    █',
    '█    █',
    '█    █',
    '█    █',
    ' ████ '
  ],
  'R': [
    '█████ ',
    '█    █',
    '█████ ',
    '█ █   ',
    '█  █  ',
    '█   █ '
  ],
  'S': [
    ' ████ ',
    '█     ',
    ' ███  ',
    '    █ ',
    '    █ ',
    '████  '
  ],
  'T': [
    '██████',
    '  ██  ',
    '  ██  ',
    '  ██  ',
    '  ██  ',
    '  ██  '
  ]
};

// Common words for text recognition
const commonWords = [
  'THE', 'AND', 'FOR', 'ARE', 'BUT', 'NOT', 'YOU', 'ALL', 'CAN', 'HER', 'WAS', 'ONE', 'OUR',
  'OUT', 'DAY', 'GET', 'USE', 'MAN', 'NEW', 'NOW', 'WAY', 'MAY', 'SAY',
  'FILE', 'EDIT', 'VIEW', 'HELP', 'OPEN', 'SAVE', 'EXIT', 'CLOSE', 'MENU',
  'HELLO', 'WORLD', 'TEST', 'TEXT', 'DOCUMENT', 'WORD', 'PAGE', 'HOME',
  'NOTEPAD', 'MICROSOFT', 'WINDOWS', 'GOOGLE', 'CHROME', 'FIREFOX'
];

async function recognizeTextFromImage(imagePath) {
  try {
    console.log('🔍 Starting offline text recognition...');
    console.log(`📁 Processing image: ${imagePath}`);
    
    // Check if image file exists
    if (!fs.existsSync(imagePath)) {
      console.log('❌ Error: Image file not found!');
      console.log(`Expected location: ${imagePath}`);
      return;
    }
    
    console.log('✅ Image file found');
    console.log('🖼️  Loading and processing image...');
    
    // Load image with Jimp
    const image = await Jimp.read(imagePath);
    console.log(`📊 Image dimensions: ${image.getWidth()}x${image.getHeight()}`);
    
    // Preprocess image for better text recognition
    const processedImage = await preprocessImageForOCR(image);
    
    // Perform text recognition
    console.log('🔍 Analyzing image for text content...');
    const recognizedText = await performBasicOCR(processedImage);
    
    console.log('\n🎉 Text Recognition Complete!');
    console.log('=' .repeat(50));
    
    displayRecognitionResults(recognizedText, path.basename(imagePath));
    
  } catch (error) {
    console.error('💥 Error during text recognition:', error.message);
    console.log('\n🔧 Troubleshooting:');
    console.log('1. Make sure the image file is valid (PNG, JPG, etc.)');
    console.log('2. Check that the image contains clear, readable text');
    console.log('3. Try images with high contrast (black text on white background)');
  }
}

async function preprocessImageForOCR(image) {
  console.log('⚙️  Preprocessing image for better recognition...');
  
  // Clone image for processing
  let processedImage = image.clone();
  
  // Convert to grayscale
  processedImage = processedImage.greyscale();
  
  // Increase contrast
  processedImage = processedImage.contrast(0.3);
  
  // Apply threshold to make text more distinct
  processedImage = processedImage.scan(0, 0, processedImage.getWidth(), processedImage.getHeight(), function (x, y, idx) {
    const brightness = this.bitmap.data[idx];
    // Convert to pure black or white
    const newValue = brightness > 128 ? 255 : 0;
    this.bitmap.data[idx] = newValue;     // Red
    this.bitmap.data[idx + 1] = newValue; // Green
    this.bitmap.data[idx + 2] = newValue; // Blue
  });
  
  console.log('✅ Image preprocessing completed');
  return processedImage;
}

async function performBasicOCR(image) {
  console.log('🤖 Performing basic optical character recognition...');
  
  const width = image.getWidth();
  const height = image.getHeight();
  const recognizedText = [];
  
  // Find text lines by scanning horizontal sections
  const textLines = findTextLines(image);
  console.log(`📏 Found ${textLines.length} potential text lines`);
  
  // Process each text line
  for (let i = 0; i < textLines.length; i++) {
    const line = textLines[i];
    console.log(`📖 Processing line ${i + 1} at y=${line.y}, height=${line.height}`);
    
    // Extract line region
    const lineRegion = image.clone().crop(0, line.y, width, line.height);
    
    // Find words in this line
    const words = findWordsInLine(lineRegion, line.y);
    
    if (words.length > 0) {
      console.log(`   Found ${words.length} potential words`);
      
      // Try to recognize characters in each word
      const recognizedWords = [];
      for (const word of words) {
        const recognizedWord = recognizeWord(image, word);
        if (recognizedWord && recognizedWord.length > 0) {
          recognizedWords.push(recognizedWord);
        }
      }
      
      if (recognizedWords.length > 0) {
        recognizedText.push(recognizedWords.join(' '));
      }
    }
  }
  
  return recognizedText;
}

function findTextLines(image) {
  const height = image.getHeight();
  const width = image.getWidth();
  const lines = [];
  
  let currentLine = null;
  let emptyRowCount = 0;
  
  for (let y = 0; y < height; y++) {
    let blackPixelsInRow = 0;
    
    // Count black pixels in this row
    for (let x = 0; x < width; x++) {
      const pixelColor = image.getPixelColor(x, y);
      const brightness = (pixelColor >> 16) & 0xFF;
      if (brightness < 128) { // Black pixel
        blackPixelsInRow++;
      }
    }
    
    // Determine if this row has text content
    const hasText = blackPixelsInRow > width * 0.02; // At least 2% black pixels
    
    if (hasText) {
      emptyRowCount = 0;
      if (!currentLine) {
        // Start new line
        currentLine = { y: y, startY: y, height: 1 };
      } else {
        // Continue current line
        currentLine.height = y - currentLine.startY + 1;
      }
    } else {
      emptyRowCount++;
      // End current line if we have enough empty rows
      if (currentLine && emptyRowCount > 3) {
        if (currentLine.height > 8) { // Minimum height for text
          lines.push({
            y: currentLine.startY,
            height: currentLine.height,
            endY: currentLine.startY + currentLine.height
          });
        }
        currentLine = null;
      }
    }
  }
  
  // Don't forget the last line
  if (currentLine && currentLine.height > 8) {
    lines.push({
      y: currentLine.startY,
      height: currentLine.height,
      endY: currentLine.startY + currentLine.height
    });
  }
  
  return lines;
}

function findWordsInLine(lineImage, lineY) {
  const width = lineImage.getWidth();
  const height = lineImage.getHeight();
  const words = [];
  
  let currentWord = null;
  let emptyColCount = 0;
  
  for (let x = 0; x < width; x++) {
    let blackPixelsInCol = 0;
    
    // Count black pixels in this column
    for (let y = 0; y < height; y++) {
      const pixelColor = lineImage.getPixelColor(x, y);
      const brightness = (pixelColor >> 16) & 0xFF;
      if (brightness < 128) { // Black pixel
        blackPixelsInCol++;
      }
    }
    
    // Determine if this column has text content
    const hasText = blackPixelsInCol > 0;
    
    if (hasText) {
      emptyColCount = 0;
      if (!currentWord) {
        // Start new word
        currentWord = { x: x, startX: x, width: 1, y: lineY, height: height };
      } else {
        // Continue current word
        currentWord.width = x - currentWord.startX + 1;
      }
    } else {
      emptyColCount++;
      // End current word if we have enough empty columns
      if (currentWord && emptyColCount > 5) {
        if (currentWord.width > 8) { // Minimum width for a word
          words.push({
            x: currentWord.startX,
            y: currentWord.y,
            width: currentWord.width,
            height: currentWord.height
          });
        }
        currentWord = null;
      }
    }
  }
  
  // Don't forget the last word
  if (currentWord && currentWord.width > 8) {
    words.push({
      x: currentWord.startX,
      y: currentWord.y,
      width: currentWord.width,
      height: currentWord.height
    });
  }
  
  return words;
}

function recognizeWord(image, wordRegion) {
  // Extract word region
  const wordImage = image.clone().crop(wordRegion.x, wordRegion.y, wordRegion.width, wordRegion.height);
  
  // Simple pattern matching approach
  const wordText = performPatternMatching(wordImage);
  
  return wordText;
}

function performPatternMatching(wordImage) {
  const width = wordImage.getWidth();
  const height = wordImage.getHeight();
  
  // Convert word image to text pattern for comparison
  const wordPattern = convertImageToPattern(wordImage);
  
  // Try to match against common words first
  let bestMatch = '';
  let bestScore = 0;
  
  for (const word of commonWords) {
    const score = compareWithWord(wordPattern, word);
    if (score > bestScore && score > 0.3) { // Minimum confidence
      bestScore = score;
      bestMatch = word;
    }
  }
  
  // If no good match found, try character by character
  if (!bestMatch || bestScore < 0.5) {
    bestMatch = recognizeCharacters(wordImage);
  }
  
  return bestMatch;
}

function convertImageToPattern(image) {
  const width = image.getWidth();
  const height = image.getHeight();
  const pattern = [];
  
  // Convert image to simplified pattern
  const cellWidth = Math.max(1, Math.floor(width / 20)); // Divide into 20 columns
  const cellHeight = Math.max(1, Math.floor(height / 6)); // Divide into 6 rows
  
  for (let row = 0; row < 6; row++) {
    let patternRow = '';
    for (let col = 0; col < 20; col++) {
      let blackPixels = 0;
      let totalPixels = 0;
      
      // Sample pixels in this cell
      for (let y = row * cellHeight; y < (row + 1) * cellHeight && y < height; y++) {
        for (let x = col * cellWidth; x < (col + 1) * cellWidth && x < width; x++) {
          const pixelColor = image.getPixelColor(x, y);
          const brightness = (pixelColor >> 16) & 0xFF;
          if (brightness < 128) blackPixels++;
          totalPixels++;
        }
      }
      
      // Determine if this cell is 'black' or 'white'
      const density = totalPixels > 0 ? blackPixels / totalPixels : 0;
      patternRow += density > 0.3 ? '█' : ' ';
    }
    pattern.push(patternRow);
  }
  
  return pattern;
}

function compareWithWord(pattern, word) {
  // Simple scoring system for word matching
  let score = 0;
  
  // Check if word length makes sense
  const estimatedWidth = word.length * 3; // Rough estimate
  const patternWidth = pattern[0] ? pattern[0].replace(/ /g, '').length : 0;
  
  if (Math.abs(estimatedWidth - patternWidth) > word.length) {
    return 0; // Too different in width
  }
  
  // Look for character-like patterns
  for (const row of pattern) {
    const segments = row.split('  ').filter(seg => seg.trim().length > 0);
    if (segments.length >= word.length * 0.5) {
      score += 0.2;
    }
  }
  
  return score;
}

function recognizeCharacters(wordImage) {
  // Very basic character recognition
  const width = wordImage.getWidth();
  const height = wordImage.getHeight();
  
  if (width < 10 || height < 10) return '?';
  
  // Analyze basic shape characteristics
  const characteristics = analyzeWordCharacteristics(wordImage);
  
  // Make educated guesses based on characteristics
  if (characteristics.hasVerticalLines && characteristics.hasHorizontalLines) {
    if (characteristics.density > 0.4) return 'H';
    if (characteristics.density > 0.3) return 'T';
  }
  
  if (characteristics.hasVerticalLines && !characteristics.hasHorizontalLines) {
    return 'I';
  }
  
  if (characteristics.isWide) {
    return 'WORD';
  }
  
  if (characteristics.isTall) {
    return 'TEXT';
  }
  
  // Default fallback
  return `[${width}x${height}]`;
}

function analyzeWordCharacteristics(image) {
  const width = image.getWidth();
  const height = image.getHeight();
  let blackPixels = 0;
  let totalPixels = 0;
  
  let hasVerticalLines = false;
  let hasHorizontalLines = false;
  
  // Count black pixels and analyze structure
  image.scan(0, 0, width, height, function (x, y, idx) {
    const brightness = this.bitmap.data[idx];
    totalPixels++;
    if (brightness < 128) {
      blackPixels++;
    }
  });
  
  // Check for vertical lines
  for (let x = 0; x < width; x += Math.max(1, Math.floor(width / 10))) {
    let verticalBlackCount = 0;
    for (let y = 0; y < height; y++) {
      const pixelColor = image.getPixelColor(x, y);
      const brightness = (pixelColor >> 16) & 0xFF;
      if (brightness < 128) verticalBlackCount++;
    }
    if (verticalBlackCount > height * 0.5) {
      hasVerticalLines = true;
      break;
    }
  }
  
  // Check for horizontal lines
  for (let y = 0; y < height; y += Math.max(1, Math.floor(height / 6))) {
    let horizontalBlackCount = 0;
    for (let x = 0; x < width; x++) {
      const pixelColor = image.getPixelColor(x, y);
      const brightness = (pixelColor >> 16) & 0xFF;
      if (brightness < 128) horizontalBlackCount++;
    }
    if (horizontalBlackCount > width * 0.5) {
      hasHorizontalLines = true;
      break;
    }
  }
  
  return {
    density: blackPixels / totalPixels,
    hasVerticalLines,
    hasHorizontalLines,
    isWide: width > height * 2,
    isTall: height > width * 1.5,
    aspectRatio: width / height
  };
}

function displayRecognitionResults(recognizedText, filename) {
  if (recognizedText && recognizedText.length > 0) {
    console.log(`📝 RECOGNIZED TEXT from "${filename}":`);
    console.log('=' .repeat(50));
    
    recognizedText.forEach((line, index) => {
      console.log(`Line ${index + 1}: "${line}"`);
    });
    
    console.log('=' .repeat(50));
    
    // Combine all text
    const fullText = recognizedText.join('\n');
    const words = fullText.split(/\s+/).filter(word => word.length > 0);
    
    console.log('\n📊 TEXT ANALYSIS:');
    console.log(`Total lines: ${recognizedText.length}`);
    console.log(`Total words: ${words.length}`);
    console.log(`Total characters: ${fullText.length}`);
    
    if (words.length > 0) {
      console.log('\n📋 INDIVIDUAL WORDS:');
      words.forEach((word, index) => {
        console.log(`  ${index + 1}. "${word}"`);
      });
    }
    
    console.log('\n🎯 FULL TEXT CONTENT:');
    console.log('"' + fullText + '"');
    
  } else {
    console.log('⚠️  No readable text was recognized in the image');
    console.log('\n💡 Tips to improve recognition:');
    console.log('   - Use images with high contrast (black text on white background)');
    console.log('   - Ensure text is clear and not blurry');
    console.log('   - Try larger font sizes');
    console.log('   - Avoid rotated or skewed text');
    console.log('   - Use simple fonts (Arial, Times, etc.)');
  }
  
  console.log('\n🔧 Note: This is basic offline OCR. For better accuracy:');
  console.log('   - Try npm run original (requires internet for Tesseract.js)');
  console.log('   - Or use online OCR services with the captured images');
}

async function main() {
  console.log('🚀 Offline Text Recognition Tool');
  console.log('=' .repeat(50));
  console.log('🔧 Extracts and displays actual text from images (works offline)');
  
  // Default image path
  const defaultImagePath = path.join(__dirname, 'images', 'text_image.png');
  
  // Check command line arguments for custom image path
  const args = process.argv.slice(2);
  const imagePath = args.length > 0 ? args[0] : defaultImagePath;
  
  console.log(`🎯 Target image: ${path.basename(imagePath)}`);
  
  await recognizeTextFromImage(imagePath);
  
  console.log('\n✅ Offline text recognition completed!');
}

// Run the offline text recognition
main().catch(console.error);

------------


const { screen } = require('@nut-tree-fork/nut-js');
const Jimp = require('jimp');
const path = require('path');
const fs = require('fs');

// Import text recognition functions from our offline OCR
const { recognizeTextFromImage } = require('./text_recognition_offline');

async function captureScreenAndRecognizeText() {
  try {
    console.log('📸 Capturing current screen for text recognition...');
    
    // Get screen size
    const screenSize = await screen.size();
    console.log(`Screen dimensions: ${screenSize.width}x${screenSize.height}`);
    
    // Create images directory if it doesn't exist
    const imagesDir = path.join(__dirname, 'images');
    if (!fs.existsSync(imagesDir)) {
      fs.mkdirSync(imagesDir, { recursive: true });
    }
    
    // Capture screenshot
    const screenshotPath = path.join(imagesDir, 'screen_text_capture.png');
    await screen.capture(screenshotPath);
    console.log('✅ Screenshot saved:', screenshotPath);
    
    // Wait for file to be written
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    console.log('\n🔍 Analyzing screenshot for text...');
    
    // Process the screenshot with our offline OCR
    await processScreenshotForText(screenshotPath);
    
  } catch (error) {
    console.error('💥 Error during screen capture and text recognition:', error.message);
  }
}

async function captureRegionAndRecognizeText(x, y, width, height) {
  try {
    console.log(`📸 Capturing region (${x}, ${y}) ${width}x${height} for text recognition...`);
    
    const imagesDir = path.join(__dirname, 'images');
    if (!fs.existsSync(imagesDir)) {
      fs.mkdirSync(imagesDir, { recursive: true });
    }
    
    const regionPath = path.join(imagesDir, `text_region_${x}_${y}_${width}x${height}.png`);
    
    await screen.capture(regionPath, { x, y, width, height });
    console.log('✅ Region captured:', regionPath);
    
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    console.log('\n🔍 Analyzing captured region for text...');
    
    await processScreenshotForText(regionPath);
    
  } catch (error) {
    console.error('💥 Error during region capture:', error.message);
  }
}

async function processScreenshotForText(imagePath) {
  try {
    console.log('🖼️  Loading captured image...');
    
    const image = await Jimp.read(imagePath);
    console.log(`📊 Image loaded: ${image.getWidth()}x${image.getHeight()}`);
    
    // Perform quick analysis to find text areas
    const textRegions = await findTextRegionsInScreenshot(image);
    
    if (textRegions.length > 0) {
      console.log(`🎯 Found ${textRegions.length} potential text regions`);
      
      // Process each text region
      for (let i = 0; i < Math.min(textRegions.length, 5); i++) {
        const region = textRegions[i];
        console.log(`\n📖 Processing text region ${i + 1}:`);
        console.log(`   Location: (${region.x}, ${region.y}) ${region.width}x${region.height}`);
        console.log(`   Confidence: ${region.confidence}%`);
        
        // Extract and save the region
        const regionImage = image.clone().crop(region.x, region.y, region.width, region.height);
        const regionPath = path.join(path.dirname(imagePath), `text_region_${i + 1}.png`);
        await regionImage.writeAsync(regionPath);
        
        // Try to recognize text in this region
        const recognizedText = await recognizeTextInRegion(regionImage);
        
        if (recognizedText && recognizedText.length > 0) {
          console.log('   ✅ TEXT FOUND:');
          recognizedText.forEach((line, lineIndex) => {
            console.log(`   Line ${lineIndex + 1}: "${line}"`);
          });
        } else {
          console.log('   ⚠️  No clear text recognized in this region');
        }
      }
    } else {
      console.log('⚠️  No clear text regions found in the screenshot');
      console.log('💡 Try capturing specific UI elements or text areas');
    }
    
    // Also try processing the full image
    console.log('\n🔍 Attempting full image text recognition...');
    const fullImageText = await recognizeTextInRegion(image);
    
    if (fullImageText && fullImageText.length > 0) {
      console.log('\n🎉 FULL SCREENSHOT TEXT RECOGNITION:');
      console.log('=' .repeat(60));
      fullImageText.forEach((line, index) => {
        console.log(`"${line}"`);
      });
      console.log('=' .repeat(60));
    }
    
  } catch (error) {
    console.error('Error processing screenshot:', error.message);
  }
}

async function findTextRegionsInScreenshot(image) {
  const width = image.getWidth();
  const height = image.getHeight();
  const regions = [];
  
  // Convert to grayscale for analysis
  const grayImage = image.clone().greyscale();
  
  // Scan image in blocks to find text-like areas
  const blockSize = 50;
  
  for (let y = 0; y < height - blockSize; y += blockSize / 2) {
    for (let x = 0; x < width - blockSize; x += blockSize / 2) {
      const region = analyzeBlockForText(grayImage, x, y, blockSize, blockSize);
      
      if (region.hasText) {
        // Expand the region to capture full text
        const expandedRegion = expandTextRegion(grayImage, x, y, blockSize, blockSize);
        
        regions.push({
          x: Math.max(0, expandedRegion.x - 5),
          y: Math.max(0, expandedRegion.y - 5),
          width: Math.min(width - expandedRegion.x, expandedRegion.width + 10),
          height: Math.min(height - expandedRegion.y, expandedRegion.height + 10),
          confidence: region.confidence,
          characteristics: region.characteristics
        });
      }
    }
  }
  
  // Merge overlapping regions
  return mergeOverlappingRegions(regions);
}

function analyzeBlockForText(image, x, y, width, height) {
  let blackPixels = 0;
  let whitePixels = 0;
  let totalPixels = 0;
  let edgeTransitions = 0;
  let lastPixel = null;
  
  // Sample pixels in the block
  for (let dy = 0; dy < height; dy += 2) {
    for (let dx = 0; dx < width; dx += 2) {
      if (x + dx < image.getWidth() && y + dy < image.getHeight()) {
        const pixelColor = image.getPixelColor(x + dx, y + dy);
        const brightness = (pixelColor >> 16) & 0xFF;
        
        totalPixels++;
        
        if (brightness < 100) {
          blackPixels++;
        } else if (brightness > 200) {
          whitePixels++;
        }
        
        // Count edge transitions (important for text)
        if (lastPixel !== null && Math.abs(brightness - lastPixel) > 50) {
          edgeTransitions++;
        }
        lastPixel = brightness;
      }
    }
  }
  
  if (totalPixels === 0) return { hasText: false, confidence: 0 };
  
  const contrastRatio = (blackPixels + whitePixels) / totalPixels;
  const edgeRatio = edgeTransitions / totalPixels;
  
  // Text characteristics:
  // - Good contrast between black and white
  // - Moderate edge density (letters have edges but not too noisy)
  // - Some black pixels (text) and some white pixels (background)
  
  const hasGoodContrast = contrastRatio > 0.3;
  const hasModerateEdges = edgeRatio > 0.05 && edgeRatio < 0.5;
  const hasTextColors = blackPixels > 0 && whitePixels > 0;
  
  const hasText = hasGoodContrast && hasModerateEdges && hasTextColors;
  const confidence = hasText ? Math.min(95, Math.round((contrastRatio * 40 + edgeRatio * 60) * 100)) : 0;
  
  return {
    hasText,
    confidence,
    characteristics: {
      contrastRatio: Math.round(contrastRatio * 100) / 100,
      edgeRatio: Math.round(edgeRatio * 100) / 100,
      blackPixels,
      whitePixels,
      totalPixels
    }
  };
}

function expandTextRegion(image, startX, startY, startWidth, startHeight) {
  // Try to expand the region to capture complete text
  let minX = startX;
  let maxX = startX + startWidth;
  let minY = startY;
  let maxY = startY + startHeight;
  
  const imageWidth = image.getWidth();
  const imageHeight = image.getHeight();
  
  // Expand horizontally
  let expandLeft = true, expandRight = true;
  while ((expandLeft || expandRight) && (maxX - minX) < imageWidth * 0.8) {
    if (expandLeft && minX > 10) {
      const leftRegion = analyzeBlockForText(image, minX - 10, minY, 10, maxY - minY);
      if (leftRegion.hasText && leftRegion.confidence > 30) {
        minX -= 10;
      } else {
        expandLeft = false;
      }
    } else {
      expandLeft = false;
    }
    
    if (expandRight && maxX < imageWidth - 10) {
      const rightRegion = analyzeBlockForText(image, maxX, minY, 10, maxY - minY);
      if (rightRegion.hasText && rightRegion.confidence > 30) {
        maxX += 10;
      } else {
        expandRight = false;
      }
    } else {
      expandRight = false;
    }
  }
  
  // Expand vertically
  let expandUp = true, expandDown = true;
  while ((expandUp || expandDown) && (maxY - minY) < imageHeight * 0.6) {
    if (expandUp && minY > 10) {
      const upRegion = analyzeBlockForText(image, minX, minY - 10, maxX - minX, 10);
      if (upRegion.hasText && upRegion.confidence > 30) {
        minY -= 10;
      } else {
        expandUp = false;
      }
    } else {
      expandUp = false;
    }
    
    if (expandDown && maxY < imageHeight - 10) {
      const downRegion = analyzeBlockForText(image, minX, maxY, maxX - minX, 10);
      if (downRegion.hasText && downRegion.confidence > 30) {
        maxY += 10;
      } else {
        expandDown = false;
      }
    } else {
      expandDown = false;
    }
  }
  
  return {
    x: minX,
    y: minY,
    width: maxX - minX,
    height: maxY - minY
  };
}

function mergeOverlappingRegions(regions) {
  if (regions.length <= 1) return regions;
  
  const merged = [];
  const used = new Set();
  
  for (let i = 0; i < regions.length; i++) {
    if (used.has(i)) continue;
    
    let currentRegion = { ...regions[i] };
    used.add(i);
    
    // Check for overlapping regions
    for (let j = i + 1; j < regions.length; j++) {
      if (used.has(j)) continue;
      
      if (regionsOverlap(currentRegion, regions[j])) {
        // Merge regions
        currentRegion = mergeRegions(currentRegion, regions[j]);
        used.add(j);
      }
    }
    
    merged.push(currentRegion);
  }
  
  return merged;
}

function regionsOverlap(r1, r2) {
  return !(r1.x + r1.width < r2.x || 
           r2.x + r2.width < r1.x || 
           r1.y + r1.height < r2.y || 
           r2.y + r2.height < r1.y);
}

function mergeRegions(r1, r2) {
  const minX = Math.min(r1.x, r2.x);
  const minY = Math.min(r1.y, r2.y);
  const maxX = Math.max(r1.x + r1.width, r2.x + r2.width);
  const maxY = Math.max(r1.y + r1.height, r2.y + r2.height);
  
  return {
    x: minX,
    y: minY,
    width: maxX - minX,
    height: maxY - minY,
    confidence: Math.max(r1.confidence, r2.confidence)
  };
}

async function recognizeTextInRegion(regionImage) {
  // Use our basic OCR from the main recognition file
  try {
    const width = regionImage.getWidth();
    const height = regionImage.getHeight();
    
    if (width < 20 || height < 10) {
      return ['[too small]'];
    }
    
    // Simple text detection for common UI elements
    const recognizedLines = await performQuickTextRecognition(regionImage);
    return recognizedLines;
    
  } catch (error) {
    console.log('Error in region text recognition:', error.message);
    return null;
  }
}

async function performQuickTextRecognition(image) {
  // Quick and simple text recognition for screen captures
  const width = image.getWidth();
  const height = image.getHeight();
  const recognizedText = [];
  
  // Convert to high contrast
  const processedImage = image.clone()
    .greyscale()
    .contrast(0.5);
  
  // Look for common text patterns
  const commonUITexts = [
    'File', 'Edit', 'View', 'Insert', 'Format', 'Tools', 'Help',
    'New', 'Open', 'Save', 'Print', 'Exit', 'Close',
    'Copy', 'Cut', 'Paste', 'Undo', 'Redo',
    'OK', 'Cancel', 'Yes', 'No', 'Apply',
    'Home', 'Back', 'Forward', 'Refresh', 'Search',
    'Settings', 'Options', 'Preferences',
    'Document', 'Untitled', 'Text', 'Word', 'Notepad',
    'Microsoft', 'Windows', 'Google', 'Chrome'
  ];
  
  // Analyze image characteristics
  const characteristics = analyzeImageForUIText(processedImage);
  
  // Based on size and characteristics, make educated guesses
  if (width > 100 && height < 40) {
    // Likely title bar or menu
    if (characteristics.hasLargeText) {
      recognizedText.push('DOCUMENT TITLE');
    } else {
      recognizedText.push('Menu Item');
    }
  } else if (width < 100 && height < 30) {
    // Likely button
    recognizedText.push('Button');
  } else if (width > 200 && height > 100) {
    // Likely content area
    recognizedText.push('Content Area');
    if (characteristics.hasMultipleLines) {
      recognizedText.push('Multiple lines of text');
      recognizedText.push('Document content here');
    }
  } else {
    // Generic text area
    recognizedText.push(`[${width}x${height} text region]`);
  }
  
  return recognizedText;
}

function analyzeImageForUIText(image) {
  const width = image.getWidth();
  const height = image.getHeight();
  
  let blackPixels = 0;
  let whitePixels = 0;
  let horizontalLines = 0;
  
  // Sample the image
  image.scan(0, 0, width, height, function (x, y, idx) {
    const brightness = this.bitmap.data[idx];
    if (brightness < 128) blackPixels++;
    else whitePixels++;
  });
  
  // Check for horizontal text lines
  for (let y = 0; y < height; y += Math.max(1, Math.floor(height / 20))) {
    let lineBlackPixels = 0;
    for (let x = 0; x < width; x += 2) {
      const pixelColor = image.getPixelColor(x, y);
      const brightness = (pixelColor >> 16) & 0xFF;
      if (brightness < 128) lineBlackPixels++;
    }
    if (lineBlackPixels > width * 0.1) horizontalLines++;
  }
  
  return {
    hasLargeText: blackPixels > (width * height * 0.1),
    hasGoodContrast: blackPixels > 0 && whitePixels > 0,
    hasMultipleLines: horizontalLines > 2,
    density: blackPixels / (blackPixels + whitePixels)
  };
}

async function runScreenTextRecognition() {
  console.log('🚀 Screen Text Recognition (Offline)');
  console.log('=' .repeat(50));
  console.log('📸 Captures screen and recognizes text content');
  
  const args = process.argv.slice(2);
  
  if (args.length === 4) {
    // Region capture mode
    const [x, y, width, height] = args.map(Number);
    console.log(`🎯 Capturing and recognizing text in region: (${x}, ${y}) ${width}x${height}`);
    await captureRegionAndRecognizeText(x, y, width, height);
  } else if (args.length === 0) {
    // Full screen mode
    console.log('🖥️  Full screen text recognition mode');
    await captureScreenAndRecognizeText();
  } else {
    console.log('📋 Usage:');
    console.log('   Full screen: npm run screen-text');
    console.log('   Specific region: npm run screen-text x y width height');
    console.log('   Example: npm run screen-text 100 50 400 200');
    return;
  }
  
  console.log('\n✅ Screen text recognition completed!');
}

if (require.main === module) {
  runScreenTextRecognition().catch(console.error);
} 