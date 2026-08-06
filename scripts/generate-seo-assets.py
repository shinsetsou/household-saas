"""Generate SEO static assets: favicon, OG image, apple touch icon, manifest icon."""
from PIL import Image, ImageDraw, ImageFont
import os
import json

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PUBLIC = os.path.join(ROOT, 'public')

BRAND_GRADIENT_TOP = (99, 102, 241)
BRAND_GRADIENT_BOTTOM = (124, 58, 237)
WHITE = (255, 255, 255)


def gradient_background(size):
    img = Image.new('RGB', (size, size), BRAND_GRADIENT_TOP)
    draw = ImageDraw.Draw(img)
    for y in range(size):
        t = y / max(size - 1, 1)
        r = int(BRAND_GRADIENT_TOP[0] + (BRAND_GRADIENT_BOTTOM[0] - BRAND_GRADIENT_TOP[0]) * t)
        g = int(BRAND_GRADIENT_TOP[1] + (BRAND_GRADIENT_BOTTOM[1] - BRAND_GRADIENT_TOP[1]) * t)
        b = int(BRAND_GRADIENT_TOP[2] + (BRAND_GRADIENT_BOTTOM[2] - BRAND_GRADIENT_TOP[2]) * t)
        draw.line([(0, y), (size, y)], fill=(r, g, b))
    return img


def rounded_mask(size, radius):
    mask = Image.new('L', (size, size), 0)
    draw = ImageDraw.Draw(mask)
    draw.rounded_rectangle((0, 0, size, size), radius=radius, fill=255)
    return mask


def draw_logo(size, text='B'):
    img = gradient_background(size)
    mask = rounded_mask(size, int(size * 0.22))
    rounded = Image.new('RGBA', (size, size), (0, 0, 0, 0))
    rounded.paste(img, (0, 0), mask)
    draw = ImageDraw.Draw(rounded)

    try:
        font_size = int(size * 0.58)
        font = ImageFont.truetype('arialbd.ttf', font_size)
    except OSError:
        try:
            font = ImageFont.truetype('arial.ttf', int(size * 0.58))
        except OSError:
            font = ImageFont.load_default()

    bbox = draw.textbbox((0, 0), text, font=font)
    w = bbox[2] - bbox[0]
    h = bbox[3] - bbox[1]
    x = (size - w) // 2 - bbox[0]
    y = (size - h) // 2 - bbox[1]
    draw.text((x, y), text, fill=WHITE, font=font)

    return rounded.convert('RGB')


def write_favicon():
    sizes = [16, 32, 48]
    images = [draw_logo(s) for s in sizes]
    images[-1].save(
        os.path.join(PUBLIC, 'favicon.ico'),
        format='ICO',
        sizes=[(s, s) for s in sizes],
    )
    print('wrote public/favicon.ico')


def write_apple_touch_icon():
    img = draw_logo(180)
    img.save(os.path.join(PUBLIC, 'apple-touch-icon.png'), format='PNG')
    print('wrote public/apple-touch-icon.png')


def write_og_default():
    W, H = 1200, 630
    img = Image.new('RGB', (W, H), BRAND_GRADIENT_TOP)
    draw = ImageDraw.Draw(img)
    for y in range(H):
        t = y / (H - 1)
        r = int(BRAND_GRADIENT_TOP[0] + (BRAND_GRADIENT_BOTTOM[0] - BRAND_GRADIENT_TOP[0]) * t)
        g = int(BRAND_GRADIENT_TOP[1] + (BRAND_GRADIENT_BOTTOM[1] - BRAND_GRADIENT_TOP[1]) * t)
        b = int(BRAND_GRADIENT_TOP[2] + (BRAND_GRADIENT_BOTTOM[2] - BRAND_GRADIENT_TOP[2]) * t)
        draw.line([(0, y), (W, y)], fill=(r, g, b))

    overlay = Image.new('RGB', (W, H))
    odraw = ImageDraw.Draw(overlay)
    odraw.rectangle((0, 0, W, H), fill=(0, 0, 0))

    img = Image.blend(img, overlay, 0.0)

    try:
        font_big = ImageFont.truetype('arialbd.ttf', 84)
        font_sub = ImageFont.truetype('arial.ttf', 38)
        font_brand = ImageFont.truetype('arialbd.ttf', 44)
    except OSError:
        font_big = ImageFont.load_default()
        font_sub = ImageFont.load_default()
        font_brand = ImageFont.load_default()

    brand = 'BarangayBooks'
    draw.text((80, 80), brand, fill=WHITE, font=font_brand)

    headline1 = 'Collect monthly HOA dues'
    headline2 = 'without the group chat chaos.'
    draw.text((80, 230), headline1, fill=WHITE, font=font_big)
    draw.text((80, 330), headline2, fill=WHITE, font=font_big)

    sub = 'Photo-verified payments. Instant delinquency reports.'
    draw.text((80, 450), sub, fill=(220, 220, 255), font=font_sub)

    badge_size = 200
    badge = draw_logo(badge_size)
    img.paste(badge, (W - badge_size - 80, H - badge_size - 80), badge if badge.mode == 'RGBA' else None)

    img.save(os.path.join(PUBLIC, 'og', 'default.png'), format='PNG', optimize=True)
    print('wrote public/og/default.png')


def write_manifest_icons():
    for size, name in [(192, 'android-chrome-192x192.png'), (512, 'android-chrome-512x512.png')]:
        img = draw_logo(size)
        img.save(os.path.join(PUBLIC, name), format='PNG')
        print(f'wrote public/{name}')


def write_manifest():
    manifest = {
        'name': 'BarangayBooks',
        'short_name': 'BarangayBooks',
        'description': 'The modern ledger for Philippine homeowners associations.',
        'start_url': '/',
        'display': 'standalone',
        'background_color': '#ffffff',
        'theme_color': '#4f46e5',
        'icons': [
            {'src': '/android-chrome-192x192.png', 'sizes': '192x192', 'type': 'image/png'},
            {'src': '/android-chrome-512x512.png', 'sizes': '512x512', 'type': 'image/png'},
        ],
    }
    path = os.path.join(PUBLIC, 'site.webmanifest')
    with open(path, 'w', encoding='utf-8') as f:
        json.dump(manifest, f, indent=2)
    print('wrote public/site.webmanifest')


if __name__ == '__main__':
    write_favicon()
    write_apple_touch_icon()
    write_og_default()
    write_manifest_icons()
    write_manifest()