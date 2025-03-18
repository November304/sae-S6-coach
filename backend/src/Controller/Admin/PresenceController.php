<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use App\Entity\Sportif;
use App\Entity\Presence;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COACH')]
class PresenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    #[Route('/admin/seance-coach/{id}/appel', name: 'app_admin_seance_coach_appel')]
    public function appel(Seance $seance): Response
    {
        // Vérifier que l'entité a bien été convertie et que la séance existe
        if (!$seance instanceof Seance) {
            $this->addFlash('error', 'Séance introuvable.');
            return $this->redirectToRoute('admin');
        }
        
        // Vérifier que le coach est bien celui assigné à la séance
        if ($seance->getCoach() !== $this->security->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le coach de cette séance');
        }
        
        // Vérifier que la séance est en statut "prévue"
        if ($seance->getStatut() !== 'prévue') {
            $this->addFlash('error', 'L\'appel ne peut être fait que pour une séance prévue');
            return $this->redirectToRoute('app_admin_seance_coach');
        }
        
        // Récupérer tous les sportifs de la séance
        $sportifs = $seance->getSportifs();

        return $this->render('admin/appel/faire_appel.html.twig', [
            'seance'   => $seance,
            'sportifs' => $sportifs,
        ]);
    }


    #[Route('/admin/seance-coach/{id}/valider-appel', name: 'app_admin_seance_coach_valider_appel', methods: ['POST'])]
    public function validerAppel(Seance $seance, Request $request): Response
    {
        // Vérifier que le coach est bien celui assigné à la séance
        if ($seance->getCoach() !== $this->security->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le coach de cette séance');
        }

        // Récupérer les données du formulaire
        $presences = $request->request->get('presence', []);
        
        // Enregistrer les présences
        foreach ($seance->getSportifs() as $sportif) {
            $sportifId = $sportif->getId();
            $present = isset($presences[$sportifId]);
            
            $this->enregistrerPresence($seance, $sportif, $present);
        }

        // Mettre à jour le statut de la séance
        $seance->setStatut('validée');
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'appel a été enregistré avec succès');
        return $this->redirectToRoute('app_admin_seance_coach');
    }

    private function enregistrerPresence(Seance $seance, Sportif $sportif, bool $present): void
    {
        // Vérifier si une présence existe déjà pour ce sportif dans cette séance
        $existingPresence = $this->entityManager->getRepository(Presence::class)->findOneBy([
            'seance' => $seance,
            'sportif' => $sportif
        ]);
        
        if ($existingPresence) {
            $existingPresence->setPresent($present);
        } else {
            $presence = new Presence();
            $presence->setSeance($seance);
            $presence->setSportif($sportif);
            $presence->setPresent($present);
            $this->entityManager->persist($presence);
        }
    }
}