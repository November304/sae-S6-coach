<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESPONSABLE')]
class AdminStatsController extends AbstractController
{

    public function __construct(private EntityManagerInterface $em, private SeanceRepository $seanceRepo)
    {

    }

    #[Route('/stats', name: 'admin_stats')]
    public function stats(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('q', '');

        // Récupération des statistiques globales
        $stats = [
            'total_reservations' => $this->seanceRepo->count([]),
            'reservations_mois' => $this->getReservationsMois(),
            'utilisateurs_actifs' => $this->getUtilisateursActifs(),
            'tauxAbsenteisme' => $this->getTauxAbsenteisme(),
            'seances_populaires' => $this->getSeancesPopulaires(),
            'evolution_labels' => $this->getEvolutionReservations()['labels'],
            'evolution_values' => $this->getEvolutionReservations()['values'],
            'taux_occupation' => $this->getTauxOccupationParCoachEtCreneau(),
        ];


        // Requête avec recherche
        $queryBuilder = $this->seanceRepo->createQueryBuilder('s')
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
        $result = $this->seanceRepo
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
        return $this->seanceRepo
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
        return $this->seanceRepo
            ->createQueryBuilder('s')
            ->select('COUNT(DISTINCT sp.id)')
            ->join('s.sportifs', 'sp')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getTauxAbsenteisme(): float
    {
        $seanceRepository = $this->seanceRepo;
        //On recupère que les séances validées
        $seances = $seanceRepository->findBy(['statut' => 'validée']);
        $totalAbsences = 0;
        $totalReservations = 0;

        foreach ($seances as $seance) {
            $totalAbsences += $seance->getPresences()->filter(fn($presence) => $presence->getPresent() === 'Absent')->count();
            $totalReservations += $seance->getSportifs()->count();
        }

        if ($totalReservations > 0) {
            return round(($totalAbsences / $totalReservations) * 100, 2);
        }

        return 0;
    }

    private function getTauxOccupationParCoachEtCreneau(): array
    {
        $seances = $this->seanceRepo
            ->createQueryBuilder('s')
            ->select('s, c.nom as coach, HOUR(s.date_heure) as heure')
            ->join('s.coach', 'c')
            ->where('s.statut = :statut')
            ->setParameter('statut', 'validée')
            ->getQuery()
            ->getResult();
        
        $coaches = [];
        $heures = [];
        $tauxParCoachEtHeure = [];
        
        foreach ($seances as $row) {
            $coach = $row['coach'];
            $heure = $row['heure'];
            
            if (!in_array($coach, $coaches)) {
                $coaches[] = $coach;
            }
            if (!in_array($heure, $heures)) {
                $heures[] = $heure;
            }
            
            // Initialiser le tableau pour ce coach et cette heure
            if (!isset($tauxParCoachEtHeure[$coach][$heure])) {
                $tauxParCoachEtHeure[$coach][$heure] = [
                    'somme' => 0,
                    'nombre' => 0
                ];
            }
            
            $seance = $row[0];
            $tauxOccupation = $seance->getTauxOccupation();
            
            $tauxParCoachEtHeure[$coach][$heure]['somme'] += $tauxOccupation;
            $tauxParCoachEtHeure[$coach][$heure]['nombre']++;
        }
        
        sort($heures);
        
        $donnees = [];
        foreach ($coaches as $coach) {
            $donneeCoach = ['coach' => $coach, 'data' => []];
            foreach ($heures as $heure) {
                if (isset($tauxParCoachEtHeure[$coach][$heure]) && $tauxParCoachEtHeure[$coach][$heure]['nombre'] > 0) {
                    $moyenne = $tauxParCoachEtHeure[$coach][$heure]['somme'] / $tauxParCoachEtHeure[$coach][$heure]['nombre'];
                    $donneeCoach['data'][$heure] = round($moyenne, 2);
                } else {
                    $donneeCoach['data'][$heure] = 0;
                }
            }
            $donnees[] = $donneeCoach;
        }
        
        return [
            'coaches' => $coaches,
            'heures' => $heures,
            'donnees' => $donnees
        ];
    }

    private function getSeancesPopulaires(): array
    {
        return $this->seanceRepo
            ->createQueryBuilder('s')
            ->select('s.theme_seance as theme, COUNT(s.id) as total_reservations')
            ->groupBy('s.theme_seance')
            ->orderBy('total_reservations', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}
