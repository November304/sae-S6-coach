<?php
namespace App\Controller\Admin;

use App\Entity\DemandeAnnulation;
use App\Entity\Seance;
use App\Form\DemandeAnnulationType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemandeAnnulationCoachController extends AbstractController
{
    #[Route('/coach/demande-annulation/{id}', name: 'app_coach_demande_annulation')]
    public function demandeAnnulation(Request $request, EntityManagerInterface $entityManager, Seance $seance): Response
    {
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
            
            return $this->redirectToRoute('admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => 'App\Controller\Admin\SeanceCoachCrudController',
            ]);
        }
        
        return $this->render('admin/annulation/demande_annulation.html.twig', [
            'form' => $form->createView(),
            'seance' => $seance,
            'ea' => [
                'crud' => [
                    'entity' => [
                        'primary_key_value' => $seance->getId(),
                        'instance' => $seance,
                    ]
                ]
            ],
        ]);
    }
}