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
- **Chaque paire texte/fond atteint son seuil**, mesuré et non estimé. `qa/contrast.py` lit
  `public/tokens.css`, résout les alias et les `light-dark()`, et compare les 22 paires sur les
  trois palettes. La palette à contraste renforcé vise 7:1, les deux autres 4,5:1.
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

Les deux comportements se déclarent dans `importmap.php` :

```php
'design-system/theme' => ['path' => 'bundles/designsystem/controllers/theme_controller.js'],
'design-system/dialog' => ['path' => 'bundles/designsystem/controllers/dialog_controller.js'],
```

puis dans `assets/stimulus_bootstrap.js` :

```js
import ThemeController from 'design-system/theme';
import DialogController from 'design-system/dialog';

app.register('theme', ThemeController);
app.register('dialog', DialogController);
```

## La vitrine

Le catalogue complet, chaque composant dans chacun de ses états, sur une page. Elle n'est pas
joignable tant que l'application ne l'a pas montée — à réserver au développement, dans
`config/routes/dev/design_system.yaml` :

```yaml
design_system:
    resource: '@DesignSystemBundle/config/routes.php'
    type: php
    prefix: /styleguide
```

## Régler

```yaml
# config/packages/design_system.yaml
design_system:
    theme_storage: cookie   # ou « none » : rien n'est retenu
    cookie_name: theme
```

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

Un bloc dans `public/tokens.css`, qui ne reprend que les alias déplacés, et un cas dans l'enum
`Theme` :

```css
[data-theme="ocean"] {
    color-scheme: light;
    --app-accent: #0e7490;
    --app-accent-hover: #155e75;
    --app-accent-soft: #ecfeff;
    --app-accent-ink: #164e63;
}
```

Le menu de choix la propose alors d'elle-même, et `qa/contrast.py` la mesure au prochain
passage.
