<?php

namespace App\Controller\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Exercice;
use App\Repository\ExerciceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ExerciceController extends AbstractController{

    //TODO : Voir si c'est nécessaire
    #[Route('/api/public/exercices', name: 'api_get_exercices', methods: ['GET'])]
    public function getExoList(ExerciceRepository $exoRepo): JsonResponse
    {
        $exos = $exoRepo->findAll();
        return $this->json($exos, JsonResponse::HTTP_OK, [], ['groups' => 'exercice:read']);
    }

    #[Route('/api/exercices/{id}', name: 'api_get_exercice', methods: ['GET'])]
    public function getExo(int $id, ExerciceRepository $exoRepo): JsonResponse
    {
        $exo = $exoRepo->find($id);
        if (!$exo) {
            return $this->json(['error' => 'Exercice non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($exo, JsonResponse::HTTP_OK, [], ['groups' => 'exercice:read']);
    }
}
