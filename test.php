The error shows that Selenium can't automatically download ChromeDriver, likely due to network/proxy restrictions. Here are several solutions:

## **Solution 1: Use webdriver-manager (Recommended)**

```python
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.chrome import ChromeDriverManager
import time


def main():
    # Initialize with webdriver-manager
    driver = webdriver.Chrome(service=Service(ChromeDriverManager().install()))
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
        driver.get("https://www.example.com")
        time.sleep(2)
        
        print("Script executed successfully!")
        
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

**Install:** `pip install webdriver-manager`

---

## **Solution 2: Manual ChromeDriver (If network is blocked)**

1. Download ChromeDriver manually from: https://googlechromelabs.github.io/chrome-for-testing/
2. Match your Chrome version (check: `chrome://version/`)
3. Extract and save the path

```python
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import time


def main():
    # Specify the path to your ChromeDriver
    service = Service(r"C:\path\to\chromedriver.exe")
    driver = webdriver.Chrome(service=service)
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
        driver.get("https://www.example.com")
        time.sleep(2)
        
        print("Script executed successfully!")
        
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

## **Solution 3: Use Firefox instead**

```python
from selenium import webdriver
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
from webdriver_manager.firefox import GeckoDriverManager
import time


def main():
    # Use Firefox with webdriver-manager
    driver = webdriver.Firefox(service=Service(GeckoDriverManager().install()))
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
        driver.get("https://www.example.com")
        time.sleep(2)
        
        print("Script executed successfully!")
        
    except Exception as e:
        print(f"An error occurred: {str(e)}")
    
    finally:
        time.sleep(2)
        driver.quit()


if __name__ == "__main__":
    main()
```

---

## **Solution 4: Edge (Usually pre-installed on Windows)**

```python
from selenium import webdriver
from selenium.webdriver.edge.service import Service
from webdriver_manager.microsoft import EdgeChromiumDriverManager
import time


def main():
    # Use Edge browser
    driver = webdriver.Edge(service=Service(EdgeChromiumDriverManager().install()))
    
    driver.maximize_window()
    driver.implicitly_wait(10)
    
    try:
        driver.get("https://www.example.com")
        time.sleep(2)
        
        print("Script executed successfully!")
        
    except Exception as e:
        print(f"An error occurred: {str(e)}")
    
    finally:
        time.sleep(2)
        driver.quit()


if __name__ == "__main__":
    main()
```

**Try Solution 1 first**. If it fails due to network restrictions, use Solution 2 and manually download ChromeDriver. Which solution would you like to try?