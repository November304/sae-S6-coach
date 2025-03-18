<?php

namespace App\Controller\Api;

use ApiPlatform\Validator\Exception\ValidationException;
use ApiPlatform\Validator\ValidatorInterface;
use App\Entity\Sportif;
use App\Repository\SportifRepository;
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
        return $this->json($sportif->getSeances(), JsonResponse::HTTP_OK, [], ['groups' => 'seance:read']);
    }

    #[Route('/api/public/sportifs', name: 'api_add_sportif', methods: ['POST'])]
    public function addSportif(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, SportifRepository $spRep): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $email = $data['email'] ?? null;
        if ($email !== null && $spRep->findOneBy(['email' => $email])) {
            return $this->json(['error' => 'Cet email est déjà utilisé'], JsonResponse::HTTP_CONFLICT);
        }
        
        $sportif = new Sportif();
        $sportif->setNom($data['nom'] ?? null);
        $sportif->setPrenom($data['prenom'] ?? null);
        $sportif->setEmail($email);
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

        return $this->json(["message"=>"L'utilisateur a bien été inscrit"], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/sportifs', name: 'api_update_sportif', methods: ['PUT', 'PATCH'])]
    public function updatesportif(Request $request,Security $security, EntityManagerInterface $em, ValidatorInterface $validator,LoggerInterface $logger): JsonResponse
    {
        $user = $security->getUser();
        
        if(!$user || !$user instanceof Sportif)
        {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), true);
        if (isset($data['nom'])) {
            $user->setNom($data['nom']);
        }
        if (isset($data['prenom'])) {
            $user->setPrenom($data['prenom']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['password'])) {
            $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));
            $user->setPasswordChangedAt(new \DateTimeImmutable());
            $request->getSession()->invalidate();

            //TODO : Faudrait rajouter des trucs JWT pr rendre les tokens invalides
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

    #[Route('/api/sportifs', name: 'api_delete_sportif', methods: ['DELETE'])]
    public function deletesportif(Security $security, EntityManagerInterface $em): JsonResponse
    {
        $sportif = $security->getUser();
        if (!$sportif instanceof Sportif) {
            return $this->json(['error' => 'Sportif non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $em->remove($sportif);
        $em->flush();

        return $this->json(['message' => 'Sportif supprimé avec succès'], JsonResponse::HTTP_OK);
    }
}
