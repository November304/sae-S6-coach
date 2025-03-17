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
           })
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ->add(Crud::PAGE_EDIT, Action::DETAIL);
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
       $fields = [
            TextField::new('nom', 'Nom'),
            TextField::new('description', 'Description'),
            IntegerField::new('duree_estimee', 'Durée estimée (en min)')
                ->formatValue(function ($value, $entity) {
                    return $this->formatDuration($value);
                }),
            ChoiceField::new('difficulte', 'Difficulté')
                ->setChoices([
                    'Facile' => 'facile',
                    'Moyen' => 'moyen',
                    'Difficile' => 'difficile'
                ])
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields =  [
                TextField::new('nom', 'Nom')
                    ->setTemplatePath('admin/exercice/header_card.html.twig'),
                TextField::new('description', 'Description')
                    ->setTemplatePath('admin/exercice/description_card.html.twig'),
                IntegerField::new('dureeEstimee', 'Durée estimée')
                    ->formatValue(function ($value, $entity) {
                        return $this->formatDuration($value);
                    })
                    ->setTemplatePath('admin/exercice/duree_card.html.twig'),
                ChoiceField::new('difficulte', 'Difficulté')
                    ->setTemplatePath('admin/exercice/difficulte_card.html.twig'),
            ];
        }

        return $fields;
    }

    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return sprintf('%dh%02d', $hours, $remainingMinutes);
    }
}
