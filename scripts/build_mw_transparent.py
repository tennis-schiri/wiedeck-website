"""Build MW website assets from the owner's already-transparent Drive original.

Never attempt white-background colour keying: it destroys metallic highlights
and creates the frayed, bright fringe visible against dark backgrounds.
"""
from pathlib import Path
from PIL import Image, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "branding" / "mw-monogram-transparent.png"
OUTPUT = ROOT / "public" / "logos"
OUTPUT.mkdir(parents=True, exist_ok=True)

with Image.open(SOURCE) as original:
    image = original.convert("RGBA")
assert image.size == (331, 120), "Unexpected approved MW artwork"
assert image.getpixel((0, 0))[3] == 0, "Source must already be transparent"

# A small inward cleanup removes the original's faint matte and soft shadow
# while keeping all of the actual three-dimensional silver ribbons.
alpha = image.getchannel("A")
alpha = alpha.filter(ImageFilter.MinFilter(3))
alpha = alpha.filter(ImageFilter.GaussianBlur(0.4))
alpha = alpha.point(lambda value: max(0, min(255, round((value - 85) * 255 / 170))))

for mode in ("light", "dark"):
    if mode == "light":
        mark = image.copy()
    else:
        # Mild brightening only for dark surroundings. Preserve original
        # curves and fine metal gradients instead of flattening to one colour.
        r, g, b, _ = image.split()
        lifted = tuple(channel.point(lambda v: min(255, v + 18)) for channel in (r, g, b))
        mark = Image.merge("RGBA", (*lifted, alpha))
    mark.putalpha(alpha)
    symbol = mark.resize((662, 240), Image.Resampling.LANCZOS)
    symbol.save(OUTPUT / f"mw-symbol-{mode}.png", optimize=True)

    favicon = Image.new("RGBA", (256, 256), (0, 0, 0, 0))
    icon_width = 244
    icon_height = round(symbol.height * icon_width / symbol.width)
    icon = symbol.resize((icon_width, icon_height), Image.Resampling.LANCZOS)
    favicon.alpha_composite(icon, ((256 - icon_width) // 2, (256 - icon_height) // 2))
    favicon.save(OUTPUT / f"mw-favicon-{mode}.png", optimize=True)

    assert symbol.getpixel((0, 0))[3] == 0, "MW symbol corner not transparent"
    assert favicon.getpixel((0, 0))[3] == 0, "MW favicon corner not transparent"

print("Approved transparent MW original used; metallic edges cleaned (light/dark).")
