#!/usr/bin/env python3
import sys, json, io, time
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.support.ui import WebDriverWait
from selenium.common.exceptions import TimeoutException, WebDriverException

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8")

MAX_ATTEMPTS = 3
MAX_RUNTIME = 60
PAGE_TIMEOUT = 15
IMPLICIT_WAIT = 5

VIDEO_SELECTORS = (
    'a[href*="/video/"]',
    '[data-e2e="user-post-item"] a',
    'a[href*="@"][href*="/video/"]',
)


def clean_url(url: str) -> str:
    if not url:
        return ""
    if url.startswith("//"):
        return "https:" + url
    if not url.startswith("http"):
        return "https://www.tiktok.com" + url
    return url


def create_driver() -> webdriver.Chrome:
    options = Options()

    options.add_argument("--headless=new")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    options.add_argument("--disable-gpu")
    options.add_argument("--disable-dev-tools")
    options.add_argument("--disable-extensions")
    options.add_argument("--disable-plugins")
    options.add_argument("--disable-images")
    options.add_argument("--disable-javascript")
    options.add_argument("--disable-blink-features=AutomationControlled")
    options.add_argument("--window-size=1366,768")
    options.add_argument(
        "--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
    )

    options.add_experimental_option("excludeSwitches", ["enable-automation"])
    options.add_experimental_option("useAutomationExtension", False)
    options.page_load_strategy = "eager"

    options.add_argument("--memory-pressure-off")
    options.add_argument("--max_old_space_size=4096")
    options.add_argument("--remote-debugging-port=9222")

    return webdriver.Chrome(options=options)


def find_video_links(driver):
    for selector in VIDEO_SELECTORS:
        elements = driver.find_elements(By.CSS_SELECTOR, selector)
        if elements:
            return elements[:1]

    # fallback scroll
    driver.execute_script("window.scrollBy(0, 500);")
    time.sleep(2)
    return driver.find_elements(By.CSS_SELECTOR, 'a[href*="/video/"]')[:1]


def extract_video_data(link):
    href = link.get_attribute("href")
    if not href or "/video/" not in href:
        return None

    title = ""
    thumb = ""

    try:
        img = link.find_element(By.TAG_NAME, "img")
        title = (
            img.get_attribute("alt")
            or img.get_attribute("aria-label")
            or ""
        )[:100]
        thumb = img.get_attribute("src") or ""
    except Exception:
        try:
            title_elem = link.find_element(By.CSS_SELECTOR, "span, div[title]")
            title = title_elem.text[:100]
        except Exception:
            pass

    return {
        "title": title,
        "url": clean_url(href),
        "image_url": thumb,
    }


def main():
    username = sys.argv[1] if len(sys.argv) > 1 else "pannonegyetem"
    start_time = time.time()
    videos = []

    for attempt in range(MAX_ATTEMPTS):
        driver = None
        try:
            print(f"Attempt {attempt + 1}/{MAX_ATTEMPTS}", flush=True)
            driver = create_driver()
            driver.set_page_load_timeout(PAGE_TIMEOUT)
            driver.implicitly_wait(IMPLICIT_WAIT)

            driver.get(f"https://www.tiktok.com/@{username}")

            if time.time() - start_time > MAX_RUNTIME:
                break

            links = find_video_links(driver)

            for link in links:
                if time.time() - start_time > MAX_RUNTIME:
                    break

                data = extract_video_data(link)
                if data:
                    videos.append(data)

            print(f"Found {len(videos)} videos", flush=True)
            break

        except WebDriverException as e:
            print(f"WebDriver error (attempt {attempt + 1}): {e}", flush=True)
        except Exception as e:
            print(f"Error (attempt {attempt + 1}): {e}", flush=True)
        finally:
            if driver:
                try:
                    driver.quit()
                except Exception:
                    pass

    print(json.dumps(videos, ensure_ascii=False, indent=2))
    sys.stdout.flush()


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        print("[]", flush=True)
    except Exception as e:
        print("[]", flush=True)
        print(f"Critical error: {e}", file=sys.stderr, flush=True)
