<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Le côté de `light-dark()` sur lequel la page se lit.
 *
 * `Auto` laisse la préférence du système trancher. C'est un choix que le visiteur exprime, et
 * jamais un mode qu'un thème déclare : un thème sait rendre `Light`, `Dark`, ou les deux.
 *
 * La valeur est ce que `<html data-mode="…">` porte, et ce que le cookie retient.
 */
enum Mode: string
{
    case Auto = 'auto';
    case Light = 'light';
    case Dark = 'dark';
}
