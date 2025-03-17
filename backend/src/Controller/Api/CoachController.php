<?php

namespace App\Controller\Api;

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
}
