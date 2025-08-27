const Tesseract = require('tesseract.js');
const path = require('path');
const fs = require('fs');

async function recognizeTextFromImage(imagePath) {
  try {
    console.log('🔍 Starting text recognition...');
    console.log(`📁 Processing image: ${imagePath}`);
    
    // Check if image file exists
    if (!fs.existsSync(imagePath)) {
      console.log('❌ Error: Image file not found!');
      console.log(`Expected location: ${imagePath}`);
      return;
    }
    
    console.log('✅ Image file found');
    console.log('🤖 Initializing OCR engine...');
    
    // Configure Tesseract for better accuracy
    const result = await Tesseract.recognize(
      imagePath,
      'eng', // Language: English
      {
        logger: (m) => {
          if (m.status === 'recognizing text') {
            const progress = Math.round(m.progress * 100);
            console.log(`📖 Processing: ${progress}%`);
          }
        }
      }
    );
    
    console.log('\n🎉 Text Recognition Complete!');
    console.log('=' .repeat(50));
    
    // Output recognized text
    const recognizedText = result.data.text.trim();
    
    if (recognizedText) {
      console.log('📝 RECOGNIZED TEXT:');
      console.log('=' .repeat(30));
      console.log(recognizedText);
      console.log('=' .repeat(30));
      
      // Additional analysis
      const words = recognizedText.split(/\s+/).filter(word => word.length > 0);
      const lines = recognizedText.split('\n').filter(line => line.trim().length > 0);
      
      console.log('\n📊 TEXT ANALYSIS:');
      console.log(`Total characters: ${recognizedText.length}`);
      console.log(`Total words: ${words.length}`);
      console.log(`Total lines: ${lines.length}`);
      
      if (words.length > 0) {
        console.log('\n📋 INDIVIDUAL WORDS:');
        words.forEach((word, index) => {
          console.log(`  ${index + 1}. "${word}"`);
        });
      }
      
      // Confidence score
      console.log(`\n🎯 Confidence: ${Math.round(result.data.confidence)}%`);
      
    } else {
      console.log('⚠️  No text was recognized in the image');
      console.log('💡 Tips to improve recognition:');
      console.log('   - Ensure text is clear and high contrast');
      console.log('   - Use images with good resolution');
      console.log('   - Make sure text is not rotated or skewed');
      console.log('   - Try images with dark text on light background');
    }
    
  } catch (error) {
    console.error('💥 Error during text recognition:', error.message);
    console.log('\n🔧 Troubleshooting:');
    console.log('1. Make sure the image file is valid (PNG, JPG, etc.)');
    console.log('2. Check that the image contains readable text');
    console.log('3. Try a different image with clearer text');
  }
}

async function main() {
  console.log('🚀 Image Text Recognition Tool');
  console.log('=' .repeat(40));
  
  // Default image path
  const defaultImagePath = path.join(__dirname, 'images', 'text_image.png');
  
  // Check command line arguments for custom image path
  const args = process.argv.slice(2);
  const imagePath = args.length > 0 ? args[0] : defaultImagePath;
  
  console.log(`🎯 Target image: ${path.basename(imagePath)}`);
  
  await recognizeTextFromImage(imagePath);
  
  console.log('\n✅ Text recognition process completed!');
}

// Run the text recognition
main().catch(console.error);


------------

const Tesseract = require('tesseract.js');
const path = require('path');
const fs = require('fs');

async function testMultipleImages() {
  console.log('🧪 Testing OCR on Multiple Images');
  console.log('=' .repeat(50));
  
  // Look for all images in the images folder
  const imagesDir = path.join(__dirname, 'images');
  
  if (!fs.existsSync(imagesDir)) {
    console.log('📁 Creating images folder...');
    fs.mkdirSync(imagesDir);
    console.log('✅ Images folder created');
    console.log('\n📋 Instructions:');
    console.log('1. Add image files (PNG, JPG, etc.) to the /images folder');
    console.log('2. Run this script again to test OCR on your images');
    return;
  }
  
  // Get all image files
  const imageExtensions = ['.png', '.jpg', '.jpeg', '.bmp', '.tiff', '.webp'];
  const imageFiles = fs.readdirSync(imagesDir)
    .filter(file => imageExtensions.some(ext => file.toLowerCase().endsWith(ext)));
  
  if (imageFiles.length === 0) {
    console.log('📝 No image files found in /images folder');
    console.log('\n📋 Instructions:');
    console.log('1. Add image files to the /images folder');
    console.log('2. Supported formats: PNG, JPG, JPEG, BMP, TIFF, WEBP');
    console.log('3. Run this script again');
    return;
  }
  
  console.log(`🖼️  Found ${imageFiles.length} image(s) to process:`);
  imageFiles.forEach((file, index) => {
    console.log(`  ${index + 1}. ${file}`);
  });
  
  // Process each image
  for (let i = 0; i < imageFiles.length; i++) {
    const imageFile = imageFiles[i];
    const imagePath = path.join(imagesDir, imageFile);
    
    console.log('\n' + '='.repeat(60));
    console.log(`📸 Processing Image ${i + 1}/${imageFiles.length}: ${imageFile}`);
    console.log('='.repeat(60));
    
    await processImageWithAdvancedOCR(imagePath, imageFile);
    
    // Add delay between images to prevent overwhelming the console
    if (i < imageFiles.length - 1) {
      console.log('\n⏳ Waiting before next image...');
      await new Promise(resolve => setTimeout(resolve, 1000));
    }
  }
  
  console.log('\n🎉 All images processed!');
}

async function processImageWithAdvancedOCR(imagePath, fileName) {
  try {
    // Get file size info
    const stats = fs.statSync(imagePath);
    console.log(`📊 File size: ${Math.round(stats.size / 1024)} KB`);
    
    console.log('🤖 Starting OCR with multiple configurations...');
    
    // Try different OCR configurations for better results
    const ocrConfigs = [
      {
        name: 'Standard',
        options: {
          tessedit_pageseg_mode: Tesseract.PSM.AUTO,
        }
      },
      {
        name: 'Single Text Block',
        options: {
          tessedit_pageseg_mode: Tesseract.PSM.SINGLE_BLOCK,
        }
      },
      {
        name: 'Single Text Line',
        options: {
          tessedit_pageseg_mode: Tesseract.PSM.SINGLE_LINE,
        }
      }
    ];
    
    let bestResult = null;
    let bestConfidence = 0;
    
    for (const config of ocrConfigs) {
      try {
        console.log(`\n🔄 Trying ${config.name} configuration...`);
        
        const result = await Tesseract.recognize(
          imagePath,
          'eng',
          {
            logger: (m) => {
              if (m.status === 'recognizing text') {
                const progress = Math.round(m.progress * 100);
                process.stdout.write(`\r   Progress: ${progress}%`);
              }
            },
            ...config.options
          }
        );
        
        process.stdout.write('\n'); // New line after progress
        
        const confidence = result.data.confidence;
        const text = result.data.text.trim();
        
        console.log(`   Confidence: ${Math.round(confidence)}%`);
        console.log(`   Characters found: ${text.length}`);
        
        if (confidence > bestConfidence && text.length > 0) {
          bestResult = result;
          bestConfidence = confidence;
          console.log('   ✅ Best result so far!');
        }
        
      } catch (configError) {
        console.log(`   ❌ ${config.name} failed:`, configError.message);
      }
    }
    
    // Display best result
    if (bestResult && bestResult.data.text.trim()) {
      console.log('\n🏆 BEST RESULT:');
      console.log('=' .repeat(40));
      console.log(`📝 Text from "${fileName}":`);
      console.log('-'.repeat(40));
      console.log(bestResult.data.text.trim());
      console.log('-'.repeat(40));
      
      // Word analysis
      const words = bestResult.data.text.trim().split(/\s+/).filter(word => word.length > 0);
      console.log(`\n📊 Analysis:`);
      console.log(`   Words found: ${words.length}`);
      console.log(`   Confidence: ${Math.round(bestResult.data.confidence)}%`);
      
      if (words.length > 0 && words.length <= 20) {
        console.log(`\n📋 Individual words:`);
        words.forEach((word, index) => {
          console.log(`   ${index + 1}. "${word}"`);
        });
      }
      
      // Look for specific patterns
      const hasNumbers = /\d/.test(bestResult.data.text);
      const hasUppercase = /[A-Z]/.test(bestResult.data.text);
      const hasLowercase = /[a-z]/.test(bestResult.data.text);
      
      console.log(`\n🔍 Content analysis:`);
      console.log(`   Contains numbers: ${hasNumbers ? '✅' : '❌'}`);
      console.log(`   Contains uppercase: ${hasUppercase ? '✅' : '❌'}`);
      console.log(`   Contains lowercase: ${hasLowercase ? '✅' : '❌'}`);
      
    } else {
      console.log('\n⚠️  No readable text found in this image');
      console.log('💡 Tips for better results:');
      console.log('   - Use high contrast images (dark text on light background)');
      console.log('   - Ensure text is not rotated or distorted');
      console.log('   - Try images with larger, clearer fonts');
      console.log('   - Avoid images with complex backgrounds');
    }
    
  } catch (error) {
    console.error(`💥 Error processing ${fileName}:`, error.message);
  }
}

// Run the test
testMultipleImages().catch(console.error);



-------------


const { screen } = require('@nut-tree-fork/nut-js');
const Tesseract = require('tesseract.js');
const path = require('path');
const fs = require('fs');

async function captureAndReadScreenText() {
  try {
    console.log('📸 Taking screenshot of current screen...');
    
    // Capture full screen
    const screenSize = await screen.size();
    console.log(`Screen size: ${screenSize.width}x${screenSize.height}`);
    
    // Take screenshot
    const screenshotPath = path.join(__dirname, 'images', 'screenshot.png');
    
    // Ensure images directory exists
    const imagesDir = path.dirname(screenshotPath);
    if (!fs.existsSync(imagesDir)) {
      fs.mkdirSync(imagesDir, { recursive: true });
    }
    
    await screen.capture(screenshotPath);
    console.log('✅ Screenshot saved to:', screenshotPath);
    
    // Wait a moment for file to be written
    await new Promise(resolve => setTimeout(resolve, 500));
    
    console.log('\n🔍 Analyzing screenshot for text...');
    
    // Process the screenshot with OCR
    await recognizeTextFromScreenshot(screenshotPath);
    
  } catch (error) {
    console.error('💥 Error during screen capture and text recognition:', error.message);
  }
}

async function captureRegionAndReadText(x, y, width, height) {
  try {
    console.log(`📸 Capturing screen region: (${x}, ${y}) ${width}x${height}...`);
    
    // Capture specific region
    const regionPath = path.join(__dirname, 'images', 'region_capture.png');
    
    // Ensure images directory exists
    const imagesDir = path.dirname(regionPath);
    if (!fs.existsSync(imagesDir)) {
      fs.mkdirSync(imagesDir, { recursive: true });
    }
    
    await screen.capture(regionPath, { x, y, width, height });
    console.log('✅ Region captured to:', regionPath);
    
    // Wait for file to be written
    await new Promise(resolve => setTimeout(resolve, 500));
    
    console.log('\n🔍 Analyzing captured region for text...');
    
    // Process the region capture with OCR
    await recognizeTextFromScreenshot(regionPath);
    
  } catch (error) {
    console.error('💥 Error during region capture and text recognition:', error.message);
  }
}

async function recognizeTextFromScreenshot(imagePath) {
  try {
    console.log('🤖 Starting OCR on captured image...');
    
    const result = await Tesseract.recognize(
      imagePath,
      'eng',
      {
        logger: (m) => {
          if (m.status === 'recognizing text') {
            const progress = Math.round(m.progress * 100);
            process.stdout.write(`\r   OCR Progress: ${progress}%`);
          }
        }
      }
    );
    
    process.stdout.write('\n'); // New line after progress
    
    const recognizedText = result.data.text.trim();
    
    if (recognizedText) {
      console.log('\n🎉 TEXT FOUND ON SCREEN!');
      console.log('=' .repeat(50));
      console.log(recognizedText);
      console.log('=' .repeat(50));
      
      // Analysis
      const words = recognizedText.split(/\s+/).filter(word => word.length > 0);
      const lines = recognizedText.split('\n').filter(line => line.trim().length > 0);
      
      console.log(`\n📊 Analysis:`);
      console.log(`   Total words: ${words.length}`);
      console.log(`   Total lines: ${lines.length}`);
      console.log(`   Confidence: ${Math.round(result.data.confidence)}%`);
      
      // Look for common UI elements
      const commonUIElements = ['OK', 'Cancel', 'Yes', 'No', 'Save', 'Open', 'Close', 'Exit', 'File', 'Edit', 'View', 'Help'];
      const foundUIElements = words.filter(word => 
        commonUIElements.some(ui => ui.toLowerCase() === word.toLowerCase())
      );
      
      if (foundUIElements.length > 0) {
        console.log(`\n🖥️  UI Elements detected: ${foundUIElements.join(', ')}`);
      }
      
      // Check for specific patterns
      const hasNumbers = /\d/.test(recognizedText);
      const hasEmail = /@/.test(recognizedText);
      const hasURL = /http|www\./.test(recognizedText);
      
      console.log(`\n🔍 Content patterns:`);
      console.log(`   Contains numbers: ${hasNumbers ? '✅' : '❌'}`);
      console.log(`   Contains email: ${hasEmail ? '✅' : '❌'}`);
      console.log(`   Contains URLs: ${hasURL ? '✅' : '❌'}`);
      
    } else {
      console.log('\n⚠️  No readable text found in the screenshot');
      console.log('This might happen if:');
      console.log('   - The screen has mostly graphics/images');
      console.log('   - Text is too small or blurry');
      console.log('   - Text color is too similar to background');
    }
    
  } catch (error) {
    console.error('💥 Error during OCR processing:', error.message);
  }
}

async function demonstrateScreenTextRecognition() {
  console.log('🚀 Screen Text Recognition Demo');
  console.log('=' .repeat(50));
  
  const args = process.argv.slice(2);
  
  if (args.length === 4) {
    // Region capture mode: node script.js x y width height
    const [x, y, width, height] = args.map(Number);
    console.log(`🎯 Region capture mode: (${x}, ${y}) ${width}x${height}`);
    await captureRegionAndReadText(x, y, width, height);
  } else if (args.length === 0) {
    // Full screen mode
    console.log('🖥️  Full screen capture mode');
    await captureAndReadScreenText();
  } else {
    console.log('📋 Usage options:');
    console.log('   Full screen: npm run screen-ocr');
    console.log('   Specific region: npm run screen-ocr -- x y width height');
    console.log('   Example: npm run screen-ocr -- 100 100 400 200');
    return;
  }
  
  console.log('\n✅ Screen text recognition completed!');
}

// Run the demonstration
demonstrateScreenTextRecognition().catch(console.error);