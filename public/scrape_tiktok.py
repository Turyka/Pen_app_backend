#!/usr/bin/env python3
import sys, json, time, io
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

def clean_url(url):
    if url.startswith('//'):
        return 'https:' + url
    if not url.startswith('http'):
        return 'https://www.tiktok.com' + url
    return url

def main():
    username = sys.argv[1] if len(sys.argv) > 1 else "pannonegyetem"

    chrome_options = Options()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-blink-features=AutomationControlled")
    chrome_options.add_argument("--window-size=1366,768")
    chrome_options.add_argument("--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64)")

    driver = webdriver.Chrome(options=chrome_options)
    wait = WebDriverWait(driver, 10)

    videos = []

    try:
        driver.get(f"https://www.tiktok.com/@{username}")

        # Wait until at least one video link appears
        wait.until(EC.presence_of_element_located(
            (By.CSS_SELECTOR, 'a[href*="/video/"]')
        ))

        # Minimal scrolling – stop early if enough videos
        for _ in range(2):
            driver.execute_script("window.scrollBy(0, document.body.scrollHeight);")
            time.sleep(0.5)

            links = driver.find_elements(By.CSS_SELECTOR, 'a[href*="/video/"]')
            if len(links) >= 5:
                break

        links = driver.find_elements(By.CSS_SELECTOR, 'a[href*="/video/"]')[:5]

        for link in links:
            href = link.get_attribute("href")
            if not href:
                continue

            title = ""
            thumb = ""

            try:
                img = link.find_element(By.TAG_NAME, "img")
                title = img.get_attribute("alt") or ""
                thumb = img.get_attribute("src") or ""
            except:
                pass

            videos.append({
                "title": title[:200],
                "url": clean_url(href),
                "image_url": thumb
            })

    except Exception:
        pass

    driver.quit()

    print(json.dumps(videos, ensure_ascii=False))
    sys.stdout.flush()

if __name__ == "__main__":
    main()
