<?php

declare(strict_types=1);

use ArnaudMoncondhuy\DesignSystem\Bridge\DoctorCommand;
use ArnaudMoncondhuy\DesignSystem\DesignSystemBundle;
use ArnaudMoncondhuy\DesignSystem\Theme\ThemeCatalog;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * Le docteur n'est monté que si l'application a la console : le paquet doit s'installer dans
 * une application qui n'en a pas.
 */
return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container): void {
    $container->services()
        ->set(DoctorCommand::class)
        ->args([
            param('kernel.project_dir'),
            param(DesignSystemBundle::COOKIE_PARAMETER),
            param(DesignSystemBundle::STYLESHEETS_PARAMETER),
            service(ThemeCatalog::class),
        ])
        ->tag('console.command')
    ;
};
