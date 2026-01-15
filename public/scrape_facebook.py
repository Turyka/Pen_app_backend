#!/usr/bin/env python3
"""
Facebook Scraper - Docker/Alpine Compatible
"""

import sys
import json
import time
import os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def scrape_facebook_posts():
    try:
        # Docker/Alpine Chrome paths (from your Dockerfile)
        chrome_binary = os.environ.get('CHROME_BIN', '/usr/bin/chromium-browser')
        chrome_driver = os.environ.get('CHROME_DRIVER', '/usr/bin/chromedriver')
        
        print(f"Using Chrome: {chrome_binary}", file=sys.stderr)
        print(f"Using Driver: {chrome_driver}", file=sys.stderr)
        
        # Verify binaries exist
        if not os.path.exists(chrome_binary):
            raise Exception(f"Chrome binary not found: {chrome_binary}")
        if not os.path.exists(chrome_driver):
            raise Exception(f"Chromedriver not found: {chrome_driver}")

        # Chrome Options - Docker/Alpine optimized
        options = Options()
        options.binary_location = chrome_binary
        options.add_argument("--headless=new")
        options.add_argument("--no-sandbox")
        options.add_argument("--disable-dev-shm-usage")
        options.add_argument("--disable-gpu")
        options.add_argument("--disable-extensions")
        options.add_argument("--disable-plugins")
        options.add_argument("--disable-images")  # Faster load
        options.add_argument("--window-size=1920,1080")
        options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")
        options.add_argument("--disable-blink-features=AutomationControlled")
        options.add_experimental_option("excludeSwitches", ["enable-automation"])
        options.add_experimental_option('useAutomationExtension', False)
        options.add_argument("--disable-web-security")
        options.add_argument("--allow-running-insecure-content")
        options.add_argument("--ignore-certificate-errors")

        # Service with explicit driver path
        service = Service(chrome_driver)
        driver = webdriver.Chrome(service=service, options=options)
        
        # Anti-detection
        driver.execute_cdp_cmd('Page.addScriptToEvaluateOnNewDocument', {
            'source': '''
                Object.defineProperty(navigator, 'webdriver', {get: () => undefined});
                Object.defineProperty(navigator, 'plugins', {get: () => [1,2,3,4,5]});
                Object.defineProperty(navigator, 'languages', {get: () => ["en-US", "en"]});
                window.chrome = {runtime: {}};
            '''
        })

        url = "https://www.facebook.com/pannon.nagykanizsa"
        print(f"Loading: {url}", file=sys.stderr)
        driver.set_page_load_timeout(30)
        driver.get(url)
        time.sleep(10)  # Alpine needs more time

        # Scroll for content
        for i in range(3):
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(3)

        time.sleep(5)

        posts = []
        selectors = [
            'div[role="article"]',
            'div[data-pagelet^="FeedUnit"]',
            '[data-testid="fbfeed_story"]',
            'div[role="feed"] div[role="article"]'
        ]
        
        article = None
        for selector in selectors:
            try:
                articles = driver.find_elements(By.CSS_SELECTOR, selector)
                if articles:
                    article = articles[0]
                    print(f"✅ Found post with: {selector}", file=sys.stderr)
                    break
            except Exception as e:
                print(f"Selector failed {selector}: {e}", file=sys.stderr)
                continue

        post_data = {"title": "", "url": "", "image_url": ""}

        if article:
            # Title/Text
            text_selectors = [
                'div[data-ad-preview="message"] span',
                'div[dir="auto"] span',
                'span.x1lliihq.x193iq5w.xeuugli.x13faqbe.x1vvkbs.x1xmvt09.x1lliihq.x1s928wv.xhnejp18.x1sa3eu8.x1qq9ws0.x26ufnx.x1ietd6p.x1i10hfl.x1qjc9v5.xjbqb8w.x1s688f.xzsf02u.a8c37x1j',
                'div[data-testid="post_message"] span'
            ]
            
            for sel in text_selectors:
                try:
                    elems = article.find_elements(By.CSS_SELECTOR, sel)
                    for elem in elems[:2]:
                        text = elem.text.strip()
                        if 20 < len(text) < 500:
                            post_data["title"] = text[:400]
                            print(f"✅ Title found: {post_data['title'][:50]}...", file=sys.stderr)
                            break
                    if post_data["title"]:
                        break
                except:
                    continue

            # Post URL
            try:
                links = article.find_elements(By.CSS_SELECTOR, 'a[href*="/posts/"], a[href*="/pfbid"], a[href*="/permalink"]')
                if links:
                    href = links[0].get_attribute("href")
                    if href:
                        post_data["url"] = href.split("?")[0]
                        print(f"✅ URL found", file=sys.stderr)
            except:
                pass

            # Image
            try:
                imgs = article.find_elements(By.CSS_SELECTOR, 'img[src*="scontent"], img[src*="fbcdn"]')
                for img in imgs:
                    src = img.get_attribute("src")
                    if src and ("scontent" in src or "fbcdn" in src) and "emoji" not in src.lower():
                        post_data["image_url"] = src
                        print(f"✅ Image found", file=sys.stderr)
                        break
            except:
                pass

        driver.quit()

        result = {
            "latest_1": post_data,
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

    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_facebook_posts()