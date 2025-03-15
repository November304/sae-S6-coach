<?php

namespace App\Controller\Admin;

use App\Entity\Exercice;
use App\Entity\Seance;
use App\Entity\Sportif;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/coach', routeName: 'coach_admin')]
#[IsGranted('ROLE_COACH')]
class DashboardCoachController extends AbstractDashboardController
{
    public function index(): Response
    {
        return $this->render('admin/coach-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestion coach');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Sportifs', 'fas fa-users', Sportif::class);

        yield MenuItem::section('Salle de sport');
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class)
            ->setController(SeanceCoachCrudController::class);
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);

    }
}
