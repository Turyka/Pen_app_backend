#!/usr/bin/env python3
"""
TikTok Scraper – Laravel Compatible
Returns ALWAYS valid JSON
Scrapes latest 10 videos from a profile
"""

from playwright.sync_api import sync_playwright
import json, sys, re, time, urllib.parse, io

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def clean_tiktok_url(url):
    """Fix malformed TikTok URLs"""
    if url.startswith('https://www.tiktok.comhttps://'):
        return url[27:]
    return url

def extract_permanent_thumbnail_from_api(image_url):
    """Extract permanent thumbnail ID from TikTok API patterns"""
    if not image_url:
        return None
    
    match = re.search(r'/tos[^/]+/([^~?]+)', image_url)
    if match:
        image_id = match.group(1)
        return f"https://p16-sign.tiktokcdn-us.com/tos-useast5-avt-006-tx/o{image_id}?x-expires=1735689600&x-signature=permanent"
    
    if '.webp' in image_url:
        parsed = urllib.parse.urlparse(image_url)
        return f"{parsed.scheme}://{parsed.netloc}{parsed.path}"
    
    return None

def scrape_tiktok_profile(username, num_posts=10):
    """Scrape latest videos from a TikTok profile"""
    videos = []
    
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(
            user_agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            viewport={'width': 1920, 'height': 1080}
        )
        page = context.new_page()
        
        # Capture thumbnails from network requests
        thumbnails = {}
        def handle_request(route, request):
            url = request.url
            if 'tiktokcdn' in url and ('aweme' in url or 'cover' in url or 'image' in url):
                match = re.search(r'/tos[^/]+/([^~?]+)', url)
                if match:
                    img_id = match.group(1)
                    thumbnails[img_id] = url
            route.continue_()
        
        page.route('**/*', handle_request)
        
        profile_url = f"https://www.tiktok.com/@{username}"
        page.goto(profile_url, wait_until='networkidle', timeout=30000)
        page.wait_for_selector('[data-e2e="user-post-item"], .tiktok-x6y88p-DivItemContainer', timeout=15000)
        time.sleep(3)
        
        # Scroll to load more videos
        for _ in range(6):
            page.keyboard.press('End')
            time.sleep(1)
        
        video_elements = page.query_selector_all(
            '[data-e2e="user-post-item"], .tiktok-x6y88p-DivItemContainer, a[href*="/video/"]'
        )[:num_posts*2]
        
        for elem in video_elements[:num_posts]:
            try:
                link_elem = elem.query_selector('a[href*="/video/"]')
                video_url = None
                if link_elem:
                    href = link_elem.get_attribute('href')
                    if href and '/video/' in href:
                        video_url = clean_tiktok_url(href)
                        if not video_url.startswith('https://www.tiktok.com/'):
                            video_url = f"https://www.tiktok.com{video_url}"
                
                if not video_url:
                    continue
                
                # Extract thumbnail
                image_url = None
                img_elems = elem.query_selector_all('img')
                for img in img_elems[:3]:
                    img_src = img.get_attribute('src') or img.get_attribute('data-src')
                    if img_src and 'tiktokcdn' in img_src:
                        match = re.search(r'/tos[^/]+/([^~?]+)', img_src)
                        if match:
                            img_id = match.group(1)
                            if img_id in thumbnails:
                                image_url = thumbnails[img_id]
                                break
                            else:
                                image_url = extract_permanent_thumbnail_from_api(img_src)
                                if image_url:
                                    break
                
                videos.append({
                    'url': video_url,
                    'image_url': image_url or ""
                })
            except:
                continue
        
        browser.close()
    
    return videos[:num_posts]

def main():
    try:
        # Hardcoded profile or could be from sys.argv
        username = "pannonegyetem"
        posts = scrape_tiktok_profile(username, 10)
        result = {
            "status": "success",
            "posts": posts
        }
    except Exception as e:
        result = {
            "status": "error",
            "error": str(e),
            "posts": []
        }
    
    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    main()
