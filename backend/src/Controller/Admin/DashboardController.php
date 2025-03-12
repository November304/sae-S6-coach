<?php

namespace App\Controller\Admin;

use App\Entity\Coach;
use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\Sportif;
use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Backoffice');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Coach', 'fas fa-users', Coach::class);
        yield MenuItem::linkToCrud('Sportifs', 'fas fa-users', Sportif::class);
        yield MenuItem::linkToCrud('Responsable', 'fas fa-users', Utilisateur::class);

        yield MenuItem::section('Salle de sport');
        yield MenuItem::linkToCrud('Seances', 'fas fa-calendar-alt', Seance::class);
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);
        
        
    }
}
