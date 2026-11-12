"""
Build Waxine Slab and Waxine Sans: the site's own type, made from two fonts
under the SIL Open Font License 1.1 (see sources/OFL-*.txt).

    Waxine Slab  <- Zilla Slab SemiBold (Typotheque for Mozilla)
    Waxine Sans  <- Fira Sans Light, Light Italic, SemiBold, SemiBold Italic

Changes, kept small on purpose:
  1. Waxine Slab: the dot on i and j is a diamond instead of a square.
  2. Both: a north-east arrow of our own (U+2197), square-cut, drawn at the
     stem weight of each font. The site uses it for links to other sites.
  3. Waxine Slab is spaced a little tighter (it is only used for titles);
     Waxine Sans uses its alternate ampersand by default.
  4. New family names (the OFL does not allow a modified font to carry the
     original's reserved name), the original copyright and licence kept,
     a note that the font was modified, and webfont files for the site.

Run:  python3 type/build.py   (needs fontTools; writes public/assets/type/)
"""
import math
import os
from fontTools.ttLib import TTFont
from fontTools.pens.ttGlyphPen import TTGlyphPen
from fontTools import subset

HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, 'sources')
OUT = os.path.join(HERE, '..', 'public', 'assets', 'type')

NOTE = 'Modified by Paul Versteeg for waxine.nl, 2026.'
SLAB_TRACKING = 8   # units per side (1000 per em): 16 tighter per letter

# Latin, Latin-1, Latin Extended-A, general punctuation, currency, arrows.
UNICODES = (list(range(0x20, 0x7F)) + list(range(0xA0, 0x180))
            + list(range(0x2000, 0x2070)) + list(range(0x20A0, 0x20C0))
            + list(range(0x2190, 0x21A0)) + [0x2122, 0x2116, 0x0131, 0x0237, 0x0307])


def poly(pen, pts):
    """Draw a closed polygon, clockwise (TrueType outer contour)."""
    area = sum(x0 * y1 - x1 * y0 for (x0, y0), (x1, y1) in zip(pts, pts[1:] + pts[:1]))
    if area > 0:            # counter-clockwise in y-up space: reverse
        pts = pts[::-1]
    pen.moveTo(pts[0])
    for p in pts[1:]:
        pen.lineTo(p)
    pen.closePath()


def r(p):
    return (round(p[0]), round(p[1]))


def draw_arrow(font, stem):
    """Our own ↗: a diagonal shaft into a square-cornered head."""
    cmap = font.getBestCmap()
    name = cmap[0x2197]
    g = font['glyf'][name]
    left, bottom, right, top = g.xMin, g.yMin, g.xMax, g.yMax
    t = stem
    h = (right - left) * 0.62          # length of the head's arms
    pen = TTGlyphPen(font.getGlyphSet())
    # Head: an L along the top and the right side.
    poly(pen, [r(p) for p in [(right - h, top), (right, top), (right, top - h),
                              (right - t, top - h), (right - t, top - t), (right - h, top - t)]])
    # Shaft: from the bottom-left to the inside corner of the head.
    d = (1 / math.sqrt(2), 1 / math.sqrt(2))
    n = (-d[1] * t / 2, d[0] * t / 2)
    a = (left + t / 2, bottom + t / 2)
    b = (right - t / 2, top - t / 2)
    poly(pen, [r(p) for p in [(a[0] + n[0], a[1] + n[1]), (b[0] + n[0], b[1] + n[1]),
                              (b[0] - n[0], b[1] - n[1]), (a[0] - n[0], a[1] - n[1])]])
    glyph = pen.glyph()
    font['glyf'][name] = glyph
    glyph.recalcBounds(font['glyf'])
    adv, _ = font['hmtx'][name]
    font['hmtx'][name] = (adv, glyph.xMin)


def diamond_dots(font):
    """Replace the square dot on i and j by a diamond of the same weight."""
    glyf = font['glyf']
    for ch in 'ij':
        name = font.getBestCmap()[ord(ch)]
        g = glyf[name]
        coords, ends, _ = g.getCoordinates(glyf)
        contours, s = [], 0
        for e in ends:
            contours.append(list(coords[s:e + 1]))
            s = e + 1
        dot = max(contours, key=lambda c: min(y for x, y in c))
        xs, ys = [x for x, y in dot], [y for x, y in dot]
        cx, cy = (min(xs) + max(xs)) / 2, (min(ys) + max(ys)) / 2
        half = math.sqrt((max(xs) - min(xs)) * (max(ys) - min(ys)) * 2) / 2 * 0.92
        pen = TTGlyphPen(font.getGlyphSet())
        for c in contours:
            if c is dot:
                poly(pen, [r(p) for p in [(cx, cy + half), (cx + half, cy), (cx, cy - half), (cx - half, cy)]])
            else:
                poly(pen, [tuple(p) for p in c])
        new = pen.glyph()
        glyf[name] = new
        new.recalcBounds(glyf)
        adv, _ = font['hmtx'][name]
        font['hmtx'][name] = (adv, new.xMin)


def tighten(font, k):
    """Take k units off each side of every spacing glyph."""
    glyf, hmtx = font['glyf'], font['hmtx']
    for name in font.getGlyphOrder():
        adv, lsb = hmtx[name]
        if adv == 0:
            continue
        g = glyf[name]
        if g.numberOfContours > 0:          # simple outline: move it left
            g.coordinates.translate((-k, 0))
            g.recalcBounds(glyf)
        # composites follow their (already moved) parts
        hmtx[name] = (adv - 2 * k, lsb - k)
    for name in font.getGlyphOrder():
        g = glyf[name]
        if g.isComposite():
            g.recalcBounds(glyf)
            hmtx[name] = (hmtx[name][0], g.xMin)


def ampersand_alternate(font):
    order = set(font.getGlyphOrder())
    if 'ampersand.ss03' in order:
        for table in font['cmap'].tables:
            if table.isUnicode() and 0x26 in table.cmap:
                table.cmap[0x26] = 'ampersand.ss03'


def rename(font, family, style):
    name = font['name']
    full = f'{family} {style}'
    ps = f'{family}-{style}'.replace(' ', '')
    rib = style if style in ('Regular', 'Italic', 'Bold', 'Bold Italic') else ('Italic' if 'Italic' in style else 'Regular')
    version = name.getDebugName(5) or 'Version 1.000'
    for rec in list(name.names):
        if rec.nameID in (1, 2, 3, 4, 6, 7, 16, 17, 18, 21, 22):
            name.removeNames(nameID=rec.nameID)
    legacy_family = family if rib in ('Regular', 'Italic') and style in ('Regular', 'Italic') else f"{family} {style.replace(' Italic', '').replace('Italic', '')}".strip()
    for nid, val in [(1, legacy_family), (2, rib), (3, f'{ps};waxine.nl;2026'), (4, full),
                     (5, f'{version}; {NOTE}'), (6, ps), (16, family), (17, style)]:
        name.setName(val, nid, 3, 1, 0x409)
        name.setName(val, nid, 1, 0, 0)
    # Keep the original copyright (0) and licence (13, 14), and say what changed.
    copyright = name.getDebugName(0) or ''
    name.removeNames(nameID=0)
    for plat in [(3, 1, 0x409), (1, 0, 0)]:
        name.setName(f'{copyright} {NOTE}'.strip(), 0, *plat)
    font['CFF '] if 'CFF ' in font else None


def build(src, family, style, slab=False, stem=100):
    font = TTFont(os.path.join(SRC, src))
    for tag in ('fpgm', 'prep', 'cvt ', 'hdmx', 'LTSH', 'VDMX'):
        if tag in font:
            del font[tag]
    for g in font['glyf'].glyphs.values():
        if hasattr(g, 'program'):
            del g.program
    font['glyf'].glyphs = {n: font['glyf'][n] for n in font.getGlyphOrder()}
    if slab:
        diamond_dots(font)
        tighten(font, SLAB_TRACKING)
    else:
        ampersand_alternate(font)
    draw_arrow(font, stem)
    rename(font, family, style)

    opts = subset.Options()
    opts.layout_features = ['*']
    opts.name_IDs = ['*']
    opts.name_languages = ['*']
    opts.notdef_outline = True
    opts.hinting = False
    opts.flavor = 'woff'
    sub = subset.Subsetter(opts)
    sub.populate(unicodes=UNICODES)
    sub.subset(font)
    os.makedirs(OUT, exist_ok=True)
    out = os.path.join(OUT, f"{family.replace(' ', '')}-{style.replace(' ', '')}.woff")
    font.flavor = 'woff'
    font.save(out)
    print(f'{out}: {os.path.getsize(out) // 1024} KB')


build('ZillaSlab-SemiBold.ttf', 'Waxine Slab', 'SemiBold', slab=True, stem=104)
build('FiraSans-Light.ttf', 'Waxine Sans', 'Light', stem=62)
build('FiraSans-LightItalic.ttf', 'Waxine Sans', 'Light Italic', stem=62)
build('FiraSans-SemiBold.ttf', 'Waxine Sans', 'SemiBold', stem=112)
build('FiraSans-SemiBoldItalic.ttf', 'Waxine Sans', 'SemiBold Italic', stem=112)

with open(os.path.join(OUT, 'OFL.txt'), 'w') as fh:
    fh.write('Waxine Slab and Waxine Sans are modified versions of Zilla Slab and Fira Sans.\n'
             f'{NOTE}\nBoth are licensed under the SIL Open Font License, Version 1.1, below.\n\n'
             '== Waxine Slab (from Zilla Slab) ==\n\n')
    fh.write(open(os.path.join(SRC, 'OFL-ZillaSlab.txt')).read())
    fh.write('\n\n== Waxine Sans (from Fira Sans) ==\n\n')
    fh.write(open(os.path.join(SRC, 'OFL-FiraSans.txt')).read())
