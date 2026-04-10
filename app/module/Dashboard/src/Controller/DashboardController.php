<?php

declare(strict_types=1);

namespace Dashboard\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class DashboardController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel([
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Dashboard', 'url' => null],
            ],
        ]);
    }

    // Principal 1
    public function principal1Item1Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 1.1',
            'descripcion' => 'Este es un módulo de demostración',
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 1', 'url' => null],
                ['nombre' => 'Principal 1.1', 'url' => null],
            ],
        ]);
    }

    public function principal1Item2Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 1.2',
            'descripcion' => 'Este es un módulo de demostración',
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 1', 'url' => null],
                ['nombre' => 'Principal 1.2', 'url' => null],
            ],
        ]);
    }

    // Principal 2
    public function principal2Item1Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 2.1',
            'descripcion' => 'Este es un módulo de demostración',
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 2', 'url' => null],
                ['nombre' => 'Principal 2.1', 'url' => null],
            ],
        ]);
    }

    public function principal2Item2Action()
    {
        return new ViewModel([
            'titulo' => 'Principal 2.2',
            'descripcion' => 'Este es un módulo de demostración',
            'breadcrumbs' => [
                ['nombre' => 'Inicio', 'url' => '/'],
                ['nombre' => 'Principal 2', 'url' => null],
                ['nombre' => 'Principal 2.2', 'url' => null],
            ],
        ]);
    }
}
