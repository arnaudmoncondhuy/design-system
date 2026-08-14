<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * La vitrine : chaque composant du catalogue, dans chacun de ses états, sur une seule page.
 *
 * Elle n'est joignable que si l'application importe `config/routes.php` du paquet, et il lui
 * revient de ne le faire qu'en développement — une page qui montre tout le catalogue n'a rien
 * à faire sur un site en production.
 */
final readonly class StyleguideController
{
    public function __construct(private Environment $twig)
    {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->render('@DesignSystem/styleguide.html.twig'));
    }
}
