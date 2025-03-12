<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

use App\Repository\CoachRepository;

class SeanceCrudController extends AbstractCrudController
{
    private $coachRepository;

    public function __construct(CoachRepository $coachRepository)
    {
        $this->coachRepository = $coachRepository;
    }
    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        $coaches = $this->coachRepository->findAll();
        $choices = [];
        foreach ($coaches as $coach) {
            $choices[$coach->getNom()] = $coach->getId();
        }


        return [
            DateTimeField::new('date_heure')->setLabel("Date et heure"),
            TextField::new('type_seance')->setLabel("Type de séance"),
            TextField::new('theme_seance')->setLabel("Thème de la séance"),
            ChoiceField::new('coach_id')
                ->setChoices($choices)
                ->setLabel("Coach"),
            AssociationField::new('sportifs')->setLabel("Sportifs"),
            AssociationField::new('exercices')->setLabel("Exercices"),
            ChoiceField::new('niveau_seance')
                ->setChoices([
                    'Débutant' => 'débutant',
                    'Intérmédiaire' => 'intermédiaire',
                    'Avancée' => 'avancé',
                ])
                ->setLabel("Niveau de la séance"),
            ChoiceField::new('statut')
                ->setChoices([
                    'Prévue' => 'prévue',
                    'Validée' => 'validée',
                    'Annulée' => 'annulée',
                ])
                ->setLabel("Statut"),
        ];
    }
    
}
