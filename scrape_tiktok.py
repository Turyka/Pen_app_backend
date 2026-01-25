#!/usr/bin/env python3
"""
Low-resource TikTok scraper (CAPTCHA-safe)
"""

import sys, json, time, io, os
from urllib.parse import urljoin
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def scrape_tiktok_posts():
    url = "https://www.tiktok.com/@pannonegyetem"

    try:
        chrome_options = Options()
        chrome_options.add_argument("--headless=new")
        chrome_options.add_argument("--no-sandbox")
        chrome_options.add_argument("--disable-dev-shm-usage")
        chrome_options.add_argument("--disable-extensions")
        chrome_options.add_argument("--disable-background-networking")
        chrome_options.add_argument("--disable-sync")
        chrome_options.add_argument("--disable-default-apps")
        chrome_options.add_argument("--disable-component-update")
        chrome_options.add_argument("--disable-hang-monitor")
        chrome_options.add_argument("--disable-ipc-flooding-protection")
        chrome_options.add_argument("--disable-renderer-backgrounding")
        chrome_options.add_argument("--disable-background-timer-throttling")
        chrome_options.add_argument("--disable-backgrounding-occluded-windows")
        chrome_options.add_argument("--blink-settings=imagesEnabled=false")
        chrome_options.add_argument("--disable-blink-features=AutomationControlled")
        chrome_options.add_experimental_option("excludeSwitches", ["enable-automation"])
        chrome_options.add_experimental_option("useAutomationExtension", False)
        chrome_options.add_argument("--window-size=1280,720")
        chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64)")

        service = Service('/usr/bin/chromedriver') if os.path.exists('/usr/bin/chromedriver') else None
        driver = webdriver.Chrome(service=service, options=chrome_options)
        driver.execute_script("Object.defineProperty(navigator, 'webdriver', {get: () => undefined})")

        driver.get(url)

        # 👇 REQUIRED for TikTok hydration
        time.sleep(4)
        driver.execute_script("window.scrollTo(0, 300);")
        time.sleep(1)

        # 👇 SINGLE DOM query only
        link = driver.find_element(By.CSS_SELECTOR, '[data-e2e="user-post-item"] a[href*="/video/"]')
        href = link.get_attribute("href")

        # Title
        title = ""
        for el in link.find_elements(By.CSS_SELECTOR, 'img[alt], img[aria-label], span'):
            title = (el.get_attribute("alt") or el.get_attribute("aria-label") or el.text or "").strip()
            if len(title) > 5:
                break

        # Image URL (attribute only)
        image_url = ""
        for img in link.find_elements(By.TAG_NAME, "img"):
            src = img.get_attribute("src") or img.get_attribute("data-src")
            if src and ("tiktokcdn" in src or "p16-sign" in src):
                image_url = src
                break

        driver.quit()

        result = {
            "latest_1": {
                "title": title[:500],
                "url": href.split("?")[0],
                "image_url": image_url
            },
            "source_url": url,
            "status": "success",
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    except Exception as e:
        result = {
            "latest_1": {"title": "", "url": "", "image_url": ""},
            "source_url": url,
            "status": "error",
            "error": str(e),
            "timestamp": time.strftime("%Y-%m-%d %H:%M:%S")
        }

    print(json.dumps(result, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    scrape_tiktok_posts()
