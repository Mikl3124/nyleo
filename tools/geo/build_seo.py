"""
Génère resources/data/seo.json (données publiques par commune pour les pages villes)
à partir des fichiers de tools/geo/sources/ produits par fetch_public_data.py.

Usage : python3 -I tools/geo/build_seo.py tools/geo/sources resources/data/seo.json
"""
import datetime, json, os, sys

SRC, OUT = sys.argv[1], sys.argv[2]


def load(name):
    path = os.path.join(SRC, name + '.json')
    if not os.path.exists(path):
        return {}
    d = json.load(open(path, encoding='utf-8'))
    d.pop('_source', None)
    return d


urba, mh, spr, risques, mairies, logements, extra = (load(n) for n in
    ['urbanisme', 'monuments', 'spr', 'risques', 'mairies', 'logements_autorises', 'communes_extra'])
geo = json.load(open('resources/data/geo.json', encoding='utf-8'))
covered = {k[:2] for k in mh}  # départements présents dans la collecte Mérimée

out = {}
for code in geo['communes']:
    c = {}
    if code in urba:
        u = urba[code]
        c['urba'] = {k: v for k, v in {
            'type': u.get('type'), 'nom': u.get('nom'), 'porteur': u.get('porteur'),
            'date': u.get('date'), 'autres': u.get('autres'), 'en_cours': u.get('procedure_en_cours'),
        }.items() if v}
    # Monuments et SPR : la base couvre tout le département, une commune absente n'en a pas.
    # Seulement pour les départements réellement collectés : sinon on ne sait pas, on ne dit rien.
    if mh and code[:2] in covered:
        c['mh'] = [{'n': m['nom'], 'p': m['protection']} for m in mh.get(code, [])]
    if spr and code[:2] in covered:
        c['spr'] = spr.get(code, [])
    if code in risques:
        r = risques[code]
        c['risques'] = r.get('risques', [])
        ppr = r.get('ppr', [])
        c['ppr'] = [{'t': p.get('type', ''), 'l': p.get('libelle', ''), 'a': ' '.join(p.get('aleas', [])), 'd': p.get('date_approbation')}
                    for p in ppr if 'approuvé' in (p.get('etat') or '')]
        c['ppr_prescrits'] = [{'t': p.get('type', ''), 'l': p.get('libelle', ''), 'a': ' '.join(p.get('aleas', []))}
                              for p in ppr if (p.get('etat') or '').startswith('prescrit')]
        for k, short in (('catnat', 'catnat'), ('sismicite', 'sismi'), ('radon', 'radon')):
            if r.get(k) is not None:
                c[short] = r[k]
    if code in mairies:
        c['mairie'] = {k: v for k, v in mairies[code].items() if v}
    if code in logements:
        c['logements'] = dict(sorted(logements[code].items()))
    if code in extra and extra[code].get('surface_km2'):
        c['surface'] = extra[code]['surface_km2']
    out[code] = c

# Communes avec leur propre page : 1 000 habitants et plus, plus les communes patrimoniales
# (site patrimonial remarquable ou au moins 10 monuments historiques) d'au moins 300 habitants,
# où presque chaque projet passe par l'Architecte des Bâtiments de France. Le bureau a toujours sa page.
PAGE_MIN_POP, HERITAGE_MIN_POP, HERITAGE_MIN_MH = 1000, 300, 10
for code, g in geo['communes'].items():
    pop, c = g.get('pop') or 0, out[code]
    heritage = bool(c.get('spr')) or len(c.get('mh', [])) >= HERITAGE_MIN_MH
    g['page'] = pop >= PAGE_MIN_POP or (heritage and pop >= HERITAGE_MIN_POP) or code == geo['base']
json.dump(geo, open('resources/data/geo.json', 'w', encoding='utf-8'), ensure_ascii=False, separators=(',', ':'))
print('pages :', sum(g['page'] for g in geo['communes'].values()))

json.dump({'updated': datetime.date.today().isoformat(), 'communes': out}, open(OUT, 'w', encoding='utf-8'),
          ensure_ascii=False, separators=(',', ':'))
print(OUT, len(out), 'communes', os.path.getsize(OUT) // 1024, 'Ko')
