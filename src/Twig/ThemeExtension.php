<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Twig;

use ArnaudMoncondhuy\DesignSystem\Theme\Theme;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeCatalog;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeStorage;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Ce que les gabarits savent de la palette : celle qui est en vigueur, et la liste de celles
 * qu'on peut proposer.
 */
final class ThemeExtension extends AbstractExtension
{
    /** @param list<string> $stylesheets les feuilles de palette, en chemins d'AssetMapper */
    public function __construct(
        private readonly ThemeStorage $storage,
        private readonly ThemeCatalog $catalog,
        private readonly array $stylesheets = [],
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_theme', $this->currentTheme(...)),
            new TwigFunction('available_themes', $this->availableThemes(...)),
            new TwigFunction('theme_stylesheets', $this->themeStylesheets(...)),
        ];
    }

    /**
     * La valeur à poser dans `<html data-theme="…">`, ou `null` quand aucun choix n'a été
     * exprimé — l'attribut est alors à omettre, et non à rendre vide.
     */
    public function currentTheme(): ?string
    {
        return $this->storage->current()?->value;
    }

    /**
     * Les palettes proposables, dans l'ordre du menu. L'absence de choix n'en fait pas partie :
     * c'est au gabarit d'ouvrir la liste par l'option qui rend la main au système.
     *
     * @return list<Theme>
     */
    public function availableThemes(): array
    {
        return $this->catalog->all();
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
