<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Theme;

/**
 * Lit dans des feuilles de style les palettes qu'elles déclarent.
 *
 * Une palette est un bloc dont le sélecteur est exactement `[data-theme="…"]`. Ce qui suit le
 * sélecteur — `[data-theme="dark"] .app-card` — repeint un composant sous une palette et n'en
 * déclare aucune.
 *
 * Les commentaires sont retirés avant lecture : un bloc montré en exemple dans une
 * documentation n'est pas une déclaration.
 *
 * Un fichier absent est ignoré : une application peut nommer une feuille qu'elle n'a pas encore
 * écrite sans que rien ne casse.
 */
final class StylesheetThemes
{
    /** La propriété par laquelle un bloc nomme sa palette pour le menu. */
    public const string LABEL = '--app-theme-label';

    /** Le seuil de contraste que la palette se donne. Sans elle, c'est le seuil ordinaire. */
    public const string RATIO = '--app-theme-ratio';

    /**
     * Les propriétés que chaque palette déclare, dans l'ordre où les feuilles les rencontrent.
     *
     * Une palette déclarée dans plusieurs blocs les cumule ; à propriété égale, la dernière lue
     * l'emporte, comme dans le navigateur.
     *
     * @return array<string, array<string, string>> palette => (propriété => valeur)
     */
    public static function declarations(string ...$paths): array
    {
        $palettes = [];

        foreach ($paths as $path) {
            if (!is_file($path) || false === $css = file_get_contents($path)) {
                continue;
            }

            self::scan(self::withoutComments($css), $palettes);
        }

        return $palettes;
    }

    /**
     * Le libellé de chaque palette, ou `null` quand sa feuille n'en déclare pas.
     *
     * @return array<string, ?string> palette => libellé
     */
    public static function labels(string ...$paths): array
    {
        return array_map(
            static fn (array $properties): ?string => isset($properties[self::LABEL])
                ? trim($properties[self::LABEL], " \t\"'")
                : null,
            self::declarations(...$paths),
        );
    }

    /**
     * Parcourt les règles d'une feuille et retient celles dont le sélecteur est une palette.
     *
     * Les règles d'une at-rule sont parcourues à leur tour : une palette déclarée sous
     * `@media` reste une palette.
     *
     * @param array<string, array<string, string>> $palettes
     */
    private static function scan(string $css, array &$palettes): void
    {
        $length = \strlen($css);
        $start = 0;

        for ($i = 0; $i < $length; ++$i) {
            if ('{' !== $css[$i]) {
                continue;
            }

            $prelude = trim(substr($css, $start, $i - $start));
            $end = self::closing($css, $i);
            $body = substr($css, $i + 1, $end - $i - 1);

            if (str_starts_with($prelude, '@')) {
                self::scan($body, $palettes);
            } else {
                foreach (explode(',', $prelude) as $selector) {
                    if (1 === preg_match('/^\[data-theme="([\w-]+)"\]$/', trim($selector), $matches)) {
                        $palettes[$matches[1]] = array_merge($palettes[$matches[1]] ?? [], self::properties($body));
                    }
                }
            }

            $i = $end;
            $start = $i + 1;
        }
    }

    /** L'accolade qui ferme celle ouverte en `$open`, ou la fin de la feuille si elle manque. */
    private static function closing(string $css, int $open): int
    {
        $depth = 0;

        for ($i = $open, $length = \strlen($css); $i < $length; ++$i) {
            if ('{' === $css[$i]) {
                ++$depth;
            } elseif ('}' === $css[$i] && 0 === --$depth) {
                return $i;
            }
        }

        return $length - 1;
    }

    /** @return array<string, string> */
    private static function properties(string $body): array
    {
        preg_match_all('/([-\w]+)\s*:\s*([^;]+)(?:;|$)/', $body, $matches, \PREG_SET_ORDER);

        $properties = [];

        foreach ($matches as $match) {
            $properties[$match[1]] = trim($match[2]);
        }

        return $properties;
    }

    /** Les sauts de ligne sont conservés : ce qui suit garde son numéro de ligne. */
    private static function withoutComments(string $css): string
    {
        return (string) preg_replace_callback(
            '~/\*.*?\*/~s',
            static fn (array $matches): string => str_repeat("\n", substr_count($matches[0], "\n")),
            $css,
        );
    }
}
