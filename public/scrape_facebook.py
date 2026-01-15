#!/usr/bin/env python3
"""
Facebook Scraper - Laravel Compatible
Returns ALWAYS valid JSON
SIMPLE FIX FOR LINUX
"""

import sys
import json
import time
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
import re
from urllib.parse import urljoin
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
import os

def scrape_facebook_posts():
    """Main scraper - always returns JSON"""
    try:
        # Chrome Options (works everywhere)
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-images")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")

        # FIX 1: TELL SELENIUM WHERE CHROMEDRIVER IS ON LINUX
        service = None
        # Check if we're on Linux (Render/Alpine)
        if os.name == 'posix':  # Linux/Unix
            # Common Linux chromedriver paths
            linux_paths = [
                '/usr/bin/chromedriver',      # Alpine default
                '/usr/local/bin/chromedriver', # Common install
                '/usr/lib/chromium/chromedriver',
            ]
            for path in linux_paths:
                if os.path.exists(path):
                    service = Service(executable_path=path)
                    break
        
        # If no Linux path found or on Windows, use default
        if service is None:
            service = Service()  # Auto-detect on Windows

        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        url = "https://www.facebook.com/pannon.nagykanizsa"
        driver.get(url)

        # FIX 2: INCREASE WAIT TIME FOR LINUX (SLOWER)
        wait_time = 10 if os.name == 'posix' else 5  # Linux: 10 sec, Windows: 5 sec
        time.sleep(wait_time)

        # Force scroll + image load
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(2)

        driver.execute_script("""
            window.scrollTo(0, 0);
            setTimeout(() => {
                document.querySelectorAll('img').forEach(img => {
                    if (!img.src || !img.complete) img.scrollIntoView({behavior: 'instant'});
                });
            }, 1500);
        """)
        time.sleep(2)

        posts = []
        selectors = ['div[role="article"]', 'div[data-pagelet^="FeedUnit"]', '[data-testid="fbfeed_story"]']
        
        article = None
        for selector in selectors:
            articles = driver.find_elements(By.CSS_SELECTOR, selector)
            if articles:
                article = articles[0]
                break

        if article:
            # TITLE
            title = ""
            text_selectors = [
                'div[data-ad-preview="message"] span',
                'div[dir="auto"] span',
                'div.x1lliihq span'
            ]
            for sel in text_selectors:
                try:
                    elems = article.find_elements(By.CSS_SELECTOR, sel)
                    if elems:
                        title = elems[0].text.strip()
                        if len(title) > 10:
                            break
                except:
                    continue

            if title:
                # URL
                links = article.find_elements(By.CSS_SELECTOR, 'a[href*="/posts/"], a[href*="/pfbid"]')
                post_url = ""
                if links:
                    href = links[0].get_attribute("href")
                    if href:
                        post_url = urljoin("https://www.facebook.com", href).split("?")[0]

                # IMAGE (3 methods)
                image_url = ""
                imgs = article.find_elements(By.CSS_SELECTOR, 
                    'img[src*="scontent"], img[src*="fbcdn"], img[data-imgperflogname*="image"]'
                )
                for img in imgs:
                    src = img.get_attribute("src")
                    if src and ("scontent" in src or "fbcdn" in src) and "emoji" not in src.lower():
                        image_url = src
                        break

                posts.append({
                    "title": title[:500],
                    "url": post_url,
                    "image_url": image_url
                })

        driver.quit()

        result = {
            "success": True,
            "saved": True,
            "post": posts[0] if posts else {"title": "", "url": "", "image_url": ""},
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    except Exception as e:
        result = {
            "success": False,
            "saved": False,
            "post": {"title": "", "url": "", "image_url": ""},
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    # ---------- ALWAYS PRINT JSON ----------
    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_facebook_posts()