# arnaudmoncondhuy/design-system

Des jetons en **deux étages**, un catalogue de composants gouverné par **quatre attributs**, et
des palettes qui se déclarent sans qu'aucun composant ne les connaisse — dont une à contraste
renforcé, destinée à la basse vision.

```css
/* Étage 1 — les échelles. Aucun composant ne les cite. */
--app-indigo-600: #4f46e5;

/* Étage 2 — les alias. Un rôle, pas une couleur. C'est le seul étage qu'un composant emploie. */
--app-accent: light-dark(var(--app-indigo-600), var(--app-indigo-350));
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
- **Le thème et le mode sont deux choix.** Le thème dit quelles couleurs — Indigo, Violet,
  Contraste renforcé… —, le mode dit clair, sombre ou automatique. Un thème qui n'a pas le mode
  choisi cède la place, dans ce mode, au thème par défaut de l'application.
- **Le thème est rendu par le serveur**, donc sans clignotement au chargement. Un seul cas pose
  un script bloquant dans le `<head>` : le mode automatique avec un thème qui n'a qu'un mode,
  où seul le navigateur sait si le système réclame l'autre.
- **En mode automatique, la préférence du système s'applique** : `color-scheme: light dark`
  suffit, il n'y a pas de media-query à dupliquer.

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
`sidebar`, `sidebar_actions`, `layout`, `body`, `footer`. `stylesheets` appelle `parent()` pour
garder les feuilles du paquet.

`sidebar_actions` se pose en tête du menu latéral, entre la marque et le bouton de repli : ce
qui doit rester sous la main même menu replié. Un menu qu'on y ouvre part du bouton vers la
droite sur un grand écran, et se cale contre le bord droit de la fenêtre sur un écran étroit.

Les messages éphémères sont rendus par le squelette, et **leur type devient un ton** : `error`
est teinté en `danger`, `notice` en `info`, et ce qui n'est pas dans cette liste passe tel quel
— un ton ajouté reste donc joignable depuis `addFlash()`. Le message traverse le catalogue de
traduction, si bien qu'une clé y devient sa phrase et qu'une phrase déjà écrite en ressort
intacte.

**Ils se posent par-dessus la page**, contre le bord droit, sous la barre du haut, et chacun a
son bouton pour se fermer. Un message de réussite ou d'information se retire seul au bout de six
secondes ; un avertissement ou un échec reste jusqu'à ce qu'on le ferme. Le délai s'interrompt
tant que le pointeur ou le focus est dessus. Sans script, ils restent en tête du contenu, dans le
flux.

Une application qui les rend ailleurs — dans un cadre Turbo, pour qu'ils suivent les envois qui
s'y font — vide le bloc `flashes` et inclut le même gabarit à l'endroit voulu :

```twig
{% block flashes %}{% endblock %}
{# … puis, dans le cadre : #}
{{ include('@DesignSystem/flashes.html.twig') }}
```

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
joignable tant que l'application ne l'a pas montée — à réserver au développement, ou à placer
derrière un droit. Le plus simple est de laisser le docteur l'écrire :

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
    cookie_name: theme      # celui du mode y ajoute `_mode` : theme_mode

    # Les dossiers dont chaque fichier est une palette : lue, servie, proposée.
    theme_directories: ['%kernel.project_dir%/assets/styles/themes']

    # Des feuilles que l'application sert déjà, simplement lues.
    theme_stylesheets: ['%kernel.project_dir%/assets/styles/app.css']

    # Les palettes réellement proposées, dans l'ordre du menu. Absente : toutes, dans l'ordre
    # des feuilles, celles à contraste renforcé en dernier.
    themes: ['indigo', 'violet', 'contrast']

    # Le thème d'ouverture, tant que le visiteur n'en a choisi aucun.
    default_theme: indigo

    # Le mode d'ouverture : auto, light ou dark.
    default_mode: auto
```

`default_theme` donne à une application son thème de base. Le visiteur reste libre d'en changer ;
c'est aussi lui qui s'affiche dans le mode qu'un autre thème n'a pas — Contraste renforcé, qui
n'existe qu'en sombre, montre ce thème-là en mode clair. Il doit donc avoir les deux modes, et
figurer parmi les thèmes que le menu propose : sinon, la compilation s'arrête.

`default_mode` fait de même pour le mode. Les deux choix sont indépendants : un visiteur qui n'a
choisi que le mode garde le thème par défaut, et l'inverse.

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

Une classe déclare le composant, et **quatre attributs** le déclinent. Chacun répond à une seule
question, et ils se combinent librement :

| Attribut | Question | Valeurs |
| --- | --- | --- |
| `data-tone` | quel sens ? | `neutral` `accent` `success` `warning` `danger` `info` |
| `data-variant` | quelle insistance ? | plein par défaut, `subtle` bordé, `ghost` sans fond — et, pour une étiquette, `solid` et `dot` |
| `data-size` | quelle taille ? | `sm`, `lg` |
| `data-hue` | quel voisin ? | `1` à `6` : une couleur qui distingue sans rien signaler |

`data-hue` sert là où la couleur n'a qu'à séparer — la pastille d'un chiffre clé, les initiales
d'un client. Elle ne dit jamais « attention » : quand un composant porte les deux attributs,
c'est `data-tone` qui l'emporte.

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
interrupteurs, contrôle segmenté, tableaux triables, listes d'objets avec leur action,
messages, chiffres clés et leur pastille, barre de progression,
listes de définitions, état vide, fil d'étapes, fenêtre modale, menu déroulant, accordéon,
navigation principale et latérale, fil d'Ariane, onglets, pagination, avatar et initiales, grilles. Le
préfixe `app-` se renomme d'un remplacement, dans `public/` et dans
`templates/layout.html.twig`.

Trois composants demandent au navigateur ce qu'il sait déjà faire, plutôt que de le réécrire :
la fenêtre modale est un `<dialog>` ouvert par `showModal()`, l'accordéon et le menu déroulant
sont des `<details>`. Le comportement `menu` n'ajoute au second que ce que l'élément ne donne
pas — la fermeture par Échap et par un clic au-dehors. Le comportement `dialog` en fait autant
pour la fenêtre modale : Échap la ferme déjà, un clic sur le fond assombri aussi.

### Chiffres clés, initiales, états

Un chiffre clé prend une pastille en posant une icône dans `app-stat-icon`, en tête : le libellé
et le chiffre se rangent alors à sa droite. La pastille prend la couleur décorative de la
statistique, ou son ton s'il y en a un.

```twig
<div class="app-stat" data-hue="1">
    <span class="app-stat-icon"><svg …></svg></span>
    <span class="app-stat-label">Postes</span>
    <span class="app-stat-value">37</span>
</div>
```

Des initiales sur un aplat distinguent deux noms voisins : `<span class="app-avatar" data-hue="3"
data-size="sm">CL</span>`. Une étiquette qui dit un état — en ligne, en panne — prend un point en
tête avec `data-variant="dot"`. Une jauge que l'on compare d'une ligne à l'autre s'épaissit avec
`data-size="lg"`.

### Des chiffres qui s'alignent

Dans la plupart des polices, un « 1 » est plus étroit qu'un « 8 » : deux montants posés l'un sous
l'autre ne tombent pas juste. `app-tabular-nums` donne à chaque chiffre la même largeur, sans
changer de police. Elle se pose sur ce qui s'affiche en colonne — montants, quantités, dates,
numéros :

```twig
<td class="app-tabular-nums">1 280,00 €</td>
```

Un lien téléphone, `<a href="tel:…">`, a d'office des chiffres de même largeur.

La règle demande ces chiffres à la police, qui doit les avoir. La police du système et celle du
thème Multicolore les ont ; celle du thème Magenta n'en connaît pas d'autres. Poppins, celle du
thème Violet, n'en a pas : le thème lui prend ses chiffres dans « Poppins Figures », les dix
chiffres de Poppins recentrés chacun sur la même largeur. Sous Violet, comme sous Magenta, tous
les chiffres s'alignent donc, avec ou sans la classe.

### La densité

Tout espacement est un multiple de `--app-gap`, par une échelle de huit paliers
(`--app-space-2xs` … `--app-space-3xl`). Resserrer l'interface entière tient donc en une ligne,
et aucune règle du catalogue n'est reprise :

```css
:root { --app-gap: 6px; }
```

### Un tableau sur un écran étroit

Un tableau large oblige, sur un téléphone, à le faire glisser de côté pour lire une ligne. Poser
`data-stack` sur le tableau le range, sous 40 em, en lignes de deux lignes de texte, comme un
journal d'appels : le titre et l'action en haut, le reste dessous. La cellule choisit sa place par
`data-slot`.

```twig
<table class="app-table" data-stack>
    <thead>
        <tr><th scope="col">Date</th><th scope="col">Sens</th><th scope="col">Numéro</th><th></th></tr>
    </thead>
    <tbody>
        <tr>
            <td>11/10/2026 18:41</td>
            <td><span class="app-badge">Reçu</span></td>
            <td data-slot="title">06 38 97 11 18</td>
            <td data-slot="aside"><button type="button" class="app-btn" data-size="sm">Écouter</button></td>
        </tr>
    </tbody>
</table>
```

| `data-slot` | Place |
| --- | --- |
| `lead` | en tête de la première ligne, avant le titre : une icône, un repère |
| `title` | la première ligne, en plus gros |
| `aside` | à droite de la première ligne, pour l'action |
| aucun | la seconde ligne, atténuée ; les cellules s'y suivent dans l'ordre de la page |

Sur grand écran, une cellule `lead` est la colonne de tête : elle se serre contre le bord.

Une liste de cent lignes reste ainsi lisible : une ligne en prend deux, et non une par colonne.

Quand l'étiquette de chaque colonne compte, `data-stack="labels"` pose une fiche à la place : chaque
valeur reprend à sa gauche le `data-label` de sa cellule.

L'entête reste dans la page, hors de vue, pour les lecteurs d'écran. Une cellule vide, au sens
strict — `<td></td>`, sans même une espace — disparaît. Au-delà de 40 em, rien ne change : le tableau
reste un tableau.

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

**Le contenu prend toute la largeur à droite du menu** : la colonne centrée, plafonnée par
`--app-content-width`, est celle du squelette sans menu. Pour garder une lecture étroite, un écran
borne lui-même son contenu — un `max-width` sur son conteneur, en citant ce jeton.

**Le menu prend le coin haut gauche**, et toute la hauteur ; l'en-tête commence à sa droite et
ne porte plus que le contexte de la page. C'est la disposition des interfaces dont le menu est
la navigation principale — et la marque suit le coin : elle se rend en tête du menu quand il y
en a un, dans l'en-tête sinon. Le bloc `brand` est le même dans les deux cas.

**Un lien porte son icône** en tête, collée telle quelle dans le `<a>`, et une étiquette en bout
de ligne pour dire ce qui attend derrière lui — `<span class="app-badge" data-variant="solid"
data-tone="danger">8</span>`.

**Un groupe de liens se nomme** par un intertitre posé entre eux — `<span
class="app-nav-heading">Standard</span>`. Un espace et un trait le détachent du groupe qui
précède ; en tête du menu, il n'a rien à détacher.

**Le menu a ses propres couleurs** — fond, liens, texte discret, survol, page en cours, filet,
ombre —, toutes reprises de la page par défaut. Une palette peut ainsi le peindre en aplat ou en
dégradé sans toucher au reste. Ce qu'une application pose elle-même dans le menu prend ces
couleurs-là, `--app-sidebar-ink-muted` pour un texte discret, et non celles de la page : sur un
menu peint, une couleur de page peut devenir illisible.

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

### Les menus de la barre du haut

Un menu de compte, une liste de notifications, un lanceur d'applications : **trois contenus, un
seul mécanisme**. Le déclencheur est un bouton ou un avatar, l'ouverture vient du `<details>`, et
le comportement `menu` ajoute la fermeture par Échap et par un clic au-dehors.

```twig
<details class="app-menu" data-controller="menu">
    <summary class="app-avatar">AM</summary>
    <div class="app-menu-panel">
        <div class="app-menu-head">…</div>   {# qui est connecté, ou de quoi la liste parle #}
        <div class="app-menu-body">…</div>   {# ce qui peut être long, et défile #}
        <a class="app-menu-item" href="…">…</a>
        <hr class="app-menu-separator">
        <button type="button" class="app-menu-item" data-tone="danger">Se déconnecter</button>
    </div>
</details>
```

Ce que le panneau sait faire, et qu'il ne faut pas réécrire :

- **la tête ne défile pas**, le corps si — sa hauteur se règle par `--app-menu-height` ;
- **`app-menu-tiles`** range ses liens en tuiles, trois par ligne — c'est la forme d'un lanceur
  d'applications. Chaque `app-menu-tile` porte une `app-menu-tile-icon` au-dessus de son nom,
  `data-hue` pour distinguer deux voisines, et `aria-current` pour celle où l'on se trouve. Sa
  largeur ne dépasse jamais celle de l'écran ;
- **`data-drop="up"`** l'ouvre vers le haut. C'est ce qu'il faut à un menu de compte posé au bas
  d'un menu latéral : vers le bas, il tomberait sous le bord de la fenêtre ;
- **`data-align="start"`** l'ouvre vers la droite, quand le déclencheur est à gauche ;
- **`app-dot`** sur le déclencheur dit qu'il y a du nouveau, sans dire combien. Le compte, quand
  il compte, s'écrit dans un `app-badge`.

Le choix du thème et du mode se rend par défaut dans la barre, en deux menus côte à côte. Pour
le poser dans son propre menu de compte, une application vide son emplacement et appelle le
contrôle où elle veut — les deux menus ensemble, ou chacun sous sa propre étiquette :

```twig
{% block theme_picker %}{% endblock %}
{# … puis, dans le panneau : #}
{{ block('theme_control') }}
{# … ou séparément : #}
{{ block('theme_select') }}
{{ block('mode_select') }}
```

Ce qui reste à l'application : quels liens, quelles notifications, quel bonjour, quelles icônes.
Le paquet donne le cadre, pas le contenu.

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

### Les comportements

Six gestes que chaque application finit par écrire, et que le navigateur ne fait pas seul. Chacun
s'enregistre sous le nom de son fichier, comme `menu` ou `dialog`.

| Comportement | Ce qu'il fait | Où le poser |
| --- | --- | --- |
| `busy` | le bouton qui a envoyé un formulaire tourne jusqu'à la réponse | un formulaire, ou ce qui en contient plusieurs |
| `autosubmit` | le formulaire part dès qu'un champ change | le formulaire ; le champ dit quand partir |
| `autorefresh` | le cadre se recharge seul | un `<turbo-frame>` |
| `filter` | ne garde que les éléments qui contiennent les mots saisis | ce qui contient le champ et la liste |
| `tabs` | n'affiche qu'un panneau à la fois | ce qui contient les onglets et les panneaux |
| `confirm` | pose une question avant une action qui ne se rattrape pas | un formulaire ou un lien |

```twig
{# Le bouton tourne pendant l'envoi ; la liste s'applique d'elle-même. #}
<form method="post" data-controller="busy autosubmit">
    <select name="sort" data-action="change->autosubmit#submit">…</select>
    <button type="submit" class="app-btn">Enregistrer</button>
</form>

{# Une question avant de supprimer, qui nomme ce qu'elle concerne. #}
<form method="post" data-controller="confirm" data-action="submit->confirm#ask"
      data-confirm-question-value="{{ 'dossier.supprimer.question'|trans({'%nom%': dossier.nom}) }}">
    <button type="submit" class="app-btn" data-tone="danger">Supprimer</button>
</form>

{# Rechargé chaque minute ; 15 secondes sans valeur. #}
<turbo-frame id="etat" data-controller="autorefresh" data-autorefresh-interval-value="60000">…</turbo-frame>

{# Le texte cherché est `data-search` s'il est posé, le texte de l'élément sinon. #}
<div data-controller="filter">
    <input type="search" data-filter-target="input" data-action="filter#apply">
    <ul>
        <li data-filter-target="item">…</li>
    </ul>
    <p role="status" data-filter-target="empty" hidden>Aucun élément ne contient ces mots.</p>
</div>

{# Des liens d'ancre vers les panneaux : l'onglet ouvert s'écrit dans l'adresse. #}
<div data-controller="tabs">
    <nav class="app-tabs" aria-label="Fiche">
        <a class="app-tabs-item" href="#resume" data-tabs-target="link" data-action="tabs#select">Résumé</a>
        <a class="app-tabs-item" href="#historique" data-tabs-target="link" data-action="tabs#select">Historique</a>
    </nav>
    <section id="resume" data-tabs-target="panel">…</section>
    <section id="historique" data-tabs-target="panel">…</section>
</div>
```

Ce qu'ils ne font pas, exprès :

- **`busy` ne suit que les envois que Turbo conduit**, les seuls dont on sait quand ils finissent :
  un formulaire qui télécharge un fichier, sous `data-turbo="false"`, laisserait sinon son bouton
  tourner pour toujours. Le bouton n'est pas désactivé — il ne serait plus transmis ;
- **`autorefresh` attend** quand l'onglet est caché, qu'une fenêtre est ouverte, qu'un champ du
  cadre a le focus ou que le cadre charge déjà : un rechargement ne doit rien faire perdre ;
- **`tabs` laisse tout affiché sans script**, et le lien saute au panneau.

**Le nom d'un comportement du paquet l'emporte sur celui d'une application** : StimulusBundle lit
le dossier du paquet après celui de l'application, et le dernier lu gagne. Une application qui
avait son propre `filter` ou son propre `tabs` le renomme avant de monter de version, ou passe à
celui du paquet.

### Les icônes

Toutes les icônes viennent de [Lucide](https://lucide.dev) (licence ISC). Le paquet n'en livre pas la
bibliothèque : il n'embarque que les quelques-unes dont ses écrans ont besoin, copiées telles quelles
de Lucide — trait de 2, bouts arrondis, couleur `currentColor`. Un projet qui veut une icône la prend
de même sur lucide.dev et la colle en SVG, dans un bouton ou un lien :

```twig
<button type="button" class="app-btn" data-variant="ghost" data-icon aria-label="Fermer">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M18 6 6 18" />
        <path d="m6 6 12 12" />
    </svg>
</button>
```

L'icône seule ne dit rien : `aria-hidden="true"` la cache aux lecteurs d'écran, et le libellé du
bouton — `aria-label` ou texte masqué — parle pour elle.

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
| `--app-theme-label` | Ce que le menu affiche. Sans elle, c'est la traduction `theme.<valeur>` du domaine `design_system` — ce dont vivent les palettes du paquet, qui restent ainsi traduisibles. |
| `--app-theme-ratio` | Le seuil de contraste que la palette se donne, quand il dépasse les 4,5:1 ordinaires. `qa/contrast.py` l'applique à toutes les paires de texte. |

Le paquet lit ces fichiers **à la compilation du conteneur**, jamais à l'exécution : il n'y a ni
analyse de feuille dans une requête, ni liste à tenir à jour à côté. Les dossiers de palettes
sont déclarés au conteneur, y compris quand ils n'existent pas encore — déposer un fichier, ou
créer le dossier qui manquait, suffit à faire recompiler le catalogue.

C'est ce qui fait disparaître toute une famille de pannes : une palette proposée qui ne repeint
rien, ou un bloc que rien ne rend, ne peuvent plus exister — la déclaration et la peinture sont
le même fichier.

**Une palette peut aller au-delà des couleurs de la page.** Elle peint le menu latéral à part
(`--app-sidebar-*`), détache les blocs par l'ombre plutôt que par le trait (`--app-card-line`,
`--app-card-shadow`), donne un dégradé et un halo à l'action principale (`--app-accent-fill`,
`--app-accent-glow`), choisit ses six couleurs décoratives (`--app-hue-*`), sa police, ses rayons.
Une police se déclare dans le même fichier, par un `@font-face` dont le fichier se range à côté :
elle ne se télécharge que lorsque la palette est choisie.

**`color-scheme` dit les modes qu'une palette sait rendre.** `color-scheme: light` ou `dark` la
réserve à l'un des deux ; `color-scheme: light dark` — ou rien, puisqu'elle hérite alors de
`:root` —, avec des alias écrits en `light-dark()`, lui donne les deux, y compris dans les
arrêts d'un dégradé. C'est le mode choisi qui décide ensuite de quel côté la page se lit, et
`qa/contrast.py` mesure chacun des côtés que la palette déclare :

```css
[data-theme="ocean"] { --app-accent: light-dark(#0e7490, #22d3ee); … }
```

Dans un mode qu'elle n'a pas, une palette cède la place au thème par défaut de l'application :
une palette claire seulement, choisie en mode sombre, montre ce thème-là en sombre.

### Où le déposer

- **Dans l'application** — `assets/styles/themes/`, et rien d'autre à faire. `theme_directories`
  en nomme d'autres si besoin ; ils doivent vivre sous les ressources du projet, faute de quoi
  la compilation s'arrête en le disant — une palette qu'AssetMapper ne peut pas servir serait
  proposée sans jamais arriver au navigateur.
- **Dans le paquet** — `public/themes/`, pour une palette qui appartient au design system
  lui-même. C'est là que vivent « contraste renforcé » (`contrast`, en sombre seulement), et trois
  thèmes plus colorés, chacun en clair et en sombre : « violet » (`violet` — menu en aplat
  violet, police Poppins), « magenta » (`magenta` — page en cours et boutons en dégradé, police
  Nunito) et « multicolore » (`multicolor` — quatre couleurs vives, police Montserrat). Leurs
  polices sont dans `public/fonts/`, avec leur licence (SIL Open Font License). Le thème de base,
  celui de `tokens.css`, s'appelle « Indigo » (`indigo`), en clair et en sombre.

  Les chiffres de Poppins y ont leur propre fichier par graisse, `poppins-figures-<graisse>.woff2` :
  un peu plus d'un kilo-octet chacun, sous la licence de Poppins. `tools/poppins_figures.py` les
  produit depuis les graisses livrées, et dit comment le relancer — quand une graisse de Poppins
  est ajoutée ou remplacée.

`theme_stylesheets` couvre l'autre cas : une feuille que l'application sert **déjà** — sa
`app.css`, par exemple — et où une palette est écrite au milieu d'autre chose. Le paquet l'y lit,
mais ne pose aucun lien : ce serait un second téléchargement du même fichier.

Une palette portée par le paquet peut être redéclarée par l'application : les feuilles sont lues
dans l'ordre, celles du paquet en tête.
