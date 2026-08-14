"""Lit des feuilles de jetons, y découvre les palettes, résout les alias et les light-dark(),
et mesure chaque paire dans chacune. Aucune valeur n'est recopiée, aucune palette n'est connue
d'avance : ce que ce script juge est ce que le navigateur voit.

Sans argument, il lit les feuilles du paquet. Pour y joindre celles d'une application :

    python3 qa/contrast.py public/tokens.css public/themes/*.css chemin/vers/assets/styles/themes/*.css
"""
import glob, re, sys

def lum(h):
    h = h.lstrip('#')
    if len(h) == 3: h = ''.join(c * 2 for c in h)
    c = [int(h[i:i+2], 16) / 255 for i in (0, 2, 4)]
    c = [x / 12.92 if x <= 0.04045 else ((x + 0.055) / 1.055) ** 2.4 for x in c]
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]

def ratio(a, b):
    la, lb = lum(a), lum(b)
    return (max(la, lb) + 0.05) / (min(la, lb) + 0.05)

def sans_commentaires(css):
    """Un bloc montré en exemple dans un commentaire n'est pas une palette."""
    return re.sub(r'/\*.*?\*/', '', css, flags=re.S)

def fermeture(css, ouvrante):
    prof = 0
    for i in range(ouvrante, len(css)):
        if css[i] == '{': prof += 1
        elif css[i] == '}':
            prof -= 1
            if prof == 0: return i
    return len(css) - 1

def regles(css):
    """Rend (sélecteur, déclarations) pour chaque règle, en descendant dans les at-rules."""
    out, debut, i = [], 0, 0
    while i < len(css):
        if css[i] == '{':
            prelude, fin = css[debut:i].strip(), fermeture(css, i)
            corps = css[i+1:fin]
            out.extend(regles(corps)) if prelude.startswith('@') else out.append((prelude, corps))
            i, debut = fin, fin + 1
        i += 1
    return out

def declarations(corps):
    return {m.group(1): m.group(2).strip()
            for m in re.finditer(r'([-\w]+)\s*:\s*([^;]+)', corps)}

def split_top(s):
    """Coupe sur la virgule de premier niveau, en ignorant celles des parenthèses imbriquées."""
    prof, out, cur = 0, [], ''
    for ch in s:
        if ch == '(': prof += 1
        elif ch == ')': prof -= 1
        if ch == ',' and prof == 0:
            out.append(cur); cur = ''
        else:
            cur += ch
    out.append(cur)
    return [p.strip() for p in out]

feuilles = sys.argv[1:] or ['public/tokens.css', *sorted(glob.glob('public/themes/*.css'))]
css = sans_commentaires('\n'.join(open(f).read() for f in feuilles))

BASE, PALETTES = {}, {}
for prelude, corps in regles(css):
    decl = declarations(corps)
    for selecteur in (s.strip() for s in prelude.split(',')):
        if selecteur == ':root':
            BASE.update(decl)
        elif m := re.fullmatch(r'\[data-theme="([\w-]+)"\]', selecteur):
            PALETTES.setdefault(m.group(1), {}).update(decl)

def resoudre(nom, palette, cote, vu=None):
    vu = vu or set()
    if nom in vu: sys.exit(f'boucle sur {nom}')
    vu.add(nom)
    src = palette if nom in palette else BASE
    if nom not in src: sys.exit(f'jeton introuvable : {nom}')
    v = src[nom]
    if v.startswith('#'): return v
    m = re.fullmatch(r'var\((--app-[\w-]+)\)', v)
    if m: return resoudre(m.group(1), palette, cote, vu)
    m = re.fullmatch(r'light-dark\((.*)\)', v, re.S)
    if m:
        part = split_top(m.group(1))[0 if cote == 'clair' else 1]
        mv = re.fullmatch(r'var\((--app-[\w-]+)\)', part)
        return resoudre(mv.group(1), palette, cote, vu) if mv else part
    sys.exit(f'valeur non résolue pour {nom} : {v}')

PAIRS = [
 ('texte courant / page',   'ink',         'bg',           4.5),
 ('texte courant / carte',  'ink',         'surface',      4.5),
 ('texte atténué / page',   'ink-soft',    'bg',           4.5),
 ('texte atténué / carte',  'ink-soft',    'surface',      4.5),
 ('texte estompé / page',   'ink-muted',   'bg',           4.5),
 ('texte estompé / carte',  'ink-muted',   'surface',      4.5),
 ('entête de table',        'ink-soft',    'raised',       4.5),
 ('lien / page',            'accent',      'bg',           4.5),
 ('lien / carte',           'accent',      'surface',      4.5),
 ('bouton principal',       'ink-inverse', 'accent',       4.5),
 ('bouton supprimer',       'ink-inverse', 'danger',       4.5),
 ('bouton valider',         'ink-inverse', 'success',      4.5),
 ('étiquette accent',       'accent-ink',  'accent-soft',  4.5),
 ('étiquette succès',       'success-ink', 'success-soft', 4.5),
 ('étiquette attention',    'warning-ink', 'warning-soft', 4.5),
 ('étiquette échec',        'danger-ink',  'danger-soft',  4.5),
 ('étiquette information',  'info-ink',    'info-soft',    4.5),
 ("message d'erreur",       'danger',      'surface',      4.5),
 ('bord de champ / carte',  'line-control','surface',      3.0),
 ('bord de champ / page',   'line-control','bg',           3.0),
 ('bord de champ / relevé', 'line-control','raised',       3.0),
 # Décoratif sur une palette ordinaire : c'est le fond teinté qui donne sa forme à l'étiquette,
 # et le texte qui porte le sens. Le filet ne devient structurant que là où une palette relève
 # son seuil, parce qu'elle a renoncé aux fonds teintés — d'où la réserve.
 ('filet d\'étiquette',     'line',        'raised',       3.0, 'renforce'),
]

# Sans attribut sur <html>, `color-scheme: light dark` laisse le système trancher : les deux
# issues se mesurent. Chaque palette déclarée se mesure ensuite pour elle-même, du côté de
# light-dark() que son propre `color-scheme` fige.
MESURES = [('sans attribut, système clair', {}, 'clair', 4.5),
           ('sans attribut, système sombre', {}, 'sombre', 4.5)]

for nom, palette in PALETTES.items():
    cote = 'sombre' if palette.get('color-scheme', '').split() == ['dark'] else 'clair'
    MESURES.append((nom, palette, cote, float(palette.get('--app-theme-ratio', 4.5))))

fautes = 0
for nom, palette, cote, seuil in MESURES:
    renforce = seuil > 4.5
    print(f"\n=== {nom}{f'  (seuil texte porté à {seuil:g}:1)' if renforce else ''} ===")
    pire = 99
    for label, fg, bg, need, *reserve in PAIRS:
        if reserve and not renforce:
            continue
        if renforce and need == 4.5:
            need = seuil
        r = ratio(resoudre(f'--app-{fg}', palette, cote), resoudre(f'--app-{bg}', palette, cote))
        pire = min(pire, r / need)
        if r < need: fautes += 1
        print(f"  {'OK ' if r >= need else 'NON'} {r:6.2f} : {need}   {label}")
    print(f"  marge la plus faible : ×{pire:.2f}")
print(f"\n{fautes} paire(s) sous le seuil, sur {len(MESURES)} palette(s).")
sys.exit(1 if fautes else 0)
