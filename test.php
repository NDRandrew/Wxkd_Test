Here are the best alternatives to Selenium for Python web automation:

## **1. Playwright (HIGHLY RECOMMENDED) ⭐**

**Why it's better for your situation:**
- ✅ No separate driver downloads needed
- ✅ Automatic browser installation
- ✅ Faster and more reliable than Selenium
- ✅ Better API and modern features
- ✅ Built by Microsoft

**Installation:**
```bash
pip install playwright
playwright install chromium  # or: msedge, firefox, webkit
```

**Basic Code:**
```python
from playwright.sync_api import sync_playwright
import time


def main():
    with sync_playwright() as p:
        # Launch browser (visible)
        browser = p.chromium.launch(headless=False)
        
        # Or use Edge
        # browser = p.chromium.launch(channel="msedge", headless=False)
        
        # Or use Firefox
        # browser = p.firefox.launch(headless=False)
        
        # Create a new page
        page = browser.new_page()
        
        try:
            # Navigate to URL
            page.goto("https://www.example.com")
            
            # Wait for page to load
            page.wait_for_load_state("networkidle")
            
            # Get title
            title = page.title()
            print(f"Page title: {title}")
            
            # Find and interact with elements
            # page.click("button#submit")
            # page.fill("input#username", "myusername")
            # page.locator("h1").text_content()
            
            # Take screenshot
            # page.screenshot(path="screenshot.png")
            
            print("Script executed successfully!")
            time.sleep(5)  # Keep browser open to see it
            
        except Exception as e:
            print(f"Error: {e}")
        
        finally:
            browser.close()


if __name__ == "__main__":
    main()
```

---

## **2. Pyppeteer (Puppeteer for Python)**

Python port of Google's Puppeteer (Chrome DevTools Protocol)

**Installation:**
```bash
pip install pyppeteer
```

**Basic Code:**
```python
import asyncio
from pyppeteer import launch


async def main():
    # Launch browser
    browser = await launch(headless=False, args=['--start-maximized'])
    page = await browser.newPage()
    
    try:
        # Navigate
        await page.goto('https://www.example.com')
        
        # Get title
        title = await page.title()
        print(f'Title: {title}')
        
        # Interact with elements
        # await page.click('#button')
        # await page.type('#input', 'text')
        
        await asyncio.sleep(5)
        
    except Exception as e:
        print(f"Error: {e}")
    
    finally:
        await browser.close()


if __name__ == '__main__':
    asyncio.get_event_loop().run_until_complete(main())
```

---

## **3. Helium (Simplified Selenium Wrapper)**

Easier syntax built on top of Selenium - if you still want Selenium

**Installation:**
```bash
pip install helium
```

**Basic Code:**
```python
from helium import *


def main():
    # Start browser (it handles drivers automatically in some cases)
    start_chrome("https://www.example.com")
    # or: start_firefox()
    
    # Much simpler syntax
    write("search query", into="Search")
    click("Search button")
    
    # Check if text exists
    if Text("Result").exists():
        print("Found result!")
    
    wait_until(Text("Something").exists)
    
    kill_browser()


if __name__ == "__main__":
    main()
```

---

## **4. Requests-HTML (Lightweight Alternative)**

For simpler scraping without full browser automation

**Installation:**
```bash
pip install requests-html
```

**Basic Code:**
```python
from requests_html import HTMLSession


def main():
    session = HTMLSession()
    
    # Get page
    r = session.get('https://www.example.com')
    
    # Render JavaScript (uses Chromium behind scenes)
    r.html.render()
    
    # Find elements
    title = r.html.find('title', first=True).text
    print(f"Title: {title}")
    
    # CSS selectors
    links = r.html.find('a')
    for link in links:
        print(link.attrs.get('href'))


if __name__ == "__main__":
    main()
```

---

## **Comparison Table:**

| Tool | Difficulty | Speed | Features | Best For |
|------|-----------|-------|----------|----------|
| **Playwright** | Medium | ⚡⚡⚡ | Most complete | Modern automation |
| **Pyppeteer** | Medium | ⚡⚡⚡ | Good | Chrome-specific tasks |
| **Helium** | Easy | ⚡⚡ | Limited | Simple Selenium tasks |
| **Requests-HTML** | Easy | ⚡⚡ | Basic | Light scraping |

---

## **My Recommendation for You:**

**Try Playwright first!** It's the most modern, doesn't have driver issues, and is actually easier to work with than Selenium.

```bash
pip install playwright
playwright install chromium
```

Then use the Playwright code I provided above. It should work without all the driver headaches you've been experiencing!

**Want me to create a more complete Playwright example for you?** 🚀