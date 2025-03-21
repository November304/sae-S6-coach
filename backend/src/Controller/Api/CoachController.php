<?php

namespace App\Controller\Api;

use App\Repository\CoachRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class CoachController extends AbstractController
{
    #[Route('/api/public/coaches', name: 'api_pub_get_coaches', methods: ['GET'])]
    public function getPublicCoachList(CoachRepository $coachRepo): JsonResponse
    {
        $coaches = $coachRepo->findAll();
        return $this->json($coaches, JsonResponse::HTTP_OK, [], ['groups' => 'coach:public:read']);
    }

    #[Route('/api/coaches', name: 'api_get_coaches', methods: ['GET'])]
    public function getCoachList(CoachRepository $coachRepo): JsonResponse
    {
        $coaches = $coachRepo->findAll();
        return $this->json($coaches, JsonResponse::HTTP_OK, [], ['groups' => 'coach:read']);
    }
}
