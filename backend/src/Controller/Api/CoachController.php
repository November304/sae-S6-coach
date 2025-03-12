<?php

namespace App\Controller;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Coach;
use App\Repository\CoachRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CoachController extends AbstractController
{
    #[Route('/api/coaches', name: 'api_get_coaches', methods: ['GET'])]
    public function getCoachList(CoachRepository $coachRepo): JsonResponse
    {
        $coaches = $coachRepo->findAll();
        return $this->json($coaches, JsonResponse::HTTP_OK, [], ['groups' => 'coach:read']);
    }

    #[Route('/api/coaches/{id}', name: 'api_get_coach', methods: ['GET'])]
    public function getCoach(int $id, CoachRepository $coachRepo): JsonResponse
    {
        $coach = $coachRepo->find($id);
        if (!$coach) {
            return $this->json(['error' => 'Coach non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($coach, JsonResponse::HTTP_OK, [], ['groups' => 'coach:read']);
    }

    #[Route('/api/coaches', name: 'api_add_coach', methods: ['POST'])]
    public function addCoach(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $coach = new Coach();
        $coach->setPrenom($data['prenom'] ?? null);
        $coach->setNom($data['nom'] ?? null);
        $coach->setEmail($data['email'] ?? null);
        $coach->setPassword(password_hash($data['password'] ?? '', PASSWORD_BCRYPT));
        $coach->setRoles(['ROLE_COACH']);
        $coach->setSpecialites($data['specialites'] ?? []);
        $coach->setTarifHoraire($data['tarif_horaire'] ?? 0);
        
        try {
            $validator->validate($coach);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($coach);
        $em->flush();

        return $this->json($coach, JsonResponse::HTTP_CREATED, [], ['groups' => 'coach:write']);
    }

    #[Route('/api/coaches/{id}', name: 'api_update_coach', methods: ['PUT', 'PATCH'])]
    public function updateCoach(int $id, Request $request, CoachRepository $coachRepo, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $coach = $coachRepo->find($id);
        if (!$coach) {
            return $this->json(['error' => 'Coach non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if (isset($data['prenom'])) $coach->setPrenom($data['prenom']);
        if (isset($data['nom'])) $coach->setNom($data['nom']);
        if (isset($data['email'])) $coach->setEmail($data['email']);
        if (isset($data['password'])) $coach->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        if (isset($data['specialites'])) $coach->setSpecialites($data['specialites']);
        if (isset($data['tarif_horaire'])) $coach->setTarifHoraire($data['tarif_horaire']);

        try {
            $validator->validate($coach);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json($coach, JsonResponse::HTTP_OK, [], ['groups' => 'coach:write']);
    }

    #[Route('/api/coaches/{id}', name: 'api_delete_coach', methods: ['DELETE'])]
    public function deleteCoach(int $id, CoachRepository $coachRepo, EntityManagerInterface $em): JsonResponse
    {
        $coach = $coachRepo->find($id);
        if (!$coach) {
            return $this->json(['error' => 'Coach non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($coach);
        $em->flush();

        return $this->json(['message' => 'Coach supprimé avec succès'], JsonResponse::HTTP_OK);
    }
}
