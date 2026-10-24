<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Twig;

use ArnaudMoncondhuy\DesignSystem\Theme\Appearance;
use ArnaudMoncondhuy\DesignSystem\Theme\Mode;
use ArnaudMoncondhuy\DesignSystem\Theme\Theme;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeCatalog;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeStorage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Ce que les gabarits savent du thème et du mode : ce qui est en vigueur, et ce qu'on peut
 * proposer.
 */
final class ThemeExtension extends AbstractExtension
{
    /**
     * @param list<string> $stylesheets les feuilles de palette, en chemins d'AssetMapper
     * @param string       $defaultMode le mode d'ouverture de l'application
     */
    public function __construct(
        private readonly ThemeStorage $storage,
        private readonly ThemeCatalog $catalog,
        private readonly array $stylesheets = [],
        private readonly string $defaultMode = 'auto',
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('theme_appearance', $this->appearance(...)),
            new TwigFunction('theme_shown_in', $this->shownIn(...)),
            new TwigFunction('available_themes', $this->availableThemes(...)),
            new TwigFunction('theme_modes', $this->modes(...)),
            new TwigFunction('theme_stylesheets', $this->themeStylesheets(...)),
        ];
    }

    /**
     * Le choix du visiteur, sinon le thème et le mode d'ouverture de l'application — chacun des
     * deux pour son compte : un visiteur qui n'a choisi que le mode garde le thème par défaut.
     */
    public function appearance(): Appearance
    {
        return $this->catalog->appearance(
            $this->storage->theme() ?? $this->catalog->default(),
            $this->storage->mode() ?? Mode::tryFrom($this->defaultMode) ?? Mode::Auto,
        );
    }

    /**
     * La valeur du thème qui s'affiche quand `$theme` est choisi et que la page se lit en
     * `$mode` — `light` ou `dark`. Le menu la donne à chaque option, pour que le changement se
     * fasse dans la page sans la recharger.
     */
    public function shownIn(Theme $theme, string $mode): string
    {
        return $this->catalog->shownIn($theme, Mode::from($mode))->value;
    }

    /**
     * Les palettes proposables, dans l'ordre du menu.
     *
     * @return list<Theme>
     */
    public function availableThemes(): array
    {
        return $this->catalog->all();
    }

    /**
     * Les modes proposables, dans l'ordre du menu : automatique, clair, sombre.
     *
     * @return list<Mode>
     */
    public function modes(): array
    {
        return Mode::cases();
    }

    /**
     * Les feuilles de palette que le gabarit pose lui-même : un fichier déposé dans un dossier
     * de palettes est servi sans que l'application ait de lien à écrire.
     *
     * @return list<string>
     */
    public function themeStylesheets(): array
    {
        return $this->stylesheets;
    }
}
