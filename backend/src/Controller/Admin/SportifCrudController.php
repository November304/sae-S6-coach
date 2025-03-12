<?php

namespace App\Controller\Admin;

use App\Entity\Sportif;
use Doctrine\ORM\Query\Expr\Select;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class SportifCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sportif::class;
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
            DateField::new('date_inscription')
                ->setLabel("Date inscription"),
            ChoiceField::new('niveau_sportif')
                ->setChoices([
                    'Débutant' => 'débutant',
                    'Intérmédiaire' => 'intermédiaire',
                    'Avancée' => 'avancé',
                ])
                ->setLabel("Niveau sportif"),
        ];
    }
    
}
