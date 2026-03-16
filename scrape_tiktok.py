#!/usr/bin/env python3
"""
TikTok Scraper - Playwright version (514MB Render.com SAFE)
"""

import sys
import json
import time
import io
import asyncio
import requests
from playwright.async_api import async_playwright

sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

# ---------------- CONFIG ----------------
LARAVEL_API_URL = "https://pen-app-backend.onrender.com/api/tiktok-keres"
API_KEY = "dQw4w9WgXcQ"
TIKTOK_USERNAME = "pannonegyetem"
TIKTOK_URL = f"https://www.tiktok.com/@{TIKTOK_USERNAME}"
# ---------------------------------------


def send_to_laravel(post):
    """Send one TikTok post to Laravel API with duplicate + image-update logic"""

    if not post.get("title") or not post.get("url") or not post.get("image_url"):
        print(f"DEBUG: Skipping post with empty fields: {post}")
        return {"status_code": None, "action": "skipped_empty_fields"}

    headers = {
        "Content-Type": "application/json",
        "X-API-KEY": API_KEY,
    }

    try:
        # Check if URL already exists
        check = requests.get(
            LARAVEL_API_URL,
            headers=headers,
            params={"url": post["url"]},
            timeout=10,
        )

        existing = None
        if check.status_code == 200:
            try:
                data = check.json()
                if data.get("exists"):
                    existing = data
            except Exception:
                pass

        if existing:
            if existing.get("image_url") != post["image_url"]:
                print(f"DEBUG: URL exists but image changed, updating id={existing.get('id')}")
                patch = requests.patch(
                    f"{LARAVEL_API_URL}/{existing.get('id')}",
                    headers=headers,
                    json={"image_url": post["image_url"]},
                    timeout=10,
                )
                try:
                    body = patch.json()
                except Exception:
                    body = patch.text or "(empty response)"
                return {"status_code": patch.status_code, "response": body, "action": "image_updated"}
            else:
                print("DEBUG: Duplicate (URL + image same), skipping.")
                return {"status_code": None, "action": "skipped_duplicate"}

        # New post
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

        try:
            body = response.json()
        except Exception:
            body = response.text or "(empty response)"

        print(f"DEBUG: Raw response [{response.status_code}]: '{str(body)[:200]}'")
        return {"status_code": response.status_code, "response": body, "action": "created"}

    except Exception as e:
        return {"status_code": None, "error": str(e)}


async def scrape_tiktok_posts():
    posts = []

    async with async_playwright() as p:
        browser = await p.chromium.launch(
            headless=True,
            args=[
                "--no-sandbox",
                "--disable-dev-shm-usage",
                "--disable-gpu",
                "--disable-blink-features=AutomationControlled",
            ]
        )

        context = await browser.new_context(
            viewport={"width": 390, "height": 844},  # mobile = lighter
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
            locale="en-US",
            timezone_id="America/New_York",
            extra_http_headers={"Accept-Language": "en-US,en;q=0.9"},
        )

        # Anti-detection
        await context.add_init_script("""
            Object.defineProperty(navigator, 'webdriver', { get: () => undefined });
            Object.defineProperty(navigator, 'plugins', { get: () => [1, 2, 3, 4, 5] });
            Object.defineProperty(navigator, 'languages', { get: () => ['en-US', 'en'] });
            window.chrome = { runtime: {}, loadTimes: function() {}, csi: function() {}, app: {} };
        """)

        page = await context.new_page()

        # Intercept item_list API responses
        captured = []

        async def handle_response(response):
            url = response.url
            if "item_list" in url and "post" in url:
                print(f"DEBUG: Intercepted: {url[:120]}")
                try:
                    body = await response.json()
                    items = body.get("itemList") or body.get("items") or []
                    if items:
                        print(f"DEBUG: Got {len(items)} items from interception!")
                        captured.extend(items)
                except Exception:
                    pass

        page.on("response", handle_response)

        try:
            print(f"DEBUG: Loading {TIKTOK_URL}...")
            await page.goto(TIKTOK_URL, wait_until="domcontentloaded", timeout=30000)
            await asyncio.sleep(8)

            # Scroll to trigger video list API calls
            for scroll_pos in [400, 800, 1200, 1600, 2000]:
                await page.evaluate(f"window.scrollTo(0, {scroll_pos})")
                await asyncio.sleep(2)

            # Scroll back up
            await page.evaluate("window.scrollTo(0, 0)")
            await asyncio.sleep(2)

            print(f"DEBUG: Intercepted {len(captured)} raw items after scrolling")

            # ── Path A: use intercepted API items ──────────────────────────────
            if captured:
                seen_ids = set()
                for item in captured:
                    vid_id = str(item.get("id", "")).strip()
                    if not vid_id or vid_id in seen_ids:
                        continue
                    seen_ids.add(vid_id)

                    title = (item.get("desc") or "").strip()
                    video_data = item.get("video", {})
                    image_url = (
                        video_data.get("cover") or
                        video_data.get("originCover") or
                        ""
                    ).strip()

                    if not title or not image_url:
                        print(f"DEBUG: Skipping item missing fields: id={vid_id}")
                        continue

                    posts.append({
                        "title": title[:500],
                        "url": f"https://www.tiktok.com/@{TIKTOK_USERNAME}/video/{vid_id}",
                        "image_url": image_url,
                    })

                    if len(posts) >= 5:
                        break

            # ── Path B: DOM scraping fallback ──────────────────────────────────
            if not posts:
                print("DEBUG: API interception empty, falling back to DOM scraping...")

                selectors = [
                    'a[href*="/video/"]',
                    'a[data-e2e="user-post-item"]',
                    'div[data-e2e="user-post-item"] a',
                    '[data-e2e="user-post-item-list"] a',
                    '.video-feed-item a[href*="/video/"]',
                ]

                video_links = []
                for selector in selectors:
                    try:
                        elements = await page.query_selector_all(selector)
                        print(f"DEBUG: Selector '{selector}' → {len(elements)} elements")
                        if elements:
                            seen_hrefs = set()
                            for elem in elements[:10]:
                                href = await elem.get_attribute("href")
                                if href and "/video/" in href and href not in seen_hrefs:
                                    seen_hrefs.add(href)
                                    video_links.append(elem)
                            if video_links:
                                print(f"DEBUG: Using {len(video_links)} links from '{selector}'")
                                break
                    except Exception as e:
                        print(f"DEBUG: Selector error '{selector}': {e}")
                        continue

                print(f"DEBUG: Processing {len(video_links[:5])} DOM video links")

                for i, link in enumerate(video_links[:5]):
                    try:
                        href = await link.get_attribute("href")
                        post_url = href.split("?")[0] if href else ""
                        if not post_url or "/video/" not in post_url:
                            continue

                        # Title: try img alt, aria-label, text
                        title = ""
                        title_selectors = ["img[alt]", "img[aria-label]", "span", ".Caption"]
                        for sel in title_selectors:
                            try:
                                elems = await link.query_selector_all(sel)
                                for elem in elems:
                                    candidate = (
                                        await elem.get_attribute("alt") or
                                        await elem.get_attribute("aria-label") or
                                        await elem.get_attribute("title") or
                                        await elem.inner_text()
                                    )
                                    if candidate and len(candidate.strip()) > 5:
                                        title = candidate.strip()[:500]
                                        print(f"DEBUG: Title from '{sel}': {title[:50]}")
                                        break
                                if title:
                                    break
                            except Exception:
                                continue

                        if not title:
                            title = f"TikTok Video {i + 1}"
                            print(f"DEBUG: Fallback title for video {i + 1}")

                        # Thumbnail
                        image_url = ""
                        img_selectors = ["img[src*='tiktokcdn']", "img[src*='p16-sign']", "img"]
                        for img_sel in img_selectors:
                            try:
                                imgs = await link.query_selector_all(img_sel)
                                for img in imgs:
                                    src = await img.get_attribute("src")
                                    if src and ("tiktokcdn" in src or "p16-sign" in src or "p19-" in src):
                                        image_url = src.strip()
                                        print(f"DEBUG: Image: {image_url[:80]}...")
                                        break
                                if image_url:
                                    break
                            except Exception:
                                continue

                        if not image_url:
                            print(f"DEBUG: No image found for video {i + 1}, skipping.")
                            continue

                        posts.append({
                            "title": title,
                            "url": post_url,
                            "image_url": image_url,
                        })

                    except Exception as e:
                        print(f"DEBUG: DOM error on video {i + 1}: {e}")
                        continue

            # ── Path C: in-browser fetch() last resort ─────────────────────────
            if not posts:
                print("DEBUG: Trying in-browser fetch()...")

                universal_content = await page.evaluate("""
                    () => {
                        const el = document.getElementById('__UNIVERSAL_DATA_FOR_REHYDRATION__');
                        return el ? el.textContent : null;
                    }
                """)

                sec_uid = None
                user_id = None
                if universal_content:
                    try:
                        data = json.loads(universal_content)
                        user_info = (
                            data.get("__DEFAULT_SCOPE__", {})
                                .get("webapp.user-detail", {})
                                .get("userInfo", {})
                                .get("user", {})
                        )
                        sec_uid = user_info.get("secUid", "")
                        user_id = user_info.get("id", "")
                        print(f"DEBUG: sec_uid={sec_uid[:30]}... user_id={user_id}")
                    except Exception as e:
                        print(f"DEBUG: Failed to parse UNIVERSAL_DATA: {e}")

                if sec_uid:
                    api_result = await page.evaluate(f"""
                        async () => {{
                            try {{
                                const url = '/api/post/item_list/?aid=1988&secUid={sec_uid}&count=5&cursor=0&userId={user_id}&sourceType=8&appId=1233';
                                const resp = await fetch(url, {{
                                    method: 'GET',
                                    credentials: 'include',
                                    headers: {{
                                        'Accept': 'application/json',
                                        'Referer': 'https://www.tiktok.com/@{TIKTOK_USERNAME}',
                                    }}
                                }});
                                const text = await resp.text();
                                return {{ status: resp.status, body: text }};
                            }} catch(e) {{
                                return {{ error: e.toString() }};
                            }}
                        }}
                    """)

                    print(f"DEBUG: In-browser fetch status: {api_result.get('status')}")
                    body_text = api_result.get("body", "")
                    print(f"DEBUG: Response preview: {body_text[:300]}")

                    if body_text:
                        try:
                            api_data = json.loads(body_text)
                            item_list = api_data.get("itemList") or api_data.get("items") or []
                            print(f"DEBUG: itemList from in-browser fetch: {len(item_list)}")

                            for item in item_list[:5]:
                                vid_id = str(item.get("id", "")).strip()
                                title = (item.get("desc") or "").strip()
                                video_data = item.get("video", {})
                                image_url = (
                                    video_data.get("cover") or
                                    video_data.get("originCover") or
                                    ""
                                ).strip()

                                if not vid_id or not title or not image_url:
                                    print(f"DEBUG: Skipping fallback item missing fields: id={vid_id}")
                                    continue

                                posts.append({
                                    "title": title[:500],
                                    "url": f"https://www.tiktok.com/@{TIKTOK_USERNAME}/video/{vid_id}",
                                    "image_url": image_url,
                                })
                        except Exception as e:
                            print(f"DEBUG: JSON parse error: {e}")

        except Exception as e:
            print(f"DEBUG: Scraper error: {e}")

        await browser.close()

    return posts[:5]


async def main():
    posts = await scrape_tiktok_posts()
    print(f"DEBUG: Total posts found: {len(posts)}")

    for i, post in enumerate(posts):
        print(f"DEBUG: Sending post {i + 1}: {post['title'][:60]}")
        result = send_to_laravel(post)
        print(f"DEBUG: Laravel result: {result}")
        time.sleep(1)

    output = {
        f"latest_{i + 1}": posts[i] if i < len(posts) else {"title": "", "url": "", "image_url": ""}
        for i in range(5)
    }
    output.update({
        "source_url": TIKTOK_URL,
        "status": "success" if posts else "no_videos",
        "found_posts": len(posts),
        "timestamp": time.strftime("%Y-%m-%d %H:%M:%S"),
    })

    print(json.dumps(output, ensure_ascii=False))
    sys.stdout.flush()


if __name__ == "__main__":
    asyncio.run(main())
