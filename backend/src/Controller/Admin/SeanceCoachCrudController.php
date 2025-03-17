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
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_COACH')]
class SeanceCoachCrudController extends AbstractCrudController
{

    public function __construct(private SecurityBundleSecurity $security, private LoggerInterface $logger){}

    public static function getEntityFqcn(): string
    {
        return Seance::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Planifier une séance')->setIcon('fa fa-plus');
            });
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
                ->setFormTypeOption('choice_label', function($sportif) {
                    return $sportif->getNom() . ' (niveau ' . $sportif->getNiveauSportif() . ')';
                })
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
            TextField::new('dureeEstimeeTotal', 'Durée totale')
                ->setFormTypeOption('disabled', true)
                ->setFormTypeOption('attr', ['readonly' => true])
                ->onlyOnForms()
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

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Seance) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        if ($entityInstance->getStatut() === 'validée') {
            throw new AccessDeniedException('Une séance validée ne peut plus être modifiée.');
        }

        $this->validateSeance($entityManager, $entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function validateSeance(EntityManagerInterface $entityManager, Seance $seance): void
    {
        $coach = $seance->getCoach();
        $dateHeure = $seance->getDateHeure();
        $dateHeureFin = DateTime::createFromInterface($dateHeure)->add(new \DateInterval('PT' . $seance->getDureeEstimeeTotal() . 'M'));
        
        $this->logger->info('dateHeure : ' . $dateHeure->format('Y-m-d H:i:s'));
        $this->logger->info('dateHeureFin : '.$dateHeureFin->format('Y-m-d H:i:s'));
        
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
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Seance) {
            parent::deleteEntity($entityManager, $entityInstance);
            return;
        }

        if ($entityInstance->getStatut() === 'validée') {
            throw new AccessDeniedException('Une séance validée ne peut pas être annulée.');
        }

        //TODO : Créer une demande d'annulation de séance vers les managers

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
