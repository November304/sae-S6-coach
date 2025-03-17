<?php

namespace App\Controller\Admin;

use App\Entity\Coach;
use App\Entity\Exercice;
use App\Entity\FicheDePaie;
use App\Entity\Seance;
use App\Entity\Sportif;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private EntityManagerInterface $em
    ) {}

   public function index(): Response
    {
        // Page d'accueil simple qui redirige vers les différentes sections
        return $this->render('admin/index.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestion administrateur');
            
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fas fa-home');
        
        yield MenuItem::section('Utilisateurs')->setPermission('ROLE_RESPONSABLE');
        yield MenuItem::linkToCrud('Coachs', 'fas fa-users', Coach::class)->setPermission('ROLE_RESPONSABLE');
        yield MenuItem::linkToCrud('Sportifs', 'fas fa-users', Sportif::class)->setPermission('ROLE_RESPONSABLE');
        yield MenuItem::linkToCrud('Responsables', 'fas fa-users', Utilisateur::class)->setPermission('ROLE_RESPONSABLE');
        
        yield MenuItem::section('Salle de sport');
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class)
            ->setController(SeanceCrudController::class)
            ->setPermission('ROLE_RESPONSABLE');
        yield MenuItem::linkToCrud('Vos séances', 'fas fa-calendar-alt', Seance::class)
            ->setController(SeanceCoachCrudController::class)
            ->setPermission('ROLE_COACH');
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);

        yield MenuItem::section('Statistiques Responsables')->setPermission('ROLE_RESPONSABLE');
        yield MenuItem::linkToRoute('Dashboard Stats', 'fas fa-tachometer-alt', 'admin_stats')->setPermission('ROLE_RESPONSABLE');
        yield MenuItem::linkToCrud('Fiche de paie', 'fa fa-file-invoice', FicheDePaie::class)->setPermission('ROLE_RESPONSABLE');
    }
}