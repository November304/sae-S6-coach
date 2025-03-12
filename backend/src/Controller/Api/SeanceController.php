<?php

namespace App\Controller;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Seance;
use App\Repository\SeanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SeanceController extends AbstractController {
    #[Route('/api/seances', name: 'api_get_seances', methods: ['GET'])]
    public function getSeanceList(SeanceRepository $seanceRepo): JsonResponse
    {
        $seances = $seanceRepo->findAll();
        return $this->json($seances, JsonResponse::HTTP_OK, [], ['groups' => 'seance:read']);
    }

    #[Route('/api/seances/{id}', name: 'api_get_seance', methods: ['GET'])]
    public function getSeance(int $id, SeanceRepository $seanceRepo): JsonResponse
    {
        $seance = $seanceRepo->find($id);
        if (!$seance) {
            return $this->json(['error' => 'Seance non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($seance, JsonResponse::HTTP_OK, [], ['groups' => 'seance:read']);
    }

    #[Route('/api/seances', name: 'api_add_seance', methods: ['POST'])]
    public function addSeance(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $seance = new Seance();
        $seance->setDateHeure(new \DateTime($data['date_heure'] ?? 'now'));
        $seance->setTypeSeance($data['type_seance'] ?? null);
        $seance->setThemeSeance($data['theme_seance'] ?? null);
        $seance->setNiveauSeance($data['niveau_seance'] ?? null);
        $seance->setCoachId($data['coach_id'] ?? null);
        if (isset($data['sportifs']) && is_array($data['sportifs'])) {
            foreach ($data['sportifs'] as $sportif) {
                $seance->addSportif($sportif);
            }
        }
        if (isset($data['exercices']) && is_array($data['exercices'])) {
            foreach ($data['exercices'] as $exercice) {
                $seance->addExercice($exercice);
            }
        }
        $seance->setStatut($data['statut'] ?? null);
        
        try {
            $validator->validate($seance);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($seance);
        $em->flush();

        return $this->json($seance, JsonResponse::HTTP_CREATED, [], ['groups' => 'seance:write']);
    }

    #[Route('/api/seances/{id}', name: 'api_update_seance', methods: ['PUT', 'PATCH'])]
    public function updateSeance(int $id, Request $request, SeanceRepository $seanceRepo, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $seance = $seanceRepo->find($id);
        if (!$seance) {
            return $this->json(['error' => 'Seance non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['date_heure'])) {
            $seance->setDateHeure(new \DateTime($data['date_heure']));
        }
        if (isset($data['type_seance'])) {
            $seance->setTypeSeance($data['type_seance']);
        }
        if (isset($data['theme_seance'])) {
            $seance->setThemeSeance($data['theme_seance']);
        }
        if (isset($data['niveau_seance'])) {
            $seance->setNiveauSeance($data['niveau_seance']);
        }
        if (isset($data['coach_id'])) {
            $seance->setCoachId($data['coach_id']);
        }
        if (isset($data['sportifs']) && is_array($data['sportifs'])) {
            foreach ($seance->getSportifs() as $sportif) {
                $seance->removeSportif($sportif);
            }
            foreach ($data['sportifs'] as $sportif) {
                $seance->addSportif($sportif);
            }
        }
        if (isset($data['exercices']) && is_array($data['exercices'])) {
            foreach ($seance->getExercices() as $exercice) {
                $seance->removeExercice($exercice);
            }
            foreach ($data['exercices'] as $exercice) {
                $seance->addExercice($exercice);
            }
        }
        if (isset($data['statut'])) {
            $seance->setStatut($data['statut']);
        }

        try {
            $validator->validate($seance);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json($seance, JsonResponse::HTTP_OK, [], ['groups' => 'seance:write']);
    }

    #[Route('/api/seances/{id}', name: 'api_delete_seance', methods: ['DELETE'])]
    public function deleteSeance(int $id, SeanceRepository $seanceRepo, EntityManagerInterface $em): JsonResponse
    {
        $seance = $seanceRepo->find($id);
        if (!$seance) {
            return $this->json(['error' => 'Seance non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($seance);
        $em->flush();

        return $this->json(['message' => 'Seance supprimé avec succès'], JsonResponse::HTTP_OK);
    }
}
