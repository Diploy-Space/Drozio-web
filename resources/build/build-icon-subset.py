"""
Rebuilds the storefront icon subset in public/assets/front-end/webfonts/.

The storefront markup uses ~158 glyphs out of four admin icon sets that together ship
1.1 MB of font. This scans the storefront templates and scripts for the icon classes they
actually use, then emits one stylesheet and four woff2 files carrying only those glyphs.

Run it after adding an icon to a storefront template:

    pip install fonttools brotli
    PYFTSUBSET=$(which pyftsubset) python resources/build/build-icon-subset.py
"""
import re, os, subprocess

SP = os.path.dirname(os.path.abspath(__file__))
ROOT = os.path.abspath(os.path.join(SP, '..', '..'))
PYFT = os.environ.get('PYFTSUBSET', 'pyftsubset')
OUT=os.path.join(ROOT,'public/assets/front-end/webfonts')
os.makedirs(OUT, exist_ok=True)

STOREFRONT = ['resources/themes', 'Modules/Wholesale/resources/views/themes',
              'Modules/Builder/resources', 'public/assets/front-end']
# The icon stylesheets themselves list every glyph in the set, so scanning them would
# defeat the whole exercise; minified vendor bundles carry the same lists.
IGNORE = re.compile(r'/(vendor|node_modules)/|uicons-|icon-set/|\.min\.(js|css)$|\.map$')
PATTERNS = {'rr': r'\bfi-rr-[a-z0-9-]+', 'sr': r'\bfi-sr-[a-z0-9-]+',
            'brands': r'\bfi-brands-[a-z0-9-]+', 'tio': r'\btio-[a-z0-9-]+'}


def scan_storefront():
    found = {k: set() for k in PATTERNS}
    for directory in STOREFRONT:
        for base, _, files in os.walk(os.path.join(ROOT, directory)):
            for name in files:
                path = os.path.join(base, name)
                if IGNORE.search(path) or not name.endswith(('.blade.php', '.js', '.jsx', '.css', '.php')):
                    continue
                text = open(path, encoding='utf-8', errors='ignore').read()
                for key, pattern in PATTERNS.items():
                    found[key] |= set(re.findall(pattern, text))
    return {k: sorted(v) for k, v in found.items()}


glyphs = scan_storefront()

SRC = {
 'rr':  dict(css='public/assets/backend/webfonts/uicons-regular-rounded.css',
             font='public/assets/backend/webfonts/uicons-regular-rounded.woff2',
             family='uicons-regular-rounded', out='uicons-regular-rounded.storefront.woff2',
             base=r'i\[class\^="fi-rr-"\]:before[^{]*\{[^}]*\}'),
 'sr':  dict(css='public/assets/backend/webfonts/uicons-solid-rounded.css',
             font='public/assets/backend/webfonts/uicons-solid-rounded.woff2',
             family='uicons-solid-rounded', out='uicons-solid-rounded.storefront.woff2',
             base=r'i\[class\^="fi-sr-"\]:before[^{]*\{[^}]*\}'),
 'brands': dict(css='public/assets/backend/webfonts/uicons-brands.css',
             font='public/assets/backend/webfonts/uicons-brands.woff2',
             family='uicons-brands', out='uicons-brands.storefront.woff2',
             base=r'i\[class\^="fi-brands-"\]:before[^{]*\{[^}]*\}'),
 'tio': dict(css='public/assets/back-end/vendor/icon-set/style.css',
             font='public/assets/back-end/vendor/icon-set/fonts/The-Icon-of9a76.ttf',
             family='The-Icon-of', out='the-icon.storefront.woff2',
             base=r'\[class\^="tio-"\],\s*\[class\*=" tio-"\]\s*\{[^}]*\}'),
}

def codepoints(cssfile, names):
    t=open(os.path.join(ROOT,cssfile),encoding='utf-8',errors='ignore').read()
    m=dict(re.findall(r'\.([A-Za-z0-9_-]+):before\s*\{\s*content:\s*"\\([0-9a-fA-F]+)"', t))
    return {n: int(m[n],16) for n in names if n in m}

parts=[]
report=[]
report=[]
for key, s in SRC.items():
    names = glyphs[key]
    cps = codepoints(s['css'], names)
    missing = [n for n in names if n not in cps]
    src_font = os.path.join(ROOT, s['font'])
    dst = os.path.join(OUT, s['out'])
    cmd = [PYFT, src_font, '--unicodes='+','.join(f'U+{c:04X}' for c in cps.values()),
           '--flavor=woff2', '--layout-features=', '--no-hinting', '--desubroutinize',
           '--output-file='+dst]
    subprocess.run(cmd, check=True, capture_output=True)
    before=os.path.getsize(src_font); after=os.path.getsize(dst)
    report.append((s['out'], len(cps), missing, before, after))

    css=open(os.path.join(ROOT,s['css']),encoding='utf-8',errors='ignore').read()
    base=re.search(s['base'], css, re.S)
    parts.append(f'@font-face {{\n    font-family: "{s["family"]}";\n'
                 f'    src: url("{s["out"]}") format("woff2");\n'
                 f'    font-display: {"block" if key=="tio" else "swap"};\n}}\n')
    parts.append((base.group(0) if base else '')+'\n')
    for n in sorted(cps):
        parts.append(f'.{n}:before {{ content: "\\{cps[n]:x}"; }}\n')

total_src = sum(r[3] for r in report)
total_out = sum(r[4] for r in report)
total_glyphs = sum(r[1] for r in report)
header = (f"/*\n * Storefront icon subset \u2014 generated, do not hand-edit.\n *\n"
          f" * The storefront uses {total_glyphs} glyphs out of the four admin icon sets it used to load\n"
          f" * in full: {total_src/1024:.0f} KB of font became {total_out/1024:.1f} KB.\n *\n"
          f" * Regenerate after adding an icon to a storefront template:\n"
          f" *   pip install fonttools brotli && python build-icon-subset.py\n *\n"
          f" * Sources: assets/backend/webfonts/uicons-{{regular,solid}}-rounded.css, uicons-brands.css,\n"
          f" *          assets/back-end/vendor/icon-set/style.css\n */\n")
css_out=os.path.join(OUT,'storefront-icons.css')
open(css_out,'w').write(header+''.join(parts))
print(f"{'file':<40}{'glyphs':>8}{'missing':>9}{'source':>12}{'subset':>10}")
for r in report:
    print(f"{r[0]:<40}{r[1]:>8}{len(r[2]):>9}{r[3]/1024:>11.0f}K{r[4]/1024:>9.1f}K")
    for name in r[2]:
        print(f"    ! {name} is used in a storefront template but is not a glyph in this set")
print(f"\n{'storefront-icons.css':<40}{'':>8}{'':>9}{'':>12}{os.path.getsize(css_out)/1024:>9.1f}K")
