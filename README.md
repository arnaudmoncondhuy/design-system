# arnaudmoncondhuy/design-system

Des jetons en **deux étages**, un catalogue de composants, et des palettes qui se déclarent
sans qu'aucun composant ne les connaisse — dont une à contraste renforcé, destinée à la basse
vision.

```css
/* Étage 1 — les échelles. Aucun composant ne les cite. */
--app-indigo-600: #4f46e5;

/* Étage 2 — les alias. Un rôle, pas une couleur. C'est le seul étage qu'un composant emploie. */
--app-accent: light-dark(var(--app-indigo-600), var(--app-indigo-400));
```

Une palette se change donc en réécrivant l'étage 2. Ajouter « contraste renforcé » a coûté
quarante lignes et n'a touché aucun composant.

## Ce que le paquet garantit

- **Aucun composant ne connaît de couleur.** Une règle qui écrit un `#rrggbb` est une faute que
  la relecture attrape ; tout passe par un alias.
- **Chaque paire texte/fond atteint son seuil**, mesuré et non estimé. `qa/contrast.py` lit les
  feuilles qu'on lui donne, y découvre les palettes, résout les alias et les `light-dark()`, et
  compare les 22 paires sur chacune. Une palette vise 4,5:1, ou le seuil qu'elle se donne par
  `--app-theme-ratio`. Pour y joindre celles d'une application :

  ```
  python3 qa/contrast.py public/tokens.css public/themes/*.css …/assets/styles/themes/*.css
  ```
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

**Il n'y a rien d'autre à écrire.** Le paquet ajoute lui-même son dossier de comportements à
ceux que StimulusBundle balaye : ses contrôleurs s'enregistrent sous le nom de leur fichier, et
l'application n'a ni entrée d'`importmap` à poser, ni contrôleur à enregistrer.

## Le docteur

```
php bin/console design-system:doctor
```

Il cherche ce qui ne lève jamais : un bloc de palette qui ne déplace rien, une palette dont
personne ne donne le libellé et qui s'affiche sous sa clé, un jeton mal orthographié dont la
propriété tombe en silence, une couleur écrite en dur qui échappe aux palettes, un cookie relu
sous un nom que personne n'écrit. Il rend un code de sortie, donc il a sa place dans une
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

Une classe déclare le composant, un attribut décline ce qu'il devient : `data-variant` et
`data-tone` portent l'intention, `data-size` la taille, et les attributs standards — `disabled`,
`aria-busy`, `aria-current` — portent l'état.

```html
<button class="app-btn" data-variant="danger" data-size="sm">Supprimer</button>
<span class="app-badge" data-tone="success">Terminé</span>
<p class="app-alert" data-tone="warning" role="status">…</p>
```

Boutons, cartes, étiquettes, formulaires, tableaux, messages, fenêtre modale, onglets, avatar,
grilles. Le préfixe `app-` se renomme d'un remplacement, dans `public/` et dans
`templates/layout.html.twig`.

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
