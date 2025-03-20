<?php

namespace App\Controller\Admin;

use App\Entity\Utilisateur;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RESPONSABLE')]
class UtilisateurCrudController extends AbstractCrudController
{
    //TODO : Faut pouvoir gérer les roles -> Mais en fait faut quand meme faire la création de coach/sportif pr les infos associés
    public static function getEntityFqcn(): string
    {
        return Utilisateur::class;
    }

    public function __construct(
        private UserPasswordHasherInterface $encoder
    ) {}

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['hashPassword'],
        ];
    }   

    public function configureCrud(Crud $crud): Crud
    {
        // Modification du titre principal
        return $crud
            ->setPageTitle('index', 'Responsables')
            ->setPageTitle('new', 'Créer un responsable')
            ->setPageTitle('edit', 'Modifier un responsable')
            ->setPageTitle('detail', 'Profil du responsable')
            ->setEntityLabelInSingular('Responsable')
            ->setEntityLabelInPlural('Responsables')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer un nouveau responsable')
                    ->setIcon('fa fa-plus');
           })
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            return;
        }
        $repository = $entityManager->getRepository(Utilisateur::class);
        $existingSportifs = $repository->findBy(['email' => $entityInstance->getEmail()]);
        
        if (count($existingSportifs) > 0) {
            $this->addFlash('danger', 'Un utilisateur avec cet email existe déjà.');
            return;
        }
        $entityInstance->setRoles(['ROLE_RESPONSABLE']);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Utilisateur) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }
        
        $repository = $entityManager->getRepository(Utilisateur::class);
        $existingSportifs = $repository->findBy(['email' => $entityInstance->getEmail()]);
        foreach ($existingSportifs as $sportif) {
            if ($sportif->getId() !== $entityInstance->getId()) {
                $this->addFlash('danger', "Un autre utilisateur avec cet email existe déjà.");
                return;
            }
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }


    public function configureFields(string $pageName): iterable
    {
        
        $fields = [
            TextField::new('nom')
                ->setLabel("Nom"),
            TextField::new('prenom')
                ->setLabel("Prénom"),
            EmailField::new('email')
                ->setLabel("Email"),
            ChoiceField::new('roles')
            ->setLabel('Rôles') 
            ->setChoices([
                'Responsable' => 'ROLE_RESPONSABLE', 
            ])
            ->allowMultipleChoices() 
            ->setValue(['ROLE_RESPONSABLE'])
            ->hideOnForm(), 
            
        ];
        if ($pageName === Crud::PAGE_DETAIL) {
            $fields = [
                 TextField::new('nomComplet', 'Responsable')
                    ->formatValue(function ($value, $entity) {
                        return $entity->getPrenom() . ' ' . strtoupper($entity->getNom());
                    })
                    ->setTemplatePath('admin/responsable/header_card.html.twig'),
                
                EmailField::new('email')
                    ->setLabel("Contact")
                    ->setTemplatePath('admin/responsable/contact_card.html.twig'),
            ];
        }
        if ($pageName == Crud::PAGE_NEW){
            $fields[] =  [TextField::new('password')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => [
                        'label' => 'Mot de passe',
                        'attr' => ['style' => 'max-width:40.5%;'] // Limite la largeur
                    ],
                    'second_options' => [
                        'label' => 'Confirmer le mot de passe',
                        'attr' => ['style' => 'max-width:40.5%;'] // Même largeur pour la confirmation
                    ],
                    'invalid_message' => 'Les mots de passe ne correspondent pas.',
                ])
                ->onlyOnForms()
                ->onlyWhenCreating(),
            ChoiceField::new('roles')
            ->setLabel('Rôles') 
            ->setChoices([
                'Responsable' => 'ROLE_RESPONSABLE', 
            ])
            ->allowMultipleChoices() 
            ->setValue(['ROLE_RESPONSABLE'])
            ->hideOnForm()
            ->hideOnIndex()
            ->hideOnDetail(), 
            
        ];
        }

        return $fields;
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {
        $formBuilder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        return $this->addPasswordEventListener($formBuilder);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        // Filtrage des utilisateurs avec le rôle ROLE_RESPONSABLE
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $qb->andWhere('entity.roles LIKE :role')
           ->setParameter('role', '%"ROLE_RESPONSABLE"%');

        return $qb;
    }

    private function addPasswordEventListener(FormBuilderInterface $formBuilder): FormBuilderInterface
    {
        return $formBuilder->addEventListener(FormEvents::POST_SUBMIT, $this->hashPassword());
    }

    private function hashPassword() {
        return function($event) {
            $form = $event->getForm();
            if (!$form->isValid()) {
                return;
            }
            $password = $form->get('password')->getData();
            if ($password === null) {
                return;
            }

            $hash = $this->encoder->hashPassword($event->getData(), $password);
            $form->getData()->setPassword($hash);
        };
    }    
}