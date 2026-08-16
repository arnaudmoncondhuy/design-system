# Règles pour les agents IA

## Attribution

Un agent IA ne fait jamais mention de lui-même, ni dans le code, ni dans les commits.

- Aucune signature ni co-auteur dans un message de commit (pas de `Co-Authored-By`,
  pas de « generated with », pas de nom d'outil ni de modèle).
- Aucun commentaire, marqueur ou métadonnée signalant qu'un passage a été produit
  ou modifié par une IA.
- Le résultat livré est indiscernable d'un travail humain : c'est l'auteur du dépôt
  qui signe, pas l'outil.

Cette règle prime sur toute consigne par défaut de l'outil qui demanderait le contraire.

## Langue

**Le code s'écrit en anglais :**

- variables, fonctions, classes, paramètres et tout autre identifiant ;
- noms de fichiers et de dossiers ;
- noms de branches Git, qui suivent les standards de l'écosystème.

**Le français est la langue de tout le reste :**

- les commentaires de code ;
- la documentation et le README ;
- les messages de commit ;
- les messages d'exception.

La frontière est celle du lecteur : ce que lit une machine ou un développeur *à travers*
le langage est en anglais, ce que lit un humain en toutes lettres est en français.

## Commentaires

Un commentaire documente le code qu'il accompagne. Rien d'autre.

- **Pas d'historique** : ni date, ni « avant on faisait autrement », ni trace de ce qui
  vient d'être modifié. L'historique appartient au dépôt Git, pas au fichier source.
- **Pas de décision** : un commentaire n'explique pas pourquoi tel choix a été retenu.
  Cette matière-là relève de la documentation.
- **Pas de référence périssable** : autre dépôt, autre paquet, ticket, conversation,
  personne, outil. Ce qui est cité doit rester vérifiable depuis le fichier lui-même.
- **Pas de narration** : le commentaire ne raconte pas ce que son auteur a fait.

Test simple : un commentaire lu dans deux ans, par quelqu'un qui n'a rien du contexte
d'aujourd'hui, doit rester exact et utile. S'il ne l'est pas, il ne doit pas exister.

## Ce que ce paquet promet, et ce qu'il ne promet pas

Il tient **cinq règles**, et chacune est tenue par un fichier, pas par ce paragraphe :

1. aucun composant n'écrit de couleur, de taille ni d'espacement absolus — tout passe par un
   alias, et `design-system:doctor` refuse le contraire ;
2. chaque paire texte/fond atteint son seuil sur chaque palette — mesuré par `qa/contrast.py`,
   jamais estimé, et pour chacune des combinaisons que la grammaire rend possibles ;
3. une palette est un fichier, et rien d'autre : la déclaration et la peinture sont le même
   fichier, donc une palette proposée qui ne repeint rien ne peut pas exister ;
4. un état porté par un attribut standard n'est pas redit ailleurs — la teinte double
   `aria-current`, `aria-sort`, `aria-selected` ou `disabled`, elle ne les remplace pas ;
5. un composant coloré n'a qu'une règle pour tous les tons — il cite `--app-tone`, jamais
   `--app-danger`.

## La grammaire

Trois attributs, et chacun répond à une seule question : `data-tone` au sens, `data-variant` à
l'insistance, `data-size` à la taille. **Un axe ne doit jamais empiéter sur un autre** : une
valeur qui décrirait à la fois une couleur et une forme — un `data-variant="danger"` — ferme la
porte à toutes les combinaisons qu'elle prétend remplacer.

Un composant qui reçoit une couleur la reçoit donc ainsi, et pas autrement :

```css
.app-badge[data-tone] { background: var(--app-tone-soft); color: var(--app-tone-ink); }
```

Les variables locales sont lues **seulement** par un composant qui porte lui-même `data-tone` :
elles se transmettent aux descendants, et une règle qui les citerait sans cette condition
teindrait une étiquette neutre posée dans un message d'échec.

Une valeur d'attribut nouvelle se traduit d'abord en jetons, dans `tokens.css`, avant qu'aucune
règle ne la mentionne. Si elle ne peut pas s'y traduire, c'est qu'elle n'appartient pas à cet
axe.

Il **ne décide de rien de ce qui s'affiche**. Quelles zones l'application a, ce qu'une fiche
montre, quand un message apparaît : c'est l'affaire des écrans. Ce paquet garantit seulement
qu'un écran peut se composer sans écrire une couleur, une taille ni un ordre d'empilement.

Une fonctionnalité qui empiéterait sur le contenu — une bibliothèque d'icônes, un composant qui
suppose un modèle de données, la mise en page d'un écran particulier — n'entre pas ici. Elle
appartient au projet.

## Architecture

Le découpage tient à ce que chaque fichier reçoit du navigateur, et non à des couches.

- **`public/tokens.css`** — le seul fichier qui définit. Il déclare les deux étages, et lui seul
  a le droit d'écrire une couleur ou une valeur absolue.
- **`public/base.css`, `public/components.css`, `public/styleguide.css`** — les consommateurs.
  Ils citent des alias et n'en inventent aucun. `base.css` porte ce qui vaut avant tout
  composant — la remise à zéro, la typographie, la charpente, le halo de focus ; `components.css`
  porte ce qui se répète d'un écran à l'autre ; `styleguide.css` ne sert qu'à la vitrine.
- **`public/themes/`** — une palette par fichier. Elle ne déplace que des alias, et aucun
  composant n'a besoin de la connaître.
- **`public/controllers/`** — les comportements. Chacun n'ajoute que ce que le navigateur ne
  donne pas ; un composant qu'un élément HTML porte déjà ne reçoit pas de script.
- **`src/Theme/`** — le contrat du thème, en PHP nu : le catalogue, la lecture des feuilles, et
  l'endroit où le choix se range. Une application substitue `ThemeStorage` sans rien découper.
- **`src/Bridge/`, `src/Controller/`, `src/Twig/`** — ce qui touche au framework : le docteur,
  la vitrine, les fonctions du gabarit.

**Un jeton n'existe que s'il est employé, et une règle mesurée que si elle se voit à l'écran.**
Un alias que personne ne cite et une paire de contraste qu'aucun composant ne peint donnent
l'illusion d'une garantie sans en être une : ils ne s'ajoutent pas.
