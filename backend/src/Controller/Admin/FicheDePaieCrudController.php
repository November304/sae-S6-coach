<?php

namespace App\Controller\Admin;

use App\Entity\FicheDePaie;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use App\Repository\CoachRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[IsGranted('ROLE_RESPONSABLE')]
class FicheDePaieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FicheDePaie::class;
    }

    private CoachRepository $coachRepository;

    public function __construct(CoachRepository $coachRepository)
    {
        $this->coachRepository = $coachRepository;
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
            })
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('js/fiche_de_paie-form.js');
    }
    
    public function configureFields(string $pageName): iterable
    {
        //On recup les coachs pr leurs tarifs horaires auto calculés
        $coaches = $this->coachRepository->findAll();
        $tarifs = [];
        foreach ($coaches as $coach) {
            $tarifs[$coach->getId()] = $coach->getTarifHoraire();
        }
        $tarifsJson = json_encode($tarifs);

        $fields = [
            AssociationField::new('coach')
                ->setLabel("Coach")
                ->setFormTypeOption('choice_label', 'nom')
                ->formatValue(function ($value, $entity) {
                    return $entity->getCoach()->getNom();
                })
                ->setFormTypeOption('attr', ['class' => 'coach-field']),

            ChoiceField::new('periode', 'Période')
                ->setChoices(['Mois' => 'mois', 'Semaine' => 'semaine'])
                ->allowMultipleChoices(false),

            IntegerField::new('total_heures', 'Total heures')
                ->setFormTypeOption('attr', ['class' => 'total-heures-field']),

            MoneyField::new('montant_total', 'Montant total')
                ->setCurrency('EUR')
                ->setStoredAsCents(false)
                ->setFormTypeOption('attr', ['readonly' => true, 'class' => 'montant-total-field']),

            HiddenField::new('tarifs_coach', 'Tarifs Coach')
                ->setFormTypeOption('mapped', false)
                ->setFormTypeOption('attr', [
                    'class'    => 'tarifs-coach-field',
                    'value'    => $tarifsJson,
                    'disabled' => true,
                ])
                ->onlyOnForms()
        ];

          if ($pageName === Crud::PAGE_DETAIL) {
            // Templates personnalisés pour la page détail
            $fields =  [
                    IdField::new('id')
                        ->setTemplatePath('admin/fiche_de_paie/header_card.html.twig')
                        ->hideOnForm(),
                    AssociationField::new('coach')
                        ->setLabel("Coach")
                        ->setTemplatePath('admin/fiche_de_paie/coach_card.html.twig'),
                    ChoiceField::new('periode', 'Période')
                        ->setTemplatePath('admin/fiche_de_paie/periode_card.html.twig'),
                    IntegerField::new('totalHeures', 'Total heures')
                        ->setTemplatePath('admin/fiche_de_paie/heures_card.html.twig'),
                    MoneyField::new('montantTotal', 'Montant total')
                        ->setCurrency('EUR')
                        ->setStoredAsCents(false)
                        ->setTemplatePath('admin/fiche_de_paie/montant_card.html.twig'),
                ];
            }
        return $fields;

    }

    
}
