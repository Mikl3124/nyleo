"""
Collecte des données publiques ouvertes par commune (Dordogne et départements voisins) pour les pages villes.

Sorties dans tools/geo/sources/ (JSON compact, une clé par code INSEE de resources/data/geo.json) :
  urbanisme.json            Géoportail de l'urbanisme (API GPU : documents en vigueur de la commune et de son EPCI, RNU)
  monuments.json            Mérimée, immeubles protégés au titre des monuments historiques (ministère de la Culture)
  spr.json                  Liste des sites patrimoniaux remarquables (ministère de la Culture)
  risques.json              API Géorisques v1 (GASPAR risques, PPRN/PPRM/PPRT, CatNat, zonage sismique, radon)
  mairies.json              API Annuaire de l'administration (service-public.fr), pivot « mairie »
  logements_autorises.json  SDES, Sitadel, données annuelles communales (DiDo)
  communes_extra.json       geo.api.gouv.fr (surface)

Bibliothèque standard uniquement. Les réponses HTTP sont mises en cache dans ~/.cache/nyleo-public-data
(relancer le script reprend là où il s'est arrêté). Aucune donnée n'est inventée : une valeur absente reste absente.
Usage : python3 -I tools/geo/fetch_public_data.py [--only urbanisme,risques,...]
"""
import csv, hashlib, io, json, os, re, sys, time, unicodedata, urllib.error, urllib.parse, urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
OUT = os.path.join(ROOT, 'tools', 'geo', 'sources')
CACHE = os.path.expanduser('~/.cache/nyleo-public-data')
# Départements de resources/data/geo.json : la Dordogne et ses voisins.
DEPS = tuple(d['code'] for d in json.load(open(os.path.join(ROOT, 'resources', 'data', 'geo.json'), encoding='utf-8'))['departments'].values())
YEARS = ('2021', '2022', '2023', '2024', '2025')
UA = 'nyleo.fr collecte donnees publiques (contact: mickael.delpech@gmail.com)'
MIN_INTERVAL = 0.2  # 5 requêtes/s au plus

GEORISQUES = 'https://georisques.gouv.fr/api/v1'
WFS = 'https://data.geopf.fr/wfs/ows'
GPU = 'https://www.geoportail-urbanisme.gouv.fr/api'
PRIO = {'PLUi': 0, 'PLU': 1, 'POS': 2, 'CC': 3, 'PSMV': 4}
ANNUAIRE = 'https://api-lannuaire.service-public.fr/api/explore/v2.1/catalog/datasets/api-lannuaire-administration/exports/json'
MERIMEE = 'https://ministere-culture.s3.sbg.io.cloud.ovh.net/POP/merimee.csv'
SPR = 'https://ministere-culture.s3.sbg.io.cloud.ovh.net/SPR/liste_sites_patrimoniaux_remarquables.csv'
COG_MVT = 'https://www.insee.fr/fr/statistiques/fichier/8377162/v_mvt_commune_2025.csv'
SITADEL ='https://data.statistiques.developpement-durable.gouv.fr/dido/api/v1/datafiles/9c90a880-4ba0-49b4-b99d-d7dd6c810dd0/csv'

_last = [0.0]


def fetch(url, params=None, kind='json', retries=6, timeout=90, valid=None):
    """GET poli avec cache disque et reprise sur erreur. Renvoie le JSON décodé ou le texte."""
    if params:
        url += '?' + urllib.parse.urlencode(params)
    path = os.path.join(CACHE, 'http', hashlib.sha1(url.encode()).hexdigest() + ('.json' if kind == 'json' else '.txt'))
    if os.path.exists(path):
        with open(path, encoding='utf-8') as f:
            return json.load(f) if kind == 'json' else f.read()
    delay = 2
    for attempt in range(retries):
        wait = _last[0] + MIN_INTERVAL - time.time()
        if wait > 0:
            time.sleep(wait)
        _last[0] = time.time()
        try:
            req = urllib.request.Request(url, headers={'User-Agent': UA, 'Accept': '*/*'})
            with urllib.request.urlopen(req, timeout=timeout) as r:
                body = r.read().decode('utf-8-sig')
            data = json.loads(body) if kind == 'json' else body
            if valid and not valid(data):
                raise ValueError('réponse invalide : ' + body[:200])
            os.makedirs(os.path.dirname(path), exist_ok=True)
            with open(path + '.tmp', 'w', encoding='utf-8') as f:
                f.write(body)
            os.replace(path + '.tmp', path)
            return data
        except urllib.error.HTTPError as e:
            if e.code in (400, 404):
                return None
            err = e
        except Exception as e:  # réseau, JSON, exception WFS
            err = e
        print(f'  ! {err} ({attempt + 1}/{retries}) {url[:120]}', file=sys.stderr)
        time.sleep(delay)
        delay = min(delay * 2, 60)
    print(f'  !! abandon {url[:160]}', file=sys.stderr)
    return None


def write(name, data, source):
    os.makedirs(OUT, exist_ok=True)
    data = {'_source': source, **{k: data[k] for k in sorted(data)}}
    with open(os.path.join(OUT, name), 'w', encoding='utf-8') as f:
        json.dump(data, f, ensure_ascii=False, separators=(',', ':'))
        f.write('\n')
    n = len(data) - 1
    print(f'{name} : {n}/{len(COMMUNES)} communes ({100 * n / len(COMMUNES):.1f} %)')


def norm(s):
    s = unicodedata.normalize('NFKD', s or '').encode('ascii', 'ignore').decode().lower()
    return re.sub(r'[^a-z0-9]+', ' ', s.replace('saint', 'st')).strip()


def fr_date(s):
    m = re.match(r'(\d{2})/(\d{2})/(\d{4})', s or '')
    return f'{m[3]}-{m[2]}-{m[1]}' if m else None


with open(os.path.join(ROOT, 'resources', 'data', 'geo.json'), encoding='utf-8') as f:
    COMMUNES = json.load(f)['communes']


def geo_api():
    """Communes actuelles (centre, surface, EPCI) depuis geo.api.gouv.fr, par département."""
    out = {}
    for dep in DEPS:
        rows = fetch(f'https://geo.api.gouv.fr/departements/{dep}/communes',
                     {'fields': 'code,nom,surface,centre,codeEpci,epci'}, valid=lambda d: isinstance(d, list)) or []
        out.update({r['code']: r for r in rows})
    return out


def communes_extra(geo):
    data = {}
    for insee in COMMUNES:
        g = geo.get(insee)
        if g and g.get('surface') is not None:
            data[insee] = {'surface_km2': round(g['surface'] / 100, 2)}
    write('communes_extra.json', data, 'geo.api.gouv.fr (surface IGN en hectares convertie en km²) ; altitudes non disponibles par cette API')


def urbanisme(geo):
    def wfs(typename, props, cql):
        return fetch(WFS, {'SERVICE': 'WFS', 'VERSION': '2.0.0', 'REQUEST': 'GetFeature', 'TYPENAMES': typename,
                           'OUTPUTFORMAT': 'application/json', 'PROPERTYNAME': props, 'CQL_FILTER': cql, 'COUNT': '5000'},
                     valid=lambda d: isinstance(d, dict) and 'features' in d, retries=4)  # WFS GPU souvent saturé

    def documents(grid):
        rows = fetch(f'{GPU}/document', {'grid': grid, 'status': 'document.production'}, valid=lambda d: isinstance(d, list)) or []
        return [r for r in rows if r.get('legalStatus') == 'APPROVED' and r.get('effectiveStatus') != 'ARCHIVE'
                and r.get('type') in PRIO]

    rings = {}

    def inside(grid, lon, lat):
        """Point dans l'emprise WKT d'une grille GPU (règle pair-impair sur tous les anneaux)."""
        if grid not in rings:
            wkt = (fetch(f'{GPU}/grid/{grid}', valid=lambda d: isinstance(d, dict)) or {}).get('geometry') or ''
            rings[grid] = [[tuple(map(float, p.split())) for p in r.split(',')] for r in re.findall(r'\(([^()]+)\)', wkt)]
        n = False
        for ring in rings[grid]:
            for (x1, y1), (x2, y2) in zip(ring, ring[1:] + ring[:1]):
                if (y1 > lat) != (y2 > lat) and lon < x1 + (lat - y1) * (x2 - x1) / (y2 - y1):
                    n = not n
        return n

    def date(name):
        m = re.search(r'_(\d{4})(\d{2})(\d{2})(?:_|$)', name or '')
        return f'{m[1]}-{m[2]}-{m[3]}' if m else None

    data, via_wfs = {}, 0
    for i, insee in enumerate(COMMUNES):
        g = geo.get(insee)
        if not g:
            continue
        lon, lat = g['centre']['coordinates']
        docs = [{'du_type': r['type'], 'grid_name': r['grid']['name'], 'grid_title': r['grid']['title'], 'name': r['originalName']}
                for r in documents(insee)]
        if g.get('codeEpci'):
            docs += [{'du_type': r['type'], 'grid_name': r['grid']['name'], 'grid_title': r['grid']['title'], 'name': r['originalName']}
                     for r in documents(g['codeEpci']) if r['type'] == 'PLUi' and inside(g['codeEpci'], lon, lat)]
        rnu = (fetch(f'{GPU}/grid/{insee}', valid=lambda d: isinstance(d, dict)) or {}).get('rnu')
        if not docs and rnu is not True:  # PLUi porté par une autre grille (ancien EPCI…) : recherche spatiale
            d = wfs('wfs_du:document', 'gid,grid_name,grid_title,name,du_type', f'INTERSECTS(the_geom,POINT({lat} {lon}))')
            docs = [f['properties'] for f in (d or {}).get('features', []) if f['properties'].get('du_type') in PRIO]
            via_wfs += bool(docs)
        if docs:
            plans = [p for p in docs if p['du_type'] != 'PSMV'] or docs
            main = max(plans, key=lambda p: (date(p['name']) or '', -PRIO[p['du_type']]))
            e = {'type': main['du_type']}
            if main['du_type'] == 'PLUi':
                e['nom'] = main['grid_title']
                e['porteur'] = g['epci']['nom'] if g.get('codeEpci') == main['grid_name'] and g.get('epci') else main['grid_title']
            else:
                e['nom'] = f"{main['du_type']} de {COMMUNES[insee]['name']}"
            if date(main['name']):
                e['date'] = date(main['name'])
            autres = sorted({p['du_type'] for p in docs if p['du_type'] != main['du_type']} & {'PSMV'})
            if autres:
                e['autres'] = autres
            data[insee] = e
        elif rnu is True:
            data[insee] = {'type': 'RNU'}
        if i % 100 == 0:
            print(f'  urbanisme {i}/{len(COMMUNES)}')
    print(f'  urbanisme : {via_wfs} communes trouvées par recherche spatiale WFS')
    write('urbanisme.json', data, "Géoportail de l'urbanisme (API www.geoportail-urbanisme.gouv.fr/api : documents en vigueur "
          "de la commune et de son EPCI, PLUi retenu si le centre de la commune selon geo.api.gouv.fr est dans son emprise ; "
          "grille communale rnu=true pour le RNU ; secours WFS IGN wfs_du:document). « date » = date d'approbation portée par le "
          "nom normalisé CNIG du document ; « nom » d'un document communal = type + commune. Procédures en cours non publiées "
          "par le GPU : non renseignées.")


def georisques():
    def gr(path, insee, key='code_insee', **extra):
        return fetch(f'{GEORISQUES}/{path}', {key: insee, **extra}, valid=lambda d: isinstance(d, dict))

    data = {}
    for i, insee in enumerate(COMMUNES):
        e = {}
        d = gr('gaspar/risques', insee)
        risques = [r['libelle_risque_long'] for x in (d or {}).get('data', []) for r in x.get('risques_detail', [])]
        if risques:
            e['risques'] = list(dict.fromkeys(risques))
        ppr = []
        for kind in ('pprn', 'pprm', 'pprt'):
            d = gr(f'gaspar/{kind}', insee, key='codeInsee', page_size=100)
            for p in (d or {}).get('content', []):
                det = fetch(f"{GEORISQUES}/gaspar/{kind}/{p['idGaspar']}", valid=lambda d: isinstance(d, dict)) or {}
                com = next((c for c in det.get('communes', []) if c.get('codeInsee') == insee), {})
                aleas = com.get('aleas') or []
                appro = sorted(fr_date(a.get('dateApprobation')) for a in aleas if a.get('dateApprobation'))
                if any(a.get('dateAbrog') for a in aleas) and not appro:
                    etat = 'abrogé'
                elif any(a.get('dateAnnulation') for a in aleas) and not appro:
                    etat = 'annulé'
                elif appro:
                    etat = 'approuvé, en révision' if p.get('etatRevision') else 'approuvé'
                elif any(a.get('datePrescription') for a in aleas):
                    etat = 'prescrit'
                else:
                    etat = None
                x = {'type': p.get('modeleProcedure'), 'libelle': p.get('libPpr')}
                if aleas:
                    x['aleas'] = list(dict.fromkeys(a['libelle'] for a in aleas if a.get('libelle')))
                if etat:
                    x['etat'] = etat
                if appro:
                    x['date_approbation'] = appro[0]
                ppr.append(x)
        if ppr:
            e['ppr'] = ppr
        d = gr('gaspar/catnat', insee, page_size=1)
        if d and d.get('response_code') == 200:
            e['catnat'] = d.get('results', 0)
        d = gr('zonage_sismique', insee)
        if d and d.get('data'):
            e['sismicite'] = int(d['data'][0]['code_zone'])
        d = gr('radon', insee)
        if d and d.get('data'):
            e['radon'] = int(d['data'][0]['classe_potentiel'])
        if e:
            data[insee] = e
        if i % 50 == 0:
            print(f'  risques {i}/{len(COMMUNES)}')
    write('risques.json', data, 'API Géorisques v1 georisques.gouv.fr (BRGM / ministère de la Transition écologique) : '
          'gaspar/risques, gaspar/pprn|pprm|pprt (+ détail par procédure pour dates et état), gaspar/catnat, zonage_sismique, radon')


def merimee(geo):
    """Mérimée : code INSEE à la date de protection, ramené à la commune actuelle (communes déléguées, puis nom)."""
    path = os.path.join(CACHE, 'merimee.csv')
    if not os.path.exists(path):
        print('  téléchargement Mérimée…')
        os.makedirs(CACHE, exist_ok=True)
        urllib.request.urlretrieve(MERIMEE, path + '.tmp')
        os.replace(path + '.tmp', path)
    parent = {}  # ancien code INSEE -> commune actuelle (INSEE, mouvements du COG)
    for r in csv.DictReader(io.StringIO(fetch(COG_MVT, kind='text') or ''), delimiter=','):
        if r['TYPECOM_AP'] == 'COM' and r['COM_AP'] != r['COM_AV'] and r['COM_AV'][:2] in DEPS:
            parent.setdefault(r['COM_AV'], r['COM_AP'])
    for dep in DEPS:
        for r in fetch('https://geo.api.gouv.fr/communes_associees_deleguees', {'codeDepartement': dep},
                       valid=lambda d: isinstance(d, list)) or []:
            parent[r['code']] = r['chefLieu']
    for code in list(parent):  # fusions successives
        seen = {code}
        while parent[code] not in COMMUNES and parent[code] in parent and parent[code] not in seen:
            seen.add(parent[code])
            parent[code] = parent[parent[code]]
    by_name = {}
    for c in COMMUNES.values():
        by_name.setdefault((c['code'][:2], norm(c['name'])), c['code'])
    data, unmatched = {}, []
    with open(path, encoding='utf-8-sig', newline='') as f:
        for r in csv.DictReader(f, delimiter='|'):
            dep = (r['Departement_format_numerique'] or '')[:2]
            if dep not in DEPS:
                continue
            code = (r['COG_Insee_lors_de_la_protection'] or '').split(';')[0].strip()
            insee = code if code in COMMUNES else parent.get(code) if parent.get(code) in COMMUNES else \
                by_name.get((dep, norm(r['Commune_forme_editoriale'] or r['Commune_forme_index'])))
            if not insee:
                unmatched.append(f"{r['Reference']} {code} {r['Commune_forme_index']}")
                continue
            typo = (r['Typologie_de_la_protection'] or '').lower()
            cl, ins = 'class' in typo, 'inscri' in typo
            if not (cl or ins):
                continue
            years = re.findall(r'\b(\d{4})(?:/\d{2}/\d{2})?\s*:', r['Date_et_typologie_de_la_protection'] or '')
            e = {'nom': (r['Titre_editorial_de_la_notice'] or r['Denomination_de_l_edifice']).strip(),
                 'protection': 'classé et inscrit' if cl and ins else 'classé' if cl else 'inscrit'}
            if years:
                e['date'] = int(min(years))
            data.setdefault(insee, []).append(e)
    for v in data.values():
        v.sort(key=lambda e: (e['protection'] == 'inscrit', e['nom']))
    if unmatched:
        print(f'  Mérimée : {len(unmatched)} notices sans commune actuelle : ' + ' | '.join(unmatched[:10]))
    write('monuments.json', data, 'Mérimée, liste des immeubles protégés au titre des monuments historiques (ministère de la Culture, '
          + MERIMEE + ') ; « date » = année de la première protection')


def spr():
    text = fetch(SPR, kind='text')
    data = {}
    for r in csv.DictReader(io.StringIO(text), delimiter=';'):
        insee = r['code_insee']
        if insee in COMMUNES:
            plan = r['type_de_plan_en_vigueur'] or r['spr_initial_regime_de_creation']
            nom = f"Site patrimonial remarquable de {COMMUNES[insee]['name']}" + (f' ({plan})' if plan else '')
            data.setdefault(insee, []).append(nom)
    write('spr.json', data, 'Liste des sites patrimoniaux remarquables, ministère de la Culture (' + SPR + ') ; '
          'le fichier ne donne pas de nom propre au site : libellé = commune + type de plan en vigueur')


def mairies():
    rows = []
    for dep in DEPS:
        rows += fetch(ANNUAIRE, {'where': f'startswith(code_insee_commune,"{dep}") and pivot like "mairie"', 'limit': -1},
                      valid=lambda d: isinstance(d, list)) or []
    best = {}
    for r in rows:
        insee = r.get('code_insee_commune')
        pivots = json.loads(r.get('pivot') or '[]')
        if insee not in COMMUNES or not any(p.get('type_service_local') == 'mairie' for p in pivots):
            continue
        nom = (r.get('nom') or '').lower()
        rank = (('déléguée' in nom) or ('annexe' in nom), r.get('ancien_code_pivot') or 'z')
        if insee not in best or rank < best[insee][0]:
            best[insee] = (rank, r)
    data = {}
    for insee, (_, r) in best.items():
        adr = next((a for a in json.loads(r.get('adresse') or '[]') if a.get('type_adresse') == 'Adresse'), None)
        e = {}
        if adr:
            voie = ', '.join(x for x in (adr.get('complement1'), adr.get('complement2'), adr.get('numero_voie'),
                                         adr.get('service_distribution')) if x)
            e.update({k: v for k, v in (('adresse', voie), ('cp', adr.get('code_postal')), ('ville', adr.get('nom_commune'))) if v})
        tel = json.loads(r.get('telephone') or '[]')
        if tel and tel[0].get('valeur'):
            e['telephone'] = tel[0]['valeur']
        site = json.loads(r.get('site_internet') or '[]')
        if site and site[0].get('valeur'):
            e['site'] = site[0]['valeur']
        if e:
            data[insee] = e
    write('mairies.json', data, "API Annuaire de l'administration, service-public.fr (api-lannuaire-administration, pivot « mairie », "
          'mairie principale hors mairies déléguées et annexes)')


def logements():
    text = fetch(SITADEL, {'withColumnName': 'true', 'withColumnDescription': 'false', 'ANNEE': 'gte:' + YEARS[0],
                           'TYPE_LGT': 'eq:Tous Logements'}, kind='text', timeout=300)
    data = {}
    for r in csv.DictReader(io.StringIO(text), delimiter=';'):
        if r['COMM'] in COMMUNES and r['ANNEE'] in YEARS and r['LOG_AUT'] != '':
            data.setdefault(r['COMM'], {})[r['ANNEE']] = int(float(r['LOG_AUT']))
    write('logements_autorises.json', data, 'SDES, Sitadel, logements autorisés et commencés, données annuelles communales non estimées '
          '(DiDo, datafile 9c90a880-4ba0-49b4-b99d-d7dd6c810dd0), tous types de logements, en date réelle')


if __name__ == '__main__':
    only = set(sys.argv[sys.argv.index('--only') + 1].split(',')) if '--only' in sys.argv else None
    steps = ('communes_extra', 'spr', 'logements', 'mairies', 'monuments', 'urbanisme', 'risques')
    geo = geo_api()
    print(f'{len(COMMUNES)} communes, {len(set(COMMUNES) & set(geo))} retrouvées dans geo.api.gouv.fr')
    for s in steps:
        if only and s not in only:
            continue
        print('==', s)
        {'communes_extra': lambda: communes_extra(geo), 'spr': spr, 'logements': logements, 'mairies': mairies,
         'monuments': lambda: merimee(geo), 'urbanisme': lambda: urbanisme(geo), 'risques': georisques}[s]()
