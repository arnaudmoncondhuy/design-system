<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Un thème proposable.
 *
 * `value` est ce que `<html data-theme="…">` porte, et ce qu'un bloc `[data-theme="…"]` d'une
 * feuille reconnaît : les deux viennent de la même déclaration, et ne peuvent donc pas diverger.
 *
 * `label` est ce que le menu affiche, quand la feuille l'a déclaré par `--app-theme-label`. Sans
 * lui, le gabarit se rabat sur la traduction `theme.<valeur>` du domaine `design_system`.
 *
 * `modes` dit ce que le thème sait rendre, d'après le `color-scheme` de sa feuille : le clair, le
 * sombre, ou les deux.
 */
final readonly class Theme
{
    /** @param list<Mode> $modes */
    public function __construct(
        public string $value,
        public ?string $label = null,
        public array $modes = [Mode::Light, Mode::Dark],
    ) {
    }

    public function has(Mode $mode): bool
    {
        return \in_array($mode, $this->modes, true);
    }
}
