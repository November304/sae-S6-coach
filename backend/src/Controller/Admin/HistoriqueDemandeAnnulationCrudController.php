<?php

namespace App\Controller\Admin;

use App\Entity\DemandeAnnulation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESPONSABLE')]
class HistoriqueDemandeAnnulationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DemandeAnnulation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Historique de demande')
            ->setEntityLabelInPlural('Historique des demandes')
            ->setPageTitle('index', 'Historique des demandes d\'annulation')
            ->setPaginatorPageSize(10);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('seance', 'Séance');
        yield TextareaField::new('motif');
        yield DateTimeField::new('dateDemande', 'Date de la demande')
            ->setFormat('dd/MM/yyyy HH:mm');
        yield TextField::new('statut');
        yield AssociationField::new('responsable')
            ->formatValue(static function ($value, $entity) {
                return $entity->getResponsable() ? $entity->getResponsable()->getNomComplet() : '';
            });
        yield DateTimeField::new('dateTraitement', 'Date de traitement')
            ->setFormat('dd/MM/yyyy HH:mm');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    // Pour filtrer uniquement les demandes traitées
    public function createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters): \Doctrine\ORM\QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        
        $queryBuilder
            ->andWhere('entity.statut IN (:statuts)')
            ->setParameter('statuts', ['validée', 'refusée'])
            ->orderBy('entity.dateTraitement', 'DESC');
            
        return $queryBuilder;
    }
}