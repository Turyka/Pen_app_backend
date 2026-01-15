#!/usr/bin/env python3
"""
Facebook Scraper - Laravel Compatible (FIXED)
Returns ALWAYS valid JSON with BEST post
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
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")

        # Auto-detect chromedriver
        service = None
        if os.path.exists('/usr/bin/chromedriver'):
            service = Service('/usr/bin/chromedriver')

        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        url = "https://www.facebook.com/pannon.nagykanizsa"
        driver.get(url)

        # BETTER scrolling - multiple scrolls to load more posts
        for i in range(3):
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(3)

        # Scroll back up and force image load
        driver.execute_script("window.scrollTo(0, 0);")
        time.sleep(2)

        driver.execute_script("""
            document.querySelectorAll('img').forEach(img => {
                if (!img.complete) {
                    img.scrollIntoView({behavior: 'instant'});
                    img.src = img.src;
                }
            });
        """)
        time.sleep(3)

        all_posts = []
        selectors = [
            'div[role="article"]', 
            'div[data-pagelet^="FeedUnit"]', 
            '[data-testid="fbfeed_story"]',
            'div[role="feed"] > div > div > div > div > div[role="article"]'
        ]
        
        # Try all selectors and collect ALL posts
        for selector in selectors:
            try:
                articles = driver.find_elements(By.CSS_SELECTOR, selector)
                for article in articles[:5]:  # Max 5 posts
                    post_data = extract_post_data(article)
                    if post_data and post_data['title']:  # Only valid posts
                        all_posts.append(post_data)
            except:
                continue

        driver.quit()

        # Pick BEST post (longest title, valid URL, image preferred)
        best_post = {"title": "", "url": "", "image_url": ""}
        if all_posts:
            all_posts.sort(key=lambda x: len(x['title']), reverse=True)
            best_post = all_posts[0]

        result = {
            "latest_1": best_post,
            "all_posts_count": len(all_posts),
            "source_url": url,
            "status": "success",
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    except Exception as e:
        result = {
            "latest_1": {"title": "", "url": "", "image_url": ""},
            "all_posts_count": 0,
            "source_url": "https://www.facebook.com/pannon.nagykanizsa",
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

def extract_post_data(article):
    """Extract data from single article - returns None if invalid"""
    try:
        # TITLE - multiple selectors, pick longest valid text
        title = ""
        text_selectors = [
            'div[data-ad-preview="message"] span',
            'div[dir="auto"] span',
            'div.x1lliihq span',
            'span.x1lliihq',
            'div[data-testid="post_message"] span',
            'div[style*="font"] span'
        ]
        
        for sel in text_selectors:
            try:
                elems = article.find_elements(By.CSS_SELECTOR, sel)
                for elem in elems[:3]:  # First 3 spans
                    text = elem.text.strip()
                    if 15 < len(text) < 500 and text not in title:  # Valid length
                        title = text
                        break
                if len(title) > 15:
                    break
            except:
                continue

        if not title:
            return None

        # URL - multiple link selectors
        post_url = ""
        link_selectors = [
            'a[href*="/posts/"]',
            'a[href*="/pfbid"]', 
            'a[href*="/permalink"]',
            'a[role="link"]'
        ]
        for link_sel in link_selectors:
            links = article.find_elements(By.CSS_SELECTOR, link_sel)
            if links:
                href = links[0].get_attribute("href")
                if href and ("/posts/" in href or "/pfbid" in href):
                    post_url = urljoin("https://www.facebook.com", href).split("?")[0]
                    break

        # IMAGE - Facebook CDN
        image_url = ""
        img_selectors = [
            'img[src*="scontent"]',
            'img[src*="fbcdn"]', 
            'img[data-imgperflogname*="image"]'
        ]
        for img_sel in img_selectors:
            imgs = article.find_elements(By.CSS_SELECTOR, img_sel)
            for img in imgs:
                src = img.get_attribute("src")
                if src and ("scontent" in src or "fbcdn" in src) and "emoji" not in src.lower():
                    image_url = src
                    break
            if image_url:
                break

        return {
            "title": title[:500],
            "url": post_url,
            "image_url": image_url
        }
    except:
        return None

if __name__ == "__main__":
    scrape_facebook_posts()