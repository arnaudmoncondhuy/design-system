<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Ne retient aucun choix : `<html>` sort sans `data-theme`, et la préférence du système
 * s'applique à chaque chargement.
 *
 * Le menu de choix continue de fonctionner dans la page ; son effet ne survit pas au
 * rechargement.
 */
final readonly class NoThemeStorage implements ThemeStorage
{
    public function current(): ?Theme
    {
        return null;
    }
}
