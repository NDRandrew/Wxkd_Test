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