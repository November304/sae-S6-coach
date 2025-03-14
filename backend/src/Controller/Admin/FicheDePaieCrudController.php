<?php

namespace App\Controller\Admin;

use App\Entity\FicheDePaie;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use App\Repository\CoachRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;

class FicheDePaieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FicheDePaie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Fiches de paie')
            ->setPageTitle('new', 'Créer une fiche de paie')
            ->setPageTitle('edit', 'Modifier une fiche de paie');           
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer une nouvelle fiche de paie')
                    ->setIcon('fa fa-plus');
            });
    }

    
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('coach')
                ->setLabel("Coach")
                ->setFormTypeOption('choice_label', 'nom'),
            ChoiceField::new('periode', 'Période')
                ->setChoices(['Mois' => 'mois', 'Semaine' => 'semaine'])
                ->allowMultipleChoices(false),
            IntegerField::new('total_heures', 'Total heures'),
            MoneyField::new('montant_total', 'Montant total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false),

        ];
    }
    
}
