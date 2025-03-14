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

    private function getChartData(): array
    {
        // Données pour le graphique des 12 derniers mois
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = new \DateTime("first day of -$i months");
            $end = new \DateTime("last day of -$i months");

            $count = $this->em->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s.id)')
                ->where('s.date_heure BETWEEN :start AND :end')
                ->setParameter('start', $month->format('Y-m-d'))
                ->setParameter('end', $end->format('Y-m-d'))
                ->getQuery()
                ->getSingleScalarResult();

            $data['labels'][] = $month->format('M Y');
            $data['values'][] = $count;
        }

        return $data;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Gestion administrateur');
            
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Accueil', 'fas fa-home');
        
        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Coachs', 'fas fa-users', Coach::class);
        yield MenuItem::linkToCrud('Sportifs', 'fas fa-users', Sportif::class);
        yield MenuItem::linkToCrud('Responsables', 'fas fa-users', Utilisateur::class);

        yield MenuItem::section('Salle de sport');
        yield MenuItem::linkToCrud('Séances', 'fas fa-calendar-alt', Seance::class);
        yield MenuItem::linkToCrud('Exercices', 'fas fa-dumbbell', Exercice::class);

        yield MenuItem::section('Statistiques Responsables');
        yield MenuItem::linkToRoute('Dashboard Stats', 'fas fa-tachometer-alt', 'admin_stats');
        yield MenuItem::linkToCrud('Fiche de paie', 'fa fa-file-invoice', FicheDePaie::class);
    }
}