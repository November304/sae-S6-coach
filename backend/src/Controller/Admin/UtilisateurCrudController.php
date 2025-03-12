<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;

class UtilisateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Modification du titre principal
        return $crud
            ->setPageTitle('index', 'Responsables')
            ->setPageTitle('new', 'Créer un Responsable')
            ->setPageTitle('edit', 'Modifier un Responsable');
    }

    public function configureFields(string $pageName): iterable
    {
        // Personnalisation des champs affichés
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('email'),
            TextField::new('nom'),
            TextField::new('prenom'),
            // Ajoutez d'autres champs selon votre entité
        ];
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        // Filtrage des utilisateurs avec le rôle ROLE_RESPONSABLE
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.roles LIKE :role')
           ->setParameter('role', '%"ROLE_RESPONSABLE"%');

        return $qb;
    }
}