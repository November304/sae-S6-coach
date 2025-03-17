<?php

namespace App\Controller\Api;

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
}
