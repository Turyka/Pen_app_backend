#!/usr/bin/env python3
"""
TikTok Scraper - Facebook Style (514MB Render.com SAFE)
Selenium + LOW MEMORY like your working Facebook script
"""

import sys
import json
import time
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
from urllib.parse import urljoin
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.common.exceptions import TimeoutException
import os

def scrape_tiktok_posts():
    """TikTok scraper - EXACT Facebook memory pattern"""
    try:
        # EXACT Facebook Chrome Options (works on 514MB)
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=360,640")  # MOBILE = LESS MEMORY
        chrome_options.add_argument("--disable-images")       # NO IMAGES = LESS RAM
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")

        # EXACT Facebook service detection
        service = None
        if os.path.exists('/usr/bin/chromedriver'):
            service = Service('/usr/bin/chromedriver')

        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
        driver.set_page_load_timeout(15)

        url = "https://www.tiktok.com/@pannonegyetem"
        driver.get(url)
        time.sleep(4)  # Facebook-style wait

        # EXACT Facebook scroll pattern
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(3)

        driver.execute_script("""
            window.scrollTo(0, 500);
            setTimeout(() => {
                document.querySelectorAll('img').forEach(img => {
                    if (!img.src || !img.complete) img.scrollIntoView({behavior: 'instant'});
                });
            }, 1000);
        """)
        time.sleep(2)

        posts = []
        
        # YOUR TIKTOK SELECTORS - Facebook fallback style
        selectors = ['a[href*="/video/"]', '[data-e2e="user-post-item"] a']
        
        video_link = None
        for selector in selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements:
                video_link = elements[0]
                break

        if video_link:
            # EXACT Facebook title extraction pattern
            title_selectors = [
                'img[alt]', 
                'img[aria-label]',
                'span'
            ]
            
            title = ""
            for sel in title_selectors:
                try:
                    elems = video_link.find_elements(By.CSS_SELECTOR, sel)
                    if elems:
                        title = elems[0].get_attribute("alt") or elems[0].get_attribute("aria-label") or elems[0].text
                        title = title.strip()[:500]
                        if len(title) > 5:
                            break
                except:
                    continue

            if title:
                # URL (Facebook style)
                href = video_link.get_attribute("href")
                post_url = urljoin("https://www.tiktok.com", href).split("?")[0] if href else ""

                # IMAGE (Facebook 3-method style)
                image_url = ""
                imgs = video_link.find_elements(By.CSS_SELECTOR, 'img')
                for img in imgs:
                    src = img.get_attribute("src")
                    if src and "tiktokcdn" in src:
                        image_url = src
                        break

                posts.append({
                    "title": title,
                    "url": post_url,
                    "image_url": image_url
                })

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
            "source_url": "https://www.tiktok.com/@pannonegyetem",
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    # ---------- EXACT Facebook JSON output ----------
    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_tiktok_posts()