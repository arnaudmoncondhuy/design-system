<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Bridge;

use ArnaudMoncondhuy\DesignSystem\Theme\StylesheetThemes;
use ArnaudMoncondhuy\DesignSystem\Theme\Theme;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeCatalog;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Examine une installation et cherche ce qui ne lève rien.
 *
 * Toutes les pannes qu'elle traque ont en commun de ne produire aucune erreur : une palette
 * proposée dans le menu qui ne repeint rien, un jeton mal orthographié dont la propriété tombe
 * en silence, un cookie relu sous un nom que personne n'écrit. Rien de tout cela n'apparaît
 * dans un journal ; seul quelqu'un qui regarde l'écran s'en aperçoit, et encore.
 *
 * Elle échoue plutôt que d'afficher : une routine qualité ne peut s'appuyer que sur ce qui rend
 * un code de sortie.
 *
 * `--fix` n'écrit que ce qui s'ajoute sans rien écraser. Il ne réécrit jamais un fichier
 * existant : deviner qu'un gabarit n'a pas été personnalisé demanderait de connaître toutes les
 * versions qu'un squelette Symfony a pu produire, et se tromper reviendrait à effacer le
 * travail de quelqu'un. Ce qu'il ne peut pas faire, il le dicte.
 */
#[AsCommand(
    name: 'design-system:doctor',
    description: 'Vérifie qu\'une installation du design system se tient.',
)]
final class DoctorCommand extends Command
{
    /** Les feuilles qui consomment les jetons, et qui n'ont donc pas le droit d'en inventer. */
    private const array CONSUMERS = ['base.css', 'components.css', 'styleguide.css'];

    /** @param list<string> $stylesheets les feuilles où les palettes se déclarent */
    public function __construct(
        private readonly string $projectDirectory,
        private readonly string $cookieName,
        private readonly array $stylesheets,
        private readonly ThemeCatalog $catalog,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'fix',
            null,
            InputOption::VALUE_NONE,
            'Écrit ce qui manque et peut être ajouté sans rien écraser.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $console = new SymfonyStyle($input, $output);
        $fix = true === $input->getOption('fix');

        $console->writeln(\sprintf('Paquet  : %s', $this->bundleDirectory()));
        $console->writeln(\sprintf('Projet  : %s', $this->projectDirectory));
        $console->writeln(\sprintf('Cookie  : %s', $this->cookieName));
        $console->writeln(\sprintf('Feuilles: %s', implode(', ', $this->stylesheets)));
        $console->writeln(\sprintf('Palettes: %s', implode(', ', array_map(
            static fn (Theme $theme): string => $theme->value.(null === $theme->label ? '' : ' ('.$theme->label.')'),
            $this->catalog->all(),
        )) ?: 'aucune'));

        $installation = $this->installation($fix);

        $troubles = [
            'Palettes' => $this->palettes(),
            'Jetons' => $this->tokens(),
            'Couleurs en dur' => $this->hardcodedColors(),
            'Traductions' => $this->duplicatedKeys(),
            'Cookie' => $this->cookie(),
            'Installation' => $installation['troubles'],
        ];

        foreach ($installation['written'] as $file) {
            $console->success(\sprintf('Écrit : %s', $file));
        }

        $found = 0;

        foreach ($troubles as $title => $lines) {
            if ([] === $lines) {
                continue;
            }

            $found += \count($lines);
            $console->section($title);

            foreach ($lines as $line) {
                $console->writeln('  '.$line);
            }
        }

        if (0 === $found) {
            $console->success('Rien à signaler.');

            return Command::SUCCESS;
        }

        $console->error(\sprintf('%d point(s) à corriger.', $found));

        if (!$fix) {
            $console->writeln('`--fix` écrit ce qui peut l\'être sans rien écraser.');
        }

        return Command::FAILURE;
    }

    /**
     * Un bloc de palette qui ne déplace aucun alias se choisit sans que rien ne change, et une
     * palette dont personne ne donne le libellé s'affiche sous sa clé de traduction.
     *
     * @return list<string>
     */
    private function palettes(): array
    {
        $troubles = [];

        foreach (StylesheetThemes::declarations(...$this->stylesheets) as $value => $properties) {
            // `color-scheme` seul suffit à repeindre : il bascule tous les `light-dark()` d'un coup.
            $moved = array_filter(
                array_keys($properties),
                static fn (string $property): bool => 'color-scheme' === $property
                    || (str_starts_with($property, '--app-') && !str_starts_with($property, '--app-theme-')),
            );

            if ([] === $moved) {
                $troubles[] = \sprintf(
                    '[data-theme="%s"] ne déplace ni alias ni color-scheme : la palette se choisit, et la page reste la même.',
                    $value,
                );
            }
        }

        foreach ($this->catalog->all() as $theme) {
            if (null === $theme->label && !$this->translated($theme->value)) {
                $troubles[] = \sprintf(
                    '« %s » n\'a ni %s dans sa feuille ni traduction theme.%s : le menu affichera la clé.',
                    $theme->value,
                    StylesheetThemes::LABEL,
                    $theme->value,
                );
            }
        }

        return $troubles;
    }

    /**
     * Le domaine `design_system` fixe le nom des fichiers qui le portent : les chercher sous ce
     * nom, dans le paquet et dans le projet, couvre tout endroit où une traduction peut vivre.
     */
    private function translated(string $value): bool
    {
        foreach ($this->translationFiles() as $file) {
            $content = (string) file_get_contents($file);

            if (1 !== preg_match('/^theme:\R((?:[ \t]+\S.*\R?)+)/m', $content, $block)) {
                continue;
            }

            if (1 === preg_match('/^\s+'.preg_quote($value, '/').'\s*:/m', $block[1])) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function translationFiles(): array
    {
        $files = [];

        foreach ([$this->bundleDirectory().'/translations', $this->projectDirectory.'/translations'] as $directory) {
            $files = [...$files, ...glob($directory.'/design_system.*.y*ml') ?: []];
        }

        return $files;
    }

    /**
     * Une clé déclarée deux fois sous le même parent ne lève rien : le catalogue ne garde que la
     * dernière, et le libellé de la première disparaît de l'écran sans que personne l'ait retiré.
     *
     * La lecture est textuelle et non structurelle, faute de quoi il n'y aurait plus rien à
     * voir : un analyseur YAML rend le catalogue déjà écrasé.
     *
     * @return list<string>
     */
    private function duplicatedKeys(): array
    {
        $troubles = [];

        foreach ($this->translationFiles() as $file) {
            $seen = [];
            $parents = [];

            foreach (explode("\n", (string) file_get_contents($file)) as $number => $line) {
                if (1 !== preg_match('/^([ ]*)([\w-]+)\s*:/', $line, $matches)) {
                    continue;
                }

                $depth = \strlen($matches[1]);

                foreach (array_keys($parents) as $known) {
                    if ($known >= $depth) {
                        unset($parents[$known]);
                    }
                }

                $key = implode('.', [...$parents, $matches[2]]);
                $parents[$depth] = $matches[2];

                if (isset($seen[$key])) {
                    $troubles[] = \sprintf(
                        '%s:%d déclare « %s » une seconde fois — la ligne %d est effacée en silence.',
                        basename($file),
                        $number + 1,
                        $key,
                        $seen[$key],
                    );
                }

                $seen[$key] = $number + 1;
            }
        }

        return $troubles;
    }

    /**
     * Un jeton cité mais jamais défini ne lève pas : la propriété tombe, et la couleur
     * disparaît de l'écran sans que rien ne le dise.
     *
     * @return list<string>
     */
    private function tokens(): array
    {
        preg_match_all('/(--app-[\w-]+)\s*:/', $this->read('tokens.css'), $defined);
        $known = array_unique($defined[1]);

        $troubles = [];

        foreach (self::CONSUMERS as $sheet) {
            // Sans valeur de repli seulement : `var(--x, …)` déclare une variable que l'instance
            // pose elle-même, et son absence de la feuille de jetons est alors voulue.
            preg_match_all('/var\(\s*(--app-[\w-]+)\s*\)/', $this->read($sheet), $cited);

            foreach (array_unique(array_diff($cited[1], $known)) as $unknown) {
                $troubles[] = \sprintf('%s cite %s, que tokens.css ne définit pas.', $sheet, $unknown);
            }
        }

        return $troubles;
    }

    /**
     * Une couleur écrite dans un composant échappe aux palettes : elle reste la même quand tout
     * le reste change, y compris sur la palette à contraste renforcé.
     *
     * @return list<string>
     */
    private function hardcodedColors(): array
    {
        $troubles = [];

        foreach (self::CONSUMERS as $sheet) {
            foreach (explode("\n", $this->read($sheet)) as $number => $line) {
                if (preg_match('/#[0-9a-fA-F]{3,8}\b|\b(?:rgba?|hsla?)\(/', $line)) {
                    $troubles[] = \sprintf('%s:%d écrit une couleur au lieu d\'un alias : %s', $sheet, $number + 1, trim($line));
                }
            }
        }

        return $troubles;
    }

    /**
     * Le serveur relit un cookie que le navigateur écrit. Deux noms qui divergent ne produisent
     * aucune erreur : le choix est simplement perdu à chaque rechargement.
     *
     * @return list<string>
     */
    private function cookie(): array
    {
        $written = preg_match(
            '/cookie:\s*\{[^}]*default:\s*\'([^\']+)\'/',
            $this->read('controllers/theme_controller.js'),
            $matches,
        ) ? $matches[1] : null;

        if (null === $written) {
            return ['le contrôleur theme_controller.js ne déclare plus de nom de cookie par défaut.'];
        }

        if ($written !== $this->cookieName) {
            return [\sprintf(
                'le serveur relit « %s », le navigateur écrit « %s » : le choix de palette ne survit pas au rechargement.',
                $this->cookieName,
                $written,
            )];
        }

        return [];
    }

    /**
     * Ce que l'application doit poser de sa main, et que le paquet ne peut pas poser à sa place.
     *
     * @return array{troubles: list<string>, written: list<string>}
     */
    private function installation(bool $fix): array
    {
        $written = [];
        $troubles = [];

        $base = $this->projectDirectory.'/templates/base.html.twig';

        if (!is_file($base)) {
            if ($fix) {
                $this->write($base, "{% extends '@DesignSystem/layout.html.twig' %}\n");
                $written[] = 'templates/base.html.twig';
            } else {
                $troubles[] = 'templates/base.html.twig est absent.';
            }
        } elseif (!str_contains((string) file_get_contents($base), '@DesignSystem/layout.html.twig')) {
            $troubles[] = 'templates/base.html.twig n\'étend pas le squelette du paquet. Y écrire :';
            $troubles[] = "    {% extends '@DesignSystem/layout.html.twig' %}";
            $troubles[] = '  Ce fichier existe : il n\'est pas réécrit, pour ne pas effacer ce qu\'il contient.';
        }

        if (!$this->routeIsMounted()) {
            $file = $this->projectDirectory.'/config/routes/dev/design_system.yaml';

            if ($fix) {
                $this->write($file, <<<'YAML'
                    # La vitrine du design system, réservée au développement : une page qui montre tout
                    # le catalogue n'a rien à faire sur un site en production.
                    design_system:
                        resource: '@DesignSystemBundle/config/routes.php'
                        type: php
                        prefix: /styleguide

                    YAML);
                $written[] = 'config/routes/dev/design_system.yaml';
            } else {
                $troubles[] = 'la vitrine n\'est montée sur aucune route. `--fix` écrit config/routes/dev/design_system.yaml.';
            }
        }

        return ['troubles' => $troubles, 'written' => $written];
    }

    /** Cherche le paquet dans les fichiers de routage de l'application, quel que soit leur nom. */
    private function routeIsMounted(): bool
    {
        $directory = $this->projectDirectory.'/config';

        if (!is_dir($directory)) {
            return false;
        }

        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)),
            '/routes.*\.(yaml|yml|php)$/',
        );

        foreach ($files as $file) {
            if (str_contains((string) file_get_contents((string) $file), 'DesignSystemBundle')) {
                return true;
            }
        }

        return false;
    }

    /** La racine du paquet : ce fichier vit dans `src/Bridge/`. */
    private function bundleDirectory(): string
    {
        return \dirname(__DIR__, 2);
    }

    /**
     * Rend la feuille débarrassée de ses commentaires : ce qu'ils contiennent est du texte, et
     * un exemple qu'on y montre n'est pas une déclaration.
     */
    private function read(string $file): string
    {
        $path = $this->bundleDirectory().'/public/'.$file;
        $content = is_file($path) ? file_get_contents($path) : false;

        if (false === $content) {
            throw new \RuntimeException(\sprintf('Le paquet est incomplet : %s est introuvable.', $path));
        }

        // Les sauts de ligne sont conservés : les numéros de ligne rapportés restent justes.
        return (string) preg_replace_callback(
            '~/\*.*?\*/~s',
            static fn (array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
            $content,
        );
    }

    private function write(string $path, string $content): void
    {
        $directory = \dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Impossible de créer %s.', $directory));
        }

        if (false === file_put_contents($path, $content)) {
            throw new \RuntimeException(\sprintf('Impossible d\'écrire %s.', $path));
        }
    }
}
