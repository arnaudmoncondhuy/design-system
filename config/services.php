<?php

declare(strict_types=1);

use ArnaudMoncondhuy\DesignSystem\Controller\StyleguideController;
use ArnaudMoncondhuy\DesignSystem\DesignSystemBundle;
use ArnaudMoncondhuy\DesignSystem\Theme\CookieThemeStorage;
use ArnaudMoncondhuy\DesignSystem\Theme\NoThemeStorage;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeCatalog;
use ArnaudMoncondhuy\DesignSystem\Twig\ThemeExtension;
use Symfony\Component\HttpFoundation\RequestStack;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Les deux adaptateurs sont montés tous les deux ; c'est l'alias posé par le bundle qui désigne
 * celui que l'application reçoit. Le conteneur retire l'autre à la compilation.
 */
return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container): void {
    $services = $container->services();

    // Le catalogue est un paramètre figé à la compilation : aucune feuille n'est relue à l'exécution.
    $services->set(ThemeCatalog::class)
        ->args([param(DesignSystemBundle::THEMES_PARAMETER)]);

    $services->set(CookieThemeStorage::class)
        ->args([service(RequestStack::class), param(DesignSystemBundle::COOKIE_PARAMETER), service(ThemeCatalog::class)]);

    $services->set(NoThemeStorage::class);

    $services->set(ThemeExtension::class)
        ->args([service(ArnaudMoncondhuy\DesignSystem\Theme\ThemeStorage::class), service(ThemeCatalog::class)])
        ->tag('twig.extension');

    // La vitrine n'est joignable que si l'application importe `config/routes.php`.
    $services->set(StyleguideController::class)
        ->args([service('twig')])
        ->tag('controller.service_arguments');
};
