#!/usr/bin/env python3
"""
Facebook Scraper - Render Compatible
Robust selectors + better waiting + fallback methods
"""

import sys
import json
import time
import io
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
from urllib.parse import urljoin, urlparse
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, NoSuchElementException
import os

def scrape_facebook_posts():
    """Robust Facebook scraper for Render"""
    driver = None
    try:
        # Enhanced Chrome Options for Render
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=1920,1080")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_argument("--disable-extensions")
        chrome_options.add_argument("--disable-plugins")
        chrome_options.add_argument("--disable-images")  # Faster loading
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36")

        # Use Render's chromedriver path
        service = Service('/usr/bin/chromedriver')
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.set_page_load_timeout(30)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
        driver.execute_script("Object.defineProperty(navigator, 'plugins', {get: () => [1, 2, 3, 4, 5]})")
        driver.execute_script("Object.defineProperty(navigator, 'languages', {get: () => ['en-US', 'en']});")

        url = "https://www.facebook.com/pannon.nagykanizsa"
        print("Loading Facebook page...", file=sys.stderr)
        driver.get(url)

        # Wait for page to load
        wait = WebDriverWait(driver, 15)
        wait.until(EC.presence_of_element_located((By.TAG_NAME, "div")))

        # Multiple scroll attempts to load content
        print("Scrolling to load posts...", file=sys.stderr)
        for i in range(3):
            driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
            time.sleep(3)
            driver.execute_script("window.scrollTo(0, 0);")
            time.sleep(2)

        # ROBUST post detection - multiple selector strategies
        post_selectors = [
            'div[role="article"]',
            'div[data-pagelet^="FeedUnit"]', 
            '[data-testid="fbfeed_story"]',
            'div[role="feed"] div[role="article"]',
            'div[data-pagelet-root="true"] div[role="article"]',
            'div.x1yztbdb div[role="article"]',  # Common FB class pattern
            'div[aria-posinset] div[role="article"]'
        ]

        posts = []
        article = None
        
        for selector in post_selectors:
            try:
                articles = driver.find_elements(By.CSS_SELECTOR, selector)
                print(f"Trying selector '{selector}': found {len(articles)} articles", file=sys.stderr)
                if articles:
                    # Filter out very short/empty posts
                    for art in articles[:3]:  # Check first 3
                        text = get_post_text(art)
                        if text and len(text.strip()) > 20:
                            article = art
                            print(f"Found valid post with selector '{selector}'", file=sys.stderr)
                            break
                    if article:
                        break
            except Exception as e:
                print(f"Selector {selector} failed: {e}", file=sys.stderr)
                continue

        if not article:
            # Last resort: grab any text content that looks like a post
            all_divs = driver.find_elements(By.CSS_SELECTOR, 'div[dir="auto"], div[data-ad-preview="message"]')
            for div in all_divs[:5]:
                text = div.text.strip()
                if len(text) > 50:
                    article = div
                    print("Using fallback article detection", file=sys.stderr)
                    break

        if article:
            title = get_post_text(article)
            post_url = get_post_url(article, url)
            image_url = get_post_image(article)

            posts.append({
                "title": title[:500] if title else "",
                "url": post_url,
                "image_url": image_url
            })
            print(f"Extracted: {title[:100]}...", file=sys.stderr)

        result = {
            "latest_1": posts[0] if posts else {"title": "", "url": "", "image_url": ""},
            "source_url": url,
            "status": "success",
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    except Exception as e:
        print(f"Scraper error: {str(e)}", file=sys.stderr)
        result = {
            "latest_1": {"title": "", "url": "", "image_url": ""},
            "source_url": "https://www.facebook.com/pannon.nagykanizsa",
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }
    
    finally:
        if driver:
            try:
                driver.quit()
            except:
                pass

    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

def get_post_text(article):
    """Extract text from article with multiple fallback selectors"""
    text_selectors = [
        'span[dir="auto"]',
        'div[dir="auto"] span',
        'div[data-ad-preview="message"] span',
        'div.x1lliihq span',
        'span',
        'div'
    ]
    
    for selector in text_selectors:
        try:
            elems = article.find_elements(By.CSS_SELECTOR, selector)
            for elem in elems[:3]:  # First 3 elements
                text = elem.text.strip()
                if text and len(text) > 10:
                    return text
        except:
            continue
    return ""

def get_post_url(article, base_url):
    """Extract post URL"""
    link_selectors = [
        'a[href*="/posts/"]',
        'a[href*="/pfbid"]', 
        'a[href*="/story_fbid="]',
        'a'
    ]
    
    for selector in link_selectors:
        try:
            links = article.find_elements(By.CSS_SELECTOR, selector)
            for link in links:
                href = link.get_attribute("href")
                if href and ("/posts/" in href or "/pfbid" in href):
                    return urljoin(base_url, href).split("?")[0]
        except:
            continue
    return ""

def get_post_image(article):
    """Extract image URL"""
    try:
        imgs = article.find_elements(By.CSS_SELECTOR, 
            'img[src*="scontent"], img[src*="fbcdn"], img'
        )
        for img in imgs:
            src = img.get_attribute("src")
            if src and ("scontent" in src or "fbcdn" in src) and "emoji" not in src.lower():
                return src
    except:
        pass
    return ""

if __name__ == "__main__":
    scrape_facebook_posts()