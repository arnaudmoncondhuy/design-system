<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Relit le choix dans deux cookies que le navigateur a posés lui-même : le thème sous
 * `$cookieName`, le mode sous `$cookieName` suivi de `_mode`.
 *
 * Les cookies ne sont ni signés ni chiffrés, et ne portent aucune donnée personnelle : une
 * valeur que le catalogue ne connaît pas est traitée comme une absence de choix.
 */
final readonly class CookieThemeStorage implements ThemeStorage
{
    public function __construct(
        private RequestStack $requests,
        private string $cookieName,
        private ThemeCatalog $catalog,
    ) {
    }

    public function theme(): ?Theme
    {
        $value = $this->cookie($this->cookieName);

        return null === $value ? null : $this->catalog->get($value);
    }

    public function mode(): ?Mode
    {
        $value = $this->cookie($this->cookieName.'_mode');

        return null === $value ? null : Mode::tryFrom($value);
    }

    private function cookie(string $name): ?string
    {
        $value = $this->requests->getCurrentRequest()?->cookies->get($name);

        return \is_string($value) ? $value : null;
    }
}
