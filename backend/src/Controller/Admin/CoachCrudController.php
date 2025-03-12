<?php

namespace App\Controller\Admin;

use App\Entity\Coach;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

class CoachCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Coach::class;
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
                ->setLabel("Mot de passe"),
            MoneyField::new('tarif_horaire')
                ->setCurrency('EUR')
                ->setLabel("Tarif horaire"),
            CollectionField::new('specialites')
                ->setLabel("Spécialités"),
        ];
    }
    
}
