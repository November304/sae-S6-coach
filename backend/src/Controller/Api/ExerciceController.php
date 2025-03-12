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
    #[Route('/api/exercices', name: 'api_get_exercices', methods: ['GET'])]
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

    #[Route('/api/exercices', name: 'api_add_exercice', methods: ['POST'])]
    public function addExo(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $exo = new Exercice();
        $exo->setNom($data['nom'] ?? null);
        $exo->setDescription($data['description'] ?? null);
        $exo->setDureeEstimee($data['duree_estimee'] ?? 0);
        $exo->setDifficulte($data['difficulte'] ?? null);
        
        try {
            $validator->validate($exo);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($exo);
        $em->flush();

        return $this->json($exo, JsonResponse::HTTP_CREATED, [], ['groups' => 'exercice:write']);
    }

    #[Route('/api/exercices/{id}', name: 'api_update_exercice', methods: ['PUT', 'PATCH'])]
    public function updateExo(int $id, Request $request, ExerciceRepository $exoRepo, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $exo = $exoRepo->find($id);
        if (!$exo) {
            return $this->json(['error' => 'Exercice non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        
        if(isset($data['nom'])){
            $exo->setNom($data['nom']);
        }
        if(isset($data['description'])){
            $exo->setDescription($data['description']);
        }
        if(isset($data['duree_estimee'])){
            $exo->setDureeEstimee($data['duree_estimee']);
        }
        if(isset($data['difficulte'])){
            $exo->setDifficulte($data['difficulte']);
        }

        try {
            $validator->validate($exo);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json($exo, JsonResponse::HTTP_OK, [], ['groups' => 'exercice:write']);
    }

    #[Route('/api/exercices/{id}', name: 'api_delete_exercice', methods: ['DELETE'])]
    public function deleteExo(int $id, ExerciceRepository $exoRepo, EntityManagerInterface $em): JsonResponse
    {
        $exo = $exoRepo->find($id);
        if (!$exo) {
            return $this->json(['error' => 'Exercice non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($exo);
        $em->flush();

        return $this->json(['message' => 'Exercice supprimé avec succès'], JsonResponse::HTTP_OK);
    }
}
