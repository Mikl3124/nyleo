"""
Génère resources/data/geo.json (communes de Dordogne et des départements voisins, cartes SVG).

Sources (Licence Ouverte Etalab) :
  - contours : https://github.com/gregoiredavid/france-geojson (IGN / Etalab)
  - communes, populations, EPCI : npm @etalab/decoupage-administratif 6.0.0
Usage : python3 -I tools/geo/build.py <dossier_sources> resources/data/geo.json
"""
import json, math, re, sys, unicodedata

SRC, OUT = sys.argv[1], sys.argv[2]
LAT0, LON0 = 45.0, 0.0
KX, KY = 111.32 * math.cos(math.radians(LAT0)), 110.57
# La Dordogne et tous ses départements limitrophes (ordre d'affichage du site).
DEPS = {
    '24': ('dordogne', 'Dordogne', 'departements/24-dordogne'),
    '33': ('gironde', 'Gironde', 'departements/33-gironde'),
    '16': ('charente', 'Charente', 'departements/16-charente'),
    '17': ('charente-maritime', 'Charente-Maritime', 'departements/17-charente-maritime'),
    '87': ('haute-vienne', 'Haute-Vienne', 'departements/87-haute-vienne'),
    '19': ('correze', 'Corrèze', 'departements/19-correze'),
    '46': ('lot', 'Lot', 'departements/46-lot'),
    '47': ('lot-et-garonne', 'Lot-et-Garonne', 'departements/47-lot-et-garonne'),
}
PAGE_MIN_POP = {code: 1000 for code in DEPS}  # build_seo.py affine ensuite la liste (communes patrimoniales)
BASE = '24543'  # Sourzac


def proj(lon, lat):
    return ((lon - LON0) * KX, -(lat - LAT0) * KY)


def dp(pts, tol):
    if len(pts) < 3:
        return pts
    keep = [False] * len(pts)
    keep[0] = keep[-1] = True
    stack = [(0, len(pts) - 1)]
    while stack:
        a, b = stack.pop()
        ax, ay = pts[a]; bx, by = pts[b]
        dx, dy = bx - ax, by - ay
        n = math.hypot(dx, dy) or 1e-9
        best, idx = 0, None
        for i in range(a + 1, b):
            px, py = pts[i]
            d = abs(dy * px - dx * py + bx * ay - by * ax) / n
            if d > best:
                best, idx = d, i
        if idx is not None and best > tol:
            keep[idx] = True
            stack += [(a, idx), (idx, b)]
    return [p for p, k in zip(pts, keep) if k]


def dp_ring(pts, tol):
    # Anneau fermé : on coupe au point le plus éloigné du départ, puis on simplifie chaque moitié.
    far = max(range(len(pts)), key=lambda i: math.hypot(pts[i][0] - pts[0][0], pts[i][1] - pts[0][1]))
    return dp(pts[:far + 1], tol)[:-1] + dp(pts[far:], tol)


def rings(geom):
    polys = [geom['coordinates']] if geom['type'] == 'Polygon' else geom['coordinates']
    return [ring for poly in polys for ring in poly[:1]]  # contours extérieurs seulement


def path(geom, tol, min_area=0.0):
    out = []
    for ring in rings(geom):
        pts = [proj(*c[:2]) for c in ring]
        if min_area and abs(area(pts)) < min_area:
            continue
        s = dp_ring(pts, tol)
        if len(s) < 4:
            continue
        out.append('M' + 'L'.join(f'{x:.1f} {y:.1f}' if tol >= 0.5 else f'{x:.2f} {y:.2f}' for x, y in s[:-1]) + 'Z')
    return ''.join(out)


def area(pts):
    return sum(x1 * y2 - x2 * y1 for (x1, y1), (x2, y2) in zip(pts, pts[1:])) / 2


def centroid(geom):
    best = max(([proj(*c[:2]) for c in r] for r in rings(geom)), key=lambda p: abs(area(p)))
    if best[0] != best[-1]:
        best = best + best[:1]
    a = area(best) or 1e-9
    cx = sum((x1 + x2) * (x1 * y2 - x2 * y1) for (x1, y1), (x2, y2) in zip(best, best[1:])) / (6 * a)
    cy = sum((y1 + y2) * (x1 * y2 - x2 * y1) for (x1, y1), (x2, y2) in zip(best, best[1:])) / (6 * a)
    return cx, cy


def bbox(paths):
    nums = [float(v) for p in paths for v in re.findall(r'-?\d+\.?\d*', p)]
    xs, ys = nums[0::2], nums[1::2]
    return min(xs), min(ys), max(xs), max(ys)


def vb(b, pad):
    return f'{b[0]-pad:.1f} {b[1]-pad:.1f} {b[2]-b[0]+2*pad:.1f} {b[3]-b[1]+2*pad:.1f}'


def slug(s):
    s = unicodedata.normalize('NFKD', s).encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', '-', s).strip('-')


data = json.load(open(f'{SRC}/etalab/package/data/communes.json'))
communes = {c['code']: c for c in data if c.get('departement') in DEPS and c.get('type') == 'commune-actuelle'}
epci = {}
for e in json.load(open(f'{SRC}/etalab/package/data/epci.json')):
    for m in e['membres']:
        epci[m['code']] = e['nom']
arr = {a['code']: a['nom'] for a in json.load(open(f'{SRC}/etalab/package/data/arrondissements.json'))}

out = {'source': 'IGN / Etalab (Licence Ouverte), france-geojson, @etalab/decoupage-administratif 6.0.0', 'base': BASE, 'departments': {}, 'communes': {}}
dep_outlines = {}
for code, (key, name, folder) in DEPS.items():
    dep = json.load(open(f"{SRC}/{folder.split('/')[1]}".replace(folder.split('/')[1], f"departement-{code}-{key}.geojson")))
    outline = path(dep['geometry'], 0.25)
    dep_outlines[code] = outline
    feats = json.load(open(f'{SRC}/communes-{code}-{key}.geojson'))['features']
    mesh = []
    for f in feats:
        c = communes.get(f['properties']['code'])
        if not c:
            continue
        p = path(f['geometry'], 0.12)
        cx, cy = centroid(f['geometry'])
        mesh.append(p)
        out['communes'][c['code']] = {
            'code': c['code'], 'name': c['nom'], 'slug': slug(c['nom']), 'dep': key,
            'cp': c.get('codesPostaux', []), 'pop': c.get('population'),
            'epci': epci.get(c['code']), 'arr': arr.get(c.get('arrondissement')),
            'x': round(cx, 2), 'y': round(cy, 2), 'path': p,
            'page': (c.get('population') or 0) >= PAGE_MIN_POP[code] or c['code'] == BASE,
        }
    b = bbox([outline])
    out['departments'][key] = {'code': code, 'name': name, 'viewBox': vb(b, 4), 'outline': outline}

# Doublons de slug dans un même département
seen = {}
for c in out['communes'].values():
    k = (c['dep'], c['slug'])
    seen.setdefault(k, []).append(c)
for k, lst in seen.items():
    if len(lst) > 1:
        for c in lst:
            c['slug'] = f"{c['slug']}-{c['cp'][0] if c['cp'] else c['code']}"

# Carte des deux départements + France simplifiée (situation)
fr = json.load(open(f'{SRC}/departements-version-simplifiee.geojson'))['features']
france = {}
for f in fr:
    code = f['properties']['code']
    if code in ('2A', '2B') or code.isdigit() and int(code) <= 95 or code in ('2A', '2B'):
        france[code] = path(f['geometry'], 2.5, min_area=40)
# Carte du rayon d'action : les départements couverts, et autour ceux qui les bordent.
AROUND = ('85', '79', '86', '36', '23', '63', '15', '12', '82', '32', '40', '81', '03', '18', '37', '49', '44', '64', '65', '31', '48', '43')
out['region'] = {'viewBox': vb(bbox(list(dep_outlines.values())), 8),
                 'neighbours': {k: path(next(f for f in fr if f['properties']['code'] == k)['geometry'], 1.4) for k in AROUND},
                 # Contours allégés pour la carte d'ensemble (les pages départements gardent le tracé fin).
                 'outlines': {DEPS[code][0]: path(json.load(open(f"{SRC}/departement-{code}-{DEPS[code][0]}.geojson"))['geometry'], 0.7) for code in DEPS}}
out['france'] = {'viewBox': vb(bbox(list(france.values())), 10), 'paths': france}

# Ville de base (Sourzac)
bx, by = out['communes'][BASE]['x'], out['communes'][BASE]['y']
for c in out['communes'].values():
    c['km'] = round(math.hypot(c['x'] - bx, c['y'] - by))

json.dump(out, open(OUT, 'w'), ensure_ascii=False, separators=(',', ':'))
pages = [c for c in out['communes'].values() if c['page']]
print('communes', len(out['communes']), 'pages', len(pages), 'size', round(len(open(OUT).read()) / 1024), 'Ko')
