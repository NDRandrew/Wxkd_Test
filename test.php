This is a **proxy/network restriction issue** common in corporate environments. The automatic download is being blocked. You need to **manually download the driver**.

## **Best Solution: Manual EdgeDriver Setup**

Since Edge is pre-installed on Windows, let's use it with a manually downloaded driver:

### **Step 1: Check your Edge version**
1. Open Edge browser
2. Go to `edge://settings/help`
3. Note the version (e.g., 141.0.2708.81)

### **Step 2: Download EdgeDriver manually**
1. Go to: https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/
2. Download the version matching your Edge version
3. Extract the `msedgedriver.exe` file
4. Save it somewhere (e.g., `C:\WebDrivers\msedgedriver.exe`)

### **Step 3: Use this code**

```python
from selenium import webdriver
from selenium.webdriver.edge.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time


def main():
    # Specify the path to your manually downloaded EdgeDriver
    service = Service(r"C:\WebDrivers\msedgedriver.exe")  # Change this path!
    driver = webdriver.Edge(service=service)
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
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
    
    finally:
        time.sleep(2)
        driver.quit()


if __name__ == "__main__":
    main()
```

---

## **Alternative: Use Chrome with Manual ChromeDriver**

### **For Chrome:**
1. Check Chrome version: `chrome://version/`
2. Download from: https://googlechromelabs.github.io/chrome-for-testing/
3. Extract `chromedriver.exe`

```python
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time


def main():
    # Specify the path to your manually downloaded ChromeDriver
    service = Service(r"C:\WebDrivers\chromedriver.exe")  # Change this path!
    driver = webdriver.Chrome(service=service)
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
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
    
    finally:
        time.sleep(2)
        driver.quit()


if __name__ == "__main__":
    main()
```

---

## **Quick Tips:**
- **Create a folder**: `C:\WebDrivers\` and put your driver there
- **Update the path** in the code to match where you saved the driver
- Use **raw string** `r"C:\path\file.exe"` or **forward slashes** `"C:/path/file.exe"`
- Make sure the driver version **matches** your browser version

**Which browser do you have installed? I'll help you get the right driver!**