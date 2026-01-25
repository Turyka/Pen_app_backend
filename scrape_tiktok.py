#!/usr/bin/env python3
import sys, json, io, time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.common.exceptions import TimeoutException, WebDriverException

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

# RENDER TIMEOUTS - SAFE
MAX_RUNTIME = 15  # Render kills at ~30s
PAGE_TIMEOUT = 10
IMPLICIT_WAIT = 3

VIDEO_SELECTORS = (
    'a[href*="/video/"]',
    '[data-e2e="user-post-item"] a',
    'a[href*="@"][href*="/video/"]',
)

def clean_url(url: str) -> str:
    if not url: return ""
    if url.startswith("//"): return "https:" + url
    if not url.startswith("http"): return "https://www.tiktok.com" + url
    return url

def create_driver() -> webdriver.Chrome:
    options = Options()

    # RENDER-OPTIMIZED Chrome
    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--disable-dev-tools")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-plugins")
    options.add_argument("--disable-images")  # Save memory
    options.add_argument("--window-size=800,600")  # Smaller
    options.add_argument("--disable-background-timer-throttling")
    options.add_argument("--disable-renderer-backgrounding")
    
    # KEEP WHAT WORKS - your exact UA
    options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
    
    # Anti-detection (keep)
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option("useAutomationExtension", False)
    options.page_load_strategy = "eager"

    return webdriver.Chrome(options=options)

def find_video_links(driver):
    # Your exact selectors - FASTER execution
    for selector in VIDEO_SELECTORS:
        elements = driver.find_elements(By.CSS_SELECTOR, selector)
        if elements:
            return elements[:1]

    # Your scroll - but faster
    driver.execute_script("window.scrollBy(0, 300);")
    time.sleep(1)  # Reduced from 2s
    return driver.find_elements(By.CSS_SELECTOR, 'a[href*="/video/"]')[:1]

def extract_video_data(link):
    # Your exact logic
    href = link.get_attribute("href")
    if not href or "/video/" not in href:
        return None

    title = ""
    thumb = ""

    try:
        img = link.find_element(By.TAG_NAME, "img")
        title = (img.get_attribute("alt") or img.get_attribute("aria-label") or "")[:100]
        thumb = img.get_attribute("src") or ""
    except:
        try:
            title_elem = link.find_element(By.CSS_SELECTOR, "span, div[title]")
            title = title_elem.text[:100]
        except:
            pass

    return {"title": title, "url": clean_url(href), "image_url": thumb}

def main():
    username = sys.argv[1] if len(sys.argv) > 1 else "pannonegyetem"
    start_time = time.time()
    videos = []

    # SINGLE ATTEMPT - FASTER
    driver = None
    try:
        print("🚀 Render-Optimized Scraper", flush=True)
        driver = create_driver()
        driver.set_page_load_timeout(PAGE_TIMEOUT)
        driver.implicitly_wait(IMPLICIT_WAIT)

        driver.get(f"https://www.tiktok.com/@{username}")
        
        if time.time() - start_time > 12:  # Early exit
            raise TimeoutException("Early timeout")

        links = find_video_links(driver)

        for link in links:
            if time.time() - start_time > MAX_RUNTIME:
                break
            data = extract_video_data(link)
            if data:
                videos.append(data)

        print(f"✅ Found {len(videos)} videos in {time.time()-start_time:.1f}s", flush=True)
        
    except Exception as e:
        print(f"⚠️ {e}", flush=True)
    finally:
        if driver:
            driver.quit()

    print(json.dumps(videos, ensure_ascii=False, indent=2), flush=True)

if __name__ == "__main__":
    main()