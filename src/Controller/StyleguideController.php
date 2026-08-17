<?php

declare(strict_types=1);

namespace ArnaudMoncondhuy\DesignSystem\Controller;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * La vitrine : chaque composant du catalogue, dans chacun de ses états, sur une seule page.
 *
 * Elle n'est joignable que si l'application la monte, et c'est à elle d'en régler l'accès :
 * réservée au développement, ou ouverte aux seules personnes qui construisent les écrans. Une
 * page qui montre tout le catalogue n'a rien à faire devant le premier venu.
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
