#!/usr/bin/env python3
"""
Facebook Scraper - Laravel Compatible
Returns ALWAYS valid JSON
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
        chrome_options.add_argument("--disable-extensions")
        chrome_options.add_argument("--disable-background-networking")
        chrome_options.add_argument("--blink-settings=imagesEnabled=false")
        chrome_options.add_argument("--window-size=1280,720")

        # NEW – memory savers
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

        # Auto-detect chromedriver
        service = None
        if os.path.exists('/usr/bin/chromedriver'):
            service = Service('/usr/bin/chromedriver')

        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        url = "https://www.facebook.com/pannon.nagykanizsa"
        driver.get(url)

        time.sleep(5)

        # Force scroll + image load
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(4)

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
            "latest_1": posts[0] if posts else {"title": "", "url": "", "image_url": ""},
            "source_url": url,
            "status": "success",
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    except Exception as e:
        result = {
            "latest_1": {"title": "", "url": "", "image_url": ""},
            "source_url": "https://www.facebook.com/pannon.nagykanizsa",
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    # ---------- ALWAYS PRINT JSON ----------
    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_facebook_posts()