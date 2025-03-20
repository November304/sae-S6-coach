<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_RESPONSABLE')]
class AdminStatsController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/stats', name: 'admin_stats')]
    public function stats(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('q', '');

        // Récupération des statistiques globales
        $stats = [
            'total_reservations' => $this->em->getRepository(Seance::class)->count([]),
            'reservations_mois' => $this->getReservationsMois(),
            'utilisateurs_actifs' => $this->getUtilisateursActifs(),
            'tauxAbsenteisme' => $this->getTauxAbsenteisme(),
            'seances_populaires' => $this->getSeancesPopulaires(),
            'evolution_labels' => $this->getEvolutionReservations()['labels'],
            'evolution_values' => $this->getEvolutionReservations()['values'],
        ];


        // Requête avec recherche
        $queryBuilder = $this->em->getRepository(Seance::class)->createQueryBuilder('s')
            ->leftJoin('s.coach', 'c')
            ->addSelect('c')
            ->orderBy('s.date_heure', 'DESC');

        if (!empty($search)) {
            $queryBuilder
                ->andWhere('s.theme_seance LIKE :search OR c.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $query = $queryBuilder->getQuery();

        $seances = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/dashboard_stats.html.twig', [
            'stats' => $stats,
            'seances' => $seances,
            'search' => $search,
        ]);
    }

    private function getEvolutionReservations(): array
    {
        $result = $this->em->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select("YEAR(s.date_heure) as annee, MONTH(s.date_heure) as mois, COUNT(s.id) as total")
            ->groupBy('annee, mois')
            ->orderBy('annee, mois', 'ASC')
            ->getQuery()
            ->getResult();

        $labels = [];
        $values = [];

        foreach ($result as $row) {
            $labels[] = $row['mois'] . '/' . $row['annee'];
            $values[] = $row['total'];
        }

        return ['labels' => $labels, 'values' => $values];
    }


    private function getReservationsMois(): int
    {
        return $this->em->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.date_heure BETWEEN :start AND :end')
            ->setParameter('start', new \DateTime('first day of this month'))
            ->setParameter('end', new \DateTime('last day of this month'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUtilisateursActifs(): int
    {
        return $this->em->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select('COUNT(DISTINCT sp.id)')
            ->join('s.sportifs', 'sp')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getTauxAbsenteisme(): float
    {
        $seanceRepository = $this->em->getRepository(Seance::class);
        $totalSeances = $seanceRepository->count([]);
        $totalAbsences = 0;

        if ($totalSeances > 0) {
            $seances = $seanceRepository->findAll();
            foreach ($seances as $seance) {
                $totalAbsences += $seance->getSportifs()->count() - $seance->getPresences()->count();
            }
            return round(($totalAbsences / ($totalSeances * 3)) * 100, 2);
        }

        return 0;
    }

    private function getSeancesPopulaires(): array
    {
        return $this->em->getRepository(Seance::class)
            ->createQueryBuilder('s')
            ->select('s.theme_seance as theme, COUNT(s.id) as total_reservations')
            ->groupBy('s.theme_seance')
            ->orderBy('total_reservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}
