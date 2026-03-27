#!/usr/bin/env python3
"""
TikTok Scraper - Facebook Style (514MB Render.com SAFE) - FEB 2026 UPDATE
Selenium + LOW MEMORY like your working Facebook script
SENDS DATA TO LARAVEL API (SECURE)
"""

import sys
import json
import time
import io
import os
import requests
from urllib.parse import urljoin

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# ---------------- CONFIG ----------------
LARAVEL_API_URL = "https://pen-app-backend.onrender.com/api/tiktok-keres"
API_KEY = "dQw4w9WgXcQ"
# ---------------------------------------


def send_to_laravel(post):
    """Send one TikTok post to Laravel API"""
    try:
        headers = {
            "Content-Type": "application/json",
            "X-API-KEY": API_KEY,
        }

        response = requests.post(
            LARAVEL_API_URL,
            headers=headers,
            json={
                "title": post["title"],
                "url": post["url"],
                "image_url": post["image_url"],
            },
            timeout=10,
        )

        return {
            "status_code": response.status_code,
            "response": response.json()
        }

    except Exception as e:
        return {
            "status_code": None,
            "error": str(e)
        }


def scrape_tiktok_posts():
    """TikTok scraper - FEB 2026 selectors"""
    try:
        # Chrome options (LOW MEMORY)
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-gpu")
        chrome_options.add_argument("--window-size=360,640")
        chrome_options.add_argument("--disable-images")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument(
            "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
        )

        service = None
        if os.path.exists('/usr/bin/chromedriver'):
            service = Service('/usr/bin/chromedriver')

        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script(
            "Object.defineProperty(navigator, 'webdriver', {get: () => undefined})"
        )
        driver.set_page_load_timeout(20)

        url = "https://www.tiktok.com/@pannonegyetem"
        driver.get(url)
        
        # Wait for page to load
        WebDriverWait(driver, 15).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        time.sleep(5)

        # Scroll to load videos
        driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(4)
        
        # Scroll back up and force image loading
        driver.execute_script("window.scrollTo(0, 0);")
        time.sleep(2)
        driver.execute_script("window.scrollTo(0, 800);")
        time.sleep(3)

        posts = []
        video_links = []

        # 2026 TikTok selectors - multiple fallback patterns
        selectors_2026 = [
            # Primary video links
            'a[href*="/video/"]',
            'a[data-e2e="user-post-item"]',
            '[role="link"][href*="/video/"]',
            
            # Container-based
            '.tiktok-x6y88p-DivItemContainer a[href*="/video/"]',
            '[data-e2e="user-post-item-list"] a',
            '.DivListItem a[href*="/video/"]',
            
            # Fallback patterns
            'div[data-e2e="user-post-item"] a',
            '.video-feed-item a[href*="/video/"]'
        ]

        print("DEBUG: Searching for video links...")
        
        for selector in selectors_2026:
            try:
                elements = driver.find_elements(By.CSS_SELECTOR, selector)
                print(f"DEBUG: Selector '{selector}' found {len(elements)} elements")
                if elements:
                    # Filter to get only video links and dedupe
                    video_elements = []
                    seen_hrefs = set()
                    for elem in elements[:10]:  # Limit to first 10
                        href = elem.get_attribute("href")
                        if href and "/video/" in href and href not in seen_hrefs:
                            seen_hrefs.add(href)
                            video_elements.append(elem)
                    
                    if video_elements:
                        video_links = video_elements[:5]
                        print(f"DEBUG: Using {len(video_links)} video links from selector '{selector}'")
                        break
            except Exception as e:
                print(f"DEBUG: Selector '{selector}' failed: {str(e)}")
                continue

        if not video_links:
            # Last resort - grab any clickable video containers
            fallback_selectors = [
                'div[data-e2e="user-post-item"]',
                '.tiktok-x6y88p-DivItemContainer',
                '[data-e2e="user-post-item-list"] > div > a'
            ]
            for sel in fallback_selectors:
                elements = driver.find_elements(By.CSS_SELECTOR, sel)
                if elements:
                    video_links = elements[:5]
                    print(f"DEBUG: Fallback selector '{sel}' used: {len(video_links)} elements")
                    break

        print(f"DEBUG: Processing {len(video_links)} video links")

        for i, video_link in enumerate(video_links[:5]):
            try:
                # Get title from multiple sources
                title = ""
                title_selectors = [
                    'img[alt*="】"]',  # Japanese captions often end with 】
                    'img[alt]', 
                    'img[aria-label]',
                    '.DivTitle',
                    '[data-e2e="user-title"]',
                    'span',
                    '.Caption'
                ]

                for sel in title_selectors:
                    try:
                        elems = video_link.find_elements(By.CSS_SELECTOR, sel)
                        for elem in elems:
                            candidate = (
                                elem.get_attribute("alt") or
                                elem.get_attribute("aria-label") or
                                elem.get_attribute("title") or
                                elem.text or
                                elem.get_attribute("textContent")
                            )
                            if candidate and len(candidate.strip()) > 5:
                                title = candidate.strip()[:500]
                                print(f"DEBUG: Title found '{title[:50]}...' from {sel}")
                                break
                        if title:
                            break
                    except:
                        continue

                if not title:
                    # Generate fallback title from URL or position
                    href = video_link.get_attribute("href")
                    title = f"TikTok Video {i+1}"
                    print(f"DEBUG: Using fallback title for video {i+1}")

                href = video_link.get_attribute("href")
                post_url = (
                    urljoin("https://www.tiktok.com", href).split("?")[0]
                    if href else f"{url}/video/{i+1}"
                )

                # Get thumbnail
                image_url = ""
                img_selectors = ['img', '[style*="background-image"]']
                for img_sel in img_selectors:
                    imgs = video_link.find_elements(By.CSS_SELECTOR, img_sel)
                    for img in imgs:
                        src = img.get_attribute("src")
                        if src and ("tiktokcdn" in src or "p16-sign" in src):
                            image_url = src
                            print(f"DEBUG: Image found: {image_url[:100]}...")
                            break
                    if image_url:
                        break

                post_data = {
                    "title": title,
                    "url": post_url,
                    "image_url": image_url
                }

                posts.append(post_data)
                print(f"DEBUG: Post {i+1}: {title[:50]}... -> {post_url}")

                # Send to Laravel
                result = send_to_laravel(post_data)
                print(f"DEBUG: API result for post {i+1}: {result}")
                time.sleep(1)

            except Exception as e:
                print(f"DEBUG: Error processing video {i+1}: {str(e)}")
                continue

        driver.quit()

        # Format result
        result = {
            f"latest_{i+1}": posts[i] if i < len(posts) else {
                "title": "",
                "url": "",
                "image_url": ""
            }
            for i in range(5)
        }

        result.update({
            "source_url": url,
            "status": "success" if posts else "no_videos",
            "found_posts": len(posts),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        })

        print(json.dumps(result, ensure_ascii=False))
        sys.stdout.flush()

    except Exception as e:
        print(f"DEBUG: Main exception: {str(e)}")
        result = {
            f"latest_{i+1}": {"title": "", "url": "", "image_url": ""}
            for i in range(5)
        }
        result.update({
            "source_url": url,
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        })
        print(json.dumps(result, ensure_ascii=False))
        sys.stdout.flush()


if __name__ == "__main__":
    scrape_tiktok_posts()