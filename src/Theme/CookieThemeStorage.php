<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Relit le choix dans un cookie que le navigateur a posé lui-même.
 *
 * Le cookie n'est ni signé ni chiffré, et ne porte aucune donnée personnelle : une valeur
 * inconnue est traitée comme une absence de choix.
 */
final readonly class CookieThemeStorage implements ThemeStorage
{
    public function __construct(
        private RequestStack $requests,
        private string $cookieName,
    ) {
    }

    public function current(): ?Theme
    {
        $value = $this->requests->getCurrentRequest()?->cookies->get($this->cookieName);

        return \is_string($value) ? Theme::tryFrom($value) : null;
    }
}
