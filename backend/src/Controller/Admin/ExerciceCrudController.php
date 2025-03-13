<?php

namespace App\Controller\Admin;

use App\Entity\Exercice;
use Dom\Text;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ExerciceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Exercice::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer un nouvel exercice')
                    ->setIcon('fa fa-plus');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Modification du titre principal
        return $crud
            ->setPageTitle('index', 'Exercices')
            ->setPageTitle('new', 'Créer un exercice')
            ->setPageTitle('edit', 'Modifier un exercice');
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nom', 'Nom'),
            TextField::new('description', 'Description'),
            IntegerField::new('duree_estimee', 'Durée estimée (en min)'),
            ChoiceField::new('difficulte', 'Difficulté')
                ->setChoices([
                    'Facile' => 'facile',
                    'Moyen' => 'moyen',
                    'Difficile' => 'difficile'
                ])
        ];
    }
}
