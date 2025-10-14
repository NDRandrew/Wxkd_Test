I'll create a robust solution that actually opens Chrome visibly and tries multiple methods with detailed feedback:

```python
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.common.by import By
import time
import os


def test_chrome_setup():
    """Test Chrome setup with multiple approaches"""
    
    # Configuration
    chromedriver_path = r"C:\WebDrivers\chromedriver.exe"
    
    # Check if chromedriver exists
    print("=" * 60)
    print("CHECKING CHROMEDRIVER...")
    print("=" * 60)
    
    if os.path.exists(chromedriver_path):
        print(f"✓ ChromeDriver found at: {chromedriver_path}")
        file_size = os.path.getsize(chromedriver_path)
        print(f"✓ File size: {file_size} bytes")
    else:
        print(f"✗ ERROR: ChromeDriver NOT found at: {chromedriver_path}")
        print("\nPlease verify:")
        print("1. The file exists at C:\\WebDrivers\\chromedriver.exe")
        print("2. It's extracted (not still in .zip)")
        print("3. The filename is exactly 'chromedriver.exe'")
        return
    
    print("\n" + "=" * 60)
    print("METHOD 1: Using Service with Options")
    print("=" * 60)
    
    try:
        # Chrome options - make it VISIBLE
        chrome_options = Options()
        chrome_options.add_argument('--start-maximized')
        chrome_options.add_argument('--disable-blink-features=AutomationControlled')
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option('useAutomationExtension', False)
        
        # Disable proxy (corporate network issue)
        chrome_options.add_argument('--no-proxy-server')
        chrome_options.add_argument('--proxy-server="direct://"')
        chrome_options.add_argument('--proxy-bypass-list=*')
        
        # Create service
        service = Service(executable_path=chromedriver_path)
        
        print("Creating Chrome driver...")
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        print("✓ Chrome opened successfully!")
        print("\nNavigating to website...")
        
        driver.get("https://www.example.com")
        time.sleep(3)
        
        print(f"✓ Page loaded!")
        print(f"✓ Page title: {driver.title}")
        print(f"✓ Current URL: {driver.current_url}")
        
        # Try to find an element
        try:
            heading = driver.find_element(By.TAG_NAME, "h1")
            print(f"✓ Found heading: {heading.text}")
        except:
            print("Note: Could not find h1 element (this is OK)")
        
        print("\n✓✓✓ SUCCESS! Chrome is working properly! ✓✓✓")
        print("\nKeeping browser open for 10 seconds so you can see it...")
        time.sleep(10)
        
        driver.quit()
        print("Browser closed.")
        return True
        
    except Exception as e:
        print(f"\n✗ METHOD 1 FAILED")
        print(f"Error type: {type(e).__name__}")
        print(f"Error message: {str(e)}")
        print("\nTrying METHOD 2...\n")
    
    print("=" * 60)
    print("METHOD 2: Direct Service Creation")
    print("=" * 60)
    
    try:
        service = Service(chromedriver_path)
        driver = webdriver.Chrome(service=service)
        
        print("✓ Chrome opened!")
        driver.maximize_window()
        
        driver.get("https://www.google.com")
        time.sleep(3)
        
        print(f"✓ Page title: {driver.title}")
        print("\n✓✓✓ SUCCESS with METHOD 2! ✓✓✓")
        print("\nKeeping browser open for 10 seconds...")
        time.sleep(10)
        
        driver.quit()
        return True
        
    except Exception as e:
        print(f"\n✗ METHOD 2 FAILED")
        print(f"Error: {str(e)}")
        print("\nTrying METHOD 3...\n")
    
    print("=" * 60)
    print("METHOD 3: Using webdriver.Chrome() directly")
    print("=" * 60)
    
    try:
        # Set environment variable
        os.environ['PATH'] += r";C:\WebDrivers"
        
        driver = webdriver.Chrome()
        
        print("✓ Chrome opened!")
        driver.maximize_window()
        
        driver.get("https://www.python.org")
        time.sleep(3)
        
        print(f"✓ Page title: {driver.title}")
        print("\n✓✓✓ SUCCESS with METHOD 3! ✓✓✓")
        print("\nKeeping browser open for 10 seconds...")
        time.sleep(10)
        
        driver.quit()
        return True
        
    except Exception as e:
        print(f"\n✗ METHOD 3 FAILED")
        print(f"Error: {str(e)}")
    
    print("\n" + "=" * 60)
    print("ALL METHODS FAILED - TROUBLESHOOTING NEEDED")
    print("=" * 60)
    print("\nPossible issues:")
    print("1. ChromeDriver version doesn't match Chrome version")
    print("2. Chrome is not installed in default location")
    print("3. Antivirus or security software blocking execution")
    print("4. Insufficient permissions")
    print("\nNext steps:")
    print("1. Check Chrome version: chrome://version/")
    print("2. Verify ChromeDriver version matches")
    print("3. Try running as Administrator")
    print("4. Check if Chrome path is: C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe")


if __name__ == "__main__":
    test_chrome_setup()
```

---

## **This script will:**
1. ✅ Check if ChromeDriver exists
2. ✅ Open Chrome VISIBLY (not headless)
3. ✅ Try 3 different methods automatically
4. ✅ Keep browser open for 10 seconds so you can see it
5. ✅ Show detailed error messages if something fails
6. ✅ Handle corporate proxy issues

---

## **Run this and tell me:**
1. **Does Chrome window open?** (Even if it crashes after)
2. **Which method works?** (1, 2, or 3)
3. **What error message appears?** (if any)

This will help me identify exactly what's going wrong! 🔍