"""Render the approved MW monogram with a transparent background.

The original metallic gradients are part of the artwork and must never be
keyed out. Detect only near-white pixels connected to the surrounding matte;
preserve isolated near-white specular reflections inside the ribbons.

Requires Pillow at deploy time; no runtime dependencies or image-generation API.
"""
from collections import deque
from pathlib import Path

from PIL import Image, ImageFilter, ImageOps

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / 'branding' / 'mw-monogram-original.png'
OUTPUT = ROOT / 'public' / 'logos'
OUTPUT.mkdir(parents=True, exist_ok=True)

original = Image.open(SOURCE).convert('RGB')
assert original.size == (379, 155), 'Unexpected original MW artwork size'
art = original.crop((2, 8, 327, 131))
gray = ImageOps.grayscale(art)
width, height = art.size
pixels = gray.load()

# The background is almost pure white. Unlike a global brightness key,
# flood-filling from the borders keeps shiny silver areas enclosed by the
# monogram fully opaque – even if the highlights themselves approach white.
near_white = 250
background = bytearray(width * height)
queue = deque()


def visit(x: int, y: int) -> None:
    index = y * width + x
    if not background[index] and pixels[x, y] >= near_white:
        background[index] = 1
        queue.append((x, y))


for x in range(width):
    visit(x, 0)
    visit(x, height - 1)
for y in range(height):
    visit(0, y)
    visit(width - 1, y)

while queue:
    x, y = queue.popleft()
    if x > 0:
        visit(x - 1, y)
    if x + 1 < width:
        visit(x + 1, y)
    if y > 0:
        visit(x, y - 1)
    if y + 1 < height:
        visit(x, y + 1)

alpha = Image.new('L', (width, height), 0)
out_alpha = alpha.load()
for y in range(height):
    for x in range(width):
        if background[y * width + x]:
            # Connected matte pixels are background, *not* semi-transparent
            # grey. The old (255 - brightness) * 40 formula left a visible
            # halo and even made near-white corner pixels opaque.
            out_alpha[x, y] = 0
        else:
            out_alpha[x, y] = 255

# Feather only the silhouette boundary. Keep the outer one-pixel matte
# completely clear after the blur (the artwork has intentional padding).
alpha = alpha.filter(ImageFilter.GaussianBlur(0.35))
for x in range(width):
    alpha.putpixel((x, 0), 0)
    alpha.putpixel((x, height - 1), 0)
for y in range(height):
    alpha.putpixel((0, y), 0)
    alpha.putpixel((width - 1, y), 0)

# Light backgrounds: keep the untouched original metal colours.
light = art.convert('RGBA')
light.putalpha(alpha)

# Dark backgrounds: lift only the dark folds while retaining the entire
# light-to-dark metal range. The old map (starting at 145) flattened the
# shading and, with the old alpha threshold, caused jagged white patches.
levels = [min(255, round(62 + 193 * (p / 255) ** 0.92)) for p in range(256)]
tone = gray.point(levels)
channels = [tone.point(lambda x, offset=o: min(255, x + offset)) for o in (0, 3, 7)]
dark = Image.merge('RGBA', (*channels, alpha))

for mode, mark in [('light', light), ('dark', dark)]:
    symbol = mark.resize((width * 2, height * 2), Image.Resampling.LANCZOS)
    symbol.save(OUTPUT / f'mw-symbol-{mode}.png', optimize=True)

    favicon = Image.new('RGBA', (256, 256), (0, 0, 0, 0))
    favicon_width = 244
    favicon_height = round(symbol.height * favicon_width / symbol.width)
    icon = symbol.resize((favicon_width, favicon_height), Image.Resampling.LANCZOS)
    favicon.alpha_composite(icon, ((256 - favicon_width) // 2, (256 - favicon_height) // 2))
    favicon.save(OUTPUT / f'mw-favicon-{mode}.png', optimize=True)

    assert symbol.getpixel((0, 0))[3] == 0, 'Symbol must be transparent'
    assert favicon.getpixel((0, 0))[3] == 0, 'Favicon must be transparent'

print('Built corrected MW monograms with protected silver highlights (light/dark).')
