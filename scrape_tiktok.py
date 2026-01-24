#!/usr/bin/env python3
import sys, json, io, time, os
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException, WebDriverException

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def clean_url(url):
    if url.startswith('//'):
        return 'https:' + url
    if not url.startswith('http'):
        return 'https://www.tiktok.com' + url
    return url

def create_driver():
    chrome_options = Options()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--disable-dev-tools")
    chrome_options.add_argument("--disable-extensions")
    chrome_options.add_argument("--disable-plugins")
    chrome_options.add_argument("--disable-images")
    chrome_options.add_argument("--disable-javascript")
    chrome_options.add_argument("--window-size=1366,768")
    chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36")
    chrome_options.add_argument("--disable-blink-features=AutomationControlled")
    chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
    chrome_options.add_experimental_option('useAutomationExtension', False)
    chrome_options.page_load_strategy = 'eager'
    
    chrome_options.add_argument("--memory-pressure-off")
    chrome_options.add_argument("--max_old_space_size=4096")
    chrome_options.add_argument("--remote-debugging-port=9222")
    
    return webdriver.Chrome(options=chrome_options)

def main():
    username = sys.argv[1] if len(sys.argv) > 1 else "pannonegyetem"
    
    start_time = time.time()
    max_runtime = 30
    
    videos = []
    
    for attempt in range(3):
        driver = None
        try:
            print(f"Attempt {attempt + 1}/3", flush=True)
            driver = create_driver()
            
            driver.set_page_load_timeout(15)
            driver.implicitly_wait(5)
            
            print("Loading TikTok page...", flush=True)
            driver.get(f"https://www.tiktok.com/@{username}")
            
            if time.time() - start_time > max_runtime:
                print("Runtime limit exceeded", flush=True)
                break
            
            wait = WebDriverWait(driver, 10)
            links = []
            
            try:
                selectors = [
                    'a[href*="/video/"]',
                    '[data-e2e="user-post-item"] a',
                    'a[href*="@"][href*="/video/"]'
                ]
                
                for selector in selectors:
                    try:
                        elements = driver.find_elements(By.CSS_SELECTOR, selector)
                        if elements:
                            links = elements[:2]
                            break
                    except:
                        continue
                        
                if not links:
                    driver.execute_script("window.scrollBy(0, 500);")
                    time.sleep(2)
                    links = driver.find_elements(By.CSS_SELECTOR, 'a[href*="/video/"]')[:2]
                    
            except TimeoutException:
                print("Timeout waiting for videos", flush=True)
                pass
            
            for i, link in enumerate(links[:2]):
                if time.time() - start_time > max_runtime:
                    break
                    
                try:
                    href = link.get_attribute("href")
                    if not href or "/video/" not in href:
                        continue
                        
                    title = ""
                    thumb = ""
                    
                    try:
                        img = link.find_element(By.TAG_NAME, "img")
                        title = (img.get_attribute("alt") or 
                               img.get_attribute("aria-label") or "")[:200]
                        thumb = img.get_attribute("src") or ""
                    except:
                        try:
                            title_elem = link.find_element(By.CSS_SELECTOR, "span, div[title]")
                            title = title_elem.text[:200]
                        except:
                            pass
                            
                    videos.append({
                        "title": title,
                        "url": clean_url(href),
                        "image_url": thumb
                    })
                    
                except Exception as e:
                    print(f"Error processing video {i}: {str(e)}", flush=True)
                    continue
            
            print(f"Found {len(videos)} videos", flush=True)
            break
            
        except WebDriverException as e:
            print(f"WebDriver error (attempt {attempt + 1}): {str(e)}", flush=True)
        except Exception as e:
            print(f"Error (attempt {attempt + 1}): {str(e)}", flush=True)
        finally:
            if driver:
                try:
                    driver.quit()
                except:
                    pass
        
        time.sleep(2)
    
    print(json.dumps(videos, ensure_ascii=False, indent=2))
    sys.stdout.flush()

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("[]", flush=True)
    except Exception as e:
        print("[]", flush=True)
        print(f"Critical error: {str(e)}", file=sys.stderr, flush=True)