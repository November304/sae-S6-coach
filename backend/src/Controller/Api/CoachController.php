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
