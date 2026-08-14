<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Les palettes que l'application propose, dans l'ordre du menu.
 *
 * Le catalogue est construit à la compilation du conteneur, à partir des feuilles de style
 * nommées par la configuration : à l'exécution, plus rien n'est lu ni analysé.
 *
 * Une palette qui n'y figure pas est traitée comme une absence de choix — un cookie qui la
 * nomme encore laisse donc la préférence du système trancher, sans erreur.
 */
final readonly class ThemeCatalog
{
    /** @var list<Theme> */
    private array $themes;

    /** @param array<string, ?string> $themes valeur => libellé déclaré par la feuille */
    public function __construct(array $themes)
    {
        $this->themes = array_map(
            static fn (string $value, ?string $label): Theme => new Theme($value, $label),
            array_keys($themes),
            array_values($themes),
        );
    }

    /** @return list<Theme> */
    public function all(): array
    {
        return $this->themes;
    }

    public function get(string $value): ?Theme
    {
        foreach ($this->themes as $theme) {
            if ($theme->value === $value) {
                return $theme;
            }
        }

        return null;
    }
}
