<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
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

    /**
     * Deux clés, et chacune ne sert qu'à restreindre : sans configuration, le paquet monte la
     * lecture par cookie et n'expose aucune route.
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
            ->end()
        ;
    }

    /**
     * @param array<array-key, mixed> $config la configuration du bundle, telle que
     *                                        {@see self::configure()} la décrit
     */
    public function loadExtension(array $config, ContainerConfigurator $configurator, ContainerBuilder $container): void
    {
        $container->setParameter(self::COOKIE_PARAMETER, $config['cookie_name'] ?? 'theme');

        $configurator->import('../config/services.php');

        $configurator->services()->alias(
            Theme\ThemeStorage::class,
            'none' === ($config['theme_storage'] ?? 'cookie')
                ? Theme\NoThemeStorage::class
                : Theme\CookieThemeStorage::class,
        );
    }
}
