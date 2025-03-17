<?php

namespace App\Controller\Admin;

use App\Entity\FicheDePaie;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COACH')]
class FicheDePaieCoachCrudController extends AbstractCrudController
{
    private SecurityBundleSecurity $security;
    private CoachRepository $coachRepository;

    public function __construct(SecurityBundleSecurity $security, CoachRepository $coachRepository)
    {
        $this->security = $security;
        $this->coachRepository = $coachRepository;
    }

    public static function getEntityFqcn(): string
    {
        return FicheDePaie::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Vos fiches de paie')
            ->setPageTitle('new', 'Créer votre fiche de paie')
            ->setPageTitle('edit', 'Modifier votre fiche de paie');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer votre fiche de paie')
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

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof FicheDePaie) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }
        // Affecter le coach connecté automatiquement
        $entityInstance->setCoach($this->security->getUser());
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, 
                                              \EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection $fields, 
                                              \EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        // Restreindre l'affichage aux fiches de paie du coach connecté
        $qb->andWhere('entity.coach = :currentCoach')
           ->setParameter('currentCoach', $this->security->getUser());

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        // Récupérer le coach connecté pour son tarif horaire
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\Coach) {
            throw new \LogicException('The user is not a coach.');
        }
        $coach = $this->coachRepository->find($user->getId());
        $tarifs = [$coach->getId() => $coach->getTarifHoraire()];
        $tarifsJson = json_encode($tarifs);

        $fields = [
            // Le champ coach peut être masqué sur les formulaires car il est affecté automatiquement
            AssociationField::new('coach')
                ->setLabel("Coach")
                ->setFormTypeOption('choice_label', 'nom')
                ->formatValue(function ($value, $entity) {
                    return $entity->getCoach()->getNom();
                })
                ->setFormTypeOption('attr', ['class' => 'coach-field'])
                ->onlyOnDetail(),
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
            $fields = [
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
