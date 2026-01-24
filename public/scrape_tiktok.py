#!/usr/bin/env python3
"""
TikTok Scraper - CLEAN JSON OUTPUT
title, url, image_url only - HEADLESS
"""

import json, sys, re, time, tempfile, shutil
from playwright.sync_api import sync_playwright
import io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def clean_url(url):
    if url.startswith('//'): return 'https:' + url
    if not url.startswith('https://'): return 'https://www.tiktok.com' + url.lstrip('/')
    return url

def main():
    username = sys.argv[1] if len(sys.argv) > 1 else "pannonegyetem"
    
    temp_dir = tempfile.mkdtemp()
    videos = []
    
    try:
        with sync_playwright() as p:
            context = p.chromium.launch_persistent_context(
            temp_dir,
            headless=True,
            args=[
                '--disable-blink-features=AutomationControlled',
                '--no-sandbox',
                '--disable-dev-shm-usage'
            ],
            viewport={'width': 1366, 'height': 768}
        )
            page = context.new_page()
            
            page.goto(f"https://www.tiktok.com/@{username}", wait_until='networkidle')
            time.sleep(5)
            
            # Scroll to load videos
            for _ in range(10):
                page.keyboard.press('End')
                time.sleep(2)
            
            time.sleep(3)
            
            # Extract first 10 videos
            video_elements = page.query_selector_all('a[href*="/video/"]')[:10]
            
            for link in video_elements:
                try:
                    href = link.get_attribute('href')
                    if not href or '/video/' not in href:
                        continue
                    
                    video_url = clean_url(href)
                    
                    # Title from alt text
                    title = ""
                    alt_img = link.query_selector('img[alt]')
                    if alt_img:
                        title = alt_img.get_attribute('alt') or ""
                    
                    # Thumbnail from tiktokcdn
                    thumb = ""
                    imgs = link.query_selector_all('img')
                    for img in imgs:
                        src = img.get_attribute('src') or img.get_attribute('data-src')
                        if src and 'tiktokcdn' in src:
                            thumb = src
                            break
                    
                    if video_url:
                        videos.append({
                            "title": title[:200],
                            "url": video_url,
                            "image_url": thumb
                        })
                    
                except:
                    continue
            
            context.close()
    
    except:
        pass
    
    finally:
        shutil.rmtree(temp_dir, ignore_errors=True)
    
    print(json.dumps(videos, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    main()