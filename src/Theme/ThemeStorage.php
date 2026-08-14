<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * D'où vient la palette choisie par le visiteur.
 *
 * Le contrat ne sait que lire : c'est le navigateur qui enregistre le choix, et le serveur qui
 * le relit au chargement suivant pour rendre `data-theme` sans que la page clignote.
 *
 * Une application qui range ce choix ailleurs — le champ d'un compte connecté — substitue son
 * implémentation à celle du paquet, sans rien changer d'autre.
 *
 * `null` signifie qu'aucun choix n'a été exprimé : la préférence du système s'applique alors.
 */
interface ThemeStorage
{
    public function current(): ?Theme;
}
