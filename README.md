# arnaudmoncondhuy/design-system

Des jetons en **deux étages**, un catalogue de composants gouverné par **trois attributs**, et
des palettes qui se déclarent sans qu'aucun composant ne les connaisse — dont une à contraste
renforcé, destinée à la basse vision.

```css
/* Étage 1 — les échelles. Aucun composant ne les cite. */
--app-indigo-600: #4f46e5;

/* Étage 2 — les alias. Un rôle, pas une couleur. C'est le seul étage qu'un composant emploie. */
--app-accent: light-dark(var(--app-indigo-600), var(--app-indigo-400));
```

Une palette se change donc en réécrivant l'étage 2. Ajouter « contraste renforcé » a coûté
quarante lignes et n'a touché aucun composant.

Ce qui n'est pas couleur suit la même règle : taille de texte, graisse, interligne, rayon,
espacement, largeur de contenu, ordre d'empilement et durée sont des alias eux aussi.

## Ce que le paquet garantit

- **Aucun composant ne connaît de couleur.** Une règle qui écrit un `#rrggbb` est une faute que
  la relecture attrape ; tout passe par un alias.
- **Aucun composant n'écrit de taille absolue.** Toutes se mesurent sur la racine, dont
  `--app-font-size` fixe la taille — relever ce seul jeton agrandit l'interface entière, et pas
  seulement le texte courant. Comme il s'écrit lui-même en `rem`, il part de ce que le lecteur a
  réglé dans son navigateur au lieu de l'écraser.
- **Chaque paire texte/fond atteint son seuil**, mesuré et non estimé. `qa/contrast.py` lit les
  feuilles qu'on lui donne, y découvre les palettes, résout les alias et les `light-dark()`, et
  compare les 47 paires sur chacune — dont les six combinaisons que chaque ton rend possibles.
  Une palette vise 4,5:1, ou le seuil qu'elle se donne par `--app-theme-ratio`. Un fond en
  dégradé porte du texte sur toute sa longueur : chacun de ses arrêts est mesuré, et c'est le
  pire qui décide. Pour y joindre les palettes d'une application :

  ```
  python3 qa/contrast.py public/tokens.css public/themes/*.css …/assets/styles/themes/*.css
  ```
- **Un composant coloré n'a qu'une règle pour les six tons.** `data-tone` est traduit en
  variables locales par `tokens.css`, et c'est tout ce qu'un composant cite. Un ton de plus se
  déclarerait là-bas, sans reprendre une ligne du catalogue.
- **`hidden` cache, quoi que porte l'élément.** L'attribut du HTML l'emporte sur le `display` que
  déclare un composant, sans quoi une alerte ou une carte cachée par le HTML resterait à l'écran.
- **Le thème est rendu par le serveur**, donc sans clignotement au chargement et sans script
  bloquant dans le `<head>`.
- **Sans attribut, la préférence du système s'applique** : `color-scheme: light dark` suffit,
  il n'y a pas de media-query à dupliquer.

## Installer

```bash
composer require arnaudmoncondhuy/design-system
```

Le bundle s'enregistre seul si l'application utilise Symfony Flex ; sinon,
dans `config/bundles.php` :

```php
ArnaudMoncondhuy\DesignSystem\DesignSystemBundle::class => ['all' => true],
```

## Employer

Le gabarit de l'application étend celui du paquet, et remplit ce qui lui appartient :

```twig
{% extends '@DesignSystem/layout.html.twig' %}

{% block brand_label %}{{ 'app.name'|trans }}{% endblock %}
{% block javascripts %}{{ importmap('app') }}{% endblock %}
```

Il reçoit alors la charpente, le lien d'évitement, le menu de choix de palette et les trois
feuilles — servies depuis `bundles/designsystem/`, l'espace qu'AssetMapper donne au dossier
`public/` d'un bundle.

Les blocs qu'elle remplit : `title`, `javascripts`, `brand`, `nav`, `header_actions`,
`sidebar`, `layout`, `body`, `footer`. `stylesheets` appelle `parent()` pour garder les feuilles
du paquet.

Les messages éphémères sont rendus par le squelette, et **leur type devient un ton** : `error`
est teinté en `danger`, `notice` en `info`, et ce qui n'est pas dans cette liste passe tel quel
— un ton ajouté reste donc joignable depuis `addFlash()`. Le message traverse le catalogue de
traduction, si bien qu'une clé y devient sa phrase et qu'une phrase déjà écrite en ressort
intacte.

**Il n'y a rien d'autre à écrire.** Le paquet ajoute lui-même son dossier de comportements à
ceux que StimulusBundle balaye : ses contrôleurs s'enregistrent sous le nom de leur fichier, et
l'application n'a ni entrée d'`importmap` à poser, ni contrôleur à enregistrer.

## Le docteur

```
php bin/console design-system:doctor
```

Il cherche ce qui ne lève jamais : un bloc de palette qui ne déplace rien, une palette dont
personne ne donne le libellé et qui s'affiche sous sa clé, un jeton mal orthographié dont la
propriété tombe en silence, une couleur écrite en dur qui échappe aux palettes, une clé de
traduction déclarée deux fois dont la première est effacée sans bruit, un cookie relu sous un
nom que personne n'écrit. Il rend un code de sortie, donc il a sa place dans une
routine qualité. Il commence par dire quelles feuilles il a lues et quelles palettes il y a
trouvées.

`--fix` écrit ce qui s'ajoute sans rien écraser — la route de la vitrine, notamment. Il ne
réécrit jamais un fichier existant : prouver qu'un gabarit n'a pas été personnalisé
demanderait de connaître tous les squelettes qu'un Symfony a pu produire, et se tromper
effacerait le travail de quelqu'un. Ce qu'il ne peut pas faire, il le dicte.

## La vitrine

Le catalogue complet, chaque composant dans chacun de ses états, sur une page. Elle n'est pas
joignable tant que l'application ne l'a pas montée — à réserver au développement. Le plus
simple est de laisser le docteur l'écrire :

```
php bin/console design-system:doctor --fix
```

ce qui dépose `config/routes/dev/design_system.yaml` :

```yaml
design_system:
    resource: '@DesignSystemBundle/config/routes.php'
    type: php
    prefix: /styleguide
```

La page étend le `base.html.twig` de l'application : elle a besoin de ses scripts, et le
squelette du paquet ne pose aucun script de lui-même.

## Régler

```yaml
# config/packages/design_system.yaml
design_system:
    theme_storage: cookie   # ou « none » : rien n'est retenu
    cookie_name: theme

    # Les dossiers dont chaque fichier est une palette : lue, servie, proposée.
    theme_directories: ['%kernel.project_dir%/assets/styles/themes']

    # Des feuilles que l'application sert déjà, simplement lues.
    theme_stylesheets: ['%kernel.project_dir%/assets/styles/app.css']

    # Les palettes réellement proposées, dans l'ordre du menu. Absente : toutes.
    themes: ['light', 'dark', 'contrast']
```

`themes` ne sert qu'à restreindre et à ordonner ; nommer une palette qu'aucune feuille ne
déclare arrête la compilation du conteneur, en disant ce que les feuilles déclarent. C'est le
seul endroit où une palette peut manquer, et ça ne passe pas inaperçu.

Pour ranger le choix ailleurs — le champ d'un compte connecté — écrire une classe qui implémente
`ThemeStorage` et la substituer :

```yaml
services:
    ArnaudMoncondhuy\DesignSystem\Theme\ThemeStorage: '@App\Theme\AccountThemeStorage'
```

## Le catalogue

Une classe déclare le composant, et **trois attributs** le déclinent. Chacun répond à une seule
question, et deux d'entre eux se combinent librement :

| Attribut | Question | Valeurs |
| --- | --- | --- |
| `data-tone` | quel sens ? | `neutral` `accent` `success` `warning` `danger` `info` |
| `data-variant` | quelle insistance ? | plein par défaut, `subtle` bordé, `ghost` sans fond |
| `data-size` | quelle taille ? | `sm`, `lg` |

```html
<button class="app-btn" data-tone="danger">Supprimer</button>
<button class="app-btn" data-tone="danger" data-variant="ghost" data-size="sm">Supprimer</button>
<span class="app-badge" data-tone="success">Terminé</span>
<span class="app-badge" data-tone="success" data-variant="solid">Terminé</span>
```

Un bouton a donc les six tons dans les trois formes, sans qu'une seule de ces dix-huit
combinaisons soit écrite : `tokens.css` traduit `data-tone` en cinq variables locales, et le
composant ne cite qu'elles.

```css
.app-btn[data-tone]        { background: var(--app-tone); color: var(--app-tone-on); }
.app-btn[data-tone]:hover  { background: var(--app-tone-hover); }
```

L'état, lui, ne s'invente pas : il passe par les attributs que le navigateur et les lecteurs
d'écran connaissent déjà — `disabled`, `aria-busy`, `aria-current`, `aria-sort`,
`aria-selected`, `aria-disabled`. **Un état porté par un attribut ARIA n'est pas redit
ailleurs** : la teinte ne fait que doubler ce qu'un lecteur d'écran lit déjà.

La largeur d'un champ suit la même règle. Un champ occupe sa ligne entière, sauf s'il annonce
combien de caractères il porte : `<input size="6">` prend alors la largeur de ce qu'il attend,
et un code à six chiffres cesse de ressembler à un champ qui en attendrait trente.

Boutons, cartes, étiquettes, formulaires, champs accolés, cases à cocher, boutons radio,
interrupteurs, contrôle segmenté, tableaux triables, messages, chiffres, barre de progression,
listes de définitions, état vide, fil d'étapes, fenêtre modale, menu déroulant, accordéon,
navigation principale et latérale, fil d'Ariane, onglets, pagination, avatar, grilles. Le
préfixe `app-` se renomme d'un remplacement, dans `public/` et dans
`templates/layout.html.twig`.

Trois composants demandent au navigateur ce qu'il sait déjà faire, plutôt que de le réécrire :
la fenêtre modale est un `<dialog>` ouvert par `showModal()`, l'accordéon et le menu déroulant
sont des `<details>`. Le comportement `menu` n'ajoute au second que ce que l'élément ne donne
pas — la fermeture par Échap et par un clic au-dehors.

### La densité

Tout espacement est un multiple de `--app-gap`, par une échelle de huit paliers
(`--app-space-2xs` … `--app-space-3xl`). Resserrer l'interface entière tient donc en une ligne,
et aucune règle du catalogue n'est reprise :

```css
:root { --app-gap: 6px; }
```

### La charpente et son menu latéral

Le squelette donne un en-tête, un contenu et un pied. Remplir le bloc `sidebar` **suffit** à le
faire passer en deux colonnes : il n'y a aucun attribut à poser en plus.

```twig
{% block sidebar %}
    <nav class="app-nav" data-orientation="vertical" aria-label="Menu">…</nav>

    {# Ce qui reste au bas du menu, quoi qu'il arrive au-dessus. #}
    <div class="app-sidebar-footer">…</div>
{% endblock %}
```

**Le menu prend le coin haut gauche**, et toute la hauteur ; l'en-tête commence à sa droite et
ne porte plus que le contexte de la page. C'est la disposition des interfaces dont le menu est
la navigation principale — et la marque suit le coin : elle se rend en tête du menu quand il y
en a un, dans l'en-tête sinon. Le bloc `brand` est le même dans les deux cas.

Le reste se comporte comme on l'attend, sans qu'une ligne soit à écrire pour cela :

- **les deux barres tiennent en place** quand la page descend, chacune dans sa colonne. Ni
  décalage à calculer ni hauteur à mesurer : toutes deux partent du haut de la fenêtre ;
- **ce sont les liens qui défilent**, pas le menu entier : `app-sidebar-footer` reste en bas
  même quand la liste est plus haute que l'écran ;
- **il devient un tiroir** à partir du moment où l'écran n'a plus de côté. Il ne reste alors
  que la marque et son bouton, collés en haut de page ; le tiroir sort du côté du bouton et
  passe par-dessus le contenu au lieu de le pousser — un menu de quinze entrées repousserait
  sinon la page de plusieurs écrans. Il se ferme par Échap, par un clic au-dehors, et par la
  croix qui prend la place du bouton.

Le tiroir est un `<dialog>` ouvert par `showModal()` : la couche supérieure, le voile et le
piège au clavier viennent du navigateur. Hors tiroir, ce dialogue n'est pas ouvert et c'est la
feuille qui l'affiche — une colonne ordinaire, que rien n'annonce comme une fenêtre. Le passage
de l'un à l'autre est le fait du comportement `sidebar`, avec une conséquence voulue : **sans
script, le panneau reste affiché dans le flux**, visible et utilisable.

Pour la disposition inverse — une barre du haut sur toute la largeur, le menu en dessous — il
suffit de réécrire la grille de la charpente :

```css
@media (min-width: 60em) {
    .app-shell[data-layout="sidebar"] {
        grid-template-areas: "header header" "sidebar main" "sidebar footer";
    }
}
```

### La page seule

Une connexion, une page d'erreur, une étape isolée : la page ne porte qu'une chose, et une
colonne pleine largeur pour un formulaire de deux champs se lit mal. Remplir le bloc `layout`
avec `centered` pose le contenu au milieu de l'écran, dans une colonne étroite :

```twig
{% block layout %}centered{% endblock %}
```

L'en-tête reste au-dessus — le choix de palette doit rester joignable avant même d'entrer. Un
menu latéral, s'il y en a un, l'emporte sur ce choix. La largeur de la colonne est le jeton
`--app-content-narrow`.

## Ajouter une palette

**Une palette, un fichier.** On le dépose, et c'est tout : rien à déclarer ailleurs — ni enum,
ni configuration, ni traduction, ni même un `<link>` à poser.

```css
/* assets/styles/themes/ocean.css */
[data-theme="ocean"] {
    --app-theme-label: "Océan";
    color-scheme: light;
    --app-accent: #0e7490;
    --app-accent-hover: #155e75;
    --app-accent-soft: #ecfeff;
    --app-accent-ink: #164e63;
}
```

Le fichier est **lu, servi et proposé du seul fait d'être là** : le menu l'offre au rechargement
suivant, le gabarit pose son `<link>`, le cookie l'accepte, `design-system:doctor` la compte, et
`qa/contrast.py` la mesure. Le retirer la retire partout.

**Un ton se déplace en entier, ou pas du tout.** Ses cinq jetons — `--app-<ton>`, `-hover`,
`-soft`, `-ink` et `--app-on-<ton>` — forment les six combinaisons que le catalogue peut
afficher, et `qa/contrast.py` les mesure toutes : n'en redéclarer qu'une partie laisse les
autres sur les valeurs d'origine, et c'est le mélange des deux qui tombe sous le seuil.

Deux propriétés ne peignent rien et ne servent qu'à déclarer la palette :

| Propriété | Rôle |
| --- | --- |
| `--app-theme-label` | Ce que le menu affiche. Sans elle, c'est la traduction `theme.<valeur>` du domaine `design_system` — ce dont vivent les trois palettes du paquet, qui restent ainsi traduisibles. |
| `--app-theme-ratio` | Le seuil de contraste que la palette se donne, quand il dépasse les 4,5:1 ordinaires. `qa/contrast.py` l'applique à toutes les paires de texte. |

Le paquet lit ces fichiers **à la compilation du conteneur**, jamais à l'exécution : il n'y a ni
analyse de feuille dans une requête, ni liste à tenir à jour à côté. Les dossiers de palettes
sont déclarés au conteneur, y compris quand ils n'existent pas encore — déposer un fichier, ou
créer le dossier qui manquait, suffit à faire recompiler le catalogue.

C'est ce qui fait disparaître toute une famille de pannes : une palette proposée qui ne repeint
rien, ou un bloc que rien ne rend, ne peuvent plus exister — la déclaration et la peinture sont
le même fichier.

### Où le déposer

- **Dans l'application** — `assets/styles/themes/`, et rien d'autre à faire. `theme_directories`
  en nomme d'autres si besoin ; ils doivent vivre sous les ressources du projet, faute de quoi
  la compilation s'arrête en le disant — une palette qu'AssetMapper ne peut pas servir serait
  proposée sans jamais arriver au navigateur.
- **Dans le paquet** — `public/themes/`, pour une palette qui appartient au design system
  lui-même. C'est là que vit « contraste renforcé ».

`theme_stylesheets` couvre l'autre cas : une feuille que l'application sert **déjà** — sa
`app.css`, par exemple — et où une palette est écrite au milieu d'autre chose. Le paquet l'y lit,
mais ne pose aucun lien : ce serait un second téléchargement du même fichier.

Une palette portée par le paquet peut être redéclarée par l'application : les feuilles sont lues
dans l'ordre, celles du paquet en tête.
