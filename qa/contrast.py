"""Lit tokens.css, résout les alias et les light-dark(), mesure chaque paire dans les trois
palettes. Aucune valeur n'est recopiée : ce que ce script juge est ce que le navigateur voit."""
import re, sys

def lum(h):
    h = h.lstrip('#')
    if len(h) == 3: h = ''.join(c * 2 for c in h)
    c = [int(h[i:i+2], 16) / 255 for i in (0, 2, 4)]
    c = [x / 12.92 if x <= 0.04045 else ((x + 0.055) / 1.055) ** 2.4 for x in c]
    return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2]

def ratio(a, b):
    la, lb = lum(a), lum(b)
    return (max(la, lb) + 0.05) / (min(la, lb) + 0.05)

def bloc(css, selecteur):
    i = css.index(selecteur)
    debut = css.index('{', i) + 1
    prof, j = 1, debut
    while prof:
        if css[j] == '{': prof += 1
        elif css[j] == '}': prof -= 1
        j += 1
    return dict((m.group(1), m.group(2).strip())
                for m in re.finditer(r'(--app-[\w-]+)\s*:\s*([^;]+);', css[debut:j-1]))

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

css = open(sys.argv[1] if len(sys.argv) > 1 else 'public/tokens.css').read()
BASE = bloc(css, ':root {')
CONTRASTE = bloc(css, '[data-theme="contrast"]')

def resoudre(nom, theme, vu=None):
    vu = vu or set()
    if nom in vu: sys.exit(f'boucle sur {nom}')
    vu.add(nom)
    src = CONTRASTE if (theme == 'contraste' and nom in CONTRASTE) else BASE
    if nom not in src: sys.exit(f'jeton introuvable : {nom}')
    v = src[nom]
    if v.startswith('#'): return v
    m = re.fullmatch(r'var\((--app-[\w-]+)\)', v)
    if m: return resoudre(m.group(1), theme, vu)
    m = re.fullmatch(r'light-dark\((.*)\)', v, re.S)
    if m:
        part = split_top(m.group(1))[0 if theme == 'clair' else 1]
        mv = re.fullmatch(r'var\((--app-[\w-]+)\)', part)
        return resoudre(mv.group(1), theme, vu) if mv else part
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
 # Décoratif en clair et en sombre : c'est le fond teinté qui donne sa forme à l'étiquette,
 # et le texte qui porte le sens. Le filet ne devient structurant que sur la palette à
 # contraste renforcé, où tous les fonds sont noirs — d'où le seuil réservé à celle-ci.
 ('filet d\'étiquette',     'line',        'raised',       3.0, 'contraste'),
]

SEUIL_RENFORCE = 7.0   # le niveau le plus exigeant, visé par la seule palette « contraste »
fautes = 0
for theme in ('clair', 'sombre', 'contraste'):
    renforce = theme == 'contraste'
    print(f"\n=== {theme}{'  (seuil texte porté à 7:1)' if renforce else ''} ===")
    pire = 99
    for label, fg, bg, need, *reserve in PAIRS:
        if reserve and reserve[0] != theme:
            continue
        if renforce and need == 4.5:
            need = SEUIL_RENFORCE
        r = ratio(resoudre(f'--app-{fg}', theme), resoudre(f'--app-{bg}', theme))
        pire = min(pire, r / need)
        if r < need: fautes += 1
        print(f"  {'OK ' if r >= need else 'NON'} {r:6.2f} : {need}   {label}")
    print(f"  marge la plus faible : ×{pire:.2f}")
print(f"\n{fautes} paire(s) sous le seuil.")
sys.exit(1 if fautes else 0)
