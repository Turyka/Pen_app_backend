#!/usr/bin/env python3
"""
Memory-optimized TikTok scraper
Returns latest video title, URL, and image URL
"""

import sys, json, time, io, os
from urllib.parse import urljoin
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def scrape_tiktok_posts():
    try:
        # -------- Chrome options (lightweight) --------
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-extensions")
        chrome_options.add_argument("--disable-background-networking")
        chrome_options.add_argument("--blink-settings=imagesEnabled=false")  # images off
        chrome_options.add_argument("--window-size=1280,720")
        
        # memory & CPU savers
        chrome_options.add_argument("--disable-features=TranslateUI,BackForwardCache,AcceptCHFrame,MediaRouter")
        chrome_options.add_argument("--disable-component-update")
        chrome_options.add_argument("--disable-sync")
        chrome_options.add_argument("--disable-default-apps")
        chrome_options.add_argument("--disable-hang-monitor")
        chrome_options.add_argument("--disable-ipc-flooding-protection")
        chrome_options.add_argument("--disable-renderer-backgrounding")
        chrome_options.add_argument("--disable-background-timer-throttling")
        chrome_options.add_argument("--disable-backgrounding-occluded-windows")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64)")
        
        # detect chromedriver
        service = Service('/usr/bin/chromedriver') if os.path.exists('/usr/bin/chromedriver') else None
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        url = "https://www.tiktok.com/@pannonegyetem"
        driver.get(url)
        time.sleep(3)  # shorter wait
        
        # -------- Scroll just enough for first video --------
        driver.execute_script("window.scrollTo(0, 200);")
        time.sleep(1)
        
        posts = []
        
        # -------- Single selector pass (first video) --------
        video_links = driver.find_elements(By.CSS_SELECTOR, '[data-e2e="user-post-item"] a[href*="/video/"]')
        if video_links:
            video_link = video_links[0]
            href = video_link.get_attribute("href")
            if href:
                # Title extraction
                title_elems = video_link.find_elements(By.CSS_SELECTOR, 'img[alt], img[aria-label], span')
                title = ""
                for t in title_elems:
                    title = (t.get_attribute("alt") or t.get_attribute("aria-label") or t.text or "").strip()
                    if len(title) > 5:
                        title = title[:500]
                        break
                
                # Post URL
                post_url = urljoin("https://www.tiktok.com", href).split("?")[0]
                
                # Image URL (get attribute only, do NOT load)
                image_url = ""
                imgs = video_link.find_elements(By.TAG_NAME, "img")
                for img in imgs:
                    src = img.get_attribute("src") or img.get_attribute("data-src") or img.get_attribute("data-thumb")
                    if src and ("tiktokcdn" in src or "p16-sign" in src):
                        image_url = src
                        break
                
                posts.append({"title": title, "url": post_url, "image_url": image_url})
        
        driver.quit()
        
        result = {
            "latest_1": posts[0] if posts else {"title": "", "url": "", "image_url": ""},
            "source_url": url,
            "status": "success" if posts else "no_videos",
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }
    
    except Exception as e:
        result = {
            "latest_1": {"title": "", "url": "", "image_url": ""},
            "source_url": url,
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }
    
    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_tiktok_posts()
