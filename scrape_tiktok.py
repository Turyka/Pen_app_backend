#!/usr/bin/env python3
"""
TikTok Scraper - EXACT Facebook Copy (WORKS 514MB Render.com)
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
import os

def scrape_tiktok_posts():
    """TikTok - EXACT Facebook pattern that WORKS"""
    try:
        # ********** EXACT FACEBOOK OPTIONS **********
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-extensions")
        chrome_options.add_argument("--disable-background-networking")
        chrome_options.add_argument("--blink-settings=imagesEnabled=false")
        chrome_options.add_argument("--window-size=1280,720")  # ← FACEBOOK SIZE!
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")

        # EXACT Facebook service
        service = None
        if os.path.exists('/usr/bin/chromedriver'):
            service = Service('/usr/bin/chromedriver')

        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        # TikTok URL
        url = "https://www.tiktok.com/@pannonegyetem"
        driver.get(url)

        # EXACT Facebook timing
        time.sleep(5)

        # EXACT Facebook scroll
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(4)

        # EXACT Facebook image force-load
        driver.execute_script("""
            window.scrollTo(0, 0);
            setTimeout(() => {
                document.querySelectorAll('img').forEach(img => {
                    if (!img.src || !img.complete) img.scrollIntoView({behavior: 'instant'});
                });
            }, 1500);
        """)
        time.sleep(3)

        posts = []
        
        # ********** TIKTOK SELECTORS **********
        selectors = [
            'a[href*="/video/"]', 
            '[data-e2e="user-post-item"] a',
            'a[href*="/@pannonegyetem/video"]'
        ]
        
        video_link = None
        for selector in selectors:
            elements = driver.find_elements(By.CSS_SELECTOR, selector)
            if elements:
                video_link = elements[0]
                break

        if video_link:
            href = video_link.get_attribute("href")
            if href and "/video/" in href:
                # ********** EXACT Facebook title extraction **********
                title_selectors = ['img[alt]', 'img[aria-label]', 'span']
                title = ""
                
                for sel in title_selectors:
                    try:
                        elems = video_link.find_elements(By.CSS_SELECTOR, sel)
                        if elems:
                            title = (elems[0].get_attribute("alt") or 
                                   elems[0].get_attribute("aria-label") or 
                                   elems[0].text or "").strip()
                            title = title[:500]
                            if len(title) > 5:
                                break
                    except:
                        continue

                if title:
                    # URL
                    post_url = urljoin("https://www.tiktok.com", href).split("?")[0]

                    # IMAGE (TikTok CDN)
                    image_url = ""
                    imgs = video_link.find_elements(By.TAG_NAME, "img")
                    for img in imgs:
                        src = img.get_attribute("src")
                        if src and ("tiktokcdn" in src or "p16-sign" in src):
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

    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_tiktok_posts()