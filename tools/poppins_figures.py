"""Fabrique « Poppins Figures » : les dix chiffres de Poppins, chacun sur la même largeur.

Pour chaque graisse de Poppins livrée dans `public/fonts/` — `poppins-<graisse>.woff2` —, le
script écrit à côté `poppins-figures-<graisse>.woff2`. Chaque chiffre y prend l'avance du plus
large des dix, et son dessin est centré dans cette avance : le dessin est celui de Poppins, seul
l'espacement change. Le fichier ne contient que les chiffres, sans crénage ni substitution.

Le thème Violet déclare ces fichiers pour les seuls chiffres (`unicode-range: U+0030-0039`) et
nomme la famille en tête de sa police : les chiffres viennent d'ici, tout le reste de Poppins.

Les fichiers produits restent sous la licence de Poppins, `public/fonts/OFL-poppins.txt`, dont
ils gardent le copyright et l'adresse ; ils portent leur propre nom de famille.

À relancer quand une graisse de Poppins est ajoutée ou remplacée, puis déclarer le fichier
produit dans `public/themes/violet.css`. Deux lancements sur les mêmes graisses écrivent les
mêmes octets.

Il demande fontTools et brotli, qu'aucune étape de `check.sh` n'emploie :

    python3 -m venv /tmp/fonttools
    /tmp/fonttools/bin/pip install fonttools brotli
    /tmp/fonttools/bin/python tools/poppins_figures.py
"""
import os
import re
import sys

from fontTools import subset
from fontTools.ttLib import TTFont

FONTS_DIR = 'public/fonts'
SOURCE = re.compile(r'^poppins-(\d{3})\.woff2$')
DIGITS = '0123456789'
FAMILY = 'Poppins Figures'


def center_digits(font):
    """Donne aux dix chiffres l'avance du plus large, et centre chaque dessin dans la sienne."""
    cmap, glyf, hmtx = font.getBestCmap(), font['glyf'], font['hmtx']
    names = [cmap[ord(digit)] for digit in DIGITS]
    advance = max(hmtx[name][0] for name in names)

    for name in names:
        glyph = glyf[name]
        shift = (advance - hmtx[name][0]) // 2
        if glyph.isComposite():
            for component in glyph.components:
                component.x += shift
        elif glyph.numberOfContours > 0:
            glyph.coordinates.translate((shift, 0))
        else:
            raise SystemExit(f'Le chiffre « {name} » n\'a aucun dessin : rien à centrer.')
        glyph.recalcBounds(glyf)
        hmtx[name] = (advance, glyph.xMin)

    return advance


def rename(font):
    """Le nom de famille, le nom complet et l'identifiant prennent « Poppins Figures »."""
    for record in font['name'].names:
        text = record.toUnicode()
        if record.nameID in (1, 3, 4, 16):
            record.string = text.replace('Poppins', FAMILY, 1)
        elif record.nameID == 6:
            record.string = text.replace('Poppins-', FAMILY.replace(' ', '') + '-', 1)


def build(weight):
    source = os.path.join(FONTS_DIR, f'poppins-{weight}.woff2')
    target = os.path.join(FONTS_DIR, f'poppins-figures-{weight}.woff2')

    # La date de modification n'est pas réécrite : sans quoi chaque lancement changerait le
    # fichier, même identique.
    font = TTFont(source, recalcTimestamp=False)
    advance = center_digits(font)
    rename(font)

    options = subset.Options()
    options.flavor = 'woff2'
    options.layout_features = []
    options.name_IDs = ['*']
    options.name_languages = ['*']
    subsetter = subset.Subsetter(options)
    subsetter.populate(unicodes=[ord(digit) for digit in DIGITS])
    subsetter.subset(font)

    font.flavor = 'woff2'
    font.save(target)
    print(f'{target} : avance {advance}, {os.path.getsize(target)} octets')


def main():
    os.chdir(os.path.join(os.path.dirname(os.path.abspath(__file__)), '..'))
    weights = sorted(m.group(1) for f in os.listdir(FONTS_DIR) if (m := SOURCE.match(f)))
    if not weights:
        sys.exit(f'Aucune graisse de Poppins dans {FONTS_DIR}/ : rien à fabriquer.')
    for weight in weights:
        build(weight)


if __name__ == '__main__':
    main()
