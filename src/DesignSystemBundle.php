<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem;

use ArnaudMoncondhuy\DesignSystem\Theme\StylesheetThemes;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Le point de montage du paquet.
 *
 * Reste à la racine de `src/` : {@see AbstractBundle::getPath()} calcule le chemin du paquet en
 * remontant de deux dossiers depuis ce fichier, et c'est ce qui rend `../config/` juste.
 *
 * Le nom de la classe détermine deux espaces de noms que l'application emploie sans les
 * déclarer : `@DesignSystem` pour les gabarits, et `bundles/designsystem` pour les feuilles de
 * `public/`, qu'AssetMapper enregistre seul. Le renommer déplace les deux.
 */
final class DesignSystemBundle extends AbstractBundle
{
    /** Le nom du cookie relu, tel que `config/services.php` l'injecte à l'adaptateur. */
    public const string COOKIE_PARAMETER = 'design_system.cookie_name';

    /** Les palettes retenues : valeur => libellé déclaré par sa feuille, ou `null`. */
    public const string THEMES_PARAMETER = 'design_system.themes';

    /** Les feuilles où les palettes se déclarent, celle du paquet en tête. */
    public const string STYLESHEETS_PARAMETER = 'design_system.theme_stylesheets';

    /** Les feuilles de palette que le gabarit doit faire servir, en chemins d'AssetMapper. */
    public const string LINKS_PARAMETER = 'design_system.theme_links';

    /**
     * Quatre clés, et chacune ne sert qu'à restreindre : sans configuration, le paquet monte la
     * lecture par cookie, propose toutes les palettes que les feuilles déclarent, et n'expose
     * aucune route.
     */
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->enumNode('theme_storage')
                    ->values(['cookie', 'none'])
                    ->defaultValue('cookie')
                    ->info(
                        "D'où se relit la palette choisie. « cookie » : le navigateur la retient, sans base ni "
                        ."compte. « none » : rien n'est retenu, la préférence du système s'applique toujours. Pour "
                        ."la lire ailleurs — le champ d'un compte connecté —, laisser cette clé et substituer le "
                        .'service `ArnaudMoncondhuy\DesignSystem\Theme\ThemeStorage` dans son propre services.yaml.'
                    )
                ->end()
                ->scalarNode('cookie_name')
                    ->defaultValue('theme')
                    ->cannotBeEmpty()
                    ->info(
                        'Le nom du cookie relu. Doit rester celui que le contrôleur Stimulus écrit : un désaccord '
                        ."ne produit aucune erreur, seulement un choix jamais relu."
                    )
                ->end()
                ->arrayNode('theme_stylesheets')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                    ->defaultValue(['%kernel.project_dir%/assets/styles/app.css'])
                    ->info(
                        'Les feuilles où l\'application déclare ses palettes, en plus de celle du paquet. Un bloc '
                        .'`[data-theme="…"]` y suffit à en ajouter une : le menu la propose, et le catalogue la '
                        .'connaît. Un fichier absent est ignoré.'
                    )
                ->end()
                ->arrayNode('theme_directories')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                    ->defaultValue(['%kernel.project_dir%/assets/styles/themes'])
                    ->info(
                        'Les dossiers dont chaque fichier `.css` est une palette. Le paquet les lit et les fait '
                        .'servir : y déposer un fichier suffit, il n\'y a ni feuille à modifier ni lien à poser. '
                        .'Un dossier absent est ignoré.'
                    )
                ->end()
                ->arrayNode('themes')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                    ->defaultValue([])
                    ->info(
                        'Les palettes réellement proposées, dans l\'ordre du menu. Vide : toutes celles que les '
                        .'feuilles déclarent. Nommer ici une palette qu\'aucune feuille ne déclare arrête la '
                        .'compilation.'
                    )
                ->end()
            ->end()
        ;
    }

    /**
     * Ajoute les comportements du paquet à ceux que StimulusBundle balaye.
     *
     * Ce qui s'y trouve est enregistré sous le nom de son fichier : `theme_controller.js`
     * devient `theme`. L'application n'a donc ni entrée d'`importmap` à écrire, ni contrôleur
     * à enregistrer à la main.
     *
     * Le défaut de StimulusBundle — `assets/controllers` — cesse de s'appliquer dès qu'une
     * configuration nomme la clé. On le reprend donc quand l'application ne l'a pas nommée, et
     * on s'abstient quand elle l'a fait, pour ne pas lui imposer un chemin qu'elle a retiré.
     */
    public function prependExtension(ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['StimulusBundle'])) {
            return;
        }

        $named = false;

        foreach ($container->getExtensionConfig('stimulus') as $stimulus) {
            if (\is_array($stimulus) && isset($stimulus['controller_paths'])) {
                $named = true;
                break;
            }
        }

        $paths = $named ? [] : ['%kernel.project_dir%/assets/controllers'];
        $paths[] = $this->getPath().'/public/controllers';

        $container->prependExtensionConfig('stimulus', ['controller_paths' => $paths]);
    }

    /**
     * @param array<array-key, mixed> $config la configuration du bundle, telle que
     *                                        {@see self::configure()} la décrit
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $stylesheets = $this->stylesheets($config, $container);

        $container->setParameter(self::COOKIE_PARAMETER, $config['cookie_name'] ?? 'theme');
        $container->setParameter(self::STYLESHEETS_PARAMETER, $stylesheets['read']);
        $container->setParameter(self::LINKS_PARAMETER, $stylesheets['links']);
        $container->setParameter(self::THEMES_PARAMETER, $this->themes($config, $stylesheets['read']));

        $configurator->import('../config/services.php');

        if (class_exists(Command::class)) {
            $configurator->import('../config/console.php');
        }

        $configurator->services()->alias(
            Theme\ThemeStorage::class,
            'none' === ($config['theme_storage'] ?? 'cookie')
                ? Theme\NoThemeStorage::class
                : Theme\CookieThemeStorage::class,
        );
    }

    /**
     * Les feuilles à lire, celle du paquet en tête pour que l'application puisse redéclarer une
     * palette qu'elle porte.
     *
     * Deux origines, et elles ne se recouvrent pas. Les dossiers de palettes tiennent un fichier
     * par palette, que le paquet lit *et* fait servir. Les feuilles nommées une à une sont
     * seulement lues : l'application les sert déjà, et un second lien les téléchargerait deux
     * fois.
     *
     * Tout est déclaré au conteneur, présent ou non : déposer un fichier, ou créer le dossier
     * qui manquait, suffit alors à faire recompiler le catalogue.
     *
     * @param array<array-key, mixed> $config
     *
     * @return array{read: list<string>, links: list<string>}
     */
    private function stylesheets(array $config, ContainerBuilder $container): array
    {
        $read = [$this->getPath().'/public/tokens.css'];
        $links = [];

        $directories = [$this->getPath().'/public/themes'];

        /** @var list<string> $declared */
        $declared = $config['theme_directories'] ?? [];

        foreach ($declared as $directory) {
            /** @var string $resolved */
            $resolved = $container->getParameterBag()->resolveValue($directory);
            $directories[] = rtrim($resolved, '/');
        }

        foreach ($directories as $directory) {
            $container->addResource(new FileExistenceResource($directory));

            if (is_dir($directory)) {
                // Le dossier lui-même est suivi : un fichier déposé ou retiré recompile.
                $container->addResource(new DirectoryResource($directory, '/\.css$/'));
            }

            foreach (glob($directory.'/*.css') ?: [] as $path) {
                $link = $this->assetPath($path, $container);

                if (null === $link) {
                    throw new \InvalidArgumentException(\sprintf(
                        "%s est une palette qu'aucun chemin d'AssetMapper ne désigne : elle serait proposée dans le "
                        ."menu sans que le navigateur la reçoive.\nUn dossier de palettes vit sous les ressources du "
                        .'projet — %s —, ou sous le `public/` du paquet.',
                        $path,
                        $container->getParameter('kernel.project_dir').'/assets/',
                    ));
                }

                $read[] = $path;
                $links[] = $link;
            }
        }

        /** @var list<string> $named */
        $named = $config['theme_stylesheets'] ?? [];

        foreach ($named as $path) {
            /** @var string $resolved */
            $resolved = $container->getParameterBag()->resolveValue($path);
            $read[] = $resolved;
        }

        foreach ($read as $path) {
            $container->addResource(new FileExistenceResource($path));

            if (is_file($path)) {
                $container->addResource(new FileResource($path));
            }
        }

        return ['read' => $read, 'links' => array_values(array_filter($links))];
    }

    /**
     * Le chemin par lequel AssetMapper sert une feuille, ou `null` quand elle vit hors des deux
     * espaces qu'il connaît — le `public/` du paquet et les ressources du projet.
     */
    private function assetPath(string $path, ContainerBuilder $container): ?string
    {
        $inBundle = $this->getPath().'/public/';

        if (str_starts_with($path, $inBundle)) {
            $namespace = strtolower((string) preg_replace('/Bundle$/', '', $this->getName()));

            return 'bundles/'.$namespace.'/'.substr($path, \strlen($inBundle));
        }

        /** @var string $projectDirectory */
        $projectDirectory = $container->getParameter('kernel.project_dir');
        $inProject = $projectDirectory.'/assets/';

        return str_starts_with($path, $inProject) ? substr($path, \strlen($inProject)) : null;
    }

    /**
     * Les palettes du catalogue. Sans liste configurée, ce sont toutes celles que les feuilles
     * déclarent ; avec une liste, ce sont celles qu'elle nomme, dans son ordre.
     *
     * @param array<array-key, mixed> $config
     * @param list<string>            $stylesheets
     *
     * @return array<string, ?string>
     */
    private function themes(array $config, array $stylesheets): array
    {
        $declared = StylesheetThemes::labels(...$stylesheets);

        /** @var list<string> $wanted */
        $wanted = $config['themes'] ?? [];

        if ([] === $wanted) {
            return $declared;
        }

        if ([] !== $unknown = array_diff($wanted, array_keys($declared))) {
            throw new \InvalidArgumentException(\sprintf(
                'design_system.themes propose %s, qu\'aucune feuille ne déclare : le menu l\'offrirait sans que rien '
                ."ne repeigne. Les feuilles lues déclarent %s.\nFeuilles lues :\n  %s",
                '« '.implode(' », « ', $unknown).' »',
                [] === $declared ? 'aucune palette' : '« '.implode(' », « ', array_keys($declared)).' »',
                implode("\n  ", $stylesheets),
            ));
        }

        return array_replace(
            array_fill_keys($wanted, null),
            array_intersect_key($declared, array_flip($wanted)),
        );
    }
}
