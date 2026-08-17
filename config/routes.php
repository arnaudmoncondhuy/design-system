<?php

declare(strict_types=1);

use ArnaudMoncondhuy\DesignSystem\Controller\StyleguideController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/**
 * La route de la vitrine.
 *
 * Elle ne s'ajoute pas d'elle-même : l'application l'importe, et décide sous quel chemin, dans
 * quel environnement et pour qui. La déclaration ci-dessous, dans `config/routes/dev/`, la
 * réserve au développement ; une application qui veut l'ouvrir au-delà lui préfère son propre
 * contrôleur, placé derrière un droit :
 *
 *     design_system:
 *         resource: '@DesignSystemBundle/config/routes.php'
 *         type: php
 *         prefix: /styleguide
 *
 * Le chemin ne commence pas par `_` : sous ce préfixe, une application Symfony laisse la route
 * hors de son pare-feu.
 */
return static function (RoutingConfigurator $routes): void {
    $routes->add('design_system_styleguide', '')
        ->controller(StyleguideController::class)
        ->methods(['GET'])
    ;
};
