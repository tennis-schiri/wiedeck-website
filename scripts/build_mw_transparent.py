"""Build transparent Michael-Wiedeck marks from the approved original monogram.

Requires Pillow at deploy time only. No runtime external dependencies.
The original artwork is preserved in branding/mw-monogram-original.png.
"""
from pathlib import Path
from PIL import Image, ImageFilter, ImageOps

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'branding' / 'mw-monogram-original.png'
OUTPUT = ROOT / 'public' / 'logos'
OUTPUT.mkdir(parents=True, exist_ok=True)

# Precisely remove the nearly white matte; preserve the MW silhouette.
original = Image.open(SOURCE).convert('RGB')
assert original.size == (379, 155), 'Unexpected MW monogram source'
art = original.crop((2, 8, 327, 131))
gray = ImageOps.grayscale(art)
alpha = gray.point(lambda p: 255 if p < 243 else 0)
alpha = alpha.filter(ImageFilter.MedianFilter(3)).filter(ImageFilter.GaussianBlur(0.55))

light = art.convert('RGBA')
light.putalpha(alpha)

# Brighten the original silver gradients on dark backgrounds, with no tile.
levels = [min(255, round(145 + 104 * (p / 255) ** .85)) for p in range(256)]
base = gray.point(levels)
channels = [base.point(lambda x, offset=o: min(255, x + offset)) for o in (4, 9, 14)]
dark = Image.merge('RGBA', (*channels, alpha))

for mode, mark in [('light', light), ('dark', dark)]:
    symbol = mark.resize((mark.width * 2, mark.height * 2), Image.Resampling.LANCZOS)
    symbol.save(OUTPUT / f'mw-symbol-{mode}.png', optimize=True)

    favicon = Image.new('RGBA', (256, 256), (0, 0, 0, 0))
    w = 244
    h = round(symbol.height * (w / symbol.width))
    icon = symbol.resize((w, h), Image.Resampling.LANCZOS)
    favicon.alpha_composite(icon, ((256 - w) // 2, (256 - h) // 2))
    favicon.save(OUTPUT / f'mw-favicon-{mode}.png', optimize=True)

    for output in (symbol, favicon):
        assert output.getpixel((0, 0))[3] == 0, 'Logo must have a transparent background'

print('Built transparent MW header and favicon assets (light/dark).')
