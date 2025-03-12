<?php

namespace App\Controller;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Sportif;
use App\Repository\SportifRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SportifController extends AbstractController{
    #[Route('/api/sportifs', name: 'api_get_sportifs', methods: ['GET'])]
    public function getSportifList(SportifRepository $sportifRepo): JsonResponse
    {
        $sportifs = $sportifRepo->findAll();
        return $this->json($sportifs, JsonResponse::HTTP_OK, [], ['groups' => 'sportif:read']);
    }

    #[Route('/api/sportifs/{id}', name: 'api_get_sportif', methods: ['GET'])]
    public function getSportif(int $id, SportifRepository $sportifRepo): JsonResponse
    {
        $sportif = $sportifRepo->find($id);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        return $this->json($sportif, JsonResponse::HTTP_OK, [], ['groups' => 'sportif:read']);
    }

    #[Route('/api/sportifs', name: 'api_add_sportif', methods: ['POST'])]
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
        if (isset($data['date_inscription'])) {
            $sportif->setDateInscription(new \DateTime($data['date_inscription']));
        } else {
            $sportif->setDateInscription(new \DateTime());
        }

        try {
            $validator->validate($sportif);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->persist($sportif);
        $em->flush();

        return $this->json($sportif, JsonResponse::HTTP_CREATED, [], ['groups' => 'sportif:write']);
    }

    #[Route('/api/sportifs/{id}', name: 'api_update_sportif', methods: ['PUT', 'PATCH'])]
    public function updatesportif(int $id, Request $request, SportifRepository $sportifRepo, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $sportif = $sportifRepo->find($id);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['nom'])) {
            $sportif->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $sportif->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $sportif->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $sportif->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
        }
        if (isset($data['niveau_sportif'])) {
            $sportif->setNiveauSportif($data['niveau_sportif']);
        }
        if (isset($data['date_inscription'])) {
            $sportif->setDateInscription(new \DateTime($data['date_inscription']));
        }

        try {
            $validator->validate($sportif);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json($sportif, JsonResponse::HTTP_OK, [], ['groups' => 'sportif:write']);
    }

    #[Route('/api/sportifs/{id}', name: 'api_delete_sportif', methods: ['DELETE'])]
    public function deletesportif(int $id, SportifRepository $sportifRepo, EntityManagerInterface $em): JsonResponse
    {
        $sportif = $sportifRepo->find($id);
        if (!$sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($sportif);
        $em->flush();

        return $this->json(['message' => 'Sportif supprimé avec succès'], JsonResponse::HTTP_OK);
    }
}
