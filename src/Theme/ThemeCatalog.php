<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Les thèmes que l'application propose, dans l'ordre du menu, et celui qui sert par défaut.
 *
 * Le catalogue est construit à la compilation du conteneur, à partir des feuilles de style
 * nommées par la configuration : à l'exécution, plus rien n'est lu ni analysé.
 *
 * Un thème qui n'y figure pas est traité comme une absence de choix — un cookie qui le nomme
 * encore laisse donc le thème par défaut s'appliquer, sans erreur.
 *
 * Le thème par défaut a les deux modes, ce que la compilation vérifie : c'est lui qui s'affiche
 * quand le thème choisi n'a pas le mode en vigueur.
 */
final readonly class ThemeCatalog
{
    /** @var list<Theme> */
    private array $themes;

    private Theme $default;

    /**
     * @param array<string, array{label: ?string, modes: list<string>}> $themes  valeur => ce que la feuille déclare
     * @param string                                                     $default la valeur du thème par défaut
     */
    public function __construct(array $themes, string $default)
    {
        $this->themes = array_map(
            static fn (string $value, array $theme): Theme => new Theme(
                $value,
                $theme['label'],
                array_map(Mode::from(...), $theme['modes']),
            ),
            array_keys($themes),
            array_values($themes),
        );

        $this->default = $this->get($default) ?? throw new \InvalidArgumentException(\sprintf(
            'Le thème par défaut « %s » ne figure pas au catalogue.',
            $default,
        ));
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

    public function default(): Theme
    {
        return $this->default;
    }

    /** Le thème qui s'affiche quand `$theme` est choisi et que la page se lit en `$mode`. */
    public function shownIn(Theme $theme, Mode $mode): Theme
    {
        return $theme->has($mode) ? $theme : $this->default;
    }

    /** Ce que la page montre pour ce choix de thème et ce mode. */
    public function appearance(Theme $theme, Mode $mode): Appearance
    {
        return new Appearance(
            $theme,
            $mode,
            $this->shownIn($theme, Mode::Light),
            $this->shownIn($theme, Mode::Dark),
        );
    }
}
