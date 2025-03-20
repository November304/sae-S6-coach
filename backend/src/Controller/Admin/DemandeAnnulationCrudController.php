<?php

namespace App\Controller\Admin;

use App\Entity\DemandeAnnulation;
use App\Entity\Seance;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESPONSABLE')]
class DemandeAnnulationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemandeAnnulation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Demande d\'annulation')
            ->setEntityLabelInPlural('Demandes d\'annulation')
            ->setPageTitle('index', 'Liste des demandes d\'annulation')
            ->setPaginatorPageSize(10); // Nombre d'éléments par page
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('seance', 'Séance');
        yield TextareaField::new('motif');
        yield DateTimeField::new('dateDemande', 'Date de la demande')
            ->hideOnForm()
            ->setFormat('dd/MM/yyyy HH:mm');
        yield TextField::new('statut')
            ->hideOnForm();
        yield AssociationField::new('responsable')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormTypeOption('disabled', true)
            ->formatValue(static function ($value, $entity) {
                return $entity->getResponsable() ? $entity->getResponsable()->getNomComplet() : '';
            });
        yield DateTimeField::new('dateTraitement', 'Date de traitement')
            ->hideOnForm()
            ->hideOnIndex()
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        $validerDemande = Action::new('validerDemande', 'Valider')
            ->linkToCrudAction('validerDemande')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (DemandeAnnulation $entity) {
                return $entity->getStatut() === 'en attente';
            });

        $refuserDemande = Action::new('refuserDemande', 'Refuser')
            ->linkToCrudAction('refuserDemande')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (DemandeAnnulation $entity) {
                return $entity->getStatut() === 'en attente';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $validerDemande)
            ->add(Crud::PAGE_INDEX, $refuserDemande)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function validerDemande(AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): Response
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $demande = $entityManager->getRepository(DemandeAnnulation::class)->find($id);
        
        if (!$demande) {
            throw $this->createNotFoundException('Demande non trouvée');
        }
        
        $demande->setStatut('validée');
        $demande->setResponsable($this->getUser());
        $demande->setDateTraitement(new DateTime());
        
        $seance = $demande->getSeance();
        $seance->setStatut('annulée');
        
        $entityManager->flush();
        
        $this->addFlash('success', 'La demande d\'annulation a été validée');
        
        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl());
    }

    public function refuserDemande(AdminUrlGenerator $adminUrlGenerator, EntityManagerInterface $entityManager): Response
    {
        $id = $this->getContext()->getRequest()->query->get('entityId');
        $demande = $entityManager->getRepository(DemandeAnnulation::class)->find($id);
        
        if (!$demande) {
            throw $this->createNotFoundException('Demande non trouvée');
        }
        
        $demande->setStatut('refusée');
        $demande->setResponsable($this->getUser());
        $demande->setDateTraitement(new DateTime());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'La demande d\'annulation a été refusée');
        
        return $this->redirect($adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl());
    }

    // Pour filtrer les demandes en attente seulement
    public function createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        $queryBuilder
            ->andWhere('entity.statut = :statut')
            ->setParameter('statut', 'en attente')
            ->orderBy('entity.dateDemande', 'DESC');
            
        return $queryBuilder;
    }
}