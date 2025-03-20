<?php

namespace App\Controller\Admin;

use App\Entity\Seance;
use DateTime;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

#[IsGranted('ROLE_COACH')]
class SeanceCoachCrudController extends AbstractCrudController
{
    public function __construct(private SecurityBundleSecurity $security, private LoggerInterface $logger, private AdminUrlGenerator $adminUrlGenerator, private EntityManagerInterface $entityManager) {}

    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $appel = Action::new('appel', 'Faire l\'appel', 'fa fa-clipboard-check')
            ->linkToRoute('app_admin_seance_coach_appel', function (Seance $seance): array {
                return ['id' => $seance->getId()];
            })
            ->displayIf(function ($seance) {
                return ($seance instanceof Seance) && $seance->getStatut() === 'prévue';
            })
            ->setCssClass('btn btn-success btn-sm text-nowrap');

        $annuler = Action::new('annuler', 'Annuler', 'fa fa-times-circle')
            ->linkToRoute('app_coach_demande_annulation', function (Seance $seance): array {
                return ['id' => $seance->getId()];
            })
            ->displayIf(function ($seance) {
                return ($seance instanceof Seance) && $seance->getStatut() === 'prévue';
            })
            ->setCssClass('btn btn-danger btn-sm text-nowrap');

        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Planifier une séance')->setIcon('fa fa-plus');
            })
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_EDIT, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $appel)
            ->add(Crud::PAGE_DETAIL, $appel)
            ->add(Crud::PAGE_INDEX, $annuler)
            ->add(Crud::PAGE_DETAIL, $annuler)
            // Modification ici pour la gestion de l'action EDIT
            ->setPermission(Action::EDIT, 'ROLE_COACH')
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action
                    ->displayIf(static function (Seance $seance) {
                        return $seance->getStatut() === 'prévue';
                    });
            })
            ->update(Crud::PAGE_DETAIL, Action::EDIT, function (Action $action) {
                return $action
                    ->displayIf(static function (Seance $seance) {
                        return $seance->getStatut() === 'prévue';
                    });
            })
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE);
    }

    public function edit(AdminContext $context)
    {
        $seance = $context->getEntity()->getInstance();

        if (!in_array($seance->getStatut(), ['prévue'])) {
            $this->addFlash('error', 'Les séances annulées ou validées ne peuvent pas être modifiées.');
            return $this->redirectToRoute('admin', [
                'crudAction' => 'index',
                'crudControllerFqcn' => self::class,
            ]);
        }

        return parent::edit($context);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Seance) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        if (in_array($entityInstance->getStatut(), ['validée', 'annulée'])) {
            throw new AccessDeniedException('Une séance validée ou annulée ne peut plus être modifiée.');
        }

        $this->validateSeance($entityManager, $entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function redirectToAppel(AdminContext $context)
    {
        // Récupération de l'EntityDto dans le contexte
        $entityDto = $context->getEntity();
        if (!$entityDto) {
            $this->addFlash('error', 'Aucune séance sélectionnée');
            return $this->redirect(
                $context->getReferrer() ?? $this->adminUrlGenerator->setAction(Action::INDEX)->generateUrl()
            );
        }

        // Récupération de l'entité réelle
        $seance = $entityDto->getInstance();

        // Vérification que la séance est bien de type Seance et a le statut attendu
        if (!$seance instanceof Seance || $seance->getStatut() !== 'prévue') {
            $this->addFlash('warning', 'Action impossible sur cette séance');
            return $this->redirectToRoute('admin');
        }

        // Redirection vers la route de l'appel en transmettant l'ID de la séance
        return $this->redirect($this->generateUrl('app_admin_seance_coach_appel', [
            'id' => $seance->getId()
        ]));
    }



    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Vos séances')
            ->setPageTitle('new', 'Planifier une séance')
            ->setPageTitle('edit', 'Modifier une séance')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/form/seance_form_theme.html.twig']);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Seance) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $entityInstance->setCoach($this->security->getUser());
        $entityInstance->setStatut('prévue');

        $this->validateSeance($entityManager, $entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
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
                    'Avancé'       => 'avancé',
                ])
                ->setLabel("Niveau de la séance")
                ->setFormTypeOption('attr', [
                    'class' => 'niveau-seance-select',
                ]),
            AssociationField::new('sportifs')
                ->setLabel("Sportifs")
                ->setFormTypeOption('choice_label', function ($sportif) {
                    return $sportif->getNom() . ' (niveau ' . $sportif->getNiveauSportif() . ')';
                })
                ->setFormTypeOption('choice_attr', function ($sportif, $key, $value) {
                    return ['data-level' => $sportif->getNiveauSportif()];
                })
                ->setFormTypeOption('attr', [
                    'class' => 'sportifs-select',
                    'data-controller' => 'seance-sportifs'
                ])
                ->setHelp('<div id="sportifs-help"></div>'),
            AssociationField::new('exercices')
                ->setLabel("Exercices")
                ->setFormTypeOption('choice_label', 'nom')
                ->setFormTypeOption('choice_attr', function ($exercice) {
                    return ['data-duree' => $exercice->getDureeEstimee()];
                })
                ->setFormTypeOption('attr', [
                    'data-controller' => 'exercices-duree',
                ])
                ->setHelp('<div id="duree-preview" class="mt-2">Durée totale estimée : <strong>0 min</strong></div>'),
            IntegerField::new('dureeEstimeeTotal', 'Durée totale')
                ->setFormTypeOption('disabled', true)
                ->setFormTypeOption('attr', ['readonly' => true])
                ->formatValue(function ($value, $entity) {
                    return $this->formatDuration($value);
                })
                ->onlyOnIndex(),
            IntegerField::new('dureeEstimeeTotal', 'Durée totale')
                ->setFormTypeOption('disabled', true)
                ->setFormTypeOption('attr', ['readonly' => true])
                ->formatValue(function ($value, $entity) {
                    return $this->formatDuration($value);
                })
                ->onlyOnDetail(),
            ChoiceField::new('statut')
                ->setChoices([
                    'Prévue' => 'prévue',
                    'Validée' => 'validée',
                    'Annulée' => 'annulée',
                ])
                ->setLabel("Statut")
                ->setFormTypeOption('data', 'prévue')
                ->hideOnForm(),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            // Remplacer les champs standards par des champs avec templates personnalisés
            $fields = [
                TextField::new('themeSeance', 'Séance')
                    ->setTemplatePath('admin/seance/header_card.html.twig'),
                DateTimeField::new('dateHeure', 'Date et heure')
                    ->setTemplatePath('admin/seance/date_status_card.html.twig'),
                AssociationField::new('coach', 'Coach')
                    ->setTemplatePath('admin/seance/coach_card.html.twig'),
                AssociationField::new('sportifs', 'Sportifs')
                    ->setTemplatePath('admin/seance/sportifs_card.html.twig'),
                AssociationField::new('exercices', 'Exercices')
                    ->setTemplatePath('admin/seance/exercices_card.html.twig'),
            ];
        }

        return $fields;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.coach = :currentCoach')
            ->setParameter('currentCoach', $this->security->getUser());

        return $qb;
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

    private function validateSeance(EntityManagerInterface $entityManager, Seance $seance): void
    {
        $coach = $seance->getCoach();
        $dateHeure = $seance->getDateHeure();
        $dateHeureFin = DateTime::createFromInterface($dateHeure)->add(new \DateInterval('PT' . $seance->getDureeEstimeeTotal() . 'M'));

        $this->logger->info('dateHeure : ' . $dateHeure->format('Y-m-d H:i:s'));
        $this->logger->info('dateHeureFin : ' . $dateHeureFin->format('Y-m-d H:i:s'));

        //Verif si coach a déjà une séance
        $qb = $entityManager->createQueryBuilder();
        $qb->select('s')
            ->from(Seance::class, 's')
            ->where('s.coach = :coach')
            ->andWhere('s.date_heure BETWEEN :dateHeure AND :dateHeureFin')
            ->setParameter('coach', $coach)
            ->setParameter('dateHeure', $dateHeure)
            ->setParameter('dateHeureFin', $dateHeureFin)
            ->setMaxResults(1);
        $existingCoachSeance = $qb->getQuery()->getOneOrNullResult();

        if ($existingCoachSeance) {
            throw new \Exception("Le coach a déjà une séance programmée à cette heure.");
        }

        //Verif si sportif a déja séance
        foreach ($seance->getSportifs() as $sportif) {
            $qb = $entityManager->createQueryBuilder();
            $qb->select('s')
                ->from(Seance::class, 's')
                ->where(':sportif MEMBER OF s.sportifs')
                ->andWhere('s.date_heure BETWEEN :dateHeure AND :dateHeureFin')
                ->setParameter('sportif', $sportif)
                ->setParameter('dateHeure', $dateHeure)
                ->setParameter('dateHeureFin', $dateHeureFin)
                ->setMaxResults(1);
            $existingSportifSeance = $qb->getQuery()->getOneOrNullResult();
            if ($existingSportifSeance) {
                throw new \Exception("Le sportif " . $sportif->getNom() . " est déjà inscrit à une séance entre " . $dateHeure->format('H:i') . " et " . $dateHeureFin->format('H:i') . ".");
            }
        }

        $this->checkNiveauSportifs($seance);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addJsFile('/js/seance-form.js')
            ->addHtmlContentToBody('
                <style>
                    #seance-help-container div { margin-bottom: 0.5rem; }
                    #sportifs-help, #niveau-help, #duree-preview {
                        color:rgb(128, 128, 128);
                        font-size: 0.9em;
                    }
                    #sportifs-help strong, #niveau-help strong, #duree-preview strong {
                        color:rgb(128, 128, 128);
                    }
                    .invalid-feedback { color: red !important; }
                </style>
            ');
    }
}
