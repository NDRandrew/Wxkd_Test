Let me help you troubleshoot. Please share the **exact error message** you're getting now, but first, let's make sure everything is set up correctly:

## **Verification Checklist:**

1. **Check your Chrome version:**
   - Open Chrome
   - Go to `chrome://version/`
   - What version do you see? (e.g., 131.0.6778.109)

2. **Verify ChromeDriver location:**
   - Is `chromedriver.exe` in `C:\WebDrivers\chromedriver.exe`?
   - Can you see the file there?

3. **Use this EXACT code** (copy-paste completely):

```python
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time


def main():
    # IMPORTANT: Update this path to match your chromedriver.exe location
    chromedriver_path = r"C:\WebDrivers\chromedriver.exe"
    
    print(f"Looking for ChromeDriver at: {chromedriver_path}")
    
    # Create service with explicit path
    service = Service(executable_path=chromedriver_path)
    
    # Create driver with service
    driver = webdriver.Chrome(service=service)
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
        print("Navigating to website...")
        driver.get("https://www.example.com")
        time.sleep(2)
        
        print("Script executed successfully!")
        print(f"Page title: {driver.title}")
        
    except TimeoutException:
        print("Element took too long to load")
    
    except NoSuchElementException:
        print("Element not found")
    
    except Exception as e:
        print(f"An error occurred: {str(e)}")
        import traceback
        traceback.print_exc()
    
    finally:
        time.sleep(2)
        driver.quit()
        print("Browser closed.")


if __name__ == "__main__":
    main()
```

---

## **If it still fails, try this alternative approach:**

```python
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
import time


def main():
    # Chrome options
    chrome_options = Options()
    # chrome_options.add_argument('--headless')  # Uncomment for headless mode
    
    # Explicit paths
    chromedriver_path = r"C:\WebDrivers\chromedriver.exe"
    
    # Try this older syntax
    driver = webdriver.Chrome(executable_path=chromedriver_path, options=chrome_options)
    
    try:
        driver.maximize_window()
        driver.get("https://www.example.com")
        time.sleep(3)
        
        print("Success! Page title:", driver.title)
        
    except Exception as e:
        print(f"Error: {e}")
    
    finally:
        driver.quit()


if __name__ == "__main__":
    main()
```

---

## **Common Issues:**

**Issue 1: Wrong ChromeDriver version**
- Chrome version: `131.x.x.x` needs ChromeDriver `131.x.x.x`
- Download from: https://googlechromelabs.github.io/chrome-for-testing/

**Issue 2: File path issues**
- Make sure it's `chromedriver.exe`, not `chromedriver.zip`
- Use raw string: `r"C:\WebDrivers\chromedriver.exe"`

**Issue 3: Chrome not in default location**
- Add this to specify Chrome location:
```python
chrome_options = Options()
chrome_options.binary_location = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
```

---

**Please run the first code and tell me:**
1. What error message appears?
2. What is your Chrome version?
3. What ChromeDriver version did you download?