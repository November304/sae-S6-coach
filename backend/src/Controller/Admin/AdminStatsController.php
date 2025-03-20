<?php

namespace App\Controller\Admin;

use App\Entity\Coach;
use App\Entity\Seance;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESPONSABLE')]
class AdminStatsController extends AbstractController
{
    private EntityManagerInterface $em;
    
    #[Route('/admin/stats', name: 'admin_stats')]
    public function index(Request $request, EntityManagerInterface $emi): Response
    {
        $this->em = $emi;
        
        // Récupération du paramètre de recherche et de la page courante
        $search = $request->query->get('search', '');
        $page = $request->query->getInt('page', 1);
        
        // Récupération des taux d'occupation paginés et filtrés
        $tauxOccupationData = $this->getTauxOccupationParSeance($search, $page);
        $pagination = [
            'total_records' => $tauxOccupationData['total'],
            'total_pages' => ceil($tauxOccupationData['total'] / 10),
            'current_page' => $page,
            'search' => $search,
        ];
        
        $stats = [
            'total_reservations' => $this->em->getRepository(Seance::class)->count([]),
            'reservations_mois' => $this->em->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(s.id)')
                ->where('s.date_heure BETWEEN :start AND :end')
                ->setParameter('start', new \DateTime('first day of this month'))
                ->setParameter('end', new \DateTime('last day of this month'))
                ->getQuery()
                ->getSingleScalarResult(),
            'utilisateurs_actifs' => $this->em->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('COUNT(DISTINCT sp.id)')
                ->join('s.sportifs', 'sp')
                ->getQuery()
                ->getSingleScalarResult(),
            'prochaines_seances' => $this->em->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->addSelect('c')
                ->leftJoin('s.coach', 'c')
                ->where('s.date_heure >= :now')
                ->setParameter('now', new \DateTime())
                ->orderBy('s.date_heure', 'ASC')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult(),
            'reservations_par_coach' => $this->em->getRepository(Seance::class)
                ->createQueryBuilder('s')
                ->select('c.nom as coach_nom, COUNT(s.id) as total')
                ->join('s.coach', 'c')
                ->groupBy('c.id')
                ->getQuery()
                ->getResult(),
            // On remplace ici la donnée par la version paginée
            'tauxOccupationParSeance' => $tauxOccupationData['data'],
            'tauxAbsenteisme' => $this->getTauxAbsenteisme(),
            'seances_populaires' => $this->getSeancesPopulaires(),
        ];

        return $this->render('admin/dashboard_stats.html.twig', [
            'stats' => $stats,
            'chart_data' => $this->getChartData(),
            'pagination' => $pagination,
        ]);
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

    private function getTauxOccupationParSeance(string $search = '', int $page = 1): array
    {
        $limit = 10;
        $seanceRepository = $this->em->getRepository(Seance::class);
        $queryBuilder = $seanceRepository->createQueryBuilder('s');

        if ($search !== '') {
            $queryBuilder
                ->where('s.themeSeance LIKE :search OR s.typeSeance LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Get total count
        $totalQuery = clone $queryBuilder;
        $total = $totalQuery->select('COUNT(s.id)')->getQuery()->getSingleScalarResult();

        // Apply pagination
        $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $seances = $queryBuilder->getQuery()->getResult();

        $tauxOccupation = [];
        foreach ($seances as $seance) {
            $maxPlaces = match($seance->getTypeSeance()) {
                'solo' => 1,
                'duo' => 2,
                'trio' => 3,
                default => 0,
            };
            $placesOccupees = $seance->getSportifs()->count();
            $taux = $maxPlaces > 0 ? round(($placesOccupees / $maxPlaces) * 100, 2) : 0;
            $tauxOccupation[] = [
                'seance' => $seance,
                'taux' => $taux,
            ];
        }

        // Sort by taux descending (for the current page only)
        usort($tauxOccupation, function ($a, $b) {
            return $b['taux'] <=> $a['taux'];
        });

        return [
            'data' => $tauxOccupation,
            'total' => $total,
        ];
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

            return round(($totalAbsences / ($totalSeances * 3)) * 100, 2); // Assuming max 3 sportifs per session
        }

        return 0;
    }

    private function getSeancesPopulaires(): array
    {
        $seanceRepository = $this->em->getRepository(Seance::class);
        $seances = $seanceRepository->createQueryBuilder('s')
            ->select('s.theme_seance as theme_seance, COUNT(s.id) as total_reservations')
            ->groupBy('s.theme_seance')
            ->orderBy('total_reservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $seances;
    }
}