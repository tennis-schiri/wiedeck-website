"""Build MW website artwork for both colour themes from approved transparent originals.

No SVG browser colour-key, background substitution or brightness-based alpha key.
All generated assets keep their original alpha silhouettes, with a one-pixel
inward matte cleanup to avoid pale fringes on dark backgrounds.
"""
from pathlib import Path

from PIL import Image, ImageDraw, ImageFilter, ImageOps

ROOT = Path(__file__).resolve().parents[1]
BRANDING = ROOT / "branding"
OUTPUT = ROOT / "public" / "logos"
OUTPUT.mkdir(parents=True, exist_ok=True)


def cleaned_alpha(image: Image.Image, edge_floor: int) -> Image.Image:
    alpha = image.getchannel("A").filter(ImageFilter.MinFilter(3))
    alpha = alpha.filter(ImageFilter.GaussianBlur(0.4))
    return alpha.point(lambda value: max(0, min(255, round((value - edge_floor) * 255 / (255 - edge_floor)))))


def metal_tone(source: Image.Image, alpha: Image.Image) -> Image.Image:
    metal = ImageOps.grayscale(source)
    levels = [min(255, round(90 + 165 * (v / 255) ** 0.72)) for v in range(256)]
    tone = metal.point(levels)
    channels = [tone.point(lambda v, extra=k: min(255, v + extra)) for k in (0, 3, 6)]
    return Image.merge("RGBA", (*channels, alpha))


def suppress_bright_edges(image: Image.Image) -> Image.Image:
    """Avoid bright single-pixel halos on dark backgrounds, without changing alpha.

    The original artwork has very bright metallic highlights. These belong
    inside opaque areas, not on the semi-transparent outside edge.
    """
    red, green, blue, alpha = image.split()
    fringe_mask = alpha.point(lambda value: 255 if 0 < value < 210 else 0)
    channels = tuple(
        Image.composite(channel.point(lambda value: min(value, 234)), channel, fringe_mask)
        for channel in (red, green, blue)
    )
    return Image.merge("RGBA", (*channels, alpha))


def save_logo(image: Image.Image, name: str) -> None:
    assert image.mode == "RGBA" and image.getpixel((0, 0))[3] == 0, f"Opaque corner: {name}"
    image.save(OUTPUT / name, optimize=True)


# The original isolated MW symbol is transparent. It is shared by the
# header, the personal 'Warum nicht?' intro, project 02 and the webdesign page.
with Image.open(BRANDING / "mw-monogram-transparent.png") as original:
    symbol_source = original.convert("RGBA")
assert symbol_source.size == (331, 120), "Unexpected MW symbol source"
assert symbol_source.getpixel((0, 0))[3] == 0, "MW symbol source must be transparent"
symbol_alpha = cleaned_alpha(symbol_source, edge_floor=85)

for mode in ("light", "dark"):
    symbol = symbol_source.copy() if mode == "light" else metal_tone(symbol_source, symbol_alpha)
    symbol.putalpha(symbol_alpha)
    symbol = symbol.resize((662, 240), Image.Resampling.LANCZOS)
    if mode == "dark":
        symbol = suppress_bright_edges(symbol)
    save_logo(symbol, f"mw-symbol-{mode}.png")

    favicon = Image.new("RGBA", (256, 256), (0, 0, 0, 0))
    icon_width = 244
    icon_height = round(symbol.height * icon_width / symbol.width)
    resized = symbol.resize((icon_width, icon_height), Image.Resampling.LANCZOS)
    favicon.alpha_composite(resized, ((256 - icon_width) // 2, (256 - icon_height) // 2))
    save_logo(favicon, f"mw-favicon-{mode}.png")

# The large logo additionally contains the MICHAEL WIEDECK wordmark.
# Its source is truly transparent, whereas the previously named 'dark
# transparent' Drive file has opaque white corners and a baked-in matte.
# Derive BOTH versions from the same clean silhouette to guarantee
# consistent geometry and no rectangular background in either theme.
with Image.open(BRANDING / "mw-hauptlogo-hell-transparent.png") as original:
    hero_source = original.convert("RGBA")
assert hero_source.size == (742, 366), "Unexpected MW wordmark source"
assert hero_source.getpixel((0, 0))[3] == 0, "MW wordmark must be transparent"
hero_alpha = cleaned_alpha(hero_source, edge_floor=35)
hero_light = hero_source.copy()
hero_light.putalpha(hero_alpha)
save_logo(hero_light, "mw-hauptlogo-hell-clean.png")

hero_dark = metal_tone(hero_source, hero_alpha)
# The wordmark and fine underline are black in the light artwork; make
# these pale silver in dark mode while retaining their *original* alpha.
draw = ImageDraw.Draw(hero_dark)
draw.rectangle((0, 263, hero_dark.width - 1, hero_dark.height - 1), fill=(246, 248, 251, 255))
hero_dark.putalpha(hero_alpha)
hero_dark = suppress_bright_edges(hero_dark)
save_logo(hero_dark, "mw-hauptlogo-dunkel-clean.png")

print("MW theme assets built: transparent header, project, intro, wordmark and favicon (light/dark).")
