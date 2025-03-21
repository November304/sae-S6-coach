<?php

namespace App\Controller\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Sportif;
use App\Repository\SeanceRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SportifController extends AbstractController{
    #[Route('/api/sportifs/me', name: 'api_get_sportif', methods: ['GET'])]
    public function getSportif(Security $security): JsonResponse
    {
        $sportif = $security->getUser();
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($sportif, JsonResponse::HTTP_OK, [], ['groups' => 'sportif:read']);
    }

    #[Route('/api/sportifs/seances', name: 'api_get_sportifs_seances', methods: ['GET'])]
    public function getMySeances(Security $security): JsonResponse
    {
        $sportif = $security->getUser();
        if (!$sportif || !$sportif instanceof Sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        $seances = $sportif->getSeances();
        $seancesPresentes = [];
        foreach ($seances as $seance) {
            foreach($seance->getPresences() as $presence){
                if($presence->getSportif() === $sportif && $presence->getPresent() === 'Présent'){
                    $seancesPresentes[] = $seance;
                    break;
                }
            }
        }   

        return $this->json($seancesPresentes, JsonResponse::HTTP_OK, [], ['groups' => 'seance:read']);
    }

    #[Route('/api/public/sportifs', name: 'api_add_sportif', methods: ['POST'])]
    public function addSportif(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $sportif = new Sportif();
        $sportif->setNom($data['nom'] ?? null);
        $sportif->setPrenom($data['prenom'] ?? null);
        $sportif->setEmail($data['email'] ?? null);
        $sportif->setPassword(password_hash($data['password'] ?? '', PASSWORD_BCRYPT));
        $sportif->setRoles(['ROLE_SPORTIF']);
        $sportif->setNiveauSportif($data['niveau_sportif'] ?? null);
        $sportif->setDateInscription(new \DateTime());

        try {
            $validator->validate($sportif);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($sportif);
        $em->flush();

        return $this->json([
            "message" => "L'utilisateur a bien été inscrit",
            "code" => JsonResponse::HTTP_CREATED
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/sportifs', name: 'api_update_sportif', methods: ['PUT', 'PATCH'])]
    public function updatesportif(Request $request,Security $security, EntityManagerInterface $em, ValidatorInterface $validator,UtilisateurRepository $userRepo): JsonResponse
    {
        $user = $security->getUser();
        
        if(!$user || !$user instanceof Sportif)
        {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);

        //Check sur l'email
        if (isset($data['email'])) {
            $email = $data['email'];
            $userWithSameEmail = $userRepo->findOneBy(['email' => $email]);
            if ($userWithSameEmail && $userWithSameEmail->getId() !== $user->getId()) {
                return $this->json(['error' => 'Cet email est déjà utilisé','code'=>JsonResponse::HTTP_BAD_REQUEST], JsonResponse::HTTP_BAD_REQUEST);
            }
        }

        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['niveau_sportif'])) {
            $user->setNiveauSportif($data['niveau_sportif']);
        }

        try {
            $validator->validate($user);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json($user, JsonResponse::HTTP_OK, [], ['groups' => 'sportif:read']);
    }

    #[Route('/api/sportifs/pwd', name: 'api_update_sportif_password', methods: ['PUT', 'PATCH'])]
    public function updateSportifPassword(Request $request,Security $security, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $user = $security->getUser();
        
        if(!$user || !$user instanceof Sportif)
        {
            return $this->json(['error' => 'Sportif non trouvé','code'=>JsonResponse::HTTP_NOT_FOUND], JsonResponse::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);

        if (isset($data['password'])) {
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
            $user->setPasswordChangedAt(new \DateTimeImmutable());
            $request->getSession()->invalidate();

            //TODO : Faudrait rajouter des trucs JWT pr rendre les tokens invalides
        }

        try {
            $validator->validate($user);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json(['message'=>'Le mot de passe a bien été modifié','code'=>JsonResponse::HTTP_OK], JsonResponse::HTTP_OK);
    }


    #[Route('/api/sportifs', name: 'api_delete_sportif', methods: ['DELETE'])]
    public function deletesportif(Security $security, EntityManagerInterface $em): JsonResponse
    {
        $sportif = $security->getUser();
        if (!$sportif instanceof Sportif) {
            return $this->json(['error' => 'Sportif non trouvé','code'=>JsonResponse::HTTP_NOT_FOUND], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($sportif);
        $em->flush();

        return $this->json([
            'message' => 'Sportif supprimé avec succès',
            'code' => JsonResponse::HTTP_OK
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/api/sportifs/stats', name: 'api_stats_sportif', methods: ['GET'])]
    public function getSportifStats(Request $request, Security $security,SeanceRepository $seanceRep,LoggerInterface $logger): JsonResponse
    {
        $sportif = $security->getUser();
        if (!$sportif instanceof Sportif) {
            return $this->json(['error' => 'Sportif non trouvé', 'code' => JsonResponse::HTTP_NOT_FOUND], JsonResponse::HTTP_NOT_FOUND);
        }

        $minDateParam = $request->query->get('min_date');
        $maxDateParam = $request->query->get('max_date');

        try {
            $minDate = new \DateTime($minDateParam);
            $maxDate = new \DateTime($maxDateParam);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Dates invalides','code' => JsonResponse::HTTP_BAD_REQUEST], JsonResponse::HTTP_BAD_REQUEST);
        }

        $seances= $seanceRep->createQueryBuilder('s')
            ->innerJoin('s.presences', 'p')
            ->where('s.date_heure BETWEEN :minDate AND :maxDate')
            ->andWhere('p.sportif = :sportif')
            ->andWhere('p.present = :present')
            ->setParameter('minDate', $minDate)
            ->setParameter('maxDate', $maxDate)
            ->setParameter('sportif', $sportif)
            ->setParameter('present', 'Présent')
            ->getQuery()
            ->getResult();

        $totalSeances = count($seances);
        $repParType = [];
        foreach ($seances as $seance) {
            $type = $seance->getTypeSeance();
            if (!isset($repParType[$type])) {
                $repParType[$type] = 0;
            }
            $repParType[$type]++;
        }

        $exerciceCounts = [];
        foreach ($seances as $seance) {
            foreach ($seance->getExercices() as $exercice) {
                $id = $exercice->getId();
                if (!isset($exerciceCounts[$id])) {
                    $exerciceCounts[$id] = [
                        'id' => $id,
                        'nom' => $exercice->getNom(),
                        'count' => 0,
                    ];
                }
                $exerciceCounts[$id]['count']++;
            }
        }

        usort($exerciceCounts, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        $topExercices = array_slice($exerciceCounts, 0, 3);

        $tempsTotal = 0;
        foreach ($seances as $seance) {
            $tempsTotal += $seance->getDureeEstimeeTotal();
        }

        return $this->json([
            'total_seances' => $totalSeances,
            'total_temps' => $tempsTotal,
            'repartition_par_type' => $repParType,
            'top_exercices' => array_values($topExercices)
        ], JsonResponse::HTTP_OK);
    }
}
