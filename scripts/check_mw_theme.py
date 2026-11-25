#!/usr/bin/env python3
"""Regression checks for MW branding in both themes on both language pages."""
from __future__ import annotations

import re
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "public"
VERSION = "20261125-mwtheme05"

pages = {
    "index.html": 4,
    "en/index.html": 4,
    "websites.html": 2,
    "en/websites.html": 2,
}
for name, total in pages.items():
    html = (SITE / name).read_text(encoding="utf-8")
    assert f"mw-logo-theme.css?v={VERSION}" in html, f"{name}: missing shared theme CSS"
    assert f"theme.js?v={VERSION}" in html, f"{name}: stale theme script"
    light = re.findall(r'<img[^>]*class="[^"]*\bmw-theme-light\b[^"]*"[^>]*>', html)
    dark = re.findall(r'<img[^>]*class="[^"]*\bmw-theme-dark\b[^"]*"[^>]*>', html)
    assert len(light) == len(dark) == total, (name, len(light), len(dark), total)
    pairs = re.findall(
        r'<img[^>]*class="[^"]*\bmw-theme-light\b[^"]*"[^>]*>\s*'
        r'<img[^>]*class="[^"]*\bmw-theme-dark\b[^"]*"[^>]*>',
        html,
    )
    assert len(pairs) == total, f"{name}: MW variants must be paired in same element"
    for line in light:
        assert f"?v={VERSION}" in line and "-light.png" in line or f"mw-hauptlogo-hell-clean.png?v={VERSION}" in line, (name, line)
    for line in dark:
        assert f"?v={VERSION}" in line and "-dark.png" in line or f"mw-hauptlogo-dunkel-clean.png?v={VERSION}" in line, (name, line)
    if name.endswith("index.html"):
        assert "mw-hauptlogo-hell-clean.png" in html and "mw-hauptlogo-dunkel-clean.png" in html
        assert 'id="mw-cutout-light"' not in html and 'id="mw-cutout-dark"' not in html
        assert 'filter:url("#mw-cutout-' not in html
    print(f"{name}: {total} light/dark MW pairs OK")

css = (SITE / "assets" / "mw-logo-theme.css").read_text(encoding="utf-8")
for selector in (
    'html[data-theme="dark"] img.mw-theme-light',
    'html[data-theme="dark"] img.mw-theme-dark',
    'html[data-theme="light"] img.mw-theme-light',
    'html[data-theme="light"] img.mw-theme-dark',
):
    assert selector in css, f"Missing {selector}"
assert "display:none!important" in css and "display:block!important" in css

assert f"?v={VERSION}" in (SITE / "theme.js").read_text(encoding="utf-8")

for name in ("mw-symbol", "mw-hauptlogo"):
    a = SITE / "logos" / (name + ("-light.png" if name == "mw-symbol" else "-hell-clean.png"))
    b = SITE / "logos" / (name + ("-dark.png" if name == "mw-symbol" else "-dunkel-clean.png"))
    with Image.open(a) as original, Image.open(b) as alternative:
        light = original.convert("RGBA")
        dark = alternative.convert("RGBA")
    expected = (662, 240) if name == "mw-symbol" else (742, 366)
    assert light.size == dark.size == expected, (name, light.size, dark.size)
    assert light.getpixel((0, 0))[3] == dark.getpixel((0, 0))[3] == 0
    assert light.getchannel("A").tobytes() == dark.getchannel("A").tobytes(), (
        name, "The two variants must share the exact same silhouette"
    )
    # Dark mode must use noticeably brighter silver, light mode the darker metal.
    kept = [(p, q) for p, q in zip(light.getdata(), dark.getdata()) if p[3] > 220]
    light_mean = sum(sum(p[:3]) for p, _ in kept) / (3 * len(kept))
    dark_mean = sum(sum(q[:3]) for _, q in kept) / (3 * len(kept))
    assert dark_mean >= light_mean + 22, (name, light_mean, dark_mean)
    print(f"{name}: original transparency, equal geometry and tone switch OK")

for mode in ("light", "dark"):
    icon = SITE / "logos" / f"mw-favicon-{mode}.png"
    with Image.open(icon) as f:
        im = f.convert("RGBA")
        assert im.size == (256, 256) and im.getpixel((0, 0))[3] == 0

print("MW theme consistency validated for all four page variants.")
