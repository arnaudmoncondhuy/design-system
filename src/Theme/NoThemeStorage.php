<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Ne retient aucun choix : `<html>` sort avec le thème et le mode par défaut de l'application à
 * chaque chargement.
 *
 * Les menus de choix continuent de fonctionner dans la page ; leur effet ne survit pas au
 * rechargement.
 */
final readonly class NoThemeStorage implements ThemeStorage
{
    public function theme(): ?Theme
    {
        return null;
    }

    public function mode(): ?Mode
    {
        return null;
    }
}
