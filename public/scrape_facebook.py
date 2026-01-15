#!/usr/bin/env python3
"""
Facebook Scraper - Alpine Linux Docker compatible
"""

import sys
import json
import time
import os
import subprocess
import logging
from urllib.parse import urljoin

# Set up logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

def check_chrome_installation():
    """Check if Chrome/Chromium is properly installed"""
    try:
        # Check if chromium-browser exists
        result = subprocess.run(['which', 'chromium-browser'], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            logger.info(f"Chromium found at: {result.stdout.strip()}")
            return True
        
        # Try chromium
        result = subprocess.run(['which', 'chromium'], 
                              capture_output=True, text=True)
        if result.returncode == 0:
            logger.info(f"Chromium found at: {result.stdout.strip()}")
            return True
            
        logger.error("Chromium not found in PATH")
        return False
        
    except Exception as e:
        logger.error(f"Error checking Chrome: {e}")
        return False

def scrape_facebook_posts():
    """Main scraper function for Alpine Linux"""
    result = {
        "success": False,
        "saved": False,
        "post": {
            "title": "",
            "url": "",
            "image_url": ""
        },
        "error": "",
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
    }
    
    driver = None
    try:
        # First check Chrome installation
        if not check_chrome_installation():
            result["error"] = "Chromium not installed or not in PATH"
            print(json.dumps(result, ensure_ascii=False))
            return
        
        # Import selenium inside try block
        from selenium import webdriver
        from selenium.webdriver.common.by import By
        from selenium.webdriver.chrome.options import Options
        from selenium.webdriver.chrome.service import Service
        
        logger.info("Initializing Chrome driver for Alpine Linux...")
        
        # Alpine Linux specific Chrome options
        chrome_options = Options()
        
        # Alpine/Linux specific
        chrome_options.add_argument('--headless=new')
        chrome_options.add_argument('--no-sandbox')
        chrome_options.add_argument('--disable-dev-shm-usage')
        chrome_options.add_argument('--disable-gpu')
        
        # Important: Alpine needs these additional arguments
        chrome_options.add_argument('--disable-software-rasterizer')
        chrome_options.add_argument('--disable-features=VizDisplayCompositor')
        
        # Set window size
        chrome_options.add_argument('--window-size=1920,1080')
        
        # User agent
        chrome_options.add_argument('--user-agent=Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
        
        # Disable images to save bandwidth
        chrome_options.add_argument('--blink-settings=imagesEnabled=false')
        
        # Try different Chrome binary locations for Alpine
        chrome_binary = None
        possible_paths = [
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/usr/local/bin/chromium',
            '/usr/bin/google-chrome-stable'  # Usually not on Alpine
        ]
        
        for path in possible_paths:
            if os.path.exists(path):
                chrome_binary = path
                logger.info(f"Using Chrome binary: {path}")
                break
        
        if chrome_binary:
            chrome_options.binary_location = chrome_binary
        
        # Set up service with chromedriver
        chromedriver_path = '/usr/bin/chromedriver'
        if not os.path.exists(chromedriver_path):
            chromedriver_path = '/usr/local/bin/chromedriver'
        
        logger.info(f"Using chromedriver at: {chromedriver_path}")
        service = Service(executable_path=chromedriver_path)
        
        # Initialize driver
        driver = webdriver.Chrome(service=service, options=chrome_options)
        
        # Anti-detection
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")
        
        logger.info("Navigating to Facebook...")
        url = "https://www.facebook.com/pannon.nagykanizsa"
        driver.get(url)
        
        # Wait longer for Alpine (slower)
        time.sleep(8)
        
        # Take screenshot for debugging
        screenshot_dir = '/tmp'
        if os.path.exists(screenshot_dir):
            try:
                screenshot_path = os.path.join(screenshot_dir, 'facebook_debug.png')
                driver.save_screenshot(screenshot_path)
                logger.info(f"Debug screenshot saved to: {screenshot_path}")
            except Exception as e:
                logger.warning(f"Could not save screenshot: {e}")
        
        # Scroll to load content
        for i in range(3):
            scroll_height = 1000 * (i + 1)
            driver.execute_script(f"window.scrollTo(0, {scroll_height});")
            time.sleep(3)
        
        # Try multiple strategies to find content
        posts_data = []
        
        # Strategy 1: Look for articles
        try:
            articles = driver.find_elements(By.CSS_SELECTOR, 'div[role="article"]')
            logger.info(f"Found {len(articles)} articles")
            
            for article in articles[:5]:
                try:
                    text = article.text.strip()
                    if len(text) < 40:
                        continue
                    
                    # Skip if it's just UI elements
                    ui_indicators = ['like', 'comment', 'share', 'follow', 'reaction']
                    if any(indicator in text.lower() for indicator in ui_indicators):
                        # If more than 2 UI indicators, probably not a post
                        ui_count = sum(1 for indicator in ui_indicators if indicator in text.lower())
                        if ui_count > 2:
                            continue
                    
                    # Extract URL
                    post_url = ""
                    try:
                        links = article.find_elements(By.TAG_NAME, 'a')
                        for link in links:
                            href = link.get_attribute('href')
                            if href and ('/posts/' in href or '/pfbid' in href):
                                post_url = urljoin('https://www.facebook.com', href.split('?')[0])
                                break
                    except:
                        pass
                    
                    # Extract image
                    image_url = ""
                    try:
                        imgs = article.find_elements(By.TAG_NAME, 'img')
                        for img in imgs:
                            src = img.get_attribute('src')
                            if src and ('scontent' in src or 'fbcdn' in src):
                                if 'emoji' not in src and 'static' not in src:
                                    image_url = src
                                    break
                    except:
                        pass
                    
                    if text:
                        posts_data.append({
                            "title": text[:400],
                            "url": post_url,
                            "image_url": image_url
                        })
                        logger.info(f"Found post: {text[:80]}...")
                        
                except Exception as e:
                    continue
                    
        except Exception as e:
            logger.warning(f"Strategy 1 failed: {e}")
        
        # Strategy 2: Look for feed stories
        if not posts_data:
            try:
                stories = driver.find_elements(By.CSS_SELECTOR, 'div[data-pagelet*="Feed"]')
                logger.info(f"Found {len(stories)} feed stories")
                
                for story in stories[:3]:
                    text = story.text.strip()
                    if len(text) > 50:
                        posts_data.append({
                            "title": text[:400],
                            "url": url,
                            "image_url": ""
                        })
                        break
            except:
                pass
        
        # Strategy 3: Fallback - get any meaningful text
        if not posts_data:
            try:
                body = driver.find_element(By.TAG_NAME, 'body')
                all_text = body.text
                lines = [line.strip() for line in all_text.split('\n') 
                        if len(line.strip()) > 30]
                
                for line in lines:
                    # Skip UI text
                    if not any(x in line.lower() for x in 
                              ['log in', 'sign up', 'password', 'email', 'create']):
                        posts_data.append({
                            "title": line[:400],
                            "url": url,
                            "image_url": ""
                        })
                        break
            except:
                pass
        
        # Prepare result in your expected format
        if posts_data:
            result = {
                "success": True,
                "saved": True,
                "post": posts_data[0],
                "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
            }
            logger.info("Successfully scraped Facebook posts")
        else:
            result = {
                "success": False,
                "saved": False,
                "post": {
                    "title": "",
                    "url": "",
                    "image_url": ""
                },
                "error": "No posts found on page",
                "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
            }
            logger.warning("No posts found")
            
    except ImportError as e:
        result["error"] = f"Selenium import error: {str(e)}"
        logger.error(f"Import error: {e}")
        
    except Exception as e:
        error_msg = str(e)
        result["error"] = error_msg
        logger.error(f"Scraping error: {error_msg}")
        
    finally:
        if driver:
            try:
                driver.quit()
                logger.info("Chrome driver closed")
            except:
                pass
    
    # Always output valid JSON
    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_facebook_posts()