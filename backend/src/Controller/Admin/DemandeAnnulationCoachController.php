<?php

namespace App\Controller\Admin;

use App\Entity\DemandeAnnulation;
use App\Entity\Seance;
use App\Form\DemandeAnnulationType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COACH')]
class DemandeAnnulationCoachController extends AbstractController
{
    #[Route('/coach/demande-annulation/{id}', name: 'app_coach_demande_annulation')]
    public function demandeAnnulation(
        Request $request,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
        Seance $seance
    ): Response {
        $existingDemande = $entityManager->getRepository(DemandeAnnulation::class)
            ->findOneBy(['seance' => $seance]);

        if ($existingDemande) {
            $this->addFlash('warning', 'Une demande d\'annulation a déjà été soumise pour cette séance.');

            $url = $adminUrlGenerator
                ->setController(SeanceCoachCrudController::class)
                ->setAction('index')
                ->generateUrl();

            return $this->redirect($url);
        }

        $demande = new DemandeAnnulation();
        $demande->setSeance($seance);
        $demande->setDateDemande(new DateTime());
        $demande->setStatut('en attente');

        $form = $this->createForm(DemandeAnnulationType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demande);
            $entityManager->flush();
            $this->addFlash('success', 'Votre demande d\'annulation a été enregistrée');

            $url = $adminUrlGenerator
                ->setController(SeanceCoachCrudController::class)
                ->setAction('index')
                ->generateUrl();

            return $this->redirect($url);
        }

        return $this->render('admin/annulation/demande_annulation.html.twig', [
            'form' => $form->createView(),
            'seance' => $seance,
        ]);
    }
}
