<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * D'où viennent le thème et le mode choisis par le visiteur.
 *
 * Le contrat ne sait que lire : c'est le navigateur qui enregistre le choix, et le serveur qui
 * le relit au chargement suivant pour rendre `data-theme` et `data-mode` sans que la page
 * clignote.
 *
 * Une application qui range ce choix ailleurs — le champ d'un compte connecté — substitue son
 * implémentation à celle du paquet, sans rien changer d'autre.
 *
 * `null` signifie qu'aucun choix n'a été exprimé : le thème ou le mode par défaut de
 * l'application s'applique alors. Les deux choix sont indépendants.
 */
interface ThemeStorage
{
    public function theme(): ?Theme;

    public function mode(): ?Mode;
}
