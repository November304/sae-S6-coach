<?php

namespace App\Controller\Admin;

use App\Entity\Sportif;
use App\Entity\Utilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Doctrine\ORM\EntityManagerInterface;



class SportifCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Sportif::class;
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

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action
                    ->setLabel('Créer un nouveau sportif')
                    ->setIcon('fa fa-plus');
            })
        ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ->add(Crud::PAGE_EDIT, Action::DETAIL);
    }

    public function configureCrud(Crud $crud): Crud
    {
        // Modification du titre principal
        return $crud
            ->setPageTitle('index', 'Sportifs')
            ->setPageTitle('new', 'Créer un sportif')
            ->setPageTitle('edit', 'Modifier un sportif')
            ->setPageTitle('detail', 'Profil du sportif')
            ->setEntityLabelInSingular('Sportif')
            ->setEntityLabelInPlural('Sportifs')
            ->setDefaultSort(['nom' => 'ASC']);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Sportif) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }
        
        $repository = $entityManager->getRepository(Utilisateur::class);
        $existingSportifs = $repository->findBy(['email' => $entityInstance->getEmail()]);
        
        if (count($existingSportifs) > 0) {
            $this->addFlash('danger', "Un utilisateur avec cet email existe déjà.");
            return;
        }
        
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Sportif) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }
        
        $repository = $entityManager->getRepository(Utilisateur::class);
        $existingSportifs = $repository->findBy(['email' => $entityInstance->getEmail()]);
        foreach ($existingSportifs as $sportif) {
            if ($sportif->getId() !== $entityInstance->getId()) {
                if ($sportif->getId() !== $entityInstance->getId()) {
                    $this->addFlash('danger', "Un autre utilisateur avec cet email existe déjà.");
                    return;
                }
            }
        }
        
        parent::updateEntity($entityManager, $entityInstance);
    }


    public function configureFields(string $pageName): iterable
    {
        $fields = [
            // Champs communs pour toutes les pages
            TextField::new('nom')->setLabel("Nom"),
            TextField::new('prenom')->setLabel("Prénom"),
            EmailField::new('email')->setLabel("Email"),
            DateField::new('date_inscription')
                ->setLabel("Date inscription")
                ->setFormTypeOption('data', new \DateTimeImmutable()),
            ChoiceField::new('niveau_sportif')
                ->setChoices([
                    'Débutant' => 'débutant',
                    'Intérmédiaire' => 'intermédiaire',
                    'Avancée' => 'avancé',
                ])
                ->setLabel("Niveau sportif"),
        ];

        if ($pageName === Crud::PAGE_DETAIL) {
            $fields = [
                TextField::new('nomComplet', 'Sportif')
                    ->formatValue(function ($value, $entity) {
                        return $entity->getPrenom() . ' ' . strtoupper($entity->getNom());
                    })
                    ->setTemplatePath('admin/sportif/header_card.html.twig'),
                
                EmailField::new('email')
                    ->setLabel("Contact")
                    ->setTemplatePath('admin/sportif/contact_card.html.twig'),
                
                DateField::new('date_inscription')
                    ->setLabel("Date inscription")
                    ->setTemplatePath('admin/sportif/date_inscription_card.html.twig'),
                
                ChoiceField::new('niveau_sportif')
                    ->setChoices([
                        'Débutant' => 'débutant',
                        'Intérmédiaire' => 'intermédiaire',
                        'Avancée' => 'avancé',
                    ])
                    ->setLabel("Niveau sportif")
                    ->setTemplatePath('admin/sportif/niveau_sportif_card.html.twig'),
            ];
        }

        // Ajout des champs de mot de passe uniquement pour la création
        if ($pageName === Crud::PAGE_NEW) {
            $fields[] = TextField::new('password')
                ->setFormType(RepeatedType::class)
                ->setFormTypeOptions([
                    'type' => PasswordType::class,
                    'first_options' => [
                        'label' => 'Mot de passe',
                        'attr' => ['style' => 'max-width:40.5%;']
                    ],
                    'second_options' => [
                        'label' => 'Confirmer le mot de passe',
                        'attr' => ['style' => 'max-width:40.5%;']
                    ],
                    'invalid_message' => 'Les mots de passe ne correspondent pas.',
                ]);
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
