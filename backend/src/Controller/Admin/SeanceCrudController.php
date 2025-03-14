<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use Doctrine\ORM\EntityManagerInterface;


class SeanceCrudController extends AbstractCrudController
{
    
    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer une nouvelle séance')
                    ->setIcon('fa fa-plus');
            });
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Séances')
            ->setPageTitle('new', 'Créer une séance')
            ->setPageTitle('edit', 'Modifier une séance')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form/seance_form_theme.html.twig']);
    }
    
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('coach')
                ->setLabel("Coach")
                ->setFormTypeOption('choice_label', 'nom')
                ->formatValue(function ($value, $entity) {
                    return $entity->getCoach()->getNom();
                }),
            DateTimeField::new('date_heure')->setLabel("Date et heure"),
            TextField::new('theme_seance')->setLabel("Thème de la séance"),
            ChoiceField::new('type_seance')
                ->setChoices([
                    'Solo' => 'solo',
                    'Duo' => 'duo',
                    'Trio' => 'trio',
                ])
                ->setLabel("Type de séance")
                ->setFormTypeOption('attr', [
                    'class' => 'type-seance-select',
                    'onChange' => 'updateSportifLimit(this.value)'
                ]),
            ChoiceField::new('niveau_seance')
                ->setChoices([
                    'Débutant'      => 'débutant',
                    'Intermédiaire' => 'intermédiaire',
                    'Avancée'       => 'avancée',
                ])
                ->setLabel("Niveau de la séance")
                ->setFormTypeOption('attr', [
                    'class' => 'niveau-seance-select',
                ]),
            AssociationField::new('sportifs')
                ->setLabel("Sportifs")
                ->setFormTypeOption('choice_label', 'nom')
                ->setFormTypeOption('choice_attr', function($sportif, $key, $value) {
                    return ['data-level' => $sportif->getNiveauSportif()];
                })
                ->setFormTypeOption('attr', [
                    'class' => 'sportifs-select',
                    'data-controller' => 'seance-sportifs'
                ])
                ->setHelp('<div id="sportifs-help"></div>'),
            AssociationField::new('exercices')
                ->setLabel("Exercices")
                ->setFormTypeOption('choice_label', 'nom'),
            ChoiceField::new('statut')
                ->setChoices([
                    'Prévue' => 'prévue',
                    'Validée' => 'validée',
                    'Annulée' => 'annulée',
                ])
                ->setLabel("Statut")
                ->setFormTypeOption('data', 'prévue'),
        ];
    }


    private function checkNiveauSportifs(Seance $seance): void
    {
        $sessionNiveau = $seance->getNiveauSeance();
        foreach ($seance->getSportifs() as $sportif) {
            // On vérifie que le niveau du sportif correspond exactement à celui de la séance
            if ($sportif->getNiveauSportif() !== $sessionNiveau) {
                throw new \Exception(
                    "Le sportif " . $sportif->getNom() . " (niveau " . $sportif->getNiveauSportif() . 
                    ") ne peut pas participer à une séance de niveau " . $sessionNiveau . "."
                );
            }
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Seance) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }
        
        // Vérification du niveau des sportifs
        $this->checkNiveauSportifs($entityInstance);
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Seance) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }
        
        // Vérification du niveau des sportifs
        $this->checkNiveauSportifs($entityInstance);
        
        parent::updateEntity($entityManager, $entityInstance);
    }


    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('/js/seance-form.js')
            ->addHtmlContentToBody('
                <style>
                    #seance-help-container div { margin-bottom: 0.5rem; }
                    #sportifs-help, #niveau-help {
                        color:rgb(128, 128, 128);
                        font-size: 0.9em;
                    }
                    #sportifs-help strong, #niveau-help strong {
                        color:rgb(128, 128, 128);
                    }
                    .invalid-feedback { color: red !important; }
                </style>
            ');
    }
}