<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Les palettes qu'un visiteur peut choisir.
 *
 * La valeur est celle que `<html data-theme="…">` porte, et que `tokens.css` reconnaît : une
 * palette ajoutée à la feuille sans l'être ici n'est jamais rendue, et l'inverse laisse la page
 * sur la palette claire, sans erreur.
 *
 * L'absence de choix n'est pas un cas : elle se représente par `null`, et laisse alors la
 * préférence du système trancher.
 */
enum Theme: string
{
    case Light = 'light';
    case Dark = 'dark';

    /** Palette à contraste renforcé, destinée à la basse vision. */
    case Contrast = 'contrast';
}
