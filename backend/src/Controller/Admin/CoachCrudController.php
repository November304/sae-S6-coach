<?php

namespace App\Controller\Admin;

use App\Entity\Coach;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;


class CoachCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coach::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer un nouveau coach')
                    ->setIcon('fa fa-plus');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Modification du titre principal
        return $crud
            ->setPageTitle('index', 'Coachs')
            ->setPageTitle('new', 'Créer un coach')
            ->setPageTitle('edit', 'Modifier un coach');
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('nom')
                ->setLabel("Nom"),
            TextField::new('prenom')
                ->setLabel("Prénom"),
            EmailField::new('email')
                ->setLabel("Email"),
            TextField::new('password')
                ->setFormType(PasswordType::class)
                ->setLabel("Mot de passe")
                ->onlyOnForms(),
            MoneyField::new('tarif_horaire')
                ->setCurrency('EUR')
                ->setLabel("Tarif horaire"),
            CollectionField::new('specialites')
                ->setLabel("Spécialités"),
        ];
    }
    
}
