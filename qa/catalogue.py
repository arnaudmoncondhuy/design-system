"""Rapproche ce que les feuilles déclarent de ce que la vitrine montre.

Cinq questions, posées au code lui-même — aucune liste n'est tenue à la main :

  1. chaque classe que `components.css` déclare est-elle montrée dans la vitrine ?
  2. chaque jeton de l'étage 2 y est-il montré ?
  3. chaque jeton de l'étage 2 est-il employé par une feuille ?
  4. une feuille cite-t-elle sans repli un jeton que personne ne déclare ?
  5. une feuille cite-t-elle l'étage 1, que `tokens.css` réserve aux alias ?

Aucune de ces pannes ne lève d'erreur. Un composant que la vitrine ne montre pas fonctionne,
il est seulement introuvable. Un alias que personne ne cite donne l'illusion d'une garantie
sans en être une. Une propriété qui cite un jeton inexistant est jetée en silence. Un composant
qui pioche à l'étage 1 cesse de suivre les palettes sans qu'aucune ne s'en plaigne. Rien de
tout cela n'apparaît dans un journal ; seul ce rapprochement le dit.

La vitrine décrit ses démonstrations par des boucles Twig. Ce script les rejoue en lisant les
listes que le gabarit se donne : sans cela, `--app-{{ tone }}` compterait pour un jeton au lieu
des six qu'il affiche.

Usage :  python3 qa/catalogue.py
"""
import glob, itertools, re, sys

TOKENS = 'public/tokens.css'
COMPOSANTS = 'public/components.css'
VITRINE = 'templates/styleguide.html.twig'

# Les feuilles qui consomment les jetons, et qui n'ont donc pas le droit d'en inventer ni de
# descendre à l'étage 1.
CONSOMMATRICES = ['public/base.css', 'public/components.css', 'public/styleguide.css']

# Le bandeau que `tokens.css` pose lui-même entre ses deux étages. C'est le fichier qui dit où
# passe la frontière, et non ce script : un nom de jeton ne trahit pas l'étage auquel il vit.
BANNIERE = 'Étage 2'


def lire(chemin):
    with open(chemin, encoding='utf-8') as f:
        return f.read()


def sans_commentaires(css):
    """Un jeton cité en exemple dans un commentaire n'est pas un jeton employé."""
    return re.sub(r'/\*.*?\*/', '', css, flags=re.S)


def fermeture(css, ouvrante):
    prof = 0
    for i in range(ouvrante, len(css)):
        if css[i] == '{':
            prof += 1
        elif css[i] == '}':
            prof -= 1
            if prof == 0:
                return i
    return len(css) - 1


def regles(css):
    """Rend (sélecteur, déclarations) pour chaque règle, en descendant dans les at-rules."""
    out, debut, i = [], 0, 0
    while i < len(css):
        if css[i] == '{':
            prelude, fin = css[debut:i].strip(), fermeture(css, i)
            corps = css[i + 1:fin]
            out.extend(regles(corps)) if prelude.startswith('@') else out.append((prelude, corps))
            i, debut = fin, fin + 1
        i += 1
    return out


# ── Ce que les feuilles déclarent ──────────────────────────────────────────────────────────

tokens = lire(TOKENS)

# L'étage se lit à la position dans le fichier, pas au nom. On borne au premier bloc `:root` :
# les blocs `[data-tone]` qui suivent posent des variables locales, que l'attribut allume et
# qu'aucune application ne règle.
debut_root = tokens.index(':root')
fin_root = fermeture(tokens, tokens.index('{', debut_root))
banniere = tokens.index(BANNIERE, debut_root)

ETAGE_1, ETAGE_2 = set(), set()
for m in re.finditer(r'^\s*(--app-[\w-]+)\s*:', tokens, re.M):
    if debut_root < m.start() < fin_root:
        (ETAGE_1 if m.start() < banniere else ETAGE_2).add(m.group(1))

# Pour la question 3, tout ce qui est déclaré où que ce soit compte : les blocs `[data-tone]`,
# les palettes, et les variables qu'une feuille se donne à elle-même.
DECLARES = set()
for feuille in [TOKENS, *sorted(glob.glob('public/themes/*.css')), *CONSOMMATRICES]:
    DECLARES.update(re.findall(r'(--[\w-]+)\s*:', sans_commentaires(lire(feuille))))

# ── Ce que la vitrine montre ───────────────────────────────────────────────────────────────

vitrine = lire(VITRINE)

VALEURS = re.compile(r"'([^']*)'")
CLES = re.compile(r"'([^']*)'\s*:")

# Les listes que le gabarit se donne, dans l'ordre où il faut les lire : une boucle peut
# parcourir une liste posée plus haut par un `set`.
#
# Les valeurs s'ajoutent au lieu de se remplacer : un même nom sert à plusieurs boucles, et ce
# que le gabarit montre est la réunion de leurs listes. N'en garder qu'une déclarerait
# manquants des jetons pourtant affichés.
variables = {}


def lier(nom, valeurs):
    variables.setdefault(nom, []).extend(v for v in valeurs if v not in variables.get(nom, []))


for nom, corps in re.findall(r'\{%\s*set\s+(\w+)\s*=\s*\[(.*?)\]\s*%\}', vitrine, re.S):
    lier(nom, VALEURS.findall(corps))
for nom, corps in re.findall(r'\{%\s*for\s+(\w+)\s+in\s*\[(.*?)\]\s*%\}', vitrine, re.S):
    lier(nom, VALEURS.findall(corps))
for cle, _valeur, corps in re.findall(r'\{%\s*for\s+(\w+),\s*(\w+)\s+in\s*\{(.*?)\}\s*%\}', vitrine, re.S):
    lier(cle, CLES.findall(corps))
for nom, source in re.findall(r'\{%\s*for\s+(\w+)\s+in\s+(\w+)\s*%\}', vitrine):
    if source in variables:
        lier(nom, variables[source])

SEGMENT = re.compile(r'\{\{\s*(\w+)\s*\}\}|([a-z0-9_-]+)')
EXPRESSION = re.compile(r'--((?:[a-z0-9_-]+|\{\{\s*\w+\s*\}\})+)')

MONTRES, inconnues = set(), set()
for expression in EXPRESSION.findall(vitrine):
    segments = []
    for interpolation, litteral in SEGMENT.findall(expression):
        if litteral:
            segments.append([litteral])
        elif interpolation in variables:
            segments.append(variables[interpolation])
        else:
            # Une variable dont les valeurs restent inconnues rendrait le compte faux sans
            # rien dire : la vitrine paraîtrait montrer moins qu'elle ne montre.
            inconnues.add(interpolation)
            segments = None
            break
    if segments is not None:
        MONTRES.update('--' + ''.join(combinaison) for combinaison in itertools.product(*segments))

CLASSES_MONTREES = set()
for attribut in re.findall(r'class="([^"]*)"', vitrine):
    nettoye = re.sub(r'\{\{.*?\}\}|\{%.*?%\}', ' ', attribut, flags=re.S)
    CLASSES_MONTREES.update(re.findall(r'[a-zA-Z][\w-]*', nettoye))

CLASSES_DECLAREES = set()
for selecteur, _corps in regles(sans_commentaires(lire(COMPOSANTS))):
    CLASSES_DECLAREES.update(re.findall(r'\.([a-zA-Z][\w-]*)', selecteur))

# ── Les quatre verdicts ────────────────────────────────────────────────────────────────────

fautes = 0


def verdict(titre, manquants, remede):
    global fautes
    if manquants:
        fautes += len(manquants)
        print(f'\n✘ {titre} ({len(manquants)}) :')
        for nom in sorted(manquants):
            print(f'    {nom}')
        print(f'  → {remede}')
    else:
        print(f'✔ {titre} : rien à signaler.')


if inconnues:
    print('✘ Boucles de la vitrine non résolues : ' + ', '.join(sorted(inconnues)))
    print('  → Ce script ne sait pas ce que ces variables parcourent, donc il ne peut pas')
    print('    dire ce que la vitrine montre. Leur donner une liste littérale, ou apprendre')
    print('    à ce script à les lire.')
    sys.exit(1)

verdict(
    'Classes déclarées mais jamais montrées',
    CLASSES_DECLAREES - CLASSES_MONTREES,
    'les ajouter à la vitrine : un composant que rien ne montre est introuvable.',
)

verdict(
    "Jetons de l'étage 2 jamais montrés",
    ETAGE_2 - MONTRES,
    'les ajouter à la vitrine : un réglage que rien ne montre ne sera pas employé.',
)

# Un jeton lu avec une valeur de repli — `var(--app-progress, 0%)` — n'est pas un jeton du
# catalogue : c'est un réglage que l'appelant pose sur l'instance, et le composant tient debout
# sans lui. Seul un jeton lu sans repli doit être déclaré quelque part ; sinon la propriété
# entière est jetée, en silence, et c'est la panne que ce contrôle cherche.
utilises, reglages = {}, {}
for feuille in CONSOMMATRICES:
    for jeton, suite in re.findall(r'var\(\s*(--[\w-]+)\s*([,)])', sans_commentaires(lire(feuille))):
        (reglages if suite == ',' else utilises).setdefault(jeton, set()).add(feuille)

# `tokens.css` compte parmi les citations : un alias qui n'a d'autre emploi que d'en nourrir un
# autre est employé pour de bon — c'est le cas de la gouttière dont l'échelle des espacements
# se déduit.
EMPLOYES = set(utilises) | set(reglages) | set(re.findall(r'var\(\s*(--[\w-]+)', sans_commentaires(tokens)))

verdict(
    "Jetons de l'étage 2 que personne n'emploie",
    ETAGE_2 - EMPLOYES,
    "les retirer : un alias que rien ne cite donne l'illusion d'une garantie sans en être une.",
)

verdict(
    'Jetons cités sans repli que personne ne déclare',
    {f'{jeton}  ({", ".join(sorted(feuilles))})'
     for jeton, feuilles in utilises.items() if jeton not in DECLARES},
    'la propriété tombe en silence. Corriger le nom, ou déclarer le jeton.',
)

verdict(
    "Feuilles qui descendent à l'étage 1",
    {f'{jeton}  ({", ".join(sorted(feuilles))})'
     for jeton, feuilles in utilises.items() if jeton in ETAGE_1},
    "employer l'alias correspondant : une couleur brute ne suit aucune palette.",
)

# Les réglages d'instance font partie de ce qu'une application peut employer, au même titre
# qu'une classe : les afficher, c'est les rendre trouvables. Ils ne sont jamais une faute.
POSES = {jeton: feuilles for jeton, feuilles in reglages.items() if jeton not in DECLARES}
if POSES:
    print(f"\nℹ️  Réglages posés sur l'instance ({len(POSES)}) — l'appelant les écrit, le")
    print('   composant tient debout sans eux :')
    for jeton, feuilles in sorted(POSES.items()):
        print(f'    {jeton}  ({", ".join(sorted(feuilles))})')

print(f'\n{len(CLASSES_DECLAREES)} classe(s), {len(ETAGE_2)} jeton(s) et {len(POSES)} réglage(s) au catalogue.')
sys.exit(1 if fautes else 0)
