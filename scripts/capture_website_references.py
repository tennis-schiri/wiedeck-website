#!/usr/bin/env python3
"""Capture genuine public homepage screenshots for wiedeck.de's website references.

Runs in GitHub Actions after the HTML upload. No third-party screenshot provider,
API key, tracking service or chargeable AI service is used.
"""
from __future__ import annotations

import os
from pathlib import Path
import shutil
import subprocess
import tempfile
from PIL import Image, ImageDraw, ImageFont, ImageStat

TARGETS = {
    "wiedeck": ["https://www.wiedeck.de/", "https://wiedeck.de/"],
    "tennis-schiri": ["https://www.tennis-schiri.de/", "https://tennis-schiri.de/"],
    "tennilo": ["https://tennilo.de/", "https://www.tennilo.de/"],
    "juxilo": ["https://juxilo.de/", "https://www.juxilo.de/"],
    "due-fratelli": [
        "https://due-fratelli-neuenhagen.de/",
        "https://www.due-fratelli-neuenhagen.de/",
    ],
}
DEST = Path("public/media/websites")
WIDTH, HEIGHT = 1120, 700


def find_browser() -> str | None:
    for candidate in (
        os.environ.get("CHROME_BIN", ""),
        "google-chrome-stable", "google-chrome", "chromium", "chromium-browser",
        "/usr/bin/google-chrome", "/opt/google/chrome/chrome",
    ):
        if candidate and (resolved := shutil.which(candidate)):
            return resolved
    return None


def screenshot(browser: str, url: str, destination: Path) -> bool:
    with tempfile.TemporaryDirectory(prefix="mw-website-shot-") as temp:
        png = Path(temp) / "shot.png"
        profile = Path(temp) / "chrome-profile"
        cmd = [
            browser, "--headless", "--no-sandbox", "--disable-gpu",
            "--disable-dev-shm-usage", "--disable-extensions",
            "--disable-background-networking", "--no-first-run",
            "--hide-scrollbars", "--force-device-scale-factor=1",
            "--window-size=1280,800", "--virtual-time-budget=5500",
            f"--user-data-dir={profile}", f"--screenshot={png}", url,
        ]
        try:
            result = subprocess.run(cmd, stdout=subprocess.DEVNULL,
                                    stderr=subprocess.PIPE, timeout=45, check=False)
            if result.returncode != 0 or not png.is_file() or png.stat().st_size < 2000:
                print(f"  Browser failed for {url} (code {result.returncode})")
                return False
            with Image.open(png) as captured:
                image = captured.convert("RGB")
                if image.width < 400 or image.height < 300:
                    print(f"  Too small screenshot for {url}: {image.size}")
                    return False
                # Fill a consistent 16:10 browser preview without distorting the page.
                desired_ratio = WIDTH / HEIGHT
                current_ratio = image.width / image.height
                if current_ratio > desired_ratio:
                    crop_width = round(image.height * desired_ratio)
                    left = (image.width - crop_width) // 2
                    image = image.crop((left, 0, left + crop_width, image.height))
                elif current_ratio < desired_ratio:
                    crop_height = round(image.width / desired_ratio)
                    image = image.crop((0, 0, image.width, crop_height))
                image = image.resize((WIDTH, HEIGHT), Image.Resampling.LANCZOS)
                # Blank captures or pure browser error pages are not valid references.
                if sum(ImageStat.Stat(image.resize((24, 15))).stddev) < 10:
                    print(f"  Screenshot appears blank for {url}")
                    return False
                image.save(destination, format="WEBP", quality=83, method=5)
                print(f"  Captured {url} -> {destination} ({destination.stat().st_size} bytes)")
                return True
        except (OSError, subprocess.TimeoutExpired, ValueError) as error:
            print(f"  Unable to capture {url}: {error}")
        return False


def unavailable(slug: str, destination: Path) -> None:
    """Explicit unavailable preview. Never imitate a screenshot."""
    image = Image.new("RGB", (WIDTH, HEIGHT), "#1a2934")
    draw = ImageDraw.Draw(image)
    font_path = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
    large = ImageFont.truetype(font_path, 37) if Path(font_path).exists() else ImageFont.load_default()
    small = ImageFont.truetype(font_path, 19) if Path(font_path).exists() else ImageFont.load_default()
    draw.rounded_rectangle((50, 50, WIDTH - 50, HEIGHT - 50), radius=25,
                           outline="#516675", width=2)
    draw.text((90, 245), slug.upper().replace("-", " / "), font=small, fill="#b5c0c7")
    draw.text((90, 304), "Screenshot derzeit", font=large, fill="#f2f6f8")
    draw.text((90, 358), "nicht verfügbar", font=large, fill="#f2f6f8")
    draw.text((90, 465), "Live-Website über den Link öffnen", font=small, fill="#b5c0c7")
    image.save(destination, format="WEBP", quality=83, method=4)
    print(f"  Screenshot unavailable: wrote clearly labelled fallback for {slug}")


def main() -> None:
    DEST.mkdir(parents=True, exist_ok=True)
    browser = find_browser()
    if not browser:
        print("No headless browser available on runner.")
    for slug, urls in TARGETS.items():
        target = DEST / f"{slug}.webp"
        if not browser or not any(screenshot(browser, url, target) for url in urls):
            unavailable(slug, target)
    assert all((DEST / f"{slug}.webp").stat().st_size > 1000 for slug in TARGETS)


if __name__ == "__main__":
    main()
