<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Ce que la page montre : le thème choisi, le mode, et le thème qui s'affiche de chaque côté.
 *
 * `light` et `dark` sont le thème choisi quand il a ce mode, et le thème par défaut sinon. Un
 * thème aux deux modes les remplit tous deux de lui-même.
 */
final readonly class Appearance
{
    public function __construct(
        public Theme $theme,
        public Mode $mode,
        public Theme $light,
        public Theme $dark,
    ) {
    }

    /**
     * Le thème que `<html data-theme="…">` porte à l'envoi de la page.
     *
     * En automatique, c'est le thème choisi : son propre `color-scheme` le fige du côté qu'il
     * sait rendre, et la page reste juste même sans script.
     */
    public function shown(): Theme
    {
        return match ($this->mode) {
            Mode::Light => $this->light,
            Mode::Dark => $this->dark,
            Mode::Auto => $this->theme,
        };
    }

    /**
     * Vrai quand seul le navigateur peut dire quel thème afficher : en automatique, le thème
     * choisi n'a qu'un mode, et le système peut réclamer l'autre.
     */
    public function followsSystem(): bool
    {
        return Mode::Auto === $this->mode && $this->light->value !== $this->dark->value;
    }
}
